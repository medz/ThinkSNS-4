<?php

tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
/**
 * 移动端设置.
 *
 * @author Medz Seven <lovevipdsw@vip.qq.com>
 **/
class MobileAction extends AdministratorAction
{
    /**
     * 前置方法.
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function _initialize()
    {
        $this->assign('isAdmin', 1);
        parent::_initialize();
    }

    /**
     * 3G办广场轮播列表.
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function w3gSlideShow()
    {
        // # 设置页面字段
        $this->pageKeyList = array('image', 'url', 'doaction');

        // # 添加tab
        array_push($this->pageTab, array(
            'title'   => '轮播列表',
            'tabHash' => 'w3gSlideShow',
            'url'     => U('admin/Mobile/w3gSlideShow'),
        ));
        array_push($this->pageTab, array(
            'title'   => '添加轮播',
            'tabHash' => 'addW3gSlideShow',
            'url'     => U('admin/Mobile/addW3gSlideShow'),
        ));

        // # 分页获取数据，20条
        $list = D('w3g_slide_show')->findPage(20);

        // # 加入操作按钮
        foreach ($list['data'] as $key => $value) {
            // # 参数
            $aid = $value['image'];
            $id = $value['id'];

            // # 添加图片
            $value = '<a href="%s" target="_blank"><img src="%s" width="300px" height="140px"></a>';
            $value = sprintf($value, getImageUrlByAttachId($aid), getImageUrlByAttachId($aid, 300, 140));
            $list['data'][$key]['image'] = $value;

            // # 添加操作按钮
            $value = '[<a href="%s">编辑</a>]&nbsp;-&nbsp;[<a href="%s">删除</a>]';
            $value = sprintf($value, U('admin/Mobile/addW3gSlideShow', array('id' => $id, 'tabHash' => 'addW3gSlideShow')), U('admin/Mobile/delW3gSlideShwo', array('id' => $id)));
            $list['data'][$key]['doaction'] = $value;
        }

        // # 设置无选择按钮
        $this->allSelected = false;

        // # 显示列表，并注销list变量
        $this->displayList($list);
        unset($list);
    }

    /**
     * [添加|编辑]3G版广场轮播.
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function addW3gSlideShow()
    {
        // # 添加tab
        array_push($this->pageTab, array(
            'title'   => '轮播列表',
            'tabHash' => 'w3gSlideShow',
            'url'     => U('admin/Mobile/w3gSlideShow'),
        ));
        array_push($this->pageTab, array(
            'title'   => (isset($_GET['id']) ? '编辑' : '添加').'轮播',
            'tabHash' => 'addW3gSlideShow',
            'url'     => U('admin/Mobile/addW3gSlideShow'),
        ));

        // # 设置页面参数
        $this->pageKeyList = array('image', 'url');

        // # 页面参数
        $data = array();

        // # 设置按钮名称
        $this->submitAlias = '添加轮播';

        // # 如果是编辑
        if (isset($_GET['id']) and intval($_GET['id'])) {
            // # 获取数据
            $data = D('w3g_slide_show')->where('`id` = '.intval($_GET['id']))->find();

            // # 设置按钮名称
            $this->submitAlias = '保存编辑';
        }

        // # 设置提交地址
        $this->savePostUrl = U('admin/Mobile/doAddW3gSlideShow', array('id' => $_GET['id']));

        // # 输出页面
        $this->displayConfig($data);
    }

    /**
     * [添加|编辑]轮播.
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function doAddW3gSlideShow()
    {
        // # 获取参数
        list($id, $image, $url) = array($_GET['id'], $_POST['image'], $_POST['url']);

        // # 安全过滤
        list($id, $image, $url) = array(intval($id), intval($image), t($url));

        // # 组装数据
        $data = array(
            'image' => $image,
            'url'   => $url,
        );

        // # 判断更新
        if ($id and D('w3g_slide_show')->where('`id` = '.$id)->field('id')->count()) {
            D('w3g_slide_show')->where('`id` = '.$id)->save($data);
            $this->success('编辑成功！');
        }

        // # 添加，失败则输出错误
        D('w3g_slide_show')->add($data) or $this->error('添加失败');

        // # 设置成功跳转地址
        $this->assign('jumpUrl', U('admin/Mobile/w3gSlideShow'));

        // # 提示成功！
        $this->success('添加成功！');
    }

    /**
     * 删除轮播.
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function delW3gSlideShwo()
    {
        $id = intval($_GET['id']);
        D('w3g_slide_show')->where('`id` = '.$id)->delete();
        $this->success('删除成功！');
    }

    /**
     * 手机版logo设置.
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function w3gLogo()
    {
        // # 添加菜单
        array_push($this->pageTab, array(
            'title'   => '开关设置',
            'tabHash' => 'setting',
            'url'     => U('admin/Mobile/setting'),
        ));
        array_push($this->pageTab, array(
            'title'   => 'Logo设置',
            'tabHash' => 'w3gLogo',
            'url'     => U('admin/Mobile/w3gLogo'),
        ));

        $this->pageKeyList = array('logo');

        $this->displayConfig();
    }

    /**
     * 手机版 �
     * �于我们.
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function w3gAbout()
    {
        $this->pageKeyList = array('about');

        array_push($this->pageTab, array(
            'title'   => '关于我们',
            'tabHash' => 'w3gAbout',
            'url'     => U('admin/Mobile/w3gAbout'),
        ));

        $this->displayConfig();
    }

    /**
     * 3G版本开�
     * �设置.
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function setting()
    {
        // # 添加菜单
        array_push($this->pageTab, array(
            'title'   => '开关设置',
            'tabHash' => 'setting',
            'url'     => U('admin/Mobile/setting'),
        ));
        array_push($this->pageTab, array(
            'title'   => 'Logo设置',
            'tabHash' => 'w3gLogo',
            'url'     => U('admin/Mobile/w3gLogo'),
        ));

        $this->pageKeyList = array('switch');

        $this->opt = array_merge($this->opt, array(
            'switch' => array(
                '1' => '开启',
                '0' => '关闭',
            ),
        ));

        $this->displayConfig();
    }
} // END class MobileAction entends AdmininistratorAction
