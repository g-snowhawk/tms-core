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
 * @see Tms\Base
 */
require_once 'Tms/Base.php';

/**
 * Application controller class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class App extends Base
{
    /** 
     * Current version
     */
    const VERSION = '1.0.0';

    /**
     * Root.
     *
     * @var string
     */
    protected $root = 'Tms';

    /**
     * Object Constructor.
     *
     * @param string $errTemplate Custom error template path
     */
    public function __construct($errTemplate = null)
    {
        // Append plugins path
        $include_path = explode(PATH_SEPARATOR, ini_get('include_path'));
        for ($i = 0; $i < count($include_path); $i++) {
            $path = "{$include_path[$i]}/plugins";
            if (is_dir($path)) {
                ++$i;
                array_splice($include_path,$i,0,$path);
            }
        }
        ini_set('include_path', implode(PATH_SEPARATOR, $include_path));

        /* Auto Load */
        \P5\Auto\Loader::setNameSpace('Tms');
        \P5\Auto\Loader::setIgnoreNameSpaceToPath(['plugin']);
        \P5\Auto\Loader::register();

        /* Reset Error Handler */
        $errHandler = new Error($errTemplate);

        // Set system language and debug mode.
        try {
            parent::__construct();
            $_ENV['P5_LOCALE'] = ucfirst(strtolower($this->cnf('global:system_lang')));
        } catch (\ErrorException $e) {
            // Not yet system installed
            if ($e->getCode() == 90990 && preg_match("/Not found configurarion file\./", $e->getMessage())) {
                $installer = new Install\Setup();
                $installer->install();
            }
        }
    }

    public function run()
    {
        // Open database
        if (!is_null($this->cnf('database:db_host')) && !$this->db->open()) {
            trigger_error('Could not open database connection. ', E_USER_ERROR);
        }

        // CLI mode
        if (php_sapi_name() === 'cli') {
            global $argv;
            $options = getopt('m:p::g::',['mode:','post::','get::']);
            $mode = (isset($options['m'])) ? $options['m'] : $options['mode'];
            $args_cli = [];
            foreach ($argv as $n => $arg) {
                if ($n === 0 || preg_match('/^-+/', $arg) || in_array($arg, $options)) {
                    continue;
                }
                $args_cli[] = $arg;
            }
            if (empty($mode)) {
                exit;
            }

            list($instance, $function, $args) = $this->instance($mode);
            call_user_func_array([$instance, $function], (array)$args_cli);
            exit;
        }

        // Single application
        if (!empty($this->cnf('application:fixed_application_name'))) {
            $this->session->param(
                'application_name',
                $this->cnf('application:fixed_application_name')
            );
        }

        $loggedin = ($this->session->param('authorized'))
            ? $this->session->param('authorized') : 'failed';

        // Signout
        if ($_SERVER['QUERY_STRING'] === 'logout' ||
            ($this->request->method === 'post' && $this->request->POST('stub') !== $this->session->param('ticket'))
        ) {
            $this->logger->log('Signout');
            $this->setcookie('limit', '', time() - 3600);
            $this->session->destroy();
            \P5\Http::redirect($this->reload());
        }

        // Authentication
        if (isset($loggedin) && $loggedin !== parent::ident()) {

            // Check Installed.
            $installed = 0;
            switch ($this->cnf('database:db_driver')) {
                case 'mysql':
                    $this->db->query("SHOW TABLES LIKE '".$this->cnf('database:db_table_prefix')."%'");
                    $installed = $this->db->rowCount();
                    break;
                case 'pgsql':
                    break;
                case 'sqlite':
                    $installed = $this->db->recordCount("SELECT name FROM sqlite_master WHERE type = 'table'");
                    break;
                default :
                    $installed = 1;
                    break;
            }
            if ($installed === 0) {
                $installer = new Install\Setup();
                $installer->install();
            } else {
                // Failure
                if (false === $this->auth('user')) {
                    $this->setcookie('enableCookie', 'yes', 0, null, null, false, false);

                    // Check guest executable
                    if (false === $this->guestExcutable($this->getMode())) {
                        $mode = (!is_null($this->cnf('application:authentication_failed')))
                            ? $this->cnf('application:authentication_failed')
                            : 'system.response:failed';
                        $this->request->param('mode', $mode);
                    }
                }
            }
        } else {
            $limit = (int) $this->cnf('global:session_limit');
            if ($limit > 0 && $this->session->param('alive') !== 'keep') {
                setcookie(
                    session_name(), session_id(), time() + $limit,
                    $this->session->getCookiePath(), $this->session->getCookieDomain(),
                    false, true
                );
            }
        }

        if ($this->session->param('messages')) {
            $this->view->bind('messages', $this->session->param('messages'));
            $this->session->clear('messages');
        }

        $this->view->bind('form',
            array(
                'action' => $this->systemURI(),
                'method' => 'post',
                'enctype' => 'application/x-www-form-urlencoded',
            )
        );
        $this->view->bind('stub', $this->csrf());

        $this->response($this->getMode());
    }
}
