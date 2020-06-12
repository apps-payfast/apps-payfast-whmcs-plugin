<?php

/**
 * PayFast Payment Gateway
 * Developed by Tech Team at Avanza Premier Payment Services
 * 
 */
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function payfast_MetaData() {
    return array(
        'DisplayName' => 'PayFast Payment Gateway',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    );
}

function payfast_config() {
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'PayFast',
        ),
        'merchant_id' => array(
            'FriendlyName' => 'Merchant ID',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'PayFast Merchant ID',
        ),
        'merchant_name' => array(
            'FriendlyName' => 'Merchant Name',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => '',
        ),
        'secured_key' => array(
            'FriendlyName' => 'Secured Key',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'PayFast Secured Key',
        ),
        'secret_word' => array(
            'FriendlyName' => 'Secret Word',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Secret Word (optional)',
        )
    );
}

function payfast_link($params) {

    // Gateway Configuration Parameters
    $merchantId = $params['merchant_id'];
    $securedKey = $params['secured_key'];

    $phonenumber = $params['clientdetails']['phonenumber'];

    if ($phonenumber == '') {
        $phonenumber = '920000000000';
    }

    // System Parameters
    $whmcsVersion = $params['whmcsVersion'];

    $url = 'https://ipguat.apps.net.pk/Ecommerce/api/Transaction/PostTransaction';

    $moduleName = $params['paymentmethod'];
    $systemUrl = $params['systemurl'];
    $callback = $systemUrl . '/modules/gateways/payfast/' . $moduleName . '.php?invamount=' . $params['amount'];

    $signature = md5($merchantId . $securedKey . $params['amount']);

    $token = get_apps_auth_token($merchantId, $securedKey);

    $htmlOutput = '<form action="' . $url . '" method="post">
<input type="hidden" name="MERCHANT_ID" value="' . $merchantId . '">
    <input type="hidden" name="MERCHANT_NAME" value="' . $params['merchant_name'] . '">
<input type="hidden" name="TOKEN" value="' . $token . '">
<input type="hidden" name="PROCCODE" value="00" >
<input type="hidden" name="APP_PLUGIN" value="WHMCS" >
<input type="hidden" name="TXNAMT" value="' . $params['amount'] . '">
<input type="hidden" name="CUSTOMER_MOBILE_NO" value="' . $phonenumber . '">
<input type="hidden" name="CUSTOMER_EMAIL_ADDRESS" value="' . $params['clientdetails']['email'] . '">
<input type="hidden" name="SIGNATURE" value="' . $signature . '">
<input type="hidden" name="VERSION" value="WHMCS1.0-' . $whmcsVersion . '">
<input type="hidden" name="TXNDESC" value="' . $params['description'] . '">
<input type="hidden" name="CURRENCY_CODE" value="PKR">
<input type="hidden" name="SUCCESS_URL" value="' . $callback . '">
<input type="hidden" name="FAILURE_URL" value="' . $callback . '">
<input type="hidden" name="BASKET_ID" value="' . $params['invoiceid'] . '">            
<input type="hidden" name="ORDER_DATE" value="' . date('Y-m-d H:i:s', time()) . '">
<input type="hidden" name="CHECKOUT_URL" value="' . $callback . '">
<input type="submit" name="submit" value="Pay Now">
';


    $htmlOutput .= '</form>';
    return $htmlOutput;
}

function get_apps_auth_token($merchantid, $secret) {

    $token_url = "https://ipguat.apps.net.pk/Ecommerce/api/Transaction/GetAccessToken?MERCHANT_ID=%s&SECURED_KEY=%s";
    $token_url = sprintf($token_url, $merchantid, $secret);
    
    $response = curl_request($token_url);
    $response_decode = json_decode($response);

    if (isset($response_decode->ACCESS_TOKEN)) {
        return $response_decode->ACCESS_TOKEN;
    }

    return;
}

function curl_request($url, $data_string = '') {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'application/json; charset=utf-8    '
    ));
	curl_setopt($ch,CURLOPT_USERAGENT,'WHMCS-PayFast Plugin-PHP-CURL');
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}
