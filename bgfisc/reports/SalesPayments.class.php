<?php


/**
 * Мениджър на отчети относно: Данни за плащанията по продажбите
 *
 * @category  bgerp
 * @package   bgfisc
 *
 * @author    Angel Trifonov <angel.trifonoff@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     НАП » Данни за плащанията по продажбите
 */
class bgfisc_reports_SalesPayments extends frame2_driver_TableData
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'n18_reports_SalesPayments';


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'acc,sales,ceo';


    /**
     * Кои полета са за избор на период
     */
    protected $periodFields = 'from,to';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('from', 'date', 'caption=От,after=compare,single=none');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none');
        
        $fieldset->FLD('operator', 'key(mvc=core_Users,select=names,allowEmpty)', 'caption=Оператор,after=to,placeholder=Всички,single=none');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_Form         $form
     * @param stdClass          $data
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted()) {
            
            // Проверка на периоди
            if (isset($form->rec->from, $form->rec->to) && ($form->rec->from > $form->rec->to)) {
                $form->setError('from,to', 'Началната дата на периода не може да бъде по-голяма от крайната.');
            }
        }
    }
    
    
    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     */
    protected function prepareRecs($rec, &$data = null)
    {
        $recs = array();
        
        $sQuery = bgfisc_Register::getQuery();
        
        if ($rec->from) {
            $sQuery->where(array("#createdOn >= '[#1#]'", $rec->from . ' 00:00:00'));
        }
        
        
        if ($rec->to) {
            $sQuery->where(array("#createdOn <= '[#1#]'",$rec->to . ' 23:59:59'));
        }
        
        while ($regRec = $sQuery -> fetch()) {
            $id = $regRec->id;
            
            //Уникален номер на продажбата
            $urn = $regRec->urn;
            
            //Системен номер на продажбата
            $sysNumber = $regRec->number;
            
            // Индивидуален номер на ФУ
            $cashRegNum = $regRec->cashRegNum;
            
            $RegClass = cls::get($regRec->classId);
            
            $className = $RegClass->className;
            
            
            //Продажби от POS
            if ($RegClass instanceof pos_Receipts) {
                $paymentDateArr = array();
                $dueAmount = $paidAmount = 0;
                
                $posRec = $className::fetch($regRec->objectId);
                
                //Ако по продажбата има сторниране
                $prntQuery = bgfisc_PrintedReceipts::getQuery();
                $prntQuery->where("#urnId = {$regRec->id}");
                
                if (empty($prntQuery->fetchAll())) {
                    continue;
                }
                
                $prntRcptArr = $prntRcptRevArr = array();
                
                while ($prntRcpt = $prntQuery->fetch()) {
                    if ($prntRcpt->type == 'reverted') {
                        
                        //$prntRcptRevArr - Сторниращи ФБ
                        $prntRcptRevArr[$prntRcpt->id] = $prntRcpt->id;
                    } else {
                        
                        //Дата на плащане (датата на издаване на касовата бележка)по продажбата
                        $paymentDate = dt::mysql2verbal($prntRcpt->createdOn, 'd.m.Y');
                        
                        //Код на оператор, регистрирал плащането
                        $userId = $prntRcpt->createdBy;
                        
                        $userId = str_pad(substr($userId, -4), 4, '0', STR_PAD_LEFT);
                    }
                }
                
                $stornoAmount = 0;
                if (!empty($prntRcptRevArr)) {
                    foreach ($prntRcptRevArr as $val) {
                        $prntRcptRev = bgfisc_PrintedReceipts::fetch($val);
                        $objectClassName = cls::get($prntRcptRev->classId)->className;
                        $revRec = $objectClassName::fetch($prntRcptRev->objectId);
                        
                        $stornoAmount += $revRec->paid;                                               //Сторнирана стойност
                    }
                }
                
                //Ако продажбата Е СТОРНИРАНА не влиза в отчета
                if (($posRec->paid + $stornoAmount) == 0) {
                    continue;
                }
                
                //Дата на откриване на продажбата
                $saleOpenDate = dt::mysql2verbal($posRec->createdOn, 'd.m.Y');
                
                //Дата на приключване на продажбата
                $saleCloseDate = dt::mysql2verbal($posRec->createdOn, 'd.m.Y');
                
                //Обща сума на продажбата
                $dueAmount = $posRec->total;
                
                $posDetQuery = pos_ReceiptDetails::getQuery();
                $posDetQuery->where("#receiptId = {$posRec->id}");
                while ($posDet = $posDetQuery->fetch()) {
                    if (strpos($posDet->action, 'sale') !== false) {
                        continue;
                    }
                    
                    $id .= $posDet->id;
                    
                    //Платена сума с ДДС
                    $paidAmount = $posRec->paid + $stornoAmount;
                    
                    //Вид плащане
                    list($pay, $payType) = explode('|', trim($posDet->action));
                    
                    $paymentType = $payType != -1 ? cond_Payments::fetch($payType)->title : 'В брой';
                }
                
                // добавяме в масива
                if (!array_key_exists($id, $recs)) {
                    $recs[$id] = (object) array(
                        'urn' => $urn,
                        'sysNumber' => $sysNumber,
                        'saleOpenDate' => $saleOpenDate,        //Дата на откриване на продажбата
                        'saleCloseDate' => $saleCloseDate,      //Дата на приключване на продажбата
                        
                        'dueAmount' => $dueAmount,              //обща сума по продажбата - в лв.
                        
                        'paymentDate' => $paymentDate,          //Дата на плащане (датата на издаване на касовата бележка)по продажбата
                        'userId' => $userId,                    //Код на оператор, регистрирал плащането
                        'paidAmount' => $paidAmount,            //Платена сума на продажбата - с ДДС
                        'paymentType' => $paymentType,          //Вид на плащането
                        'cashRegNum' => $cashRegNum,            //Индивидуален номер на ФУ
                    
                    
                    );
                }
            }
            
            
            ///////////////////////////////////////////////////////////////////
            //Продажби по договор за продажба
            if (!$RegClass instanceof pos_Receipts) {
                $paymentDateArr = array();
                
                $saleRec = $className::fetch($regRec->objectId);
                
                //Дата на откриване на продажбата
                $saleOpenDate = dt::mysql2verbal($saleRec->createdOn, 'd.m.Y');
                
                //Дата на приключване на продажбата
                if ($saleRec->closedOn) {
                    $saleCloseDate = dt::mysql2verbal($saleRec->closedOn, 'd.m.Y');
                } else {
                    $saleCloseDate = '';
                }
                
                //Отпечатани бележки по тази продажба
                $prntQuery = bgfisc_PrintedReceipts::getQuery();
                $prntQuery->where("#urnId = {$regRec->id}");
                
                $prntRcptArr = $prntRcptRevArr = array();
                
                while ($prntRcpt = $prntQuery->fetch()) {
                    if ($prntRcpt->type == 'reverted') {
                        
                        //$prntRcptRevArr - Сторно ФБ
                        $prntRcptRevArr[$prntRcpt->id] = $prntRcpt->id;
                    } else {
                        
                        //$prntRcptArr - Приходни ФБ
                        $prntRcptArr[$prntRcpt->id] = $prntRcpt->id;
                    }
                }
                
                $stornoAmountArr = array();
                if (!empty($prntRcptRevArr)) {
                    foreach ($prntRcptRevArr as $val) {
                        $prntRcptRev = bgfisc_PrintedReceipts::fetch($val);
                        $objectClassName = cls::get($prntRcptRev->classId)->className;
                        
                        //РКО към който е издаден ФБ
                        $rcoRec = $objectClassName::fetch($prntRcptRev->objectId);
                        
                        //Документа към който е издаден РКО (СклРаз примерно)
                        $revDocClassId = doc_Containers::fetch($rcoRec->fromContainerId)->docClass;
                        $revDocClassName = cls::get($revDocClassId)->className;
                        $revDocId = doc_Containers::fetch($rcoRec->fromContainerId)->docId;
                        $revDocRec = $revDocClassName::fetch($revDocId);
                        
                        //ПКО-то към който е издаден РКО на тази бележка
                        $pcoDoc = doc_Containers:: getDocument($rcoRec->originId);
                        $pcoKey = core_Classes::getId($pcoDoc->className).'|'.$pcoDoc->that.'|'.$prntRcptRev->id;
                        
                        //Сторнирани стойности по ПКО и сторно бележка
                        $stornoAmountArr[$pcoKey] += $rcoRec->amountDeal;
                    }
                }
                
                //Обща сума на продажбата
                $dueAmount = $saleRec->amountDeal;
                
                
                //Ако към УНП няма касова бележка, записа не влиза в справката
                if (empty($prntRcptArr)) {
                    continue;
                }
                
                
                foreach ($prntRcptArr as $val) {
                    $prntRcpt = bgfisc_PrintedReceipts::fetch($val);
                    $clsName = core_Classes::getName($prntRcpt->classId);
                    
                    $stornoAmount = 0;
                    foreach ($stornoAmountArr as $key => $val) {
                        list($clsId, $objId) = explode('|', trim($key));
                        if ($clsId == $prntRcpt->classId && $objId == $prntRcpt->objectId) {
                            $stornoAmount += $val;
                        }
                    }
                    
                    
                    //Платена сума с ДДС
                    $amountPaid = $clsName::fetchField($prntRcpt->objectId, 'amountDeal');
                    
                    //Дата на плащане (датата на издаване на касовата бележка)по продажбата
                    if ($prntRcpt->type == 'normal') {
                        $paymentDate = dt::mysql2verbal($prntRcpt->createdOn, 'd.m.Y');
                        $id .= '|'.$prntRcpt->id;
                        
                        //Код на оператор, регистрирал плащането
                        $userId = $prntRcpt->createdBy;
                        
                        $userId1 = str_pad(substr($userId, -4), 4, '0', STR_PAD_LEFT);
                        if ($saleRec->paymentMethodId) {
                            $paymentType = cond_PaymentMethods::fetch($saleRec->paymentMethodId)->type;
                        } else {
                            $paymentType = '';
                        }
                        
                        $nonCashQuery = cash_NonCashPaymentDetails::getQuery();
                        $nonCashQuery -> where("#classId = {$prntRcpt->classId} AND #objectId = {$prntRcpt->objectId}");
                        
                        $amountPaidArr = array();
                        
                        $nonPaidSum = 0;
                        while ($nonCashPayment = $nonCashQuery->fetch()) {
                            $nonPaidSum += $nonCashPayment-> amount;
                            
                            $amountPaidArr[$nonCashPayment->id] = (object) array(
                                'amount' => $nonCashPayment->amount,
                                'payType' => $nonCashPayment-> paymentId
                            );
                        }
                        
                        if (!empty($amountPaidArr)) {
                            $aaa = 10;
                            
                            $amountPaidArr[-1] = (object) array(
                                'amount' => $amountPaid - $nonPaidSum,
                                'payType' => -1,
                            );
                        } else {
                            $amountPaidArr[-1] = (object) array(
                                'amount' => $amountPaid,
                                'payType' => -1,
                            );
                        }
                        
                        foreach ($amountPaidArr as $key => $val) {
                            $id .= '|'.$key;
                            $amountPaid = $val -> amount;
                            $paymentType = ($val->payType != -1) ? cond_Payments::fetch($val->payType)->title : 'В брой';
                            
                            
                            // добавяме в масива
                            if (!array_key_exists($id, $recs)) {
                                $recs[$id] = (object) array(
                                    'urn' => $urn,
                                    'sysNumber' => $sysNumber,
                                    'saleOpenDate' => $saleOpenDate,        //Дата на откриване на продажбата
                                    'saleCloseDate' => $saleCloseDate,      //Дата на приключване на продажбата
                                    'dueAmount' => $dueAmount,              //обща сума по продажбата - в лв.
                                    'paymentDate' => $paymentDate,          //Дата на плащане (датата на издаване на касовата бележка)по продажбата
                                    'userId' => $userId,                    //Код на оператор, регистрирал плащането
                                    
                                    'paidAmount' => $amountPaid,            //Платена сума на продажбата - с ДДС
                                    'paymentType' => $paymentType,          //Вид на плащането
                                    
                                    'cashRegNum' => $cashRegNum,            //Индивидуален номер на ФУ
                                
                                
                                );
                            }
                        }
                    }
                }
            }
        }
        
        return $recs;
    }
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *                         - записа
     * @param bool     $export
     *                         - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        
        $fld->FLD('urn', 'varchar', 'caption=Продажба->УНП');
        $fld->FLD('sysNumber', 'varchar', 'caption=Продажба->Сис. номер');
        
        $fld->FLD('saleOpenDate', 'varchar', 'caption=Продажба->Откриване');
        $fld->FLD('saleCloseDate', 'varchar', 'caption=Продажба->Приключване');
        $fld->FLD('dueAmount', 'double(smartRound,decimals=2)', 'caption=Продажба->Обща стойност');
        
        $fld->FLD('paymentDate', 'varchar', 'caption=Плащане->дата');
        $fld->FLD('userId', 'varchar', 'caption=Плащане->Оператор');
        
        $fld->FLD('paidAmount', 'double(smartRound,decimals=2)', 'caption=Плащане->Сума');
        
        $fld->FLD('paymentType', 'varchar', 'caption=Плащане->Вид');
        $fld->FLD('cashRegNum', 'varchar', 'caption=Плащане->ФУ');
        
        return $fld;
    }
    
    
    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *                       - записа
     * @param stdClass $dRec
     *                       - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        
        $row = new stdClass();
        
        if (isset($dRec->urn)) {
            $row->urn = $dRec->urn;
        }
        
        if (isset($dRec->sysNumber)) {
            $row->sysNumber = $dRec->sysNumber;
        }
        
        if (isset($dRec->saleOpenDate)) {
            $row->saleOpenDate = $dRec->saleOpenDate;
        }
        
        if (isset($dRec->saleCloseDate)) {
            $row->saleCloseDate = $dRec->saleCloseDate;
        }
        
        if (isset($dRec->dueAmount)) {
            $row->dueAmount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->dueAmount);
        }
        
        if (isset($dRec->paymentDate)) {
            $row->paymentDate = $Date->toVerbal($dRec->paymentDate);
        }
        
        if (isset($dRec->userId)) {
            $row->userId = $dRec->userId;
        }
        
        if (isset($dRec->paidAmount)) {
            $row->paidAmount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->paidAmount);
        }
        
        if (isset($dRec->vat)) {
            $row->vat = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->vat);
        }
        
        if (isset($dRec->paymentType)) {
            $row->paymentType = $dRec->paymentType;
        }
        
        if (isset($dRec->cashRegNum)) {
            $row->cashRegNum = $dRec->cashRegNum;
        }
        
        return $row;
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param core_ET             $tpl
     * @param stdClass            $data
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $Date = cls::get('type_Date');
        
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN from--><div>|От|*: [#from#]</div><!--ET_END from-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                        <!--ET_BEGIN dealers--><div>|Търговци|*: [#dealers#]</div><!--ET_END dealers-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));
        
        
        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->from) . '</b>', 'from');
        }
        
        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->to) . '</b>', 'to');
        }
        
        if ((isset($data->rec->dealers)) && ((min(array_keys(keylist::toArray($data->rec->dealers))) >= 1))) {
            foreach (type_Keylist::toArray($data->rec->dealers) as $dealer) {
                $dealersVerb .= (core_Users::getTitleById($dealer) . ', ');
            }
            
            $fieldTpl->append('<b>' . trim($dealersVerb, ',  ') . '</b>', 'dealers');
        } else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'dealers');
        }
        
        
        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }
    
    
    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass            $res
     * @param stdClass            $rec
     * @param stdClass            $dRec
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
    }
}
