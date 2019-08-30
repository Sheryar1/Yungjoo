<?php
class CancelBodyMessageValidator{
	public function CancelBodyMessageValidator(){
		
	}
	public function validate($mdto) {
		// ���ұݾ�
		if($mdto->getParameter(CANCEL_AMT) == null || $mdto->getParameter(CANCEL_AMT) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("���ұݾ� �̼��� �����Դϴ�.");
			}
			
			throw new ServiceException("V801","���ұݾ� �̼��� �����Դϴ�.");
		}
		
		// ���һ���
		if($mdto->getParameter(CANCEL_MSG) == null || $mdto->getParameter(CANCEL_MSG) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("���һ��� �̼��� �����Դϴ�.");
			}
			throw new ServiceException("V802","���һ��� �̼��� �����Դϴ�.");
		}
		
		// ����ID
		if($mdto->getParameter(MID) == null || $mdto->getParameter(MID) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("����ID �̼��� �����Դϴ�.");
			}
			throw new ServiceException("V201","����ID �̼��� �����Դϴ�.");
		}
		

		if($mdto->getParameter(PAY_METHOD) == null || $mdto->getParameter(PAY_METHOD) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("���Ҽ����� �������� �ʾҽ��ϴ�. ���� �������� ���� BANK, CARD, CELLPHONE �� �� �ϳ��� �����ؾ� �մϴ�.");
			}
			throw new ServiceException("V103","���Ҽ����� �������� �ʾҽ��ϴ�.");
		}
	}
	
}
?>
