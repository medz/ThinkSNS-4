<?php

namespace Apps\Information\Controller;

defined('SITE_PATH') || exit('Forbidden');

use Apps\Information\Common;
use AdministratorAction            as Controller;
use Apps\Information\Model\Cate    as CateModel;
use Apps\Information\Model\Subject as SubjectModel;
use Apps\Information\Model\Top     as TopModel;

Common::import(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');

/**
 * 资讯后台管理控制器
 *
 * @package Apps\Information\Controller\Admin
 * @author Seven Du <lovevipdsw@vip.qq.com>
 **/
class Admin extends Controller
{
    /*=================================非Action区域========================================*/
    /**
     * 初始化操作
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function _initialize()
    {
        parent::_initialize();
        Common::setHeader('text/html', 'utf-8');

        $this->pageTitle['index'] = '分类列表';
        $this->pageTitle['subjectList'] = '文章列表';
        $this->pageTitle['config'] = '常规配置';
    }

    /**
     * 添加tab菜单
     *
     * @param string $hash  方法的名称
     * @param string $url   点击跳转地址，非绝对地址，而是U函数需要构造的内容
     * @param string $name  描述名称
     * @param array  $param url拓展参数
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    private function _addTab($hash, $url, $name = '', array $param = array())
    {
        if (!$name) {
            $name = $this->pageTitle[$hash];
        }
        array_push($this->pageTab, array(
            'title' => $name,
            'tabHash' => $hash,
            'url' => U($url, $param),
        ));
    }

    /**
     * 公用TAB切换菜单
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    private function _commonTab()
    {
        /* index */
        $this->_addTab('index', 'Information/Admin/index');
        /* 主体列表 */
        $this->_addTab('subjectList', 'Information/Admin/subjectList');
        /* 常规配置 */
        $this->_addTab('config', 'Information/Admin/config');
    }

/*=================================Action区域===========================================*/

    /**
     * 常规配置
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function config()
    {
        $this->_commonTab();
        $this->pageKeyList = array('hotTime', 'commentHotTime', 'guide');
        $this->displayConfig();
    }

    /**
     * 资讯分类列表
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function index()
    {
        $this->_commonTab();
        $this->pageKeyList = array('id', 'name', 'rank', 'action');
        $this->searchKey = array('id', 'name');

        /* # 排序保存按钮 */
        array_push($this->pageButton, array(
            'title' => '保存',
            'id' => 'information-submit',
            'data' => array(
                'uri' => U('Information/Admin/saveCateRank'),
            ),
        ));
        /* 搜索按钮 */
        array_push($this->pageButton, array(
            'title' => '搜索',
            'id' => 'information-search',
        ));
        /* 删除 */
        array_push($this->pageButton, array(
            'title' => '删除',
            'id' => 'information-delete',
            'data' => array(
                'uri' => U('Information/Admin/deleteCate', array('ids' => '__IDS__')),
            ),
        ));
        array_push($this->pageButton, array(
            'title' => '添加',
            'id' => 'information-add',
            'data' => array(
                'url' => U('Information/Admin/cateAdd', array('tabHash' => 'index')),
            ),
        ));

        list($id, $name) = Common::getInput(array('id', 'name'), 'get');

        $data = CateModel::getInstance()->getAdmin4Rank($id, $name);
        // var_dump($data, CateModel::getInstance());exit;
        foreach ($data['data'] as $key => $value) {
            unset($value['isDel']);
            $value['rank'] = '<input id="information-ranks" type="number" value="'.$value['rank'].'" data-id="'.$value['id'].'" min="0" step="1">';
            $value['action'] = '<a href="'.U('Information/Admin/cateAdd', array('id' => $value['id'])).'">[修改名称]</a>&nbsp-&nbsp;<a href="'.U('Information/Admin/deleteCate', array('ids' => $value['id'])).'">[删除分类]</a>';

            $data['data'][$key] = $value;
        }

        /* 添加js */
        array_push($this->appJsList, 'js/admin.cate.js');

        $this->displayList($data);
    }

    /**
     * 添加分类
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function cateAdd()
    {
        $id = Common::getInput('id', 'get');
        $id = intval($id);
        $this->_commonTab();
        $this->notEmpty = $this->pageKeyList = array('name');
        $this->submitAlias = '添加';
        $id && $this->submitAlias = '修改';
        $this->savePostUrl = U('Information/Admin/saveCate', array('id' => $id));
        $this->displayConfig(CateModel::getInstance()->setId($id)->getInfoById());
    }

    /**
     * 保存分类数据
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function saveCate()
    {
        list($id, $name) = Common::getInput(array('id', 'name'));
        CateModel::getInstance()->setId($id)->setName($name);

        /* # 如果存在，则修改分类名称 */
        if (CateModel::getInstance()->hasById() && !($status = CateModel::getInstance()->changeName())) {
            $this->error(CateModel::getInstance()->getError());

        /* # 添加分类，是否失败 */
        } elseif (!$status && !CateModel::getInstance()->add()) {
            $this->error(CateModel::getInstance()->getError());
        }

        $this->assign('jumpUrl', U('Information/Admin/index'));
        $this->success('操作成功！');
    }

    /**
     * 保存分类等级设置
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function saveCateRank()
    {
        ob_end_clean();
        ob_start();
        $ranks = Common::getInput('ranks', 'post');
        $ranks = explode(',', $ranks);
        foreach ($ranks as $value) {
            $value = explode('=', $value);
            if ($value[0]) {
                CateModel::getInstance()->setId($value[0])->setRank($value[1])->changeRankById();
            }
        }
        echo 1;
        ob_end_flush();
        exit;
    }

    /**
     * 删除分类
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function deleteCate()
    {
        $ids = Common::getInput('ids');
        $ids = explode(',', $ids);
        if (!CateModel::getInstance()->delete($ids)) {
            $this->error(CateModel::getInstance()->getError());
        }
        $this->success('删除成功');
    }

    /**
     * 文章列表
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function subjectList()
    {
        $this->_commonTab();
        $this->pageKeyList = array('id', 'cate', 'subject', 'author', 'rtime', 'hits', 'action');
        $this->searchKey = array('id', 'cid', 'subject', 'author', 'isTop', 'isPre', 'isIndex');
        /* 搜索按钮 */
        array_push($this->pageButton, array(
            'title' => '搜索',
            'id' => 'subject-search',
        ));
        /* # 批量删除 */
        array_push($this->pageButton, array(
            'title' => '删除',
            'id' => 'subject-delete',
            'data' => array(
                'uri' => U('Information/Admin/subjectDel', array('ids' => '__IDS__')),
            ),
        ));
        /* # 添加文字 */
        array_push($this->pageButton, array(
            'title' => '添加',
            'id' => 'subject-add',
            'data' => array(
                'url' => U('Information/Admin/addSubject', array('tabHash' => 'subjectList')),
            ),
        ));
        /* 添加JS */
        array_push($this->appJsList, 'js/admin.subject.js');

        $cates = array(0 => '全部');
        foreach (CateModel::getInstance()->get4Rank() as $cate) {
            $cates[$cate['id']] = $cate['name'];
        }
        $this->opt['cid'] = $cates;
        unset($cate, $cates);
        $this->opt['isTop'] = array(
            '0' => '不限',
            '1' => '推荐',
        );
        $this->opt['isIndex'] = array(
            '0' => '不限',
            '1' => '推荐',
        );
        $this->opt['isPre'] = array(
            '0' => '不限',
            '1' => '预发布',
        );

        /* # 搜索提交地址 */
        $this->searchPostUrl = U('Information/Admin/subjectList', array('tabHash' => 'subjectList'));
        list($id, $cid, $subject, $isPre, $isTop, $isIndex, $author) = Common::getInput(array('id', 'cid', 'subject', 'isPre', 'isTop', 'isIndex', 'author'));

        if ($author) {
            $author = explode(',', $author);
        } else {
            $author = array();
        }

        $data = SubjectModel::getInstance()->getAdminData(20, $id, $cid, $subject, $isPre, $isTop, $isIndex, $author);
        foreach ($data['data'] as $key => $value) {
            $value['cate'] = CateModel::getInstance()->setId($value['cid'])->getInfoById();
            $value['cate'] = $value['cate']['name'];
            $value['rtime'] = date('Y-m-d H:i:s', $value['rtime']);
            $value['author'] = getUserName($value['author']).'(UID:'.$value['author'].')';
            $value['action'] = '';
            if ($value['isPre']) {
                $value['action'] .= '<a href="'.U('Information/Admin/postSubjectPre', array('id' => $value['id'])).'">[发布]</a>&nbsp;-&nbsp;';
            }
            if ($value['isIndex']) {
                $value['action'] .= '<a href="'.U('Information/Admin/indexTop', array('id' => $value['id'],  'type' => 0)).'">[取消首页推荐]</a>&nbsp;-&nbsp;';
            } else {
                $value['action'] .= '<a href="'.U('Information/Admin/indexTop', array('tabHash' => 'subjectList', 'id' => $value['id'], 'type' => 1)).'">[首页推荐]</a>&nbsp;-&nbsp;';
            }
            if ($value['isTop']) {
                $value['action'] .= '<a href="'.U('Information/Admin/unSubjectTop', array('id' => $value['id'])).'">[取消推荐]</a>&nbsp;-&nbsp;';
            } else {
                $value['action'] .= '<a href="'.U('Information/Admin/subjectTop', array('tabHash' => 'subjectList', 'id' => $value['id'])).'">[推荐]</a>&nbsp;-&nbsp;';
            }

            $value['action'] .= '<a href="'.U('Information/Admin/addSubject', array('id' => $value['id'], 'tabHash' => 'subjectList')).'">[编辑]</a>&nbsp;-&nbsp;';

            $value['action'] .= '<a href="'.U('Information/Admin/subjectDel', array('ids' => $value['id'])).'">[删除]</a>';

            $data['data'][$key] = $value;
        }
        // var_dump($data);exit;

        $this->displayList($data);
    }

    /**
     * 取消推荐文章
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function unSubjectTop()
    {
        $id = intval(Common::getInput('id'));
        if (TopModel::getInstance()->setSid($id)->delBySid()) {
            TopModel::getInstance()->cleanTable();
            $this->success('执行成功');
        }
        $this->error('执行失败');
    }

    /**
     * 设置推荐文章
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function subjectTop()
    {
        $id = intval(Common::getInput('id'));
        SubjectModel::getInstance()->setId($id);
        /* # 检查是否不存在 */
        if (!SubjectModel::getInstance()->has()) {
            $this->error('当前主题不存在');

        /* # 检查是预发布主题 */
        } elseif (SubjectModel::getInstance()->hasIsPre()) {
            $this->error('当前主题资讯是投稿预发布状态，无法推荐，请先正是发布后推荐！');
        }

        $data = SubjectModel::getInstance()->getSubject();

        $this->_commonTab();
        $this->notEmpty = $this->pageKeyList = array('title', 'image');
        $this->savePostUrl = U('Information/Admin/saveSubjectTop', array('id' => $id));
        $this->displayConfig();
    }

    /**
     * 设置首页推荐文章
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function indexTop()
    {
        $id = intval(Common::getInput('id'));
        $type = intval(Common::getInput('type'));
        SubjectModel::getInstance()->setId($id);
        /* # 检查是否不存在 */
        if (!SubjectModel::getInstance()->has()) {
            $this->error('当前主题不存在');

        /* # 检查是预发布主题 */
        } elseif (SubjectModel::getInstance()->hasIsPre()) {
            $this->error('当前主题资讯是投稿预发布状态，无法推荐，请先正是发布后推荐！');
        }

        //$data = SubjectModel::getInstance()->getSubject();
        if (SubjectModel::getInstance()->where('id='.$id)->setField('isIndex', $type)) {
            $this->success('执行成功');
        }
        $this->error('执行失败');
    }
    /**
     * 保存推荐数据
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function saveSubjectTop()
    {
        $id = intval(Common::getInput('id'));
        list($title, $image) = Common::getInput(array('title', 'image'), 'post');
        $image = intval($image);
        if (TopModel::getInstance()->setTitle($title)->setImage($image)->setSid($id)->add()) {
            $this->assign('jumpUrl', U('Information/Admin/subjectList', array('tabHash' => 'subjectList')));
            $this->success('设置推荐成功');
        }
        $this->error(TopModel::getInstance()->getError());
    }

    /**
     * 删除主题数据
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function subjectDel()
    {
        $ids = Common::getInput('ids');
        $ids = explode(',', $ids);
        if (!SubjectModel::getInstance()->delete($ids)) {
            $this->error(SubjectModel::getInstance()->getError());
        }
        $this->success('删除成功');
    }

    /**
     * 发布预发布的主题
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function postSubjectPre()
    {
        $id = Common::getInput('id');
        if (SubjectModel::getInstance()->setId($id)->setIsPre(false)->update()) {
            $this->success('发布成功');
        }
        $this->error(SubjectModel::getInstance()->getError());
    }

    /**
     * 主题信息
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function addSubject()
    {
        $id = Common::getInput('id');
        $this->_commonTab();
        $this->notEmpty = $this->pageKeyList = array('subject', 'cid', 'abstract', 'content');
        $this->savePostUrl = U('Information/Admin/saveSubject', array('id' => $id));

        $cates = array();
        foreach (CateModel::getInstance()->get4Rank() as $cate) {
            $cates[$cate['id']] = $cate['name'];
        }
        $this->opt['cid'] = $cates;
        unset($cate, $cates);
        $this->displayConfig(SubjectModel::getInstance()->setId($id)->getSubject());
    }

    /**
     * 保存主题数据
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function saveSubject()
    {
        list($subject, $cid, $abstract, $content) = Common::getInput(array('subject', 'cid', 'abstract', 'content'), 'post');
        $content = addslashes($content);
        $id = Common::getInput('id');
        SubjectModel::getInstance()->setId($id)
            ->setCate($cid)
            ->setSubject($subject)
            ->setAbstract($abstract)
            ->setContent($content)
            ->setRTime();
        if ($id && SubjectModel::getInstance()->update()) {
            $this->success('更新主题成功');
        } elseif (SubjectModel::getInstance()->setCTime()->setAuthor($this->mid)->add()) {
            $this->assign('jumpUrl', U('Information/Admin/subjectList', array('tabHash' => 'subjectList')));
            $this->success('添加主题成功');
        }
        $this->error(SubjectModel::getInstance()->getError());
    }
} // END class Admin extends Controller
class_alias('Apps\Information\Controller\Admin', 'AdminAction');
