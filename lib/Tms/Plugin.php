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
 * Plugin class.
 *
 * @license  https://www.plus-5.com/licenses/mit-license  MIT License
 * @author   Taka Goto <www.plus-5.com>
 */
class Plugin extends User
{
    /*
     * Using common accessor methods
     */
    use \Tms\Accessor;

    /**
     * Object constructor.
     */
    public function __construct()
    {
        $params = func_get_args();
        call_user_func_array('parent::__construct', $params);
    }
}
