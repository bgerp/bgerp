<?php 


/**
 * Документ за Нареждане Разписки
 *
 *
 * @category  bgerp
 * @package   cash
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_CashWithdrawOrders extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Нареждане Разписка";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, bank_Wrapper, bank_DocumentWrapper,
     	plg_Sorting,doc_DocumentPlg, plg_Printing,
     	plg_Search,doc_plg_MultiPrint, bgerp_plg_Blank, acc_plg_Contable';//, doc_plg_BusinessDoc
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "id, number=Номер, reason, valior, amount, currencyId, state, createdOn, createdBy";
    
    
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
    var $singleTitle = 'Нареждане Разписка';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/money_add.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Nr";
    
    
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
    
    
    var $canRevert = 'bank, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'bank/tpl/SingleCashWithdrawOrder.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'valior, proxyName';
    
    
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
    	$this->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума,mandatory,width=6em');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,width=6em');
    	$this->FLD('reason', 'varchar(255)', 'caption=Основание,width=100%,mandatory');
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,width=6em,mandatory');
    	$this->FLD('execBank', 'varchar(255)', 'caption=До->Банка,width=16em,mandatory');
    	$this->FLD('execBankBranch', 'varchar(255)', 'caption=До->Клон,width=16em');
        $this->FLD('execBankAdress', 'varchar(255)', 'caption=До->Адрес,width=16em');
    	$this->FLD('ordererName', 'varchar(255)', 'caption=Наредител->Име,mandatory,width=16em');
    	$this->FLD('ordererIban', 'key(mvc=bank_OwnAccounts,select=bankAccountId)', 'caption=Наредител->IBAN,mandatory,width=16em');
    	$this->FLD('proxyName', 'varchar(255)', 'caption=Упълномощено лице->Име,mandatory');
    	$this->FLD('proxyEgn', 'drdata_EgnType', 'caption=Упълномощено лице->ЕГН,mandatory');
    	$this->FLD('proxyIdcard', 'varchar(16)', 'caption=Упълномощено лице->Лк. No,mandatory');
    	$this->FLD('debitAccount', 'acc_type_Account(maxColumns=1)', 'caption=Упълномощено лице->Сч. сметка,mandatory');
    	$this->FLD('peroCase', 'key(mvc=cash_Cases,select=name)', 'caption=От каса,input=hidden');
    	$this->FLD('proxyId', 'int', 'input=hidden,notNull');
    	$this->FLD('proxyClassId', 'key(mvc=core_Classes,select=name)', 'input=hidden,notNull');
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
        $this->FNC('isContable', 'int', 'column=none');
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
    	$conf = core_Packs::getConfig('crm');
    	$myCompany = crm_Companies::fetch($conf->BGERP_OWN_COMPANY_ID);
    	$data->form->setDefault('ordererName', $myCompany->name);
    	$data->form->setDefault('ordererIban', bank_OwnAccounts::getCurrent());
    	$data->form->setReadOnly('ordererIban');
    	$data->form->setReadOnly('ordererName');
    	
    	$account = bank_OwnAccounts::getOwnAccountInfo();
    	$data->form->setDefault('execBank', $account->bank);
    	$data->form->setReadOnly('execBank');
    	
    	$today = date("d-m-Y", time());
    	$data->form->setDefault('valior', $today);
    	
    	static::getProxyInfo($data->form);
    	
    	//@TODO метод в за извличане на информацията от избраната собствена сметка
    }
    
    
    /**
     * 
     */
    static function getProxyInfo(core_Form $form)
    {
    $suggestions = array();
    	$cu = core_Users::getCurrent();
    	$cuRow = core_Users::recToVerbal($cu);
    	
    	//@TODO да го оптимизирам още
    	$suggestions[$cuRow->names] = $cuRow->names;
    	$list = acc_Lists::fetchBySystemId('accountableInd');
    	$itemsQuery = acc_Items::getQuery();
    	$itemsQuery->where("#lists LIKE '%{$list->id}%'");
    	while($itemRec = $itemsQuery->fetch()) {
    		$personRec = crm_Persons::fetch($itemRec->objectId);
    		$suggestions[$personRec->name] = $personRec->name;
    	}
    
    	$form->setSuggestions('proxyName',$suggestions);
    	/*
    	$folderClass = doc_Folders::fetchCoverClassName($folderId);
    	$proxyRec = $folderClass::fetch($proxyId);
    	$form->setDefault('proxyName', $proxyRec->name);
    	$form->setDefault('proxyEgn', $proxyRec->egn);
    	$form->setReadOnly('proxyName');
    	$form->setReadOnly('proxyEgn');*/
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	//@TODO
    	
    	$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
    	// При принтирането на 'Чернова' скриваме системите полета и заглавието
    	if(Mode::is('printing')){
    		if($rec->state == 'draft') {
    			unset($row->header);
    			unset($row->createdBy);
    			unset($row->createdOn);
    			unset($row->debitAccount);
    			unset($row->creditAccount);
    		}
    	}
    }
    
	/**
     * Вкарваме css файл за единичния изглед
     */
	static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$tpl->push('bank/tpl/css/belejka.css', 'CSS');
    }
    
    
    /**
   	 *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
   	 *  Създава транзакция която се записва в Журнала, при контирането
   	 */
    public static function getTransaction($id)
    {
    	//@TODO
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
    	
    	return $self->abbr . $rec->id;
    }
    
}