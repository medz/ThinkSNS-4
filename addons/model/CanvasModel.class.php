<?php
/**
 * 画布类
 * @author Stream
 *
 */
class CanvasModel extends Model
{
    protected $tableName = 'diy_canvas';

    protected $path;

    public function _initialize()
    {
        $this->path = CANVAS_PATH;
        parent::_initialize();
    }
    /**
     * 返回画布详细信息
     * @param  unknown_type $map
     * @param  unknown_type $fields
     * @return unknown
     */
    public function getCanvasInfo($map, $fields = 'id,canvas_name,title,data,description')
    {
        $data = $this->where($map)->field($fields)->find();
        $data['canvas_name'] = str_replace('.html', '', $data['canvas_name']);
        $data['data'] = base64_decode($data['data']);
        $data['data'] = $this->convert('lower', $data['data']);

        return $data;
    }
    /**
     * 返回画布列表
     * @param  int    $limit
     * @param  array  $map
     * @param  string $fields
     * @return array  or false
     */
    public function getCanvasList($limit = 20, $map, $fields = 'id,title,canvas_name,description')
    {
        $list = $this->where($map)->field($fields)->findPage($limit);

        return $list;
    }
    /**
     * 添加画布
     * @param  array $data
     * @return bool  or 1
     */
    public function addCanvas($data)
    {
        if (empty($data['title']) > 0) {
            $this->error = '画布标题不能为空';

            return false;
        }
        if (empty($data['canvas_name'])) {
            $this->error = '画布名称不能为空';

            return false;
        }
        if (empty($data['data'])) {
            $this->error = '画布内容不能为空';

            return false;
        }
        $map['canvas_name'] = $data['canvas_name'];
        $count = $this->where($map)->count();
        if ($count) {
            $this->error = '已存在相同的画布名称';

            return false;
        }
        $this->createhtml($data);
        $data['canvas_name'] = trim($data['canvas_name']).'.html';
        $data['data'] = base64_encode($data['data']);
        $res = $this->add($data);

        return $res;
    }
    /**
     * 修改画布
     * @param  array $data
     * @return bool  or 1
     */
    public function saveCanvas($data)
    {
        if (empty($data['title']) > 0) {
            $this->error = '画布标题不能为空';

            return false;
        }
        if (empty($data['canvas_name'])) {
            $this->error = '画布名称不能为空';

            return false;
        }
        if (empty($data['data'])) {
            $this->error = '画布内容不能为空';

            return false;
        }
        $map['canvas_name'] = $data['canvas_name'];
        $map['id'] = array('neq', $data['id']);
        $canvasname = $this->where($map)->getField('canvas_name');
        if ($canvasname) {
            $this->error = '已存在相同的画布名称';

            return false;
        }
        $data['data'] = $this->convert('upper', $data['data']);
        //如果修改了页面名字删除历史的缓存页面
// 		if ( $canvasname != trim( $data['name'] ) ){
// 			unlink( $this->path.$canvasname );
// 		}
        //生成缓存文件
        $this->createhtml($data);
        $data['canvas_name'] = trim($data['canvas_name']).'.html';
        $data['data'] = base64_encode($data['data']);
        $res = $this->where('id='.$data['id'])->save($data);

        return $res;
    }
    /**
     * 删除画布
     * @param  array $map
     * @return bool
     */
    public function deleteCanvas($map)
    {
        //删除对应文件
        $names = $this->where($map)->field('canvas_name')->findAll();

        foreach ($names as $n) {
            unlink($this->path.$n['canvas_name']);
        }
        //删除对应数据
        $res = $this->where($map)->delete();

        return $res;
    }
    private function convert($type, $data)
    {
        // 模板内容替换
        $replace = array(
            '__ROOT__' => '__root__',           // 当前网站地址
            '__UPLOAD__' => '__upload__',         // 上传文件地址
            '__PUBLIC__' => '__public__',         // 公共静态地址
            '__THEME__' => '__theme__',   // 主题静态地址
            '__APP__' => '__app__',     // 应用静态地址
        );
        if ($type == 'lower') {
            $data = str_replace(array_keys($replace), array_values($replace), $data);
        } else {
            $data = str_replace(array_values($replace), array_keys($replace), $data);
        }

        return $data;
    }
    /**
     * 创建静态画布
     * @param array $data
     */
    private function createhtml($data)
    {
        $filename = $this->path.$data['canvas_name'].'.html';
        file_put_contents($filename, $data['data']);
    }
    /**
     * 获取最后错误信息
     * @return string 最后错误信息
     */
    public function getLastError()
    {
        return $this->error;
    }
}
