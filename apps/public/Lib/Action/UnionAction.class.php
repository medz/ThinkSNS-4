<?php
/**
 * 联盟控制器.
 *
 * @author chenweichuan <chenweichuan@zhishisoft.com>
 *
 * @version TS3.0
 */
class UnionAction extends Action
{
    private $_union_model = null; // 联盟模型对象字段

    /**
     * 初始化控制器，实例化联盟模型对象
     */
    protected function _initialize()
    {
        $this->_union_model = model('Union');
    }

    /**
     * 添加联盟操作.
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function doUnion()
    {
        // 安全过滤
        $fid = intval($_POST['fid']);
        $res = $this->_union_model->doUnion($this->mid, intval($fid));
        $this->ajaxReturn($res, $this->_union_model->getError(), false !== $res);
    }

    /**
     * 取消联盟操作.
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function unUnion()
    {
        // 安全过滤
        $fid = intval($_POST['fid']);
        $res = $this->_union_model->unUnion($this->mid, $fid);
        $this->_union_model->unUnion($fid, $this->mid);
        $this->ajaxReturn($res, '操作成功', false !== $res);
    }

    /**
     * 添加联盟操作.
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function doAgree()
    {
        // 安全过滤
        $fid = intval($_POST['uid']);
        $res = $this->_union_model->doUnion($this->mid, $fid);

        // 自动相互关注
        $fids[] = $fid;
        model('Follow')->eachDoFollow($this->mid, $fids);

        $this->ajaxReturn($res, $this->_union_model->getError(), false !== $res);
    }

    /**
     * 取消联盟操作.
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function doRefuse()
    {
        // 安全过滤
        $fid = intval($_POST['uid']);
        $res = $this->_union_model->unUnion($fid, $this->mid);
        $this->ajaxReturn($res, $this->_union_model->getError(), false !== $res);
    }

    /**
     * 批量添加联盟操作.
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function bulkDoUnion()
    {
        // 安全过滤
        $res = $this->_union_model->bulkDoUnion($this->mid, t($_POST['fids']));
        $this->ajaxReturn($res, $this->_union_model->getError(), false !== $res);
    }
}
