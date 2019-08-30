<?php
require_once dirname(__FILE__).'/../core/Constants.php';
require_once dirname(__FILE__).'/NiceLog.php';
class NicePayLogJournal{
	private static $instance;
	private $logPath;
	private $eventLogger;

	private $appLogger;
	private function NicePayLogJournal(){
		
	}
	public static function getInstance(){
		if(!isset(NicePayLogJournal::$instance)){
			NicePayLogJournal::$instance = new NicePayLogJournal();
		}
		return NicePayLogJournal::$instance;
	}
	public function setLogDirectoryPath($logPath){
		$this->logPath = $logPath;
	}
	public function configureNicePayLog4PHP(){
		if(!isset($this->appLogger) || !isset($this->eventLogger)){
			try {
				
				
				$this->appLogger = new NICELog("DEBUG","application");
				if($this->appLogger->StartLog($this->logPath)){
					
					
				}else{
					echo "�α� ���� ���� ����";
				}

				
				
			} catch (Exception $e) {
				echo "Exception  : Log Configuration Error";
			}
			
		}
		
	}
	
	

	public function writeAppLog($string){
		$this->appLogger->WriteLog($string);
	}

	public function errorAppLog($string){
		$this->appLogger->WriteLog($string);
	}

	public function warnAppLog($string){
		$this->appLogger->WriteLog($string);
	}

	public function closeAppLog($string){
		$this->appLogger->CloseNiceLog($string);
	}
	
	
}
?>
