<?php
class Loop extends Column{
	private $map = array();
	public function Loop(){
		
	}
	public function getMap(){
		return $this->map;
	}
	public function add($column){
		$this->map[$column->getName()] = $column;
	}
}
?>