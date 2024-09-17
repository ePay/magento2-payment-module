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

namespace Epay\Payment\Model\Api\Epay;

use Epay\Payment\Model\Api\Epay\ApiEndpoints;
use Epay\Payment\Model\Api\EpayApiModels;

class Action extends Base
{
    /**
     * Get Payment window url
     *
     * @param \Epay\Payment\Model\Api\Epay\Request\Payment $paymentRequest
     * @return \Epay\Payment\Model\Api\Epay\Request\Models\Url
     */
    public function getPaymentWindowUrl($paymentRequest)
    {
        $baseUrl = $this->_getEndpoint(
                ApiEndpoints::ENDPOINT_EPAY_INTEGRATION
            ) . '/ewindow/Default.aspx';
        $url = $this->_epayHelper->getEpayApiModel(
            EpayApiModels::REQUEST_MODEL_URL
        );

        $url->url = $baseUrl . "?" . http_build_query($paymentRequest);

        return $url;
    }

    /**
     * Get ePay payment window js url
     *
     * @return string
     */
    public function getPaymentWindowJSUrl()
    {
        $url = $this->_getEndpoint(
                ApiEndpoints::ENDPOINT_EPAY_INTEGRATION
            ) . '/ewindow/paymentwindow.js';
        return $url;
    }

    /**
     * Get ePay payment logo url
     *
     * @param string $merchantNumber
     * @return string
     */
    public function getPaymentLogoUrl($merchantNumber)
    {
        $url = $this->_getEndpoint(
                ApiEndpoints::ENDPOINT_EPAY_INTEGRATION
            ) . "/paymentlogos/PaymentLogos.aspx?merchantnumber={$merchantNumber}&direction=2&padding=1&rows=1&logo=0&showdivs=0&iframe=1";
        return $url;
    }

    /**
     * Get ePay logo url
     *
     * @return string
     */
    public function getEpayLogoUrl()
    {
        $url = $this->_getEndpoint(
                ApiEndpoints::ENDPOINT_EPAY_ASSETS
            ) . "/epay_icon_64x64.png";
        return $url;
    }

    /**
     * Capture transaction
     *
     * @param int|long $amount
     * @param string $transactionId
     * @param \Epay\Payment\Model\Api\Epay\Request\Models\Auth $auth
     * @return \Epay\Payment\Model\Api\Epay\Response\Capture
     */
    public function capture($amount, $transactionId, $auth)
    {
        try {
            $param = [
                'merchantnumber' => $auth->merchantNumber,
                'transactionid' => $transactionId,
                'amount' => (string)$amount,
                'group' => '',
                'pbsResponse' => -1,
                'epayresponse' => -1,
                'pwd' => $auth->pwd
            ];

            $url = $this->_getEndpoint(
                    ApiEndpoints::ENDPOINT_REMOTE
                ) . '/payment.asmx?WSDL';
            $client = $this->_initSoapClient($url);

            $result = $client->capture($param);
            $captureResponse = $this->_epayHelper->getEpayApiModel(
                EpayApiModels::RESPONSE_CAPTURE
            );
            $captureResponse->result = $result->captureResult;
            $captureResponse->epayResponse = $result->epayresponse;
            $captureResponse->pbsResponse = $result->pbsResponse;

            return $captureResponse;
        } catch (\Exception $ex) {
            $this->_epayLogger->addEpayError("-1", $ex->getMessage());
            return null;
        }
    }

    /**
     * Credit transaction
     *
     * @param int|long $amount
     * @param string $transactionId
     * @param \Epay\Payment\Model\Api\Epay\Request\Models\Auth $auth
     * @return \Epay\Payment\Model\Api\Epay\Response\Credit
     */
    public function credit($amount, $transactionId, $auth)
    {
        $creditResponse = $this->_epayHelper->getEpayApiModel(
            EpayApiModels::RESPONSE_CREDIT
        );
        try {
            $param = [
                'merchantnumber' => $auth->merchantNumber,
                'transactionid' => $transactionId,
                'amount' => (string)$amount,
                'group' => '',
                'pbsresponse' => -1,
                'epayresponse' => -1,
                'pwd' => $auth->pwd
            ];
            $url = $this->_getEndpoint(
                    ApiEndpoints::ENDPOINT_REMOTE
                ) . '/payment.asmx?WSDL';
            $client = $this->_initSoapClient($url);
            $result = $client->credit($param);

            $creditResponse->result = $result->creditResult;
            $creditResponse->epayResponse = $result->epayresponse;
            $creditResponse->pbsResponse = $result->pbsresponse;

            return $creditResponse;
        } catch (\Exception $ex) {
            $this->_epayLogger->addEpayError("-1", $ex->getMessage());
            return null;
        }
    }

    /**
     * Delete transaction
     *
     * @param string $transactionId
     * @param \Epay\Payment\Model\Api\Epay\Request\Models\Auth $auth
     * @return \Epay\Payment\Model\Api\Epay\Response\Delete
     */
    public function delete($transactionId, $auth)
    {
        try {
            $param = [
                'merchantnumber' => $auth->merchantNumber,
                'transactionid' => $transactionId,
                'group' => '',
                'epayresponse' => -1,
                'pwd' => $auth->pwd
            ];
            $url = $this->_getEndpoint(
                    ApiEndpoints::ENDPOINT_REMOTE
                ) . '/payment.asmx?WSDL';
            $client = $this->_initSoapClient($url);
            $result = $client->delete($param);
            $deleteResponse = $this->_epayHelper->getEpayApiModel(
                EpayApiModels::RESPONSE_DELETE
            );
            $deleteResponse->result = $result->deleteResult;
            $deleteResponse->epayResponse = $result->epayresponse;

            return $deleteResponse;
        } catch (\Exception $ex) {
            $this->_epayLogger->addEpayError("-1", $ex->getMessage());
            return null;
        }
    }

    /**
     * Get Transaction
     *
     * @param string $transactionId
     * @param \Epay\Payment\Model\Api\Epay\Request\Models\Auth $auth
     * @return \Epay\Payment\Model\Api\Epay\Response\Transaction
     */
    public function getTransaction($transactionId, $auth)
    {
        try {
            $param = [
                'merchantnumber' => $auth->merchantNumber,
                'transactionid' => $transactionId,
                'epayresponse' => -1,
                'pwd' => $auth->pwd
            ];

            $url = $this->_getEndpoint(
                    ApiEndpoints::ENDPOINT_REMOTE
                ) . '/payment.asmx?WSDL';
            $client = $this->_initSoapClient($url);

            $result = $client->gettransaction($param);
            $getTransactionResponse = $this->_epayHelper->getEpayApiModel(
                EpayApiModels::RESPONSE_TRANSACTION
            );
            $getTransactionResponse->result = $result->gettransactionResult;
            $getTransactionResponse->epayResponse = $result->epayresponse;
            $getTransactionResponse->transactionInformation = $result->transactionInformation;

            return $getTransactionResponse;
        } catch (\Exception $ex) {
            $this->_epayLogger->addEpayError("-1", $ex->getMessage());
            return null;
        }
    }
}
