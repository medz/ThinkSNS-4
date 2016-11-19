<?php
/**
 * DIY Widget
 * @example {:W('Diy',array())}
 * @version TS3.0
 */
class DiyWidget extends Widget
{
    /**
     * [render description]
     * @param  int id [description]
     * @return int widget_user_id [description]
     */
    public function render($data)
    {
        $var['id'] = 1; //自定义diy的位置

        !empty($data) && $var = array_merge($var, $data);

        $wigdetList = model('Widget')->getUserWidget($var['id'], $GLOBALS['ts']['uid']);

        $var = array_merge($var, $wigdetList);

        return $this->renderFile(dirname(__FILE__).'/default.html', $var);
    }

    public function addWidget()
    {
        $var = $_REQUEST;
        //全部列表
        $var['list'] = model('Widget')->getWidgetList();

        $wigdetList = model('Widget')->getUserWidget($var['diyId'], $GLOBALS['ts']['uid']);

        foreach ($wigdetList['widget_list'] as $v) {
            $var['selected'][] = $v['appname'].':'.$v['name'];
        }

        return $this->renderFile(dirname(__FILE__).'/add.html', $var);
    }

    public function dosort()
    {
        $id = intval($_REQUEST['diyId']);
        $uid = $GLOBALS['ts']['mid'];
        $targets = t($_REQUEST['targets']);
        model('Widget')->dosort($id, $uid, $targets);
    }

    public function doadd()
    {
        if (model('Widget')->saveUserWigdet(intval($_POST['diyId']), $GLOBALS['ts']['uid'], t($_POST['selected']))) {
            $return = array('status' => 1, 'data' => '', 'info' => L('PUBLIC_SAVE_SUCCESS'));
        } else {
            $return = array('status' => 0, 'data' => '', 'info' => L('PUBLIC_SAVE_FAIL'));
        }

        echo json_encode($return);
        exit();
    }

    public function set()
    {
        $var = $_REQUEST;

        $data = $_POST;

        $return = model('Widget')->updateUserWidget($var['diyId'], $GLOBALS['ts']['uid'], $var['appname'].':'.$var['widget_name'], $data);

        if ($return) {
            $return = array('status' => 1, 'data' => '', 'info' => L('PUBLIC_SETING_SUCCESS'));
        } else {
            $return = array('status' => 0, 'data' => '', 'info' => L('PUBLIC_SYSTEM_SETTING_FAIL'));
        }

        echo json_encode($return);
        exit();
    }

    public function del()
    {
        $var = $_REQUEST;

        $return = model('Widget')->deleteUserWidget($var['diyId'], $GLOBALS['ts']['uid'], $var['appname'].':'.$var['widget_name']);

        if ($return) {
            $return = array('status' => 1, 'data' => '', 'info' => L('PUBLIC_DELETE_SUCCESS'));
        } else {
            $return = array('status' => 0, 'data' => '', 'info' => L('PUBLIC_DELETE_FAIL'));
        }

        echo json_encode($return);
        exit();
    }

    public function updateWidget()
    {
        model('Widget')->updateWidget();
        echo 'Wigdet'.L('PUBLIC_UPDATE_SUCCESS');
        exit();
    }

    public function config()
    {
        $var['diyId'] = intval($_REQUEST['id']);

        $var['list'] = model('Widget')->getWidgetList();

        $slist = model('Widget')->getDiyWidgetById($var['diyId']);

        $slist = unserialize($slist['widget_list']);

        foreach ($slist as $v) {
            $var['selected'][] = $v['appname'].':'.$v['name'];
        }

        return $this->renderFile(dirname(__FILE__).'/config.html', $var);
    }
    public function doconfig()
    {
        if (model('Widget')->configWidget(intval($_POST['diyId']), t($_POST['selected']))) {
            $return = array('status' => 1, 'data' => '', 'info' => L('PUBLIC_SAVE_SUCCESS'));
        } else {
            $return = array('status' => 0, 'data' => '', 'info' => L('PUBLIC_SAVE_FAIL'));
        }

        echo json_encode($return);
        exit();
    }
}
