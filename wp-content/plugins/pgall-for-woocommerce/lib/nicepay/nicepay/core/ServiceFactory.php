<?php
require_once dirname(__FILE__).'/IoAdaptorService.php';
class ServiceFactory{
	public function ServiceFactory(){
		
	}
	public function createService($serviceMode){
		$service = new IoAdaptorService();
		return $service;
	}
}
?>
