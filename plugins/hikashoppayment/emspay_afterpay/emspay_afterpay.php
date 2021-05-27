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

class plgHikashoppaymentEmspay_AfterPay extends GingerPluginGateway implements GingerOrderLines, GingerCustomerPersonalInformation
{
    var $name = GingerBankConfig::BANK_PREFIX.'afterpay';

    public function __construct($subject, array $config)
    {

        $this->pluginConfig['shipped_status'] = ['PLG_HIKASHOPPAYMENT_GINGER_AFTERPAY_SHIPPED_STATUS', 'orderstatus'];
        $this->pluginConfig['test_api_key'] = ['PLG_HIKASHOPPAYMENT_GINGER_AFTERPAY_TEST_API_KEY', 'input'];
        $this->pluginConfig['ip_filtering'] = ['PLG_HIKASHOPPAYMENT_GINGER_AFTERPAY_IP_FILTERING', 'input'];
        $this->pluginConfig['countries_available'] = ['PLG_HIKASHOP_GINGER_AFTERPAY_COUNTRIES_AVAILABLE', 'input'];
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
        $element->payment_params->countries_available = 'NL, BE';
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
                $userCountry = GingerHelper::getCountryZone($order->billing_address->address_country[0]);
                $availableCountries = $method->payment_params->countries_available;

                if (!GingerHelper::ipIsEnabled($method->payment_params->ip_filtering) || !GingerHelper::countriesValidation($availableCountries, current($userCountry)->zone_code_2)) {
                    return true;
                }
                $method->custom_html = $this->extraCustomerFields();
            }
        }

        parent::onPaymentDisplay($order, $methods, $usable_methods);
    }

    /**
     * @return string - html form with two input fields: gender and day of birthday
     * @since 1.6.0
     */
    public function extraCustomerFields()
    {
        $selectedMaleStatus = JFactory::getSession()->get('ginger_gender') == 'male' ? " selected" : "";
        $selectedFemaleStatus = JFactory::getSession()->get('ginger_gender') == 'female' ? " selected" : "";

        $html = "
        
        ".JText::_('PLG_HIKASHOPPAYMENT_GINGER_AFTERPAY_MESSAGE_SELECT_GENDER').'<br/>'."
        <select name='gender' id=".$this->name." class=".$this->name.">
            <option ".$selectedMaleStatus." value='male'>".JText::_('PLG_HIKASHOPPAYMENT_GINGER_AFTERPAY_MESSAGE_SELECT_GENDER_MALE')."</option>
            <option  ".$selectedFemaleStatus. " value='female'>".JText::_('PLG_HIKASHOPPAYMENT_GINGER_AFTERPAY_MESSAGE_SELECT_GENDER_FEMALE')."</option>
        </select><br/>"

        . JText::_('PLG_HIKASHOPPAYMENT_GINGER_AFTERPAY_MESSAGE_ENTER_DOB').'<br>'
        ."<input type='text' name='dob' value='".JFactory::getSession()->get('ginger_dob')."'/>"
        ."<style>.hikabtn_checkout_payment_submit{display:none;}</style>";

        return $html;
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
     * since 1.6.0
     */
    public function getShippingOrderLineType()
    {
        return 'shipping_fee';
    }
}
