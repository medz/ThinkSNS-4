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
        'private_key_path' => '',
    );
    if ($alipayConfig) {
        $config = array_merge($config, $alipayConfig);
    }

    return $config;
}

function createAlipayUrl(array $alipayConfig, array $parameter, $type = 1)
{
    $alipayConfig = getAlipayConfig($alipayConfig);

    if ($type != 3) {//调用官方sdk 参数需指定传入
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
    }

    $alipaySubmit = new AlipaySubmit($alipayConfig);
    if ($type == 1) {
        $url = $alipaySubmit->alipay_gateway_new;
        $url .= $alipaySubmit->buildRequestParaToString($parameter);
    } elseif ($type == 2) {
        $parameter['seller_id'] = trim($alipayConfig['partner']);

        $url = $alipaySubmit->alipay_client_url;
        $url .= urlencode(json_encode(array('requestType' => 'SafePay', 'fromAppUrlScheme' => 'com.zhiyiThinkSNS4', 'dataString' => $alipaySubmit->buildRequestParaToString($parameter)))); //带客户端协议的参数拼接
    } elseif ($type == 3) {
        $url = $alipaySubmit->buildRequestParaToString($parameter);
    }

    return $url;
}

function alipaytest($param, $config)
{
    $privatekey = file_get_contents($config['private_key_path']);
    $res = openssl_pkey_get_private($privatekey);
    ksort($param);
    reset($param);
    openssl_sign(createRsaLinkstring($param), $sign, $res);
    openssl_free_key($sign);
    $sign = base64_encode($sign);
    $param['sign'] = urlencode($sign);
    $param['sign_type'] = 'RSA';
    $param['bizcontext'] = $config['biz_content'];
    // var_dump($param);var_dump(createRsaLinkstring($param));die;
    return createRsaLinkstring($param);
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
/**
 * RSA解密
 * @param $content 需要解密的内容，密文
 * @param $private_key_path 商户私钥文件路径
 * return 解密后内容，明文
 */
function rsaDecrypt($content, $private_key_path)
{
    $priKey = file_get_contents($private_key_path);
    $res = openssl_get_privatekey($priKey);
    //用base64将内容还原成二进制
    $content = base64_decode($content);
    //把需要解密的内容，按128位拆开解密
    $result = '';
    for ($i = 0; $i < strlen($content) / 128; $i++) {
        $data = substr($content, $i * 128, 128);
        openssl_private_decrypt($data, $decrypt, $res);
        $result .= $decrypt;
    }
    openssl_free_key($res);

    return $result;
}

/**
 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
 * @param $para 需要拼接的数组
 * return 拼接完成以后的字符串
 */
function createRsaLinkstring($para)
{
    $arg = '';
    while (list($key, $val) = each($para)) {
        $arg .= $key.'='.urlencode($val).'&';
    }
    //去掉最后一个&字符
    $arg = substr($arg, 0, count($arg) - 2);

    //如果存在转义字符，那么去掉转义
    if (get_magic_quotes_gpc()) {
        $arg = stripslashes($arg);
    }

    return $arg;
}
