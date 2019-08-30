<?php
abstract class AbstractService{
	public function service($webMessageDTO){
		// ��û �޽��� �����ϱ�
		$requestBytes = $this->createMessage($webMessageDTO);
		
		if(LogMode::isAppLogable()){
			$logJournal = NicePayLogJournal::getInstance();
			$logJournal->writeAppLog("�۽� ".strlen($requestBytes)." Bytes");
		}
		
		// ��û �޽��� ������
		$responseBytes = $this->send($requestBytes);
		
		if(LogMode::isAppLogable()){
			$logJournal = NicePayLogJournal::getInstance();
			$logJournal->writeAppLog("���� ".strlen($responseBytes)." Bytes");
		}
		
		// ���� �� �޽��� �Ľ��ϱ�
		$responseDTO = $this->parseMessage($responseBytes);
		
		if(LogMode::isAppLogable()){
			$logJournal = NicePayLogJournal::getInstance();
			$logJournal->writeAppLog("���� -> [".$responseDTO->getParameter("ResultCode")."][".trim($responseDTO->getParameter("ResultMsg"))."]");
		}
		
		return $responseDTO;
		
	}
	public abstract function createMessage($webMessageDTO);
	public abstract function send($webMessageDTO);
	public abstract function parseMessage($responseBytes);
	
}
?>
