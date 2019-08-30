<?php

$payment_methods = explode( ',', $params['payment_method'] );

?>

<div class="pafw-instant-payment-methods-wrapper" style="<?php echo 'yes' != $params['show_payment_method'] ? 'display: none;' : ''; ?>">
	<?php
	$gateway_tags       = '';
	$gateway_field_tags = '';

	foreach ( $payment_methods as $payment_method ) {
		$class_name = 'WC_Gateway_' . ucwords( $payment_method, '_' );

		if ( class_exists( $class_name ) ) {
			$gateway = new $class_name();

			ob_start();
			wc_get_template( 'checkout/pafw/payment-method.php', array ( 'gateway' => $gateway, 'uid' => $params['uid'] ), '', PAFW()->template_path() );
			$gateway_tags .= ob_get_clean();

			ob_start();
			wc_get_template( 'checkout/pafw/payment-field.php', array ( 'gateway' => $gateway ), '', PAFW()->template_path() );
			$gateway_field_tags .= ob_get_clean();
		}
	}

	echo $gateway_tags;
	?>
</div>
<div class="pafw-instant-payment-fields-wrapper" style="<?php echo 'yes' != $params['show_payment_field'] ? 'display: none;' : ''; ?>">
	<?php echo $gateway_field_tags; ?>
</div>
