<?php
/**
 * 短信模型
 *
 * @package ThinkSNS\Medz\Model\SMS
 * @author Medz Seven <lovevipdsw@vip.qq.com>
 **/
class SmsModel extends Model
{
    /**
     * 校验锁时间，单位秒
     *
     * @var int
     **/
    const LOCKTIME = 60;

    /**
     * 短信服务器地址
     *
     * @var string
     **/
    protected $url;

    /**
     * 发送服务器参数
     *
     * @var string
     **/
    protected $param;

    /**
     * 成功返回表示代码
     *
     * @var string
     **/
    protected $resultCode;

    /**
     * 发送方式
     * type [auto,post,get] auto标识get+post并存
     *
     * @var string
     **/
    protected $type = 'auto';

    /**
     * 短信平台提供商
     *
     * @var string
     **/
    protected $provider = 'auto';

    /**
     * 短信模板
     *
     * @var string
     **/
    protected $template = '您的验证码是：{rand}。如非本人操作，可不用理会！';

    /**
     * 消息
     *
     * @var string
     **/
    private $message;

    /**
     * 短信记录表
     *
     * @var string
     **/
    protected $tableName = 'sms';

    /**
     * 数据表保护字段成员
     *
     * @var array
     **/
    protected $fields = array('phone', 'code', 'message', 'time');

    /**
     * 储存Curl对象的变量
     *
     * @var object
     **/
    protected $curl;

    /**
     * 用户发送的手机号码
     *
     * @var int
     **/
    protected $phone;

    /**
     * 验证码代码
     *
     * @var int
     **/
    protected $code;

    /**
     * 构造方法 - 获取短信数据配置
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    final public function __construct($name = '')
    {
        parent::__construct($name);
        $conf = model('Xdata')->get('admin_Config:sms');

        $this->url = $conf['sms_server'];
        $this->param = $conf['sms_param'];
        $this->resultCode = strtolower($conf['success_code']);
        $this->type = $conf['send_type'];
        $this->provider = $conf['service'];

        $conf['template'] and $this->template = $conf['template'];

        unset($conf);

        $this->curl = curl_init();

        $this->code = rand(1000, 9999);
    }

    /**
     * 析构方法 - 主要关闭curl
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    final public function __destruct()
    {
        curl_close($this->curl);
    }

    /**
     * 设置消息
     *
     * @param string $message 消息
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    protected function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * 获取消息
     *
     * @return string
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * 获取验证码
     *
     * @return int
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function getCode()
    {
        return $this->code;
    }

    /**
     * 所以方法方法前置方法
     *
     * @return bool
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    protected function allBefore()
    {
        /* # 检查短信平台地址 */
        if (!$this->url) {
            $this->setMessage('短信平台地址为空');

            return false;

        /* # 检查手机号码是否为空 */
        } elseif (!$this->phone) {
            $this->setMessage('手机号码不能为空');

            return false;

        /* # 检查CURL对象 */
        } elseif (!$this->curl) {
            $this->setMessage('初始化发送组建失败！');
        }

        return true;
    }

    /**
     * 发送数据
     *
     * @return string
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    protected function curl()
    {
        /* # set url addres */
        curl_setopt($this->curl, CURLOPT_URL, $this->url);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($this->curl, CURLOPT_HEADER, false);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_NOBODY, true);
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->param);

        return curl_exec($this->curl);
    }

    /**
     * 发送
     *
     * @return bool
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    protected function send()
    {
        $result = $this->curl();
        switch (strtolower($this->provider)) {
            /* # 互亿无线 */
            case 'ihuyi':
                $result = $this->ihuyi($result);
                break;

            /* 短信宝 */
            case 'smsbao':
                $result = $this->smsbao($result);
                break;

            default:
                $result = $this->auto($result);
                break;
        }

        $result or $this->getMessage() or $this->setMessage('发送失败');

        return $result;
    }

    /*========================发送结果检验方法区域================*/

    /**
     * 自适应校验发送是否成功
     *
     * @param  string $data 数据;
     * @return bool
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    protected function auto($data)
    {
        $data = strtolower($data);

        return false !== strstr($data, $this->resultCode);
    }

    /**
     * 互亿无线平台校验
     *
     * @param  string $data 数据
     * @return bool
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    protected function ihuyi($data)
    {
        $data = simplexml_load_string($data);
        $data = json_encode($data);
        $data = json_decode($data, false);
        $this->setMessage($data->msg);
        if (intval($data->code) == 2) {
            return true;
        }

        return false;
    }

    /**
     * 短信宝 www.smsbao.com
     *
     * @param  string $data 数据
     * @return bool
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function smsbao($data)
    {
        $code = array(
            0 => '发送成功',
            30 => '密码错误',
            40 => '账号不存在',
            41 => '余额不足',
            42 => '帐号过期',
            43 => 'IP地址限制',
            50 => '内容含有敏感词',
            51 => '手机号码不正确',
        );
        $data = intval($data);
        $code[$data] and
        $this->setMessage($code[$data]);

        return !$data;
    }

    /*========================End=================================*/

    /**
     * 获取平台配置
     *
     * @return array
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getService()
    {
        return array(
            'auto' => '自动判断',
            'ihuyi' => '互亿无线',
            'smsbao' => '短信宝',
        );
    }

    /**
     * undocumented function
     *
     * @author
     **/
    protected function buildParam()
    {
        $this->param = str_replace('{tel}', $this->phone, $this->param);
        $this->template = str_replace('{rand}', $this->code, $this->template);
        $this->param = str_replace('{message}', rawurlencode($this->template), $this->param);

        if (in_array($this->type, array('auto', 'get'))) {
            $this->url = parse_url($this->url);
            isset($this->url['query']) and $this->url['query'] .= '&';
            $this->url['query'] .= $this->param;

            $url = '';
            $this->url['scheme'] and $url .= $this->url['scheme'].'://';
            $this->url['host'] and $url .= $this->url['host'];
            $this->url['port'] and $url .= ':'.$this->url['port'];
            $this->url['path'] and $url .= $this->url['path'];
            $this->url['query'] and $url .= '?'.$this->url['query'];
            $this->url = $url;
            unset($url);
        }
    }

    /**
     * 时间锁，检查是否不可以发送
     *
     * @return bool
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    protected function lock()
    {
        $lockName = 'captcha_lock_'.$this->phone;
        $lickTime = $_SESSION[$lockName];

        if ($lickTime > time()) {
            $this->setMessage('请不要频繁发送，'.($lickTime - time()).'秒再试。');

            return true;
        }

        return false;
    }

    /**
     * 时间加锁
     *
     * @param int $time 加锁的时间
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    protected function InLock($time = null)
    {
        /* # 判断是否有自定义事件 */
        $time or $time = time() + self::LOCKTIME;

        /* # 兼容格式化时间，转为时间戳 */
        is_numeric($time) or $time = strtotime($time);

        $_SESSION['captcha_lock_'.$this->phone] = time();
    }

    /**
     * 发送验证码
     *
     * @param int $phone 要发送到的手机号码
     * @param  bool $sendLock 发送锁，默认关闭
     * @return bool
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function sendCaptcha($phone, $sendLock = false)
    {
        $this->phone = $phone;
        /* # 前置检查必要信息 */
        if (!$this->allBefore()) {
            return false;

        /* # 检查锁 */
        } elseif ($sendLock and $this->lock()) {
            return false;
        }

        $this->buildParam();
        $this->InLock();

        $debug = false;
        if (isset($_REQUEST['client_debuging'])) {
            $this->setMessage($this->getCode());
            $debug = true;
            $result = false;
        }

        if ($debug || ($result = $this->send())) {
            $this->add(array(
                'phone' => $this->phone,
                'code' => $this->code,
                'time' => time(),
            ));
        }

        return $result;
    }

    /**
     * 校验验证码是否正确
     *
     * @param  float $phone 手机号码
     * @param  int   $code  验证码
     * @return bool
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function CheckCaptcha($phone, $code)
    {
        $data = $this->where('`phone` = '.floatval($phone).' AND `message` = \'\' AND `code` != 0 AND `time` > '.(time() - 1800))->field('code')->order('`time` DESC')->getField('code');
        $code = intval($code);
        /* # 判断验证码是否为空 */
        if (!$code) {
            $this->setMessage('验证码不能为空');

            return false;

        /* # 判断是否有发送记录数据 */
        } elseif (!$data) {
            $this->setMessage('没有发送记录');

            return false;

        /* # 判断是否匹配 */
        } elseif ($data != $code) {
            $this->setMessage('验证码不正确');

            return false;
        }

        return true;
    }

    /**
     * 发送短息消息
     *
     * @param int $phone 手机号码
     * @param  string $message  短信内容
     * @param  bool   $sendLock 时间锁， 默认关闭
     * @return bool
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function sendMessage($phone, $message, $sendLock = false)
    {
        $this->phone = $phone;
        $this->template = $message;

        /* # 前置必要条件检查 */
        if (!$this->allBefore()) {
            return false;

        /* # 锁检查 */
        } elseif ($sendLock and $this->lock()) {
            return false;
        }

        $this->buildParam();
        $this->InLock();

        if ($result = $this->send()) {
            $this->add(array(
                'phone' => $this->phone,
                'message' => $this->template,
                'time' => time(),
            ));
        }

        return $result;
    }

    /**
     * 发送验证码到邮箱
     *
     * @param  string $email    邮箱地址
     * @param  bool   $sendLock 是否有时间锁
     * @return bool
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function sendEmaillCaptcha($email, $sendLock = false)
    {
        $this->phone = $email;
        /* # 判断是否是email */
        if (!preg_match("/^\w+(?:[-+.']\w+)*@\w+(?:[-.]\w+)*\.\w+(?:[-.]\w+)*$/", $email)) {
            $this->setMessage('邮箱格式不正确');

            return false;
        } elseif ($sendLock and $this->lock()) {
            return false;
        }

        $site = model('Xdata')->get('admin_Config:site');
        $email = model('Mail');
        $title = $$GLOBALS['ts']['site']['site_name'].'验证码';
        $body = '您的验证码是：'.$this->code;
        $body = '<style>a.email_btn,a.email_btn:link,a.email_btn:visited{background:#0F8CA8;padding:5px 10px;color:#fff;width:80px;text-align:center;}</style><div style="width:540px;border:#0F8CA8 solid 2px;margin:0 auto"><div style="color:#bbb;background:#0f8ca8;padding:5px;overflow:hidden;zoom:1"><div style="float:right;height:15px;line-height:15px;padding:10px 0;display:none">2012年07月15日</div>
					<div style="float:left;overflow:hidden;position:relative"><a><img style="border:0 none" src="'.$GLOBALS['ts']['site']['site_logo'].'"></a></div></div>
					<div style="background:#fff;padding:20px;min-height:300px;position:relative">		<div style="font-size:14px;">
						            	<p style="padding:0 0 20px;margin:0;font-size:12px">'.$body.'</p>
						            </div></div><div style="background:#fff;">
			            <div style="text-align:center;height:18px;line-height:18px;color:#999;padding:6px 0;font-size:12px">若不想再收到此类邮件，请点击<a href="'.U('public/Account/notify').'" style="text-decoration:none;color:#3366cc">设置</a></div>
			            <div style="line-height:18px;text-align:center"><p style="color:#999;font-size:12px">'.$site['site_footer'].'</p></div>
			        </div></div>';

        if ($email->send_email($this->phone, $title, $body)) {
            $this->InLock();

            $this->add(array(
                'phone' => $this->phone,
                'code' => $this->code,
                'time' => time(),
            ));

            return true;
        }
        $this->setMessage($email->message);

        return false;
    }

    /**
     * 验证邮箱验证码正确性
     *
     * @param  string $email 邮箱地址
     * @param  int    $code  验证码
     * @return bool
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function checkEmailCaptcha($email, $code)
    {
        $code = intval($code);
        $data = $this->where('`phone` LIKE \''.t($email).'\' AND `code` != 0 AND `message` = \'\' AND time > '.(time() - 1800))->field('code')->order('`time` DESC')->getField('code');

        /* # 检查邮箱地址是否为空 */
        if (!$email) {
            $this->setMessage('邮箱不能为空');

            return false;

        /* # 需要检验的验证码是否为空 */
        } elseif (!$code) {
            $this->setMessage('验证码不能为空');

            return false;

        /* # 检查验证码是否正确 */
        } elseif ($data == $code) {
            return true;
        }

        $this->setMessage('验证码不正确');

        return false;
    }
} // END class SMSModel extends Model
