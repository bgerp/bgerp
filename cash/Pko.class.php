<?php



/**
 * Документ за Приходни Касови ордери
 *
 *
 * @category  bgerp
 * @package   cash
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cash_Pko extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Приходни Kасови Oрдери";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, cash_Wrapper, plg_Sorting, doc_plg_BusinessDoc,
                     doc_DocumentPlg, plg_Printing, doc_SequencerPlg,
                     plg_Search,doc_plg_MultiPrint, bgerp_plg_Blank, acc_plg_Contable';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "id, number, reason, valior, amount, currencyId, rate, state, createdOn, createdBy";
    
    
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
    var $singleTitle = 'Приходен Касов Ордер';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/money_add.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Pko";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'cash, ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'cash, ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'cash, ceo';
    
    
    /**
     * Кой може да го контира?
     */
    var $canConto = 'acc,admin';
    
    
    var $canRevert = 'cash, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'cash/tpl/Pko.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'number, valior, contragentName';
    
    
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
    	$this->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума,mandatory,width=30%');
    	$this->FLD('reason', 'varchar(255)', 'caption=Основание,width=100%,mandatory');
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,mandatory,width=30%');
    	$this->FLD('number', 'int', 'caption=Номер,width=50%,width=30%');
    	$this->FLD('contragentName', 'varchar(255)', 'caption=Контрагент->Вносител,mandatory,width=100%');
    	$this->FLD('contragentId', 'int', 'input=hidden,notNull');
    	$this->FLD('contragentClassId', 'key(mvc=core_Classes,select=name)', 'input=hidden,notNull');
    	$this->FLD('contragentAdress', 'varchar(255)', 'input=hidden');
        $this->FLD('contragentPlace', 'varchar(255)', 'input=hidden');
        $this->FLD('contragentPcode', 'varchar(255)', 'input=hidden');
        $this->FLD('contragentCountry', 'varchar(255)', 'input=hidden');
    	$this->FLD('depositor', 'varchar(255)', 'caption=Контрагент->Броил,mandatory');
    	$this->FLD('creditAccounts', 'acc_type_Account(maxColumns=1)', 'caption=Контрагент->Сметка,mandatory');
    	$this->FLD('peroCase', 'key(mvc=cash_Cases,select=name)', 'caption=Каса,input=hidden');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута->Код,width=6em');
    	$this->FLD('equals', 'int', 'caption=Валута->Равностойност,input=none');
    	$this->FLD('baseCurrency', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута->Основна,input=hidden');
    	$this->FLD('rate', 'double(decimals=2)', 'caption=Валута->Курс,width=6em');
    	$this->FLD('notes', 'richtext(rows=6)', 'caption=Допълнително->Бележки');
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
    	$folderId = $data->form->rec->folderId;
    	
    	// Информацията за контрагента на папката
    	expect($contragentData = doc_Folders::getContragentData($folderId), "Проблем с данните за контрагент по подразбиране");
    	
    	if($contragentData) {
    		if($contragentData->company) {
    			
    			$data->form->setDefault('contragentName', $contragentData->company);
    		} elseif ($contragentData->name) {
    			
    			// Ако папката е на лице, то вносителя по дефолт е лицето
    			$data->form->setDefault('contragentName', $contragentData->name);
    			$data->form->setDefault('depositor', $contragentData->name);
    		}
    		$data->form->setReadOnly('contragentName');
    	} 

    	if($originId = $data->form->rec->originId) {
    		 $doc = doc_Containers::getDocument($originId);
    		 $data->form->setDefault('reason', "Към документ #{$doc->getHandle()}");
    	}
    	
    	$query = static::getQuery();
    	$query->where("#folderId = {$folderId}");
    	$query->orderBy('createdOn', 'DESC');
    	$query->limit(1);
    	
    	if($lastRec = $query->fetch()) {
    		$data->form->setDefault('depositor', $lastRec->depositor);
    		$currencyId = $lastRec->currencyId;
    	} else {
    		$currencyId = currency_Currencies::getIdByCode();
    	}
    	
    	$today = date("d-m-Y", time());
    	
    	// Поставяме стойности по подразбиране
    	$data->form->setDefault('valior', $today);
    	$data->form->setDefault('currencyId', $currencyId);
    	
    	$options = static::getPossibleAccs(); 
    	$data->form->setOptions('creditAccounts', $options);
    }

    
    /**
     * Между кои сметки можем да избираме да запишем контрагента
     * @return array $options между кои сметки можем да избираме
     **/
    static function getPossibleAccs()
    {
    	$options = array();
    	$conf = core_Packs::getConfig('cash');
    	$array = type_Keylist::toArray($conf->CASH_PKO_CREDIT_ACC);
    	foreach($array as $id) {
    		$rec = acc_Accounts::fetch($id);
    		$options[$rec->id] = acc_Accounts::getRecTitle($rec);
    	}
    	
    	return $options;
    }

    
    /**
     * Проверка и валидиране на формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
    	if ($form->isSubmitted()){
    		
	    	$form->rec->contragentClassId = doc_Folders::fetchField($form->rec->folderId, 'coverClass');
	        $form->rec->contragentId = doc_Folders::fetchCoverId($form->rec->folderId);
	    	$contragentData = doc_Folders::getContragentData($form->rec->folderId);
	    	$form->rec->contragentCountry = $contragentData->country;
	    	$form->rec->contragentPcode = $contragentData->pCode;
	    	$form->rec->contragentPlace = $contragentData->place;
	    	$form->rec->contragentAdress = $contragentData->adress;
	    	$form->rec->peroCase = cash_Cases::getCurrent();
	    	
	    	// Взема периода за който се отнася документа, според датата му
	    	$accPeriods = cls::get('acc_Periods');
		    $period = $accPeriods->fetchByDate($rec->valior);
	    	if(!$period->baseCurrencyId){
		    	$period->baseCurrencyId = currency_Currencies::getIdByCode();
		    }
		    
		    if(!$form->rec->rate){
		       
	    		// Ако не е зададен курс на валутата
		    	$currencyRates = currency_CurrencyRates::fetch("#currencyId = {$form->rec->currencyId}");
		    		
		    	// Ако текущата валута е основната валута 
		    	($currencyRates) ? $form->rec->rate = round($currencyRates->rate, 4) : $form->rec->rate = 1;
		    		
		    	// Коя е базовата валута, и нейния курс
		    	$baseCurrencyRate = currency_CurrencyRates::fetch("#currencyId = {$period->baseCurrencyId}");
		    			
		    	// Ако основната валута за периода не фигурира в currency_CurrencyRates, 
		    	// то приемаме че тя е Евро
		    	if(!$baseCurrencyRate){
		    		$baseCurrencyRate = new stdClass();
		    		$baseCurrencyRate->currencyId = currency_Currencies::getIdByCode('EUR');
		    		$baseCurrency->code = 'EUR';
		    		$baseCurrencyRate->rate = 1;
		    	}
		    		
		    	// Преизчисляваме колко е курса на подадената валута към основната за периода
		    	$form->rec->rate = round($baseCurrencyRate->rate/$form->rec->rate, 4);
		    }
		    
		    if($form->rec->rate != 1) {
		   		$form->rec->equals = round($form->rec->amount * $form->rec->rate, 2);
		    } 
		    
	    	$form->rec->baseCurrency = $period->baseCurrencyId;
    	}
    	
    	acc_Periods::checkDocumentDate($form);
    }
   
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->number = static::getHandle($rec->id);
    	
    	if($fields['-single']){
    		
    		// Адреса на контрагента
    		$row->contragentName .= trim(
                sprintf("<br>%s<br>%s %s<br> %s", 
                 	$row->contragentCountry,
                    $row->contragentPcode,
                    $row->contragentPlace,
                    $row->contragentAdress
                )
            );
           
           $accRec = acc_Accounts::fetch($rec->creditAccounts);
    	   $row->creditAccounts = acc_Accounts::getRecTitle($accRec);
    	
            if(!$rec->equals) {
	    		
	    		//не показваме курса ако валутата на документа съвпада с тази на периода
	    		unset($row->rate);
	    		unset($row->baseCurrency);
	    	} 
           
	    	$spellNumber = cls::get('core_SpellNumber');
		    $amountVerbal = $spellNumber->asCurrency($rec->amount, 'bg', FALSE);
		    $row->amountVerbal = $amountVerbal;
		    	
    		// Вземаме данните за нашата фирма
    		$conf = core_Packs::getConfig('crm');
    		$companyId = $conf->BGERP_OWN_COMPANY_ID;
        	$myCompany = crm_Companies::fetch($companyId);
        	$row->organisation = $myCompany->name;
        	$row->organisation .= trim(
                sprintf("<br>%s %s<br> %s", 
                    $myCompany->place,
                    $myCompany->pCode,
                    $myCompany->address
                )
            );
            
    		// Извличаме имената на създателя на документа (касиера)
    		$cashierRec = core_Users::fetch($rec->createdBy);
    		$cashierRow = core_Users::recToVerbal($cashierRec);
	    	$row->cashier = $cashierRow->names;
	    	
        }
       
        // Показваме заглавието само ако не сме в режим принтиране
    	if(!Mode::is('printing')){
    		$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
    	}
    }
    
    
    /**
     * Вкарваме css файл за единичния изглед
     */
	static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$tpl->push('cash/tpl/styles.css', 'CSS');
    }
    
    
   	/**
   	 *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
   	 *  Създава транзакция която се записва в Журнала, при контирането
   	 */
    public static function getTransaction($id)
    {
       	// Извличаме записа
        expect($rec = self::fetch($id));
        $conf = core_Packs::getConfig('cash');
        
        
        // Курса по който се обменя валутата  на ордера към основната валута за периода
        $entrAmount = $rec->rate * $rec->amount; 
        // Намираме класа на контрагента
        $contragentId = doc_Folders::fetchCoverId($rec->folderId);
        $contragentClass = doc_Folders::fetchCoverClassName($rec->folderId);
        
        // Подготвяме информацията която ще записваме в Журнала
        $result = (object)array(
            'reason' => $rec->reason, // основанието за ордера
            'valior' => $rec->valior,   // датата на ордера
            'totalAmount' => $entrAmount,
            'entries' =>array( (object)array(
                'amount' => $rec->rate * $rec->amount,	// равностойноста на сумата в основната валута
                
                'debitAcc' => $conf->CASH_CASE_ACCOUNT, // дебитната сметка
                'debitItem1' => (object)array('cls'=>'cash_Cases', 'id'=>$rec->peroCase),  // перо каса
        		'debitItem2' => (object)array('cls'=>'currency_Currencies', 'id'=>$rec->currencyId),// перо валута
                'debitQuantity' => $rec->amount,
                'debitPrice' => $rec->rate,
        		
                'creditAccId' => $rec->creditAccounts, // кредитна сметка
                'creditItem1' => (object)array('cls'=>$contragentClass, 'id'=>$contragentId), // перо контрагент
                'creditItem2' => (object)array('cls'=>'currency_Currencies', 'id'=>$rec->currencyId), // перо валута
                'creditQuantity' => $rec->amount,
                'creditPrice' => $rec->rate,
            ))
        );
        
        // Ако кредитната сметка не поддържа втора номенклатура, премахваме
        // от масива второто перо на кредитната сметка
        $cAcc = acc_journal_Account::byId($rec->creditAccounts);
        if(!$cAcc->groupId2){
        	unset($result->entries[0]->creditItem2);
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
    	
    	return $self->abbr . $rec->number;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public static function fetchByHandle($parsedHandle)
    {
        return static::fetch("#number = '{$parsedHandle['id']}'");
    } 
}
