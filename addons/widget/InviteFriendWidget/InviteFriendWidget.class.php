<?php
/**
 * 邀请好友
 * @example {:W('InviteFriend')}
 * @version TS3.0
 */
class InviteFriendWidget extends Widget
{
    /**
     * 渲染邀请好友页面
     */
    public function render($data)
    {

        //渲染模版
        $content = $this->renderFile(dirname(__FILE__).'/content.html', $var);

        unset($var, $data);

        //输出数据
        return $content;
    }
}
