!(function () {
  "use strict";

  // Aliases for window objects
  const wpElement = window.wp.element;
  const wpHtmlEntities = window.wp.htmlEntities;
  const wpI18n = window.wp.i18n;
  const wcBlocksRegistry = window.wc.wcBlocksRegistry;
  const wcSettings = window.wc.wcSettings;

  // Yoco billing name validation for the Blocks checkout.
  //
  // Uses the wc/store/validation store so errors render under their respective
  // fields (billing_first_name, billing_last_name, etc.), matching how core
  // WooCommerce Blocks displays per-field validation. Because the Blocks
  // checkout-frontend checks `hasValidationErrors()` before submitting, setting
  // a validation error here blocks the place-order request client-side — the
  // PHP `woocommerce_rest_checkout_process_payment_with_context` callback in
  // Gateway.php stays as a last-line defence for direct API consumers only.
  //
  // Validates on every cart/payment state change, so:
  //  - typing into the name field updates/clears the per-field error live,
  //  - switching TO Yoco with existing bad names shows both errors,
  //  - switching AWAY from Yoco clears the Yoco-set errors.
  //
  // Clears only the field IDs we actually set (tracked in `ourErrors`) so we
  // don't step on Blocks' own "required" / format-level validation.
  (function () {
    if (!window.wp || !window.wp.data) {
      return;
    }

    const wpData = window.wp.data;
    const subscribe = wpData.subscribe;
    const select = wpData.select;
    const dispatch = wpData.dispatch;

    if (typeof subscribe !== "function") {
      return;
    }

    const YOCO_METHOD = "class_yoco_wc_payment_gateway";
    const VALIDATION_STORE = "wc/store/validation";
    const CHECKOUT_STORE = "wc/store/checkout";
    // Regex must match the PHP pattern in Gateway.php::validate_billing_name_chars.
    const NAME_PATTERN = /^[A-Za-zÀ-ÖØ-öø-ÿ\s'\-]+$/u;
    // Single-character version used to identify offending characters.
    const ALLOWED_CHAR_PATTERN = /[A-Za-zÀ-ÖØ-öø-ÿ\s'\-]/u;

    // Tracks which validation error IDs this script currently owns, so we can
    // clear only those and not touch validation errors set by Blocks itself.
    // `let` because clearAllYocoFieldErrors reassigns it to an empty object.
    let ourErrors = {};

    // Mirrors Gateway.php::get_invalid_chars_message so messages match exactly.
    const getInvalidCharsMessage = (value) => {
      const chars = Array.from(value || "");
      const invalid = [];
      for (const c of chars) {
        if (!ALLOWED_CHAR_PATTERN.test(c) && invalid.indexOf(c) === -1) {
          invalid.push(c);
        }
      }
      if (invalid.length === 0) {
        return "";
      }
      const template =
        wpI18n && typeof wpI18n.__ === "function"
          ? wpI18n.__(
              'Field may only contain letters, spaces, hyphens, and apostrophes. Please remove: " %1$s "',
              "yoco-payment-gateway",
            )
          : 'Field may only contain letters, spaces, hyphens, and apostrophes. Please remove: " %1$s "';
      return wpI18n && typeof wpI18n.sprintf === "function"
        ? wpI18n.sprintf(template, invalid.join(" "))
        : template.replace("%1$s", invalid.join(" "));
    };

    const clearAllYocoFieldErrors = () => {
      const validationDispatch = dispatch(VALIDATION_STORE);
      if (
        !validationDispatch ||
        typeof validationDispatch.clearValidationError !== "function"
      ) {
        return;
      }
      // Snapshot the IDs and reset ourErrors BEFORE dispatching, so the
      // re-entrant subscribe call (clearValidationError synchronously notifies
      // listeners) sees ourErrors empty, returns early, and doesn't recurse
      // back into clearAllYocoFieldErrors → stack overflow.
      const ids = Object.keys(ourErrors);
      ourErrors = {};
      ids.forEach((id) => {
        validationDispatch.clearValidationError(id);
      });
    };

    subscribe(() => {
      const paymentStore = select("wc/store/payment");
      const cartStore = select("wc/store/cart");
      const validationStore = select(VALIDATION_STORE);
      const validationDispatch = dispatch(VALIDATION_STORE);
      if (!paymentStore || !cartStore || !validationDispatch) {
        return;
      }

      const activeMethod =
        typeof paymentStore.getActivePaymentMethod === "function"
          ? paymentStore.getActivePaymentMethod()
          : null;

      // Yoco not active → drop only the errors we own and bail. Guard against
      // running on every unrelated store tick when there's nothing to clean up.
      if (activeMethod !== YOCO_METHOD) {
        if (Object.keys(ourErrors).length > 0) {
          clearAllYocoFieldErrors();
        }
        return;
      }

      const customerData =
        typeof cartStore.getCustomerData === "function"
          ? cartStore.getCustomerData() || {}
          : {};
      const billing = customerData.billingAddress || {};
      const shipping = customerData.shippingAddress || {};

      // Only the billing name ever reaches Yoco (see Checkout.php::buildMetadata,
      // which calls $order->get_billing_first_name() / _last_name()). But which
      // DOM fields a shopper is actually typing into depends on the "Use same
      // address for billing" checkbox:
      //
      //  - Checked (default in Blocks): the billing section is hidden and
      //    billing mirrors shipping at submit. The user edits the *shipping*
      //    inputs, so we must attach errors to shipping_first_name /
      //    shipping_last_name for them to render under the visible fields.
      //  - Unchecked: billing has its own inputs, and whatever is in shipping
      //    is never sent to Yoco. We validate and attach errors only to
      //    billing_first_name / billing_last_name.
      //
      // Either way we're guarding the same value — the one that will become
      // billing at place-order time — we just pick the field IDs the user can
      // see so the per-field error renders next to what they're editing.
      const checkoutStore = select(CHECKOUT_STORE);
      const useShippingAsBilling =
        checkoutStore &&
        typeof checkoutStore.getUseShippingAsBilling === "function"
          ? checkoutStore.getUseShippingAsBilling()
          : false;

      const fields = useShippingAsBilling
        ? [
            {
              id: "shipping_first_name",
              value: shipping.first_name || "",
            },
            {
              id: "shipping_last_name",
              value: shipping.last_name || "",
            },
          ]
        : [
            {
              id: "billing_first_name",
              value: billing.first_name || "",
            },
            {
              id: "billing_last_name",
              value: billing.last_name || "",
            },
          ];

      // If the shopper just toggled the checkbox, `ourErrors` may still hold
      // IDs from the previous field set (e.g. `billing_first_name` after
      // switching to same-address mode). Clear any of our errors whose IDs are
      // no longer in the active set so stale messages don't linger under
      // hidden inputs.
      const activeIds = new Set(fields.map((f) => f.id));
      Object.keys(ourErrors).forEach((id) => {
        if (!activeIds.has(id)) {
          delete ourErrors[id];
          validationDispatch.clearValidationError(id);
        }
      });

      // Self-healing pass: query the validation store for the current entry on
      // each field. Only dispatch when state actually diverges from what we'd
      // produce, so we never loop and we recover transparently when Blocks'
      // own address-form effect clears one of our entries during init.
      const getExisting =
        validationStore &&
        typeof validationStore.getValidationError === "function"
          ? (id) => validationStore.getValidationError(id)
          : () => null;

      const errorsToSet = {};
      fields.forEach((field) => {
        const valid = field.value === "" || NAME_PATTERN.test(field.value);
        if (valid) {
          if (ourErrors[field.id]) {
            // Drop ownership BEFORE dispatching so the synchronous re-entrant
            // subscribe tick sees ourErrors[id] === undefined and skips it.
            delete ourErrors[field.id];
            validationDispatch.clearValidationError(field.id);
          }
          return;
        }
        const message = getInvalidCharsMessage(field.value);
        const existing = getExisting(field.id);
        if (!existing || existing.message !== message) {
          errorsToSet[field.id] = {
            message: message,
            hidden: false,
          };
        }
      });

      if (
        Object.keys(errorsToSet).length > 0 &&
        typeof validationDispatch.setValidationErrors === "function"
      ) {
        // Take ownership BEFORE dispatching so the re-entrant subscribe tick
        // sees ourErrors already populated and the getExisting() comparison
        // matches → empty errorsToSet → no further dispatch → no recursion.
        Object.keys(errorsToSet).forEach((id) => {
          ourErrors[id] = true;
        });
        validationDispatch.setValidationErrors(errorsToSet);
      }
    });
  })();

  // Function to retrieve Yoco initialization data
  const data = () => {
    const data = wcSettings.getSetting(
      "class_yoco_wc_payment_gateway_data",
      null,
    );
    if (!data) {
      throw new Error("Yoco initialization data is not available");
    }
    return data;
  };

  const description = () => {
    return wpHtmlEntities.decodeEntities(data()?.description || "");
  };

  // Register Yoco payment method
  wcBlocksRegistry.registerPaymentMethod({
    name: "class_yoco_wc_payment_gateway",
    label: wpElement.createElement(() =>
      wpElement.createElement(
        "span",
        {
          style: {
            display: "flex",
            flex: "1 1 auto",
            flexWrap: "wrap",
            justifyContent: "space-between",
            alignItems: "center",
            columnGap: "1ch",
            rowGap: "0.4em",
          },
        },
        wpElement.createElement("img", {
          src: data()?.logo_url,
          alt: "Yoco logo",
          style: { height: "1.1em" },
        }),
        wpElement.createElement(
          "span",
          {
            style: {
              display: "flex",
              flexWrap: "wrap",
              columnGap: "0.25ch",
              rowGap: "0.2em",
            },
          },
          Object.entries(data()?.providers_icons || {}).map(([alt, src]) =>
            wpElement.createElement("img", {
              key: alt,
              src,
              alt: alt + " logo",
              style: { height: "1.5em", maxHeight: "32px" },
            }),
          ),
        ),
      ),
    ),
    ariaLabel: wpI18n.__("Yoco payment method", "yoco-payment-gateway"),
    canMakePayment: () => true,
    content: wpElement.createElement(description, null),
    edit: wpElement.createElement(description, null),
    supports: {
      features: null !== data()?.supports ? data().supports : [],
    },
  });
})();
