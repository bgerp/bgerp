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
    public $interfaces = 'doc_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting, plg_Printing,
                    doc_DocumentPlg, doc_ActivatePlg, bgerp_plg_Blank, doc_plg_BusinessDoc';
       
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin,sales';
    
    
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
    public $listFields = 'tools=Пулт, number=Номер, reff, date, contragentName, deliveryTermId, createdOn,createdBy';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'number';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';


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
        $this->FLD('reff', 'varchar(255)', 'caption=Ваш реф');
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        $this->FLD('paymentMethodId', 'key(mvc=salecond_PaymentMethods,select=name)','caption=Плащане->Метод,width=8em');
        $this->FLD('paymentCurrencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)','caption=Плащане->Валута,width=8em');
        $this->FLD('wat', 'enum(yes=с начисляване,no=без начисляване)','caption=Плащане->ДДС');
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
       ($mvc->getDefaultWat($form->rec->contragentClassId, $form->rec->contragentId)) ? $form->setDefault('wat', 'yes') : $form->setDefault('wat', 'no');
    }
	
    
    /**
     * Дали да се начислява ДДС на контрагента
     * Начисляваме ДДС в следния ред:
     * 1. Ако контрагента е лице - винаги
     * 2. Ако контрагента е българска фирма - винаги
     * 3. Ако фирмата има "BG" в wat номера си - винаги
     * 4. Ако фирмата не е българска и няма данъчен номер - винаги
     * 5. Ако никое не е изпълнено - не начисляваме ДДС
     * 
     * @param int $contragentClassId - ид на класа на контрагента
     * @param false $contragentId - ид на контрагента
     * @return boolean TRUE/FALSE - начислява ли се или не ДДС
     */
    function getDefaultWat($contragentClassId, $contragentId)
    {
    	// Ако контрагента е лице, начисляваме ДДС
    	if($contragentClassId == crm_Persons::getClassId()) return TRUE;
    	
    	$contragentClass = cls::get($contragentClassId);
    	$data = $contragentClass::getContragentData($contragentId);
    	$conf = core_Packs::getConfig('crm');
    	$ownCountryId = drdata_Countries::fetchField("#commonName = '{$conf->BGERP_OWN_COMPANY_COUNTRY}'", 'id');
    	
    	// за всички BG фирми начисляваме ДДС
    	if($ownCountryId == $data->countryId) return TRUE;
    	
    	// Всички фирми имащи BG в Ват номера си
    	if($data->vatNo && (preg_match("/^BG/", $data->vatNo))) return TRUE;
    	
    	// за всички чуждестранни фирми с липсващ/невалиден wat номер
    	if($ownCountryId != $data->countryId && !$data->vatNo) return TRUE;
    	
    	// Ако никое не е изпълнено не начисляваме ДДС
    	return FALSE;
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
    	$row->contragentAdress = trim(sprintf("%s %s\n%s", $contragentData->place, $contragentData->pCode, $contragentData->country));
    	if($contragentData->person) {
    		$row->contragentAdress .= " {$contragentData->pAddress}";
    		$row->email =  $varchar->toVerbal($contragentData->pEmail);
    		$row->fax =  $varchar->toVerbal($contragentData->pFax);
    		$row->tel =  $varchar->toVerbal($contragentData->pTel);
    	}

    	if($contragentData->company) {
    		$row->contragentAdress .= " {$contragentData->address}";
    		$row->email =  $varchar->toVerbal($contragentData->email);
    		$row->fax =  $varchar->toVerbal($contragentData->fax);
    		$row->tel =  $varchar->toVerbal($contragentData->tel);
    	}
    	$row->contragentAdress = $varchar->toVerbal($row->contragentAdress);
    	$row->number = $mvc->getHandle($rec->id);
		if($fields['-list']){
			$row->number = ht::createLink($row->number, array($mvc, 'single', $rec->id));
		}
		
		$cuRec = core_Users::fetch(core_Users::getCurrent());
		$row->username = core_Users::recToVerbal($cuRec, 'names')->names;
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
        $row->title = $this->abbr . $rec->id;
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;

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
    		}
    	}
    }
}