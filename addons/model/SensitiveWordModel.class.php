<?php

class SensitiveWordModel extends Model
{
    const MUST_REPLACE = 3;

    const ONE_OPTION = '禁止关键词';
    const TWO_OPTION = '审核关键词';
    const THREE_OPTION = '替换关键词';

    protected $tableName = 'sensitive_word';

    private $_type = array(1, 2, 3);

    public function setSensitiveWord($word, $replace, $type, $cid, $uid, $id = null)
    {
        if (empty($word) || (empty($replace) && $type == self::MUST_REPLACE) || !in_array($type, $this->_type) || empty($cid) || empty($uid)) {
            return false;
        }

        $data['word'] = $word;
        $type == self::MUST_REPLACE && $data['replace'] = $replace;
        $data['type'] = $type;
        $data['sensitive_category_id'] = $cid;
        $data['uid'] = $uid;
        $data['ctime'] = time();

        $result = false;
        if (empty($id)) {
            $result = $this->add($data);
        } else {
            $map['sensitive_word_id'] = $id;
            $result = $this->where($map)->save($data);
        }

        return (boolean) $result;
    }

    public function rmSensitiveWord($id)
    {
        if (empty($id)) {
            return false;
        }
        $map['sensitive_word_id'] = $id;
        $result = $this->where($map)->delete();

        return (boolean) $result;
    }

    public function getSensitiveWordList($map = array(), $limit = 20)
    {
        $list = $this->where($map)->findPage($limit);

        $list['data'] = $this->_formatData($list['data']);

        return $list;
    }

    public function getSensitiveWord($id)
    {
        $map['sensitive_word_id'] = $id;
        $data = $this->where($map)->find();

        $data = $this->_formatData(array($data));
        $data = array_shift($data);

        return $data;
    }

    private function _formatData($data)
    {
        $categoryHash = model('CategoryTree')->setTable('sensitive_category')->getCategoryHash();
        foreach ($data as &$value) {
            $type = $value['type'];
            switch ($type) {
                case 1:
                    $value['type_name'] = self::ONE_OPTION;
                    break;
                case 2:
                    $value['type_name'] = self::TWO_OPTION;
                    break;
                case 3:
                    $value['type_name'] = self::THREE_OPTION;
                    break;
            }

            $value['uname'] = getUserName($value['uid']);
            $value['format_ctime'] = date('Y-m-d H:i:s', $value['ctime']);
            $value['sensitive_category'] = $categoryHash[$value['sensitive_category_id']];
        }

        return $data;
    }

    public function checkedContent($content)
    {
        $list = $this->field('`word`,`type`,`replace`')->findAll();

        $ban = array();
        $replace = array();
        $audit = array();

        foreach ($list as $value) {
            switch ($value['type']) {
                case 1:
                    $ban[$value['word']] = '';
                    break;
                case 2:
                    $audit[$value['word']] = '';
                    break;
                case 3:
                    $replace[$value['word']] = $value['replace'];
                    break;
            }
        }

        if (!empty($ban) && strlen(strtr($content, $ban)) < strlen($content)) {
            return array('status' => false, 'type' => 1, 'data' => '内容中包含禁止词汇');
        }

        !empty($replace) && $content = strtr($content, $replace);

        if (!empty($audit) && strlen(strtr($content, $audit)) < strlen($content)) {
            return array('status' => true, 'type' => 2, 'data' => $content);
        }

        return array('status' => true, 'type' => 3, 'data' => $content);
    }
}
