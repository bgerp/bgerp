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
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Приходни Kасови Oрдери";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, cash_Wrapper, plg_Sorting,
                     doc_DocumentPlg, plg_Printing, plg_Search, doc_ActivatePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "id, reason, date, amount, rate, notes, createdOn, createdBy"; // , peroContragent, peroDocument";
    
    
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
    var $abbr = "Пко";
    
    
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
    	$this->FLD('importer', 'varchar(255)', 'caption=Вносител,width=100%,mandatory');
    	$this->FLD('recipient', 'varchar(255)', 'caption=Получател,width=100%,mandatory');
    	$this->FLD('reason', 'varchar(255)', 'caption=Основание,width=100%,mandatory');
    	$this->FLD('date', 'date', 'caption=Дата,mandatory');
    	$this->FLD('amount', 'double(decimals=2)', 'caption=Сума,mandatory');
    	$this->FLD('amountVerbal', 'varchar(255)', 'caption=Словом,input=none');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,mandatory');
    	$this->FLD('rate', 'double(decimals=2)', 'caption=Курс');
    	$this->FLD('notes', 'richtext', 'caption=Бележки');
    }
    
    
    /**
     *  Обработка на формата за редакция и добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$folderId = Request::get('folderId');
    	$query = static::getQuery();
    	$query->XPR('max', 'int', 'max(#id)');
    	if($folderId){
    		$query->where("#folderId = {$folderId}");
    	}
    	
    	if($rec = $query->fetch()->max) {
    		
    		// Ако има последен ПКО в папката то взимаме неговите стойности
    		// за вносител и получател  и ги слагаме за дефолт стойности
    		$lastOrder = static::fetch($rec);
    		$data->form->setDefault('importer', $lastOrder->importer);
    		$data->form->setDefault('recipient', $lastOrder->recipient);
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
    		if($rec->currencyId != $period->baseCurrencyId) {
    			if(!$rec->rate){
    				$currencyRates = currency_CurrencyRates::fetch("#currencyId = {$rec->currencyId}");
    				$row->rate = round($currencyRates->rate, 2);
    			}
    			
    			// Коя е базовата валута
    			$baseCurrency = currency_CurrencyRates::fetch("#currencyId = {$period->baseCurrencyId}");
    			
    			$row->equals = round($row->amount / $row->rate * $baseCurrency->rate, 2);
    		}
    		
    		if(core_Users::haveRole('cash', core_Users::getCurrent())){
    			$row->cashier = core_Users::getCurrent('names');
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