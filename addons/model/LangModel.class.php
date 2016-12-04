<?php
/**
 * 多语言模型 - 数据对象模型
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class LangModel extends Model
{
    protected $tableName = 'lang';
    protected $fields = array(0 => 'lang_id', 1 => 'key', 2 => 'appname', 3 => 'filetype', 4 => 'zh-cn', 5 => 'en', 6 => 'zh-tw', '_autoinc' => true, '_pk' => 'id');

    protected $langType = array();            // 默认的语言类型设置

    /**
     * 初始化方法，设置默认语言
     */
    public function _initialize()
    {
        empty($this->langType) && $this->langType = C('DEFAULT_LANG_TYPE');
    }

    /**
     * 获取当前系统的语设置
     * @return string 当前系统的语设置
     */
    public function getLangType()
    {
        // 每次直接从这里获取
        return $this->langType;
    }

    /**
     * 获取语言配置内容列表
     * @param array $map 查询条件
     * @return array 语言配置内容列表
     */
    public function getLangContent($map)
    {
        empty($map) && $map = array();
        $data = $this->where($map)->findPage(20);

        return $data;
    }

    /**
     * 获取单条语言配置内容
     * @param int $sid 语言资源ID
     * @return array 单条语言配置内容
     */
    public function getLangSetInfo($sid)
    {
        $data = $this->where('lang_id='.$sid)->find();

        return $data;
    }

    /**
     * 更改语言配置内容
     * @param  array $data 语言配置内容
     * @param  int $sid 语言资源ID
     * @return int 是否更改成功，1表示成功；0表示失败
     */
    public function updateLangData($data, $sid)
    {
        $addData['key'] = strtoupper(t($data['key']));
        $addData['appname'] = strtoupper(t($data['appname']));
        $addData['filetype'] = $data['filetype'];
        $fields = $this->getLangType();
        foreach ($fields as $value) {
            $addData[$value] = $data[$value];
        }

        if ($sid == 0) {
            // 判断重复
            $map['key'] = $data['key'];
            $map['appname'] = $data['appname'];
            $map['filetype'] = $data['filetype'];
            $count = $this->where($map)->count();
            if ($count > 0) {
                return 2;
            }
            $result = $this->add($addData);
        } else {
            $result = $this->where('lang_id='.$sid)->save($addData);
        }
        // 更新缓存文件
        $this->createCacheFile($addData['appname'], $addData['filetype']);
        $result = ($result === false) ? 0 : 1;

        return $result;
    }

    /**
     * 删除指定的语言配置内容
     * @param  int $sid 语言资源ID
     * @return mix 删除失败返回false，删除成功返回删除的语言资源ID
     */
    public function deleteLangData($sid)
    {
        $map['lang_id'] = array('IN', $sid);
        $result = $this->where($map)->delete();

        return $result;
    }

    /**
     * 创建语言缓存文件
     * @param string $app  应用名称
     * @param bool   $isJs 是否是Js文件
     */
    public function createCacheFile($app, $isJs)
    {
        set_time_limit(0);
        // 判断文件夹路径是否存在
        if (!file_exists(LANG_PATH)) {
            mkdir(LANG_PATH, 0777);
        }

        $map['appname'] = $app;
        $map['filetype'] = $isJs;
        $fields = $this->getLangType();
        $data = $this->where($map)->findAll();

        if ($isJs) {
            $this->_getJavaScriptFile($app, $fields, $data);
        } else {
            $this->_getPhpFile($app, $fields, $data);
        }
    }

    /**
     * 写入PHP语言文件
     * @param string $app    应用名称
     * @param array  $fields 语言类型字段
     * @param array  $data   语言的相关数据
     */
    private function _getPhpFile($app, $fields, $data)
    {
        $app = strtolower($app);
        foreach ($fields as $value) {
            $fileName = LANG_PATH.'/'.$app.'_'.$value.'.php';
            // 权限处理
            $fp = fopen($fileName, 'w+');
            $fileData = "<?php\n";
            $fileData .= "return array(\n";
            foreach ($data as $val) {
                $val[$value] = str_replace("'", '‘', $val[$value]);            // 处理掉单引号
                $content[] = "'{$val['key']}'=>'{$val[$value]}'";
            }
            $fileData .= implode(",\n", $content);
            $fileData .= "\n);";
            fwrite($fp, $fileData);
            fclose($fp);
            unset($fileData);
            unset($content);
            @chmod($fileName, 0775);
        }
    }

    /**
     * 写入JavaScript语言文件
     * @param string $app    应用名称
     * @param array  $fields 语言类型字段
     * @param array  $data   语言的相关数据
     */
    private function _getJavaScriptFile($app, $fields, $data)
    {
        $app = strtolower($app);
        foreach ($fields as $value) {
            $fileName = LANG_PATH.'/'.$app.'_'.$value.'.js';
            $fp = fopen($fileName, 'w+');
            $fileData = '';
            foreach ($data as $val) {
                $val[$value] = str_replace("'", '‘', $val[$value]);            // 处理掉单引号
                $content[] = "LANG['{$val['key']}']='{$val[$value]}';";
            }
            $fileData .= implode("\n", $content);
            fwrite($fp, $fileData);
            fclose($fp);
            unset($fileData);
            unset($content);
            @chmod($fileName, 0775);
        }
    }

    /**
     * 初始化整站的语言包
     */
    public function initSiteLang()
    {
        $dirArray = array();
        //取apps目录下的应用包名
        if (false != ($handle = opendir(APPS_PATH))) {
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..' && !strpos($file, '.')) {
                    $dirArray[] = $file;
                }
            }
            //关闭句柄
            closedir($handle);
        }
        if (empty($dirArray)) {
            $dirArray = C('DEFAULT_APPS');
        }

        foreach ($dirArray as $app) {
            $this->createCacheFile($app, 0);
            $this->createCacheFile($app, 1);
        }
        touch(DATA_PATH.'/lang/_initSiteLang.lock');
        // F('initSiteLang.lock', time());
    }
}
