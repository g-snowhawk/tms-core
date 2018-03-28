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
 * Data validation class.
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Validator
{
    /**
     * Error.
     *
     * @var array
     */
    private $_errors = array();

    /**
     * Check targets.
     *
     * @var array
     */
    private $_checker = array();

    /**
     * Object Constructor.
     *
     * @param array $checker
     */
    public function __construct(array $checker)
    {
        $this->_errors = array();

        foreach ($checker as $check) {
            $key = array_shift($check);
            $this->_checker[$key] = array(
                'source' => array_shift($check),
                'type'   => array_shift($check),
                'errnum' => (!empty($check)) ? array_shift($check) : 1,
                'args'   => (!empty($check)) ? $check : null,
            );
        }
    }

    /**
     * Validation.
     *
     * @param array $values
     *
     * @return bool
     */
    public function valid($values)
    {
        $duplicate = array();
        foreach ($this->_checker as $key => $unit) {
            if ($unit['errnum'] === 0) {
                continue;
            }
            $s = $unit['source'];
            if (strpos($s, ',')) {
                $t = \P5\Text::explode(',', $s);
                $s = array_shift($t);
            }

            if ($unit['type'] === 'necessary' && !isset($values[$s])) {
                $values[$s] = '';
            }

            if (isset($values[$s])) {
                if (isset($duplicate[$s])) {
                    continue;
                }
                if (isset($t)) {
                    $value = array($values[$s]);
                    foreach ($t as $k) {
                        $value[] = $values[$k];
                    }
                } else {
                    $value = $values[$s];
                }
                $this->_errors[$key] = ($this->check($key, $value)) ? 0 : $unit['errnum'];
                if ($this->_errors[$key] > 0) {
                    $duplicate[$s] = 1;
                }
            }
        }

        return $this->_errors;
    }

    /**
     * Validate.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return bool
     */
    public function check($key, $value)
    {
        $result = false;
        $type = $this->_checker[$key]['type'];

        switch ($type) {
            case 'digit':
                return ctype_digit($value);
                break;
            case 'double':
                if (   preg_match('/^[0-9]*\.[0-9]+$/', $value)
                    || preg_match('/^[0-9]+$/', $value)
                ) {
                    $result = true;
                }
                break;
            case 'eq':
                if ($value === $stype) {
                    $result = true;
                }
                break;
            case 'exists':
                $result = file_exists($value);
                break;
            case 'float':
                if (is_numeric($value)) {
                    $float = (float)$value;
                    $result = (string)$float === $value;
                }
                break;
            case 'int':
                if (is_numeric($value)) {
                    $int = (int)$value;
                    $result = (string)$int === $value;
                }
                break;
            case 'mail':
                return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
                break;
            case 'ne':
                if ($value !== $stype) {
                    $result = true;
                }
                break;
            case 'range':
                $args = $this->_checker[$key]['args'];
                $result = ((float)$value >= (float)$args[0] && (float)$value <= (float)$args[1]);
                break;
            case 'retype':
                $result = ($value[0] === $value[1]);
                break;
            case 'writable':
                if (is_dir($value)) {
                    $result = true;
                    try {
                        @touch($value.'/writabletest');
                        unlink($value.'/writabletest');
                    } catch (\ErrorException $e) {
                        $result = false;
                    }
                } else {
                    $result = is_writable($value);
                }
                break;
            default:
                $result = !empty($value);
        }

        return $result;
    }
}
