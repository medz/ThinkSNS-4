<?php
/**
 * 系统接口
 *
 * @package ThinkSNS\Api\Sociax\System
 * @author Seven Du <lovevipdsw@vip.qq.com>
 **/
class SystemApi extends Api
{
    /**
     * 提交反馈信息
     *
     * @reuqest int $uid [null] 可为空，默认从token中读取
     * @reuqest string $content 反馈内容，不能为空
     * @return array
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function sendFeeedback()
    {
        $uid = intval($_REQUEST['uid']);
        $uid or $uid = $this->mid;
        $content = t($_REQUEST['content']);

        /* # 检查是否有uid */
        if (!$uid) {
            $this->error(array(
                'status' => 0,
                'msg' => '缺少用户UID',
            ));

        /* # 检查是否有反馈内容 */
        } elseif (!$content) {
            $this->error(array(
                'status' => -1,
                'msg' => '请输入反馈内容',
            ));

        /* # 检查内容是否超出 */
        } elseif (get_str_length($content) > 500) {
            $this->error(array(
                'status' => -2,
                'msg' => '反馈长度超出最大小指500字',
            ));
        }

        /* # 添加反馈，和错误提示 */
        model('Feedback')->add(array('uid' => $uid, 'content' => $content, 'type' => 1, 'cTime' => time(), 'mTime' => 0)) or $this->error(array(
            'status' => -3,
            'msg' => '反馈失败！',
        ));

        /* # 反馈成功 */
        return array(
            'status' => 1,
            'msg' => '反馈成功',
        );
    }
} // END class SystemApi extends Api
