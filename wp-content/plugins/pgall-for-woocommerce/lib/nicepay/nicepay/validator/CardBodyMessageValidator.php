<?php
require_once dirname(__FILE__).'/BodyMessageValidator.php';
class CardBodyMessageValidator implements BodyMessageValidator{
	public function CardBodyMessageValidator(){

	}
	public function validate($mdto){

    if ($mdto->getParameter(TR_KEY) == null) {
  		// ī������(����, ����, �ؿ�)
  		if($mdto->getParameter(CARD_TYPE) == null || $mdto->getParameter(CARD_TYPE) == ""){
  			if(LogMode::isAppLogable()){
  				$logJournal = NicePayLogJournal::getInstance();
  				$logJournal->errorAppLog("ī������ �̼��� �����Դϴ�.");
  			}
  			throw new ServiceException("V501","ī������ �̼��� �����Դϴ�.");
  		}
  
  
  
  		// ī���ڵ�
  		if($mdto->getParameter(CARD_CODE) == null || $mdto->getParameter(CARD_CODE) == ""){
  			if(LogMode::isAppLogable()){
  				$logJournal = NicePayLogJournal::getInstance();
  				$logJournal->errorAppLog("ī���ڵ� �̼��� �����Դϴ�.");
  			}
  			throw new ServiceException("V503","ī���ڵ� �̼��� �����Դϴ�.");
  		}
  
  		// �����ڿ���
  		if($mdto->getParameter(CARD_INTEREST) == null || $mdto->getParameter(CARD_INTEREST) == ""){
  			if(LogMode::isAppLogable()){
  				$logJournal = NicePayLogJournal::getInstance();
  				$logJournal->errorAppLog("ī�� �����ڿ��� �̼��� �����Դϴ�.");
  			}
  			throw new ServiceException("V505","ī�� �����ڿ��� �̼��� �����Դϴ�.");
  		}
  
  
  		// ī������ �� ü
  		if((PERSONAL_CARD_TYPE != $mdto->getParameter(CARD_TYPE)) &&
  		(BUSINESS_CARD_TYPE != $mdto->getParameter(CARD_TYPE))){
  				
  			if(LogMode::isAppLogable()){
  				$logJournal = NicePayLogJournal::getInstance();
  				$logJournal->errorAppLog("ī������ ���� �����Դϴ�. ����(0), ����(1), �ؿ�(2) ī�����¸�  ���� �����մϴ�. �� ������ :".$mdto->getParameter(CARD_TYPE));
  			}
  				
  			throw new ServiceException("V508","ī������ �������� �ʴ� ���� �����Ͽ����ϴ�.");
  		}
  
  
  
  		// KeyIn������ ����
  		if(CARD_AUTH_TYPE_KEYIN == $mdto->getParameter(CARD_AUTH_FLAG)){
  			// ī����ȣ+��ȿ�Ⱓ
  			if( (CARD_KEYIN_CL01 == $mdto->getParameter(CARD_KEYIN_CL)) || (CARD_KEYIN_CL11 == $mdto->getParameter(Constants.CARD_KEYIN_CL))) {
  				// ��ȿ�Ⱓ
  				if($mdto->getParameter(CARD_EXPIRE) == null || $mdto->getParameter(CARD_EXPIRE) == ""){
  					if(LogMode::isAppLogable()){
  						$logJournal = NicePayLogJournal::getInstance();
  						$logJournal->errorAppLog("��ȿ�Ⱓ �̼��� �����Դϴ�.");
  					}
  					throw new ServiceException("V510","��ȿ�Ⱓ �̼��� �����Դϴ�.");
  				}
  
  				//��ȿ�Ⱓ �ڸ��� üũ
  				$expireYYMM = $mdto->getParameter(CARD_EXPIRE);
  				if(strlen($expireYYMM) != 4){
  					if(LogMode::isAppLogable()){
  						$logJournal = NicePayLogJournal::getInstance();
  						$logJournal->errorAppLog("��ȿ�Ⱓ��  4�ڸ����� �մϴ�.");
  					}
  					throw new ServiceException("V511","��ȿ�Ⱓ �������� �ʴ� ���� �����Ͽ����ϴ�.");
  				}
  				// ��ȿ�Ⱓ ���� üũ
  				$expireMonth =  (int) substr($expireYYMM,2,2);
  				if($expireMonth <0 || $expireMonth > 12){
  					if(LogMode::isAppLogable()){
  						$logJournal = NicePayLogJournal::getInstance();
  						$logJournal->errorAppLog("��ȿ�Ⱓ�� �� ���°� �߸� �����Ǿ����ϴ�. 1������ 12������ �Է� �����մϴ�. �� �Է°� :".$expireMonth);
  					}
  					throw new ServiceException("V512","��ȿ�Ⱓ�� �� ���°� �߸� �����Ǿ����ϴ�.");
  				}
  			}
  				
  			if(CARD_KEYIN_CL11 == $mdto->getParameter(CARD_KEYIN_CL)){
  				// ���й�ȣ üũ
  				if($mdto->getParameter(CARD_PWD) == null || $mdto->getParameter(CARD_PWD) == ""){
  					if(LogMode::isAppLogable()){
  						$logJournal = NicePayLogJournal::getInstance();
  						$logJournal->errorAppLog("ī�� ���й�ȣ�� �Էµ��� �ʾҽ��ϴ�. ���������� ���й�ȣ�� �ʿ��մϴ�.");
  					}
  					throw new ServiceException("V513","ī�� ���й�ȣ ���Է� �����Դϴ�.");
  				}
  			}
  		}
		} else {
		
		
		
		}
		
	}

}
		?>
