/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Ui/js/model/messageList',
        'mage/translate',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function (ko, $, Component, globalMessageList, $t, fullScreenLoader) {
        'use strict';

        return Component.extend(
            {
                initialize: function () {
                    this._super();
                    this.loadEPayPaymentWindowJs();
                },
                defaults: {
                    template: 'Epay_Payment/payment/epay-form'
                },
                redirectAfterPlaceOrder: false,
                getEpayTitle: function () {
                    return window.checkoutConfig.payment.epay.paymentTitle;
                },
                getEpayDescription: function () {
                    return window.checkoutConfig.payment.epay.paymentDescription;
                },
                getEpayLogo: function () {
                    return window.checkoutConfig.payment.epay.paymentLogoSrc;
                },
                getEpayPaymentLogoSrc: function () {
                    return window.checkoutConfig.payment.epay.paymentTypeLogoSrc;
                },
                afterPlaceOrder: function () {
                    fullScreenLoader.startLoader();
                    this.getPaymentWindow();
                },
                getPaymentWindow: function () {
                    var self = this;
                    var url = window.checkoutConfig.payment.epay.checkoutUrl;
                   
                    $.get(url)
                        .done(
                            function (response) {
                                response = JSON.parse(response);
                                if (!response) {
                                    self.showError($t("Error opening payment window"));
                                    $.mage.redirect(window.checkoutConfig.payment.epay.cancelUrl);
                                }
                                self.openPaymentWindow(response);
                            }
                        ).fail(
                        function (error) {
                            self.showError($t("Error opening payment window") + ': ' + error.statusText);
                            $.mage.redirect(window.checkoutConfig.payment.epay.cancelUrl);
                        }
                    );
                },
                openPaymentWindow: function (request) {
                    var paymentwindow = new PaymentWindow(request);
                    paymentwindow.open();
                },
                loadEPayPaymentWindowJs: function () {
                    $.getScript(window.checkoutConfig.payment.epay.paymentWindowJsUrl);
                },
                showError: function (errorMessage) {
                    globalMessageList.addErrorMessage(
                        {
                            message: errorMessage
                        }
                    );
                }
            }
        );
    }
);
