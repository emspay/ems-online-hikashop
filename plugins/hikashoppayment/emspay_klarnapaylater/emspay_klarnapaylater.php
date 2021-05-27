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

class plgHikashoppaymentEmspay_Klarnapaylater extends GingerPluginGateway implements GingerOrderLines
{
    var $name = GingerBankConfig::BANK_PREFIX.'klarnapaylater';

    public function __construct($subject, array $config)
    {
        $this->pluginConfig['shipped_status'] = ['PLG_HIKASHOPPAYMENT_GINGER_KLARNAPAYLATER_SHIPPED_STATUS', 'orderstatus'];
        $this->pluginConfig['test_api_key'] = ['PLG_HIKASHOPPAYMENT_GINGER_KLARNAPAYLATER_TEST_API_KEY', 'input'];
        $this->pluginConfig['ip_filtering'] = ['PLG_HIKASHOPPAYMENT_GINGER_KLARNAPAYLATER_IP_FILTERING', 'input'];
        parent::__construct($subject, $config);
    }

    /**
     * @param $element
     * @since v1.0.0
     */
    public function getPaymentDefaultValues(&$element)
    {
        parent::getPaymentDefaultValues($element);
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

        if ($order->order_status == $this->payment_params->shipped_status)
        {
            if (isset($order->old) && $order->old->order_payment_method == $this->name)
            {
                $helper = new GingerHelper($this->payment_params);
                $gingerOrder = $helper->getOrder($order->old->order_payment_params['ginger_order_id']);
                $transaction_id = !empty(current($gingerOrder['transactions'])) ? current($gingerOrder['transactions'])['id'] : null;
                $helper->captureOrder($gingerOrder['id'], $transaction_id);
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
                if (!GingerHelper::ipIsEnabled($method->payment_params->ip_filtering)) {
                    return true;
                }
            }
        }
        parent::onPaymentDisplay($order, $methods, $usable_methods);
    }

    /**
     * @return string - order lines type
     * since 1.6.0
     */
    public function getOrderLinesType()
    {
        return 'physical';
    }

    /**
     * @return string - shipping order line type
     * @since 1.6.0
     */
    public function getShippingOrderLineType()
    {
        return 'shipping_fee';
    }
}
