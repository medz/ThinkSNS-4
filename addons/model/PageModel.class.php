<?php
/**
 * DIY页面类
 * @author Stream
 *
 */
class PageModel extends Model
{
    protected $tableName = 'diy_page';

    /**
     * 返回页面详细信息
     * @param  unknown_type $id
     * @param  unknown_type $field
     * @return unknown
     */
    public function getPageInfo($map, $field = 'id,page_name,domain,canvas,manager,status,guest,seo_title,seo_keywords,seo_description')
    {
        $data = $this->where($map)->field($field)->find();

        return $data;
    }
    /**
     * 返回页面列表
     * @param array  $map
     * @param string $field
     */
    public function getPageList($limit = 20, $map, $field = 'id,page_name,domain,canvas,manager,visit_count')
    {
        $list = $this->where($map)->field($field)->findPage($limit);

        return $list;
    }
    /**
     * 添加页面
     * @param  array $data
     * @return bool
     */
    public function addPage($data)
    {
        if (empty($data['page_name'])) {
            $this->error = '页面标题不能为空';

            return false;
        }
        if (empty($data['domain'])) {
            $this->error = '链接名称不能为空';

            return false;
        }
        $map['domain'] = $data['domain'];
        $exsit = $this->where($map)->count();
        if ($exsit) {
            $this->error = '已有相同的链接名称';

            return false;
        }
        $data['uid'] = $GLOBALS['ts']['mid'];
        $data['ctime'] = $_SERVER['REQUEST_TIME'];
        $res = $this->add($data);

        return $res;
    }
    /**
     * 修改页面
     * @param  array $data
     * @return bool
     */
    public function savePage($data)
    {
        if (empty($data['page_name'])) {
            $this->error = '页面标题不能为空';

            return false;
        }
        if (empty($data['domain'])) {
            $this->error = '链接名称不能为空';

            return false;
        }
        $map['domain'] = $data['domain'];
        $map['id'] = array('neq', $data['id']);
        $exsit = $this->where($map)->count();
        if ($exsit) {
            $this->error = '已有相同的链接名称';

            return false;
        }
        $res = $this->where('id='.$data['id'])->save($data);

        return $res;
    }
    /**
     * 删除页面
     * @param  array $map
     * @return bool
     */
    public function deletePage($map)
    {
        //判断是否有系统默认页面
        $map['lock'] = 1;
        $count = $this->where($map)->count();
        if ($count) {
            return 0;
        }
        unset($map['lock']);
        $res = $this->where($map)->delete();

        return $res;
    }
    /**
     * 保存页面数据
     */
    public function saveData($page, $layoutData, $widgetData)
    {
        $map['domain'] = $page;

        $save['layout_data'] = $layoutData;
        $save['widget_data'] = serialize($widgetData);
        $result = $this->where($map)->save($save);

        return $result;
    }
    /**
     * 返回管理员信息
     * @param  array    $map
     * @return Ambigous <返回新的一维数组, multitype:Ambigous <array, string> >
     */
    public function getManagers($map)
    {
        $list = $this->where($map)->field('manager')->findAll();

        return explode(',', implode(',', getSubByKey($list, 'manager')));
    }
    /**
     * 获取最后错误信息
     * @return string 最后错误信息
     */
    public function getLastError()
    {
        return $this->error;
    }
    /**
     * 浏览量
     * @param unknown_type $pageId
     */
    public function addReader($pageId)
    {
        $this->where('id='.$pageId)->setInc('visit_count');
    }
}
