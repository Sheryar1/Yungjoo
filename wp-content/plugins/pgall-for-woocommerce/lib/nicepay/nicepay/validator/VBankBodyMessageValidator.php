<?php
require_once dirname(__FILE__).'/BodyMessageValidator.php';
class VBankBodyMessageValidator implements BodyMessageValidator{
	public function VBankBodyMessageValidator(){
		
	}
	public function validate($mdto){
		// ���������Աݸ�����
		if($mdto->getParameter(VBANK_EXPIRE_DATE) == null || $mdto->getParameter(VBANK_EXPIRE_DATE) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("���������Աݸ����� �̼��� �����Դϴ�.");
			}
			throw new ServiceException("V701","���������Աݸ����� �̼��� �����Դϴ�.");
		}
	}	
		
}

?>
