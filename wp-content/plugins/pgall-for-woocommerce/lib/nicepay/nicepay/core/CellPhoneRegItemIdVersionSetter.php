<?php
class CellPhoneRegItemIdVersionSetter implements MessageIdVersionSetter{
	public function CellPhoneRegItemIdVersionSetter(){
		
	}
	public function fillIdAndVersion($webMessageDTO) {
		$webMessageDTO->setParameter(VERSION, "NPG01");
		$webMessageDTO->setParameter(ID, "CPR01");
	}
}