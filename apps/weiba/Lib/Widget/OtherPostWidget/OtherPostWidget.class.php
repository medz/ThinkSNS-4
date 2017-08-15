<?php
/**
 * 可能感�
 * �趣的人Widget.
 *
 * @author zivss <guolee226@gmail.com>
 *
 * @version TS3.0
 */
class OtherPostWidget extends Widget
{
    /**
     * 渲染可能感�
     * �趣的人页面.
     *
     * @param array $data
     *                    �
     * �置相�
     * �数据
     *
     * @return string 渲染页面的HTML
     */
    public function render($data)
    {
        $list = $this->_getRelatedGroup($data);
        $var['uid'] = intval($data['uid']);
        $var['post_id'] = intval($data['post_id']);
        $var['max'] = intval($data['max']);
        $var['topic_list'] = $list;
        $var['title'] = '热门帖子';
        $content = $this->renderFile(dirname(__FILE__).'/index.html', $var);

        return $content;
    }

    /**
     * 换一换数据处理.
     *
     * @return json 渲染页面所需的JSON数据
     */
    public function changeRelate()
    {
        $var['uid'] = $data['uid'] = intval(t($_POST['uid']));
        $var['post_id'] = $data['post_id'] = intval(t($_POST['post_id']));
        $var['max'] = $data['max'] = intval(t($_POST['max']));
        $list = $this->_getRelatedGroup($data);
        $var['topic_list'] = $list;
        $var['title'] = $data['title'];
        $content = $this->renderFile(dirname(__FILE__).'/_index.html', $var);
        exit(json_encode($content));
    }

    /**
     * 获取用户的相�
     * �数据.
     *
     * @param array $data
     *                    �
     * �置相�
     * �数据
     *
     * @return array 显示所需数据
     */
    private function _getRelatedGroup($data)
    {
        $map['is_del'] = 0;
        if (!$data['max']) {
            $data['max'] = 10;
        }
        //$list = model( 'Cache' )->get('weiba_post_recommend');
        if (!$list) {
            $map1['post_id'] = array('neq', $data['post_id']);
            $map1['is_del'] = 0;
            $list = M('weiba_post')->where($map1)->order('rand()')->limit($data['max'])->select();
            !$list && $list = 1;
            //model( 'Cache' )->set( 'weiba_post_recommend' , $list , 86400 );
        }

        return $list;
    }
}
