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
 * Plugin class.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <www.plus-5.com>
 */
class Plugin
{
    protected $app;

    public function __construct(\Tms\App $app)
    {
        $this->app = $app;
    }

    /**
     * check setting variables.
     *
     * @return bool
     */
    public function __isset($name)
    {
        switch ($name) {
            case 'view': return true;
        }

        return property_exists($this, $name);
    }

    /**
     * Getter method.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        switch ($name) {
            case 'db':      return $this->app->db;
            case 'env':     return $this->app->env;
            case 'request': return $this->app->request;
            case 'session': return $this->app->session;
            case 'view':    return $this->app->view;
        }
        if (false === property_exists($this, $name)) {
            if ($this->app->cnf('global:debugmode') === '1') {
                trigger_error("property `$name` does not exists.", E_USER_ERROR);
            }

            return;
        }

        return $this->$name;
    }
}
