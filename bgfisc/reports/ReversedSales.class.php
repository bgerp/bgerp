<?php


/**
 * Мениджър на отчети относно: Сторнирани продажби
 *
 * @category  bgerp
 * @package   bgfisc
 *
 * @author    Angel Trifonov <angel.trifonoff@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     НАП » Сторнирани продажби
 */
class bgfisc_reports_ReversedSales extends frame2_driver_TableData
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'n18_reports_ReversedSales';


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'acc,sales,ceo';
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;


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
        
        $fieldset->FLD('dealers', 'users(rolesForAll=ceo|repAllGlobal, rolesForTeams=ceo|manager|repAll|repAllGlobal),allowEmpty', 'caption=Търговци,after=to');
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
        
        //Състояние на документите , които влизат в справката
        $stateArr = array('active', 'closed','waiting');
        
        $sQuery = bgfisc_Register::getQuery();
        
        if ($rec->from) {
            $sQuery->where(array("#createdOn >= '[#1#]'", $rec->from . ' 00:00:00'));
        }
        
        
        if ($rec->to) {
            $sQuery->where(array("#createdOn <= '[#1#]'",$rec->to . ' 23:59:59'));
        }
        
        //Всички сторниращи бележки $revReceiptArr
        $revReceiptsQuery = bgfisc_PrintedReceipts::getQuery();
        $revReceiptsQuery->where("#type = 'reverted'");
        
        $revReceiptArr = array();
        while ($revReceipt = $revReceiptsQuery->fetch()) {
            $costDocClassName = cls::get($revReceipt->classId)->className;
            $costDocRec = $costDocClassName::fetch($revReceipt->objectId);
            
            if (!is_null($costDocRec->fromContainerId)) {
                $fromDocClassRec = doc_Containers::fetch($costDocRec->fromContainerId);
            }
            
            if ($fromDocClassRec) {
                $fromDocClassName = cls::get($fromDocClassRec->docClass)->className;
                $fromDocRec = $fromDocClassName::fetch($fromDocClassRec->docId);
            }
            
            $revReceiptArr[$revReceipt->id] = (object) array(
                'revReceiptId' => $revReceipt->id,
                'threadId' => $fromDocRec->threadId,
                'urnId' => $revReceipt->urnId,
                'costDocClassName' => $costDocClassName,
                'fromDocClassName' => $fromDocClassName,
                'fromDocId' => $fromDocRec->id,
                'costDocId' => $costDocRec->id,
                'costDocRec' => $costDocRec,
                'fromDocRec' => $fromDocRec
            );
        }
        
        if (empty($revReceiptArr)) {
            $recs = array();
            
            return ;
        }
        
        $sQuery->in('id', core_Array::extractValuesFromArray($revReceiptArr, 'urnId'));
        
        //Всички УНП-та за този период, които имат сторниращи бележки $regRecArr
        while ($registerRec = $sQuery -> fetch()) {
            $regRecArr[$registerRec->id] = $registerRec;
        }
        
        if (empty($regRecArr)) {
            $recs = array();
            
            return ;
        }
        
        
        foreach ($regRecArr as $regRec) {
            
            //Уникален номер на продажбата
            $urn = $regRec->urn;
            
            //Системен номер на продажбата
            $sysNumber = $regRec->number;
            
            $RegClass = cls::get($regRec->classId);
            
            $className = $RegClass->className;
            
            //Ако продажбата е от POS
            if ($RegClass instanceof pos_Receipts) {
                
                
                //Всички сторниращи бележки към това УНП
                $revertReceptThisUnr = array();
                
                foreach ($revReceiptArr as $val) {
                    if ($regRec->id == $val->urnId) {
                        $revertReceptThisUnr[] = $val->costDocRec->id;
                    }
                }
                
                //Детайли по сторниращите бележки към това УНП
                $dposQuery = pos_ReceiptDetails::getQuery();
                $dposQuery->in('receiptId', $revertReceptThisUnr);
                
                $dposQuery->EXT('state', 'pos_Receipts', 'externalName=state,externalKey=receiptId');
                
                //Филтър по състояние
                $dposQuery->in('state', $stateArr);
                
              
                $posDetailsArr = array();
                while ($posDetail = $dposQuery->fetch()) { 
                    if (strpos($posDetail->action, 'sale') === false) {
                        continue;
                    }
                    
                    $posDetailsArr[$posDetail->id] = $posDetail;
                }
                
                // Отпечатани фискални бонове към това УНП
                $revertReceptRecArr = array();
                foreach ($revReceiptArr as $key => $val) {
                    $valUrn = $val->urnId;
                    if ($regRec->id == $valUrn) {
                        $revertReceptRec = bgfisc_PrintedReceipts::fetch($key); // rec-a на касова бележка от това УНП
                        $revKey = $valUrn.'|'.$revertReceptRec->objectId;
                        $revertReceptRecArr[$revKey] = $revertReceptRec;
                        
                        //Дата на приключване на продажбата: датата на последната касова бележка
                        $saleCloseDate = dt::mysql2verbal(max(bgfisc_PrintedReceipts::fetch($key)->createdOn, $saleCloseDate), 'd.m.Y');
                        
                        //Време на приключване на продажбата
                        $saleCloseTime = dt::mysql2verbal(max(bgfisc_PrintedReceipts::fetch($key)->createdOn, $saleCloseTime), 'H:i:s');
                    }
                }
                
                foreach ($posDetailsArr as $detail) {
                    $vatSum = $amountSum = 0;
                    
                    //Ключ за $recs
                    $id = $regRec->id.'|'.$detail->id;
                    
                    //Код на стоката/услугата
                    if (!is_null(cat_Products::fetchField($detail->productId, 'code'))) {
                        $productCode = cat_Products::fetchField($detail->productId, 'code');
                    } else {
                        $productCode = 'Art'.$detail->productId;
                    }
                    
                    //Наименование на стоката/услугата
                    $name = cat_Products::fetchField($detail->productId, 'name');
                    
                    //количество
                    $quantity = $detail->quantity;
                    
                    //Единична цена
                    $price = $detail->price;
                    
                    //Отстъпка
                    $discount = $detail->amount * $detail->discountPercent;
                    
                    //ДДС ставка
                    $vatRate = $detail->param * 100;
                    
                    
                    //ДДС - сума
                    $vatSum = ($detail->amount - $discount) * $detail->param;
                    
                    //Обща сума
                    $amountSum = ($detail->amount - $discount) + $vatSum;
                    
                    
                    $cashKey = $regRec->id.'|'.$detail->receiptId;
                    
                    //Отпечатана сторнираЩА касова бележка
                    $revRcpPrnt = $revertReceptRecArr[$cashKey];
                    
                    //Дата на сторниране на продажбата
                    $revertDate = dt::mysql2verbal($revRcpPrnt->createdOn, 'd.m.Y');
                    
                    //Време на сторниране на продажбата
                    $revertTime = dt::mysql2verbal($revRcpPrnt->createdOn, 'H:i:s');
                    
                    // Индивидуален номер на ФУ регистрирал сторнирането
                    $cashRegNum = $regRec->cashRegNum;
                    
                    //Код на оператор, регистрирал плащането
                    $userId = $revRcpPrnt->createdBy;
                    
                    // добавяме в масива
                    if (!array_key_exists($id, $recs)) {
                        $recs[$id] = (object) array(
                            'urn' => $urn,
                            'sysNumber' => $sysNumber,
                            'productCode' => $productCode,       //Код на стоката/услугата
                            'name' => $name,                     //Наименование на стоката/услугата
                            'quantity' => $quantity,             //Количество
                            'price' => $price,                   //Единична цена
                            'discount' => $discount,             //Отстъпка
                            'vatRate' => $vatRate,               //ДДС - ставка
                            'vat' => $vatSum,                    //ДДС - сума
                            'totalAmount' => $amountSum,         //Обща сума
                            'saleCloseDate' => $saleCloseDate,   //Дата на приключване на продажбата
                            'saleCloseTime' => $saleCloseTime,   //Време на приключване на продажбата
                            'revertDate' => $revertDate,         //Дата на сторниране на продажбата
                            'revertTime' => $revertTime,         //Време на сторниране на продажбата
                            'cashRegNum' => $cashRegNum,         //Индивидуален номер на ФУ регистрирал сторнирането
                            'userId' => $userId,                 //Код на оператор, регистрирал сторнирането
                        
                        );
                    } else {
                        $obj = &$recs[$id];
                    }
                }
            }
            
            //Ако продажбата не е от POS
            if (!$RegClass instanceof pos_Receipts) {
                $saleThreadId = $revReceipt = null;
                $tempArr = array();
                $documents = array('sales_Sales' => 'sales_SalesDetails', 'sales_Services' => 'sales_ServicesDetails',
                    'store_ShipmentOrders' => 'store_ShipmentOrderDetails','store_Receipts' => 'store_ReceiptDetails',
                    'sales_Invoices' => 'sales_InvoiceDetails'
                );
                
                
                
                $Master = $className;
                
                if (!in_array($className::fetch($regRec->objectId)->state, $stateArr))continue;
                
                $saleThreadId = $className::fetch($regRec->objectId)->threadId;
                
                foreach ($revReceiptArr as $revReceipt) {
                    if ($saleThreadId != $revReceipt->threadId) {
                        continue;
                    }
                    
                    
                    $Detail = $documents[$revReceipt->fromDocClassName];
                    $Detail = cls::get($Detail);
                    $masterKey = $Detail->masterKey;
                    $detailName = $Detail->className;
                    
                    //Детайли по продажбата
                    $detQuery = $detailName::getQuery();
                    $detQuery->where("#{$masterKey} = {$revReceipt->fromDocId}");
                    
                    //Ако сторно бележката е издадена по кредитно известие и няма детайли
                    if (empty($detQuery->fetchAll())) {
                    
                    //Ключ за $recs
                        $id = $regRec->id.'|'.$revReceipt->fromDocId;
                        
                        //Дата на приключване на продажбата: датата на последната касова бележка
                        $saleCloseDate = dt::mysql2verbal($className::fetch($regRec->objectId)->closedOn, 'd.m.Y');
                        
                        //Време на приключване на продажбата
                        $saleCloseTime = dt::mysql2verbal($className::fetch($regRec->objectId)->closedOn, 'H:i:s');
                        
                        
                        // добавяме в масива
                        if (!array_key_exists($id, $recs)) {
                            $recs[$id] = (object) array(
                                'urn' => $urn,
                                'sysNumber' => $sysNumber,
                                'productCode' => $productCode,       //Код на стоката/услугата
                                'name' => $name,                     //Наименование на стоката/услугата
                                'quantity' => $quantity,             //Количество
                                'price' => $price,                   //Единична цена
                                'discount' => $discount,             //Отстъпка
                                'vatRate' => $vatRate,               //ДДС - ставка
                                'vat' => $vatSum,                    //ДДС - сума
                                'totalAmount' => $amountSum,         //Обща сума
                                'saleCloseDate' => $saleCloseDate,   //Дата на приключване на продажбата
                                'saleCloseTime' => $saleCloseTime,   //Време на приключване на продажбата
                                'revertDate' => $revertDate,         //Дата на сторниране на продажбата
                                'revertTime' => $revertTime,         //Време на сторниране на продажбата
                                'cashRegNum' => $cashRegNum,         //Индивидуален номер на ФУ регистрирал сторнирането
                                'userId' => $userId,                 //Код на оператор, регистрирал сторнирането
                            
                            );
                        }
                    }
                    
                    $detail = null;
                    $detailsArr = array();
                    while ($detail = $detQuery->fetch()) {
                        $detailsArr[$detail->id] = $detail;
                    }
                    
                    //Дата на приключване на продажбата: датата на последната касова бележка
                    $saleCloseDate = dt::mysql2verbal($className::fetch($regRec->objectId)->closedOn, 'd.m.Y');
                    
                    //Време на приключване на продажбата
                    $saleCloseTime = dt::mysql2verbal($className::fetch($regRec->objectId)->closedOn, 'H:i:s');
                    
                    
                    foreach ($detailsArr as $detail) {
                        $vatSum = $amountSum = 0;
                        
                        //Ключ за $recs
                        $id = $regRec->id.'|'.$detail->id;
                        
                        //Код на стоката/услугата
                        if (!is_null(cat_Products::fetchField($detail->productId, 'code'))) {
                            $productCode = cat_Products::fetchField($detail->productId, 'code');
                        } else {
                            $productCode = 'Art'.$detail->productId;
                        }
                        
                        //Наименование на стоката/услугата
                        $name = cat_Products::fetchField($detail->productId, 'name');
                        
                        //количество
                        $quantity = $detail->quantity;
                        
                        //Единична цена
                        $price = $detail->price;
                        
                        //Отстъпка
                        $discount = $detail->amount * $detail->discount;
                        
                        //ДДС ставка
                        $vatKoef = cat_Products::getVat($detail->productId);
                        $vatRate = $vatKoef * 100;
                        
                        
                        //ДДС - сума
                        $vatSum = ($detail->amount - $discount) * $vatKoef;
                        
                        //Обща сума
                        $amountSum = ($detail->amount - $discount) + $vatSum;
                        
                        
                        $cashKey = $regRec->id.'|'.$detail->receiptId;
                        
                        //Отпечатана сторнираЩА касова бележка
                        $revRcpPrnt = $revertReceptRecArr[$cashKey];
                        
                        //Дата на сторниране на продажбата
                        $revertDate = dt::mysql2verbal($revRcpPrnt->createdOn, 'd.m.Y');
                        
                        //Време на сторниране на продажбата
                        $revertTime = dt::mysql2verbal($revRcpPrnt->createdOn, 'H:i:s');
                        
                        // Индивидуален номер на ФУ регистрирал сторнирането
                        $cashRegNum = $regRec->cashRegNum;
                        
                        //Код на оператор, регистрирал плащането
                        $userId = $revRcpPrnt->createdBy;
                        
                        // добавяме в масива
                        if (!array_key_exists($id, $recs)) {
                            $recs[$id] = (object) array(
                                'urn' => $urn,
                                'sysNumber' => $sysNumber,
                                'productCode' => $productCode,       //Код на стоката/услугата
                                'name' => $name,                     //Наименование на стоката/услугата
                                'quantity' => $quantity,             //Количество
                                'price' => $price,                   //Единична цена
                                'discount' => $discount,             //Отстъпка
                                'vatRate' => $vatRate,               //ДДС - ставка
                                'vat' => $vatSum,                    //ДДС - сума
                                'totalAmount' => $amountSum,         //Обща сума
                                'saleCloseDate' => $saleCloseDate,   //Дата на приключване на продажбата
                                'saleCloseTime' => $saleCloseTime,   //Време на приключване на продажбата
                                'revertDate' => $revertDate,         //Дата на сторниране на продажбата
                                'revertTime' => $revertTime,         //Време на сторниране на продажбата
                                'cashRegNum' => $cashRegNum,         //Индивидуален номер на ФУ регистрирал сторнирането
                                'userId' => $userId,                 //Код на оператор, регистрирал сторнирането
                            
                            );
                        } else {
                            $obj = &$recs[$id];
                        }
                        
                        $productCode = $name = $quantity = $price = $discount = $vatRate = $vatSum = $amountSum = $saleCloseDate = $saleCloseTime = $revertDate = $revertTime = $cashRegNum = $userId = '';
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
            
            $fld->FLD('productCode', 'varchar', 'caption=Код,tdClass=centered');
            $fld->FLD('name', 'varchar', 'caption=Име');
            
            $fld->FLD('quantity', 'varchar', 'caption=Количество,tdClass=centered');
            $fld->FLD('price', 'varchar', 'caption=Ед.цена,tdClass=centered');
            
            $fld->FLD('discount', 'varchar', 'caption=Отстъпка,tdClass=centered');
            
            $fld->FLD('vatRate', 'varchar', 'caption=ДДС->Ставка,tdClass=centered');
            $fld->FLD('vat', 'varchar', 'caption=ДДС->сума,tdClass=centered');
            
            $fld->FLD('totalAmount', 'varchar', 'caption=Сума,tdClass=centered');
            
            $fld->FLD('saleCloseDate', 'varchar', 'caption=Приключване->дата,tdClass=centered');
            $fld->FLD('saleCloseTime', 'varchar', 'caption=Приключване->време,tdClass=centered');
            
            $fld->FLD('revertDate', 'varchar', 'caption=Сторниране->дата,tdClass=centered');
            $fld->FLD('revertTime', 'varchar', 'caption=Сторниране->време,tdClass=centered');
            
            $fld->FLD('cashRegNum', 'varchar', 'caption=ФУ');
            $fld->FLD('userId', 'varchar', 'caption=Оператор');
     
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
        
        if (isset($dRec->productCode)) {
            $row->productCode = $dRec->productCode;
        }
        
        if (isset($dRec->name)) {
            $row->name = $dRec->name;
        }
        
        if (isset($dRec->quantity)) {
            $row->quantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantity);
            $row->quantity = ht::styleNumber($row->quantity, $dRec->quantity);
        }
        
        if (isset($dRec->discount)) {
            $row->discount = $dRec->discount != 0 ?core_Type::getByName('double(decimals=2)')->toVerbal($dRec->discount):'';
        }
        
        if (isset($dRec->price)) {
            $row->price = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->price);
        }
        
        if (isset($dRec->vatRate)) {
            $row->vatRate = $dRec->vatRate;
        }
        
        if (isset($dRec->vat)) {
            $row->vat = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->vat);
        }
        
        if (isset($dRec->totalAmount)) {
            $row->totalAmount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalAmount);
            $row->totalAmount = ht::styleNumber($row->totalAmount, $dRec->totalAmount);
        }
        
        if (isset($dRec->saleCloseDate)) {
            $row->saleCloseDate = $dRec->saleCloseDate;
        }
        
        if (isset($dRec->saleCloseTime)) {
            $row->saleCloseTime = $dRec->saleCloseTime;
        }
        
        if (isset($dRec->revertDate)) {
            $row->revertDate = $dRec->revertDate;
        }
        
        if (isset($dRec->revertTime)) {
            $row->revertTime = $dRec->revertTime;
        }
        
        if (isset($dRec->cashRegNum)) {
            $row->cashRegNum = $dRec->cashRegNum;
        }
        
        if (isset($dRec->userId)) {
            $row->userId = $dRec->userId;
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
