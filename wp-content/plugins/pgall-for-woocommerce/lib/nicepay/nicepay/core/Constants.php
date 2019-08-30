<?php

define("NICEPAY_DOMAIN_NAME","211.33.136.39");

define("NICEPAY_ADAPTOR_LISTEN_PORT",9001);
define("PAY_METHOD","PayMethod");	
define("CARD_PAY_METHOD","CARD");	
define("BANK_PAY_METHOD","BANK");	
define("VBANK_PAY_METHOD","VBANK");	
define("CELLPHONE_PAY_METHOD","CELLPHONE");	
define("CPBILL_PAY_METHOD","CPBILL");
define("CASHRCPT_PAY_METHOD","CASHRCPT");
define("VBANK_BULK_PAY_METHOD","VBANK_BULK");

	
define("ESCROW_DELIVERY_REGISTER","DELVREG");
define("ESCROW_BUY_DECISION","BUYDECN");
define("ESCROW_BUY_REJECT","BUYREJT");	

define("SERVICE_MODE","SERVICE_MODE");	
define("PAY_SERVICE_CODE","PY0");	
define("CANCEL_SERVICE_CODE","CL0");	
define("CELLPHONE_REG_ITEM","CP0");	
define("CELLPHONE_SELF_DLVER","CP1");	
define("CELLPHONE_SMS_DLVER","CP2");	
define("CELLPHONE_ITEM_CONFM","CP4");	
define("ESCROW_SERVICE_CODE","EW0");

define("VERSION","Version");
define("ENC_FLAG","EncFlag");

define("GOODS_CNT","GoodsCnt");
define("GOODS_NAME","GoodsName");
define("GOODS_AMT","Amt");
define("MOID","Moid");
define("CURRENCY","Currency");
define("MID","MID");	
define("MERCHANT_KEY","LicenseKey");
define("MALL_IP","MallIP");
define("USER_IP","UserIP");
define("RETURN_URL","ReturnURL");	
define("MALL_USER_ID","MallUserID");	
define("BUYER_NAME","BuyerName");	
define("BUYER_AUTH_NO","BuyerAuthNum");	
define("BUYER_TEL","BuyerTel");	
define("BUYER_EMAIL","BuyerEmail");	
define("PARENT_EMAIL","ParentEmail");	
define("BUYER_ADDRESS","BuyerAddr");	
define("BUYER_POST_NO","BuyerPostNo");	
define("CARD_TYPE","CardType");	
define("CARD_CODE","CardCode");	
define("CARD_NO","CardNum");	

define("CARD_AUTH_FLAG","AuthFlag");	
define("CARD_KEYIN_CL","KeyInCl");
define("CARD_AUTH_TYPE","AuthType");

define("CARD_QUOTA","CardQuota");	
define("CARD_INTEREST","CardInterest");	
define("CARD_EXPIRE","CardExpire");	
define("CARD_PWD","CardPwd");	
define("CARD_POINT","CardPoint");	
define("CARD_XID","CardXID");	
define("CARD_ECI","CardECI");	
define("CARD_CAVV","CardCAVV");	
define("CARD_JOIN_CODE","JoinCode");
define("ISP_PGID","ISPPGID");	
define("ISP_CODE","ISPCode");	
define("ISP_SESSION_KEY","ISPSessionKey");	
define("ISP_ENC_DATA","ISPEncData");	
define("BANK_CODE","BankCode");	
define("BANK_ENC_DATA","BankEncData");	
define("VBANK_EXPIRE_DATE","VbankExpDate");	
define("VBANK_EXPIRE_TIME","VbankExpTime");	
define("RECEIPT_AMT","ReceiptAmt");	
define("RECEIPT_TYPE","ReceiptType");	
define("RECEIPT_TYPE_NO","ReceiptTypeNo");	


define("CANCEL_AMT","CancelAmt");	
define("CANCEL_MSG","CancelMsg");	
define("CANCEL_PWD","CancelPwd");	
define("CANCEL_IP","CancelIP");	
define("SECURE_PARAMS","SECURE_PARAMS");	
define("PERSONAL_CARD_TYPE","01");	
define("BUSINESS_CARD_TYPE","02");	
define("CREDIT_CARD","0");	
define("CHECK_CARD","1");	
define("EACH_BY_CARD_SERVICE","0");	 // �ſ�ī�� ������ ���� ���� (X�Ƚ�, ILK, ISP)
define("KEYIN","1");	             // ī����ȣ+��ȿ�Ⱓ (������)
define("KEYIN_AUTH","2");	         // ī����ȣ+��ȿ�Ⱓ+���й�ȣ+�ֹι�ȣ

define("CARD_AUTH_TYPE_KEYIN","01");
define("CARD_AUTH_TYPE_ISP","02");
define("CARD_AUTH_TYPE_VISA3D","03");

// TR_KEY �߰�
define("TR_KEY", "TrKey");
define("ID","ID");
define("EDIT_DATE","EdiDate");
define("LENGTH","Length");
define("TID","TID");
define("ERROR_SYSTEM","ErrorSys");
define("ERROR_CODE","ErrorCD");
define("ERROR_MSG","ErrorMsg");
define("LENGTH_START_POS",24);
define("LENGTH_END_POS",30);
define("LENGTH_MSG_SIZE",6);
define("ENCRYPT_DATA","EncryptData");

define("ETC_ERROR_MESSAGE","��Ÿ������ �߻��Ͽ����ϴ�.");


define("SOCKET_SO_TIMEOUT",120000);
define("CONNECT_TIMEOUT",1000);
define("EVENT_LOG","EVENT_LOG");
define("APP_LOG","APP_LOG");
define("NICEPAY_LOG_HOME","NICEPAY_LOG_HOME");
define("LOG_DIRECTORY_CONF_NAME","");
define("CP_ID","CPID");
define("CP_PWD","CPPWD");
define("ITEM_TYPE","ItemType");
define("ITEM_COUNT","ItemCount");
define("ITEM_INFO","ItemInfo");
define("SERVICE","SERVICE");
define("EMAIL","Email");
define("IPADDR","IPADDR");
define("SERVER_INFO","ServerInfo");
define("DST_ADDR","DstAddr");
define("IDEN","Iden");
define("CARRIER","Carrier");
define("SMS_OTP","SmsOTP");
define("ENCODED_TID","EncodedTID");
define("CP_TID","CPTID");

define("GOODS_CL","GoodsCl");
//define("CP_TID","CPTID");
    
    
define("VBANK_CODE","VbankBankCode");
define("EXP_DATE","VbankExpDate");
define("ACCT_NAME","VBankAccountName");
define("REFUND_ACCT","VbankRefundAccount");
define("REFUND_BANK_CODE","VbankRefundBankCode");
define("REFUND_ACCT_NAME","VbankRefundName");
define("RECEIT_SUPPLY_AMT","ReceiptSupplyAmt");
define("RECEIT_VAT","ReceiptVAT");
define("RECEIT_SERVICE_AMT","ReceiptServiceAmt");
define("RECEIT_TAXFREE_AMT","ReceiptTaxFreeAmt");    
define("RECEIT_SUB_NUM","ReceiptSubNum");
    		
define("NET_CANCEL_CODE","NetCancelCode");
        
define("SVC_CD_CARD","01"); //�ſ�ī��
define("SVC_CD_BANK","02"); //������ü
define("SVC_CD_VBANK","03"); //��������
define("SVC_CD_RECEIPT","04"); //���ݿ�����
define("SVC_CD_CELLPHONE","05"); //�޴�������
define("SVC_CD_CPBILL","06"); //�޴�������
	
define("CARD_KEYIN_CL01","01");  //ī����ȣ+��ȿ�Ⱓ
	
define("CARD_KEYIN_CL11","11");  //ī����ȣ+��ȿ�Ⱓ+�ֹι�ȣ7+���й�ȣ2

define("SVC_PRDT_CD_ONLINE","01"); // �¶���
define("SVC_PRDT_CD_MOBILE","02"); // ������
define("SVC_PRDT_CD_IPTV","03");   // IPTV

define("MALL_RESERVED","MallReserved");
define("RETRY_URL","RetryURL");

define("DELV_CORP_NAME","DeliveryCoNm");
define("INVOICE_NO","InvoiceNum");
define("REGISTER_NAME","RegisterName");

define("TRANS_TYPE","TransType");	
define("SUB_ID","SUB_ID");
define("REC_KEY","RecKey");
define("PHONE_ID","PhoneID");
define("FN_CD","FnCd");

define("PARTIAL_CANCEL_CODE","PartialCancelCode");

define("CHARSET","CHARSET");	

?>
