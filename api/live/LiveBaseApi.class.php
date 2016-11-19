
<?php
/**
 * 签到API接口
 * @author
 * @version  TS4.0
 */
class LiveBaseApi extends Api
{
    //protected $stream_server = '在这里填写直播服务器地址';
    public $stream_server = '';
    //定义请求header
    public $header = [];
    public $curl_header = array('Auth-Appid: zb60225160269831');
    //对接智播1.0 usid前缀
    protected $usid_prex = 'ThinkSNS_';

    public function __construct()
    {
        parent::__construct();
        $this->_initialize();
    }

    /**
     * @name 初始化
     */
    public function _initialize()
    {
        //设置请求的header参数
        $this->header = [
            'Auth-Appid' => 'zb60225160269831', //'zb602251775577514102'
        ];
        //管理后台设置的直播验证服务地址
        $this->stream_server = 'http://zbtest.zhibocloud.cn';
        if (!M('')->query("show tables like '".C('DB_PREFIX')."live_user_info'")) {
            $sql = '    CREATE TABLE IF NOT EXISTS `'.C('DB_PREFIX')."live_user_info` (
                            `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
                            `uid` int(11) DEFAULT 0 COMMENT 'uid',
                            `usid` varchar(255) NOT NULL DEFAULT '' COMMENT 'usid,不能修改',
                            `uname` varchar(255) NOT NULL DEFAULT '' COMMENT '昵称',
                            `sex` tinyint(2) NOT NULL DEFAULT 1 COMMENT '性别',
                            `ticket` varchar(255) NOT NULL DEFAULT '' COMMENT '票据',
                            `ctime` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
                            `mtime` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间',
                            PRIMARY KEY (`id`)
                        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='直播用户信息表' AUTO_INCREMENT=16 ;";
            if (false === M('')->execute($sql)) {
                //数据表创建失败
                return array(
                        'status' => 0,
                        '内部错误，请联系管理员',
                    );
            }
        }
    }


    /**
     * @name 检测是否是一个url地址
     */
    public function isUrl($url = '')
    {
        return preg_match('/^http(s)?:\/\/.+/', $url) ? true : false;
    }


    /**
     * @name 获取直播服务器的地址
     */
    public function getStreamServiceUrl()
    {
        if (!$this->isUrl($this->stream_server)) {
            return '';
        }

        return $this->stream_server;
    }

    /**
     * 检测直播服务地址
     */
    protected function checkStreamServiceUrl()
    {
        $url = $this->getStreamServiceUrl();
        if (!$url) {
            return false;
        }

        return true;
    }

    protected function is_ZhiboService()
    {
        if ($this->header['Auth-Appid'] === $_SERVER['HTTP_AUTH_APPID']) {
            return true;
        } else {
            return false;
        }
    }
}
