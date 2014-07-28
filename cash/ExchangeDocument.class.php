<?php 


/**
 * Документ за Смяна на валута
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cash_ExchangeDocument extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Касови обмени на валути";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, cash_Wrapper, plg_Printing, acc_plg_Contable,
     	plg_Sorting,doc_DocumentPlg, acc_plg_DocumentSummary,
     	plg_Search,doc_plg_MultiPrint, bgerp_plg_Blank, doc_SharablePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт, number=Номер, valior, reason, folderId, creditCurrency=Обменени->Валута, creditQuantity=Обменени->Сума, debitCurrency=Получени->Валута, debitQuantity=Получени->Сума, state, createdOn, createdBy";
    
    
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
    var $singleTitle = 'Касова обмяна на валута';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/money_exchange.png';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,cash';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,cash';
	
	
    /**
     * Абревиатура
     */
    var $abbr = "Sv";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'cash, ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'cash, ceo';
    
    
    /**
     * Кой може да го контира?
     */
    var $canConto = 'acc, cash, ceo';
    
    
    /**
     * Кой може да сторнира
     */
    var $canRevert = 'cash, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'bank/tpl/SingleExchangeDocument.shtml';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "4.8|Финанси";
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'reason, peroFrom, peroTo';
    
    
	/**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,mandatory');
    	$this->FLD('reason', 'varchar(255)', 'caption=Основание,input,mandatory');
    	$this->FLD('peroFrom', 'key(mvc=cash_Cases, select=name)','caption=От->Каса');
    	$this->FLD('creditCurrency', 'key(mvc=currency_Currencies, select=code)','caption=От->Валута');
    	$this->FLD('creditPrice', 'double(smartRound)', 'input=none');
    	$this->FLD('creditQuantity', 'double(smartRound)', 'caption=От->Сума');
        $this->FLD('peroTo', 'key(mvc=cash_Cases, select=name)','caption=Към->Каса');
        $this->FLD('debitCurrency', 'key(mvc=currency_Currencies, select=code)','caption=Към->Валута');
        $this->FLD('debitQuantity', 'double(smartRound)', 'caption=Към->Сума');
       	$this->FLD('debitPrice', 'double(smartRound)', 'input=none');
        $this->FLD('equals', 'double(smartRound)', 'input=none,caption=Общо,summary=amount');
       	$this->FLD('rate', 'double(smartRound)', 'input=none');
        $this->FLD('state', 
            'enum(draft=Чернова, active=Активиран, rejected=Сторнирана, closed=Контиран)', 
            'caption=Статус, input=none'
        );
        $this->FLD('sharedUsers', 'userList', 'input=none,caption=Споделяне->Потребители');
    }
    
    
	/**
	 *  Подготовка на филтър формата
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{
		// Добавяме към формата за търсене търсене по Каса
		cash_Cases::prepareCaseFilter($data, array('peroFrom', 'peroTo'));
	}
    
    
	/**
     *  Добавяме помощник за избиране на сч. операция
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
    	if ($action != 'add') {
            return;
        }
        
        if($folderId = Request::get('folderId')){
	        if($folderId != cash_Cases::fetchField(cash_Cases::getCurrent(), 'folderId')){
	        	return Redirect(array('cash_Cases', 'list'), FALSE, "Документът не може да се създаде в папката на неактивна каса");
	        }
        }
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    { 
    	$form = &$data->form;
    	$cCase = cash_Cases::getCurrent();
    	$form->rec->folderId = cash_Cases::forceCoverAndFolder($cCase);
    	$today = dt::verbal2mysql();
        $currencyId = acc_Periods::getBaseCurrencyId($today);
        
        $form->setReadOnly('peroFrom', $cCase);
        $form->setDefault('creditCurrency', $currencyId);
        $form->setDefault('debitCurrency', $currencyId);
        $form->setDefault('valior', $today);
	}
    
    
    /**
     * Проверка след изпращането на формата
     */
    function on_AfterInputEditForm($mvc, $form)
    { 
    	if ($form->isSubmitted()){
    		
    		$rec = &$form->rec;
    		
    		if(!$rec->creditQuantity || !$rec->debitQuantity) {
    			$form->setError("creditQuantity, debitQuantity", "Трябва да са въведени и двете суми !!!");
    			return;
    		} 
    		
    		if($rec->creditCurrency == $rec->debitCurrency) {
		    	$form->setWarning('creditCurrency, debitCurrency', 'Валутите са едни и същи, няма смяна на валута !!!');
		    	return;
    		}
		    	
		    // Изчисляваме курса на превалутирането спрямо входните данни
		    $cCode = currency_Currencies::getCodeById($rec->creditCurrency);
		    $dCode = currency_Currencies::getCodeById($rec->debitCurrency);
		    $cRate = currency_CurrencyRates::getRate($rec->valior, $cCode, acc_Periods::getBaseCurrencyCode($rec->valior));
		    $rec->creditPrice = $cRate;
		    $rec->debitPrice = ($rec->creditQuantity * $rec->creditPrice) / $rec->debitQuantity;
		    $rec->rate = round($rec->creditPrice / $rec->debitPrice, 4);
		   
		    if($msg = currency_CurrencyRates::hasDeviation($rec->rate, $rec->valior, $cCode, $dCode)){
		    	$form->setWarning('rate', $msg);
		    }
		    
		    // Каква е равностойноста на обменената сума в основната валута за периода
		    if($dCode == acc_Periods::getBaseCurrencyCode($rec->valior)){
		    	$rec->equals = $rec->creditQuantity * $rec->rate;
		    } else {
		    	$rec->equals = currency_CurrencyRates::convertAmount($rec->debitQuantity, $rec->valior, $dCode, NULL);
		    }
		    
    		$toCashiers = cash_Cases::fetchField($rec->peroTo, 'cashiers');
    		$rec->sharedUsers = keylist::merge($rec->sharedUsers, $toCashiers);
    		$rec->sharedUsers = keylist::removeKey($rec->sharedUsers, core_Users::getCurrent());
    	}
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->number = static::getHandle($rec->id);
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    	}	
    	
    	if($fields['-single']) {
	    	
	    	// Показваме заглавието само ако не сме в режим принтиране
		    if(!Mode::is('printing')){
		    	$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
		    }
		    
		    $row->peroTo = cash_Cases::getHyperLink($rec->peroTo, TRUE);
		    $row->peroFrom = cash_Cases::getHyperLink($rec->peroFrom, TRUE);
    	}
    }
    
    
    /**
   	 *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
   	 *  Създава транзакция която се записва в Журнала, при контирането
   	 */
    public static function getTransaction($id)
    {
    	// Извличаме записа
        expect($rec = self::fetchRec($id));
        
        $entry = array(
            'amount' => $rec->debitQuantity * $rec->debitPrice,
            'debit' => array(
                '501',
        		array('cash_Cases', $rec->peroTo),
        		array('currency_Currencies', $rec->debitCurrency),
                'quantity' => $rec->debitQuantity
            ),
            'credit' => array(
                '501',
            	array('cash_Cases', $rec->peroFrom),
            	array('currency_Currencies', $rec->creditCurrency),
                'quantity' => $rec->creditQuantity
            ),
        );
      	
      	// Подготвяме информацията която ще записваме в Журнала
        $result = (object)array(
            'reason' => $rec->reason,   // основанието за ордера
            'valior' => $rec->valior,   // датата на ордера
            'entries' => array($entry)
        );
        
        return $result;
    }
    
    
    /**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public static function finalizeTransaction($id)
    {
        $rec = self::fetchRec($id);
        $rec->state = 'closed';
                
        return self::save($rec);
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        // Може да създаваме документ-а само в дефолт папката му
        if (doc_Folders::fetchCoverClassName($folderId) == 'cash_Cases') {
        	return TRUE;
        } 
        	
       return FALSE;
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
    	if ($threadRec->folderId == static::getDefaultFolder(NULL, FALSE) || doc_Folders::fetchCoverClassName($threadRec->folderId) == 'cash_Cases') {
        	return TRUE;
       } 
        
       return FALSE;
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = $this->singleTitle . " №{$id}";
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
		$row->recTitle = $rec->reason;
		
        return $row;
    }
    
    
	/**
     * Връща счетоводното основание за документа
     */
    public function getContoReason($id)
    {
    	$rec = $this->fetchRec($id);
    	
    	return $this->getVerbal($rec, 'reason');
    }
}
