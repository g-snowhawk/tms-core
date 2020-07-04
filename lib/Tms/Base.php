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
 * Common accessor methods.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto  <www.plus-5.com>
 */
abstract class Base
{
    /**
     * Default configuration file path.
     */
    const INIFILE = 'config.php';

    /**
     * Execute default function name
     */
    const DEFAULT_METHOD = 'defaultView';

    /**
     * Default session name
     */
    const SESSION_NAME = 'TMSTICKETID';

    /**
     * Database class.
     *
     * @var P5_Db
     */
    private $db;

    /**
     * Environment class.
     *
     * @var P5_Envitonment
     */
    private $env;

    /**
     * Form class.
     *
     * @var P5_Html_Form
     */
    private $request;

    /**
     * Session class.
     *
     * @var P5_Session
     */
    private $session;

    /**
     * Template Engine.
     *
     * @var Twig_Environment
     */
    private $view;

    /**
     * configuration object.
     *
     * @var P5_Config_Parser
     */
    private $configuration;

    /**
     * System logger.
     *
     * @var object
     */
    private $logger;

    /**
     * Error Messges.
     *
     * @var array
     */
    public $err;

    /**
     * Object constructer.
     */
    public function __construct($errTemplate = null)
    {
        $this->loadConfiguration();

        if (!defined('ERROR_LOG_DESTINATION')) {
            define('ERROR_LOG_DESTINATION', $this->cnf('global:log_dir').'/error.log');
        }

        $this->logger = new Logger(dirname(ERROR_LOG_DESTINATION), $this);
        $this->env = new \P5\Environment();
        $this->request = new \P5\Html\Form();

        Plugin::register();

        if (!is_null($this->cnf('database:db_host'))) {
            $this->db = new Db(
                $this->cnf('database:db_driver'),
                $this->cnf('database:db_host'),
                $this->cnf('database:db_source'),
                $this->cnf('database:db_user'),
                $this->cnf('database:db_password'),
                $this->cnf('database:db_port'),
                $this->cnf('database:db_encoding')
            );
            $this->db->setTablePrefix($this->cnf('database:db_table_prefix'));
        }

        $save_path = $this->cnf('global:tmp_dir');
        $path = (string)$this->cnf('session:cookie_path');
        if (false !== strpos($path, '*')) {
            $pattern = preg_quote($path, '/');
            $pattern = '/^('.str_replace(['\\*\\*', '\\*'], ['.+', '[^\/]+'], $pattern).')/';
            if ($s = preg_match($pattern, \P5\Environment::server('request_uri'), $match)) {
                $path = $match[1];
            }
        }
        $domain = (string)$this->cnf('session:domain');
        $secure = (\P5\Environment::server('https') === 'on');
        $httponly = true;

        if (php_sapi_name() === 'cli') {
            $options = getopt('',['phpsessid:']);
            if (isset($options['phpsessid'])) {
                session_id($options['phpsessid']);
            }
        }

        $this->session = new \P5\Session('nocache', $save_path, 0, $path, $domain, $secure, $httponly);
        $name = $this->cnf('session:name');
        if (empty($name)) {
            $name = self::SESSION_NAME;
        }
        $this->session->setName($name);
        $this->session->start();

        $this->view = $this->createView();
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
        if (false === property_exists($this, $name) &&
            false === property_exists(__CLASS__, $name)
        ) {
            if (DEBUG_MODE > 0) {
                trigger_error("property `$name` does not exists.", E_USER_ERROR);
            }

            return;
        }

        return $this->$name;
    }

    /**
     * Getting configuration paramater.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    public function cnf($key, $value = null)
    {
        return $this->configuration->param($key, $value);
    }

    /**
     * Load configration from file
     */
    public function loadConfiguration()
    {
        $config = (defined('CONFIG_FILE')) ? CONFIG_FILE : self::INIFILE;
        if (!file_exists($config)) {
            throw new \ErrorException('Not found configurarion file.', 90990);
        }
        $this->configuration = new \P5\Config\Parser($config);
    }

    /**
     * Refreshing configuration file
     *
     * @param array $conf
     *
     * @return bool
     */
    public function refreshConfiguration(array $conf)
    {
        $config_file = (defined('CONFIG_FILE')) ? CONFIG_FILE : self::INIFILE;
        if (!is_writable($config_file)) {
            $config_file = $this->cnf('global:data_dir') . '/config.php';
        }

        if (isset($conf['plugins']) && isset($conf['plugins']['paths'])) {
            $conf['plugins']['paths'] = array_values(
                array_unique($conf['plugins']['paths'])
            );
        }

        $configuration = [
            implode('', ['<', '?php']),
            ';',
            '; System configuration',
            '; modify : ' . date('Y-m-d h:i:s'),
            ';',
            $this->configuration->toString($conf),
        ];
        if (file_put_contents($config_file, implode(PHP_EOL, $configuration))) {
            $this->configuration = new \P5\Config\Parser($config_file);

            return true;
        }

        return false;
    }

    public function mergeConfiguration($uid)
    {
        $prefs = $this->db->select(
            'section,config,value',
            'user_preference',
            'WHERE userkey = ?',
            [$uid]
        );
        $user_preference = [];
        foreach ($prefs as $pref) {
            $user_preference[$pref['section']][$pref['config']] = $pref['value'];
        }
        $this->configuration->merge($user_preference);
    }

    /**
     * Authentication.
     *
     * @param string $authTable
     */
    public function auth($authTable)
    {
        $uname = $this->request->POST('uname');
        $upass = $this->request->POST('upass');
        $ukeep = $this->request->POST('ukeep');

        $err = array('vl_empty' => 0, 'vl_mismatch' => 0, 'vl_nocookie' => 0);
        $auth = new \Tms\Security($authTable, $this->db, $this->cnf('global:password_encrypt_algorithm'));

        $secret = '';
        $columns = (is_null($this->db)) ? [] : $this->db->getFields('user', false, false, "like 'pw_%'");
        $expire = (in_array('pw_expire', $columns)) ? 'pw_expire' : null;

        if (false === $auth->authentication($uname, $upass, $secret, $expire)) {
            if (!is_null($this->request->POST('authEnabler'))) {
                if (!isset($_COOKIE['enableCookie'])) {
                    $err['vl_nocookie'] = 1;
                } else {
                    if (empty($uname)) {
                        $err['vl_empty'] = 1;
                    }
                    if (empty($upass)) {
                        $err['vl_empty'] = 1;
                    }
                    if ($err['vl_empty'] !== 1) {
                        $err['vl_mismatch'] = 1;
                    }
                }

                // Blocking Brute-force attack
                if (!empty($err)) {
                    sleep(3);
                }
            } else {
                if (   is_null($this->session->param('uname'))
                    && strtolower($this->cnf('application:guest')) === 'allow'
                ) {
                    $uname = 'guest';
                    $secret = uniqid();
                    $this->session->param('authorized', self::ident($uname, $secret));
                    $this->session->param('uname', $uname);
                    $this->session->param('securet', $secret);
                    return true;
                } elseif ($this->session->param('uname') === 'guest' && empty($uname)) {
                    $this->session->clear('authorized');
                    $this->session->clear('securet');
                }
            }
            $this->view->bind(
                'form',
                [
                    'action' => $_SERVER['REQUEST_URI'],
                    'method' => 'post',
                    'enctype' => 'application/x-www-form-urlencoded',
                ]
            );
            $post = $this->request->POST();
            if (!isset($post['uname'])) {
                $post['uname'] = $uname;
            }
            $this->view->bind('err', $err);
            $this->view->bind('post', $post);
            $header = array('id' => 'signin', 'title' => \P5\Lang::translate('TITLE_SIGNIN'));
            $this->view->bind('header', $header);
            $this->view->bind('stub', $this->csrf());

            return false;
        }

        // Switch account alias to entity
        $alias = $this->db->get('alias', 'user', 'uname=?', [$uname]);
        if (!is_null($alias)) {
            $this->session->param('alias', $uname);
            $uname = $this->db->get('uname', 'user', 'id=?', [$alias]);
        }

        if (!is_null($this->request->POST('authEnabler'))) {
            session_regenerate_id(true);
        }

        if ($ukeep) {
            $this->session->delay(time() + 60 * 60 * 24 * 365);
            $this->session->param('alive', 'keep');
        }

        $limit = (int) $this->cnf('global:session_limit');
        if ($limit > 0 && $this->session->param('alive') !== 'keep') {
            $this->setcookie('limit', $limit);
        }

        $securet = bin2hex(openssl_random_pseudo_bytes(16));
        $this->session->param('authorized', self::ident($uname, $securet));
        $this->session->param('uname', $uname);
        $this->session->param('securet', $securet);

        $this->logger->log('Signin');
        $this->reload();
    }

    /**
     * User Identity.
     *
     * @param string $name
     * @param string $secret
     *
     * @return string
     */
    public function ident($name = null, $securet = null)
    {
        if (is_null($name)) {
            $name = $this->session->param('uname');
        }
        if (is_null($securet)) {
            $securet = $this->session->param('securet');
        }

        if ($name === 'guest' && $this->cnf('application:guest') === 'allow' && !empty($securet)) {
            return $securet;
        }

        return openssl_encrypt(
            $name.filter_input(INPUT_SERVER, 'REMOTE_ADDR').
            filter_input(INPUT_SERVER, 'HTTP_USER_AGENT'),
            'aes-128-ecb',
            $securet
        );
    }

    public static function lowerCamelCase($str)
    {
        return preg_replace_callback(
            '/[-_]([a-z])/',
            function($matches) {
                return strtoupper($matches[1]);
            },
            strtolower($str)
        );
    }

    public static function upperCamelCase($str)
    {
        return ucfirst(self::lowerCamelCase($str));
    }

    /**
     * Create instance.
     *
     * @param mixed $mode
     *
     * @return object
     */
    public function instance($mode = null)
    {
        $unit = self::checkMode($mode, $this->root, $this->cnf('plugins:paths'));
        $package = $unit['namespace'] . '\\' . $unit['package'];
        $instance = new $package($this);
        if (empty($unit['function']) && method_exists($instance, self::DEFAULT_METHOD)) {
            $unit['function'] = self::DEFAULT_METHOD;
        }

        return array($instance, $unit['function'], $unit['arguments']);
    }

    public function guestExcutable($mode) //: boolean
    {
        $unit = self::checkMode($mode, $this->root, $this->cnf('plugins:paths'));
        $package = $unit['namespace'] . '\\' . $unit['package'];

        if (!is_callable([$package,'guestExecutables'])) {
            return false;
        }

        list($class, $executables) = $package::guestExecutables();

        $implements = array_diff(
            class_implements($class),
            class_implements(get_parent_class($class))
        );
        if (!in_array('Tms\\Unauth', $implements)) {
            return false;
        }

        return in_array($unit['function'], $executables);
    }

    private static function checkMode($mode, $root = '', $plugin_paths = [])
    {
        $unit = self::parseMode($mode);
        if (!empty($unit['namespace'])) {
            if (is_null($plugin_paths)) {
                //
            } elseif (in_array($unit['namespace'], $plugin_paths)) {
                $unit['namespace'] = '\\plugin\\' . $unit['namespace'];
            } else {
                throw new \ErrorException("{$unit['namespace']} is not enabled");
            }
        }
        if (empty($unit['namespace'])) {
            $unit['namespace'] = $root;
        }

        //list($namespace, $prefix) = self::findClass('\\'.$unit['namespace']);
        //if (!empty($prefix)) {
        //    $unit['namespace'] = "$prefix$namespace";
        //}

        $package = $unit['namespace'] . '\\' . $unit['package'];
        if (!class_exists($package)) {
            throw new \ErrorException("Class `{$package}' is not found");
        } elseif (   false === is_subclass_of($package, 'Tms\\PackageInterface')
            && false === is_a($package, 'Tms\\User\\Response', true)
            && false === is_a($package, 'Tms\\System\\Response', true)
            && false === is_a($package, 'Tms\\Plugin', true)
        ) {
            trigger_error("System Error: Class `$package' is an invalid package.", E_USER_ERROR);
        }

        return $unit;
    }

    /*
     * Parse mode
     *
     * @param string $mode
     *
     * @return array
     */
    public static function parseMode($mode)
    {
        $namespace = null;
        $package = $mode;
        $function = self::DEFAULT_METHOD;
        $arguments = null;
        if (preg_match("/^((.+)[~#])?(.+?)(:+(.+))?$/", $mode, $match)) {

            $namespace = strtolower($match[2]);
            $separator = substr($match[1], -1);
            if ($separator === "~") {
                $namespace = self::upperCamelCase($match[2]);
            }

            // inarray paths
            //
            $package = $match[3];

            if (isset($match[5])) {
                $function = $match[5];
                if (preg_match('/(.+)\((.*)\)/', $function, $pair)) {
                    $function = $pair[1];
                    $arguments = \P5\Text::explode(',', $pair[2]);
                }
            }

            $mode = [
                'namespace' => $namespace,
                'package' => $package,
                'function' => self::lowerCamelCase($function),
                'arguments' => $arguments,
            ];

            $mode['plugin'] = (substr($match[1], -1) === "~");
        } else {
            $mode = [
                'namespace' => $namespace,
                'package' => $package,
                'function' => $function,
                'arguments' => $arguments,
            ];
        }

        $dirs = array_map(
            function($str) {
                return self::upperCamelCase($str);
            },
            explode('.', $mode['package'])
        );
        $mode['package'] = implode('\\', $dirs);

        return $mode;
    }

    /**
     * for CSRF attacks.
     *
     * @param bool $force
     */
    public function csrf($force = false)
    {
        $ticket = $this->session->param('ticket');
        if (empty($ticket) || $force === true) {
            $ticket = bin2hex(openssl_random_pseudo_bytes(16, $cstrong));
            $this->session->param('ticket', $ticket);
        }

        return $ticket;
    }

    /**
     * Select default if mode is empty.
     *
     * @return string
     */
    public function getMode()
    {
        $mode = $this->request->param('mode');
        if (!$mode || !preg_match("/^([0-9a-z_\-]+[~#])?[0-9a-z\._\-]+(:[0-9a-z_\-]+)?(\(.*\))?$/i", $mode)) {
            $mode = $this->getDefaultMode();
        }

        return $mode;
    }

    /**
     * Default mode
     *
     * @return string
     */
    public function getDefaultMode()
    {
        if (!$mode = $this->cnf('application:default_mode')) {
            if ($current_application = $this->session->param('application_name')) {
                $class = Common::classFromApplicationName($current_application);
                $mode = method_exists($class, 'getDefaultMode') ? $class::getDefaultMode($this) : $class::DEFAULT_MODE;
            } else {
                $mode = 'user.response';
            }
        }

        $pluginResponse = $this->execPlugin('overrideDefaultMode', $mode);
        if (!empty($pluginResponse)) {
            $mode = array_shift($pluginResponse);
        }

        return $mode;
    }

    /**
     * Response from application
     *
     * @param string $mode
     * @param array $extend_args
     *
     * @return void
     */
    public function response($mode, array $extend_args = null)
    {
        list($instance, $function, $arguments) = $this->instance($mode);

        if (!is_null($extend_args)) {
            $arguments = array_merge((array)$arguments, $extend_args);
        }

        try {
            $instance->init();
            if (is_null($arguments)) {
                $instance->$function();
            } else {
                call_user_func_array([$instance, $function], $arguments);
            }
        } catch (PermitException $e) {
            $this->view->bind('alert', $e->getMessage());
            $this->view->render('permitfailure.tpl');
        } catch (ViewException $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        } catch (\Exception $e) {
            $this->view->bind('alert', $e->getMessage());
            $this->view->render('systemerror.tpl');
        }
    }

    /**
     * Current application name
     *
     * @param string $application_name
     *
     * @return string
     */
    public function currentApplication($application_name = null)
    {
        if (!is_null($application_name)) {
            $this->session->param('application_name', $application_name);
        }
        if (!$this->session->param('application_name')) {
            $mode = $this->getMode();
            if (preg_match('/^([0-9a-z_\-]+)~.+$/', $mode, $match)) {
                $this->root = self::upperCamelCase($match[1]);
                $mode = preg_replace('/^[0-9a-z_\-]+~/', '', $mode);
            }
            $tmp = explode(':', $mode);
            $mode = $tmp[0];
            $mode = explode('.', $mode);
            if (count($mode) > 2 || strpos($mode[0],'#') !== false) {
                $this->session->param('application_name', $mode[0]);
            }
        }

        return $this->session->param('application_name');
    }

    /**
     * Set Cookie.
     *
     * @param string $name
     * @param string $value
     * @param int    $expire
     */
    public function setcookie($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $http_only = true)
    {
        if (php_sapi_name() === 'cli') {
            return;
        }

        if (is_null($path)) {
            $uri = parse_url($this->env->server('request_uri'));
            $path = preg_replace('/\/+$/', '/', dirname($uri['path']));
        }
        setcookie($name, $value, $expire, $path, $domain, $secure, $http_only);
    }

    /**
     * Create view class instance
     *
     * @return Tms\View
     */
    public function createView()
    {
        $debug = (DEBUG_MODE > 0);

        $cache_dir = $this->cnf('global:cache_dir');
        if (empty($cache_dir)) {
            $cache_dir = false;
        }

        $paths = [dirname(__DIR__) . '/' . View::TEMPLATE_DIR_NAME];

        $application_name = $this->currentApplication();
        if (!empty($application_name)) {
            $currentAppName = Common::classFromApplicationName($application_name);
            if (class_exists($currentAppName) && method_exists($currentAppName, 'templateDir')) {
                array_unshift($paths, $currentAppName::templateDir());
            }
        }

        $plugins = array_reverse(array_unique((array)$this->cnf('plugins:paths')));
        foreach ($plugins as $plugin) {
            $class = "\\plugin\\$plugin";
            if (class_exists($class) && method_exists($class, 'extendTemplateDir')) {
                array_unshift($paths, $class::extendTemplateDir());
            }
        }

        return new View(array_filter($paths), $debug, $cache_dir);
    }

    /**
     * Execute plugin function.
     *
     * @return mixed
     */
    public function execPlugin()
    {
        $arguments = func_get_args();
        $function = array_shift($arguments);

        $current_app = $this->session->param('application_name');

        $stacks = debug_backtrace();
        foreach ($stacks as $stack) {
            if (   $stack['function'] === __FUNCTION__
                || $stack['class'] === 'Tms\\Common'
                || $stack['class'] === 'Tms\\Base'
            ) {
                continue;
            }
            $caller = $stack['object'] ?? null;
            array_unshift($arguments, $stack['class']);
            break;
        }

        $plugins = array_unique((array)$this->cnf('plugins:paths'));
        $result = [];
        foreach ($plugins as $plugin) {
            $class = "\\plugin\\" . preg_replace('/^\\\?plugin\\\/', '', $plugin);

            if (!class_exists($class)) {
                $path = preg_replace('/^\//', '', str_replace('\\', '/', $class));
                if (false !== ($filename = stream_resolve_include_path($path . '.php'))
                    || false !== ($filename = stream_resolve_include_path("$path/" . basename($path) . '.php'))
                ) {
                    require_once $filename;
                }
            }

            if (class_exists($class) && method_exists($class, $function)) {
                $inst = new $class($this);
                if (!empty($caller)) {
                    $inst->setCaller($caller);
                }
                $result[$plugin] = call_user_func_array([$inst, $function], $arguments);
            }
        }

        $this->session->param('application_name', $current_app);

        return $result;
    }

    public function systemURI($fullpath = false)
    {
        $url = $this->cnf('global:base_url');
        if ($fullpath) {
            return $url;
        }
        $parsed_url = parse_url($url);
        return $parsed_url['path'];
    }

    public function reload($qsa = false)
    {
        $url = \P5\Environment::server('request_uri');

        if (!empty($this->session->param('direct_uri'))) {
             $url = $this->session->param('direct_uri');
             $this->session->clear('direct_uri');
        }
        elseif ($qsa === false) {
            $url = preg_replace('/\?.*$/', '', $url);
        }

        \P5\Http::redirect($url);
    }

    public static function isAjax()
    {
        return \P5\Environment::server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    //public static function findClass($class)
    //{
    //    $className = null;
    //    $namespace = null;
    //    $prefixes = \P5\Auto\Loader::getIgnoreNameSpaceToPath();
    //    foreach ($prefixes as $prefix) {
    //        if (class_exists("\\$prefix$class")) {
    //            $className = $class;
    //            $namespace = $prefix;
    //            break;
    //        }
    //    }
    //    return [$className, $namespace];
    //}
}
