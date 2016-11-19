<?php
/**
 * 话题模型
 * @version TS3.0
 */
class FeedTopicModel extends Model
{
    public $tableName = 'feed_topic';

    //添加话题
    public function addTopic($content, $feedId = false, $type)
    {
        $content = str_replace('＃', '#', $content);
        preg_match_all("/#([^#]*[^#^\s][^#]*)#/is", $content, $arr);
        $arr = array_unique($arr[1]);
        $topicIds = array();
        foreach ($arr as $v) {
            // $topicIds[] = $this->addKey($v, $feedId, $type);
            array_push($topicIds, $this->addKey($v, $feedId, $type));
        }
        // if (count($topicIds) == 1) {
        // 	return $topicIds[0];
        // }

        count($topicIds) == 1 and $topicId = $topicIds['0'];

        return $topicIds;
    }

    //添加话题
    private function addKey($key, $feedId, $type)
    {
        //$map['name'] = trim(t(mStr(preg_replace("/#/",'',trim($key)),150,'utf-8',false)));
        $map['topic_name'] = trim(preg_replace('/#/', '', t($key)));
        if ($topic = $this->where($map)->find()) {
            $this->setInc('count', $map);
            if ($topic['recommend'] == 1) {
                model('Cache')->rm('feed_topic_recommend'); //清除缓存
            }
            if ($feedId) {
                $this->addFeedJoinTopic($map['topic_name'], $feedId, $type, true);
            }
        } else {
            $map['count'] = 1;
            $map['ctime'] = time();
            $map['status'] = 0;
            $map['lock'] = 0;
            $map['domain'] = '';
            $map['recommend'] = 0;
            $topicId = $this->add($map);
            if ($feedId) {
                $this->addFeedJoinTopic($topicId, $feedId, $type);
            }

            return $topicId;
        }
    }

    //添加分享与话题的关联
    private function addFeedJoinTopic($topicNameOrId, $feedId, $type, $isExist = false)
    {
        if ($isExist) {
            $map['topic_name'] = $topicNameOrId;
            $topicId = $this->where($map)->getField('topic_id');
        } else {
            $topicId = $topicNameOrId;
        }

        $add['feed_id'] = $feedId;
        $add['topic_id'] = $topicId;
        if (is_null($type)) {
            $add['type'] = 0;
        } else {
            $add['type'] = $type;
        }
    //	$add['transpond_id'] = $data['transpond_id'];

        D('feed_topic_link')->add($add);
    }

    //删除分享与话题关联
    public function deleteWeiboJoinTopic($feedId)
    {
        $del['feed_id'] = $feedId;
        if ($topic_id = D('feed_topic_link')->where($del)->getField('topic_id')) {
            D('feed_topic_link')->where($del)->delete();
            D('feed_topic')->where('topic_id='.$topic_id)->setDec('count');
            if (D('feed_topic')->where('topic_id='.$topic_id)->getField('recommend') == 1) {
                model('Cache')->rm('feed_topic_recommend'); //清除缓存
            }
        }
    }

    // 获取话题详细信息
    public function getTopic($topic_name = null, $add = true)
    {
        if (!$topic_name) {
            return false;
        } elseif (!($id = $this->getTopicId($topic_name, $add))) {
            return false;
        } elseif (($topic = $this->where('`topic_id` = '.$id)->find())) {
            $topic['topic_name'] or $topic['topic_name'] = $topic_name;
        }

        return $topic;

        // if ($topic_name) {
        // 	$topic_name = $topic_name;
        // 	$map['topic_id'] = $this->getTopicId($topic_name,$add);
        // 	if ( !$map['topic_id'] ){
        // 		return false;
        // 	}
        // } else {
        // 	return false;
        // }
        // //$map['isdel'] = 0;
        // $topic = $this->where($map)->find();
        // if ($topic) {
        // 	$topic['topic_name'] = $topic_name ? t($topic_name) : D('Topic', 'weibo')->getField('topic_name', "topic_id={$topic['topic_id']}");
        // }
        // return $topic;
    }
    /**
     *返回与话题相关的分享ID
     */
    public function getFeedIdByTopic($topic)
    {
        $sql = "select b.feed_id as fid from {$this->tablePrefix}feed_topic a inner join {$this->tablePrefix}feed_topic_link b on a.topic_id=b.topic_id where a.topic_name ='".$topic."'";
        $feeds = $this->query($sql);

        return getSubByKey($feeds, 'fid');
    }
    /**
     * 获取给定话题名的话题ID
     * @param  string $name 话题名
     * @return int    话题ID
     */
    public function getTopicId($topic_name, $add = true)
    {
        $topic_name = preg_replace('/#/', '', $topic_name);

        // # 判断是否不存在话题
        if (empty($topic_name) or !$topic_name) {
            return 0;

        // # 获取数据库话题的ID
        } elseif (($id = $this->where('`topic_name` LIKE "'.$topic_name.'"')->field('`topic_id`')->getField('topic_id'))) {
            return $id;

        // # 添加话题
        } elseif ($add) {
            return $this->add(array(
                'topic_name' => $topic_name,
                'count' => '0',
                'ctime' => time(),
            ));
        }

        return 0;

        // $map['topic_name'] = preg_replace("/#/",'',$topic_name);
        // if (empty($map['topic_name'])) return 0;
        // $info = $this->where($map)->find();
        // if ($info['topic_id']) {
        // 	return $info['topic_id'];
        // }
        // if( $add ){
        // 	$map['count'] = 0;
        // 	$map['ctime'] = time();
        // 	return $this->add($map);
        // }
        // return false;
    }
}
