<?php
class Column {
	const MODE_A = 1;
	const MODE_N = 2;
	const MODE_AN = 3;
	const MODE_AH = 4;
	private $name;
	private $description;
	private $mode;
	private $size;
	private $encrypt;
	private $required;
    public function getDescription() {
		return $this->description;
	}
	public function setDescription($description) {
		$this->description = $description;
	}
	public function isEncrypt() {
		return $this->encrypt;
	}
	public function setEncrypt($encrypt) {
		$this->encrypt = $encrypt;
	}
	public function getMode() {
		return $this->mode;
	}
	public function setMode($mode) {
		$this->mode = $mode;
	}
	public function getName() {
		
		return $this->name;
	}
	public function setName($name) {
		$this->name = $name;
	}
	public function isRequired() {
		return $this->required;
	}
	public function setRequired($required) {
		$this->required = $required;
	}
	public function getSize() {
		return $this->size;
	}
	public function setSize($size) {
		$this->size = $size;
	}
	
	
	
}
?>
