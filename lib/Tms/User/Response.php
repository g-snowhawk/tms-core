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
    public function defaultView($restriction = null)
    {
        $this->checkPermission('user.read');

        $this->view->bind('users', parent::getUsers($restriction));

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

        $alias = $this->session->param('alias');
        if (!empty($alias)) {
            $alias_id = $this->db->get('id', 'user', 'uname=?', [$alias]);
            $this->request->param('id', $alias_id);
            $this->editAlias();
        }

        $aliases = $this->db->select('id,fullname,email,uname','user','WHERE alias=?',[$this->uid]);
        $this->view->bind('aliases', $aliases);

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
                'id, admin, fullname, company, email, url, zip, state, city, town,
                 address1, address2, tel, fax, create_date, modify_date',
                'user', 'id = ?', [$this->request->param('id')]
            );
            $stat = $this->db->select(
                '*', 'permission',
                'WHERE userkey = ? AND application IN (?,?)',
                [$this->request->param('id'), '', $this->currentApp()]
            );

            $perm = [];
            $applications = $this->navItems();
            foreach ($applications as $application) {
                $key = $application['code'].'.exec';
                $perm[$key] = '1';
            }

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
        $global = $this->getPrivileges($this->uid);
        foreach ($global as $tmp => $priv) {
            $parent = &$perms;
            $keys = explode('.', $tmp);
            foreach ($keys as $key) {
                if (empty($key)) {
                    continue;
                }
                if (!isset($parent[$key])) {
                    $parent[$key] = [];
                }
                $parent = &$parent[$key];
            }
            $parent = $priv;
            unset($parent);
        }

        $this->view->bind('perms', ['global' => $perms]);

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

    /**
     * Switch user account.
     */
    public function switchUser()
    {
        $this->checkPermission('system');

        $origin = $this->session->param('origin');
        if (is_array($origin)) {
            $origin[] = $this->session->param('uname');
        } else {
            $origin = [$this->session->param('uname')];
        }
        $this->session->param('origin', $origin);

        $uname = $this->db->get('uname', 'user', 'id=?', [$this->request->param('id')]);
        $securet = bin2hex(openssl_random_pseudo_bytes(16));
        $this->session->param('authorized', $this->app->ident($uname, $securet));
        $this->session->param('uname', $uname);
        $this->session->param('securet', $securet);

        $this->clearUserInfo();

        $mode = 'user.response';
        if (!$this->hasPermission('user.read')) {
            $mode = $this->app->getDefaultMode();
        }

        $this->app->execPlugin('afterSwitchUser');

        \P5\Http::redirect($this->app->systemURI()."?mode=$mode");
    }

    /**
     * Rewind user account.
     */
    public function rewind()
    {
        $origin = $this->session->param('origin');
        if (is_null($origin)) {
            return;
        }

        $uname = array_pop($origin);
        if (!empty($uname)) {
            $securet = bin2hex(openssl_random_pseudo_bytes(16));
            $this->session->param('authorized', $this->app->ident($uname, $securet));
            $this->session->param('uname', $uname);
            $this->session->param('securet', $securet);

            if (empty($origin)) {
                $this->session->clear('origin');
            } else {
                $this->session->param('origin', $origin);
            }
            $this->clearUserInfo();
        }

        \P5\Http::redirect($this->app->systemURI().'?mode=user.response');
    }

    /**
     * Show alias edit form.
     */
    public function editAlias()
    {
        if ($this->request->param('id') !== $this->uid) {
            $this->checkPermission('user.alias');
        }

        if ($this->request->method === 'post') {
            $post = $this->request->post();
        } else {
            $post = $this->db->get(
                'id, admin, fullname, email, url, zip, state, city, town,
                 address1, address2, tel, fax, create_date, modify_date',
                'user', 'id = ?', [$this->request->param('id')]
            );
        }

        $this->view->bind('post', $post);

        $globals = $this->view->param();
        $form = $globals['form'];
        $form['confirm'] = \P5\Lang::translate('CONFIRM_SAVE_DATA');
        $this->view->bind('form', $form);

        parent::defaultView('user-alias_edit');
    }

    /**
     * Show alias edit subform with Ajax.
     */
    public function editAliasSubform()
    {
        $status = $this->hasPermission('user.read');

        if (   $this->request->method === 'post'
            && $this->request->post('request_type') !== 'response-subform'
        ) {
            $post = $this->request->post();
        } else {
            $post = $this->db->get(
                'id, admin, fullname, email, url, zip, state, city, town,
                 address1, address2, tel, fax, create_date, modify_date',
                'user', 'id = ?', [$this->request->param('id')]
            );
        }
        $this->view->bind('post', $post);

        $response = $this->view->render('user/alias_edit_subform.tpl', true);

        $json = [
            'status' => $status,
            'response' => $response,
        ];

        header('Content-type: text/plain; charset=utf-8');
        echo json_encode($json);
        exit;
    }

    public function aliasList()
    {
        $aliases = $this->db->select('id,fullname,email,uname','user','WHERE alias=?',[$this->uid]);
        $this->view->bind('aliases', $aliases);

        $json = [
            'status' => 0,
            'source' => $this->view->render('user/alias_list.tpl', true)
        ];
        header('Content-type: text/plain; charset=utf-8');
        echo json_encode($json);
        exit;
    }
}
