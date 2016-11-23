<?php
/*
    微信支付类
    生成ios直接可以调用的支付链接
 */
class WeChatPay
{
    public $unifiedorder_url = 'https://api.mch.weixin.qq.com/pay/unifiedorder'; //微信统一下单地址

    public $clientpay_url = 'weixin://app/%s/pay/?nonceStr=%s&package=Sign%%3DWXPay&partnerId=%s&prepayId=%s&timeStamp=%s&sign=%s&signType=SHA1'; //给IOS调用微信客户端支付协议的url

    //预支付订单参数
    // $param=array(
    // "body" => "积分充值:".$data['charge_sroce'].'积分',		//商品描述交易字段格式根据不同的应用场景按照以下格式：APP——需传入应用市场上的APP名字-实际商品名称，天天爱消除-游戏充值。
    // "appid" => $chargeConfigs['weixin_pid'],					//微信开放平台审核通过的应用APPID
    // "device_info" => "APP",									//终端设备号(门店号或收银设备ID)，默认请传"WEB"
    // "mch_id" => $chargeConfigs['weixin_mid'], 				//微信支付分配的商户号
    // "nonce_str" => mt_rand(),								//随机字符串，不长于32位
    // "notify_url" => SITE_URL.'/weixin_notify_api.php',		//接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
    // "out_trade_no" => $data['serial_number'],				//商户系统内部的订单号,32个字符内、可包含字母
    // "spbill_create_ip" => $ip, 								//客户端ip
    // "total_fee" => $data['charge_value']*100,				//这里的最小单位是分，跟支付宝不一样。1就是1分钱。只能是整形。
    // "trade_type" => "APP"									//支付类型
    // );

    //微信sign加密方法
    public function setWXsign($param, $wxkey)
    {
        ksort($param);
        $sign = '';
        foreach ($param as $key => $value) {
            if ($value && $key != 'sign' && $key != 'key') {
                $sign .= $key.'='.$value.'&';
            }
        }
        $sign .= 'key='.$wxkey;
        $sign = strtoupper(md5($sign));

        return $sign;
    }

    //设置下单需要传入的xml
    public function setxml($param, $sign)
    {
        $xml = "<xml>\n";
        foreach ($param as $key => $value) {
            $xml .= '<'.$key.'>'.$value.'</'.$key.">\n";
        }
        $xml .= '<sign>'.$sign."</sign>\n";
        $xml .= '</xml>';
        $opts = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type: text/xml',
                'content' => $xml,
                ),
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                ),
            );

        return $opts;
    }


    //微信预下单 并获得回调参数
    public function getUnifiedResult($opts)
    {
        $context = stream_context_create($opts);
        $result = file_get_contents($this->unifiedorder_url, false, $context); //微信支付下单地址
        $result = simplexml_load_string($result, null, LIBXML_NOCDATA);

        return $result;
    }


    /*
          返回给客户端调用的支付协议链接或参数数组
        $pid 应用id
        $mid 商户id
        $key 商户加密key
        $type  1-返回支付协议链接  2-返回支付调用参数
        预下单请求微信服务器得到noncestr和prepayid参数。
     */
    public function getClientPayUrl($pid, $noncestr, $mid, $prepayid, $key, $type = 1)
    {
        $input = array(
            'noncestr' => ''.$noncestr,
            'prepayid' => ''.$prepayid, //上一步请求微信服务器得到nonce_str和prepay_id参数。
            'appid' => $pid,
            'package' => 'Sign=WXPay',
            'partnerid' => $mid,
            'timestamp' => time(),
            );
        $sign = $this->setWXsign($input, $key);

        switch ($type) {
            case 1:

                return sprintf($this->clientpay_url, $pid, $noncestr, $mid, $prepayid, $input['timestamp'], $sign);
                break;
            case 2:
                $input['sign'] = $sign;

                return $input;
                break;
        }
    }

    //返给微信支付的成功标识
    public function successReturn()
    {
        echo   '<xml>
                <return_code><![CDATA[SUCCESS]]></return_code>
                </xml>'; //返回成功
    }

    //返给微信支付的失败信息
    public function errorReturn($msg)
    {
        echo   '<xml>
              	<return_code><![CDATA[FAIL]]></return_code>
  				<return_msg><![CDATA['.$msg.']]></return_msg>
                </xml>'; //返回成功
    }

    //判断weixin回调参数
    //返回参数为对象
    public function notifyReturn($key)
    {
        $result = file_get_contents('php://input');
        $result = simplexml_load_string($result, null, LIBXML_NOCDATA);
        $sign = $this->setWXsign($result, $key);
        if ($sign == $result->sign) {
            $this->successReturn();

            return $result;
        } else {
            $this->errorReturn('签名错误');

            return false;
        }
    }

    /*
        下单流程接口
        $type  1-返回支付协议链接  2-返回支付调用参数
     */
    public function getPayParam($param, $pid, $mid, $key, $type = 1)
    {
        $sign = $this->setWXsign($param, $key);
        $opts = $this->setxml($param, $sign);
        $result = $this->getUnifiedResult($opts);
        $return = $this->getClientPayUrl($pid, $result->nonce_str, $mid, $result->prepay_id, $key, $type);

        return $return;
    }
}
