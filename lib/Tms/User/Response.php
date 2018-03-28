<?php
/**
 * This file is part of Tak-Me System.
 *
 * Copyright (c)2016 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Tms\User;

/**
 * User management request response class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Response extends \Tms\User
{
    /**
     * Object Constructor.
     */
    public function __construct()
    {
        $params = func_get_args();
        call_user_func_array('parent::__construct', $params);

        $this->view->bind(
            'header',
            ['title' => 'ユーザー管理', 'id' => 'user', 'class' => 'user']
        );
    }

    /**
     * Default view.
     */
    public function defaultView()
    {
        $this->checkPermission('user.read');

        $ret = $this->db->nsmGetDecendants(
            'children.id, children.company, children.email',
            '(SELECT * FROM table::user WHERE id = ?)',
            '(SELECT * FROM table::user WHERE restriction IS NULL)',
            [$this->uid]
        );
        $this->view->bind('users', $ret);

        $globals = $this->view->param();
        $form = $globals['form'];
        $form['confirm'] = \P5\Lang::translate('CONFIRM_DELETE_DATA');
        $this->view->bind('form', $form);

        parent::defaultView('user-default');
    }

    /**
     * Profile Edit.
     */
    public function profile()
    {
        $this->request->param('id', $this->uid);
        $this->edit(true);
    }

    /**
     * Show edit form.
     *
     * @param bool $profile
     */
    public function edit($profile = false)
    {
        if ($profile === false) {
            $this->checkPermission('user.read');
        }

        if ($this->request->method === 'post') {
            $post = $this->request->post();
        } else {
            $post = $this->db->get(
                'id, company, email, url, zip, state, city, town,
                 address1, address2, tel, fax, create_date, modify_date',
                'user', 'id = ?', [$this->request->param('id')]
            );
            $stat = $this->db->select(
                '*', 'permission',
                'WHERE userkey = ? AND application IN (?,?)',
                [$this->request->param('id'), '', $this->currentApp()]
            );

            $perm = [];
            foreach ($stat as $unit) {
                $tmp = [];
                $tmp[] = ($unit['filter1'] !== '0') ? $unit['filter1'] : '';
                $tmp[] = ($unit['filter2'] !== '0') ? $unit['filter2'] : '';
                $tmp[] = $unit['application'];
                $tmp[] = $unit['class'];
                $tmp[] = $unit['type'];
                $key = preg_replace('/^\.+/', '', implode('.', $tmp));
                $perm[$key] = $unit['priv'];
            }
            $post['perm'] = $perm;
        }

        if ($profile) {
            $post['profile'] = 1;
        }

        $this->view->bind('post', $post);

        $perms = [];
        $perms['global'] = $this->getPrivileges($this->uid);
        $this->view->bind('perms', $perms);

        $globals = $this->view->param();
        $form = $globals['form'];
        $form['confirm'] = \P5\Lang::translate('CONFIRM_SAVE_DATA');
        $this->view->bind('form', $form);

        $class = self::classFromApplicationName($this->session->param('application_name'));
        if (defined("$class::USER_EDIT_EXTENDS")) {
            $package = $class::USER_EDIT_EXTENDS;
            $this->view->bind('apps', new $package($this->app));
        }

        parent::defaultView('user-edit');
    }
}
