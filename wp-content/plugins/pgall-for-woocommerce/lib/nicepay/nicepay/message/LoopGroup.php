<?php
class LoopGroup{
	private $loopSet;
	private $index;
	public function LoopGroup(){
		$this->loopSet = array();
		$this->index = 0;
	}
	public function add($loopData){
		 $this->loopSet[$this->index++]  =  $loopData ;  
	}
	public function get($index){
		return $this->loopSet[$index];
	}
	public function size(){
		return sizeof($this->loopSet);
	}
	
	
	
}
?>