<?php

use Apps\Information\Model\Subject as SubjectModel;

/**
 * 首页文章推荐Widget
 *
 */
class RelatedArticleWidget extends Widget
{
    /**
     * 渲染首页文章推荐页面
     * @param  array  $data 配置相关数据
     * @return string 渲染页面的HTML
     */
    public function render($data)
    {
        $var = $data;
        $key = '_getRelatedIndexArticle_'.date('Ymd');
        $var['article'] = S($key);
        if ($var['article'] === false && $var['isIndex'] == 1) {
            $info = SubjectModel::getInstance()->where(array('isIndex' => $var['isIndex']))->find();
            preg_match_all('/\<img(.*?)src\=\"(.*?)\"(.*?)\/?\>/is', $info['content'], $image);
            $info['image'] = $image[2];
            $var['article'] = $info;

            S($key, $var['article'], 86400);
        }
        $content = $this->renderFile(dirname(__FILE__).'/relatedArticle.html', $var);

        return $content;
    }
}
