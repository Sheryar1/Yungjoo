<?php
require_once dirname(__FILE__).'/../exception/ServiceException.php';
require_once dirname(__FILE__).'/../log/LogMode.php';
class BuyerMessageDataValidator{
	public function BuyerMessageDataValidator(){
		
	}
	public function validate($mdto) {

		if($mdto->getParameter(PAY_METHOD) !="VBANK_BULK"){
			// ������ �̸�
			if($mdto->getParameter(BUYER_NAME)===null || $mdto->getParameter(BUYER_NAME) == ""){
				
				if(LogMode::isAppLogable())	{
					$logJournal = NicePayLogJournal::getInstance();
					$logJournal->errorAppLog("�������̸� �̼��� �����Դϴ�.");
				}
				
				throw new ServiceException("V301","�������̸� �̼��� �����Դϴ�.");	
			}
			
			
			// �����ڿ���ó
			if($mdto->getParameter(BUYER_TEL) == null || $mdto->getParameter(BUYER_TEL) == ""){
				
				if(LogMode::isAppLogable()) {
					$logJournal = NicePayLogJournal::getInstance();
					$logJournal->errorAppLog("�����ڿ���ó �̼��� �����Դϴ�.");
				}
				
				throw new ServiceException("V303","�����ڿ���ó �̼��� �����Դϴ�.");	
			}
			
			// �����ڸ����ּ�
			if($mdto->getParameter(BUYER_EMAIL) == null || $mdto->getParameter(BUYER_EMAIL) == ""){
				if(LogMode::isAppLogable() == true){
					$logJournal = NicePayLogJournal::getInstance();
					$logJournal->errorAppLog("�����ڸ����ּ� �̼��� �����Դϴ�.");
				}
				throw new ServiceException("V304","�����ڸ����ּ� �̼��� �����Դϴ�.");
			}
		}
		
	}
	
	
}
?>
