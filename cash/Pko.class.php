<?php


/**
 * Документ за Приходни касови ордери
 *
 *
 * @category  bgerp
 * @package   cash
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
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
    public $title = 'Приходни касови ордери';
    
    
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
    public $abbr = 'Pko';
    
    
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
    public $newBtnGroup = '4.1|Финанси';
    
    
    /**
     * Кое поле отговаря на броилия парите
     */
    protected $personDocumentField = 'depositor';
    
    
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
     * Описание на модела
     */
    public function description()
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
        
        // Добавяне на таблица за избор на безналични плащания
        $rec->exPayments = cash_NonCashPaymentDetails::getPaymentsTableArr($rec->id, $mvc->getClassId());
        $form->FLD('payments', "table(columns=paymentId|amount,captions=Плащане|Сума,validate=cash_NonCashPaymentDetails::validatePayments)", "caption=Безналично плащане->Избор,before=contragentName");
        
        $form->setFieldTypeParams('payments', array('paymentId_opt' => array('' => '') + cls::get('cond_Payments')->makeArray4Select('title', '#currencyCode IS NULL OR #currencyCode = ""')));
        $form->setDefault('payments', $rec->exPayments);
        $rec->exPayments = type_Table::toArray($rec->exPayments);
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    protected static function on_AfterSubmitInputEditForm($mvc, $form)
    {
        $rec = &$form->rec;
        $rec->nonCashPayments = array();
        
        $nonCashSum = 0;
        $payments = type_Table::toArray($rec->payments);
        array_walk($payments, function($a) use (&$nonCashSum){
            $amount = core_Type::getByName('double')->fromVerbal($a->amount);
            $nonCashSum += $amount;
        });
        
        if ($nonCashSum > $rec->amount) {
            $form->setError('payments', 'Общата сума на безналичните методи за плащане е над тази от ордера');
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        if(empty($rec->payments) && empty($rec->exPayments)) return;
        
        $payments = type_Table::toArray($rec->payments);
        
        // Обновяване на безналичните плащания ако има
        $update = $delete = $notDelete = array();
        foreach ($payments as $obj) {
            $amount = core_Type::getByName('double')->fromVerbal($obj->amount);
            $update[$obj->paymentId] = (object) array('documentId' => $rec->id, 'paymentId' => $obj->paymentId, 'amount' => $amount);
            $paymentId = $obj->paymentId;
            $notDelete[$paymentId] = $paymentId;
            
            if(is_array($rec->exPayments)){
                $foundRec = array_filter($rec->exPayments, function ($a) use ($paymentId) { return $paymentId == $a->paymentId;});
                if(is_object($foundRec)){
                    $update[$obj->paymentId]->id = $foundRec->id;
                }
            }
        }
        
        // Ъпдейт на нужните записи
        if (countR($update)) {
            cls::get('cash_NonCashPaymentDetails')->saveArray_($update);
        }
        
        // Изтриване на старите записи
        if(is_array($rec->exPayments)){
            $delete = array_filter($rec->exPayments, function ($a) use ($notDelete) { return !array_key_exists($a->paymentId, $notDelete);});
            if (countR($delete)) {
                foreach ($delete as $obj) {
                    cash_NonCashPaymentDetails::delete($obj->id);
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
        foreach ($operations as $sysId => $op) {
            if ($op['debit'] == static::$baseAccountSysId) {
                $options[$sysId] = $op['title'];
            }
        }
        
        return $options;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'changeline' && isset($rec)){
            if($rec->isReverse == 'yes'){
                $requiredRoles = 'no_one';
            }
        }
    }
}
