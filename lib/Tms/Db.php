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
 * Database connection class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Db extends \P5\Db
{
    const GET_RETURN_HASH = 1;
    const GET_RETURN_ARRAY = 2;
    const GET_RETURN_STATEMENT = 3;
    const GET_RETURN_COLUMN = 4;

    /**
     * Database table prefix.
     *
     * @var string
     */
    private $previx;

    /**
     * Object Constructor.
     *
     * @param string $driver   Database driver
     * @param string $host     Database server host name or IP address
     * @param string $source   Data source
     * @param string $user     Database user name
     * @param string $password Database password
     * @param string $port     Database server port
     * @param string $enc      Database encoding
     */
    public function __construct($driver, $host, $source, $user, $password, $port = 3306, $enc = '')
    {
        $dbDriver = $driver;
        $dbHost = $host;
        $dbPort = $port;
        $dbSource = $source;
        $dbUser = $user;
        $dbPasswd = $password;
        $dbEnc = $enc;
        parent::__construct($dbDriver, $dbHost, $dbSource, $dbUser,
                            $dbPasswd, $dbPort, $dbEnc);
    }

    public function setTablePrefix($prefix)
    {
        return $this->previx = $prefix;
    }

    /**
     * Table name.
     *
     * @param string $key
     *
     * @return string
     */
    public function TABLE($key)
    {
        return $this->previx.strtolower($key);
    }

    /**
     * Quotation SQL String.
     *
     * @param mixed $value
     * @param bool  $isZero
     * @param bool  $isNull
     *
     * @return string
     */
    public function quote($value, $isZero = false, $isNull = false)
    {
        if (is_string($value)) {
            $value = preg_replace("/\[%%P5:/", '[$P5:', $value);
        }

        return parent::quote($value, $isZero, $isNull);
    }

    /**
     * exec insert SQL.
     *
     * @param string $table
     * @param array  $data
     * @param array  $raws
     * @param array  $fields
     *
     * @return mixed
     */
    public function insert($table, array $data, $raws = null, $fields = null)
    {
        return parent::insert($this->TABLE($table), $data, $raws, $fields);
    }

    /**
     * exec update SQL.
     *
     * @param string $table
     * @param array  $data
     * @param string $statement
     * @param array  $options
     * @param array  $raws
     * @param array  $fields
     *
     * @return mixed
     */
    public function update($table, $data, $statement = '', $options = array(), $raws = null, $fields = null)
    {
        return parent::update($this->TABLE($table), $data, $statement, $options, $raws);
    }

    /**
     * exec insert or update SQL.
     *
     * @param string $table
     * @param array  $data
     * @param array  $unique
     * @param array  $raws
     * @param array  $fields
     *
     * @return mixed
     */
    public function replace($table, array $data, $unique, $raws = array(), $fields = null)
    {
        return parent::replace($this->TABLE($table), $data, $unique, $raws);
    }

    /**
     * exec delete SQL.
     *
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function delete($table, $statement = '', $options)
    {
        return parent::delete($this->TABLE($table), $statement, $options);
    }

    /**
     * exec update or insert SQL.
     *
     * @param string $table
     * @param array  $data
     * @param array  $unique
     * @param array  $raws
     *
     * @return mixed
     */
    public function updateOrInsert($table, array $data, $unique, $raws = [])
    {
        return parent::updateOrInsert($this->TABLE($table), $data, $unique, $raws);
    }

    /**
     * Select.
     *
     * @param string $columns
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function select($columns, $table, $statement = '', $options = array())
    {
        return parent::select($columns, $this->TABLE($table), $statement, $options);
    }

    /**
     * Select Single.
     *
     * @param string $columns
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function selectSingle($columns, $table, $statement = '', $options = array())
    {
        $result = parent::select($columns, $this->TABLE($table), $statement, $options);
        if (is_array($result) && count($result) > 0) {
            return array_shift($result);
        }

        return $result;
    }

    /**
     * Exists Records.
     *
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function exists($table, $statement = '', $options = array())
    {
        return parent::exists($this->TABLE($table), $statement, $options);
    }

    /**
     * Get Value.
     *
     * @param string $columns
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function get($column, $table, $statement = '', $options = array())
    {
        return parent::get($column, $this->TABLE($table), $statement, $options);
    }

    /**
     * MIN Value.
     *
     * @param string $column
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function min($column, $table, $statement = '', $options = array())
    {
        return parent::min($column, $this->TABLE($table), $statement, $options);
    }

    /**
     * MAX Value.
     *
     * @param string $column
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function max($column, $table, $statement = '', $options = array())
    {
        return parent::max($column, $this->TABLE($table), $statement, $options);
    }

    /**
     * Update Modified date.
     *
     * @param string $table
     * @param string $statement
     * @param array  $options
     * @param string $column
     * @param array  $extra
     *
     * @return bool
     */
    public function modified($table, $statement = '', array $options = [], $column = 'modify_date', $extra = [])
    {
        return $this->update($table, $extra, $statement, $options, [$column => 'CURRENT_TIMESTAMP']);
    }

    /**
     * RecordCount.
     *
     * @param string $table
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function count($table, $statement = '', $options = [])
    {
        return parent::count($this->TABLE($table), $statement, $options);
    }

    /**
     * Execute query and return.
     *
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function getAll($statement, $options = [], $return_type = self::GET_RETURN_ARRAY)
    {
        $sql = self::build($statement, $options);
        if (false !== $stat = $this->query($sql)) {
            switch ($return_type) {
                case self::GET_RETURN_HASH:
                    return $stat->fetch(\PDO::FETCH_ASSOC);
                case self::GET_RETURN_ARRAY:
                    return $stat->fetchAll(\PDO::FETCH_ASSOC);
                case self::GET_RETURN_STATEMENT:
                    return $stat;
                case self::GET_RETURN_COLUMN:
                    return $stat->fetchColumn();
                default:
                    return;
            }
        }

        return false;
    }

    /**
     * Build SQL Statement.
     *
     * @param string $sql
     * @param array  $options
     *
     * @return string
     */
    public function build($sql, $options)
    {
        $sql = preg_replace_callback(
            "/table::([^\s]+)/",
            function ($matches) {
                return $this->TABLE($matches[1]);
            },
            $sql
        );

        return $this->prepareStatement($sql, $options);
    }

    /**
     * Prepare.
     *
     * @param string $statement
     *
     * @return mixed
     */
    public function prepare($statement)
    {
        $statement = preg_replace_callback(
            "/table::([^\s]+)/",
            function ($matches) {
                return $this->TABLE($matches[1]);
            },
            $statement
        );
        return parent::prepare($statement);
    }

    /**
     * SQL for Decendant nodes from Nested Set Model.
     *
     * @param string $columns
     * @param string $parent
     * @param string $children
     *
     * @return mixed
     */
    public static function nsmDecendantsSQL($columns, $parent, $children = null)
    {
        if (is_null($children)) {
            $children = $parent;
        }

        return  "SELECT $columns
                   FROM $parent parent
                        LEFT OUTER JOIN $children children
                                     ON children.lft > parent.lft
                                    AND children.lft < parent.rgt
                  WHERE children.id IS NOT NULL";
    }

    /**
     * SQL for Decendant nodes from Nested Set Model.
     *
     * @param string $columns
     * @param string $parent
     * @param string $children
     *
     * @return mixed
     */
    public function nsmGetDecendants($columns, $parent, $children = null, $options = null)
    {
        return $this->getAll(self::nsmDecendantsSQL($columns, $parent, $children), $options);
    }

    /**
     * SQL for child nodes from Nested Set Model.
     *
     * @param string $columns
     * @param string $parent
     * @param string $midparent
     * @param string $children
     * @param string $filters
     *
     * @return mixed
     */
    public static function nsmChildrenSQL($columns, $parent, $midparent = null, $children = null, $filters = '')
    {
        if (is_null($midparent)) {
            $midparent = $parent;
        }
        if (is_null($children)) {
            $children = $parent;
        }

        return  "SELECT $columns
                   FROM $parent parent
                        LEFT OUTER JOIN $children children
                                     ON children.lft > parent.lft
                                    AND children.lft < parent.rgt
                  WHERE NOT EXISTS
                        (
                            SELECT *
                              FROM $midparent midparent
                             WHERE midparent.lft BETWEEN parent.lft AND parent.rgt
                               AND children.lft BETWEEN midparent.lft AND midparent.rgt
                               AND midparent.id NOT IN (children.id, parent.id)
                        ) $filters";
    }

    public function nsmGetChildren($columns, $parent, $midparent = null, $children = null, $filters = '', $options = null)
    {
        return $this->getAll(self::nsmChildrenSQL($columns, $parent, $midparent, $children, $filters), $options);
    }

    /**
     * SQL for root node from Nested Set Model.
     *
     * @param string $columns
     * @param string $parent
     * @param string $children
     *
     * @return mixed
     */
    public static function nsmRootSQL($columns, $parent, $children = null)
    {
        if (is_null($children)) {
            $children = $parent;
        }

        return "SELECT $columns
                  FROM $children children
                 WHERE NOT EXISTS (
                               SELECT * 
                                 FROM $parent parent
                                WHERE children.lft > parent.lft
                                  AND children.lft < parent.rgt
                           )";
    }

    /**
     * Fetch root node from Nested Set Model.
     *
     * @param string $columns
     * @param string $parent
     * @param string $children
     *
     * @return mixed
     */
    public function nsmGetRoot($columns, $parent, $children = null, $options = [], $statement = '')
    {
        return $this->getAll(self::nsmRootSQL($columns, $parent, $children).$statement, $options);
    }

    /**
     * SQL for parent nodes from Nested Set Model.
     *
     * @param string $columns
     * @param string $parent
     * @param string $children
     *
     * @return mixed
     */
    public static function nsmParentSQL($columns, $parent, $children = null)
    {
        if (is_null($children)) {
            $children = $parent;
        }

        return "SELECT parent.id
                  FROM ($children) children
                       LEFT OUTER JOIN ($parent) parent
                                    ON parent.lft < children.lft
                                   AND parent.lft = (SELECT MAX(lft)
                                                       FROM $parent child
                                                      WHERE children.lft > child.lft
                                                        AND children.lft < child.rgt)";
    }

    /**
     * Fetch parent nodes from Nested Set Model.
     *
     * @param string $columns
     * @param string $parent
     * @param string $children
     * @param array  $options
     *
     * @return mixed
     */
    public function nsmGetParent($columns, $parent, $children = null, $options = null)
    {
        return $this->getAll(self::nsmParentSQL($columns, $parent, $children), $options, self::GET_RETURN_COLUMN);
    }

    /**
     * SQL for parent nodes from Nested Set Model.
     *
     * @param string $columns
     * @param string $parent
     * @param string $children
     * @param string $limit
     *
     * @return mixed
     */
    public static function nsmParentsSQL($columns, $parent, $children = null, $limit = null)
    {
        if (is_null($children)) {
            $children = $parent;
        }
        $filters = (is_null($limit)) ? '' : ' AND parent.lft >= ?';

        return "SELECT $columns
                  FROM $children children 
                       LEFT OUTER JOIN $parent parent
                                    ON children.lft > parent.lft 
                                   AND children.lft < parent.rgt
                 WHERE children.id = ? $filters";
    }

    /**
     * Fetch parent nodes from Nested Set Model.
     *
     * @param int    $child_id
     * @param string $columns
     * @param string $parent
     * @param string $children
     * @param int    $parent_id
     *
     * @return mixed
     */
    public function nsmGetParents($child_id, $columns, $parent, $children = null, $parent_id = null)
    {
        return $this->getAll(self::nsmParentsSQL($columns, $parent, $children, $parent_id), [$child_id, $parent_id]);
    }

    /**
     * SQL for position from Nested Set Model.
     *
     * @param string $parent
     * @param string $children
     *
     * @return mixed
     */
    public static function nsmPositionSQL($parent, $children = null)
    {
        if (is_null($children)) {
            $children = $parent;
        }

        return "SELECT CASE WHEN child.rgt IS NULL
                            THEN parent.lft
                            ELSE MAX(child.rgt)
                        END AS lft, parent.rgt AS rgt
                  FROM $parent parent
                       LEFT OUTER JOIN $children child
                                    ON parent.lft = (SELECT MAX(lft)
                                                       FROM $children children
                                                      WHERE child.lft > children.lft
                                                        AND child.lft < children.rgt)";
    }

    public function nsmGetPosition($parent, $children = null, $options = null)
    {
        return $this->getAll(self::nsmPositionSQL($parent, $children), $options, self::GET_RETURN_HASH);
    }

    /**
     * SQL for count children from Nested Set Model.
     *
     * @param string $parent
     * @param string $children
     *
     * @return mixed
     */
    public static function nsmCountSQL($parent, $children = null)
    {
        if (is_null($children)) {
            $children = $parent;
        }

        return "SELECT COUNT(children.id) AS cnt
                  FROM $parent parent
                  LEFT OUTER JOIN $children children
                               ON parent.lft = (
                                      SELECT MAX(lft) 
                                        FROM $children child
                                       WHERE children.lft > child.lft 
                                         AND children.lft < child.rgt 
                                  )
                 GROUP BY parent.id";
    }

    public function nsmGetCount($parent, $children = null, $options = null)
    {
        return $this->getAll(self::nsmCountSQL($parent, $children), $options, self::GET_RETURN_COLUMN);
    }

    /**
     * Path to nodes.
     *
     * @param string $columns
     * @param string $top
     * @param string $middle
     * @param string $bottom
     *
     * @return string
     */
    public static function nsmNodePathSQL($columns, $top, $middle = null, $bottom = null)
    {
        if (is_null($middle)) {
            $middle = $top;
        }
        if (is_null($bottom)) {
            $bottom = $top;
        }

        return "SELECT $columns
                  FROM $top top, $middle middle, $bottom bottom
                 WHERE top.id = ?
                   AND bottom.id = ?
                   AND middle.lft BETWEEN top.lft AND top.rgt
                   AND bottom.lft BETWEEN middle.lft AND middle.rgt
                 ORDER BY middle.lft";
    }

    public function nsmGetNodePath($columns, $top, $middle = null, $bottom = null, $options = [])
    {
        return $this->getAll(self::nsmNodePathSQL($columns, $top, $middle, $bottom), $options);
    }

    /**
     * Copy record.
     *
     * @param array  $cols
     * @param string $dest
     * @param string $source
     * @param string $statement
     * @param array  $options
     *
     * @return mixed
     */
    public function copyRecord($cols, $dest, $source = '', $statement = '', $options = null)
    {
        if (empty($source)) {
            $source = $dest;
        }
        $dest = $this->TABLE($dest);
        $source = $this->TABLE($source);

        $sql = "INSERT INTO $dest
                     SELECT ".implode(',', $cols)."
                       FROM $source";
        if (!empty($statement)) {
            $sql .= " WHERE $statement";
        }

        return $this->exec(self::build($sql, $options));
    }
}
