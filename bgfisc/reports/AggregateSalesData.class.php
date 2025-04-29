<?php


/**
 * Мениджър на отчети относно: Обобщени данни за продажбите
 *
 * @category  bgerp
 * @package   bgfisc
 *
 * @author    Angel Trifonov <angel.trifonoff@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     НАП » Обобщени данни за продажбите
 */
class bgfisc_reports_AggregateSalesData extends frame2_driver_TableData
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'n18_reports_AggregateSalesData';


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
        
         $form->input('operator');
        
         $posQuery = pos_Receipts::getQuery();

         $posQuery->show('createdBy');
         
         $suggestionsPos = arr::extractValuesFromArray($posQuery->fetchAll(), 'createdBy');
        
         $salesQuery = sales_Sales::getQuery();
      
         $salesQuery->show('createdBy');
        
         $suggestionsSales = arr::extractValuesFromArray($salesQuery->fetchAll(), 'createdBy');
        
         $suggestions = $suggestionsPos+$suggestionsSales;
        
         foreach ($suggestions as $val) {
             $suggestions[$val] = core_Users::fetch("#id = ${val}")->names;
         }
        
         asort($suggestions);
         $form->setOptions('operator', $suggestions);
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
            
            //Състояние на документите , които влизат в справката
            $stateArr = array('active', 'closed','waiting');
            
            $id = $regRec->id;
            
            //Уникален номер на продажбата
            $urn = $regRec->urn;
            
            //Код на оператор
            $userId = $regRec->userId;
            
            $userId = str_pad(substr($userId, -4), 4, '0', STR_PAD_LEFT);
            
            //Системен номер на продажбата
            $sysNumber = $regRec->number;
            
            $RegClass = cls::get($regRec->classId);
            
            $className = $RegClass->className;
            
            
            //Продажби от POS
            if ($RegClass instanceof pos_Receipts) {
                $posRec = $className::fetch($regRec->objectId);
                
                //Филтър по състояние
                if (!in_array($posRec->state, $stateArr)) {
                    continue;
                }
                
                //Филтър по оператор
                if ($rec->operator && $rec->operator != $posRec->createdBy) {
                    continue;
                }
                
                //Ако продажбата Е СТОРНИРАНА не влиза в отчета
                
                if (!is_null($posRec->revertId)) {
                    continue;
                }
                
                //Дата на откриване на продажбата
                $saleOpenDate = dt::mysql2verbal($posRec->createdOn, 'd.m.Y');
                
                //Време на откриване на продажбата
                $saleOpenTime = dt::mysql2verbal($posRec->createdOn, 'H:i:s');
                
                //Код и наименование на търговски обект
                if (!is_null($posRec->pointId)) {
                    $storeId = pos_Points::fetch($posRec->pointId)->storeId;
                    
                    if ($storeLocationId = store_Stores::fetchField($storeId, 'locationId')) {
                        $storeAddress = crm_Locations::getAddress($storeLocationId);
                    }
                }
                
                //Код на работното място
                $pointRec = pos_Points::fetch($posRec->pointId);
                $workplace = cash_Cases::getTitleById($pointRec->caseId);
                
                $posDet = pos_ReceiptDetails::getQuery();
                $posDet->where(array('#receiptId = [#1#]',$posRec->id));
                
                $vatSum = $amountSum = $dueAmount = $discount = 0;
                while ($detail = $posDet->fetch()) {
                    if (strpos($detail->action, 'sale') === false) {
                        continue;
                    }
                    
                    //Отстъпка
                    $discount += ($detail->amount * $detail->discountPercent);
                    
                    //ДДС - сума
                    $vatSum += ($detail->amount - $discount) * $detail->param;
                    
                    //Обща сума на продажбата - без ДДС
                    $amountSum += ($detail->amount - ($detail->amount * $detail->discountPercent));
                    
                    //Стойност на продажбата - с ДДС
                    $dueAmount += ($amountSum + $vatSum);
                }
                
                //Отпечатани ФБ
                $rcptPrint = bgfisc_PrintedReceipts::fetch(array("#urnId = '[#1#]'", $regRec->id));
                
                //Ако към УНП няма касова бележка, записа не влиза в справката
                if (!$rcptPrint) {
                    continue;
                }
                
                //Дата на приключване на продажбата
                $saleCloseDate = dt::mysql2verbal($rcptPrint->createdOn, 'd.m.Y');
                
                //Време на приключване на продажбата
                $saleCloseTime = dt::mysql2verbal($rcptPrint->createdOn, 'H:i:s');
                
                
                //Клиент код
                $contragentClassName = cls::getClassName($posRec->contragentClass);
                
                $contragentCode = phpsubstr($contragentClassName, 4, 1) . $posRec->contragentObjectId;
                
                //Клиент име
                $contragentName = $posRec->contragentName;
                
                // добавяме в масива
                if (!array_key_exists($id, $recs)) {
                    $recs[$id] = (object) array(
                        'urn' => $urn,
                        'sysNumber' => $sysNumber,
                        'storeAddress' => $storeAddress,
                        'saleOpenDate' => $saleOpenDate,
                        'saleOpenTime' => $saleOpenTime,
                        'workplace' => $workplace,
                        'userId' => $userId,
                        'totalAmount' => $amountSum + $discount,
                        'discount' => $discount,
                        'vat' => $vatSum,
                        'dueAmount' => $dueAmount,
                        'invoiceNumber' => $invoice->number,
                        'invoiceDate' => $invoice->date,
                        'saleCloseDate' => $saleCloseDate,
                        'saleCloseTime' => $saleCloseTime,
                        'contragentCode' => $contragentCode,
                        'contragentName' => $contragentName,
                    
                    );
                }
            }
            
            //Продажби по договор за продажба
            if ($RegClass instanceof sales_Sales) {
                $saleRec = $className::fetch($regRec->objectId);
                
                //Филтър по състояние
                if (!in_array($saleRec->state, $stateArr)) {
                    continue;
                }
                
                //Филтър по оператор
                if ($rec->operator && $rec->operator != $posRec->createdBy) {
                    continue;
                }
                
                //Фактури по продажбата
                $invQuery = sales_Invoices::getQuery();
                
                //Филтър по състояние
                $invQuery->where("#state = 'active'");
                
                $invQuery->where("#threadId = {$saleRec->threadId} ");
                
                $invoicesArr = array();
                while ($invoice = $invQuery->fetch()) {
                    $invoicesArr[] = (object) array(
                        'data' => $invoice->date,
                        'number' => $invoice->number
                    );
                }
                
                $counter = count($invoicesArr) == 0 ? 1 :count($invoicesArr);
                
                for ($i = 0; $i < $counter;$i++) {
                    if (empty($invoicesArr)) {
                        $invoiceNumber = '';
                        $invoiceDate = '';
                    } else {
                        $invoiceNumber = $invoicesArr[$i]->number;
                        $invoiceDate = $invoicesArr[$i]->data;
                        $id .= '|'.$invoicesArr[$i]->number;
                    }
                    
                    //Ако по продажбата има сторниране
                    list($thisUrn) = (explode('|', $id));
                    $prntQuery = bgfisc_PrintedReceipts::getQuery();
                    $prntQuery->where("#type = 'reverted' AND #urnId = ${thisUrn}");
                    $stornoVat = $stornoAmount = 0;
                    if (!empty($prntQuery->fetchAll())) {
                        while ($prntRcptRev = $prntQuery->fetch()) {
                            $objectClassName = cls::get($prntRcptRev->classId)->className;
                            $rcoRec = $objectClassName::fetch($prntRcptRev->objectId);
                            
                            if (!is_null($rcoRec->fromContainerId)) {
                                continue;
                            }
                            
                            $revDocClassId = doc_Containers::fetch($rcoRec->fromContainerId)->docClass;
                            $revDocClassName = cls::get($revDocClassId)->className;
                            $revDocId = doc_Containers::fetch($rcoRec->fromContainerId)->docId;
                            $revDocRec = $revDocClassName::fetch($revDocId);
                            
                            $stornoVat += $revDocRec->amountDeliveredVat;
                            $stornoAmount += $revDocRec->amountDelivered - $stornoVat;
                        }
                    }
                    
                    //Дата на откриване на продажбата
                    $saleOpenDate = dt::mysql2verbal($saleRec->createdOn, 'd.m.Y');
                    
                    //Време на откриване на продажбата
                    $saleOpenTime = dt::mysql2verbal($saleRec->createdOn, 'H:i:s');
                    
                    //Код и наименование на търговски обект
                    $storeAddress = '';
                    if ($saleRec->shipmentStoreId) {
                        if ($storeLocationId = store_Stores::fetchField($saleRec->shipmentStoreId, 'locationId')) {
                            $storeAddress = crm_Locations::getAddress($storeLocationId);
                        }
                    } else {
                        $storeId = store_ShipmentOrders::fetch("#threadId = {$saleRec->threadId} AND #storeId IS NOT NULL")->storeId;
                        
                        if ((!is_null($storeId)) && $storeLocationId = store_Stores::fetch($storeId)->locationId) {
                            $storeAddress = crm_Locations::getAddress($storeLocationId);
                        }
                    }
                    
                    //Код на работното място
                    $workplace = cash_Cases::getTitleById($saleRec->caseId);
                    
                    //Ако продажбата Е СТОРНИРАНА не влиза в отчета
                    if ($saleRec->amountVat == $stornoVat && ($saleRec->amountDeal - $saleRec->amountVat) == $stornoAmount) {
                        continue;
                    }
                    
                    $vatSum = $amountSum = 0;
                    
                    //Отстъпка
                    $discount = $saleRec->amountDiscount;
                    
                    //ДДС - сума
                    $vatSum = $saleRec->amountVat - $stornoVat;
                    
                    //Обща сума на продажбата - без ДДС
                    $amountSum = ((($saleRec->amountDeal - $saleRec->amountVat)) + $discount) - $stornoAmount;
                    
                    //Стойност на продажбата - с ДДС
                    $dueAmount = $saleRec->amountDeal - $stornoAmount;
                    
                    //Дата на приключване на продажбата
                    if ($saleRec->closedOn && $saleRec->state == 'closed') {
                        $saleCloseDate = dt::mysql2verbal($saleRec->closedOn, 'd.m.Y');
                    } else {
                        $saleCloseDate = '';
                    }
                    
                    //Време на приключване на продажбата
                    if ($saleRec->closedOn && $saleRec->state == 'closed') {
                        $saleCloseTime = dt::mysql2verbal($saleRec->closedOn, 'H:i:s');
                    } else {
                        $saleCloseTime = '';
                    }
                    
                    //Клиент код
                    $contragentClassName = cls::getClassName($saleRec->contragentClassId);
                    
                    $contragentCode = phpsubstr($contragentClassName, 4, 1) . $saleRec->contragentId;
                    
                    //Клиент име
                    $contragentName = $contragentClassName::getTitleById($saleRec->contragentId);
                    
                    
                    // добавяме в масива
                    if (!array_key_exists($id, $recs)) {
                        $recs[$id] = (object) array(
                            'urn' => $urn,
                            'sysNumber' => $sysNumber,
                            'storeAddress' => $storeAddress,
                            'saleOpenDate' => $saleOpenDate,
                            'saleOpenTime' => $saleOpenTime,
                            'workplace' => $workplace,
                            'userId' => $userId,
                            'totalAmount' => $amountSum,
                            'discount' => $discount,
                            'vat' => $vatSum,
                            'dueAmount' => $dueAmount,
                            'invoiceNumber' => $invoiceNumber,
                            'invoiceDate' => $invoiceDate,
                            'saleCloseDate' => $saleCloseDate,
                            'saleCloseTime' => $saleCloseTime,
                            'contragentCode' => $contragentCode,
                            'contragentName' => $contragentName,
                        
                        );
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
        
        $fld->FLD('urn', 'varchar', 'caption=УНП');
        $fld->FLD('sysNumber', 'varchar', 'caption=Номер');
        
        $fld->FLD('storeAddress', 'varchar', 'caption=Код / Обект');
        $fld->FLD('saleOpenDate', 'varchar', 'caption=Откриване->дата,tdClass=centered');
        $fld->FLD('saleOpenTime', 'varchar', 'caption=Откриване->време,tdClass=centered');
        $fld->FLD('workplace', 'varchar', 'caption=Раб. място,tdClass=centered');
        $fld->FLD('userId', 'varchar', 'caption=Оператор,tdClass=centered');
        
        $fld->FLD('totalAmount', 'double(decimals=2)', 'caption=Сума,tdClass=centered');
        $fld->FLD('discount', 'double(decimals=2)', 'caption=Отстъпка,tdClass=centered');
        $fld->FLD('vat', 'double(decimals=2)', 'caption=ДДС,tdClass=centered');
        $fld->FLD('dueAmount', 'double(decimals=2)', 'caption=Стойност,tdClass=centered');
        
        $fld->FLD('invoiceNumber', 'int', 'caption=Фактура->Номер,tdClass=centered');
        $fld->FLD('invoiceDate', 'varchar', 'caption=Фактура->Дата,tdClass=centered');
        
        $fld->FLD('saleCloseDate', 'varchar', 'caption=Приключване->дата,tdClass=centered');
        $fld->FLD('saleCloseTime', 'varchar', 'caption=Приключване->време,tdClass=centered');
        
        $fld->FLD('contragentCode', 'varchar', 'caption=Клиент->код,tdClass=centered');
        $fld->FLD('contragentName', 'varchar', 'caption=Клиент->име');
        
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
        $groArr = array();
        
        $row = new stdClass();
        
        if (isset($dRec->urn)) {
            $row->urn = $dRec->urn;
        }
        
        if (isset($dRec->sysNumber)) {
            $row->sysNumber = $dRec->sysNumber;
        }
        
        if (isset($dRec->storeAddress)) {
            $row->storeAddress = $dRec->storeAddress;
        }
        
        if (isset($dRec->saleOpenDate)) {
            $row->saleOpenDate = $dRec->saleOpenDate;
        }
        
        if (isset($dRec->saleOpenTime)) {
            $row->saleOpenTime = $dRec->saleOpenTime;
        }
        
        if (isset($dRec->workplace)) {
            $row->workplace = $dRec->workplace;
        }
        
        if (isset($dRec->userId)) {
            $row->userId = $dRec->userId;
        }
        
        
        if (isset($dRec->totalAmount)) {
            $row->totalAmount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalAmount);
        }
        
        if (isset($dRec->discount)) {
            $row->discount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->discount);
        }
        
        if (isset($dRec->vat)) {
            $row->vat = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->vat);
        }
        
        if (isset($dRec->dueAmount)) {
            $row->dueAmount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->dueAmount);
        }
        
        if (isset($dRec->invoiceNumber)) {
            $row->invoiceNumber .= $Int->toVerbal($dRec->invoiceNumber);
        }
        
        if (isset($dRec->invoiceDate)) {
            $row->invoiceDate = $Date->toVerbal($dRec->invoiceDate);
        }
        
        if (isset($dRec->saleCloseDate)) {
            $row->saleCloseDate = $dRec->saleCloseDate;
        }
        
        if (isset($dRec->saleCloseTime)) {
            $row->saleCloseTime = $dRec->saleCloseTime;
        }
        
        if (isset($dRec->contragentCode)) {
            $row->contragentCode = $dRec->contragentCode;
        }
        
        if (isset($dRec->contragentName)) {
            $row->contragentName = $dRec->contragentName;
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
                                        <!--ET_BEGIN operator--><div>|Оператор|*: [#operator#]</div><!--ET_END operator-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));
        
        
        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->from) . '</b>', 'from');
        }
        
        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->to) . '</b>', 'to');
        }
        
        if (isset($data->rec->operator)) {
            $fieldTpl->append('<b>' . core_Users::fetch($data->rec->operator)->names . '</b>', 'operator');
        } else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'operator');
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
