<?php
/**
 * 标签选择
 * @example W('Tag', array('width'=>'500px', 'appname'=>'support','apptable'=>'support','row_id'=>0,'tpl'=>'show','tag_url'=>'aaa','name'=>'public'))
 * @author zhishisoft
 * @version TS3.0
 */
class TagWidget extends Widget
{
    /**
     * @param string width wigdet显示宽度
     * @param string appname 资源对应的应用名称
     * @param string apptable 资源对应的表
     * @param int row_id 资源在资源所在表中的主键ID,为0表示该资源为新加资源，需要在资源添加,往sociax_app_tag表添加相关数据
     * @param string tpl 显示模版，tag/show 默认是tag，如果为show表示只显示标签
     * @param string tag_url 标签上的链接前缀，为空表示标签没有链接，只针对tpl=show有效
     * @param string name 输入框的input名称,标签的ID存储的隐藏域名称为 {name}_tags
     */
    public function render($data)
    {
        $var = array();
        $var['width'] = '200px';
        $var['appname'] = 'public';
        $var['apptable'] = 'user';
        $var['row_id'] = 0;
        $var['tpl'] = 'tag';
        $var['tag_num'] = 10;
        $var['isUserTag'] = false;
        is_array($data) && $var = array_merge($var, $data);
        // 清除缓存
        model('Cache')->rm('temp_'.$var['apptable'].$GLOBALS['ts']['mid']);
        $var['add_url'] = U('widget/Tag/addTag', array('appname' => $var['appname'], 'apptable' => $var['apptable'], 'row_id' => $var['row_id']));
        $var['delete_url'] = U('widget/Tag/deleteTag', array('appname' => $var['appname'], 'apptable' => $var['apptable'], 'row_id' => $var['row_id']));
        // 获取标签
        $tags = model('Tag')->setAppName($var['appname'])->setAppTable($var['apptable'])->getAppTags($var['row_id'], $var['isUserTag']);
        $var['tags'] = $tags;
        // 以选中ID
        $map['row_id'] = array('IN', $var['row_id']);
        $map['table'] = $var['apptable'];
        $app_tags = D('app_tag')->where($map)->findAll();
        $tag_ids = getSubByKey($app_tags, 'tag_id');
        $var['tags_my_ids'] = implode(',', $tag_ids);
        // 获取标签名称
        $var['tags_my'] = model('Tag')->getTagNames($tag_ids);

        // 获取推荐标签
        $uid = intval($data['uid']);
        $var['uid'] = $uid;
        $var['selected'] = model('UserCategory')->getRelatedUserInfo($uid);
        !empty($var['selected']) && $var['selectedIds'] = getSubByKey($var['selected'], 'user_category_id');
        $var['nums'] = count($tags);
        $var['categoryTree'] = model('CategoryTree')->setTable('user_category')->getNetworkList();
        foreach ($var['categoryTree'] as $key => $value) {
            if (empty($value['child'])) {
                unset($var['categoryTree'][$key]);
            }
        }
        $content = $this->renderFile(dirname(__FILE__).'/'.$var['tpl'].'.html', $var);

        return $content;
    }

    /*
     * 添加个人兴趣
     * @access public
     * @return void
     */
    public function addTag()
    {
        // 获取标签内容
        $_POST['name'] = t($_POST['name']);
        // 判断是否为空
        if (empty($_POST['name'])) {
            exit(json_encode(array('status' => 0, 'info' => L('PUBLIC_TAG_NOEMPTY'))));
        }
        // 其他相关参数
        $appName = t($_REQUEST['appname']);
        $appTable = t($_REQUEST['apptable']);
        $row_id = intval($_REQUEST['row_id']);
        $result = model('Tag')->setAppName($appName)->setAppTable($appTable)->addAppTags($row_id, t($_POST['name']));
        // 返回相关参数
        $return['info'] = model('Tag')->getError();
        $return['status'] = !empty($result) > 0 ? 1 : 0;
        $return['data'] = $result;
        exit(json_encode($return));
    }

    /*
     * 删除个人兴趣
     * @access public
     * @return void
     */
    public function deleteTag()
    {
        $appName = t($_REQUEST['appname']);
        $appTable = t($_REQUEST['apptable']);
        $row_id = intval($_REQUEST['row_id']);
        $result = model('Tag')->setAppName($appName)->setAppTable($appTable)->deleteAppTag($row_id, t($_POST['tag_id']));

        $return['info'] = model('Tag')->getError();
        $return['status'] = $result;
        $return['data'] = null;
        echo json_encode($return);
        exit();
    }

    /**
     * 获取标签的ID
     */
    public function getTagId()
    {
        // 获取标签名称
        $name = t($_POST['name']);
        // 判断标签是否为空
        if (empty($name)) {
            $res['status'] = 0;
            $res['info'] = L('PUBLIC_TAG_NOEMPTY');
            exit(json_encode($res));
        }
        // 其他相关参数
        $appName = t($_REQUEST['appname']);
        $appTable = t($_REQUEST['apptable']);
        // $rowId = intval($_REQUEST['row_id']);
        $tagInfo = model('Tag')->setAppName($appName)->setAppTable($appTable)->getTagId($name);

        if ($tagInfo === false) {
            $res['status'] = 0;
            $res['info'] = '获取标签ID失败';
        } else {
            $res['status'] = 1;
            $res['info'] = '获取标签ID成功';
            $res['data'] = $tagInfo;
        }

        exit(json_encode($res));
    }
}
