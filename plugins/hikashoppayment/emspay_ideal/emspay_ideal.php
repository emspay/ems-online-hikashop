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
     * @param $order
     * @param $methods
     * @param $usable_methods
     * @return bool
     * @since v1.0.0
     */
    public function onPaymentDisplay(&$order, &$methods, &$usable_methods)
    {
        $app = JFactory::getApplication();

        foreach ($methods as $method)
        {
            if ($method->payment_type == $this->name)
            {
                if (empty($method->payment_params->api_key)) {
                    $app->enqueueMessage(JText::_('LIB_GINGER_API_KEY_NOT_SET'), 'error');
                }else{
                    $method->custom_html = $this->getIssuers($method->payment_params);
                }
            }
        }
        parent::onPaymentDisplay($order, $methods, $usable_methods);
    }

    /**
     * @param $payment_params
     * @return string - html select form with issuers in option
     * @since 1.6.0
     */
    public function getIssuers($payment_params): string
    {
        $helper = new GingerHelper($payment_params);

        $html = '<select name="issuer" id="'.$this->name.'" class="'.$this->name.'">';
        foreach ($helper->getIssuers() AS $issuer)
        {
            $html .= '<option value="'.$issuer['id'].'">'.$issuer['name']."</option>";
        }
        $html .= "</select>";
        $html .= "<style>.hikabtn_checkout_payment_submit{display:none;}</style>";

        return $html;
    }
}
