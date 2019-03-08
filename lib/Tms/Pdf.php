<?php
/**
 * This file is part of Tak-Me System.
 *
 * Copyright (c)2019 PlusFive (https://www.plus-5.com)
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 */

namespace Tms;

/**
 * Use TCPDF/FPDI Library
 */
use setasign\Fpdi\TcpdfFpdi;

/**
 * PDF Writer
 *
 * @license https://www.plus-5.com/licenses/mit-license  MIT License
 * @author  Taka Goto <www.plus-5.com>
 */
class Pdf
{
    private $engine;

    /**
     * Object Constructor.
     */
    public function __construct()
    {
        $this->engine = new TcpdfFpdi();

        // Default settings
        $this->engine->SetAutoPageBreak(false);
        $this->engine->SetCompression(true);
        $this->engine->setPrintHeader(false);
        $this->engine->setPrintFooter(false);

        // Enbed the font subset
        $this->engine->setFontSubsetting(true);
    }

    public function handler(): object
    {
        return $this->engine;
    }

    public function loadTemplate($path): int
    {
        return $this->engine->setSourceFile($path);
    }

    public function addPageFromTemplate($page = 1): array
    {
        $this->engine->addPage();
        $imported_page = $this->engine->importPage($page);
        return $this->engine->useTemplate($imported_page);
    }

    public function saveFileAs($path): bool
    {
        $this->engine->Output($path, "F");

        return file_exists($path);
    }

    public static function mapAttrToArray($value): array
    {
        if (empty($value)) {
            $value = [];
        } elseif (is_string($value)) {
            $value = array_map('trim', explode(',', $value));
        }

        return $value;
    }

    public static function mapAttrToBoolean($value): bool
    {
        $casted_value = (bool)$value;

        if (false !== $casted_value && is_string($value)) {
            $value = strtolower($value);
            if ($value === 'no' || $value === 'false') {
                $casted_value = false;
            }
        }

        return $casted_value;
    }

    public function setMetaData(array $data = []): void
    {
        foreach ($data as $key => $value) {
            if (empty($value)) {
                continue;
            }
            switch (strtolower($key)) {
                case 'author':   $this->engine->SetAuthor($value);   break;
                case 'creator':  $this->engine->SetCreator($value);  break;
                case 'keywords': $this->engine->SetKeywords($value); break;
                case 'subject':  $this->engine->SetSubject($value);  break;
                case 'title':    $this->engine->SetTitle($value);    break;
            }
        }
    }

    public function draw(array $drawing_map, ?array $data, $force_y = null, $offset_y = 0)
    {
        if (is_null($data)) {
            return;
        }

        foreach ($drawing_map as $property) {
            $origin_y = (is_null($force_y)) ? $property['y'] : $force_y;
            $origin_y += $offset_y;
            $this->engine->SetXY($property['x'], $origin_y);
            $value = '';

            if (is_array($property['name'])) {
                $delimiter = array_shift($property['name']);
                $names = [];
                foreach ($property['name'] as $name) {
                    $names[] = $data[$name];
                }
                $value .= implode($delimiter, $names);
            } else {
                if (isset($data[$property['name']])) {

                    if (is_array($data[$property['name']])) {
                        if (isset($property['format'])) {
                            $params = array_merge([$property['format']],$data[$property['name']]);
                            $data[$property['name']] = call_user_func_array('sprintf', $params);
                        } else {
                            $data[$property['name']] = implode('', $data[$property['name']]);
                        }
                    }

                    $value .= $data[$property['name']];
                }
            }
            if (empty($value) && $value !== 0 && $value !== '0') {
                continue;
            }

            if (isset($property['dateformat'])
                && false !== ($timestamp = strtotime($value))
            ) {
                $value = date($property['dateformat'], $timestamp);
            }

            $value_str = $property['prefix'] . $value . $property['suffix'];

            $this->engine->SetFont($property['font'], $property['style'], $property['size']);

            $C = isset($property['color'][0]) ? $property['color'][0] :  0;
            $M = isset($property['color'][1]) ? $property['color'][1] : -1;
            $Y = isset($property['color'][2]) ? $property['color'][2] : -1;
            $K = isset($property['color'][3]) ? $property['color'][3] : -1;
            $this->engine->SetTextColor($C, $M, $Y, $K);

            $spacing = (empty($property['pitch'])) ? 0 : $property['pitch'];
            $this->engine->setFontSpacing($spacing);

            switch ($property['type']) {
                case 'Cell' :
                    $this->engine->Cell($property['width'], $property['height'], $value_str, 0, 0, $property['align'], 0, '', 1, $property['flg']);
                    break;
                case 'MultiCell' :
                    $this->engine->MultiCell($property['width'], 0, $value_str, 0, $property['align'], 0, 1, '', '', $property['flg'], 0, false, false, $property['height']);
                    break;
                case 'Image' :
                    $this->engine->Image($data[$property['name']], $property['x'], $property['y'], $property['width'], $property['height']);
                    break;
                case 'ImageEps' :
                    $this->engine->ImageEps($data[$property['name']], $property['x'], $property['y'], $property['width'], $property['height']);
                    break;
                case 'Trif' :
                    if (empty($data[$property['name']])
                        && (string)$data[$property['name']] !== '0'
                    ) {
                        break;
                    }
                    $value = number_format($data[$property['name']]);
                case 'Tri' :
                    $value_str = $property['prefix'] . preg_replace("/^-/", "", $value) . $property['suffix'];
                    if ($value < 0) {
                        $text_width = $this->engine->GetStringWidth($value_str);
                        if (empty($property['poly'])) {
                            $pitch = empty($property['pitch']) ? 1 : 1 / $property['pitch'];
                            $width = round($text_width / strlen($value_str) * $pitch);
                            $height = $width * (1 + (1 - $pitch));
                            $shift = ($pitch === 1) ? $text_width + $width / 2 : $text_width;
                            $x = $property['x'] + ($property['width'] - $shift) - 3;
                            $y = $origin_y + (($property['height'] - $height) / 1.8) - 0.3;
                        } else {
                            $width = $property['poly'][0];
                            $height = $property['poly'][1];
                            $x = $property['x'] + ($property['width'] - ($text_width + $property['poly'][2]));
                            $y = $origin_y + $property['poly'][3];
                        }
                        $this->engine->Polygon([
                            $x, $y,
                            $x + ($width / 2), $y + $height,
                            $x - ($width / 2), $y + $height
                        ]);
                    }
                    $this->engine->Cell(
                        $property['width'], $property['height'], $value_str,
                        0, 0, $property['align'], 0, '', 1, $property['flg']
                    );
                    break;
                case 'Curr' :
                    if (empty($data[$property['name']])) {
                        break;
                    }
                    if ($data[$property['name']] >= 0) {
                        $property['prefix'] = '';
                    }
                    $value_str = $property['prefix'] . number_format(abs($value)) . $property['suffix'];
                    $this->engine->Cell(
                        $property['width'], $property['height'], $value_str,
                        0, 0, $property['align'], 0, '', 1, $property['flg']
                    );
                    break;
            }
        }
    }

    public function encrypt(array $permissions, $user_password = '', $master_password = null, $mode = 0, $pubkeys = null): void
    {
        $this->engine->SetProtection($permissions, $user_password, $master_password, $mode, $pubkeys);
    }
}
