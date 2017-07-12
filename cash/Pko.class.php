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
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=cash_transaction_Pko, bgerp_DealIntf, email_DocumentIntf';
   
    
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
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'cash/tpl/Pko.shtml';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFileNarrow = 'cash/tpl/PkoNarrow.shtml';

    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.1|Финанси";
    
    
    /**
     * Кое поле отговаря на броилия парите
     */
    protected $personDocumentField = "depositor";
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'cash_NonCashPaymentDetails';
    

    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'cash_NonCashPaymentDetails';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'termDate';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, termDate,modifiedOn';

    
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
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	// Динамично добавяне на полета
    	$paymentArr = cash_NonCashPaymentDetails::getPaymentsArr($rec->id, $mvc->getClassId());
    	foreach ($paymentArr as $key => $obj){
    		$caption = cond_Payments::getTitleById($obj->paymentId);
    		$form->FLD($key, 'double(Min=0)', "caption=Безналично плащане->{$caption},before=contragentName,autohide");
    		$form->setDefault($key, $obj->amount);
    	}
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    protected static function on_AfterSubmitInputEditForm($mvc, $form)
    {
    	$rec = &$form->rec;
    	$rec->nonCashPayments = array();
    	
    	$arr = (array)$rec;
    	$nonCashSum = 0;
    	$keys = array();
    	foreach ($arr  as $key => $value){
    		if(strpos($key, '_payment') !== FALSE){
    			$nonCashSum += $value;
    			$keys[] = $key;
    			$rec->nonCashPayments[$key] = $value;
    		}
    	}
    		
    	if($nonCashSum > $rec->amount){
			$form->setError(implode(',', $keys), 'Общата сума на безналичните методи за плащане е над тази от ордера');
		}
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	if(is_array($rec->nonCashPayments)){
    		$update = $delete = array();
    		$paymentArr = cash_NonCashPaymentDetails::getPaymentsArr($rec->id, $mvc->getClassId());
    		
    		foreach ($rec->nonCashPayments as $key => $value){
    			if(!empty($value)){
    				$update[$key] = (object)array('documentId' => $rec->id, 'paymentId' => $paymentArr[$key]->paymentId, 'amount' => $value, 'id' => $paymentArr[$key]->id);
    			} else {
    				if(isset($paymentArr[$key]->id)){
    					$delete[] = $paymentArr[$key]->id;
    				}
    			}
    		}
    		
    		// Ъпдейт на нужните записи
    		if(count($update)){
    			cls::get('cash_NonCashPaymentDetails')->saveArray_($update);
    		}
    		
    		// Изтриване на старите записи
    		if(count($delete)){
    			foreach ($delete as $id){
    				cash_NonCashPaymentDetails::delete($id);
    			}
    		}
    	}
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
}
