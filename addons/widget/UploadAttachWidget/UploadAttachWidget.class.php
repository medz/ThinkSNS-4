<?php
/**
 * 如果一个页面上需要使用多个上传的wigdet 请传入参数 end(不同的值)用来区别
 * 如果有自己的上传模板,请传入tpl值用来区别
 * @author yKF48801
 *
 */
class UploadAttachWidget extends Widget
{
    private static $rand = 1;
    public function render($data)
    {
        //默认参数
        $var['callback'] = 'attach_upload_success';
        $var['l_button'] = '浏 览';
        $var['l_loading'] = '正在上传...';
        $var['l_succes'] = '上传完成.';
        //上传类型，默认为附件
        if (!isset($data['type'])) {
            $var['type'] = 'attach';
        } else {
            $var['type'] = $data['type'];
        }
        //上传个数限制.默认10个
        if (!isset($data['limit'])) {
            $var['limit'] = 10;
        } else {
            $var['limit'] = $data['limit'];
        }
        //获取后台配置，设置的大小不能大于后台配置的上传大小，也不能大于系统支持的大小
        if (!isset($data['allow_size']) || empty($data['allow_size'])) {
            $attachopt = model('Xdata')->get('admin_Config:attach');
            $attachopt['allow_size'] = ($attachopt['attach_max_size'] <= ini_get('upload_max_filesize')) ? $attachopt['attach_max_size'] : intval(ini_get('upload_max_filesize'));
        }
        $data['allow_size'] = (isset($data['allow_size']) && $data['allow_size'] <= $attachopt['allow_size']) ? ($data['allow_size']).'MB' : $attachopt['allow_size'].'MB';
        //获取后台配置，设置的类型必须是后台配置的类型中的
        if (!isset($data['allow_exts']) || empty($data['allow_exts'])) {
            $data['allow_exts'] = $attachopt['attach_allow_extension'];
        }
        //编辑时.带入已有附件的参数 以逗号分割的附件IDs或数组
        $aids = $data['edit'];
        if (is_array($aids)) {
            $var['editdata'] = X('Xattach')->getAttach($aids);
        }
        //合并参数
        !isset($data['end']) && $data['end'] = '';
        //模版赋值
        $var = array_merge($var, $data);
        $var['rand'] = self::$rand;
        if ($data['tpl'] == 'flash') {
            //渲染模版
            $content = $this->renderFile(dirname(__FILE__).'/FlashUploadAttach.html', $var);
        } else {
            //渲染模版
            $content = $this->renderFile(dirname(__FILE__).'/UploadAttach.html', $var);
        }
        self::$rand ++;

        unset($var, $data);
        //输出数据
        return $content;
    }
}
