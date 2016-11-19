<?php
/**
 * 地区选择 widget
 * @example W('Area',array('curPro'=>1,'curCity'=>2,'area'=>3,'tpl'=>'loadCity'))
 * @author Jason
 * @version TS3.0
 */
class AreaWidget extends Widget
{
    /**
     * @param int curPro 当前省的ID
     * @param int curCity 当前城市的ID
     * @param int area 当前地区的ID
     * @param string  tpl 选用的地区选择模版 loadCity(链接方式) loadArea(文本框形式)
     */
    public function render($data)
    {
        empty($data['tpl']) && $data['tpl'] = 'loadArea';

        if ($data['tpl'] == 'loadCity') {
            if (empty($data['curPro'])) {
                $info = model('Area')->getAreaById($data['curCity']);
                $data['city_ids'] = $info['pid'].','.$data['curCity'];
            } else {
                $data['city_ids'] = $data['curPro'].','.$data['curCity'];
            }
        }
        if (!empty($data['area'])) {
            $data['city_ids'] .= ','.$data['area'];
        }

        if ($data['tpl'] == 'selectArea') {
            $selectedArea = explode(',', t($_GET['selected']));
            if (!empty($selectedArea[0])) {
                $data['selectedarea'] = t($_GET['selected']);
            }

            $list = model('CategoryTree')->setTable('area')->getNetworkList();
            if ($data['tpl'] == 'selectArea') {
                $tmp = array();
                foreach ($list as $key => $value) {
                    $tmp['area_'.$key] = $value;
                }
                $list = $tmp;
                unset($tmp);
            }
            $data['list'] = json_encode($list);
            $data['required'] = isset($data['required']) ? $data['required'] : true;
        }
        if ($data['curPro'] && $data['curCity'] && $data['area']) {
            $data['selected'] = $data['curPro'].','.$data['curCity'].','.$data['area'];
        }
        $content = $this->renderFile(dirname(__FILE__).'/'.$data['tpl'].'.html', $data);

        return $content;
    }

    /**
     * 渲染地区选择弹窗
     */
    public function area()
    {
        // 已选择的地区
        $selectedArea = explode(',', t($_GET['selected']));
        if (!empty($selectedArea[0])) {
            $data['selectedarea'] = t($_GET['selected']);
        }

        $list = model('Area')->getNetworkList(0);
        $data['list'] = json_encode($list);
        // 模板选择
        $tpl = isset($_GET['tpl']) ? t($_GET['tpl']).'_' : 'loadArea_';

        echo $this->renderFile(dirname(__FILE__).'/'.$tpl.'.html', $data);
    }
}
