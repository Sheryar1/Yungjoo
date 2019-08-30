<?php
require_once dirname(__FILE__).'/WebMessageDTO.php';
require_once dirname(__FILE__).'/../log/LogMode.php';
require_once dirname(__FILE__).'/../log/NicePayLogJournal.php';
require_once dirname(__FILE__).'/../core/MessageIdVersionFactory.php';
require_once dirname(__FILE__).'/../core/ErrorMessageHandler.php';
require_once dirname(__FILE__).'/../core/ServiceFactory.php';
require_once dirname(__FILE__).'/../core/SecureMessageProcessor.php';
require_once dirname(__FILE__).'/../core/IoAdaptorTransport.php';
require_once dirname(__FILE__).'/../validator/CommonMessageValidator.php';
require_once dirname(__FILE__).'/WebParamGatherFactory.php';
require_once dirname(__FILE__).'/PayCommonWebParamGather.php';
require_once dirname(__FILE__).'/../validator/GoodsMessageDataValidator.php';
require_once dirname(__FILE__).'/../validator/MerchantMessageDataValidator.php';
require_once dirname(__FILE__).'/../validator/BuyerMessageDataValidator.php';
require_once dirname(__FILE__).'/../validator/BodyMessageValidatorFactory.php';
require_once dirname(__FILE__).'/../validator/CellPhoneRegItemBodyValidator.php';
require_once dirname(__FILE__).'/../validator/CellPhoneSelfDeliverBodyValidator.php';
require_once dirname(__FILE__).'/../validator/CellPhoneSmsDeliverBodyValidator.php';
require_once dirname(__FILE__).'/../validator/CellPhoneItemConfirmBodyValidator.php';
require_once dirname(__FILE__).'/../validator/CancelBodyMessageValidator.php';
require_once dirname(__FILE__).'/../exception/ServiceExceptionCallbackHandler.php';
require_once dirname(__FILE__).'/../exception/NetCancelCallback.php';
require_once dirname(__FILE__).'/../exception/NetCancelCallback.php';
require_once dirname(__FILE__).'/../message/MessageTemplateCreator.php';
class NicePayWEB{
	private $webMessageDTO;
	public function NicePayWEB(){
		$this->webMessageDTO = new WebMessageDTO();
	}
	public function doService($request){
	
		try {
			
			// �α� ���� ���� Ȯ��
			$eventLogEnable = $this->webMessageDTO->getParameter(EVENT_LOG);
			
			
			// APP�α� ���� ���� Ȯ�� �� �ʱ�ȭ
			$appLogEnable = $this->webMessageDTO->getParameter(APP_LOG);
			if("1" == $appLogEnable){
				LogMode::enableAppLogMode();
			}
			
			if("1"== $this->webMessageDTO->getParameter(APP_LOG)){
				$logJournal = NicePayLogJournal::getInstance();
                $directoryPath = $this->webMessageDTO->getParameter(NICEPAY_LOG_HOME);
				$logJournal->setLogDirectoryPath($directoryPath);
				$logJournal->configureNicePayLog4PHP();
			}
			
			$serviceMode  = $this->webMessageDTO->getParameter(SERVICE_MODE);
			// ���񽺸��忡 ���� version�� ID����
			$messageIdVersionFactory = new MessageIdVersionFactory();
			$messageIdVersionSetter = $messageIdVersionFactory->create($serviceMode,$this->webMessageDTO->getParameter(PAY_METHOD));
			$messageIdVersionSetter->fillIdAndVersion($this->webMessageDTO);
			
			// ���� ������ ��ȿ�� üũ
			$parameterSetValidator = new CommonMessageValidator();
			$parameterSetValidator->validate($this->webMessageDTO);
			
			//���� Gather
			$commonWebGather = new PayCommonWebParamGather();
			$commonWebGather->charset =  $this->webMessageDTO->getParameter(CHARSET);

	   	    $commonGatherParam = $commonWebGather->gather($request);
			$this->webMessageDTO->add($commonGatherParam);
			
			// ���񽺺��� http request value gather
			$webParamGatherFactory = new WebParamGatherFactory();
			$webParamGather = $webParamGatherFactory->createParamGather($serviceMode,$this->webMessageDTO->getParameter(PAY_METHOD));
			if($webParamGather!=null){
				$gatherParam = $webParamGather->gather($request);
				$this->webMessageDTO->add($gatherParam);
			}
			// ��ȿ�� üũ
			$this->paramValidateByValidate($serviceMode);
			
			if(LogMode::isAppLogable()){
				$logJournal = NicePayLogJournal::getInstance();
				$logJournal->writeAppLog("�������� �Ϸ�" );
			}
			// ������ ����
			$ioAdaptorService = $this->createIoAdaptorService($serviceMode,$this->webMessageDTO->getParameter(PAY_METHOD));
			
			// ������ ���� 
			$responseWebMDTO = $ioAdaptorService->service($this->webMessageDTO);
						
			$responseWebMDTO->setParameter(SERVICE_MODE, $serviceMode);

			if(LogMode::isAppLogable()){
				$logJournal->closeAppLog("");
			}
			
			return $responseWebMDTO;
			
			
		}catch(ServiceException $e){
			echo $e->getErrorMessage();
			// ServiceException �߻��� Ư�� �����ڵ庰�� ������ ó���Ѵ�.
			$callbackHandler = new ServiceExceptionCallbackHandler();
			$netCancelCallback = new NetCancelCallback();
			$netCancelCallback->setWebMessageDTO($this->webMessageDTO);
			$netCancelCallback->setServiceException($e);
			$callbackHandler->doHandle(array($netCancelCallback));
			$errorHandler = new ErrorMessageHandler();
			// �����ڵ�, �����޽����� �����Ѵ�.
			return $errorHandler->doHandle($e);
		}catch(Exception $e){
				echo $e->getMessage();
				// �����ڵ�, �����޽����� �����Ѵ�.
				$errorHandler = new ErrorMessageHandler();
				// �����ڵ�, �����޽����� �����Ѵ�.
				return $errorHandler->doHandle($e);
			}
			
		}
		private function paramValidateByValidate($serviceMode){
			// ������������ ���� ��ǰ����,��������  ��ȿ�� üũ
			if(PAY_SERVICE_CODE == $serviceMode){
								
				if("CASHRCPT" != $this->webMessageDTO->getParameter(PAY_METHOD)){
					$goodsValidator = new GoodsMessageDataValidator();
					$goodsValidator->validate($this->webMessageDTO);
					
					$merchantValidator = new MerchantMessageDataValidator();
					$merchantValidator->validate($this->webMessageDTO);
					
					$buyerValidator = new BuyerMessageDataValidator();
					$buyerValidator->validate($this->webMessageDTO);
				}
								
				$bodyValidatorFactory = new BodyMessageValidatorFactory();
				$bodyValidator = $bodyValidatorFactory->createValidator($this->webMessageDTO->getParameter(PAY_METHOD));
				
				if($bodyValidator!=null){
					$bodyValidator->validate($this->webMessageDTO);
				}
				
			}else if(CANCEL_SERVICE_CODE == $serviceMode){
				$cancelValidator = new CancelBodyMessageValidator();
				$cancelValidator->validate($this->webMessageDTO);
			}else if(ESCROW_SERVICE_CODE == $serviceMode){
				$bodyValidatorFactory = new BodyMessageValidatorFactory();
				$bodyValidator = $bodyValidatorFactory->createValidator($this->webMessageDTO->getParameter(PAY_METHOD));
				
				if($bodyValidator!=null){
					$bodyValidator->validate($this->webMessageDTO);
				}
			}
		}
		private function createIoAdaptorService($serviceMode,$payMethod){
			// ���� ���ø� ����
			$msgTemplateCreator = new MessageTemplateCreator();
			
			$requestTemplateDocument = $msgTemplateCreator->createRequestDocumentTemplate($serviceMode,$payMethod);
			$responseTemplateDocument = $msgTemplateCreator->createResponseDocumentTemplate($serviceMode,$payMethod);
			
			// ������Ŭ���� ����
			$serviceFactory = new ServiceFactory();
			$ioAdaptorService  = $serviceFactory->createService($serviceMode);
			$ioAdaptorService->setRequestTemplateDocument($requestTemplateDocument);
			$ioAdaptorService->setResponseTemplateDocument($responseTemplateDocument);
			
			
			// ���������� ���� ��ȣȭ ������ ó�� ����
			if(PAY_SERVICE_CODE == $serviceMode){
				$ioAdaptorService->registerSecureMessageProcessor(new SecureMessageProcessor());
			}
				
			// socket ���� Ŭ���� ����
			$ioAdaptorTransport = new IoAdaptorTransport();
			$ioAdaptorService->setTransport($ioAdaptorTransport);
			return $ioAdaptorService;
		}
		public function setParam($key, $value){
			$this->webMessageDTO->setParameter($key, $value);
		}
		public function getParam($key){
			return $this->webMessageDTO->getParameter($key);
		}
		public function setSecureTargetParams($targetParams){
			$this->webMessageDTO->setParameter(SECURE_PARAMS, $targetParams);
		}
		
	}
?>
