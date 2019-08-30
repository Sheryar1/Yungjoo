<?php
class SecureMessageCreator {
	public function SecureMessageCreator(){
		
	}
	public function createMessage($msg){
		$hashString = md5($msg);
		return base64_encode($hashString);
	}
	
	
}
?>