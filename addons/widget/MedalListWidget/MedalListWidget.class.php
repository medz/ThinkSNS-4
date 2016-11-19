<?php
/**
 * 勋章列表前台展示
 * @author Stream
 *
 */
class MedalListWidget extends Widget
{
    public function render($data)
    {
        if (!CheckTaskSwitch()) {
            return;
        }
        // TODO Auto-generated method stub
        $map['uid'] = $data['uid'] ? $data['uid'] : $GLOBALS['ts']['mid'];
        $user_info = model('User')->getUserInfo($map['uid']);
        $medals = $user_info['medals'];
        if (!$medals) {
            return;
        }
        $medalids = getSubByKey($medals, 'id');
        $map['medal_id'] = array('in', $medalids);
        //加入缓存 如果勋章数目有变化的话 重新获取在缓存
        $key = 'medal_user_'.$map['uid'].'_'.count($medalids);
        $usermedal = model('Cache')->get($key);
        if (!$usermedal) {
            $umedal = D('medal_user')->where($map)->field('medal_id,`desc`,ctime')->findAll();
            $usermedal = array();
            foreach ($umedal as $u) {
                $usermedal[$u['medal_id']]['desc'] = $u['desc'];
                $usermedal[$u['medal_id']]['ctime'] = $u['ctime'];
            }
            model('Cache')->set($key, $usermedal);
        }
        foreach ($medals as &$m) {
            $usermedal[$m['id']]['desc'] && $m['desc'] = $usermedal[$m['id']]['desc'];
            $m['ctime'] = date('Y-m-d H:i:s', $usermedal[$m['id']]['ctime']);
        }
        $var['medals'] = $medals;
        $var['isme'] = $map['uid'] == $GLOBALS['ts']['mid'] ? true : false;
        $var['uid'] = $map['uid'];
        $content = $this->renderFile(dirname(__FILE__).'/list.html', $var);

        return $content;
    }
}
