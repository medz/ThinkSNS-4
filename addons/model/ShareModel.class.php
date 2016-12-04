<?php
/**
 * 分享模型 - 业务逻辑模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class ShareModel
{
    /**
     * 分享到分享
     *
     * @example 需要传入的$data值
     *          sid：转发的分享/资源ID
     *          app_name：app名称
     *          content：转发时的内容信息，有时候会有某些标题的资源
     *          body：转发时，自定义写入的内容
     *          type：分享类型（分享原文app_row_table）
     *          comment：是否给原作者评论
     * @param array $data
     *                          分享的相关数据
     * @param string $from
     *                          是否发@给资源作者，默认为share
     * @param array $lessUids
     *                        去掉@用户，默认为null
     * @return array  分享操作后，相关反馈信息数据
     */
    public function shareFeed($data, $from = 'share', $lessUids = null)
    {
        // 返回的数据结果集
        $return = array(
                'status' => 0,
                'data' => L('PUBLIC_SHARE_FAILED'),
        ); // 分享失败
                                                                       // 验证数据正确性
        if (empty($data ['sid'])) {
            return $return;
        }

        $stable = t($data ['type']); // 资源所在的表名
        $sid = t($data ['sid']);
        $app = isset($data ['app_name']) ? $data ['app_name'] : APP_NAME; // 当前产生分享所属的应用
        $forApi = $data ['forApi'] ? true : false; // 是否为接口形式
        if (! $oldInfo = model('Source')->getSourceInfo($stable, $sid, $forApi, $app)) {
            $return ['data'] = L('PUBLIC_INFO_SHARE_FORBIDDEN'); // 此信息不可以被分享
            return $return;
        }
        // 内容数据
        $d ['content'] = isset($data ['content']) ? str_replace(SITE_URL, '[SITE_URL]', $data ['content']) : '';
        $d ['body'] = str_replace(SITE_URL, '[SITE_URL]', $data ['body']);
        // 处理分享类型
        $feedType = 'repost'; // 默认为普通的转发格式
        if (! empty($oldInfo ['feedtype']) && ! in_array($oldInfo ['feedtype'], array(
                'post',
                'postimage',
                'postfile',
        ))) {
            $feedType = $oldInfo ['feedtype'];
        }
        if ($app != 'public') { // 非分享类型内容转发
            $oldInfo ['uid'] = $oldInfo ['source_user_info'] ['uid'];
            $oldInfo ['sourceInfo'] ['source_id'] = $oldInfo ['feed_id'];
            $feedType = $app.'_repost';
        }

        $d ['sourceInfo'] = ! empty($oldInfo ['sourceInfo']) ? $oldInfo ['sourceInfo'] : $oldInfo;

        /* emoji处理 */
        isset($d['sourceInfo']['source_content']) &&
        $d['sourceInfo']['source_content'] = formatEmoji(true, $d['sourceInfo']['source_content']);

        // 是否发送@上级节点
        $isOther = ($from == 'comment') ? false : true;
        // 获取上个节点资源ID
        $d ['curid'] = $data ['curid'];

        // 获取转发原分享信息
        $appId = $oldInfo ['source_id'];
        $appTable = $oldInfo ['source_table'];

        $d ['from'] = isset($data ['from']) ? intval($data ['from']) : 0;
        $d ['latitude'] = isset($data ['latitude']) ? $data ['latitude'] : 0;
        $d ['longitude'] = isset($data ['longitude']) ? $data ['longitude'] : 0;
        $d ['address'] = isset($data ['address']) ? $data ['address'] : 0;
        if ($res = model('Feed')->put($GLOBALS ['ts'] ['mid'], $app, $feedType, $d, $appId, $appTable, null, $lessUids, $isOther, 1)) {
            // if($data['comment'] != 0 && $oldInfo['uid'] != $data['comment_touid']) {
            if (($data ['comment'] != 0 || $data ['comment_old'] != 0)) { // && $oldInfo['uid'] != $data['comment_touid']
                                                                         // 发表评论
                $c ['type'] = 2;
                $c ['app'] = $app;
                $c ['table'] = 'feed';
                $c ['app_uid'] = $oldInfo ['uid'];
                $c ['content'] = ! empty($d ['body']) ? $d ['body'] : $d ['content'];
                $c ['row_id'] = ! empty($oldInfo ['sourceInfo']) ? $oldInfo ['sourceInfo'] ['source_id'] : $appId;
                $c ['client_type'] = getVisitorClient();
                $notCount = true;
                unlockSubmit();
                $comment_id = model('Comment')->addComment($c, true, $notCount, $lessUids);
                // 同步到微吧
                if ($app == 'weiba') {
                    $postDetail = D('weiba_post')->where('feed_id='.$c ['row_id'])->find();
                    if ($postDetail) {
                        $datas ['weiba_id'] = $postDetail ['weiba_id'];
                        $datas ['post_id'] = $postDetail ['post_id'];
                        $datas ['post_uid'] = $postDetail ['post_uid'];
                        // $datas['to_reply_id'] = $data['to_comment_id']?D('weiba_reply')->where('comment_id='.$data['to_comment_id'])->getField('reply_id'):0;
                        // $datas['to_uid'] = $data['to_uid'];
                        $datas ['uid'] = $GLOBALS ['ts'] ['mid'];
                        $datas ['ctime'] = time();
                        $datas ['content'] = $c ['content'];
                        $datas ['comment_id'] = $comment_id;
                        if (D('weiba_reply')->add($datas)) {
                            $map ['last_reply_uid'] = $this->mid;
                            $map ['last_reply_time'] = $datas ['ctime'];
                            D('weiba_post')->where('post_id='.$datas ['post_id'])->save($map);
                            // 回复统计数加1
                            D('weiba_post')->where('post_id='.$datas ['post_id'])->setInc('reply_count');
                        }
                    }
                }
            }
            // 添加话题
            model('FeedTopic')->addTopic(html_entity_decode($d ['body'], ENT_QUOTES), $res ['feed_id'], $feedType);
            // 渲染数据
            $rdata = $res; // 渲染完后的结果
            $rdata ['feed_id'] = $res ['feed_id'];
            $rdata ['app_row_id'] = $data ['sid'];
            $rdata ['app_row_table'] = $data ['type'];
            $rdata ['app'] = $app;
            $rdata ['is_repost'] = 1;
            switch ($app) {
                case 'weiba':
                    $rdata ['from'] = getFromClient(0, $app, '微吧');
                    break;
                default:
                    $rdata ['from'] = getFromClient($from, $app);
                    break;
            }
            $return ['data'] = $rdata;
            $return ['status'] = 1;
            // 被分享内容“分享统计”数+1，同时可检测出app,table,row_id 的有效性
            if (! $pk = D($data ['type'], $data ['app_name'])->getPk()) {
                $pk = $data ['type'].'_id';
            }
            D($data ['type'], $data ['app_name'])->setInc('repost_count', "`{$pk}`={$data['sid']}", 1);
            if ($data ['curid'] != $data ['sid'] && ! empty($data ['curid'])) {
                if (! $pk = D($data ['curtable'])->getPk()) {
                    $pk = $data ['curtable'].'_id';
                }
                D($data ['curtable'])->setInc('repost_count', "`{$pk}`={$data['curid']}", 1);
                D($data ['curtable'])->cleanCache($data ['curid']);
            }
            D($data ['type'], $data ['app_name'])->cleanCache($data ['sid']);
        } else {
            $return ['data'] = model('Feed')->getError();
        }

        return $return;
    }

    /**
     * 分享给同事
     *
     * @example 需要传入的$data值
     *          uid：同事用户ID
     *          sid：转发的分享/资源ID
     *          app_name：app名称
     *          content：转发时的内容信息，有时候会有某些标题的资源
     *          body：转发时，自定义写入的内容
     *          type：分享类型
     *          comment：是否给原作者评论
     * @param array $data
     *                     分享的相关数据
     * @return array 分享操作后，相关反馈信息数据
     */
    public function shareMessage($data)
    {
        $return = array(
                'status' => 0,
                'data' => L('PUBLIC_SHARE_FAILED'),
        ); // 分享失败
        $app = t($data ['app_name']);
        $msg ['to'] = trim($data ['uids'], ',');
        if (empty($msg ['to'])) {
            $return ['data'] = L('PUBLIC_SHARE_TOUSE_EMPTY'); // 分享接受人不能为空
            return $return;
        }
        if (! $oldInfo = model('Source')->getSourceInfo($data ['type'], $data ['sid'], false, $app)) {
            $return ['data'] = L('PUBLIC_INFO_SHARE_FORBIDDEN'); // 此信息不可以被分享
            return $return;
        }
        $data ['content'] = trim($data ['content']);
        $content = empty($data ['content']) ? '' : "“{$data['content']}”&nbsp;//&nbsp;";
        $content = parse_html($content);
        $message ['to'] = $msg ['to'];
        $message ['content'] = $content.parse_html($oldInfo ['source_content']).'&nbsp;&nbsp;<a href="'.$oldInfo ['source_url'].'" target="_blank">查看</a>';
        if (model('Message')->postMessage($message, $GLOBALS ['ts'] ['_user'] ['uid'])) {
            // 发表评论
            $c ['type'] = 3;
            $c ['app'] = $app;
            $c ['table'] = 'feed';
            $c ['app_uid'] = $oldInfo ['uid'];
            $c ['content'] = str_replace(SITE_URL, '[SITE_URL]', $data ['content']);
            $c ['row_id'] = ! empty($oldInfo ['sourceInfo']) ? $oldInfo ['sourceInfo'] ['source_id'] : $data ['sid'];
            $c ['client_type'] = getVisitorClient();
            $notCount = false;
            unlockSubmit();
            $comment_id = model('Comment')->addComment($c, true, $notCount, $lessUids);
            // $config['name'] = $GLOBALS['ts']['_user']['uname'];
            // $config['content'] = $content;
            // //$config['sourceurl'] = $oldInfo['source_url'];
            // $touids = explode(',', $msg['to']);
            // foreach($touids as $v) {
            // model('Notify')->sendNotify($v, 'new_message', $config);
            // }

            $return = array(
                    'status' => 1,
                    'data' => L('PUBLIC_SHARE_SUCCESS'),
            ); // 分享成功
        }

        return $return;
    }
}
