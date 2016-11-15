<?php

require_once dirname(__FILE__).'/lib/alipay_submit.class.php';
require_once dirname(__FILE__).'/lib/alipay_notify.class.php';

function getAlipayConfig(array $alipayConfig = null)
{
    $config = array(
        'partner' => '',
        'seller_email' => '',
        'key' => '',
        'sign_type' => 'MD5',
        'input_charset' => 'utf-8',
        'cacert' => dirname(__FILE__).'/cacert.pem',
        'transport' => 'http',
    );
    if ($alipayConfig) {
        $config = array_merge($config, $alipayConfig);
    }

    return $config;
}

function createAlipayUrl(array $alipayConfig, array $parameter, $type = 1)
{
    $alipayConfig = getAlipayConfig($alipayConfig);
    $parameter = array_merge(array(
        'service' => 'create_direct_pay_by_user',
        'partner' => trim($alipayConfig['partner']),
        'seller_email' => trim($alipayConfig['seller_email']),
        'payment_type' => 1,
        'notify_url' => '',
        'return_url' => '',
        'out_trade_no' => time(),
        'subject' => '支付订单',
        'total_fee' => 0.01,
        'body' => '',
        'show_url' => '',
        'anti_phishing_key' => '',
        'exter_invoke_ip' => '',
        '_input_charset' => trim(strtolower($alipayConfig['input_charset'])),
    ), $parameter);
    $alipaySubmit = new AlipaySubmit($alipayConfig);
    if ($type == 1) {
        $url = $alipaySubmit->alipay_gateway_new;
        $url .= $alipaySubmit->buildRequestParaToString($parameter);
    } else {
        $parameter['seller_id'] = trim($alipayConfig['partner']);

        $url = $alipaySubmit->alipay_client_url;
        $url .= urlencode(json_encode(array('requestType' => 'SafePay', 'fromAppUrlScheme' => 'com.zhiyiThinkSNS4', 'dataString' => $alipaySubmit->buildRequestParaToString($parameter))));
    }


    return $url;
}

function verifyAlipayReturn(array $alipayConfig)
{
    $alipayConfig = getAlipayConfig($alipayConfig);
    $alipayNotify = new AlipayNotify($alipayConfig);
    $verifyResult = $alipayNotify->verifyReturn();

    return (bool) $verifyResult;
}

function verifyAlipayNotify(array $alipayConfig)
{
    $alipayConfig = getAlipayConfig($alipayConfig);
    $alipayNotify = new AlipayNotify($alipayConfig);
    $verifyResult = (bool) $alipayNotify->verifyNotify();
    echo $verifyResult ? 'success' : 'fail';

    return $verifyResult;
}
