<?php

require_once dirname(__FILE__).'/MessageIdVersionSetter.php';
class PayCardServiceIdVersionSetter implements MessageIdVersionSetter{
	public function PayCardServiceIdVersionSetter(){
		
	}
	public function fillIdAndVersion($webMessageDTO) {
		$webMessageDTO->setParameter(VERSION, "NPG01");
		$webMessageDTO->setParameter(ID, "FCD01");
	}
	
}
?>
