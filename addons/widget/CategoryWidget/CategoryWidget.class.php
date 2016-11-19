<?php
/**
 * 分类 widget
 * @example W('Category',array('app_name'=>'support','model_name'=>'SupportCategory','method'=>'getEnCate','tpl'=>'select','id'=>4,'inputname'=>'category_en'))
 * @author Jason
 * @version TS3.0
 */
class CategoryWidget extends Widget
{
    /*
      * @param app_name 此分类所在的应用名称
      * @param model_name 分类model名称
      * @param method 分类model中根据某个id获取对应分类信息的函数
      * @param tpl 显示模版：分select和menu两种，select为选择分类形式，menu为菜单形式
      * @param id 当前分类ID
      * @param inputname 分类存储的input字段名称，只针对tpl=select 有效
      * @param title 自定义点击项的名称，只针对tpl=select有效
      * @param cate_url 分类的URL前缀，针对tpl=menu或者tpl=two有效
      * @param pid 当前分类的父级ID，针对tpl=menu有效
     */
/*	public function render($data) {
        $var['tpl'] = 'select';
        $var['id'] = $var['pid'] = 0;
        $var['inputname'] = 'category';
        is_array($data) && $var = array_merge($var,$data);

        switch($var['tpl']){
            case 'select':
                return $this->select($var);
                break;
            case 'menu':
                return $this->menu($var);
                break;
            case 'two':
                return $this->two($var);
                break;
            case 'twochecked':
                return $this->twochecked($var);
                break;
            case 'twonums':
                return $this->twonums($var);
                break;
            case 'twofloat':
                return $this->twofloat($var);
                break;
        }
    }*/

    /*
     * 选择分类
     */
/*	private function select($data){
        $model = D(ucfirst($data['model_name']),$data['app_name']);
        $data['catePath'] = $model->getCatePathById($data['id'],$data['method']);
        $content = $this->renderFile (dirname(__FILE__)."/".$data['tpl'].'.html', $data );
        return $content;
    }*/

    /*
     * 收藏分类
     */
/*	private function menu($data){
        !isset($data['title']) && $data['title'] = L('PUBLIC_ALL_CATEGORIES');
        $model = D(ucfirst($data['model_name']),$data['app_name']);

        $data['id'] = !empty($data['id']) ? intval($data['id']) : 0;

        $list  = $model->$data['method']($data['id'],true);

        $data['pid'] = $pid = $list['pid'];

        $pInfo[] = array('id'=>0,'name'=>$data['title'],'pid'=>0);
        $childInfo = array();

        if($pid == 0){
            if(!empty($list['child']) && $data['id']!=0){
                $pInfo[] = array('id'=>$list['id'],'name'=>$list['name'],'pid'=>0);
                foreach($list['child'] as $k=>$v){
                    $childInfo[] = array('id'=>$k,'name'=>$v,'pid'=>$data['id']);
                }
            }else{
                $list = $model->$data['method'](0,true);
                foreach($list['child'] as $k=>$v){
                    $childInfo[] = array('id'=>$k,'name'=>$v,'pid'=>0);
                }
            }
        }else{
            $pdepart =  $model->$data['method']($pid,true);
            if(!empty($list['child'])){
                $pInfo[] = array('id'=>$list['id'],'name'=>$list['name'],'pid'=>$pid);
                foreach($list['child'] as $k=>$v){
                    $childInfo[] = array('id'=>$k,'name'=>$v,'pid'=>$data['id']);
                }
            }else{
                $pInfo[] = array('id'=>$pdepart['id'],'name'=>$pdepart['name'],'pid'=>$pdepart['pid']);
                foreach($pdepart['child'] as $k=>$v){
                    $childInfo[] = array('id'=>$k,'name'=>$v,'pid'=>$pdepart['pid']);
                }
            }

        }

        $data['pInfo'] = $pInfo;
        $data['childInfo'] = $childInfo;

        $content = $this->renderFile (dirname(__FILE__)."/".$data['tpl'].'.html', $data );
        return $content;
    }*/

    /*
     * 两级分类
     */
/*	private function two($data){
        $model = D(ucfirst($data['model_name']),$data['app_name']);
        $data['cateList'] = $model->$data['method'](0);
        $content = $this->renderFile (dirname(__FILE__)."/".$data['tpl'].'.html', $data );
        return $content;
    }
*/
    /*
     * 弹出窗
     */
/*	public function selectBox(){
        $data = $this->getData();
        $model = D(ucfirst($data['model_name']),$data['app_name']);
        $pids = $model->getPidsById($data['id']);
        if(empty($pids)) $pids = array(0);
        foreach($pids as $v){
            $select = $model->$data['method']($v,true);
            $data['select'][$v] = $select['child'];
        }
        $data['pids'] = $pids;
        $content = $this->renderFile (dirname(__FILE__).'/selectbox.html', $data );
        return $content;
    }*/

    /*
     * 获取某级下面的子集
     */
/*	public function getChild(){
        $data = $this->getData();
        $model = D(ucfirst($data['model_name']),$data['app_name']);
        $select = $model->$data['method']($data['id'],true);
        if(!empty($select['child'])){
            echo json_encode(array('data'=>$select['child'],'status'=>1));//表示没有数据
        }else{
            echo json_encode(array('data'=>'','status'=>0));//表示没有数据
        }
        exit();
    }*/

    /*
     * getCatePathById方法不存在？？？
     * @return 分类地址
     */
/*	public function getCatePath(){
        $data = $this->getData();
        $model = D(ucfirst($data['model_name']),$data['app_name']);
        $catePath = $model->getCatePathById($data['id'],$data['method']);
        echo  implode($catePath,' <span class="symbol-next">»</span> ');
        die();
    }*/

    /*
     * 两级分类选择模板
     */
/*	private function twochecked($data) {
        $data = $this->getData($data);
        $model = D(ucfirst($data['model_name']),$data['app_name']);
        $data['cateList'] = $model->$data['method'](0);
        $content = $this->renderFile(dirname(__FILE__)."/".$data['tpl'].'.html', $data);
        return $content;
    }*/

    /*
     * 两级分类带统计数目
     */
/*	private function twonums($data) {
        $data = $this->getData($data);
        $cid = empty($data['cid']) ? 0 : intval($data['cid']);
        $model = D(ucfirst($data['model_name']),$data['app_name']);
        $data['cateList'] = $model->$data['method']($cid);
        $content = $this->renderFile(dirname(__FILE__)."/".$data['tpl'].'.html', $data);
        return $content;
    }*/

    /*
     * 浮动两级分类模板
     */
/*	private function twofloat($data) {
        $model = D(ucfirst($data['model_name']),$data['app_name']);
        $data['cateList'] = $model->$data['method'](0);
        $content = $this->renderFile(dirname(__FILE__)."/".$data['tpl'].'.html', $data);
        return $content;
    }*/

    /*
     * 数据安全过滤
     */
/*	private function getData($data = null){
        if(!$data)
            $data = array();
        else
            $data = $_GET;
        foreach ($data as $key => $value) {
            if(preg_match('/^[a-zA-Z0-9_]+$/i',$value)){
                $cleandata[$key] = t($value);
            }
        }
        return $cleandata;
    }*/
}
