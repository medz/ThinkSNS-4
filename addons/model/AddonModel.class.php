<?php
/**
 * 插件模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class AddonModel extends Model
{
    protected $tableName = 'addons';
    protected $fields = array(
            0 => 'addonId',
            1 => 'name',
            2 => 'pluginName',
            3 => 'author',
            4 => 'info',
            5 => 'version',
            6 => 'status',
            7 => 'lastupdate',
            8 => 'site',
            9 => 'tsVersion',
            10 => 'is_weixin',
    );
    private $valid = array(); // 已安装插件
    private $invalid = array(); // 待安装插件
    private $fileAddons = array(); // 插件对象

    /**
     * 获取所有插件列表
     *
     * @return array 所有插件列表
     */
    public function getAddonAllList()
    {
        $this->_getFileAddons();
        // 获取数据库中的所有插件
        $map ['is_weixin'] = intval($_GET ['is_weixin']);
        $databaseAddons = $this->where($map)->findAll();
        $this->_validAddons($databaseAddons);

        $this->_invalidAddons();
        $result ['valid'] ['data'] = $this->valid;
        $result ['valid'] ['name'] = '已安装插件';
        $result ['invalid'] ['data'] = $this->invalid;
        $result ['invalid'] ['name'] = '待安装插件';

        return $result;
    }

    /**
     * 重置所有已安装插件列表缓存
     *
     * @return array 最新的插件列表
     */
    public function resetAddonCache($renew = false)
    {
        if (empty($this->fileAddons) || $renew) {
            $this->_getFileAddons();
        }
        $addonList = $this->getAddonsValid();

        $addonCache = array();
        foreach ($addonList as $key => $value) {
            if (isset($this->fileAddons [$value ['name']])) {
                $addonCache = $this->_createAddonsCacheData($value ['name'], $addonCache);
            }
        }
        $res = S('system_addons_list', $addonCache);

        return $addonCache;
    }

    /**
     * 获取已安装的插件列表
     *
     * @return array 已安装的插件列表
     */
    public function getAddonsValid()
    {
        $map ['status'] = '1';

        return $this->where($map)->findAll();
    }

    /**
     * 获取未安装的插件列表
     *
     * @return array 未安装的插件列表
     */
    public function getAddonsInvalid()
    {
        // TODO:待完成
    }

    /**
     * 通过插件ID停止插件
     *
     * @param  int  $id
     *                  插件ID
     * @return bool 插件是否停止
     */
    public function stopAddonsById($id)
    {
        if (empty($id)) {
            return false;
        }
        // 将数据库中标示该插件停止
        $result = $this->_stopAddons('addonId', intval($id));

        return $result ? true : false;
    }

    /**
     * 通过插件名称停止插件
     *
     * @param  string $name
     *                      插件名称
     * @return bool   插件是否停止
     */
    public function stopAddonsByName($name)
    {
        if (empty($name)) {
            return false;
        }
        // 将数据库中标示该插件停止
        $result = $this->_stopAddons('name', $name);

        return $result ? true : false;
    }

    /**
     * 通过插件ID获取插件对象
     *
     * @param  int    $id
     *                    插件ID
     * @return object 指定插件对象
     */
    public function getAddonObj($id)
    {
        $data = $this->getAddon($id);
        if ($data) {
            $this->_getFileAddons();

            return $this->fileAddons [$data ['name']];
        }

        return false;
    }

    /**
     * 停止插件
     *
     * @param  string $field
     *                       查询插件的Key值
     * @param  string $value
     *                       查询插件的Value值
     * @return bool   插件是否停止
     */
    private function _stopAddons($field, $value)
    {
        // 将数据库中标示该插件停止
        $map [$field] = $value;
        if ($filed != 'name') {
            $addon = $this->where($map)->find();
            $name = $addon ['name'];
        } else {
            $name = $value;
        }
        $save ['status'] = '0';
        $result = $this->where($map)->save($save);
        if ($result) {
            $addonCacheList = $this->resetAddonCache();
            S('system_addons_list', $addonCacheList);
        }

        return $result ? true : false;
    }

    /**
     * 通过插件名称启动插件
     *
     * @param  string $name
     *                      插件名称
     * @return bool   插件是否启动
     */
    public function startAddons($name)
    {
        // 先查看该插件是否安装
        $map ['name'] = t($name);
        $addon = $this->where($map)->find();
        // 装载缓存列表
        $this->_getFileAddons();
        if (! isset($this->fileAddons [$name])) {
            return false;
            // throw new ThinkException("插件".$name."的目录不存在");
        }
        // 如果安装后启用的，设置插件启动
        if ($addon && $addon ['status'] == 0) {
            $save ['status'] = '1';
            $result = $this->where($map)->save($save) ? true : false;
        } elseif ($addon && $addon ['status'] == 1) {
            $result = false;
        } else {
            $addonObject = $this->fileAddons [$name];
            $add = $addonObject->getAddonInfo();
            $add ['name'] = $name;
            $add ['status'] = '1';
            if ($this->add($add) && $addonObject->install()) {
                $result = true;
            } else {
                $result = false;
            }
        }

        if ($result) {
            $addonCacheList = $this->resetAddonCache();
            S('system_addons_list', $addonCacheList);
        }

        return $result;
    }

    /**
     * 通过插件名称卸载插件
     *
     * @param  string $name
     *                      插件名称
     * @return bool   插件是否卸载成功
     */
    public function uninstallAddons($name)
    {
        if (empty($name)) {
            return false;
        }
        $this->_getFileAddons();
        if (! isset($this->fileAddons [$name])) {
            return false;
            // throw new ThinkException("插件".$name."不存在");
        }
        $addonObject = $this->fileAddons [$name];
        $addonObject->uninstall();

        $map ['name'] = $name;
        $result = $this->where($map)->delete() ? true : false;
        if ($result) {
            $addonCacheList = $this->resetAddonCache();
            S('system_addons_list', $addonCacheList);
        }

        return $result;
    }

    /**
     * 获取指定插件信息
     *
     * @param  int   $id
     *                       插件ID
     * @param  int   $status
     *                       插件状态
     * @return array 指定插件信息
     */
    public function getAddon($id, $status = 1)
    {
        $map ['addonId'] = intval($id);
        $status = intval($status);
        $map ['status'] = "$status";

        return $this->where($map)->find();
    }

    /**
     * 获取所有插件管理面板所需数据
     *
     * @return array 所有插件管理面板所需数据
     */
    public function getAddonsAdmin()
    {
        $valid = $this->getAddonsValid();
        $this->_getFileAddons();
        if (empty($valid)) {
            return array();
        }
        $data = array();
        foreach ($valid as $value) {
            $obj = isset($this->fileAddons [$value ['name']]) ? $this->fileAddons [$value ['name']] : null;
            if ($obj && $obj->adminMenu()) {
                $data [] = array(
                        $value ['pluginName'],
                        $value ['addonId'],
                );
            }
        }

        return $data;
    }

    /**
     * 创建插件缓存数据
     *
     * @param  string $name
     *                           插件名称
     * @param  array  $addonList
     *                           插件列表
     * @return array  返回插件列表
     */
    private function _createAddonsCacheData($name, $addonList)
    {
        $list = $this->fileAddons [$name]->getHooksList($name);
        // 合并钩子缓存列表
        if (empty($addonList)) {
            $addonList = $list;
        } else {
            $result = array();
            $addonListKey = array_keys($addonList);
            $listKey = array_keys($list);
            $addonList = array_merge_recursive($addonList, $list);
        }

        return $addonList;
    }

    /**
     * 验证已安装的插件
     *
     * @param array $databaseAddons
     *                              插件列表数据
     */
    private function _validAddons($databaseAddons)
    {
        if (empty($databaseAddons)) {
            return;
        }
        foreach ($databaseAddons as $value) {
            if ($value ['status'] == 1) {
                $this->valid [] = $value;
            } else {
                $this->invalid [] = $value;
            }
            if (isset($this->fileAddons [$value ['name']])) {
                unset($this->fileAddons [$value ['name']]);
            }
        }
    }

    /**
     * 验证未安装的插件
     *
     */
    private function _invalidAddons()
    {
        // 获取未启用的插件
        foreach ($this->fileAddons as $key => $value) {
            $data = $value->getAddonInfo();
            $data ['status'] = 0;
            $data ['name'] = $key;
            $this->invalid [] = $data;
        }
    }

    /**
     * 设置所有插件对象
     *
     */
    private function _getFileAddons()
    {
        // 获取文件夹下面的所有插件
        $dirName = ADDON_PATH.'/plugin/';
        $dir = dir($dirName);
        $fileAddons = array();
        while (false !== $entry = $dir->read()) {
            if ($entry == '.' || $entry == '..' || $entry == '.svn') {
                continue;
            }
            $path = $dirName.'/'.$entry;
            $addonsFile = $path.'/'.$entry.'Addons.class.php';
            tsload(CORE_PATH.'/OpenSociax/addons/AbstractAddons.class.php');
            tsload(CORE_PATH.'/OpenSociax/addons/NormalAddons.class.php');
            tsload(CORE_PATH.'/OpenSociax/addons/SimpleAddons.class.php');
            if (file_exists($addonsFile)) {
                tsload($addonsFile);
                $class = $entry.'Addons';
                $fileAddons [$entry] = new $class ();
                $fileAddons [$entry]->setPath($path);
            }
        }

        $this->fileAddons = $fileAddons;
    }

    /**
     * 获取后台所有插件URL
     *
     * @return array 后台所有插件URL
     */
    public function getAddonsAdminUrl()
    {
        $addons = $this->getAddonsAdmin();
        $r = array();
        foreach ($addons as $value) {
            $r [$value [0]] = U('admin/Addons/admin', array(
                    'pluginid' => $value [1],
                    'is_weixin' => intval($_GET ['is_weixin']),
            ));
        }

        return $r;
    }
}
