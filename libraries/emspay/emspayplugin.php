<?php

defined('_JEXEC') or die('Restricted access');

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
        'invalid_status' => array('INVALID_STATUS', 'orderstatus'),
        'verified_status' => array('VERIFIED_STATUS', 'orderstatus')
    );

    /**
     * @param object $subject
     * @param array $config
     * @since v1.0.0
     */
    public function __construct(&$subject, $config)
    {
        JImport('emspay.ems-php.vendor.autoload');
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
    public function onAfterCheckoutStep()
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

            if ($emsOrder->status()->isError()) {
                $this->app->enqueueMessage(
                    JText::_($emsOrder->transactions()->current()->reason()->toString()),
                    'error'
                );
                $this->app->enqueueMessage(JText::_('LIB_EMSPAY_PAYMENT_STATUS_ERROR'), 'error');
                $this->app->redirect($this->pluginConfig['cancel_url'][2].'&order_id='.$order->order_id);
            }

            $this->payment_params->payment_url = $emsOrder->firstTransactionPaymentUrl();

            return $this->showPage('end');
        }
    }

    /**
     * @param $statuses
     * @since v1.0.0
     */
    public function onPaymentNotification(&$statuses)
    {
        $app = JFactory::getApplication();
        $merchant_order_id = JRequest::getInt('merchant_order_id');
        $ginger_order_id = JRequest::getString('order_id');
        $hikaOrder = $this->getOrder($merchant_order_id);
        $this->loadPaymentParams($hikaOrder);
        $cartClass = hikashop_get('class.cart');

        $ginger = \GingerPayments\Payment\Ginger::createClient(
            $this->payment_params->api_key
        );

        if ($this->payment_params->bundle_cacert === '1') {
            $ginger->useBundledCA();
        }

        if (JRequest::getMethod() === 'POST') {
            $webhookData = json_decode(file_get_contents('php://input'), true);
            $this->processWebhook($ginger, $webhookData);
        }

        $emsOrder = $ginger->getOrder($ginger_order_id);
        $return_url = $this->pluginConfig['return_url'][2].'&order_id='.$merchant_order_id;
        $cancel_url = $this->pluginConfig['cancel_url'][2].'&order_id='.$merchant_order_id;

        if ($emsOrder->status()->isCompleted()
            || $emsOrder->status()->isProcessing()
            || $emsOrder->status()->isNew()
        ) {
            $this->modifyOrder($merchant_order_id, $this->payment_params->verified_status, true, true);
            $app->enqueueMessage(JText::_('LIB_EMSPAY_ORDER_IS_PLACED'));
            $cartClass->cleanCartFromSession(false);
            $app->redirect($return_url);
        } else {
            $this->modifyOrder($merchant_order_id, $this->payment_params->invalid_status, true, true);
            $app->enqueueMessage(JText::_('LIB_EMSPAY_PAYMENT_STATUS_ERROR'), 'error');
            $app->redirect($cancel_url);
        }
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
            if ($emsOrder->status()->isCompleted()) {
                $this->modifyOrder($merchantOrderId, $this->payment_params->verified_status, true, true);
            } else {
                $this->modifyOrder($merchantOrderId, $this->payment_params->invalid_status, true, true);
            }
        }
        exit;
    }
}
