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
 * @title     Склад » Частично експедирани нестандартни артикули
 */
class store_reports_NonPublicItems extends frame2_driver_TableData
{
    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields ;



    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,manager,store,planning,purchase';


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
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {

        $fieldset->FLD('users', 'userList(rolesForAll=sales|ceo,allowEmpty,roles=ceo|sales)', 'caption=Потрбители->Търговец,after=title,single=none');
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

        //Определяне на активните складове
        $storeQuery = store_Stores::getQuery();
        $storeQuery->where("#state = 'active'");
        $activeStateArr = arr::extractValuesFromArray($storeQuery->fetchAll(),'id');


        $shQuery = store_ShipmentOrders::getQuery();
        $shQuery->in('state', array('pending', 'draft'));

        //Филтър по потребители
        $shQuery->where("(#createdBy NOT IN (' . implode('|', $rec->users) . ')) OR (#modifiedBy NOT IN (' . implode('|', $rec->users) . '))");

        $shipmentArr = arr::extractValuesFromArray($shQuery->fetchAll(),'id');

        $shDetQuery = store_ShipmentOrderDetails::getQuery();

        $shDetQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');

        $shDetQuery->EXT('storeId', 'store_ShipmentOrders', 'externalName=storeId,externalKey=shipmentId');

        $shDetQuery->in('shipmentId',$shipmentArr);

        $shDetQuery->where("#isPublic = 'no'");

        while ($shDetRec = $shDetQuery->fetch()) {

            //Количество от артикула в склада от ЕН
            if(!in_array($shDetRec->storeId,$activeStateArr)) continue;
            $storeQuantity = store_Products::getQuantities($shDetRec->productId,$shDetRec->storeId);
            $allStoriesQuantity = store_Products::getQuantities($shDetRec->productId);

            //Експедирано количество общо от всички опаковки
            $shipmentQuantity = $shDetRec->quantityInPack * $shDetRec->packQuantity;

            if (! array_key_exists($shDetRec->productId, $recs)) {
                $recs[$shDetRec->productId] =

                    (object) array(

                        'shipmentId' => $shDetRec->shipmentId,
                        'productId' => $shDetRec->productId,
                        'storeId' => $shDetRec->storeId,
                        'shipmentQuantity' => $shipmentQuantity,
                        'storeQuantity' => $storeQuantity->quantity,
                        'allStoriesQuantity' => $allStoriesQuantity->quantity,
                        'measure' => cat_Products::fetchField($shDetRec->productId, 'measureId'),
                    );
            } else {
                $obj = &$recs[$shDetRec->productId];

                $obj->quantity += $shDetRec->quantity;
            }

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
            $fld->FLD('tag', 'varchar', 'caption=Таг');
        }else{
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

        $row->productId = cat_Products::getHyperlink($dRec->productId, 'name');

        $row->shipmentQuantity = $Double->toVerbal($dRec->shipmentQuantity);
        $row->storeQuantity = $Double->toVerbal($dRec->storeQuantity);
        $row->allStoriesQuantity = $Double->toVerbal($dRec->allStoriesQuantity);

        if($dRec->shipmentQuantity < $dRec->storeQuantity){
            $row->shipmentQuantity = "<span class= 'red'>".$Double->toVerbal($dRec->shipmentQuantity);
            $row->storeQuantity = "<span class= 'red'>" . '<b>' .$Double->toVerbal($dRec->storeQuantity). '</b>';
        }
        if(($dRec->shipmentQuantity >= $dRec->storeQuantity) && ($dRec->allStoriesQuantity > $dRec->shipmentQuantity)){
            $row->shipmentQuantity = "<span class= 'red'>".$Double->toVerbal($dRec->shipmentQuantity);
            $row->allStoriesQuantity = "<span class= 'red'>" . '<b>' .$Double->toVerbal($dRec->allStoriesQuantity). '</b>';

        }



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
}
