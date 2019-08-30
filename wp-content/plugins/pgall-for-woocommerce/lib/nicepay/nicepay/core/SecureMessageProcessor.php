<?php
require_once dirname(__FILE__).'/SecureValueSetter.php';
class SecureMessageProcessor{
	public function SecureMessageProcessor(){
		
	}
	public function doProcess($messageDTO){
		$secureValueSetter = new SecureValueSetter();
		$secureValueSetter->fillValue($messageDTO);
	}
	
}

?>
