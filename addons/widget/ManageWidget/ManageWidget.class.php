<?php

/**
 * 用户可管理的应用列表 Widget
 * @example {:W('Manage')}
 * @version TS3.0
 */
class ManageWidget extends Widget
{
    private static $rand = 1;

    /**
     * 渲染应用列表模版
     */
    public function render($data)
    {
        $var = array();

        is_array($data) && $var = array_merge($var, $data);

        $var['rand'] = self::$rand;

        $list = model('App')->getManageApp($GLOBALS['ts']['mid']);
        if (empty($var['current'])) {
            $var['current_title'] = L('PUBLIC_BUSINESS_MANAGEMENT');
        } else {
            $var['list']['public'] = L('PUBLIC_REBUSINESS_MANAGEMENT');
        }

        foreach ($list as $v) {
            $var['list'][$v['app_name']] = $v['app_alias'].L('PUBLIC_MANAGEMENT');
            if ($var['current'] == $v['app_name']) {
                $var['current_title'] = $v['app_alias'].L('PUBLIC_MANAGEMENT');
            }
        }

        //渲染模版
        $content = $this->renderFile(dirname(__FILE__).'/default.html', $var);

        self::$rand ++;

        unset($var, $data);
        //输出数据
        return $content;
    }
}
