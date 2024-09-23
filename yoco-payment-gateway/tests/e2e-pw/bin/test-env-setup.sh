#!/usr/bin/env bash

ENABLE_HPOS="${ENABLE_HPOS:-0}"
ENABLE_NEW_PRODUCT_EDITOR="${ENABLE_NEW_PRODUCT_EDITOR:-0}"
ENABLE_TRACKING="${ENABLE_TRACKING:-0}"

# Load environment variables from .env file
if [ -f .env ]; then
  export $(grep -v '^#' .env | xargs)
else
   echo "File .env does not exist."
   exit;
fi

# echo -e 'Normalize permissions for wp-content directory \n'
# docker-compose -f $(wp-env install-path)/docker-compose.yml run --rm -u www-data -e HOME=/tmp tests-wordpress sh -c "chmod -c ugo+w /var/www/html/wp-config.php \
# && chmod -c ugo+w /var/www/html/wp-content \
# && chmod -c ugo+w /var/www/html/wp-content/themes \
# && chmod -c ugo+w /var/www/html/wp-content/plugins \
# && mkdir -p /var/www/html/wp-content/upgrade \
# && chmod -c ugo+w /var/www/html/wp-content/upgrade"

echo -e '  ✔ Create customer'
npm run wp-env run tests-cli -- wp user create customer customer@woocommercecoree2etestsuite.com \
	--user_pass=password \
	--role=customer \
	--first_name='Jane' \
	--last_name='Smith' \
	--user_registered='2022-01-01 12:23:45' \
	> /dev/null

echo -e '  ✔ Set permalinks'
npm run wp-env run tests-cli -- wp rewrite structure '/%postname%/' --hard > /dev/null

echo -e '  ✔ Install wp importer'
npm run wp-env run tests-cli -- wp plugin install wordpress-importer --activate > /dev/null

echo -e '  ✔ Install woocommerce-reset plugin'
npm run wp-env run tests-cli -- wp plugin install https://github.com/woocommerce/woocommerce-reset/archive/refs/heads/trunk.zip --activate > /dev/null

echo -e '  ✔ Import products'
npm run wp-env run tests-cli -- wp import wp-content/plugins/yoco-payment-gateway/tests/e2e-pw/test-data/sample_products.xml --authors=skip > /dev/null

echo -e '  ✔ Update Blog Name'
npm run wp-env run tests-cli -- wp option update blogname "Yoco e2e tests" > /dev/null

echo -e '  ✔ Set store address'
npm run wp-env run tests-cli -- wp option update woocommerce_store_address "56 Shortmarket Street" > /dev/null
npm run wp-env run tests-cli -- wp option update woocommerce_store_city "Cape Town" > /dev/null
npm run wp-env run tests-cli -- wp option update woocommerce_default_country "ZA:WC" > /dev/null
npm run wp-env run tests-cli -- wp option update woocommerce_store_postcode "8000" > /dev/null
npm run wp-env run tests-cli -- wp option update woocommerce_currency "ZAR" > /dev/null

echo -e '  ✔ Set proxy address for WP_HOME and WP_SITEURL'
DOMAIN="https://$SUBDOMAIN.loca.lt"
npm run wp-env run tests-cli -- sh -c '
export DOMAIN="'"$DOMAIN"'"
WP_HOME_LINE=$(grep -Fn "WP_HOME" wp-config.php | cut -d: -f1) && WP_SITEURL_LINE=$(grep -Fn "WP_SITEURL" wp-config.php | cut -d: -f1) && sed -i "${WP_HOME_LINE}s|.*|define( '\''WP_HOME'\'', '\''${DOMAIN}'\'' );|" wp-config.php && sed -i "${WP_SITEURL_LINE}s|.*|define( '\''WP_SITEURL'\'', '\''${DOMAIN}'\'' );|" wp-config.php' > /dev/null
