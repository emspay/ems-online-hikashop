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

class plgHikashoppaymentEmspay_Klarnapaylater extends GingerPluginGateway implements
    GingerOrderLines,
    GingerIPFiltering,
    GingerOrderCapturing,
    GingerAdditionalTestingEnvironment
{
    var $name = GingerBankConfig::BANK_PREFIX.'klarnapaylater';

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