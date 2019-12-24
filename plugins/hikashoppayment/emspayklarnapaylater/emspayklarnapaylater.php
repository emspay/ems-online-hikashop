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

class plgHikashoppaymentEmspayKlarnapaylater extends EmspayPlugin
{
    var $name = 'emspayklarnapaylater';

    public function __construct($subject, array $config)
    {
        parent::__construct($subject, $config);

        $this->pluginConfig['shipped_status'] = ['PLG_HIKASHOPPAYMENT_EMSPAYKLARNAPAYLATER_SHIPPED_STATUS', 'orderstatus'];
        $this->pluginConfig['test_api_key'] = ['PLG_HIKASHOPPAYMENT_EMSPAYKLARNAPAYLATER_TEST_API_KEY', 'input'];
        $this->pluginConfig['ip_filtering'] = ['PLG_HIKASHOPPAYMENT_EMSPAYKLARNAPAYLATER_IP_FILTERING', 'input'];
    }

    /**
     * @param $element
     * @since v1.0.0
     */
    public function getPaymentDefaultValues(&$element)
    {
        $element->payment_name = JText::_('PLG_HIKASHOPPAYMENT_EMSPAYKLARNAPAYLATER_NAME');
        $element->payment_description = JText::_('PLG_HIKASHOPPAYMENT_EMSPAYKLARNAPAYLATER_DESCRIPTION_INPUT_TEXT');
        $element->payment_params->address_type = 'billing';
        $element->payment_params->notification = 1;
        $element->payment_params->invalid_status = 'cancelled';
        $element->payment_params->verified_status = 'confirmed';
        $element->payment_params->shipped_status = 'shipped';
    }

    /**
     * @param $order
     * @param $send_email
     */
    public function onAfterOrderUpdate(&$order, &$send_email)
    {
        $hikaOrder = $this->getOrder($order->order_id);
        $this->loadPaymentParams($hikaOrder);

        if ($order->order_status == $this->payment_params->shipped_status) {

            if (isset($order->old) && $order->old->order_payment_method == $this->name) {

                $ginger = \GingerPayments\Payment\Ginger::createClient(
                    $this->payment_params->test_api_key ?: $this->payment_params->api_key
                );

                if ($this->payment_params->bundle_cacert === '1') {
                    $ginger->useBundledCA();
                }

                $ginger->setOrderCapturedStatus(
                    $ginger->getOrder($order->old->order_payment_params['emspay_order_id'])
                );
            }
        }
    }

    /**
     * @param $order
     * @param $methods
     * @param $method_id
     * @return bool
     * @since v1.0.0
     */
    public function onAfterOrderConfirm(&$order, &$methods, $method_id)
    {
        hikashopPaymentPlugin::onAfterOrderConfirm($order, $methods, $method_id);

        if (empty($this->payment_params->api_key) && empty($this->payment_params->test_api_key)) {
            $this->app->enqueueMessage(JText::_('PLG_HIKASHOPPAYMENT_EMSPAYKLARNAPAYLATER_ERROR_APIKEY_NOT_SET'), 'error');
            $this->app->redirect($this->pluginConfig['cancel_url'][2].'&order_id='.$order->order_id);
        } else {
            try {
                $emsOrder = $this->createEmspayOrder($order);
                if ($emsOrder['status'] == 'error')  {
                    $this->app->enqueueMessage(
                        JText::_($emsOrder->transactions()->current()->reason()->toString()),
                        'error'
                    );
                    $this->app->redirect($this->pluginConfig['cancel_url'][2].'&order_id='.$order->order_id);
                } elseif ($emsOrder->status()->isCancelled()) {
                    $this->modifyOrder($order->order_id, $this->payment_params->invalid_status, true, true);
                    $this->app->enqueueMessage(
                        JText::_(PLG_HIKASHOPPAYMENT_EMSPAYKLARNAPAYLATER_ERROR_TRANSACTION_IS_CANCELLED),
                        'error'
                    );
                    $this->app->redirect($this->pluginConfig['cancel_url'][2].'&order_id='.$order->order_id);
                } else {

                    $payment_params = ['emspay_order_id' => $emsOrder->id()->toString()];

                    $this->modifyOrder($order->order_id, $this->payment_params->verified_status, true, true,
                        $payment_params);

                    $this->app->enqueueMessage(
                        JText::_('PLG_HIKASHOPPAYMENT_EMSPAYKLARNAPAYLATER_MESSAGE_TRANSACTION_SUCCESS')
                    );
                    hikashop_get('class.cart')->cleanCartFromSession(false);
                    return $this->showPage('end');
                }
            } catch (\Exception $e) {
                $this->app->enqueueMessage($e->getMessage(), 'error');
                $this->app->redirect($this->pluginConfig['cancel_url'][2].'&order_id='.$order->order_id);
            }
        }
    }

    /**
     * @param $order
     * @param $methods
     * @param $usable_methods
     * @return mixed
     */
    public function onPaymentDisplay(&$order, &$methods, &$usable_methods)
    {
        foreach ($methods as $method) {
            if ($method->payment_type == $this->name) {
                if (!EmspayHelper::ipIsEnabled($method->payment_params->ip_filtering)) {
                    return true;
                }
                $method->custom_html = $this->customInfoHTML();
            }
        }

        parent::onPaymentDisplay($order, $methods, $usable_methods);
    }

    /**
     * @return string
     */
    public function customInfoHTML()
    {
        $html = JText::_('PLG_HIKASHOPPAYMENT_EMSPAYKLARNAPAYLATER_MESSAGE_SELECT_GENDER').' <br/>';
        $html .= '<select name="gender" id="'.$this->name.'" class="'.$this->name.'">';
        $html .= '<option value="male" '
            .(JFactory::getSession()->get('emspay_gender') == 'male' ? " selected" : "").'>'
            .JText::_('PLG_HIKASHOPPAYMENT_EMSPAYKLARNAPAYLATER_MESSAGE_SELECT_GENDER_MALE').'</option>';
        $html .= '<option value="female" '
            .(JFactory::getSession()->get('emspay_gender') == 'male' ? " selected" : "").'>'
            .JText::_('PLG_HIKASHOPPAYMENT_EMSPAYKLARNAPAYLATER_MESSAGE_SELECT_GENDER_FEMALE').'</option>';
        $html .= "</select><br/>";
        $html .= JText::_('PLG_HIKASHOPPAYMENT_EMSPAYKLARNAPAYLATER_MESSAGE_ENTER_DOB').'<br>';
        $html .= '<input type="text" name="dob" value="'.JFactory::getSession()->get('emspay_dob').'"/>';
        $html .= "<style>.hikabtn_checkout_payment_submit{display:none;}</style>";

        return $html;
    }

    /**
     * @return array
     * @since v1.0.0
     */
    protected function createEmspayOrder()
    {
        $currency = $this->currency->currency_code;
        $totalInCents = EmspayHelper::getAmountInCents($this->order->order_full_price);
        $orderId = $this->order->order_id;
        $description = JFactory::getConfig()->get('sitename').' #'.$orderId;
        $returnUrl = $this->pluginConfig['notify_url'][2].'&merchant_order_id='.$orderId;
        $customer = EmspayHelper::getCustomerInfo($this->user, $this->order);
        $orderLines = $this->getOrderLines();
        $plugin = ['plugin' => EmspayHelper::getPluginVersion($this->name)];
        $ginger = \Ginger\Ginger::createClient(EmspayHelper::GINGER_ENDPOINT,
            $this->payment_params->api_key,
            $this->payment_params->bundle_cacert === '1' ?
                [
                    CURLOPT_CAINFO => EmspayHelper::getCaCertPath()
                ] : []
        );
        return $ginger->createOrder([
            'merchant_order_id' => (string)$orderId,
            'customer' => $customer,
            'extra' => $plugin,
            'currency' => $currency,
            'amount' => $totalInCents,
            'description' => $description,
            'return_url' => $returnUrl,
            'webhook_url' => $returnUrl,
            'transactions' => [
                [
                    'payment_method' => 'klarna-pay-later',
                ]
            ],

        ]);
    }

    /**
     * @return mixed
     * @since v1.0.0
     */
    public function getOrderLines()
    {
        $cartClass = hikashop_get('class.cart');
        $cart = $cartClass->loadFullCart();
        $orderLines = [];

        foreach ($cart->products AS $item) {
            $orderLines[] = [
                'name' => $item->product_name,
                'type' => 'physical',
                'currency' => 'EUR',
                'amount' => EmspayHelper::getAmountInCents($item->prices[0]->unit_price->price_value_with_tax),
                'quantity' => (int) $item->cart_product_quantity,
                'vat_percentage' => EmspayHelper::getAmountInCents(@$item->prices[0]->taxes[0]->tax_rate),
                'merchant_order_line_id' => $item->product_id
            ];
        }

        if ($cart->shipping && EmspayHelper::getAmountInCents(@$cart->shipping[0]->shipping_price_with_tax) > 0) {
            $orderLines[] = $this->getShippingOrderLine($cart);
        }

        return $orderLines;
    }

    /**
     * @param $cart
     * @return array
     */
    public function getShippingOrderLine($cart)
    {
        return [
            'name' => $cart->shipping[0]->shipping_name,
            'type' => 'shipping_fee',
            'amount' => EmspayHelper::getAmountInCents($cart->shipping[0]->shipping_price_with_tax),
            'currency' => 'EUR',
            'vat_percentage' => EmspayHelper::getAmountInCents(@$cart->shipping[0]->taxes[0]->tax_rate),
            'merchant_order_line_id' => $cart->shipping[0]->shipping_id,
            'quantity' => 1,
        ];
    }
}
