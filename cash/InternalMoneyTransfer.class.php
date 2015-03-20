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
class cash_InternalMoneyTransfer extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=cash_transaction_InternalMoneyTransfer';
   
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Вътрешни касови трансфери";
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    var $onlyFirstInThread = TRUE;
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, cash_Wrapper,acc_plg_Contable, acc_plg_DocumentSummary,
     	plg_Sorting,doc_DocumentPlg, plg_Printing, plg_Search, doc_plg_MultiPrint, bgerp_plg_Blank, acc_plg_Contable, doc_SharablePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт, valior, title=Документ, reason, folderId, currencyId=Валута, amount, state, createdOn, createdBy";
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,cash';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,cash';
	
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Вътрешен касов трансфер';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/money.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Cvt";
    
    
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
    var $canConto = 'ceo, acc, cash';
    
    
    /**
     * Кой може да сторнира
     */
    var $canRevert = 'cash, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'bank/tpl/SingleInternalMoneyTransfer.shtml';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "4.6|Финанси";
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'reason,creditCase,debitBank,debitCase, id';
    
    
    /**
     * Позволени операции
     */
    public $allowedOperations = array('case2case' => array('debit' => '501', 'credit' => '501'),
    							   'case2bank' => array('debit' => '503', 'credit' => '501'));
    
    
	/**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('operationSysId', 'enum(case2case=Вътрешeн касов трансфер,case2bank=Захранване на банкова сметка)', 'caption=Операция,mandatory,silent');
    	$this->FLD('amount', 'double(decimals=2)', 'caption=Сума,mandatory,summary=amount');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута');
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,mandatory');
    	$this->FLD('reason', 'varchar(255)', 'caption=Основание,input,mandatory');
    	$this->FLD('creditAccId', 'acc_type_Account()','caption=Кредит,input=none');
    	$this->FLD('creditCase', 'key(mvc=cash_Cases, select=name)','caption=От->Каса');
    	$this->FLD('debitAccId', 'acc_type_Account()','caption=Дебит,input=none');
        $this->FLD('debitCase', 'key(mvc=cash_Cases, select=name)','caption=Към->Каса,input=none');
    	$this->FLD('debitBank', 'key(mvc=bank_OwnAccounts, select=bankAccountId)','caption=Към->Банк. сметка,input=none');
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
		cash_Cases::prepareCaseFilter($data, array('creditCase', 'debitCase'));
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
	   
    	if($folderId = Request::get('folderId')){
    		if($folderId != cash_Cases::fetchField(cash_Cases::getCurrent(), 'folderId')){
	        	return Redirect(array('cash_Cases', 'list'), FALSE, "Документът не може да се създаде в папката на неактивна каса");
	        }
        }
        
        // Има ли вече зададено основание? 
        if (Request::get('operationSysId', 'varchar')) {
            
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
    	$form->FNC('operationSysId', 'enum(case2case=Вътрешeн касов трансфер,case2bank=Захранване на банкова сметка)', 'input,caption=Операция');
    	$form->FNC('folderId', 'key(mvc=doc_Folders,select=title)', 'input=hidden,caption=Папка');
    	$form->title = 'Нов вътрешен касов трансфер';
        $form->toolbar->addSbBtn('Напред', '', 'ef_icon = img/16/move.png, title=Продължете напред');
        $form->toolbar->addBtn('Отказ', toUrl(array('cash_InternalMoneyTransfer', 'list')),  'ef_icon = img/16/close16.png, title=Прекратяване на действията');
        
       	$folderId = cash_Cases::forceCoverAndFolder(cash_Cases::getCurrent());
       	$form->setDefault('folderId', $folderId);
        
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
    		expect($operationSysId = Request::get('operationSysId'));
    	} else {
    		$operationSysId = $form->rec->operationSysId;
    	}
        
        switch($operationSysId) {
        	case "case2case":
        		$form->setField("debitCase", "input");
        		break;
        	case "case2bank":
        		$form->setField("debitBank", "input");
        		$form->setOptions("debitBank", bank_OwnAccounts::getOwnAccounts());
        		break;
        }
        $form->setReadOnly('operationSysId');
        $today = dt::verbal2mysql();
        $form->setDefault('valior', $today);
        $form->setDefault('currencyId', acc_Periods::getBaseCurrencyId($today));
      	$form->setReadOnly('creditCase', cash_Cases::getCurrent());
     }
    
     
    /**
     * Проверка след изпращането на формата
     */
    function on_AfterInputEditForm($mvc, $form)
    { 
    	if ($form->isSubmitted()){
    		
    		$rec = &$form->rec;
    		
    		$rec->debitAccId = $mvc->allowedOperations[$rec->operationSysId]['debit'];
    		$rec->creditAccId = $mvc->allowedOperations[$rec->operationSysId]['credit'];
    		
    		// Проверяваме дали валутите на дебитната сметка съвпадат
    		// с тези на кредитната
    		$mvc->validateForm($form);
    	}
    }
    
    
    /**
     * При Каса -> Каса
     *    Валутата на касата към която местим става същата като тази на
     *    касата от която местим
     * При Каса -> Банка
     * 	  Проверява дали валутата на касата отговаря на тази на избраната
     * 	  банкова сметка, ако не - сетва грешка
     *
     * @param core_Form $form 
     */
    function validateForm($form)
    {
    	$rec = &$form->rec;
    	if($rec->operationSysId == 'case2case') {
    		$caseRec = cash_Cases::fetch($rec->debitCase);
    		if($caseRec->autoShare == 'yes'){
    			$rec->sharedUsers = keylist::merge($rec->sharedUsers, $caseRec->cashiers);
    			$rec->sharedUsers = keylist::removeKey($rec->sharedUsers, core_Users::getCurrent());
    		}
    		
    		// Двете Каси трябва да са различни
    	if($rec->creditCase == $rec->debitCase) {
    			$form->setError("debitCase", 'Дестинацията е една и съща !!!');
    		} 
    	} elseif($rec->operationSysId == 'case2bank') {
    		$bankRec = bank_OwnAccounts::fetch($rec->debitBank);
    		if($bankRec->autoShare == 'yes'){
    			$rec->sharedUsers = keylist::removeKey($bankRec->operators, core_Users::getCurrent());
    		}
    		
    		$debitInfo = bank_OwnAccounts::getOwnAccountInfo($rec->debitBank);
    		if($debitInfo->currencyId != $rec->currencyId) {
    			$form->setError("debitBank", 'Банковата сметка е в друга валута !!!');
    		}
    	} 
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->title = $mvc->getLink($rec->id, 0);
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    	}	
    	
    	if($fields['-single']) {
    		$row->currency = currency_Currencies::getCodeById($rec->currencyId);
    		
    		// Изчисляваме равностойността на сумата в основната валута
    		if($rec->rate != '1') {
	    		$double = cls::get('type_Double');
	    		$double->params['decimals'] = 2;
	    		$equals = currency_CurrencyRates::convertAmount($rec->amount, $rec->valior, $row->currency);
    			$row->equals = $mvc->getFieldType('amount')->toVerbal($equals);
	    		$row->baseCurrency = acc_Periods::getBaseCurrencyCode($rec->valior);
    		}
	    	
	    	$row->creditCase = cash_Cases::getHyperLink($rec->creditCase, TRUE);
	    	
	    	if($rec->debitCase){
	    		$row->debitCase = cash_Cases::getHyperLink($rec->debitCase, TRUE);
	    	}
	    	
	    	if($rec->debitBank){
	    		$row->debitBank = bank_OwnAccounts::getHyperLink($rec->debitBank, TRUE);
	    	}
    	}
    }
    
    
    /**
     * Поставя бутони за генериране на други банкови документи възоснова
     * на този, само ако документа е "чернова".
     */
	static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	if($data->rec->state == 'draft') {
	    	$data->toolbar->addBtn('Вносна бележка', array('bank_DepositSlips', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE, ''), NULL, 'ef_icon = img/16/view.png, title=Създаване на вносна бележка');
    	}
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
        if ($folderId == static::getDefaultFolder(NULL, FALSE) || doc_Folders::fetchCoverClassName($folderId) == 'cash_Cases') {
        	
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
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(__CLASS__);
    
    	return $self->singleTitle . " №$rec->id";
    }
}
