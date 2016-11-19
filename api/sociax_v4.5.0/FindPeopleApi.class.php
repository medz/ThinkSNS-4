<?php
/*
 * 找伙伴
 */
class FindPeopleApi extends Api
{
    public function rank_score()
    {
        $user = model('User')->getUserInfo($this->mid);
        $my ['uname'] = $user ['uname'];
        $my ['remark'] = $user ['remark'];
        $my ['avatar'] = $user ['avatar_big'];

        // 积分排行
        $scoreuids = M('credit_user')->field('uid,`score`')->order('`score` desc,uid')->limit(10000)->findAll();
        $iscore = 0;
        foreach ($scoreuids as $key => $gu) {
            $iscore ++;

            $gu ['uid'] == $this->mid && $rank = $iscore;
            if ($key < 14) {
                $gu ['rank'] = (string) $iscore;
                $user = model('User')->getUserInfo($gu ['uid']);
                $gu ['uname'] = $user ['uname'];
                $gu ['avatar'] = $user ['avatar_big'];

                $map ['key'] = 'weibo_count';
                $map ['uid'] = $gu ['uid'];
                $gu ['weibo_count'] = (string) M('user_data')->where($map)->getField('value');

                $lists [] = $gu;
            }
        }
        empty($rank) && $rank = 10000; // 一万名后不再作排名，以提高性能

        $my ['rank'] = '排名：'.$rank;
        $my ['lists'] = $lists;

        return $my;
    }
    public function rank_medal()
    {
        $user = model('User')->getUserInfo($this->mid);
        $my ['uname'] = $user ['uname'];
        $my ['remark'] = $user ['remark'];
        $my ['avatar'] = $user ['avatar_big'];

        // 勋章排行
        $medaluids = M('medal_user')->field('uid,count(medal_id) as mcount')->group('uid')->order('mcount desc,uid')->limit(10000)->findAll();
        $imedal = 0;
        foreach ($medaluids as $key => $mu) {
            $imedal ++;

            $mu ['uid'] == $this->mid && $rank = $imedal;
            if ($key < 14) {
                $mu ['rank'] = (string) $imedal;
                $mu ['mcount'] = (string) $mu ['mcount'];
                $user = model('User')->getUserInfo($mu ['uid']);
                $mu ['uname'] = $user ['uname'];
                $mu ['avatar'] = $user ['avatar_big'];
                $mu ['remark'] = $user ['remark'];

                $lists [] = $mu;
            }
        }
        // empty ( $rank ) && $rank = 10000; // 一万名后不再作排名，以提高性能

        $my ['rank'] = '排名：'.$rank;
        $my ['lists'] = $lists;

        return $my;
    }
    /**
     * 找人首页-搜索用户 --using
     *
     * @param string $key
     *                       搜索关键词
     * @param string $max_id
     *                       上次返回的最后一个用户ID
     * @param string $count
     *                       数量
     * @request int $rus 感兴趣的人返回个数，default：5
     * @return array 用户列表
     */
    public function search_user()
    {
        /* 感兴趣的人人数 */
        $rus = intval($this->data['rus']);
        $rus or
        $rus = 5;

        $key = trim(t($this->data ['key']));
        if ($key) {
            /* 注销，可以搜索自己~ */
            // $map ['uid'] = array(
            //         'neq',
            //         $this->mid,
            // );
            $map ['is_init'] = 1;
            $map ['is_audit'] = 1;
            $map ['is_active'] = 1;
            $map ['is_del'] = 0;
            $max_id = $this->max_id ? intval($this->max_id) : 0;
            $count = $this->count ? intval($this->count) : 20;
            $map2 = $map;
            $map2 ['uname'] = $key;
            $uid_arr = model('User')->where($map2)->field('uid,uname,intro')->findAll(); // 先搜索和key一致的，优先显示
            if ($uid_arr) {
                $map ['uid'] = array(
                        'neq',
                        $uid_arr [0] ['uid'],
                );
                if (!empty($key)) {
                    $_map ['search_key'] = array('like', '%'.$key.'%');

                    //备注
                    $ruid_arr = D('UserRemark')->searchRemark($this->mid, t($this->data['key']));
                    if ($ruid_arr) {
                        $_map ['uid'] = array('IN', $ruid_arr);
                        $_map ['_logic'] = 'OR';
                    }

                    $map['_complex'] = $_map;
                }

                if (! $max_id) {
                    $user_list = (array) model('User')->where($map)->field('uid,uname,intro')->order('uid desc')->limit($count - 1)->findAll();
                    $user_list = array_merge($uid_arr, $user_list);
                } else {
                    $map ['uid'] = array(
                            array(
                                    'lt',
                                    $max_id,
                            ),
                            array(
                                    'neq',
                                    $uid_arr [0] ['uid'],
                            ),
                            'AND',
                    );
                    $user_list = (array) model('User')->where($map)->field('uid,uname,intro')->order('uid desc')->limit($count)->findAll();
                }
            } else {
                ! empty($max_id) && $map ['uid'] = array(
                        'lt',
                        $max_id,
                );

                if (!empty($key)) {
                    $_map ['search_key'] = array('like', '%'.$key.'%');

                    //备注
                    $ruid_arr = D('UserRemark')->searchRemark($this->mid, t($this->data['key']));
                    if ($ruid_arr) {
                        $_map ['uid'] = array('IN', $ruid_arr);
                        $_map ['_logic'] = 'OR';
                    }

                    $map['_complex'] = $_map;
                }

                $user_list = (array) model('User')->where($map)->field('uid,uname,intro')->order('uid desc')->limit($count)->findAll();
                // dump(model('User')->getLastSql());
            }
            $follow_status = model('Follow')->getFollowStateByFids($this->mid, getSubByKey($user_list, 'uid'));
            foreach ($user_list as $k => $v) {
                $user_list [$k] ['uid'] = $v['uid'];
                $user_list [$k] ['uname'] = $v['uname'];
                $user_list [$k] ['remark'] = D('UserRemark')->getRemark($this->mid, $v['uid']);
                $user_list [$k] ['intro'] = $user_list [$k] ['intro'] ? formatEmoji(false, $user_list [$k] ['intro']) : '';
                $user_list [$k] ['follow_status'] = $follow_status [$v ['uid']];
                $user_info = api('User')->get_user_info($v ['uid']);
                $user_list [$k] ['avatar'] = $user_info ['avatar'] ['avatar_big'];
            }
        } else { // 获取感兴趣的5个人
            $user = model('RelatedUser')->getRelatedUser($rus);
            $user_list = array();
            foreach ($user as $k => $v) {
                $user_list [$k] ['uid'] = $v ['userInfo'] ['uid'];
                $user_list [$k] ['uname'] = $v ['userInfo'] ['uname'];
                $user_list [$k] ['remark'] = $v ['userInfo'] ['remark'];
                $user_list [$k] ['avatar'] = $v ['userInfo'] ['avatar_big'];
                $user_list [$k] ['intro'] = $v ['info'] ['msg'] ? formatEmoji(false, $v ['info'] ['msg']) : '';
                $user_list [$k] ['follow_status'] = model('Follow')->getFollowState($this->mid, $v ['userInfo'] ['uid']);
            }
        }

        return $user_list;
    }

    /**
     * 按标签搜索 --using
     *
     * @return array 所有标签分类
     */
    public function get_user_tags()
    {
        $level1 = D('user_category')->where('pid=0')->order('sort asc,user_category_id asc')->findAll();
        $categoryTree = array();
        foreach ($level1 as $k => $v) {
            $categoryTree [$k] ['title'] = $v ['title'];
            $categoryTree [$k] ['child'] = D('user_category')->where('pid='.$v ['user_category_id'])->field('user_category_id as id,title')->findAll();
        }

        return $categoryTree;
    }

    /**
     * 按标签搜索用户 --using
     *
     * @param
     *        	integer tag_id 标签ID
     * @param
     *        	integer max_id 上次返回的最后一个用户ID
     * @param  string $count
     *                       数量
     * @return array  用户列表
     */
    public function search_by_tag()
    {
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;
        $cid = intval($this->data ['tag_id']);
        if (! $cid) {
            return array(
                    'status' => 0,
                    'msg' => '请选择标签',
            );
        }
        $pid = M('UserCategory')->where('user_category_id='.$cid)->getField('pid');
        if ($pid == 0) {
            $cids = M('UserCategory')->where('pid='.$cid)->getAsFieldArray('user_category_id');

            $cmap ['user_category_id'] = array(
                    'IN',
                    $cids,
            );

            $title = M('UserCategory')->where($cmap)->findAll();

            foreach ($title as $key => $value) {
                $amap ['name'] = array(
                        'LIKE',
                        $value ['title'],
                );
                $tag = M('tag')->where($amap)->getField('tag_id');
                if ($tag) {
                    $tag_id [] = $tag;
                }
            }
            $tmap ['tag_id'] = array(
                    'IN',
                    $tag_id,
            );
        } else {
            $cmap ['user_category_id'] = intval($cid);
            $title = M('UserCategory')->where($cmap)->find();
            $amap ['name'] = array(
                    'LIKE',
                    $title ['title'],
            );
            $tag_id [] = M('tag')->where($amap)->getField('tag_id');
            $tmap ['tag_id'] = array(
                    'IN',
                    $tag_id,
            );
        }
        ! empty($max_id) && $tmap ['row_id'] = array(
                'lt',
                $max_id,
        );
        $uids = M('app_tag')->field('`row_id`')->where($tmap)->order('row_id desc')->limit($count)->findAll();

        $user_list = array();
        foreach ($uids as $k => $v) {
            $user_info = api('User')->get_user_info($v ['row_id']);
            $user_list [$k] ['uid'] = $user_info ['uid'];
            $user_list [$k] ['uname'] = $user_info ['uname'];
            $user_list [$k] ['remark'] = $user_info ['remark'];
            $user_list [$k] ['avatar'] = $user_info ['avatar'] ['avatar_big'];
            $user_list [$k] ['intro'] = $user_info ['intro'] ? formatEmoji(false, $user_info ['intro']) : '';
            $user_list [$k] ['follow_status'] = model('Follow')->getFollowState($this->mid, $v ['row_id']);
        }

        return $user_list;
    }


    /**
     * 按性别搜索用户 --using
     *
     * @param
     *         integer sex 性别
     * @param
     *         integer max_id 上次返回的最后一个用户ID
     * @param
     *         string $count 数量
     * @return
     *         array  用户列表
     */
    public function search_by_sex()
    {
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;

        if ($this->data ['sex'] == '') {
            return array(
                    'status' => 0,
                    'msg' => '请选择性别',
            );
        }
        $sex = intval($this->data ['sex']);

        //0查全部
        $sex != 0 && $map['sex'] = $sex;

        ! empty($max_id) && $map ['uid'] = array(
                'lt',
                $max_id,
        );
        $uids = D('User')->field('uid')->where($map)->order('uid desc')->limit($count)->findAll();

        $user_list = array();
        foreach ($uids as $k => $v) {
            $user_info = api('User')->get_user_info($v ['uid']);
            $user_list [$k] ['uid'] = $user_info ['uid'];
            $user_list [$k] ['uname'] = $user_info ['uname'];
            $user_list [$k] ['remark'] = $user_info ['remark'];
            $user_list [$k] ['avatar'] = $user_info ['avatar'] ['avatar_big'];
            $user_list [$k] ['intro'] = $user_info ['intro'] ? formatEmoji(false, $user_info ['intro']) : '';
            $user_list [$k] ['follow_status'] = model('Follow')->getFollowState($this->mid, $v ['uid']);
        }

        return $user_list;
    }

    /**
     * 获取地区(按字母) --using
     *
     * @return array 城市列表
     */
    public function get_user_city()
    {
        $my = model('User')->where('`uid` = '.$this->mid)->getField('city');
        $letters = array(
                'my' => array(),
                'A' => array(),
                'B' => array(),
                'C' => array(),
                'D' => array(),
                'E' => array(),
                'F' => array(),
                'G' => array(),
                'H' => array(),
                'I' => array(),
                'J' => array(),
                'K' => array(),
                'L' => array(),
                'M' => array(),
                'N' => array(),
                'O' => array(),
                'P' => array(),
                'Q' => array(),
                'R' => array(),
                'S' => array(),
                'T' => array(),
                'U' => array(),
                'V' => array(),
                'W' => array(),
                'X' => array(),
                'Y' => array(),
                'Z' => array(),
        );
        $provinces = D('area')->where('pid=0')->findAll();
        $map ['pid'] = array(
                'in',
                getSubByKey($provinces, 'area_id'),
        );
        $map ['title'] = array(
                'exp',
                'not in("市辖区","县","市","省直辖县级行政单位" ,"省直辖行政单位")',
        );
        $citys = D('area')->where($map)->findAll();

        foreach ($citys as $k => $v) {
            $first_letter = getFirstLetter($v ['title']);
            $letters [$first_letter] [$v ['area_id']] ['city_id'] = $v ['area_id'];
            $letters [$first_letter] [$v ['area_id']] ['city_name'] = $v ['title'];
            if ($v['area_id'] == $my) {
                $letters ['my'] [$v ['area_id']] ['city_id'] = $v ['area_id'];
                $letters ['my'] [$v ['area_id']] ['city_name'] = $v ['title'];
            }
            unset($first_letter);
        }

        return $letters;
    }

    /**
     * 按地区搜索用户 --using
     *
     * @param
     *        	integer city_id 城市ID
     * @param
     *        	integer max_id 上次返回的最后一个用户ID
     * @param  string $count
     *                       数量
     * @return array  用户列表
     */
    public function search_by_city()
    {
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;
        $city_id = intval($this->data ['city_id']);
        if (! $city_id) {
            return array(
                    'status' => 0,
                    'msg' => '请选择地区',
            );
        }
        ! empty($max_id) && $map ['uid'] = array(
                'lt',
                $max_id,
        );
        $map ['city'] = $city_id;
        $map ['is_init'] = 1;
        $uids = model('User')->where($map)->order('uid desc')->field('uid')->limit($count)->findAll();
        $user_list = array();
        foreach ($uids as $k => $v) {
            $user_info = api('User')->get_user_info($v ['uid']);
            $user_list [$k] ['uid'] = $user_info ['uid'];
            $user_list [$k] ['uname'] = $user_info ['uname'];
            $user_list [$k] ['remark'] = $user_info ['remark'];
            $user_list [$k] ['avatar'] = $user_info ['avatar'] ['avatar_big'];
            $user_list [$k] ['intro'] = $user_info ['intro'] ? formatEmoji(false, $user_info ['intro']) : '';
            $user_list [$k] ['follow_status'] = model('Follow')->getFollowState($this->mid, $v ['uid']);
        }

        return $user_list;
    }

    /**
     * 获取认证分类 --using
     *
     * @return array 所有认证分类
     */
    public function get_user_verify()
    {
        $categoryTree = model('UserGroup')->where('is_authenticate=1')->field('user_group_id as verify_id,user_group_name as title')->findAll();
        foreach ($categoryTree as $k => $v) {
            $child = D('user_verified_category')->where('pid='.$v ['verify_id'])->field('user_verified_category_id,title')->findAll();
            if ($child) {
                foreach ($child as $k1 => $v1) {
                    $categoryTree [$k] ['child'] [$k1] ['verify_id'] = $v ['verify_id'].'_'.$v1 ['user_verified_category_id'];
                    $categoryTree [$k] ['child'] [$k1] ['title'] = $v1 ['title'];
                }
            } else {
                $categoryTree [$k] ['child'] = array();
            }
        }

        return $categoryTree;
    }

    /**
     * 按认证搜索用户 --using
     *
     * @param
     *        	integer verify_id 认证类型ID
     * @param
     *        	integer max_id 上次返回的最后一个ID
     * @param  string $count
     *                       数量
     * @return array  用户列表
     */
    public function search_by_verify()
    {
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;
        $verify_id = t($this->data ['verify_id']);
        if (! $verify_id) {
            return array(
                    'status' => 0,
                    'msg' => '请选择认证类型',
            );
        }

        $verify_arr = explode('_', $verify_id);
        $map ['usergroup_id'] = intval($verify_arr [0]);
        if ($verify_arr [1]) {
            $map ['user_verified_category_id'] = intval($verify_arr [1]);
        }
        ! empty($max_id) && $map ['id'] = array(
                'lt',
                $max_id,
        );
        $map ['verified'] = 1;
        $uids = D('user_verified')->where($map)->field('id,uid')->order('id desc')->limit($count)->findAll();
        $user_list = array();
        foreach ($uids as $k => $v) {
            $user_list [$k] ['id'] = $v ['id'];
            $user_info = api('User')->get_user_info($v ['uid']);
            $user_list [$k] ['uid'] = $user_info ['uid'];
            $user_list [$k] ['uname'] = $user_info ['uname'];
            $user_list [$k] ['remark'] = $user_info ['remark'];
            $user_list [$k] ['avatar'] = $user_info ['avatar'] ['avatar_big'];
            $user_list [$k] ['intro'] = $user_info ['intro'] ? formatEmoji(false, $user_info ['intro']) : '';
            $user_list [$k] ['follow_status'] = model('Follow')->getFollowState($this->mid, $v ['uid']);
        }

        return $user_list;
    }

    /**
     * 更新用户当前地理位置信息
     *
     * @return array
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function updateUserLocation()
    {
        /*
         * 纬度
         * @var float
         */
        $lat = floatval($this->data['latitude']);

        /*
         * 经度
         * @var float
         */
        $lng = floatval($this->data['longitude']);

        /* # 判断是否存在，存在则进行下一步否则添加信息 */
        if (!D('mobile_user')->where('`uid` = '.$this->mid)->field('uid')->count()) {
            $userData = model('User')->where('`uid` = '.$this->mid)->field('`uname`, `intro`, `sex`')->find();
            D('mobile_user')->add(array(
                'nickname' => $userData['uname'],
                'infomation' => $userData['intro'],
                'sex' => $userData['sex'],
                'uid' => $this->mid,
            ));

            return array(
                'status' => 1,
                'message' => '位置添加成功',
            );

        /* 判断是否更新成功 */
        } elseif (D('mobile_user')->where('`uid` = '.$this->mid)->save(array(
            'last_latitude' => $lat,
            'last_longitude' => $lng,
        ))) {
            return array(
                'status' => 1,
                'message' => '位置更新成功',
            );
        }

        return array(
            'status' => 0,
            'message' => '位置未改变',
        );
    }

    /**
     * 附近的人API
     *
     * @return array 附近的人列表
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function around()
    {
        /*
         * 纬度
         * @var float
         */
        $lat = floatval($this->data['latitude']);

        /*
         * 经度
         * @var float
         */
        $lng = floatval($this->data['longitude']);

        /*
         * 计算多大的范围，单位km
         * @var integer
         */
        $distance = 1;

        /*
         * 地球平均半径
         * @var float
         */
        $earthRadius = 6371.393;

        /*
         * 保证分页页码位于get全局变量
         */
        $_GET['p'] = $_REQUEST['p'];
        $_GET['p'] || $_GET['p'] = $this->data['page'];

        $dataNum = 20;

        $distanceSql = '( '.$earthRadius.' * acos( cos( radians('.$lat.') ) * cos( radians( last_latitude ) ) * cos( radians( last_longitude ) - radians('.$lng.') ) + sin( radians('.$lat.') ) * sin( radians( last_latitude ) ) ) )';

        $field = '`uid`, `last_latitude`, `last_longitude`, '.$distanceSql.' AS `distance`';

        $count = 'SELECT count(*) AS `num` FROM (SELECT '.$distanceSql.' AS `distance` FROM `%s` HAVING `distance` < %d) AS `table`';
        $count = sprintf($count, D('mobile_user')->getTableName(), $distance);
        $count = D()->query($count);
        $count = $count[0]['num'];

        $list = D('mobile_user')->having('`distance` < '.$distance)->order('`distance` ASC')->field($field)->findPage($dataNum, $count);

        foreach ($list['data'] as $key => $value) {
            /*
             * 用户数据
             * @var array
             */
            $userData = D('User')->getUserInfo($value['uid']);

            /*
             * 临时数据
             * @var array
             */
            $data = array();

            /*
             * 用户UID
             */
            $data['uid'] = $userData['uid'];

            /*
             * 用户名
             */
            $data['username'] = $userData['uname'];

            /*
             * 用户备注
             */
            $data['remark'] = $userData['remark'];

            /*
             * 用户距离
             */
            // $data['distance'] = $this->getDistinct($lat, $lng, $value['last_latitude'], $value['last_longitude']);
            $data['distance'] = intval($value['distance'] * 1000);

            /*
             * 用户头像
             */
            $data['avatar'] = $userData['avatar_big'];

            /*
             * 当前用户对该用户的关注状态
             */
            $data['followStatus'] = model('Follow')->getFollowState($this->mid, $userData['uid']);

            /*
             * 用户简介
             */
            $data['intro'] = formatEmoji(false, $userData['intro']);

            /*
             * 将临时数据替换为正式数据
             */
            $list['data'][$key] = $data;
        }
        unset($data, $userData, $value, $key, $list['html'], $list['totalRows']);

        /*
         * 返回数据
         */
        return $list;
    }

    /**
     * 获取用户与当前位置之间的距离 单位m
     *
     * @param  float  $nowLat  当前纬度
     * @param  float  $nowLng  当前经度
     * @param  float  $userLat 计算的用户纬度
     * @param  float  $userLng 计算的用户经度
     * @return string 单位数值
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    protected function getDistinct($nowLat, $nowLng, $userLat, $userLng)
    {
        $earthRadius = 6371393; //approximate radius of earth in meters

        /*
        Convert these degrees to radians
        to work with the formula
        */

        $nowLat = ($nowLat * pi()) / 180;
        $nowLng = ($nowLng * pi()) / 180;

        $userLat = ($userLat * pi()) / 180;
        $userLng = ($userLng * pi()) / 180;

        /*
        Using the
        Haversine formula

        http://en.wikipedia.org/wiki/Haversine_formula

        calculate the distance
        */

        $calcLongitude = $userLng - $nowLng;
        $calcLatitude = $userLat - $nowLat;

        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($nowLat) * cos($userLat) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = $earthRadius * $stepTwo;

        return round($calculatedDistance);
    }

    /**
     * 根据经纬度获取两点之间的距离 --using
     *
     * @param  float $myLat
     *                        纬度
     * @param  float $myLng
     *                        经度
     * @param  float $userLat
     *                        纬度
     * @param  float $userLng
     *                        经度
     * @return float 距离
     */
    // private function getDistinct($myLat, $myLng, $userLat, $userLng) {
    // 	$earthRadius = 6367000; // approximate radius of earth in meters
    // 	$lat1 = ($myLat * pi ()) / 180;
    // 	$lng1 = ($myLng * pi ()) / 180;
    // 	$lat2 = ($userLat * pi ()) / 180;
    // 	$lng2 = ($userLng * pi ()) / 180;
    // 	$calcLongitude = $lng2 - $lng1;
    // 	$calcLatitude = $lat2 - $lat1;
    // 	$stepOne = pow ( sin ( $calcLatitude / 2 ), 2 ) + cos ( $lat1 ) * cos ( $lat2 ) * pow ( sin ( $calcLongitude / 2 ), 2 );
    // 	$stepTwo = 2 * asin ( min ( 1, sqrt ( $stepOne ) ) );
    // 	$calculatedDistance = round ( $earthRadius * $stepTwo / 1000, 1 );
    // 	return $calculatedDistance . 'km';
    // }

    /**
     * 根据通讯录搜索用户 --using
     *
     * @param
     *        	string tel 以逗号连接的手机号码串
     * @return array
     */
    public function search_by_tel()
    {
        $tel_array = array_unique(array_filter(explode(',', $this->data ['tel'])));
        $data = array();
        $user_list = array();
        $user_list1 = array();
        if ($tel_array) {
            foreach ($tel_array as $k => $v) {
                if (preg_match("/^[1][3578]\d{9}$/", t($v)) !== 0) {
                    if ($uid = model('User')->where(array(
                            'phone' => t($v),
                    ))->getField('uid')) {
                        $user_info = api('User')->get_user_info($uid);
                        $user_list [$k] ['tel'] = $v;
                        $user_list [$k] ['uid'] = $user_info ['uid'];
                        $user_list [$k] ['uname'] = $user_info ['uname'];
                        $user_list [$k] ['remark'] = $user_info ['remark'];
                        $user_list [$k] ['avatar'] = $user_info ['avatar'] ['avatar_big'];
                        $user_list [$k] ['intro'] = $user_info ['intro'] ? formatEmoji(false, $user_info ['intro']) : '';
                        $user_list [$k] ['follow_status'] = model('Follow')->getFollowState($this->mid, $user_info ['uid']);
                    } else {
                        $user_list1 [$k] ['uid'] = 0;
                        $user_list1 [$k] ['tel'] = $v;
                    }
                }
            }
            $data = array_merge($user_list, $user_list1);
        }

        return $data;
    }
    public function top_ad()
    {
        $map ['place'] = 127;
        $map ['display_type'] = 3;

        $info = M('ad')->where($map)->order('display_order desc, ctime desc')->find();
        // dump(M()->getLastSql());
        $info ['content'] = unserialize($info ['content']);
        // 获取附件图片地址
        foreach ($info ['content'] as &$val) {
            $attachInfo = model('Attach')->getAttachById($val ['banner']);
            $val ['bannerpic'] = getImageUrl($attachInfo ['save_path'].$attachInfo ['save_name']);
        }
        // dump ( $info );

        return $info;
    }
}
