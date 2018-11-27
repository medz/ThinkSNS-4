<?php

class DenounceApi extends Api
{
    public function post()
    {
        $map['from'] = t($this->data['from']);
        $map['aid'] = intval($this->data['aid']);
        if (empty($map['from']) || empty($map['aid'])) {
            $return['status'] = 0;
            $return['info'] = '参数错误';

            return $return;
        }
        $map['uid'] = $this->mid;
        $info = array();
        $sourceUrl = '';
        switch ($map['from']) {
            case 'feed':
                $info = model('Feed')->get($map['aid']);
                $map['fuid'] = $info['uid'];
                $sourceUrl = U('public/Profile/feed', array('feed_id' => $info['feed_id'], 'uid' => $info['uid']));
                break;
            case 'weiba':
                break;
        }
        // 判断资源是否删除
        $fmap['feed_id'] = $map['aid'];
        $fmap['is_del'] = 0;
        $isExist = model('Feed')->where($fmap)->count();
        if ($isExist == 0) {
            $return['status'] = 0;
            $return['info'] = '内容已被删除，举报失败';

            return $return;
        }
        $return = array();
        model('Denounce')->where($map)->count();
        if ($isDenounce = model('Denounce')->where($map)->count()) {
            $return['status'] = 0;
            $return['info'] = L('PUBLIC_REPORTING_INFO');
        } else {
            $map['content'] = $info['body'];
            $map['reason'] = '手机端举报';
            $map['source_url'] = str_replace(SITE_URL, '[SITE_URL]', $sourceUrl);
            $map['ctime'] = time();
            if ($id = model('Denounce')->add($map)) {
                // 添加积分
                model('Credit')->setUserCredit($this->mid, 'report_weibo');
                model('Credit')->setUserCredit($map['fuid'], 'reported_weibo');

                $return = array('status' => 1, 'info' => '您已经成功举报此信息');
            } else {
                $return = array('status' => 0, 'info' => L('PUBLIC_REPORT_ERROR'));
            }
        }

        return $return;
    }
}
