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

class IDER_UserInfoManager
{
    /**
     * Normalize the user info to object.
     *
     * @param $userInfo
     * @return array|mixed|object
     */
    static function normalize($userInfo)
    {
        $userInfo = (array)$userInfo;

        // explode json packed claims
        $userInfo = self::_checkJsonfields($userInfo);

        $userInfo = (object)$userInfo;

        return $userInfo;
    }

    /**
     * Flatten json from sub-keyed to single-keyed.
     *
     * @param $userData
     * @return mixed
     */
    private static function _checkJsonfields($userData)
    {
        foreach ($userData as $key => $claim) {
            if (IDER_Helpers::isJSON($claim)) {
                $subclaims = json_decode($claim);
                // break down the claim
                foreach ($subclaims as $subkey => $subclaim) {
                    $userData[$key . '.' . $subkey] = $subclaim;
                }
                // delete the original claim
                unset($userData[$key]);
            }
        }
        return $userData;
    }

}
