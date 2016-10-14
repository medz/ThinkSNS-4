<?php

use Ts\Models\Department as DepModel;

/**
 * 菜单选择Widget
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class MenuWidget extends Widget
{
    /**
     * 模板渲染
     * @param  array  $data 相关数据
     * @return string 用户身份选择模板
     */
    public function render($data)
    {
        // 设置模板
        $template = empty($data['type']) ? 'tag' : strtolower(t($data['type']));
        // 获取相关数据
        $var['cid'] = intval($data['cid']);
        $var['area'] = intval($data['area']);
        $var['unit'] = intval($data['unit']);
        $var['sex'] = intval($data['sex']);
        $var['verify'] = intval($data['verify']);
        $var['type'] = t($data['type']);
        // 获取一级分类
        switch ($var['type']) {
            case 'tag':
                // 父级选中ID
                $pid = model('UserCategory')->where('user_category_id='.$var['cid'])->getField('pid');
                $var['pid'] = ($pid == 0) ? $var['cid'] : $pid;
                $var['menu'] = model('UserCategory')->getNetworkList();
                break;
            case 'area':
                $var['allProvince'] = model('CategoryTree')->setTable('area')->getNetworkList();
                //上级id
                $cate = D('area')->where('area_id='.$var['area'])->find();
                if ($cate['pid'] != 0) {
                    $var['parent1'] = D('area')->where('area_id='.$cate['pid'])->find();  //上级
                    if ($var['parent1']['pid'] != 0) {
                        $var['parent2'] = D('area')->where('area_id='.$var['parent1']['pid'])->find(); //上上级
                    } else {
                        $var['parent2']['title'] = $var['parent1']['title'];
                        $var['parent2']['area_id'] = $var['parent1']['area_id'];
                        $var['parent1']['title'] = $cate['title'];
                        $var['parent1']['id'] = $cate['area_id'];
                    }
                } else {
                    $var['parent1']['title'] = $cate['title'];
                    $var['parent1']['id'] = $cate['area_id'];
                }

                //下级
                if ($var['area'] != 0) {
                    $var['city'] = model('CategoryTree')->setTable('area')->getNetworkList($var['area']);
                    if (!$var['city']) {
                        $var['city'] = model('CategoryTree')->setTable('area')->getNetworkList($cate['pid']);
                    }
                }

                break;
            case 'verify':
                // $var['menu'] = model('CategoryTree')->setTable('user_verified_category')->getNetworkList();
                $var['menu'] = model('UserGroup')->where('is_authenticate=1')->findAll();
                foreach ($var['menu'] as $k => $v) {
                    $var['menu'][$k]['child'] = D('user_verified_category')->where('pid='.$v['user_group_id'])->findAll();
                }
                $var['pid'] = intval($data['pid']);
                break;
            case 'official':
                $var['menu'] = model('CategoryTree')->setTable('user_official_category')->getNetworkList();
                break;
            case 'unit':
                /*$var['menu'] = model('Department')->getAllHash();
                foreach ($var['menu'] as $key => &$value) {
                    $value['id'] = $value['department_id'];
                    unset($value['department_id']);
                }*/
                $var['allUnit'] = model('Department')->getDepartment(1)['_child'];
                //上级id
                $cate = DepModel::where('department_id', $var['unit'])->first();
                $cate = $cate ? $cate->toArray() : [];

                if ($cate['parent_dept_id'] != 1) {
                    $parent1 = DepModel::where('department_id', $cate['parent_dept_id'])->first(); //上级

                    $var['parent1'] = $parent1 ? $parent1->toArray() : [];
                    if ($var['parent1']['parent_dept_id'] != 1) {
                        $parent2 = DepModel::where('department_id', $var['parent1']['parent_dept_id'])->first(); //上上级
                        $var['parent2'] = $parent2 ? $parent2->toArray() : [];
                    } else {
                        $var['parent2']['title'] = $var['parent1']['title'];
                        $var['parent2']['department_id'] = $var['parent1']['department_id'];
                        $var['parent1']['title'] = $cate['title'];
                        $var['parent1']['id'] = $cate['department_id'];
                    }
                } else {
                    $var['parent1']['title'] = $cate['title'];
                    $var['parent1']['id'] = $cate['department_id'];
                }

                //下级
                if ($var['unit'] != 1) {
                    $next = DepModel::where('parent_dept_id', $var['unit'])->get();
                    $var['next'] = $next ? $next->toArray() : [];
                    if (!$var['unit']) {
                        $next = DepModel::where('parent_dept_id', $cate['parent_dept_id'])->get();
                        $var['next'] = $next ? $next->toArray() : [];
                    }
                }
                break;
        }

        // 渲染模版
        $content = $this->renderFile(dirname(__FILE__).'/'.$template.'.html', $var);
        // 输出数据
        return $content;
    }
}
