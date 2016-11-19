<?php

// 礼物接口
class TagApi extends Api
{
    /**
     * 以分类树形结构获取所有标签
     *
     * @return array
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getTreeAll()
    {
        return model('CategoryTree')->setTable('user_category')->getNetworkList();
    }

    /**
     * 所有礼物 --using
     *
     * @param
     *        	integer categoryId 分类id
     * @param
     *        	integer max_id 上次返回的最后一个礼物
     * @param
     *        	integer count 礼物个数
     * @return array 所有礼物
     */
    public function tag_all()
    {
        $var ['categoryTree'] = model('CategoryTree')->setTable('user_category')->getNetworkList();
        $all = array();
        foreach ($var ['categoryTree'] as $key => $value) {
            if (! empty($value ['child'])) {
                // dump( $value ['child']);
                $all = array_merge($all, $value ['child']);
            }
        }

        return $all;
    }

    /**
     * 我的礼物 --using
     */
    public function tag_my()
    {
        // $tags = model ( 'Tag' )->setAppName ( 'public' )->setAppTable ( 'user' )->getAppTags ( $this->mid );
        // $lists = array ();
        // foreach ( $tags as $k => $v ) {
        // 	$arr ['tag_id'] = $k;
        // 	$arr ['tag_name'] = $v;
        // 	$lists [] = $arr;
        // }
        // return $lists;
        $_tags = model('Tag')->setAppName('public')->setAppTable('user')->getAppTags($this->mid, true);
        $tags = array();
        foreach ($_tags as $tagId => $tagName) {
            array_push($tags, array(
                'tag_id' => $tagId,
                'tag_name' => $tagName,
            ));
        }

        return $tags;
    }

    /*
     * 添加个人标签
     * @access public
     * @return void
     */
    public function addTag()
    {
        // $tags = model('Tag')->setAppName('public')->setAppTable('user')->getAppTags($this->mid);
        // if (count($tags) >= 5) {
        //     return array(
        //             'status' => 0,
        //             'info' => '最多只能选择5个'
        //     );
        // }

        // 获取标签内容
        $_REQUEST ['name'] = t($_REQUEST ['name']);
        $tags = explode(',', $_REQUEST['name']);
        // 判断是否为空
        if (empty($tags)) {
            return array(
                    'status' => 0,
                    'info' => L('PUBLIC_TAG_NOEMPTY'),
            );
        }

        if (count($tags) > 5) {
            return array(
                    'status' => 0,
                    'info' => '最多只能选择5个',
            );
        }
        M('app_tag')->where(array('app' => 'public', 'table' => 'user', 'row_id' => $this->mid))->delete();
        // 其他相关参数
        $appName = 'public';
        $appTable = 'user';
        $row_id = $this->mid;
        $result = model('Tag')->setAppName($appName)->setAppTable($appTable)->addAppTags($row_id, $tags);
        // 返回相关参数
        $return ['info'] = model('Tag')->getError();
        $return ['status'] = ! empty($result) > 0 ? 1 : 0;
        $return ['data'] = $result;

        return $return;
    }

    /*
     * 删除个人标签
     * @access public
     * @return void
     */
    public function deleteTag()
    {
        $appName = 'public';
        $appTable = 'user';
        $row_id = $this->mid;
        $result = model('Tag')->setAppName($appName)->setAppTable($appTable)->deleteAppTag($row_id, t($_REQUEST ['tag_id']));

        $return ['info'] = model('Tag')->getError();
        $return ['status'] = ! empty($result) > 0 ? 1 : 0;
        $return ['data'] = $result;

        return $return;
    }
}
