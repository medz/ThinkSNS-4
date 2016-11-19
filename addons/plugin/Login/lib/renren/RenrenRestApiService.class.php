<?php
/*
 * 调用人人网RESTful API的客户端类，本类需要继承RESTClient类方可使用
 * 要求最低的PHP版本是5.2.0，并且还要支持以下库：cURL, Libxml 2.6.0
 * This class for invoke RenRen RESTful Webservice
 * It MUST be extends RESTClient
 * The requirement of PHP version is 5.2.0 or above, and support as below:
 * cURL, Libxml 2.6.0
 *
 * @Modified by mike on 17:54 2011/12/21.
 * @Modified by Edison tsai on 14:41 2011/01/13 for wrap call_id & session_key in this class.
 *
 * @Version: 0.0.5 alpha
 * @Created: 17:09:40 2010/11/23
 * @Author:	Edison tsai<dnsing@gmail.com>
 * @Blog:	http://www.timescode.com
 * @Link:	http://www.dianboom.com
 */

 #Include configure resources

 class RenrenRestApiService extends HttpRequestService
 {
     private $_config;
     private $_postFields = '';
     private $_params = array();
     private $_currentMethod;
     private static $_sigKey = 'sig';
     private $_sig = '';
     private $_call_id = '';

     private $_keyMapping = array(
                'api_key' => '',
                'method' => '',
                'v' => '',
                'format' => '',
            );

     public function __construct()
     {
         global $config;

         parent::__construct();

         $this->_config = $config;

         if (empty($this->_config->APIURL) || empty($this->_config->APIKey) || empty($this->_config->SecretKey)) {
             throw new exception('Invalid API URL or API key or Secret key, please check config.inc.php');
         }
     }

    /**
     * GET wrapper
     * @param method String
     * @param parameters Array
     * @return mixed
     */
    public function GET()
    {
        $args = func_get_args();
        $this->_currentMethod = trim($args[0]); #Method
        $this->paramsMerge($args[1])
             ->getCallId()
             ->setConfigToMapping()
             ->generateSignature();

        #Invoke
        unset($args);

        return $this->_GET($this->_config->APIURL, $this->_params);
    }

    /**
     * POST wrapper，基于curl函数，需要支持curl函数才行
     * @param method String
     * @param parameters Array
     * @return mixed
     */
    public function rr_post_curl()
    {
        $args = func_get_args();
        $this->_currentMethod = trim($args[0]); #Method
        $this->paramsMerge($args[1])
             ->getCallId()
             ->setConfigToMapping()
             ->generateSignature();

        #Invoke
        unset($args);

        return $this->_POST($this->_config->APIURL, $this->_params);
    }
    /**
     * Generate signature for sig parameter
     * @param method String
     * @param parameters Array
     * @return RenRenClient
     */
    private function generateSignature()
    {
        $arr = array_merge($this->_params, $this->_keyMapping);
        ksort($arr);
        reset($arr);
        $str = '';
        foreach ($arr as $k => $v) {
            $v = $this->convertEncoding($v, $this->_encode, 'utf-8');
            $arr[$k] = $v; //转码，你懂得
                $str .= $k.'='.$v;
        }

        $this->_params = $arr;
        $str = md5($str.$this->_config->SecretKey);
        $this->_params[self::$_sigKey] = $str;
        $this->_sig = $str;

        unset($str, $arr);

        return $this;
    }

    /**
     * Parameters merge
     * @param $params Array
     * @modified by Edison tsai on 15:56 2011/01/13 for fix non-object bug
     * @return RenRenClient
     */
    private function paramsMerge($params)
    {
        $this->_params = $params;

        return $this;
    }

    /**
     * Setting mapping value
     * @modified by Edison tsai on 15:04 2011/01/13 for add call id & session_key
     * @return RenRenClient
     */
    private function setConfigToMapping()
    {
        $this->_keyMapping['api_key'] = $this->_config->APIKey;
        $this->_keyMapping['method'] = $this->_currentMethod;
        $this->_keyMapping['v'] = $this->_config->APIVersion;
        $this->_keyMapping['format'] = $this->_config->decodeFormat;

        return $this;
    }

     private function setAPIURL($url)
     {
         $this->_config->APIURL = $url;
     }

    /**
     * Generate call id
     * @author Edison tsai
     * @created 14:48 2011/01/13
     * @return RenRenClient
     */
    public function getCallId()
    {
        $this->_call_id = str_pad(mt_rand(1, 9999999999), 10, 0, STR_PAD_RIGHT);

        return $this;
    }

     public function rr_post_fopen()
     {
         $args = func_get_args();
         $this->_currentMethod = trim($args[0]); #Method
        $this->paramsMerge($args[1])
             ->getCallId()
             ->setConfigToMapping()
             ->generateSignature();

        #Invoke
        unset($args);

         return $this->_POST_FOPEN($this->_config->APIURL, $this->_params);
     }
     public function rr_photo_post_fopen()
     {
         $args = func_get_args();
         $this->_currentMethod = trim($args[0]); #Method
        $this->paramsMerge($args[1])
             ->getCallId()
             ->setConfigToMapping()
             ->generateSignature();

        #Invoke
        $photo_files = $args[2];

         unset($args);

         return $this->_photoUpload($this->_config->APIURL, $this->_params, $photo_files);
     }
 }
