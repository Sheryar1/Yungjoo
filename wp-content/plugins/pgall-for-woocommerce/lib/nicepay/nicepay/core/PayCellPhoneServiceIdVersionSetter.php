<?php

require_once dirname(__FILE__).'/MessageIdVersionSetter.php';
class PayCellPhoneServiceIdVersionSetter implements MessageIdVersionSetter{
	public function PayCellPhoneServiceIdVersionSetter(){
		
	}
	public function fillIdAndVersion($webMessageDTO) {
		$webMessageDTO->setParameter(VERSION, "NPG01");
		$webMessageDTO->setParameter(ID, "FCP01");
	}
	
	
}
?>