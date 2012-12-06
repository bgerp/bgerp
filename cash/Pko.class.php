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
    var $interfaces = 'doc_DocumentIntf, doc_ContragentDataIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Приходни Kасови Oрдери";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, cash_Wrapper, plg_Sorting, doc_plg_BusinessDoc,
                     doc_DocumentPlg, plg_Printing, doc_SequencerPlg,
                     plg_Search, doc_ActivatePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "id, number, reason, date, amount, currencyId, rate, state, createdOn, createdBy";
    
    
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
     * Кой може да го отхвърли?
     */
    var $canReject = 'cash, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'cash/tpl/Pko.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'number, date, contragentFolder';
    
      
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('number', 'int', 'caption=Номер,width=50%');
    	$this->FLD('date', 'date(format=d.m.Y)', 'caption=Дата,mandatory');
    	$this->FLD('reason', 'varchar(255)', 'caption=Основание,width=100%,mandatory');
    	$this->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума,mandatory');
    	$this->FLD('contragentFolder', 'key(mvc=doc_Folders,select=title)', 'caption=Контрагент->Получател,mandatory,width=100%');
    	$this->FLD('depositor', 'varchar(255)', 'caption=Контрагент->Броил,mandatory');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута->Код');
    	$this->FLD('rate', 'double(decimals=2)', 'caption=Валута->Курс');
    	$this->FLD('notes', 'richtext(rows=6)', 'caption=Допълнително->Бележки');
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
    	
        // Поставяне на уникален индекс
    	$this->setDbUnique('number');
    }
    
    
    /**
     *  Обработка на формата за редакция и добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$folderId = $data->form->rec->folderId;
    	
    	// Информацията за контрагента на папката
    	try {
    		$contragentData = doc_Folders::getContragentData($folderId);
    	} catch(Exception $e) {
    		$contragentData = NULL;
    	};
    	
    	if($contragentData) {
    		$data->form->setDefault('contragentFolder', $folderId);
    		$data->form->setReadOnly('contragentFolder');
    		if($contragentData->name) {
    			
    			// Ако папката е на лице, то вносителя по дефолт е лицето
    			$data->form->setDefault('depositor', $contragentData->name);
    		}
    		
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
    	$data->form->setDefault('date', $today);
    	$data->form->setDefault('currencyId', $currencyId);
    }


    /**
     * Проверка и валидиране на формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        acc_Periods::checkDocumentDate($form);
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->number = static::getHandle($rec->id);
    	
    	if($fields['-single']){
    		
    		$contragentData = doc_Folders::getContragentData($rec->contragentFolder);
    		if($contragentData->company)
    			$row->contragent = $contragentData->company;
    		elseif($contragentData->name)
    			$row->contragent = $contragentData->name;
    		else 
    			$row->contragent = '';
    		
    		// Адреса на контрагента
    		$row->contragent .= trim(
                sprintf("<br>%s %s<br> %s", 
                    $contragentData->place,
                    $contragentData->pCode,
                    $contragentData->pAddress
                )
            );
            	
    		$accPeriods = cls::get('acc_Periods');
            
            // Взема периода за който се отнася документа, според датата му
    		$period = $accPeriods->fetchByDate($rec->date);
    		if(!$period->baseCurrencyId){
    				
    				// Ако периода е без посочена валута, то зимаме по дефолт BGN
    				$period->baseCurrencyId = currency_Currencies::getIdByCode();
    		}
    		
    		// Ако избраната валута е различна от основната за периода
    		if($rec->currencyId != $period->baseCurrencyId) {
    			
    			// Ако не е зададен курс на валутата
    			if(!$rec->rate){
    				$currencyRates = currency_CurrencyRates::fetch("#currencyId = {$rec->currencyId}");
    				
    				// Ако текущата валута е основната валута 
    				($currencyRates) ? $row->rate = round($currencyRates->rate, 4) : $row->rate = 1;
    			}
    			
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
    			
    			$baseCurrency = currency_Currencies::fetch($baseCurrencyRate->currencyId);
    			
    			// Каква е равостойноста на сумата към текущата валута, и кода на основната валута
    			$row->baseCurrency = $baseCurrency->code;
    			
    			// Преизчисляваме колко е курса на подадената валута към основната за периода
    			$row->rate = round($baseCurrencyRate->rate/$row->rate, 4);
    			
    			// Намираме равностойноста на подадената валута в основната за периода
    			$row->equals = round($rec->amount * $row->rate, 2);
    			$num = cls::get('type_Double');
    			$num->params['decimals']= 2;
    			$row->rate = $num->toVerbal($row->rate);
    			$row->equals = $num->toVerbal($row->equals);
    		}
    		
    		$spellNumber = cls::get('core_SpellNumber');
	    	$amountVerbal = $spellNumber->asCurrency($rec->amount, 'bg', FALSE);
	    	$row->amountVerbal = $amountVerbal;
	    	
    		// Вземаме данните за нашата фирма
    		$conf = core_Packs::getConfig('crm');
    		$companyId = $conf->BGERP_OWN_COMPANY_ID;
        	$myCompany = crm_Companies::fetch($companyId);
        	$row->adress = trim(
                sprintf("%s %s<br> %s", 
                    $myCompany->place,
                    $myCompany->pCode,
                    $myCompany->address
                )
            );
            
    		$row->organisation = $myCompany->name;
    		
	    	if(core_Users::haveRole('cash', core_Users::getCurrent())){
	    		
	    		// Получателят е текущия потребител, ако има роля касиер
	    		$row->cashier =  core_Users::getCurrent('names');
	    	}
        }
    }
    
    
    /**
     * Вкарваме css файл за единичния изглед
     */
	static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$tpl->push('cash/tpl/styles.css', 'CSS');
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
        $row->recTitle = $rec->reason;
        
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
     * Имплементиране на интерфейсен метод 
     */
    static function getHandle($id)
    {
    	$rec = static::fetch($id);
    	$me = cls::get(get_called_class());
    	
    	return $me->abbr . $rec->number;
    }
}