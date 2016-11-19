<?php

// +----------------------------------------------------------------------
// | ThinkPHP
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$

/**
 +------------------------------------------------------------------------------
 * HTML标签库解析类
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Template
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
import('TagLib');
class TagLibHtml extends TagLib
{
    //类定义开始

    /**
     +----------------------------------------------------------
     * editor标签解析 插入可视化编辑器
     * 格式： <html:editor id="editor" name="remark" type="FCKeditor" content="{$vo.remark}" />
     +----------------------------------------------------------
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _editor($attr)
    {
        $tag = $this->parseXmlAttr($attr, 'editor');
        $id = !empty($tag['id']) ? $tag['id'] : '_editor';
        $name = $tag['name'];
        $style = !empty($tag['style']) ? $tag['style'] : '';
        $width = !empty($tag['width']) ? $tag['width'] : '100%';
        $height = !empty($tag['height']) ? $tag['height'] : '320px';
        $content = $tag['content'];
        $type = $tag['type'] ;
        switch (strtoupper($type)) {
            case 'FCKEDITOR':
                $parseStr = '<!-- 编辑器调用开始 --><script type="text/javascript" src="__ROOT__/Public/Js/FCKeditor/fckeditor.js"></script><textarea id="'.$id.'" name="'.$name.'">'.$content.'</textarea><script type="text/javascript"> var oFCKeditor = new FCKeditor( "'.$id.'","'.$width.'","'.$height.'" ) ; oFCKeditor.BasePath = "__ROOT__/Public/Js/FCKeditor/" ; oFCKeditor.ReplaceTextarea() ;function resetEditor(){setContents("'.$id.'",document.getElementById("'.$id.'").value)}; function saveEditor(){document.getElementById("'.$id.'").value = getContents("'.$id.'");} function InsertHTML(html){ var oEditor = FCKeditorAPI.GetInstance("'.$id.'") ;if (oEditor.EditMode == FCK_EDITMODE_WYSIWYG ){oEditor.InsertHtml(html) ;}else	alert( "FCK必须处于WYSIWYG模式!" ) ;}</script> <!-- 编辑器调用结束 -->';
                break;
            case 'EWEBEDITOR':
                $parseStr = "<!-- 编辑器调用开始 --><script type='text/javascript' src='__ROOT__/Public/Js/eWebEditor/js/edit.js'></script><input type='hidden'  id='{$id}' name='{$name}'  value='{$conent}'><iframe src='__ROOT__/Public/Js/eWebEditor/ewebeditor.htm?id={$name}' frameborder=0 scrolling=no width='{$width}' height='{$height}'></iframe><script type='text/javascript'>function saveEditor(){document.getElementById('{$id}').value = getHTML();} </script><!-- 编辑器调用结束 -->";
                break;
            case 'NETEASE':
                $parseStr = '<!-- 编辑器调用开始 --><textarea id="'.$id.'" name="'.$name.'" style="display:none">'.$content.'</textarea><iframe ID="Editor" name="Editor" src="__ROOT__/Public/Js/HtmlEditor/index.html?ID='.$name.'" frameBorder="0" marginHeight="0" marginWidth="0" scrolling="No" style="height:'.$height.';width:'.$width.'"></iframe><!-- 编辑器调用结束 -->';
                break;
            case 'UBB':
                $parseStr = '<script type="text/javascript" src="__ROOT__/Public/Js/UbbEditor.js"></script><div style="padding:1px;width:'.$width.';border:1px solid silver;float:left;"><script LANGUAGE="JavaScript"> showTool(); </script></div><div><TEXTAREA id="UBBEditor" name="'.$name.'"  style="clear:both;float:none;width:'.$width.';height:'.$height.'" >'.$content.'</TEXTAREA></div><div style="padding:1px;width:'.$width.';border:1px solid silver;float:left;"><script LANGUAGE="JavaScript">showEmot();  </script></div>';
                break;
            default:
                $parseStr = '<textarea id="'.$id.'" style="'.$style.'" name="'.$name.'" >'.$content.'</textarea>';
        }

        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * link标签解析
     * 格式： <html:link file="" type="" />
     +----------------------------------------------------------
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _link($attr)
    {
        $tag = $this->parseXmlAttr($attr, 'link');
        $file = $tag['href'];
        $type = isset($tag['type']) ?
                    strtolower($tag['type']) :
                    strtolower(substr(strrchr($file, '.'), 1));
        if ($type == 'js') {
            $parseStr = "<script type='text/javascript' src='".$file."'></script> ";
        } elseif ($type == 'css') {
            $parseStr = "<link rel='stylesheet' type='text/css' href='".$file."' />";
        }

        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * link标签解析
     * 格式： <html:link file="" type="" />
     +----------------------------------------------------------
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _import($attr)
    {
        $tag = $this->parseXmlAttr($attr, 'import');
        $file = $tag['file'];
        $basepath = !empty($tag['basepath']) ? $tag['basepath'] : WEB_PUBLIC_URL;
        $type = !empty($tag['type']) ? strtolower($tag['type']) : 'js';
        if ($type == 'js') {
            $parseStr = "<script type='text/javascript' src='".$basepath.'/'.str_replace(array('.', '#'), array('/', '.'), $file).'.js'."'></script> ";
        } elseif ($type == 'css') {
            $parseStr = "<link rel='stylesheet' type='text/css' href='".$basepath.'/'.str_replace(array('.', '#'), array('/', '.'), $file).'.css'."' />";
        }

        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * imageBtn标签解析
     * 格式： <html:imageBtn type="" value="" />
     +----------------------------------------------------------
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _imageBtn($attr)
    {
        $tag = $this->parseXmlAttr($attr, 'imageBtn');
        $name = $tag['name'];                //名称
        $value = $tag['value'];                //文字
        $id = $tag['id'];                //ID
        $style = $tag['style'];                //样式名
        $click = $tag['click'];                //点击
        $type = empty($tag['type']) ? 'button' : $tag['type'];                //按钮类型

        if (!empty($name)) {
            $parseStr = '<div class="'.$style.'" ><input type="'.$type.'" id="'.$id.'" name="'.$name.'" value="'.$value.'" onclick="'.$click.'" class="'.$name.' imgButton"></div>';
        } else {
            $parseStr = '<div class="'.$style.'" ><input type="'.$type.'" id="'.$id.'"  name="'.$name.'" value="'.$value.'" onclick="'.$click.'" class="button"></div>';
        }

        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * imageLink标签解析
     * 格式： <html:imageLink type="" value="" />
     +----------------------------------------------------------
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _imgLink($attr)
    {
        $tag = $this->parseXmlAttr($attr, 'imgLink');
        $name = $tag['name'];                //名称
        $alt = $tag['alt'];                //文字
        $id = $tag['id'];                //ID
        $style = $tag['style'];                //样式名
        $click = $tag['click'];                //点击
        $type = $tag['type'];                //点击
        if (empty($type)) {
            $type = 'button';
        }
        $parseStr = '<span class="'.$style.'" ><input title="'.$alt.'" type="'.$type.'" id="'.$id.'"  name="'.$name.'" onmouseover="this.style.filter=\'alpha(opacity=100)\'" onmouseout="this.style.filter=\'alpha(opacity=80)\'" onclick="'.$click.'" align="absmiddle" class="'.$name.' imgLink"></span>';

        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * select标签解析
     * 格式： <html:select options="name" selected="value" />
     +----------------------------------------------------------
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _select($attr)
    {
        $tag = $this->parseXmlAttr($attr, 'select');
        $name = $tag['name'];
        $options = $tag['options'];
        $values = $tag['values'];
        $output = $tag['output'];
        $multiple = $tag['multiple'];
        $id = $tag['id'];
        $size = $tag['size'];
        $first = $tag['first'];
        $selected = $tag['selected'];
        $style = $tag['style'];
        $ondblclick = $tag['dblclick'];
        $onchange = $tag['change'];

        if (!empty($multiple)) {
            $parseStr = '<select id="'.$id.'" name="'.$name.'" ondblclick="'.$ondblclick.'" onchange="'.$onchange.'" multiple="multiple" class="'.$style.'" size="'.$size.'" >';
        } else {
            $parseStr = '<select id="'.$id.'" name="'.$name.'" onchange="'.$onchange.'" ondblclick="'.$ondblclick.'" class="'.$style.'" >';
        }
        if (!empty($first)) {
            $parseStr .= '<option value="" >'.$first.'</option>';
        }
        if (!empty($options)) {
            $parseStr .= '<?php  foreach($'.$options.' as $key=>$val) { ?>';
            if (!empty($selected)) {
                $parseStr .= '<?php if(!empty($'.$selected.') && ($'.$selected.' == $key || in_array($key,$'.$selected.'))) { ?>';
                $parseStr .= '<option selected="selected" value="<?php echo $key ?>"><?php echo $val ?></option>';
                $parseStr .= '<?php }else { ?><option value="<?php echo $key ?>"><?php echo $val ?></option>';
                $parseStr .= '<?php } ?>';
            } else {
                $parseStr .= '<option value="<?php echo $key ?>"><?php echo $val ?></option>';
            }
            $parseStr .= '<?php } ?>';
        } elseif (!empty($values)) {
            $parseStr .= '<?php  for($i=0;$i<count($'.$values.');$i++) { ?>';
            if (!empty($selected)) {
                $parseStr .= '<?php if(isset($'.$selected.') && ((is_string($'.$selected.') && $'.$selected.' == $'.$values.'[$i]) || (is_array($'.$selected.') && in_array($'.$values.'[$i],$'.$selected.')))) { ?>';
                $parseStr .= '<option selected="selected" value="<?php echo $'.$values.'[$i] ?>"><?php echo $'.$output.'[$i] ?></option>';
                $parseStr .= '<?php }else { ?><option value="<?php echo $'.$values.'[$i] ?>"><?php echo $'.$output.'[$i] ?></option>';
                $parseStr .= '<?php } ?>';
            } else {
                $parseStr .= '<option value="<?php echo $'.$values.'[$i] ?>"><?php echo $'.$output.'[$i] ?></option>';
            }
            $parseStr .= '<?php } ?>';
        }
        $parseStr .= '</select>';

        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * checkbox标签解析
     * 格式： <html:checkbox checkboxs="" checked="" />
     +----------------------------------------------------------
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _checkbox($attr)
    {
        $tag = $this->parseXmlAttr($attr, 'checkbox');
        $name = $tag['name'];
        $checkboxes = $tag['checkboxes'];
        $checked = $tag['checked'];
        $separator = $tag['separator'];
        $checkboxes = $this->tpl->get($checkboxes);
        $checked = $this->tpl->get($checked) ? $this->tpl->get($checked) : $checked;
        $parseStr = '';
        foreach ($checkboxes as $key => $val) {
            if ($checked == $key || in_array($key, $checked)) {
                $parseStr .= '<input type="checkbox" checked="checked" name="'.$name.'[]" value="'.$key.'">'.$val.$separator;
            } else {
                $parseStr .= '<input type="checkbox" name="'.$name.'[]" value="'.$key.'">'.$val.$separator;
            }
        }

        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * radio标签解析
     * 格式： <html:radio radios="name" checked="value" />
     +----------------------------------------------------------
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _radio($attr)
    {
        $tag = $this->parseXmlAttr($attr, 'radio');
        $name = $tag['name'];
        $radios = $tag['radios'];
        $checked = $tag['checked'];
        $separator = $tag['separator'];
        $radios = $this->tpl->get($radios);
        $checked = $this->tpl->get($checked) ? $this->tpl->get($checked) : $checked;
        $parseStr = '';
        foreach ($radios as $key => $val) {
            if ($checked == $key) {
                $parseStr .= '<input type="radio" checked="checked" name="'.$name.'[]" value="'.$key.'">'.$val.$separator;
            } else {
                $parseStr .= '<input type="radio" name="'.$name.'[]" value="'.$key.'">'.$val.$separator;
            }
        }

        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * list标签解析
     * 格式： <html:list datasource="" show="" />
     *
     +----------------------------------------------------------
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function _list($attr)
    {
        $tag = $this->parseXmlAttr($attr, 'list');
        $id = $tag['id'];                       //表格ID
        $datasource = $tag['datasource'];               //列表显示的数据源VoList名称
        $pk = empty($tag['pk']) ? 'id' : $tag['pk']; //主键名，默认为id
        $style = $tag['style'];                    //样式名
        $name = !empty($tag['name']) ? $tag['name'] : 'vo';                 //Vo对象名
        $action = $tag['action'];                   //是否显示功能操作
        $checkbox = $tag['checkbox'];                 //是否显示Checkbox
        if (isset($tag['actionlist'])) {
            $actionlist = explode(',', trim($tag['actionlist']));    //指定功能列表
        }

        if (substr($tag['show'], 0, 1) == '$') {
            $show = $this->tpl->get(substr($tag['show'], 1));
        } else {
            $show = $tag['show'];
        }
        $show = explode(',', $show);                //列表显示字段列表

        //计算表格的列数
        $colNum = count($show);
        if (!empty($checkbox)) {
            $colNum++;
        }
        if (!empty($action)) {
            $colNum++;
        }

        //显示开始
        $parseStr = "<!-- Think 系统列表组件开始 -->\n";
        $parseStr .= '<table id="'.$id.'" class="'.$style.'" cellpadding=0 cellspacing=0 >';
        $parseStr .= '<tr><td height="5" colspan="'.$colNum.'" class="topTd" ></td></tr>';
        $parseStr .= '<tr class="row" >';
        //列表需要显示的字段
        $fields = array();
        foreach ($show as $key => $val) {
            $fields[] = explode(':', $val);
        }
        if (!empty($checkbox) && 'true' == strtolower($checkbox)) {
            //如果指定需要显示checkbox列
            $parseStr .= '<th width="8"><input type="checkbox" id="check" onclick="CheckAll(\''.$id.'\')"></th>';
        }
        foreach ($fields as $field) {
            //显示指定的字段
            $property = explode('|', $field[0]);
            $showname = explode('|', $field[1]);
            if (isset($showname[1])) {
                $parseStr .= '<th width="'.$showname[1].'">';
            } else {
                $parseStr .= '<th>';
            }
            $showname[2] = isset($showname[2]) ? $showname[2] : $showname[0];
            $parseStr .= '<a href="javascript:sortBy(\''.$property[0].'\',\'{$sort}\',\''.ACTION_NAME.'\')" title="按照'.$showname[2].'{$sortType} ">'.$showname[0].'<eq name="order" value="'.$property[0].'" ><img src="../Public/images/{$sortImg}.gif" width="12" height="17" border="0" align="absmiddle"></eq></a></th>';
        }
        if (!empty($action)) {
            //如果指定显示操作功能列
            $parseStr .= '<th >操作</th>';
        }

        $parseStr .= '</tr>';
        $parseStr .= '<volist name="'.$datasource.'" id="'.$name.'" ><tr class="row" onmouseover="over(event)" onmouseout="out(event)" onclick="change(event)" >';    //支持鼠标移动单元行颜色变化 具体方法在js中定义

        if (!empty($checkbox)) {
            //如果需要显示checkbox 则在每行开头显示checkbox
            $parseStr .= '<td><input type="checkbox" name="key"	value="{$'.$name.'.'.$pk.'}"></td>';
        }
        foreach ($fields as $field) {
            //显示定义的列表字段
            $parseStr .= '<td>';
            if (!empty($field[2])) {
                // 支持列表字段链接功能 具体方法由JS函数实现
                $href = explode('|', $field[2]);
                if (count($href) > 1) {
                    //指定链接传的字段值
                    // 支持多个字段传递
                    $array = explode('^', $href[1]);
                    if (count($array) > 1) {
                        foreach ($array as $a) {
                            $temp[] = '\'{$'.$name.'.'.$a.'|addslashes}\'';
                        }
                        $parseStr .= '<a href="javascript:'.$href[0].'('.implode(',', $temp).')">';
                    } else {
                        $parseStr .= '<a href="javascript:'.$href[0].'(\'{$'.$name.'.'.$href[1].'|addslashes}\')">';
                    }
                } else {
                    //如果没有指定默认传编号值
                    $parseStr .= '<a href="javascript:'.$field[2].'(\'{$'.$name.'.'.$pk.'|addslashes}\')">';
                }
            }
            if (strpos($field[0], '^')) {
                $property = explode('^', $field[0]);
                foreach ($property as $p) {
                    $unit = explode('|', $p);
                    if (count($unit) > 1) {
                        $parseStr .= '{$'.$name.'.'.$unit[0].'|'.$unit[1].'} ';
                    } else {
                        $parseStr .= '{$'.$name.'.'.$p.'} ';
                    }
                }
            } else {
                $property = explode('|', $field[0]);
                if (count($property) > 1) {
                    $parseStr .= '{$'.$name.'.'.$property[0].'|'.$property[1].'}';
                } else {
                    $parseStr .= '{$'.$name.'.'.$field[0].'}';
                }
            }
            if (!empty($field[2])) {
                $parseStr .= '</a>';
            }
            $parseStr .= '</td>';
        }
        if (!empty($action)) {
            //显示功能操作
            if (!empty($actionlist[0])) {
                //显示指定的功能项
                $parseStr .= '<td>';
                foreach ($actionlist as $val) {
                    if (strpos($val, ':')) {
                        $a = explode(':', $val);
                        $b = explode('|', $a[1]);
                        if (count($b) > 1) {
                            $c = explode('|', $a[0]);
                            if (count($c) > 1) {
                                $parseStr .= '<a href="javascript:'.$c[1].'(\'{$'.$name.'.'.$pk.'}\')"><?php if(0== (is_array($'.$name.')?$'.$name.'["status"]:$'.$name.'->status)){ ?>'.$b[1].'<?php } ?></a><a href="javascript:'.$c[0].'({$'.$name.'.'.$pk.'})"><?php if(1== (is_array($'.$name.')?$'.$name.'["status"]:$'.$name.'->status)){ ?>'.$b[0].'<?php } ?></a> ';
                            } else {
                                $parseStr .= '<a href="javascript:'.$a[0].'(\'{$'.$name.'.'.$pk.'}\')"><?php if(0== (is_array($'.$name.')?$'.$name.'["status"]:$'.$name.'->status)){ ?>'.$b[1].'<?php } ?><?php if(1== (is_array($'.$name.')?$'.$name.'["status"]:$'.$name.'->status)){ ?>'.$b[0].'<?php } ?></a> ';
                            }
                        } else {
                            $parseStr .= '<a href="javascript:'.$a[0].'(\'{$'.$name.'.'.$pk.'}\')">'.$a[1].'</a> ';
                        }
                    } else {
                        $array = explode('|', $val);
                        if (count($array) > 2) {
                            $parseStr .= ' <a href="javascript:'.$array[1].'(\'{$'.$name.'.'.$array[0].'}\')">'.$array[2].'<a> ';
                        } else {
                            $parseStr .= ' {$'.$name.'.'.$val.'} ';
                        }
                    }
                }
                $parseStr .= '</td>';
            }
        }
        $parseStr .= '</tr></volist><tr><td height="5" colspan="'.$colNum.'" class="bottomTd"></td></tr></table>';
        $parseStr .= "\n<!-- Think 系统列表组件结束 -->\n";

        return $parseStr;
    }
}//类定义结束
