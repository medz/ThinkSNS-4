<?php

class ReplyDiggWidget extends Widget
{
    public function render($data)
    {
        $var['row_id'] = intval($data['row_id']);
        $var['digg_count'] = intval($data['digg_count']);
        $var['diggArr'] = (array) $data['diggArr'];
        $var['diggId'] = empty($data['diggId']) ? 'digg' : t($data['diggId']);
        $var['tpl'] = empty($data['tpl']) ? 'default' : t($data['tpl']);

        return $this->renderData($var);
    }

    public function addDigg()
    {
        $rowId = intval($_POST['row_id']);
        $result = D('WeibaReplyDigg', 'Weiba')->addDigg($rowId, $this->mid);
        if ($result) {
            $res['status'] = 1;
            $res['info'] = D('WeibaReplyDigg', 'Weiba')->getLastError();
        } else {
            $res['status'] = 0;
            $res['info'] = D('WeibaReplyDigg', 'Weiba')->getLastError();
        }
        exit(json_encode($res));
    }

    public function delDigg()
    {
        $row_id = intval($_POST['row_id']);
        $result = M('WeibaReplyDigg', 'Weiba')->delDigg($row_id, $this->mid);
        if ($result) {
            $res['status'] = 1;
            $res['info'] = D('WeibaReplyDigg', 'Weiba')->getLastError();
        } else {
            $res['status'] = 0;
            $res['info'] = D('WeibaReplyDigg', 'Weiba')->getLastError();
        }
        exit(json_encode($res));
    }

    private function renderData($var)
    {
        extract($var, EXTR_OVERWRITE);
        $html = '<span id="'.$diggId.$row_id.'" rel="'.$digg_count.'">';
        if (!isset($diggArr[$row_id])) {
            if (!empty($digg_count)) {
                if ($tpl == 'w3g') {
                    $html .= '<a href="javascript:;" onclick="addDigg('.$row_id.')">赞('.$digg_count.')</a>';
                } else {
                    $html .= '<a href="javascript:;" onclick="weiba.digg.addDigg('.$row_id.')"><i class="digg-like"></i>('.$digg_count.')</a>';
                }
            } else {
                if ($tpl == 'w3g') {
                    $html .= '<a href="javascript:;" onclick="addDigg('.$row_id.')">赞</a>';
                } else {
                    $html .= '<a href="javascript:;" onclick="weiba.digg.addDigg('.$row_id.')"><i class="digg-like"></i></a>';
                }
            }
        } else {
            if (!empty($digg_count)) {
                if ($tpl == 'w3g') {
                    $html .= '<a href="javascript:;" onclick="delDigg('.$row_id.')" class="like-h digg-like-yes">赞('.$digg_count.')</a>';
                } else {
                    $html .= '<a href="javascript:;" class="like-h digg-like-yes" onclick="weiba.digg.delDigg('.$row_id.')"><i class="digg-like"></i>('.$digg_count.')</a>';
                }
            }
        }
        $html .= '</span>';

        return $html;
    }
}
