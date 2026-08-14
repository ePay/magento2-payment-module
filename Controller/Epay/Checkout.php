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

namespace Epay\Payment\Controller\Epay;

class Checkout extends \Epay\Payment\Controller\AbstractActionController
{
    /**
     * Checkout Action
     */
    public function execute()
    {
        $order = $this->_getOrder();
        $result = $this->getEPayPaymentWindowRequest($order);
        $resultJson = json_encode($result);

        return $this->_resultJsonFactory->create()->setData($resultJson);
    }

    /**
     * Get the Epay Payment window url
     *
     * @param \Magento\Sales\Model\Order
     * @return string|null
     */
    public function getEPayPaymentWindowRequest($order)
    {
        try {
            $epayMethod = $this->_getPaymentMethodInstance(
                $order->getPayment()->getMethod()
            );
            $response = $epayMethod->getPaymentWindow($order);
            return $response;
        } catch (\Exception $ex) {
            $this->_epayLogger->addEpayError($order->getId(), $ex->getMessage());
            return null;
        }
    }
}
