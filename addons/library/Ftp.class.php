<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: pengyong <i@pengyong.info>
// +----------------------------------------------------------------------

class Ftp
{
    //FTP 连接资源
    private $link;

    //FTP连接时间
    public $link_time;

    //错误代码
    private $err_code = 0;

    //传送模式{文本模式:FTP_ASCII, 二进制模式:FTP_BINARY}
    public $mode = FTP_BINARY;

    /**
     初始化类

     **/
    public function start($data)
    {
        if (empty($data['port'])) {
            $data['port'] = '21';
        }
        if (empty($data['pasv'])) {
            $data['pasv'] = false;
        }
        if (empty($data['ssl'])) {
            $data['ssl'] = false;
        }
        if (empty($data['timeout'])) {
            $data['timeout'] = 30;
        }

        return $this->connect($data['server'], $data['username'], $data['password'], $data['port'], $data['pasv'], $data['ssl'], $data['timeout']);
    }

    /**
     * 连接FTP服务器
     * @param string $host                       　　 服务器地址
     * @param string $username　　　用户名
     * @param string $password　　　密�      �
     * @param int    $port　　　　           服务器端口，默认值为21
     * @param bool   $pasv                       是否开启被动模式
     * @param bool   $ssl　　　　            　是否使用SSL连接
     * @param int    $timeout                    超时时间　
     */
    public function connect($host, $username = '', $password = '', $port = '21', $pasv = false, $ssl = false, $timeout = 30)
    {
        $start = time();
        if ($ssl) {
            if (!$this->link = @ftp_ssl_connect($host, $port, $timeout)) {
                $this->err_code = 1;

                return false;
            }
        } else {
            if (!$this->link = @ftp_connect($host, $port, $timeout)) {
                $this->err_code = 1;

                return false;
            }
        }

        if (@ftp_login($this->link, $username, $password)) {
            if ($pasv) {
                ftp_pasv($this->link, true);
            }
            $this->link_time = time() - $start;

            return true;
        } else {
            $this->err_code = 1;

            return false;
        }
        register_shutdown_function(array(&$this, 'close'));
    }

    /**
     * 创建文件夹
     * @param string $dirname 目录名，
     */
    public function mkdir($dirname)
    {
        if (!$this->link) {
            $this->err_code = 2;

            return false;
        }
        $dirname = $this->ck_dirname($dirname);
        $nowdir = '/';
        foreach ($dirname as $v) {
            if ($v && !$this->chdir($nowdir.$v)) {
                if ($nowdir) {
                    $this->chdir($nowdir);
                }
                @ftp_mkdir($this->link, $v);
            }
            if ($v) {
                $nowdir .= $v.'/';
            }
        }

        return true;
    }

    /**
     * 上传文件
     * @param string $remote 远程存放地址
     * @param string $local  本地存放地址
     */
    public function put($remote, $local)
    {
        if (!$this->link) {
            $this->err_code = 2;

            return false;
        }
        $dirname = pathinfo($remote, PATHINFO_DIRNAME);
        if (!$this->chdir($dirname)) {
            $this->mkdir($dirname);
        }
        if (@ftp_put($this->link, $remote, $local, $this->mode)) {
            return true;
        } else {
            $this->err_code = 7;

            return false;
        }
    }

    /**
     * 删除文件夹
     * @param string $dirname 目录地址
     * @param bool   $enforce 强制删除
     */
    public function rmdir($dirname, $enforce = false)
    {
        if (!$this->link) {
            $this->err_code = 2;

            return false;
        }
        $list = $this->nlist($dirname);
        if ($list && $enforce) {
            $this->chdir($dirname);
            foreach ($list as $v) {
                $this->f_delete($v);
            }
        } elseif ($list && !$enforce) {
            $this->err_code = 3;

            return false;
        }
        @ftp_rmdir($this->link, $dirname);

        return true;
    }

    /**
     * 删除指定文件
     * @param string $filename 文件名
     */
    public function delete($filename)
    {
        if (!$this->link) {
            $this->err_code = 2;

            return false;
        }
        if (@ftp_delete($this->link, $filename)) {
            return true;
        } else {
            $this->err_code = 4;

            return false;
        }
    }

    /**
     * 返回给定目录的文件列表
     * @param  string $dirname 目录地址
     * @return array  文件列表数据
     */
    public function nlist($dirname)
    {
        if (!$this->link) {
            $this->err_code = 2;

            return false;
        }
        if ($list = @ftp_nlist($this->link, $dirname)) {
            return $list;
        } else {
            $this->err_code = 5;

            return false;
        }
    }

    /**
     * 在 FTP 服务器上改变当前目录
     * @param string $dirname 修改服务器上当前目录
     */
    public function chdir($dirname)
    {
        if (!$this->link) {
            $this->err_code = 2;

            return false;
        }
        if (@ftp_chdir($this->link, $dirname)) {
            return true;
        } else {
            $this->err_code = 6;

            return false;
        }
    }

    /**
     * 获取错误信息
     */
    public function get_error()
    {
        if (!$this->err_code) {
            return false;
        }
        $err_msg = array(
            '1' => 'Server can not connect',
            '2' => 'Not connect to server',
            '3' => 'Can not delete non-empty folder',
            '4' => 'Can not delete file',
            '5' => 'Can not get file list',
            '6' => 'Can not change the current directory on the server',
            '7' => 'Can not upload files',
        );

        return $err_msg[$this->err_code];
    }

    /**
     * 检测目录名
     * @param  string $url 目录
     * @return 由    / 分开的返回数组
     */
    private function ck_dirname($url)
    {
        $url = str_replace('', '/', $url);
        $urls = explode('/', $url);

        return $urls;
    }

    /**
     * 关闭FTP连接
     */

    public function close()
    {
        return @ftp_close($this->link);
    }
}
