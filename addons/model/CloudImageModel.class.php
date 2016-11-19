<?php

class CloudImageModel
{
    // 上传文件的最大值
    public $maxSize = 2048000;

    // 允许上传的文件后缀,留空不作后缀检查
    public $allowExts = '';

    // 允许上传的文件类型,留空不做检查
    public $allowTypes = '';

    // 使用对上传图片进行缩略图处理
    public $thumb = false;

    // 缩略图最大宽度
    public $thumbMaxWidth = 1024;

    // 缩略图最大高度
    public $thumbMaxHeight = 2048;

    // 上传文件保存路径
    public $customPath = '';
    public $savePath = '';
    public $saveName = '';

    // 上传文件Hash规则函数名,可以是 md5_file sha1_file 等
    public $hashType = 'md5_file';

    public $autoCheck = true;

    // 错误信息
    private $error = '';

    // 上传成功的文件信息
    private $uploadFileInfo ;

    // 又拍云配置
    private $config;

    //构造函数
    public function __construct()
    {
        $this->config = $this->getConfig();
    }

    //获取又拍云配置
    public function getConfig()
    {
        $config = model('Xdata')->get('admin_Config:cloudimage');

        return $config;
    }

    //是否开启Upyun
    public function isOpen()
    {
        return intval($this->config['cloud_image_open']);
    }

    //获取上传地址
    public function getUploadUrl()
    {
        return $this->config['cloud_image_api_url'].'/'.$this->config['cloud_image_bucket'].'/';
    }

    //获取上传地址
    public function getImageUrl($filename, $width, $height, $cut)
    {
        $filename = str_replace('//', '/', '/'.trim($filename));
        $prefix_urls = trim($this->config['cloud_image_prefix_urls']);
        $prefix_urls = explode(',', $prefix_urls);
        $prefix_urls = array_filter($prefix_urls);
        if (count($prefix_urls) > 1) {
            $prefix_key = abs(crc32($filename) % count($prefix_urls));
            $cloud_image_prefix_url = $prefix_urls[$prefix_key];
        } else {
            $cloud_image_prefix_url = $prefix_urls[0];
        }
        $cloud_image_prefix_url = trim($cloud_image_prefix_url);
        if ($width && $height) {
            return $cloud_image_prefix_url.$filename.'!'.$width.'x'.$height.(($cut) ? '.cut' : '').'.jpg';
        } else {
            return $cloud_image_prefix_url.$filename;
        }
    }

    //获取上传图片信息
    public function getImageInfo($filename)
    {
        $image_url = $this->getImageUrl($filename);
        $exif = file_get_contents($image_url.'!exif');
        if (!$exif) {
            return false;
        } else {
            return json_decode($exif);
        }
    }

    public function getFileInfo($filename)
    {
        //上传到云服务器
        $config = $this->getConfig();
        tsload(ADDON_PATH.'/library/upyun.class.php');
        $cloud = new UpYun($config['cloud_image_bucket'], $config['cloud_image_admin'], $config['cloud_image_password']);
        $cloud->setTimeout(60);
        $res = $cloud->getFileInfo($filename);

        return $res;
    }

    //获取附件URL前缀
    public function getUrlPrefix()
    {
        return $this->config['cloud_image_url'];
    }

    //获取表单API相关信息
    public function getPolicydoc()
    {
        $policydoc = array(
           'bucket' => $this->config['cloud_image_bucket'],
           'expiration' => time() + 600, //1分钟超时
           'save-key' => '/{year}/{mon}{day}/{random}.{suffix}',
           'allow-file-type' => 'jpg,jpeg,gif,png',
           'content-length-range' => '0,5120000',    //最大5M
        );

        return $policydoc;
    }

    //获取policy
    public function getPolicy()
    {
        $policy = base64_encode(json_encode($this->getPolicydoc()));

        return $policy;
    }

    //获取signature
    public function getSignature()
    {
        $signature = md5($this->getPolicy().'&'.$this->config['cloud_image_form_api_key']);

        return $signature;
    }

    /**
     * 写入文件
     * @param  string $filename    文件相对路径
     * @param  string $filecontent 文件数据
     * @return bool
     */
    public function writeFile($filename, $filecontent)
    {
        //上传到云服务器
        $config = $this->getConfig();
        tsload(ADDON_PATH.'/library/upyun.class.php');
        $cloud = new UpYun($config['cloud_image_bucket'], $config['cloud_image_admin'], $config['cloud_image_password']);
        $cloud->setTimeout(60);
        $res = $cloud->writeFile($filename, $filecontent, true);
        if (!$res) {
            $this->error = '上传到云服务器失败！';

            return false;
        } else {
            return true;
        }
    }

    /**
     * 删除文件
     * @param  string $filename 文件相对路径
     * @return bool
     */
    public function deleteFile($filename)
    {
        //上传到云服务器
        $config = $this->getConfig();
        tsload(ADDON_PATH.'/library/upyun.class.php');
        $cloud = new UpYun($config['cloud_image_bucket'], $config['cloud_image_admin'], $config['cloud_image_password']);
        $cloud->setTimeout(60);
        $res = $cloud->deleteFile($filename);
        if (!$res) {
            $this->error = '删除云服务器文件失败！';

            return false;
        } else {
            return true;
        }
    }

    /**
     * 上传文件
     * @param  string         $savePath 上传文件保存路径
     * @return string
     * @throws ThinkExecption
     */
    public function upload($savePath = '')
    {
        if (!$this->isOpen()) {
            $this->error = '没有开启云图片功能';

            return false;
        }

        $fileInfo = array();
        $isUpload = false;

        // 获取上传的文件信息,对$_FILES数组信息处理
        $files = $this->dealFiles($_FILES);
        foreach ($files as $key => $file) {
            //过滤无效的上传
            if (!empty($file['name'])) {
                $file['key'] = $key;
                $file['extension'] = $this->getExt($file['name']);

                if ($this->savePath) {
                    $file['savepath'] = $this->savePath;
                } else {
                    $file['savepath'] = $this->customPath;
                }

                if ($this->saveName) {
                    $file['savename'] = $this->saveName;
                } else {
                    $file['savename'] = uniqid().'.'.$file['extension'];
                }

                //移动设备上传的无后缀的图片，默认为jpg
                if ($GLOBALS['fromMobile'] == true && empty($file['extension'])) {
                    $file['extension'] = 'jpg';
                    $file['savename'] = trim($file['savename'], '.').'.jpg';
                } elseif ($this->autoCheck) {
                    if (!$this->check($file)) {
                        return false;
                    }
                }

                //计算hash
                if (function_exists($this->hashType)) {
                    $fun = $this->hashType;
                    $file['hash'] = $fun($file['tmp_name']);
                }

                //上传到云服务器
                $config = $this->getConfig();
                tsload(ADDON_PATH.'/library/upyun.class.php');
                $cloud = new UpYun($config['cloud_image_bucket'], $config['cloud_image_admin'], $config['cloud_image_password']);
                $cloud->setTimeout(60);

                $file_content = file_get_contents($file['tmp_name']);
                $res = $cloud->writeFile('/'.$file['savepath'].$file['savename'], $file_content, true);
                if (!$res) {
                    $this->error = '上传到云服务器失败！';

                    return false;
                }

                //上传成功后保存文件信息，供其它地方调用
                unset($file['tmp_name'], $file['error'], $file_content);

                $fileInfo[] = $file;
                $isUpload = true;
            }
        }

        if ($isUpload) {
            $this->uploadFileInfo = $fileInfo;

            return true;
        } else {
            $this->error = '上传出错！文件不符合上传要求。';

            return false;
        }
    }

    /**
     * 转换上传文件数组变量为正确的方式
     * @param  array $files 上传的文件变量
     * @return array
     */
    private function dealFiles($files)
    {
        $fileArray = array();
        foreach ($files as $file) {
            if (is_array($file['name'])) {
                $keys = array_keys($file);
                $count = count($file['name']);
                for ($i = 0; $i < $count; $i++) {
                    foreach ($keys as $key) {
                        $fileArray[$i][$key] = $file[$key][$i];
                    }
                }
            } else {
                $fileArray = $files;
            }
            break;
        }

        return $fileArray;
    }

    /**
     * 获取错误代码信息
     * @param  string         $errorNo 错误号码
     * @throws ThinkExecption
     */
    protected function error($errorNo)
    {
        switch ($errorNo) {
            case 1:
                $size = ini_get('upload_max_filesize');
                if (strpos($size, 'M') !== false || strpos($size, 'm') !== false) {
                    $size = intval($size) * 1024;
                    $size = byte_format($size);
                }
                //edit by  yangjs
                if (isset($this->maxSize) && !empty($this->maxSize)) {
                    $size = byte_format($this->maxSize);
                }
                $this->error = '上传文件大小不符，文件不能超过 '.$size;
                break;
            case 2:
                $size = ini_get('upload_max_filesize');
                if (strpos($size, 'M') !== false || strpos($size, 'm') !== false) {
                    $size = intval($size) * 1024;
                    $size = byte_format($size);
                }
                //edit by  yangjs
                if (isset($this->maxSize) && !empty($this->maxSize)) {
                    $size = byte_format($this->maxSize);
                }
                $this->error = '上传文件大小不符，文件不能超过 '.$size;
                break;
            case 3:
                $this->error = '文件只有部分被上传';
                break;
            case 4:
                $this->error = '没有文件被上传';
                break;
            case 6:
                $this->error = '找不到临时文件夹';
                break;
            case 7:
                $this->error = '文件写入失败';
                break;
            default:
                $this->error = '未知上传错误！';
        }

        return ;
    }

    /**
     * 检查上传的文件
     * @param  array $file 文件信息
     * @return bool
     */
    private function check($file)
    {
        if ($file['error'] !== 0) {
            //文件上传失败
            //捕获错误代码
            $this->error($file['error']);

            return false;
        }
        //文件上传成功，进行自定义规则检查
        //检查文件大小
        if (!$this->checkSize($file['size'])) {
            $this->error = '上传文件大小不符,文件不能超过 '.byte_format($this->maxSize);

            return false;
        }

        //检查文件Mime类型
        if (!$this->checkType($file['type'])) {
            $this->error = '上传文件MIME类型不允许！';

            return false;
        }
        //检查文件类型
        if (!$this->checkExt($file['extension'])) {
            $this->error = '上传文件类型不允许';

            return false;
        }

        //检查是否合法上传
        if (!$this->checkUpload($file['tmp_name'])) {
            $this->error = '非法上传文件！';

            return false;
        }

        return true;
    }

    /**
     * 检查上传的文件类型是否合法
     * @param  string $type 数据
     * @return bool
     */
    private function checkType($type)
    {
        if (!empty($this->allowTypes)) {
            if (!is_array($this->allowTypes)) {
                $this->allowTypes = explode(',', $this->allowTypes);
            }

            return in_array(strtolower($type), $this->allowTypes);
        }

        return true;
    }

    /**
     * 检查上传的文件后缀是否合法
     * @param  string $ext 后缀名
     * @return bool
     */
    private function checkExt($ext)
    {
        if (!empty($this->allowExts)) {
            if (!is_array($this->allowExts)) {
                $this->allowExts = explode(',', $this->allowExts);
            }

            return in_array(strtolower($ext), $this->allowExts, true);
        }

        return true;
    }

    /**
     * 检查文件大小是否合法
     * @param  int  $size 数据
     * @return bool
     */
    private function checkSize($size)
    {
        return !($size > $this->maxSize) || (-1 == $this->maxSize);
    }

    /**
     * 检查文件是否非法提交
     * @param  string $filename 文件名
     * @return bool
     */
    private function checkUpload($filename)
    {
        return is_uploaded_file($filename);
    }

    /**
     * 取得上传文件的后缀
     * @param  string $filename 文件名
     * @return bool
     */
    private function getExt($filename)
    {
        $pathinfo = pathinfo($filename);

        return $pathinfo['extension'];
    }

    /**
     * 取得上传文件的信息
     * @return array
     */
    public function getUploadFileInfo()
    {
        return $this->uploadFileInfo;
    }

    /**
     * 取得最后一次错误信息
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->error;
    }
}
