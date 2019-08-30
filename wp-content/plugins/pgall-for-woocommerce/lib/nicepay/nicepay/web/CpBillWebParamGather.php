<?php
require_once dirname(__FILE__).'/WebParamGather.php';

class CpBillWebParamGather implements WebParamGather{
	public function gather($request){
		$webParam = new WebMessageDTO();
		
		
		$carrier = $request["Carrier"];
		$webParam->setParameter(CARRIER,$carrier);
		
		
		
		$dstAddr = $request["DstAddr"];
		$webParam->setParameter(DST_ADDR,$dstAddr);
		
		
		
		$iden = $request["Iden"];
		$webParam->setParameter(IDEN,$iden);
		
		
		return $webParam;
	}
}
?>
