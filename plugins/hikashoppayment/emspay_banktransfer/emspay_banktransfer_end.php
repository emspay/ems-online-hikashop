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

?>

-<div class="hikashop_ginger_end" id="hikashop_ginger_end">
    <span class="hikashop_banktransfer_end_message" id="hikashop_banktransfer_end_message">
    	<?php echo JText::_('ORDER_IS_COMPLETE').'<br/>'.
                       JText::sprintf('PLEASE_TRANSFERT_MONEY', $this->amount).'<br/>'.
                       JText::_('PLG_HIKASHOPPAYMENT_GINGER_BANKTRANSFER_PAYMENT_REFERENCE')." ".$this->payment_reference.'<br/>'.
                       $this->bank_information. '<br/><br/>'.
                       JText::_('PLG_HIKASHOPPAYMENT_GINGER_BANKTRANSFER_BANK_NOTICE').'<br/>'.
                       JText::_('THANK_YOU_FOR_PURCHASE').'<br/>';
        ?>
	</span>
</div>

<?php
$doc = JFactory::getDocument();
$doc->addScriptDeclaration("window.hikashop.ready(function(){document.getElementById('hikashop_ginger_form').submit();});");
JRequest::setVar('noform', 1);
?>