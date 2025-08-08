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

namespace Epay\Payment\Controller;

use Epay\Payment\Helper\EpayConstants;
use Epay\Payment\Model\Method\Epay\Payment as EpayPayment;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;

abstract class AbstractActionController extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Epay\Payment\Helper\Data
     */
    protected $_epayHelper;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var \Epay\Payment\Logger\EpayLogger
     */
    protected $_epayLogger;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $_paymentHelper;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $_orderSender;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $_invoiceSender;

    /**
     * Application Event Dispatcher
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;


    protected $_quoteFactory;

    /**
     * AbstractActionController constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Epay\Payment\Helper\Data $epayHelper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Epay\Payment\Logger\EpayLogger $epayLogger
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Epay\Payment\Helper\Data $epayHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Epay\Payment\Logger\EpayLogger $epayLogger,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Quote\Model\QuoteFactory $quoteFactory
    ) {
        parent::__construct($context);
        $this->_orderFactory = $orderFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_epayHelper = $epayHelper;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_epayLogger = $epayLogger;
        $this->_paymentHelper = $paymentHelper;
        $this->_orderSender = $orderSender;
        $this->_invoiceSender = $invoiceSender;
        $this->_quoteFactory = $quoteFactory;

        $this->_eventManager = $context->getEventManager();
    }

    /**
     * Get order object
     *
     * @return \Magento\Sales\Model\Order
     */
    protected function _getOrder()
    {
        $incrementId = $this->_checkoutSession->getLastRealOrderId();
        return $this->getOrder($incrementId);
    }

    /**
     * Get order by IncrementId
     *
     * @param  $incrementId
     * @return \Magento\Sales\Model\Order
     */
    protected function _getOrderByIncrementId($incrementId)
    {
        return $this->getOrder($incrementId);
    }

    /**
     * Get order object
     *
     * @param mixed $incrementId
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder($incrementId)
    {
        return $this->_orderFactory->create()->loadByIncrementId($incrementId);
    }

    /**
     * Set the order details
     *
     * @param \Magento\Sales\Model\Order $order
     */
    protected function setOrderDetails($order)
    {
        $message = __("Order placed and is now awaiting payment authorization");
        $order->addStatusHistoryComment($message);
        $order->setIsNotified(false);
        $order->save();
    }

    protected function acceptOrder($methodReference)
    {
        $posted = $this->getRequest()->getParams();
        if (array_key_exists('orderid', $posted)) {
            $order = $this->_getOrderByIncrementId($posted['orderid']);

            $this->_checkoutSession->setLastOrderId($order->getId());
            $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->_checkoutSession->setLastQuoteId($order->getQuoteId());
            $this->_checkoutSession->setLastSuccessQuoteId($order->getQuoteId());

            $payment = $order->getPayment();
            if (isset($payment)) {
                $payment->setAdditionalInformation(
                    EpayConstants::PAYMENT_STATUS_ACCEPTED,
                    true
                );
                $payment->save();
            }
        }
        $this->_redirect('checkout/onepage/success');
    }

    /**
     * Cancel the order
     */
    protected function cancelOrder()
    {
        $order = $this->_getOrder();
        if (isset($order) && $order->getId() && $order->getState(
            ) != Order::STATE_CANCELED) {
            $payment = $order->getPayment();
            if (isset($payment)) {
                $epayReference = $payment->getAdditionalInformation(
                    EpayPayment::METHOD_REFERENCE
                );
                $paymentStatusAccepted = $payment->getAdditionalInformation(
                    EpayConstants::PAYMENT_STATUS_ACCEPTED
                );

                $paymentMethodInstance = $this->_getPaymentMethodInstance(
                    $order->getPayment()->getMethod()
                );

                $orderStatusAfterCanceledPayment = $paymentMethodInstance->getConfigData(EpayConstants::ORDER_STATUS_AFTER_CANCELED_PAYMENT);

                if (empty($epayReference) && $paymentStatusAccepted != true && $orderStatusAfterCanceledPayment != "pending_payment") {
                    $comment = __(
                        "The order was canceled through the payment window"
                    );
                    $orderIncrementId = $order->getIncrementId();
                    $this->_epayLogger->addCheckoutInfo(
                        $orderIncrementId,
                        $comment
                    );
                    $order->addStatusHistoryComment($comment);
                    $order->cancel();

                    //Restore Quote
                    $this->_checkoutSession->restoreQuote();
                } else {

                    if($orderStatusAfterCanceledPayment == "pending_payment")
                    {
                        $order->setState(Order::STATE_PENDING_PAYMENT)->setStatus('pending_payment');

                        $quote = $this->_quoteFactory->create()->load($order->getQuoteId());

                        if ($quote && $quote->getId()) {
                            $quote->setIsActive(true);
                            // $quote->setReservedOrderId(null);
                            $quote->save();

                            $this->_checkoutSession->replaceQuote($quote);
                        }

                        // $this->_checkoutSession->restoreQuote();
                    }

                    $comment = __("Order cancelling attempt avoided");
                    $order->addStatusHistoryComment($comment);
                }
                $order->save();
            }
        }

        $this->_redirect('checkout/cart');
    }

    /**
     * Get Payment method instance object
     *
     * @param string $method
     * @return {MethodInstance}
     */
    protected function _getPaymentMethodInstance($method)
    {
        return $this->_paymentHelper->getMethodInstance($method);
    }

    /**
     * Process the callback data
     *
     * @param \Magento\Sales\Model\Order $order $order
     * @param \Epay\Payment\Model\Method\AbstractPayment $paymentMethodInstance
     * @param string $txnId
     * @param string $methodReference
     * @param string $ccType
     * @param string $ccNumber
     * @param mixed $feeAmountInMinorUnits
     * @param mixed $minorUnits
     * @param mixed $status
     * @param boolean $isInstantCapture
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return void
     */
    protected function _processCallbackData(
        $order,
        $paymentMethodInstance,
        $txnId,
        $methodReference,
        $ccType,
        $ccNumber,
        $feeAmountInMinorUnits,
        $minorUnits,
        $status,
        $isInstantCapture,
        $payment = null,
        $fraudStatus = 0
    ) {
        try {
            if (!isset($payment)) {
                $payment = $order->getPayment();
            }
            $storeId = $order->getStoreId();
            $this->updatePaymentData(
                $order,
                $txnId,
                $methodReference,
                $ccType,
                $ccNumber,
                $paymentMethodInstance,
                $status,
                $isInstantCapture,
                $fraudStatus
            );

            if ($paymentMethodInstance->getConfigData(
                    EpayConstants::ADD_SURCHARGE_TO_PAYMENT,
                    $storeId
                ) == 1 && $feeAmountInMinorUnits > 0) {
                $this->addSurchargeToOrder(
                    $order,
                    $feeAmountInMinorUnits,
                    $minorUnits,
                    $ccType,
                    $paymentMethodInstance
                );
            }

            if (!$order->getEmailSent() && $paymentMethodInstance->getConfigData(
                    EpayConstants::SEND_MAIL_ORDER_CONFIRMATION,
                    $storeId
                ) == 1) {
                $this->sendOrderEmail($order);
            }
            if ($isInstantCapture) {
                $this->createInvoice($order, $paymentMethodInstance, false);
            }
            if (!$isInstantCapture && $paymentMethodInstance->getConfigData(
                    EpayConstants::INSTANT_INVOICE,
                    $storeId
                ) == 1) {
                $this->createInvoice($order, $paymentMethodInstance, true);
            }
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Update the order and payment informations
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string $txnId
     * @param string $methodReference
     * @param string $ccType
     * @param string $ccNumber
     * @param \Epay\Payment\Model\Method\AbstractPayment $paymentMethodInstance
     * @param mixed $status
     * @param boolean $isInstantCapture
     * @param mixed $fraudStatus
     * @return void
     */
    public function updatePaymentData(
        $order,
        $txnId,
        $methodReference,
        $ccType,
        $ccNumber,
        $paymentMethodInstance,
        $status,
        $isInstantCapture,
        $fraudStatus
    ) {
        try {
            $payment = $order->getPayment();
            $payment->setTransactionId($txnId);
            $payment->setIsTransactionClosed(false);
            $payment->setAdditionalInformation([$methodReference => $txnId]);
            $transactionComment = __("Payment authorization was a success.");
            if ($fraudStatus == 1) {
                $payment->setIsFraudDetected(true);
                $order->setStatus(Order::STATUS_FRAUD);
                $transactionComment = __("Fraud was detected on the payment");
            } else {
                $order->setStatus($status);
            }
            $storeId = $order->getStoreId();
            $orderCurrentState = $order->getState();
            if ($orderCurrentState === Order::STATE_CANCELED && $paymentMethodInstance->getConfigData(
                    EpayConstants::UNCANCEL_ORDER_LINES,
                    $storeId
                ) == 1) {
                $this->unCancelOrderItems($order);
            }

            $order->setState(Order::STATE_PROCESSING);
            $transaction = $payment->addTransaction(Transaction::TYPE_AUTH);
            $payment->addTransactionCommentsToOrder(
                $transaction,
                $transactionComment
            );

            if ($order->getPayment()->getMethod(
                ) === \Epay\Payment\Model\Method\Epay\Payment::METHOD_CODE) {
                $ccType = $this->_epayHelper->calcCardtype($ccType);
            }

            $payment->setCcType($ccType);
            $payment->setCcNumberEnc($ccNumber);

            $payment->setAdditionalInformation(
                EpayConstants::INSTANT_CAPTURE,
                $isInstantCapture
            );
            $payment->save();

            $order->save();
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Un-Cancel order lines
     *
     * @param \Magento\Sales\Model\Order $order
     */
    public function unCancelOrderItems($order)
    {
        try {
            $productStockQty = [];
            foreach ($order->getAllVisibleItems() as $item) {
                $productStockQty[$item->getProductId()] = $item->getQtyCanceled();
                foreach ($item->getChildrenItems() as $child) {
                    $productStockQty[$child->getProductId()] = $item->getQtyCanceled(
                    );
                    $child->setQtyCanceled(0);
                    $child->setTaxCanceled(0);
                    $child->setDiscountTaxCompensationCanceled(0);
                }
                $item->setQtyCanceled(0);
                $item->setTaxCanceled(0);
                $item->setDiscountTaxCompensationCanceled(0);
            }
            $this->_eventManager->dispatch(
                'sales_order_manage_inventory',
                [
                    'order' => $order,
                    'product_qty' => $productStockQty
                ]
            );
            $order->setSubtotalCanceled(0);
            $order->setBaseSubtotalCanceled(0);
            $order->setTaxCanceled(0);
            $order->setBaseTaxCanceled(0);
            $order->setShippingCanceled(0);
            $order->setBaseShippingCanceled(0);
            $order->setDiscountCanceled(0);
            $order->setBaseDiscountCanceled(0);
            $order->setTotalCanceled(0);
            $order->setBaseTotalCanceled(0);
            $comment = __(
                "The order was un-canceled by the ePay Checkout Callback"
            );
            $order->addStatusHistoryComment($comment, false);
            $order->save();
            $this->_epayLogger->addCheckoutInfo($order->getId(), $comment);
        } catch (\Exception $ex) {
            $comment = __(
                    "The order could not be un-canceled - Reason:"
                ) . $ex->getMessage();
            $order->addStatusHistoryComment($comment, false);
            $order->save();
            $this->_epayLogger->addCheckoutInfo($order->getId(), $comment);
        }
    }

    /**
     * Add Surcharge to the order
     *
     * @param \Magento\Sales\Model\Order $order
     * @param mixed $feeAmountInMinorunits
     * @param mixed $minorunits
     * @param mixed $ccType
     * @param \Epay\Payment\Model\Method\AbstractPayment $paymentMethodInstance
     * @return void
     */
    public function addSurchargeToOrder(
        $order,
        $feeAmountInMinorunits,
        $minorunits,
        $ccType,
        $paymentMethodInstance
    ) {
        try {
            foreach ($order->getAllItems() as $item) {
                if ($item->getSku() === EpayConstants::EPAY_SURCHARGE) {
                    return;
                }
            }

            $baseFeeAmount = $this->_epayHelper->convertPriceFromMinorunits(
                $feeAmountInMinorunits,
                $minorunits
            );
            $feeAmount = $order->getStore()->getBaseCurrency()->convert(
                $baseFeeAmount,
                $order->getOrderCurrencyCode()
            );
            $text = $ccType . ' - ' . __("Surcharge fee");
            $storeId = $order->getStoreId();

            if ($paymentMethodInstance->getConfigData(
                    EpayConstants::SURCHARGE_MODE,
                    $storeId
                ) === EpayConstants::SURCHARGE_ORDER_LINE) {
                $feeItem = $this->_epayHelper->createSurchargeItem(
                    $baseFeeAmount,
                    $feeAmount,
                    $storeId,
                    $order->getId(),
                    $text
                );
                $order->addItem($feeItem);
                $order->setBaseSubtotal($order->getBaseSubtotal() + $baseFeeAmount);
                $order->setBaseSubtotalInclTax(
                    $order->getBaseSubtotalInclTax() + $baseFeeAmount
                );
                $order->setSubtotal($order->getSubtotal() + $feeAmount);
                $order->setSubtotalInclTax(
                    $order->getSubtotalInclTax() + $feeAmount
                );
            } else {
                //Add fee to shipment
                $order->setBaseShippingAmount(
                    $order->getBaseShippingAmount() + $baseFeeAmount
                );
                $order->setBaseShippingInclTax(
                    $order->getBaseShippingInclTax() + $baseFeeAmount
                );
                $order->setShippingAmount($order->getShippingAmount() + $feeAmount);
                $order->setShippingInclTax(
                    $order->getShippingInclTax() + $feeAmount
                );
            }

            $order->setBaseGrandTotal($order->getBaseGrandTotal() + $baseFeeAmount);
            $order->setGrandTotal($order->getGrandTotal() + $feeAmount);

            $feeMessage = $text . ' ' . __("added to order");
            $order->addStatusHistoryComment($feeMessage);
            $order->save();
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Send the orderconfirmation mail to the customer
     *
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    public function sendOrderEmail($order)
    {
        try {
            $this->_orderSender->send($order);
            $order->addStatusHistoryComment(
                __("Notified customer about order #%1", $order->getIncrementId())
            )
                ->setIsCustomerNotified(1)
                ->save();
        } catch (\Exception $ex) {
            $order->addStatusHistoryComment(
                __(
                    "Could not send order confirmation for order #%1",
                    $order->getIncrementId()
                )
            )
                ->setIsCustomerNotified(0)
                ->save();
        }
    }

    /**
     * Create an invoice
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Epay\Payment\Model\Method\AbstractPayment $paymentMethodInstance
     * @param boolean
     */
    public function createInvoice(
        $order,
        $paymentMethodInstance,
        $isOnlineCapture = true
    ) {
        try {
            if ($order->canInvoice()) {
                $invoice = $order->prepareInvoice();
                $storeId = $order->getStoreId();

                if ($isOnlineCapture) {
                    $invoice->setRequestedCaptureCase(
                        \Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE
                    );
                } else {
                    $invoice->setRequestedCaptureCase(
                        \Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE
                    );
                }

                $invoice->register();
                $invoice->save();
                $transactionSave = $this->_objectManager->create(
                    'Magento\Framework\DB\Transaction'
                )
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
                $transactionSave->save();

                if ($paymentMethodInstance->getConfigData(
                        EpayConstants::INSTANT_INVOICE_MAIL,
                        $order->getStoreId()
                    ) == 1) {
                    $invoice->setEmailSent(1);
                    $this->_invoiceSender->send($invoice);
                    $order->addStatusHistoryComment(
                        __("Notified customer about invoice #%1", $invoice->getId())
                    )
                        ->setIsCustomerNotified(1)
                        ->save();
                }
            }
        } catch (\Exception $ex) {
            $order->addStatusHistoryComment(
                __(
                    "Could not create or Capture the Invoice for order #%1 - Reason: %2",
                    $order->getId(),
                    $ex->getMessage()
                )
            )
                ->setIsCustomerNotified(0)
                ->save();
        }
    }

    /**
     * Log Error
     *
     * @param string $paymentMethod
     * @param mixed $id
     * @param mixed $errorMessage
     */
    protected function _logError($paymentMethod, $id, $errorMessage)
    {
        if ($paymentMethod === EpayPayment::METHOD_CODE) {
            $this->_epayLogger->addEpayError($id, $errorMessage);
        } else {
            $this->_epayLogger->addError($errorMessage);
        }
    }

    /**
     * Get Callback Response
     *
     * @param mixed $statusCode
     * @param mixed $message
     * @param mixed $id
     * @return mixed
     */
    protected function _createCallbackResult($statusCode, $message, $id)
    {
        $result = $this->_resultJsonFactory->create();
        $result->setHttpResponseCode($statusCode);

        $result->setData(
            [
                'id' => $id,
                'message' => $message
            ]
        );

        return $result;
    }
}
