<?php

defined('SITE_PATH') || exit('Forbidden');
include_once SITE_PATH.'/apps/Event/Common/common.php';

use Api;
use Apps\Event\Common;
use Apps\Event\Model\Cate;
use Apps\Event\Model\Enrollment;
use Apps\Event\Model\Event;
use Apps\Event\Model\Star;

/**
 * 活动API.
 *
 * @author Seven Du <lovevipdsw@vip.qq.com>
 **/
class EventApi extends Api
{
    /**
     * 获取有活动的日期
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getEventDays()
    {
        /* 获取初始化时间戳 */
        list($cid, $area, $time, $wd) = Common::getInput(array('cid', 'area', 'time', 'wd'));

        return Event::getInstance()->getMonthEventDay($cid, $area, $wd, $time);
    }

    /**
     * 提交活动评论.
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function postComment()
    {
        list($eid, $content, $ruid, $tocid) = Common::getInput(array('eid', 'content', 'to_uid', 'to_comment_id'));
        $info = Event::getInstance()->get($eid);
        $info or
        self::error(array(
            'status'  => 0,
            'message' => '活动已经删除',
        ));
        $data = array(
            'app'                => 'Event',
            'table'              => 'event_list',
            'app_uid'            => $info['uid'],
            'content'            => t($content),
            'row_id'             => intval($eid),
            'to_uid'             => intval($ruid),
            'to_comment_id'      => intval($tocid),
            'app_row_table'      => 'event_list',
            'app_row_id'         => intval($eid),
            'app_detail_url'     => U('Event/Info/index', array('id' => $eid)),
            'app_detail_summary' => $info['name'],
        );
        if (model('Comment')->addComment($data, true)) {
            self::success(array(
                'status'  => 1,
                'message' => '回复成功',
            ));
        }
        self::error(array(
            'status'  => 0,
            'message' => model('Comment')->getError(),
        ));
    }

    /**
     * 取消关注活动.
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function unStar()
    {
        $eid = Common::getInput('eid', 'post');
        if (Star::getInstance()->un($eid, $this->mid)) {
            self::success(array(
                'status'  => 1,
                'message' => '取消关注成功',
            ));
        }
        self::error(array(
            'status'  => 0,
            'message' => Star::getInstance()->getError(),
        ));
    }

    /**
     * 关注一个活动.
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function star()
    {
        $eid = Common::getInput('eid', 'post');
        if (Star::getInstance()->add($eid, $this->mid)) {
            self::success(array(
                'status'  => 1,
                'message' => '关注成功',
            ));
        }
        self::error(array(
            'status'  => 0,
            'message' => Star::getInstance()->getError(),
        ));
    }

    /**
     * 我发起的活动.
     *
     * @request int $page 分页
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function myPost()
    {
        return $this->findEvendByType(1);
    }

    /**
     * 我参与的活动.
     *
     * @request int $page 分页
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function myEnrollment()
    {
        return $this->findEvendByType(0);
    }

    /**
     * 我关注的活动.
     *
     * @request int $page 分页
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function myStar()
    {
        return $this->findEvendByType(2);
    }

    /**
     * 更具类型，返回列表数据.
     *
     * @param  int   $type 获取的类型， 0我参与的活动 1我发起的活动， 2我关注的活动
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    protected function findEvendByType($type = 0)
    {
        if (!in_array($type, array(0, 1, 2))) {
            $type = 0;
        }
        /* 取得数据 */
        switch ($type) {
            case 2:
                $list = Star::getInstance()->getEvent($this->mid);
                break;

            case 1:
                $list = Event::getInstance()->getMyEvent($this->mid);
                break;

            case 0:
            default:
                $list = Enrollment::getInstance()->getUserEvent($this->mid);
                break;
        }
        foreach ($list['data'] as $key => $value) {
            $value['area'] = model('Area')->getAreaById($value['area']);
            $value['area'] = $value['area']['title'];
            $value['city'] = model('Area')->getAreaById($value['city']);
            $value['city'] = $value['city']['title'];
            $value['image'] = getImageUrlByAttachId($value['image']);
            $value['cate'] = Cate::getInstance()->getById($value['cid']);
            $value['cate'] = $value['cate']['name'];
            $value['user'] = model('User')->getUserInfo($value['uid']);
            $list['data'][$key] = $value;
        }

        return $list;
    }

    /**
     * 上传图片.
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function uploadImage()
    {
        return $this->uploadFile('image', 'event_image', 'gif', 'jpg', 'png', 'jpeg');
    }

    /**
     * 上传文件.
     *
     * @param string $uploadType 上传文件的类型
     * @param string $attachType 保存文件的类型
     * @param string [$param, $param ...] 限制文件上传的类型
     *
     * @return array
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    protected function uploadFile($uploadType, $attachType)
    {
        $ext = func_get_args();
        array_shift($ext);
        array_shift($ext);

        $option = array(
            'attach_type' => $attachType,
            'app_name'    => 'Event',
        );
        count($ext) and $option['allow_exts'] = implode(',', $ext);

        $info = model('Attach')->upload(array(
            'upload_type' => $uploadType,
        ), $option);

        // # 判断是否有上传
        if (count($info['info']) <= 0) {
            return array(
                'status' => '-1',
                'msg'    => '没有上传的文件',
            );

        // # 判断是否上传成功
        } elseif ($info['status'] == false) {
            return array(
                'status' => '0',
                'msg'    => $info['info'],
            );
        }

        return array(
            'status' => 1,
            'data'   => array_pop($info['info']),
        );
    }

    /**
     * 创建活动.
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function create()
    {
        list($title, $stime, $etime, $area, $city, $address, $place, $image, $mainNumber, $price, $tips, $cate, $audit, $content) = Common::getInput(array('title', 'stime', 'etime', 'area', 'city', 'address', 'place', 'image', 'mainNumber', 'price', 'tips', 'cate', 'audit', 'content'));
        $audit != 1 and
        $audit = 0;
        /* 有大写参数，APP可能穿错，避免错误，还是多写一下 */
        $mainNumber or $mainNumber = Common::getInput('mainnumber');
        if (($id = Event::getInstance()->setName($title) //活动标题
                                ->setStime($stime) // 开始时间
                                ->setEtime($etime) // 结束时间
                                ->setArea($area) // 地区
                                ->setCity($city) // 城市
                                ->setLocation($address) // 详细地址
                                ->setPlace($place)  // 场所
                                ->setImage($image) // 封面图片
                                ->setManNumber($mainNumber)  // 活动人数
                                ->setPrice($price)  // 价格
                                ->setCid($cate) // 分类
                                ->setAudit($audit)  // 是否需要权限审核
                                ->setContent($content)  // 活动详情
                                ->setUid($this->mid) // 发布活动的用户
                                ->setTips($tips) // 费用说明
                                ->add())) {
            self::message(array(
                'status'  => 1,
                'message' => '发布成功',
                'data'    => $id,
            ));
        }
        self::error(array(
            'status'  => 0,
            'message' => Event::getInstance()->getError(),
        ));
    }

    /**
     * 活动报名.
     *
     * @request int $eid 活动id
     * @request string $name 称呼
     * @request int $sex 性别
     * @request int $num 报名数量
     * @request string $phone 联系方式
     * @request string $note 备注
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function enrollment()
    {
        list($eid, $name, $sex, $num, $phone, $note) = Common::getInput(array('eid', 'name', 'sex', 'num', 'phone', 'note'));
        if (Enrollment::getInstance()->add($this->mid, $eid, $name, $sex, $num, $phone, $note, time())) {
            self::success(array(
                'status'  => 1,
                'message' => '报名成功',
            ));
        }
        self::error(array(
            'status'  => 0,
            'message' => Enrollment::getInstance()->getError(),
        ));
    }

    /**
     * 获取活动回复列表.
     *
     * @request int $eid 活动id
     * @request int $page 分页参数
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getReply()
    {
        $eid = Common::getInput('eid');
        $eid = intval($eid);

        return model('Comment')->setAppName('Event')
                               ->setAppTable('event_list')
                               ->getCommentList(array(
                                       'row_id' => array('eq', $eid),
                                   ), 'comment_id DESC');
    }

    /**
     * 获取活动详情.
     *
     * @request int $eid 活动id
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getInfo()
    {
        $id = Common::getInput('eid');
        if (!$id or !($data = Event::getInstance()->get($id)) or $data['del']) {
            self::error(array(
                'status'  => 0,
                'message' => '您访问的活动不存在，或者已经被删除！',
            ));
        }

        /* 地区 */
        $data['area'] = model('Area')->getAreaById($data['area']);
        $data['area'] = $data['area']['title'];
        $data['city'] = model('Area')->getAreaById($data['city']);
        $data['city'] = $data['city']['title'];

        /* 分类 */
        $data['cate'] = Cate::getInstance()->getById($data['cid']);
        $data['cate'] = $data['cate']['name'];

        /* 用户 */
        $data['user'] = model('User')->getUserInfo($data['uid']);

        /* 当前用户报名情况 */
        $data['enrollment'] = Enrollment::getInstance()->hasUser($id, $this->mid);

        /* 是否已经关注了活动 */
        $data['star'] = Star::getInstance()->has($id, $this->mid);

        /* 报名用户 */
        $data['enrollmentUsers'] = Enrollment::getInstance()->getEventUsers($id);

        /* 封面 */
        $data['image'] = getImageUrlByAttachId($data['image']);

        return $data;
    }

    /**
     * 获取活动列表 - 按照最新发布排序.
     *
     * @request int $cid 分类id
     * @request int $area 地区ID
     * @request string $time 时间，格式化时间或者时间戳
     * @request string  $wd 关键词
     * @request int $page 分页，默认是 1
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getList()
    {
        list($cid, $area, $time, $wd) = Common::getInput(array('cid', 'area', 'time', 'wd'));
        $data = Event::getInstance()->getList($cid, $area, $wd, $time);
        foreach ($data['data'] as $key => $value) {
            $value['area'] = model('Area')->getAreaById($value['area']);
            $value['area'] = $value['area']['title'];
            $value['city'] = model('Area')->getAreaById($value['city']);
            $value['city'] = $value['city']['title'];
            $value['image'] = getImageUrlByAttachId($value['image']);
            $value['cate'] = Cate::getInstance()->getById($value['cid']);
            $value['cate'] = $value['cate']['name'];
            $data['data'][$key] = $value;
        }

        return $data;
    }

    /**
     * 获取推荐活动.
     *
     * @request int $num 获取的数量，默认5条
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getTopEvent()
    {
        $num = Common::getInput('num');
        $num or $num = 5;
        $data = Event::getInstance()->getRightEvent($num);
        foreach ($data as $key => $value) {
            $value['area'] = model('Area')->getAreaById($value['area']);
            $value['area'] = $value['area']['title'];
            $value['city'] = model('Area')->getAreaById($value['city']);
            $value['city'] = $value['city']['title'];
            $value['image'] = getImageUrlByAttachId($value['image']);
            $value['cate'] = Cate::getInstance()->getById($value['cid']);
            $value['cate'] = $value['cate']['name'];
            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * 获取地区信息.
     *
     * @request pid 地区父ID 默认是0
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getArea()
    {
        $pid = intval(Common::getInput('pid'));
        $pid <= 0 and
        $pid = 0;

        return model('Area')->getAreaList($pid);
    }

    /**
     * 获取全部不重复，活动已经使用的地区.
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getAreaAll()
    {
        return Event::getInstance()->getArea();
    }

    /**
     * 获取所有分类.
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getCateAll()
    {
        return Cate::getInstance()->getAll();
    }

    /**
     * 初始化API方法.
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function _initialize()
    {
        Common::setHeader('application/json', 'utf-8');
    }
} // END class EventApi extends Api
