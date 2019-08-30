<?php
class CancelServiceIdVersionSetter implements MessageIdVersionSetter{
	public function CancelServiceIdVersionSetter(){
		
	}
	public function fillIdAndVersion($webMessageDTO) {
		$webMessageDTO->setParameter(VERSION, "NPG01");
		$webMessageDTO->setParameter(ID, "IPGC1");
	}
}
?>