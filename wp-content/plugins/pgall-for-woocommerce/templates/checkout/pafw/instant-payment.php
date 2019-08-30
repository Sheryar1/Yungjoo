<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div class="pafw-instant-payment-wrapper">
    <script>
        jQuery(document).ready(function ($) {
            var $form = $('form.checkout.<?php echo $params['uid']; ?>');

            $('input.pafw-simple-payment', $form).on('click', function () {
                var $terms = $('input[name=terms]', $form);

                if ($terms.length > 0 && !$terms.is(':checked')) {
                    alert('이용 약관에 동의하셔야 합니다.');
                    return;
                }

                var payment_method = $('input[name=payment_method]:checked', $form).val();
                $form.triggerHandler('checkout_place_order_' + payment_method);
            });

            $('input[name=payment_method]', $form).on('click', function () {
                var payment_method = $(this).val();

                $('.payment_box', $form).css('display', 'none');
                $('.payment_box.payment_method_' + payment_method, $form).css('display', 'block');
            });

            $('div.pafw-change-billing-info', $form).on('click', function () {
                $('div.pafw-account', $form).css('display', 'none');
                $('div.pafw-billing-address', $form).css('display', 'block');
            });

            setTimeout(function () {
                $(document).trigger('country_to_state_changing', ['KR', $form]);
                $('input[name=payment_method]:first', $form).trigger('click');
            });
        });
    </script>
    <form class="checkout <?php echo $params['uid']; ?>">

		<?php wc_get_template( 'checkout/pafw/form-' . $params['template'] . '.php', array ( 'params' => $params ), '', PAFW()->template_path() ); ?>

    </form>
</div>