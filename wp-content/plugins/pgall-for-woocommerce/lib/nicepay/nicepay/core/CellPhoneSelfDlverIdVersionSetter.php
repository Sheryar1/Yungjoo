<?php
class CellPhoneSelfDlverIdVersionSetter implements MessageIdVersionSetter{
	public function CellPhoneSelfDlverIdVersionSetter(){
		
	}
	public function fillIdAndVersion($webMessageDTO) {
		$webMessageDTO->setParameter(VERSION, "NPG01");
		$webMessageDTO->setParameter(ID, "CPD01");
	}
}

?>