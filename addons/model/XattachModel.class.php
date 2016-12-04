<?php

// +----------------------------------------------------------------------
// | OpenSociax [ open your team ! ]
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://www.sociax.com.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: genixsoft.net <智士软件>
// +----------------------------------------------------------------------
// $Id$

/**
 +------------------------------------------------------------------------------
 * 附件操作服务，参考XattachModel
 +------------------------------------------------------------------------------
 */

//载入上传操作类
tsload(SITE_PATH.'/addons/library/UploadFile.class.php');

class XattachModel
{
    /**
     * 上传附件
     *
     * @param string $attach_type 附件类型
     * @param array  $input_options 配置选项[不推荐修改, 默认使用后台的配置]
     */
    public function upload($attach_type = 'attach', $input_options = array())
    {
        $system_default = model('Xdata')->lget('attach');
        if (empty($system_default['attach_path_rule']) || empty($system_default['attach_max_size']) || empty($system_default['attach_allow_extension'])) {
            $data['attach_path_rule'] = 'Y/md/H/';
            $data['attach_max_size'] = '2'; // 默认2M
            $data['attach_allow_extension'] = 'jpg,gif,png,jpeg,bmp,zip,rar,doc,xls,ppt,docx,xlsx,pptx,pdf';
            model('Xdata')->lput('attach', $data);
            $system_default = $data;
        }

        //载入默认规则
        $default_options = array();
        $default_options['custom_path'] = date($system_default['attach_path_rule']);                //应用定义的上传目录规则：'Y/md/H/'
        $default_options['max_size'] = floatval($system_default['attach_max_size']) * 1000000;    //单位: 兆
        $default_options['allow_exts'] = $system_default['attach_allow_extension'];                //'jpg,gif,png,jpeg,bmp,zip,rar,doc,xls,ppt,docx,xlsx,pptx,pdf'
        $default_options['allow_types'] = '';
        $default_options['save_path'] = UPLOAD_PATH.'/'.$default_options['custom_path'];
        $default_options['save_name'] = '';
        $default_options['save_rule'] = 'uniqid';
        $default_options['save_to_db'] = true;

        //定制化设这 覆盖默认设置
        $options = array_merge($default_options, $input_options);

        //用户ID
        if (intval($options['uid']) == 0) {
            $options['uid'] = $_SESSION['mid'];
        }

        //初始化上传参数
        $upload = new UploadFile($options['max_size'], $options['allow_exts'], $options['allow_types']);
        //设置上传路径
        $upload->savePath = $options['save_path'];
        //启用子目录
        $upload->autoSub = false;
        //保存的名字
        $upload->saveName = $options['save_name'];
        //默认文件名规则
        $upload->saveRule = $options['save_rule'];
        //是否缩略图
        $upload->thumb = false;

        //创建目录
        mkdir($upload->savePath, 0777, true);

        //执行上传操作
        if (!$upload->upload()) {

            //上传失败，返回错误
            $return['status'] = false;
            $return['info'] = $upload->getErrorMsg();

            return    $return;
        } else {
            $upload_info = $upload->getUploadFileInfo();
            $xattach = M('Attach');
            //保存信息到附件表
            if ($options['save_to_db']) {
                foreach ($upload_info as $u) {
                    $map['attach_type'] = $attach_type;
                    $map['userId'] = $options['uid'];
                    $map['name'] = $u['name'];
                    $map['type'] = $u['type'];
                    $map['size'] = $u['size'];
                    $map['extension'] = strtolower($u['extension']);
                    $map['hash'] = $u['hash'];
                    $map['savepath'] = $options['custom_path'];
                    $map['savename'] = $u['savename'];
                    $map['uploadTime'] = time();
                    //$map['savedomain']=	C('ATTACH_SAVE_DOMAIN'); //如果做分布式存储，需要写方法来分配附件的服务器domain
                    $aid = $xattach->add($map);
                    $map['id'] = intval($aid);
                    $map['key'] = $u['key'];
                    $infos[] = $map;
                    unset($map);
                }
            } else {
                /*foreach($upload_info as $k => $u){
                    $upload_info[$k]['savepath']	=	$options['custom_path'];
                }
                $infos	=	$upload_info;*/
                foreach ($upload_info as $u) {
                    $map['attach_type'] = $attach_type;
                    $map['userId'] = $options['uid'];
                    $map['name'] = $u['name'];
                    $map['type'] = $u['type'];
                    $map['size'] = $u['size'];
                    $map['extension'] = strtolower($u['extension']);
                    $map['hash'] = $u['hash'];
                    $map['savepath'] = $options['custom_path'];
                    $map['savename'] = $u['savename'];
                    $map['uploadTime'] = time();
                    //$map['savedomain']=	C('ATTACH_SAVE_DOMAIN'); //如果做分布式存储，需要写方法来分配附件的服务器domain
                    $map['key'] = $u['key'];
                    $infos[] = $map;
                    unset($map);
                }
            }
            //输出信息
            $return['status'] = true;
            $return['info'] = $infos;
            //上传成功，返回信息
            return    $return;
        }
    }

    //直接直接保存文件到附件表
    public function addFile($attach_type = 'attach', $attach_file)
    {

        //获取系统配置
        $system_default = model('Xdata')->lget('attach');
        if (empty($system_default['attach_path_rule']) || empty($system_default['attach_max_size']) || empty($system_default['attach_allow_extension'])) {
            $data['attach_path_rule'] = 'Y/md/H/';
            $data['attach_max_size'] = '2'; // 默认2M
            $data['attach_allow_extension'] = 'jpg,gif,png,jpeg,bmp,zip,rar,doc,xls,ppt,docx,xlsx,pptx,pdf';
            model('Xdata')->lput('attach', $data);
            $system_default = $data;
        }

        //获取文件信息
        if (!file_exists($attach_file)) {

            //上传失败，返回错误
            $return['status'] = false;
            $return['info'] = '原始文件不存在';

            return    $return;
        } else {

            //读取附件大小
            $file['size'] = filesize($attach_file);
            $file['hash'] = md5_file($attach_file);

            //读取附件名称、后缀名
            if (function_exists('pathinfo')) {
                $fileinfo = pathinfo($attach_file);
                $file['name'] = $fileinfo['basename'];
                $file['extension'] = $fileinfo['extension'];
            } else {
                $file['name'] = basename($attach_file);
                preg_match('|\.(\w+)$|', $file['name'], $file['extension']);
            }

            //读取附件类型
            if (function_exists('mime_content_type')) {
                $file['type'] = mime_content_type($attach_file);
            } elseif (in_array(strtolower($file['extension']), array('jpeg', 'jpg', 'gif', 'png', 'bmp'))) {
                $file['type'] = 'image/'.$file['extension'];
            } else {
                $file['type'] = 'text/'.$file['extension'];
            }
        }

        //载入默认规则
        $options = array();
        $options['custom_path'] = date($system_default['attach_path_rule']);
        $options['save_path'] = UPLOAD_PATH.'/'.$options['custom_path'];
        $options['save_name'] = uniqid().'.'.$file['extension'];
        $options['save_to_db'] = true;

        //用户ID
        if (intval($options['uid']) == 0) {
            $options['uid'] = intval($_SESSION['mid']);
        }

        //创建目录
        mkdir($options['save_path'], 0777, true);

        //执行上传操作
        if (!copy($attach_file, $options['save_path'].'/'.$options['save_name'])) {

            //上传失败，返回错误
            $return['status'] = false;
            $return['info'] = '文件转移失败';

            return    $return;
        } else {

            //保存信息到附件表
            $map['attach_type'] = $attach_type;
            $map['userId'] = $options['uid'];
            $map['name'] = $file['name'];
            $map['type'] = $file['type'];
            $map['size'] = $file['size'];
            $map['extension'] = strtolower($file['extension']);
            $map['hash'] = $file['hash'];
            $map['savepath'] = $options['custom_path'];
            $map['savename'] = $options['save_name'];
            $map['uploadTime'] = time();
            //$map['savedomain']=	C('ATTACH_SAVE_DOMAIN'); //如果做分布式存储，需要写方法来分配附件的服务器domain
            $aid = M('Attach')->add($map);
            $map['id'] = intval($aid);
            $map['key'] = 0;

            //上传成功，输出信息
            $return['status'] = true;
            $return['info'] = $map;

            return    $return;
        }
    }

    /**
     * 下载附件
     *
     * @param int $aid 附件ID 为空时使用$_REQUEST['id']作为附件ID
     */
    public function download($aid)
    {
        if (intval($aid) == 0) {
            $aid = intval($_REQUEST['id']);
        }
        $attach = model('Attach')->field('savepath,savename,name')->where("id='$aid'")->find();
        if (!$attach) {
            $this->error('附件不存在或已被删除！');
        }
        //下载函数
        tsload('./addons/library/Http.class.php');
        $file_path = UPLOAD_PATH.'/'.$attach['savepath'].$attach['savename'];
        if (file_exists($file_path)) {
            $filename = strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') ? iconv('utf-8', 'gbk', $attach['name']) : preg_replace('/\s/', '_', $attach['name']);
            Http::download($file_path, $filename);
        } else {
            $this->error('附件不存在或已被删除！');
        }
    }

    /* 后台管理相关方法 */

    //返回失败信息
    public function error($info, $return = false)
    {
        $output['status'] = false;
        $output['info'] = $info;
        if ($return) {
            return $output;
        } else {
            echo json_encode($output);
        }
    }

    //返回成功信息
    public function success($info, $return = false)
    {
        $output['status'] = true;
        $output['info'] = $info;
        if ($return) {
            return $output;
        } else {
            echo json_encode($output);
        }
    }

    //返回附件数据
    public function getAttach($attachId, $field = '*')
    {
        if (!$attachId || !isset($attachId)) {
            return false;
        }

        if (!is_array($attachId)) {
            $attachId = explode(',', $attachId);
        }
        foreach ($attachId as $v) {
            $attachIds[] = intval($v);
        }

        $map['attach_id'] = array('in', array_map('intval', $attachIds));
        $data = M('Attach')->where($map)->field($field)->findAll();

        return $data;
    }

    //运行服务
    public function run()
    {
    }

    //启动服务，未编码
    public function _start()
    {
        return true;
    }

    //停止服务，未编码
    public function _stop()
    {
        return true;
    }

    //卸载服务，未编码
    public function _install()
    {
        return true;
    }

    //卸载服务，未编码
    public function _uninstall()
    {
        return true;
    }
}
