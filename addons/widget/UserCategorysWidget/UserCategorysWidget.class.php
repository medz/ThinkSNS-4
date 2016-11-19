<?php
/**
 * 用户身份选择Widget
 *注册插件
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class UserCategorysWidget extends Widget
{
    /**
     * 模板渲染
     * @param  array  $data 相关数据
     * @return string 用户身份选择模板
     */
    public function render($data)
    {
        // 设置模板
        $template = empty($data['tpl']) ? 'category' : t($data['tpl']);
        // 选择模板数据
        switch ($template) {
            case 'pop':
                $var = $this->_login($data);
                $var['required'] = isset($data['required']) ? $data['required'] : true;
                break;
            case 'category':
                $var = $this->_login($data);
                break;
            case 'userCategory':
                $var = $this->_user($data);
                break;
        }
        $var['callback'] = $data['callback'];
        // 渲染模版
        $content = $this->renderFile(dirname(__FILE__).'/'.$template.'.html', $var);
        // 输出数据
        return $content;
    }

    /**
     * 添加用户与用户身份的关联信息
     */
    public function addRelatedUser()
    {
        $uid = intval($_POST['uid']);
        $cid = intval($_POST['cid']);
        $res = model('UserCategory')->addRelatedUser($uid, $cid);
        $result['status'] = $res ? 1 : 0;
        exit(json_encode($result));
    }

    /**
     * 删除用户与用户身份的关联信息
     */
    public function deleteRelateUser()
    {
        $uid = intval($_POST['uid']);
        $cid = intval($_POST['cid']);
        $res = model('UserCategory')->deleteRelatedUser($uid, $cid);
        $result['status'] = $res ? 1 : 0;
        exit(json_encode($result));
    }

    /**
     * 登录页面，获取选择模板数据
     * @param  array $data 参数数据
     * @return array 获取的模板数据
     */
    private function _login($data)
    {
        // 获取用户分类信息
        $uid = intval($data['uid']);
        $var['uid'] = $uid;
        $var['selected'] = model('UserCategory')->getRelatedUserInfo($uid);
        !empty($var['selected']) && $var['selectedIds'] = getSubByKey($var['selected'], 'user_category_id');
        $var['nums'] = count($var['selectedIds']);
        //$var['categoryTree'] = model('UserCategory')->getNetworkList();
        $var['categoryTree'] = model('CategoryTree')->setTable('user_category')->getNetworkList();
        foreach ($var['categoryTree'] as $key => $value) {
            if (empty($value['child'])) {
                unset($var['categoryTree'][$key]);
            }
        }

        return $var;
    }

    /**
     * 人物分类页面，获取选择模板数据
     * @param  array $data 参数数据
     * @return array 获取的模板数据
     */
    public function _user($data)
    {
        // 获取跳转链接
        !empty($data['url']) && $var['url'] = t($data['url']);
        // 获取分类ID
        !empty($data['cid']) && $var['cid'] = intval($data['cid']);
        // 获取用户分类信息
        $uid = intval($data['uid']);
        $var['uid'] = $uid;
        $var['selected'] = model('UserCategory')->getRelatedUserInfo($uid);
        $var['selectedIds'] = getSubByKey($var['selected'], 'user_category_id');
        $var['nums'] = count($var['selectedIds']);
        $var['categoryTree'] = model('UserCategory')->getNetworkList();
        foreach ($var['categoryTree'] as $key => $value) {
            if (empty($value['child'])) {
                unset($var['categoryTree'][$key]);
            }
        }
        $aCids = getSubByKey($var['categoryTree'], 'id');
        if (!in_array($var['cid'], $aCids)) {
            $map['user_category_id'] = $var['cid'];
            $var['childCid'] = $var['cid'];
            $var['cid'] = model('UserCategory')->where($map)->getField('pid');
        }

        return $var;
    }

    public function show()
    {
        $var = $this->_login($data);

        $config = model('Xdata')->get('admin_Config:register');
        $var['tag_num'] = $config['tag_num'];

        $var['selectedIds'] = explode(',', t($_GET['selected']));
        $var['selectedIds'] = array_filter($var['selectedIds']);
        $var['selectedIds'] = array_unique($var['selectedIds']);
        $var['nums'] = count($var['selectedIds']);

        $content = $this->renderFile(dirname(__FILE__).'/show.html', $var);

        return $content;
    }
}
