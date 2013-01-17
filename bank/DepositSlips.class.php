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
    var $interfaces = 'doc_DocumentIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Вносни Бележки";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, bank_Wrapper, bank_TemplateWrapper, plg_Printing,
     	plg_Sorting, doc_plg_BusinessDoc, doc_DocumentPlg,
     	plg_Search, doc_plg_MultiPrint, bgerp_plg_Blank';
    
    
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
    var $canConto = 'acc, bank';
    
    
    /**
     * Кой може да сторнира
     */
    var $canRevert = 'bank, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
     var $singleLayoutFile = 'bank/tpl/SingleDepositSlip.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'valior, reason, beneficiaryName';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума,mandatory,width=6em');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,width=6em');
    	$this->FLD('reason', 'varchar(255)', 'caption=Основание,width=100%,mandatory');
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,width=6em,mandatory');
    	$this->FLD('execBank', 'varchar(255)', 'caption=До->Банка,width=16em,mandatory');
    	$this->FLD('execBankBranch', 'varchar(255)', 'caption=До->Клон,width=16em');
        $this->FLD('execBankAdress', 'varchar(255)', 'caption=До->Адрес,width=16em');
    	$this->FLD('beneficiaryName', 'varchar(255)', 'caption=Получател->Име,mandatory,width=16em');
    	$this->FLD('beneficiaryIban', 'iban_Type', 'caption=Получател->IBAN,mandatory,width=16em');
    	$this->FLD('beneficiaryBank', 'varchar(255)', 'caption=Получател->Банка,width=16em');
    	$this->FLD('depositor', 'varchar(255)', 'caption=Вносител->Име,mandatory');
    	$this->FLD('originClassId', 'key(mvc=core_Classes,select=name)', 'input=none');
    }
    
    
    /**
     *  Обработка на формата за редакция и добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$form = &$data->form;
    	$originId = $form->rec->originId;
    	
    	//Извличаме дефолт информацията от последния запис в папката
    	$query = static::getQuery();
    	$query->where("#folderId = {$form->rec->folderId}");
    	if($form->rec->threadId) {
    		$query->where("#threadId = {$form->rec->threadId}");
    	}
    	$query->orderBy('createdOn', 'DESC');
    	$query->limit(1);
    	if($lastRec = $query->fetch()) {
    		$form->setDefault('beneficiaryIban', $lastRec->beneficiaryIban);
    		$form->setDefault('execBank', $lastRec->execBank);
    		$form->setDefault('execBankBranch', $lastRec->execBankBranch);
    		$form->setDefault('currencyId', $lastRec->currencyId);
    		$form->setDefault('execBankAdress', $lastRec->execBankAdress);
    		$form->setDefault('beneficiaryBank', $lastRec->beneficiaryBank);
    		$form->setDefault('depositor', $lastRec->depositor);
    	} 
    	
    	if($originId) {
    		
    		// Ако основанието е по банков документ намираме кой е той
    		$doc = doc_Containers::getDocument($originId);
    		$class = $doc->className;
    		$dId = $doc->that;
    		$rec = $class::fetch($dId);
    		$form->setDefault('originClassId', $class::getClassId());
    		
    		// Извличаме каквато информация можем от оригиналния документ
    		$form->setDefault('currencyId', $rec->currencyId);
    		$form->setDefault('amount', $rec->amount);
    		$form->setDefault('reason', $rec->reason);
    		$form->setDefault('valior', $rec->valior);

    		if($class == 'bank_IncomeDocument') {
    			
    			// Ако оригиналния документ е "Приходен банков документ", то
    			// бенефициента на вносната бележка е "Моята Фирма"
    			$myCompany = crm_Companies::fetchOwnCompany();
	    		$form->setDefault('beneficiaryName', $myCompany->company);
	    		$ownAccount = bank_OwnAccounts::getOwnAccountInfo($rec->ownAccount);
	    		$form->setDefault('beneficiaryIban', $ownAccount->iban);
	    		$form->setDefault('beneficiaryBank', $ownAccount->bank);
	    		
	    		// Ако контрагента е лице, слагаме името му за получател
	    		if($rec->contragentClassId != crm_Companies::getClassId()){
	    			$form->setDefault('depositor', $rec->contragentName);
	    		}
    		
    		} elseif($class == 'bank_CostDocument'){
    			$myCompany = crm_Companies::fetchOwnCompany();
	    		$form->setDefault('beneficiaryName', $rec->contragentName);
	    		$beneficiaryIbans = bank_Accounts::getContragentIbans($rec->contragentId,$rec->contragentClassId);
    			$form->setSuggestions('beneficiaryIban', $beneficiaryIbans);
	    	}
    		
    	} else {
    	
	    	// Поставяме стойности по подразбиране
	    	$today = dt::verbal2mysql();
	    	$form->setDefault('currencyId', currency_Currencies::getIdByCode());
	    	$form->setDefault('valior', $today);
	    	
	    	static::getContragentInfo($form);
    	}
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
    	$row->number = static::getHandle($rec->id);
    	
    	if($fields['-single']) {
    		$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" ;
    		$spellNumber = cls::get('core_SpellNumber');
			$row->sayWords = $spellNumber->asCurrency($rec->amount, 'bg', FALSE);
	        
	    	// При принтирането на 'Чернова' скриваме системите полета и заглавието
	    	if(Mode::is('printing')){
	    			unset($row->header);
	    	}
    	}
    }
    
    
    /**
     * Обработка след изпращане на формата
     */
	static function on_AfterInputEditForm($mvc, &$form)
	{
		if($form->isSubmitted()){
		    if(!$form->rec->beneficiaryBank){
				 $form->rec->beneficiaryBank = drdata_Banks::getBankName($form->rec->beneficiaryIban);
			}
		}
	 }
    
	 
	 /**
	  * Функция която скрива бланката с логото на моята фирма
	  * при принтиране ако документа е базиран на
	  * "приходен банков документ"
	  */
	 function renderSingleLayout_($data)
	 {
	 	$tpl = parent::renderSingleLayout_($data);
	 	if(Mode::is('printing')){
	 		
		 	if($data->row->originClassId == 'bank_IncomeDocument') {
		 		
		 		// скриваме логото на моята фирма
		 		$tpl->replace('','blank');
		 	}
	 	}
	 	
	 	return $tpl;
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
