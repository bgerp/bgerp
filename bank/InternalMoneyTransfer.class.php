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
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    //var $searchFields = 'valior, contragentName';
    

    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('amount', 'double(decimals=2)', 'caption=Сума,width=6em,mandatory');
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,width=6em,mandatory');
    	$this->FLD('reason', 'varchar(255)', 'caption=Основание,width=100%,mandatory');
    	$this->FLD('operationId', 'key(mvc=acc_Operations,select=name)', 'caption=Операция,width=6em,mandatory,silent');
        $this->FLD('creditAccId', 'acc_type_Account()','caption=Кредит,width=300px,input=none');
    	$this->FLD('creditEnt1', 'acc_type_Item(select=numTitleLink)', 'caption=От->перо 1');
        $this->FLD('creditEnt2', 'acc_type_Item(select=numTitleLink)', 'caption=От->перо 2');
        $this->FLD('creditEnt3', 'acc_type_Item(select=numTitleLink)', 'caption=От->перо 3');
        $this->FLD('debitAccId', 'acc_type_Account()','caption=Дебит,width=300px,input=none');
        $this->FLD('debitEnt1', 'acc_type_Item(select=numTitleLink)', 'caption=Към->перо 1');
        $this->FLD('debitEnt2', 'acc_type_Item(select=numTitleLink)', 'caption=Към->перо 2');
        $this->FLD('debitEnt3', 'acc_type_Item(select=numTitleLink)', 'caption=Към->перо 3');
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
    	// Очакваме и намираме коя е извършената операция
    	if(!$data->form->rec->id) {
    		expect($operationId = Request::get('operationId'));
    	} else {
    		$operationId = $data->form->rec->operationId;
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
            $data->form->setField("{$type}Ent1", 'input=none');
            $data->form->setField("{$type}Ent2", 'input=none');
            $data->form->setField("{$type}Ent3", 'input=none');
    		($type == 'debit') ? $division = tr('Към') : $division = tr('От');
            
    		foreach ($acc->groups as $i => $list) {
                if (!$list->rec->itemsCnt) {
                    return Redirect(array('acc_Items', 'list', 'listId'=>$list->rec->id), FALSE, tr("Липсва избор за |* \"{$list->rec->name}\""));
                }
                $data->form->getField("{$type}Ent{$i}")->type->params['lists'] = $list->rec->num;
                $data->form->setField("{$type}Ent{$i}", "mandatory,input,caption={$division}->" . $list->rec->name);
            }
        }
        
        $today = dt::verbal2mysql();
        $data->form->setDefault('valior', $today);
        $data->form->setReadOnly('operationId');

        // Перото на валутата по подразбиране
        $currencyClassId = currency_Currencies::getClassId();
        $currencyId = currency_Currencies::getIdByCode();
        $currencyItem = acc_Items::fetch("#objectId={$currencyId} AND #classId={$currencyClassId}");
        
        // Ако имаме втора аналитичност валута, слагаме и дефолт стойност
        if($data->form->getField('debitEnt2')->input != 'none') {
        	$data->form->setDefault('debitEnt2', $currencyItem->id);
        }
    	if($data->form->getField('creditEnt2')->input != 'none') {
        	$data->form->setDefault('creditEnt2', $currencyItem->id);
        }
    }
    
     
    /**
     * Проверка след изпращането на формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
    	if ($form->isSubmitted()){
    		if($form->rec->debitEnt1 == $form->rec->creditEnt1) {
    			
    			// Неможе началното и крайното перо да съвпадат
    			$form->setError('creditEnt1', 'Началната и крайната дестинация са същите !!!');
    		}
    		
    		$operation = acc_Operations::fetch($form->rec->operationId);
    		$form->rec->debitAccId = $operation->debitAccount;
    		$form->rec->creditAccId = $operation->creditAccount;
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
        
    	// Счетоводния период, в който ще се контира документа
        $accPeriods = cls::get('acc_Periods');
		$period = $accPeriods->fetchByDate($rec->valior);
		
		// За всеки дебитен и кредитен запис намираме перото му
		foreach(array('debit', 'credit') as $type) {
			${"{$type}Quantity"} = $rec->amount;
			${"{$type}Price"} = 1;
			foreach (range(1, 3) as $n) {
				if(!$rec->{"{$type}Ent{$n}"}) {
					
					// Ако записа е празен го скипваме
					${"{$type}Item{$n}"} = NULL;
					
					continue;
				}
				
				// Намираме кое перо съответства на записа
				${"{$type}Item{$n}Rec"} = acc_Items::fetch($rec->{"{$type}Ent{$n}"});
				if($n == 2) {
					
					// Очакваме второто перо да е валута
					expect(cls::haveInterface('currency_CurrenciesAccRegIntf', ${"{$type}Item{$n}Rec"}->classId));
					$itemCode = currency_Currencies::getCodeById(${"{$type}Item{$n}Rec"}->objectId);
					$baseCurrencyCode = currency_Currencies::getCodeById($period->baseCurrencyId);
					$rate = currency_CurrencyRates::getRateBetween($baseCurrencyCode, $itemCode, $rec->valior);
					${"{$type}Quantity"} = $rec->amount / $rate;
					${"{$type}Price"} = $rate;
				}
				
				// Инстанцираме обект който ще подадем в масива за контиране
				// с име (debit/credit)Item(1/2/3);
				${"{$type}Item{$n}"} = new stdClass();
				$className = cls::getClassName(${"{$type}Item{$n}Rec"}->classId);
				${"{$type}Item{$n}"}->cls = $className;
				${"{$type}Item{$n}"}->id = ${"{$type}Item{$n}Rec"}->objectId;
			}
		}
		
		// Подготвяме информацията която ще записваме в Журнала
        $result = (object)array(
            'reason' => $rec->reason,   // основанието за ордера
            'valior' => $rec->valior,   // датата на ордера
            'totalAmount' => $rec->amount,
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