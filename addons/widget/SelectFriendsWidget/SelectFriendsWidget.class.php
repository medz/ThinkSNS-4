<?php
/**
 * 选择好友Widget
 */
class SelectFriendsWidget extends Widget
{
    /**
     * 选择好友Widget
     *
     * $data的参数:
     * array(
     * 	'name'(可选)	=> '表单的name', // 默认为"fri_ids"
     * )
     *
     * @see Widget::render()
     */
    public function render($data)
    {
        $data['name'] || $data['name'] = 'fri_ids';

        $content = $this->renderFile(dirname(__FILE__).'/SelectFriends.html', $data);

        return $content;
    }
}
