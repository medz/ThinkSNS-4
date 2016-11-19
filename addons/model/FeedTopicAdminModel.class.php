<?php
/**
 * 话题模型
 * @version TS3.0
 */
class FeedTopicAdminModel extends Model
{
    //var $tableName = 'feed_topic';

    /**
     * 获取全部话题
     * @param int limit 每页显示条数
     * @return array 话题列表
     */
    public function getTopic($limit = 20, $isrecommend = 0)
    {
        if ($isrecommend) {
            $map['recommend'] = 1;
        }
        if ($_POST) {
            $_POST['topic_id'] && $map['topic_id'] = intval($_POST['topic_id']);
            $_POST['topic_name'] && $map['topic_name'] = array('like', '%'.t($_POST['topic_name']).'%');
            $_POST['recommend'] && $map['recommend'] = $_POST['recommend'] == 1 ? 1 : 0;
            $_POST['essence'] && $map['essence'] = $_POST['essence'] == 1 ? 1 : 0;
            $_POST['lock'] && $map['lock'] = $_POST['lock'] == 1 ? 1 : 0;
        }
        $topic_list = D('feed_topic')->where($map)->order('recommend desc,recommend_time desc,ctime desc')->findpage($limit);
        //数据格式化
        foreach ($topic_list['data'] as $k => $v) {
            if ($v['recommend'] == 1) {
                $topic_list['data'][$k]['topic_name'] = '<a target="_blank" href="'.U('public/Topic/index', array('k' => urlencode($topic_list['data'][$k]['topic_name']))).'">'.$topic_list['data'][$k]['topic_name'].'</a>';
            } else {
                $topic_list['data'][$k]['topic_name'] = '<a target="_blank" href="'.U('public/Topic/index', array('k' => urlencode($topic_list['data'][$k]['topic_name']))).'">'.$topic_list['data'][$k]['topic_name'].'</a>';
            }
            $pic = D('attach')->where('attach_id='.$v['pic'])->find();
            $topic_list['data'][$k]['pic'] && $topic_list['data'][$k]['pic'] = '<img src="'.getImageUrl($pic['save_path'].$pic['save_name']).'" width="50">';
            $topic_user = explode(',', $v['topic_user']);
            $topic_user_info = model('User')->getUserInfoByUids($topic_user);
            $topic_list['data'][$k]['topic_user'] = '';
            foreach ($topic_user as $key => $val) {
                $topic_list['data'][$k]['topic_user'] .= $topic_user_info[$val]['space_link'].'<br />';
            }
            //dump($topic_list['data'][$k]['topic_user']);exit;
            // $isrecommend = $v['recommend']?'是':'否';
            // $topic_list['data'][$k]['recommend'] = '<a href="javascript:void(0);" onclick="admin.setTopic(1,'.$v['topic_id'].','.intval($v['recommend']).')">'.$isrecommend.'</a>';
            // $isessence = $v['essence']?'是':'否';
            // $topic_list['data'][$k]['essence'] = '<a href="javascript:void(0);" onclick="admin.setTopic(2,'.$v['topic_id'].','.intval($v['essence']).')">'.$isessence.'</a>';
            $islock = $v['lock'] ? '取消屏蔽' : '屏蔽';
            // 操作数据
            $topic_list['data'][$k]['DOACTION'] = '<a href="javascript:void(0);" onclick="admin.setTopic(3,'.$v['topic_id'].','.intval($v['lock']).')">'.$islock.'</a> - ';
            $topic_list['data'][$k]['DOACTION'] .= '<a href="'.U('admin/Content/editTopic', array('topic_id' => $v['topic_id'], 'tabHash' => 'editTopic')).'">编辑</a>';
            //$listData['data'][$k]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.deleteUser(\''.$v['uid'].'\')">'.L('PUBLIC_STREAM_DELETE').'</a>';
        }

        return $topic_list;
    }

    /**
     * 后台添加话题
     * @param array data 要添加的话题信息
     * @return bool 是否成功
     */
    public function addTopic($data)
    {
        $maps['topic_name'] = t($data['topic_name']);
        $maps['count'] = 0;
        $maps['note'] = t($data['note']);
        $maps['domain'] = t($data['domain']);
        $maps['des'] = t($data['des']);
        $maps['pic'] = t($data['pic']);
        $maps['topic_user'] = t($data['topic_user']);
        $maps['outlink'] = t($data['outlink']);
        $maps['recommend'] = intval($data['recommend']);
        if ($maps['recommend']) {
            $maps['recommend_time'] = time();
        }
        $maps['essence'] = intval($data['essence']);
        $maps['ctime'] = time();

        return D('feed_topic')->add($maps);
    }
}
