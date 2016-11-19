<?php
/**
 * //TODO  以后统一优化成分类的多级选择widget
 * 部门选择
 * @example W('Department',array('tpl'=>'input','inputName'=>'depart','canChange'=>1,'sid'=>1,'defaultName'=>'无','defaultId'=>'0','callback'=>'contactBack'))
 * @author jason
 * @version TS3.0
 */
class DepartmentWidget extends Widget
{
    private static $rand = 1;
    public static $userDepartHash = array();

    /**
     * @param string tpl 部门选择类型 admin:下拉形式  input:表单输入形式   menu:菜单形式
     * @param string inputName 表单输入的值，只针对tpl=input
     * @param int sid 当前选择的部门ID
     * @param int canChange 是否可修改，只针对tpl=input有效
     * @param srting defaultName 默认的部门名称，只针对tpl=input有效
     * @param string defaultId 默认的部门ID，只针对tpl=input有效
     * @param string callback 选择部门之后的回调函数，只针对tpl=menu有效
     */
    public function render($data)
    {
        self::$rand++;
        $var['rand'] = self::$rand;
        $var['tpl'] = 'admin';

        $var = is_array($data) ? array_merge($var, $data) : $var;

        $var['defaultId'] = intval($var['defaultId']);

        if ($var['select'] == 1) {    //下拉形式
            //部门选择wigdet
            $var['pid'] = !empty($data['pid']) ? $data['pid'] : 0;
            $var['parentList'] = model('Department')->getHashDepartment($var['pid'], $var['sid'], $var['nosid'], intval($var['notop']));

            $content = $this->renderFile(dirname(__FILE__)."/{$var['tpl']}.html", $var);

            return $content;
        } else {
            //显示用户wigdet
            switch ($var['tpl']) {
                case 'input':
                    $departHash = model('Department')->getAllHash();
                    $var['defaultName'] = $departHash[$var['defaultId']]['title'];
                    break;    //input 输入框
                case 'menu':        //菜单展示
                //看看有没有子节点数据
                $var['pid'] = !empty($data['pid']) ? intval($data['pid']) : 0;
                //
                $var['sid'] = !empty($data['sid']) ? intval($data['sid']) : 0;

                //全部部门
                $pInfo[] = array('sid' => 0, 'pid' => 0, 'name' => L('PUBLIC_DEPARTMENT_ALL'));

                $list = $this->_getList($var['sid']);

                $childInfo = array();

                foreach ($list['_child'] as $v) {
                    $pInfo[] = array('sid' => $v['department_id'], 'pid' => $v['parent_dept_id'], 'name' => $v['title']);

                    if ($v['department_id'] == $var['sid'] || $v['department_id'] == $var['pid']) {
                        foreach ($v['_child'] as $vv) {
                            $childInfo[] = array('sid' => $vv['department_id'], 'pid' => $vv['parent_dept_id'], 'name' => $vv['title']);
                        }
                    }
                }
                if (!empty($var['pid'])) {
                    $ppid = model('Department')->getDepartment($sid);
                    $var['ppid'] = $ppid['parent_dept_id'];
                } else {
                    $var['ppid'] = 0;
                }

                $var['pInfo'] = $pInfo;
                $var['childInfo'] = $childInfo;

                break;
            }

            return $this->renderFile(dirname(__FILE__)."/{$var['tpl']}.html", $var);
        }
    }

    /**
     * 获取部门列表
     * @return array 部门列表
     */
    private function _getList($sid)
    {
        //判断是否有子节点

        $data = model('Department')->getDepartment($sid);
        if (!empty($sid)) {
            $list['_child'][0] = $data;
        } else {
            $list = $data;
        }

        //取父数据
        if (!empty($sid) && empty($data['_child'])) {
            $list = $this->_getList($data['parent_dept_id']);
        }

        return $list;
    }

   /**
    * 修改部门
    */
   public function change()
   {
       $var = $_REQUEST;
       $var['parentList'] = model('Department')->getHashDepartment(intval($var['pid']), $var['sid'], $var['nosid'], intval($var['notop']));

       return $this->renderFile(dirname(__FILE__).'/change.html', $var);
   }

   /**
    * 选择部门
    * @return array 已选择的部门
    */
   public function selectDepartment()
   {
       $return = array('status' => 1, 'data' => '');

       $return['data'] = model('Department')->getHashDepartment(t($_REQUEST['pid']), t($_REQUEST['sid']), t($_REQUEST['nosid']), t($_REQUEST['notop']));

       if (empty($return['data'])) {
           $return['data'] = array();
       }
       echo json_encode($return);
       exit();
   }
}
