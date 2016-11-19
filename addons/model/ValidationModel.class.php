<?php
/**
 * 验证模型 - 业务逻辑模型，没有数据表暂时待定
 * @example
 * 验证服务（ValidationService）主要用于邀请注册、修改帐号等需要用户验证的操作。
 * @author daniel <desheng.young@gmail.com>
 * @version TS3.0
 */
class ValidationModel extends Model
{
    protected $tableName = 'validation';
    protected $fields = array(0 => 'validation_id', 1 => 'type', 2 => 'from_uid', 3 => 'to_user', 4 => 'data', 5 => 'code', 6 => 'target_url', 7 => 'is_active', 8 => 'ctime', '_autoinc' => true, '_pk' => 'validation_id');

    /**
     * 添加验证
     * @param  int    $from_uid   验证的来源
     * @param  string $to_user    验证的目的地
     * @param  string $target_url 进行验证的url地址
     * @param  string $type       验证类型
     * @param  string $data       附加数据
     * @return bool   是否添加认证成功
     */
    public function addValidation($from_uid, $to_user, $target_url, $type = '', $data = '')
    {
        $validation['type'] = $type;
        $validation['from_uid'] = $from_uid;
        $validation['to_user'] = $to_user;
        $validation['data'] = $data;
        $validation['is_active'] = 1;
        $validation['ctime'] = time();
        $vid = model('Validation')->add($validation);

        if ($vid) {
            $validation_code = $this->__generateCode($vid);
            $target_url = $target_url."&validationid=$vid&validationcode=$validation_code";
            $res = model('Validation')->where("`validation_id`=$vid")->setField(array('code', 'target_url'), array($validation_code, $target_url));
            if ($res) {
                return $target_url;
            } else {
                return false;
            }
        } else {
            return $vid;
        }
    }

    /**
     * 分发验证
     * @param int    $id              验证的ID（为0或留空时，自动从$_REQUEST获取）
     * @param string $validation_code 验证码（为0或留空时，自动从$_REQUEST获取）
     */
    public function dispatchValidation($id = 0, $validation_code = 0)
    {
        if (!$this->getValidation($id, $validation_code)) {
            redirect(SITE_URL, 5, L('PUBLIC_INVATE_CODE_ERROR'));            // 邀请码错误或已失效
        }
    }

    /**
     * 取消邀请，即设置验证为失效
     * @param  int  $id 验证的ID（为0或留空时，自动从$_REQUEST获取）
     * @return bool 是否取消成功
     */
    public function unsetValidation($id = 0)
    {
        $where = $id != 0 ? "`validation_id`=$id" : '`validation_id`='.intval($_REQUEST['validationid']).' AND `code`="'.h($_REQUEST['validationcode']).'"';

        return model('Validation')->where($where)->setField('is_active', 0);
    }

    /**
     * 获取邀请详细
     * @param  int    $id   验证的ID（为0或留空时，自动从$_REQUEST获取）
     * @param  string $code 验证码（为0或留空时，自动从$_REQUEST获取）
     * @return mix    获取失败返回false，成功后返回邀请详细
     */
    public function getValidation($id = 0, $code = 0)
    {
        if ($id == 0 && $code == 0 && !empty($_REQUEST['validationid']) && !empty($_REQUEST['validationcode'])) {
            $where = '`validation_id`='.intval($_REQUEST['validationid']).' AND `code`="'.h($_REQUEST['validationcode']).'" AND `is_active`=1';
        } elseif ($id != 0) {
            $id = intval($id);
            $where = $code == 0 ? "`validation_id`=$id AND `is_active`=1" : "`validation_id`=$id AND `is_active`=1 AND `code`='$code'";
        } elseif ($code != 0) {
            $where = $id == 0 ? '`code`="'.h($code).'" AND `is_active`=1' : "`validation_id`=$id AND `is_active`=1 AND `code`='$code'";
        } else {
            return false;
        }

        return model('Validation')->field('validation_id AS validationid, type,from_uid,to_user,data,code,target_url,is_active,ctime')->where($where)->find();
    }

    /**
     * 判断给定的验证ID和验证码是否合法
     * @param  int    $id   验证的ID（为0或留空时，自动从$_REQUEST获取）
     * @param  string $code 验证码（为0或留空时，自动从$_REQUEST获取）
     * @return bool   验证ID与验证码是否合法
     */
    public function isValidValidationCode($id = 0, $code = 0)
    {
        if (($id == 0 && $code != 0) || ($id != 0 && $code == 0)) {
            return false;
        }

        if ($id == 0 && $code == 0) {
            $id = intval($_REQUEST['validationid']);
            $code = h($_REQUEST['validationcode']);
        }

        return model('Valiation')->where("`validation_id`=$id AND `code`='$code' AND `is_active`=1")->find();
    }

    /**
     * 检查Email地址是否合法
     * @param  string $email 邮件地址
     * @return bool   邮件地址是否合法
     */
    public function isValidEmail($email)
    {
        return preg_match("/[_a-zA-Z\d\-\.]+@[_a-zA-Z\d\-]+(\.[_a-zA-Z\d\-]+)+$/i", $email) !== 0;
    }

    /**
     * 验证Email是否可用
     * @param  string $email 邮件地址
     * @param  int    $uid   邀请人的用户ID
     * @return bool   邮件地址是否可用
     */
    public function isEmailAvailable($email, $uid = false)
    {
        // Email格式错误
        if (!$this->isValidEmail($email)) {
            return false;
        } elseif ($res = model('User')->field('`uid`')->where("`email`='{$email}'")->find()) {
            // 邀请数据已经存在
            if ($uid) {
                if ($res['uid'] != intval($uid)) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * 生成唯一的验证码
     * @param  int    $id 验证的ID（为0或留空时，自动从$_REQUEST获取）
     * @return string 验证码
     */
    private function __generateCode($id)
    {
        return md5($id.'thinksns#^!@*#%^!@#');
    }
}
