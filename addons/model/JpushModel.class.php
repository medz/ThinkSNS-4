<?php
/**
 * 极光推送模型
 *
 * @version TS4.5
 * @name JpushModel
 * @author Foreach
 *
 */
class JpushModel extends Model
{
    protected static $client;

    public function __construct()
    {
        $config = D('system_data')->where(array('key' => 'jpush'))->getField('value');
        $config = unserialize($config);
        static::$client = new \JPush\Client(t($config['key']), t($config['secret']));
    }


    /**
     * 推送消息
     * @param array $uids 用户uid
     * @param string $alert 消息内容
     * @param  array      $extras
     * @param  int        $type
     * @param  int        $rose
     * @return array|bool
     */
    public function pushMessage($uids = array(), $alert = '', $extras = array())
    {
        $audience = array('alias' => $uids);
        foreach ($audience['alias'] as $k => $v) {
            $audience['alias'][$k] = (string) $v;
        }
        $audience['alias'] = array_values($audience['alias']);

        $result = static::$client->push()
            ->setPlatform('all')
            ->setNotificationAlert($alert)
            ->addAllAudience()
            ->addAndroidNotification($alert, null, null, $extras)
            ->addIosNotification($alert, null, null, null, 'iOS category', $extras)
            ->setMessage($alert, $title, '', $extras)
            ->setOptions(0, null, null, true, null)//True 表示推送生产环境，False 表示要推送开发环境
            ->send();
        var_dump($result);
        exit;

        if ($result == null) {
            return false;
        }

        return $result;
    }
}
