<?php


/**
 * Документ за Приходни касови ордери
 *
 *
 * @category  bgerp
 * @package   cash
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
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
        $update = $notDelete = array();
        foreach ($payments as $obj) {
            $amount = core_Type::getByName('double')->fromVerbal($obj->amount);
            $update[$obj->paymentId] = (object) array('documentId' => $rec->id, 'paymentId' => $obj->paymentId, 'amount' => $amount);
            $paymentId = $obj->paymentId;
            $notDelete[$paymentId] = $paymentId;
            
            if(is_array($rec->exPayments)){
                $foundRec = array_filter($rec->exPayments, function ($a) use ($paymentId) { return $paymentId == $a->paymentId;});
                if(isset($foundRec->id)){
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


    /**
     * Преди рендиране на тулбара
     */
    public static function on_BeforeRenderSingleToolbar($mvc, &$res, &$data)
    {
        $rec = $data->rec;

        if(isset($data->toolbar->buttons['btnConto'])){

            // Ако има направено безналично плащане с карта и има периферия за банковия терминал
            $cardPaymentId = pos_Setup::get('CARD_PAYMENT_METHOD_ID');
            $cardPaymentRec = cash_NonCashPaymentDetails::fetch("#paymentId = {$cardPaymentId} AND #documentId = {$rec->id}");

            if(!is_object($cardPaymentRec)) return;
            $amount = round($cardPaymentRec->amount * $rec->rate, 2);

            $deviceRec = peripheral_Devices::getDevice('bank_interface_POS');
            if(!is_object($deviceRec)) return;

            $data->toolbar->removeBtn('btnConto');
            $warning = $mvc->getContoWarning($rec, $rec->isContable);
            $errorUrl = toUrl($mvc->getSingleUrlArray($rec), 'local');
            $data->_enableCardPayment = true;

            // Подмяна на бутона за контиране с такъв за обръщане към банковия терминал
            $hash = bank_interface_POS::getPaymentHash($mvc->getClassId(), $rec->id);
            $successUrl = toUrl(array($mvc, 'successfullcardpayment', $rec->id, 'hash' => $hash), 'local');
            $btnAttr = array('id' => "btnConto{$rec->containerId}", 'warning' => $warning, 'data-amount' => $amount, 'data-errorUrl' => $errorUrl, 'class' => 'cardPaymentBtn', 'ef_icon' => 'img/16/tick-circle-frame.png', 'title' => 'Контиране на документа');
            $btnAttr['data-successUrl'] = $successUrl;
            $btnAttr['data-returnUrl'] = core_Packs::isInstalled('bgfisc') ? toUrl(array($mvc, 'contocash', $rec->id), 'local') : toUrl($mvc->getContoUrl($rec->id));
            $btnAttr['data-onerror'] = tr('Неуспешно плащане с банковия терминал|*!');
            $btnAttr['data-oncancel'] = tr('Отказвано плащане с банков терминал|*!');

            $data->toolbar->addFnBtn('Контиране', '', $btnAttr);
        }
    }


    /**
     * Успешно потвърждаване с банковия терминал
     */
    public function act_successfullcardpayment()
    {
        $isAjax = Request::get('ajax_mode');
        $success = true;
        $hash = Request::get('hash', 'varchar');

        if(!$hash) {
            $success = false;
            if(!$isAjax) expect(false);
        }

        expect($id = Request::get('id', 'int'));
        expect($rec = static::fetch($id));
        $hashIsOk = ($hash == bank_interface_POS::getPaymentHash($this->getClassId(), $id));
        if(!$hashIsOk) {
            $success = false;
            if(!$isAjax) expect(false);
        }

        if(!$this->haveRightFor('conto', $rec)){
            $success = false;
            if(!$isAjax) expect(false);
        }

        $res = array();
        if($success){

            // Записване на допълнителната информация за банковото плащане
            $param = Request::get('param', 'enum(manual,card)');
            $cardPaymentId = pos_Setup::get('CARD_PAYMENT_METHOD_ID');
            $cardPaymentRec = cash_NonCashPaymentDetails::fetch("#paymentId = {$cardPaymentId} AND #documentId = {$rec->id}");
            $cardPaymentRec->param = $param;
            cash_NonCashPaymentDetails::save($cardPaymentRec);

            if($param == 'card'){
                $this->logWrite('Авт. потвърдено картово плащане', $id);
            } else {
                $this->logWrite('Ръчно потвърдено картово плащане', $id);
            }
            core_Statuses::newStatus('Успешно плащане с карта');

            $resObj = new stdClass();
            $resObj->func = 'successfullCardPayment';
            $resObj->arg = array('url' => Request::get('redirectUrl'), 'redirect' => !core_Packs::isInstalled('bgfisc'));
            $res[] = $resObj;
        }

        // Показване веднага на чакащите статуси
        $hitTime = Request::get('hitTime', 'int');
        $idleTime = Request::get('idleTime', 'int');
        $statusData = status_Messages::getStatusesData($hitTime, $idleTime);
        $res = array_merge($res, (array) $statusData);

        return $res;
    }


    /**
     * Вкарваме css файл за единичния изглед
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        if (Mode::isReadOnly()) return;
        if(!$data->_enableCardPayment) return;

        // Ако ще се плаща директно с банковия терминал
        $deviceRec = peripheral_Devices::getDevice('bank_interface_POS');
        if(!is_object($deviceRec)) return;

        $intf = cls::getInterface('bank_interface_POS', $deviceRec->driverClass);
        $tpl->append($intf->getJS($deviceRec), 'SCRIPTS');

        $tpl->push('cash/js/scripts.js', 'JS');
        jquery_Jquery::run($tpl, 'cashActions();');

        $manualConfirmBtn = ht::createFnBtn('Ръчно потвърждение', '', '', array('class' => 'modalBtn confirmPayment disabledBtn'));
        $manualCancelBtn = ht::createFnBtn('Назад', '', '', array('class' => 'closePaymentModal modalBtn disabledBtn'));

        $modalTpl =  new core_ET('<div class="fullScreenCardPayment" style="position: fixed; top: 0; z-index: 1002; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9);display: none;"><div style="position: absolute; top: 30%; width: 100%"><h3 style="color: #fff; font-size: 56px; text-align: center;">' . tr('Плащане с банковия терминал') .' ...<br> ' . tr('Моля, изчакайте') .'!</h3><div class="flexBtns">' . $manualConfirmBtn->getContent() . ' ' . $manualCancelBtn->getContent() . '</div></div></div>');
        $tpl->append($modalTpl);
    }
}
