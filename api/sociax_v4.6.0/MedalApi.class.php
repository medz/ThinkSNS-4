<?php
/**
 * 勋章API.
 *
 * @author Medz Seven <lovevipdsw@vip.qq.com>
 **/
class MedalApi extends Api
{
    /**
     * 获取�
     * �部勋章.
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getAll()
    {
        $all = model('Medal')->order('`id` ASC')->select();

        $medals = array();
        is_array($all) and $medals = $this->formatMedal($all);

        unset($all);

        return Ts\Service\ApiMessage::withArray($medals, 1, '');
        // return $medals;
    }

    /**
     * 获取用户勋章.
     *
     * @request int [$uid] 获取的用户ID，默认可以不传则表示获取当前登录用户
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getUser()
    {
        /* # 获取用户ID */
        $uid = $this->data['uid'] > 0 ? $this->data['uid'] : $this->mid;

        /* # 构建需要查询的SQL */
        $sql = 'SELECT `medal`.* FROM `%s` AS `user` INNER JOIN `%s` AS `medal` ON `user`.`medal_id` = `medal`.`id` WHERE `user`.`uid` = %d ORDER BY `user`.`ctime` DESC';
        $sql = sprintf($sql, D('medal_user')->getTableName(), D('medal')->getTableName(), intval($uid));

        /* # 查询数据 */
        $all = D()->query($sql);

        /* # 取得格式化的数据 */
        $medals = array();
        is_array($all) and $medals = $this->formatMedal($all);

        /* # 注销无用的数据 */
        unset($uid, $sql, $all);

        /* # 返回数据 */
        return Ts\Service\ApiMessage::withArray($medals, 1, '');
        // return $medals;
    }

    /**
     * 格式化出需要的数据.
     *
     * @param array $medals 数据库原始勋章数据
     * @param array [$data] 额外携带的数据
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    private function formatMedal(array $medals, array $data = array())
    {
        foreach ($medals as $value) {
            $medal = array();
            $medal['id'] = $value['id'];
            $medal['name'] = $value['name'];
            $medal['desc'] = $value['desc'];
            $medal['icon'] = explode('|', $value['src']);
            $medal['icon'] = getImageUrl($medal['icon']['1']);
            $medal['show'] = explode('|', $value['share_card']);
            $medal['show'] = getImageUrl($medal['show'][1]);
            array_push($data, $medal);
        }

        unset($medals, $value, $medal);

        return Ts\Service\ApiMessage::withArray($data, 1, '');
        // return $data;
    }
} // END class MedalApi extends Api
