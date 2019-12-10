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

class plgHikashoppaymentIngpspCashondelivery extends IngpspPlugin
{
    var $name = 'ingpspcashondelivery';

    public function __construct($subject, array $config)
    {
        parent::__construct($subject, $config);

        unset($this->pluginConfig['invalid_status']);
        unset($this->pluginConfig['verified_status']);

        $this->pluginConfig['order_status'] = array('ORDER_STATUS', 'orderstatus', 'verified');
    }

    /**
     * @param $element
     * @since v1.0.0
     */
    public function getPaymentDefaultValues(&$element)
    {
        $element->payment_name = JText::_('PLG_HIKASHOPPAYMENT_INGSPSPCASHONDELIVERY_NAME');
        $element->payment_description = JText::_('PLG_HIKASHOPPAYMENT_INGSPSPCASHONDELIVERY_NAME');
        $element->payment_params->address_type = 'billing';
        $element->payment_params->notification = 1;
        $element->payment_params->order_status = 'created';
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

        if (empty($this->payment_params->api_key)) {
            $this->app->enqueueMessage(
                JText::_('PLG_HIKASHOPPAYMENT_INGSPSPCASHONDELIVERY_ERROR_APIKEY_NOT_SET'),
                'error'
            );
            $this->app->redirect($this->pluginConfig['cancel_url'][2].'&order_id='.$order->order_id);
        } else {
            try {
                $ingOrder = $this->createIngpspOrder($order);

                if ($ingOrder->status()->isError()) {
                    $this->app->enqueueMessage(
                        JText::_($ingOrder->transactions()->current()->reason()->toString()),
                        'error'
                    );
                    $this->app->enqueueMessage(
                        JText::_('PLG_HIKASHOPPAYMENT_INGSPSPCASHONDELIVERY_ERROR_TRANSACTION'),
                        'error'
                    );
                    $this->app->redirect($this->pluginConfig['cancel_url'][2].'&order_id='.$order->order_id);
                }

                if ($order->order_status != $this->payment_params->order_status) {
                    $this->modifyOrder($order->order_id, $this->payment_params->order_status,
                        (bool) @$this->payment_params->status_notif_email,
                        false
                    );
                }

                $currencyClass = hikashop_get('class.currency');
                $this->amount = $currencyClass->format($order->order_full_price, $order->order_currency_id);
                $this->order_number = $order->order_number;
                $this->removeCart = true;

                return $this->showPage('end');
            } catch (\Exception $e) {
                $this->app->enqueueMessage($e->getMessage(), 'error');
                $this->app->redirect($this->pluginConfig['cancel_url'][2].'&order_id='.$order->order_id);
            }
        }
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

        return $ginger->createCashondeliveryOrder(
            $totalInCents, // Amount in cents
            $currency,     // Currency
            [],            // Payment details
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
