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
        const data = wcSettings.getSetting( 'class_yoco_wc_payment_gateway_data', null );
        if ( ! data) {
            throw new Error( 'Yoco initialization data is not available' );
        }
        return data;
    };

    const description = () => {
        return wpHtmlEntities.decodeEntities(data()?.description || "");
    };

    // Register Yoco payment method
    wcBlocksRegistry.registerPaymentMethod( {
        name: 'class_yoco_wc_payment_gateway',
        label: wpElement.createElement(
            () => wpElement.createElement(
                'span',
                { style: { display: 'flex', flex: '1 1 auto', flexWrap: 'wrap', justifyContent: 'space-between', alignItems: 'center', columnGap: '1ch', rowGap: '0.4em' } },
                wpElement.createElement( 'img', {
                    src: data()?.logo_url,
                    alt: 'Yoco logo',
                    style: { height: '1.1em' }
                }),
                wpElement.createElement(
                    'span',
                    { style: { display: 'flex', flexWrap: 'wrap', columnGap: '0.25ch', rowGap: '0.2em' } },
                    Object.entries( data()?.providers_icons || {} ).map( ( [alt, src] ) =>
                        wpElement.createElement( 'img', {
                            key: alt,
                            src,
                            alt: alt + " logo",
                            style: { height: '1.5em', maxHeight: '32px' }
                        })
                    )
                )
            )
        ),
        ariaLabel: wpI18n.__( 'Yoco payment method', 'yoco-payment-gateway' ),
        canMakePayment: () => true,
        content: wpElement.createElement(description, null),
        edit: wpElement.createElement(description, null),
        supports: {
            features: null !== data()?.supports ? data().supports : []
        }
    } );
})();
