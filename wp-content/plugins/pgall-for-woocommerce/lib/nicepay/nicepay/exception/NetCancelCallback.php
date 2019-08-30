<?php
require_once dirname(__FILE__).'/Callback.php';
require_once dirname(__FILE__).'/../core/ErrorCodes.php';
class NetCancelCallback implements Callback{
	private $webMessageDTO;
	private $serviceException;
	private static $NETCANCEL_TARGET_ERROR_CODES;
	public function NetCancelCallback(){
		if(!isset(NetCancelCallback::$NETCANCEL_TARGET_ERROR_CODES)){
			NetCancelCallback::$NETCANCEL_TARGET_ERROR_CODES = array();
			NetCancelCallback::$NETCANCEL_TARGET_ERROR_CODES[0] = ErrorCodes::S002;
			NetCancelCallback::$NETCANCEL_TARGET_ERROR_CODES[1] = ErrorCodes::X003;
			NetCancelCallback::$NETCANCEL_TARGET_ERROR_CODES[2] = ErrorCodes::T001;
			NetCancelCallback::$NETCANCEL_TARGET_ERROR_CODES[3] = ErrorCodes::T002;
			NetCancelCallback::$NETCANCEL_TARGET_ERROR_CODES[4] = ErrorCodes::T003;
		}
	}
	public function setWebMessageDTO($webMessageDTO) {
		$this->webMessageDTO = $webMessageDTO;
	}
	public function setServiceException($serviceException){
		$this->serviceException = $serviceException;
	}
	public function doCallback(){
		try {
			if($this->isServiceExceptionTargetNetCancel()){ // Ư�� �����ڵ� �߻��� ������
				$requestMsgDTO = new WebMessageDTO();
				// Header (�̿� ������ �ڵ� ����)
				$requestMsgDTO->setParameter(VERSION, "NPG01");  // ����
				$requestMsgDTO->setParameter(ID, "IPGC1");  // ����ID
				$requestMsgDTO->setParameter(TID, $this->webMessageDTO->getParameter(TID));  // �ŷ����̵�
				$requestMsgDTO->setParameter(ENC_FLAG, $this->webMessageDTO->getParameter(ENC_FLAG));  // �Ϻ�ȣȭ����


				// Body
				$requestMsgDTO->setParameter(PAY_METHOD, $this->webMessageDTO->getParameter(PAY_METHOD));  // ���Ҽ���
				$requestMsgDTO->setParameter(CANCEL_AMT, $this->webMessageDTO->getParameter(GOODS_AMT));  // ���ұݾ�
				$requestMsgDTO->setParameter(CANCEL_MSG, "������");  // ���һ���
				$requestMsgDTO->setParameter(MID, $this->webMessageDTO->getParameter(MID));  // ����ID
				$requestMsgDTO->setParameter(CANCEL_PWD, "");  // �����н�����
				$requestMsgDTO->setParameter(MERCHANT_KEY, $this->webMessageDTO->getParameter(MERCHANT_KEY));  // ����KEY
				$requestMsgDTO->setParameter(CANCEL_IP, $this->webMessageDTO->getParameter(MALL_IP));  // ����IP
				$requestMsgDTO->setParameter(NET_CANCEL_CODE, "1");  // �����ұ���


				$msgTemplateCreator = new MessageTemplateCreator();
				$cancelRequestDocument = $msgTemplateCreator->createRequestDocumentTemplate(CANCEL_SERVICE_CODE,"");
				$cancelResponseDocument = $msgTemplateCreator->createResponseDocumentTemplate(CANCEL_SERVICE_CODE,"");
				$serviceFactory = new ServiceFactory();
				
				$adaptorService =  $serviceFactory->createService(CANCEL_SERVICE_CODE);
				$adaptorService->setRequestTemplateDocument($cancelRequestDocument);
				$adaptorService->setResponseTemplateDocument($cancelResponseDocument);
				
				$ioAdaptorTransport = new IoAdaptorTransport();
				
				$adaptorService->setTransport($ioAdaptorTransport);
				
				// ������ ����
				$adaptorService->service($requestMsgDTO);

				if(LogMode::isAppLogable()){
					$logJournal = NicePayLogJournal::getInstance();
					$logJournal->errorAppLog("������ ��û");
				}

			}
			
		} catch (ServiceException $e) {
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				
				$logJournal->errorAppLog("���� ���ҽ� ���ܰ� �߻��Ͽ����ϴ�. :"+$e->getMessage());
			}
		}
		
	}
	private function isServiceExceptionTargetNetCancel(){
		$isServiceExceptionTarget = false;
		if(PAY_SERVICE_CODE == $this->webMessageDTO->getParameter(SERVICE_MODE)){
			$errorCode = $this->serviceException->getErrorCode();
			foreach (NetCancelCallback::$NETCANCEL_TARGET_ERROR_CODES as $key=>$value){
				if($value == $errorCode){
					$isServiceExceptionTarget = true;
					break;
				}
			}
		}
		return $isServiceExceptionTarget;
	}
	
}
?>
