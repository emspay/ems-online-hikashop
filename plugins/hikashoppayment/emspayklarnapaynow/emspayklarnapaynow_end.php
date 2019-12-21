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
 * @version     v1.3.0
 * @copyright   COPYRIGHT (C) 2019 GINGER PAYMENTS B.V.
 * @license     The MIT License (MIT)
 * @since       v1.0.0
 **/

?>

<div class="hikashop_ginger_end" id="hikashop_ginger_end">
    <span id="hikashop_ginger_end_message" class="hikashop_ginger_end_message">
        <?php echo JText::sprintf('PLEASE_WAIT_BEFORE_REDIRECTION_TO_X', $this->payment_name); ?><br/>
        <?php echo JText::_('CLICK_ON_BUTTON_IF_NOT_REDIRECTED'); ?>
    </span>
        <span id="hikashop_ginger_end_spinner" class="hikashop_ginger_end_spinner">
        <img src="<?php echo HIKASHOP_IMAGES.'spinner.gif'; ?>"/>
    </span>
    <form id="hikashop_ginger_form" name="hikashop_ginger_form"
          action="<?php echo $this->payment_params->payment_url; ?>" method="POST">
        <div id="hikashop_ginger_end_image" class="hikashop_ginger_end_image">
            <input id="hikashop_ginger_button" class="btn btn-primary" type="submit"
                   value="<?php echo JText::_('PAY_NOW'); ?>" name=""
                   alt="<?php echo JText::_('PAY_NOW'); ?>"/>
        </div>
    </form>
</div>

<?php
$doc = JFactory::getDocument();
$doc->addScriptDeclaration("window.hikashop.ready(function(){document.getElementById('hikashop_ginger_form').submit();});");
JRequest::setVar('noform', 1);
?>