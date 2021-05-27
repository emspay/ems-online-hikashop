<?php

defined('_JEXEC') or die('Restricted access');
if (!JFactory::getSession()->get('ginger_notify_url')) die('Restricted access');
JFactory::getLanguage()->load('lib_ginger', JPATH_SITE);

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
<div style="
            height: calc(100vh - 30px);
            display: flex;
            flex-direction: column;
            align-items: center;">
    <div>
        <img src="libraries/ginger/assets/ajax-loader.gif" width="30px" height="30px" style=" margin-top: 10px">
    </div>
    <p id="explode_timer" style="position: relative; font-size: 20px; margin-top: 10px">
        <?php echo JText::_('LIB_GINGER_ATTEMPT_MESSAGE')?> <span id="explode_timer_counter">0 / 6</span>
    </p>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.js"   integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk="   crossorigin="anonymous"></script>
<script type="text/javascript">
    $(document).ready(function pendingProcess() {
        var counter = 0;
        var notify_url = '<?php echo JFactory::getSession()->get('ginger_notify_url');?>';
        <?php JFactory::getSession()->clear('ginger_notify_url');?>
        var loop = setInterval(
            function refresh_pending()
            {
                counter++;
                $('#explode_timer_counter').html(counter + ' / 6');
                $.ajax({
                    type: "POST",
                    url: notify_url,
                    data: {orderStatusOnPending: '1'},
                    dataType: 'json',
                    complete: function (data)
                    {
                        if (data.responseJSON.orderStatus != "processing")
                        {
                            window.location.href = notify_url;
                        }
                    },
                });

                if (counter >= 6)
                {
                    clearInterval(loop);
                    window.location.href = notify_url +  "&expiredAfterPending=1";
                }
            },
            10000
        );
    });
</script>