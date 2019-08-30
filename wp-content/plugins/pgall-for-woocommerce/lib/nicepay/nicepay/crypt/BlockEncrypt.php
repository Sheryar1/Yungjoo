<?php

require_once dirname(__FILE__).'/../../secure/Crypt/TripleDES.php';
class BlockEncrypt{
	private static $instance;
	private $keyData;
	private function BlockEncrypt(){
		$this->initailizePrivateKey();
	}
	private function initailizePrivateKey(){
		$filePath = dirname(__FILE__).'/nicepay.key';
		
		$fp = fopen($filePath,'r');
		
		$this->keyData = fread($fp,24);
		fclose($fp);	
	}
	public static function getInstance(){
		if(!isset(BlockEncrypt::$instance)){
			BlockEncrypt::$instance = new BlockEncrypt();
		}
		return BlockEncrypt::$instance;
	} 
	public function encrypt($plainText){
		$aes = new Crypt_TripleDES(CRYPT_DES_MODE_ECB);	
		$aes->setKey($this->keyData);
		
		return $aes->encrypt($plainText);
	}
	public function decrypt($cipher){
                $aes = new Crypt_AES(CRYPT_AES_MODE_ECB);

                $aes->setKey($this->keyData);

                return $aes->decrypt($cipher);
        }

	
	
	
	
}

?>
