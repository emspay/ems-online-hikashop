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
 * @version     v1.3.0
 * @copyright   COPYRIGHT (C) 2019 GINGER PAYMENTS B.V.
 * @license     The MIT License (MIT)
 * @since       v1.0.0
 **/

JImport('emspay.emspayplugin');

class plgHikashoppaymentEmspayIdeal extends EmspayPlugin
{
    var $name = 'emspayideal';

    /**
     * @param $element
     * @since v1.0.0
     */
    public function getPaymentDefaultValues(&$element)
    {
        $element->payment_name = JText::_('PLG_HIKASHOPPAYMENT_EMSPAYIDEAL_NAME');
        $element->payment_description = JText::_('PLG_HIKASHOPPAYMENT_EMSPAYIDEAL_DESCRIPTION_INPUT_TEXT');
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
                $ginger = \Ginger\Ginger::createClient(
                    EmspayHelper::GINGER_ENDPOINT,
                    $method->payment_params->api_key,
                    $this->payment_params->bundle_cacert === '1' ?
                        [
                            CURLOPT_CAINFO => EmspayHelper::getCaCertPath()
                        ] : []
                );
                $method->custom_html = $this->processIssuers($ginger->getIdealIssuers());
            }
        }

        parent::onPaymentDisplay($order, $methods, $usable_methods);
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
        $issuer = JFactory::getSession()->get('emspay_issuer');
        $orderId = $this->order->order_id;
        $description = JFactory::getConfig()->get('sitename').' #'.$orderId;
        $returnUrl = $this->pluginConfig['notify_url'][2].'&merchant_order_id='.$orderId;
        $customer = EmspayHelper::getCustomerInfo($this->user, $this->order);
        $ginger = \Ginger\Ginger::createClient(
            EmspayHelper::GINGER_ENDPOINT,
            $this->payment_params->api_key,
            $this->payment_params->bundle_cacert === '1' ?
                [
                    CURLOPT_CAINFO => EmspayHelper::getCaCertPath()
                ] : []
        );
        return $ginger->createOrder([
            'currency' => (string) $currency,
            'amount' => (int) $totalInCents,
            'transactions' => [
                 [
                    'payment_method' => 'ideal',
                    'payment_method_details' => ['issuer_id' => (string) $issuer]
                  ]
            ],
            'merchant_order_id' => (string) $orderId,
            'description' => (string) $description,
            'customer' => (array) $customer,
            'return_url' => (string) $returnUrl,
            'webhook_url' => (string) $returnUrl,
            ]);

    }
}
