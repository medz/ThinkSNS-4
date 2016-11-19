<?php
/**
 * 举报弹窗Widget
 * @example {:W('Denouce',array())}
 * @author jason
 * @version TS3.0
 */
class DenouceWidget extends Widget
{
    private static $rand = 1;

    /**
     * @return bool false
     */
    public function render($data)
    {
        return  false;
    }

    /**
     * 举报弹框
     * @return string 弹窗页面HTML
     */
    public function index()
    {
        // 获取相关数据
        $var = $this->getVar();
        // TODO
        if ($var['source']['app'] != 'public' && $var['source']['is_repost'] == 0) {
            $var['source']['source_content'] = $var['source']['api_source']['source_content'];
        }
        $content = $this->renderFile(dirname(__FILE__).'/index.html', $var);

        return $content;
    }

    /**
     * 判断资源是否已经被举报
     * @return json 判断后的相关信息
     */
    public function isDenounce()
    {
        $map['from'] = t($_REQUEST['type']);
        $map['aid'] = t($_REQUEST['aid']);
        $map['uid'] = $GLOBALS['ts']['mid'];
        $count = model('Denounce')->where($map)->count();
        $res = array();
        if ($count) {
            $res['status'] = 1;
            $res['data'] = '该信息已被举报！';
        } else {
            $res['status'] = 0;
            $res['data'] = L('PUBLIC_REPORT_ERROR');
        }
        exit(json_encode($res));
    }

    /**
     * 格式化模板变量
     * @return array 被举报的信息
     */
    public function getVar()
    {
        if (empty($_GET['aid']) || empty($_GET['fuid']) || empty($_GET['type'])) {
            return false;
        }
        foreach ($_GET as $k => $v) {
            $var[$k] = t($v);
        }
        $var['uid'] = $GLOBALS['ts']['mid'];
        empty($var['app']) && $var['app'] = 'public';
        $var['source'] = model('Source')->getSourceInfo($var['type'], $var['aid'], false, $var['app']);

        return $var;
    }

    /**
     * 提交举报
     * @return array 举报信息和操作状态
     */
    public function post()
    {
        $map['from'] = trim($_POST['from']);
        $map['aid'] = intval($_POST['aid']);
        $map['uid'] = intval($_POST['uid']);
        $map['fuid'] = intval($_POST['fuid']);
        // 判断资源是否删除

        $fmap['is_del'] = 0;
        if ($_POST['from'] == 'weiba_post') {
            $fmap['post_id'] = intval($_POST['aid']);
            $isExist = M('weiba_post')->where($fmap)->count();
        } else {
            $fmap['feed_id'] = intval($_POST['aid']);
            $isExist = model('Feed')->where($fmap)->count();
        }
        if ($isExist == 0) {
            $return['status'] = 0;
            $return['data'] = '内容已被删除，举报失败';
            exit(json_encode($return));
        }
        $return = array();
        if ($isDenounce = model('Denounce')->where($map)->count()) {
            $return = array('status' => 0, 'data' => L('PUBLIC_REPORTING_INFO'));
        } else {
            $map['content'] = h($_POST['content']);
            $map['reason'] = t($_POST['reason']);
            $map['source_url'] = str_replace(SITE_URL, '[SITE_URL]', t($_POST['source_url']));
            $map['ctime'] = time();
            if ($id = model('Denounce')->add($map)) {
                //添加积分
                model('Credit')->setUserCredit($_POST['uid'], 'report_weibo');
                model('Credit')->setUserCredit($_POST['fuid'], 'reported_weibo');

                $touid = D('user_group_link')->where('user_group_id=1')->field('uid')->findAll();
                foreach ($touid as $k => $v) {
                    model('Notify')->sendNotify($v['uid'], 'denouce_audit');
                }
                $return = array('status' => 1, 'data' => '您已经成功举报此信息');
            } else {
                $return = array('status' => 0, 'data' => L('PUBLIC_REPORT_ERROR'));
            }
        }
        exit(json_encode($return));
    }
}
