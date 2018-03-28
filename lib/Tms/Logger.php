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
 * Custom Logging class.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <www.plus-5.com>
 */
class Logger
{
    private $logfile;
    private $app;
    private $separator = ' ';
    private $logsize = 1048576;
    private $maxlogs = 7;

    public function __construct($logdir, $app)
    {
        $this->logfile = \P5\File::realpath("$logdir/access.log");
        $this->app = $app;
    }

    public function log($message, $level = 0)
    {
        $log = array(
            \P5\Environment::server('remote_addr'),
            $this->app->session->param('uname'),
            date('[Y-m-d H:i:s]'),
            '"'.$message.'"',
            '"'.\P5\Environment::server('HTTP_USER_AGENT').'"',
            "\n",
        );
        error_log(implode($this->separator, $log), 3, $this->logfile);
        $size = filesize($this->logfile);
        if ((int) $size >= $this->logsize) {
            $this->rotate();
        }
    }

    private function rotate()
    {
        for ($i = $this->maxlogs; $i > 1; --$i) {
            $j = $i - 1;
            $file_s = $this->logfile.".$j";
            $file_d = $this->logfile.".$i";
            if (file_exists($file_s)) {
                rename($file_s, $file_d);
            }
        }
        rename($this->logfile, $this->logfile.'.1');
    }
}
