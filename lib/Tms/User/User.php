<?php
/**
 * This file is part of Tak-Me System.
 *
 * Copyright (c)2016 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Tms;

/**
 * User management class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class User extends \Tms\Common
{
    /*
     * Using common accessor methods
     */
    use \Tms\Accessor;

    /**
     * User properties.
     *
     * @var array
     */
    private $userinfo;

    /**
     * Current user ID.
     *
     * @var int
     */
    private $uid;

    /*
     * Permission table keys
     *
     * @var array
     */
    private $permission_table_keys = ['userkey','filter1','filter2','application','class','type'];

    /**
     * Object constructor.
     */
    public function __construct()
    {
        $params = func_get_args();
        call_user_func_array('parent::__construct', $params);

        if (is_null($this->userinfo)) {
            $this->setUserInfo();
        }
    }

    /**
     * Load user information from database
     */
    private function setUserInfo()
    {
        $get = $this->db->select(
            '*', 'user', 'WHERE uname = ?',
            [$this->session->param('uname')]
        );
        if (isset($get[0])) {
            $this->uid = $get[0]['id'];
            $this->userinfo = $get[0];
        }
    }

    /**
     * Save the data.
     *
     * @return bool
     */
    protected function save()
    {
        if ($this->request->param('profile') === '1') {
            $this->request->post('id', $this->uid);
        } else {
            $id = $this->request->POST('id');
            $check = (empty($id)) ? 'create' : 'update';
            $this->checkPermission('user.'.$check);
        }

        $post = $this->request->post();

        $table = 'user';
        $skip = ['id', 'admin', 'create_date', 'modify_date'];

        $valid = [];
        $valid[] = ['vl_company', 'company', 'empty'];
        $valid[] = ['vl_email', 'email', 'empty'];
        if (empty($post['id'])) {
            $valid[] = ['vl_uname', 'uname', 'empty'];
        }

        if (!$this->validate($valid)) {
            return false;
        }
        $this->db->begin();

        $fields = $this->db->getFields($this->db->TABLE($table));
        $permissions = [];
        $save = [];
        $raw = [];
        foreach ($fields as $field) {
            if (in_array($field, $skip)) {
                continue;
            }
            if (isset($post[$field])) {
                if ($field === 'upass') {
                    if (!empty($post[$field])) {
                        $save[$field] = \P5\Security::encrypt($post[$field], '', $this->app->cnf('global:password_encrypt_algorithm'));
                    }
                    continue;
                }
                $save[$field] = $post[$field];
            }
        }
        if (empty($post['id'])) {
            $parent = '(SELECT * FROM table::user WHERE id = ?)';
            $unit = $this->db->nsmGetPosition($parent, 'table::user', [$this->uid]);
            $parent_lft = (float) $unit['lft'];
            $parent_rgt = (float) $unit['rgt'];

            $save['lft'] = ($parent_lft * 2 + $parent_rgt) / 3;
            $save['rgt'] = ($parent_lft + $parent_rgt * 2) / 3;

            $raw = ['create_date' => 'CURRENT_TIMESTAMP'];
            if (false !== $result = $this->db->insert($table, $save, $raw)) {
                $post['id'] = $this->db->lastInsertId(null, 'id');
            }
        } else {
            $result = $this->db->update($table, $save, 'id = ?', [$post['id']], $raw);
        }
        if ($result !== false) {
            $modified = ($result > 0) ? $this->db->modified($table, 'id = ?', [$post['id']]) : true;
            if ($modified) {
                if ($this->request->param('profile') !== '1' && false === $this->updatePermission($post)) {
                    $result = false;
                }
            } else {
                $result = false;
            }
            if ($result !== false) {
                return $this->db->commit();
            }
        }
        trigger_error($this->db->error());
        $this->db->rollback();

        return false;
    }

    /**
     * Remove data.
     *
     * @return bool
     */
    protected function remove()
    {
        $this->checkPermission('user.remove');

        $result = 0;
        $this->db->begin();
        if (false !== $result = $this->db->delete('user', 'id = ?', [$this->request->param('delete')])) {
            return $this->db->commit();
        }
        trigger_error($this->db->error());
        $this->db->rollback();

        return false;
    }

    /**
     * User ID from uname.
     *
     * @param \Tms\Db $db
     *
     * @return int
     */
    public static function getUserID(Db $db)
    {
        return $db->get('id', 'user', 'uname = ?', [$_SESSION['uname']]);
    }

    /**
     * Reference permission.
     *
     * @param string $key
     * @param int    $filter1
     * @param int    $filter2
     *
     * @return bool
     */
    public function hasPermission($key, $filter1 = 0, $filter2 = 0)
    {
        if (is_null($this->userinfo)) {
            $this->setUserInfo();
        }

        // Administrators have full control
        if ($this->isAdmin()) {
            return true;
        }

        //$options = array_values(self::parsePermissionKey($key));
        //$statement = 'userkey = ? AND filter1 = ? AND filter2 = ? AND application = ? AND class = ? AND type = ?';
        //array_unshift($options, $this->uid, $filter1, $filter2);
        //$perm = $this->db->get('priv', 'permission', $statement, $options);
        $perm = $this->getPrivilege($key, $filter1, $filter2);

        return $perm === '1';
    }

    /**
     * Checking permission.
     *
     * @param string $type
     * @param int    $filter1
     * @param int    $filter2
     */
    protected function checkPermission($type, $filter1 = null, $filter2 = null)
    {
        if (false === $this->hasPermission($type, $filter1, $filter2)) {
            throw new PermitException(\P5\Lang::translate('PERMISSION_DENIED'));
        }
    }

    /**
     * Update user permissions.
     *
     * @return bool
     */
    public function updatePermission($post)
    {
        $userkey = $post['id'];

        $class = $this->classFromApplicationName($this->session->param('application_name'));
        if (method_exists($class, 'clearApplicationPermission')) {
            if (false === $class::clearApplicationPermission($this->db, $userkey)) {
                return false;
            }
        }

        if (false === $this->db->delete('permission', 'userkey = ? AND application = ?', [$userkey, ''])) {
            return false;
        }

        $permissions = $this->request->POST('perm');
        if (is_array($permissions)) {
            foreach ($permissions as $key => $value) {
                $filter1 = 0;
                $filter2 = 0;
                $tmp = explode('.', $key);
                if (count($tmp) > 3) {
                    $filter1 = array_shift($tmp);
                    $filter2 = array_shift($tmp);
                }
                $key = implode('.', $tmp);
                if (false === $this->savePermission($key, $value, $userkey, $filter1, $filter2)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Update or insert permission record.
     *
     * @param string $key
     * @param int    $priv    0 or 1
     * @param int    $userkey
     * @param int    $filter1
     * @param int    $filter2
     *
     * @return bool
     */
    private function savePermission($key, $priv, $userkey, $filter1 = 0, $filter2 = 0)
    {
        $value = $this->parsePermissionKey($key);
        $value['userkey'] = $userkey;
        $value['filter1'] = $filter1;
        $value['filter2'] = $filter2;
        $value['priv']    = $priv;

        return $this->db->updateOrInsert('permission', $value, $this->permission_table_keys);
    }

    protected static function parsePermissionKey($key)
    {
        $tmp = explode('.', $key);
        while (count($tmp) < 3) {
            array_unshift($tmp, '');
        }

        return ['application' => $tmp[0], 'class' => $tmp[1], 'type' => $tmp[2]];
    }

    /**
     * Reference user privileges.
     *
     * @param int    $userkey
     * @param int    $filter1
     * @param int    $filter2
     * @param string $application
     * @param string $class
     *
     * @return mixed
     */
    protected function getPrivileges($userkey, $filter1 = '0', $filter2 = '0', $application = null, $class = null)
    {
        $priv = [];
        $statement = 'WHERE userkey = ? AND filter1 = ? AND filter2 = ?';
        $options = [$userkey, $filter1, $filter2];
        if (!is_null($application)) {
            $statement .= ' AND application = ?';
            $options[] = $application;
        }
        if (!is_null($class)) {
            $statement .= ' AND class = ?';
            $options[] = $class;
        }
        $data = $this->db->select(
            'application,class,type,priv',
            'permission', $statement, $options
        );
        foreach ($data as $unit) {
            $key = implode('.', [$unit['application'],$unit['class'],$unit['type']]);
            $priv[$key] = (int)$unit['priv'];
        }

        return $priv;
    }

    public function getPrivilege($key, $filter1, $filter2)
    {
        $options = array_values(self::parsePermissionKey($key));
        $statement = 'userkey = ? AND filter1 = ? AND filter2 = ? AND application = ? AND class = ? AND type = ?';
        array_unshift($options, $this->uid, $filter1, $filter2);

        return $this->db->get('priv', 'permission', $statement, $options);
    }

    public function isAdmin()
    {
        return $this->userinfo['admin'] > 0;
    }

    /**
     * Children of the user.
     *
     * @param int    $id
     * @param string $col
     *
     * @return array
     */
    public function childUsers($id, $col = '*')
    {
        $tmp = \P5\Text::explode(',', $col);
        $columns = [];
        foreach ($tmp as $column) {
            $columns[] = 'children.'.$column;
        }
        $columns = implode(',', $columns);

        $parent = '(SELECT * FROM table::user WHERE id = :userkey)';
        $midparent = '(SELECT * FROM table::user)';

        return $this->db->nsmGetChildren($columns, $parent, $midparent, $midparent, 'AND children.id IS NOT NULL', ['userkey' => $id]);
    }
}
