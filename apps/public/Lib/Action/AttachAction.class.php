<?php

//附件管理
class AttachAction extends Action
{
    public function _initialize()
    {
        if (!$this->mid) {
            echo '-1';    //没有权限，需要登录后上传
        }
    }

    //兼容旧路径
    public function showdataimage()
    {
        $savepath = t($_REQUEST['savepath']);
        $savename = t($_REQUEST['savename']);
        //防止跨目录
        $savepath = str_ireplace('..', '', $savepath);
        $savename = str_ireplace('..', '', $savename);

        list($first, $extension) = explode('.', $savename);
        $imagetype = array('jpg', 'png', 'gif', 'bmp', 'jpeg');
        if (!in_array(strtolower($extension), $imagetype)) {
            return;
        } else {
            if ($_REQUEST['temp'] == 1) {
                $source_image = UPLOAD_PATH.'/temp/'.$savename;
                header('Content-type: image/'.$extension);
                readfile($source_image);
                exit;
            }

            $source_image = UPLOAD_PATH.'/'.$savepath.$savename;
            if (file_exists($source_image)) {
                //加缓存
                $offset = 60 * 60 * 24 * 7; //图片过期7天
                header('cache-control: must-revalidate');
                header('cache-control: max-age='.$offset);
                header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).'GMT');
                header('Pragma: max-age='.$offset);
                header('Expires:'.gmdate('D, d M Y H:i:s', time() + $offset).' GMT');
                $this->set_cache_limit($offset);
                header('Content-type: image/'.$extension);
                readfile($source_image);
                //持久化
                $new_image_dir = './data/uploads/'.$savepath;
                if (!is_dir($new_image_dir)) {
                    @mkdir($new_image_dir, 0777, true);
                }

                if (is_dir($new_image_dir)) {
                    @copy($source_image, $new_image_dir.$savename);
                }
            }
        }
    }

    public function set_cache_limit($second = 1)
    {
        $second = intval($second);
        if ($second == 0) {
            return;
        }

        $id = $_SERVER['HTTP_IF_NONE_MATCH'];
        $etag = time().'||'.base64_encode($_SERVER['REQUEST_URI']);
        if ($id == '') {
            //无tag，发送新tag
            header("Etag:$etag", true, 200);

            return;
        }
        list($time, $uri) = explode('||', $id);
        if ($time < (time() - $second)) {
            //过期了，发送新tag
            header("Etag:$etag", true, 200);
        } else {
            //未过期，发送旧tag
            header("Etag:$id", true, 304);
            exit(-1);
        }
    }

    public function image_file_download($file)
    {
        $size = image_get_info(file_create_path($file));
        if ($size) {
            $modified = filemtime(file_create_path($file));

        //apache only
        $request = getallheaders();

            if (isset($request['If-Modified-Since'])) {
                //remove information after the semicolon and form a timestamp
          $request_modified = explode(';', $request['If-Modified-Since']);
                $request_modified = strtotime($request_modified[0]);
            }

        // Compare the mtime on the request to the mtime of the image file
        if ($modified <= $request_modified) {
            header('HTTP/1.1 304 Not Modified');
            exit();
        }

        //enable caching on this url for proxy servers
        $headers = array('Content-Type: '.$size['mime_type'],
                         'Last-Modified: '.gmdate('D, d M Y H:i:s', $modified).' GMT',
                         'Cache-Control: public', );

            return $headers;
        }
    }

    //下载输出附件
    public function download()
    {
        $aid = intval($_REQUEST['id']);
        $uid = intval($_REQUEST['uid']);
        $attach = model('Attach')->where("id={$aid} AND userId={$uid}")->find();
        //$attach	=	model('Xattach')->where("id='$aid'")->find();

        if (!$attach) {
            $this->error(L('attach_noexist'));
        }
        //下载函数
        require_cache('./addons/library/Http.class.php');
        $file_path = UPLOAD_PATH.'/'.$attach['savepath'].$attach['savename'];
        if (file_exists($file_path)) {
            $filename = iconv('utf-8', 'gb2312', $attach['name']);
            Http::download($file_path, $filename);
        } else {
            $this->error(L('attach_noexist'));
        }
    }

    //ewebeditor控件上传
    public function ewebeditor()
    {

        //执行附件上传操作
        $attach_type = 'ewebeditor';

        $options['uid'] = $this->mid;
        $options['allow_exts'] = 'jpg,jpeg,bmp,png,gif';
        $info = X('Xattach')->upload($attach_type, $options);

        if (is_array($info['info'])) {
            $image_url = SITE_URL.'/data/uploads/'.$info['info'][0]['savepath'].$info['info'][0]['savename'];
        }

        //上传成功
        if ($info['status'] == true) {
            echo $image_url;
        }
    }

    //kissy编辑器上传
    public function kissy()
    {

        //执行附件上传操作
        $attach_type = 'kissy';

        $options['uid'] = $this->mid;
        $options['allow_exts'] = 'jpg,jpeg,bmp,png,gif';
        $info = X('Xattach')->upload($attach_type, $options);

        if (is_array($info['info'])) {
            $image_url = SITE_URL.'/data/uploads/'.$info['info'][0]['savepath'].$info['info'][0]['savename'];
        }

        //上传成功
        if ($info['status'] == true) {
            echo '{"status": "0", "imgUrl": "'.$image_url.'"}';
        } else {
            echo '{"status": "1", "error": "'.$info['info'].'"}';
        }
    }

    //上传附件
    public function ajaxUpload()
    {
        //执行附件上传操作
        $d['type_name'] = 11;
        D('feedback_type')->add($d);
        $attach_type = t($_REQUEST['type']);

        $options['uid'] = $this->mid;

        //加密传输这个字段,防止客户端乱设置.
        $options['allow_exts'] = t(jiemi($_REQUEST['exts']));
        $options['allow_size'] = t(jiemi($_REQUEST['size']));
        $jiamiData = jiemi(t($_REQUEST['token']));
        list($options['allow_exts'], $options['need_review'], $fid) = explode('||', $jiamiData);
        $options['limit'] = intval(jiemi($_REQUEST['limit']));
        $options['now_pageCount'] = intval($_REQUEST['now_pageCount']);

        $data['upload_type'] = $attach_type;

        $info = model('Attach')->upload($data, $options);
        //上传成功
        echo json_encode($info);
    }

    // 图片截取
    public function thumbImage()
    {

        // 过滤成本地图片地址
        $src_arr = explode('?', $_POST['bigImage']);
        $src = $src_arr[0];
        $src = str_ireplace(SITE_URL, '.', 'http://'.$_SERVER['HTTP_HOST'].$src);

        // 获取源图的扩展名宽高
        list($sr_w, $sr_h, $sr_type, $sr_attr) = @getimagesize($src);
        if ($sr_type) {
            //获取后缀名
            $ext = image_type_to_extension($sr_type, false);
        } else {
            echo '-1';
        }

        // 获取相关数据
        $txt_left = (float) $_POST['txt_left'];
        $txt_top = (float) $_POST['txt_top'];
        $txt_width = (float) $_POST['txt_width'];
        $txt_height = (float) $_POST['txt_height'];
        $zoom = (float) $_POST['txt_Zoom'];

        // 头像大方快的宽高
        $targ_w = 120;
        $targ_h = 120;

        // 头像小方块的宽高
        $small_w = 45;
        $small_h = 45;

        // 生成头像目录
        $face_path = SITE_PATH.'/data/userface/'.$this->mid;
        $face_url = SITE_URL.'/data/userface/'.$this->mid;
        if (!file_exists($face_path)) {
            mkdir($face_path, 0777, true);
            chmod($face_path, 0777);
        }
        // 生成头像名称
        $middle_name = $face_path.'/middle_face.jpg';        // 中图
        $small_name = $face_path.'/small_face.jpg';        // 小图

        // 生成原图拷贝
        $func = ($ext != 'jpg') ? 'imagecreatefrom'.$ext : 'imagecreatefromjpeg';
        $img_r = call_user_func($func, $src);

        //计算原图截图坐标，以源图左上角为坐标原点
        $dx1 = $txt_left;
        $dy1 = $txt_top;
        $dx2 = $dx1 + $targ_w;
        $dy2 = $dy1 + $targ_h;

        $sx1 = 0;
        $sy1 = 0;
        $sx2 = $txt_width;
        $sy2 = $txt_height;

        //计算拷贝区域坐标，以源图左上角为坐标原点
        $smx1 = $dx1 > $sx1 ? $dx1 : $sx1;
        $smy1 = $dy1 > $sy1 ? $dy1 : $sy1;
        $smx2 = $dx2 > $sx2 ? $sx2 : $dx2;
        $smy2 = $dy2 > $sy2 ? $sy2 : $dy2;

        $src_x = $smx1 / $zoom;
        $src_y = $smy1 / $zoom;

        $src_w = ($smx2 - $smx1) / $zoom;
        $src_h = ($smy2 - $smy1) / $zoom;

        //计算拷贝区域坐标，以目标图左上角为坐标原点，坐标原点平移到 $dx1,$dy1
        $dst_x = $smx1 - $dx1;
        $dst_y = $smy1 - $dy1;

        $dst_w = $smx2 - $smx1;
        $dst_h = $smy2 - $smy1;

        //dump($smx1.','.$smy1.','.$smx2.','.$smy2.','.$src_w.','.$src_h.'|'.$dmx1.','.$dmy1.','.$dmx2.','.$dmy2.','.$dst_w.','.$dst_h);

        // 开始切割大方块头像
        $dst_r = imagecreatetruecolor($targ_w, $targ_h);
        $back = imagecolorallocate($dst_r, 255, 255, 255);
        imagefilledrectangle($dst_r, 0, 0, $targ_w, $targ_h, $back);
        imagecopyresampled($dst_r, $img_r, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

        imagepng($dst_r, $middle_name);  // 生成中图
        chmod($middle_name, 0777);

        // 开始切割大方块头像成小方块头像
        $sdst_r = imagecreatetruecolor($small_w, $small_h);
        imagecopyresampled($sdst_r, $dst_r, 0, 0, 0, 0, $small_w, $small_h, $targ_w, $targ_h);

        imagepng($sdst_r, $small_name);  // 生成小图
        chmod($small_name, 0777);

        imagedestroy($dst_r);
        imagedestroy($sdst_r);
        imagedestroy($img_r);

        $output = array('big' => $face_url.'/middle_face.jpg', 'small' => $face_url.'/small_face.jpg');

        echo json_encode($output);
    }

    //删除附件
    public function deleteAttach($aid, $uid)
    {
        $map['uid'] = $uid;
        $map['id'] = $aid;

        //执行附件删除操作
        $result = model('Xattach')->where($map)->limit(1)->delete();
        //上传成功
        echo $result;
    }
}
