<?php
/**
 * DIY模块dao类
 * @author Stream
 *
 */
class DiyWidgetModel extends Model
{
    protected $tableName = 'diy_widget';

    public function checkCache($sign)
    {
        //得到该widget的缓存配置消息
        $map['pluginId'] = $sign;
        $cacheInfo = $this->where($map)->field('cacheTime,cTime,mTime,cache')->find();
        if (!$cacheInfo) {
            return true;
        }
        if (empty($cacheInfo['cache'])) {
            return true;
        }
        //如果等于0.则为每次都要渲染
        if ($cacheInfo['cacheTime'] == 0) {
            return true;
        } else {
            //有缓存时间
            $time = $cacheInfo['cacheTime'] * 60;
            if (time() - $cacheInfo['mTime'] >= $time) {
                return true;
            } else {
                return false;
            }
        }
    }
    /**
     * 返回模块参数
     * @param  string $sign
     * @return mixed
     */
    public function getTagInfo($sign)
    {
        $map['pluginId'] = $sign;
        $data = $this->where($map)->field('pluginId,tagLib,content,ext,status')->find();
        $ext = unserialize($data['ext']);
        $result['tagName'] = $ext['tagInfo']['name'];
        $result['content'] = $data['content'];
        $result['tagLib'] = $data['tagLib'];
        $result['attr'] = unserialize($ext['attr']);

        return $result;
    }
    /**
     * 返回模块参数  $sign是数组
     * @param  array $sign
     * @return mixed
     */
    public function getTagInofs(array $sign)
    {
        $map['pluginId'] = array('in', $sign);
        $res = implode("','", $sign);
        $data = $this->query("select pluginId,tagLib,content,ext,status from {$this->tablePrefix}diy_widget where pluginId in ('".$res."')");
        $result = array();
        foreach ($data as $value) {
            $ext = unserialize($value['ext']);
            $result[$value['pluginId']]['tagName'] = $ext['tagInfo']['name'];
            $result[$value['pluginId']]['content'] = $value['content'];
            $result[$value['pluginId']]['tagLib'] = $value['tagLib'];
            $result[$value['pluginId']]['attr'] = unserialize($ext['attr']);
        }

        return $result;
    }

    /**
     * 通过id获取到该widget的tagName
     */
    public function getTagName($widgetId)
    {
        $map['widgetId'] = $widgetId;
        $ext = unserialize($this->where($map)->getField('ext'));

        return $ext['tagInfo']['name'];
    }

    /**
     * 通过id获取到该widget的模板
     */

    public function getTemplateByPluginId($pluginId)
    {
        $map['pluginId'] = $pluginId;
        $content = $this->where($map)->getField('content');

        return empty($content) ? false : $content;
    }

    /**
     * 通过id获取到该widget的模板
     */
    public function getTemplateByWidgetId($WidgetId)
    {
        $map['widgetId'] = $WidgetId;
        $content = $this->where($map)->getField('content');

        return empty($content) ? false : $content;
    }
    /**
     * 通过Id更新该widget的样式模板
     */
    public function setTemplate($widgetId, $content)
    {
        $map['widgetId'] = $widgetId;
        $save['content'] = $content;

        return $this->where($map)->save($save);
    }

    public function getCache($sign)
    {
        $map['pluginId'] = $sign;

        return $this->where($map)->getField('cache');
    }

    public function saveCache($sign, $content)
    {
        $map['pluginId'] = $sign;
        $save['cache'] = $content;
        $save['mTime'] = time();
        $result = $this->where($map)->save($save);
    }
    /**
     * 是否存在相同的模块
     * @param  unknown_type $sign
     * @return bool
     */
    public function checkHasWidget($sign)
    {
        $map['pluginId'] = $sign;
        $count = $this->where($map)->count();

        return $count > 0 ? true : false;
    }
}
