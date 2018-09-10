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


class IDER_Server
{
    /** Server Instance */
    public static $_instance = null;
    /** Options */
    public static $options = null;

    /**
     * IDER_Server constructor.
     */
    function __construct()
    {
        self::init();
    }

    /**
     * Initialize function.
     */
    static function init()
    {
        // spl_autoload_register(array(__CLASS__, 'autoloader'));
        self::includes();
    }

    /**
     *  IDEROpenIDClient Initializer.
     */
    public static function getIDerOpenIdClientIstance()
    {
        // Overriding the redirect URL checking for subdirectory installations.
        \IDERConnect\IDEROpenIDClient::$IDERRedirectURL = IDer_Helpers::getBaseResourcePath() . \IDERConnect\IDEROpenIDClient::$IDERRedirectURL;

        // Overriding log file folder.
        \IDERConnect\IDEROpenIDClient::$IDERLogFile = IDER_MODULE_DIR . '/log/ider-connect.log';

        if (is_null(\IDERConnect\IDEROpenIDClient::$_instance)) {
            \IDERConnect\IDEROpenIDClient::$_instance = new \IDERConnect\IDEROpenIDClient(Configuration::get('IDER_LOGIN_CLIENT_ID'), Configuration::get('IDER_LOGIN_CLIENT_SECRET'), Configuration::get('IDER_LOGIN_EXTRA_SCOPE'));
        }

        return \IDERConnect\IDEROpenIDClient::$_instance;
    }

    /**
     * Start the request to IDer server.
     */
    public static function IDerOpenIdClientHandler()
    {

        $scope = Tools::getValue('scope');

        try {
            $iderconnect = IDER_Server::getIDerOpenIdClientIstance();

            if (!empty($scope)) {
                $iderconnect->setScope($scope);
            }
            $iderconnect->authenticate();

            $userInfo = $iderconnect->requestUserInfo();

            IDER_Callback::handler($userInfo);

        } catch (Exception $e) {
            return IDER_Callback::access_denied($e->getMessage());
        } finally {
            exit;
        }
    }

    /**
     * Populate the instance if the plugin for extendability.
     *
     * @return object plugin instance
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Plugin includes called during load of plugin.
     *
     * @return void
     */
    public static function includes()
    {
        include_once __DIR__ . '/IDER_Helpers.php';
        include_once __DIR__ . '/IDER_UserInfoManager.php';
        include_once __DIR__ . '/IDER_Callback.php';
    }

    /**
     * Autoloader for classes.
     *
     * @param $class
     * @return bool
     */
    private static function autoloader($class)
    {
        $path = IDER_MODULE_DIR . '/ider_login/';
        $paths = array();
        $exts = array('.php', '.class.php');
        $paths[] = $path;
        $paths[] = $path . 'includes/';
        foreach ($paths as $p)
            foreach ($exts as $ext) {
                if (file_exists($p . $class . $ext)) {
                    require_once($p . $class . $ext);
                    return true;
                }
            }
        return false;
    }

    /**
     * Includes the composer library (should be used when composer fails).
     *
     * @param $dir
     */
    private static function loadPackage($dir)
    {
        $composer = json_decode(file_get_contents("$dir/composer.json"), 1);
        $namespaces = $composer['autoload']['psr-4'];
        // Foreach namespace specified in the composer, load the given classes
        foreach ($namespaces as $namespace => $classpath) {
            spl_autoload_register(function ($classname) use ($namespace, $classpath, $dir) {
                // Check if the namespace matches the class we are looking for
                if (preg_match("#^" . preg_quote($namespace) . "#", $classname)) {
                    // Remove the namespace from the file path since it's psr4
                    $classname = str_replace($namespace, "", $classname);
                    $filename = preg_replace("#\\\\#", "/", $classname) . ".php";
                    include_once $dir . "/" . $classpath . "/$filename";
                }
            });
        }
    }
}
