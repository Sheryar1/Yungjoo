<?php
class CellPhoneItemConfmIdVersionSetter implements MessageIdVersionSetter{
	public function CellPhoneItemConfmIdVersionSetter(){
		
	}
	
	
	public function fillIdAndVersion($webMessageDTO) {
		$webMessageDTO->setParameter(VERSION, "NPG01");
		$webMessageDTO->setParameter(ID, "CPF01");
		
	}
	
}

?>