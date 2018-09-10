<?php

/**
 * Jlm SRL
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.ider.com/IDER-LICENSE-COMMUNITY.txt
 *
 ********************************************************************
 * @package    Jlmsrl_Iderlogin
 * @copyright  Copyright (c) 2016 - 2018 Jlm SRL (http://www.jlm.srl)
 * @license    https://www.ider.com/IDER-LICENSE-COMMUNITY.txt
 */

class IDER_Helpers extends Tools
{
    /**
     * It returns the URL+URI.
     *
     * @return string
     */
    public static function getBasePath()
    {
        return trim(_PS_BASE_URL_, '/') . '/' . trim(__PS_BASE_URI__, '/') . '/';
    }

    /**
     * It returns the IDer button link.
     *
     * @return string
     */
    public static function getIDerButtonLink()
    {
        return static::getBasePath() . 'iderbutton';
    }

    public static function getBaseResourcePath()
    {
        return trim(__PS_BASE_URI__, '/') . '/';
    }

    /**
     * Function to log into file.
     *
     * @param $text
     * @param $filename
     * @param string $ext
     */
    static function logRotate($text, $filename, $ext = 'log')
    {
        $text = "[" .strftime("%Y-%m-%d %H:%M:%S") . "] " . $text . "\n";
        // Add basepath.
        $filename = IDER_PLUGIN_DIR . '/logs/' . $filename;
        // Add the point.
        $ext = '.' . $ext;
        if (!file_exists($filename . $ext)) {
            touch($filename . $ext);
            chmod($filename . $ext, 0755);
        }
        // 2 mb
        if (filesize($filename . $ext) > 5 * 1024 * 1024) {
            // Search for available filename.
            $n = 1;
            while (file_exists($filename . '.' . $n . $ext)) {
                $n++;
            }
            rename($filename . $ext, $filename . '.' . $n . $ext);
            touch($filename . $ext);
            chmod($filename . $ext, 0755);
        }
        if (!is_writable($filename . $ext)) {
            error_log("Cannot open log file ($filename$ext)");
        }
        if (!$handle = fopen($filename . $ext, 'a')) {
            echo "Cannot open file ($filename$ext)";
        }
        if (fwrite($handle, $text) === FALSE) {
            echo "Cannot write to file ($filename$ext)";
        }
        fclose($handle);
    }

    /**
     * It checks if a string is a JSON.
     *
     * @param $string
     * @return bool
     */
    static function isJSON($string){
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }

}
