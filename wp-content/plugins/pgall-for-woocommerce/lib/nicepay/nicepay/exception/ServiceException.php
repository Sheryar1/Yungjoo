<?php
require_once dirname(__FILE__).'/../core/ErrorMessagesMap.php';
class ServiceException extends Exception{
	private $errorCode;
	private $errorMessage;
	public function ServiceException($errorCode,$errorMessage){
		$this->errorCode = $errorCode;
		$this->errorMessage = $errorMessage;
	}
	public function getErrorCode() {
		return $this->errorCode;
	}
	public function setErrorCode($errorCode) {
		$this->errorCode = $errorCode;
	}
	public function getErrorMessage() {
		if($this->errorMessage == null || "" == $this->errorMessage){
			return parent::getMessage();
		}else{
			if(ErrorMessagesMap::containsErrorCode($this->errorCode)){
				return ErrorMessagesMap::getErrorMessage($this->errorCode);
			}else{
				return $this->errorMessage;
			}
		}
	}
	public function setMessage($msg){
		$this->errorMessage = $msg;
	}
	
}
?>
