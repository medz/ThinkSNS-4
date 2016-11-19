<?php

/**
 * 备注 Widget
 * @example W('Remark',array('uid'=>1000,'remark'=>'TS3.0','showonly'=>0))
 * @version TS3.0
 */
class RemarkWidget extends Widget
{
    private static $rand = 1;
    /**
     * @param int uid 目标用户的UID
     * @param string remark 用户已经被设置的备注名称
     * @param int showonly 是否只显示已有的备注
     */
    public function render($data)
    {
        $var = array();
        $var['uid'] = $GLOBALS['ts']['uid'];
        $var['remark'] = '';
        is_array($data) && $var = array_merge($var, $data);
        if ($var['uid'] == $GLOBALS['ts']['mid']) {
            return '';
        }
        $var['rand'] = self::$rand;
        //渲染模版
        // $content = $this->renderFile(dirname(__FILE__)."/remark.html",$var);
        $content = $this->renderData($var);

        self::$rand ++;

        unset($var, $data);

        //输出数据
        return $content;
    }

    //不用模板直接返回数据
    private function renderData($var)
    {
        extract($var, EXTR_OVERWRITE);

        $html = "<span class='remark'>";
        if (!empty($remark)) {
            $html .= "<em>(</em><a href=\"javascript:;\" title='".L('PUBLIC_CLICK_EDIT')."' event-node='setremark' remark='".urlencode($remark)."' class =\"remark_{$uid}\" uid='{$uid}'>{$remark}</a><em>)</em>";
        } elseif ($showonly != 1) {
            $html .= "<em>(</em><a href=\"javascript:;\" title='".L('PUBLIC_CLICK_SETING')."' event-node='setremark' remark='' class =\"remark_{$uid}\" uid='{$uid}'>".L('PUBLIC_REMARK_SETTING').'</a><em>)</em>';
        }
        $html .= '</span>';

        return $html;
    }

    /**
     * 渲染备注编辑弹框
     * @return string 修改后的备注内容
     */
    public function edit()
    {
        $var = $_REQUEST;
        $content = $this->renderFile(dirname(__FILE__).'/edit.html', $var);

        return $content;
    }
}
