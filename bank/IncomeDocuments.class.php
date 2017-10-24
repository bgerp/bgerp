<?php 


/**
 * Приходен банков документ
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_IncomeDocuments extends bank_Document
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=bank_transaction_IncomeDocument, bgerp_DealIntf, email_DocumentIntf';

    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Приходни банкови документи";
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Приходен банков документ';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/bank_add.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Pbd";
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'bank/tpl/SingleIncomeDocument.shtml';
    
    
    /**
     * Файл с шаблон за единичен изглед в мобилен
     */
    public $singleLayoutFileNarrow = 'bank/tpl/SingleIncomeDocumentNarrow.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.3|Финанси";
    
    
    /**
     * Основна сч. сметка
     */
    public static $baseAccountSysId = '503';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "termDate,valior=Вальор, title=Документ, reason, folderId, currencyId, amount, state, createdOn, createdBy";
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, termDate,valior,modifiedOn';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        parent::getFields($this);
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $form = &$data->form;
        $today = dt::verbal2mysql();
        
        $contragentId = doc_Folders::fetchCoverId($form->rec->folderId);
        $contragentClassId = doc_Folders::fetchField($form->rec->folderId, 'coverClass');
        $form->setDefault('contragentId', $contragentId);
        $form->setDefault('contragentClassId', $contragentClassId);
        
        expect($origin = $mvc->getOrigin($form->rec));
        $form->setOptions('ownAccount', bank_OwnAccounts::getOwnAccounts(FALSE));
        
        $mvc->setDefaultsFromOrigin($origin, $form, $options);
        
        $form->setSuggestions('contragentIban', bank_Accounts::getContragentIbans($form->rec->contragentId, $form->rec->contragentClassId));
        $form->setDefault('currencyId', acc_Periods::getBaseCurrencyId($today));
        
        $form->setOptions('operationSysId', $options);
        
        if(isset($form->defaultOperation) && array_key_exists($form->defaultOperation, $options)){
        	$form->setDefault('operationSysId', $form->defaultOperation);
        }
        
        $cData = cls::get($contragentClassId)->getContragentData($contragentId);
        $form->setReadOnly('contragentName', ($cData->person) ? $cData->person : $cData->company);
    }
    
    
    /**
     * Връща платежните операции
     */
    protected static function getOperations($operations)
    {
        $options = array();
        
        // Оставяме само тези операции, в които се дебитира основната сметка на документа
        foreach ($operations as $sysId => $op){
            if($op['debit'] == static::$baseAccountSysId){
                $options[$sysId] = $op['title'];
            }
        }
        
        return $options;
    }
    
    
    /**
     * Поставя бутони за генериране на други банкови документи възоснова
     * на този, само ако документа е "чернова"
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        
    	if($rec->state == 'draft') {
            if(bank_PaymentOrders::haveRightFor('add', (object)array('originId' => $rec->containerId, 'folderId' => $rec->folderId))) {
                $data->toolbar->addBtn('Платежно нареждане', array('bank_PaymentOrders', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE, ''), NULL, 'ef_icon=img/16/pln.png,title=Създаване на ново платежно нареждане');
            }
            
            if(bank_DepositSlips::haveRightFor('add', (object)array('originId' => $rec->containerId, 'folderId' => $rec->folderId))){
                $data->toolbar->addBtn('Вносна бележка', array('bank_DepositSlips', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE, ''), NULL, 'ef_icon=img/16/vnb.png,title=Създаване на нова вносна бележка');
            }
        }
    }
}
