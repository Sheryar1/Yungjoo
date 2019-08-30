<?php
class GoodsMessageDataValidator{
	public function GoodsMessageDataValidator(){
		
	}
	public function validate($mdto){
		
		// ��ǰ����
		if($mdto->getParameter(GOODS_CNT) == null || $mdto->getParameter(GOODS_CNT) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("��ǰ���� �̼��� �����Դϴ�.");
			}
			throw new ServiceException("V104","��ǰ���� �̼��� �����Դϴ�.");
		}

		if($mdto->getParameter(PAY_METHOD) !="VBANK_BULK"){
				
			// ��ǰ��
			if($mdto->getParameter(GOODS_NAME) == null || $mdto->getParameter(GOODS_NAME) == ""){
				if(LogMode::isAppLogable()){
					$logJournal = NicePayLogJournal::getInstance();
					$logJournal->errorAppLog("��ǰ�� �̼��� �����Դϴ�.");
				}
				throw new ServiceException("V401","��ǰ�� �̼��� �����Դϴ�.");
			}
			
			// �ݾ�
			if($mdto->getParameter(GOODS_AMT) == null || $mdto->getParameter(GOODS_AMT) == ""){
				if(LogMode::isAppLogable()){
					$logJournal = NicePayLogJournal::getInstance();
					$logJournal->errorAppLog("��ǰ�ݾ� �̼��� �����Դϴ�.");
				}
				throw new ServiceException("V402","��ǰ�ݾ� �̼��� �����Դϴ�.");
			}
		}
		
		// ��ȭ���� 
		if($mdto->getParameter(CURRENCY) == null || $mdto->getParameter(CURRENCY) == ""){
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->errorAppLog("��ȭ���� �̼��� �����Դϴ�.");
			}
			throw new ServiceException("V203","��ȭ���� �̼��� �����Դϴ�.");
		}
	}
}

?>
