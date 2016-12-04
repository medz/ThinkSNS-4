<?php
/**
 * 意见反馈模型 - 数据对象模型
 *
 * @package ThinkSNS\Model\Feedback
 * @author Medz Seven <lovevipdsw@vip.qq.com>
 **/
class FeedbackModel extends Model
{
    /**
     * 模型数据表名称
     *
     * @var string
     **/
    protected $tableName = 'feedback';

    /**
     * 数据模型保护字段
     *
     * @var array
     **/
    protected $fields = array('id', 'content', 'uid', 'cTime', 'mTime', 'type', '_autoinc' => true, '_pk' => 'id');

    /*======================== Type 字段公约 Start ===========================*/
    /* # 有添加，请加入公约说明 */
    /* # type = 1 移动端APP反馈 */

    /**
     * 类型名称对应
     *
     * @var array
     **/
    protected $types = array(
        1 => '移动端APP反馈',
    );

    /*======================== Type 字段公约 End   ===========================*/

    /**
     * 类型模型
     *
     * @var object
     **/
    protected $typeModel;

    /**
     * 初始化模型
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    protected function _initialize()
    {
        /* # 获取反馈分类模型 */
        $this->typeModel = D('feedback_type');
    }

    /**
     * 以查询的方式添加类型
     *
     * @return int 类型ID
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    protected function selectAddType($type)
    {
        /* # 检查是否是合法的类型 */
        if (!isset($this->types[$type]) or !$this->types[$type]) {
            $this->_error = '保护字段不合法';

            return false;

        /* # 查询类型ID or 添加 */
        } elseif (
            !($typeID = $this->typeModel->where('`type_name` LIKE \''.$this->types[$type].'\'')->field(`type_id`)->getField('type_id')) and
            !($typeID = $this->typeModel->add(array('type_name' => $this->types[$type])))
        ) {
            $this->_error = '无法增加反馈类型';

            return false;
        }

        return $typeID;
    }

    /**
     * 添加反馈
     *
     * @param int $type 反馈的类型
     * @param  string $content 反馈的内容
     * @param  int    $uid     反馈的UID，默认为0，兼容某些地方，可以匿名反馈
     * @return bool
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function add($type, $content, $uid = 0)
    {
        /* # 验证类型 */
        if (!($type = $this->selectAddType($type))) {
            return flase;
        }

        /* # 添加数据 */
        return parent::add(array(
            'content' => $content,
            'cTime' => time(),
            'type' => $type,
            'uid' => intval($uid),
        ));
    }

    /**
     * 更新反馈信息
     *
     * @return bool
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function update($fid, $content)
    {
        /* # ID 转为int */
        $fid = intval($fid);

        /* # 检查是否存在 */
        if (!$this->where('`id` = '.$fid)->field('`id`')->count()) {
            $this->_error = '更新的反馈信息不存在';

            return false;
        }

        /* # 更新数据 */
        return $this->where('`id` = '.$fid)->save(array(
            'mTime' => time(),
            'content' => $content,
        ));
    }

    /**
     * 根据ID删除反馈信息
     *
     * @return bool
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function delete($fid)
    {
        $this->where('`id` = '.intval($fid));

        return parent::delete();
    }

    /**
     * 更具type类型获取分页数据
     *
     * @param int $type   类型
     * @param int $number 每页显示的数量
     * @param  bool  $asc    是否按照时间正序排列，默认为false，以时间倒叙
     * @return array
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function findDataToPageByType($type, $number = 10, $asc = false)
    {
        /* # 检查是否存在类型 */
        if (!($type = $this->selectAddType($type))) {
            return false;
        }

        return $this->where('`type` = '.$type)->order('`cTime` '.($asc ? 'ASC' : 'DESC'))->findPage($number);
    }
} // END class FeedbackModel extends Model
