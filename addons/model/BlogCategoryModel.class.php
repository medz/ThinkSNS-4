<?php
/**
 * BlogCategoryModel
 * 知识分类model
 * @uses Model
 * @package
 * @version $id$
 * @copyright 2009-2011 SamPeng
 * @author SamPeng <sampeng87@gmail.com>
 * @license PHP Version 5.2 {@link www.sampeng.cn}
 */
class BlogCategoryModel extends BaseModel
{
    /**
     * getCategory
     * 获取所有分类
     */
    public function getCategory($uid = 0)
    {
        //先从缓存里面获取
        $result = $this->where("`uid`=$uid".' and pid=0')->field('name,uid,id,pid')->findAll();
        //重组数据集结构
        $newresult = array();
        foreach ($result as $value) {
            $newresult[$value['id']]['id'] = $value['id'];
            $newresult[$value['id']]['name'] = $value['name'];
            $newresult[$value['id']]['uid'] = $value['uid'];
            $newresult[$value['id']]['child'] = $res = $this->where('pid='.$value['id'])->field('name,uid,id')->select();
        }

        return $newresult;
    }

    public function isCategoryExist($name, $uid = 0, $gid = 0)
    {
        $map['name'] = $name;
        $map['uid'] = array('in', "$uid,0");
        $map['id'] = array('neq', $gid);

        return $this->where($map)->find() ? true : false;
    }

    public function isCategoryEmpty($gid)
    {
        return M('blog')->where('`category`='.intval($gid))->find() ? false : true;
    }

    /**
     * addCategory
     * 增加分类
     * @param mixed $map
     */
    public function addCategory($map, $dao = null)
    {
        //检查是否为空
        $this->__equalTrueEchoMsg(empty($map['name']), -3);
        //检测是否有重复的分类
        $this->__checkCategory($map, $dao);
        $map = $this->merge($map);
        $result = $this->add($map);
        $this->__issetQueryToMsg($result, -1);
    }

    private function __checkCategory($data, $dao)
    {
        global $ts;
        $category = $this->getCategory($ts['user']['uid']);
        foreach ($category as $value) {
            $this->__equalTrueEchoMsg($value['name'] == $data['name'], -2);
        }
    }
    private function __equalTrueEchoMsg($query, $msg)
    {
        if (true === $query) {
            echo $msg;
            exit();
        }
    }

    private function __issetQueryToMsg($query, $msg)
    {
        if (isset($query)) {
            echo $query;
        } else {
            echo $msg;
        }
        exit();
    }

    /**
     * deleteCategory
     * 删除分类
     * @param mixed $map
     */
    public function deleteCategory($map, $formCate = null, $obj = null)
    {
        //先判断合法性

        if (empty($map)) {
            throw new ThinkException('不能是空条件删除');
        }
        //转移被删分类下的知识到默认分类
        if (isset($formCate)) {
            $result = $obj->setField('category', $formCate, 'category = '.$map['id']);

            $category_title = M('blog_category')->where("`id`={$formCate}")->getField('name');
            $obj->setField('category_title', $category_title, "`category`={$formCate}");
        } else {
            //删除分类下的所有知识
            $obj->where('category = '.$map['id'])->delete();
        }

        //删除分类
        return $this->where($map)->delete();
    }
    public function deleteCategoryForId($map)
    {
        //先判断合法性
        if (empty($map)) {
            throw new ThinkException('不能是空条件删除');
        }
        //转移被删分类下的知识到默认分类
//        $obj=D("Blog");
//        if( isset( $formCate ) ) {
//
//            $result = $obj->setField( 'category',$formCate,'category = '.$map['id'] );
//        }else {
//        //删除分类下的所有知识
//            $obj->where( 'category = '.$map['id'] )->delete();
//        }

        //删除分类
        return $this->where($map)->delete();
    }
    /**
     * editCategory
     * 编辑分类
     * @param mixed $map
     */
    public function editCategory($data)
    {
        foreach ($data as $key => $value) {
            $map1[] = "`id` = $key";
            $map2[] = "WHEN `id` = $key THEN '$value'";
        }
        $case = implode(' ', $map2);
        $where = implode(' or ', $map1);

        $sql = 'UPDATE `'.C('DB_PREFIX')."blog_category`
                    SET `name` = (case {$case} end)
                    WHERE {$where} ";
        $query = $this->execute($sql);

        return $query;
    }

    /**
     * getCategoryName
     * 通过id获得名字
     * @param mixed $id
     */
    public function getCategoryName($id)
    {
        $map['id'] = $id;
        $result = $this->where($map)->field('name')->find();

        return $result['name'];
    }

    /**
     * getUserCategory
     * 获得用户的分类
     * @param mixed $uid
     */
    public function getUserCategory($uid)
    {
        $map['uid'] = array('in', "$uid,0");
        $result = $this->where($map)->field('name,id,uid')->order('`uid` ASC, id ASC')->findAll();

        return $result;
    }
}
