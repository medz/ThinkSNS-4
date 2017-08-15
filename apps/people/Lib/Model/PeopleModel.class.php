<?php
/**
 * 找人模型 - 业务逻辑模型.
 *
 * @author zivss <guolee226@gmail.com>
 *
 * @version TS3.0
 */
class PeopleModel extends model
{
    /**
     * 通过条件查询相应的用户信息.
     *
     * @param array  $data 相应的查询条件
     * @param string $type 查询类型
     *
     * @return array 相应的用户信息
     */
    public function getPeople($data, $type)
    {
        //		if($data['app'] == 'w3g'){
        //			// 设置查询条件
        //			$list = array();
        //			switch($type) {
        //				case 'tag':
        //					$list = model('UserCategory')->w3g_getUidsByCid($data, $authenticate);
        //					break;
        //				case 'area':
        //					$list = $this->_w3g_getFilterData($data);
        //					break;
        //				case 'verify':
        //					$list = $this->_w3g_getVerifyData($data);
        //					break;
        //				case 'official':
        //					$list = $this->_w3g_getOfficialData($data);
        //					break;
        //			}
        //			// 获取用户ID
        //			$uids = getSubByKey($list['data'], 'uid');
        //			// 用户数据信息组装        
        //			$list['data'] = $this->getUserInfos($uids, $list['data']);
        //                        dump($list);exit;
        //			return $list;
        //		}
        // 设置查询条件
        $list = array();
        $data['limit'] = intval($data['limit']) ? intval($data['limit']) : 30;
        switch ($type) {
            case 'tag':
                $list = model('UserCategory')->getUidsByCid($data['cid'], $authenticate, $data['limit']);
                break;
            case 'area':
                $list = $this->_getFilterData($data);
                break;
            case 'verify':
                $list = $this->_getVerifyData($data);
                break;
            case 'official':
                $list = $this->_getOfficialData($data);
                break;
        }
        // 获取用户ID
        $uids = getSubByKey($list['data'], 'uid');

        foreach ($list['data'] as $k => $vo) {
            $list['data'][$k]['user_tag'] = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags($vo['uid']);
            //$list['data'][$k]['user_tag'] = empty ( $list['data'][$k]['user_tag'] ) ? '' : implode ( '、', $list['data'][$k]['user_tag'] );
            $list['data'][$k]['userdata'] = model('UserData')->getUserData($vo['uid']);
        }

        // 用户数据信息组装
        $list['data'] = $this->getUserInfos($uids, $list['data']);
        //dump($list['data']);exit;
        return $list;
    }

    public function searchUser($searchKey, $lastUid, $curType = 1, $limit = 20, $page = 1)
    {
        $userlist = array();
        if ($searchKey != '') {
            // if($curType == 3){         //按标签搜索
            // 	$data['name'] = $searchKey;
            // 	$tagid = D('tag')->where($data)->getField('tag_id');
            // 	$maps['app'] = 'public';
            // 	$maps['table'] = 'user';
            // 	$maps['tag_id'] = $tagid;
            // 	//获取当前lastUid下的标签用户
            // 	$lastUid && $maps['row_id'] = array('lt', $lastUid);
            // 	$user_ids = getSubByKey(D('app_tag')->where($maps)->field('row_id as uid')->order('row_id desc')->findAll(),'uid');
            // 	return $user_ids;
            // 	//获取当前lastUid下的用户数据
            // 	$map['uid'] = array('in',$user_ids);
            // 	$map['is_active'] = 1;
            // 	$map['is_audit'] = 1;
            // 	$map['is_init'] = 1;
            // 	$userlist = D('user')->where($map)->field('uid')->limit(20)->order('uid desc')->findAll();
            // 	foreach($userlist['data'] as &$v){
            // 		$v = model('User')->getUserInfo($v['uid']);
            // 		unset($v);
            // 	}
            // 	//获取满足条件的统计数据
            // 	unset($maps['row_id']);
            // 	$all_uids = getSubByKey(D('app_tag')->where($maps)->field('row_id as uid')->order('row_id desc')->findAll(),'uid');
            // 	//获取当前lastUid下的用户数据
            // 	$map['uid'] = array('in',$all_uids);
            // 	$map['is_active'] = 1;
            // 	$map['is_audit'] = 1;
            // 	$map['is_init'] = 1;
            // 	$userlist['count'] = D('user')->where($map)->field('uid')->order('uid desc')->count();
            // }else{
            $userlist = model('User')->w3g_searchUser($searchKey, $lastUid, 0, $limit, $page);
            //$userlist = model('User')->searchUser($searchKey, $lastUid, 0, $limit,'','','0',$page);
            //}
            $uids = getSubByKey($userlist['data'], 'uid');
            $userlist['lastUid'] = end($uids);
            $userlist['data'] = $this->getUserInfos($uids, $userlist['data']);

            /*$usercounts = model('UserData')->getUserDataByUids( $uids );
            $userGids = model('UserGroupLink')->getUserGroup( $uids );
            $followstatus = model('Follow')->getFollowStateByFids($GLOBALS['ts']['mid'] , $uids );
            foreach($userlist['data'] as $k=>$v){
                $userlist['data'][$k]['usercount'] = $usercounts[$v['uid']];
                $userlist['data'][$k]['userTag'] = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags($v['uid']);
                // 获取用户用户组信息
// 					$userGids = model('UserGroupLink')->getUserGroup($v['uid']);
                $userGroupData = model('UserGroup')->getUserGroupByGids($userGids[$v['uid']]);
                foreach($userGroupData as $key => $value) {
                    if($value['user_group_icon'] == -1) {
                        unset($userGroupData[$key]);
                        continue;
                    }
                    $userGroupData[$key]['user_group_icon_url'] = THEME_PUBLIC_URL.'/image/usergroup/'.$value['user_group_icon'];
                }
                $userlist['data'][$k]['userGroupData'] = $userGroupData;
                // 获取用户积分信息
                //$userlist['data'][$k]['userCredit'] = model('Credit')->getUserCredit($this->uid);
                //关注状态
                $userlist['data'][$k]['follow_state'] = $followstatus[ $v['uid'] ];
            }*/
        }

        return $userlist;
    }

    /**
     * 获取筛选用户数据列表.
     *
     * @param array  $data  筛选相�
     * �条件
     * @param string $field 字段数据
     * @param string $order 排序数据
     *
     * @return array 筛选用户数据列表
     */
    /*private function _w3g_getFilterData($data, $field = 'u.uid', $order = 'u.uid DESC')
    {
        // 设置查询条件
        $map['u.is_init'] = 1;
        $map['u.is_del'] = 0;
        // 设置表名
        $table = '`'.C('DB_PREFIX').'user` AS u';
        if(!empty($data['cid'])) {
            $tagInfo = model('UserCategory')->where('user_category_id='.intval($data['cid']))->find();

            if($tagInfo['pid'] == 0){
                $tags = model('UserCategory')->where('pid='.$tagInfo['user_category_id'])->findAll();

                foreach($tags as $k=>$v){
                    $tag_id = D('tag')->where(array('name'=>t($v['title'])))->getField('tag_id');
                    if($tag_id){
                        $tagId[] = $tag_id;
                        unset($tag_id);
                    }
                }
                $maps['tag_id'] = array('in',$tagId);
            }else{
                $tagId = D('tag')->where(array('name'=>t($tagInfo['title'])))->getField('tag_id');
                $maps['tag_id'] = $tagId;
            }
            //dump($tagId);exit;
            $maps['app'] = 'public';
            $maps['table'] = 'user';
            $data['lastUid'] && $maps['row_id'] = array('lt', $data['lastUid']);
            $tag_user = D('app_tag')->where($maps)->order('row_id desc')->findAll();
            $map['uid'] = array('in',getSubByKey($tag_user,'row_id'));
            // $table .= ' LEFT JOIN `'.C('DB_PREFIX').'user_category_link` AS c ON u.uid = c.uid';
            // // 若是第一级 TODO
            // $categoryInfo = model('UserCategory')->where('user_category_id='.intval($data['cid']))->find();
            // if($categoryInfo['pid'] == 0) {
            // 	$cids[] = intval($data['cid']);
            // 	$childCids = model('UserCategory')->where('pid='.intval($data['cid']))->getAsFieldArray('user_category_id');
            // 	$cids = array_merge($cids, $childCids);
            // 	$map['c.user_category_id'] = array('IN', $cids);
            // } else {
            // 	$map['c.user_category_id'] = intval($data['cid']);
            // }
        }
        if(!empty($data['verify'])) {
            $map['v.verified'] = 1;
            $table .= ' LEFT JOIN `'.C('DB_PREFIX').'user_verified` AS v ON u.uid = v.uid';
            $data['verify'] == 1 && $map['v.id'] = array('EXP', 'IS NOT NULL');
            $data['verify'] == 2 && $map['v.id'] = array('EXP', 'IS NULL');
        }
        // 搜索地区条件判断
        $pid1 = model('Area')->where('area_id='.$data['area'])->getField('pid');
         $level = 1;
        if($pid1 != 0){
            $level = $level +1;
            $pid2 = model('Area')->where('area_id='.$pid1)->getField('pid');
            if($pid2 != 0){
                $level = $level +1;
            }
        }
        switch ($level) {
            case '1':
                !empty($data['area']) && $map['province'] = intval($data['area']);
                break;
            case '2':
                !empty($data['area']) && $map['city'] = intval($data['area']);
                break;
            case '3':
                !empty($data['area']) && $map['area'] = intval($data['area']);
                break;

            default:
                # code...
                break;
        }

        !empty($data['sex']) && $map['sex'] = intval($data['sex']);

        $list['data'] = D()->table($table)
                            ->field($field)
                            ->where($map)
                            ->order($order)
                            ->limit($data['limit'])
                            ->findAll();

        return $list;
    }*/
    /**
     * 获取筛选认证用数据列表.
     *
     * @param array  $data  筛选相�
     * �条件
     * @param string $field 字段数据
     * @param string $order 排序数据
     *
     * @return array 筛选认证用数据列表
     */
    /*public function _w3g_getVerifyData($data, $field = 'u.uid, v.info', $order = 'u.uid DESC')
    {
        // 设置表明
        $table = '`'.C('DB_PREFIX').'user_verified` AS v LEFT JOIN `'.C('DB_PREFIX').'user` AS u ON u.uid = v.uid';
        if($data['cid']){
            if($data['pid']){
                $maps['user_verified_category_id'] = array('in', getSubByKey(D('user_verified_category')->where('user_verified_category_id='.$data['cid'])->findAll(),'user_verified_category_id'));
                $maps['verified'] = 1;
                if($data['uids']){
                    $maps['uid'] = array('EXP', 'NOT IN ('.$data['uids'].')');  //排除置顶用户
                }
                $map['u.uid'] = array('in', getSubByKey(D('user_verified')->where($maps)->field('uid')->findAll(),'uid'));
            }else{
                $map['u.uid'] = array('in', getSubByKey(D('user_verified')->where('verified=1 AND usergroup_id='.$data['cid'])->field('uid')->findAll(),'uid'));
            }
        }else{
            $map['u.uid'] = array('in', getSubByKey(D('user_verified')->where('verified=1')->field('uid')->findAll(),'uid'));
        }
        // $uids_arr = array();
        // if(ia_array($map['u.uid'][1])){
        // 	foreach($map['u.uid'][1] as $v){
        // 		if($v >= $data['lastUid']){
        // 			continue;
        // 		}
        // 		$uids_arr[] = $v;
        // 	}
        // }
        // $map['u.uid'] = array('in', $uids_arr);
        $data['lastUid'] && $map['uid'] = array('lt', $data['lastUid']);
        // 查询数据
        $list['data'] = D()->table($table)
                            ->where($map)
                            ->order($order)
                            ->limit($data['limit'])
                            ->findAll();
        return $list;
    }*/

    /**
     * 获取筛选官方用户数据列表.
     *
     * @param array  $data  筛选相�
     * �条件
     * @param string $field 字段数据
     * @param string $order 排序数据
     *
     * @return array 筛选官方用户数据列表
     */
    /*private function _w3g_getOfficialData($data, $field = 'u.uid, o.info', $order = 'u.uid DESC')
    {
        // 设置表明
        $table = '`'.C('DB_PREFIX').'user_official` AS o LEFT JOIN `'.C('DB_PREFIX').'user` AS u ON u.uid = o.uid';
        // 设置查询条件
        $map['u.is_init'] = 1;
        $map['u.is_del'] = 0;
        // 排除用户
        if(!empty($data['uids'])) {
            $map['u.uid'] = array('EXP', 'NOT IN ('.$data['uids'].')');
        }
        if(!empty($data['cid'])) {
            $map['o.user_official_category_id'] = intval($data['cid']);
        }
        $data['lastUid'] && $map['uid'] = array('lt', $data['lastUid']);
        // 查询数据
        $list['data'] = D()->table($table)->where($map)->order($order)->limit($data['limit'])->findAll();

        return $list;
    }*/

    /**
     * 获取筛选用户数据列表.
     *
     * @param array  $data  筛选相�
     * �条件
     * @param string $field 字段数据
     * @param string $order 排序数据
     * @param int    $page  分页个数
     *
     * @return array 筛选用户数据列表
     */
    private function _getFilterData($data, $field = 'u.uid', $order = 'u.uid DESC', $page = 30)
    {
        $data['limit'] && $page = intval($data['limit']);
        // 设置查询条件
        $map['u.is_init'] = 1;
        $map['u.is_del'] = 0;
        // 设置表名
        $table = '`'.C('DB_PREFIX').'user` AS u';
        if (!empty($data['cid'])) {
            $tagInfo = model('UserCategory')->where('user_category_id='.intval($data['cid']))->find();

            if ($tagInfo['pid'] == 0) {
                $tags = model('UserCategory')->where('pid='.$tagInfo['user_category_id'])->findAll();

                foreach ($tags as $k => $v) {
                    $tag_id = D('tag')->where(array('name' => t($v['title'])))->getField('tag_id');
                    if ($tag_id) {
                        $tagId[] = $tag_id;
                        unset($tag_id);
                    }
                }
                $maps['tag_id'] = array('in', $tagId);
            } else {
                $tagId = D('tag')->where(array('name' => t($tagInfo['title'])))->getField('tag_id');
                $maps['tag_id'] = $tagId;
            }
            //dump($tagId);exit;
            $maps['app'] = 'public';
            $maps['table'] = 'user';
            $tag_user = D('app_tag')->where($maps)->findAll();
            $map['uid'] = array('in', getSubByKey($tag_user, 'row_id'));
            // $table .= ' LEFT JOIN `'.C('DB_PREFIX').'user_category_link` AS c ON u.uid = c.uid';
            // // 若是第一级 TODO
            // $categoryInfo = model('UserCategory')->where('user_category_id='.intval($data['cid']))->find();
            // if($categoryInfo['pid'] == 0) {
            // 	$cids[] = intval($data['cid']);
            // 	$childCids = model('UserCategory')->where('pid='.intval($data['cid']))->getAsFieldArray('user_category_id');
            // 	$cids = array_merge($cids, $childCids);
            // 	$map['c.user_category_id'] = array('IN', $cids);
            // } else {
            // 	$map['c.user_category_id'] = intval($data['cid']);
            // }
        }
        if (!empty($data['verify'])) {
            $map['v.verified'] = 1;
            $table .= ' LEFT JOIN `'.C('DB_PREFIX').'user_verified` AS v ON u.uid = v.uid';
            $data['verify'] == 1 && $map['v.id'] = array('EXP', 'IS NOT NULL');
            $data['verify'] == 2 && $map['v.id'] = array('EXP', 'IS NULL');
        }
        // 搜索地区条件判断
        $pid1 = model('Area')->where('area_id='.$data['area'])->getField('pid');
        $level = 1;
        if ($pid1 != 0) {
            $level = $level + 1;
            $pid2 = model('Area')->where('area_id='.$pid1)->getField('pid');
            if ($pid2 != 0) {
                $level = $level + 1;
            }
        }
        switch ($level) {
            case '1':
                !empty($data['area']) && $map['province'] = intval($data['area']);
                break;
            case '2':
                !empty($data['area']) && $map['city'] = intval($data['area']);
                break;
            case '3':
                !empty($data['area']) && $map['area'] = intval($data['area']);
                break;

            default:
                // code...
                break;
        }

        !empty($data['sex']) && $map['sex'] = intval($data['sex']);

        $list = D()->table($table)->field($field)->where($map)->order($order)->findPage($page);

        return $list;
    }

    /**
     * 获取筛选认证用数据列表.
     *
     * @param array  $data  筛选相�
     * �条件
     * @param string $field 字段数据
     * @param string $order 排序数据
     * @param int    $page  分页个数
     *
     * @return array 筛选认证用数据列表
     */
    public function _getVerifyData($data, $field = 'u.uid, v.info', $order = 'u.uid DESC', $page = 30)
    {
        $data['limit'] && $page = intval($data['limit']);
        // 设置表明
        $table = '`'.C('DB_PREFIX').'user_verified` AS v LEFT JOIN `'.C('DB_PREFIX').'user` AS u ON u.uid = v.uid';
        if ($data['cid']) {
            if ($data['pid']) {
                $maps['user_verified_category_id'] = array('in', getSubByKey(D('user_verified_category')->where('user_verified_category_id='.$data['cid'])->findAll(), 'user_verified_category_id'));
                $maps['verified'] = 1;
                if ($data['uids']) {
                    $maps['uid'] = array('EXP', 'NOT IN ('.$data['uids'].')');  //排除置顶用户
                }
                $map['u.uid'] = array('in', getSubByKey(D('user_verified')->where($maps)->field('uid')->findAll(), 'uid'));
            } else {
                $map['u.uid'] = array('in', getSubByKey(D('user_verified')->where('verified=1 AND usergroup_id='.$data['cid'])->field('uid')->findAll(), 'uid'));
            }
        } else {
            $map['u.uid'] = array('in', getSubByKey(D('user_verified')->where('verified=1')->field('uid')->findAll(), 'uid'));
        }
        // 查询数据
        $list = D()->table($table)->where($map)->order($order)->findPage($page);

        return $list;
    }

    /**
     * 获取筛选官方用户数据列表.
     *
     * @param array  $data  筛选相�
     * �条件
     * @param string $field 字段数据
     * @param string $order 排序数据
     * @param int    $page  分页个数
     *
     * @return array 筛选官方用户数据列表
     */
    private function _getOfficialData($data, $field = 'u.uid, o.info', $order = 'u.uid DESC', $page = 30)
    {
        $data['limit'] && $page = intval($data['limit']);
        // 设置表明
        $table = '`'.C('DB_PREFIX').'user_official` AS o LEFT JOIN `'.C('DB_PREFIX').'user` AS u ON u.uid = o.uid';
        // 设置查询条件
        $map['u.is_init'] = 1;
        $map['u.is_del'] = 0;
        // 排除用户
        if (!empty($data['uids'])) {
            $map['u.uid'] = array('EXP', 'NOT IN ('.$data['uids'].')');
        }
        if (!empty($data['cid'])) {
            $map['o.user_official_category_id'] = intval($data['cid']);
        }
        // 查询数据
        $list = D()->table($table)->where($map)->order($order)->findPage($page);

        return $list;
    }

    /**
     * 获取用户相�
     * �信息.
     *
     * @param array $uids 用户ID数组
     *
     * @return array 用户相�
     * �数组
     */
    public function getUserInfos($uids, $data)
    {
        // 获取用户基本信息
        $userInfos = model('User')->getUserInfoByUids($uids);
        // 获取用户统计数据
        $userDataInfo = model('UserData')->getUserKeyDataByUids('follower_count', $uids);
        // 获取关注信息
        $followStatusInfo = model('Follow')->getFollowStateByFids($GLOBALS['ts']['mid'], $uids);
        // 获取用户组信息
        $userGroupInfo = model('UserGroupLink')->getUserGroupData($uids);
        // 组装数据
        foreach ($data as &$value) {
            $value = array_merge($value, $userInfos[$value['uid']]);
            $value['user_data'] = $userDataInfo[$value['uid']];
            $value['follow_state'] = $followStatusInfo[$value['uid']];
            $value['user_group'] = $userGroupInfo[$value['uid']];
        }

        return $data;
    }

    /**
     * 获取指定用户的相�
     * �信息.
     *
     * @param array  $uids  指定用户ID数组
     * @param string $type  指定类型
     * @param int    $limit 显示数据，默认为3
     *
     * @return array 指定用户的相�
     * �信息
     */
    public function getTopUserInfos($uids, $type, $limit = 3)
    {
        if (empty($uids)) {
            return array();
        }
        // 整理成数组
        $uids = is_array($uids) ? $uids : explode(',', $uids);
        // 获取相关用户信息
        $map['u.uid'] = array('IN', $uids);
        $map['u.is_init'] = 1;
        $map['u.is_del'] = 0;
        switch ($type) {
            case 'verify':
                $map['v.verified'] = 1;
                $data = D()->table('`'.C('DB_PREFIX').'user_verified` AS v LEFT JOIN `'.C('DB_PREFIX').'user` AS u ON u.uid = v.uid')->where($map)->group('u.uid')->limit($limit)->findAll();
                break;
            case 'official':
                $data = D()->table('`'.C('DB_PREFIX').'user_official` AS v LEFT JOIN `'.C('DB_PREFIX').'user` AS u ON u.uid = v.uid')->where($map)->group('u.uid')->limit($limit)->findAll();
                break;
        }
        $list = $this->getUserInfos($uids, $data);

        return $list;
    }

    /**
     * 通过条件查询相应的用户信息（新）.
     *
     * @param array  $data 相应的查询条件
     * @param string $type 查询类型
     * @param int    $mid  用户uid
     *
     * @return array 相应的用户信息
     */
    public function getPeopleNew($data, $type, $mid)
    {
        // 设置查询条件
        $list = array();
        $data['limit'] = intval($data['limit']) ? intval($data['limit']) : 30;
        switch ($type) {
            case 'tag':
                $list = model('UserCategory')->getUidsByCid($data['cid'], $authenticate, $data['limit']);
                break;
            case 'area':
                $list = $this->_getFilterData($data);
                break;
            case 'verify':
                $list = $this->_getVerifyData($data);
                break;
            case 'official':
                $list = $this->_getOfficialData($data);
                break;
            case 'unit':
                $list = $this->_getUnitData($data);
                break;
        }
        $uids = getSubByKey($list['data'], 'uid');
        if (!$uids) {
            return null;
        }
        $objList = \Ts\Models\User::whereIn('uid', $uids)->where(function ($query) use ($mid) {
            if ($mid > 0) {
                $query->where('uid', '!=', intval($mid));
            }
        })->orderBy('uid', 'desc')->get();
        unset($list['data']);
        // 用户数据信息组装
        $list['data'] = $this->getUsersInfoNew($objList);

        return $list;
    }

    /**
     * 获取用户组�
     * 数据（新）.
     *
     * @param $objList
     *
     * @return mixed
     */
    public function getUsersInfoNew($objList)
    {
        $list = array();
        foreach ($objList as $v) {
            $list[] = $this->getUserInfoNew($v);
        }
        unset($objList);

        return $list;
    }

    /**
     * 获取用户详�
     * （新）.
     *
     * @param $userObj
     *
     * @return array|bool|mixed|static
     */
    public function getUserInfoNew($userObj)
    {
        $userInfo = static_cache('user_info_new_'.$userObj->uid);
        if (empty($userInfo)) {
            $userInfo = model('Cache')->get('ui_new_'.$userObj->uid);
        }
        if (empty($userInfo)) {
            $userInfo = $userObj->toArray();
            // 获取用户头像
            $userInfo['avatar_original'] = $userInfo['face']->avatar_original;
            $userInfo['avatar_big'] = $userInfo['face']->avatar_big;
            $userInfo['avatar_middle'] = $userInfo['face']->avatar_middle;
            $userInfo['avatar_small'] = $userInfo['face']->avatar_small;
            $userInfo['avatar_tiny'] = $userInfo['face']->avatar_tiny;
            $userInfo['avatar_url'] = U('public/Attach/avatar', array(
                'uid' => $userInfo['uid'],
            ));
            $userInfo['space_url'] = !empty($userInfo['domain']) ? U('public/Profile/index', array(
                'uid' => $userInfo['domain'],
            )) : U('public/Profile/index', array(
                'uid' => $userInfo['uid'],
            ));
            $userInfo['space_link'] = "<a href='".$userInfo['space_url']."' target='_blank' uid='{$userInfo['uid']}' event-node='face_card'>".$userInfo['uname'].'</a>';
            $userInfo['space_link_no'] = "<a href='".$userInfo['space_url']."' title='".$userInfo['uname']."' target='_blank'>".$userInfo['uname'].'</a>';

            // 获取用户标签
            foreach ($userObj->tags as $tagLink) {
                $userInfo['user_tag'][$tagLink->tag->tag_id] = $tagLink->tag->name;
            }
            //
            foreach ($userObj->userData as $userdata) {
                $userInfo['userdata'][$userdata->key] = $userdata->value;
            }
            // 部门
            foreach ($userObj->department as $depart) {
                $_depart['department_id'] = $depart->department->department_id;
                $_depart['title'] = $depart->department->title;
                $_depart['parent_dept_id'] = $depart->department->parent_dept_id;
                $_depart['display_order'] = $depart->department->display_order;
                $userInfo['depart'][] = $_depart;
                unset($_depart);
            }
            // 勋章
            foreach ($userObj->medal as $medal) {
                $_medal['id'] = $medal->medal->id;
                $_medal['name'] = $medal->medal->name;
                $_medal['desc'] = $medal->medal->desc;
                $_medal['src'] = $medal->medal->src;
                $_medal['small_src'] = $medal->medal->small_src;
                $_medal['type'] = $medal->medal->type;
                $_medal['share_card'] = $medal->medal->share_card;
                $userInfo['medals'][] = $_medal;
                unset($_medal);
            }
            // 用户组
            foreach ($userObj->group as $key => $group) {
                if ($key > 0) {
                    $userInfo['group_icon'] .= '&nbsp;';
                }
                if ($group->info->user_group_icon != -1) {
                    $_group['user_group_id'] = $group->info->user_group_id;
                    $_group['user_group_name'] = $group->info->user_group_name;
                    $_group['user_group_icon'] = $group->info->user_group_icon;
                    $_group['user_group_type'] = $group->info->user_group_type;
                    $_group['app_name'] = $group->info->app_name;
                    $_group['is_authenticate'] = $group->info->is_authenticate;
                    $_group['user_group_icon_url'] = $group->info->icon;
                    $userInfo['group_icon'] .= $group->info->image;
                    $userInfo['api_user_group'] = $_group;
                    unset($_group);
                }
            }
            $userInfo['user_group'] = $userInfo['group_icon_only'] = $userInfo['api_user_group'];
            $userInfo['credit_info'] = $userObj->credit;
            // 被关注数
            $userInfo['user_data']['follower_count'] = $userInfo['userdata']['follower_count'];

            unset($userInfo['face'], $userInfo['tags'], $userInfo['user_data'], $userInfo['department'], $userInfo['medal'], $userInfo['group'], $userInfo['credit']);
            model('Cache')->set('ui_new_'.$userInfo['uid'], $userInfo, 600);
            static_cache('user_info_new'.$userInfo['uid'], $userInfo);
        }
        // 与该用户的关注状态
        if ($_SESSION['mid']) {
            $userInfo['follow_state']['following'] = $userObj->followIngStatus($_SESSION['mid']);
            $userInfo['follow_state']['follower'] = $userObj->followStatus($_SESSION['mid']);
        }

        return $userInfo;
    }
}
