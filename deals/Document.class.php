<?php



/**
 * Базов клас за наследяване документи свързани със сделките
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class deals_Document extends deals_PaymentDocument
{
    
    
    /**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = "valior, title=Документ, currencyId=Валута, folderId, amount, state, createdOn, createdBy";
	
	
	/**
	 * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
	 */
	public $rowToolsSingleField = 'title';
	
	
	/**
	 * Полета свързани с цени
	 */
	public $priceFields = 'amount,amountDeal,rate';
	
	
	/**
	 * Дали в листовия изглед да се показва бутона за добавяне
	 */
	public $listAddBtn = FALSE;
	
	
	/**
	 * Поле за филтриране по дата
	 */
	public $filterDateField = 'createdOn, valior,modifiedOn';
	
	
	/**
	 * Полета от които се генерират ключови думи за търсене (@see plg_Search)
	 */
	public $searchFields = 'operationSysId,name,dealId,dealHandler,currencyId,description,contragentId,contragentClassId';
	
	
    /**
     * @param core_Mvc $mvc
     */
    protected static function addDocumentFields(core_Mvc $mvc)
    {
    	$mvc->FLD('operationSysId', 'varchar', 'caption=Операция,input=hidden');
    	$mvc->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,mandatory');
    	$mvc->FLD('name', 'varchar(255)', 'caption=Име,mandatory');
    	$mvc->FLD('dealId', 'key(mvc=findeals_Deals,select=dealName,allowEmpty)', 'caption=Сделка,input=none');
    	$mvc->FLD('amount', 'double(decimals=2)', 'caption=Платени,mandatory,summary=amount');
    	$mvc->FNC('dealHandler', 'varchar', 'caption=Насрещна сделка->Сделка,mandatory,input,silent,removeAndRefreshForm=currencyId|rate|amountDeal');
    	$mvc->FLD('amountDeal', 'double(decimals=2)', 'caption=Насрещна сделка->Заверени,mandatory,input=none');
    	$mvc->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута->Код,input=none');
    	$mvc->FLD('rate', 'double(decimals=5)', 'caption=Валута->Курс,input=none');
    	$mvc->FLD('description', 'richtext(bucket=Notes,rows=6)', 'caption=Допълнително->Бележки');
    	$mvc->FLD('creditAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
    	$mvc->FLD('debitAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
    	$mvc->FLD('contragentId', 'int', 'input=hidden,notNull');
    	$mvc->FLD('contragentClassId', 'key(mvc=core_Classes,select=name)', 'input=hidden,notNull');
    	$mvc->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно)', 'caption=Статус, input=none');
    	$mvc->FLD('isReverse', 'enum(no,yes)', 'input=none,notNull,value=no');

    	$mvc->setDbIndex('valior');
    }
	
	
	/**
	 *  Обработка на формата за редакция и добавяне
	 */
	public static function on_AfterPrepareEditForm($mvc, $res, $data)
	{
		$folderId = $data->form->rec->folderId;
		$form = &$data->form;
		$rec = &$form->rec;
		 
		$contragentId = doc_Folders::fetchCoverId($folderId);
		$contragentClassId = doc_Folders::fetchField($folderId, 'coverClass');
		$form->setDefault('contragentId', $contragentId);
		$form->setDefault('contragentClassId', $contragentClassId);
		 
		// Поставяме стойности по подразбиране
		$form->setDefault('valior', dt::today());
		 
		expect($origin = $mvc->getOrigin($form->rec));
		expect($origin->haveInterface('bgerp_DealAggregatorIntf'));
		$form->rec->originId = $origin->fetchField('containerId');
		
		$dealInfo = $origin->getAggregateDealInfo();
		expect(count($dealInfo->get('allowedPaymentOperations')));
		 
		// Показваме само тези финансови операции в които е засегнат контрагента
		$suggestions = findeals_Deals::fetchDealOptions($dealInfo->get('involvedContragents'));
		
		if(count($suggestions)){
			$form->setSuggestions('dealHandler', array('' => '') + $suggestions);
		}
		 
		$form->dealInfo = $dealInfo;
		$form->setDefault('operationSysId', $mvc::$operationSysId);
		$form->setField('amount', "unit=|*{$dealInfo->get('currency')} |по сделката");
		
		// Използваме помощната функция за намиране името на контрагента
		if(empty($form->rec->id)) {
			$form->setDefault('description', "Към документ #{$origin->getHandle()}");
		} else {
			$form->rec->dealHandler = findeals_Deals::getHandle($form->rec->dealId);
		}
		 
		if(isset($form->rec->dealHandler)){
			$errorMsg = '';
			$doc = doc_Containers::getDocumentByHandle($form->rec->dealHandler);
			
			if(!$doc){
				$errorMsg = 'Няма документ с такъв хендлър';
			} elseif(!$doc->isInstanceOf('findeals_Deals')){
				$errorMsg = 'Документа трябва да е финансова сделка';
			} elseif(!$doc->haveRightFor('single')){
				$errorMsg = 'Нямате достъп до документа';
			}
			
			if($errorMsg !== ''){
				$form->setError('dealHandler', $errorMsg);
			} else {
				$form->rec->currencyId = currency_Currencies::getIdByCode($doc->fetchField('currencyId'));
				$form->setField('amountDeal', "unit=|*{$doc->fetchField('currencyId')}");
				
				if($form->rec->currencyId != currency_Currencies::getIdByCode($origin->fetchField('currencyId'))){
					$form->setField('amountDeal', 'input');
				}
				
				// Трябва намерената сделка да е активна
				if($doc->fetchField('state') != 'active'){
					$form->setError('dealHandler', 'Сделката трябва да е активна');
				} else {
					$rec->dealId = findeals_Deals::fetchField($doc->that, 'id');
				}
			}
		}
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 *
	 * @param core_Mvc $mvc
	 * @param core_Form $form
	 */
	public static function on_AfterInputEditForm($mvc, &$form)
	{
		if($form->isSubmitted()){
			$rec = &$form->rec;
			
			$origin = $mvc->getOrigin($form->rec);
			$currencyId = $origin->fetchField('currencyId');
			$code = currency_Currencies::getCodeById($rec->currencyId);
			
			if($code == $currencyId){
				$rec->amountDeal = $rec->amount;
			}
			
			if($msg = currency_CurrencyRates::checkAmounts($rec->amount, $rec->amountDeal, $rec->valior, $currencyId, $code)){
				$form->setError('amount', $msg);
			}
		}

		$mvc->invoke('AfterInputDocumentEditForm', array($form));
	}
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	$row = new stdClass();
    	$row->title = $this->singleTitle . " №{$id}";
    	$row->authorId = $rec->createdBy;
    	$row->author = $this->getVerbal($rec, 'createdBy');
    	$row->state = $rec->state;
    	$row->recTitle = $row->title;
    
    	return $row;
    }
    
    
    /**
     * Може ли документа може да се добави в посочената папка?
     *
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
    	return FALSE;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	$docState = $firstDoc->fetchField('state');
    	 
    	if(($firstDoc->haveInterface('bgerp_DealAggregatorIntf') && $docState == 'active')){
    		// Ако няма позволени операции за документа не може да се създава
    		$dealInfo = $firstDoc->getAggregateDealInfo();
    		
    		// Ако няма позволени операции за документа не може да се създава
    		$operations = $dealInfo->get('allowedPaymentOperations');
    		
    		return isset($operations[static::$operationSysId]) ? TRUE : FALSE;
    	}
    
    	return FALSE;
    }
	
    
	/**
	 *  Обработки по вербалното представяне на данните
	 */
	protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$row->title = $mvc->getHyperlink($rec->id, TRUE);
		 
		if($fields['-single']){
			$row->nextHandle= findeals_Deals::getHyperlink($rec->dealId);
			$origin = $mvc->getOrigin($rec->id);
			$row->dealHandle = $origin->getHyperlink();
			$row->dealCurrencyId = $origin->fetchField('currencyId');
		}
	}
	
	
	/**
	 * Имплементация на @link bgerp_DealIntf::getDealInfo()
	 *
	 * @param int|object $id
	 * @return bgerp_iface_DealAggregator
	 * @see bgerp_DealIntf::getDealInfo()
	 */
	public function pushDealInfo($id, &$aggregator)
	{ 
	}
}