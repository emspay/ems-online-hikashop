<?php

defined('_JEXEC') or die('Restricted access');

/**
 *   ╲          ╱
 * ╭──────────────╮  COPYRIGHT (C) 2019 GINGER PAYMENTS B.V.
 * │╭──╮      ╭──╮│
 * ││//│      │//││  This software is released under the terms of the
 * │╰──╯      ╰──╯│  MIT License.
 * ╰──────────────╯
 *   ╭──────────╮    https://www.gingerpayments.com/
 *   │ () () () │
 *
 * @category    Ginger
 * @package     Ginger HikaShop
 * @author      Ginger Payments B.V. (plugins@gingerpayments.com)
 * @version     v1.6.0
 * @copyright   COPYRIGHT (C) 2019 GINGER PAYMENTS B.V.
 * @license     The MIT License (MIT)
 * @since       v1.0.0
 **/

JImport('ginger.GingerPluginGateway');

class plgHikashoppaymentEmspay_Ideal extends GingerPluginGateway implements GingerIssuers
{
    var $name = GingerBankConfig::BANK_PREFIX.'ideal';

    /**
     * @param $payment_params
     * @return string - html select form with issuers in option
     * @since 1.6.0
     */
    public function getIssuers($payment_params): string
    {
        $helper = new GingerHelper($payment_params);

        $html = '<select name="issuer" id="'.$this->name.'" class="'.$this->name.'">';
        $html .= '<option value="">Choose your bank:</option>';
        foreach ($helper->getIssuers() AS $issuer)
        {
            $html .= '<option value="'.$issuer['id'].'">'.$issuer['name']."</option>";
        }
        $html .= "</select>";
        $html .= "<style>.hikabtn_checkout_payment_submit{display:none;}</style>";

        return $html;
    }
}
