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
    var $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Платежни нареждания";
    
    
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
    var $canConto = 'acc,bank';
    
    
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
    var $searchFields = 'valior, contragentName';
    
    
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
    	$this->FLD('ownAccount', 'key(mvc=bank_OwnAccounts,select=bankAccountId)', 'caption=От сметка->IBAN,mandatory,width=16em');
    	$this->FLD('execBank', 'varchar(255)', 'caption=От сметка->Банка,mandatory,width=16em');
        $this->FNC('accCurrency', 'varchar', 'caption=От сметка->Валута,width=6em,input');
    	$this->FLD('execBankBic', 'varchar(255)', 'caption=От сметка->Bic,width=6em,input=hidden');
    	$this->FLD('execBranch', 'varchar(255)', 'caption=От сметка->Клон,width=16em');
        $this->FLD('execBranchAddress', 'varchar(255)', 'caption=От сметка->Адрес,width=16em');
        $this->FLD('contragentName', 'varchar(255)', 'caption=Получател->Име,mandatory,width=16em');
    	$this->FLD('contragentIban', 'iban_Type', 'caption=Получател->IBAN,mandatory,width=16em');
        $this->FLD('debitAccount', 'acc_type_Account(maxColumns=1)', 'caption=Получател->Сч. сметка,mandatory');
    	$this->FLD('contragentId', 'int', 'input=hidden,notNull');
    	$this->FLD('contragentBank', 'varchar(255)', 'caption=Получател->Банка,input=hidden');
    	$this->FLD('contragentBankBic', 'varchar(255)', 'caption=Получател->Bic,input=hidden');
        $this->FLD('contragentClassId', 'key(mvc=core_Classes,select=name)', 'input=hidden,notNull');
        $this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
        $this->FNC('isContable', 'int', 'column=none');
    	
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
    	static::setDefaults($data->form);
    	static::getContragentInfo($data->form);
    	static::getOwnAccountBankInfo($data->form);
    	static::getPossibleAccounts($data->form);
    	
    	
    }
    
    
    /**
     * Попълваме стойностите по-подразбиране взети от последния документ
     * от същия тип в папката
     */
    function setDefaults(core_Form $form)
    {
    	$query = static::getQuery();
    	$query->where("#folderId = {$form->rec->folderId}");
    	$query->orderBy('createdOn', 'DESC');
    	$query->limit(1);
    	if($lastRec = $query->fetch()) {
    		$form->setDefault('execBank', $lastRec->execBank);
    		$form->setDefault('execBranch', $lastRec->execBranch);
    		$form->setDefault('currencyId', $lastRec->currencyId);
    		$form->setDefault('execBranchAddress', $lastRec->execBranchAddress);
    		$form->setDefault('ownAccount', $lastRec->ownAccount);
    		$form->setDefault('contragentIban', $lastRec->contragentIban);
    	}

    	// Поставяме стойности по подразбиране
    	$today = date("d-m-Y", time());
        $form->setDefault('currencyId', currency_Currencies::getIdByCode());
    	$form->setDefault('valior', $today);
    	$form->setDefault('ownAccount', bank_OwnAccounts::getCurrent());
    	$form->setReadOnly('ownAccount');
    }
    
    
    /**
     * Попълва формата със 
     * Списък от Сч.сметки които можем да дебитираме 
     */
    function getPossibleAccounts(core_Form $form)
    {
    	$options = array();
    	$conf = core_Packs::getConfig('bank');
    	$array = type_Keylist::toArray($conf->BANK_PO_DEBIT_ACC);
    	foreach($array as $id) {
    		$rec = acc_Accounts::fetch($id);
    		$options[$rec->id] = acc_Accounts::getRecTitle($rec);
    	}
    	
    	$form->setOptions('debitAccount', $options);
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
    			
    			$form->setDefault('contragentName', $contragentData->company);
    		} elseif ($contragentData->name) {
    			
    			// Ако папката е на лице, то вносителя по дефолт е лицето
    			$form->setDefault('contragentName', $contragentData->name);
    		}
    		$form->setReadOnly('contragentName');
    	}
    	
    	$options = bank_Accounts::getContragentIbans($contragentId, $contragentClassId);
	    $form->setSuggestions('contragentIban', $options);
    }
    
    
    /**
     *  Попълва формата с
     *  Информацията за текущия банков акаунт и неговата банка
     */
    static function getOwnAccountBankInfo(core_Form $form)
    {
    	$rec = bank_OwnAccounts::fetch($form->rec->ownAccount);
    	$accRec = bank_Accounts::fetch($rec->bankAccountId);
    	$form->setDefault('execBank', $accRec->bank);
    	$form->setDefault('execBankBic', $accRec->bic);
    	if($accRec->bic) {
    		$form->setDefault('execBankBic', $accRec->bic);
    	}
    	$accCode = currency_Currencies::fetchField($accRec->currencyId, 'code');
    	$accCode = $form->getField('accCurrency')->type->toVerbal($accCode);
    	$form->setDefault('accCurrency', $accCode);
    	$form->setReadOnly('execBank');
    	$form->setReadOnly('accCurrency');
    }
    
    
    /**
     * Обработка след като формата е събмитната
     */
    function on_AfterInputEditForm($mvc, $form)
    {
    	if ($form->isSubmitted()){
    		$contragentId = doc_Folders::fetchCoverId($form->rec->folderId);
    		$contragentClassId = doc_Folders::fetchField($form->rec->folderId, 'coverClass');
    		$contragentIban = $form->rec->contragentIban;
    		$form->rec->contragentId = $contragentId;
    		$form->rec->contragentClassId = $contragentClassId;
    		if(!$form->rec->contragentBank) {
    			$form->rec->contragentBank = drdata_Banks::getBankName($contragentIban);
    		}
    		if(!$form->rec->contragentBankBic) {
    			$form->rec->contragentBankBic = drdata_Banks::getBankBic($contragentIban);
    		}
    	}
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
    	
    	$debitRec = acc_Accounts::fetch($rec->debitAccount);
    	$row->debitAccount = acc_Accounts::getRecTitle($debitRec);
    	$conf = core_Packs::getConfig('bank');
    	
    	$creditRec = acc_Accounts::fetch("#systemId = {$conf->BANK_PO_CREDIT_SYSID}");
    	$row->creditAccount = acc_Accounts::getRecTitle($creditRec);
    	
    	// Временно решение за рендирането на знака # пред iban-a ако го има
    	$row->contragentIban = $rec->contragentIban;
    	
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
   	 *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
   	 *  Създава транзакция която се записва в Журнала, при контирането
   	 *  @TODO правилно изчисляване на цената на кредита
   	 */
    public static function getTransaction($id)
    {
    	// Извличаме записа
        expect($rec = self::fetch($id));
        $conf = core_Packs::getConfig('bank');
        $cAcc = new acc_journal_Account($conf->BANK_PO_CREDIT_SYSID);
        $dAcc = acc_journal_Account::byId($rec->debitAccount);
        
        // Курса по който се обменя валутата  на ордера към основната валута за периода
        $entrAmount = $rec->amount; 
        
        // Намираме класа на контрагента
        $contragentId = doc_Folders::fetchCoverId($rec->folderId);
        $contragentClass = doc_Folders::fetchCoverClassName($rec->folderId);
    	
        // Подготвяме информацията която ще записваме в Журнала
        $result = (object)array(
            'reason' => $rec->reason, // основанието за ордера
            'valior' => $rec->valior,   // датата на ордера
            'totalAmount' => $entrAmount,
            'entries' =>array( (object)array(
                'amount' => $rec->amount,	// равностойноста на сумата в основната валута
                
                'debitAcc' => $dAcc->systemId, // дебитната сметка
                'debitItem1' => (object)array('cls'=>$contragentClass, 'id'=>$contragentId),  // перо каса
        		'debitItem2' => (object)array('cls'=>'currency_Currencies', 'id'=>$rec->currencyId),// перо валута
                'debitQuantity' => $rec->amount,
                'debitPrice' => 1,
        		
                'creditAccId' => $cAcc->id, // кредитна сметка
                'creditItem1' => (object)array('cls'=>'bank_OwnAccounts', 'id'=>$rec->ownAccount), // перо контрагент
                'creditQuantity' => $rec->amount,
                'creditPrice' => 1,
            ))
        );
       
        // Ако дебитната сметка няма втора аналитичност, премахваме
        // втория елемент
        $dAcc = acc_journal_Account::byId($rec->debitAccount);
    	if(!$dAcc->groupId2){
        	unset($result->entries[0]->debitItem2);
        }
        
        return $result;
    }
    
    
	/**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public static function finalizeTransaction($id)
    {
        $rec = (object)array(
            'id' => $id,
            'state' => 'active'
        );
        
        return self::save($rec);
    }
    
    
    /**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::rejectTransaction
     */
    public static function rejectTransaction($id)
    {
        $rec = self::fetch($id, 'id,state,valior');
        
        if ($rec) {
            static::reject($id);
        }
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