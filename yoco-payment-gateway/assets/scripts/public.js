! (function () {
    "use strict";

    // Aliases for window objects
    var wpElement = window.wp.element;
    var wpHtmlEntities = window.wp.htmlEntities;
    var wpI18n = window.wp.i18n;
    var wcBlocksRegistry = window.wc.wcBlocksRegistry;
    var wcSettings = window.wc.wcSettings;

    // Function to retrieve Yoco initialization data
    const data = () => {
        const data = wcSettings.getSetting("class_yoco_wc_payment_gateway_data", null);
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
            wpElement.createElement("img", {
                src: data()?.logo_url,
                alt: data()?.title,
                style: { height: '1.1em' }
            })
        ),
        ariaLabel: wpI18n.__("Yoco payment method", "yoco_wc_payment_gateway"),
        canMakePayment: () => true,
        content: wpElement.createElement(description, null),
        edit: wpElement.createElement(description, null),
        supports: {
            features: null !== data()?.supports ? data().supports : []
        }
    });
})();
