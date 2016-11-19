<?php
/**
 * 内置文章模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class XarticleModel extends Model
{
    protected $tableName = 'x_article';
    protected $fields = array('id', 'title', 'uid', 'mtime', 'sort', 'content', 'attach', 'type');

    /**
     * 保存公告数据
     * @param  array    $data 公告相关数据
     * @return bool|int 若成功返回公告ID，失败返回false
     */
    public function saveArticle($data)
    {
        // 处理数据
        $add['uid'] = $save['uid'] = $GLOBALS['ts']['mid'];
        $add['title'] = $save['title'] = t($data['title']);
        $add['content'] = $save['content'] = h($data['content']);
        $add['attach'] = $save['attach'] = trim(t($data['attach_ids']), '|');    // 附件ID
        $add['mtime'] = $save['mtime'] = time();
        $add['type'] = $save['type'] = intval($data['type']);

        if (empty($add['title'])) {
            $this->error = L('PUBLIC_COMMENT_MAIL_TITLE');            // 标题不可为空
            return false;
        }
        if (empty($add['content'])) {
            $this->error = L('PUBLIC_COMMENT_MAIL_REQUIRED');        // 内容不可为空
            return false;
        }

        if (!empty($data['id'])) {
            // 编辑操作
            $map['id'] = $data['id'];

            return $this->where($map)->save($save);
        } else {
            // 添加操作
            if ($id = $this->add($add)) {
                $edit['sort'] = $id;

                return $this->where('id='.$id)->save($edit);
            }
        }
    }

    /**
     * 删除指定公告操作
     * @param  int $id 公告ID
     * @return int 0表示删除失败，1表示删除成功
     */
    public function delArticle($id)
    {
        if (empty($id)) {
            $this->error = L('PUBLIC_ID_NOEXIST');            // ID不能为空
            return false;
        }
        $map['id'] = is_array($id) ? array('IN', $id) : intval($id);

        return $this->where($map)->delete();
    }
}
