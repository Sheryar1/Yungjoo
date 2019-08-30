<?php
abstract class KeyUtils {

	
	private function KeyUtils(){
		
	}
	
	public static function genTID($mid,$svcCd,$svcPrdtCd){
		$buffer = array();
		
		$nanotime = microtime(true);
		
		
		$nanoString = str_replace(".","",$nanotime,strlen($nanotime));
		
		$nanoStrLength = strlen($nanoString);
		
		$yyyyMMddHHmmss = date("YmdHis");
		
		
		$appendNanoStr = substr($nanoString,$nanoStrLength-9,4);
		
		$buffer = array_merge($buffer,str_split($mid));
		$buffer = array_merge($buffer,str_split($svcCd));
		$buffer = array_merge($buffer,str_split($svcPrdtCd));
		$buffer = array_merge($buffer,str_split(substr($yyyyMMddHHmmss,2,strlen($yyyyMMddHHmmss))));
		$buffer = array_merge($buffer,str_split($appendNanoStr));
		
		
		return implode($buffer);
	}
}
?>
