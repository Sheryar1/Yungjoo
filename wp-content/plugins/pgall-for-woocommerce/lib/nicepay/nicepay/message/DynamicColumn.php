<?php
class DynamicColumn extends Column{
	private $column;
	public function DynamicColumn($column){
		$this->column = $column;
	}
	public function getColumn(){
		return $this->column;
	}
}

?>
