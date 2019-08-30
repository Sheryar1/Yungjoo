<?php
class LogMode{
	
	private static $EVENT_LOG_MODE = false;
	
	private static $APP_LOG_MODE = false;
	
	public static function isEventLogable(){
		return self::$EVENT_LOG_MODE;
	}
	public static function isAppLogable(){
		return self::$APP_LOG_MODE;
	}
	public static function enableEventLogMode(){
		self::$EVENT_LOG_MODE = true;
	}
	public static function enableAppLogMode(){
		self::$APP_LOG_MODE = true;
	}
	
}

?>
