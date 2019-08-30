<?php
require_once dirname(__FILE__).'/BodyMessageValidator.php';

class CpBillBodyMessageValidator implements BodyMessageValidator{
	public function validate($mdto){
		// �����籸��
		if($mdto->getParameter(CARRIER) == null || $mdto->getParameter(CARRIER) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("�����籸�� �̼��� �����Դϴ�.");
			}
			throw new ServiceException("VB02","�����籸�� �̼��� �����Դϴ�.");
		}
		
		// �޴�����ȣ
		if($mdto->getParameter(DST_ADDR) == null || $mdto->getParameter(DST_ADDR) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("�޴�����ȣ �̼��� �����Դϴ�.");
			}
			throw new ServiceException("VB05","�޴�����ȣ �̼��� �����Դϴ�.");
		}
		
		// ����������ȣ
		if($mdto->getParameter(IDEN) == null || $mdto->getParameter(IDEN) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("����������ȣ(�ֹι�ȣ,�����ڹ�ȣ) �̼��� �����Դϴ�.");
			}
			throw new ServiceException("VB09","����������ȣ(�ֹι�ȣ,�����ڹ�ȣ) �̼��� �����Դϴ�.");
		}
		
		if($mdto->getParameter(USER_IP) == null || $mdto->getParameter(USER_IP) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("���� IP �̼��� �����Դϴ�.");
			}
			throw new ServiceException("VB10","���� IP �̼��� �����Դϴ�.");
		}
		
	}	
}
?>
