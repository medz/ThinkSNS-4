<?php

class RelatedUserHooks extends Hooks
{
    public function home_index_right_top()
    {
        $this->assign('mid', $GLOBALS['ts']['mid']);

        $this->display('related');
    }
}
