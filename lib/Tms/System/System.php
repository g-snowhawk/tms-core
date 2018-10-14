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
 * User management class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class System extends \Tms\User
{
    /*
     * Using common accessor methods
     */
    use \Tms\Accessor;

    const CLASS_PATH = 'manifesto';
    const CLASS_FILE = 'setup.php';
    const CLASS_NAME = 'Setup';

    private $checksum = [];
    private $checksum_plugins = [];

    /**
     * Object constructor.
     */
    public function __construct()
    {
        $params = func_get_args();
        call_user_func_array('parent::__construct', $params);

        $this->loadChecksum();
    }

    private function loadChecksum()
    {
        $install_log = $this->app->cnf('global:log_dir') . '/install.log';
        if (file_exists($install_log) && false !== $fh = fopen($install_log, 'r')) {
            while (false !== $unit = fgetcsv($fh, 0, "\t")) {
                $this->checksum[$unit[0]] = ['version' => $unit[1], 'md5' => $unit[2]];
            }
        }

        $plugins_log = $this->app->cnf('global:log_dir') . '/plugins.log';
        if (file_exists($plugins_log) && false !== $fh = fopen($plugins_log, 'r')) {
            while (false !== $unit = fgetcsv($fh, 0, "\t")) {
                $this->checksum_plugins[$unit[0]] = ['version' => $unit[1], 'md5' => $unit[2]];
            }
        }
    }

    protected function saveChecksum($logfile = 'install.log')
    {
        $install_log = $this->app->cnf('global:log_dir') . "/$logfile";

        $checksums = ($logfile === 'install.log') ? $this->checksum : $this->checksum_plugins;
        $src = '';
        foreach ($checksums as $key => $value) {
            $src .= implode("\t", [$key, $value['version'], $value['md5']]) . PHP_EOL;
        }

        return file_put_contents($install_log, $src);
    }

    protected function setChecksum($unit)
    {
        $this->checksum[$unit[0]] = ['version' => $unit[1], 'md5' => $unit[2]];
    }

    protected function getPackageMd5($key)
    {
        return (isset($this->checksum[$key])) ? $this->checksum[$key]['md5'] : null;
    }

    protected function getPackageVersion($key)
    {
        return (isset($this->checksum[$key])) ? $this->checksum[$key]['version'] : null;
    }

    protected function setChecksumPlugins($unit)
    {
        $this->checksum_plugins[$unit[0]] = ['version' => $unit[1], 'md5' => $unit[2]];
    }

    protected function getPluginMd5($key)
    {
        return (isset($this->checksum_plugins[$key])) ? $this->checksum_plugins[$key]['md5'] : null;
    }

    protected function getPluginVersion($key)
    {
        return (isset($this->checksum_plugins[$key])) ? $this->checksum_plugins[$key]['version'] : null;
    }

    public static function getVersion($package)
    {
        try {
            $path = str_replace('\\', DIRECTORY_SEPARATOR, $package) . "/manifesto/setups.php";
            include_once($path);
            $class = "\\$package\\setup";
            return $class::VERSION;
        } catch (\ErrorException $e) {
            // Noop
        }

        return 'unknown';
    }
}
