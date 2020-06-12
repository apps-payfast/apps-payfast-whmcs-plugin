<?php

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);


// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

$basketid = isset($_REQUEST['basket_id']) ? $_REQUEST['basket_id'] : '';
$apps_status_msg = isset($_REQUEST['err_msg']) ? $_REQUEST['err_msg'] : '';
$apps_transactionid = isset($_REQUEST['transaction_id']) ? $_REQUEST['transaction_id'] : '';
$apps_statuscode = isset($_REQUEST['err_code']) ? $_REQUEST['err_code'] : '';
$apps_rdv_key = isset($_REQUEST['Rdv_Message_Key']) ? $_REQUEST['Rdv_Message_Key'] : '';
$response_key = isset($_REQUEST['responseKey']) ? $_REQUEST['responseKey'] : '';
$invoice_amount = isset($_REQUEST['invamount']) ? $_REQUEST['invamount'] : '';

$invoiceid = checkCbInvoiceID($basketid, "PayFast");

if ($invoiceid != $basketid) {
    header('Location: ' . $gatewayParams['systemurl']);
    exit;
}

if ($response_key != '') {
    $local_key = md5($gatewayParams['merchant_id'] . $basketid . $gatewayParams['secret_word'] . $invoice_amount . $apps_statuscode);
    if ($local_key != $response_key) {
        header('Location: ' . $gatewayParams['systemurl']);
        exit;
    }
}

$transactionStatus = ($apps_statuscode == '00' || $apps_statuscode == '000') ? 'Success' : 'Failure';

logTransaction("PayFast", $_REQUEST, "Unsuccessful");
logTransaction("PayFast", $apps_status_msg, "Error Message");
logTransaction("PayFast", $apps_rdv_key, "RDV Message Key");
logTransaction("PayFast", $apps_transactionid, "Transaction ID");
logTransaction("PayFast", $apps_statuscode, "Error Code");

if ($transactionStatus === 'Success') {
    checkCbTransID($apps_transactionid);
    addInvoicePayment($invoiceid, $apps_transactionid, "", "", "payfast");
    logTransaction("PayFast", $_REQUEST, "Successful");
    echo "<p align=\"center\"><a href=\"" . $CONFIG['SystemURL'] . "/viewinvoice.php?id=" . $invoiceid . "&paymentsuccess=true\">Click here to return to " . $CONFIG['CompanyName'] . "</a></p>";
    exit();
}

echo "<p align=\"center\"><a href=\"" . $CONFIG['SystemURL'] . "/viewinvoice.php?id=" . $invoiceid . "&paymentfailed=true\">Click here to return to " . $CONFIG['CompanyName'] . "</a></p>";

exit();


/**
 * Validate Callback Invoice ID.
 *
 * Checks invoice ID is a valid invoice number. Note it will count an
 * invoice in any status as valid.
 *
 * Performs a die upon encountering an invalid Invoice ID.
 *
 * Returns a normalised invoice ID.
 *
 * @param int $invoiceId Invoice ID
 * @param string $gatewayName Gateway Name
 */
/**
 * Check Callback Transaction ID.
 *
 * Performs a check for any existing transactions with the same given
 * transaction number.
 *
 * Performs a die upon encountering a duplicate.
 *
 * @param string $transactionId Unique Transaction ID
 */
checkCbTransID($transactionId);

/**
 * Log Transaction.
 *
 * Add an entry to the Gateway Log for debugging purposes.
 *
 * The debug data can be a string or an array. In the case of an
 * array it will be
 *
 * @param string $gatewayName        Display label
 * @param string|array $debugData    Data to log
 * @param string $transactionStatus  Status
 */
logTransaction($gatewayParams['name'], $_POST, $transactionStatus);

if ($success) {

    /**
     * Add Invoice Payment.
     *
     * Applies a payment transaction entry to the given invoice ID.
     *
     * @param int $invoiceId         Invoice ID
     * @param string $transactionId  Transaction ID
     * @param float $paymentAmount   Amount paid (defaults to full balance)
     * @param float $paymentFee      Payment fee (optional)
     * @param string $gatewayModule  Gateway module name
     */
    addInvoicePayment(
            $invoiceId, $transactionId, $paymentAmount, $paymentFee, $gatewayModuleName
    );
}