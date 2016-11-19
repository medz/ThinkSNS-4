<?php
    $creditSet = array();
    //其中 score=>5 表示默认 积分变化为5,其他扩展类型为0
    $creditSet[] = array('action' => 'demo', 'info' => 'public加积分例子(+)', 'score' => '5');
    $creditSet[] = array('action' => 'demo_', 'info' => 'public减积分例子(-)', 'score' => '5');
    $creditSet[] = array('action' => 'nocredit', 'info' => 'public积分设置为空的例子(+)', 'score' => '0');

    return $creditSet;
