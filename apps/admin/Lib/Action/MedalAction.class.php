<?php

tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
/**
 * 后台勋章管理类.
 *
 * @author Stream
 */
class MedalAction extends AdministratorAction
{
    public function _initialize()
    {
        $this->pageTitle['index'] = '勋章列表';
        $this->pageTitle['addMedal'] = '添加勋章';
        $this->pageTitle['editMedal'] = '编辑勋章';
        $this->pageTitle['userMedal'] = '用户勋章列表';
        $this->pageTitle['addUserMedal'] = '颁发勋章';
        $this->pageTitle['editUserMedal'] = '编辑用户勋章';
        parent::_initialize();
    }

    /**
     * 勋章列表.
     */
    public function index()
    {
        $list = model('Medal')->getList();
        $this->pageKeyList = array('id', 'name', 'desc', 'src', 'small_src', 'DOACTION');
        foreach ($list['data'] as &$v) {
            $v['src'] = '<img src="'.$v['src'].'" width="100px" height="100px"/>';
            $v['small_src'] = '<img src="'.$v['small_src'].'" />';
            $v['DOACTION'] = '<a href="'.U('admin/Medal/editMedal', array('id' => $v['id'])).'" >编辑</a>&nbsp;-&nbsp;';
            $v['DOACTION'] .= " <a href='javascript:void(0)' onclick='admin.deletemedal(".$v['id'].")'>删除</a>";
        }
        $this->pageButton[] = array('title' => '添加', 'onclick' => "javascript:location.href='".U('admin/Medal/addMedal')."';");
        $this->pageButton[] = array('title' => '删除', 'onclick' => 'admin.deletemedal()');
        $this->displayList($list);
    }

    /**
     * 添加勋章.
     */
    public function addMedal()
    {
        $this->pageKeyList = array('name', 'desc', 'src', 'share_card');
        $this->savePostUrl = U('admin/Medal/doAddMedal');
        $this->notEmpty = array('name', 'src');
        $this->displayConfig();
    }

    /**
     * 添加勋章到数据库.
     */
    public function doAddMedal()
    {
        $name = t($_POST['name']);
        if (!$name) {
            $this->error('勋章名称不能为空');
        }
        $exist = model('Medal')->where("`name`='".$name."'")->find();
        if (!$exist) {
            //大图and小图
            if ($_POST['src']) {
                $attachsrc = model('Attach')->getAttachById($_POST['src']);
                $src = $attachsrc['save_path'].$attachsrc['save_name'];
                //小图由大图自动生成
                $small_src = getImageUrl($src, 50, 50);
                $_POST['small_src'] = $_POST['src'].'|'.$small_src;
                //大图
                $_POST['src'] = $_POST['src'].'|'.$src;
            } else {
                $this->error('请上传大图');
            }

            //炫耀卡片
            if ($_POST['share_card']) {
                $sharecard_src = model('Attach')->getAttachById($_POST['share_card']);
                $_POST['share_card'] = $_POST['share_card'].'|'.$sharecard_src['save_path'].$sharecard_src['save_name'];
            }
            // //小图
            // if ( $_POST['small_src'] ){
            // 	$attachsmall = model('Attach')->getAttachById( $_POST['small_src'] );
            // 	$smallsrc = $attachsmall['save_path'].$attachsmall['save_name'];
            // } else {
            // 	$this->error('请上传小图');
            // }
            // $_POST['small_src'] = $_POST['small_src'].'|'.$smallsrc;

            $data = model('Medal')->create();
            $res = model('Medal')->add($data);
            if ($res) {
                $this->assign('jumpUrl', U('admin/Medal/index'));
                $this->success('添加成功');
            } else {
                $this->error('添加失败');
            }
        } else {
            $this->error('已存在相同名称的勋章');
        }
    }

    /**
     * 编辑勋章.
     */
    public function editMedal()
    {
        $id = intval($_GET['id']);
        $this->pageKeyList = array('medal_id', 'name', 'desc', 'src', 'share_card');
        $medal = model('Medal')->where('id='.$id)->find();
        $src = explode('|', $medal['src']);
        $medal['medal_id'] = $id;
        $medal['src'] = $src[0];
        //炫耀卡片
        $share_card_src = explode('|', $medal['share_card']);
        $medal['share_card'] = $share_card_src[0];
        //$medal['pic_url'] = getImageUrl( $src[1] );
        $this->savePostUrl = U('admin/Medal/doEditMedal');
        $this->notEmpty = array('name', 'src');
        $this->displayConfig($medal);
    }

    /**
     * 保存勋章到数据库.
     */
    public function doEditMedal()
    {
        $id = intval($_POST['medal_id']);
        $name = t($_POST['name']);
        if (!$name) {
            $this->error('勋章名称不能为空');
        }
        $exist = model('Medal')->where("`name`='".$name."' and id!=".$id)->find();
        if (!$exist) {
            if ($_POST['src']) {
                $attach = model('Attach')->getAttachById($_POST['src']);
                $src = $attach['save_path'].$attach['save_name'];
                //小图由大图自动生成
                $small_src = getImageUrl($src, 50, 50);
                $_POST['small_src'] = $_POST['src'].'|'.$small_src;
                //大图
                $_POST['src'] = $_POST['src'].'|'.$src;
            } else {
                unset($_POST['src']);
            }
            // if ( $_POST['small_src'] ){
            // 	$smallattach = model('Attach')->getAttachById( $_POST['small_src'] );
            // 	$smallsrc = $smallattach['save_path'].$smallattach['save_name'];
            // 	$_POST['small_src'] = $_POST['small_src'].'|'.$smallsrc;
            // } else {
            // 	unset($_POST['small_src']);
            // }

            //炫耀卡片
            if ($_POST['share_card']) {
                $sharecard_src = model('Attach')->getAttachById($_POST['share_card']);
                $_POST['share_card'] = $_POST['share_card'].'|'.$sharecard_src['save_path'].$sharecard_src['save_name'];
            }

            $data = model('Medal')->create();
            $res = model('Medal')->where('id='.$id)->save($data);
            if ($res) {
                $this->assign('jumpUrl', U('admin/Medal/index'));
                $this->success('编辑成功');
            } else {
                $this->error('编辑失败');
            }
        } else {
            $this->error('已存在相同名称的勋章');
        }
    }

    /**
     * 用户勋章列表.
     */
    public function userMedal()
    {
        if ($_POST['user']) {
            $map['uid'] = array('in', explode(',', t($_POST['user'])));
        }
        if ($_POST['medal']) {
            $mearray = is_array($_POST['medal']) ? $_POST['medal'] : array(intval($_POST['medal']));
            $map['medal_id'] = array('in', $mearray);
        }
        $list = model('Medal')->getUserMedalList($map);
        $this->pageKeyList = array('id', 'uname', 'medalname', 'medalsrc', 'desc', 'DOACTION');
        foreach ($list['data'] as &$v) {
            $v['medalsrc'] = '<img src="'.getImageUrl($v['medalsrc']).'" width="100px" height="100px" />';
            $v['DOACTION'] = '<a href="'.U('admin/Medal/editUserMedal', array('id' => $v['id'])).'" >编辑</a>';
// 			$v['DOACTION'] .= " <a href='javascript:void(0)' onclick='admin.deleteusermedal(".$v['id'].")'>删除</a>";
        }
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => '颁发勋章', 'onclick' => "javascript:location.href='".U('admin/Medal/addUserMedal')."';");
// 		$this->pageButton[] = array( 'title' => '删除' , 'onclick' => "admin.deleteusermedal()" );
        $this->searchKey = array('user', 'medal');
        $medals = model('Medal')->getAllMedal();
        $medals[0] = '不限';
        ksort($medals);
        $this->opt['medal'] = $medals;
        $this->displayList($list);
    }

    public function editUserMedal()
    {
        $id = intval($_GET['id']);
        $data = D('medal_user')->where('id='.$id)->find();
        $medals = model('Medal')->getAllMedal();
        $this->opt['medal_id'] = $medals;
        $this->pageKeyList = array('id', 'uid', 'medal_id', 'desc');
        $this->savePostUrl = U('admin/Medal/doEditUserMedal');
        $user = model('User')->getUserInfo($data['uid']);
        $data['uid'] = $user['uname'];

        $this->displayConfig($data);
    }

    public function doEditUserMedal()
    {
        $id = intval($_POST['id']);
        $data['medal_id'] = intval($_POST['medal_id']);
        $data['desc'] = t($_POST['desc']);

        $res = D('medal_user')->where('id='.$id)->save($data);
        if ($res) {
            $this->assign('jumpUrl', U('admin/Medal/userMedal'));
            $this->success('编辑成功');
        } else {
            $this->error('编辑失败');
        }
    }

    /**
     * 添加用户勋章.
     */
    public function addUserMedal()
    {
        $this->pageKeyList = array('user', 'medal', 'attach_id', 'attach_small', 'medal_name', 'medal_desc', 'desc');

        $medals = model('Medal')->getAllMedal();
        $medals[0] = '添加勋章';
        ksort($medals);
        $this->opt['medal'] = $medals;

        $this->savePostUrl = U('admin/Medal/doAddUserMedal');
        $this->notEmpty = array('user', 'attach_id', 'attach_small', 'medal_name');
        $data['type'] = 1;
        $this->displayConfig($data);
    }

    public function doAddUserMedal()
    {
        $desc = t($_POST['desc']);
        $medalDao = model('Medal');
        $medal = intval($_POST['attach_id']);
        $medalsmall = intval($_POST['attach_small']);

        $users = explode(',', $_POST['user']);
        if (!$users[0]) {
            $this->error('没有选择用户');
        }

        if ($medal) {
            $data['name'] = t($_POST['medal_name']);
            if ($data['name']) {
                $exist = $medalDao->where($data)->find();
                if ($exist) {
                    $this->error('已存在相同的勋章名称');
                } else {
                    $data['desc'] = t($_POST['medal_desc']);
                    $attach = model('Attach')->getAttachById($medal);
                    $src = $attach['save_path'].$attach['save_name'];
                    $data['src'] = $medal.'|'.$src;

                    //小图
                    if ($_POST['attach_small']) {
                        $attachsmall = model('Attach')->getAttachById($medalsmall);
                        $smallsrc = $attachsmall['save_path'].$attachsmall['save_name'];
                    } else {
                        $this->error('请上传勋章小图');
                    }
                    $data['small_src'] = $medalsmall.'|'.$smallsrc;

                    $medal_id = $medalDao->add($data);
                }
            } else {
                $this->error('请输入勋章名称');
            }
        } else {
            $medal_id = intval($_POST['medal']);
        }
        if ($medal_id) {
            $res = $medalDao->addUserMedal($users, $medal_id, $desc);
            if ($res) {
                //清除用户缓存
                model('User')->cleanCache($users);
                $this->assign('jumpUrl', U('admin/Medal/userMedal'));
                $this->success('添加成功');
            } else {
                $this->error($medalDao->getLastError());
            }
        } else {
            $this->error('没有选择勋章');
        }
    }

    /**
     * 删除勋章.
     */
    public function doDeleteMedal()
    {
        $ids = t($_POST['id']);
        $map['id'] = array('in', explode(',', $ids));
        $res = model('Medal')->where($map)->delete();
        //删除用户勋章
        $mu['medal_id'] = array('in', explode(',', $ids));
        D('medal_user')->where($mu)->delete();
        if ($res) {
            $return['status'] = 1;
            $return['data'] = '删除成功';
        } else {
            $return['status'] = 0;
            $return['data'] = '删除失败';
        }
        exit(json_encode($return));
    }

    /**
     * 删除用户勋章.
     */
    public function doDeleteUserMedal()
    {
        $ids = t($_POST['id']);
        $map['id'] = array('in', explode(',', $ids));
        $res = D('medal_user')->where($map)->delete();
        if ($res) {
            $return['status'] = 1;
            $return['data'] = '删除成功';
        } else {
            $return['status'] = 0;
            $return['data'] = '删除失败';
        }
        exit(json_encode($return));
    }
}
