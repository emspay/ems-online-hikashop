<?php

defined('_JEXEC') or die('Restricted access');
use \Ginger\Ginger;

class EmspayPlugin extends hikashopPaymentPlugin
{
    var $accepted_currencies = array("EUR");
    var $multiple = true;
    var $pluginConfig = array(
        'api_key' => array('LIB_EMSPAY_API_KEY', 'input'),
        'bundle_cacert' => array('LIB_EMSPAY_BUNDLE_CA_CERT', 'boolean', '1'),
        'notification' => array('ALLOW_NOTIFICATIONS_FROM_X', 'boolean', '1'),
        'notify_url' => array('NOTIFY_URL_DEFINE', 'html', ''),
        'cancel_url' => array('CANCEL_URL_DEFINE', 'html', ''),
        'return_url' => array('RETURN_URL', 'html'),
        'new_status' => array('NEW_STATUS', 'orderstatus','created'),
        'processing_status' => array('PROCESSING_STATUS', 'orderstatus','pending'),
        'see_transactions_status' => array('SEE_TRANSACTIONS_STATUS', 'orderstatus','pending'),
        'completed_status' => array('COMPLETED_STATUS', 'orderstatus','confirmed'),
        'error_status' => array('ERROR_STATUS', 'orderstatus','cancelled'),
        'cancelled_status' => array('CANCELLED_STATUS', 'orderstatus','cancelled'),
        'expired_status' => array('EXPIRED_STATUS', 'orderstatus','cancelled'),
    );
    private $merchant_order_id;

    /**
     * @param object $subject
     * @param array $config
     * @since v1.0.0
     */
    public function __construct(&$subject, $config)
    {
        JImport('emspay.vendor.autoload');
        JImport('emspay.emspayhelper');

        JFactory::getLanguage()->load('lib_emspay', JPATH_SITE);
        JFactory::getLanguage()->load('plg_hikashoppayment_'.$this->name, JPATH_ADMINISTRATOR);


        $this->pluginConfig['notification'][0] = JText::sprintf('ALLOW_NOTIFICATIONS_FROM_X', 'EMS Online');
        $this->pluginConfig['return_url'][2] = HIKASHOP_LIVE."index.php?option=com_hikashop&ctrl=checkout&task=after_end";
        $this->pluginConfig['notify_url'][2] = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment='.$this->name.'&tmpl=component';
        $this->pluginConfig['cancel_url'][2] = HIKASHOP_LIVE."index.php?option=com_hikashop&ctrl=order&task=cancel_order";

        return parent::__construct($subject, $config);
    }

    /**
     * @return void
     * @since v1.0.0
     */
    public function onCheckoutWorkflowLoad()
    {
        if (JRequest::getString('issuer')) {
            JFactory::getSession()->set('emspay_issuer', JRequest::getString('issuer'));
        }
        if (JRequest::getString('dob')) {
            JFactory::getSession()->set('emspay_dob', JRequest::getString('dob'));
        }
        if (JRequest::getString('gender')) {
            JFactory::getSession()->set('emspay_gender', JRequest::getString('gender'));
        }
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
        parent::onAfterOrderConfirm($order, $methods, $method_id);

        if (empty($this->payment_params->api_key)) {
            $this->app->enqueueMessage(JText::_('LIB_EMSPAY_API_KEY_NOT_SET'), 'error');
            $this->app->redirect($this->pluginConfig['cancel_url'][2]);
        } else {
            $emsOrder = $this->createEmspayOrder($order);
            if ($emsOrder['status'] == 'error') {
                $this->app->enqueueMessage(
                    JText::_($emsOrder['transactions'][0]['reason']),
                    'error'
                );
                $this->app->enqueueMessage(JText::_('LIB_EMSPAY_PAYMENT_STATUS_ERROR'), 'error');
                $this->app->redirect($this->pluginConfig['cancel_url'][2].'&order_id='.$order->order_id);
            }
            $this->app->redirect($emsOrder['transactions'][0]['payment_url']);
        }
    }

    /**
     * @param $statuses
     * @since v1.0.0
     */
    public function onPaymentNotification(&$statuses)
    {
        $this->merchant_order_id = JRequest::getInt('merchant_order_id');
        $this->getHikashopOrder();
        $ginger_order_id = JRequest::getString('order_id');
        $cacert_path = EmspayHelper::getCaCertPath();
        $ginger = Ginger::createClient(
            EmspayHelper::GINGER_ENDPOINT,
            $this->payment_params->api_key,
            $this->payment_params->bundle_cacert === '1' ?
                [
                    CURLOPT_CAINFO => $cacert_path
            ] : []
        );

        if (JRequest::getMethod() === 'POST') {
            $webhookData = json_decode(file_get_contents('php://input'), true);
            $this->processWebhook($ginger, $webhookData);
        }

        $emsOrder = $ginger->getOrder($ginger_order_id);
        $return_url = $this->pluginConfig['return_url'][2].'&order_id='.$this->merchant_order_id;
        $cancel_url = $this->pluginConfig['cancel_url'][2].'&order_id='.$this->merchant_order_id;

        switch ($emsOrder['status']) {
            case 'completed' :
                $this->updateOrderStatus($this->payment_params->completed_status, $return_url,false);
                break;
            case 'new' :
                $this->updateOrderStatus($this->payment_params->new_status, $cancel_url);
                break;
            case 'processing' :
                $this->updateOrderStatus($this->payment_params->processing_status, $cancel_url);
                break;
            case 'error' :
                $this->updateOrderStatus($this->payment_params->error_status, $cancel_url);
                break;
            case 'cancelled' :
                $this->updateOrderStatus($this->payment_params->cancelled_status, $cancel_url);
                break;
            case 'expired' :
                $this->updateOrderStatus($this->payment_params->expired_status, $cancel_url);
                break;
            case 'see-transactions' :
                $this->updateOrderStatus($this->payment_params->see_transactions_status, $cancel_url);
                break;
        }
    }

    protected function getHikashopOrder(){
        $hikaOrder = $this->getOrder($this->merchant_order_id);
        $this->loadPaymentParams($hikaOrder);
    }

    protected function updateOrderStatus($new_order_status, $redirect_url, $clean_cart = true){
        $cartClass = hikashop_get('class.cart');
        $app = JFactory::getApplication();
        $customMessage = JText::_('LIB_EMSPAY_PAYMENT_STATUS_'.strtoupper($new_order_status));

        $this->modifyOrder($this->merchant_order_id, $new_order_status, true, true);

        if ($new_order_status != 'cancelled') {
        $app->enqueueMessage($customMessage);
        } else {
        $app->enqueueMessage($customMessage, 'error');
        }

        if (!$clean_cart) {
        $cartClass->cleanCartFromSession(false);
        }

        $app->redirect($redirect_url);
    }

    /**
     * Method processes calls to webhook url
     *
     * @param object $emsApi
     * @param array $webhookData
     * @return void
     * @since v1.0.0
     */
    public function processWebhook($emsApi, array $webhookData)
    {
        if ($webhookData['event'] == 'status_changed') {
            $emsOrder = $emsApi->getOrder($webhookData['order_id']);
            $merchantOrderId = $emsOrder->getMerchantOrderId();
            if ($emsOrder['status'] == 'completed') {
                $this->modifyOrder($merchantOrderId, $this->payment_params->verified_status, true, true);
            } else {
                $this->modifyOrder($merchantOrderId, $this->payment_params->invalid_status, true, true);
            }
        }
        exit;
    }
}
