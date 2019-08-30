<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>결제제한 시간(15분) 초과</title>
</head>
<script type="text/javascript">
	<?php if( wp_is_mobile() ) : ?>
	location.href = '<?php echo $redirect_url; ?>';
	<?php else : ?>
	opener.jQuery.fn.pafw_kakaopay_return( '결제제한 시간(15분)이 초과되었습니다.' );
	window.close();
	<?php endif; ?>
</script>
<body>
</body>
</html>
