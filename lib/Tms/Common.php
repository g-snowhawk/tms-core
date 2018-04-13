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

    /*
     * Pagination class
     *
     * @ver \P5\Pagination
     */
    public $pager;

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

        $this->pager = new \P5\Pagination();
    }

    /**
     * check setting variables.
     *
     * @return bool
     */
    public function __isset($name)
    {
        switch ($name) {
            case 'isAjax': return true;
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
            case 'isAjax': return \Tms\Base::isAjax();
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
        $err = $v->valid($this->request->param(), $this->request->files());
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
     * @param string $cast_string
     *
     * @return array
     */
    protected function createSaveData($table_name, array $post, array $skip, $cast_string = null)
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

            if (is_array($data[$field])) {
                switch ($cast_string) {
                    case 'json':
                        $data[$field] = json_encode($data[$field]);
                        break;
                    case 'serialize':
                        $data[$field] = serialize($data[$field]);
                        break;
                    case 'implode':
                    case 'join':
                        $data[$field] = implode(',', $data[$field]);
                        break;
                }
            }
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

    protected function postReceived($message, $status, $response, array $options = [])
    {
        foreach ($options as $option) {
            if (is_callable($option[0])) {
                call_user_func_array($option[0], (array)$option[1]);
            }
        }

        if (is_array($status)) {
            $number = $status['status'];
            unset($status['status']);
            $arguments = $status;
            $status = $number;
        }

        // Response to javascript XMLHttpRequest
        if ($this->isAjax) {
            $content_type = 'text/plain';
            $result = [
                'status' => $status,
                'message' => $message,
            ];

            if (isset($arguments)) {
                $result['arguments'] = $arguments;
            }

            $ret = call_user_func_array($response[0], (array)$response[1]);
            $result['response'] = (is_array($ret))
                ? $ret
                : ['type' => 'replace', 'source' => $ret];

            $callback = $this->request->param('callback');
            if (!empty($callback)) {
                $result['response'] = ['type' => 'callback', 'source' => $callback];
            }

            switch ($this->request->param('returntype')) {
                case 'json':
                    $content_type = 'application/json';
                    $source = json_encode($result);
                    break;
                case 'xml':
                    $content_type = 'text/xml';
                    // TODO: convert array to XML
                    //$source = {XML source code};
                    break;
            }
            \P5\Http::responseHeader("Content-type","$content_type; charset=utf-8");
            echo $source;
            exit;
        }

        // Response to normal HttpRequest
        if ($status === 0) {
            $this->session->param('messages', $message);
        }
        call_user_func_array($response[0], (array)$response[1]);
    }

    protected function redirect($mode, $type = 'redirect')
    {
        $url = $this->app->systemURI().'?mode='.filter_var($mode, FILTER_SANITIZE_ENCODED, FILTER_FLAG_STRIP_HIGH);
        if (!$this->isAjax) {
            \P5\Http::redirect($url);
        }

        $response = ['type' => $type, 'source' => $url];

        if ($type !== 'redirect') {
            list($instance, $function, $args) = $this->app->instance($mode);
            try {
                $instance->init();
                $response['source'] = (is_null($args))
                    ? $instance->$function()
                    : call_user_func_array([$instance, $function], $args);
            } catch (\Exception $e) {
                trigger_error($e->getMessage());
            }
        }

        return $response;
    }

    protected function classNameToMode($instance = null)
    {
        if (is_null($instance)) {
            $instance = $this;
        }

        $mode = strtolower(strtr(get_class($instance), '\\', '.'));

        if ($instance instanceof Plugin) {
            $mode = preg_replace('/\./', '~', $mode, 1);
        }

        return $mode;
    }

    protected function startPolling()
    {
        if (touch($this->pollingPath())) {
            return microtime(true);
        }
        return false;
    }

    protected function endPolling()
    {
        $polling_file = $this->pollingPath();
        return (file_exists($polling_file)) ? unlink($polling_file) : true;
    }

    protected function updatePolling($data)
    {
        return file_put_contents($this->pollingPath(), $data);
    }

    protected function echoPolling()
    {
        $polling_file = $this->pollingPath();

        $json = [
            'status' => 'ended',
            'callback' => 'TM.subform.ended',
            'arguments' => []
        ];
        if (file_exists($polling_file) && is_file($polling_file)) {
            $json['callback'] = 'TM.subform.progress';
            $json['arguments'] = [file_get_contents($polling_file)];
        } elseif (file_exists("$polling_file.log")) {
            $json['callback'] = 'TM.subform.showLog';
            $json['arguments'] = [
                $this->request->param('polling_id'),
                $this->classNameToMode().':showPollingLog'
            ];
        }

        // TODO: $json['finally'] support dynamically setting
        $finally = "$polling_file.finally";
        if (file_exists($finally)) {
            $json['finally'] = json_decode(file_get_contents($finally),true);
            unlink($finally);
        }

        \P5\Http::nocache();
        \P5\Http::responseHeader('Content-type','application/json');
        echo json_encode($json);
    }

    protected function pollingPath()
    {
        return implode(
            DIRECTORY_SEPARATOR,
            [$this->app->cnf('global:tmp_dir'),$this->request->param('polling_id')]
        );
    }

    public function showPollingLog()
    {
        \P5\Http::nocache();
        \P5\Http::responseHeader('Content-type','text/plain; charset=utf-8');

        $logfile = $this->pollingPath().'.log';
        if (file_exists($logfile)) {
            readfile($logfile);
            unlink($logfile);
        } else {
            echo 'Log file '.$logfile.' is not found.';
        }

        exit;
    }
}
