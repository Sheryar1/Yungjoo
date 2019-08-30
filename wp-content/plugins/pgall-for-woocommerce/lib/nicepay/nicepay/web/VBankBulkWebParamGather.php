<?php
require_once dirname(__FILE__).'/WebParamGather.php';
class VBankBulkWebParamGather implements WebParamGather{
	public function VBankBulkWebParamGather(){
		
	}
	public function gather($request) {
		$webParam = new WebMessageDTO();
		
		
		$webParam->setParameter(VBANK_CODE,$request["VbankBankCode"]);


		$webParam->setParameter("VbankNum",$request["VbankNum"]);

		$webParam->setParameter("VbankNum",$request["VbankNum"]);

		$webParam->setParameter(ACCT_NAME,$request["VBankAccountName"]);

		$webParam->setParameter(EXP_DATE,$request["VbankExpDate"]);

		
		$webParam->setParameter("VbankExpTime",$request["VbankExpTime"]);
		
		$transType = $request["TransType"] == null ? "0" : $request["TransType"];
		$webParam->setParameter(TRANS_TYPE,$transType);
		
		$trKey = $request["TrKey"] == null ? "0" : $request["TrKey"];
		$webParam->setParameter(TR_KEY,$trKey);
		
		return $webParam;
	}
	
}
?>
