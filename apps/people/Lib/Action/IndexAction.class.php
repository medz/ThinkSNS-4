<?php
/**
 * 找人首页控制器.
 *
 * @author zivss <guolee226@gmail.com>
 *
 * @version TS3.0
 */
class IndexAction extends Action
{
    public function _initialize()
    {
        $this->appCssList[] = 'people.css';
    }

    public function index()
    {
        $conf = model('Xdata')->get('admin_User:findPeopleConfig');
        if (!$conf['findPeople']) {
            $this->error('找人功能已关闭');
        }
        $this->assign('findPeopleConfig', $conf['findPeople']);
        if (!isset($_GET['type']) || empty($_GET['type'])) {
            $_GET['type'] = $conf['findPeople'][0];
        } else {
            if (!in_array(t($_GET['type']), $conf['findPeople'])) {
                $this->error('参数错误！');
            }
        }
        // 获取相关数据
        $cid = isset($_GET['cid']) ? intval($_GET['cid']) : 0;
        $sex = isset($_GET['sex']) ? intval($_GET['sex']) : 0;
        $area = isset($_GET['area']) ? intval($_GET['area']) : 0;
        $verify = isset($_GET['verify']) ? intval($_GET['verify']) : 0;
        // 加载数据
        $this->assign('cid', $cid);
        $this->assign('sex', $sex);
        $this->assign('area', $area);
        $this->assign('verify', $verify);
        // 页面类型
        $type = isset($_GET['type']) ? t($_GET['type']) : $conf['findPeople'][0];
        $this->assign('type', $type);
        // 获取后台配置用户
        if (in_array($type, array('verify', 'official'))) {
            switch ($type) {
                case 'verify':
                    $conf = model('Xdata')->get('admin_User:verifyConfig');
                    $this->assign('pid', intval($_GET['pid']));
                    break;
                case 'official':
                    $conf = model('Xdata')->get('admin_User:official');
                    break;
            }
            $this->assign('topUser', $conf['top_user']);
        }

        //SEO
        switch ($type) {
            case 'verify':
                $title = '认证用户推荐';
                $cate = model('CategoryTree')->setTable('user_verified_category')->getNetworkList();  //TODO
                break;
            case 'official':
                $title = '官方推荐';
                $cate = model('CategoryTree')->setTable('user_official_category')->getNetworkList();
                break;
            case 'tag':
                $title = '按标签查找用户';
                $cate = model('UserCategory')->getNetworkList();
                break;
            case 'area':
                $title = '按地区查找用户';
                //上级id
                $pid = D('area')->where('area_id='.$area)->getField('pid');
                //同级
                $tongji = model('CategoryTree')->setTable('area')->getNetworkList($pid);
                //下级
                $cate = model('CategoryTree')->setTable('area')->getNetworkList($area);
                //dump($cate);exit;
                break;
        }
        $cate = getSubByKey($cate, 'title');
        $this->setTitle($title);
        $this->setKeywords($title);
        $this->setDescription(implode(',', $cate));

        $this->display();
    }

    /**
     * 获取指定父分类的树形结构.
     *
     * @return int   $pid 父分类ID
     * @return array 指定父分类的树形结构
     */
    public function getNetworkList()
    {
        $pid = intval($_REQUEST['pid']);
        $list = model('CategoryTree')->setTable('area')->getNetworkList($pid);
        $id = $pid + 100;
        // exit($list[$id]['child']);
        //dump($list[$id]['child']);
        exit(json_encode($list[$id]['child']));
    }
}
