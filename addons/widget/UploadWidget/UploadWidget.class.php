<?php
/**
 * 文件上传
 * 主要由core.uploadFile完成前端ajaxpost功能
 * 要自定义回调显示则需要自定义回调函数
 * @example {:W('Upload',array('callback'=>'callback','uploadType'=>'file','inputname'=>'inputname','urlquery'=>'a=aaa&b=bb','attachIds'=>'1,2,3,4'))}
 * @author jason
 * @version TS3.0
 */

class UploadWidget extends Widget
{
    private static $rand = 1;

    /**
     * @param string callback 上传之后的回调函数，不自定义就不要传递此参数
     * @param string uploadType 上传类型，分file和image
     * @param string inputname 上传组件的name
     * @param string urlquery 需要存储在附件表的一些信息，暂时可能用到的不多
     * @param mixed attachIds 已有附件ID，可以为空
     */
    public function render($data)
    {
        $var = array();
        $var['callback'] = "''";
        $var['uploadType'] = 'file';
        $var['inputname'] = 'attach';
        $var['attachIds'] = '';
        $var['inForm'] = 1;
        $var['limit'] = empty($data['limit']) ? 0 : intval($data['limit']);

        is_array($data) && $var = array_merge($var, $data);

        $uploadType = in_array($var['uploadType'], array('image', 'file', 'cloudimage', 'cloudfile')) ? t($var['uploadType']) : 'file';
        $uploadTemplate = $uploadType.'upload.html';

        if (!empty($var['attachIds'])) {
            !is_array($var['attachIds']) && $var['attachIds'] = explode(',', $var['attachIds']);

            $attachInfo = model('Attach')->getAttachByIds($var['attachIds']);
            foreach ($attachInfo as $v) {
                if ($var['uploadType'] == 'image') {
                    $v['src'] = getImageUrl($v['save_path'].$v['save_name'], 100, 100, true);
                }
                $v['extension'] = strtolower($v['extension']);
                $var['attachInfo'][] = $v;
            }

            $var['attachIds'] = implode('|', $var['attachIds']);
        }

        //渲染模版
        $content = $this->renderFile(dirname(__FILE__).'/'.$uploadTemplate, $var);

        unset($var, $data);

        //输出数据
        return $content;
    }

    /**
     * 附件上传
     * @return array 上传的附件的信息
     */
    public function save()
    {
        $data['attach_type'] = t($_REQUEST['attach_type']);
        $data['upload_type'] = $_REQUEST['upload_type'] ? t($_REQUEST['upload_type']) : 'file';

        //针对后台页面同时包含文件和图片上传
        // if($data['upload_type']=='file'){
        //     unset($_FILES['attach']);
        // }else{
        //     unset($_FILES['upload_file']);
        // }

        $thumb = intval($_REQUEST['thumb']);
        $width = intval($_REQUEST['width']);
        $height = intval($_REQUEST['height']);
        $cut = intval($_REQUEST['cut']);

        //Addons::hook('widget_upload_before_save', &$data);

        $option['attach_type'] = $data['attach_type'];
        $info = model('Attach')->upload($data, $option);
        //Addons::hook('widget_upload_after_save', &$info);

        if ($info['status']) {
            $data = $info['info'][0];
            if ($thumb == 1) {
                $data['src'] = getImageUrl($data['save_path'].$data['save_name'], $width, $height, $cut);
            } else {
                $data['src'] = $data['save_path'].$data['save_name'];
            }

            $data['extension'] = strtolower($data['extension']);
            $return = array('status' => 1, 'data' => $data);
        } else {
            $return = array('status' => 0, 'data' => $info['info']);
        }

        $isAjaxUrl = isset($_REQUEST['isAjaxUrl']) ? true : false;
        if ($isAjaxUrl) {
            $editorId = t($_REQUEST['editorid']);
            $ajaxInfo = array();
            if ($thumb == 1) {
                $ajaxInfo['url'] = getImageUrl($return['data']['save_path'].$return['data']['save_name'], $width, $height, $cut);
            } else {
                $ajaxInfo['url'] = getImageUrl($return['data']['save_path'].$return['data']['save_name']);
            }

            if ($_REQUEST['getUrl'] == 1) {
                ob_end_clean();
                echo $ajaxInfo['url'];
                ob_end_flush();
                exit;
            }

            $ajaxInfo['state'] = 'SUCCESS';
            //echo $ajaxInfo['url'];
            echo "<script>parent.EditorList['".$editorId."'].getWidgetCallback('image')('".$ajaxInfo[ 'url' ]."','".$ajaxInfo[ 'state' ]."')</script>";
            // exit(getImageUrl($return['data']['save_path'].$return['data']['save_name']));
        } else {
            echo json_encode($return);
            exit();
        }
    }

    /**
     * 编辑器图片上传
     * @return array 上传图片的路径及错误信息
     */
    public function saveEditorImg()
    {
        $data['attach_type'] = 'editor_img';
        $data['upload_type'] = 'image'; //使用又拍云时，必须指定类型为image
        $info = model('Attach')->upload($data);
        if ($info['status']) {
            $data = $info['info'][0];
            $data['src'] = getImageUrl($data['save_path'].$data['save_name']);
            $return = array('error' => 0, 'url' => $data['src']);
        } else {
            $return = array('error' => 1, 'message' => $info['info']);
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 编辑器文件上传
     * @return array 上传文件的信息
     */
    public function saveEditorFile()
    {
        $data['attach_type'] = 'editor_file';
        $data['upload_type'] = 'file'; //使用又拍云时，必须指定类型为file
        $info = model('Attach')->upload($data);
        if ($info['status']) {
            $data = $info['info'][0];
            $data['src'] = getImageUrl($data['save_path'].$data['save_name'], 100, 100, true);
            $data['extension'] = strtolower($data['extension']);
            $return = array('status' => 1, 'data' => $data);
        } else {
            $return = array('status' => 0, 'data' => $info['info']);
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 附件下载
     */
    public function down()
    {
        $isLogin = model('Passport')->isLogged();
        if (!$isLogin) {
            header('Location:'.U('public/Passport/login'));
            // die('游客不允许下载附加');
        }

        $aid = intval($_GET['attach_id']);

        $attach = model('Attach')->getAttachById($aid);

        if (!$attach) {
            die(L('PUBLIC_ATTACH_ISNULL'));
        }

        $filename = $attach['save_path'].$attach['save_name'];
        $realname = auto_charset($attach['name'], 'UTF-8', 'GBK//IGNORE');

        //下载函数
        tsload(ADDON_PATH.'/library/Http.class.php');
        //从云端下载
        $cloud = model('CloudAttach');
        if ($cloud->isOpen()) {
            $url = $cloud->getFileUrl($filename);
            redirect($url);
            //$content = $cloud->getFileContent($filename); //读文件下载
            //Http::download('', $realname, $content);
        //从本地下载
        } else {
            if (file_exists(UPLOAD_PATH.'/'.$filename)) {
                Http::download(UPLOAD_PATH.'/'.$filename, $realname);
            } else {
                echo L('PUBLIC_ATTACH_ISNULL');
            }
        }
    }
    public function uploadVideo()
    {
        // dump($_FILES);
        // dump($_REQUEST);exit;
        $info = model('Video')->upload_by_web();
        // $return = array('status'=>1,'data'=>$info);
        echo json_encode($info);
        exit();
    }
}
