<?php
/**
 * 筛选类型Widget.
 *
 * @author zivss <guolee226@gmail.com>
 *
 * @version TS3.0
 */
class FilterWidget extends Widget
{
    /**
     * 模板渲染.
     *
     * @param array $data 相�
     * �数据
     *
     * @return string 用户身份选择模板
     */
    public function render($data)
    {
        // 设置模板
        $template = empty($data['tpl']) ? 'filter' : t($data['tpl']);
        // 获取相关数据
        $var['cid'] = intval($data['cid']);
        $var['area'] = intval($data['area']);
        $var['sex'] = intval($data['sex']);
        $var['verify'] = intval($data['verify']);
        $var['type'] = t($data['type']);
        // 获取相应类型
        switch ($var['type']) {
            case 'tag':
                $var['areaList'] = model('Area')->getNetworkList();
                $path = array();
                // 获取面包屑
                $path[] = '标签';
                if ($var['cid'] == 0) {
                    $path[] = '<a href="'.U('people/Index/index', array('cid' => $var['cid'], 'area' => $var['area'], 'sex' => $var['sex'], 'verify' => $var['verify'], 'type' => $var['type'])).'">全部</a>';
                } else {
                    $category = model('UserCategory')->where('user_category_id='.$var['cid'])->find();
                    if ($category['pid'] != 0) {
                        $pCategory = model('UserCategory')->where('user_category_id='.$category['pid'])->find();
                        $path[] = '<a href="'.U('people/Index/index', array('cid' => $pCategory['user_category_id'], 'area' => $var['area'], 'sex' => $var['sex'], 'verify' => $var['verify'], 'type' => $var['type'])).'">'.$pCategory['title'].'</a>';
                    }
                    $path[] = '<a href="'.U('people/Index/index', array('cid' => $var['cid'], 'area' => $var['area'], 'sex' => $var['sex'], 'verify' => $var['verify'], 'type' => $var['type'])).'">'.$category['title'].'</a>';
                }
                $var['path'] = implode('&nbsp;&gt;&nbsp;', $path);
                break;
            case 'area':
                $var['tag'] = model('UserCategory')->getNetworkList();
                // 获取面包屑
                $path = array();
                $path[] = '地区';
                if ($var['area'] == 0) {
                    $path[] = '<a href="'.U('people/Index/index', array('cid' => $var['cid'], 'area' => $var['area'], 'sex' => $var['sex'], 'verify' => $var['verify'], 'type' => 'area')).'">全部</a>';
                } else {
                    $pid = model('Area')->where('area_id='.$var['area'])->find();
                    if ($pid['pid'] != 0) {
                        $ppid = model('Area')->where('area_id='.$pid['pid'])->getField('pid');
                        $ppInfo = model('Area')->where('area_id='.$ppid)->find();
                        if ($ppInfo) {
                            $path[] = '<a href="'.U('people/Index/index', array('cid' => $var['cid'], 'area' => $ppInfo['area_id'], 'sex' => $var['sex'], 'verify' => $var['verify'], 'type' => 'area')).'">'.$ppInfo['title'].'</a>';
                        }
                    }
                    $name = model('Area')->where('area_id='.$pid['pid'])->getField('title');
                    if ($name) {
                        $path[] = '<a href="'.U('people/Index/index', array('cid' => $var['cid'], 'area' => $pid['pid'], 'sex' => $var['sex'], 'verify' => $var['verify'], 'type' => 'area')).'">'.$name.'</a>';
                    }

                    if ($pid) {
                        $pInfo = model('Area')->where('area_id='.$var['area'])->find();
                        $path[] = '<a href="'.U('people/Index/index', array('cid' => $var['cid'], 'area' => $var['area'], 'sex' => $var['sex'], 'verify' => $var['verify'], 'type' => 'area')).'">'.$pInfo['title'].'</a>';
                    }
                }
                $var['path'] = implode('&nbsp;&gt;&nbsp;', $path);
                break;
        }

        // 渲染模版
        $content = $this->renderFile(dirname(__FILE__).'/'.$template.'.html', $var);
        // 输出数据
        return $content;
    }
}
