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

class plgHikashoppaymentEmspayBankTransfer extends EmspayPlugin
{
    var $name = 'emspaybanktransfer';

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
        $element->payment_name = JText::_('PLG_HIKASHOPPAYMENT_EMSPAYBANKTRANSFER_NAME');
        $element->payment_description = JText::_('PLG_HIKASHOPPAYMENT_EMSPAYBANKTRANSFER_DESCRIPTION');
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
                JText::_('PLG_HIKASHOPPAYMENT_EMSPAYBANKTRANSFER_ERROR_APIKEY_NOT_SET'),
                'error'
            );
            $this->app->redirect($this->pluginConfig['cancel_url'][2]);
        } else {
            $emsOrder = $this->createEmspayOrder();

            if ($emsOrder['status'] == 'error') {
                $this->app->enqueueMessage(
                    JText::_($emsOrder->transactions()->current()->reason()->toString()),
                    'error'
                );
                $this->app->enqueueMessage(
                    JText::_('PLG_HIKASHOPPAYMENT_EMSPAYBANKTRANSFER_ERROR_TRANSACTION'),
                    'error'
                );
                $this->app->redirect($this->pluginConfig['cancel_url'][2].'&order_id='.$order->order_id);
            }

            $paymentReference = self::getGingerPaymentReference($emsOrder);

            if ($order->order_status != $this->payment_params->order_status) {
                $this->modifyOrder($order->order_id, $this->payment_params->order_status,
                    (bool) @$this->payment_params->status_notif_email,
                    false
                );
            }

            $currencyClass = hikashop_get('class.currency');
            $this->amount = $currencyClass->format($order->order_full_price, $order->order_currency_id);
            $this->order_number = $order->order_number;
            $this->payment_reference = $paymentReference;
            $this->removeCart = true;

            return $this->showPage('end');
        }
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
        $customer = EmspayHelper::getCustomerInfo($this->user, $this->order);
        $returnUrl = $this->pluginConfig['notify_url'][2].'&merchant_order_id='.$orderId;
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
//            'customer' => $customer,
            'extra' => $plugin,
            'currency' => $currency,
            'amount' => $totalInCents,
            'description' => $description,
            'return_url' => $returnUrl,
            'transactions' => [
                [
                    'payment_method' => 'bank-transfer',
                ]
            ],

        ]);
    }

    /**
     * @param array $gingerOrder
     * @return mixed
     * @since v1.0.0
     */
    public static function getGingerPaymentReference(array $gingerOrder)
    {
        return $gingerOrder['transactions'][0]['payment_method_details']['reference'];
    }
}
