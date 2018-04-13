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
        if (empty($this->session->param('uname'))) {
            return;
        }

        $get = $this->db->select(
            '*', 'user', 'WHERE uname = ?',
            [$this->session->param('uname')]
        );
        if (isset($get[0])) {
            $this->uid = $get[0]['id'];
            $this->userinfo = $get[0];
        }
    }

    protected function clearUserInfo()
    {
        $this->userinfo = null;
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
        $valid[] = ['vl_fullname', 'fullname', 'empty'];
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

        if ($this->isRoot() && $this->request->param('profile') !== '1') {
            $save['admin'] = ($post['admin'] === '1') ? $post['admin'] : '0';
        }

        if (empty($post['id'])) {
            $parent_rgt = $this->db->get('rgt', 'user', 'id = ?', [$this->uid]);

            $save['lft'] = $parent_rgt;
            $save['rgt'] = $parent_rgt + 1;

            $update_parent = $this->db->prepare(
                "UPDATE table::user
                    SET lft = CASE WHEN lft > :parent_rgt
                                   THEN lft + 2
                                   ELSE lft END,
                        rgt = CASE WHEN rgt >= :parent_rgt
                                   THEN rgt + 2
                                   ELSE rgt END
                  WHERE rgt >= :parent_rgt"
            );

            $raw = ['create_date' => 'CURRENT_TIMESTAMP'];
            if (   false !== $update_parent->execute(['parent_rgt' => $parent_rgt])
                && false !== $result = $this->db->insert($table, $save, $raw)
            ) {
                $post['id'] = $this->db->lastInsertId(null, 'id');
            }
        } else {
            $result = $this->db->update($table, $save, 'id = ?', [$post['id']], $raw);
        }
        if ($result !== false) {
            $modified = ($result > 0) ? $this->db->modified($table, 'id = ?', [$post['id']]) : true;
            if ($modified) {
                if (   $this->request->param('profile') !== '1'
                    && false === $this->updatePermission($post)
                ) {
                    $result = false;
                }
            } else {
                $result = false;
            }

            if (   $this->request->param('profile') === '1'
                && false === $this->removeAlias($post)
            ) {
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
        if (    false !== $result = $this->db->delete('user', 'id = ?', [$this->request->param('delete')])
             && false !== $result = $this->db->delete('user', 'alias = ?', [$this->request->param('delete')])
        ) {
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

        if ($key === 'root') {
            return $this->isRoot();
        }

        // Administrators have full control
        if ($this->isAdmin()) {
            return true;
        }

        $perm = $this->getPrivilege($key, $filter1, $filter2);

        if (strchr($key, '.exec') === '.exec') {
            return $perm !== '0';
        }

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

        $applications = $this->navItems();
        foreach ($applications as $application) {
            $key = $application['code'].'.exec';
            if (!is_array($permissions)) {
                $permissions = [];
            }
            if (!isset($permissions[$key])) {
                $permissions[$key] = '0';
            }
        }

        if (is_array($permissions)) {
            foreach ($permissions as $key => $value) {

                if (strchr($key, '.exec') === '.exec' && $value === '1') {
                    continue;
                }

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
        if (empty($this->uid)) {
            return;
        }

        $options = array_values(self::parsePermissionKey($key));
        $statement = 'userkey = ? AND filter1 = ? AND filter2 = ? AND application = ? AND class = ? AND type = ?';
        array_unshift($options, $this->uid, $filter1, $filter2);

        return $this->db->get('priv', 'permission', $statement, $options);
    }

    public function isAdmin()
    {
        return $this->userinfo['admin'] > 0;
    }

    public function isRoot()
    {
        return isset($this->userinfo['lft']) && $this->userinfo['lft'] === '0';
    }

    public function isParent($child_id)
    {
        $parent = $this->db->nsmGetParent(
            'parent.id',
            '(SELECT * FROM table::user)',
            '(SELECT * FROM table::user WHERE id = :id)',
            ['id' => $child_id]
        );
        return $this->uid === $parent;
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

    /**
     * get user alias data.
     *
     * @param int $own
     * @param steing $columns
     *
     * @return array
     */
    protected function getAliases($own, $columns = '*')
    {
        return $this->db->select($columns, 'user', 'WHERE alias = ?', [$own]);
    }

    /**
     * Save user alias data.
     *
     * @return bool
     */
    protected function saveAlias()
    {
        $id = $this->request->POST('id');
        $check = (empty($id)) ? 'create' : 'update';
        $this->checkPermission('user.'.$check);

        $post = $this->request->post();

        $table = 'user';
        $skip = ['id', 'alias', 'admin', 'create_date', 'modify_date'];

        $valid = [];
        $valid[] = ['vl_fullname', 'fullname', 'empty'];
        $valid[] = ['vl_email', 'email', 'empty'];
        if (empty($post['id'])) {
            $valid[] = ['vl_uname', 'uname', 'empty'];
        }

        if (!$this->validate($valid)) {
            return false;
        }
        $this->db->begin();

        $fields = $this->db->getFields($this->db->TABLE($table));
        //$permissions = [];
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

            $save['alias'] = $this->uid;
            $save['lft'] = 1000000;
            $save['rgt'] = 1000000;

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
                // ...
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
     * Remove user alias data.
     *
     * @return bool
     */
    protected function removeAlias($post)
    {
        if (empty($post['remove'])) {
            return true;
        }

        foreach ((array)$post['remove'] as $key => $value) {
            if ($value !== 'on') {
                continue;
            }
            if (false === $this->db->delete('user', 'id=?', [$key])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Users List
     *
     * @param mixed $restriction    string|array
     *
     * @return array|false
     */
    protected function getUsers($restriction = null, $sort = null, $limit = null, $offset = null)
    {
        $filter = 'restriction IS NULL';
        $options = [$this->uid];
        if (is_array($restriction)) {
            $filter = 'restriction IN ('.implode(',',array_fill(0,count($restriction),'?')).')';
            $options = array_merge($options, $restriction);
        }
        elseif (!empty($restriction)) {
            $filter = 'restriction = ?';
            $options[] = $restriction;
        }

        $orderby = '';
        if (!empty($sort)) {
            $orderby .= " ORDER BY $sort";
        }

        $extensions = '';
        if (!empty($limit)) {
            $offset = (!empty($offset)) ? (int)$offset.',' : '';
            $extensions .= ' LIMIT '. $offset . (int)$limit;
        }

        return $this->db->nsmGetDecendants(
            'children.id, children.fullname, children.company, children.email',
            '(SELECT * FROM table::user WHERE id = ?)',
            "(SELECT * FROM table::user WHERE $filter$orderby)",
            $options, $extensions
        );
    }

    protected function eraseUnusedPermission()
    {
        return $this->db->exec(
            "DELETE p FROM tm_permission p
               LEFT JOIN tm_user u 
                 ON p.userkey = u.id
              WHERE u.id IS NULL"
        );
    }
}
