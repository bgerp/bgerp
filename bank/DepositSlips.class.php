<?php 


/**
 * Документ за Вносни Бележки
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_DepositSlips extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Вносни Бележки";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, bank_Wrapper, bank_DocumentWrapper, plg_Printing,
     	plg_Sorting, doc_plg_BusinessDoc,doc_DocumentPlg,
     	plg_Search,doc_plg_MultiPrint, bgerp_plg_Blank, acc_plg_Contable';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт, number=Номер, reason, valior, amount, currencyId, state, createdOn, createdBy";
    
    
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
    var $singleTitle = 'Вносна Бележка';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/money_add.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Vb";
    
    
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
    var $canConto = 'acc,bank';
    
    
    var $canRevert = 'bank, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
     var $singleLayoutFile = 'bank/tpl/SingleDepositSlip.shtml';
    
    
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
    	$this->FLD('execBank', 'varchar(255)', 'caption=До->Банка,width=16em');
    	$this->FLD('execBankBranch', 'varchar(255)', 'caption=До->Клон,width=16em');
        $this->FLD('execBankAdress', 'varchar(255)', 'caption=До->Адрес,width=16em');
    	$this->FLD('beneficiaryName', 'varchar(255)', 'caption=Получател->Име,mandatory,width=16em');
    	$this->FLD('beneficiaryIban', 'iban_Type', 'caption=Получател->IBAN,mandatory,width=16em');
    	$this->FLD('debitAccount', 'acc_type_Account(maxColumns=1)', 'caption=Получател->Сметка,mandatory');
    	$this->FLD('beneficiaryBank', 'varchar(255)', 'caption=Получател->Банка,mandatory,width=16em');
        $this->FLD('depositorName', 'varchar(255)', 'caption=Вносител->Име,mandatory');
    	$this->FLD('peroCase', 'key(mvc=cash_Cases,select=name)', 'input=hidden');
    	$this->FLD('beneficiaryId', 'int', 'input=hidden,notNull');
    	$this->FLD('beneficiaryClassId', 'key(mvc=core_Classes,select=name)', 'input=hidden,notNull');
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
        $this->FNC('isContable', 'int', 'column=none');
    	 
        // Поставяне на уникален индекс
    	$this->setDbUnique('number');
    }
    
    
	/**
     * @todo Чака за документация...
     */
    static function on_CalcIsContable($mvc, $rec)
    {
        $rec->isContable =
        ($rec->state == 'draft');
    }
    
    
    /**
     *  Обработка на формата за редакция и добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	// Поставяме стойности по подразбиране
    	$today = date("d-m-Y", time());
    	$data->form->setDefault('valior', $today);
    	$data->form->setDefault('peroCase', cash_Cases::getCurrent());
    	$data->form->setOptions('depositorName', static::getAccountableItems());
    	
    	static::getContragentInfo($data->form);
    	static::getPossibleAccounts($data->form);
    }
    
    
    /**
     * Списък от подочетни лице + текущя потребител (bank) които могат да 
     * внасят в сметката на контрагента
     */
    static function getAccountableItems()
    {
    	$suggestions = array();
    	$cu = core_Users::getCurrent();
    	$cuRow = core_Users::recToVerbal($cu);
    	
    	$suggestions[$cuRow->names] = $cuRow->names;
    	$list = acc_Lists::fetchBySystemId('accountableInd');
    	$itemsQuery = acc_Items::getQuery();
    	$itemsQuery->where("#lists LIKE '%{$list->id}%'");
    	while($itemRec = $itemsQuery->fetch()) {
    		$personRec = crm_Persons::fetch($itemRec->objectId);
    		$suggestions[$personRec->name] = $personRec->name;
    	}
    	
    	return $suggestions;
    }
    
    
    /**
     *  Попълва формата с информацията за контрагента извлечена от папката
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
    		$form->setReadOnly('beneficiaryName');
    	}
    	
    	$options = bank_Accounts::getContragentIbans($contragentId, $contragentClassId);
	    $form->setSuggestions('beneficiaryIban', $options);
    
    	$query = static::getQuery();
    	$query->where("#folderId = {$folderId}");
    	$query->orderBy('createdOn', 'DESC');
    	$query->limit(1);
    	if($lastRec = $query->fetch()) {
    		$form->setDefault('beneficiaryIban', $lastRec->beneficiaryIban);
    		$form->setDefault('execBank', $lastRec->execBank);
    		$form->setDefault('execBankBranch', $lastRec->execBankBranch);
    		$form->setDefault('currencyId', $lastRec->currencyId);
    		$form->setDefault('execBankAdress', $lastRec->execBankAdress);
    		$form->setDefault('depositorName', $lastRec->depositorName);
    	} 
    }
    
    
    /**
     * Попълва формата със сметките които можем да дебитираме
     */
 	static function getPossibleAccounts(core_Form $form)
    {
    	$options = array();
    	$conf = core_Packs::getConfig('bank');
    	$array = type_Keylist::toArray($conf->BANK_VB_DEBIT_ACC);
    	foreach($array as $id) {
    		$rec = acc_Accounts::fetch($id);
    		$options[$rec->id] = acc_Accounts::getRecTitle($rec);
    	}
    	
    	$form->setOptions('debitAccount', $options);
    }
    
    
 	/**
     * Обработка след като формата е събмитната
     */
    function on_AfterInputEditForm($mvc, $form)
    {
    	if ($form->isSubmitted()){
    		$contragentId = doc_Folders::fetchCoverId($form->rec->folderId);
    		$contragentClassId = doc_Folders::fetchField($form->rec->folderId, 'coverClass');
    		$form->rec->beneficiaryId = $contragentId;
    		$form->rec->beneficiaryClassId = $contragentClassId;
    		$accRec = bank_Accounts::fetch("#iban = '{$form->rec->beneficiaryIban}'");
    		$form->rec->beneficiaryBank = $accRec->bank;
    	}
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
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
    	$row->number = static::getHandle($rec->id);
    	$conf = core_Packs::getConfig('crm');
    	$myCompany = crm_Companies::fetch($conf->BGERP_OWN_COMPANY_ID);
    	$row->orderer = $myCompany->name;
    	
    	$spellNumber = cls::get('core_SpellNumber');
		$row->sayWords = $spellNumber->asCurrency($rec->amount, 'bg', FALSE);
        
		$conf = core_Packs::getConfig('bank');
    	$debitRec = acc_Accounts::fetch($rec->debitAccount);
    	$row->debitAccount = acc_Accounts::getRecTitle($debitRec);
    	
    	$creditRec = acc_Accounts::fetch("#systemId = {$conf->BANK_VB_CREDIT_SYSID}");
    	$row->creditAccount = acc_Accounts::getRecTitle($creditRec);
    	
    	// При принтирането на 'Чернова' скриваме системите полета и заглавието
    	if(Mode::is('printing')){
    		if($rec->state == 'draft') {
    			unset($row->header);
    			unset($row->createdBy);
    			unset($row->createdOn);
    			unset($row->debitAccount);
    			unset($row->creditAccount);
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
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    static function getHandle($id)
    {
    	$rec = static::fetch($id);
    	$self = cls::get(get_called_class());
    	
    	return $self->abbr . $rec->id;
    }
}