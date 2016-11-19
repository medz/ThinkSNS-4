<?php
/**
 * 用户信息显示Widget
 * @author guolee226@gmail.com
 * @version TS3.0
 */
class UserInformationWidget extends Widget
{
    /**
     * 模板渲染
     * @param  array  $data 相关数据
     * @return string 用户身份选择模板
     */
    public function render($data)
    {
        $var['uid'] = intval($data['uid']);
        $var['tpl'] = t($data['tpl']);
        // 是否有返回首页的链接
        $var['isReturn'] = $data['isReturn'] ? true : false;
        // 获取用户信息
        $var['userInfo'] = model('User')->getUserInfo($var['uid']);
        // 获取用户用户组信息
        $userGids = model('UserGroupLink')->getUserGroup($var['uid']);
        $userGroupData = model('UserGroup')->getUserGroupByGids($userGids[$var['uid']]);
        foreach ($userGroupData as $key => $value) {
            if ($value['user_group_icon'] == -1) {
                unset($userGroupData[$key]);
                continue;
            }
            $userGroupData[$key]['user_group_icon_url'] = THEME_PUBLIC_URL.'/image/usergroup/'.$value['user_group_icon'];
        }
        $var['userGroupData'] = $userGroupData;
        // 获取相关的统计数目
        $var['userData'] = model('UserData')->getUserData();
        foreach ($var['userData'] as &$value) {
            $value = $this->limitedNumbers($value, 99999);
        }
        // 获取用户积分信息
        $var['userCredit'] = model('Credit')->getUserCredit($var['uid']);
        // Tab选中类型
        $var['current'] = '';
        strtolower(ACTION_NAME) == 'myfeed' && strtolower(MODULE_NAME) == 'index' && $var['current'] = 'myfeed';
        strtolower(ACTION_NAME) == 'following' && strtolower(MODULE_NAME) == 'index' && $var['current'] = 'following';
        strtolower(ACTION_NAME) == 'follower' && strtolower(MODULE_NAME) == 'index' && $var['current'] = 'follower';
        strtolower(ACTION_NAME) == 'index' && strtolower(MODULE_NAME) == 'collection' && $var['current'] = 'collection';
        // 用户分类信息
        $map['app'] = 'public';
        $map['table'] = 'user';
        $map['row_id'] = $var['uid'];
        if ($var['tpl'] === 'top') {
            $var['userTags'] = D()->table(C('DB_PREFIX').'app_tag AS a LEFT JOIN '.C('DB_PREFIX').'tag AS b ON a.tag_id = b.tag_id')
                                  ->where($map)
                                  ->findAll();
        }
        // 获取关注状态
        $GLOBALS['ts']['mid'] != $var['uid'] && $var['follow_state'] = model('Follow')->getFollowState($GLOBALS['ts']['mid'], $var['uid']);

        // 渲染模版
        $content = $this->renderFile(dirname(__FILE__).'/'.$var['tpl'].'.html', $var);
        // 输出数据
        return $content;
    }

    /**
     * 将统计数据限定指定的数目
     * @param int $nums  指定的数目
     * @param int $limit 限定的数目
     */
    private function limitedNumbers($nums, $limit = 99999)
    {
        $nums > $limit && $nums = $limit.'+';

        return $nums;
    }
}
