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
    var $title = "Смяна на валута";
    
    
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
    var $singleTitle = 'Смяна на валута';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/money_add.png';
    
    
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
    	$this->FLD('operationId', 'key(mvc=acc_Operations,select=name)', 'caption=Операция,width=6em,mandatory,silent');
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,width=6em,mandatory');
    	$this->FLD('reason', 'varchar(255)', 'caption=Основание,width=20em,input,mandatory');
    	$this->FLD('creditAccId', 'acc_type_Account()','caption=Кредит,width=300px,input=none');
    	$this->FLD('creditEnt1', 'acc_type_Item(select=numTitleLink)', 'caption=От->перо 1');
        $this->FLD('creditEnt2', 'acc_type_Item(select=numTitleLink)', 'caption=От->перо 2');
        $this->FLD('creditEnt3', 'acc_type_Item(select=numTitleLink)', 'caption=От->перо 3');
        $this->FLD('creditQuantity', 'float', 'width=6em,caption=От->Сума');
        $this->FLD('creditPrice', 'float', 'input=none');
        $this->FLD('debitAccId', 'acc_type_Account()','caption=Дебит,width=300px,input=none');
        $this->FLD('debitEnt1', 'acc_type_Item(select=numTitleLink)', 'caption=Към->перо 1');
        $this->FLD('debitEnt2', 'acc_type_Item(select=numTitleLink)', 'caption=Към->перо 2');
        $this->FLD('debitEnt3', 'acc_type_Item(select=numTitleLink)', 'caption=Към->перо 3');
        $this->FLD('debitQuantity', 'float', 'width=6em,caption=Към->Сума');
        $this->FLD('debitPrice', 'float', 'input=none');
       	$this->FLD('rate', 'float', 'input=none');
        $this->FLD('state', 
            'enum(draft=Чернова, active=Активиран, rejected=Сторнирана, closed=Контиран)', 
            'caption=Статус, input=none'
        );
        $this->FNC('isContable', 'int', 'column=none');
    }
    
    
	/**
     * @TODO
     */
	static function on_CalcIsContable($mvc, $rec)
    {
        $rec->isContable =
        ($rec->state == 'draft');
    }
    
    
	/**
     *  Добавяме помощник за избиране на сч. операция
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
    	if ($action != 'add') {
            
            return;
        }
        
        if (!$mvc->haveRightFor($action)) {
            
            return;
        }

       // Има ли вече зададено основание? 
       if (Request::get('operationId', 'int')) {
            
           // Има основание - не правим нищо
           return;
        }
        
        $form = static::prepareReasonForm();
        $form->input();
        $form = $form->renderHtml();
        $tpl = $mvc->renderWrapping($form);
        
        return FALSE;
    }
    
    
    /**
     * Подготвяме формата от която ще избираме посоката на движението
     */
    static function prepareReasonForm()
    {
    	$form = cls::get('core_Form');
    	$form->method = 'GET';
    	$form->FNC('operationId', 'key(mvc=acc_Operations, select=name)', 'input,caption=Операция');
    	$form->title = 'Ново Превалутиране';
        $form->toolbar->addSbBtn('Напред', '', array('class'=>'btn-next btn-move'));
        $form->toolbar->addBtn('Отказ', toUrl(array($this, 'list')), array('class'=>'btn-cancel'));
        
        $options = acc_Operations::getPossibleOperations(get_called_class());
        $form->setOptions('operationId', $options);
        
        return $form;
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    { 
    	$form = &$data->form;
    	
    	// Очакваме и намираме коя е извършената операция
    	if(!$form->rec->id) {
    		expect($operationId = Request::get('operationId'));
    	} else {
    		$operationId = $form->rec->operationId;
    	}
    	
    	$operation = acc_Operations::getOperationInfo($operationId);
       
    	// Трябва документа да поддържа тази операция
    	$classId = core_Classes::fetchIdByName(get_called_class());
        expect($operation->document == $classId, 'Този документ не поддържа избраната операция');
        
        $debitAcc = $operation->debitAccount;
        $creditAcc = $operation->creditAccount;
       
        // Перото на валутата по подразбиране
        $today = dt::verbal2mysql();
        $currencyClassId = currency_Currencies::getClassId();
        $currencyId = acc_Periods::getBaseCurrencyId($today);
        $currencyItem = acc_Items::fetch("#objectId={$currencyId} AND #classId={$currencyClassId}");
        
        foreach (array('debit' => 'Дебит', 'credit' => 'Кредит') as $type => $caption) {
            
            $acc = ${"{$type}Acc"};
           
            // Скриваме всички полета за пера, и после показваме само тези, за които съответната
            // (дебит или кредит) сметка има аналитичност.
            $form->setField("{$type}Ent1", 'input=none');
            $form->setField("{$type}Ent2", 'input=none');
            $form->setField("{$type}Ent3", 'input=none');
    		
            ($type == 'debit') ? $division = tr('Към') : $division = tr('От');
            foreach ($acc->groups as $i => $list) {
                if (!$list->rec->itemsCnt) {
                    return Redirect(array('acc_Items', 'list', 'listId'=>$list->rec->id), FALSE, tr("Липсва избор за |* \"{$list->rec->name}\""));
                }
               
                if($list->rec->systemId == 'case') {
                	$singleName = tr('Каса');
                } elseif($list->rec->systemId == 'currency'){
                	$singleName = tr('Валута');
                } else {
                	$singleName = tr('Банкова сметка');
                }
                $form->getField("{$type}Ent{$i}")->type->params['lists'] = $list->rec->num;
                $form->setField("{$type}Ent{$i}", "mandatory,input,caption={$division}->" . $singleName);
    		 
            }
		   
		    // Ако поддържа номенклатура валута, слагаме и стойност по дефолт
		    if($pos = acc_Lists::getPosition($acc->rec->systemId, 'currency_CurrenciesAccRegIntf')) {
		    	$form->setField("{$type}Ent{$pos}", $currencyItem->id);
		    }
      	}
      	
      	$form->setDefault('valior', $today);
        $form->setReadOnly('operationId');
	}
    
    
    /**
     * Проверка след изпращането на формата
     */
    function on_AfterInputEditForm($mvc, $form)
    { 
    	if ($form->isSubmitted()){
    		
    		$rec = &$form->rec;
    		
		    $operation = acc_Operations::fetch($rec->operationId);
    		$rec->debitAccId = $operation->debitAccount;
    		$rec->creditAccId = $operation->creditAccount;
    		
    		if(!$rec->creditQuantity || !$rec->debitQuantity) {
    			$form->setError("creditQuantity, debitQuantity", "Трябва да са въведени и двете суми !!!");
    		} else {
    			$creditCurrency = static::getCurrency('credit', $rec);
		    	$debitCurrency = static::getCurrency('debit', $rec);
    			if($creditCurrency == $debitCurrency) {
		    		$form->setWarning('creditEnt1, debitEnt1', 'Валутите са едни и същи, няма смяна на валута !!!');
		    	}
		    	
		    	// Изчисляваме курса на превалутирането спрямо входните данни
		    	$cCode = currency_Currencies::getCodeById($creditCurrency);
		    	$dCode = currency_Currencies::getCodeById($debitCurrency);
		    	$cRate = currency_CurrencyRates::getRate($rec->valior, $cCode, acc_Periods::getBaseCurrencyCode($rec->valior));
		    	$rec->creditPrice = $cRate;
		    	$rec->debitPrice = ($rec->creditQuantity * $rec->creditPrice) / $rec->debitQuantity;
		    	$rec->rate = round($rec->creditPrice / $rec->debitPrice, 4);
		    	
		    	// Каква сума очакваме да е въведена
		    	$expAmount = currency_CurrencyRates::convertAmount($rec->creditQuantity, $rec->valior, $cCode, $dCode);
		    	
		    	// Проверяваме дали дебитната сума има голяма разлика
		    	// спрямо очакваната, ако да сетваме предупреждение
		    	if(!$mvc->compareAmounts($rec->debitQuantity, $expAmount)) {
		    		$form->setWarning('debitQuantity', 'Изходната сума има голяма ралзика спрямо очакваното.
		    						   Сигурни ли сте че искате да запишете документа');
		    	}
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
    function compareAmounts($givenAmount, $expAmount)
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
     * Проверява дебитните или кредитните пера и намира номера на валутата
     * от дебита или кредита
     * @param string $type - дебитно или кредитно перо обхождаме
     * @param stdClass $rec - Запис от модела
     * @return int $id - Id на валутата която е в сметката
     */
    static function getCurrency($type, $rec)
    {
    	expect($type == 'debit' || $type == 'credit');
    	$accId = $rec->{"{$type}AccId"};
    	$bankItemPos = acc_Lists::getPosition($accId, 'bank_OwnAccRegIntf');
    	
    	// Ако има Банкова номенклатура намираме валутата от банковата сметка
    	if($bankItemPos) {
    		${"{$type}BankItem"} = acc_Items::fetchField($rec->{"{$type}Ent{$bankItemPos}"},'objectId');
    		${"{$type}Bank"} = bank_OwnAccounts::getOwnAccountInfo(${"{$type}BankItem"});
    		
    		return ${"{$type}Bank"}->currencyId;
    	} else {
    		
    		// Ако няма номенклатура Банки, очакваме да има номенклатура 
    		// Валути и намираме ид-то на валутата
    		$currencyItemPos = acc_Lists::getPosition($accId, 'currency_CurrenciesAccRegIntf');
    		${"{$type}currencyItem"} = acc_Items::fetchField($rec->{"{$type}Ent{$currencyItemPos}"},'objectId');
    		
    		return ${"{$type}currencyItem"};
    	}
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->number = static::getHandle($rec->id);
    	
    	if($fields['-single']) {
    		
    		// Пълни имена на дебитната и кредитната сметка
    		$debitRec = acc_Accounts::getRecBySystemId($rec->debitAccId);
	    	$row->debitAccId = acc_Accounts::getRecTitle($debitRec);
	    	
	    	$creditRec = acc_Accounts::getRecBySystemId($rec->creditAccId);
	    	$row->creditAccId = acc_Accounts::getRecTitle($creditRec);
	    	
    		$currencyId = static::getCurrency('debit', $rec);
    		$row->currency = currency_Currencies::getCodeById($currencyId);
    		
    		$double = cls::get('type_Double');
	    	$double->params['decimals'] = 2;
	    	$row->creditQuantity = $double->toVerbal($rec->creditQuantity);
	    	$row->debitQuantity = $double->toVerbal($rec->debitQuantity);
	    	$row->rate = (float)$rec->rate;
	    	
	    	$row->equals = $double->toVerbal($rec->creditQuantity * $rec->creditPrice);
    		$period = acc_Periods::fetchByDate($rec->valior);
			$row->baseCurrency = currency_Currencies::getCodeById($period->baseCurrencyId);
    		
			$dCurrency = static::getCurrency('debit', $rec);
    		$row->debitPrice = currency_Currencies::getCodeById($dCurrency);
    			
    		$cCurrency = static::getCurrency('credit', $rec);
    		$row->creditPrice = currency_Currencies::getCodeById($cCurrency);
    			
			// Показваме заглавието само ако не сме в режим принтиране
	    	if(!Mode::is('printing')){
	    		$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
	    	}
    	}
    }
    
    
    /**
     * Поставя бутони за генериране на други банкови документи възоснова
     * на този.
     */
	static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	//@TODO
    }
    
    
    /**
   	 *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
   	 *  Създава транзакция която се записва в Журнала, при контирането
   	 */
    public static function getTransaction($id)
    {
    	// Извличаме записа
        expect($rec = self::fetch($id));
        
        $entry = array(
            'amount' => $rec->debitQuantity * $rec->debitPrice,
            'debit' => array(
                $rec->debitAccId,
                'quantity' => $rec->debitQuantity
            ),
            'credit' => array(
                $rec->creditAccId,
                'quantity' => $rec->creditQuantity
            ),
        );
        
      	foreach(array('debit', 'credit') as $type) {
      	    foreach (range(1, 3) as $n) {
          	    if (!$rec->{"{$type}Ent{$n}"}) {
    				// Ако не е зададено перо - пропускаме
    				continue;
    			}
    			
    			$entry[$type][] = new acc_journal_Item($rec->{"{$type}Ent{$n}"});
      	    }
      	}
      	
      	
      	// Подготвяме информацията която ще записваме в Журнала
        $result = (object)array(
            'reason' => $rec->reason,   // основанието за ордера
            'valior' => $rec->valior,   // датата на ордера
            'entries' => array($entry)
        );
        
       //bp($result);
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
        
        return $row;
    }
}
