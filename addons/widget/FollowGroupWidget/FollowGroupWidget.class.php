<?php
/**
 * 调整关注用户分组Widget
 * @example {:W('FollowGroup', array('uid'=>$_following['uid'], 'fid'=>$_following['fid'], 'follow_group_status' => $follow_group_status)}
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class FollowGroupWidget extends Widget
{
    /**
     * 渲染关注用户分组调整模板
     * @example
     * $data['uid'] integer 用户ID
     * $data['fid'] integer 关注用户ID
     * $data['follow_group_status'] array 指定关注用户的分组信息
     * @data['tpl'] string 模板字段
     * @param  array  $data 配置的相关信息
     * @return string 渲染后的模板数据
     */
    public function render($data)
    {
        // 选择模板
        $template = empty($data['tpl']) ? 'FollowGroup' : t($data['tpl']);
        // 关注用户分类信息
        $followGroupStatus = $data['follow_group_status'];
        // 组装数据
        foreach ($followGroupStatus as $key => $value) {
            $value['gid'] != 0 && $value['title'] = (strlen($value['title']) + mb_strlen($value['title'], 'UTF8')) / 2 > 4 ? getShort($value['title'], 2) : $value['title'];
            $data['status'] .= $value['title'].',';
            if (!empty($followGroupStatus[$key + 1]) && (strlen($data['status']) + mb_strlen($data['status'], 'UTF8')) / 2 >= 6) {
                $data['status'] .= '...,';
                break;
            }
        }
        $data['status'] = substr($data['status'], 0, -1);
        $title = getSubByKey($followGroupStatus, 'title');       // 用于存储原始数据
        $data['title'] = implode(',', $title);

        $content = $this->renderFile(dirname(__FILE__).'/'.$template.'.html', $data);

        return $content;
    }

    /**
     * 渲染添加分组页面
     */
    public function addgroup()
    {
        return  $content = $this->renderFile(dirname(__FILE__).'/addgroup.html');
    }

    /**
     * 添加分组
     * @return array 添加分组状态和提示信息
     */
    public function doaddGroup()
    {
        // 验证是否超出个数
        $count = model('FollowGroup')->where('uid='.$GLOBALS['ts']['mid'])->count();
        if ($count >= 5) {
            $return = array('status' => 0, 'data' => '最多只能创建5个分组');
            exit(json_encode($return));
        }

        $groupname = htmlspecialchars($_POST['groupname'], ENT_QUOTES);
        // var_dump($groupname);die;
        $followGroup = model('FollowGroup')->getGroupList($GLOBALS['ts']['mid']);
        foreach ($followGroup as $v) {
            if ($v['title'] === $groupname) {
                $return = array('status' => 0, 'data' => L('PUBLIC_USER_GROUP_EXIST'));
                exit(json_encode($return));
            }
        }
        // 插入数据
        $res = model('FollowGroup')->setGroup($GLOBALS['ts']['mid'], $groupname);
        if ($res == 0) {
            $return = array('status' => 0, 'data' => L('PUBLIC_ADD_GROUP_NAME_ERROR'));
        } else {
            $return = array('status' => 1, 'data' => $res);
        }
        exit(json_encode($return));
    }

    /**
     * 渲染编辑分组页面
     */
    public function editgroup()
    {
        $followGroupDao = model('FollowGroup');
        $var['group_list'] = $followGroupDao->getGroupList($GLOBALS['ts']['mid']);
        foreach ($var['group_list'] as &$v) {
            $v['title'] = htmlspecialchars($v['title'], ENT_QUOTES);
        }

        return  $content = $this->renderFile(dirname(__FILE__).'/editgroup.html', $var);
    }

    /**
     * 验证分组个数
     * @return mixed 验证分组状态和提示信息
     */
    public function checkGroup()
    {
        $map['uid'] = $this->mid;
        $nums = model('FollowGroup')->where($map)->count();
        if ($nums >= 10) {
            $return = array('data' => L('PUBLIC_CRETAE_GROUP_MAX_TIPES', array('num' => 10)), 'status' => 0);
            echo json_encode($return);
            exit();
        }
        echo 1;
    }
}
