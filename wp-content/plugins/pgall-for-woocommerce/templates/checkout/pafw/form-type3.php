<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<?php wc_get_template( 'checkout/pafw/account.php', array ( 'params' => $params ), '', PAFW()->template_path() ); ?>

<?php wc_get_template( 'checkout/pafw/payment-methods.php', array ( 'params' => $params ), '', PAFW()->template_path() ); ?>

<?php wc_get_template( 'checkout/pafw/terms.php', array ( 'params' => $params ), '', PAFW()->template_path() ); ?>

<?php wc_get_template( 'checkout/pafw/payment-fields.php', array ( 'params' => $params ), '', PAFW()->template_path() ); ?>

<?php wc_get_template( 'checkout/pafw/payment-button.php', array ( 'params' => $params ), '', PAFW()->template_path() ); ?>



