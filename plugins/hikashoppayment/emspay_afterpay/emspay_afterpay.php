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
 * @version     v
 * @copyright   COPYRIGHT (C) 2019 GINGER PAYMENTS B.V.
 * @license     The MIT License (MIT)
 * @since       v1.0.0
 **/

JImport('ginger.GingerPluginGateway');

class plgHikashoppaymentEmspay_AfterPay extends GingerPluginGateway implements
    GingerOrderLines,
    GingerCustomerPersonalInformation,
    GingerIPFiltering,
    GingerCountryValidation,
    GingerOrderCapturing,
    GingerAdditionalTestingEnvironment
{
    var $name = GingerBankConfig::BANK_PREFIX.'afterpay';

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
