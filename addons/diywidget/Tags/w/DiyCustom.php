<?php
/**
 * 自定义标签模块
 * @author Stream
 *
 */
class DiyCustom extends TagsAbstract
{
    /**
     * 是否是封闭的标签
     * @var unknown_type
     */
    public static $TAG_CLOSED = true;
    public static $html = array();
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

        return dirname(__FILE__).'/DiyCustom/temp.html';
    }
    public function replaceTag($attr, $value = '')
    {
        $this->init($attr, $value);
        $widgetDao = model('DiyWidget');
        if (empty($value)) {
            $content = $widgetDao->getTemplateByPluginId($this->sign);
        } else {
            $content = $value;
        }
        $var = $this->replace();
        extract($var, EXTR_OVERWRITE);
        fetch($this->templateFile, $var);

        return $content;
    }
    /* (non-PHPdoc)
     * @see TagsAbstract::replace()
     */
    public function replace()
    {
        // TODO
    }
}
