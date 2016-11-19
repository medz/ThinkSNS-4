<?php
/**
 * 用户统计Widget
 * @version TS3.0
 */
    class UserCountWidget extends Widget
    {
        public function render($data)
        {
            $content = '';

            return $content;
        }

        /**
         * 获取指定用户的通知统计数目
         */
        public function getUnreadCount()
        {
            $count = model('UserCount')->getUnreadCount($GLOBALS['ts']['mid']);
            $data['status'] = 1;
            $data['data'] = $count;
            echo json_encode($data);
        }
    }
