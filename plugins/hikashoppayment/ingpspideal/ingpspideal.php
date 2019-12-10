<?php

defined('_JEXEC') or die('Restricted access');

/**
 *   ╲          ╱
 * ╭──────────────╮  COPYRIGHT (C) 2017 GINGER PAYMENTS B.V.
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
 * @version     v1.3.0
 * @copyright   COPYRIGHT (C) 2017 GINGER PAYMENTS B.V.
 * @license     The MIT License (MIT)
 * @since       v1.0.0
 **/

JImport('emspay.ingpspplugin');

class plgHikashoppaymentIngpspIdeal extends IngpspPlugin
{
    var $name = 'ingpspideal';

    /**
     * @param $element
     * @since v1.0.0
     */
    public function getPaymentDefaultValues(&$element)
    {
        $element->payment_name = JText::_('PLG_HIKASHOPPAYMENT_INGSPSPIDEAL_NAME');
        $element->payment_description = JText::_('PLG_HIKASHOPPAYMENT_INGSPSPIDEAL_DESCRIPTION_INPUT_TEXT');
        $element->payment_params->address_type = 'billing';
        $element->payment_params->notification = 1;
        $element->payment_params->invalid_status = 'cancelled';
        $element->payment_params->verified_status = 'confirmed';
    }

    /**
     * @param array $issuers
     * @return string
     * @since v1.0.0
     */
    public function processIssuers(array $issuers)
    {
        $html = '<select name="issuer" id="'.$this->name.'" class="'.$this->name.'">';
        foreach ($issuers AS $issuer) {
            $html .= '<option value="'.$issuer['id'].'">'.$issuer['name']."</option>";
        }
        $html .= "</select>";
        $html .= "<style>.hikabtn_checkout_payment_submit{display:none;}</style>";

        return $html;
    }

    /**
     * @param $order
     * @param $methods
     * @param $usable_methods
     * @return bool
     * @since v1.0.0
     */
    public function onPaymentDisplay(&$order, &$methods, &$usable_methods)
    {
        foreach ($methods as $method) {
            if ($method->payment_type == $this->name) {
                $ginger = \GingerPayments\Payment\Ginger::createClient(
                    $method->payment_params->api_key,
                    $method->payment_params->ing_product
                );
                if ($method->payment_params->bundle_cacert === '1') {
                    $ginger->useBundledCA();
                }
                $method->custom_html = $this->processIssuers($ginger->getIdealIssuers()->toArray());
            }
        }

        parent::onPaymentDisplay($order, $methods, $usable_methods);
    }

    /**
     * @return \GingerPayments\Payment\Order
     * @since v1.0.0
     */
    protected function createIngpspOrder()
    {
        IngpspHelper::clearKlarnaSessionData();

        $currency = $this->currency->currency_code;
        $totalInCents = IngpspHelper::getAmountInCents($this->order->order_full_price);
        $issuer = JFactory::getSession()->get('ingpsp_issuer');
        $orderId = $this->order->order_id;
        $description = JFactory::getConfig()->get('sitename').' #'.$orderId;
        $returnUrl = $this->pluginConfig['notify_url'][2].'&merchant_order_id='.$orderId;
        $customer = IngpspHelper::getCustomerInfo($this->user, $this->order);
        $plugin = ['plugin' => IngpspHelper::getPluginVersion($this->name)];
        $ginger = \GingerPayments\Payment\Ginger::createClient(
            $this->payment_params->api_key,
            $this->payment_params->ing_product
        );

        if ($this->payment_params->bundle_cacert === '1') {
            $ginger->useBundledCA();
        }

        return $ginger->createIdealOrder(
            $totalInCents, // Amount in cents
            $currency,     // Currency
            $issuer,       // Issuer Id
            $description,  // Description
            $orderId,      // Merchant Order Id
            $returnUrl,    // Return URL
            null,          // Expiration Period
            $customer,     // Customer Information
            $plugin,       // Extra Information
            $returnUrl     // WebHook URL
        );
    }
}
