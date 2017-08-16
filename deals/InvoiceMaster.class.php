<?php



/**
 * Базов клас за наследяване на ф-ри
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class deals_InvoiceMaster extends core_Master
{
	
	
	/**
	 * Поле за единичния изглед
	 */
	public $rowToolsSingleField = 'number';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'dealValue,vatAmount,baseAmount,total,vatPercent,discountAmount';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $valiorFld = 'date';
    
    
    /**
     * Може ли да се принтират оттеглените документи?
     */
    public $printRejected = TRUE;
    
    
    /**
     * Работен кеш
     */
    protected $cache = array();
    
    
    /**
     * На кой ред в тулбара да се показва бутона за принтиране
     */
    public $printBtnToolbarRow = 1;
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = FALSE;
    
    
    /**
     * Дата на очакване
     */
    public $termDateFld = 'dueDate';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn,date,dueDate';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'number,date,dueTime,dueDate,vatDate';
    
    
    /**
     * След описанието на полетата
     */
    protected static function setInvoiceFields(core_Master &$mvc)
    {
    	$mvc->FLD('date', 'date(format=d.m.Y)', 'caption=Дата,  notNull, mandatory');
    	$mvc->FLD('place', 'varchar(64)', 'caption=Място, class=contactData');
    	$mvc->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
    	$mvc->FLD('contragentId', 'int', 'input=hidden');
    	$mvc->FLD('contragentName', 'varchar', 'caption=Контрагент->Име, mandatory, class=contactData');
    	$mvc->FLD('responsible', 'varchar(255)', 'caption=Контрагент->Отговорник, class=contactData');
    	$mvc->FLD('contragentCountryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Контрагент->Държава,mandatory,contragentDataField=countryId');
    	$mvc->FLD('contragentVatNo', 'drdata_VatType', 'caption=Контрагент->VAT №,contragentDataField=vatNo');
    	$mvc->FLD('uicNo', 'type_Varchar', 'caption=Контрагент->Национален №,contragentDataField=uicId');
    	$mvc->FLD('contragentPCode', 'varchar(16)', 'caption=Контрагент->П. код,recently,class=pCode,contragentDataField=pCode');
    	$mvc->FLD('contragentPlace', 'varchar(64)', 'caption=Контрагент->Град,class=contactData,contragentDataField=place');
    	$mvc->FLD('contragentAddress', 'varchar(255)', 'caption=Контрагент->Адрес,class=contactData,contragentDataField=address');
    	$mvc->FLD('changeAmount', 'double(decimals=2)', 'input=none');
    	$mvc->FLD('reason', 'text(rows=2)', 'caption=Плащане->Основание, input=none');
    	
    	$mvc->FLD('dueTime', 'time(suggestions=3 дена|5 дена|7 дена|14 дена|30 дена|45 дена|60 дена)', 'caption=Плащане->Срок');
    	$mvc->FLD('dueDate', 'date', 'caption=Плащане->Краен срок');
    	$mvc->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута->Код,input=hidden');
    	$mvc->FLD('rate', 'double(decimals=5)', 'caption=Плащане->Курс,before=dueTime,input=hidden,silent');
    	$mvc->FLD('displayRate', 'double(decimals=5)', 'caption=Плащане->Курс,before=dueTime');
    	$mvc->FLD('deliveryId', 'key(mvc=cond_DeliveryTerms, select=codeName, allowEmpty)', 'caption=Доставка->Условие,input=hidden');
    	$mvc->FLD('deliveryPlaceId', 'key(mvc=crm_Locations, select=title)', 'caption=Доставка->Място,hint=Избор измежду въведените обекти на контрагента');
    	$mvc->FLD('vatDate', 'date(format=d.m.Y)', 'caption=Данъчни параметри->Дата на ДС,hint=Дата на възникване на данъчното събитие');
    	$mvc->FLD('vatRate', 'enum(yes=Включено, separate=Отделно, exempt=Oсвободено, no=Без начисляване)', 'caption=Данъчни параметри->ДДС,input=hidden');
    	$mvc->FLD('vatReason', 'varchar(255)', 'caption=Данъчни параметри->Основание,recently,Основание за размера на ДДС');
    	$mvc->FLD('additionalInfo', 'richtext(bucket=Notes, rows=6)', 'caption=Допълнително->Бележки');
    	$mvc->FLD('dealValue', 'double(decimals=2)', 'caption=Без ДДС, input=hidden,summary=amount');
    	$mvc->FLD('vatAmount', 'double(decimals=2)', 'caption=ДДС, input=none,summary=amount');
    	$mvc->FLD('discountAmount', 'double(decimals=2)', 'caption=Отстъпка->Обща, input=none,summary=amount');
    	$mvc->FLD('sourceContainerId', 'key(mvc=doc_Containers,allowEmpty)', 'input=hidden,silent');
    }
    
    
    /**
     *  Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
    	if(!Request::get('Rejected', 'int')){
    		$data->listFilter->FNC('invState', 'enum(all=Всички, draft=Чернова, active=Контиран)', 'caption=Състояние,input,silent');
    		 
    		$data->listFilter->showFields .= ',invState';
    		$data->listFilter->input();
    		$data->listFilter->setDefault('invState', 'all');
    		 
    		if($rec = $data->listFilter->rec){
    		
    			// Филтър по състояние
    			if($rec->invState){
    				if($rec->invState != 'all'){
    					$data->query->where("#state = '{$rec->invState}'");
    				}
    			}
    		}
    	}
    	
    	$data->query->orderBy('#number', 'DESC');
    }
    
    
    /**
     * Изпълнява се след обновяване на информацията за потребител
     */
    public static function on_AfterUpdate($mvc, $rec, $fields = NULL)
    {
    	if($rec->type === 'dc_note'){
    		
    		// Ако е известие и има поне един детайл обновяваме мастъра
    		$Detail = $mvc->mainDetail;
    		$query = $mvc->{$Detail}->getQuery();
    		$query->where("#{$mvc->{$Detail}->masterKey} = '{$rec->id}'");
    		if($query->fetch()){
    			$mvc->updateQueue[$rec->id] = $rec->id;
    		}
    	}
    }


    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id, $save = TRUE)
    {
    	$rec = $this->fetchRec($id);
    	$Detail = cls::get($this->mainDetail);
    	
    	$query = $Detail->getQuery();
    	$query->where("#{$Detail->masterKey} = '{$rec->id}'");
    	$recs = $query->fetchAll();
    	
    	if(count($recs)){
    		foreach ($recs as &$dRec){
    			$dRec->price = $dRec->price * $dRec->quantityInPack;
    		}
    	}
    	
    	$Detail->calculateAmount($recs, $rec);
    	
    	$rate = ($rec->displayRate) ? $rec->displayRate : $rec->rate;
    	
    	$rec->dealValue = $this->_total->amount * $rate;
    	$rec->vatAmount = $this->_total->vat * $rate;
    	$rec->discountAmount = $this->_total->discount * $rate;
    	
    	if($save){
    		return $this->save($rec);
    	}
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    public static function on_BeforePrepareEditTitle($mvc, &$res, &$data)
    {
    	$rec = &$data->form->rec;
    	if($rec->type == 'dc_note'){
    		$data->singleTitle = ($rec->dealValue <= 0) ? 'кредитно известие' : 'дебитно известие';
    	} else {
    		$data->singleTitle = $mvc->singleTitle;
    	}
    }
    
    
    /**
     * Валидиране на полето 'vatDate' - дата на данъчно събитие (ДС)
     *
     * Грешка ако ДС е след датата на фактурата или на повече от 5 дни преди тази дата.
     */
    public static function on_ValidateVatDate(core_Mvc $mvc, $rec, core_Form $form)
    {
    	if (empty($rec->vatDate)) {
    		return;
    	}
    
    	// Датата на ДС не може да бъде след датата на фактурата, нито на повече от 5 дни преди нея.
    	if ($rec->vatDate > $rec->date || dt::addDays(5, $rec->vatDate) < $rec->date) {
    		$form->setError('vatDate', '|Данъчното събитие трябва да е до 5 дни|* <b>|преди|*</b> |датата на фактурата|*');
    	}
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public function renderSingleLayout($data)
    {
    	$tpl = parent::renderSingleLayout($data);
    	
    	if(Mode::is('printing') || Mode::is('text', 'xhtml')){
    		$tpl->removeBlock('header');
    	}
    	
    	return $tpl;
    }


    /**
     * След подготовка на тулбара на единичен изглед.
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
    	 
    	if($rec->type == 'invoice' && $rec->state == 'active'){
    		if($mvc->haveRightFor('add', (object)array('type' => 'dc_note','threadId' => $rec->threadId)) && $mvc->canAddToThread($rec->threadId)){
    			$data->toolbar->addBtn('Известие||D/C note', array($mvc, 'add', 'originId' => $rec->containerId, 'type' => 'dc_note', 'ret_url' => TRUE), 'ef_icon=img/16/layout_join_vertical.png,title=Дебитно или кредитно известие към документа,rows=2');
    		}
    	}
    }


    /**
     * Попълва дефолтите на Дебитното / Кредитното известие
     */
    protected function populateNoteFromInvoice(core_Form &$form, core_ObjectReference $origin)
    {
    	if($this instanceof purchase_Invoices){
    		$form->setField('contragentSource', 'input=none');
    		$form->setField('selectedContragentId', 'input=none');
    	}
    	
    	$caption = ($form->rec->type == 'debit_note') ? 'Увеличение' : 'Намаление';
    	$invArr = (array)$origin->fetch();
    	
    	// Трябва фактурата основание да не е ДИ или КИ
    	expect($invArr['type'] == 'invoice');
    	
    	$number = $origin->getInstance()->recToVerbal((object)$invArr)->number;
    	 
    	$invDate = dt::mysql2verbal($invArr['date'], 'd.m.Y');
    	
    	if($invArr['type'] != 'dc_note'){
    		$show = TRUE;
    		if(isset($form->rec->id)){
    			$Detail = cls::get($this->mainDetail);
    			$dQuery = $Detail->getQuery();
    			$dQuery->where("#invoiceId = {$form->rec->id}");
    			
    			if($dQuery->count()){
    				$show = FALSE;
    			}
    		}
    		
    		if($show === TRUE){
    			$cache = $this->getInvoiceDetailedInfo($form->rec->originId);
    			
    			if(count($cache->vats) == 1){
    				$form->setField('changeAmount', "unit={$invArr['currencyId']} без ДДС");
    				$form->setField('changeAmount', "input,caption=Задаване на увеличение/намаление на фактура->Промяна");
    				$form->rec->changeAmountVat = key($cache->vats);
    				
    				$min = $invArr['dealValue'] / (($invArr['displayRate']) ? $invArr['displayRate'] : $invArr['rate']);
    				$min = round($min, 4);
    				 
    				$form->setFieldTypeParams('changeAmount', array('min' => -1 * $min));
    				 
    				if($invArr['dpOperation'] == 'accrued'){
    						
    					// Ако е известие към авансова ф-ра поставяме за дефолт сумата на фактурата
    					$caption = '|Промяна на авансово плащане|*';
    					$form->setField('changeAmount', "caption={$caption}->|Аванс|*,mandatory");
    				}
    			}
    		}
    	}
    	
    	foreach(array('id', 'number', 'date', 'containerId', 'additionalInfo', 'dealValue', 'vatAmount', 'state', 'discountAmount', 'createdOn', 'createdBy', 'modifiedOn', 'modifiedBy', 'vatDate', 'dpAmount', 'dpOperation', 'sourceContainerId', 'dueDate', 'type', 'originId', 'changeAmount') as $key){
    		unset($invArr[$key]);
    	}
    
    	if($form->rec->type == 'credit_note'){
    		unset($invArr['dueDate']);
    	}
    	
    	// Копиране на повечето от полетата на фактурата
    	foreach($invArr as $field => $value){
    		$form->setDefault($field, $value);
    	}
    	 
    	$form->setDefault('date', dt::today());
    	
    	$form->setField('vatRate', 'input=hidden');
    	$form->setField('deliveryId', 'input=none');
    	$form->setField('deliveryPlaceId', 'input=none');
    	$form->setField('displayRate', 'input=hidden');
    	
    	foreach(array('contragentName', 'contragentVatNo', 'uicNo', 'contragentCountryId', 'contragentPCode', 'contragentPlace', 'contragentAddress') as $name){
    		if($form->rec->{$name}){
    			$form->setReadOnly($name);
    		}
    	}
    }
    

    /**
     * Връща тялото на имейла генериран от документа
     * 
     * @see email_DocumentIntf
     * @param int $id - ид на документа
     * @param boolean $forward
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = FALSE)
    {
    	$handle = $this->getHandle($id);
    	
    	if($this->getField('type', FALSE)){
    		$rec = static::fetch($id);
    		switch($rec->type){
    			case 'invoice':
    				$type = "приложената фактура";
    				break;
    			case 'debit_note':
    				$type = "приложеното дебитно известие";
    				break;
    			case 'credit_note':
    				$type = "";
    			case 'dc_note':
    				$type = ($rec->dealValue <= 0) ? "приложеното кредитно известие" : "приложеното дебитно известие";
    				break;
    		}
    	} else {
    		$type = 'приложената проформа фактура';
    	}
    	
    	// Създаване на шаблона
    	$tpl = new ET(tr("Моля запознайте се с") . " [#type#]:\n#[#handle#]");
    	$tpl->append($handle, 'handle');
    	$tpl->append(tr($type), 'type');
    
    	return $tpl->getContent();
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
		$row = new stdClass();
		
		$template = $this->getTemplate($id);
		$lang = doc_TplManager::fetchField($template, 'lang');
		
		if($lang){
			core_Lg::push($lang);
		}
		
        $row->title = static::getRecTitle($rec);
        
        if($lang){
        	core_Lg::pop();
        }
        
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->authorId = $rec->createdBy;
        $row->state = $rec->state;
        $row->recTitle = $row->title;
        
        return $row;
   }

   
   /**
    * Връща масив от използваните нестандартни артикули в фактурата
    * 
    * @param int $id - ид на фактура
    * @return param $res - масив с използваните документи
    * 					['class'] - инстанция на документа
    * 					['id'] - ид на документа
    */
   public function getUsedDocs_($id)
   {
	   	return deals_Helper::getUsedDocs($this, $id);
   }


   /**
    * Документа не може да се активира ако има детайл с количество 0
    */
   public static function on_AfterCanActivate($mvc, &$res, $rec)
   {
	   	if($rec->type == 'dc_note' && isset($rec->changeAmount)){
	   		return $res = TRUE;
	   	}
	   	
	   	// Ако няма ид, не може да се активира документа
	   	if(empty($rec->id) && !isset($rec->dpAmount)) {
	   		
	   		return $res = FALSE;
	   	}
	   	 
	   	// Ако има Авансово плащане може да се активира
	   	if(isset($rec->dpAmount)){
	   		$res = (round($rec->dealValue, 2) < 0 || is_null($rec->dealValue)) ? FALSE : TRUE;
	   		
	   		return;
	   	}
	   	 
	   	if($rec->type != 'dc_note'){
	   		$Detail = $mvc->mainDetail;
	   		$dQuery = $mvc->{$Detail}->getQuery();
	   		$dQuery->where("#{$mvc->{$Detail}->masterKey} = {$rec->id}");
	   		$dQuery->where("#quantity = 0");
	   		 
	   		// Ако има поне едно 0-во к-во документа, не може да се активира
	   		if($dQuery->fetch()){
	   			$res = FALSE;
	   		}
	   	}
   }
   
   
   /**
    * Извиква се след успешен запис в модела
    */
   public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
   {
	   	$Source = $mvc->getSourceOrigin($rec);
	   	if(!$Source) return;

	   	// Инвалидираме кеша на документа
	   	doc_DocumentCache::cacheInvalidation($Source->fetchField('containerId'));
	  
	   	if($rec->_isClone === TRUE) return;
	   	
	   	// Само ако записа е след редакция
	   	if($rec->_edited !== TRUE) return;
	   	
	   	// И не се начислява аванс
	   	if($rec->dpAmount && $rec->dpOperation == 'accrued') return;
		
	   	// Ако е ДИ или КИ и има зададена сума не зареждаме нищо
	   	if($rec->type != 'invoice' && isset($rec->changeAmount)) return;
	   	
	   	// И няма детайли
	   	$Detail = cls::get($mvc->mainDetail);
	   	if($Detail->fetch("#{$Detail->masterKey} = '{$rec->id}'")) return;
	   	
	   	if($Source->haveInterface('deals_InvoiceSourceIntf')){
	   		$detailsToSave = $Source->getDetailsFromSource($mvc);
	   		if(is_array($detailsToSave)){
	   			foreach ($detailsToSave as $det){
	   				$det->{$Detail->masterKey} = $rec->id;
	   				$Detail->save($det);
	   			}
	   		}
	   	}
   }
   
   
   /**
    * Подготвя данните (в обекта $data) необходими за единичния изглед
    */
   public function prepareSingle_($data)
   {
	   	parent::prepareSingle_($data);
	   	$rec = &$data->rec;
	   	
	   	if(empty($data->noTotal)){
	   		if(isset($rec->type) && $rec->type != 'invoice' && isset($rec->changeAmount)){
	   			$this->_total = new stdClass();
	   			$this->_total->amount = $rec->dealValue / $rec->rate;
	   			$this->_total->vat = $rec->vatAmount / $rec->rate;
	   			$percent = round($this->_total->vat / $this->_total->amount, 2);
	   			$this->_total->vats["{$percent}"] = (object)array('amount' => $this->_total->vat, 'sum' => $this->_total->amount);
	   		}
	   		
	   		$this->invoke('BeforePrepareSummary', array($this->_total));
	   		
	   		$rate = ($rec->displayRate) ? $rec->displayRate : $rec->rate;
	   		$data->summary = deals_Helper::prepareSummary($this->_total, $rec->date, $rate, $rec->currencyId, $rec->vatRate, TRUE, $rec->tplLang);
	   		
	   		$data->row = (object)((array)$data->row + (array)$data->summary);
	   		$data->row->vatAmount = $data->summary->vatAmount;
	   	}
   }
    
    
   /**
    * След подготовка на тулбара на единичен изглед.
    */
   public static function on_AfterPrepareSingle($mvc, &$res, &$data)
   {
    	$rec = &$data->rec;
    	
    	$myCompany = crm_Companies::fetchOwnCompany();
    	if($rec->contragentCountryId != $myCompany->countryId){
    		$data->row->place = str::utf2ascii($data->row->place);
    	}
    }
    
    
    /**
     * След подготовката на навигацията по сраници
     */
    public static function on_AfterPrepareListPager($mvc, &$data)
    {

        if(Mode::is('printing')){
    	    unset($data->pager);
    	}
    }
    
    
    /**
     * След подготовка на формата
     */
    protected static function prepareInvoiceForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$form->setDefault('date', dt::today());
    	if(empty($form->rec->id)){
    		$form->rec->contragentClassId = doc_Folders::fetchCoverClassId($form->rec->folderId);
    		$form->rec->contragentId = doc_Folders::fetchCoverId($form->rec->folderId);
    	}
    	
    	// При създаване на нова ф-ра зареждаме полетата на
    	// формата с разумни стойности по подразбиране.
    	expect($origin = $mvc::getOrigin($form->rec));
    	$firstDocument = doc_Threads::getFirstDocument($form->rec->threadId);
    	
    	$coverClass = doc_Folders::fetchCoverClassName($form->rec->folderId);
    	$coverId = doc_Folders::fetchCoverId($form->rec->folderId);
    	$form->setDefault('contragentName', $coverClass::fetchField($coverId, 'name'));
    
    	if(!$firstDocument->isInstanceOf('findeals_AdvanceDeals')){
    		$className = doc_Folders::fetchCoverClassName($form->rec->folderId);
    		if($className == 'crm_Persons'){
    			$numType = 'bglocal_EgnType';
    			$form->setField('uicNo', 'caption=Контрагент->ЕГН');
    			$form->getField('uicNo')->type = cls::get($numType);
    		}
    	}
    
    	$type = Request::get('type');
    	wp($type, Request::$vars);
    	
    	if(empty($type)){
    		$type = 'invoice';
    	}
    	$form->setDefault('type', $type);
    	
    	if($firstDocument->haveInterface('bgerp_DealAggregatorIntf')){
    		$aggregateInfo = $firstDocument->getAggregateDealInfo();
    		
    		$form->rec->vatRate    = $aggregateInfo->get('vatType');
    		$form->rec->currencyId = $aggregateInfo->get('currency');
    		$form->rec->rate       = $aggregateInfo->get('rate');
    		$form->setSuggestions('displayRate', array('' => '', $aggregateInfo->get('rate') => $aggregateInfo->get('rate')));
    		
    		if($aggregateInfo->get('paymentMethodId') && !($mvc instanceof sales_Proformas)){
    			$paymentMethodId = $aggregateInfo->get('paymentMethodId');
    			$plan = cond_PaymentMethods::getPaymentPlan($paymentMethodId, $aggregateInfo->get('amount'), $form->rec->date);
    			
    			if(!isset($form->rec->id)){
    				$form->setDefault('dueTime', $plan['timeBalancePayment']);
    			}
    		}
    		 
    		$form->rec->deliveryId = $aggregateInfo->get('deliveryTerm');
    		if($aggregateInfo->get('deliveryLocation')){
    			$form->setDefault('deliveryPlaceId', $aggregateInfo->get('deliveryLocation'));
    		}
    		
    		$data->aggregateInfo = $aggregateInfo;
    		$form->aggregateInfo = $aggregateInfo;
    	} 
    	 
    	// Ако ориджина също е фактура
    	$origin = $mvc->getSourceOrigin($form->rec);
    	if($origin->className  == $mvc->className){
    		$mvc->populateNoteFromInvoice($form, $origin);
    		$data->flag = TRUE;
    	}
    	 
    	if(empty($data->flag)){
    		$locations = crm_Locations::getContragentOptions($coverClass, $coverId);
    		$form->setOptions('deliveryPlaceId',  array('' => '') + $locations);
    	}
    	 
    	// Метод който да бъде прихванат от deals_plg_DpInvoice
    	$mvc->prepareDpInvoicePlg($data);
    	
    	if($form->rec->currencyId == acc_Periods::getBaseCurrencyCode($form->rec->date)){
    		$form->setField('displayRate', 'input=hidden');
    	}
    	
    	$noReason1 = acc_Setup::get('VAT_REASON_OUTSIDE_EU');
    	$noReason2 = acc_Setup::get('VAT_REASON_IN_EU');
    	$suggestions = array('' => '', $noReason1 => $noReason1, $noReason2 => $noReason2);
    	$form->setSuggestions('vatReason', $suggestions);
    }
    
    
    /**
     * След изпращане на формата
     */
    protected static function inputInvoiceForm(core_Mvc $mvc, core_Form $form)
    {
    	if ($form->isSubmitted()) {
    		$rec = &$form->rec;
    		
    		if(isset($rec->dueDate) && $rec->dueDate < $rec->date){
    			$form->setError('date,dueDate', "Крайната дата за плащане трябва да е след вальора");
    		}
    		
    		if(!$rec->displayRate){
    			$rec->displayRate = currency_CurrencyRates::getRate($rec->date, $rec->currencyId, NULL);
    			if(!$rec->displayRate){
    				$form->setError('rate', "Не може да се изчисли курс");
    			}
    		} else {
    			if($msg = currency_CurrencyRates::hasDeviation($rec->displayRate, $rec->date, $rec->currencyId, NULL)){
    				$form->setWarning('displayRate', $msg);
    			}
    		}
    		 
    		$Vats = cls::get('drdata_Vats');
    		$rec->contragentVatNo = $Vats->canonize($rec->contragentVatNo);
    		 
    		foreach ($mvc->fields as $fName => $field) {
    			$mvc->invoke('Validate' . ucfirst($fName), array($rec, $form));
    		}
    		 
    		if(strlen($rec->contragentVatNo) && !strlen($rec->uicNo)){
    			$rec->uicNo = drdata_Vats::getUicByVatNo($rec->contragentVatNo);
    		} elseif(!strlen($rec->contragentVatNo) && !strlen($rec->uicNo)){
    			$form->setError('contragentVatNo,uicNo', 'Трябва да е въведен поне един от номерата');
    		}
    		 
    		// Ако е ДИ или КИ
    		if($rec->type != 'invoice'){
    		    wp($rec);
    			if(isset($rec->changeAmount)){
    				if($rec->changeAmount == 0){
    					$form->setError('changeAmount', 'Не може да се създаде известие с нулева стойност');
    					
    					return;
    				}
    			}
    			
    			if(isset($rec->changeAmountVat)){
    				$vat = $rec->changeAmountVat;
    			} else {
    				// Изчисляване на стойността на ддс-то
    				$vat = acc_Periods::fetchByDate()->vatRate;
    				 
    				// Ако не трябва да се начислява ддс, не начисляваме
    				if($rec->vatRate != 'yes' && $rec->vatRate != 'separate'){
    					$vat = 0;
    				}
    			}
    			
    			$origin = doc_Containers::getDocument($rec->originId);
    			$originRec = $origin->fetch('dpAmount,dpOperation,dealValue');
    			
    			if($originRec->dpOperation == 'accrued' || isset($rec->changeAmount)){
    				$diff = ($rec->changeAmount * $rec->rate);
    				$rec->vatAmount = $diff * $vat;
    				
    				// Стойността е променената сума
    				$rec->dealValue = $diff;
    			}
    		}
    		
    		if(!empty($rec->dueDate) && !empty($rec->dueTime)){
    			$cDate = dt::addSecs($rec->dueTime, $rec->date);
    			$cDate = dt::verbal2mysql($cDate, FALSE);
    			if($cDate != $rec->dueDate){
    				$form->setError('date,dueDate,dueTime', "Невъзможна стойност на датите");
    			}
    		}
    	}
    	
    	$form->rec->_edited = TRUE;
    	
    	// Метод който да бъде прихванат от deals_plg_DpInvoice
    	$mvc->inputDpInvoice($form);
    }
    
    
    /**
     * Преди запис в модела
     */
    protected static function beforeInvoiceSave($rec)
    {
    	if (!empty($rec->folderId)) {
    		if(empty($rec->contragentClassId)){
    			$rec->contragentClassId = doc_Folders::fetchCoverClassId($rec->folderId);
    		}
    		if(empty($rec->contragentId)){
    			$rec->contragentId = doc_Folders::fetchCoverId($rec->folderId);
    		}
    	}
    	
    	if($rec->state == 'active'){
    		 
    		if(empty($rec->place) && $rec->state == 'active'){
    			$inCharge = cls::get($rec->contragentClassId)->fetchField($rec->contragentId, 'inCharge');
    			$inChargeRec = crm_Profiles::getProfile($inCharge);
    			$myCompany = crm_Companies::fetchOwnCompany();
    			$place = empty($inChargeRec->place) ? $myCompany->place : $inChargeRec->place;
    			$countryId = empty($inChargeRec->country) ? $myCompany->countryId : $inChargeRec->country;
    
    			$rec->place = $place;
    			if($rec->contragentCountryId != $countryId){
    				$cCountry = drdata_Countries::fetchField($countryId, 'commonNameBg');
    				$rec->place .= (($place) ? ", " : "") . $cCountry;
    			}
    		}
    		
    		if(empty($rec->dueDate)){
    			$dueTime = ($rec->dueTime) ? $rec->dueTime : sales_Setup::get('INVOICE_DEFAULT_VALID_FOR');
    		
    			if($dueTime){
    				$rec->dueDate = dt::verbal2mysql(dt::addSecs($dueTime, $rec->date), FALSE);
    			}
    		}
    	}
    }
    
    
    /**
     * Вербално представяне на фактурата
     */
    protected static function getVerbalInvoice($mvc, $rec, $row, $fields)
    {
    	$row->rate = ($rec->displayRate) ? $row->displayRate : $row->rate;
    	
    	if($rec->number){
    		$row->number = str_pad($rec->number, '10', '0', STR_PAD_LEFT);
    	}
    	 
    	if($rec->type == 'dc_note'){
    		core_Lg::push($rec->tplLang);
    		$row->type = ($rec->dealValue <= 0) ? tr('Кредитно известие') : tr('Дебитно известие');
    		core_Lg::pop();
    	}
    	
    	if($fields['-list']){
    		if($rec->number){
    			$row->number = ht::createLink($row->number, $mvc->getSingleUrlArray($rec->id), NULL, 'ef_icon=img/16/invoice.png');
    		}
    	
    		$total = $rec->dealValue + $rec->vatAmount - $rec->discountAmount;
    		$noVat = $rec->dealValue - $rec->discountAmount;
    		
    		$totalToVerbal = (!empty($rec->rate)) ? $total / $rec->rate : $total;
    		$novatToVerbal = (!empty($rec->rate)) ? $noVat / $rec->rate : $noVat;
    		$amountToVerbal = (!empty($rec->rate)) ? $rec->vatAmount / $rec->rate : $rec->vatAmount;
    		
    		$row->dealValue = $mvc->getFieldType('dealValue')->toVerbal($totalToVerbal);
    		$row->valueNoVat = $mvc->getFieldType('dealValue')->toVerbal($novatToVerbal);
    		$row->vatAmount = $mvc->getFieldType('dealValue')->toVerbal($amountToVerbal);
    		
    		if($total < 0){
    			$row->dealValue = "<span class='red'>{$row->dealValue}</span>";
    			$row->valueNoVat = "<span class='red'>{$row->valueNoVat}</span>";
    			$row->vatAmount = "<span class='red'>{$row->vatAmount}</span>";
    		}
    	}
    	
    	if($fields['-single']){
    		if(empty($rec->vatReason)){
    			if(!drdata_Countries::isEu($rec->contragentCountryId)){
    				$row->vatReason = acc_Setup::get('VAT_REASON_OUTSIDE_EU');
    			} elseif(!empty($rec->contragentVatNo) && $rec->contragentCountryId != drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id')){
    				$row->vatReason = acc_Setup::get('VAT_REASON_IN_EU');
    			}
    		}
    		
    		core_Lg::push($rec->tplLang);
    		
    		if($rec->originId && $rec->type != 'invoice'){
    			unset($row->deliveryPlaceId, $row->deliveryId);
    		}
    	
    		if(doc_Folders::fetchCoverClassName($rec->folderId) == 'crm_Persons'){
    			$row->cNum = tr('|ЕГН|* / <i>Personal №</i>');
    		} else {
    			$row->cNum = tr('|ЕИК|* / <i>UIC</i>');
    		}
    	
    		$userRec = core_Users::fetch($rec->createdBy);
    		$usernames = core_Users::recToVerbal($userRec, 'names')->names;
    		$row->username = core_Lg::transliterate($usernames);
    		
    		$row->userCode = crc32("{$usernames}|{$userRec->id}");
    		$row->userCode = substr($row->userCode, 0, 6);
    		
    		if($rec->type != 'invoice' && !($mvc instanceof sales_Proformas)){
    			$originRec = $mvc->getSourceOrigin($rec)->fetch();
    			$originRow = $mvc->recToVerbal($originRec);
    			$row->originInv = $originRow->number;
    			$row->originInvDate = $originRow->date;
    		}
    			
    		if($rec->rate == 1){
    			unset($row->rate);
    		}
    			
    		if(!$row->vatAmount){
    			$coreConf = core_Packs::getConfig('core');
    			$pointSign = $coreConf->EF_NUMBER_DEC_POINT;
    			
    			$row->vatAmount = "<span class='quiet'>0" . $pointSign . "00</span>";
    		}
    		
    		if($rec->deliveryPlaceId){
    			if($gln = crm_Locations::fetchField($rec->deliveryPlaceId, 'gln')){
    				$row->deliveryPlaceId .= ', ' . $gln;
    			}
    		}
    		
    		// Ако не е въведена дата на даначно събитие, приема се, че е текущата
    		if(empty($rec->vatDate)){
    			$row->vatDate = $mvc->getFieldType('vatDate')->toVerbal($rec->date);
    		}
    		
    		foreach (array('contragentPlace', 'contragentAddress') as $cfld){
    			if(!empty($rec->{$cfld})){
    				$row->{$cfld} = core_Lg::transliterate($row->{$cfld});
    			}
    		}
    		
    		if(empty($rec->dueDate)){
    			$defTime = ($mvc instanceof purchase_Invoices) ? purchase_Setup::get('INVOICE_DEFAULT_VALID_FOR') : sales_Setup::get('INVOICE_DEFAULT_VALID_FOR');
    			$dueTime = (isset($rec->dueTime)) ? $rec->dueTime : $defTime;
    			
    			if($dueTime){
    				$dueDate = dt::verbal2mysql(dt::addSecs($dueTime, $rec->date), FALSE);
    				$row->dueDate = $mvc->getFieldType('dueDate')->toVerbal($dueDate);
    				if(!$rec->dueTime){
    					$time = cls::get('type_Time')->toVerbal($defTime);
    					$row->dueDate = ht::createHint($row->dueDate, "Според срока за плащане по подразбиране|*: {$time}");
    				}
    			}
    		}
    		
    		// Вербална обработка на данните на моята фирма и името на контрагента
    		$headerInfo = deals_Helper::getDocumentHeaderInfo($rec->contragentClassId, $rec->contragentId, $row->contragentName);
    		foreach (array('MyCompany', 'MyAddress', 'MyCompanyVatNo', 'uicId', 'contragentName') as $fld){
    			$row->{$fld} = $headerInfo[$fld];
    		}
    		
    		core_Lg::pop();
    	}
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$row = new stdClass();
    	$row->type = static::getVerbal($rec, 'type');
    	if($rec->type == 'dc_note'){
    		$row->type = ($rec->dealValue <= 0) ? 'Кредитно известие' : 'Дебитно известие';
    	}
    	
    	$row->number = str_pad($rec->number, '10', '0', STR_PAD_LEFT);
    	$num = ($row->number) ? $row->number : $rec->id;
    
    	return tr("|{$row->type}|* №{$num}");
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
    	$rec = $this->fetchRec($id);
    	$total = $rec->dealValue + $rec->vatAmount - $rec->discountAmount;
    	$total = ($rec->type == 'credit_note') ? -1 * $total : $total;
 
    	setIfNot($dueDate, $rec->dueDate, $rec->date);
    	
    	$aggregator->push('invoices', array('dueDate' => $dueDate, 'total' => $total));
    	$aggregator->sum('invoicedAmount', $total);
    	$aggregator->setIfNot('invoicedValior', $rec->date);
    	
    	if(isset($rec->dpAmount)){
    		if($rec->dpOperation == 'accrued'){
    			$aggregator->sum('downpaymentInvoiced', $total);
    		} elseif($rec->dpOperation == 'deducted') {
    			$vat = acc_Periods::fetchByDate($rec->date)->vatRate;
    			
    			// Колко е приспаднатото плащане с ддс
    			$deducted = abs($rec->dpAmount);
    			$vatAmount = ($rec->vatRate == 'yes' || $rec->vatRate == 'separate') ? ($deducted) * $vat : 0;
    			$aggregator->sum('downpaymentDeducted', $deducted + $vatAmount);
    		}
    	} else {
    		
    		// Ако е ДИ и КИ към ф-ра за начисляване на авансово плащане, променяме платения аванс по сделката
    		if($rec->type == 'dc_note'){
    			$originOperation = doc_Containers::getDocument($rec->originId)->fetchField('dpOperation');
    			if($originOperation == 'accrued'){
    				$aggregator->sum('downpaymentInvoiced', $total);
    			}
    		}
    	}
    
    	$Detail = $this->mainDetail;
    	
    	$dQuery = $Detail::getQuery();
    	$dQuery->where("#invoiceId = '{$rec->id}'");
    
    	// Намираме всички фактурирани досега продукти
    	$invoiced = $aggregator->get('invoicedProducts');
    	while ($dRec = $dQuery->fetch()) {
    		$p = new stdClass();
    		$p->productId   = $dRec->productId;
    		$p->packagingId = $dRec->packagingId;
    		$p->quantity    = $dRec->quantity * $dRec->quantityInPack;
    
    		// Добавяме към фактурираните продукти
    		$update = FALSE;
    		if(count($invoiced)){
    			foreach ($invoiced as &$inv){
    				if($inv->productId == $p->productId){
    					$inv->quantity += $p->quantity;
    					$update = TRUE;
    					break;
    				}
    			}
    		}
    		 
    		if(!$update){
    			$invoiced[] = $p;
    		}
    	}
    	
    	$aggregator->set('invoicedProducts', $invoiced);
    }
    
    
    /**
     * След подготовка на авансова ф-ра
     */
    public static function on_AfterPrepareDpInvoicePlg($mvc, &$res, &$data)
    {
    	
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputDpInvoice($mvc, &$res, &$form)
    {
    	
    }
    
    
    /**
     * Кешира информация за оригиналните стойностти на детайлите на известието
     */
    public function getInvoiceDetailedInfo($containerId)
    {
    	expect($document = doc_Containers::getDocument($containerId));
    	expect($document->isInstanceOf($this));
    	
    	if(!isset($this->cache[$containerId])){
    		$cache = $vats = array();
    		$Detail = $this->mainDetail;
    		$query = $Detail::getQuery();
    		$vatRate = $document->fetchField('vatRate');
    		$dpAmount = $document->fetch('dpAmount');
    		
    		$count = 0;
    		$query->where("#{$this->{$Detail}->masterKey} = '{$document->that}'");
    		while($dRec = $query->fetch()){
    			$cache[$count][$dRec->productId] = array('quantity' => $dRec->quantity, 'price' => $dRec->packPrice);
    			$count++;
    			
    			$vat = 0;
    			if($vatRate != 'no' && $vatRate != 'exempt'){
    				$v = cat_Products::getVat($dRec->productId, $document->fetchField('date'));
    			}
    			$vats[$v] = $v;
    		}
    		
    		if(!count($cache)){
    			if(isset($dpAmount)){
    				$v = ($vatRate == 'yes' && $vatRate != 'separate') ? 0.2 : 0; 
    				$vats["{$v}"] = $v;
    			}
    		}
    		
    		$this->cache[$containerId] = (object)array('recs' => $cache, 'vats' => $vats);
    	}
    	
    	return $this->cache[$containerId];
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	// Не може да се оттеглят документи, към които има създадени КИ и ДИ
    	if($action == 'reject' && isset($rec)){
    		if(!($mvc instanceof sales_Proformas)){
    			if($mvc->fetch("#originId = {$rec->containerId} AND #state = 'active'")){
    				$res = 'no_one';
    			}
    		}
    	}
    	
    	// Ако възстановяваме известие и оригиналът му е оттеглен, не можем да го възстановим
    	if($action == 'restore' && isset($rec)){
    		if(isset($rec->type) && $rec->type != 'invoice'){
    			if($mvc->fetch("#containerId = {$rec->originId} AND #state = 'rejected'")){
    				$res = 'no_one';
    			}
    		}
    	}
    	
    	// Към ф-ра не можем да правим корекция, трябва да направим КИ или ДИ
    	if($action == 'correction' && isset($rec)){
    		$res = 'no_one';
    	}
    	
    	// Може да се генерира фактура само в нишка с начало сделка, или от друга фактура
    	if($action == 'add' && isset($rec->originId)){
    		$origin = doc_Containers::getDocument($rec->originId);
    		$state = $origin->rec()->state;
    		if($state != 'active'){
    			$res = 'no_one';
    		} else {
    			if(!($origin->getInstance() instanceof deals_DealMaster || $origin->getInstance() instanceof deals_InvoiceMaster || $origin->getInstance() instanceof findeals_AdvanceReports || $origin->getInstance() instanceof sales_Proformas)){
    				$res = 'no_one';
    			}
    		}
    	}
    	
    	if($action == 'add' && isset($rec->sourceContainerId)){
    		$Source = doc_Containers::getDocument($rec->sourceContainerId);
    		if(!$Source->haveInterface('deals_InvoiceSourceIntf')){
    			$res = 'no_one';
    		} else {
    			$sourceState = $Source->fetchField('state');
    			if($Source->isInstanceOf('deals_InvoiceMaster')){
    				$boolRes = $sourceState != 'active';
    			} else {
    				$boolRes = $sourceState != 'active' && $sourceState != 'draft';
    			}
    			
    			if($boolRes){
    				$res = 'no_one';
    			}
    		}
    	}
    	
    	// Не може да се контира КИ и ДИ, ако оригиналната фактура е оттеглена
    	if($action == 'conto' && isset($rec)){
    		if($res != 'no_one'){
    			if($rec->type == 'dc_note'){
    				$origin = doc_Containers::getDocument($rec->originId);
    				if($origin->fetchField('state') == 'rejected'){
    					$res = 'no_one';
    				}
    			}
    		}
    	}
    }
    

    /**
     * Намира ориджина на фактурата (ако има)
     */
    public static function getOrigin($rec)
    {
    	$origin = NULL;
    	$rec = static::fetchRec($rec);
    
    	if($rec->originId) {
    		return doc_Containers::getDocument($rec->originId);
    	}
    
    	if($rec->threadId){
    		return doc_Threads::getFirstDocument($rec->threadId);
    	}
    	 
    	return $origin;
    }
    
    
    /**
     * Кой е източника на фактурата
     */
    public static function getSourceOrigin($rec)
    {
    	$rec = static::fetchRec($rec);
    	
    	if($rec->sourceContainerId) {
    		return doc_Containers::getDocument($rec->sourceContainerId);
    	}
    	
    	return static::getOrigin($rec);
    }
    
    
    /**
     * Артикули които да се заредят във фактурата/проформата, когато е създадена от
     * определен документ
     *
     * @param mixed $id - ид или запис на документа
     * @param deals_InvoiceMaster $forMvc - клас наследник на deals_InvoiceMaster в който ще наливаме детайлите
     * @return array $details - масив с артикули готови за запис
     * 				  o productId      - ид на артикул
     * 				  o packagingId    - ид на опаковка/основна мярка
     * 				  o quantity       - количество опаковка
     * 				  o quantityInPack - количество в опаковката
     * 				  o discount       - отстъпка
     * 				  o price          - цена за единица от основната мярка
     */
    public function getDetailsFromSource($id, deals_InvoiceMaster $forMvc)
    {
    	$details = array();
    	$rec = static::fetchRec($id);
    	
    	// Ако начисляваме аванс или има въведена нова стойност не се копират детайлите
    	if($rec->dpOperation == 'accrued') return $details;
    	
    	$Detail = cls::get($this->mainDetail);
    	$query = $Detail->getQuery();
    	$query->where("#{$Detail->masterKey} = '{$rec->id}'");
    	 
    	while($dRec = $query->fetch()){
    		unset($dRec->id);
    		unset($dRec->{$Detail->masterKey});
    		unset($dRec->createdOn);
    		unset($dRec->createdBy);
    		$details[] = $dRec;
    	}
    	
    	return $details;
    }

    
    /**
     * Преди рендиране на таблицата
     */
    public static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
    	if(!count($data->rows)) return;
    	$data->listTableMvc->FNC('valueNoVat', 'int');
    	
    	if(Mode::is('printing')){ 
    	    unset($data->pager);
    	}
    }
    
    
    /**
     * Оттегляне на документ
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param int|stdClass $id
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
    	$rec = $mvc->fetchRec($id);
    	doc_DocumentCache::invalidateByOriginId($rec->containerId);
    }
    
    
    /**
     * Възстановяване на оттеглен документ
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param int $id
     */
    public static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
    {
    	$rec = $mvc->fetchRec($id);
    	doc_DocumentCache::invalidateByOriginId($rec->containerId);
    }
}
