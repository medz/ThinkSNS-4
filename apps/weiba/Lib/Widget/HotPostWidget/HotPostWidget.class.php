<?php
/**
 * 可能感�
 * �趣的人Widget.
 *
 * @author zivss <guolee226@gmail.com>
 *
 * @version TS3.0
 */
class HotPostWidget extends Widget
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
        $list = $this->_getRelatedGroup($data);
        $var['topic_list'] = $list;
        $var['title'] = '热门帖子';
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
        $map['recommend'] = 1;
        $map['lock'] = 0;
        $map['is_del'] = 0;
        if (!$data['limit']) {
            $data['limit'] = 10;
        }
        //$list = model( 'Cache' )->get('weiba_post_recommend');
        if (!$list) {
            $list = M('weiba_post')->where($map)->order('rand()')->limit($data['limit'])->select();
            !$list && $list = 1;
            //model( 'Cache' )->set( 'weiba_post_recommend' , $list , 86400 );
        }

        return $list;
    }
}
