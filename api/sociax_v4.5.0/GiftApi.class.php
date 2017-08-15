<?php

namespace Api;

defined('SITE_PATH') || exit('Forbidden');
include_once SITE_PATH.'/apps/Gift/Common/common.php';

use Api;
use Apps\Gift\Common;
use Apps\Gift\Model\Gift    as GiftModel;
use Apps\Gift\Model\GiftLog as LogModel;

/**
 * 礼物接口.
 *
 * @author Seven Du <lovevipdsw@vip.qq.com>
 **/
class Gift extends Api
{
    /**
     * 初始化API方法.
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function _initialize()
    {
        Common::setHeader('application/json', 'utf-8');
    }

    /**
     * 列表获取礼物.
     *
     * @request int p 页码，默认值是1页
     * @request int cate 分类，值只有1和2，1代表虚拟礼物，2代表实体礼物，不传代表�
     * �部
     * @request int num 每页返回的数据条数 默认20条
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getList()
    {
        list($cate, $num) = Common::getInput(array('cate', 'num'));
        list($cate, $num) = array(intval($cate), intval($num));

        $where = '`cate` IN (1, 2)';
        if ($cate >= 1 && $cate <= 2) {
            $where = '`cate` = '.$cate;
        }
        $where .= ' AND `isDel` != 1';

        /* # 设置每页返回的数据条数 */
        $num || $num = 20;

        $data = GiftModel::getInstance()->where($where)->order('`id` DESC')->findPage($num);

        /* # 判断页数是否超出 */
        if (Common::getInput('page') > $data['totalPages']) {
            $data['data'] = array();
        }

        foreach ($data['data'] as $key => $value) {
            $value['image'] = getImageUrlByAttachId($value['image']);
            $value['count'] = LogModel::getInstance()->getUserCount($value['id']);
            $data['data'][$key] = $value;
        }

        return $data;
    }

    /**
     * 获取礼物详细.
     *
     * @request int $id 礼物ID
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getInfo()
    {
        $id = intval(Common::getInput('id'));
        $data = GiftModel::getInstance()->getById($id);
        $data['image'] && $data['image'] = getImageUrlByAttachId($data['image']);
        $data['count'] = LogModel::getInstance()->getUserCount($id);

        $data['info'] = preg_replace('/\<img(.*?)src\=\"\/(.*?)\"(.*?)(\/?)\>/is', '<img\\1src="SITE_URL/\\2"\\3\\4>', $data['info']);
        $data['info'] = str_replace('SITE_URL', parse_url(SITE_URL, PHP_URL_SCHEME).'://'.parse_url(SITE_URL, PHP_URL_HOST).'/', $data['info']);

        /* # 剔除width和height和align，防止宽高溢出 */
        $data['info'] = preg_replace('/(width|height|align)\=\"(.*?)\"/is', '', $data['info']);

        return $data;
    }

    /**
     * �
     * �换礼物.
     *
     * @reuqest int id 礼物ID
     * @reuqest int uid 赠送的人的UID
     * @reuqest int num �
     * �换的数量
     * @reuqest string addres 邮寄地址
     * @request string say 祝福语
     * @request int type 类型
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function buy()
    {
        list($id, $uid, $num, $addres, $say, $type) = Common::getInput(array('id', 'uid', 'num', 'addres', 'say', 'type'));
        list($name, $phone) = Common::getInput(array('name', 'phone'));

        /* # 参数过滤处理 */
        $id = intval($id);
        $uid = intval($uid);
        $num = intval($num);
        $type = intval($type);
        $addres = t($addres);
        $say = t($say);
        $name = t($name);
        $phone = t($phone);

        /* # 获取当前用户积分 */
        $score = model('Credit')->getUserCredit($this->mid);
        $score = $score['credit']['score']['value'];

        /* # 判断是否登陆 */
        if (!$this->mid) {
            return array(
                'status' => 0,
                'mesage' => '请先登录后再兑换',
            );

            /* # 判断物品是否不存在 */
        } elseif (!$id || !($gift = GiftModel::getInstance()->getById($id))) {
            return array(
                'status' => -1,
                'mesage' => '兑换的该物品不存在',
            );

            /* # 判断赠送的用户是否不存在 */
        } elseif (!$uid || !model('User')->hasUser($uid, true)) {
            return array(
                'status' => -2,
                'mesage' => '对不起，您赠送的用户不存在',
            );

            /* # 判断是否赠送过了 */
        } elseif (LogModel::getInstance()->hasGive($id, $uid, $this->mid)) {
            return array(
                'status' => -3,
                'mesage' => '您已经赠送过给该用户，请勿重复赠送',
            );

            /* # 判断积分是否充足 */
        } elseif ($gift['score'] > $score) {
            return array(
                'status' => -4,
                'mesage' => '您的积分余额不足，请先充值积分，或者做任务获得积分。',
            );

            /* # 判断数量是否少于1 */
        } elseif ($num < 1) {
            return array(
                'status' => -5,
                'mesage' => '赠送数量不得少于1份',
            );

            /* # 判断是否超出库存 */
        } elseif ($gift['stock'] < $num) {
            return array(
                    'status' => -7,
                    'mesage' => '数量超出库存数量：'.$gift['stock'],
                );

            /* # 判断是否超出限购 */
        } elseif ($gift['max'] < $num && $gift['max']) {
            return array(
                    'status' => -6,
                    'mesage' => '数量超出限购数量：'.$gift['max'],
                );

            /* # 判断是否缺少祝福语 */
        } elseif (!$say) {
            return array(
                'status' => -8,
                'mesage' => '请输入祝福语',
            );

            /* # 判断真实姓名是否为空 */
        } elseif (!$name && $gift['cate'] == 2) {
            return array(
                'status' => -12,
                'mesage' => '用户真实姓名不能为空',
            );

            /* # 判断是否输入了联系方式 */
        } elseif (!$phone && $gift['cate'] == 2) {
            return array(
                'status' => -13,
                'mesage' => '用户联系方式不能为空',
            );

            /* # 判断是否输入了地址 */
        } elseif (!$addres && $gift['cate'] == 2) {
            return array(
                'status' => -9,
                'mesage' => '请输入正确的收货地址',
            );

            /* # 判断是否是不允许的赠送类型 */
        } elseif (!in_array($type, array(1, 2, 3))) {
            return array(
                'status' => -10,
                'mesage' => '不允许的赠送类型，请在页面上正确的选择赠送类型',
            );

            /* # 判断是否兑换失败 */
        } elseif (!LogModel::getInstance()->add($uid, $this->mid, $id, $type, $say, $num, $addres)) {
            return array(
                'status' => -11,
                'mesage' => LogModel::getInstance()->getError(),
            );
        }

        /* # 发送系统消息 */
        if ($gift['cate'] == 1) {
            $message = '礼物提示：'.getUserName($this->mid).'送给了您'.$num.'份“'.$gift['name'].'”,快<a href="'.U('Gift/Index/my', array('type' => '1')).'">去看看</a>！';
            if ($type == 1) {
                $message = '礼物提示：您收到了'.$num.'份你们赠送的礼物“'.$gift['name'].'”,快<a href="'.U('Gift/Index/my', array('type' => '1')).'">去看看</a>！';
            }
        } else {
            $message = '礼物提示：'.getUserName($this->mid).'送给了您'.$num.'份“'.$gift['name'].'”,请等待快递发货通知！';
            if ($type == 1) {
                $message = '礼物提示：您收到了'.$num.'份你们赠送的礼物“'.$gift['name'].'”,请等待快递发货通知！';
            }
        }
        LogModel::getInstance()->sendMessage($uid, $message);

        return array(
            'status' => 1,
            'mesage' => '恭喜您，成功的为您的好友送出了礼物！您可以去充值或者完成任务获得更多积分哦！',
        );
    }

    /**
     * 获取用户获得/赠送的礼物.
     *
     * @param int type 0：获得的礼物 1：赠送的礼物
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getLog()
    {
        $type = Common::getInput('type');
        if ($type) {
            $data = LogModel::getInstance()->getOutLog($this->mid, 0, 20);
        } else {
            $data = LogModel::getInstance()->getInLog($this->mid, 0, 20);
        }

        /* # 判断页数是否超出 */
        if (Common::getInput('page') > $data['totalPages']) {
            $data['data'] = array();
        }

        return $data;
    }

    /**
     * 转增虚拟礼物.
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function transfer()
    {
        list($id, $uid, $say) = Common::getInput(array('id', 'uid', 'say'));
        $data = LogModel::getInstance()->getById($id);
        $data = array_merge($data, GiftModel::getInstance()->getById($data['gid']));
        $uid = intval($uid);
        $say = t($say);
        list($num, $type) = Common::getInput(array('num', 'type'));
        list($num, $type) = array(intval($num), intval($type));

        /* # 判断是否存在礼物 */
        if (!$data) {
            return array('status' => 0, 'message' => '转增的礼物不存在');

            /* # 判断赠送的用户是否不存在 */
        } elseif (!$uid || !model('User')->hasUser($uid, true)) {
            return array('status' => 0, 'message' => '对不起，您赠送的用户不存在');

            /* # 判断祝福语是否为空 */
        } elseif (!$say) {
            return array('status' => 0, 'message' => '请输入祝福语');

            /* # 判断是否不是虚拟礼物 */
        } elseif ($data['cate'] != 1) {
            return array('status' => 0, 'message' => '您转账的礼物不是虚拟物品！');

            /* # 判断礼物是否属于自己 */
        } elseif ($data['inUid'] != $this->mid || $data['notIn'] == 1) {
            return array('status' => 0, 'message' => '该礼物不属于您！');

            /* # 判断该礼物是否已经赠送过了 */
        /*} elseif (LogModel::getInstance()->hasGive($data['gid'], $uid, $this->mid)) {*/
            /*return array('status' => 0, 'message' => '您已经赠送过给该用户，请勿重复赠送');*/

        /* # 判断转赠的数量是否不合法 */
        } elseif ($num <= 0 || $num > $data['num']) {
            return array('status' => 0, 'message' => '您转赠的数量不合法，必须大于0且小于或等于您收到的数量！');

            /* # 判断转赠类似是否不合法 */
        } elseif (!in_array($type, array(1, 2, 3))) {
            return array('status' => 0, 'message' => '不允许的赠送类型，请在页面上正确的选择赠送类型');

            /* # 检查是否转增失败 */
        } elseif (!LogModel::getInstance()->transfer($id, $uid, $say, $num, $type)) {
            return array('status' => 0, 'message' => '转增失败！');
        }

        /* # 发送提示 */
        $message = '礼物提示：'.getUserName($this->mid).'送给了您'.$data['num'].'份“'.$data['name'].'”,快<a href="'.U('Gift/Index/my').'">去看看</a>！';
        LogModel::getInstance()->sendMessage($uid, $message);

        /* # 转增成功 */
        return array('status' => 1, 'message' => '礼物转赠成功！');
    }
} // END class Gift extends Api
class_alias('Api\Gift', 'GiftApi');
