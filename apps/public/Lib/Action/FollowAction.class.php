<?php
/**
 * �
 * �注控制器.
 *
 * @author chenweichuan <chenweichuan@zhishisoft.com>
 *
 * @version TS3.0
 */
class FollowAction extends Action
{
    private $_follow_model = null;         // 关注模型对象字段

    /**
     * 初始化控制器，实例化�
     * �注模型对象
     */
    protected function _initialize()
    {
        $this->_follow_model = model('Follow');
    }

    /**
     * 添加�
     * �注操作.
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function doFollow()
    {
        // 安全过滤
        // $fid = t($_POST['fid']);
        $fid = intval($_POST['fid']);
        $res = $this->_follow_model->doFollow($this->mid, intval($fid));
        $this->ajaxReturn($res, $this->_follow_model->getError(), false !== $res);
    }

    /**
     * 取消�
     * �注操作.
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function unFollow()
    {
        // 安全过滤
        $fid = t($_POST['fid']);
        $res = $this->_follow_model->unFollow($this->mid, intval($fid));
        $this->ajaxReturn($res, $this->_follow_model->getError(), false !== $res);
    }

    /**
     * 批量添加�
     * �注操作.
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function bulkDoFollow()
    {
        // 安全过滤
        $res = $this->_follow_model->bulkDoFollow($this->mid, t($_POST['fids']));
        $this->ajaxReturn($res, $this->_follow_model->getError(), false !== $res);
    }
}
