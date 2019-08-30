<?php
require_once dirname(__FILE__).'/BodyMessageValidator.php';
require_once dirname(__FILE__).'/../exception/ServiceException.php';
require_once dirname(__FILE__).'/../log/LogMode.php';

class BuyDecisionBodyMessageValidator implements BodyMessageValidator{
	
	public function BuyDecisionBodyMessageValidator(){
		
	}
	public function validate($mdto){
		// TID
		if($mdto->getParameter(TID) == null || $mdto->getParameter(TID) == ""){
			
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("�ŷ�TID �̼��� �����Դϴ�.");
			}
			throw new ServiceException("VC01","�ŷ�TID �̼��� �����Դϴ�.");
		}	

		// MID
		if($mdto->getParameter(MID) == null || $mdto->getParameter(MID) == ""){
			
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("MID �̼��� �����Դϴ�.");
			}
			throw new ServiceException("VC04","MID �̼��� �����Դϴ�.");
		}	
		
		// TID
		if($mdto->getParameter(BUYER_AUTH_NO) == null || $mdto->getParameter(BUYER_AUTH_NO) == ""){
			
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("����������ȣ ���Է� �����Դϴ�.");
			}
			throw new ServiceException("VC05","����������ȣ ���Է� �����Դϴ�.");
		}	
		
	}
	
}

?>