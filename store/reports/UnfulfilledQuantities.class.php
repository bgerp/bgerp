<?php


/**
 * Мениджър на отчети за неизпълнени количества
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Склад » Неизпълнени количества
 */
class store_reports_UnfulfilledQuantities extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,manager,store,planing,purchase';
    
    
    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;
    
    
    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck;
    
    
    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields ;
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        //$fieldset->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,after=title');
        $fieldset->FLD('from', 'date', 'caption=От,after=title,single=none,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,mandatory');
        $fieldset->FLD('contragent', 'key(mvc=doc_Folders,select=title,allowEmpty)', 'caption=Контрагент,placeholder=Всички,single=none,after=to');
        $fieldset->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,placeholder=Всички,single=none,after=contragent');
        $fieldset->FLD('group', 'key(mvc=cat_Groups,select=name,allowEmpty)', 'caption=Група артикули,placeholder=Всички,after=storeId,single=none');
        $fieldset->FLD('tolerance', 'double', 'caption=Толеранс,after=group,unit = %,single=none,mandatory');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *                                      $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        $form->setDefault('tolerance', 5);
        
        $salesQuery = sales_Sales::getQuery();
        
        $salesQuery->EXT('folderTitle', 'doc_Folders', 'externalName=title,externalKey=folderId');
        
        $salesQuery->groupBy('folderId');
        
        $salesQuery->show('folderId, contragentId, folderTitle');
        
        while ($contragent = $salesQuery->fetch()) {
            if (!is_null($contragent->contragentId)) {
                $suggestions[$contragent->folderId] = $contragent->folderTitle;
            }
        }
        
        asort($suggestions);
        
        $form->setSuggestions('contragent', $suggestions);
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
        $rec = $form->rec;
        
        if ($form->isSubmitted()) {
            if ($rec->tolerance < 0) {
                $form->setError('tolerance', ' Толераса трябва да е положително число. ');
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
        $saleDetRecs = array();
        $shipDetRecs = array();
        $recs = array();
        
        
        //Продажби
        $querySaleDetails = sales_SalesDetails::getQuery();
        
        //    $querySaleDetails->where(array("#createdOn >= '[#1#]' AND #createdOn <= '[#2#]'",$rec->from . ' 00:00:01',$rec->to . ' 23:59:59'));
        
        $querySaleDetails->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        
        $querySaleDetails->EXT('threadId', 'sales_Sales', 'externalName=threadId,externalKey=saleId');
        
        $querySaleDetails->EXT('folderId', 'sales_Sales', 'externalName=folderId,externalKey=saleId');
        
        $querySaleDetails->EXT('state', 'sales_Sales', 'externalName=state,externalKey=saleId');
        
        $querySaleDetails->EXT('contragentClassId', 'sales_Sales', 'externalName=contragentClassId,externalKey=saleId');
        
        $querySaleDetails->EXT('contragentId', 'sales_Sales', 'externalName=contragentId,externalKey=saleId');
        
        $querySaleDetails->where("#state != 'rejected'");
        
        if (!is_null($rec->contragent)) {
            $checkedContragents = keylist::toArray($rec->contragent);
            
            $querySaleDetails-> in('folderId', $checkedContragents);
        }
        
        $querySaleDetails->where("#isPublic = 'yes'");
        
        $querySaleDetails->show('id,saleId,contragentClassId,contragentId,productId,threadId,folderId,quantity,createdOn');
        
        while ($saleArt = $querySaleDetails->fetch()) {
            $saleThreadsIds[] = $saleArt->threadId;
            $saleKey = $saleArt->threadId.'|'.$saleArt->productId;
            
            // добавяме в масива
            if (!array_key_exists($saleKey, $saleDetRecs)) {
                $saleDetRecs[$saleKey] = (object) array(
                    
                    'productId' => $saleArt->productId,
                    'requestQuantity' => $saleArt->quantity,
                    'saleId' => $saleArt->saleId,
                    'contragentClassId' => $saleArt->contragentClassId,
                    'contragentId' => $saleArt->contragentId,
                    'threadId' => $saleArt->threadId,
                    'folderId' => $saleArt->folderId
                
                );
            } else {
                $obj = &$saleDetRecs[$saleKey];
                $obj->requestQuantity += $saleArt->quantity;
            }
        }
        
        
        //Експедиционни нареждания
        $queryShipmentOrderDetails = store_ShipmentOrderDetails::getQuery();
        
        $queryShipmentOrderDetails->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
        
        $queryShipmentOrderDetails->EXT('threadId', 'store_ShipmentOrders', 'externalName=threadId,externalKey=shipmentId');
        
        $queryShipmentOrderDetails->EXT('shipmentOrderActivatedOn', 'store_ShipmentOrders', 'externalName=activatedOn,externalKey=shipmentId');
        
        $queryShipmentOrderDetails->EXT('state', 'store_ShipmentOrders', 'externalName=state,externalKey=shipmentId');
        
        $queryShipmentOrderDetails->EXT('storeId', 'store_ShipmentOrders', 'externalName=storeId,externalKey=shipmentId');
        
        $queryShipmentOrderDetails->where("#state = 'active'");
        
        $queryShipmentOrderDetails->where(array("#shipmentOrderActivatedOn >= '[#1#]' AND #shipmentOrderActivatedOn <= '[#2#]'",$rec->from . ' 00:00:01',$rec->to . ' 23:59:59'));
        
        //филтър по склад
        if ($rec->storeId) {
            $queryShipmentOrderDetails->where("#storeId = {$rec->storeId}");
        }
        
        //Филтър по групи артикули
        if (isset($rec->group)) {
            $queryShipmentOrderDetails->where('#groups IS NOT NULL');
            $queryShipmentOrderDetails->likeKeylist('groups', $rec->group);
        }
        
        $queryShipmentOrderDetails->show('id,shipmentId,productId,threadId,quantity,createdOn,shipmentOrderActivatedOn');
        
        while ($shipmentDet = $queryShipmentOrderDetails->fetch()) {
            $threadId = $shipmentDet->threadId;
            
            $saleIdShip = doc_Threads::getFirstDocument($threadId)->that;
            
            $firstDocumentName = doc_Threads::getFirstDocument($threadId)->className;
            
            if ($firstDocumentName != 'sales_Sales') {
                continue;
            }
            
            $shipKey = $shipmentDet->threadId.'|'.$shipmentDet->productId;
            
            // добавяме в масива
            if (!array_key_exists($shipKey, $shipDetRecs)) {
                $shipDetRecs[$shipKey] = (object) array(
                    
                    'id' => $shipmentDet->id,
                    'productId' => $shipmentDet->productId,
                    'shipedQuantity' => $shipmentDet->quantity,
                    'saleIdShip' => $saleIdShip,
                    'firstDocumentName' => $firstDocumentName,
                    'shipmentId' => $shipmentDet->shipmentId,
                    'threadIdShip' => $shipmentDet->threadId
                
                );
            } else {
                $obj = &$shipDetRecs[$shipKey];
                $obj->shipedQuantity += $shipmentDet->quantity;
            }
        }
        
        foreach ($shipDetRecs as $key => $ship) {
            foreach ($saleDetRecs as $saleKey => $sale) {
                expect($ship->firstDocumentName == 'sales_Sales');
                
                if ($key == $saleKey) {
                    expect($sale->saleId == $ship->saleIdShip);
                    
                    $tolerance = (100 - $rec->tolerance) / 100;
                    
                    if ($ship->shipedQuantity < ($sale->requestQuantity * $tolerance)) {
                        $recs[$saleKey] = (object) array(
                            
                            'saleId' => $sale->saleId,
                            'productId' => $sale->productId,
                            'measure' => cat_Products::getProductInfo($sale->productId)->productRec->measureId,
                            'shipmentId' => $ship->shipmentId,
                            'shipedQuantity' => $ship->shipedQuantity,
                            'requestQuantity' => $sale->requestQuantity,
                            'contragentClassId' => $sale->contragentClassId,
                            'contragentId' => $sale->contragentId
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
        
        $fld->FLD('saleId', 'varchar', 'caption=Продажба,tdClass=centered');
        $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        $fld->FLD('contragent', 'varchar', 'caption=Контрагент,tdClass=centered');
        $fld->FLD('measure', 'varchar', 'caption=Мярка,tdClass=centered');
        $fld->FLD('requestQuantity', 'double(smartRound,decimals=2)', 'caption=Количество->Заявено,smartCenter');
        $fld->FLD('shipedQuantity', 'double(smartRound,decimals=2)', 'caption=Количество->Експедирано,smartCenter');
        $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'caption=Количество->Неизпълнение,smartCenter');
        if ($rec->limmits == 'yes') {
            $fld->FLD('minQuantity', 'double(smartRound,decimals=2)', 'caption=Минимално,smartCenter');
            $fld->FLD('maxQuantity', 'double(smartRound,decimals=2)', 'caption=Максимално,smartCenter');
            $fld->FLD('conditionQuantity', 'text', 'caption=Състояние,tdClass=centered');
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
        
        $row = new stdClass();
        
        $row->saleId = sales_Sales::getLinkToSingle_($dRec->saleId, 'id');
        
        //$row->productId = cat_Products::getLinkToSingle_($dRec->productId, 'name');
        
        $row->productId = cat_Products::getShortHyperlink($dRec->productId);
        
        $contragentClassName = cls::getClassName($dRec->contragentClassId);
        
        $row->contragent = $contragentClassName::getTitleById($dRec->contragentId);
        
        if (isset($dRec->measure)) {
            $row->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
        }
        
        $row->requestQuantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->requestQuantity);
        
        $row->shipedQuantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->shipedQuantity);
        
        $row->quantity = "<span class = 'red'>".'<b>'. core_Type::getByName('double(decimals=2)')->toVerbal($dRec->requestQuantity - $dRec->shipedQuantity).'</b>'.'</span>';
        
        return $row;
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
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                <small><div><!--ET_BEGIN from-->|От|*: [#from#]<!--ET_END from--></div></small>
                                <small><div><!--ET_BEGIN to-->|До|*: [#to#]<!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN contragent-->|Контрагент|*: [#contragent#]<!--ET_END contragent--></div></small>
                                <small><div><!--ET_BEGIN group-->|Група артикули|*: [#group#]<!--ET_END group--></div></small>
                                <small><div><!--ET_BEGIN storeId-->|Склад|*: [#storeId#]<!--ET_END storeId--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
        
        
        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' . $data->rec->from . '</b>', 'from');
        }
        
        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $data->rec->to . '</b>', 'to');
        }
        
        
        if (isset($data->rec->contragent)) {
            foreach (type_Keylist::toArray($data->rec->contragent) as $contragent) {
                $marker++;
                
                $contragentVerb .= (doc_Folders::getTitleById($contragent));
                
                if ((count(type_Keylist::toArray($data->rec->contragent))) - $marker != 0) {
                    $contragentVerb .= ', ';
                }
            }
            
            $fieldTpl->append('<b>' . $contragentVerb . '</b>', 'contragent');
        } else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'contragent');
        }
        
        if (isset($data->rec->group)) {
            $fieldTpl->append('<b>' . cat_Groups::getVerbal($data->rec->group, 'name') . '</b>', 'group');
        }
        
        if (isset($data->rec->storeId)) {
            $fieldTpl->append('<b>' . store_Stores::getTitleById($data->rec->storeId) . '</b>', 'storeId');
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
    protected static function on_AfterGetCsvRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec)
    {
    }
}
