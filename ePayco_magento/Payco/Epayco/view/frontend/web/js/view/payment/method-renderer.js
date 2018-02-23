define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'epayco',
                component: 'Payco_Epayco/js/view/payment/method-renderer/epayco'
            }
        );
        return Component.extend({});
    }
);