<?php

/* # include base class */
import(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
use Ts\Models as Model;

/**
 * APP 客户端设置.
 *
 * @author Medz Seven <lovevipdsw@vip.qq.com>
 **/
class ApplicationAction extends AdministratorAction
{
    /**
     * 轮播列表设置类型.
     *
     * @var string
     **/
    protected $type = array(
        'false'       => '仅展示',
        'url'         => 'URL地址',
        'weiba'       => '微吧',
        'post'        => '帖子',
        'weibo'       => '微博',
        'topic'       => '话题',
        'channel'     => '频道',
        'user'        => '用户',
        'information' => '资讯',
    );

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 轮播列表.
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function index()
    {
        $this->pageKeyList = array('title', 'image', 'type', 'data','rank', 'doAction');
        array_push($this->pageTab, array(
            'title'   => '轮播列表',
            'tabHash' => 'index',
            'url'     => U('admin/Application/index'),
        ));
        array_push($this->pageTab, array(
            'title'   => '添加轮播',
            'tabHash' => 'addSlide',
            'url'     => U('admin/Application/addSlide'),
        ));

        $list = D('application_slide')->order('rank asc')->findPage(20);

        foreach ($list['data'] as $key => $value) {
            // # 参数
            $aid = $value['image'];
            $id = $value['id'];

            $list['data'][$key]['type'] = $this->type[$value['type']];

            // # 添加图片
            $value = '<a href="%s" target="_blank"><img src="%s" width="300px" height="140px"></a>';
            $value = sprintf($value, getImageUrlByAttachId($aid), getImageUrlByAttachId($aid, 300, 140));
            $list['data'][$key]['image'] = $value;
            $mvSlide1 = "admin.mvSlide(".$id.",'up')";
            $mvSlide2 = "admin.mvSlide(".$id.",'down')";
            $list['data'][$key]['rank'] = '<label><a href="javascript:;" class="ico-top" onclick="'.$mvSlide1.'"></a></label><label><a href="javascript:;" class="ico-btm" onclick="'.$mvSlide2.'"></a></label>';
            // # 添加操作按钮
            $value = '[<a href="%s">编辑</a>]&nbsp;-&nbsp;[<a href="%s">删除</a>]';
            $value = sprintf($value, U('admin/Application/addSlide', array('id' => $id, 'tabHash' => 'addSlide')), U('admin/Application/delSlide', array('id' => $id)));
            $list['data'][$key]['doAction'] = $value;
        }

        $this->allSelected = false;

        $this->displayList($list);
    }
    /**
     * 移动发现轮播操作.
     *
     * @param int $id    发现轮播ID - A
     * @param int $baseId 发现轮播ID - B
     *
     * @return bool 是否移动成功
     */
    public function doMvSlide()
    {
        $id = intval($_POST['id']);
        $baseId = intval($_POST['baseId']);
        $map['id'] = array('IN', array($id, $baseId));
        $order = D('application_slide')->where($map)->select();
        if (count($order) < 2) {
            $result['status'] = 0;
            $result['info'] = '操作失败';
        }
        foreach ($order as $v){
            if($v['id'] == $baseId){
                $res = D('application_slide')->where('`id`='.$id)->setField('rank', $v['rank']);
            }
            if($v['id'] == $id){
                $res = D('application_slide')->where('`id`='.$baseId)->setField('rank',$v['rank']);
            }
        }
        if ($res) {
            $result['status'] = 1;
            $result['info'] = '操作成功';
        } else {
            $result['status'] = 0;
            $result['info'] = '操作失败';
        }

        exit(json_encode($result));
    }
    /**
     * 添加|修改 幻灯.
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function addSlide()
    {
        $this->pageKeyList = array('title', 'image', 'type', 'data');
        $this->notEmpty = array('title', 'image', 'type');
        array_push($this->pageTab, array(
            'title'   => '轮播列表',
            'tabHash' => 'index',
            'url'     => U('admin/Application/index'),
        ));
        array_push($this->pageTab, array(
            'title'   => '添加轮播',
            'tabHash' => 'addSlide',
            'url'     => U('admin/Application/addSlide'),
        ));

        $this->opt['type'] = $this->type;

        $this->savePostUrl = U('admin/Application/doSlide', array('id' => intval($_GET['id'])));

        $data = array();

        if (isset($_GET['id']) and intval($_GET['id'])) {
            $data = D('application_slide')->where('`id` = '.intval($_GET['id']))->find();
        }

        $this->displayConfig($data);
    }

    /**
     * 添加|修改幻灯数据.
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function doSlide()
    {
        list($id, $title, $image, $type, $data) = array($_GET['id'], $_POST['title'], $_POST['image'], $_POST['type'], $_POST['data']);
        list($id, $title, $image, $type, $data) = array(intval($id), t($title), intval($image), t($type), $data);

        if (!in_array($type, array('false', 'url', 'weiba', 'post', 'weibo', 'topic', 'channel', 'user', 'information'))) {
            $this->error('跳转类型不正确');
        } elseif (!$title) {
            $this->error('标题不能为空');
        } elseif (!$image) {
            $this->error('必须上传轮播图片');
        } elseif (in_array($type, array('url', 'weiba', 'post', 'weibo', 'topic', 'channel', 'user', 'information') and !$data)) {
            $this->error('您设置的跳转类型必须设置类型参数');
        }

        $data = array(
            'title' => $title,
            'image' => $image,
            'type'  => $type,
            'data'  => $data,
        );

        if ($id and D('application_slide')->where('`id` = '.$id)->field('id')->count()) {
            D('application_slide')->where('`id` = '.$id)->save($data);
            S('api_discover_system', null);
            $this->success('修改成功');
        }
        $data['rank'] = D('application_slide')->count();
        D('application_slide')->data($data)->add() or $this->error('添加失败');

        $this->assign('jumpUrl', U('admin/Application/index'));
        $this->success('添加成功');
    }

    /**
     * 删除幻灯.
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function delSlide()
    {
        $id = intval($_GET['id']);
        D('application_slide')->where('`id` = '.$id)->delete();
        S('api_discover_system', null);
        $this->success('删除成功');
    }

    /*======================== Socket setting start ===========================*/

    /**
     * Socket 服务器设置.
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function socket()
    {
        $this->pageKeyList = array('socketaddres');
        array_push($this->pageTab, array(
            'title' => 'Socket服务器地址设置',
            'hash'  => 'socket',
            'url'   => U('admin/Application/socket'),
        ));
        $this->displayConfig();
    }

    /*======================== Socket setting end   ===========================*/

    /*================= Application about setting start ========================*/

    /**
     * 客户端About页面设置.
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function about()
    {
        $this->pageKeyList = array('about');
        array_push($this->pageTab, array(
            'title' => '关于我们设置',
            'hash'  => 'about',
            'url'   => U('admin/Application/about'),
        ));
        $this->displayConfig();
    }

    /**
     * 客户端用户协议页面设置.
     *
     * @author bs
     **/
    public function agreement()
    {
        $this->pageKeyList = array('agreement');
        array_push($this->pageTab, array(
            'title' => '用户协议设置',
            'hash'  => 'agreement',
            'url'   => U('admin/Application/agreement'),
        ));
        $this->displayConfig();
    }

    /*================= Application about setting end   ========================*/

    /*================ Application feedback setting start ======================*/

    /**
     * APP反馈管理.
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function feedback()
    {
        $this->pageKeyList = array('user', 'content', 'time', 'doaction');
        array_push($this->pageTab, array(
            'title' => 'APP反馈管理',
            'hash'  => 'feedback',
            'url'   => U('admin/Application/feedback'),
        ));
        $this->allSelected = false;

        /* # 每页显示的条数 */
        $number = 20;

        /* # 反馈类型，app反馈为1 */
        $type = 1;

        /* # 是否按照时间正序排列 */
        $asc = false;

        $list = model('Feedback')->findDataToPageByType($type, $number, $asc);

        foreach ($list['data'] as $key => $value) {
            $data = array();
            $data['content'] = formatEmoji(false,$value['content']);
            $data['user'] = getUserName($value['uid']);
            $data['time'] = friendlyDate($value['cTime']);

            $data['doaction'] = '<a href="'.U('admin/Application/deleteFeedback', array('fid' => $value['id'])).'">[删除反馈]</a>';

            $list['data'][$key] = $data;
        }
        unset($data, $key, $value);

        $this->displayList($list);
    }

    /**
     * 删除反馈.
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function deleteFeedback()
    {
        $fid = intval($_REQUEST['fid']);
        model('Feedback')->delete($fid);
        $this->success('删除成功！');
    }

    /*================ Application feedback setting End   ======================*/

    /**
     * 极光推送
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function jpush()
    {
        $this->pageKeyList = array('key', 'secret', 'apns_production');
        array_push($this->pageTab, array(
            'title' => '极光推送设置',
            'hash'  => 'jpush',
            'url'   => U('admin/Application/jpush'),
        ));
        $this->opt['apns_production'] = array('0' => '开发环境', '1' => '生产环境');

        $this->displayConfig();
    }

    //app端直播支付相关配置 bs
    public function ZB_config()
    {
        $this->pageKeyList = array('version', 'android', 'ios', 'cash_exchange_ratio_list');
        $this->pageTab[] = array('title' => '充值配置', 'tabHash' => 'charge', 'url' => U('admin/Config/charge'));
        $this->pageTab[] = array('title' => '直播版充值配置', 'tabHash' => 'ZBcharge', 'url' => U('admin/Config/ZBcharge'));
        array_push($this->pageTab, array(
            'title'   => '提现配置',
            'tabHash' => 'ZB_config',
            'url'     => U('admin/Application/ZB_config'),
        ));
        $this->opt['android'] = $this->opt['ios'] = array(
            'alipay' => '支付宝',
            'weixin' => '微信支付',
        );

        $this->displayConfig();
    }

    //提现管理
    public function ZB_credit_order()
    {
        $this->pageTab[] = array('title' => '提现记录', 'tabHash' => 'ZB_credit_order', 'url' => U('admin/Application/ZB_credit_order'));
        $this->pageButton[] = array('title' => '搜索记录', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => '批量驳回', 'onclick' => 'admin.setReason()');
        $this->pageKeyList = array('order_number', 'type', 'uid', 'uname', 'account', 'gold', 'amount', 'ctime', 'utime', 'status', 'DOACTION');
        $this->searchKey = array('uid', 'order_number', 'account');
        $this->searchPostUrl = U('admin/Application/ZB_credit_order');
        $this->_listpk = 'order_number';
        if ($_POST) {
            $_POST['uid'] && $map['uid'] = $_POST['uid'];
            $_POST['order_number'] && $map['order_number'] = array('like', '%'.$_POST['order_number'].'%');
            $_POST['account'] && $map['account'] = array('like', '%'.$_POST['account'].'%');
        }
        $list = D('credit_order')->where($map)->findPage(20);
        foreach ($list['data'] as $key => &$value) {
            $value['type'] = $value['type'] == 1 ? '支付宝' : ($value['type'] == 2 ? '微信' : '');
            if ($value['status'] == 0) {
                $value['DOACTION'] = '<a href="'.U('admin/Application/pass', array('number' => $value['order_number'])).'">处理</a> ';
                $value['DOACTION'] .= ' <a href="javascript:;" onclick="admin.setReason(\''.$value['order_number'].'\')">驳回</a>';
            }

            switch ($value['status']) {
                case '0':
                    $value['status'] = '<font color="orange">待处理</font>';
                    break;
                case '1':
                    $value['status'] = '<font color="green">已处理</font>';
                    break;
                case '2':
                    $value['status'] = '<font color="red">已驳回</font>';
                    break;
            }
            $value['ctime'] = date('Y-m-d h:i:s', $value['ctime']);
            $value['utime'] = empty($value['utime']) ? '暂无处理' : date('Y-m-d h:i:s', $value['utime']);
            $value['uname'] = getUserName($value['uid']);
        }
        $this->displayList($list);
    }

    public function pass()
    {
        $return = $this->solveOrder($_GET['number'], 1);
        if ($return['status'] == 0) {
            $this->success($return['message']);
        } else {
            $this->error($return['message']);
        }
    }

    public function setReason()
    {
        $numbers = $_GET['number'];
        $this->assign('numbers', $numbers);
        $this->display();
    }

    public function doSetReason()
    {
        $numbers = explode(',', $_POST['number']);
        foreach ($numbers as $key => $value) {
            if (!empty($value)) {
                $this->solveOrder($value, 2, $_POST['reason']);
            }
        }
        exit(json_encode(array('status' => 1, 'info' => '驳回成功')));
    }

    /**
     * 处理提现.
     */
    private function solveOrder($number, $type, $reason = '')
    {
        $map['order_number'] = $number; //多个以逗号隔开 支持批量
        $save['status'] = intval($type) == 1 ? 1 : 2;
        $save['utime'] = time();
        $orderinfo = Model\CreditOrder::where('order_number', $number)->first();
        if ($orderinfo->status == 0) {
            // dumP($orderinfo->uid);die;
            $do = D('credit_order')->where($map)->save($save); //更新处理时间 处理状态

            if ($do) {
                $uinfo = D('User')->where(array('uid' => $orderinfo->uid))->find();
                if ($type == 1) {
                    $messagecontent = '您的提现申请已被处理，请注意查收';
                    if (!empty($uinfo['phone'])) {
                        D('Sms')->sendMessage($uinfo['phone'], $messagecontent);
                    }
                } else {
                    $messagecontent = '您的提现申请已被驳回，理由是'.$reason;
                    if (!empty($uinfo['phone'])) {
                        D('Sms')->sendMessage($uinfo['phone'], $messagecontent);
                    }

                    $record['cid'] = 0; //没有对应的积分规则
                    $record['type'] = 4; //4-提现
                    $record['uid'] = $orderinfo->uid;
                    $record['action'] = '提现驳回';
                    $record['des'] = '';
                    $record['change'] = '积分<font color="red">+'.$orderinfo->gold.'</font>'; //驳回积分加回来
                    $record['ctime'] = time();
                    $record['detail'] = json_encode(array('score' => '+'.$orderinfo->gold));
                    $record['reason'] = $reason;
                    D('credit_record')->add($record);
                    D('credit_user')->setInc('score', 'uid='.$orderinfo->uid, $orderinfo->gold);
                }

                return array('message' => '操作成功', 'status' => 0);
            } else {
                return array('message' => '操作失败', 'status' => 1);
            }
        }
    }

    /**
     * APP端微博来源设置
     *
     * @author zsy
     */
    public function configureFrom()
    {
        $this->pageTab[] = array('title' => 'APP端配置微博来源', 'tabHash' => 'configureFrom', 'url' => U('admin/Application/configureFrom'));
        $this->pageKeyList = array('phone', 'mobile', 'iPhone', 'iPad', 'windows', 'h5', 'web');

        $this->displayConfig();
    }

    /* *********************APP版本start************************* */
    /**
     * 更新安卓安装包页面
     */
    public function versionByAndroid()
    {
        array_push($this->pageTab, array(
            'title' => 'Android版本设置',
            'tabHash' => 'versionByAndroid',
            'url' => U('admin/Application/versionByAndroid'),
        ));
        array_push($this->pageTab, array(
            'title' => 'Ios版本设置',
            'tabHash' => 'versionByIos',
            'url' => U('admin/Application/versionByIos'),
        ));
        //$this->pageButton('Android');
        $version = $_REQUEST['version']?:'';//app版本
        $versionInfo = model('Xdata')->get('admin_Application:versionByAndroid'.$version);
        $apkUrl = SITE_URL.'/public/'.$version.'/'.($versionInfo['apk']?:'ts-Android'.$version.'.apk');
        $this->assign($versionInfo);
        $this->assign('apkUrl', $apkUrl);
        $this->display();
    }

    /**
     * 执行上传安装包
     */
    public function saveAndroidVersion()
    {
        if ($_POST) {
            $appVersion = $_REQUEST['appVersion']?:'';
            $versionInfo = 'admin_Application:versionByAndroid'.$appVersion;
            $Info = model('Xdata')->get($versionInfo);
            $tmp_file_path = SITE_PATH.'/public/'.$appVersion.'/';//在根目录下增加tmp目录的路径
            $fileurl = $tmp_file_path.($Info['apk']?:'ts-Android.apk');
            if(!file_exists($tmp_file_path)){
                mkdir($tmp_file_path, 0777);//如果不存在tmp目录，则建立
            }
            if ($_FILES['apk']['name']) {
                if ($_FILES['apk']['error'] > 0) {
                    $this->error('文件上传出错');
                }
                $type = substr($_FILES['apk']['name'], strpos($_FILES['apk']['name'], '.') + 1);
                if ($type != 'apk') {
                    $this->error('文件类型错误');
                }
                unlink($tmp_file_path.$_FILES['apk']['name']);
                unlink($fileurl);
                move_uploaded_file($_FILES['apk']['tmp_name'], $tmp_file_path.$_FILES['apk']['name']);
            }
            if ($_POST['version']) {
                $data['version'] = $_POST['version'];
                $data['is_update'] = $_POST['is_update'];
                if(!$_FILES['apk']['name']){
                    $versionInfo = model('Xdata')->get('admin_Application:versionByAndroid');
                    $apk = $versionInfo['apk'];
                }else{
                    $apk = $_FILES['apk']['name'];
                }
                $data['apk'] = $apk;
                $data['title'] = $_POST['title'];
                $data['explain'] = $_POST['explain'];
                model('Xdata')->put($versionInfo, $data);
            }
        }

        $this->success('保存成功');
    }

    /**
     * 更新Ios版本
     */
    public function versionByIos()
    {
        $_REQUEST['tabHash'] = 'versionByIos';
        if ($_POST) {
            $appVersion = $_REQUEST['appVersion']?:'';
            $versionInfo = 'admin_Application:versionByIos'.$appVersion;
            $data['version'] = $_POST['version'];
            $data['appstore_link'] = $_POST['appstore_link'];
            $data['is_update'] = $_POST['is_update'];
            $data['title'] = $_POST['title'];
            $data['explain'] = $_POST['explain'];
            model('Xdata')->put($versionInfo, $data);
            $this->success('保存成功');
        }else{
            array_push($this->pageTab, array(
                'title' => 'Android版本设置',
                'tabHash' => 'versionByAndroid',
                'url' => U('admin/Application/versionByAndroid'),
            ));
            array_push($this->pageTab, array(
                'title' => 'Ios版本设置',
                'tabHash' => 'versionByIos',
                'url' => U('admin/Application/versionByIos'),
            ));
            //$this->pageButton('Ios');
            $version = $_REQUEST['version'];//app版本
            $versionInfo = model('Xdata')->get('admin_Application:versionByIos'.$version);
            $this->assign($versionInfo);
            $this->display();
        }
    }
    //app版本
    public function pageButton($version = 'Android'){
        $pageButton[] = array('title' =>'系统版', 'onclick' => "location.href = '".U('admin/Application/versionBy'.$version)."'");
        $pageButton[] = array('title' =>'聊天版', 'onclick' => "location.href = '".U('admin/Application/versionBy'.$version,['version'=>'Chat'])."'");
        $pageButton[] = array('title' =>'图片版', 'onclick' => "location.href = '".U('admin/Application/versionBy'.$version,['version'=>'Image'])."'");
        $pageButton[] = array('title' =>'圈子社交版', 'onclick' => "location.href = '".U('admin/Application/versionBy'.$version,['version'=>'Circle'])."'");
        $pageButton[] = array('title' =>'视频版', 'onclick' => "location.href = '".U('admin/Application/versionBy'.$version,['version'=>'Video'])."'");
        $pageButton[] = array('title' =>'媒体版', 'onclick' => "location.href = '".U('admin/Application/versionBy'.$version,['version'=>'Media'])."'");
        $pageButton[] = array('title' =>'人脉版', 'onclick' => "location.href = '".U('admin/Application/versionBy'.$version,['version'=>'Contacts'])."'");
        $pageButton[] = array('title' =>'直播版', 'onclick' => "location.href = '".U('admin/Application/versionBy'.$version,['version'=>'Live'])."'");
        $this->assign('pageButton', $pageButton);
    }
    /* *********************APP版本end************************* */

    /**
     * APP配置
     */
    public function postContentAuthen(){
        $this->pageKeyList = array('postContentAuthenByAndroid', 'postContentAuthenByIos','weiboContent','weibaTitle','weibaContent','weiboComment','weibaComment','eventComment','informationComment');
        array_push($this->pageTab, array(
            'title' => 'APP配置',
            'tabHash' => 'postContentAuthen',
            'url' => U('admin/Application/postContentAuthen'),
        ));
        $this->opt['postContentAuthenByAndroid'] = array('0' => '否', '1' => '是');
        $this->opt['postContentAuthenByIos'] = array('0' => '否', '1' => '是');
        $this->displayConfig();
    }
} // END class ApplicationAction extends AdministratorAction
