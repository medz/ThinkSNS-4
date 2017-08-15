<?php

//显示无限极分类的HTML代码
/**
 * $field = array('id','name','pid','sort')
 *   <tr>.
 */
//TODO 可以移动到functions中
function showCatetree($data, $field, $func, $p = array())
{
    $pid = empty($p) ? '0' : $p[$field['id']];
    $pname = empty($p) ? '-' : $p[$field['name']];
    //$display = empty($p) ? "":"style='display:none'";
    $display = '';
    $html = '<table width="100%" id="table'.$pid.'" '.$display.'>';
    foreach ($data as $key => $val) {    //每行操作
        $html .= "<tr overstyle='on'>";
        foreach ($val as $k => $v) {
            if (!in_array($k, $field)) {
                continue;
            }
            if ($k == $field['pid']) {
                $html .= "<td catetd ='yes' rel='{$val[$field['id']]}' width='20%'>".$pname.'</td>';
            } else {
                $html .= "<td catetd ='yes' rel='{$val[$field['id']]}' width='20%'>".$v.'</td>';
            }
        }
        $html .= "<td><span rel='edit' cateid='".$val[$field['id']]."' func='{$func}'>".L('PUBLIC_MODIFY')."</span>
			<span rel='move' cateid='".$val[$field['id']]."' func='{$func}'>".L('PUBLIC_MOVES')."</span>	
			<span rel='del' cateid='".$val[$field['id']]."' func='{$func}'>".L('PUBLIC_STREAM_DELETE').'</span></td></tr>';
        //递归
        if (!empty($val['_child'])) {
            $html .= "<tr><td colspan='10'>".showCatetree($val['_child'], $field, $func, $val).'</td></tr>';
        }
    }

    return $html.'</table>';
}
//传统形式显示无限极分类树
/**
 *   <tr><td>ID</td><td>部门</td><td>排序</td><td>操作</td></tr>
 *   {:showTree($tree,$field,$_func)}.
 *
 * @param unknown_type $data
 * @param unknown_type $field
 * @param unknown_type $func
 * @param unknown_type $p
 */
function showTree($data, $field, $func, $p = '')
{
    $html = '';
    $p = empty($p) ? '' : $p.' - ';
    // $big  = empty($p) ? "style='font-weight:bold'" : '';
    foreach ($data as $key => $val) {
        $html .= "<tr><td>{$val[$field['id']]}</td>
				 <td>{$p}{$val[$field['name']]}</td>"
                 //<td>{$val[$field['sort']]}</td>
."<td><span rel='edit' cateid='".$val[$field['id']]."' func='{$func}'>".L('PUBLIC_MODIFY')."</span>-
			<span rel='move' cateid='".$val[$field['id']]."' func='{$func}'>".L('PUBLIC_MOVES')."</span>-
			<span rel='del' cateid='".$val[$field['id']]."' func='{$func}'>".L('PUBLIC_STREAM_DELETE').'</span></td></tr>';
        if (!empty($val['_child'])) {
            $html .= showTree($val['_child'], $field, $func, $p.$val[$field['name']]);
        }
    }

    return $html;
}

function admin_formatsize($fileSize)
{
    $size = sprintf('%u', $fileSize);
    if ($size == 0) {
        return '0 Bytes';
    }
    $sizename = array(' Bytes', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB');

    return round($size / pow(1024, ($i = floor(log($size, 1024)))), 2).$sizename[$i];
}

//递归取给定的目录的文件MD5列表
function _makeMd5FileToArray($dir, $res = array())
{
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            $path = str_replace(SITE_PATH.'/', '', $dir);
            while (($file = readdir($dh)) !== false) {
                if ($file == '.' or $file == '..' or $file == '.svn') {
                    continue;
                }

                if (is_dir($dir.$file)) {
                    $res = _makeMd5FileToArray($dir.$file.'/', $res);
                } else {
                    $res[$path.$file] = md5_file($dir.$file);
                }
            }
        }
        closedir($dh);
    }

    return $res;
}
/**
 *把给定的目录生成一个文件MD5列表.
 *
 * @param array|string $dir  目录路径
 * @param string       $type 类型：core 核心 app 应用 plug 插件 theme 模板
 * @param string       $name �
 * 名
 */
function makeMd5File($dir, $type, $name)
{
    if (!is_array($dir)) {
        $dir = array($dir);
    }

    $arr = array();
    foreach ($dir as $path) {
        $path = SITE_PATH.'/'.$path.'/';
        $res = _makeMd5FileToArray($path);
        $arr = array_merge($arr, $res);
    }

    return F('md5FileInfo_'.$type.'_'.$name, $arr, DATA_PATH.'/update');
}

// 获取图片地址 - 兼容云
function getImageUrlApp($file, $width = '0', $height = 'auto', $cut = false, $replace = false)
{
    $cloud = model('CloudImage');
    if ($cloud->isOpen()) {
        $imageUrl = $cloud->getImageUrl($file, $width, $height, $cut);
    } else {
        if ($width > 0) {
            $thumbInfo = getThumbImage($file, $width, $height, $cut, $replace);
            $imageUrl = C('TS_UPDATE_SITE').'/data/upload/'.ltrim($thumbInfo['src'], '/');
        } else {
            $imageUrl = C('TS_UPDATE_SITE').'/data/upload/'.ltrim($file, '/');
        }
    }

    return $imageUrl;
}
