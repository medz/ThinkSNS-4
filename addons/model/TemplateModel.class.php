<?php
/**
 * 模板管理模型 - 数据对象模型
 * @author daniel <desheng.young@gmail.com>
 * @version TS3.0
 */
class TemplateModel extends Model
{
    protected $tableName = 'template';

    /**
     * 获取模板列表
     * @param  array  $map   查询条件
     * @param  string $order 排序，默认'name ASC,tpl_id ASC'
     * @param  int    $limit 一次查询条数，默认30
     * @return array  模板列表
     */
    public function getTemplate($map = array(), $order = 'tpl_id DESC', $limit = 30)
    {
        $list = $this->where($map)->order($order)->findPage($limit);

        return $list;
    }

    /**
     * 通过模板ID获取模板信息
     * @param  integet $tplId 模板ID
     * @return array   模板ID获取模板信息
     */
    public function getTemplateById($tplId)
    {
        if (empty($tplId)) {
            return array();
        }
        $map['tpl_id'] = $tplId;
        $data = $this->where($map)->find();

        return $data;
    }

    /**
     * 按照模板名称查找模板
     * @param  string $name 模板名称
     * @return array  模板名称查找模板
     */
    public function getTemplateByName($name)
    {
        if (empty($name)) {
            return array();
        }
        $map['name'] = $name;
        $data = $this->where($name)->find();

        return $data;
    }

    /**
     * 添加模版操作
     * @param  array $data 模板相关数据
     * @return bool 是否添加成功
     */
    public function addTemplate($data)
    {
        if (empty($data)) {
            return false;
        }
        $data['ctime'] = empty($data['ctime']) ? time() : $data['ctime'];
        $result = $this->add($data);

        return (boolean) $result;
    }

    /**
     * 编辑模板操作
     * @param int $tplId 模板ID
     * @param  array $data  模板相关数据
     * @return bool 是否添加成功
     */
    public function upTemplate($tplId, $data)
    {
        if (empty($tplId) || empty($data)) {
            return false;
        }
        $map['tpl_id'] = $tplId;
        $result = $this->where($map)->save($data);

        return (boolean) $result;
    }

    /**
     * 删除指定模板ID的模板数据
     * @param  array|int $tplIds 模板ID数组，也可以是单个模板ID
     * @return bool      是否删除成功
     */
    public function delTemplate($tplIds)
    {
        $tplIds = is_array($tplIds) ? $tplIds : explode(',', $tplIds);
        if (empty($tplIds)) {
            return false;
        }
        $map['tpl_id'] = array('IN', $tplIds);
        $result = $this->where($map)->delete();

        return (boolean) $result;
    }

    /**
     * 删除模板
     * @param  string $where 可以是模板ID：template_ids或模板名称：names多个ID或名称是数组形式，也可用“,”分隔
     * @return bool   是否删除成功
     */
    public function deleteTemplate($where)
    {
        if (empty($where)) {
            return false;
        }

        $where = is_array($where) ? $where : explode(',', $where);
        if (is_numeric($where[0])) {
            $map['tpl_id'] = array('IN', $where);
        } elseif (is_string($where[0])) {
            $map['name'] = array('IN', $where);
        }
        if (empty($map)) {
            return false;
        }
        $result = $this->where($map)->delete();

        return (boolean) $result;
    }

    /**
     * 解析模板（将模板中变量替换成数据）
     * @param  string      $tpl_name    模板名称
     * @param  array       $data        模板中的变量和数据
     * @param  bool        $auto_record 是否添加模板记录
     * @return bool|string 模板解析的结果
     */
    public function parseTemplate($tpl_name, $data, $auto_record = null)
    {
        $map['name'] = $tpl_name;
        $template = $this->where($map)->find();
        if (!$template) {
            return false;
        }

        $auto_record = isset($auto_record) ? $auto_record : $template['is_cache'];
        $title = '';
        $body = '';

        // 标题模板
        if (!empty($template['title'])) {
            $keys = array_keys($data['title']);
            $values = array_values($data['title']);
            foreach ($keys as $k => $v) {
                $keys[$k] = '{'.$v.'}';
            }
            $template['title'] = str_replace($keys, $values, $template['title']);
            unset($keys, $values);
        }
        // 内容模板
        if (!empty($template['body'])) {
            $keys = array_keys($data['body']);
            $values = array_values($data['body']);
            foreach ($keys as $k => $v) {
                $keys[$k] = '{'.$v.'}';
            }
            $template['body'] = str_replace($keys, $values, $template['body']);
            unset($keys, $values);
        }

        //自动添加模板记录
        if ($auto_record) {
            $record_data['uid'] = isset($data['uid']) ? $data['uid'] : $_SESSION['mid'];
            $record_data['tpl_name'] = $template['name'];
            $record_data['tpl_alias'] = $template['alias'];
            $record_data['type'] = $template['type'];
            $record_data['type2'] = $template['type2'];
            $record_data['ctime'] = time();
            unset($template['tpl_id']);
            $record_data['data'] = serialize($template);

            return $this->addTemplateRecord($record_data);
        } else {
            return $template;
        }
    }

    /**
     * 添加模板记录
     * @param  array $data 模板的各种参数
     * @return bool  是否添加成功
     */
    public function addTemplateRecord($data)
    {
        $result = D('template_record')->add($data);

        return (boolean) $result;
    }

    /**
     * 查询模板记录
     * @param  array  $map   查询条件
     * @param  string $order 结果排序，默认'tpl_record_id DESC'
     * @param  int    $limit 查询条数，默认30
     * @return array  模板记录
     */
    public function getTemplateRecordByMap($map = array(), $order = 'tpl_record_id DESC', $limit = 30)
    {
        $result = D('template_record')->where($map)->order($order)->findPage($limit);
        foreach ($result['data'] as $key => $val) {
            $result['data'][$key]['data'] = unserialize($val['data']);
        }

        return $result;
    }
}
