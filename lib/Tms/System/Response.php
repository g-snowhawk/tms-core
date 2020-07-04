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

use ReflectionClass;

/**
 * User management request response class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Response extends \Tms\System
{
    /**
     * Object Constructor.
     */
    public function __construct()
    {
        $params = func_get_args();
        call_user_func_array('parent::__construct', $params);

        $this->view->bind(
            'header',
            ['title' => \P5\Lang::translate('HEADER_TITLE'), 'id' => 'system', 'class' => 'system']
        );
    }

    /**
     * Default view.
     */
    public function defaultView()
    {
        $this->checkPermission('root');

        $packages = [];
        $n = 0;

        $include_paths = explode(PATH_SEPARATOR, ini_get('include_path'));
        foreach ($include_paths as $include_path) {
            if (!file_exists($include_path)) {
                continue;
            }
            self::loadAllIncludes($include_path);
        }

        self::loadAllByAutoLoader();

        foreach (get_declared_classes() as $class) {
            $reflection = new ReflectionClass($class);
            if ($reflection->isUserDefined() === false) {
                continue;
            }
            $physical_path =  $reflection->getFileName();
            $namespace = $reflection->getNamespaceName();
            $class_name = $reflection->getName();

            if (strpos($physical_path, self::CLASS_PATH) !== false
                && preg_match('/\\\setup$/i', $class_name)
                && !preg_match('/^plugin\\\/i', $class_name)
            ) {
                $unit = [
                    'classname' => $class_name,
                    'namespace' => $namespace,
                    'current_version' => $this->getPackageVersion($namespace),
                    'detail' => $class::getDescription(),
                ];
                if ($this->getPackageMd5($namespace) !== md5_file($physical_path)
                    && version_compare($class::VERSION, $unit['current_version'], '>')
                ) {
                    $unit['path'] = $physical_path;
                    $unit['new_version'] = $class::VERSION;
                }

                $packages[] = $unit;
                ++$n;
            }
        }

        $template = 'system/default.tpl';

        $this->view->bind('packages', $packages);

        $this->setHtmlId('system-default');
        $this->view->render($template);
    }

    public function failed()
    {
        $this->view->bind(
            'header',
            [
                'title' => 'Login Panel',
                'id' => 'signin',
                'class' => 'signin'
            ]
        );
        $this->view->render('signin.tpl');
    }

    public function change()
    {
        $current_application = $this->session->param('application_name');
        $application_name = $this->request->param('app');
        if ($application_name !== $current_application) {
            if (!empty($current_application)) {
                $class = $this->classFromApplicationName($current_application);
                $class::unload();
            }
            $this->session->param('application_name', $application_name);
            $current_application = $application_name;
        }

        $class = $this->classFromApplicationName($current_application);
        $url = $this->app->systemURI().'?mode='.urlencode($class::DEFAULT_MODE);
        \P5\Http::redirect($url);
    }

    /**
     * Plugins list view.
     */
    public function plugins()
    {
        $this->checkPermission('root');

        $packages = [];
        $n = 0;

        $include_paths = explode(PATH_SEPARATOR, ini_get('include_path'));
        foreach ($include_paths as $include_path) {
            if (!file_exists($include_path)) {
                continue;
            }
            self::loadAllIncludes($include_path);
        }

        self::loadAllByAutoLoader();

        foreach (get_declared_classes() as $class) {
            $reflection = new ReflectionClass($class);
            if ($reflection->isUserDefined() === false) {
                continue;
            }
            $physical_path =  $reflection->getFileName();
            $namespace = $reflection->getNamespaceName();
            $class_name = $reflection->getName();

            if (strpos($physical_path, self::CLASS_PATH) !== false
                && preg_match('/\\\setup$/i', $class_name)
                && preg_match('/^plugin\\\/i', $class_name)
            ) {
                $unit = [
                    'classname' => $class_name,
                    'namespace' => $namespace,
                    'current_version' => $this->getPluginVersion($namespace),
                    'detail' => $class::getDescription(),
                ];
                if ($this->getPackageMd5($namespace) !== md5_file($physical_path)
                    && version_compare($class::VERSION, $unit['current_version'], '>')
                ) {
                    $unit['path'] = $physical_path;
                    $unit['new_version'] = $class::VERSION;
                }

                $packages[] = $unit;
                ++$n;
            }
        }

        $template = 'system/plugins.tpl';

        $this->view->bind('packages', $packages);

        $this->setHtmlId('system-default');
        $this->view->render($template);
    }

    /**
     * log view.
     */
    public function log()
    {
        $template = 'system/log.tpl';

        $this->setHtmlId('system-log');
        $this->view->render($template);
    }

    public function errorLog()
    {
        self::echoLog(ERROR_LOG_DESTINATION);
    }

    public function accessLog()
    {
        self::echoLog(dirname(ERROR_LOG_DESTINATION).'/access.log');
    }

    public function printFrame()
    {
        $this->setHtmlId('print-dummy');
        $this->appendHtmlClass('print');
        parent::defaultView('print_dummy');
    }

    private static function echoLog($logfile)
    {
        \P5\Http::nocache();
        \P5\Http::responseHeader('Content-type', 'text/plain', 'charset=utf-8');
        if (file_exists($logfile) && filesize($logfile) > 0) {
            readfile($logfile);
        }
        else {
            echo 'No log...';
        }
        exit;
    }
}
