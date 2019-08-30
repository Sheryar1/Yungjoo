<?php
class CellPhoneSelfDeliverBodyValidator{
	public function CellPhoneSelfDeliverBodyValidator(){
		
	}
	public function validate($mdto){
		if($mdto->getParameter(SERVER_INFO) == null || $mdto->getParameter(SERVER_INFO) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->writeAppLog("�ŷ�KEY �̼��� �����Դϴ�.");
			}
			throw new ServiceException("VA01","�ŷ�KEY �̼��� �����Դϴ�.");
		}
		
		if($mdto->getParameter(DST_ADDR) == null || $mdto->getParameter(DST_ADDR) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->writeAppLog("�޴�����ȣ �̼��� �����Դϴ�.");
			}
			throw new ServiceException("VA05","�޴�����ȣ �̼��� �����Դϴ�.");
		}
		
		if($mdto->getParameter(IDEN) == null || $mdto->getParameter(IDEN) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->writeAppLog("����������ȣ(�ֹι�ȣ,�����ڹ�ȣ) �̼��� �����Դϴ�.");
			}
			throw new ServiceException("VA09","����������ȣ(�ֹι�ȣ,�����ڹ�ȣ) �̼��� �����Դϴ�.");
		}
		
		if($mdto->getParameter(CARRIER) == null || $mdto->getParameter(CARRIER) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->writeAppLog("�����籸�� �̼��� �����Դϴ�.");
			}
			throw new ServiceException("VA02","�����籸�� �̼��� �����Դϴ�.");
		}

	}
}
?>
