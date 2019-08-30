<?php
class CellPhoneSmsDeliverBodyValidator{
	public function CellPhoneSmsDeliverBodyValidator(){
		
	}
	public function validate($mdto){
		if($mdto->getParameter(SERVER_INFO) == null || $mdto->getParameter(SERVER_INFO) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->writeAppLog("�ŷ�KEY �̼��� �����Դϴ�.");
			}
			throw new ServiceException("VA01","�ŷ�KEY �̼��� �����Դϴ�.");
		}
		
		if($mdto->getParameter(SMS_OTP) == null || $mdto->getParameter(SMS_OTP) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->writeAppLog("SMS���ι�ȣ �̼��� �����Դϴ�.");
			}
			throw new ServiceException("VA03","SMS���ι�ȣ �̼��� �����Դϴ�.");
		}
	}
	
	
}
?>
