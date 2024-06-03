<?php
/**
 * Copyright (c) 2019. All rights reserved ePay Payment Solutions.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    ePay Payment Solutions
 * @copyright ePay Payment Solutions (https://epay.dk)
 * @license   ePay Payment Solutions
 */

namespace Epay\Payment\Model\Method\Epay;

use Epay\Payment\Helper\EpayConstants;
use Epay\Payment\Model\Method\Epay\Payment as EpayPayment;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * @var string
     */
    protected $methodCode = EpayPayment::METHOD_CODE;

    /**
     * @var Object
     */
    protected $_ePayMethod;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $_paymentHelper;

    /**
     * Config Provider
     *
     * @param \Magento\Payment\Helper\Data $paymentHelper
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentHelper
    ) {
        $this->_paymentHelper = $paymentHelper;
        $this->_ePayMethod = $this->_paymentHelper->getMethodInstance(
            $this->methodCode
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [
            'payment' => [
                $this->methodCode => [
                    'paymentTitle' => $this->_ePayMethod->getConfigData(
                        EpayConstants::TITLE
                    ),
                    'paymentDescription' => $this->_ePayMethod->getConfigData(
                        EpayConstants::DESCRIPTION
                    ),
                    'paymentLogoSrc' => $this->_ePayMethod->getEpayLogoUrl(),
                    'paymentWindowJsUrl' => $this->_ePayMethod->getEPayPaymentWindowJsUrl(
                    ),
                    'paymentTypeLogoSrc' => $this->_ePayMethod->getEpayPaymentTypeUrl(
                    ),
                    'checkoutUrl' => $this->_ePayMethod->getCheckoutUrl(),
                    'cancelUrl' => $this->_ePayMethod->getCancelUrl()
                ]
            ]
        ];

        return $config;
    }
}
