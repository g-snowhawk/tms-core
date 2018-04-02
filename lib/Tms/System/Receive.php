<?php
/**
 * This file is part of Tak-Me System.
 *
 * Copyright (c)2016 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Tms\System;

/**
 * User management request response class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Receive extends Response
{
    /**
     * Execute setup
     */
    public function setup()
    {
        $paths = $this->request->POST('paths');
        if (empty($paths)) {
            $this->init();
        }
        $configuration = $this->app->cnf(null);
        $failed = [];
        foreach ($paths as $classfile) {
            include_once($classfile);
            $checksum = md5_file($classfile);
            if (preg_match("/(Tms\/.+)\/" . preg_quote(self::CLASS_FILE, '/') . "$/", $classfile, $match)) {
                $class = str_replace(
                    self::CLASS_PATH,
                    self::CLASS_NAME,
                    strtr($match[1], '/', '\\')
                );
                $namespace = $class::getNameSpace();
                $current_version = $this->getPackageVersion($namespace);
                if (empty($current_version)) {
                    $current_version = 0;
                }
                $instance = new $class($this->app, $current_version);
                if (get_parent_class($instance) !== 'Tms\\PackageSetup') {
                    continue;
                }
                if (false !== $instance->update($configuration, $current_version)) {
                    $this->setChecksum([$namespace, $class::VERSION, $checksum]);
                }
                else {
                    $failed[] = $instance->getMessage();
                }
            }
        }
        $this->saveChecksum();
        $this->app->refreshConfiguration($configuration);

        $tmpfile = $this->app->cnf('global:data_dir') . '/config.php';
        if (!file_exists($tmpfile)) {
            $this->session->param('messages', \P5\Lang::translate('SUCCESS_SETUP'));
        }

        if (!empty($failed)) {
            $this->session->param('messages', implode(PHP_EOL, $failed));
        }

        $bundles = $this->navItems();
        if (count($bundles) === 1) {
            $this->app->currentApplication($bundles[0]['name']);
        }

        $url = $this->app->systemURI().'?mode=system.response';
        \P5\Http::redirect($url);
    }

    /**
     * Execute setup
     */
    public function setupPlugin()
    {
        $paths = $this->request->POST('paths');
        if (empty($paths)) {
            $this->init();
        }
        $configuration = $this->app->cnf(null);
        foreach ($paths as $classfile) {
            include_once($classfile);
            $checksum = md5_file($classfile);
            if (preg_match("/plugins\/(.+)\/" . preg_quote(self::CLASS_FILE, '/') . "$/", $classfile, $match)) {
                $class = str_replace(
                    self::CLASS_PATH,
                    self::CLASS_NAME,
                    strtr($match[1], '/', '\\')
                );
                $namespace = $class::getNameSpace();
                $current_version = $this->getPackageVersion($namespace);
                if (empty($current_version)) {
                    $current_version = 0;
                }
                $instance = new $class($this->app, $current_version);
                if (get_parent_class($instance) !== 'Tms\\PackageSetup') {
                    continue;
                }
                if (false !== $instance->update($configuration, $current_version)) {
                    $this->setChecksum([$namespace, $class::VERSION, $checksum]);
                }
            }
        }
        $this->saveChecksum('plugins.log');
        $this->app->refreshConfiguration($configuration);

        $tmpfile = $this->app->cnf('global:data_dir') . '/config.php';
        if (!file_exists($tmpfile)) {
            $this->session->param('messages', \P5\Lang::translate('SUCCESS_SETUP'));
        }

        $url = $this->app->systemURI().'?mode=system.response:plugins';
        \P5\Http::redirect($url);
    }

    /**
     * Download or delete configuration file
     */
    public function download()
    {
        $config_file = $this->app->cnf('global:data_dir') . '/config.php';
        $filename = basename($config_file);
        $content_length = filesize($config_file);
        $start = $this->request->POST('start_cookie');
        $end = $this->request->POST('end_cookie');
        setcookie($end, $_COOKIE[$start]);
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Content-length: $content_length");
        header('Content-Type: text/plain; charset=utf-8');
        readfile($config_file);
        unlink($config_file);
        exit;
    }

    /**
     * Log rotation
     */
    public function logRotate()
    {
        $error_log  = ERROR_LOG_DESTINATION;
        $access_log = dirname($error_log).'/access.log';
        $ext = date("YmdHis");

        if ($this->request->POST('errorlog_rotate') === '1' && file_exists($error_log)) {
            if (false === rename($error_log, preg_replace("/^(.+)\.log$/", "$1.$ext.log", $error_log))) {
                $this->session->param('messages', \P5\Lang::translate('FAILD_ERRORLOG_ROTATE'));
            }
        }

        if ($this->request->POST('accesslog_rotate') === '1' && file_exists($access_log)) {
            if (false === rename($access_log, preg_replace("/^(.+)\.log$/", "$1.$ext.log", $access_log))) {
                $this->session->param('messages', \P5\Lang::translate('FAILD_ACCESSLOG_ROTATE'));
            }
        }

        $url = $this->app->systemURI().'?mode=system.response:log';
        \P5\Http::redirect($url);
    }
}
