<?php


/**
 * Базов клас за наследяване документи свързани със сделките
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class deals_Document extends core_Master
{
    
	/**
	 * Полета от които се генерират ключови думи за търсене (@see plg_Search)
	 */
	public $searchFields = 'name, folderId, dealId, id';
    
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = "id, valior, name, folderId, currencyId=Валута, amount, state, createdOn, createdBy";
	
	
	/**
	 * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
	 */
	public $rowToolsSingleField = 'name';
	
	
	/**
	 * Полета свързани с цени
	 */
	public $priceFields = 'amount';
	
	
    /**
     * @param core_Mvc $mvc
     */
    protected static function addDocumentFields(core_Mvc $mvc)
    {
    	$mvc->FLD('operationSysId', 'varchar', 'caption=Операция,input=hidden');
    	$mvc->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,mandatory');
    	$mvc->FLD('name', 'varchar(255)', 'caption=Име,mandatory');
    	$mvc->FNC('dealHandler', 'varchar', 'caption=Сделка,mandatory,input');
    	$mvc->FLD('dealId', 'key(mvc=findeals_Deals,select=detailedName,allowEmpty)', 'caption=Сделка,input=none');
    	$mvc->FLD('amount', 'double(smartRound)', 'caption=Сума,mandatory,summary=amount');
    	$mvc->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута->Код');
    	$mvc->FLD('rate', 'double(smartRound,decimals=2)', 'caption=Валута->Курс');
    	$mvc->FLD('description', 'richtext(bucket=Notes,rows=6)', 'caption=Бележки');
    	$mvc->FLD('creditAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
    	$mvc->FLD('debitAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
    	$mvc->FLD('contragentId', 'int', 'input=hidden,notNull');
    	$mvc->FLD('contragentClassId', 'key(mvc=core_Classes,select=name)', 'input=hidden,notNull');
    	$mvc->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 'caption=Статус, input=none');
    	$mvc->FLD('isReverse', 'enum(no,yes)', 'input=none,notNull,value=no');
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
		$dealInfo = $origin->getAggregateDealInfo();
		expect(count($dealInfo->get('allowedPaymentOperations')));
		 
		// Показваме само тези финансови операции в които е засегнат контрагента
		$suggestions = findeals_Deals::fetchDealOptions($dealInfo->get('involvedContragents'));
		
		if(count($suggestions)){
			$form->setSuggestions('dealHandler', array('' => '') + $suggestions);
		}
		 
		$form->dealInfo = $dealInfo;
		$form->setDefault('operationSysId', $mvc::$operationSysId);
		 
		// Използваме помощната функция за намиране името на контрагента
		if(empty($form->rec->id)) {
			$form->setDefault('description', "Към документ #{$origin->getHandle()}");
			$form->rec->currencyId = currency_Currencies::getIdByCode($dealInfo->get('currency'));
			$form->rec->rate = $dealInfo->get('rate');
		} else {
			$form->rec->dealHandler = findeals_Deals::getHandle($form->rec->dealId);
		}
		 
		$form->addAttr('currencyId', array('onchange' => "document.forms['{$data->form->formAttr['id']}'].elements['rate'].value ='';"));
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
			
			if($rec->dealHandler){
				$doc = doc_Containers::getDocumentByHandle($rec->dealHandler);
				if(!$doc){
					$form->setError('dealHandler', 'Няма документ с такъв хендлър');
				} else {
					// Трябва намерената сделка да е активна
					if($doc->fetchField('state') != 'active'){
						$form->setError('dealHandler', 'Сделката трябва да е активна');
					} else {
						$rec->dealId = findeals_Deals::fetchField($doc->that, 'id');
					}
				}
			}
		}

		$mvc->invoke('AfterInputDocumentEditForm', array($form));
	}
	
	
	/**
	 * Връща разбираемо за човека заглавие, отговарящо на записа
	 */
	public static function getRecTitle($rec, $escaped = TRUE)
	{
		$self = cls::get(get_called_class());
		 
		return "{$self->singleTitle} №{$rec->id}";
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
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$row->number = $mvc->getHandle($rec->id);
		if($fields['-list']){
			$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
		}
		 
		if($fields['-single']){
			if(findeals_Deals::haveRightFor('single', $rec->dealId)){
				$row->dealId = ht::createLink($row->dealId, array('findeals_Deals', 'single', $rec->dealId));
			}
	
			$baseCurrencyId = acc_Periods::getBaseCurrencyId($rec->valior);
	
			if($baseCurrencyId != $rec->currencyId) {
				$Double = cls::get('type_Double');
				$Double->params['decimals'] = 2;
				$rec->amountBase = round($rec->amount * $rec->rate, 2);
				$row->amountBase = $Double->toVerbal($rec->amountBase);
				$row->baseCurrency = currency_Currencies::getCodeById($baseCurrencyId);
			} else {
				unset($row->rate);
			}
		}
	}
    
    
	/**
	 * Извиква се след подготовката на toolbar-а за табличния изглед
	 */
	public static function on_AfterPrepareListToolbar($mvc, &$data)
	{
		$data->toolbar->removeBtn('btnAdd');
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