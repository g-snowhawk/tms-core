<?php
/**
 * This file is part of Tak-Me System.
 *
 * Copyright (c)2016-2017 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Tms;

/**
 * Application installer base class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
abstract class PackageSetup
{
    /**
     * Instance of base application
     *
     * @var Tms\App
     */
    protected $app;

    /**
     * Message for result of setup
     *
     * @var string
     */
    protected $message;

    /**
     * version number of an installed package.
     */
    protected $installed_version;

    /**
     * Execute install/update package
     *
     * @param array &$configuration
     *
     * @return bool
     */
    abstract public function update(&$configuration);

    /**
     * Message for result of setup
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Update database.
     *
     * @param string $dir
     *
     * @return bool
     */
    protected function updateDatabase($dir)
    {
        $suffix = ($this->app->cnf('database:db_driver') !== 'sqlite' && $this->app->cnf('database:db_driver') !== 'sqlite2') ? '.sql' : '.sqlite';
        $sql_files = glob($dir."/sql/ver*/*$suffix");
        foreach ($sql_files as $sql_file) {
            if (!preg_match('/ver([0-9\.]+)\/.+'.preg_quote($suffix, '/').'$/', $sql_file, $match)
             || version_compare($match[1], $this->installed_version, '<=')
             || version_compare($match[1], static::VERSION, '>')
            ) {
                continue;
            }
            $sql = file_get_contents($sql_file);
            $sql = preg_replace('/TMS_/', $this->app->cnf('database:db_table_prefix'), $sql);
            $queries = explode(';', $sql);
            foreach ($queries as $query) {
                $query = preg_replace("/-- [^\r\n]+/s", '', $query);
                $query = preg_replace("/^[\s]+/s", '', $query);
                if (empty($query)) {
                    continue;
                }
                if (false === $this->app->db->query($query)) {
                    trigger_error($this->app->db->error());
                    return false;
                }
            }
        }

        return true;
    }
}
