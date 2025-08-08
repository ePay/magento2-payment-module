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

namespace Epay\Payment\Helper;

class EpayConstants
{
    //Surcharge
    const EPAY_SURCHARGE = 'surcharge_fee';
    const SURCHARGE_SHIPMENT = "surcharge_shipment";
    const SURCHARGE_ORDER_LINE = "surcharge_order_line";

    //Rounding
    const ROUND_UP = "round_up";
    const ROUND_DOWN = "round_down";
    const ROUND_DEFAULT = "round_default";

    //AgeVerfication
    const AGEVERIFICATION_DISABLED = "ageverification_disabled";
    const AGEVERIFICATION_ENABLED_ALL = "ageverification_enabled_all";
    const AGEVERIFICATION_ENABLED_DK = "ageverification_enabled_dk";

    //Config constants
    const ORDER_STATUS = 'order_status';
    const MASS_CAPTURE_INVOICE_MAIL = 'masscaptureinvoicemail';
    const TITLE = 'title';
    const DESCRIPTION = 'description';
    const MERCHANT_NUMBER = 'merchantnumber';
    const ACCESS_TOKEN = 'accesstoken';
    const SECRET_TOKEN = 'secrettoken';
    const MD5_KEY = 'md5key';
    const PAYMENTTYPE = 'paymenttype';
    const SPLITPAYMENT = 'splitpayment';
    const PAYMENT_WINDOW_ID = 'paymentwindowid';
    const INSTANT_CAPTURE = 'instantcapture';
    const INSTANT_INVOICE = 'instantinvoice';
    const INSTANT_INVOICE_MAIL = 'instantinvoicemail';
    const IMMEDIATEREDI_REDIRECT_TO_ACCEPT = 'immediateredirecttoaccept';
    const ADD_SURCHARGE_TO_PAYMENT = 'addsurchargetopayment';
    const SURCHARGE_MODE = 'surchargemode';
    const SEND_MAIL_ORDER_CONFIRMATION = 'sendmailorderconfirmation';
    const WINDOW_STATE = 'windowstate';
    const ENABLE_MOBILE_PAYMENT_WINDOW = 'enablemobilepaymentwindow';
    const REMOTE_INTERFACE = 'remoteinterface';
    const REMOTE_INTERFACE_PASSWORD = 'remoteinterfacepassword';
    const PAYMENT_GROUP = 'paymentgroup';
    const OWN_RECEIPT = 'ownreceipt';
    const ENABLE_INVOICE_DATA = 'enableinvoicedata';
    const ROUNDING_MODE = 'roundingmode';
    const UNCANCEL_ORDER_LINES = 'uncancelorderlines';
    const ALLOW_LOW_VALUE_EXEMPTION = 'allowlowvalueexemption';
    const LIMIT_LOW_VALUE_EXEMPTION = 'limitlowvalueexemption';
    const TIMEOUT = 'timeout';
    const AGEVERIFICATIONMODE = 'ageverificationmode';
    //Actions
    const CAPTURE = 'capture';
    const REFUND = 'refund';
    const VOID = 'void';
    const GET_TRANSACTION = 'gettransaction';

    //Action lock
    const PAYMENT_STATUS_ACCEPTED = 'payment_status_accepted';
    
    const ORDER_STATUS_AFTER_CANCELED_PAYMENT = 'order_status_after_canceled_payment';

}
