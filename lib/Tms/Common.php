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
 * Common methods for Tak-Me System.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
abstract class Common
{
    /**
     * Application.
     *
     * @var Tms_App
     */
    public $app;

    /**
     * Template file name.
     *
     * @var string
     */
    public $template_file = 'default.html.twig';

    /**
     * Object Constructer.
     */
    public function __construct()
    {
        $params = func_get_args();
        foreach ($params as $param) {
            if (is_object($param) && (get_class($param) === 'Tms\\App' || is_subclass_of($param, 'Tms\\App'))) {
                $this->app = $param;
            }
        }

        if (is_null($this->app)) {
            throw new \ErrorException('No such application');
        }

        if (method_exists($this, 'packageName')) {
            $this->session->param('application_name', $this->packageName());
        }
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
            case 'db': return $this->app->db;
            case 'env': return $this->app->env;
            case 'request': return $this->app->request;
            case 'session': return $this->app->session;
            case 'view': return $this->app->view;
        }
        if (false === property_exists($this, $name)) {
            if ($this->app->cnf('global:debugmode') === '1') {
                trigger_error("property `$name` does not exists.", E_USER_ERROR);
            }

            return;
        }

        return $this->$name;
    }

    /**
     * Modify HTML ID.
     *
     * @param $id
     */
    public function setHtmlId($id)
    {
        $globals = $this->view->param();
        $header = $globals['header'];
        $header['id'] = $id;
        $this->view->bind('header', $header);
    }

    /**
     * Validation form data.
     *
     * @param array $valid
     *
     * @return bool
     */
    protected function validate($valid)
    {
        $plugins = $this->app->execPlugin('beforeValidate', $valid);
        foreach ($plugins as $plugin => $results) {
            if (isset($results['valid'])) {
                $valid = array_merge($valid, (array)$results['valid']);
            }
        }

        $result = true;
        $v = new \Tms\Validator($valid);
        $err = $v->valid($this->request->param());
        foreach ($err as $key => $value) {
            if ($value >= 1) {
                $result = false;
            }
            $this->app->err[$key] = $value;
        }

        return $result;
    }

    /**
     * Create save data.
     *
     * @param string $table_name
     * @param array  $post
     * @param array  $skip
     *
     * @return array
     */
    protected function createSaveData($table_name, array $post, array $skip)
    {
        $data = [];
        $fields = $this->db->getFields($this->db->TABLE($table_name));
        foreach ($fields as $field) {
            if (in_array($field, $skip)) {
                continue;
            }
            if (!isset($post[$field])) {
                continue;
            }
            $data[$field] = (empty($post[$field])) ? null : $post[$field];
        }

        return $data;
    }

    public function navItems()
    {
        $nav = [];
        $install_log = $this->app->cnf('global:log_dir') . '/install.log';

        if (file_exists($install_log)) {
            $tmp = file($install_log);
            foreach ($tmp as $line) {
                list($class, $version, $md5) = explode("\t", $line);
                $nav[] = [
                    'code' => $class::packageName(),
                    'name' => $class::applicationName(),
                ];
            }
        }

        return $nav;
    }

    public function currentApp()
    {
        return $this->session->param('application_name');
    }

    public function init()
    {
        $this->view->bind('apps', $this);
        $this->view->bind('nav', $this->navItems());
    }

    public function staticPath()
    {
        $path = dirname(\P5\Auto\Loader::convertNameToPath(self::classFromApplicationName($this->app->currentApplication()), true));
        $path = str_replace(\P5\Environment::server('document_root'), '', $path);
        return "$path/";
    }

    public function defaultView()
    {
        $args = func_get_args();

        $id = (isset($args[0])) ? $args[0] : 'default';
        $this->setHtmlId($id);

        $this->view->bind('err', $this->app->err);

        $template = (isset($args[1])) ? $args[1] : strtr($id, '-', '/') . View::TEMPLATE_EXTENTION;
        $this->view->render($template);
    }

    public static function classFromApplicationName($application_name)
    {
        return "\\Tms\\" . ucfirst(strtolower($application_name));
    }

    public function plugin()
    {
        $args = func_get_args();
        $func = array_shift($args);

        $stacks = debug_backtrace();
        foreach ($stacks as $stack) {
            if ($stack['function'] === __FUNCTION__) {
                continue;
            }
            $class = null;
            if (isset($stack['class'])) {
                if ($stack['class'] === 'Tms\\Common' || $stack['class'] === 'Tms\\Base') {
                    continue;
                }
                $class = $stack['class'];
            }
            array_unshift($args, $class);
            break;
        }

        $plugins = $this->app->cnf('plugins:paths');
        foreach ($plugins as $plugin) {
            $class = "\\$plugin";
            if (method_exists($class, $func)) {
                $inst = new $class($this->app);
                return call_user_func_array([$inst, $func], $args);
            }
        }
    }
}
