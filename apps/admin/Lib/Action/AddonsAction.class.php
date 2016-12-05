<?php
/**
 * 后台插件管理.
 *
 * @author jason <yangjs17@yeah.net>
 *
 * @version TS3.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AddonsAction extends AdministratorAction
{
    /**
     * 插件列表页面.
     */
    public function index()
    {
        $model = model('Addon');
        $admin = $model->getAddonsAdmin();
        $result = $model->getAddonAllList();
        foreach ($result['valid']['data'] as $key => $value) {
            if ($_GET['is_weixin']) {
                $result['valid']['data'][$key]['admin'] = true;
            } else {
                foreach ($admin as $v) {
                    if ($v[1] == $value['addonId']) {
                        $result['valid']['data'][$key]['admin'] = true;
                    }
                }
            }
        }

        $this->assign('list', $result);
        $this->display();
    }

    /**
     * 开启插件操作.
     */
    public function startAddon()
    {
        $result = model('Addon')->startAddons(t($_GET['name']));
        if (true === $result) {
            $this->success('启用成功');
        } else {
            $this->error('启动失败');
        }
    }

    /**
     * 停止插件操作.
     */
    public function stopAddon()
    {
        $result = model('Addon')->stopAddonsById(intval($_GET['addonId']));
        if (true === $result) {
            $this->success('停用成功');
        } else {
            $this->error('停用失败');
        }
    }

    /**
     * 卸载插件操作.
     */
    public function uninstallAddon()
    {
        $result = model('Addon')->uninstallAddons(t($_GET['name']));
        if (true === $result) {
            $this->success('卸载成功');
        } else {
            $this->error('卸载失败');
        }
    }

    /**
     * 插件后台管理页面.
     */
    public function admin()
    {
        $addon = model('Addon')->getAddonObj(intval($_GET['pluginid']));
        $addonInfo = model('Addon')->getAddon(intval($_GET['pluginid']));
        if (!$addon) {
            $this->error('插件未启动或插件不存在');
        }
        $info = $addon->getAddonInfo();
        $adminMenu = $addon->adminMenu();
        if (!$adminMenu) {
            $this->assign('addonName', $info['pluginName']);
            $this->assign('menu', false);
            $this->display();

            return;
        }
        $this->assign('menu', $adminMenu);
        if (empty($_GET['page'])) {
            $_GET['page'] = $page = array_shift(array_keys($adminMenu));
        } else {
            $page = t($_GET['page']);
        }
        $this->assign('page', $page);
        $this->assign('addonName', $addonInfo['pluginName']);
        $this->assign('name', $addonInfo['name']);
        $this->assign('isAjax', $this->isAjax());
        $this->display();
    }

    public function doAdmin()
    {
        $addonInfo = model('Addon')->getAddon(intval($_GET['pluginid']));
        $result = array(
                'status' => true,
                'info'   => '',
        );

        F('Cache_App', null);

        Addons::addonsHook($addonInfo['name'], t($_GET['page']), array(
                'result' => &$result,
        ), true);

        // dump($result);

        if ($result['status']) {
            $_POST['jumpUrl'] && $this->assign('jumpUrl', $_POST['jumpUrl']);
            $this->success($result['info']);
        } else {
            $this->error($result['info']);
        }
    }
}
