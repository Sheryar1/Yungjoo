<?php
class Document {
	private $header = null;
	private $map = null;
	private $id=null;
	private $version=null;
	private $description=null;
	private $prefixId = null;
	public function Document(){
		
	}
	public function getVersion() {
		return $this->version;
	}
	public function setVersion($version) {
		$this->version = $version;
	}
	public function getDescription() {
		return $this->description;
	}
	public function setDescription($description) {
		$this->description = $description;
	}
	public function getId() {
		return $this->id;
	}
	public function setId($id) {
		$this->id = $id;
	}
	public function getPrefixId() {
		return $this->prefixId;
	}
	public function setPrefixId($prefixId) {
		$this->prefixId = $prefixId;
	}
	public function getHeader() {
		return $this->header;
	}
	public function setHeader($header) {
		$this->header = $header;
	}
	public function getMap(){
		return $this->map;
	}
	public function add($column){
		$this->map[$column->getName()] = $column;
	}
	protected function appendPrefix($message){
		$newMap = array();
		
		foreach($message as $key=>$value){
			$newMap[$key] = $message[$key];
		}
		
		foreach($this->map as $key=>$value){
			$newMap[$key] = $this->map[$key];
		}
		
		$this->map = $newMap;
	}
}
?>
