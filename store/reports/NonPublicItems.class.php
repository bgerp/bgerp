<?php


/**
 * Мениджър на отчети за частично експедирани нестандартни артикули
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Склад » Нестандартни артикули - количества за експедиране
 */
class store_reports_NonPublicItems extends frame2_driver_TableData
{
    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields;


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,manager,store,sales';


    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;

    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField = 'productId';


    /**
     * Дефолтен текст за нотификация
     */
    protected static $defaultNotificationText = 'Непълно експедиране на нестандартен Артикул';


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
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {

        $fieldset->FLD('users', 'userList(rolesForAll=sales|ceo,allowEmpty,roles=ceo|sales|store)', 'caption=Експедиционни нареждания създадени от->Потребители,mandatory,after=title,single=none');

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

        $rec->priority = 'normal';

        //Определяне на активните складове
        $storeQuery = store_Stores::getQuery();
        $storeQuery->where("#state = 'active'");
        $activeStateArr = arr::extractValuesFromArray($storeQuery->fetchAll(), 'id');


        $shQuery = store_ShipmentOrders::getQuery();
        $shQuery->in('state', array('pending', 'draft'));


        $arr = keylist::toArray($rec->users);

        //Филтър по потребители
        $shQuery->where('#createdBy IN (' . implode(',', $arr) . ')');

        if (!$shQuery->count()) {
            return $recs;
        }

        $shipmentArr = arr::extractValuesFromArray($shQuery->fetchAll(), 'id');

        $shDetQuery = store_ShipmentOrderDetails::getQuery();

        $shDetQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');

        $shDetQuery->EXT('storeId', 'store_ShipmentOrders', 'externalName=storeId,externalKey=shipmentId');

        $shDetQuery->in('shipmentId', $shipmentArr);

        $shDetQuery->where("#isPublic = 'no'");

        while ($shDetRec = $shDetQuery->fetch()) {

            //Количество от артикула в склада от ЕН
            if (!in_array($shDetRec->storeId, $activeStateArr)) continue;
            $storeQuantity = store_Products::getQuantities($shDetRec->productId, $shDetRec->storeId);
            $allStoriesQuantity = store_Products::getQuantities($shDetRec->productId);

            //Експедирано количество общо от всички опаковки
            $shipmentQuantity = $shDetRec->quantityInPack * $shDetRec->packQuantity;

            if (!$rec->id) {
                $stopNot = '';
            } else {
                $stopNot = $rec->data->recs[$shDetRec->productId]->stopNot;
            }

            if (!array_key_exists($shDetRec->productId, $recs)) {
                $recs[$shDetRec->productId] =

                    (object)array(

                        'shipmentId' => $shDetRec->shipmentId,
                        'productId' => $shDetRec->productId,
                        'storeId' => $shDetRec->storeId,
                        'shipmentQuantity' => $shipmentQuantity,
                        'storeQuantity' => $storeQuantity->quantity,
                        'allStoriesQuantity' => $allStoriesQuantity->quantity,
                        'measure' => cat_Products::fetchField($shDetRec->productId, 'measureId'),
                        'stopNot' => $stopNot,
                    );
            } else {
                $obj = &$recs[$shDetRec->productId];

                $obj->quantity += $shDetRec->quantity;
            }

        }

        // Проверява условията за изпращане нотификация и ако са изпълнени изпраща
        if (countR($recs)) {
            self::sendNotificationOnThisReport($rec);
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

            $fld->FLD('shipmentId', 'key(mvc=store_ShipmentOrders,select=id)', 'caption=ЕН');
            $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
            $fld->FLD('shipmentQuantity', 'double', 'caption=Количество -> по ЕН');
            $fld->FLD('storeQuantity', 'double', 'caption=Количество -> в склада');
            $fld->FLD('allStoriesQuantity', 'double', 'caption=Количество -> общо');
            $fld->FLD('stopNot', 'text', 'caption=Stop');
        } else {
            $fld->FLD('shipmentId', 'key(mvc=store_ShipmentOrders,select=id)', 'caption=ЕН');
            $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
            $fld->FLD('shipmentQuantity', 'double', 'caption=Количество-> по ЕН');
            $fld->FLD('storeQuantity', 'double', 'caption=Количество -> в склада');
            $fld->FLD('allStoriesQuantity', 'double', 'caption=Количество -> общо');


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

        $shipmentHandle = '#' . store_ShipmentOrders::getHandle($dRec->shipmentId);
        $row->shipmentId = ht::createLink($shipmentHandle, array('store_ShipmentOrders', 'Single', $dRec->shipmentId), null);
        $oldShipment = $dRec->shipmentId;

        $row->productId = cat_Products::getHyperlink($dRec->productId, 'name');

        $row->shipmentQuantity = $Double->toVerbal($dRec->shipmentQuantity);
        $row->storeQuantity = $Double->toVerbal($dRec->storeQuantity);
        $row->allStoriesQuantity = $Double->toVerbal($dRec->allStoriesQuantity);


        if ($dRec->shipmentQuantity < $dRec->storeQuantity) {
            $row->shipmentQuantity = "<span class= 'red'>" . $Double->toVerbal($dRec->shipmentQuantity);
            $row->storeQuantity = "<span class= 'red'>" . '<b>' . $Double->toVerbal($dRec->storeQuantity) . '</b>';
        }
        if (($dRec->shipmentQuantity >= $dRec->storeQuantity) && ($dRec->allStoriesQuantity > $dRec->shipmentQuantity)) {
            $row->shipmentQuantity = "<span class= 'red'>" . $Double->toVerbal($dRec->shipmentQuantity);
            $row->allStoriesQuantity = "<span class= 'red'>" . '<b>' . $Double->toVerbal($dRec->allStoriesQuantity) . '</b>';

        }

        if ($dRec->stopNot == 'stop') {
            $icon = "ef_icon=img/16/checkbox_yes.png";
        }
        if ($dRec->stopNot == '') {
            $icon = "ef_icon=img/16/checkbox_no.png";
        }
        $row->stopNot .= ht::createLink('', array('store_reports_NonPublicItems', 'SetStop', 'productId' => $dRec->productId, 'recId' => $rec->id, 'ret_url' => true), null, $icon);

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
                                        <!--ET_BEGIN users--><div>|Потребители|*: [#users#]</div><!--ET_END users-->
                                       
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));

        if (isset($data->rec->users)) {

            $fieldTpl->append(core_Type::getByName('userList')->toVerbal($data->rec->users), 'users');

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


    }


    /**
     * Да се изпраща ли нова нотификация на споделените потребители, при опресняване на отчета
     *
     * @param stdClass $rec
     *
     * @return bool $res
     */
    public function canSendNotificationOnRefresh($rec)
    {
        return false;
    }

    /**
     * Изпращане на нотификации на споделените потребители
     *
     * @param stdClass $rec
     *
     * @return void
     */
    public function sendNotificationOnThisReport($rec)
    {
        $art = null;
        $cond = false;
        $me = cls::get(get_called_class());

        if (!$rec->data->recs) return;

        foreach ($rec->data->recs as $r) {

            $selRec = true;
            if ($r->stopNot == '') {
                $selRec = false;
            }

            if (($r->shipmentQuantity < $r->storeQuantity) && ($selRec === false)) {

                $rec->priority = 'alert';

                $cond = true;
            }

            if (($r->shipmentQuantity >= $r->storeQuantity) &&
                ($r->shipmentQuantity < $r->allStoriesQuantity) &&
                ($selRec === false)) {

                $cond = true;

            }
        }

        if ($cond === false) return;

        // Ако няма избрани потребители за нотифициране, не се прави нищо
        $userArr = keylist::toArray($rec->sharedUsers);

        if (!in_array($rec->createdBy, $userArr)) {
            array_push($userArr, $rec->createdBy);
        }

        if (!countR($userArr)) {
            $userArr = array($rec->createdBy => $rec->createdBy, $rec->modifiedBy => $rec->modifiedBy);
        }

        $text = self::$defaultNotificationText . $art;
        $msg = new core_ET($text);

        // Заместване на параметрите в текста на нотификацията
        Mode::push('text', 'plain');
        $params = store_reports_NonPublicItems::getNotificationParams($rec);
        Mode::pop('text');
        if (is_array($params)) {
            $msg->placeArray($params);
        }

        $url = array('frame2_Reports', 'single', $rec->id);
        $msg = $msg->getContent();

        // На всеки от абонираните потребители се изпраща нотификацията за промяна на документа
        foreach ($userArr as $userId) {
            bgerp_Notifications::add($msg, $url, $userId, $rec->priority);
        }
    }

    /**
     * Връща следващите три дати, когато да се актуализира справката
     *
     * @param stdClass $rec
     *                      - запис
     *
     * @return array|FALSE - масив с три дати или FALSE ако не може да се обновява
     */
    public function getNextRefreshDates($rec)
    {
        $date = new DateTime(dt::now());
        $date->add(new DateInterval('P0DT1H0M0S'));
        $d1 = $date->format('Y-m-d H:i:s');
        $date->add(new DateInterval('P0DT1H0M0S'));
        $d2 = $date->format('Y-m-d H:i:s');
        $date->add(new DateInterval('P0DT1H0M0S'));
        $d3 = $date->format('Y-m-d H:i:s');

        return array(
            $d1,
            $d2,
            $d3
        );
    }

    /**
     * Промяна на стойностите min и max
     *
     */
    public function act_SetStop()
    {
        expect($recId = Request::get('recId', 'int'));
        expect($productId = Request::get('productId', 'int'));

        $rec = frame2_Reports::fetch($recId);

        $stopNot = $rec->data->recs[$productId]->stopNot;

        if ($stopNot == '') {

            $rec->data->recs[$productId]->stopNot = 'stop';

        } elseif ($stopNot == 'stop') {

            $rec->data->recs[$productId]->stopNot = '';
        }

        cls::get('frame2_Reports')->save_($rec);

        frame2_Reports::refresh($rec);

        return new Redirect(getRetUrl());
    }
}
