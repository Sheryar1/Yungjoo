<?php
class PayBankServiceIdVersionSetter implements MessageIdVersionSetter{
	public function PayBankServiceIdVersionSetter(){
		
	}
	public function fillIdAndVersion($webMessageDTO) {
		$webMessageDTO->setParameter(VERSION, "NPG01");
		$webMessageDTO->setParameter(ID, "FBK01");
	}
	
}
?>