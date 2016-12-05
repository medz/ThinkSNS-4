<?php

class MyloveAction extends Action
{
    public function _initialize()
    {
        header('Content-Type:text/html; charset=UTF8');
    }

    public function index()
    {
        $url = 'http://i/dz3/api/mobile/index.php?module=myfavthread&version=1&page=1&mobile=no';
        $content = file_get_contents($url);
        dump($content);
        $content = json_decode($content, true);
        dump($content);
    }
}
