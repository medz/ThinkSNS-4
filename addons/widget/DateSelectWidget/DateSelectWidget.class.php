<?php
/**
 * 日期选择
 * @example 调用方法：W('DateSelect',array('name'=>'dateSelect','class'=>'s-txt','id'=>'dateSelectId','value'=>'','dtype'=>'full'))
 * @author  jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class DateSelectWidget extends Widget
{
    private static $rand = 1;

    /**
     * @param string name 组件input的名称
     * @param string class 组件input的样式
     * @param int id 组件input的ID
     * @param string value 默认的值
     * @param string dtype 时间类型
     */
    public function render($data)
    {
        !isset($data['width']) && $data['width'] = 200;

        $var = array();

        is_array($data) && $var = array_merge($var, $data);

        //渲染模版
        $content = $this->renderFile(dirname(__FILE__).'/default.html', $var);

        return $content;
    }
}
