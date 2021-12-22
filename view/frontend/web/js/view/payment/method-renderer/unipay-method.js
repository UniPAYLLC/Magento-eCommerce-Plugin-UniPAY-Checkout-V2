/**
 * Copyright © 2018 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'mage/url'
    ],
    function (Component, quote, url) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'UniPAYPaymentGateway_Unipay/payment/unipay'
            },

            redirectAfterPlaceOrder: false,
            /**
             * After place order callback
             */
            afterPlaceOrder: function () {
                window.location.replace(
                    url.build('unipay/redirect/redirect')
                );
            }
        });
    }
);
