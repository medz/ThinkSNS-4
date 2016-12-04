<?php
/**
 * 表情模型 - 业务逻辑模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class ExpressionModel
{
    /**
     * 获取当前所有的表情
     * @param bool $flush 是否更新缓存，默认为false
     * @return array 返回表情数据
     */
    public function getAllExpression($flush = false)
    {
        $cache_id = '_model_expression';
        if (($res = S($cache_id)) === false || $flush === true) {
            global $ts;
            $pkg = $ts['site']['expression'];
            // $pkg = $pkg ? $pkg : 'default';
            $pkg = $pkg ? $pkg : 'new';
            $filepath = THEME_PUBLIC_PATH.'/image/expression/'.$pkg;
            require_once ADDON_PATH.'/library/io/Dir.class.php';
            $expression = new Dir($filepath);
            $expression_pkg = $expression->toArray();

            $res = array();
            foreach ($expression_pkg as $value) {
                /*
                if(!is_utf8($value['filename'])){
                    $value['filename'] = auto_charset($value['filename'],'GBK','UTF8');
                }*/
                list($file) = explode('.', $value['filename']);
                $temp['title'] = $file;
                $temp['emotion'] = '['.$file.']';
                $temp['filename'] = $value['filename'];
                $temp['type'] = $pkg;
                $res[$temp['emotion']] = $temp;
            }
            S($cache_id, $res);
        }

        return $res;
    }

    /**
     * 将表情格式化成HTML形式
     * @param  string $data 内容数据
     * @return string 转换为表情链接的内容
     */
    public function parse($data)
    {
        $data = preg_replace('/img{data=([^}]*)}/', "<img src='$1'  data='$1' >", $data);

        return $data;
    }
}
