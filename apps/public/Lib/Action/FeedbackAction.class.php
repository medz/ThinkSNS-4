<?php
/**
 * 意见反馈控制器.
 */
class FeedbackAction extends Action
{
    /**
     * 添加意见反馈操作.
     */
    public function feedback()
    {
        if (t($_POST['textarea'])) {
            $feedbacktype = D('')->table(C('DB_PREFIX').'feedback_type')->where('type_name = "'.t($_POST['select']).'"')->find();
            $map['feedbacktype'] = $feedbacktype['type_id'];
            $map['feedback'] = t($_POST['textarea']);
            $map['uid'] = $this->mid;
            $map['cTime'] = time();
            $map['type'] = 0;
            $res = model('Feedback')->add($map);
            if ($map['feedback'] == '') {
                $this->error(L('PUBLIC_INPUT_FEEDBACK'));            // 请填写反馈内容
            }
            if ($res) {
                $touid = D('user_group_link')->where('user_group_id=1')->field('uid')->findAll();
                foreach ($touid as $k => $v) {
                    model('Notify')->sendNotify($v['uid'], 'feedback_audit');
                }
                $return = array('status' => 1, 'data' => L('PUBLIC_REPORTING_INFO'));
                $this->assign('jumpUrl', U('public/Index/index'));
                $this->success(L('PUBLIC_SUBMIT_FEEDBACK_SUCCESS'));            // 提交成功，感谢您的反馈
            } else {
                $this->error(model()->getError());
            }
        } else {
            $this->error(L('PUBLIC_INPUT_FEEDBACK'));            // 请填写反馈内容
        }
    }
}
