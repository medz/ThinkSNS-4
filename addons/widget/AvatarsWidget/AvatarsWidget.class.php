<?php
/**
 * 头像上传组件
 *登录注册
 * @example {:W('Avatar',array('avatar'=>$user_info,'defaultImg'=>$user_info['avatar_big'],'callback'=>'gotoStep3'))}
 * @version TS3.0
 */
class AvatarsWidget extends Widget
{
    /**
     * @param array avatar 用户信息
     * @param string defaultImg 头像地址
     * @param string callback 回调方法
     */
    public function render($data)
    {
        $template = 'default';
        $var = array();
        if ($template === 'default') {
            $var['password'] = time();
            $var['defaultImg'] = 'noavatar/big.jpg';
            $var['uploadUrl'] = urlencode(U('public/Account/doSaveUploadAvatar'));
            // 获取附件配置信息
            $attachConf = model('Xdata')->get('admin_Config:attachimage');
            $var['attach_max_size'] = $attachConf['attach_max_size'];
            $var['attach_allow_extension'] = $attachConf['attach_allow_extension'];

            is_array($data) && $var = array_merge($var, $data);
        }
        $content = $this->renderFile(dirname(__FILE__)."/{$template}.html", $var);

        return $content;
    }

    /**
     * 输出新头像
     */
    public function getflashHtml()
    {
    }
}
