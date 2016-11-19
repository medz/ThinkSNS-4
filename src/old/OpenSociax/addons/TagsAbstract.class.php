<?php
/**
 * DIY模块类
 * @author Stream
 *
 */
abstract class TagsAbstract
{
    protected $templateFile = ''; //模板文件名
    protected $attr = array(); //属性
    protected $value = ''; //如果是嵌套格式，则为内部数据
    protected $tmplCacheFile = ''; //缓存文件路径
    protected $sign = '';

    /**
     * 参数赋值
     * @param unknown_type $attr
     * @param unknown_type $value
     */
    protected function init($attr, $value = '')
    {
        $this->attr = $attr;
        $this->templateFile = $this->getTemplateFile();

        if (! empty($value)) {
            $this->value = $value;
            $this->sign = tsmd5(json_encode($this->attr).$this->value);
        } else {
            $this->sign = tsmd5(json_encode($this->attr).$this->templateFile);
        }
        if (is_array($this->attr['head_link']) && !empty($this->attr['head_link'])) {
            foreach ($this->attr['head_link'] as &$value) {
                $value->url = str_replace('[@]', '&', $value->url);
            }
        }
        $this->tmplCacheFile = C('TMPL_CACHE_PATH').'/'.APP_NAME.'_'.$this->sign.C('TMPL_CACHFILE_SUFFIX');
    }
    /**
     * 编译并返回内容
     * @param  unknown_type $attr
     * @param  unknown_type $value
     * @param  unknown_type $tagInfo
     * @return Ambigous     <void, mixed>|string
     */
    public function replaceTag($attr, $value = '', $tagInfo)
    {
        $this->init($attr, $value);
        //调用子类的replace方法把参数引入
        $var = $this->replace();

        return fetch($this->templateFile, $var);
    }
    /**
     * 保存模块数据到数据库
     * @param  unknown_type $attr
     * @param  unknown_type $value
     * @param  unknown_type $tagInfo
     * @return string
     */
    public function parseTag($attr, $value = '', $tagInfo)
    {
        $this->init($attr, $value);
        $widgetDao = model('DiyWidget');
        $hasWidget = $widgetDao->checkHasWidget($this->sign);
        if ($hasWidget) {
            return $this->sign;
        }
        $map ['pluginId'] = $this->sign;
        $map ['tagLib'] = $tagInfo ['tagLib'];
        $map ['pageId'] = empty($attr ['pageId']) ? 0 : $attr ['pageId'];
        $map ['channelId'] = empty($attr ['channelId']) ? 0 : $attr ['channelId'];
        $map ['content'] = $value;
        $ext ['templatePath'] = $this->getTemplateFile();
        $ext ['attr'] = serialize($attr);
        $ext ['tagInfo'] ['name'] = $tagInfo ['tagInfo'] ['name'];
        $map ['cacheTime'] = isset($attr['cacheTime']) ? intval($attr['cacheTime']) : 0;
        $ext ['tagInfo'] ['path'] = $tagInfo ['tagInfo'] ['path'];
        $map ['ext'] = serialize($ext);
        $map ['cTime'] = time();
        $map ['mTime'] = time();
        $result = model('DiyWidget')->add($map);

        return $this->sign;
    }

    protected function replaceContent($content)
    {
        // 系统默认的特殊变量替换
        $replace = array(
            '../Public' => APP_PUBLIC_PATH, // 项目公共目录
            '__PUBLIC__' => WEB_PUBLIC_PATH, // 站点公共目录
            '__TMPL__' => APP_TMPL_PATH,  // 项目模板目录
            '__ROOT__' => __ROOT__,       // 当前网站地址
            '__APP__' => __APP__,        // 当前项目地址
            '__URL__' => __URL__,        // 当前模块地址
            '__ACTION__' => __ACTION__,     // 当前操作地址
            '__SELF__' => __SELF__,       // 当前页面地址
            '__THEME__' => __THEME__,        // 主题页面地址
            '__UPLOAD__' => __UPLOAD__,        // 上传文件地址
        );
        // 允许用户自定义模板的字符串替换
        if (is_array(C('TMPL_PARSE_STRING'))) {
            $replace = array_merge($replace, C('TMPL_PARSE_STRING'));
        }
        $content = str_replace(array_keys($replace), array_values($replace), $content);

        return $content;
    }

    /**
     * 解析
     */
    abstract public function getTemplateFile($tpl = '');

    /**
     * 替换
     */
    abstract public function replace();
}
