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
    protected $app;

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
     * Object Constructor.
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

        if (!is_null($this->session) && method_exists($this, 'packageName')) {
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
            case 'app': return false;
            case 'isAjax': return true;
            case 'view': return true;
        }

        return property_exists($this, $name) && !is_null($this->$name);
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
            case 'app': return null;
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
        $header = $this->view->param('header');
        $header['id'] = $id;
        $this->view->bind('header', $header);
    }

    /**
     * Modify HTML Class.
     *
     * @param $class
     */
    public function setHtmlClass($class)
    {
        $header = $this->view->param('header');
        $header['class'] = $class;
        $this->view->bind('header', $header);
    }

    /**
     * Append HTML Class.
     *
     * @param $class
     */
    public function appendHtmlClass($class)
    {
        $header = $this->view->param('header');
        if (isset($header['class'])) {
            $header['class'] = array_merge((array)$header['class'], (array)$class); 
            $header['class'] = array_values(array_filter($header['class']));
        }
        else {
            $header['class'] = $class;
        }
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

        $errors = [];
        $validator = new \Tms\Validator($valid);
        $error = $validator->valid($this->request->param(), $this->request->files());
        foreach ($error as $key => $value) {
            if ($value > 0) {
                $errors[$key] = $value;
            }
            $this->app->err[$key] = $value;
        }

        $plugins = $this->app->execPlugin('afterValidate');
        foreach ($this->app->err as $key => $value) {
            if ($value === 0) {
                if (isset($errors[$key])) {
                    unset($errors[$key]);
                }
                continue;
            }
            $errors[$key] = $value;
        }

        return count($errors) === 0;
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
                    'class' => $class,
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
        $config = [
            'global' => [
                'enable_user_alias' => $this->app->cnf('global:enable_user_alias'),
                'assets_path' => $this->app->cnf('global:assets_path'),
            ]
        ];
        $this->view->bind('config', $config);
        $this->view->bind('apps', $this);
        $this->view->bind('nav', $this->navItems());

        if ($cookie = \P5\Environment::cookie('script_referer')) {
            $this->view->bind('referer', $cookie);
            setcookie('script_referer', '', time() - 1);
        }
        elseif ($this->request->param('script_referer')) {
            $this->view->bind('referer', $this->request->param('script_referer'));
        }
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

        $plugins = $this->app->execPlugin('beforeRendering', $id);

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

        $plugins = $this->app->cnf('plugins:paths');
        if (strpos($func, '::') > 0) {
            list($plugin, $func) = explode('::', $func, 2);
            $plugins = [$plugin];
        }
        elseif (strpos($func, '~') > 0) {
            $unit = \Tms\Base::parseMode($func);
            $plugins = [$unit['namespace']];
            $package = $unit['package'];
            $func = $unit['function'];
        }

        $stacks = debug_backtrace();
        foreach ($stacks as $stack) {
            if ($stack['function'] === __FUNCTION__) {
                continue;
            }
            $caller = null;
            if (isset($stack['class'])) {
                if ($stack['class'] === 'Tms\\Common' || $stack['class'] === 'Tms\\Base') {
                    continue;
                }
                $caller = $stack['class'];
            }
            array_unshift($args, $caller);
            break;
        }

        foreach ($plugins as $plugin) {
            $class = "\\plugin\\$plugin";
            if (isset($package)) {
                $class .= "\\$package";
            }

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
            $this->setMessages($message);
        }
        call_user_func_array($response[0], (array)$response[1]);
    }

    protected function redirect($mode, $type = 'redirect')
    {
        if ($type === 'redirect') {
            $mode = preg_replace_callback(
                '/%5C(%[0-9A-F]{2})/',
                function($match) {
                    return urldecode($match[1]);
                },
                filter_var($mode, FILTER_SANITIZE_ENCODED, FILTER_FLAG_STRIP_HIGH)
            );
            $url = $this->app->systemURI()."?mode=$mode";
        }
        else {
            $url = $mode;
        }

        if (!$this->isAjax) {
            \P5\Http::redirect($url);
        }

        $response = ['type' => $type, 'source' => $url];

        if ($type !== 'redirect' && $type !== 'referer' && $type !== 'reload') {
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

        if (preg_match('/^plugin\.(.+)$/', $mode, $match)) {
            $mode = $match[1];
        }

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

    protected function echoPolling(Array $response = null)
    {
        $polling_file = $this->pollingPath();

        $json = [
            'status' => 'ended',
            'response' => [
                'type' => 'callback',
                'source' => 'TM.subform.ended',
            ],
            'arguments' => []
        ];
        if (file_exists($polling_file) && is_file($polling_file)) {
            $json['response']['source'] = 'TM.subform.progress';
            $json['arguments'] = [file_get_contents($polling_file)];
        } elseif (file_exists("$polling_file.log")) {
            $json['response']['source'] = 'TM.subform.showLog';
            $json['arguments'] = [
                $this->request->param('polling_id'),
                $this->classNameToMode().':showPollingLog'
            ];
        }
        if (!is_null($response)) {
            $json['response'] = $response;
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
        exit;
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

    /**
     * template by other application
     */
    protected function useExtendedTemplate()
    {
        $items = $this->navItems();
        foreach ($items as $item) {
            $class = $item['class'];
            $class = '\\'.ltrim($class, '\\');
            if (method_exists($class, 'extendedTemplatePath')) {
                $path = $class::extendedTemplatePath(\P5\Http::getURI(), $this);
                //$this->view->addPath($path);
                $this->view->prependPath($path);
            }
        }
    }

    protected function setMessages($message)
    {
        $this->session->param('messages', $message);
    }

    protected function appendMessages($message)
    {
        $origin = $this->session->param('messages');
        $separator = (empty($origin)) ? '' : PHP_EOL;
        $this->session->param('messages', $origin.$separator.$message);
    }
}
