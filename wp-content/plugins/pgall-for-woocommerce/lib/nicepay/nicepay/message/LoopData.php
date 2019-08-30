<?php
class LoopData {
	private $loopData;
	public function LoopData(){
		$this->loopData = array();
	}
	public function add($parameterSet){
		foreach($parameterSet as $key=>$value){
			$this->$loopData[$key] = $parameterSet[$key];
		}
	}
	public function getParameter($key){
		return $this->loopData[$key];
	}
	public function setParameter($key,$value){
		$this->loopData[$key] = $value;
	}
}
?>