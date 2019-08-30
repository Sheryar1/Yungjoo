<?php
require_once dirname(__FILE__).'/BodyMessageValidator.php';
require_once dirname(__FILE__).'/../exception/ServiceException.php';
require_once dirname(__FILE__).'/../log/LogMode.php';

class DeliveryRegisterBodyMessageValidator implements BodyMessageValidator{
	
	public function DeliveryRegisterBodyMessageValidator(){
		
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

		// INVOICE_NO
		if($mdto->getParameter(INVOICE_NO) == null || $mdto->getParameter(INVOICE_NO) == ""){
			
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("��������ȣ �̼��� �����Դϴ�.");
			}
			throw new ServiceException("VC02","��������ȣ �̼��� �����Դϴ�.");
		}	
		
		// REGISTER_NAME
		if($mdto->getParameter(REGISTER_NAME) == null || $mdto->getParameter(REGISTER_NAME) == ""){
			
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("�����ڸ� �̼��� �����Դϴ�.");
			}
			throw new ServiceException("VC03","�����ڸ� �̼��� �����Դϴ�.");
		}	
		
		if($mdto->getParameter(MID) == null || $mdto->getParameter(MID) == ""){
			
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("MID �̼��� �����Դϴ�.");
			}
			throw new ServiceException("VC04","MID �̼��� �����Դϴ�.");
		}
		
	}
}