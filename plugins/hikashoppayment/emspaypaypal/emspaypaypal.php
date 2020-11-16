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
 * @version     v1.5.2
 * @copyright   COPYRIGHT (C) 2019 GINGER PAYMENTS B.V.
 * @license     The MIT License (MIT)
 * @since       v1.0.0
 **/

JImport('emspay.emspayplugin');

class plgHikashoppaymentEmspayPayPal extends EmspayPlugin
{
    var $name = 'emspaypaypal';

    /**
     * @param $element
     * @since v1.0.0
     */
    public function getPaymentDefaultValues(&$element)
    {
        $element->payment_name = JText::_('PLG_HIKASHOPPAYMENT_EMSPAYPAYPAL_NAME');
        $element->payment_description = JText::_('PLG_HIKASHOPPAYMENT_EMSPAYPAYPAL_DESCRIPTION');
        $element->payment_params->address_type = 'billing';
        $element->payment_params->notification = 1;
        $element->payment_params->new_status = 'created';
        $element->payment_params->processing_status = 'pending';
        $element->payment_params->see_transactions_status = 'pending';
        $element->payment_params->completed_status = 'confirmed';
        $element->payment_params->error_status = 'cancelled';
        $element->payment_params->cancelled_status = 'cancelled';
        $element->payment_params->expired_status = 'cancelled';
    }

    /**
     * @return array
     * @since v1.0.0
     */
    protected function createEmspayOrder()
    {
        EmspayHelper::clearKlarnaSessionData();

        $currency = $this->currency->currency_code;
        $totalInCents = EmspayHelper::getAmountInCents($this->order->order_full_price);
        $orderId = $this->order->order_id;
        $description = JFactory::getConfig()->get('sitename').' #'.$orderId;
        $returnUrl = $this->pluginConfig['notify_url'][2].'&merchant_order_id='.$orderId;
        $customer = EmspayHelper::getCustomerInfo($this->user, $this->order);
        $plugin = ['plugin' => EmspayHelper::getPluginVersion($this->name)];
        $ginger = \Ginger\Ginger::createClient(EmspayHelper::GINGER_ENDPOINT,
            $this->payment_params->api_key,
            $this->payment_params->bundle_cacert === '1' ?
                [
                    CURLOPT_CAINFO => EmspayHelper::getCaCertPath()
                ] : []
        );
        return $ginger->createOrder([
            'merchant_order_id' => (string) $orderId,
            'customer' => $customer,
            'extra' => $plugin,
            'currency' => (string) $currency,
            'amount' => $totalInCents,
            'description' => $description,
            'return_url' => $returnUrl,
            'webhook_url' => $returnUrl,
            'transactions' => [
                [
                    'payment_method' => 'paypal',
                ]
            ],

        ]);
    }
}
