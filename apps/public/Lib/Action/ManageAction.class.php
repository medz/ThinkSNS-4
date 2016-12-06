<?php
/**
 * ManageAction 用户管理的应用模块.
 *
 * @version TS3.0
 */
class ManageAction extends Action
{
    private $appList;

    /**
     * 模块初始化,获取当前用户管理的应用.
     */
    public function _initialize()
    {
        $this->appList = model('App')->getManageApp($this->mid);

        if (empty($this->appList)) {
            $this->error(L('PUBLIC_NO_FRONTPLATFORM_PERMISSION'));
        }
    }

    /**
     * 展示用户管理的应用.
     */
    public function index()
    {
        $this->assign('appList', $this->appList);
        $this->setTitle(L('PUBLIC_MANAGE_INDEX'));
        $this->display();
    }
}
