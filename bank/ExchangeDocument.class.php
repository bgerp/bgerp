<?php 


/**
 * Документ за Смяна на валута
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_ExchangeDocument extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Смяна на валута (Банки)";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, bank_Wrapper, bank_DocumentWrapper, plg_Printing,
     	plg_Sorting,doc_DocumentPlg,Accounts=acc_Accounts, Lists=acc_Lists, Items=acc_Items,
     	plg_Search,doc_plg_MultiPrint, bgerp_plg_Blank, acc_plg_Contable';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт, number=Номер, reason, valior, state, createdOn, createdBy";
    
    
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
    var $singleTitle = 'Смяна на валута (банки)';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/money_exchange.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Sv";
    
    
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
    var $singleLayoutFile = 'bank/tpl/SingleExchangeDocument.shtml';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "4.7|Финанси";
    
	/**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,width=6em,mandatory');
    	$this->FLD('reason', 'varchar(255)', 'caption=Основание,width=23em,input,mandatory');
    	$this->FLD('peroFrom', 'key(mvc=bank_OwnAccounts, select=bankAccountId)','input,caption=От->Б. сметка,width=20em');
    	$this->FLD('creditPrice', 'float', 'input=none');
    	$this->FLD('creditQuantity', 'float', 'width=6em,caption=От->Сума');
        $this->FLD('peroTo', 'key(mvc=bank_OwnAccounts, select=bankAccountId)', 'input,caption=Към->Б. сметка,width=20em');
        $this->FLD('debitQuantity', 'float', 'width=6em,caption=Към->Сума');
       	$this->FLD('debitPrice', 'float', 'input=none');
        $this->FLD('rate', 'float', 'input=none');
        $this->FLD('state', 
            'enum(draft=Чернова, active=Активиран, rejected=Сторнирана, closed=Контиран)', 
            'caption=Статус, input=none'
        );
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    { 
    	$form = &$data->form;
    	$today = dt::verbal2mysql();
        $form->setDefault('peroFrom', cash_Cases::getCurrent('id', FALSE));
        $form->setDefault('valior', $today);
        
        $ownAccounts = bank_OwnAccounts::getOwnAccounts();
        $form->setOptions('peroFrom', $ownAccounts);
		$form->setOptions('peroTo', $ownAccounts);
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
    		
    		$creditAccInfo = bank_OwnAccounts::getOwnAccountInfo($rec->peroFrom);
    		$debitAccInfo = bank_OwnAccounts::getOwnAccountInfo($rec->peroTo);
    		if($creditAccInfo->currencyId == $debitAccInfo->currencyId) {
		    	$form->setWarning('peroFrom, peroTo', 'Валутите са едни и същи, няма смяна на валута !!!');
		    }
		    
    		// Изчисляваме курса на превалутирането спрямо входните данни
		    $cCode = currency_Currencies::getCodeById($creditAccInfo->currencyId);
		    $dCode = currency_Currencies::getCodeById($debitAccInfo->currencyId);
		    $cRate = currency_CurrencyRates::getRate($rec->valior, $cCode, acc_Periods::getBaseCurrencyCode($rec->valior));
		    $rec->creditPrice = $cRate;
		    $rec->debitPrice = ($rec->creditQuantity * $rec->creditPrice) / $rec->debitQuantity;
		    $rec->rate = round($rec->creditPrice / $rec->debitPrice, 4);
		    	
		    // Каква сума очакваме да е въведена
		    $expAmount = currency_CurrencyRates::convertAmount($rec->creditQuantity, $rec->valior, $cCode, $dCode);
		    	
		    // Проверяваме дали дебитната сума има голяма разлика
		    // спрямо очакваната, ако да сетваме предупреждение
		    if(!static::compareAmounts($rec->debitQuantity, $expAmount)) {
		    	$form->setWarning('debitQuantity', 'Изходната сума има голяма ралзика спрямо очакваното.
		    					   Сигурни ли сте че искате да запишете документа');
		    }
    	}
    }
    
    
    /**
     *  Функция проверяваща колко '%' е отклонението от очакваната
     *  сума и тази получена след превалутирането
     *  @param double $givenAmount - Въведената сума
     *  @param double $expAmount - Очакваната сума
     *  @return boolean TRUE/FALSE - Имали голямо отклонение
     */
    static function compareAmounts($givenAmount, $expAmount)
    {
    	$conf = core_Packs::getConfig('bank');
    	$percent = $conf->BANK_EXCHANGE_DIFFERENCE;
		    	
		// Намираме разликата в проценти между реалната и очакваната
		// дебитна сума. Ако разликата им е по-голяма от 5%
		// връщаме FALSE
		$difference = abs($givenAmount - $expAmount) / min($givenAmount, $expAmount) * 100;
		if(round($difference, 2) > $percent) {
		    
			return FALSE;
		} 
		
		return TRUE;
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->number = static::getHandle($rec->id);
    	
    	if($fields['-single']) {
    		$double = cls::get('type_Double');
	    	$double->params['decimals'] = 2;
	    	$row->creditQuantity = $double->toVerbal($rec->creditQuantity);
	    	$row->debitQuantity = $double->toVerbal($rec->debitQuantity);
	    	$row->rate = (float)$rec->rate;
	    	
	    	$creditAccInfo = bank_OwnAccounts::getOwnAccountInfo($rec->peroFrom);
    		$debitAccInfo = bank_OwnAccounts::getOwnAccountInfo($rec->peroTo);
    		
	    	$row->equals = $double->toVerbal($rec->creditQuantity * $rec->creditPrice);
    		$row->baseCurrency = acc_Periods::getBaseCurrencyId($rec->valior);
    		$row->debitPrice = currency_Currencies::getCodeById($debitAccInfo->currencyId);
    		$row->creditPrice = currency_Currencies::getCodeById($creditAccInfo->currencyId);
    		$row->currency = currency_Currencies::getCodeById($debitAccInfo->currencyId);
			
    		// Показваме заглавието само ако не сме в режим принтиране
	    	if(!Mode::is('printing')){
	    		$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
	    	}
    	}
    }
    
    
    /**
   	 *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
   	 *  Създава транзакция която се записва в Журнала, при контирането
   	 */
    public static function getTransaction($id)
    {
    	// Извличаме записа
        expect($rec = self::fetch($id));
        $cOwnAcc = bank_OwnAccounts::getOwnAccountInfo($rec->peroFrom, 'currencyId');
        $dOwnAcc = bank_OwnAccounts::getOwnAccountInfo($rec->peroTo);
        $entry = array(
            'amount' => $rec->debitQuantity * $rec->debitPrice,
            'debit' => array(
                '503',
                array('bank_OwnAccounts', $rec->peroTo),
        		array('currency_Currencies', $dOwnAcc->currencyId),
                'quantity' => $rec->debitQuantity
            ),
            'credit' => array(
                '503',
                array('bank_OwnAccounts', $rec->peroFrom),
        		array('currency_Currencies', $cOwnAcc->currencyId),
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
        $rec = (object)array(
            'id' => $id,
            'state' => 'closed'
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
    
        // Може да създаваме документ-а само в дефолт папката му
        if($folderId == static::getDefaultFolder()) {
        	
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
        $row->title = $rec->reason;
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;

        return $row;
    }
}
