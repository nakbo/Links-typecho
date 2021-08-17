<?php

/**
 * 友情链接 - 基于<a href="http://www.imhan.com">寒泥</a>的友情插件
 *
 * @package Links
 * @author 南博工作室
 * @version 1.0.0
 * @link https://github.com/krait-team/Links-typecho
 *
 */
class Links_Plugin implements Typecho_Plugin_Interface
{
    /**
     * @return string|void
     * @throws Typecho_Db_Exception
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        $adapterName = $db->getAdapterName();

        if (strpos($adapterName, 'Mysql') !== false) {
            try {
                $columns = $db->fetchAll($db->query("SHOW COLUMNS FROM {$prefix}links;"));
                $columns = array_map(function ($column) {
                    return $column['Field'];
                }, $columns);
                $alter = array(
                    'mail' => 'ALTER TABLE `' . $prefix . 'links` ADD `mail` VARCHAR(255) DEFAULT NULL;',
                    'desc' => 'ALTER TABLE `' . $prefix . 'links` CHANGE `description` `desc` VARCHAR(255) DEFAULT NULL;'
                );
                foreach ($alter as $column => $query) {
                    if (!in_array($column, $columns)) {
                        $db->query($query);
                    }
                }
            } catch (Exception $e) {
                $charset = $db->getConfig()['charset'] ?: 'utf8';
                $db->query('CREATE TABLE IF NOT EXISTS `' . $prefix . 'links` (
                `lid` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(96) DEFAULT NULL,
                `mail` VARCHAR(255) DEFAULT NULL,
                `url` VARCHAR(255) DEFAULT NULL,
                `image` VARCHAR(255) DEFAULT NULL,
                `desc` VARCHAR(255) DEFAULT NULL,
                `sort` VARCHAR(32) DEFAULT NULL,
                `user` TEXT,
                `order` INT(10) UNSIGNED DEFAULT 0,
                PRIMARY KEY  (`lid`)
              ) DEFAULT CHARSET=' . $charset);
            }
        } else if (strpos($adapterName, 'SQLite') !== false) {
            if ($db->fetchRow($db->query("SELECT name FROM sqlite_master WHERE TYPE='table' AND name='{$prefix}links';", Typecho_Db::READ))) {
                if ($rows = $db->fetchRow($db->select()->from('table.links'))) {
                    $alter = array(
                        'mail' => 'ALTER TABLE `' . $prefix . 'links` ADD COLUMN `mail` VARCHAR(255) DEFAULT NULL;',
                        'desc' => 'ALTER TABLE `' . $prefix . 'links` ADD COLUMN `desc` VARCHAR(255) DEFAULT NULL;',
                    );
                    foreach ($alter as $column => $query) {
                        if (!array_key_exists($column, $rows)) {
                            $db->query($query);
                        }
                    }
                }
            } else {
                $db->query('CREATE TABLE ' . $prefix . 'links ( 
                    "lid" INTEGER NOT NULL PRIMARY KEY, 
                    "name" VARCHAR(96) DEFAULT NULL,
                    "mail" VARCHAR(255) DEFAULT NULL,
                    "url" VARCHAR(255) DEFAULT NULL,
                    "image" VARCHAR(255) DEFAULT NULL,
                    "desc" VARCHAR(255) DEFAULT NULL,
                    "sort" VARCHAR(32) DEFAULT NULL,
                    "user" TEXT,
                    "order" INT(10) UNSIGNED DEFAULT 0);');
            }
        } else {
            throw new Typecho_Plugin_Exception(_t('你的适配器为%s，目前只支持Mysql和SQLite', $adapterName));
        }

        Typecho_Plugin::factory('Nabo_Links')->order = ['Links_Action', 'orderOf'];
        Typecho_Plugin::factory('Nabo_Links')->insert = ['Links_Action', 'insertOf'];
        Typecho_Plugin::factory('Nabo_Links')->modify = ['Links_Action', 'modifyOf'];
        Typecho_Plugin::factory('Nabo_Links')->delete = ['Links_Action', 'deleteOf'];
        Typecho_Plugin::factory('Nabo_Links')->select = ['Links_Action', 'selectOf'];

        Helper::addAction('links', 'Links_Action');
        Helper::addPanel(3, 'Links/Manage.php', '友情链接', '管理友链', 'administrator');

        return _t('友情插件已经激活');
    }

    /**
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Db_Exception
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        if (Helper::options()->plugin('Links')->allowDrop) {
            $db = Typecho_Db::get();
            $db->query("DROP TABLE `{$db->getPrefix()}links`", Typecho_Db::WRITE);
        }

        Helper::removeAction('links');
        Helper::removePanel(3, 'Links/Manage.php');
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $relations = new Typecho_Widget_Helper_Form_Element_Textarea(
            'relations', NULL, NULL,
            '友情关系', '这里根据自身特殊专用, 例如一行一个朋友的邮箱, 然后在主题里自定义使用');
        $form->addInput($relations);

        $drop = new Typecho_Widget_Helper_Form_Element_Radio(
            'allowDrop', array(
            '0' => '不删除',
            '1' => '删除',
        ), '0', '删数据表', '请选择是否在禁用插件时，删除友情链接的数据表，此表是本插件创建的。如果选择不删除，那么禁用后再次启用还是之前的用户数据就不用重新个人配置');
        $form->addInput($drop);
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * @param int $id
     * @return Typecho_Widget_Helper_Form
     * @throws Typecho_Db_Exception
     * @throws Typecho_Widget_Exception
     */
    public static function form($id = 0)
    {
        $options = Helper::options();
        $form = new Typecho_Widget_Helper_Form(Typecho_Common::url('/action/links', $options->index),
            Typecho_Widget_Helper_Form::POST_METHOD);

        $name = new Typecho_Widget_Helper_Form_Element_Text('name', NULL, NULL, _t('名称*'));
        $form->addInput($name);

        $mail = new Typecho_Widget_Helper_Form_Element_Text('mail', NULL, NULL, _t('邮箱'));
        $form->addInput($mail);

        $url = new Typecho_Widget_Helper_Form_Element_Text('url', NULL, 'https://', _t('地址*'));
        $form->addInput($url);

        $image = new Typecho_Widget_Helper_Form_Element_Text('image', NULL, NULL, _t('图片'), _t('需要以http://开头，留空表示没有链接图片'));
        $form->addInput($image);

        $desc = new Typecho_Widget_Helper_Form_Element_Textarea('desc', NULL, NULL, _t('描述'));
        $form->addInput($desc);

        $sort = new Typecho_Widget_Helper_Form_Element_Text('sort', NULL, NULL, _t('分类'), _t('建议以英文字母开头，只包含字母与数字'));
        $form->addInput($sort);

        $user = new Typecho_Widget_Helper_Form_Element_Textarea('user', NULL, NULL, _t('自定义数据'), _t('该项用于用户自定义数据扩展'));
        $form->addInput($user);

        $lid = new Typecho_Widget_Helper_Form_Element_Hidden('lid');
        $form->addInput($lid);

        $do = new Typecho_Widget_Helper_Form_Element_Hidden('do');
        $form->addInput($do);

        $submit = new Typecho_Widget_Helper_Form_Element_Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        if (empty($id)) {
            $action = 'insert';

            $do->value('insert');
            $submit->value(_t('增加链接'));
        } else {
            $action = 'update';

            $db = Typecho_Db::get();
            if (empty($link = $db->fetchRow($db->select()->from('table.links')->where('lid = ?', $id)))) {
                throw new Typecho_Widget_Exception(_t('链接不存在'), 404);
            }

            $lid->value($link['lid']);
            $name->value($link['name']);
            $mail->value($link['mail']);
            $url->value($link['url']);
            $image->value($link['image']);
            $desc->value($link['desc']);
            $user->value($link['user']);
            $sort->value($link['sort']);
            $do->value('update');
            $submit->value(_t('编辑链接'));
        }

        switch ($action) {
            case 'update':
                $lid->addRule('required', _t('链接主键不存在'));
                $lid->addRule(['Links_Plugin', 'exists'], _t('链接不存在'));
            case 'insert':
                $name->addRule('required', _t('必须填写链接名称'));
                $url->addRule('required', _t('必须填写链接地址'));
                $url->addRule('url', _t('不是一个合法的链接地址'));
                $image->addRule('url', _t('不是一个合法的图片地址'));
        }

        return $form;
    }

    /**
     * @param $lid
     * @return bool
     * @throws Typecho_Db_Exception
     */
    public static function exists($lid)
    {
        $db = Typecho_Db::get();
        return !empty($db->fetchRow($db->select()
            ->from('table.links')
            ->where('lid = ?', $lid)->limit(1)));
    }

    /**
     * @param null $pattern
     * @param int $limit
     * @param null $sort
     * @return string
     * @throws Typecho_Db_Exception
     */
    public static function formatOf($pattern = NULL, $limit = 0, $sort = NULL)
    {
        if (empty($pattern)) {
            return '';
        }

        $links = Links_Action::selectOf(
            $limit, $sort
        );

        $callback = '';
        $format = array('{lid}', '{name}', '{mail}',
            '{url}', '{sort}', '{desc}',
            '{description}', '{image}', '{user}'
        );
        foreach ($links as $link) {
            $callback .= str_replace($format,
                array($link['lid'], $link['name'], $link['mail'],
                    $link['url'], $link['sort'], $link['desc'],
                    $link['desc'], $link['image'], $link['user']
                ), $pattern
            );
        }
        unset($format);

        return $callback;
    }

    /**
     * @target 格式化
     */
    public static function output_str()
    {
        return call_user_func_array(['Links_Plugin', 'formatOf'], func_get_args());
    }

    /**
     * @target 输出
     */
    public static function output()
    {
        echo call_user_func_array(['Links_Plugin', 'formatOf'], func_get_args());
    }
}
