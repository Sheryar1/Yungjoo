<?php
class PayVbankBulkServiceIdVersionSetter implements MessageIdVersionSetter{
	public function fillIdAndVersion($webMessageDTO) {
		$webMessageDTO->setParameter(VERSION, "NPG01");
		$webMessageDTO->setParameter(ID, "FVB01");
	}
	
}
?>