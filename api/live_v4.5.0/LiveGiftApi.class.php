
<?php
use Ts\Models\CreditUser;

/**
 * 签到API接口.
 *
 * @author
 *
 * @version  TS4.0
 */
require_once 'LiveBaseApi.class.php';
class LiveGiftApi extends LiveBaseApi
{
    /**
     * @name 添加/更新一个直播用户
     * @params 依次传�
     * � (string)usid,(int)sex,(string)uname,(boolean)ticket
     *
     * @return array 结果信息
     */
    private $Service_User_Url = '';
    private $mod = '';
    private $live_user_mod = '';
    private $user_mod = '';

    /**
     * 构造方法.
     *
     * @Author   Wayne[qiaobin@zhiyicx.com]
     * @DateTime 2016-10-12T21:50:08+0800
     */
    public function __construct()
    {
        parent::__construct();
        $this->Service_Gift_Url = $this->stream_server.'/users/syncNotify';
        $this->live_user_mod = M('live_user_info');
        $this->mod = M('');
        $this->user_mod = model('User');
    }

    /**
     * 送礼物.
     *
     * @Author   Wayne[qiaobin@zhiyicx.com]
     * @DateTime 2016-10-12T21:55:46+0800
     *
     * @return [type] [description]
     */
    public function haddleGift()
    {
        if (!$this->is_ZhiboService()) {
            return array(
                    'status' => 0,
                    'msg'    => '授权错误',
                );
        }
        $data = $_REQUEST;
        //获取订单
        $order = $data['order'];
        if (!$order) {
            $this->error = '订单不存在';

            return false;
        }

        /* 接收订单详细信息 **/
        $gift = [
            'num'         => $data['num'],        //数量
            'to_usid'     => $data['to_usid'],    //接收礼物的用户标识
            'usid'        => $data['usid'],       //赠送礼物的用户标识
            'type'        => $data['type'],       //当前的消费类型
            'order'       => $data['order'],      //在直播服务器上生成的订单号
            'description' => $data['description'], //礼物中文描述
            'ctime'       => $data['ctime'],      //订单创建时间
            'order_type'  => $data['order_type'], //订单类型
        ];
        $map['usid'] = array(
                                    'IN',
                                    array(
                                        '"'.$gift['usid'].'"',
                                        '"'.$gift['to_usid'].'"',
                                    ),
                                );
        $uids = M('live_user_info')->where($map)->getField('usid,uid');
        $gift['to_uid'] = $uids[$gift['to_usid']];
        $gift['uid'] = $uids[$gift['usid']];
        $credit_mod = new CreditUser();
        $user_data = $credit_mod->where('uid', $gift['uid'])->select('score')->first();
        if ($user_data->score < $gift['num']) {
            return array('status' => 0, 'message' => '金币数量不足');
        }
        $live_gift_log = M('live_gift_log');
        $res = M('live_gift_log')->add($gift);
        if (!$res) { //赠送记录
            echo json_encode(array('status' => 0, 'message' => '记录添加失败'));
            exit;
        } else {
            //用户积分增加以及减少
            $creditMod = model('Credit');
            $creditMod->setUserCredit($gift['uid'], array('des' => '赠送礼物', 'score' => -$gift['num'], 'name' => '', 'alias' => '赠送礼物', 'type' => 6, 'cid' => 0), 1, array('score' => -$gift['num'], 'order' => $gift['order']));
            $creditMod->setUserCredit($gift['to_uid'], array('des' => '收到礼物', 'score' => $gift['num'], 'name' => '收到礼物', 'alias' => '收到礼物', 'type' => 6, 'cid' => 0), 1, array('score' => $gift['num'], 'order' => $gift['order']));
            echo json_encode(array('status' => 1, 'data' => array('is_sync' => 1)));
            exit;
        }
        die;
    }
}
