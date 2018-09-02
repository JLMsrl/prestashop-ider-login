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

<div class="panel">
	<h3><i class="icon icon-tags"></i> {l s='Documentation' mod='ider_login'}</h3>
	<p>{l s='This plugin is meant to be used with' mod='ider_login'} <a target="_blank" href="https://www.ider.com/">IDer Connect System</a></p>
	<br/>
	<h4>{l s='Setting up IDer Client Account' mod='ider_login'}</h4>
	<ul style="padding-left: 15px;">
		<li>
			{l s='Create a new client and set the Redirect URI (aka callback URL) to:' mod='ider_login'} <strong>IDer_helpers::getBasePath()idercallback</strong>
		</li>
		<li>
			{l s='Copy the Client ID and Client Secret in the text fields below.' mod='ider_login'}
		</li>
		<li>
			{l s='Set the campaign id to retrieve the user data you chose.' mod='ider_login'}
		</li>
		<li>
			{l s='If you open a custom campaign and want your customer to land on a specific page, please configure it in the advanced setting "Campaigns Landing pages" using the format' mod='ider_login'} <strong>&lt;{l s='Campaign id' mod='ider_login'}&gt;=&lt;{l s='Landing Page' mod='ider_login'}&gt;</strong>
		</li>
	</ul>
</div>
