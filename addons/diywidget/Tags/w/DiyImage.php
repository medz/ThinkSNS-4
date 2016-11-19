<?php
/**
 * 图片模块
 * @author Stream
 *
 */
class DiyImage extends TagsAbstract
{
    /**
     * 是否是封闭的标签
     * @var unknown_type
     */
    public static $TAG_CLOSED = false;

    public $config = array();

    public function __construct()
    {
    }
    public function getTagStatus()
    {
        return self::$TAG_CLOSED;
    }
    /**
     * 返回模板文件路径
     */
    public function getTemplateFile($tpl = '')
    {
        //返回需要渲染的模板
        $file = $this->attr ['style'];
        if (!empty($tpl)) {
            $file = $tpl;
        }

        return dirname(__FILE__).'/DiyImage/'.$file.'.html';
    }

    /**
     * 这里返回的是模板中需要渲染的变量
     */
    public function replace()
    {
        $time = str_replace(array(' ', '.'), '', microtime());
        $var['image'] = $this->attr['image_list'];
        $var['effect'] = $this->attr['effect']; //动画可选 'none','scrollx', 'scrolly', 'fade'
        $var['autoPlay'] = $this->attr['autoPlay']; //是否自动播放
        $var['autoPlayInterval'] = $this->attr['autoPlayInterval']; //自动播放间隔时间
        $var['width'] = $this->attr['width'];
        $var['height'] = $this->attr['height'];

        foreach ($var['image'] as &$value) {
            $value->path = getImageUrl($value->path);
            $value->url = str_replace('[@]', '&', $value->url);
        }
        $var['imgId'] = 'i'.substr($this->sign, 0, 5).$time;
        $var['imgPanel'] = 'i'.substr($this->sign, 0, 6).$time;
        $var['imgNav'] = 'i'.substr($this->sign, 0, 7).$time;

        return $var;
    }
}
