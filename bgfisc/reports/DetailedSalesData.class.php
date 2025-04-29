<?php


/**
 * Мениджър на отчети относно: Детайлни данни за продажбите
 *
 * @category  bgerp
 * @package   bgfisc
 *
 * @author    Angel Trifonov <angel.trifonoff@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     НАП » Детайлни данни за продажбите
 */
class bgfisc_reports_DetailedSalesData extends frame2_driver_TableData
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'n18_reports_DetailedSalesDat';


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'acc,sales,ceo';
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField ;


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
        $fieldset->FLD('from', 'date', 'caption=От,after=compare,removeAndRefreshForm,single=none');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,removeAndRefreshForm,single=none');
        
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
        
        if ($rec->from) {
            $posQuery->where(array("#createdOn >= '[#1#]'", $rec->from . ' 00:00:00'));
        }
        
        if ($rec->to) {
            $posQuery->where(array("#createdOn <= '[#1#]'",$rec->to . ' 23:59:59'));
        }
        
        $posQuery->show('createdBy');
        
        $suggestionsPos = arr::extractValuesFromArray($posQuery->fetchAll(), 'createdBy');
        
        $salesQuery = sales_Sales::getQuery();
        
        if ($rec->from) {
            $salesQuery->where(array("#createdOn >= '[#1#]'", $rec->from . ' 00:00:00'));
        }
        
        if ($rec->to) {
            $salesQuery->where(array("#createdOn <= '[#1#]'",$rec->to . ' 23:59:59'));
        }
        
        $salesQuery->show('createdBy');
        
        $suggestionsSales = arr::extractValuesFromArray($salesQuery->fetchAll(), 'createdBy');
        
        $suggestions = ($suggestionsPos+$suggestionsSales);
        
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
        
        //Състояние на документите , които влизат в справката
        $stateArr = array('active', 'closed','waiting');
        
        $sQuery = bgfisc_Register::getQuery();
        
        if ($rec->from) {
            $sQuery->where(array("#createdOn >= '[#1#]'", $rec->from . ' 00:00:00'));
        }
        
        
        if ($rec->to) {
            $sQuery->where(array("#createdOn <= '[#1#]'",$rec->to . ' 23:59:59'));
        }
        
        while ($regRec = $sQuery -> fetch()) {
            
            //Уникален номер на продажбата
            $urn = $regRec->urn;
            
            //Системен номер на продажбата
            $sysNumber = $regRec->number;
            
            $RegClass = cls::get($regRec->classId);
            
            $className = $RegClass->className;
            
            
            //Продажби от POS
            if ($RegClass instanceof pos_Receipts) {
                $posRec = $className::fetch($regRec->objectId);
                
                //Ако продажбата Е СТОРНИРАНА не влиза в отчета
                if (!is_null($posRec->revertId)) {
                    continue;
                }
                
                $posDetQuery = pos_ReceiptDetails::getQuery();
                
                $posDetQuery->where('#receiptId IS NOT NULL');
                
                $posDetQuery->EXT('state', 'pos_Receipts', 'externalName=state,externalKey=receiptId');
                
                $posDetQuery->EXT('createdBy', 'pos_Receipts', 'externalName=createdBy,externalKey=receiptId');
                
                $posDetQuery->where(array('#receiptId = [#1#]',$posRec->id));
                
                //Филтър по състояние
                $posDetQuery->in('state', $stateArr);
                
                //Филтър по оператор
                if($rec->operator){
                    $posDetQuery->where("#createdBy = $rec->operator");
                }
                
                $vatSum = $amountSum = 0;
                while ($detail = $posDetQuery->fetch()) {
                    
                    if (strpos($detail->action, 'sale') === false) {
                        continue;
                    }
                    
                    //Ключ за $recs
                    $id = $regRec->id.'|'.$detail->productId;
                    
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
                    $vatKoef = $detail->param;
                    $vatRate = $vatKoef * 100;
                    
                    
                    //ДДС - сума
                    $vatSum = ($detail->amount - $discount) * $vatKoef;
                    
                    //Обща сума
                    $amountSum = ($detail->amount - $discount) + $vatSum;
                    
                    // добавяме в масива
                    if (!array_key_exists($id, $recs)) {
                        $recs[$id] = (object) array(
                            'urn' => $urn,
                            'sysNumber' => $sysNumber,
                            'productCode' => $productCode,   //Код на стоката/услугата
                            'name' => $name,                 //Наименование на стоката/услугата
                            'quantity' => $quantity,         //Количество
                            'price' => $price,               //Единична цена
                            'discount' => $discount,         //Отстъпка
                            'vatRate' => $vatRate,           //ДДС - ставка
                            'vat' => $vatSum,                //ДДС - сума
                            'totalAmount' => $amountSum,     //Обща сума
                        
                        );
                    } else {
                        $obj = &$recs[$id];
                    }
                }
            }
            
            //Продажби по договор
            if (!$RegClass instanceof pos_Receipts) {
                
                $saleRec = $className::fetch($regRec->objectId);
                
                $documents = array('sales_Sales' => 'sales_SalesDetails','store_ShipmentOrders' => 'store_ShipmentOrderDetails');
                foreach ($documents as $key => $val){
                    
                    $query = $key::getQuery(); 
                    
                    $query->where("#threadId = $saleRec->threadId");
                    
                    //Филтър по състояние
                    $query->in('state', $stateArr);
                    
                    //Филтър по оператор
                    if($rec->operator){
                        $query->where("#createdBy = $rec->operator");
                    }
                
                $RecsIdArr =  arr::extractValuesFromArray($query->fetchAll(), 'id');
                
                if (empty($RecsIdArr))continue;
              
                $Master = $key;
                
                $Detail = $documents[$Master];
                $Detail = cls::get($Detail);
                $masterKey = $Detail->masterKey;
                $detailName = $Detail->className;
               
                $saleDet = $detailName::getQuery();
               
                $saleDet->where("#{$masterKey} IS NOT NULL");
           
                $saleDet->in($masterKey, $RecsIdArr); 
                
                $vatSum = $amountSum = 0;
                while ($detail = $saleDet->fetch()) {
                    
                    //Ключ за $recs
                    $id = $regRec->id.'|'.$detail->productId;
                    
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
                    
                    //ДДС ставка
                    $vatKoef = cat_Products::getVat($detail->productId);
                    $vatRate = $vatKoef * 100;
                    
                     //Отстъпка
                    $discount = $detail->amount * $detail->discount;
                    
                    //ДДС - сума
                    $vatSum = ($detail->amount - $discount) * $vatKoef;///if ($saleRec->id == 1362)bp($detail,$detail->amount - $discount);
                    
                   
                    
                    //Обща сума
                    $amountSum = ($detail->amount - $discount) + $vatSum;
                    
                    // добавяме в масива
                    if (!array_key_exists($id, $recs)) {
                        $recs[$id] = (object) array(
                            'urn' => $urn,
                            'sysNumber' => $sysNumber,
                            'productCode' => $productCode,   //Код на стоката/услугата
                            'name' => $name,                 //Наименование на стоката/услугата
                            'quantity' => $quantity,         //Количество
                            'price' => $price,               //Единична цена
                            'discount' => $discount,         //Отстъпка
                            'vatRate' => $vatRate,           //ДДС - ставка
                            'vat' => $vatSum,                //ДДС - сума
                            'totalAmount' => $amountSum,     //Обща сума
                        
                        );
                    } else {
                        $obj = &$recs[$id];
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
        
            $fld->FLD('urn', 'varchar', 'caption=УНП');
            $fld->FLD('sysNumber', 'varchar', 'caption=Номер');
            
            $fld->FLD('productCode', 'varchar', 'caption=Код,tdClass=centered');
            $fld->FLD('name', 'varchar', 'caption=Име');
            
            $fld->FLD('quantity', 'double(smartRound,decimals=3)', 'caption=Количество,tdClass=centered');
            $fld->FLD('price', 'varchar', 'caption=Ед.цена,tdClass=centered');
            
            $fld->FLD('discount', 'varchar', 'caption=Отстъпка,tdClass=centered');
            
            $fld->FLD('vatRate', 'varchar', 'caption=ДДС->Ставка,tdClass=centered');
            $fld->FLD('vat', 'varchar', 'caption=ДДС->сума,tdClass=centered');
            
            $fld->FLD('totalAmount', 'varchar', 'caption=Сума,tdClass=centered');
       
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
            $row->quantity = core_Type::getByName('double(smartRound,decimals=3)')->toVerbal($dRec->quantity);
        }
        
        if (isset($dRec->discount)) {
            $row->discount = $dRec->discount != 0 ?core_Type::getByName('double(smartRound,decimals=3)')->toVerbal($dRec->discount):'';
        }
        
        if (isset($dRec->price)) {
            $row->price = core_Type::getByName('double(smartRound,decimals=3)')->toVerbal($dRec->price);
        }
        
        if (isset($dRec->vatRate)) {
            $row->vatRate = $dRec->vatRate;
        }
        
        if (isset($dRec->vat)) {
            $row->vat = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->vat);
        }
        
        if (isset($dRec->totalAmount)) {
            $row->totalAmount = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalAmount);
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
