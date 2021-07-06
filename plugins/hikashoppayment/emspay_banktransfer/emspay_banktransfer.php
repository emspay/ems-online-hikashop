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

class plgHikashoppaymentEmspay_BankTransfer extends GingerPluginGateway implements GingerIdentificationPay
{
    var $name = GingerBankConfig::BANK_PREFIX.'banktransfer';

    public function identificationProcess($gingerOrder)
    {
        $paymentReference = current($gingerOrder['transactions'])['payment_method_details']['reference'];
        $gingerOrderIBAN = current($gingerOrder['transactions'])['payment_method_details']['creditor_iban'];
        $gingerOrderBIC = current($gingerOrder['transactions'])['payment_method_details']['creditor_bic'];
        $gingerOrderHolderName = current($gingerOrder['transactions'])['payment_method_details']['creditor_account_holder_name'];
        $gingerOrderHolderCity = current($gingerOrder['transactions'])['payment_method_details']['creditor_account_holder_city'];

        $bankInformation = "IBAN: ".$gingerOrderIBAN." <br/>
                                BIC: ".$gingerOrderBIC." <br/>
                                Account holder: ".$gingerOrderHolderName." <br/>
                                City: ".$gingerOrderHolderCity;

        if ($this->order->order_status != $this->payment_params->new_status)
        {
            $this->modifyOrder($this->order->order_id, $this->payment_params->new_status, true, false);//order_status
        }

        $currencyClass = hikashop_get('class.currency');
        $this->amount = $currencyClass->format($this->order->order_full_price, $this->order->order_currency_id);
        $this->order_number = $this->order->order_number;
        $this->payment_reference = $paymentReference;
        $this->bank_information = $bankInformation;
        $this->removeCart = true;

        return $this->showPage('end');
    }

}
