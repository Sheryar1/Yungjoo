<?php
require_once dirname(__FILE__).'/CallbackHandler.php';
class ServiceExceptionCallbackHandler implements CallbackHandler{
	public function doHandle($callbacks){
		foreach($callbacks as $key=>$callback){
			$callback->doCallback();
		}
	}
	
}
?>
