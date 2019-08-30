<?php
class PayReceiptServiceIdVersionSetter implements MessageIdVersionSetter{
	public function fillIdAndVersion($webMessageDTO) {
		$webMessageDTO->setParameter(VERSION, "NPG01");
		$webMessageDTO->setParameter(ID, "FCH01");
	}
	
}
?>
