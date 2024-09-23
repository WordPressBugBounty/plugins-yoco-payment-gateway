document.addEventListener('DOMContentLoaded', function() {
  const testKeyField     = document.getElementById('woocommerce_class_yoco_wc_payment_gateway_test_secret_key');
  const liveKeyField     = document.getElementById('woocommerce_class_yoco_wc_payment_gateway_live_secret_key');
  const testKeyFieldWrap = document.createElement('div');
  const liveKeyFieldWrap = document.createElement('div');

  const passButtonHTML = '<button type="button" class="button button-secondary yoco-hide-key hide-if-no-js" data-toggle="0" aria-label="Show password"><span class="dashicons dashicons-visibility" aria-hidden="true"></span></button>';

  if (testKeyField) {
    wrap( testKeyField, testKeyFieldWrap);
    testKeyField.addEventListener('keyup', event => validateKeyField(event, 'test'));
    testKeyFieldWrap.insertAdjacentHTML('beforeend', passButtonHTML);
    testKeyFieldWrap.classList.add('yoco-api-key');
    testKeyFieldWrap.addEventListener('click', event => toggleAPIkey(event));
  }

  if (liveKeyField) {
    wrap( liveKeyField, liveKeyFieldWrap);
    liveKeyField.addEventListener('keyup', event => validateKeyField(event, 'live'));
    liveKeyFieldWrap.insertAdjacentHTML('beforeend', passButtonHTML);
    liveKeyFieldWrap.classList.add('yoco-api-key');
    liveKeyFieldWrap.addEventListener('click', event => toggleAPIkey(event));
  }
});

function toggleAPIkey(event) {
  const el = event.target;

  if ( ! el.classList.contains('yoco-hide-key') ) {
    return;
  };

  const toggle = 0 == el.dataset.toggle ? true : false;
  const input = el.previousSibling;
  const iconEl = el.firstChild;
  const icons = ['dashicons-visibility', 'dashicons-hidden'];
  const ico1 = toggle ? 0 : 1;
  const ico2 = toggle ? 1 : 0;

  el.dataset.toggle = toggle ? 1 : 0;
  el.ariaLabel = toggle ? 'Hide API key' : 'Show API key';
  input.type = toggle ? 'text' : 'password';
  iconEl.classList.replace(icons[ico1], icons[ico2]);
}

function wrap(elToWrap, wrapper) {
  elToWrap.parentNode.insertBefore(wrapper, elToWrap);
  wrapper.appendChild(elToWrap);
}

function validateKeyField(event, type) {
  removeError(event.target);

  if (! isKeyPrefixValid(event.target.value, type) || ! isKeyLengthValid(event.target.value)) {
    disableSaveButton();
    displayError(event.target, `Please check the formatting of the ${type} secret key.`);
    return;
  }

  enableSaveButton();
}

function isKeyLengthValid(key) {
  return 36 === key.length;
}

function isKeyPrefixValid(key, type) {
  switch (type) {
    case 'test':
      return key.match(/^sk_test_/);

    case 'live':
      return key.match(/^sk_live_/);

    default:
      return false;
  }
}

function disableSaveButton() {
  document.querySelector('.woocommerce-save-button').setAttribute('disabled', true);
}

function enableSaveButton() {
  document.querySelector('.woocommerce-save-button').removeAttribute('disabled');
}

function displayError(field, message) {
  const span = document.createElement('span');

  span.classList.add('yoco-woocommerce-settings-error');
  span.textContent = message;

  field.parentNode.append(span);
}

function removeError(field) {
  const error = field.parentNode.querySelector('.yoco-woocommerce-settings-error');

  if (error) {
    error.remove();
  }
}
