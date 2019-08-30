<?php
class CellPhoneRegItemBodyValidator{
	public function CellPhoneRegItemBodyValidator(){
		
	}
	public function validate($mdto){
		if($mdto->getParameter(MID) == null || $mdto->getParameter(MID) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("����ID �̼��� �����Դϴ�.");
			}
			throw new ServiceException("V201","����ID �̼��� �����Դϴ�.");
		}
		
		if($mdto->getParameter(GOODS_NAME) == null || $mdto->getParameter(GOODS_NAME) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("��ǰ�� �̼��� �����Դϴ�.");
			}
			throw new ServiceException("V401","��ǰ�� �̼��� �����Դϴ�.");
		}
		
		if($mdto->getParameter(GOODS_AMT) == null || $mdto->getParameter(GOODS_AMT) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("��ǰ�ݾ� �̼��� �����Դϴ�.");
			}
			throw new ServiceException("V402","��ǰ�ݾ� �̼��� �����Դϴ�.");
		}
		
	}
}

?>
