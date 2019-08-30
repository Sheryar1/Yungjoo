<?php
class PayVbankServiceIdVersionSetter implements MessageIdVersionSetter{
	public function fillIdAndVersion($webMessageDTO) {
		$webMessageDTO->setParameter(VERSION, "NPG01");
		$webMessageDTO->setParameter(ID, "FVK01");
	}
	
}
?>