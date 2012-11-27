<?php



/**
 * Документ за Разходни Касови Ордери
 *
 *
 * @category  bgerp
 * @package   cash
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cash_Rko extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Разходни Касови Ордери";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, plg_Printing,
                     cash_Wrapper, plg_Sorting,
                     doc_DocumentPlg, plg_Search, doc_ActivatePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "id, reason, date, amount, currencyId, rate, createdOn, createdBy";
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'reason';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Разходен Касов Ордер';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/money_delete.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Рко";
    
    
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
    var $singleLayoutFile = 'cash/tpl/CashOrder.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'number, reason, amount, date';
    
      
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('number', 'int', 'caption=Номер,width=50%,mandatory');
    	$this->FLD('depositor', 'varchar(255)', 'caption=Вносител,width=100%,mandatory');
    	$this->FLD('recipient', 'varchar(255)', 'caption=Получател,width=100%,mandatory');
    	$this->FLD('reason', 'varchar(255)', 'caption=Основание,width=100%,mandatory');
    	$this->FLD('date', 'date', 'caption=Дата,mandatory');
    	$this->FLD('amount', 'double(decimals=2)', 'caption=Сума,mandatory');
    	$this->FLD('amountVerbal', 'varchar(255)', 'caption=Словом,input=none');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,mandatory');
    	$this->FLD('rate', 'double(decimals=2)', 'caption=Курс');
    	$this->FLD('notes', 'richtext', 'caption=Бележки');
    	
    	$this->setDbUnique('number');
    }
    
    
	/**
     *  Обработка на формата за редакция и добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$folderId = Request::get('folderId');
    	
    	// Информацията за контрагента на папката
    	try {
    		$contragent = doc_Folders::getContragentData($folderId);
    	} catch(Exception $e) {
    		$contragent = NULL;
    	};
    	
    	if($contragent) {
    		if($contragent->company){
    			
    			// Ако контрагента е компания то взимаме името и
    			$depositor = $contragent->company;
    		} else {
    			
    			// Ако контрагента е лице взимаме името му;
    			$depositor = $contragent->name;
    		}
    		
    		// Сетваме контрагента на формата и го правим Read-Only
    		$data->form->setDefault('depositor', $depositor);
    		$data->form->setReadOnly('depositor');
    		
    	} else {
    		
    		// Ако папката не е обвързана с контрагент то намираме името на
    		// вносителя от последно въведения ПКО
	    	$query = static::getQuery();
	    	$query->XPR('max', 'int', 'max(#id)');
	    	if($folderId){
	    		$query->where("#folderId = {$folderId}");
	    	}
	    	
	    	if($rec = $query->fetch()->max) {
	    		$lastOrder = static::fetch($rec);
	    		$data->form->setDefault('depositor', $lastOrder->depositor);
	    	}
    	}
    	
    	if(core_Users::haveRole('cash', core_Users::getCurrent())){
    		
    			// Получателят е текущия потребител, ако има роля касиер
    			$data->form->setDefault('recipient', core_Users::getCurrent('names'));
    	}
    	
    	// Коя е текущата дата, и валута по подразбиране "BGN"
    	$today = date("d-m-Y", time());
    	$currency = currency_Currencies::fetch("#code = 'BGN'"); 
    	
    	// Поставяме стойности по подразбиране
    	$data->form->setDefault('date', $today);
    	$data->form->setDefault('currencyId', $currency->id);
    }
    
    
	/**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_BeforeSave($mvc, &$id, $rec)
    {
    	// Записваме вербалната стойност на сумата
    	$spellNumber = cls::get('core_SpellNumber');
    	$amountVerbal = $spellNumber->asCurrency($rec->amount);
    	$rec->amountVerbal = $amountVerbal;
    }
    
    
	/**
     *  Обрабтки по вербалното прдставяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($fields['-single']){
    		$accPeriods = cls::get('acc_Periods');
    		$period = $accPeriods->getPeriod();
    		if(!$period->baseCurrencyId){
    				
    				// Ако периода е без посочена валута, то зимаме по дефолт BGN
    				$period->baseCurrencyId = currency_Currencies::fetchField("#code = 'BGN'", "id");
    			}
    		
    		//	Ако избраната валута е различна от основната за периода
    		if($rec->currencyId != $period->baseCurrencyId) {
    			
    			// Ако не е зададен курс на валутата
    			if(!$rec->rate){
    				$currencyRates = currency_CurrencyRates::fetch("#currencyId = {$rec->currencyId}");
    				
    				// Ако текущата валута е основната валута 
    				($currencyRates) ? $row->rate = round($currencyRates->rate, 2) : $row->rate = 1;
    			}
    			
    			// Коя е базовата валута, и нейния курс
    			$baseCurrencyRate = currency_CurrencyRates::fetch("#currencyId = {$period->baseCurrencyId}");
    			$baseCurrency = currency_Currencies::fetch($baseCurrencyRate->currencyId);
    			
    			// Каква е равостойноста на сумата към текущата валута, и кода на основната валута
    			$row->baseCurrency = $baseCurrency->code;
    			$row->equals = round(($rec->amount / $row->rate) * $baseCurrencyRate->rate, 2);
    		}
    		
    		// Вземаме данните за нашата фирма
    		$conf = core_Packs::getConfig('crm');
    		$companyId = $conf->BGERP_OWN_COMPANY_ID;
        	$myCompany = crm_Companies::fetch($companyId);
    		$row->organisation = $myCompany->name;
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
}