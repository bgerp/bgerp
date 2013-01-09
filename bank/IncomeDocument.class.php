<?php 


/**
 * Приходен Банков Документ
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_IncomeDocument extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Приходен Банков Документ";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, bank_Wrapper, bank_DocumentWrapper, plg_Printing,
     	plg_Sorting, doc_plg_BusinessDoc,doc_DocumentPlg,
     	plg_Search,doc_plg_MultiPrint, bgerp_plg_Blank, acc_plg_Contable';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт, number=Номер, reason, valior, amount, currencyId, state, createdOn, createdBy";
    
    
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
    var $singleTitle = 'Приходен Банков Документ';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/money_add.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Pbd";
    
    
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
    var $singleLayoutFile = 'bank/tpl/SingleIncomeDocument.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'valior, contragentName';
    

    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,width=6em,mandatory');
    	$this->FLD('reason', 'varchar(255)', 'caption=Основание,width=100%,mandatory');
    	$this->FLD('operationId', 'key(mvc=acc_Operations,select=name)', 'caption=Операция,width=100%,mandatory,silent');
    	$this->FLD('ownAccount', 'key(mvc=bank_OwnAccounts,select=bankAccountId)', 'caption=В сметка->IBAN,mandatory,width=16em');
    	$this->FLD('contragentName', 'varchar(255)', 'caption=От->Контрагент,mandatory,width=16em');
    	$this->FLD('contragentId', 'int', 'input=hidden,notNull');
    	$this->FLD('contragentClassId', 'key(mvc=core_Classes,select=name)', 'input=hidden,notNull');
    	$this->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Валута->Сума,mandatory,width=6em');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута->Код,width=6em');
    	$this->FLD('rate', 'double(decimals=2)', 'caption=Валута->Курс,width=6em');
    	$this->FLD('debitAccId', 'acc_type_Account()','caption=debit,width=300px,input=none');
        $this->FLD('creditAccId', 'acc_type_Account()','caption=Кредит,width=300px,input=none');
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
        $this->FNC('isContable', 'int', 'column=none');
    }
    
    
	/**
     * 
     */
	static function on_CalcIsContable($mvc, $rec)
    {
        $rec->isContable =
        ($rec->state == 'draft');
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$form = &$data->form;
    	
    	$today = dt::verbal2mysql();
        $form->setDefault('valior', $today);
        $form->setDefault('currencyId', currency_Currencies::getIdByCode());
    	$form->setDefault('ownAccount', bank_OwnAccounts::getCurrent());
    	$form->setReadOnly('ownAccount');
    	
        $options = acc_Operations::getPossibleOperations(get_called_class());
        $form->setOptions('operationId', $options);
     
        static::getContragentInfo($form);
    }
    
     
     /**
      * @TODO
      */
     static function getContragentInfo(core_Form $form)
     {
     	$folderId = $form->rec->folderId;
    	$contragentId = doc_Folders::fetchCoverId($folderId);
    	$contragentClassId = doc_Folders::fetchField($folderId, 'coverClass');
    	
   		// Информацията за контрагента на папката
    	expect($contragentData = doc_Folders::getContragentData($folderId), "Проблем с данните за контрагент по подразбиране");
    	
    	if($contragentData) {
    		if($contragentData->company) {
    			
    			$form->setDefault('contragentName', $contragentData->company);
    		} elseif ($contragentData->name) {
    			
    			// Ако папката е на лице, то вносителя по дефолт е лицето
    			$form->setDefault('contragentName', $contragentData->name);
    		}
    		$form->setReadOnly('contragentName');
    	}
     }
    
     
     /**
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->number = static::getHandle($rec->id);
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