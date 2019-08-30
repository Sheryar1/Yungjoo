<?php
class PayServiceIdVersionSetter{
	public function PayServiceIdVersionSetter(){
		
	}
	public function fillIdAndVersion($webMessageDTO) {
		$webMessageDTO->setParameter(VERSION, "NPG01");
		$webMessageDTO->setParameter(ID, "MALL1");
		
	}
}

?>