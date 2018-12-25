<?php
/**
 * This file is part of Tak-Me System.
 *
 * Copyright (c)2016 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Tms\User;

/**
 * User management request response class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Unauth extends \Tms\User implements \Tms\Unauth
{
    public static function guestExecutables() //: array
    {
        return array(
            __CLASS__, 
            array_diff(
                get_class_methods(__CLASS__),
                get_class_methods(get_parent_class()),
                [__FUNCTION__]
            )
        );
    }

    public function reminder() //: void
    {
        echo 'Remindeer';
        exit;
    }
}
