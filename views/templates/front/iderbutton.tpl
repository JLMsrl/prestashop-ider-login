{*
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
*}

<div class="{{Configuration::get('IDER_LOGIN_WRAPPER_CLASSES')}}" style="{{Configuration::get('IDER_LOGIN_WRAPPER_CSS')}}">
    <a href="{{IDER_Helpers::getIDerButtonLink()}}" style="{{Configuration::get('IDER_LOGIN_BUTTON_CSS')}}">
        <div class="ider-login-button">{l s='Login with IDer' mod='ider_login'}</div>
    </a>
</div>
