<?php

class Links_Action extends Typecho_Widget implements Widget_Interface_Do
{
    /**
     * @param $link
     * @return mixed
     * @throws Typecho_Db_Exception
     */
    public static function insertOf($link)
    {
        $db = Typecho_Db::get();
        $link['order'] = $db->fetchObject($db->select(array('MAX(order)' => 'maxOrder'))
                ->from('table.links'))->maxOrder + 1;
        $link['lid'] = $db->query($db
            ->insert('table.links')
            ->rows($link));

        return $link;
    }

    /**
     * @param $link
     * @return mixed
     * @throws Typecho_Db_Exception
     */
    public static function modifyOf($link)
    {
        $db = Typecho_Db::get();
        $db->query($db->update('table.links')
            ->rows($link)
            ->where('lid = ?', $link['lid']));

        return $link;
    }

    /**
     * @param $list
     * @return int
     * @throws Typecho_Db_Exception
     */
    public static function deleteOf($list)
    {
        $db = Typecho_Db::get();
        $deleteCount = 0;
        foreach ($list as $lid) {
            if ($db->query($db->delete('table.links')->where('lid = ?', $lid))) {
                $deleteCount++;
            }
        }

        return $deleteCount;
    }

    /**
     * @param int $limit
     * @param null $sort
     * @return array
     * @throws Typecho_Db_Exception
     */
    public static function selectOf($limit = 0, $sort = NULL)
    {
        $db = Typecho_Db::get();
        $select = $db->select()->from('table.links');
        if (isset($sort)) {
            $select->where('table.links.sort = ?', $sort);
        }
        $select->order('table.links.order');
        if ($limit > 0) {
            $select->limit($limit);
        }

        return $db->fetchAll($select);
    }

    /**
     * @param $list
     * @throws Typecho_Db_Exception
     */
    public static function orderOf($list)
    {
        $db = Typecho_Db::get();
        foreach ($list as $sort => $lid) {
            $db->query($db->update('table.links')
                ->rows(array('order' => $sort + 1))
                ->where('lid = ?', $lid));
        }
    }

    /**
     * @throws Typecho_Db_Exception
     * @throws Typecho_Exception
     * @throws Typecho_Widget_Exception
     */
    public function addOf()
    {
        if (Links_Plugin::form()->validate()) {
            $this->response->goBack();
        }

        /** 取出数据 */
        $link = $this->request->from('name', 'mail', 'url', 'sort', 'image', 'desc', 'user');

        /** 插入数据 */
        $link = self::insertOf($link);

        /** 设置高亮 */
        $this->widget('Widget_Notice')->highlight('link-' . $link['lid']);

        /** 提示信息 */
        $this->widget('Widget_Notice')->set(_t('链接 <a href="%s">%s</a> 已经被增加',
            $link['url'], $link['name']), NULL, 'success');

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('extending.php?panel=Links%2FManage.php', Helper::options()->adminUrl));
    }

    /**
     * @throws Typecho_Db_Exception
     * @throws Typecho_Exception
     * @throws Typecho_Widget_Exception
     */
    public function saveOf()
    {
        if (Links_Plugin::form($this->request->lid)->validate()) {
            $this->response->goBack();
        }

        /** 取出数据 */
        $link = $this->request->from('lid', 'name', 'mail', 'sort', 'image', 'url', 'desc', 'user');

        /** 更新数据 */
        self::modifyOf($link);

        /** 设置高亮 */
        $this->widget('Widget_Notice')->highlight('link-' . $link['lid']);

        /** 提示信息 */
        $this->widget('Widget_Notice')->set(_t('链接 <a href="%s">%s</a> 已经被更新',
            $link['url'], $link['name']), NULL, 'success');

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('extending.php?panel=Links%2FManage.php', Helper::options()->adminUrl));
    }

    /**
     * @throws Typecho_Db_Exception
     * @throws Typecho_Exception
     */
    public function removeOf()
    {
        $lids = $this->request->filter('int')->getArray('lid');
        $deleteCount = self::deleteOf($lids);

        /** 提示信息 */
        $this->widget('Widget_Notice')->set($deleteCount > 0 ? _t('链接已经删除') : _t('没有链接被删除'), NULL,
            $deleteCount > 0 ? 'success' : 'notice');

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('extending.php?panel=Links%2FManage.php', Helper::options()->adminUrl));
    }

    /**
     * @throws Typecho_Db_Exception
     */
    public function sortOf()
    {
        $links = $this->request->filter('int')->getArray('lid');
        self::orderOf($links);
    }

    /**
     * 接口需要实现的入口函数
     *
     * @access public
     * @return void
     * @throws Typecho_Exception
     */
    public function action()
    {
        Typecho_Widget::widget('Widget_User')
            ->pass('administrator');

        $this->on($this->request->is('do=insert'))->addOf();
        $this->on($this->request->is('do=update'))->saveOf();
        $this->on($this->request->is('do=delete'))->removeOf();
        $this->on($this->request->is('do=sort'))->sortOf();
        $this->response->redirect(Helper::options()->adminUrl);
    }
}
