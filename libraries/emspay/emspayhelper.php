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
        ($order->cart->billing_adress->address_telephone1==null)?$phone1='':$phone1=$order->cart->billing_adress->address_telephone1;
        ($order->cart->billing_adress->address_telephone2==null)?$phone2='':$phone2=$order->cart->billing_adress->address_telephone2;
        return array_filter([
            'address_type' => 'billing',
            'email_address' => $user->user_email,
            'merchant_customer_id' => $user->user_id,
            'first_name' => $order->cart->billing_address->address_firstname,
            'last_name' => $order->cart->billing_address->address_lastname,
            'phone_numbers' => array_values([
                $phone1,
                $phone2,
            ]),
            'country' => $order->cart->billing_address->address_country->zone_code_2,
            'address' => implode("\n",
                array_filter(array(
                        $order->cart->billing_address->address_street,
                        $order->cart->billing_address->address_street2,
                        $order->cart->billing_address->address_post_code
                        ." ".$order->cart->billing_address->address_city,
                    )
                )
            ),
            'postal_code' => $order->cart->billing_address->address_post_code,
            'locale' => self::getLocale(),
            'ip_address' => JFactory::getApplication()->input->server->get('REMOTE_ADDR'),
            'birthdate' => JFactory::getSession()->get('emspay_dob'),
            'gender' => JFactory::getSession()->get('emspay_gender'),
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
