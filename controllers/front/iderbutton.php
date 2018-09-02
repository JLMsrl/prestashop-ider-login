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

class Ider_LoginIderbuttonModuleFrontController extends ModuleFrontController
{
    /**
     * Allow any user to access this route.
     */
    public $guestAllowed = true;

    /**
     * IDer OID client will make a redirect.
     */
    public function initContent()
    {

        if(!$this->context->customer->isLogged()) {
            IDER_Server::IDerOpenIdClientHandler();
        }

        parent::initContent();

        header('HTTP/1.0 403 Forbidden');
        header('Status: 403 Forbidden');

        // Set the view
        $this->setTemplate('module:ider_login/views/templates/front/errors/error403.tpl');

    }

}
