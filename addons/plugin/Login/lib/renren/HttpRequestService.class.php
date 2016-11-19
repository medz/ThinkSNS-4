<?php
/*
 * 调用远程RESTful的客户端类
 * 要求最低的PHP版本是5.2.0，并且还要支持以下库：cURL, Libxml 2.6.0
 * This class for invoke remote RESTful Webservice
 * The requirement of PHP version is 5.2.0 or above, and support as below:
 * cURL, Libxml 2.6.0
 *
 * @Version: 0.0.1 alpha
 * @Created: 11:06:48 2010/11/23
 * @Author:	Edison tsai<dnsing@gmail.com>
 * @Blog:	http://www.timescode.com
 * @Link:	http://www.dianboom.com
 */

 class HttpRequestService
 {
     #cURL Object
    private $ch;
  #Contains the last HTTP status code returned.
    public $http_code;
  #Contains the last API call.
    private $http_url;
  #Set up the API root URL.
    public $api_url;
  #Set timeout default.
    public $timeout = 10;
  #Set connect timeout.
    public $connecttimeout = 30;
  #Verify SSL Cert.
    public $ssl_verifypeer = false;
  #Response format.
    public $format = ''; // Only support json & xml for extension
    public $decodeFormat = 'json'; //default is json
    public $_encode = 'utf-8';
  #Decode returned json data.
    //public $decode_json = true;
  #Contains the last HTTP headers returned.
    public $http_info = array();
     public $http_header = array();
     private $contentType;
     private $postFields;
     private static $paramsOnUrlMethod = array('GET', 'DELETE');
     private static $supportExtension = array('json', 'xml');
  #For tmpFile
    private $file = null;
  #Set the useragnet.
    private static $userAgent = 'Timescode_RESTClient v0.0.1-alpha';

     public function __construct()
     {
         $this->ch = curl_init();
        /* cURL settings */
        curl_setopt($this->ch, CURLOPT_USERAGENT, self::$userAgent);
         curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
         curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
         curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($this->ch, CURLOPT_AUTOREFERER, true);
         curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
         curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Expect:'));
         curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
         curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
         curl_setopt($this->ch, CURLOPT_HEADER, false);
     }

    /**
     * Execute calls
     * @param $url String
     * @param $method String
     * @param $postFields String
     * @param $username String
     * @param $password String
     * @param $contentType String
     * @return RESTClient
     */
    public function call($url, $method, $postFields = null, $username = null, $password = null, $contentType = null)
    {
        if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0 && !empty($this->format)) {
            $url = "{$this->api_url}{$url}.{$this->format}";
        }

        $this->http_url = $url;
        $this->contentType = $contentType;
        $this->postFields = $postFields;

        $url = in_array($method, self::$paramsOnUrlMethod) ? $this->to_url() : $this->get_http_url();

        is_object($this->ch) or $this->__construct();

        switch ($method) {
          case 'POST':
            curl_setopt($this->ch, CURLOPT_POST, true);
            if ($this->postFields != null) {
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->postFields);
            }
            break;
          case 'DELETE':
            curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
          case 'PUT':
            curl_setopt($this->ch, CURLOPT_PUT, true);
            if ($this->postFields != null) {
                $this->file = tmpFile();
                fwrite($this->file, $this->postFields);
                fseek($this->file, 0);
                curl_setopt($this->ch, CURLOPT_INFILE, $this->file);
                curl_setopt($this->ch, CURLOPT_INFILESIZE, strlen($this->postFields));
            }
            break;
        }

        $this->setAuthorizeInfo($username, $password);
        $this->contentType != null && curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Content-type:'.$this->contentType));

        curl_setopt($this->ch, CURLOPT_URL, $url);

        $response = curl_exec($this->ch);
        $this->http_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        $this->http_info = array_merge($this->http_info, curl_getinfo($this->ch));

        $this->close();

        return $response;
    }
     public function call_fopen($url, $method, $postFields = null, $username = null, $password = null, $contentType = null)
     {
         if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0 && !empty($this->format)) {
             $url = "{$this->api_url}{$url}.{$this->format}";
         }

         $this->http_url = $url;
         $this->contentType = $contentType;
         $this->postFields = $postFields;

         $url = in_array($method, self::$paramsOnUrlMethod) ? $this->to_url() : $this->get_http_url();
         $params = array('http' => array(
               'method' => 'POST',
               'header' => 'Content-type: application/x-www-form-urlencoded'."\r\n",
               'content' => $this->create_post_body($this->postFields),
            ));
         $ctx = stream_context_create($params);
         $fp = fopen($url, 'rb', false, $ctx);

         if (!$fp) {
             die('can not open server!');
         }

         $response = @stream_get_contents($fp);
         if ($response === false) {
             die('can not get message form server!');
          //throw new Exception("Problem reading data from {$url}, {$php_errormsg}");
         }

         return $response;
     }

     public function setEncode($encode)
     {
         !empty($encode) and $this->_encode = $encode;

         return $this;
     }
     public static function convertEncoding($source, $in, $out)
     {
         $in = strtoupper($in);
         $out = strtoupper($out);
         if ($in == 'UTF8') {
             $in = 'UTF-8';
         }
         if ($out == 'UTF8') {
             $out = 'UTF-8';
         }
         if ($in == $out) {
             return $source;
         }

         if (function_exists('mb_convert_encoding')) {
             return mb_convert_encoding($source, $out, $in);
         } elseif (function_exists('iconv')) {
             return iconv($in, $out.'//IGNORE', $source);
         }

         return $source;
     }
    /**
     * POST wrapper，不基于curl函数，环境可以不支持curl函数
     * @param method String
     * @param parameters Array
     * @return mixed
     */
    public function do_post_request($url, $postdata, $files)
    {
        $data = '';
        $boundary = '---------------------'.substr(md5(rand(0, 32000)), 0, 10);

        //Collect Postdata
        foreach ($postdata as $key => $val) {
            $data .= "--$boundary\r\n";
            $data .= 'Content-Disposition: form-data; name="'.$key."\"\r\n\r\n".$val."\r\n";
        }
        $data .= "--$boundary\r\n";

        //Collect Filedata
        foreach ($files as $key => $file) {
            $fileContents = file_get_contents($file['tmp_name']);
            $data .= "Content-Disposition: form-data; name=\"{$key}\"; filename=\"{$file['name']}\"\r\n";
            $data .= 'Content-Type: '.$file['type']."\r\n";
            $data .= "Content-Transfer-Encoding: binary\r\n\r\n";
            $data .= $fileContents."\r\n";
            $data .= "--$boundary--\r\n";
        }

        $params = array('http' => array(
               'method' => 'POST',
               'header' => 'Content-Type: multipart/form-data; boundary='.$boundary,
               'content' => $data,
            ));

        $ctx = stream_context_create($params);
        $fp = fopen($url, 'rb', false, $ctx);

        if (!$fp) {
            die('can not open server!');
        }

        $response = @stream_get_contents($fp);
        if ($response === false) {
            die('can not get message form server!');
          //throw new Exception("Problem reading data from {$url}, {$php_errormsg}");
        }

        return $response;
    }
     /**
      * POST wrapper for insert data
      * @param $url String
      * @param $params mixed
      * @param $username String
      * @param $password String
      * @param $contentType String
      * @return RESTClient
      */
     public function _POST($url, $params = null, $username = null, $password = null, $contentType = null)
     {
         $response = $this->call($url, 'POST', $params, $username, $password, $contentType);
         //$this->convertEncoding($response,'utf-8',$this->_encode)
         return $this->json_foreach($this->parseResponse($response));
     }

     public function _POST_FOPEN($url, $params = null, $username = null, $password = null, $contentType = null)
     {
         $response = $this->call_fopen($url, 'POST', $params, $username, $password, $contentType);

         return $this->json_foreach($this->parseResponse($response));
     }
     public function _photoUpload($url, $postdata, $files)
     {
         $response = $this->do_post_request($url, $postdata, $files);

         return $this->json_foreach($this->parseResponse($response));
     }
     //将stdclass object转换成数组，并转换编码
     public function json_foreach($jsonArr)
     {
         if (is_object($jsonArr)) {
             $jsonArr = get_object_vars($jsonArr);
         }
         foreach ($jsonArr as $k => $v) {
             if (is_array($v)) {
                 $jsonArr[$k] = $this->json_foreach($v);
             } elseif (is_object($v)) {
                 $v = get_object_vars($v);
                 $jsonArr[$k] = $this->json_foreach($v);
             } else {
                 $v = $this->convertEncoding($v, 'utf-8', $this->_encode);
                 $jsonArr[$k] = $v;
             }
         }

         return $jsonArr;
     }
     /**
      * PUT wrapper for update data
      * @param $url String
      * @param $params mixed
      * @param $username String
      * @param $password String
      * @param $contentType String
      * @return RESTClient
      */
     public function _PUT($url, $params = null, $username = null, $password = null, $contentType = null)
     {
         $response = $this->call($url, 'PUT', $params, $username, $password, $contentType);

         return $this->parseResponse($this->convertEncoding($response, 'utf-8', $this->_encode));
     }
     public function create_post_body($post_params)
     {
         $params = array();
         foreach ($post_params as $key => &$val) {
             if (is_array($val)) {
                 $val = implode(',', $val);
             }
             $params[] = $key.'='.urlencode($val);
         }

         return implode('&', $params);
     }
     /**
      * GET wrapper for get data
      * @param $url String
      * @param $params mixed
      * @param $username String
      * @param $password String
      * @return RESTClient
      */
     public function _GET($url, $params = null, $username = null, $password = null)
     {
         $response = $this->call($url, 'GET', $params, $username, $password);

         return $this->parseResponse($response);
     }

     /**
      * DELETE wrapper for delete data
      * @param $url String
      * @param $params mixed
      * @param $username String
      * @param $password String
      * @return RESTClient
      */
     public function _DELETE($url, $params = null, $username = null, $password = null)
     {
         #Modified by Edison tsai on 09:50 2010/11/26 for missing part
         $response = $this->call($url, 'DELETE', $params, $username, $password);

         return $this->parseResponse($response);
     }

     /*
     * Parse response, including json, xml, plain text
     * @param $resp String
     * @param $ext	String, including json/xml
     * @return String
     */
     public function parseResponse($resp, $ext = '')
     {
         $ext = !in_array($ext, self::$supportExtension) ? $this->decodeFormat : $ext;

         switch ($ext) {
                case 'json':
                    $resp = json_decode($resp); break;
                case 'xml':
                    $resp = self::xml_decode($resp); break;
        }

         return $resp;
     }

     /*
     * XML decode
     * @param $data String
     * @param $toArray boolean, true for make it be array
     * @return String
     */
      public static function xml_decode($data, $toArray = false)
      {
          /* TODO: What to do with 'toArray'? Just write it as you need. */
            $data = simplexml_load_string($data);

          return $data;
      }

     public static function objectToArray($obj)
     {
     }

      /**
       * parses the url and rebuilds it to be
       * scheme://host/path
       */
      public function get_http_url()
      {
          $parts = parse_url($this->http_url);

          $port = @$parts['port'];
          $scheme = $parts['scheme'];
          $host = $parts['host'];
          $path = @$parts['path'];

          $port or $port = ($scheme == 'https') ? '443' : '80';

          if (($scheme == 'https' && $port != '443')
            || ($scheme == 'http' && $port != '80')) {
              $host = "$host:$port";
          }

          return "$scheme://$host$path";
      }

      /**
       * builds a url usable for a GET request
       */
      public function to_url()
      {
          $post_data = $this->to_postdata();
          $out = $this->get_http_url();
          if ($post_data) {
              $out .= '?'.$post_data;
          }

          return $out;
      }

      /**
       * builds the data one would send in a POST request
       */
      public function to_postdata()
      {
          return http_build_query($this->postFields);
      }

     /**
      * Settings that won't follow redirects
      * @return RESTClient
      */
     public function setNotFollow()
     {
         curl_setopt($this->ch, CURLOPT_AUTOREFERER, false);
         curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, false);

         return $this;
     }

     /**
      * Closes the connection and release resources
      */
     public function close()
     {
         curl_close($this->ch);
         if ($this->file != null) {
             fclose($this->file);
         }
     }

     /**
      * Sets the URL to be Called
      * @param $url String
      */
     public function setURL($url)
     {
         $this->url = $url;
     }

     /**
      * Sets the format type to be extension
      * @param $format String
      * @return bool
      */
     public function setFormat($format = null)
     {
         if ($format == null) {
             return false;
         }
         $this->format = $format;

         return true;
     }

     /**
      * Sets the format type to be decoded
      * @param $format String
      * @return bool
      */
     public function setDecodeFormat($format = null)
     {
         if ($format == null) {
             return false;
         }
         $this->decodeFormat = $format;

         return true;
     }

     /**
      * Set the Content-Type of the request to be send
      * Format like "application/json" or "application/xml" or "text/plain" or other
      * @param string $contentType
      */
     public function setContentType($contentType)
     {
         $this->contentType = $contentType;
     }

     /**
      * Set the authorize info for Basic Authentication
      * @param $username String
      * @param $password String
      */
     public function setAuthorizeInfo($username, $password)
     {
         if ($username != null) { #The password might be blank
             curl_setopt($this->ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
             curl_setopt($this->ch, CURLOPT_USERPWD, "{$username}:{$password}");
         }
     }

     /**
      * Set the Request HTTP Method
      * @param $method String
      */
     public function setMethod($method)
     {
         $this->method = $method;
     }

     /**
      * Set Parameters to be send on the request
      * It can be both a key/value par array (as in array("key"=>"value"))
      * or a string containing the body of the request, like a XML, JSON or other
      * Proper content-type should be set for the body if not a array
      * @param $params mixed
      */
     public function setParameters($params)
     {
         $this->postFields = $params;
     }

      /**
       * Get the header info to store.
       */
      public function getHeader($ch, $header)
      {
          $i = strpos($header, ':');
          if (!empty($i)) {
              $key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
              $value = trim(substr($header, $i + 2));
              $this->http_header[$key] = $value;
          }

          return strlen($header);
      }
 }
