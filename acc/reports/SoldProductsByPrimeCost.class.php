<?php


/**
 * Мениджър на отчети
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Счетоводство » Продадени артикули по себестойност
 */
class acc_reports_SoldProductsByPrimeCost extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,admin,debug';


    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;

    /**
     * Кои полета от таблицата в справката да се сумират в обобщаващия ред
     *
     * @var int
     */
    protected $summaryListFields = 'amount';


    /**
     * Как да се казва обобщаващия ред. За да се покаже трябва да е зададено $summaryListFields
     *
     * @var int
     */
    protected $summaryRowCaption = 'ОБЩО';


    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;


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
        //Период
        $fieldset->FLD('period', 'key(mvc=acc_Periods,title=title)', 'caption = Период,after=title,single=none');

        $fieldset->FLD('groups', 'keylist(mvc=cat_Groups,select=name)', 'caption=Артикули->Групи артикули,after=period,placeholder=Всички,silent,single=none');

        $fieldset->FLD('stores', 'keylist(mvc=store_Stores,select=name)', 'caption=Склад,after=groups,placeholder=Всички,silent,single=none');

    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver $Driver
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

        $debitAccId = acc_Accounts::fetch("#num = 701")->id;
        $creditAccId = acc_Accounts::fetch("#num = 321")->id;

        $from = acc_Periods::fetch($rec->period)->start;
        $to = acc_Periods::fetch($rec->period)->end;

        $sallDetQuery = acc_JournalDetails::getQuery();

        $sallDetQuery->EXT('state', 'acc_Journal', 'externalName=state,externalKey=journalId');
        $sallDetQuery->EXT('createdOnJournal', 'acc_Journal', 'externalName=createdOn,externalKey=journalId');

        $sallDetQuery->where(array("#createdOnJournal >= '[#1#]' AND #createdOnJournal <= '[#2#]'", $from . ' 00:00:00', $to . ' 23:59:59'));

        $sallDetQuery->where("#debitAccId = $debitAccId AND #creditAccId = $creditAccId");

        while ($saleDetRec = $sallDetQuery->fetch()) {

            //Филтър по склад
            if($rec->stores) {
                $storeRec = store_Stores::fetch(acc_Items::fetch($saleDetRec->creditItem1)->objectId);
               if(!keylist::isIn($storeRec->id,$rec->stores))continue;
            }

            //Артикул
            $pRec = cat_Products::fetch(acc_Items::fetch($saleDetRec->creditItem2)->objectId);

            //Филтър по групи артикули
            if($rec->groups) {
                if(!keylist::isIn(keylist::toArray($pRec->groups),$rec->groups))continue;
            }

            $artCode = $pRec->code;

            $quantity = $saleDetRec->creditQuantity;

            $amount = $saleDetRec->amount;

            $id = $pRec->id;

            // Запис в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object)array(

                    'code' => $artCode,                                   //Код на артикула

                    'productId' => $pRec->id,                             //Id на артикула

                    'measureId' => $pRec->measureId,                        //Мярка

                    'store' => $storeRec->id,                             //количество

                    'quantity' => $quantity,                              //количество
                    'amount' => $amount,                                  //стойност на продажбите за артикула

                );
            } else {
                $obj = &$recs[$id];

                $obj->quantity += $quantity;
                $obj->primeCost += $amount;

            }

        }


        //  bp($recs);


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
            $fld->FLD('code', 'varchar', 'caption=Код');
            $fld->FLD('productId', 'varchar', 'caption=Артикул');
            $fld->FLD('measureId', 'varchar', 'smartCenter,caption=Мярка');
            $fld->FLD('quantity', 'varchar', 'smartCenter,caption=Продажби->Количество');
            $fld->FLD('amount', 'varchar', 'smartCenter,caption=Продажби->Стойност');
        }else{
            $fld->FLD('code', 'varchar', 'caption=Код');
            $fld->FLD('productId', 'varchar', 'caption=Артикул');
            $fld->FLD('measureId', 'varchar', 'smartCenter,caption=Мярка');
            $fld->FLD('quantity', 'varchar', 'smartCenter,caption=Продажби->Количество');
            $fld->FLD('amount', 'varchar', 'smartCenter,caption=Продажби->Стойност');
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
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;


        $row = new stdClass();

        if (isset($dRec->code)) {
            $row->code = $dRec->code;
        }
        if (isset($dRec->productId)) {
            $row->productId = cat_Products::getLinkToSingle_($dRec->productId, 'name');
        }

        if (isset($dRec->measureId)) {
            $row->measureId = cat_UoM::fetchField($dRec->measureId, 'shortName');
        }

        $row->quantity = $Double->toVerbal($dRec->quantity);
        $row->amount = $Double->toVerbal($dRec->amount);


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
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;

        $res->quantity = $Double->toVerbal($dRec->quantity);
        $res->amount = $Double->toVerbal($dRec->amount);
        $res->measureId = cat_UoM::fetchField($dRec->measureId, 'shortName');
    }


}
