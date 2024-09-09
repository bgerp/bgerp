<?php

/**
 * Мениджър на отчети за най-добре продаващи се продукти
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     POS  » Най-добре продаващи се артикули
 */
class pos_reports_BestSellingItems extends frame2_driver_TableData
{
    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields = 'marketabilityQ,marketabilityA';

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
    public $groupByField;

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

        $fieldset->FLD('from', 'date', 'caption=От,refreshForm,after=title,single=none');
        $fieldset->FLD('to', 'date', 'caption=До,refreshForm,after=from,single=none');

        $fieldset->FLD('pos', 'keylist(mvc=pos_Points,select=name,allowEmpty)', 'caption=ПОС терминали->ПОС,placeholder=Всички,after=to,single=none');

        $fieldset->FLD('begin', 'hour', 'caption=Времена на засичане->Начало,after=pos,mandatory,single=none');
        $fieldset->FLD('end', 'hour', 'caption=Времена на засичане->Край,after=begin,single=none,mandatory');
        $fieldset->FNC('days', 'int', 'caption=Брой дни със продажби,after=end,single=none,input=none,mandatory');

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

        $salesFrequency = $itemSales = $date = $days = array();
        while ($receiptDetailRec = $receiptQuery->fetch()) {

            $receiptRec = pos_Receipts::fetch($receiptDetailRec->receiptId);

            //Филтър по POS
            if (isset($rec->pos) && (!in_array($receiptRec->pointId, keylist::toArray($rec->pos)))) continue;

            //Време на продажбата
            $sellDT = DateTime::createFromFormat("Y-m-d H:i:s", "$receiptRec->waitingOn");
            $sellTime = $sellDT->format('H:i');
            $sellDate = $sellDT->format('Y-m-d');

            $id = $receiptDetailRec->productId;

            //Масив с артикули, които ги има  в бележките издадени в избрания часови период
            if (($sellTime > $rec->begin) && ($sellTime < $rec->end)) {

                //Честота на продажбите(брой дни в които е продаван артикул)
                if (!array_key_exists($receiptDetailRec->productId . '|' . $sellDate, $date)) {
                    $date[$receiptDetailRec->productId . '|' . $sellDate] = $sellDate;
                    $salesFrequency[$receiptDetailRec->productId]++;
                }

                //Общ брой дни с продажби
                if (!array_key_exists($sellDate, $days)) {
                    $days[$sellDate] = $sellDate;
                }
                if (!array_key_exists($id, $itemSales)) {
                    $itemSales[$id] = (object)array(
                        'date' => $sellDate,
                        'time' => $sellTime,
                        'productId' => $receiptDetailRec->productId,
                        'code' => cat_Products::fetch($receiptDetailRec->productId)->code,
                        'quantity' => $receiptDetailRec->quantity,
                        'amount' => $receiptDetailRec->price * $receiptDetailRec->quantity,
                    );
                } else {

                    $obj = &$itemSales[$id];
                    $obj->quantity += $receiptDetailRec->quantity;
                    $obj->amount += $receiptDetailRec->price * $receiptDetailRec->quantity;

                }
            }
        }

        $rec->days = countR($days);
        foreach ($itemSales as $key => $val) {

            $recs[$key] = (object)array(
                'productId' => $val->productId,
                'quantity' => $val->quantity,
                'amount' => $val->amount,
                'salesFrequency' => $salesFrequency[$val->productId],
                'marketabilityQ' => $val->quantity / $salesFrequency[$val->productId],
                'marketabilityA' => $val->amount / $salesFrequency[$val->productId],
            );

        }

        if (countR($recs)) {
            arr::sortObjects($recs, 'marketabilityA', 'desc');
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

            $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
            $fld->FLD('quantity', 'double(decimals=2)', 'caption=Продажби->К-во');
            $fld->FLD('amount', 'double(decimals=2)', 'caption=Продажби->Стойност');
            $fld->FLD('marketabilityQ', 'double(decimals=2)', 'caption=Продаваемост->Ср. К-во');
            $fld->FLD('marketabilityA', 'double(decimals=2)', 'caption=Продаваемост->Ср. Стойност');
            // $fld->FLD('salesFrequency', 'varchar', 'caption=Продаваемост->Честота,tdClass=centered');

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

        $row = new stdClass();

        $row->productId = cat_Products::getHyperlink($dRec->productId, true);
        $row->quantity = $Double->toVerbal($dRec->quantity);
        $row->amount = $Double->toVerbal($dRec->amount);
        $row->marketabilityA = $Double->toVerbal($dRec->marketabilityA);
        $row->marketabilityQ = $Double->toVerbal($dRec->marketabilityQ);
        $row->salesFrequency = $dRec->salesFrequency . '/' . $rec->days;

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
        $Hour = cls::get('type_Hour');
        $Int = cls::get('type_Int');
        $Enum = cls::get('type_Enum', array('options' => array('date' => 'Дата', 'productId' => 'Артикул')));


        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN from--><div>|От|*: [#from#]</div><!--ET_END from-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                        <!--ET_BEGIN begin--><div>|Начало|*: [#begin#]</div><!--ET_END begin-->
                                        <!--ET_BEGIN end--><div>|Край|*: [#end#]</div><!--ET_END end-->
                                        <!--ET_BEGIN days--><div>|Дни с продажби|*: [#days#]</div><!--ET_END days-->     
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));


        if (isset($data->rec->from)) {
            $fieldTpl->append($Date->toVerbal($data->rec->from), 'from');
        }

        if (isset($data->rec->to)) {
            $fieldTpl->append($Date->toVerbal($data->rec->to), 'to');
        }


        if (isset($data->rec->mark)) {
            $fieldTpl->append($Hour->toVerbal($data->rec->mark), 'mark');
        }

        if (isset($data->rec->begin)) {
            $fieldTpl->append($Hour->toVerbal($data->rec->begin), 'begin');
        }

        if (isset($data->rec->end)) {
            $fieldTpl->append($Hour->toVerbal($data->rec->end), 'end');
        }

        if (isset($data->rec->days)) {
            $fieldTpl->append($Int->toVerbal($data->rec->days), 'days');
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

