<?php
class CellPhoneItemConfirmBodyValidator{
	public function CellPhoneItemConfirmBodyValidator(){
		
	}
	public function validate($mdto){

		if($mdto->getParameter(SERVER_INFO) == null || $mdto->getParameter(SERVER_INFO) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->writeAppLog("�ŷ�KEY �̼��� �����Դϴ�.");
			}
			throw new ServiceException("VA01","�ŷ�KEY �̼��� �����Դϴ�.");
		}
		
		if($mdto->getParameter(ENCODED_TID) == null || $mdto->getParameter(ENCODED_TID) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
                                $logJournal->writeAppLog("ENCODE ��üTID �̼��� �����Դϴ�.");
			}
			throw new ServiceException("VA10","ENCODE ��üTID �̼��� �����Դϴ�.");
		}


	}
}
?>
