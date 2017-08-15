<?php
/**
 * app 提现�.
 *
 * 值模块
 * bs.
 */
use Ts\Models as Model;

class ApplicationApi extends Api
{
    //加密key
    protected $key = 'ThinkSNS';

    //获取版本号 用于app获取更新配置
    public function getVersion()
    {
        $info = model('Xdata')->get('admin_Application:ZB_config');
        if (!empty($info['version'])) {
            $version = $info['version'];
        } else {
            $version = 1; //未配置  初始版本
        }

        return Ts\Service\ApiMessage::withArray($version, 1, '');
        // return $this->rd($version);
    }

    //获取支付相关配置
    public function getZBConfig()
    {
        //获取配置简单加密
        $key = $this->data['key'];
        if (md5($this->key) != $key) {
            return $this->rd('', '认证失败', 1);
        }
        $chongzhi_info = model('Xdata')->get('admin_Config:charge');
        $info['cash_exchange_ratio_list'] = getExchangeConfig('cash');
        $info['charge_ratio'] = $chongzhi_info['charge_ratio'] ?: '100'; //1人民币等于多少积分
        $info['charge_description'] = $chongzhi_info['description'] ?: '充值描述'; //充值描述
        $field = $this->data['field']; //关键字  不传为全部
        if ($field) {
            $field = explode(',', $field);
            foreach ($info as $key => $value) {
                if (!in_array($key, $field)) {
                    unset($info[$key]);
                }
            }
        }

        return Ts\Service\ApiMessage::withArray($info, 1, '');
    }

    //生成提现订单号
    private function getOrderId()
    {
        //暂用这种简单的订单号生成办法。。。。请求密集时可能出现订单号重复？
        $number = date('YmdHis').rand(1000, 9999);

        return Ts\Service\ApiMessage::withArray($number, 1, '');
    }

    /**
     * 发布提现申请.
     */
    public function createOrder()
    {
        $data['order_number'] = $this->getOrderId();
        $data['uid'] = $this->mid;

        $accountinfo = $this->getUserAccount();
        if ($accountinfo['status'] == 1) {
            return Ts\Service\ApiMessage::withArray('', 0, '请先绑定提现账户');
            // return $this->rd('', '请先绑定提现账户', 1);
        }
        $data['account'] = $accountinfo['data']['account'];
        $data['type'] = intval($accountinfo['data']['type']); //绑定获取

        $data['gold'] = intval($this->data['gold']);
        $data['amount'] = $this->data['amount'];
        $data['ctime'] = time();
        // if (!$data['account']) {

        //     return $this->rd('','请填写提现账户',1);
        // }
        if (!$data['gold']) {
            return Ts\Service\ApiMessage::withArray('', 0, '请填写提现金额');
            // return $this->rd('', '请填写提现金额', 1);
        }
        $score = D('credit_user')->where(array('uid' => $this->mid))->getField('score');
        if ($score < $data['gold']) {
            return Ts\Service\ApiMessage::withArray('', 0, '积分不足');
            // return $this->rd('', '积分不足', 1);
        }
        $info = Model\CreditOrder::insert($data);
        if ($info) {
            $record['cid'] = 0; //没有对应的积分规则
            $record['type'] = 4; //4-提现
            $record['uid'] = $this->mid;
            $record['action'] = '用户提现';
            $record['des'] = '';
            $record['change'] = '积分<font color="green">-'.$data['gold'].'</font>'; //提现申请扣积分   如果驳回再加回来
            $record['ctime'] = time();
            $record['detail'] = json_encode(array('score' => '-'.$data['gold']));
            D('credit_record')->add($record);
            D('credit_user')->setDec('score', 'uid='.$this->mid, $data['gold']);
            D('Credit')->cleanCache($this->mid);

            return Ts\Service\ApiMessage::withArray('', 1, '提交成功请等待审核');
            // return $this->rd('', '提交成功请等待审核', 0);
        } else {
            return Ts\Service\ApiMessage::withArray('', 0, '保存失败，请稍后再试');
            // return $this->rd('', '保存失败，请稍后再试', 1);
        }
    }

    /**
     * 绑定/解绑账户
     * bs.
     */
    public function setUserAccount()
    {
        $status = intval($this->data['status']) ?: 1; //type 1-绑定 2-解绑
        if ($status == 1) {
            $data['account'] = $this->data['account'];
            if (!$data['account']) {
                return Ts\Service\ApiMessage::withArray('', 0, '请输入需要绑定的账户');
                // return $this->rd('', '请输入需要绑定的账户', 1);
            }
            $data['type'] = intval($this->data['type']) ?: 1; //1-支付宝 2-微信
            if (Model\UserAccount::find($this->mid)) {
                return Ts\Service\ApiMessage::withArray('', 0, '已有绑定账户');
                // return $this->rd('', '已有绑定账户', 1);
            }
            $data['uid'] = $this->mid;
            $data['ctime'] = time();
            $info = Model\UserAccount::insert($data);
            if ($info) {
                return Ts\Service\ApiMessage::withArray('', 1, '绑定成功');
                // return $this->rd('', '绑定成功', 0);
            } else {
                return Ts\Service\ApiMessage::withArray('', 0, '绑定失败，请稍后再试');
                // return $this->rd('', '绑定失败，请稍后再试', 1);
            }
        } else {
            if (!Model\UserAccount::find($this->mid)) {
                return Ts\Service\ApiMessage::withArray('', 0, '未绑定账户');
                // return $this->rd('', '未绑定账户', 1);
            }
            $info = Model\UserAccount::where('uid', $this->mid)->delete();
            if ($info) {
                return Ts\Service\ApiMessage::withArray('', 1, '解绑成功');
                // return $this->rd('', '解绑成功', 0);
            } else {
                return Ts\Service\ApiMessage::withArray('', 0, '操作失败，请稍后再试');
                // return $this->rd('', '操作失败，请稍后再试', 1);
            }
        }
    }

    /**
     * 查看提现账户.
     */
    public function getUserAccount()
    {
        $info = Model\UserAccount::find($this->mid);
        if (!$info) {
            return Ts\Service\ApiMessage::withArray('', 0, '未绑定账户');
            // return $this->rd('', '未绑定账户', 1);
        } else {
            $data['account'] = $info->account;
            $data['type'] = $info->type;

            return Ts\Service\ApiMessage::withArray($data, 1, '');
            // return $this->rd($data);
        }
    }

    public function test()
    {
        $order = $this->getOrderId();

        return $order;
    }
}
