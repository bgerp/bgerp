<?php



/**
 * Документ за Приходни касови ордери
 *
 *
 * @category  bgerp
 * @package   cash
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cash_Pko extends cash_Document
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=cash_transaction_Pko, bgerp_DealIntf, email_DocumentIntf, doc_ContragentDataIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Приходни касови ордери";
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Приходен касов ордер';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/money_add.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Pko";
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'cash/tpl/Pko.shtml';

    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.1|Финанси";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	// Зареждаме полетата от бащата
    	parent::getFields($this);
    	$this->FLD('depositor', 'varchar(255)', 'caption=Контрагент->Броил,mandatory');
    }
	
	
    /**
     *  Обработка на формата за редакция и добавяне
     */
    public static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$folderId = $data->form->rec->folderId;
    	$form = &$data->form;
    	
    	$contragentId = doc_Folders::fetchCoverId($folderId);
        $contragentClassId = doc_Folders::fetchField($folderId, 'coverClass');
    	$form->setDefault('contragentId', $contragentId);
        $form->setDefault('contragentClassId', $contragentClassId);
    	
        expect($origin = $mvc->getOrigin($form->rec));
        $dealInfo = $origin->getAggregateDealInfo();
        $pOperations = $dealInfo->get('allowedPaymentOperations');
        
        $options = self::getOperations($pOperations);
        expect(count($options));
        
        // Използваме помощната функция за намиране името на контрагента
    	$form->setDefault('reason', "Към документ #{$origin->getHandle()}");
    	if($dealInfo->get('dealType') != findeals_Deals::AGGREGATOR_TYPE){
    		$amount = ($dealInfo->get('amount') - $dealInfo->get('amountPaid')) / $dealInfo->get('rate');
    		if($amount <= 0) {
    		 	$amount = 0;
    		}
    		 		 
    		$defaultOperation = $dealInfo->get('defaultCaseOperation');
    		if($defaultOperation == 'customer2caseAdvance'){
    		 	$amount = $dealInfo->get('agreedDownpayment') / $dealInfo->get('rate');
    		 }
    	}
    		 	
    	// Ако потребителя има права, логва се тихо
	    if($caseId = $dealInfo->get('caseId')){
	    	cash_Cases::selectCurrent($caseId);
	    }
    		 	
	    $cId = currency_Currencies::getIdByCode($dealInfo->get('currency'));
	    $form->setDefault('dealCurrencyId', $cId);
	    $form->setDefault('currencyId', $cId);
    		 		
    	if($dealInfo->get('dealType') == sales_Sales::AGGREGATOR_TYPE){
    		$dAmount = currency_Currencies::round($amount, $dealInfo->get('currency'));
    		if($dAmount != 0){
    		 	$form->setDefault('amountDeal',  $dAmount);
    		 }
    	}
    	
    	// Поставяме стойности по подразбиране
    	$form->setDefault('valior', dt::today());
        
        if($contragentClassId == crm_Companies::getClassId()){
    		$form->setSuggestions('depositor', crm_Companies::getPersonOptions($contragentId, FALSE));
    	}
        
    	$form->setOptions('operationSysId', $options);
    	if(isset($defaultOperation) && array_key_exists($defaultOperation, $options)){
    		$form->setDefault('operationSysId', $defaultOperation);	
        }
        
    	$form->setDefault('peroCase', cash_Cases::getCurrent());
    	$cData = cls::get($contragentClassId)->getContragentData($contragentId);
    	$form->setReadOnly('contragentName', ($cData->person) ? $cData->person : $cData->company);
    	
    	$form->setField('amountDeal', array('unit' => "|*{$dealInfo->get('currency')}, |платени (погасени) по сделката|*"));
	
    	if($form->rec->currencyId != $form->rec->dealCurrencyId){
    		$form->setField('amount', 'input');
    	}
    }

    
    /**
     * Връща платежните операции
     */
    protected static function getOperations($operations)
    {
    	$options = array();
    	
    	// Оставяме само тези операции в които се дебитира основната сметка на документа
    	foreach ($operations as $sysId => $op){
    		if($op['debit'] == static::$baseAccountSysId){
    			$options[$sysId] = $op['title'];
    		}
    	}
    	
    	return $options;
    }
    
    function act_test()
    {
    	$r = cls::get('cash_Setup');
    	$r->updateDocuments();
    }
}
