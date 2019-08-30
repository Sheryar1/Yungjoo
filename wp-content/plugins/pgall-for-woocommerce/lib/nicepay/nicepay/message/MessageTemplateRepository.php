<?php
require_once dirname(__FILE__).'/DocumentManager.php';
class MessageTemplateRepository {
	private $documentManager;
	private static $instance;
	private function MessageTemplateRepository(){
		
		$this->documentManager = DocumentManager::newInstance("nice_mall.xml");
			
	}
	public static function  getInstance(){
		if(!isset(MessageTemplateRepository::$instance)){
			MessageTemplateRepository::$instance = new MessageTemplateRepository();
		}
		return MessageTemplateRepository::$instance;
	}
	public function getDocumentTemplate($id){
		return $this->documentManager->getMessage($id);
	}
	
	
	
	
	
	
}
?>
