<?php

class AvatarBoxWidget extends Widget
{
    public function render($data)
    {
        $var['small_avatar'] = getUserFace($this->mid);
        $content = $this->renderFile(dirname(__FILE__).'/default.html', $var);

        return $content;
    }

    public function show()
    {
        $var['user_info'] = model('User')->getUserInfo($GLOBALS['ts']['mid']);
        $var['callback'] = 'avatar_box_callback';
        $content = $this->renderFile(dirname(__FILE__).'/show.html', $var);

        return $content;
    }
}
