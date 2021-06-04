<?php


class GingerBankConfig
{
    /**
     * BANK_PREFIX using to provide dynamic names
     */
    const BANK_PREFIX = "emspay_";

    /**
     * BANK_ENDPOINT using for create Ginger client
     */
    const BANK_ENDPOINT = 'https://api.online.emspay.eu';


    /**
     * @param $hikashopPaymentName
     * @return string
     * @since 1.6.0
     * key - hikashop payment method name
     * value - ems payment method name
     */
    public static function paymentMapping($hikashopPaymentName): string
    {
        $paymentMap = [
            'afterpay' => 'afterpay',
            'amex' => 'amex',
            'applepay' => 'apple-pay',
            'googlepay' => 'google-pay',
            'bancontact' => 'bancontact',
            'banktransfer' => 'bank-transfer',
            'creditcard' => 'credit-card',
            'ideal' => 'ideal',
            'klarnapaylater' => 'klarna-pay-later',
            'klarnapaynow' => 'klarna-pay-now',
            'payconiq' => 'payconiq',
            'paypal' => 'paypal',
            'tikkiepaymentrequest' => 'tikkie-payment-request',
            'wechat' => 'wechat'
        ];

        return $paymentMap[$hikashopPaymentName];
    }
}