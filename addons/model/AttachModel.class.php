<?php
/**
 * 附件模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
// 加载上传操作类
require_once SITE_PATH.'/addons/library/UploadFile.class.php';

class AttachModel extends Model
{
    protected $tableName = 'attach';

    protected $fields = array('attach_id', 'app_name', 'table', 'row_id', 'attach_type', 'uid', 'ctime', 'name', 'type', 'size', 'extension', 'hash', 'private', 'is_del', 'save_path', 'save_name', 'save_domain', 'from', 'width', 'height');

    /**
     * 通过附件ID获取附件数据 - 不分页型
     * @param  array  $ids   附件ID数组
     * @param  string $field 附件数据显示字段，默认为显示全部
     * @return array  相关附件数据
     */
    public function getAttachByIds($ids, $field = '*')
    {
        foreach ($ids as $key => $id) {
            $ids[$key] = intval($id);
            if ($ids[$key] <= 0) {
                unset($ids[$key]);
            }
        }
        if (empty($ids)) {
            return null;
        }

        $name = 'attach_ids_'.md5(json_encode($ids).$field);

        $data = S($name);
        if (!$data) {
            if (empty($ids)) {
                return false;
            }
            !is_array($ids) && $ids = explode(',', $ids);

            /* # sql注入，ID过滤 */
            array_map('intval', $ids);

            $map['attach_id'] = array('IN', $ids);
            $data = $this->where($map)->field($field)->order('attach_id asc')->findAll();
            S($name, $data);
        }

        return $data;
    }

    /**
     * 通过单个附件ID获取其附件信息
     * @param  int   $id 附件ID
     * @return array 指定附件ID的附件信息
     */
    public function getAttachById($id)
    {
        $id = intval($id);
        if (empty($id)) {
            return false;
        }

        $name = 'ts_attach_id_'.$id;
        $sc = S($name);

        if (!$sc) {
            // 获取静态缓存
            $sc = static_cache('attach_infoHash_'.$id);
            if (!empty($sc)) {
                return $sc;
            }
            // 获取缓存
            $sc = model('Cache')->get('Attach_'.$id);
            if (empty($sc)) {
                $map['attach_id'] = $id;
                $sc = $this->where($map)->find();
                empty($sc) && $sc = array();
                model('Cache')->set('Attach_'.$id, $sc, 3600);
            }
            static_cache('attach_infoHash_'.$id, $sc);
            S($name, $sc);
        }

        return $sc;
    }

    /**
     * 获取附件列表 - 分页型
     * @param  array  $map   查询条件
     * @param  string $field 显示字段
     * @param  string $order 排序条件，默认为id DESC
     * @param  int    $limit 结果集个数，默认为20
     * @return array  附件列表数据
     */
    public function getAttachList($map, $field = '*', $order = 'id DESC', $limit = 20)
    {
        !isset($map['is_del']) && ($map['is_del'] = 0);
        $list = $this->where($map)->field($field)->order($order)->findPage($limit);

        return $list;
    }

    /**
     * 删除附件信息，提供假删除功能
     * @param  int    $id    附件ID
     * @param  string $type  操作类型，若为delAttach则进行假删除操作，deleteAttach则进行彻底删除操作
     * @param  string $title ???
     * @return array  返回操作结果信息
     */
    public function doEditAttach($id, $type, $title)
    {
        $return = array('status' => '0', 'data' => L('PUBLIC_ADMIN_OPRETING_ERROR'));        // 操作失败
        if (empty($id)) {
            $return['data'] = L('PUBLIC_ATTACHMENT_ID_NOEXIST');            // 附件ID不能为空
        } else {
            $map['attach_id'] = is_array($id) ? array('IN', $id) : intval($id);
            $save['is_del'] = ($type == 'delAttach') ? 1 : 0;        //TODO:1 为用户uid 临时为1
            if ($type == 'deleteAttach') {
                // 彻底删除操作
                $res = D('Attach')->where($map)->delete();
                // TODO:删除附件文件
            } else {
                // 假删除或者恢复操作
                $res = D('Attach')->where($map)->save($save);
            }
            if ($res) {
                //TODO:是否记录知识，以及后期缓存处理
                $return = array('status' => 1, 'data' => L('PUBLIC_ADMIN_OPRETING_SUCCESS'));        // 操作成功
            }
        }

        return $return;
    }

    /**
     * 获取所有附件的扩展名
     * @return array 扩展名数组
     */
    public function getAllExtensions()
    {
        $name = 'ts_area_ext';
        $res = S($name);
        if (!$res) {
            $res = $this->field('`extension`')->group('`extension`')->findAll();
            $res = getSubByKey($res, 'extension');
            S($name, $res);
        }

        return $res;
    }

    /**
     * 上传附件
     * @param  array $data          附件相关信息
     * @param  array $input_options 配置选项[不推荐修改, 默认使用后台的配置]
     * @param  bool  $thumb         是否启用缩略图
     * @return array 上传的附件的信息
     */
    public function upload($data = null, $input_options = null, $thumb = false)
    {
        //echo json_encode($data);
        $system_default = model('Xdata')->get('admin_Config:attach');
        if (empty($system_default['attach_path_rule']) || empty($system_default['attach_max_size']) || empty($system_default['attach_allow_extension'])) {
            $system_default['attach_path_rule'] = 'Y/md/H/';
            $system_default['attach_max_size'] = '2';        // 默认2M
            $system_default['attach_allow_extension'] = 'jpg,gif,png,jpeg,bmp,zip,rar,doc,xls,ppt,docx,xlsx,pptx,pdf';
            model('Xdata')->put('admin_Config:attach', $system_default);
        }

        // 上传若为图片，则修改为图片配置
        if ($data['upload_type'] === 'image') {
            $image_default = model('Xdata')->get('admin_Config:attachimage');
            $system_default['attach_max_size'] = $image_default['attach_max_size'];
            $system_default['attach_allow_extension'] = $image_default['attach_allow_extension'];
            $system_default['auto_thumb'] = $image_default['auto_thumb'];
        }

        // 载入默认规则
        $default_options = array();
        $default_options['custom_path'] = date($system_default['attach_path_rule']);                    // 应用定义的上传目录规则：'Y/md/H/'
        $default_options['max_size'] = floatval($system_default['attach_max_size']) * 1024 * 1024;        // 单位: 兆
        $default_options['allow_exts'] = $system_default['attach_allow_extension'];                    // 'jpg,gif,png,jpeg,bmp,zip,rar,doc,xls,ppt,docx,xlsx,pptx,pdf'
        $default_options['save_path'] = UPLOAD_PATH.'/'.$default_options['custom_path'];
        $default_options['save_name'] = ''; //指定保存的附件名.默认系统自动生成
        $default_options['save_to_db'] = true;
        //echo json_encode($default_options);exit;

        // 定制化设这，覆盖默认设置
        $options = is_array($input_options) ? array_merge($default_options, $input_options) : $default_options;
        //云图片
        if ($data['upload_type'] == 'image') {
            $cloud = model('CloudImage');
            if ($cloud->isOpen()) {
                return $this->cloudImageUpload($options);
            } else {
                return $this->localUpload($options);
            }
        }

        //云附件
        else {
            //if($data['upload_type']=='file'){
            $cloud = model('CloudAttach');
            if ($cloud->isOpen()) {
                return $this->cloudAttachUpload($options);
            } else {
                return $this->localUpload($options);
            }
        }
    }

    private function cloudImageUpload($options)
    {
        $upload = model('CloudImage');
        $upload->maxSize = $options['max_size'];
        $upload->allowExts = $options['allow_exts'];
        $upload->customPath = $options['custom_path'];
        $upload->saveName = $options['save_name'];

        // 执行上传操作
        if (!$upload->upload()) {
            // 上传失败，返回错误
            $return['status'] = false;
            $return['info'] = $upload->getErrorMsg();

            return $return;
        } else {
            $upload_info = $upload->getUploadFileInfo();
            // 保存信息到附件表
            $data = $this->saveInfo($upload_info, $options);
            // 输出信息
            $return['status'] = true;
            $return['info'] = $data;
            // 上传成功，返回信息
            return $return;
        }
    }

    private function cloudAttachUpload($options)
    {
        $upload = model('CloudAttach');
        $upload->maxSize = $options['max_size'];
        $upload->allowExts = $options['allow_exts'];
        $upload->customPath = $options['custom_path'];
        $upload->saveName = $options['save_name'];

        // 执行上传操作
        if (!$upload->upload()) {
            // 上传失败，返回错误
            $return['status'] = false;
            $return['info'] = $upload->getErrorMsg();

            return $return;
        } else {
            $upload_info = $upload->getUploadFileInfo();
            // 保存信息到附件表
            $data = $this->saveInfo($upload_info, $options);
            // 输出信息
            $return['status'] = true;
            $return['info'] = $data;
            // 上传成功，返回信息
            return $return;
        }
    }

    private function localUpload($options)
    {
        // 初始化上传参数
        $upload = new UploadFile($options['max_size'], $options['allow_exts'], $options['allow_types']);
        // 设置上传路径
        $upload->savePath = $options['save_path'];
        // 启用子目录
        $upload->autoSub = false;
        // 保存的名字
        $upload->saveName = $options['save_name'];
        // 默认文件名规则
        $upload->saveRule = $options['save_rule'];
        // 是否缩略图
        if ($options['auto_thumb'] == 1) {
            $upload->thumb = true;
        }

        // 创建目录
        mkdir($upload->save_path, 0777, true);

        // 执行上传操作
        if (!$upload->upload()) {
            // 上传失败，返回错误
            $return['status'] = false;
            $return['info'] = $upload->getErrorMsg();

            return $return;
        } else {
            $upload_info = $upload->getUploadFileInfo();
            // 保存信息到附件表
            $data = $this->saveInfo($upload_info, $options);
            // 输出信息
            $return['status'] = true;
            $return['info'] = $data;
            // 上传成功，返回信息
            return $return;
        }
    }

    private function saveInfo($upload_info, $options)
    {
        $data = array(
            'table' => t($data['table']),
            'row_id' => $data['row_id'] ? intval($data['row_id']) : 0,
            'app_name' => t($data['app_name']),
            'attach_type' => t($options['attach_type']),
            'uid' => (int) $data['uid'] ? $data['uid'] : $GLOBALS['ts']['mid'],
            'ctime' => time(),
            'private' => $data['private'] > 0 ? 1 : 0,
            'is_del' => 0,
            'from' => isset($data['from']) ? intval($data['from']) : getVisitorClient(),
        );
        if ($options['save_to_db']) {
            foreach ($upload_info as $u) {
                $name = t($u['name']);
                $data['name'] = $name ? $name : $u['savename'];
                $data['type'] = $u['type'];
                $data['size'] = $u['size'];
                $data['extension'] = strtolower($u['extension']);
                $data['hash'] = $u['hash'];
                $data['save_path'] = $options['custom_path'];
                $data['save_name'] = $u['savename'];
                if (in_array(strtolower($u['extension']), array('jpg', 'gif', 'png', 'jpeg', 'bmp')) && !in_array($options['attach_type'], array('feed_file', 'weiba_attach'))) {
                    list($width, $height) = getImageInfo($data['save_path'].$data['save_name']);
                    $data['width'] = $width;
                    $data['height'] = $height;
                } else {
                    $data['width'] = 0;
                    $data['height'] = 0;
                }
                $aid = $this->add($data);
                $data['attach_id'] = intval($aid);
                $data['key'] = $u['key'];
                $data['size'] = byte_format($data['size']);
                $infos[] = $data;
                unset($data['attach_id']);
                unset($data['key']);
                unset($data['size']);
            }
        } else {
            foreach ($upload_info as $u) {
                $name = t($u['name']);
                $data['name'] = $name ? $name : $u['savename'];
                $data['type'] = $u['type'];
                $data['size'] = byte_format($u['size']);
                $data['extension'] = strtolower($u['extension']);
                $data['hash'] = $u['hash'];
                $data['save_path'] = $options['custom_path'];
                $data['save_name'] = $u['savename'];
                //$data['save_domain'] = C('ATTACH_SAVE_DOMAIN'); 	//如果做分布式存储，需要写方法来分配附件的服务器domain
                $data['key'] = $u['key'];
                $infos[] = $data;
            }
        }

        return $infos;
    }

    public function saveAttach($file)
    {
        # code...
    }

    /**
     * 更新附件图片，保存图片的宽度和高度
     */
    public function upImageAttach($page = 1, $count = 100)
    {
        set_time_limit(0);
        $where = "`extension` IN ('jpg', 'gif', 'png', 'jpeg', 'bmp')";
        $limit = ($page - 1) * $count.', '.$count;
        $list = $this->field('attach_id, save_path, save_name')->where($where)->limit($limit)->order('attach_id DESC')->findAll();
        if (empty($list)) {
            return false;
        }
        foreach ($list as $value) {
            $url = getImageUrl($value['save_path'].$value['save_name']);
            $imageInfo = getimagesize($url);
            if ($imageInfo === false) {
                continue;
            }
            list($width, $height) = $imageInfo;
            $data['width'] = $width;
            $data['height'] = $height;
            $map['attach_id'] = $value['attach_id'];
            $this->where($map)->save($data);
        }

        return true;
    }

    public function upImageAttachCloud($page = 1, $count = 100)
    {
        set_time_limit(0);
        $where = "`extension` IN ('jpg', 'gif', 'png', 'jpeg', 'bmp') AND attach_type != 'feed_file'";
        $limit = ($page - 1) * $count.', '.$count;
        $list = $this->field('attach_id, save_path, save_name')->where($where)->limit($limit)->order('attach_id DESC')->findAll();
        if (empty($list)) {
            return false;
        }
        foreach ($list as $value) {
            $imageInfo = json_decode(file_get_contents(getImageUrl($value['save_path'].$value['save_name']).'!exif'));
            settype($imageInfo, 'array');
            if (empty($imageInfo)) {
                continue;
            }
            $data['width'] = $imageInfo['width'];
            $data['height'] = $imageInfo['height'];
            $map['attach_id'] = $value['attach_id'];
            $this->where($map)->save($data);
        }

        return true;
    }
}
