<?php
/**
 * �
 * �注分组控制器.
 *
 * @author jason <yangjs17@yeah.net>
 *
 * @version TS3.0
 */
class FollowGroupAction extends Action
{
    /**
     * 分组选择数据加载操作.
     *
     * @param string $type 弹窗类型，box、list
     *
     * @return [type] [description]
     */
    public function selector($type = 'box')
    {
        $fid = intval($_REQUEST['fid']);
        isset($_REQUEST['isrefresh']) && $this->assign('isrefresh', intval($_REQUEST['isrefresh']));

        $followGroupDao = D('FollowGroup');
        $group_list = $followGroupDao->getGroupList($this->mid);
        $f_group_status = $followGroupDao->getGroupStatus($this->mid, $fid);

        if ($type == 'list') {
            // TODO:未完成？
            //foreach($group_list as &$v){
            //	 $v['title'] = (strlen($v['title'])+mb_strlen($v['title'],'UTF8'))/2>6?getShort($v['title'],3):$v['title'];
            //}
        }

        $this->assign('fuserInfo', model('User')->getUserInfo($fid));
        $this->assign('fid', $fid);
        $this->assign('group_list', $group_list);
        $this->assign('f_group_status', $f_group_status);
        $check_group = getSubByKey($f_group_status, 'gid');
        $this->assign('check_group', $check_group);
    }

    /**
     * 分组选择页面，下拉式.
     */
    public function selectorList()
    {
        $this->selector('list');
        $this->display();
    }

    /**
     * 分组选择页面，弹窗式.
     */
    public function selectorBox()
    {
        $this->selector();
        $this->display();
    }

    /**
     * 设置指定好友的�
     * �注分组状态
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function setFollowGroup()
    {
        $gid = intval($_REQUEST['gid']);
        $fid = intval($_REQUEST['fid']);
        $add = t($_REQUEST['add']);
        exit(json_encode($this->_setFollowGroup($gid, $fid, $add)));
    }

    /**
     * 设置指定好友的�
     * �注分组状态 - 多个分组.
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function setFollowGroups()
    {
        $gids = t($_REQUEST['gids']);
        $fid = intval($_REQUEST['fid']);
        $add = t($_REQUEST['add']);
        if (!$add) {
            D('UserFollowGroupLink')->where('uid='.$this->mid.' and fid='.$fid)->delete();
        }
        if (empty($gids) || empty($fid)) {
            $res['status'] = 0;
            $res['info'] = '保存失败';
        } else {
            $gids = explode(',', $gids);
            foreach ($gids as $gid) {
                $gid = intval($gid);
                $this->_setFollowGroup($gid, $fid, $add);
            }
            $res['status'] = 1;
            $res['info'] = '保存成功';
        }
        exit(json_encode($res));
    }

    /**
     * 设置指定用户的分组.
     *
     * @param int    $gid    分组ID
     * @param int    $fid    用户ID
     * @param string $action 操作状态类型，空、add、delete
     */
    private function _setFollowGroup($gid, $fid, $add)
    {
        $followGroupDao = D('FollowGroup');
        $followGroupDao->setGroupStatus($this->mid, $fid, $gid, $add);
        $follow_group_status = $followGroupDao->getGroupStatus($this->mid, $fid);
        foreach ($follow_group_status as $k => $v) {
            $v['gid'] != 0 && $v['title'] = (strlen($v['title']) + mb_strlen($v['title'], 'UTF8')) / 2 > 4 ? getShort($v['title'], 2) : $v['title'];
            $_follow_group_status .= $v['title'].',';
            if (!empty($follow_group_status[$k + 1]) && (strlen($_follow_group_status) + mb_strlen($_follow_group_status, 'UTF8')) / 2 >= 6) {
                $_follow_group_status .= '...,';
                break;
            }
        }
        $_follow_group_status = substr($_follow_group_status, 0, -1);
        S('weibo_followlist_'.$this->mid, null);
        $result['title'] = $_follow_group_status;
        $title = getSubByKey($follow_group_status, 'title');       // 用于存储原始数据
        $result['oldTitle'] = implode(',', $title);

        return $result;
    }

    /**
     * 添加�
     * �注分组操作.
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function saveGroup()
    {
        $follow_group_id = intval($_REQUEST['gid']);
        if (!empty($follow_group_id)) {
            $save['title'] = htmlspecialchars($_REQUEST['title'], ENT_QUOTES);
            if ($save['title'] === '') {
                $this->ajaxReturn('', L('PUBLIC_FROUPNAME_NOEMPTY'), 0);            // 分组名称不能为空
            }
            // 判断用户分组名称是否存在
            $group_list = model('FollowGroup')->getGroupList($this->mid);
            foreach ($group_list as $v) {
                if ($v['title'] === $save['title']) {
                    $this->ajaxReturn('', L('PUBLIC_SAVE_GROUP_FAIL'), 0);            // 保存分组失败
                }
            }

            if (D('')->table(C('DB_PREFIX').'user_follow_group')->where("follow_group_id={$follow_group_id}")->save($save)) {
                // 清理缓存
                model('FollowGroup')->cleanCache($GLOBALS['ts']['mid'], $follow_group_id);
                $this->ajaxReturn('', L('PUBLIC_SAVE_GROUP_SUCCESS'), 1);            // 保存分组成功
            }
        } else {
            $this->ajaxReturn('', L('PUBLIC_SAVE_GROUP_FAIL'), 0);                    // 保存分组失败
        }
    }

    /**
     * 设置�
     * �注分组Tab页面.
     */
    public function setGroupTab()
    {
        if (is_numeric($_REQUEST['gid'])) {
            $gid = intval($_REQUEST['gid']);
            $title = D('FollowGroup')->getField('title', "follow_group_id={$gid}");
            $this->assign('gid', $gid);
            $this->assign('title', $title);
        }

        $this->display();
    }

    /**
     * 保存用户备注操作.
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function saveRemark()
    {
        $r = array('status' => 0, 'data' => L('PUBLIC_REMARK_ADD_FAIL'));            // 备注添加失败
        // 设置备注
        if (!empty($_POST['fid'])) {
            $map['uid'] = $GLOBALS['ts']['mid'];
            $map['fid'] = intval($_POST['fid']);
            $save['remark'] = t($_POST['remark']);
            // 默认全部编辑正确
            D('')->table(C('DB_PREFIX').'user_follow')->where($map)->save($save);
            S('follow_remark_'.$map['uid'], null);
            $r = array('status' => 1, 'data' => $save['remark']);
        }
        exit(json_encode($r));
    }

    /**
     * 设置用户�
     * �注分组、修改�
     * �注分组操作.
     */
    public function setGroup()
    {
        $title = trim(t($_REQUEST['title']));
        if ($title === '') {
            $this->error(L('PUBLIC_FROUPNAME_NOEMPTY'));            // 分组名称不能为空
        }
        if (!$_REQUEST['gid']) {
            $res = D('FollowGroup')->setGroup($this->mid, $title);
            $gid = $res;
        } else {
            $gid = intval($_REQUEST['gid']);
            $res = D('FollowGroup')->setGroup($this->mid, $title, $gid);
        }

        if (!empty($_REQUEST['fid']) && !empty($gid)) {
            $fid = intval($_REQUEST['fid']);
            $this->_setFollowGroup($gid, $fid, 'add');
        }
        S('weibo_followlist_'.$this->mid, null);

        if ($res) {
            $this->success($res);
        } else {
            $error = !$_REQUEST['gid'] ? L('PUBLIC_USER_GROUP_EXIST') : L('PUBLIC_OPERATE_GROUP_FAIL');            // 您已经创建过这个分组了，分组操作失败
            $this->error($error);
        }
    }

    /**
     * 删除指定用户的指定�
     * �注分组.
     *
     * @return json 是否删除成功
     */
    public function deleteGroup()
    {
        $gid = intval($_REQUEST['gid']);
        if (empty($gid)) {
            $msg['status'] = 0;
            $msg['info'] = '删除失败';
            exit(json_encode($msg));
        }
        $res = D('FollowGroup')->deleteGroup($this->mid, $gid);
        if ($res) {
            $msg['status'] = 1;
            $msg['info'] = '删除成功';
            exit(json_encode($msg));
        } else {
            $msg['status'] = 0;
            $msg['info'] = '删除失败';
            exit(json_encode($msg));
        }
    }
}
