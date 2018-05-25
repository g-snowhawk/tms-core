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

            $n = 1;
            $original_key = $key;
            while (isset($this->_checker[$key])) {
                $key = "$original_key.$n";
                ++$n;
            }

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
     * @param array $files
     *
     * @return bool
     */
    public function valid($values, $files)
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

            if (!isset($values[$s]) && isset($files[$s])) {
                $values[$s] = $files[$s];
            }

            if ($unit['type'] === 'empty' && !isset($values[$s])) {
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

                $error_key = preg_replace('/\.[0-9]+$/', '', $key);
                $this->_errors[$error_key] = ($this->check($key, $value)) ? 0 : $this->_checker[$key]['errnum'];
                if ($this->_errors[$error_key] > 0) {
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
                $result = ctype_digit($value);
                break;
            case 'disallowtags':
                $striped = strip_tags($value);
                $result = ($striped === $value);
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
            case 'upload':
                $result = true;
                if ($value['error'] > 0) {
                    $result = false;
                    $this->_checker[$key]['errnum'] = $value['error'];
                } else {
                    if (is_array($this->_checker[$key]['args'])) {
                        $ext = pathinfo($value['name'], PATHINFO_EXTENSION);
                        $result = in_array(strtolower(".$ext"), array_map('strtolower', $this->_checker[$key]['args']));
                    }
                }
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
