<?php
/**
 * This file is part of Tak-Me System.
 *
 * Copyright (c)2016-2017 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Tms\Install;

/**
 * @see P5_Auto_Loader
 */
//require_once 'P5/Auto/Loader.php';

/**
 * Application install class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Setup
{
    private $request;
    private $session;
    private $view;
    private $db;
    private $cnf;
    private $save_dir;

    /**
     * running mode.
     *
     * @var string
     */
    private $mode;

    /**
     * error messages.
     *
     * @var array
     */
    private $err = [];

    /**
     * Configuration file path.
     *
     * @var string
     */
    private $config = \Tms\Base::INIFILE;

    /**
     * Object Constructor.
     */
    public function __construct()
    {
        $templates_dir = __DIR__ . '/../../templates';

        $this->view = new \Tms\View($templates_dir, true);

        if (defined('CONFIG_FILE') && !empty(CONFIG_FILE)) {
            $this->config = CONFIG_FILE;
        }
        $_ENV['P5_LOCALE'] = ucfirst(strtolower('ja'));

        $this->request = new \P5\Html\Form();
        $this->session = new \P5\Session('nocache');
        $this->session->start();

        $tmp_file = $this->request->POST('tmp_file');
        if (file_exists($tmp_file)) {
            $this->cnf = parse_ini_file($tmp_file, true);
        }

        if (empty($this->cnf['database']) && $this->request->POST('db_driver')) {
            if (empty($this->cnf)) {
                $this->cnf = [];
            }
            $this->cnf['database'] = [
                'db_driver' => $this->request->POST('db_driver'),
                'db_host' => $this->request->POST('db_host'),
                'db_port' => $this->request->POST('db_port'),
                'db_source' => $this->request->POST('db_source'),
                'db_user' => $this->request->POST('db_user'),
                'db_password' => $this->request->POST('db_password'),
                'db_encoding' => $this->request->POST('db_encoding'),
                'db_table_prefix' => $this->request->POST('db_table_prefix'),
            ];
        }

        if (isset($this->cnf['database'])) {
            $this->db = new \Tms\Db(
                $this->cnf['database']['db_driver'],
                $this->cnf['database']['db_host'],
                $this->cnf['database']['db_source'],
                $this->cnf['database']['db_user'],
                $this->cnf['database']['db_password'],
                $this->cnf['database']['db_port'],
                $this->cnf['database']['db_encoding']
            );
            $this->db->setTablePrefix($this->cnf['database']['db_table_prefix']);
        }
    }

    /**
     * Install application.
     */
    public function install()
    {
        $step = 1;
        $this->mode = 'step'.$step;
        if ($this->request->method === 'post') {
            list($proc, $this->mode) = explode('.', $this->request->POST('mode'));
            $this->err = [];

            $valid = [];
            switch ($this->mode) {
            case 'step1':
                $valid[] = ['vl_base_url', 'base_url', 'empty'];
                $valid[] = ['vl_base_url_2', 'base_url', 'uri'];
                $valid[] = ['vl_domain_name', 'domain_name', 'empty'];
                $valid[] = ['vl_docroot', 'docroot', 'empty'];
                $valid[] = ['vl_notexists_docroot', 'docroot', 'exists'];
                $valid[] = ['vl_save_dir', 'save_dir', 'empty'];
                $valid[] = ['vl_notexists_save_dir', 'save_dir', 'exists'];
                $valid[] = ['vl_notwritable_save_dir', 'save_dir', 'writable'];
                $valid[] = ['vl_assets_path', 'assets_path', 'empty'];

                // algorithms
                $algo = ['crypt'];
                $algo = array_merge($algo, hash_algos());
                $algo = array_merge($algo, openssl_get_cipher_methods());
                $this->view->bind('algo', $algo);

                break;
            case 'step2':
                $valid[] = ['vl_db_driver', 'db_driver', 'empty'];
                $valid[] = ['vl_db_host', 'db_host', 'empty'];
                $valid[] = ['vl_db_source', 'db_source', 'empty'];
                $valid[] = ['vl_db_user', 'db_user', 'empty'];
                $valid[] = ['vl_db_password', 'db_password', 'empty'];

                // algorithms
                $algo = ['crypt'];
                $algo = array_merge($algo, hash_algos());
                $algo = array_merge($algo, openssl_get_cipher_methods());
                $this->view->bind('algo', $algo);

                break;
            case 'step3':
                $valid[] = ['vl_company', 'company', 'empty'];
                $valid[] = ['vl_email', 'email', 'empty'];
                $valid[] = ['vl_uname', 'uname', 'empty'];
                $valid[] = ['vl_upass', 'upass', 'empty'];
                $valid[] = ['vl_retype', 'upass,retype', 'retype'];

                $this->view->bind('base_url', $this->cnf['global']['base_url']);

                $dir = realpath(dirname($this->config));
                $basename = basename($this->config);
                $this->view->bind('config_path', "$dir/$basename");

                break;
            case 'step4':
                break;
            }

            if (false !== $this->validate($valid)) {
                if ($this->save()) {
                    if (preg_match('/step([0-9]+)$/', $this->mode, $match)) {
                        $step = ($match[1] + 1);
                        $this->mode = 'step'.$step;
                        $this->request->POST('mode', 'install.'.$this->mode);
                    }
                }
            }
        } else {
            $protocol = (\P5\Environment::server('SERVER_PORT') === '443') ? 'https://' : 'http://';
            $base_url = $protocol.\P5\Environment::server('SERVER_NAME').\P5\Environment::server('REQUEST_URI');
            $domain_name = \P5\Environment::server('SERVER_NAME');
            $docroot = \P5\Environment::server('DOCUMENT_ROOT');
            $save_path = SYSTEM_ROOT.'/data';
            $assets_path = rtrim(dirname(\P5\Environment::server('REQUEST_URI').'.'),'/');
            $this->request->POST('base_url', $base_url);
            $this->request->POST('domain_name', $domain_name);
            $this->request->POST('docroot', $docroot);
            $this->request->POST('save_dir', $save_path);
            $this->request->POST('assets_path', "$assets_path/");
            $this->request->POST('mode', 'install.step1');
        }

        $this->view->bind('err', $this->err);
        $this->view->bind('step', $step);
        $this->view->bind('post', $this->request->POST());
        $this->view->bind(
            'form',
            ['method' => 'post',
             'action' => \P5\Environment::server('REQUEST_URI'),
             'enctype' => 'application/x-www-form-urlencoded', ]
        );

        \P5\Http::nocache();
        echo $this->view->render("install/{$this->mode}.tpl");
        exit;
    }

    /**
     * POST Data validation.
     *
     * @param array $valid
     *
     * @return bool
     */
    protected function validate($valid)
    {
        $result = true;

        $validator = new \Tms\Validator($valid);
        $errors = $validator->valid($this->request->param());
        foreach ($errors as $key => $value) {
            if ($value === 1) {
                $result = false;
            }
            $this->err[$key] = $value;
        }

        switch ($this->mode) {
            case 'step1':
                $this->save_dir = rtrim($this->request->POST('save_dir'), '/');
                if (file_exists($this->config)) {
                    try {
                        rename($this->config, $this->save_dir.'/'.$this->config.'.'.date('YmdHis'));
                    } catch (\ErrorException $e) {
                        $this->err['vl_exists_config'] = 1;
                        $this->view->bind('config_path', realpath($this->cofig));
                        $result = false;
                    }
                }
                break;
            case 'step2':
                if ($result) {
                    if (!$this->db->open() && !$this->db->create()) {
                        $this->err['vl_db_connection'] = \P5\Lang::translate('DB_CONNECT_ERROR').$db->error();
                        $result = false;
                    }
                }
                break;
        }

        return $result;
    }

    /**
     * Data saving.
     *
     * @return bool
     */
    public function save()
    {
        switch ($this->mode) {
            case 'step1':
                $base_url = $this->request->POST('base_url');
                $unit = parse_url($base_url);
                $docroot = preg_replace("/\/$/", '', $this->request->POST('docroot'));
                $this->cnf = ['global' => [
                    'domain_name' => $this->request->POST('domain_name'),
                    'base_url' => $base_url,
                    'base_dir' => dirname($docroot),
                    'docroot' => $docroot,
                    'data_dir' => $this->save_dir,
                    'tmp_dir' => $this->save_dir.'/tmp',
                    'log_dir' => $this->save_dir.'/logs',
                    'cache_dir' => $this->save_dir.'/cache',
                    'system_path' => $unit['path'],
                    'assets_path' => $this->request->POST('assets_path'),
                    'system_lang' => 'ja',
                ]];
                $this->request->POST('tmp_file', $this->save_dir.'/tmp.ini');

                return $this->saveConfigFile($this->request->POST('tmp_file'));
            case 'step2':
                $this->cnf['global']['password_encrypt_algorithm'] = $this->request->POST('password_encrypt_algorithm');
                if ($this->saveConfigFile($this->request->POST('tmp_file'))) {
                    if ($this->db->open()) {
                        $suffix = ($this->cnf['database']['db_driver'] !== 'sqlite' && $this->cnf['database']['db_driver'] !== 'sqlite2') ? '.sql' : '.sqlite';
                        $sql_files = glob(__DIR__."/sql/*$suffix");
                        foreach ($sql_files as $sql_file) {
                            $sql = file_get_contents($sql_file);
                            $sql = preg_replace('/TMS_/', $this->cnf['database']['db_table_prefix'], $sql);
                            $queries = explode(';', $sql);
                            foreach ($queries as $query) {
                                $query = preg_replace("/-- [^\r\n]+/s", '', $query);
                                $query = preg_replace("/^[\s]+/s", '', $query);
                                if (empty($query)) {
                                    continue;
                                }
                                if (false === $this->db->query($query)) {
                                    $this->err['db_connection'] = $this->db->error();

                                    return false;
                                }
                            }
                        }

                        return true;
                    }
                }

                return false;
            case 'step3':
                if ($this->db->open()) {
                    $plain = $this->request->POST('upass');
                    $secret = '';
                    $encrypt = \P5\Security::encrypt($plain, $secret, $this->cnf['global']['password_encrypt_algorithm']);
                    $this->request->POST('upass', $encrypt);
                    $fields = [
                        'uname', 'email', 'upass',
                        'company', 'division', 'representative', 'representative_rubi', 'url',
                        'state', 'city', 'town', 'address1', 'address2',
                    ];
                    $save = [];
                    foreach ($fields as $field) {
                        $save[$field] = $this->request->POST($field);
                    }

                    $save['admin'] = '1';
                    $save['lft'] = '0';
                    $save['rgt'] = '999999';

                    $raw = [];
                    $raw['create_date'] = 'CURRENT_TIMESTAMP';
                    $raw['modify_date'] = 'CURRENT_TIMESTAMP';

                    if ($this->db->insert('user', $save, $raw)) {
                        $this->session->param('uname', $this->request->POST('uname'));
                        if (!file_exists($this->config) || is_writable($this->config)) {
                            $contents = ';'.PHP_EOL.
                                        '; System configuration'.PHP_EOL.
                                        '; modify : '.date('Y-m-d h:i:s').PHP_EOL.
                                        ';'.PHP_EOL;
                            foreach ($this->cnf as $section => $arr) {
                                $contents .= PHP_EOL."[$section]".PHP_EOL;
                                foreach ($arr as $key => $value) {
                                    $contents .= "$key = \"$value\"".PHP_EOL;
                                }
                            }
                            if (file_put_contents($this->config, implode('', array('<', '?php', PHP_EOL, $contents)))) {
                                $tmp_file = $this->request->POST('tmp_file');
                                if (@unlink($tmp_file)) {
                                    $this->session->destroy();
                                    header('Location:'.$this->cnf['global']['base_url']);
                                    exit;
                                }
                            }
                        }

                        return true;
                    }
                    $this->request->POST('upass', $plain);
                    $this->err['vl_db_error'] = $this->db->error();
                }
                break;
            case 'step4':
                if (is_null($this->cnf)) {
                    $this->err['vl_once'] = 1;
                    break;
                }
                $contents = ';'.PHP_EOL.
                            '; System configuration'.PHP_EOL.
                            '; modify : '.date('Y-m-d h:i:s').PHP_EOL.
                            ';'.PHP_EOL;
                foreach ($this->cnf as $section => $arr) {
                    $contents .= PHP_EOL."[$section]".PHP_EOL;
                    foreach ($arr as $key => $value) {
                        $contents .= "$key = \"$value\"".PHP_EOL;
                    }
                }
                $tmp_file = $this->request->POST('tmp_file');
                $contents = implode('', array('<', '?php', PHP_EOL, $contents));
                $filename = basename($this->config);
                $content_length = strlen($contents);
                header("Content-Disposition: attachment; filename=\"$filename\"");
                header("Content-length: $content_length");
                header('Content-Type: text/plain; charset=utf-8');
                echo $contents;
                @unlink($tmp_file);
                exit;
            default: break;
        }

        return false;
    }

    /**
     * Saveing into the file.
     *
     * @param string $tmp_file
     *
     * @return bool
     */
    private function saveConfigFile($tmp_file)
    {
        $contents = '';
        foreach ($this->cnf as $section => $arr) {
            $contents .= "[$section]".PHP_EOL;
            foreach ($arr as $key => $value) {
                $contents .= "$key = \"$value\"".PHP_EOL;
            }
        }

        return file_put_contents($tmp_file, $contents);
    }
}
