<?php


/**
 * Мениджър на отчети за бързоизчерпващи се пеодукти
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     POS  » Бързо изчерпващи се артикули
 */
class pos_reports_QuicklyOutOfStockProducts extends frame2_driver_TableData
{
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
    protected $summaryRowCaption = 'ОБЩО ЗА ПЕРИОДА';


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,manager,debug';

    /**
     * По кое поле да се групира
     */
    public $groupByField ;

    /**
     * По-кое поле да се групират данните след групиране, вътре в групата
     */
    protected $subGroupFieldOrder;


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
    protected $hashField;


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

        $fieldset->FLD('from', 'date', 'caption=От,refreshForm,after=title,single=none');
        $fieldset->FLD('to', 'date', 'caption=До,refreshForm,after=from,single=none');

        $fieldset->FLD('pos', 'keylist(mvc=pos_Points,select=name,allowEmpty)', 'caption=ПОС терминали->ПОС,placeholder=Всички,after=to,single=none');

        $fieldset->FLD('begin', 'hour', 'caption=Времена на засичане->Начало,after=pos');
        $fieldset->FLD('mark', 'hour', 'caption=Времена на засичане->Граница,after=begin');
        $fieldset->FLD('end', 'hour', 'caption=Времена на засичане->Край,after=mark');


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

        $receiptQuery = pos_ReceiptDetails::getQuery();
        $receiptQuery->EXT('waitingOn', 'pos_Receipts', 'externalName=waitingOn,externalKey=receiptId');
        $receiptQuery->EXT('state', 'pos_Receipts', 'externalName=state,externalKey=receiptId');
        $receiptQuery->where("#waitingOn IS NOT NULL");
        $receiptQuery->where("#productId IS NOT NULL");

        //Филтър по състояние
        $receiptQuery->in('state', array('waiting', 'closed'));

        if ($rec->to < substr(($rec->to), 0, 10) . ' 00:00:01') {
            $end = substr(($rec->to), 0, 10) . ' 23:59:59';
        } else {
            $end = $rec->to;
        }

        $receiptQuery->where(array("#waitingOn>= '[#1#]' AND #waitingOn <= '[#2#]'", $rec->from, $end));
       // bp($receiptQuery->fetchAll());
        $prodInbeginArr = $prodInEndArr = array();
        $a = 0;
        while ($receiptDetailRec = $receiptQuery->fetch()) {

            $receiptRec = pos_Receipts::fetch($receiptDetailRec->receiptId);

            //Филтър по POS
            if (isset($rec->pos) && (!in_array($receiptRec->pointId, keylist::toArray($rec->pos)))) continue;

            //Време на продажбата
            $sellDT = DateTime::createFromFormat("Y-m-d H:i:s", "$receiptRec->waitingOn");
            $sellTime = $sellDT->format('H:i');
            $sellDate = $sellDT->format('Y-m-d');

           // $id = $receiptDetailRec->productId.'|'.$sellDate;
            $id = $receiptDetailRec->productId;

            //Масив с артикули, които ги има  в бележките издадени между началото и границата
            if (($sellTime > $rec->begin) && ($sellTime < $rec->mark)) {

                if (!array_key_exists($id, $prodInbeginArr)) {
                    $prodInbeginArr[$id] = (object)array(

                        'date' => $sellDate,
                        'productId' => $receiptDetailRec->productId,
                        'quantity' => $receiptDetailRec->quantity,
                        'amount' => $receiptDetailRec->price * $receiptDetailRec->quantity,
                    );
                } else {

                    $obj = &$prodInbeginArr[$id];
                    $obj->quantity += $receiptDetailRec->quantity;
                    $obj->amount += $receiptDetailRec->price * $receiptDetailRec->quantity;

                }
            }

            //Масив с артикули, които ги има  в бележките издадени между границата и края
            if (($sellTime > $rec->mark) && ($sellTime < $rec->end)) {

                if (!array_key_exists($id, $prodInEndArr)) {
                    $prodInEndArr[$id] = (object)array(

                        'date' => $sellDate,
                        'productId' => $receiptDetailRec->productId,
                        'quantity' => $receiptDetailRec->quantity,
                        'amount' => $receiptDetailRec->price * $receiptDetailRec->quantity,
                    );
                } else {

                    $obj = &$prodInEndArr[$id];
                    $obj->quantity += $receiptDetailRec->quantity;
                    $obj->amount += $receiptDetailRec->price * $receiptDetailRec->quantity;

                }
            }
        }

        $prodsAfterMarktArr = arr::extractValuesFromArray($prodInEndArr, 'productId');

        foreach ($prodInbeginArr as $key => $val) {

            if (!in_array($key, $prodsAfterMarktArr)) {

                if (!array_key_exists($key, $recs)) {
                    $recs[$key] = (object)array(
                        'date' => $val->date,
                        'productId' => $val->productId,
                        'quantity' => $val->quantity,
                        'amount' => $val->amount,
                    );
                }
            }
        }

        if (countR($recs)) {
            arr::sortObjects($recs, 'quantity', 'desc');
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

           // $fld->FLD('date',  'date', 'caption=Дата');
            $fld->FLD('productId',  'key(mvc=cat_Products,select=name)', 'caption=Артикул');
            $fld->FLD('quantity', 'double(decimals=2)', 'caption=Количество');
            $fld->FLD('amount', 'double(decimals=2)', 'caption=Стойност');

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
        $Datetime = cls::get('type_Datetime');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;

        $row = new stdClass();

        $row->date = $dRec->date;

        $row->productId = cat_Products::getHyperlink($dRec->productId, true);
        $row->quantity = $Double->toVerbal($dRec->quantity) ;
        $row->amount = $Double->toVerbal($dRec->amount) ;

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

        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;

        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN from--><div>|От|*: [#from#]</div><!--ET_END from-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                        <!--ET_BEGIN crmGroup--><div>|Група|*: [#crmGroup#]</div><!--ET_END crmGroup-->
                                        <!--ET_BEGIN catGroup--><div>|Група артикули|*: [#catGroup#]</div><!--ET_END catGroup-->
                                        <!--ET_BEGIN allCompanyDiscount--><div>|Общо авт. отстъпки|*: [#allCompanyDiscount#] лв.</div><!--ET_END allCompanyDiscount-->     
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));


        if (isset($data->rec->from)) {
            $fieldTpl->append($Date->toVerbal($data->rec->from), 'from');
        }

        if (isset($data->rec->to)) {
            $fieldTpl->append($Date->toVerbal($data->rec->to), 'to');
        }


        if (isset($data->rec->catGroup)) {
            $fieldTpl->append(cat_Groups::getTitleById($data->rec->catGroup), 'catGroup');
        }

        if (isset($data->rec->crmGroup)) {
            $fieldTpl->append(crm_Groups::getTitleById($data->rec->crmGroup), 'crmGroup');
        }

        if (isset($data->rec->allCompanyDiscount)) {

            $fieldTpl->append($Double->toVerbal(array_sum($data->rec->allCompanyDiscount)), 'allCompanyDiscount');
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

