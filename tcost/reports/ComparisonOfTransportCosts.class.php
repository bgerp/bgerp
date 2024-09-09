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
 * @title     Логистика » Сравнение на очакваните и реалните транспортни разходи
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
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck;
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;
    
    
    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'from,to';
    
    
    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields = 'difference,contragent';


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
        $fieldset->FLD('from', 'date', 'caption=От,after=compare,single=none,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,mandatory');
        $fieldset->FLD('currency', 'varchar', 'caption=Валута,input=none,mandatory');
        $fieldset->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Държава,placeholder = Всички,after=to,single=none');
        $fieldset->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)', 'caption=Условие на доставка,placeholder = Всички,after=country,single=none');
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
        
        $form->setDefault('currency', 'BGN');
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
        $salesItems = array();
        
        $iQuery = acc_Items::getQuery();
        
        $classId = core_Classes::getId('sales_Sales');
        
        $nomId = acc_Lists::fetchByName('Разходни обекти')->id;
        
        $iQuery->likeKeylist('lists', $nomId);
        
        $iQuery->where("(#classId = ${classId})");
        
        $iQuery->EXT('saleState', 'sales_Sales', 'externalName=state,externalKey=objectId');
        
        $iQuery->EXT('saleThreadId', 'sales_Sales', 'externalName=threadId,externalKey=objectId');
        
        $iQuery->EXT('saleContoActions', 'sales_Sales', 'externalName=contoActions,externalKey=objectId');
        
        $iQuery->where("(#saleState = 'closed') OR (#saleState = 'active')");
        
        $iQuery->EXT('saleActivatedOn', 'sales_Sales', 'externalName=activatedOn,externalKey=objectId');
        
        $iQuery->where('(#saleActivatedOn IS NOT NULL)');
        
        $iQuery->where(array("#saleActivatedOn >= '[#1#]' AND #saleActivatedOn <= '[#2#]'", $rec->from . ' 00:00:00', $rec->to . ' 23:59:59'));

        while ($iRec = $iQuery->fetch()) {
            
            //договори за продажба които са разходни обекти за избрания период
            $saleAndThread = $iRec->objectId.'|'.$iRec->saleThreadId.'|'.$iRec->saleContoActions;
            
            $salesItems[$iRec->id] = $saleAndThread;
        }
        if (empty($salesItems)) {
            
            return $recs;
        }
        
        
        // масив с разходните обекти за проверка
        $salesItemsIds = array_keys($salesItems);
        
        $totalExpectedTransportCost = 0;
        
        foreach ($salesItems as $key => $val) {
            $id = $key;
            list($saleIdItem, $threadIdItem, $salecontoActions) = explode('|', $val);

            //условие на доставка
            $deliveryTermId = sales_Sales::fetch($saleIdItem)->deliveryTermId;

            $hiddenTransportCost = sales_TransportValues::calcInDocument('sales_Sales', $saleIdItem);
            
           // if (strpos($salecontoActions, 'ship') != false) {
                $visibleTransportCost = self::getVisibleTransportCost($saleIdItem);
          //  }

            //if (in_array($threadIdItem, array_keys($ppsArr))) {
                //$visibleTransportCost += $ppsArr[$threadIdItem];
            //}
            
            
            // добавяме в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    
                    'saleId' => $saleIdItem,
                    'deliveryTermId' => $deliveryTermId,
                    'contragentClassId' => sales_Sales::fetchField($saleIdItem, 'contragentClassId'),
                    'contragentId' => sales_Sales::fetchField($saleIdItem, 'contragentId'),
                    'itemId' => $key,
                    'expectedTransportCost' => $hiddenTransportCost + $visibleTransportCost
                );
            } else {
                $obj = &$recs[$id];
            }
            
            $visibleTransportCost = $hiddenTransportCost = 0;
        }

        $cQuery = acc_CostAllocations::getQuery();
        
        $cQuery->in('expenseItemId', $salesItemsIds);
        
        $totalAmountPart = 0;
        
        $stateArr = array('draft','rejected','pending');
        
        while ($alocatedCost = $cQuery->fetch()) {
            $marker = 1;
            
            $className = cls::get($alocatedCost-> detailClassId)->className;
            
            $detailRec = $className::fetch($alocatedCost-> detailRecId);

            //Проверка, дали артикула е от тип "Транспортна услуга"
            if (cat_Products::fetch($detailRec-> productId)->isPublic == 'no' &&
                !cat_Products::haveDriver($detailRec-> productId,'transsrv_ProductDrv')){
                continue;
            }
            if(cat_Products::fetch($detailRec-> productId)->isPublic == 'yes') {
               $transIdArr = keylist::toArray(sales_Setup::get('TRANSPORT_PRODUCTS_ID'));
               expect(!empty($transIdArr),'Липсва избран артикул за транспорт');
               if (!in_array($detailRec-> productId,$transIdArr))continue;

            }

            $masterClassName = cls::get($alocatedCost-> detailClassId)->Master->className;

            
            if ($className == 'purchase_PurchasesDetails') {
                if (in_array($masterClassName::fetchField($detailRec->requestId, 'state'), $stateArr)) {
                    continue;
                }
                
                if (strpos($masterClassName::fetchField($detailRec->requestId, 'contoActions'), 'ship') === false) {
                    continue;
                }

                $recs[$alocatedCost->expenseItemId]->purchaseId .= $detailRec-> requestId.'/'.$alocatedCost-> detailClassId.',';
            }
            
            if (($className == 'purchase_ServicesDetails') || ($className == 'sales_ServicesDetails')) {
                if (in_array($masterClassName::fetchField($detailRec->shipmentId, 'state'), $stateArr)) {
                    continue;
                }

                if (substr($className, 0, 5) == 'sales') {
                    $marker = -1;
                }
                 $recs[$alocatedCost->expenseItemId]->purchaseId .= $detailRec-> shipmentId.'/'.$alocatedCost-> detailClassId.',';
            }

            if (is_null($recs[$alocatedCost->expenseItemId]->countryId)) {
                if (!is_null(cat_Products::fetch($detailRec-> productId)->toCountry)) {
                    $recs[$alocatedCost->expenseItemId]->countryId = cat_Products::fetch($detailRec-> productId)->toCountry;
                }
            }
            
            $recs[$alocatedCost->expenseItemId]->className = $className;
            $recs[$alocatedCost->expenseItemId]->purMasterClassName = $masterClassName;
            $recs[$alocatedCost->expenseItemId]->alocatedPart = $alocatedCost-> quantity;
            $recs[$alocatedCost->expenseItemId]->amount = $detailRec-> amount;
            $recs[$alocatedCost->expenseItemId]->amountPart += $detailRec-> price * $alocatedCost-> quantity * $marker;
        }

        foreach ($recs as $key => $val) {

            //Филтър по условие на доставка
            if ($rec->deliveryTermId) {
                if (($rec->deliveryTermId != $val->deliveryTermId) || is_null($val->deliveryTermId)) {
                    unset($recs[$key]);
                    continue;
                }
            }
            
            //филтър по държава
            if ($rec->country) {
                if (($rec->country != $val->countryId) || is_null($val->countryId)) {
                    unset($recs[$key]);
                    continue;
                }
            }
            
            //сумиране на колоните
            $totalAmountPart += $val-> amountPart;
            $totalExpectedTransportCost += $val->expectedTransportCost;
            $recs[$key]->difference = $val->expectedTransportCost - $val->amountPart;
        }
        
        if (!is_null($recs)) {
            arr::sortObjects($recs, 'difference', 'asc', 'native');
        }
        
        if (!empty($recs)) {
            $totalArr['total'] = (object) array(
                'totalAmountPart' => $totalAmountPart,
                'totalExpectedTransportCost' => $totalExpectedTransportCost,
                'totalDifference' => $totalExpectedTransportCost - $totalAmountPart
            );
            
            array_unshift($recs, $totalArr['total']);
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
        
        
        if ($export === false) {
            $fld->FLD('saleId', 'varchar', 'caption=Продажба,tdClass=centered');
            $fld->FLD('deliveryTermId', 'varchar', 'caption=Условие на доставка,tdClass=centered');
            $fld->FLD('contragent', 'varchar', 'caption=Контрагент,tdClass=centered');
            $fld->FLD('expectedTransportCost', 'varchar', 'caption=Очакванo,tdClass=centered');
            $fld->FLD('amountPart', 'varchar', 'caption=Платено,tdClass=centered');
            $fld->FLD('difference', 'varchar', 'caption=Разлика,tdClass=centered');
            $fld->FLD('purchaseId', 'varchar', 'caption=Разходен документ');
            if (!$rec->country) {
                $fld->FLD('country', 'varchar', 'caption=Държава,tdClass=centered');
            }
        } else {
            $fld->FLD('saleId', 'varchar', 'caption=Продажба,tdClass=centered');
            $fld->FLD('deliveryTermId', 'varchar', 'caption=Условие на доставка,tdClass=centered');
            $fld->FLD('contragent', 'varchar', 'caption=Контрагент,tdClass=centered');
            $fld->FLD('expectedTransportCost', 'double(decimals=2)', 'caption=Очакванo,tdClass=centered');
            $fld->FLD('amountPart', 'double(decimals=2)', 'caption=Платено,tdClass=centered');
            $fld->FLD('difference', 'double(decimals=2)', 'caption=Разлика,tdClass=centered');
            $fld->FLD('purchaseId', 'varchar', 'caption=Разходен документ');
            $fld->FLD('country', 'varchar', 'caption=Държава,tdClass=centered');
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
        
        if (!is_null($dRec->totalAmountPart)) {
            $row->contragent = '<b>' . 'ОБЩО ЗА ПЕРИОДА:' . '</b>';
            $row->expectedTransportCost = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalExpectedTransportCost) . '</b>';
            $row->amountPart = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalAmountPart) . '</b>';
            $row->difference = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalDifference) . '</b>';
            $row->difference = ht::styleNumber($row->difference, $dRec->totalDifference);
            
            return $row;
        }
        
        $row->expectedTransportCost = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->expectedTransportCost);
        
        $Sale = doc_Containers::getDocument(sales_Sales::fetch($dRec->saleId)->containerId);
        $saleState = sales_Sales::fetch($dRec->saleId)->state;
        $saleHandle = sales_Sales::getHandle($dRec->saleId);
        $singleUrl = $Sale->getUrlWithAccess($Sale->getInstance(), $Sale->that);
        
        $row->saleId = "<span class= 'state-{$saleState} document-handler' >".ht::createLink(
            "#{$saleHandle}",
            $singleUrl,
            false,
            "ef_icon={$Sale->singleIcon}"
            ). '</span>';

        if($dRec->deliveryTermId){
            $row->deliveryTermId = cond_DeliveryTerms::fetch($dRec->deliveryTermId)->codeName;
        }

        
        $contragentClass = core_Classes::getName($dRec->contragentClassId);
        $row->contragent = $contragentClass::fetchField($dRec->contragentId, 'name');
        
        
        $row->amountPart = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->amountPart);
        
        $row->difference = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->difference);
        $row->difference = ht::styleNumber($row->difference, ($dRec->difference));
        
        if (isset($dRec->purchaseId)) {
            $purchaise = explode(',', trim($dRec->purchaseId, ','));
            
            foreach ($purchaise as $v) {
                list($purId, $detId) = explode('/', $v);
                
                $purMasterClassName = cls::get($detId)->Master->className;
                
                $Purchase = doc_Containers::getDocument($purMasterClassName::fetch($purId)->containerId);
                $purchaseState = $purMasterClassName::fetch($purId)->state;
                $purchaseHandle = $purMasterClassName::getHandle($purId);
                $singleUrl = $Purchase->getUrlWithAccess($Purchase->getInstance(), $Purchase->that);
                $purchases .= "<span class= 'state-{$purchaseState} document-handler' >".ht::createLink(
                            "#{$purchaseHandle}",
                            $singleUrl,
                            false,
                            "ef_icon={$Purchase->singleIcon}"
                            ). '</span>'.' ';
            }
            
            $row->purchaseId = trim($purchases);
        }
        if (!is_null($dRec->countryId)) {
            $row->country = drdata_Countries::getCountryName($dRec->countryId);
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
                                        <!--ET_BEGIN country--><div>|Държава|*: [#country#]</div><!--ET_END country-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));
        
        
        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->from) . '</b>', 'from');
        }
        
        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->to) . '</b>', 'to');
        }
        
        if (isset($data->rec->country)) {
            $fieldTpl->append('<b>' . drdata_Countries::getCountryName($data->rec->country) . '</b>', 'country');
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
        if ($res->totalAmountPart) {
            $res->saleId = 'ОБЩО ЗА ПЕРИОДА:';
            $res->expectedTransportCost = $dRec->totalExpectedTransportCost;
            $res->amountPart = $dRec->totalAmountPart;
            $res->difference = $dRec->totalDifference;
        } else {
            $saleHandle = sales_Sales::getHandle($dRec->saleId);
            $res->saleId = $saleHandle;
            $contragentClass = core_Classes::getName($dRec->contragentClassId);
            $res->contragent = $contragentClass::fetchField($dRec->contragentId, 'name');
            
            if (!is_null($dRec->countryId)) {
                $res->country = drdata_Countries::getCountryName($dRec->countryId);
            }
            
            if (isset($dRec->purchaseId)) {
                $purchaise = explode(',', trim($dRec->purchaseId, ','));
                foreach ($purchaise as $v) {
                    list($purId, $detId) = explode('/', $v);
                    
                    $purMasterClassName = cls::get($detId)->Master->className;
                    
                    $purchaseHandle .= $purMasterClassName::getHandle($purId).', ';
                }
                
                $res->purchaseId = trim($purchaseHandle, ', ');
            }
        }
    }
    
    
    /**
     * Колко е видимия транспорт начислен в сделката
     *
     * @param stdClass $docId - запис на ред
     *
     * @return float - сумата на видимия транспорт в основна валута без ДДС
     */
    public function getVisibleTransportCost($docId)
    {
        // Извличат се всички детайли и се изчислява сумата на транспорта, ако има
        $query = sales_SalesDetails::getQuery();
        $query->where("#saleId = {$docId}");
        
        return sales_TransportValues::getVisibleTransportCost($query);
    }
}
