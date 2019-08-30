<?php
require_once dirname(__FILE__).'/AbstractService.php';
require_once dirname(__FILE__).'/HeaderValueSetter.php';
require_once dirname(__FILE__).'/../util/IOUtils.php';
require_once dirname(__FILE__).'/../crypt/BlockEncrypt.php';
class IoAdaptorService extends AbstractService{
	private $requestTemplateDocument;
	private $responseTemplateDocument;
	private $transport;
	private $secureMessageProcessor;
	public function IoAdaptorService(){
		
	}
	public function registerSecureMessageProcessor($secureMessageProcessor){
		$this->secureMessageProcessor = $secureMessageProcessor;
	}
	public function setRequestTemplateDocument($requestTemplateDocument) {
		$this->requestTemplateDocument = $requestTemplateDocument;
	}
	public function setResponseTemplateDocument($responseTemplateDocument) {
		$this->responseTemplateDocument = $responseTemplateDocument;
	}
	public function setTransport($transport) {
		$this->transport = $transport;
	}

	public function createMessage($mdto){
		
		if($this->requestTemplateDocument == null)
			throw new ServiceException("S001","��û���� ���ø��� �������� �ʽ��ϴ�.");
		
		
		// ���� �޽��� Value ����
		$headerValueSetter = new HeaderValueSetter();
		$headerValueSetter->fillValue($mdto);
		
		// secureMessageProcessor not null -> ���������̸� ��ȣȭ ������ ����
		if($this->secureMessageProcessor!=null){
			$this->secureMessageProcessor->doProcess($mdto);
		}
		
		
		// body�κ� ������ ���� , header�κ� ������  ����
		$requestBytes = array();

		if("S" == $mdto->getParameter(ENC_FLAG)){
			$bodyBuffer = $this->makeBodyBytesMessage($this->requestTemplateDocument,$mdto);
			$headerBuffer = $this->makeHeaderBytesMessage($this->requestTemplateDocument,$mdto);
		
			$headerBuffer = str_split(implode($headerBuffer));	
	
			$nonCryptBuffer = array_slice($headerBuffer,0,172);

			
			$mergeBuffer = array();
			
			$mergeBuffer = array_merge($mergeBuffer,array_slice($headerBuffer,172));
			
			$mergeBuffer = array_merge($mergeBuffer,$bodyBuffer);
			
			
			$encryptData = BlockEncrypt::getInstance()->encrypt(implode($mergeBuffer));
			
			
			$encryptData = base64_encode($encryptData);
			

			$dataLength = strlen($encryptData)+sizeof($nonCryptBuffer);
			$dataStringLength = sizeof(str_split((string)$dataLength));
			$length = "";
			for($i = 0 ; $i < 6 - $dataStringLength ; $i++){
				$length=$length."0";
			}
					
				
			$length=$length.$dataLength;
			// ���� ������ �����ϱ�
			$sendBytes = array();
			$sendBytes = array_merge($sendBytes,array_slice($nonCryptBuffer,0,24));
			$sendBytes = array_merge($sendBytes,str_split($length));
			$sendBytes = array_merge($sendBytes,array_slice($nonCryptBuffer,30));
			
			$sendBytes = array_merge($sendBytes,str_split($encryptData));
			
		        $requestBytes = array_merge($requestBytes,$sendBytes);	
			
		}else{
			$bodyBuffer = $this->makeBodyBytesMessage($this->requestTemplateDocument,$mdto);
			
		
			// ��ü ���� ����
			$mdto->setParameter(LENGTH, strlen(implode($bodyBuffer)) + $this->requestTemplateDocument->getHeader()->getLength());
			$headerBuffer = $this->makeHeaderBytesMessage($this->requestTemplateDocument,$mdto);
			
			$requestBytes = array_merge($requestBytes,$headerBuffer);
			$requestBytes = array_merge($requestBytes,$bodyBuffer);
		}
		
		
			
		return implode($requestBytes);
	}
	
	public function send($msg) {
		$responseBytes = $this->transport->doTrx($msg);
		return $responseBytes;
	}
	
	public function parseMessage($msg) {
		if($this->responseTemplateDocument == null)
			throw new ServiceException("S002","�������� ���ø��� �������� �ʽ��ϴ�.");
		
		
		try {
			$responseMessageDTO = new WebMessageDTO();
		
			$recvBytesArray = str_split($msg);			

	
			// Header ó��
			$recvBytesArray = $this->parseHeaderMessage($recvBytesArray, $responseMessageDTO);
			
			// Body ó��
			$recvBytesArray = $this->parseBodyMessage($recvBytesArray, $responseMessageDTO);
			
			return $responseMessageDTO;
		} catch (Exception $e) {
			throw new ServiceException("T003","���� ������ �Ľ� �� ���ܰ� �߻��Ͽ����ϴ�. : "+$e->getMessage());
		} 
	}
	private function parseBodyMessage($buffer,
			$responseMessageDTO) {
				$map = $this->responseTemplateDocument->getMap();
				foreach($map as $key=>$value){
					$column = $value;
					$buffer = IOUtils::readFromStream($buffer,$column,$responseMessageDTO);
				}
				
	}
	private function parseHeaderMessage($buffer,
			$responseMessageDTO)  {
		$header = $this->responseTemplateDocument->getHeader();
		$map = $header->getMap();
		foreach($map as $key=>$value){
			$column = $value;
			$buffer = IOUtils::readFromStream($buffer,$column,$responseMessageDTO);
					
		}	
		return $buffer;
	}
	private function makeHeaderBytesMessage($requestDocumentTemplate,$mdto){
		$headerBuffer = array();
		try {
			
			$header = $requestDocumentTemplate->getHeader();
			$map = $header->getMap();
			foreach($map as $key=>$value){
				$column = $value;
				
				$headerBuffer = IOUtils::writeToStream($headerBuffer,$column,$mdto);
			}
			
		} catch (Exception $e) {
			throw new ServiceException("T004","��û ������ ������ ���� �� ������ �߻��Ͽ����ϴ�. : "+e.getMessage());
		} 
		return $headerBuffer;
		
	}
	private function makeBodyBytesMessage($requestDocumentTemplate,$mdto){
		$bodyBuffer = array();
		$map = $requestDocumentTemplate->getMap();

		$logJournal = NicePayLogJournal::getInstance();
		//$logJournal->writeAppLog("$requestDocumentTemplate  [".$requestDocumentTemplate."]");

		if(isset($map)){
			try {
				foreach($map as $key=>$value){
					$column = $value;
					$bodyBuffer = IOUtils::writeToStream($bodyBuffer,$column,$mdto);
				}
			} catch (Exception $e) {
				throw new ServiceException("T005","��û ������ �ٵ��� ���� �� ������ �߻��Ͽ����ϴ�. : "+$e->getMessage());
			}
		}
		
		return $bodyBuffer;
	}
}

?>
