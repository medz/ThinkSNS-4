<?php
/**
 * 报表图形Js图形Widget,用于生产各种图形报表
 * @example {:W('Plot', array('type'=>'pieChart', 'id'=>'1', 'pWidth'=>$pWidth, 'pHeight'=>$pHeight, 'key'=>$key, 'value'=>$value1, 'color'=>$color, 'show'=>$show1))}
 * @author zivss
 * @version TS3.0
 **/
class PlotWidget extends Widget
{
    /**
     * @param string type 报表类型 分为pieChart(饼状图)、pointLabelsChart(折线图)、barChart(柱状图)
     * @param int id //未知
     * @param int pWidth 图表宽度
     * @param int pHeight 图表高度
     * @param array key //好像没用
     * @param array value
     * @param array color
     */
    public function render($data)
    {
        $var['tpl'] = isset($data['tpl']) ? $data['tpl'] : 'chart';
        // 获取渲染模板
        $type = t($data['type']);
        $var['type'] = $type;
        $var['id'] = t($data['id']);
        $pWidth = isset($data['pWidth']) ? intval($data['pWidth']) : 120;
        $pHeight = isset($data['pHeight']) ? intval($data['pHeight']) : 120;
        // 显示图形的div长宽
        $var['plotCls'] = 'width:'.$pWidth.'px;height:'.$pHeight.'px';
        // 获取图形显示数据
        $plotData = $this->getPlotPlugins($type, $data);
        $var = array_merge($var, $plotData);

        //渲染模版
        $content = $this->renderFile(dirname(__FILE__).'/'.$var['tpl'].'.html', $var);

        return $content;
    }

    /*** 私有方法 ***/
    /**
     * Plot插件路由
     * @return 统计图信息
     */
    private function getPlotPlugins($type, $data)
    {
        switch ($type) {
            case 'pieChart':
                $var = $this->getPieChart($data);
                break;
            case 'pointLabelsChart':
                $var = $this->getPointLabels($data);
                break;
            case 'barChart':
                $var = $this->getBarChart($data);
                break;
            case 'zx':
                $var = $this->getZx($data);
                break;
        }

        return $var;
    }

    /**
     * 未知，无显示
     */
    public function getZx($data)
    {
        /*
        $data['value'][] = array(-1,4,4,2,5,6,7,13);
        $data['value'][] = array(-1,2,4,5,4,2,3,22);
        $data['ticks']	= array('周一','er','san','si','wu','liu','ri');
        $data['title']  = '一周进展';
        测试数据
        */
        $var['jsHtml'] = '';
        $most = 0;
        foreach ($data['value'] as $k => $v) {
            $tmp = '';
            foreach ($v as $kk => $vv) {
                $vv > $most && $most = $vv;
                $tmp[] = "[$kk,$vv]";
            }
            $var['jsHtml'] .= " args.obj[$k] =[".implode(',', $tmp).'];';
        }
        foreach ($data['ticks'] as $k => $v) {
            $var['jsHtml'] .= " args.ticks[$k] = '{$v}';";
        }
        $most = $most % 5 > 0 ? $most + (5 - $most % 5) : $most;

        $var['jsHtml'] .= " args.title = '{$data['title']}';";
        $var['jsHtml'] .= " args['x'][0] = 0;";
        $var['jsHtml'] .= " args['x'][1] = ".count($data['ticks']).';';
        $var['jsHtml'] .= " args['y'][0] = 0;";
        $var['jsHtml'] .= " args['y'][1] = {$most};";
        $var['jsHtml'] .= " args['y']['numberTicks'] = 5;";

        return $var;
    }

    /**
     * 获取饼状图显示数据
     * @return array 饼状图信息
     */
    private function getPieChart($data)
    {
        // 配置对应的数据
        $var['key'] = implode(',', $data['key']);
        // 换算数据的百分比
        $sum = array_sum($data['value']);
        if ($sum == 0) {
            $var['value'] = '100,0';
        } else {
            foreach ($data['value'] as &$value) {
                $value = 100 * $value / $sum;
            }
            $var['value'] = implode(',', $data['value']);
        }
        $var['color'] = implode(',', $data['color']);
        $var['show'] = $data['show'];
        $var['location'] = $data['location'];

        return $var;
    }

    /**
     * 获取折线图显示数据
     * @return array 折线图信息
     */
    private function getPointLabels($data)
    {
        // 配置对应的数据
        $var['value'] = implode(',', $data['value']);
        // 报表标题
        $var['title'] = t($data['title']);
        $var['show'] = $data['show'];
        $var['location'] = $data['location'];

        return $var;
    }

    /**
     * 获取条形图显示数据
     * @return array 条形图信息
     */
    private function getBarChart($data)
    {
        // 配置对应的数据
        $var['key'] = implode(',', $data['key']);
        $var['value'] = implode(',', $data['value']);
        // 报表标题
        $var['title'] = t($data['title']);
        $var['show'] = $data['show'];
        $var['location'] = $data['location'];

        return $var;
    }
}
