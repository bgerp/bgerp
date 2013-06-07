<?php
/**
 * Клас 'sales_Quotations'
 *
 * Мениджър на документи за Оферта за продажба
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Quotations extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Оферти';


    /**
     * Абревиатура
     */
    var $abbr = 'Q';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'sales_Quotes';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, doc_ContragentDataIntf, email_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting, plg_Printing, doc_EmailCreatePlg,
                    doc_DocumentPlg, doc_ActivatePlg, bgerp_plg_Blank, doc_plg_BusinessDoc';
       
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin,sales';
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/document_quote.png';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'admin,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'admin,sales';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'admin,sales';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'admin,sales';
    
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, date, contragentName, deliveryTermId, createdOn,createdBy';
    

    /**
     * Детайла, на модела
     */
    public $details = 'sales_QuotationsDetails, others=sales_QuotationsOthers' ;
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Оферта';
    
    
   /**
     * Шаблон за еденичен изглед
     */
   var $singleLayoutFile = 'sales/tpl/SingleLayoutQuote.shtml';
   
   
   /**
     * Групиране на документите
     */ 
   var $newBtnGroup = "3.7|Търговия";
   
   
   /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('date', 'date', 'caption=Дата, mandatory,width=8em'); 
    	$this->FLD('contragentName', 'varchar(255)', 'caption=Клиент,mandatory,width=15em');
    	$this->FLD('receiver', 'key(mvc=crm_Persons, select=name)', 'caption=Получател');
        $this->FLD('reff', 'varchar(255)', 'caption=Ваш реф');
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        $this->FLD('paymentMethodId', 'key(mvc=salecond_PaymentMethods,select=name)','caption=Плащане->Метод,width=8em');
        $this->FLD('paymentCurrencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)','caption=Плащане->Валута,width=8em');
        $this->FLD('rate', 'double(decimals=2)', 'caption=Плащане->Курс,width=8em');
        $this->FLD('vat', 'enum(yes=с начисляване,freed=освободено,export=без начисляване)','caption=Плащане->ДДС,oldFieldName=wat');
        $this->FLD('deliveryTermId', 'key(mvc=salecond_DeliveryTerms,select=codeName)', 'caption=Доставка->Условие,width=8em');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
       $form = $data->form;
       $form->setDefault('date', dt::now());
       
       $mvc->populateContragentData($form);
    }
	
    
    /**
     * Извиква се след въвеждането на данните от Request във формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
	    	$rec = &$form->rec;
		    if(!$rec->rate){
			    $rec->rate = round(currency_CurrencyRates::getRate($rec->date, $rec->paymentCurrencyId, NULL), 4);
			}
		
	    	if(!currency_CurrencyRates::hasDeviation($rec->rate, $rec->date, $rec->paymentCurrencyId, NULL)){
			    $form->setWarning('rate', 'Изходната сума има голяма ралзика спрямо очакваното.
			    					  Сигурни ли сте че искате да запишете документа');
			}
		}
    }
    
    
    /**
     * Ако офертата е създадена към спецификация, попълваме
     * данните на спецификацията в детайлите
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	if(!empty($rec->originId)){
    		$origin = doc_Containers::getDocument($rec->originId);
    		if($origin->className == 'techno_Specifications'){
    			$mvc->sales_QuotationsDetails->insertFromSpecification($rec, $origin);
    		}
    	}
    }
    
    
    /**
     * Попълваме информацията за контрагента
     */
    private function populateContragentData(core_Form &$form)
    {
    	$rec = &$form->rec;
    	expect($data = doc_Folders::getContragentData($rec->folderId), "Проблем с данните за контрагент по подразбиране");
    	$contragentClassId = doc_Folders::fetchCoverClassId($rec->folderId);
    	$contragentId = doc_Folders::fetchCoverId($rec->folderId);
    	$form->setDefault('contragentClassId', $contragentClassId);
    	$form->setDefault('contragentId', $contragentId);
    	
    	if($data->person) {
    		$form->setDefault('contragentName', $data->person);
    		
    	} elseif ($data->company) {
    		$form->setDefault('contragentName', $data->company);
    	}
    	$form->setReadOnly('contragentName');
    	
    	if($data->countryId){
    		$currencyCode  = drdata_Countries::fetchField($data->countryId, 'currencyCode');
    	} else {
    		$currencyCode = acc_Periods::getBaseCurrencyCode($rec->date);
    	}
    	
    	if($contragentClassId == crm_Companies::getClassId()){
    		$options = array();
    		$personQuery = crm_Persons::getQuery();
    		$personQuery->where("#buzCompanyId = {$contragentId}");
    		while($pRec = $personQuery->fetch()){
    			$options[$pRec->id] = crm_Persons::recToVerbal($pRec, 'name')->name;
    		}
    	}
    	
    	(!count($options)) ? $form->setField('receiver', 'input=none') : $form->setOptions('receiver', $options);
    	
    	$form->setDefault('paymentCurrencyId', $currencyCode);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$varchar = cls::get('type_Varchar');
    	
    	if(!Mode::is('printing')){
    		$row->header = $mvc->singleTitle . " №<b>{$row->id}</b> ({$row->state})" ;
    	}
    	
    	$contragentData =  doc_Folders::getContragentData($rec->folderId);
    	
    	if($contragentData->person) {
    		$row->contragentAdress .= " {$contragentData->pAddress}";
    	}

    	if($contragentData->company) {
    		$row->contragentAdress .= " {$contragentData->address}";
    	}

    	$row->contragentAdress .= trim(sprintf(" <br />%s %s<br />%s",$contragentData->pCode, $contragentData->place, $contragentData->country));
    
    	$row->number = $mvc->getHandle($rec->id);
		
		$username = core_Users::fetch($rec->createdBy);
		$row->username = core_Users::recToVerbal($username, 'names')->names;
		
		if($rec->receiver){
			$personRec = crm_Persons::fetch($rec->receiver);
			$row->personPosition = crm_Persons::recToVerbal($personRec, 'buzPosition')->buzPosition;
		}
		
		switch($rec->vat){
			case 'yes':
				$row->vat = tr('с');
				break;
			case 'freed':
			case 'export':
				$row->vat = tr('без');
				break;
		}
		
		if($rec->rate == 1){
			unset($row->rate);
		}
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    static function getHandle($id)
    {
    	$rec = static::fetch($id);
    	$self = cls::get(get_called_class());
    	
    	return $self->abbr . $rec->id;
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = "Оферта №" .$this->abbr . $rec->id;
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $row->title;

        return $row;
    }
    
    
	/**
     * Вкарваме css файл за единичния изглед
     */
	static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$tpl->push('sales/tpl/styles.css', 'CSS');
    }
    
    
    /**
     * След проверка на ролите
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec, $userId)
    {
    	if($action == 'activate'){
    		if(!$rec->id) {
    			
    			// Ако документа се създава, то неможе да се активира
    			$res = 'no_one';
    		} else {
    			
    			// Ако няма задължителни продукти/услуги неможе да се активира
    			$detailQuery = sales_QuotationsDetails::getQuery();
    			$detailQuery->where("#quotationId = {$rec->id}");
    			$detailQuery->where("#optional = 'no'");
    			if(!$detailQuery->count()){
    				$res = 'no_one';
    			}
    			
    			if(!empty($rec->originId)){
    				
    				// Ако е базирана на спецификация и тя е чернова
    				// активирането е забранено
	    			$origin = doc_Containers::getDocument($rec->originId);
	    			if($origin->className == 'techno_Specifications' && $origin->fetchField('state') == 'draft'){
	    				$res = 'no_one';
	    			}
    			}
    		}
    	}
    }
    
    
	/**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашата оферта:") . '#[#handle#]');
        $tpl->append($handle, 'handle');
        return $tpl->getContent();
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
    	$threadRec = doc_Threads::fetch($threadId);
    	$coverClass = doc_Folders::fetchCoverClassName($threadRec->folderId);
    	
    	return cls::haveInterface('doc_ContragentDataIntf', $coverClass);
    }
    
    
	/**
     * Документи-оферти могат да се добавят само в папки с корица контрагент.
     */
    public static function canAddToFolder($folderId)
    {
        $coverClass = doc_Folders::fetchCoverClassName($folderId);
    
        return cls::haveInterface('doc_ContragentDataIntf', $coverClass);
    }
}