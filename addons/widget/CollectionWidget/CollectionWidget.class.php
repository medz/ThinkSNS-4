<?php
/**
 * 收藏
 * @example W('Collection',array('sid'=>1,'stable'=>'feed','sapp'=>'public','tpl'=>'simple'))
 * @author Jason
 * @version TS3.0
 */
class CollectionWidget extends Widget
{
    /**
     * @param int sid 资源ID
     * @param string stable 资源所在的表
     * @param string sapp 资源所在的应用
     * @param string tpl 渲染的模板，可分为simple(有统计数) 和 btn(无统计数)
     */
    public function render($data)
    {
        $var['tpl'] = 'btn';
        $var['type'] = 'btn';

        is_array($data) && $var = array_merge($var, $data);

        $var['coll'] = model('Collection')->getCollection($var['sid'], $var['stable']);
        $var['count'] = model('Collection')->getCollectionCount($var['sid'], $var['stable']);

        //默认模板直接输出，减少模版解析，提升效率
        if ($var['tpl'] == 'btn') {
            extract($var, EXTR_OVERWRITE);
            if (!$coll) {
                return "<a href=\"javascript:;\" onclick=\"core.plugInit('collection',this,'{$type}','{$sid}','{$stable}','{$sapp}')\" rel=\"add\">".L('PUBLIC_STREAM_LIKE').'</a>';
            } else {
                return "<a href=\"javascript:;\" onclick=\"core.plugInit('collection',this,'{$type}','{$sid}','{$stable}','{$sapp}')\" rel=\"remove\">".L('PUBLIC_CANCEL_FAVORITE').'</a>';
            }
        }

        $content = $this->renderFile(dirname(__FILE__).'/'.t($var['tpl']).'.html', $var);

        return $content;
    }

    /**
     * 添加收藏记录
     * @return array 收藏状态和成功提示
     */
    public function addColl()
    {
        $return = array('status' => 0, 'data' => L('PUBLIC_FAVORITE_FAIL'));
        if (empty($_POST['sid']) || empty($_POST['stable'])) {
            $return['data'] = L('PUBLIC_RESOURCE_ERROR');
            echo json_encode($return);
            exit();
        }
        $data['source_table_name'] = t($_POST['stable']);
        $data['source_id'] = intval($_POST['sid']);
        $data['source_app'] = t($_POST['sapp']);

        // 验证资源是否已经被删除
        $key = $data['source_table_name'].'_id';
        $map[$key] = $data['source_id'];
        $map['is_del'] = 0;
        $isExist = model(ucfirst($data['source_table_name']))->where($map)->count();
        if (empty($isExist)) {
            $return = array('status' => 0, 'data' => '内容已被删除，收藏失败');
            exit(json_encode($return));
        }

        if (model('Collection')->addCollection($data)) {
            $return = array('status' => 1, 'data' => L('PUBLIC_FAVORITE_SUCCESS'));
        } else {
            $return['data'] = model('Collection')->getError();
            empty($return['data']) && $return['data'] = L('PUBLIC_FAVORITE_FAIL');
        }
        exit(json_encode($return));
    }

    /**
     * 取消收藏
     * @return array 成功取消的状态及错误提示
     */
    public function delColl()
    {
        $return = array('status' => 0, 'data' => L('PUBLIC_EDLFAVORITE_ERROR'));
        if (empty($_POST['sid']) || empty($_POST['stable'])) {
            $return['data'] = L('PUBLIC_RESOURCE_ERROR');
            echo json_encode($return);
            exit();
        }
        if (model('Collection')->delCollection(intval($_POST['sid']), t($_POST['stable']))) {
            $return = array('status' => 1, 'data' => L('PUBLIC_CANCEL_ERROR'));
        } else {
            $return['data'] = model('Collection')->getError();
            empty($return['data']) && $return['data'] = L('PUBLIC_EDLFAVORITE_ERROR');
        }
        exit(json_encode($return));
    }
}
