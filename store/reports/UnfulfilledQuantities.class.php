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
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields = 'saleId';


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,manager,store,planning,purchase';

    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'saleId';


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
    protected $changeableFields;


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

        $fieldset->FLD('from', 'date', 'caption=От,after=title,single=none,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,mandatory');
        $fieldset->FLD('saleState', 'set(active=Активна, closed=Приключена, pending=В заявка)', 'notNull,caption=Статус на сделката,maxRadio=3,after=to,mandatory,single=none');
        $fieldset->FLD('storable', 'enum(storable=Складируеми, nonStorable=Не складируеми, all=Всички)', 'notNull,caption=Вид на артикула,maxRadio=3,after=saleState,mandatory,single=none');
        $fieldset->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,placeholder=Всички,after=storable');
        $fieldset->FLD('contragent', 'key(mvc=doc_Folders,select=title,allowEmpty)', 'caption=Контрагент,placeholder=Всички,single=none,after=storeId');
        $fieldset->FLD('groups', 'keylist(mvc=cat_Groups,select=name)', 'caption=Група,after=contragent,placeholder=Всички,single=none');
        $fieldset->FLD('tolerance', 'double', 'caption=Изпълнени под,after=groups,unit = %,single=none,mandatory');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *                                      $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;

        $form->setDefault('tolerance', 5);
        $form->setDefault('saleState', 'closed');
        $form->setDefault('storable', 'storable');

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
     * @param embed_Manager $Embedder
     * @param core_Form $form
     * @param stdClass $data
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
        $salesThreadsIdArr = array();
        $recs = array();

        // Записи на детайли на продажби затворени през този период,
        // които не са бързи продажби и не са затворени чрез обединяване
        $querySaleDetails = sales_SalesDetails::getQuery();

        $querySaleDetails->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');

        $querySaleDetails->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');

        $querySaleDetails->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');

        $querySaleDetails->EXT('threadId', 'sales_Sales', 'externalName=threadId,externalKey=saleId');

        $querySaleDetails->EXT('closedOn', 'sales_Sales', 'externalName=closedOn,externalKey=saleId');

        $querySaleDetails->EXT('activatedOn', 'sales_Sales', 'externalName=activatedOn,externalKey=saleId');

        $querySaleDetails->EXT('folderId', 'sales_Sales', 'externalName=folderId,externalKey=saleId');

        $querySaleDetails->EXT('state', 'sales_Sales', 'externalName=state,externalKey=saleId');

        $querySaleDetails->EXT('contragentClassId', 'sales_Sales', 'externalName=contragentClassId,externalKey=saleId');

        $querySaleDetails->EXT('contragentId', 'sales_Sales', 'externalName=contragentId,externalKey=saleId');

        $querySaleDetails->EXT('contoActions', 'sales_Sales', 'externalName=contoActions,externalKey=saleId');

        $querySaleDetails->EXT('closeWith', 'sales_Sales', 'externalName=closeWith,externalKey=saleId');

        $querySaleDetails->in('state', array('closed', 'active', 'pending'));

        $or = false;
        if (in_array('closed', explode(',', $rec->saleState))) {
            $querySaleDetails->where(array("#state = 'closed' AND #closeWith IS NULL AND #closedOn >= '[#1#]' AND #closedOn <= '[#2#]'", $rec->from . ' 00:00:00', $rec->to . ' 23:59:59'));
            $or = true;
        }

        if (in_array('active', explode(',', $rec->saleState))) {
            $querySaleDetails->where(array("#state = 'active' AND #activatedOn >= '[#1#]' AND #activatedOn <= '[#2#]'", $rec->from . ' 00:00:00', $rec->to . ' 23:59:59'), $or);
            $or = true;
        }

        if (in_array('pending', explode(',', $rec->saleState))) {
            $querySaleDetails->where(array("#state = 'pending' AND #createdOn >= '[#1#]' AND #createdOn <= '[#2#]'", $rec->from . ' 00:00:00', $rec->to . ' 23:59:59'), $or);
        }

        //Филтър по групи артикули
        if (isset($rec->groups)) {

            plg_ExpandInput::applyExtendedInputSearch('cat_Products', $querySaleDetails, $rec->groups, 'productId');

        }

        $querySaleDetails->where("FIND_IN_SET('ship', REPLACE(#contoActions, ' ', '')) = 0");

        //Филтър по контрагент на масива на продажбите
        if (!is_null($rec->contragent)) {
            $checkedContragents = keylist::toArray($rec->contragent);

            $querySaleDetails->in('folderId', $checkedContragents);
        }

        //Филтър за нестандартни артикули. В справката влизат САМО СТАНДАРТНИ
        $querySaleDetails->where("#isPublic = 'yes'");

        //Филтър за вид артикул
        if ($rec->storable == 'storable') {
            $querySaleDetails->where("#canStore = 'yes'");
        } elseif ($rec->storable == 'nonStorable') {
            $querySaleDetails->where("#canStore = 'no'");
        }

        $querySaleDetails->show('id,saleId,contragentClassId,contragentId,productId,threadId,folderId,quantity,createdOn,groups,state');

        // Синхронизира таймлимита с броя записи
        $timeLimit = $querySaleDetails->count() * 0.2;

        if ($timeLimit >= 30) {
            core_App::setTimeLimit($timeLimit);
        }

        while ($saleArt = $querySaleDetails->fetch()) {

            $saleKey = $saleArt->threadId . '|' . $saleArt->productId;

            // добавяме в масива на артикулите от договорите през избрания период(филтрирани)
            if (!array_key_exists($saleKey, $saleDetRecs)) {
                $saleDetRecs[$saleKey] = (object)array(

                    'productId' => $saleArt->productId,
                    'requestQuantity' => $saleArt->quantity,
                    'saleId' => $saleArt->saleId,
                    'contragentClassId' => $saleArt->contragentClassId,
                    'contragentId' => $saleArt->contragentId,
                    'threadId' => $saleArt->threadId,
                    'folderId' => $saleArt->folderId,
                    'state' => $saleArt->state,

                );
            } else {
                $obj = &$saleDetRecs[$saleKey];
                $obj->requestQuantity += $saleArt->quantity;
            }
        }

        //Масив с нишките в които се намират продажбите за проследяване: затворени през този период, не бързи, за стандартни артикули
        $salesThreadsIdArr = (arr::extractValuesFromArray($saleDetRecs, 'threadId'));

        //Детайли от Експедиционни нареждания , които са в нишките на избраните продажби
        $queryShipmentOrderDetails = store_ShipmentOrderDetails::getQuery();

        $queryShipmentOrderDetails->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');

        $queryShipmentOrderDetails->EXT('threadId', 'store_ShipmentOrders', 'externalName=threadId,externalKey=shipmentId');

        $queryShipmentOrderDetails->EXT('shipmentOrderActivatedOn', 'store_ShipmentOrders', 'externalName=activatedOn,externalKey=shipmentId');

        $queryShipmentOrderDetails->EXT('state', 'store_ShipmentOrders', 'externalName=state,externalKey=shipmentId');

        $queryShipmentOrderDetails->EXT('storeId', 'store_ShipmentOrders', 'externalName=storeId,externalKey=shipmentId');

        $queryShipmentOrderDetails->where("#state = 'active'");

        //Филтрираме само тези, които са от нишките на избраните артикули
        $queryShipmentOrderDetails->in('threadId', $salesThreadsIdArr);

        //филтър по склад
        if ($rec->storeId) {
            $queryShipmentOrderDetails->where("#storeId = {$rec->storeId}");
        }

        //Филтър по групи артикули
        if (isset($rec->group)) {
            $queryShipmentOrderDetails->where('#groups IS NOT NULL');

            plg_ExpandInput::applyExtendedInputSearch('cat_Products', $queryShipmentOrderDetails, $rec->group, 'productId');

        }

        $queryShipmentOrderDetails->show('id,shipmentId,productId,threadId,quantity,createdOn,shipmentOrderActivatedOn,groups');

        while ($shipmentDet = $queryShipmentOrderDetails->fetch()) {

            $saleIdShip = doc_Threads::getFirstDocument($shipmentDet->threadId)->that;

            $firstDocumentName = doc_Threads::getFirstDocument($shipmentDet->threadId)->className;

            if ($firstDocumentName != 'sales_Sales') {
                continue;
            }

            $shipKey = $shipmentDet->threadId . '|' . $shipmentDet->productId;

            // добавяме в масива от артикули експедирани в този период, и от нишките на избраните продажби
            if (!array_key_exists($shipKey, $shipDetRecs)) {
                $shipDetRecs[$shipKey] = (object)array(

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

        //Добавяме артикули, от които няма нищо експедирано но ги има в договорите
        $shipDetKeysArr = array_keys($shipDetRecs);       //Масив с ключове на масива на детайлите по експедиционните

        $salesDetKeysArr = array_keys($saleDetRecs);      //Масив с ключове на масива на детайлите по договорите за продажби

        foreach ($salesDetKeysArr as $saleDetKey) {

            if (!in_array($saleDetKey, $shipDetKeysArr)) {

                $shipDetRecs[$saleDetKey] = (object)array(


                    'productId' => $saleDetRecs->productId,
                    'shipedQuantity' => 0,
                    'shipmentId' => '',
                    'firstDocumentName' => 'sales_Sales',
                    'saleIdShip' => $saleDetRecs->saleId,
                    'threadIdShip' => $saleDetRecs->threadId

                );


            }
        }

        foreach ($saleDetRecs as $saleKey => $sale) {
            foreach ($shipDetRecs as $shipKey => $ship) {
                expect($ship->firstDocumentName == 'sales_Sales');

                if ($shipKey == $saleKey) {
                    // expect($sale->saleId == $ship->saleIdShip);

                    $tolerance = (100 - $rec->tolerance) / 100;

                    $shipedQuantity = $ship->shipedQuantity;

                    if ($shipedQuantity < ($sale->requestQuantity * $tolerance)) {
                        $recs[$saleKey] = (object)array(

                            'saleId' => $sale->saleId,
                            'productId' => $sale->productId,
                            'measure' => cat_Products::getProductInfo($sale->productId)->productRec->measureId,
                            'shipmentId' => $ship->shipmentId,
                            'shipedQuantity' => $shipedQuantity,
                            'requestQuantity' => $sale->requestQuantity,
                            'contragentClassId' => $sale->contragentClassId,
                            'contragentId' => $sale->contragentId,
                            'state' => $sale->state,
                        );
                    }
                }
            }
        }
        if (!is_null($recs)) {

            arr::sortObjects($recs, 'saleId', 'desc');
        }


        return $recs;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *                         - записа
     * @param bool $export
     *                         - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        if ($export === false) {
            $fld->FLD('saleId', 'varchar', 'caption=Продажба,tdClass=centered');
            $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
            $fld->FLD('contragent', 'varchar', 'caption=Контрагент,tdClass=centered');
            $fld->FLD('measure', 'varchar', 'caption=Мярка,tdClass=centered');
            $fld->FLD('requestQuantity', 'double(decimals=2)', 'caption=Количество->Заявено,smartCenter');
            $fld->FLD('shipedQuantity', 'double(decimals=2)', 'caption=Количество->Експедирано,smartCenter');
            $fld->FLD('quantity', 'double(decimals=2)', 'caption=Количество->Неизпълнение,smartCenter');
        } else {
            $fld->FLD('saleId', 'varchar', 'caption=Продажба,tdClass=centered');
            $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
            $fld->FLD('code', 'varchar', 'caption=Код');
            $fld->FLD('contragent', 'varchar', 'caption=Контрагент,tdClass=centered');
            $fld->FLD('measure', 'varchar', 'caption=Мярка,tdClass=centered');
            $fld->FLD('requestQuantity', 'double(decimals=2)', 'caption=Количество->Заявено,smartCenter');
            $fld->FLD('shipedQuantity', 'double(decimals=2)', 'caption=Количество->Експедирано,smartCenter');
            $fld->FLD('quantity', 'double(decimals=2)', 'caption=Количество->Неизпълнение,smartCenter');

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

        if ($dRec->saleId) {
            $sRec = sales_Sales::fetch($dRec->saleId);
            $Sale = doc_Containers::getDocument(sales_Sales::fetch($dRec->saleId)->containerId);

            $handle = sales_Sales::getHandle($dRec->saleId);
            $state = (sales_Sales::fetch($dRec->saleId)->state);
            $singleUrl = toUrl(array($Sale->className, 'single', $dRec->saleId));

            $row->saleId = ht::createLink("#{$handle}", $singleUrl, false, "ef_icon={$Sale->singleIcon}");
            $row->saleId .= ' / ' . $sRec->valior;

        }


        //$row->productId = cat_Products::getLinkToSingle_($dRec->productId, 'name');

        $row->productId = cat_Products::getShortHyperlink($dRec->productId);

        $contragentClassName = cls::getClassName($dRec->contragentClassId);

        $row->contragent = $contragentClassName::getTitleById($dRec->contragentId);

        if (isset($dRec->measure)) {
            $row->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
        }

        $row->requestQuantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->requestQuantity);

        $row->shipedQuantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->shipedQuantity);

        $row->quantity = "<span class = 'red'>" . '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->requestQuantity - $dRec->shipedQuantity) . '</b>' . '</span>';

        $state = $dRec->state;

        $row->ROW_ATTR['class'] = "state-{$state}";

        return $row;
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN from--><div>|От|*: [#from#]</div><!--ET_END from-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                        <!--ET_BEGIN contragent--><div>|Контрагент|*: [#contragent#]</div><!--ET_END contragent-->
                                        <!--ET_BEGIN group--><div>|Група артикули|*: [#group#]</div><!--ET_END group-->
                                        <!--ET_BEGIN storeId--><div>|Склад|*: [#storeId#]</div><!--ET_END storeId-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));


        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' . $data->rec->from . '</b>', 'from');
        }

        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $data->rec->to . '</b>', 'to');
        }


        if (isset($data->rec->contragent)) {
            $marker = 0;
            foreach (type_Keylist::toArray($data->rec->contragent) as $contragent) {
                $marker++;

                $contragentVerb .= (doc_Folders::getTitleById($contragent));

                if ((countR(type_Keylist::toArray($data->rec->contragent))) - $marker != 0) {
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
     * @param stdClass $res
     * @param stdClass $rec
     * @param stdClass $dRec
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;

        $contragentClassName = cls::getClassName($dRec->contragentClassId);

        $res->contragent = $contragentClassName::getTitleById($dRec->contragentId);

        $pRec = cat_Products::fetch($dRec->productId);
        $res->code = cat_Products::getVerbal($pRec, 'code');

        if (isset($dRec->measure)) {
            $res->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
        }

        $res->quantity = $Double->toVerbal($dRec->requestQuantity - $dRec->shipedQuantity);
    }
}
