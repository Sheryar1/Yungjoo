<?php
class CommonMessageValidator{
	public function CommonMessageValidator(){
		
	}
	public function validate($mdto){
	
		// ��ȣȭ �÷��� ���� üũ
		if($mdto->getParameter(ENC_FLAG) == null || $mdto->getParameter(ENC_FLAG) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("��ȣȭ �÷��װ� �������� �ʾҽ��ϴ�. N �Ǵ� S�� �����Ǿ��� �մϴ�.");
			}
			throw new ServiceException("V101","��ȣȭ �÷��� �̼��� �����Դϴ�.");
		}
		
		// ���񽺱���üũ
		if($mdto->getParameter(SERVICE_MODE) == null || $mdto->getParameter(SERVICE_MODE) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("���񽺸��尡 �������� �ʾҽ��ϴ�.");
			}
			throw new ServiceException("V102","���񽺸��带 �������� �ʾҽ��ϴ�.");
		}
		
		// ���񽺱��п� ���� ���Ҽ��� üũ
		if(PAY_SERVICE_CODE == $mdto->getParameter(SERVICE_MODE)){
			if($mdto->getParameter(PAY_METHOD) == null || $mdto->getParameter(PAY_METHOD) == ""){
				if(LogMode::isAppLogable()){
					$logJournal = NicePayLogJournal::getInstance();
					$logJournal->errorAppLog("���Ҽ����� �������� �ʾҽ��ϴ�. �������� �������� ���� BANK, CARD, VBANK, CELLPHONE �� �� �ϳ��� �����ؾ� �մϴ�.");
				}
				throw new ServiceException("V103","���Ҽ����� �������� �ʾҽ��ϴ�.");
			}
		}
		
		
	}
}

?>
