<?php 


/**
 * Документ за Вътрешно Паричен Трансфер
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_InternalMoneyTransfer extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Вътрешно Парични Трансфери";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, bank_Wrapper, bank_DocumentWrapper, plg_Printing,
     	plg_Sorting,doc_DocumentPlg,Accounts=acc_Accounts, Lists=acc_Lists, Items=acc_Items,
     	plg_Search,doc_plg_MultiPrint, bgerp_plg_Blank, acc_plg_Contable';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт, number=Номер, reason, valior, amount, state, createdOn, createdBy";
    
    
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
    var $singleTitle = 'Вътрешно Паричен Трансфер';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/money_add.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Vpt";
    
    
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
    var $singleLayoutFile = 'bank/tpl/SingleInternalMoneyTransfer.shtml';
    
    
	/**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('operationId', 'key(mvc=acc_Operations,select=name)', 'caption=Операция,width=6em,mandatory,silent');
    	$this->FLD('amount', 'double(decimals=2)', 'caption=Сума,width=6em,mandatory');
    	$this->FLD('currencyItem', 'acc_type_Item(select=numTitleLink)', 'caption=Валута,input=none');
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,width=6em,mandatory');
    	$this->FLD('reason', 'varchar(255)', 'caption=Основание,width=20em,input,mandatory');
    	$this->FLD('creditAccId', 'acc_type_Account()','caption=Кредит,width=300px,input=none');
    	$this->FLD('creditEnt1', 'acc_type_Item(select=numTitleLink)', 'caption=От->перо 1');
        $this->FLD('creditEnt2', 'acc_type_Item(select=numTitleLink)', 'caption=От->перо 2');
        $this->FLD('creditEnt3', 'acc_type_Item(select=numTitleLink)', 'caption=От->перо 3');
        $this->FLD('creditQuantity', 'double(minDecimals=2)', 'width=6em,caption=От->Сума');
        $this->FLD('debitAccId', 'acc_type_Account()','caption=Дебит,width=300px,input=none');
        $this->FLD('debitEnt1', 'acc_type_Item(select=numTitleLink)', 'caption=Към->перо 1');
        $this->FLD('debitEnt2', 'acc_type_Item(select=numTitleLink)', 'caption=Към->перо 2');
        $this->FLD('debitEnt3', 'acc_type_Item(select=numTitleLink)', 'caption=Към->перо 3');
        $this->FLD('debitQuantity', 'double(minDecimals=2)', 'width=6em,caption=Към->Сума');
        $this->FLD('rate', 'double(decimals=2)', 'caption=Валута->Курс,width=6em,input=none');
        $this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
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
    	$form->title = 'Нов Вътрешен Паричен Трансфер';
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
    		$operationId = $form->operationId;
    	}
    	
    	$operation = acc_Operations::getOperationInfo($operationId);
       
    	// Трябва документа да поддържа тази операция
    	$classId = core_Classes::fetchIdByName(get_called_class());
        expect($operation->document == $classId, 'Този документ не поддържа избраната операция');
        
        $debitAcc = $operation->debitAccount;
        $creditAcc = $operation->creditAccount;
        
      	foreach (array('debit' => 'Дебит', 'credit' => 'Кредит') as $type => $caption) {
            
            $acc = ${"{$type}Acc"};
            
            // Скриваме всички полета за пера, и после показваме само тези, за които съответната
            // (дебит или кредит) сметка има аналитичност.
            $form->setField("{$type}Ent1", 'input=none');
            $form->setField("{$type}Ent2", 'input=none');
            $form->setField("{$type}Ent3", 'input=none');
    		
            ($type == 'debit') ? $division = tr('Към') : $division = tr('От');
            
    		// Намираме на коя позиция се намира номенклатурата Валути, ако я има
    		$pos = acc_Lists::getPosition(${"{$type}Acc"}->rec->systemId, 'currency_CurrenciesAccRegIntf');
    		
    		
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
    			
                // Ако няма превалутираме и дебитната сметка има перо валута,
				// ние правим това поле скрито, то ще се попълни със
				// стойнста от кредитното поле
				if($pos == $i) {
					$form->setField("{$type}Ent{$i}", "input=hidden");
				}
			}
      			if($pos && $type == 'credit') {
		        	$form->getField("currencyItem")->type->params['lists'] = $list->rec->num;
		            $form->setField("currencyItem", "input");
        		}
        		
	    		// Ако не превалутираме нямаме нужда да показваме количествоо
	            $form->setField("{$type}Quantity", 'input=hidden');
	        }
        
        $today = dt::verbal2mysql();
        $form->setDefault('valior', $today);
        $form->setReadOnly('operationId');
		
        // Перото на валутата по подразбиране
        $currencyClassId = currency_Currencies::getClassId();
        $currencyId = currency_Currencies::getIdByCode();
        $currencyItem = acc_Items::fetch("#objectId={$currencyId} AND #classId={$currencyClassId}");
       
        if($form->getField('currencyItem')->input != 'none') {
        	$form->setDefault('currencyItem', $currencyItem->id);
        }
        // Ако имаме втора аналитичност валута, слагаме и дефолт стойност
        if($form->getField('debitEnt2')->input != 'none') {
        	$form->setDefault('debitEnt2', $currencyItem->id);
        }
    	if($form->getField('creditEnt2')->input != 'none') {
        	$form->setDefault('creditEnt2', $currencyItem->id);
        }
     }
    
     
    /**
     * Проверка след изпращането на формата
     */
    function on_AfterInputEditForm($mvc, $form)
    { 
    	if ($form->isSubmitted()){
    		
    		$rec = &$form->rec;
    		
    		$accPeriods = cls::get('acc_Periods');
		    $period = $accPeriods->fetchByDate($rec->valior);
		    $baseCurrency = currency_Currencies::getCodeById($period->baseCurrencyId);
		    
    		$operation = acc_Operations::fetch($rec->operationId);
    		$rec->debitAccId = $operation->debitAccount;
    		$rec->creditAccId = $operation->creditAccount;
    		
    		// Ако няма превалутиране  проверяваме дали валутите на
    		// дебитната сметка съвпадат с тези на кредитната
    		$mvc->validateForm($form);
    		$rec->debitQuantity = $rec->amount;
    		$rec->creditQuantity = $rec->amount;
    			
    		$quantityOnly = ($debitAcc->rec->type == 'passive' && $debitAcc->rec->strategy) ||
        	($creditAcc->rec->type == 'active' && $creditAcc->rec->strategy);
    		if(!$quantityOnly) {
    			$currencyId = static::getCurrency('debit', $rec);
		    	$currencyCode = currency_Currencies::getCodeById($currencyId);
    			$rate = currency_CurrencyRates::getRateBetween($currencyCode, $baseCurrency, $rec->valior);
    			$rec->rate = $rate;
    		}
    	}
    }
    
    
    /**
     * При Каса -> Каса
     *    Валутата на касата към която местим става същата като тази на
     *    касата от която местим
     * При Каса -> Банка
     * 	  Проверява дали валутата на касата отговаря на тази на избраната
     * 	  банкова сметка, ако не - сетва грешка
     * При Банка -> Каса
     *    Валутата на касата към която прехвърляме приема стойноста на
     *    валутата на сметката от която местим
     * При Банка -> Банка 
     *    Проверява дали банковата сметка към която прехвърляме да е 
     *    същата като тази на банката от която местим, ако не - сетва 
     *    грешка
     * 
     * @param core_Form $form 
     */
    function validateForm($form)
    {
    		$rec = &$form->rec;
    		
    		// Намираме дали перо от кредита и дебита и дали е банкова сметка
    		// Ако е банкова сметка намераме на коя позиция е точно, ако няма
    		// съответната променлива е NULL ако в дебитния или кредитния акаунт
    		// няма номенклатура банкови сметки 
    		$debitAcc = $rec->debitAccId;
    		$creditAcc = $rec->creditAccId;
    		$debitBankPos = acc_Lists::getPosition($debitAcc, 'bank_OwnAccRegIntf');
    		$creditBankPos = acc_Lists::getPosition($creditAcc, 'bank_OwnAccRegIntf');	
    		
    		if($debitBankPos && $creditBankPos) {
    			
    			// Ако и Имаме Банкови сметки от двете страни
    			$debitCurrency = static::getCurrency('debit', $rec);
    			$creditCurrency = static::getCurrency('credit', $rec);
    			if($debitCurrency != $creditCurrency) {
    				$form->setError("debitEnt{$debitBankPos}", 'Банковата сметка е в друга валута !!!');
    			}
    			
    		} elseif(!$debitBankPos && !$creditBankPos) { 
    			
    			// Ако няма банкови сметки и движението е Каса -> Каса
    			$debitCurrencyPos = acc_Lists::getPosition($debitAcc, 'currency_CurrenciesAccRegIntf');
    			$creditCurrencyPos = acc_Lists::getPosition($creditAcc, 'currency_CurrenciesAccRegIntf');
    			
    			// Приемаме че дебитната валута е същата като кредитната
    			$rec->{"debitEnt{$debitCurrencyPos}"} = $rec->currencyItem;
    			$rec->{"creditEnt{$creditCurrencyPos}"} = $rec->currencyItem;
    		
    		
    		} elseif($debitBankPos && !$creditBankPos) {
    			$currencyItem = acc_Items::fetchField($rec->currencyItem,'objectId');
    			$creditCurrencyPos = acc_Lists::getPosition($creditAcc, 'currency_CurrenciesAccRegIntf');
    			$rec->{"creditEnt{$creditCurrencyPos}"} = $rec->currencyItem;
    			
    			// Ако движението е Каса -> Банка
    			$debitCurrency = static::getCurrency('debit', $rec);
    			if($debitCurrency != $currencyItem) {
    				$form->setError("debitEnt{$debitBankPos}", 'Банковата сметка е в друга валута !!!');
    			}
    		
    		} elseif(!$debitBankPos && $creditBankPos) {
    			
    			// Ако движението е Банка -> Каса
    			$creditBank = acc_Items::fetchField($rec->{"creditEnt{$creditBankPos}"},'objectId');
    			$creditOwnAcc = bank_OwnAccounts::getOwnAccountInfo($creditBank);
    			$debitCurrencyPos = acc_Lists::getPosition($debitAcc, 'currency_CurrenciesAccRegIntf');
    			$currencyClassId = currency_Currencies::getClassId();
        		$currencyItem = acc_Items::fetch("#objectId={$creditOwnAcc->currencyId} AND #classId={$currencyClassId}");
    			
        		// Валутата на касата е същата като тази на банковата сметка
        		$rec->{"debitEnt{$debitCurrencyPos}"} = $currencyItem->id;
    		}
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
    		
    		// Изчисляваме равностойността на сумата в основната валута
    		
    		if($rec->rate != '1') {
	    		$double = cls::get('type_Double');
	    		$double->params['decimals'] = 2;
	    		$row->equals = $double->toVerbal($rec->amount * $rec->rate);
    		
	    		$accPeriods = cls::get('acc_Periods');
			    $period = $accPeriods->fetchByDate($rec->valior);
			    $row->baseCurrency = currency_Currencies::getCodeById($period->baseCurrencyId);
    		}
    		
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
        
      	foreach(array('debit', 'credit') as $type) {
        	${"{$type}Quantity"} = $rec->{"{$type}Quantity"};
			${"{$type}Price"} = $rec->rate;
        	foreach (range(1, 3) as $n) {
				if(!$rec->{"{$type}Ent{$n}"}) {
					
					// Ако записа е празен го скипваме
					${"{$type}Item{$n}"} = NULL;
					continue;
				}
			${"{$type}Item{$n}"} = new stdClass();
			${"{$type}ItemRec{$n}"} = acc_Items::fetch($rec->{"{$type}Ent{$n}"});
        	${"{$type}Item{$n}"}->cls = ${"{$type}ItemRec{$n}"}->classId;
        	${"{$type}Item{$n}"}->id = ${"{$type}ItemRec{$n}"}->objectId;
        	}
        }
    	
		// Подготвяме информацията която ще записваме в Журнала
        $result = (object)array(
            'reason' => $rec->reason,   // основанието за ордера
            'valior' => $rec->valior,   // датата на ордера
            'totalAmount' => $rec->amount * $rec->rate,
            'entries' => array( (object)array(
                'amount' => $rec->amount,
                'debitAcc' => $rec->debitAccId,
                'debitItem1' => $debitItem1,
                'debitItem2' => $debitItem2,
                'debitItem3' => $debitItem3,
                'debitQuantity' => $debitQuantity,
                'debitPrice' => $debitPrice,
                'creditAcc' => $rec->creditAccId,
                'creditItem1' => $creditItem1,
                'creditItem2' => $creditItem2,
                'creditItem3' => $creditItem3,
                'creditQuantity' => $creditQuantity,
                'creditPrice' => $creditPrice,
            ))
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