<?php

class ToolAction extends Action
{
    // 获取官方服务器上应用的信息给本地服务器
    public function downloadApp()
    {
        $map['develop_id'] = intval($_GET['develop_id']);
        $dao = D('develop', 'develop');

        $info = $dao->getDetailDevelop($map['develop_id']);
        $info['packageURL'] = getAttachUrl($info['file']['filename']);
        $info['app_name'] = $info['package'];

        // 记录下载数
        $dao->where($map)->setInc('download_count');

        echo json_encode($info);
    }

    // 自动获取升级包信息给本地服务器
    public function getVersionInfo()
    {
        $result = M('system_update')->where('status=1')->field('id,title,version,package')->findAll();
        foreach ($result as $k => $v) {
            $list[$v['id']] = $v;
            unset($result[$k]);
        }
        echo json_encode($list);
    }

    /**
     * 验证站点是否在官方服务器上注册.
     *
     * @return JSON 返回相关数据
     */
    public function checkedHost()
    {
        $host = t($_GET['h']);
        $result = D('DevelopRegistration', 'develop')->checked($host);
        $res = array();
        if ($result) {
            $res['status'] = 1;
            $res['info'] = '验证通过';
        } else {
            $res['status'] = 0;
            $res['info'] = '验证失败';
        }

        exit(json_encode($res));
    }
}
