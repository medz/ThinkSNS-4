<?php
/**
 * 公告模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class AnnouncementModel extends Model
{
    protected $tableName = 'announcement';
    protected $fields = array('id', 'title', 'uid', 'mtime', 'sort', 'content', 'attach', '_pk' => 'id');

    /**
     * 保存公告数据
     * @param  array $data 公告所需数据，用户UID、公告标题、公告内容、相关附件、创建时间
     * @return int   返回成功的公告ID
     */
    public function saveAnnoun($data)
    {
        $add['uid'] = $save['uid'] = 1;        // TODO:UID临时写死
        $add['title'] = $save['title'] = t($data['title']);
        $add['content'] = $save['content'] = t($data['content']);    //TODO:编辑器可能不适宜用t函数
        $add['attach'] = $save['attach'] = t($data['attach']);
        $add['mtime'] = $save['mtime'] = time();

        // 保存公告数据操作
        if (!empty($data['id'])) {
            // 编辑公告
            $map['id'] = $data['id'];

            return $this->where($map)->save($save);
        } else {
            // 添加公告
            if ($id = $this->add($add)) {
                $edit['sort'] = $id;

                return $this->where('id='.$id)->save($edit);
            }
        }
    }

    /**
     * 删除公告
     * @param  int $id 公告ID
     * @return int 是否删除成功
     */
    public function delannoun($id)
    {
        // 验证数据正确性
        if (empty($id)) {
            $this->error = L('PUBLIC_WRONG_DATA');

            return false;
        }

        $map['id'] = is_array($id) ? array('IN', $id) : intval($id);

        return $this->where($map)->delete();
    }
}
