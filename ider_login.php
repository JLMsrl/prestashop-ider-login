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


// Check for PrestaShop environment.
if (!defined('_PS_VERSION_')) {
    exit;
}

// Define IDER constants.
if (!defined('IDER_MODULE_FILE')) {
    define('IDER_MODULE_FILE', __FILE__);
}

if (!defined('IDER_MODULE_DIR')) {
    define('IDER_MODULE_DIR', __DIR__);
}

if (!defined('IDER_CLIENT_VERSION')) {
    define('IDER_CLIENT_VERSION', '1.0.0');
}

if (!defined('IDER_SITE_DOMAIN')) {
    define('IDER_SITE_DOMAIN', implode(".", array_reverse(array_slice(array_reverse(explode(".", $_SERVER['HTTP_HOST'])), 0, 2))));
}

class IDer_Login extends Module
{

    /**
     * Define default config.
     */
    protected $defaultSettings = [
        'IDER_LOGIN_CLIENT_ID' => null,
        'IDER_LOGIN_CLIENT_SECRET' => null,
        'IDER_LOGIN_EXTRA_SCOPE' => null,
        'IDER_LOGIN_ENABLE_BUTTON' => null,
        'IDER_LOGIN_WELCOME_PAGE' => null,
        'IDER_LOGIN_CAMPAIGNS_LANDING_PAGES' => null,
        'IDER_LOGIN_WRAPPER_CLASSES' => 'ider-login-wrapper',
        'IDER_LOGIN_WRAPPER_CSS' => null,
        'IDER_LOGIN_BUTTON_CSS' => null,
    ];

    /**
     * IDer_Login constructor.
     */
    public function __construct()
    {
        $this->name = 'ider_login';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'JLMsrl';

        $this->need_instance = false;
        $this->ps_versions_compliancy = array(
            'min' => '1.6',
            'max' => _PS_VERSION_
        );
        $this->bootstrap = true;

        // Call the parent constructor
        parent::__construct();

        $this->displayName = $this->l('IDer Login');
        $this->description = 'This module provides functionality to register and connect to your PrestaShop via IDer Service.';

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall IDer Login?');

        if(empty(Configuration::get('IDER_LOGIN_CLIENT_ID'))
            || empty(Configuration::get('IDER_LOGIN_CLIENT_SECRET'))
            || empty(Configuration::get('IDER_LOGIN_EXTRA_SCOPE')))
        {
            $this->warning = $this->l('The IDer module is not configured properly.');
        }

        // Composer
        require_once(dirname(__FILE__) . '/vendor/autoload.php');

        // Require module tools
        require_once(dirname(__FILE__) . '/includes/IDER_Server.php');

        // Set debug mode
        if (defined('IDER_SERVER')) {
            \IDERConnect\IDEROpenIDClient::$IDERServer = IDER_SERVER;
        }

        // Bootstrap the IDER Server instance
        IDER_Server::instance();
    }

    /**
     * Install the module.
     */
    public function install()
    {
        // Run the SQL queries.
        include(dirname(__FILE__) . '/sql/install.php');

        // Set default plugin settings
        foreach ($this->defaultSettings as $defaultSetting => $value) {
            if($value){
                Configuration::updateValue($defaultSetting, $value);
            }
        }

        return parent::install()
            && $this->registerHook('backOfficeHeader')
            && $this->registerHook('moduleRoutes')
            && $this->registerHook('displayCustomerLoginFormAfter')
            && $this->registerHook('header')
            && $this->registerHook('iderLoginBeforeCallbackHandler')
            && $this->registerHook('iderLoginAfterCallbackHandler');
    }

    /**
     * Uninstall the module.
     */
    public function uninstall()
    {
        // Run the SQL queries.
        include(dirname(__FILE__) . '/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Load the configuration form.
     */
    public function getContent()
    {
        // If values have been submitted in the form, process.
        if (((bool)Tools::isSubmit('submitIderLoginModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $outputBefore = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
        $outputAfter = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/variables.tpl');

        return $outputBefore . $this->renderForm() . $outputAfter;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitIderLoginModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 4,
                        'type' => 'text',
                        'name' => 'IDER_LOGIN_CLIENT_ID',
                        'label' => $this->l('Client ID')
                    ),
                    array(
                        'col' => 4,
                        'type' => 'text',
                        'name' => 'IDER_LOGIN_CLIENT_SECRET',
                        'label' => $this->l('Client Secret')
                    ),
                    array(
                        'col' => 4,
                        'type' => 'text',
                        'name' => 'IDER_LOGIN_EXTRA_SCOPE',
                        'label' => $this->l('Scope Name')
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Login button'),
                        'name' => 'IDER_LOGIN_ENABLE_BUTTON',
                        'is_bool' => true,
                        'desc' => $this->l('Adds IDer button in the default PrestaShop login.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            )
                        )
                    ),
                    array(
                        'col' => 4,
                        'type' => 'text',
                        //     'prefix' => IDER_Helpers::getBasePath(),
                        'name' => 'IDER_LOGIN_WELCOME_PAGE',
                        'label' => $this->l('Welcome Page')
                    ),
                    array(
                        'col' => 4,
                        'rows' => 6,
                        'type' => 'textarea',
                        'name' => 'IDER_LOGIN_CAMPAIGNS_LANDING_PAGES',
                        'label' => $this->l('Campaigns Landing pages')
                    ),
                    array(
                        'col' => 4,
                        'type' => 'text',
                        'name' => 'IDER_LOGIN_WRAPPER_CLASSES',
                        'label' => $this->l('IDer Button wrapper additional classes'),
                        'desc' => $this->l('If you\'re using Bootstrap you can write "col-md-4 col-md-offset-3" for example.'),
                    ),
                    array(
                        'col' => 4,
                        'rows' => 6,
                        'type' => 'textarea',
                        'name' => 'IDER_LOGIN_WRAPPER_CSS',
                        'label' => $this->l('IDer Button wrapper additional css'),
                    ),
                    array(
                        'col' => 4,
                        'rows' => 6,
                        'type' => 'textarea',
                        'name' => 'IDER_LOGIN_BUTTON_CSS',
                        'label' => $this->l('IDer Button additional css'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'IDER_LOGIN_CLIENT_ID' => Configuration::get('IDER_LOGIN_CLIENT_ID'),
            'IDER_LOGIN_CLIENT_SECRET' => Configuration::get('IDER_LOGIN_CLIENT_SECRET'),
            'IDER_LOGIN_EXTRA_SCOPE' => Configuration::get('IDER_LOGIN_EXTRA_SCOPE'),
            'IDER_LOGIN_ENABLE_BUTTON' => Configuration::get('IDER_LOGIN_ENABLE_BUTTON'),
            'IDER_LOGIN_WELCOME_PAGE' => Configuration::get('IDER_LOGIN_WELCOME_PAGE'),
            'IDER_LOGIN_CAMPAIGNS_LANDING_PAGES' => Configuration::get('IDER_LOGIN_CAMPAIGNS_LANDING_PAGES'),
            'IDER_LOGIN_WRAPPER_CLASSES' => Configuration::get('IDER_LOGIN_WRAPPER_CLASSES', 'ider-login-wrapper'),
            'IDER_LOGIN_WRAPPER_CSS' => Configuration::get('IDER_LOGIN_WRAPPER_CSS'),
            'IDER_LOGIN_BUTTON_CSS' => Configuration::get('IDER_LOGIN_BUTTON_CSS')
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $formValues = $this->getConfigFormValues();

        foreach (array_keys($formValues) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name || Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
        }
    }

    /**
     * Add the custom routes to make IDer work.
     */
    public function hookModuleRoutes()
    {
        return array(
            'module-ider_login-iderbutton' => array( //PrestaShop will use this pattern to compare addresses: module-{module_name}-{controller_name}
                'controller' => 'iderbutton', // Module controller name "ider_login/controllers/front/{$value}" where $value = iderbutton
                'rule' => 'iderbutton', // Page URL
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ider_login', // Module name
                )
            ),
            'module-ider_login-idercallback' => array(
                'controller' => 'idercallback',
                'rule' => 'idercallback',
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'ider_login',
                )
            )
        ) ;
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    /**
     * Hooks the content after the login form.
     */
    public function hookDisplayCustomerLoginFormAfter()
    {
        if(Configuration::get('IDER_LOGIN_ENABLE_BUTTON')) {
            // Check if the IDer config is correct
            if(!empty(Configuration::get('IDER_LOGIN_CLIENT_ID'))
                && !empty(Configuration::get('IDER_LOGIN_CLIENT_SECRET'))
                && !empty(Configuration::get('IDER_LOGIN_EXTRA_SCOPE')))
            {
                return $this->display(__FILE__, 'views/templates/front/iderbutton.tpl');
            }
        }
    }

    /**
     * Before the IDer handler.
     */
    public function hookIderLoginBeforeCallbackHandler($userInfo, $scopes)
    {
        $handled = false;
        if (in_array('yourscope', $scopes)) {
            // do something...

            // true will prevent further processing
            $handled = true;
        }
        return $handled;
    }

    /**
     * After the IDer handler.
     */
    public function hookIderLoginAfterCallbackHandler($userInfo, $scopes)
    {
        if(!empty($scopes)){

            if (in_array('yourscope', $scopes)) {
                // do something...
            }

        }

        $landingPages = Configuration::get('IDER_LOGIN_CAMPAIGNS_LANDING_PAGES');

        preg_match_all('/^(?!#)([\w-]+)=(.+)/m', $landingPages, $matches);

        $landingPagesArray = array_combine($matches[1], $matches[2]);

        foreach ($landingPagesArray as $scope => $landingPage) {
            if (in_array($scope, $scopes)) {

                Tools::redirect($landingPage);
                exit;

            }
        }

    }

}

