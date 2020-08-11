<?php

defined('_JEXEC') or die('Restricted access');

class EmspayHelper
{
     /**
      * GINGER_ENDPOINT used for create Ginger client
      */
    const GINGER_ENDPOINT = 'https://api.online.emspay.eu';
     /**
     * Get CA certificate path
     *
     * @return bool|string
     */
     public static function getCaCertPath(){
           return dirname(__FILE__).'/ginger-php/assets/cacert.pem';
     }
     /**
     * @param object $user
     * @param object $order
     * @return array
     * @since v1.0.0
     */
    public static function getCustomerInfo($user, $order)
    {
        $temp_merchant_customer_id = $user->user_id;
        return array_filter([
            'address_type' => 'billing',
            'email_address' => (string) $user->user_email,
            'merchant_customer_id' => (string) $temp_merchant_customer_id,
            'first_name' => (string) $order->cart->billing_address->address_firstname,
            'last_name' => (string) $order->cart->billing_address->address_lastname,
            'phone_numbers' => (array) array_filter([
                $order->cart->billing_address->address_telephone,
                $order->cart->billing_address->address_telephone2,
            ]),
            'country' => (string) $order->cart->billing_address->address_country->zone_code_2,
            'address' => implode("\n",
                array_filter(array(
                        (string) $order->cart->billing_address->address_street,
                        (string) $order->cart->billing_address->address_street2,
                        (string) $order->cart->billing_address->address_post_code
                        ." ".(string) $order->cart->billing_address->address_city,
                    )
                )
            ),
            'postal_code' => (string) $order->cart->billing_address->address_post_code,
            'locale' => (string) self::getLocale(),
            'ip_address' => (string) JFactory::getApplication()->input->server->get('REMOTE_ADDR'),
        ]);
    }

    /**
     * @param string $amount
     * @return int
     * @since v1.0.0
     */
    public static function getAmountInCents($amount)
    {
        return (int) round($amount * 100);
    }

    /**
     * @return mixed
     * @since v1.0.0
     */
    public static function getLocale()
    {
        $lang = JFactory::getLanguage();
        return str_replace('-', '_', $lang->getTag());
    }

    /**
     * return void
     * @since v1.0.0
     */
    public static function clearKlarnaSessionData()
    {
        JFactory::getSession()->clear('emspay_dob');
        JFactory::getSession()->clear('emspay_gender');
    }

    /**
     * @param $ipList
     * @return bool
     * @since v1.0.0
     */
    public static function ipIsEnabled($ipList)
    {
        if (strlen($ipList) > 0) {

            $ipWhitelist = array_map('trim', explode(',', $ipList));

            if (!in_array($_SERVER['REMOTE_ADDR'], $ipWhitelist)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $countriesList
     * @return bool
     * @since v1.0.0
     */
    public static function countriesValidation($countriesList, $userCountry)
    {
        if (empty($countriesList))
        {
            return true;
        } else {
            $expCountriesList = array_map('trim', (explode(",", $countriesList)));
            return in_array($userCountry, $expCountriesList);
        }

    }

    public static function getCountryZone($billing_country)
    {
        $db	= JFactory::getDBO();
        $query ='SELECT zone_code_2 FROM '.hikashop_table('zone').' WHERE zone_namekey LIKE "'.$billing_country .'"';
        $db->setQuery($query);

        return  $db->loadObjectList();
    }
    /**
     * Method obtains plugin information from the manifest file
     *
     * @param string $name
     * @return string
     */
    public static function getPluginVersion($name)
    {
        $xml = JFactory::getXML(JPATH_SITE."/plugins/hikashoppayment/{$name}/{$name}.xml");

        return sprintf('Joomla HikaShop v%s', (string) $xml->version);
    }
}
