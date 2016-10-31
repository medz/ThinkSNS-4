<?php
/**
 * 签到API接口
 * @author
 * @version  TS4.0
 */
require_once 'LiveBaseApi.class.php';
class LiveUserApi extends LiveBaseApi
{
    /**
     * @name 添加/更新一个直播用户
     * @params 依次传入 (string)usid,(int)sex,(string)uname,(boolean)ticket
     * @return array 结果信息
     */

    private $Service_User_Url = '';
    private $mod = '';

    public function __construct()
    {
        parent::__construct();
        $this->Service_User_Url = $this->stream_server.'/Users';
        $this->mod = M('live_user_info');
    }

    public function postUser()
    {

        //检查是否设置直播地址
        if (!$this->checkStreamServiceUrl()) {
            return array(
                    'status' => 0,
                    'msg' => '请先设置直播服务器地址',
                );
        }
        $uid = intval($_REQUEST['uid']);
        //获取直播服务器地址
        $live_service = $this->getStreamServiceUrl();
        //组装数据
        $data = [
            'usid' => $this->usid_prex.$uid, //传递uid增加前缀
            'uname' => getUserName($uid), //用户名
            'sex' => getUserField($uid, 'sex'),  //传递性别
        ];

        if ($this->mod->where(array('usid' => $data['usid']))->count() && !isset($data[ 'ticket'])) {
            return array(
                    'status' => 0,
                    'msg' => '直播用户已经存在',
                );
            die;
        }

        //参数检测
        if (in_array('', $data)) {
            return array(
                    'status' => 0,
                    'msg' => '参数不完整',
                );
            die;
        }

        $result = json_decode(tocurl($this->Service_User_Url, $this->curl_header, $data), true);
        if ($result['code'] == 1) {
            $add_data['uid'] = $uid;
            $add_data['sex'] = $data['sex'];
            $add_data['usid'] = $data['usid'];
            $add_data['ticket'] = $result['data']['ticket'];
            $add_data['uname'] = $data['uname'];
            $add_data['ctime'] = $add_data['mtime'] = time();
            if (!isset($data['ticket'])) {
                if (!$this->mod->add($add_data)) {
                    //写入直播用户数据失败
                    return array(
                            'status' => 0,
                            'msg' => '直播用户注册失败',
                        );
                    die;
                }

                return array(
                        'status' => 1,
                        'msg' => '直播用户注册成功',
                        'data' => $add_data,
                    );
                die;
            } else {
                unset($add_data['ctime']);
                if (!$this->mod->where(array('usid' => $add_data[ 'usid' ]))->save($add_data)) {
                    //写入直播用户数据失败
                    return array(
                            'status' => 0,
                            'msg' => '直播用户更新失败',
                        );
                    die;
                }

                return array(
                        'status' => 1,
                        'msg' => '直播用户更新成功',
                        'data' => $add_data,
                    );
                die;
            }
        }
    }

    /**
     * 获取用户信息
     * @Author   Wayne[qiaobin@zhiyicx.com]
     * @DateTime 2016-10-13T00:27:51+0800
     * @return [type] [description]
     */
    public function getUserData()
    {
        if (!$this->is_ZhiboService()) {
            return array(
                        'status' => 0,
                        '授权错误',
                    );
        }
        $usid = $_REQUEST['usid'];
        $uid = M('live_user_info')->where(array(
                                                                'usid' => $usid,
                                                                ))
                                                    ->getField('uid');
        if (!$uid) {
            return array(
                    'status' => 0,
                    'message' => '用户不存在',
                );
        }
        // 用户不存在
        if (!$credit = M('credit_user')->where(array('uid' => $uid))->find()) {
            $data = array(
                    'gold' => 0,
                    'zan_count' => 0,
                    'zan_remain' => 0,
                    'uname' => getUserName($uid),
                    'sex' => getUserField($uid, 'sex'),
                );
        } else {
            $data = array(
                    'gold' => $credit['score'],
                    'zan_count' => $credit['zan_count'],
                    'zan_remain' => $credit['zan_remain'],
                    'uname' => getUserName($uid),
                    'sex' => getUserField($uid, 'sex'),
                );
        }

        return array(
                'status' => 1,
                'data' => $data,
            );
    }


    /**
     * 同步数据
     * @Author   Wayne[qiaobin@zhiyicx.com]
     * @DateTime 2016-10-13T01:03:34+0800
     * @return [type] [description]
     */
    public function syncData()
    {
        if (!$this->is_ZhiboService()) {
            return array(
                        'status' => 0,
                        '授权错误',
                    );
            exit;
        }
        $usid = $_REQUEST['usid'];
        $data = $_REQUEST['data'];
        if (!$usid || !$data) {
            return array(
                    'status' => 0,
                    'message' => '参数传递错误',
                );
        }
        $uid = M('live_user_info')->where(array('usid' => $usid))->getField('uid');
        $save_data = array(
                                    //'score'         => $data['gold'],
                                    'zan_count' => $data['zan_count'],
                                    'zan_remain' => array('exp', 'zan_remain +'.$data['zan_remain']),
                                    'live_time' => $data['live_time'],
            );
        $credit_mod = M('credit_user');
        $credit_mod->startTrans();
        if (!$credit_mod->where(array('uid' => $uid))->save($save_data)) {
            //保存失败，回滚数据
            $credit_mod->rollback();
        } else {
            //提交事务
            $credit_mod->commit();
        }
        file_put_contents('sfs', $credit_mod->getLastSql());

        return array(
                    'status' => 1,
                    'data' => array('is_sync' => 1),
                );
    }
}
