<?php
class CellPhoneSmsDlverIdVersionSetter implements MessageIdVersionSetter{
	public function CellPhoneSmsDlverIdVersionSetter(){
		
	}
	public function fillIdAndVersion($webMessageDTO) {
		$webMessageDTO->setParameter(VERSION, "NPG01");
		$webMessageDTO->setParameter(ID, "CPE01");
	}
}
?>