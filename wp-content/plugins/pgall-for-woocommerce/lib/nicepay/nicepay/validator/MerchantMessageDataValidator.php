<?php
class MerchantMessageDataValidator{
	public function MerchantMessageDataValidator(){
		
	}
	public function validate($mdto){
		// MID
		if($mdto->getParameter(MID) == null || $mdto->getParameter(MID) == ""){
			if(LogMode::isAppLogable()) {
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("MID �̼��� �����Դϴ�.");
			}
			throw new ServiceException("V201","����ID �̼��� �����Դϴ�.");
		}
		
		// LicenseKey 
		if($mdto->getParameter(MERCHANT_KEY) == null || $mdto->getParameter(MERCHANT_KEY) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("LicenseKey �̼��� �����Դϴ�.");
			}
			throw new ServiceException("V202","LicenseKey �̼��� �����Դϴ�.");
		}
		
		// MallIP
		
	}
}
?>
