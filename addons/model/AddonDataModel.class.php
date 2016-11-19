<?php
/**
 * 插件数据模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
// 载入Key-Value存储引擎模型 - 数据对象模型
tsload(ADDON_PATH.'/model/XdataModel.class.php');
class AddonDataModel extends XdataModel
{
    const PREFIX = 'addons:';            // 前缀

    protected $list_name = 'addons';    // 存储的list名称

    /**
     * 写入插件参数列表
     * @param  string $addonName 插件名称
     * @param  array  $data      插件相关数据
     * @return bool   是否写入成功
     */
    public function lputAddons($addonName, $data = array())
    {
        return parent::lput(self::PREFIX.$addonName, $data);
    }

    /**
     * 读取插件参数列表
     * @param  string $addonName 插件名称
     * @return array  插件参数列表
     */
    public function lgetAddons($addonName)
    {
        return parent::lget(self::PREFIX.$addonName);
    }

    /**
     * 写入单个插件数据
     * @param  string $key     要存储的参数list:key
     * @param  string $value   要存储的参数的值
     * @param  bool   $replace false为插入新参数，ture为更新已有参数，默认为true
     * @return bool   是否写入成功
     */
    public function putAddons($key, $value = '', $replace = false)
    {
        return parent::put(self::PREFIX.$key, $value, $replace);
    }

    /**
     * 读取插件数据list:key
     * @param  string $key 要获取的某个参数list:key；如过没有:则认为，只有list没有key
     * @return string 相应的list中的key值数据
     */
    public function getAddons($key)
    {
        return parent::get(self::PREFIX.$key);
    }

    /**
     * 批量读取插件数据，非必要
     * @param  string       $listName 插件参数列表list
     * @param  array|object $keys     参数键key
     * @return array        通过list与key批量获取的数据
     */
    public function getAllAddons($listName, $keys)
    {
        return parent::getAll(self::PREFIX.$listName, $keys);
    }
}
