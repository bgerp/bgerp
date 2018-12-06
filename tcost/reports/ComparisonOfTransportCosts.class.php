<?php

/**
 * Мениджър на отчети за сравнение на траспортни разходи
 *
 * @category  bgerp
 * @package   tcost
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Логистика » Сравнение на очкваните и реалните траспортни разходи
 */
class tcost_reports_ComparisonOfTransportCosts extends frame2_driver_TableData
{
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, acc, repAllGlobal, trans';
    
    
    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField;
    
    
    /**
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var string
     */
    protected $newFieldToCheck;
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;
    
    
    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'from,to,compare,group,dealers,contragent,crmGroup,articleType';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        
        $fieldset->FLD('from', 'date', 'caption=От,after=compare,single=none,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,mandatory');
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
            
            if (isset($form->rec->compare) && $form->rec->compare == 'year') {
                $toLastYear = dt::addDays(-365, $form->rec->to);
                if ($form->rec->from < $toLastYear) {
                    $form->setError('compare', 'Периода трябва да е по-малък от 365 дни за да сравнявате с "миналогодишен" период.
                                                  За да сравнявате периоди по-големи от 1 година, използвайте сравнение с "предходен" период');
                }
            }
        }
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
        
        $sQuery = sales_Sales::getQuery();
        
        $sQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$rec->to}') OR (#valior >= '{$fromPreviuos}' AND #valior <= '{$toPreviuos}')");
        
        while ($sRec = $sQuery->fetch()) {
            
            //договори за продажба за периода
            $salesInPeriod[$sRec->id]=$sRec->id;
        }
        
        
        
        $iQuery = acc_Items::getQuery();
        
        $classId = core_Classes::getId('sales_Sales');
        
        $nomId = acc_Lists::fetchByName('Разходни обекти')->id;
        
        $iQuery->likeKeylist('lists', $nomId);
        
        $iQuery->where("(#classId = $classId)");
        
        while ($iRec = $iQuery->fetch()) {
            
            //договори за продажба които са разходни обекти
            $itemsDoc[$iRec->id]=$iRec->objectId;
            
        }
        
        $salesItems = array_intersect($itemsDoc,$salesInPeriod );
        
        
        // масив с разходните обекти за проверка
        $salesItemsIds = array_keys($salesItems);
        
        $totalExpectedTransportCost =0;
        
        foreach ($salesItems as $key=>$val){
            
            $id = $key;
            
            // добавяме в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    
                    'saleId' => $val,
                    'itemId' => $key,
                    'expectedTransportCost' =>sales_Sales::fetchField($val,'expectedTransportCost')
                );
            } else {
                $obj = &$recs[$id];
                
            }
            
            $totalExpectedTransportCost += sales_Sales::fetchField($val,'expectedTransportCost');
            
            
        }
        
        
        
        
        $cQuery = acc_CostAllocations::getQuery();
        
        $cQuery->in('expenseItemId', $salesItemsIds);
        
        $totalAmountPart = 0;
        
        while ($alocatedCost = $cQuery->fetch()){
            
            
            $className = cls::get($alocatedCost-> detailClassId)->className;
            
            if ($className == 'purchase_PurchasesDetails'){
                
            $recs[$alocatedCost->expenseItemId]->className = $className;
            $detail = $className::fetch($alocatedCost-> detailRecId);
            $recs[$alocatedCost->expenseItemId]->purchaseId .=$detail-> requestId.'/'.$alocatedCost-> detailClassId.',';
            
            
            }
            
            if ($className == 'purchase_ServicesDetails'){
                
                $recs[$alocatedCost->expenseItemId]->className = $className;
                $detail = $className::fetch($alocatedCost-> detailRecId);
                $recs[$alocatedCost->expenseItemId]->purchaseId .=$detail-> shipmentId.'/'.$alocatedCost-> detailClassId.',';
                
                
            }
            
            $aaa[]=$detail;
            
            $recs[$alocatedCost->expenseItemId]->alocatedPart =$alocatedCost-> quantity;
            $recs[$alocatedCost->expenseItemId]->amount =$detail-> amount;
            $recs[$alocatedCost->expenseItemId]->amountPart +=$detail-> amount*$alocatedCost-> quantity;
            $totalAmountPart+=$detail-> amount*$detail-> quantity;
            
        }//bp($aaa);
   
        $totalArr['total'] = (object) array(
            'totalAmountPart' => $totalAmountPart,
            'totalExpectedTransportCost' => $totalExpectedTransportCost
        );
        
        array_unshift($recs, $totalArr['total']);
      
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
        
        
        if ($export === false) {
            
            $fld->FLD('saleId', 'varchar', 'caption=Продажба,tdClass=centered');
            $fld->FLD('expectedTransportCost', 'varchar', 'caption=Очакванo,tdClass=centered');
            $fld->FLD('amountPart', 'varchar', 'caption=Платено,tdClass=centered');
            $fld->FLD('purchaseId', 'varchar', 'caption=Покупка,tdClass=centered');
            
        } else {
            
            
        }
        
        
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
        
        if ($dRec->totalAmountPart) {
            $row->saleId = '<b>' . 'ОБЩО ЗА ПЕРИОДА:' . '</b>';
            $row->expectedTransportCost = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalExpectedTransportCost) . '</b>';
            $row->amountPart = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalAmountPart) . '</b>';
            
            
            
            return $row;
        }
        
        $row->expectedTransportCost =core_Type::getByName('double(decimals=2)')->toVerbal($dRec->expectedTransportCost);
        
        //$row->saleId = sales_Sales::getHyperlink($dRec->saleId);
        
        $Sale = doc_Containers::getDocument(sales_Sales::fetch($dRec->saleId)->containerId);
        $saleHandle = sales_Sales::getHandle($dRec->saleId);
        $singleUrl = $Sale->getUrlWithAccess($Sale->getInstance(), $Sale->that);
        
        $row->saleId = ht::createLink(
            "#{$saleHandle}",
            $singleUrl,
            false,
            "ef_icon={$Sale->singleIcon}");
            
            $row->amountPart =core_Type::getByName('double(decimals=2)')->toVerbal($dRec->amountPart);
            
//             $Purchase = doc_Containers::getDocument(purchase_Purchases::fetch($dRec->purchaseId)->containerId);
//             $purchaseHandle = purchase_Purchases::getHandle($dRec->purchaseId);
//             $singleUrl = $Purchase->getUrlWithAccess($Purchase->getInstance(), $Purchase->that);
            
//             $row->purchaseId = ht::createLink(
//                 "#{$purchaseHandle}",
//                 $singleUrl,
//                 false,
//                 "ef_icon={$Purchase->singleIcon}");

            $purchaise = explode(',',trim($dRec->purchaseId,','));
            
            foreach ($purchaise as $v){
                
                $arr=explode('/', $v);
                
                //$clsassName = cls::get($arr[1])->className;
                
                $row->purchaseId ='';
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
                                <small><div><!--ET_BEGIN from-->|От|*: [#from#]<!--ET_END from--></div></small>
                                <small><div><!--ET_BEGIN to-->|До|*: [#to#]<!--ET_END to--></div></small>
            
                                </fieldset><!--ET_END BLOCK-->"));
        
        
        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->from) . '</b>', 'from');
        }
        
        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->to) . '</b>', 'to');
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

