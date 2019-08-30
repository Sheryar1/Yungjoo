<?php
require_once dirname(__FILE__).'/ModelLoader.php';
class DocumentManager{
	private $map;
	private $header = null;
	private function DocumentManager(){
	}
	public static function newInstance($xmlFile){
		
		$docMgr = new DocumentManager();
		try {
			$loader = new ModelLoader();
			$loader->load($xmlFile,$docMgr);
		}catch(Exception $e){
			throw $e;
		}
		return $docMgr;
	}
	public function getMap(){
		return $this->map;
	}
	public function getMessage($id){
		return $this->map[$id];
	}
	public function addAll($subMap){
		foreach($subMap as $key=>$value){
			$this->map[$key] = $value;
		}
		
	}
	public function getHeader(){
		return $this->header;
	}
	public function setHeader($header){
		$this->header = $header;
	}
}
?>
