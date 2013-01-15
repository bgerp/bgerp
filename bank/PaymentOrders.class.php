<?php 


/**
 * Документ за Платежно Нареждане
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_PaymentOrders extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Платежни нареждания";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, bank_Wrapper, bank_TemplateWrapper, plg_Printing,
     	plg_Sorting, doc_plg_BusinessDoc,doc_DocumentPlg,doc_plg_MultiPrint, bgerp_plg_Blank';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт, number=Номер, reason, valior, amount, currencyId, createdOn, createdBy";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'reason';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Платежно Нареждане';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/money_add.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Pn";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'bank, ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'bank, ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'bank, ceo';
    
    
    /**
     * Кой може да го контира?
     */
    var $canConto = 'acc, bank';
    
    
    /**
     * Кой може да сторнира
     */
    var $canRevert = 'bank, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'bank/tpl/SinglePaymentOrder.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'valior, beneficiaryName';
    
    
    /**
     * Параметри за принтиране
     */
    var $printParams = array( array('Оригинал'),
    						  array('Копие'),); 

    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума,mandatory,width=6em');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,width=6em');
    	$this->FLD('reason', 'varchar(255)', 'caption=Основание,width=100%,mandatory');
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,width=6em,mandatory');
    	$this->FLD('moreReason', 'text(rows=2)', 'caption=Допълнително,width=100%');
    	$this->FLD('paymentSystem', 'enum(bisera=БИСЕРА,rings=РИНГС)','caption=Пл. система,default=bisera,width=6em');
    	$this->FLD('orderer', 'varchar(255)', 'caption=Наредител->Име,mandatory,width=16em');
    	$this->FLD('ordererIban', 'iban_Type', 'caption=Наредител->Б. Сметка,mandatory,width=16em');
    	$this->FNC('accCurrency', 'varchar', 'caption=Наредител->Валута,width=6em,input');
    	$this->FLD('execBank', 'varchar(255)', 'caption=Наредител->Банка,width=16em');
    	$this->FLD('execBranch', 'varchar(255)', 'caption=Наредител->Клон,width=16em');
        $this->FLD('execBranchAddress', 'varchar(255)', 'caption=Наредител->Адрес,width=16em');
        $this->FLD('beneficiaryName', 'varchar(255)', 'caption=Получател->Име,mandatory,width=16em');
    	$this->FLD('beneficiaryIban', 'iban_Type', 'caption=Получател->IBAN,mandatory,width=16em');
     }
    
    
    /**
     *  Обработка на формата за редакция и добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$form = &$data->form;
    	$originId = Request::get('originId');
    	
    	$query = static::getQuery();
    	$query->where("#folderId = {$form->rec->folderId}");
    	$query->orderBy('createdOn', 'DESC');
    	$query->limit(1);
    	if($lastRec = $query->fetch()) {
    		$form->setDefault('execBranch', $lastRec->execBranch);
    		$form->setDefault('currencyId', $lastRec->currencyId);
    		$form->setDefault('execBank', $lastRec->execBank);
    		$form->setDefault('execBranch', $lastRec->execBranch);
    		$form->setDefault('execBranchAddress', $lastRec->execBranchAddress);
    		$form->setDefault('ordererIban', $lastRec->ordererIban);
    		$form->setDefault('beneficiaryName', $lastRec->beneficiaryName);
    		$form->setDefault('beneficiaryIban', $lastRec->beneficiaryIban);
    	} else {
    		$form->setDefault('currencyId', currency_Currencies::getIdByCode());
	    }
	    
    	if($originId) {
    		$doc = doc_Containers::getDocument($originId);
    		$class = $doc->className;
    		$dId = $doc->that;
    		$rec = $class::fetch($dId);
    		
    		$form->setDefault('currencyId', $rec->currencyId);
    		$form->setDefault('amount', $rec->amount);
    		$form->setDefault('reason', $rec->reason);
    		$form->setDefault('valior', $rec->valior);
    		
    		if($class == 'bank_IncomeDocument') {
    			
    			// Ако оригиналния документ е приходен, наредителя е контрагента
    			// а получателя е моята фирма
    			$myCompany = crm_Companies::fetchOwnCompany();
    			$form->setDefault('beneficiaryName', $myCompany->company);
    			$ownAcc = bank_OwnAccounts::getOwnAccountInfo($rec->ownAccount);
    			$form->setDefault('beneficiaryIban', $ownAcc->iban);
    			$form->setDefault('orderer', $rec->contragentName);
    			$orderIbans = bank_Accounts::getContragentIbans($rec->contragentId,$rec->contragentClassId);
    			$form->setSuggestions('ordererIban', $orderIbans);
    			
    			
    		} else {
    			// Ако оригиналния документ е приходен, наредителя е моята фирма
    			// а получателя е контрагента
    			//@TODO 
    		}
    		$accCode = currency_Currencies::getCodeById($rec->currencyId);
    		$accCode = $form->getField('accCurrency')->type->toVerbal($accCode);
    		$form->setDefault('accCurrency', $accCode);
    		$form->setReadOnly('accCurrency');
    		
    	} else {
    		//static::getOwnAccountBankInfo($data->form);
    		
    		
    		// Поставяме стойности по подразбиране
	    	$today = dt::verbal2mysql();
	    	$form->setDefault('valior', $today);
	    	//$form->setReadOnly('ownAccount');
    	}
    	
    	static::getContragentInfo($data->form);
    }
    
    
    /**
     * 
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		if(!$form->rec->execBank){
		    		$form->rec->execBank = drdata_Banks::getBankName($form->rec->ordererIban);
		    	}
    	}
    }
    
    
    /**
     *  Попълва формата с
     *  Информацията за контрагента извлечена от папката
     */
    static function getContragentInfo(core_Form $form)
    {
    	$folderId = $form->rec->folderId;
    	$contragentId = doc_Folders::fetchCoverId($folderId);
    	$contragentClassId = doc_Folders::fetchField($folderId, 'coverClass');
    	
   		// Информацията за контрагента на папката
    	expect($contragentData = doc_Folders::getContragentData($folderId), "Проблем с данните за контрагент по подразбиране");
    	
    	if($contragentData) {
    		if($contragentData->company) {
    			
    			$form->setDefault('beneficiaryName', $contragentData->company);
    		} elseif ($contragentData->name) {
    			
    			// Ако папката е на лице, то вносителя по дефолт е лицето
    			$form->setDefault('beneficiaryName', $contragentData->name);
    		}
    	}
    	
    	$options = bank_Accounts::getContragentIbans($contragentId, $contragentClassId);
	   // $form->setSuggestions('beneficiaryIban', $options);
    }
    
    
    /**
     *  Попълва формата с
     *  Информацията за текущия банков акаунт и неговата банка
     */
    static function getOwnAccountBankInfo(core_Form $form)
    {
    	$rec = bank_OwnAccounts::fetch($form->rec->ownAccount);
    	$accRec = bank_Accounts::fetch($rec->bankAccountId);
    	$accCode = currency_Currencies::fetchField($accRec->currencyId, 'code');
    	$accCode = $form->getField('accCurrency')->type->toVerbal($accCode);
    	$form->setDefault('accCurrency', $accCode);
    	$form->setReadOnly('accCurrency');
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->number = static::getHandle($rec->id);
    	
    	if($fields['-single']) {
    		
    		$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
	    	
	    	$myCompany = crm_Companies::fetchOwnCompany();
	    	$row->orderer = $myCompany->company;
	    	
	    	// Извличаме името на банката и BIC-а  от нашата сметка
	    	//$row->execBank = drdata_Banks::getBankName($row->account);
	    	//$row->execBankBic = drdata_Banks::getBankBic($row->account);
	    	
	    	// Временно решение за рендирането на знака # пред iban-a ако го има
	    	$row->beneficiaryIban = $rec->beneficiaryIban;
	    	
	    	// Извличаме името на банката и BIC-а на получателя от IBAN-а му
	    	$row->contragentBank = drdata_Banks::getBankName($rec->beneficiaryIban);
	    	$row->contragentBankBic = drdata_Banks::getBankBic($rec->beneficiaryIban);
	    	
	    	// При принтирането на 'Чернова' скриваме системните полета и заглавието
	    	if(Mode::is('printing')){
	    			unset($row->header);
	    	}
	    }
    }
    
    
    /**
     * Вкарваме css файл за единичния изглед
     */
	static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$tpl->push('bank/tpl/css/belejka.css', 'CSS');
    }
    
    
    /*
     * Реализация на интерфейса doc_DocumentIntf
     */
    
    
 	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = $rec->reason;
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        return $row;
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     * @param $firstClass string класът на корицата на папката
     */
    public static function canAddToFolder($folderId, $folderClass)
    {
        if (empty($folderClass)) {
            $folderClass = doc_Folders::fetchCoverClassName($folderId);
        }
    
        return $folderClass == 'crm_Companies' || $folderClass == 'crm_Persons';
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
    
}