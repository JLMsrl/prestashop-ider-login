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

class IDER_Callback
{

    /**
     * It handles the user registration after OID login.
     */
    static function handler($userInfo)
    {
        // Normalize the user info
        $userInfo = IDER_UserInfoManager::normalize($userInfo);

        // Trigger an hook where a user can handle as he want
        $handled = Hook::exec('iderBeforeCallbackHandler', array($userInfo, $_SESSION['openid_connect_scope']));

        // if user function hadn't been exclusive let's resume the standard flow
        if (!$handled) {
            self::defaultHandler($userInfo);
        }
    }

    /**
     * Register a user if doesn't exists or login otherwise.
     */
    static function defaultHandler($userInfo)
    {

        $module = new IDer_Login();

        // ps: if user uses same email on a new IDer profile the sub will be updated on the old profile
        // check if user exists by email
        $user = Customer::getCustomersByEmail($userInfo->email);

        // Get the first user of the array (kind like LIMIT 1 of sql)
        $user = reset($user);

        // check if user exists by sub
        if (!$user) {
            $user = self::_user_load_by_sub($userInfo->sub);
        }

        // if new, register first
        if (!$user) {
            $user = self::_do_register($userInfo);
        }

        // Here the user should be always available
        $userID = (array_key_exists('id_customer', $user)) ? $user['id_customer'] : $user->id;

        if($user) {
            // update user sub
            self::_update_ider_sub($userID, $userInfo->sub);
        }

        // unset the sub
        unset($userInfo->sub);

        // check for email changes
        if($user['email'] !== $userInfo->email){
             if(self::_local_mail_identical($userID, $user['email'])){
                 self::_update_user_mail($userID, $userInfo->email);
             }else{
                 self::user_logout();
                 Tools::displayError('Fatal error');

                 self::access_denied($module->l('403 Forbidden'), $module->l('Update the IDer email first!'));
                 Tools::redirect(IDER_Helpers::getBasePath());
             }
        }

        // Log the User In
        self::_login_customer($userID);
        // update the user info into IDer table
        self::_update_ider_table($userID, $userInfo);

        // Update customer addresses
        self::_update_ider_address($userID, $userInfo);

        if (Context::getContext()->customer->isLogged()) {

            // pass the control to user defined functions and landing pages
            Hook::exec('iderAfterCallbackHandler', array($userInfo, $_SESSION['openid_connect_scope']));

            // Redirect the user to the right page
            Tools::redirect(Configuration::get('IDER_LOGIN_WELCOME_PAGE'));

            exit;
        }

        self::access_denied($module->l('403 Forbidden'), $module->l('Customer unable to login.'));

    }

    /**
     * Show error message if the user doesn't have access.
     */
    static function access_denied($errorMsg, $description = '')
    {
        $module = new IDer_Login();

        if (is_null($errorMsg)) {
            $errorMsg = $module->l("Error authenticating user");
        }

        $template = Tools::file_get_contents(__DIR__ . '/../views/templates/front/errors/error.html');

        $template = str_replace('ider_error_title', $errorMsg, $template);
        $template = str_replace('ider_error_description', $errorMsg, $template);

        die($template);
    }
    /**
     * Logout the user.
     */
    static function user_logout()
    {
        $customer = Context::getContext()->customer;

        $customer->logout();

        // Just to be sure
        @session_destroy();
        @session_start();
    }

    /**
     * It loads the user by sub.
     */
    private static function _user_load_by_sub($userSub)
    {

        $sql = "SELECT * FROM " . _DB_PREFIX_ . "ider_login WHERE sub='"  . pSQL($userSub) . "'";

        $result = Db::getInstance()->GetRow($sql);

        // Load the user.
        if($result) {
            return (new Customer((int)$result['id_customer']));
        }

        return false;

    }

    /**
     * Update IDer database sub.
     */
    private static function _update_ider_sub($userID, $userSub)
    {
        $sql = "INSERT INTO " . _DB_PREFIX_ . "ider_login (id_customer, sub) VALUES (" . pSQL($userID) . ", '" . pSQL($userSub) ."') ON DUPLICATE KEY UPDATE sub=sub";

        $result = Db::getInstance()->execute($sql);

        return $result;
    }

    /**
     * Add a record foreach field inside IDer table
     */
    private static function _update_ider_table($userID, $userInfo)
    {
        foreach ($userInfo as $key => $value) {

            $sql = "INSERT INTO " . _DB_PREFIX_ . "ider_user_data (id_customer, user_field, user_value) VALUES('" . $userID . "', '" . pSQL($key) . "', '" . pSQL($value) . "') ON DUPLICATE KEY UPDATE id_customer=VALUES(id_customer), user_field=VALUES(user_field), user_value=VALUES(user_value)";

            // Run the query
            Db::getInstance()->execute($sql);

        }
    }

    /**
     * Update the IDer address
     */
    private static function _update_ider_address($userID, $userInfo)
    {

        $userInfo = (array)$userInfo;

        // Get the address ID
        $sql = "SELECT * FROM " . _DB_PREFIX_ . "ider_login WHERE id_customer=" . $userID;
        $result = Db::getInstance()->GetRow($sql);

        if(State::getIdByIso($userInfo['address.region']) && Country::getByIso($userInfo['address.country'])) {

            $address = new CustomerAddressCore($result['id_address']);

            $address->id_customer = $userID;
            $address->id_country = Country::getByIso($userInfo['address.country']);
            $address->id_state = State::getIdByIso($userInfo['address.region']);
            $address->alias = "IDer";
            $address->lastname = $userInfo['family_name'];
            $address->firstname = $userInfo['given_name'];
            $address->address1 = $userInfo['address.street_address'];
            $address->postcode = $userInfo['address.postal_code'];
            $address->city = $userInfo['address.locality'];

            $address->save();

            $addressID = (array_key_exists('id_address', $address)) ? $address['id_address'] : $address->id;

            // Associate customer ID
            $sql = "UPDATE " . _DB_PREFIX_ . "ider_login SET id_address='" . pSQL($addressID) . "' WHERE id_customer=" . $userID;

            return Db::getInstance()->execute($sql);

        }

       return false;

    }

    /**
     * Register an user.
     */
    private static function _do_register($customerInfo)
    {
        if (!empty($customerInfo->sub)) {

            $password = Tools::passwdGen();

            // Build customer fields.
            $customer = new CustomerCore();
            $customer->email = $customerInfo->email;
            $customer->firstname = $customerInfo->given_name;
            $customer->lastname = $customerInfo->family_name;
            $customer->id_gender = ($customerInfo->gender == 'm') ? 1 : 2;
            $customer->birthday = $customerInfo->birthdate;
            $customer->active = true;
            $customer->deleted = false;
            $customer->is_guest = false;
            $customer->passwd = Tools::hash($password);

            // Create a new user account.
            if ($customer->add()) {
                return $customer;
            }
        }
        //Error
        return false;
    }

    /**
     * Check if the local mail are identical
     */
    private static function _local_mail_identical($userID, $userMail)
    {

        $areIdentical = true;

        $sql = "SELECT COUNT(*) FROM " . _DB_PREFIX_ . "ider_user_data WHERE id_customer="  . pSQL($userID) . " AND user_field='email' AND user_value='" . pSQL($userMail) . "'";

        $iderLocalEmail = Db::getInstance()->getValue($sql) > 0;

        if(!$iderLocalEmail){
            $areIdentical = false;
        }

        return $areIdentical;

    }
    /**
     * Update the old mail with a new one
     */
    private static function _update_user_mail($userID, $email)
    {
        $user = new Customer($userID);
        $user->email = $email;
        $user->save();
    }

    /**
     * Logs a given customer in.
     *
     * @throws mixed
     * @return mixed
     */
    public static function _login_customer($id_customer)
    {
        // Make sure that that the customers exists.
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "customer` WHERE `id_customer` = '" . pSQL($id_customer) . "'";
        $result = Db::getInstance()->GetRow($sql);
        // The user account has been found!
        if (!empty($result['id_customer'])) {
            // See => CustomerCore::getByEmail
            $customer = new Customer();
            $customer->id = $result['id_customer'];
            foreach ($result as $key => $value) {
                if (key_exists($key, $customer)) {
                    $customer->{$key} = $value;
                }
            }
            // See => AuthControllerCore::processSubmitLogin
            Hook::exec('actionBeforeAuthentication');
            $context = Context::getContext();
            $context->cookie->id_customer = (int) ($customer->id);
            $context->cookie->customer_lastname = $customer->lastname;
            $context->cookie->customer_firstname = $customer->firstname;
            $context->cookie->logged = 1;
            $context->cookie->is_guest = $customer->isGuest();
            $context->cookie->passwd = $customer->passwd;
            $context->cookie->email = $customer->email;
            // Customer is logged in
            $customer->logged = 1;
            // Add customer to the context
            $context->customer = $customer;
            if (Configuration::get('PS_CART_FOLLOWING') && (empty($context->cookie->id_cart) || Cart::getNbProducts($context->cookie->id_cart) == 0) && $id_cart = (int) Cart::lastNoneOrderedCart($context->customer->id)) {
                $context->cart = new Cart($id_cart);
            }else{
                $context->cart->id_carrier = 0;
                $context->cart->setDeliveryOption(null);
                $context->cart->id_address_delivery = Address::getFirstCustomerAddressId((int) ($customer->id));
                $context->cart->id_address_invoice = Address::getFirstCustomerAddressId((int) ($customer->id));
            }
            $context->cart->id_customer = (int) $customer->id;
            $context->cart->secure_key = $customer->secure_key;
            $context->cart->save();
            $context->cookie->id_cart = (int) $context->cart->id;
            $context->cookie->update();
            $context->cart->autosetProductAddress();
            Hook::exec('actionAuthentication');
            // Login information have changed, so we check if the cart rules still apply
            CartRule::autoRemoveFromCart($context);
            CartRule::autoAddToCart($context);
            // Customer is now logged in.
            return true;
        }
        // Invalid customer specified.
        return false;
    }

    /**
     * Creates a new customer based on the given data.
     */
    public static function _create_customer_from_data(array $userData)
    {
        if (is_array($userData) && !empty($userData['user_token']) && !empty($userData['identity_token']))
        {
            $password = Tools::passwdGen();

            // Build customer fields.
            $customer = new CustomerCore();
            $customer->firstname = $userData['user_first_name'];
            $customer->lastname = $userData['user_last_name'];
            $customer->id_gender = $userData['user_gender'];
            $customer->birthday = $userData['user_birthdate'];
            $customer->active = true;
            $customer->deleted = false;
            $customer->is_guest = false;
            $customer->passwd = Tools::hash($password);

            // Create a new user account.
            if ($customer->add()) {
                return true; // $customer->id;
            }
        }
        //Error
        return false;
    }

}
