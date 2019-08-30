<?php
require_once dirname(__FILE__).'/WebParamGather.php';
class CardWebParamGather implements WebParamGather{
	public function CardWebParamGather(){
		
	}
	public function gather($request){
		$webParam = new WebMessageDTO();
		
		//card code
		$cardCode = isset($request["FormBankCd"]) ? $request["FormBankCd"] : "";
		$webParam->setParameter(CARD_CODE,$cardCode);
		
		//card pwd
		$cardPwd = isset($request["CardPwd"]) ? $request["CardPwd"] : "";
		$webParam->setParameter(CARD_PWD, $cardPwd);
		
		// card no
		$cardNo = isset($request["CardNo"]) ? $request["CardNo"] : "";
		$webParam->setParameter(CARD_NO, $cardNo);
		
		// cardexpire
		$cardExpire =isset($request["CardExpire"]) ? $request["CardExpire"] : "";
		$webParam->setParameter(CARD_EXPIRE,$cardExpire);
		
		$cardPoint = isset($request["CardPoint"]) ? $request["CardPoint"] : "";
		$webParam->setParameter(CARD_POINT,$cardPoint);
		
		// card interest
		$cardInterest = isset($request["CardInterest"]) ? $request["CardInterest"] : "";
		$webParam->setParameter(CARD_INTEREST, $cardInterest);
		// card quota
		$cardQuota = isset($request["CardQuota"]) ? $request["CardQuota"] : "";
		$webParam->setParameter(CARD_QUOTA, $cardQuota);
		
		//AUTH_FLAG
		$authFlag = isset($request["AuthFlg"]) ? $request["AuthFlg"] : "";
		$webParam->setParameter(CARD_AUTH_FLAG, $authFlag);
		
		
		//AUTH_TYPE
		$authType = isset($request["AuthType"]) ? $request["AuthType"] : "";
		$webParam->setParameter(CARD_AUTH_TYPE, $authType);
		
		//KEYIN_CL
		$keyinCl = isset($request["KeyInCl"]) ? $request["KeyInCl"] : "";
		$webParam->setParameter(CARD_KEYIN_CL, $keyinCl);
		
		// CARD TYPE ����
		$buyerAuthName = $request[BUYER_AUTH_NO];
		$cardType = "";
		if(strlen($buyerAuthName) == 10){
			$cardType = "02"; //����
		}else{
			$cardType = "01"; //����
		}
		$webParam->setParameter(CARD_TYPE, $cardType);
		
		
		// mpi
		
		$transType = $request["TransType"] == null ? "0" : $request["TransType"];
		$webParam->setParameter(TRANS_TYPE,$transType);
		
		$trKey = $request["TrKey"] == null ? "0" : $request["TrKey"];
		$webParam->setParameter(TR_KEY,$trKey);
		
	
		$ServiceAmt = $request["ServiceAmt"] == null ? "0" : $request["ServiceAmt"];
		$webParam->setParameter("ServiceAmt",$ServiceAmt);
		

		$GoodsVat = $request["GoodsVat"] == null ? "0" : $request["GoodsVat"];
		$webParam->setParameter("GoodsVat",$GoodsVat);
		

		$SupplyAmt = $request["SupplyAmt"] == null ? "0" : $request["SupplyAmt"];
		$webParam->setParameter("SupplyAmt",$SupplyAmt);
		

		$TaxFreeAmt = $request["TaxFreeAmt"] == null ? "0" : $request["TaxFreeAmt"];
		$webParam->setParameter("TaxFreeAmt",$TaxFreeAmt);
		
		return $webParam;
	}
	
}
?>
