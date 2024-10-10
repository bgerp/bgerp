<?php


/**
 * Мениджър на отчети Рефери на колички в е-магазина
 *
 *
 * @category  bgerp
 * @package   eshop
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     e-Shop » Рефери на колички в е-магазина
 */
class eshop_reports_ReferersOfCarts extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, debug';


    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields;


    /**
     * Кои полета от таблицата в справката да се сумират в обобщаващия ред
     *
     * @var int
     */
    protected $summaryListFields;


    /**
     * Как да се казва обобщаващия ред. За да се покаже трябва да е зададено $summaryListFields
     *
     * @var int
     */
    protected $summaryRowCaption = 'ОБЩО';


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

        //Период
        $fieldset->FLD('from', 'date', 'caption=От,after=title,single=none,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,mandatory');

    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
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

        $cartQuery = eshop_CartDetails::getQuery();
        $cartQuery->EXT('activatedOn', 'eshop_Carts', 'externalName=activatedOn,externalKey=cartId');
        $cartQuery->EXT('createdOn', 'eshop_Carts', 'externalName=createdOn,externalKey=cartId');
        $cartQuery->EXT('state', 'eshop_Carts', 'externalName=state,externalKey=cartId');
        $cartQuery->EXT('totalNoVat', 'eshop_Carts', 'externalName=totalNoVat,externalKey=cartId');
        $cartQuery->EXT('ip', 'eshop_Carts', 'externalName=ip,externalKey=cartId');
        $cartQuery->EXT('brid', 'eshop_Carts', 'externalName=brid,externalKey=cartId');

        $cartQuery->where("#state = 'active'");
        $cartQuery->where(array("#activatedOn >= '[#1#]' AND #activatedOn <= '[#2#]'", $rec->from . ' 00:00:00', $rec->to . ' 23:59:59'));

        while ($cartRec = $cartQuery->fetch()) {

            $id = $cartRec->cartId;
            $date = date('d-m-Y', strtotime($cartRec->activatedOn));
            $time = date(' H:i:s', strtotime($cartRec->activatedOn));

            //Проферка за рефер
            $chekTyme = dt::addSecs(-1 * 60 * 60 * 2, $cartRec->createdOn);
            // $chekTyme =dt::addSecs(-1 * 60 * 60 * 2 , '2013-08-13 16:09:25');

            if ($vRec = vislog_Referer::fetch("#ip = '{$cartRec->ip}' AND #createdOn >= '{$chekTyme}' AND #createdOn <= '{$cartRec->createdOn}'")) {

                $referer = $vRec->id;
            } else {
                $referer = '';
            }

            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object)array(

                    'cartId' => $cartRec->cartId,
                    'dt' => $cartRec->activatedOn,
                    'date' => $date,
                    'time' => $time,
                    'products' => 1,
                    'totalNoVat' => $cartRec->totalNoVat,
                    'ip' => $cartRec->ip,
                    'brid' => $cartRec->brid,
                    'referer' => $referer,

                );
            } else {
                $obj = &$recs[$id];
                $obj->products++;
            }
        }

        return $recs;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec - записа
     * @param bool $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');

        if ($export === false) {

            $fld->FLD('dt', 'datetime', 'caption=Дата/час,tdClass=leftAlign');
            $fld->FLD('products', 'int', 'caption=Брой артикули,tdClass=leftAlign');
            $fld->FLD('totalNoVat', 'double(decimals=2)', 'caption=Сума,smartCenter');
            $fld->FLD('ip', 'ip(15,showNames)', 'caption=Ip,smartCenter');
            $fld->FLD('brid', 'varchar(8)', 'caption=Браузър,smartCenter');
            $fld->FLD('referer', 'varchar(8)', 'caption=Рефер,smartCenter');

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
        $Double = core_Type::getByName('double(decimals=2)');
        $Date = cls::get('type_Date');

        $row = new stdClass();

        $row->dt = dt::mysql2verbal($dRec->dt);

        $url = eshop_Carts::getSingleUrlArray_($dRec->cartId);


        $row->products = ht::createLinkRef($dRec->products,$url);

        $row->totalNoVat = $Double->toVerbal($dRec->totalNoVat);

        $row->ip = type_Ip::decorateIp($dRec->ip, $dRec->dt, true, true);
        $row->brid = log_Browsers::getLink($dRec->brid);

        // row->userId .= type_Ip::decorateIp($rec->ip, $rec->createdOn)."</br>" .log_Browsers::getLink($rec->brid);

        if (is_numeric($dRec->referer)) {
            $row->referer = vislog_Referer::getVerbal_($dRec->referer, 'referer');
        }else{
            $row->referer ='';
        }
        return $row;
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {


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

    }

}
