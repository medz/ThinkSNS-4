<?php
/**
 * 隐私模块.
 *
 * @author  liuxiaoqing <liuxiaoqing@zhishisoft.com>
 *
 * @version TS3.0
 */
class PrivacyAction extends Action
{
    /**
     * 模块初始化.
     */
    protected function _initialize()
    {
    }

    /**
     * 隐私设置页面.
     */
    public function index()
    {
        $this->display();
    }

    /**
     * 保存隐私设置.
     */
    public function doSavePrivacy()
    {
        $this->display();
    }
}
