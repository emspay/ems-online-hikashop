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

class plgHikashoppaymentIngpspPayPal extends IngpspPlugin
{
    var $name = 'ingpsppaypal';

    /**
     * @param $element
     * @since v1.0.0
     */
    public function getPaymentDefaultValues(&$element)
    {
        $element->payment_name = JText::_('PLG_HIKASHOPPAYMENT_INGPSPPAYPAL_NAME');
        $element->payment_description = JText::_('PLG_HIKASHOPPAYMENT_INGPSPPAYPAL_DESCRIPTION');
        $element->payment_params->address_type = 'billing';
        $element->payment_params->notification = 1;
        $element->payment_params->invalid_status = 'cancelled';
        $element->payment_params->verified_status = 'confirmed';
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

        return $ginger->createPayPalOrder(
            $totalInCents, // Amount in cents
            $currency,     // Currency
            [],
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
