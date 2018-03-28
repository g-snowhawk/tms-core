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
 * Custom Error Handler.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Error extends \P5\Error
{
    /**
     * Error Log Type.
     *
     * @string
     */
    private $_errorType = array(
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Perse',
        E_NOTICE => 'Notice',
        E_USER_ERROR => 'U_Error',
        E_USER_WARNING => 'U_Warning',
        E_USER_NOTICE => 'U_Notice',
    );

    /**
     * Object Constructor.
     */
    public function __construct($template = null)
    {
        parent::__construct($template);
    }

    /**
     * New error handler.
     *
     * @param int    $errno
     * @param string $errstr
     * @param string $errfile
     * @param int    $errline
     * @param array  $errcontext
     */
    public function errorHandler($errno, $errstr, $errfile, $errline, $errcontext)
    {
        parent::errorHandler($errno, $errstr, $errfile, $errline, $errcontext);
    }
}
