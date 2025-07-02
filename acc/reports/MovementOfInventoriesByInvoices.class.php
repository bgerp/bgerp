<?php


/**
 * Мениджър на отчети за стоки на склад
 *
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Счетоводство » Движение на материални ценности по фактури
 */
class acc_reports_MovementOfInventoriesByInvoices extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,debug, acc';


    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField;


    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields = 'endQuantity,endAmount';


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
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('products', 'fileman_FileType(bucket=reports)', 'caption=Файл с Артикули,placeholder=Избери,after=title,removeAndRefreshForm,silent,single=none,class=w100,input');

        // Период на справката
        $fieldset->FLD('from', 'date', 'caption=От,after=products,single=none,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,mandatory');

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

        $form->setDefault('from', '1970-01-01');
        $form->setDefault('to', dt::today() . '23:59:59');
        $form->setDefault('seeByGroups', 'no');
        $form->setDefault('orderBy', 'name');

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
        $recs = [];

        if (empty($rec->products)) return $recs;

        $fRec = fileman_Files::fetchByFh($rec->products);
        expect($fRec, 'Липсва файл за импортиране');

        $csv = csv_Lib::getCsvRowsFromFile(fileman::extractStr($fRec->fileHnd), ['skip' => '@']);

        foreach ((array)$csv['data'] as $row) {
            $code = trim($row[1] ?? '');
            $startQuantity = (float)str_replace(',', '.', $row[2] ?? 0);
            $startAmount = (float)str_replace(',', '.', $row[3] ?? 0);

            if (!$code) continue;

            $recs[$code] = (object)[
                'productId' => '',
                'code' => $code,
                'prodName' => '',
                'measureId' => '',
                'startQuantity' => $startQuantity,
                'startAmount' => $startAmount,
                'inQuantity' => 0.0,
                'inAmount' => 0.0,
                'outQuantity' => 0.0,
                'outAmount' => 0.0,
                'endQuantity' => 0.0,
                'endAmount' => 0.0,
            ];
        }

        $this->fillInQuantitiesAndAmountsFromPurchases($rec, $recs);
        $this->fillOutQuantitiesAndAmountsFromSales($rec, $recs);

        foreach ($recs as &$r) {
            // Преобразуване към числови стойности, ако не са
            $startQty = is_numeric($r->startQuantity) ? (float)$r->startQuantity : 0.0;
            $inQty = is_numeric($r->inQuantity) ? (float)$r->inQuantity : 0.0;
            $outQty = is_numeric($r->outQuantity) ? (float)$r->outQuantity : 0.0;

            $startAmt = is_numeric($r->startAmount) ? (float)$r->startAmount : 0.0;
            $inAmt = is_numeric($r->inAmount) ? (float)$r->inAmount : 0.0;
            $outAmt = is_numeric($r->outAmount) ? (float)$r->outAmount : 0.0;

            // Крайни стойности
            $r->endQuantity = $startQty + $inQty - $outQty;
            $r->endAmount = $startAmt + $inAmt - $outAmt;
        }

        if (countR($recs)) {
            arr::sortObjects($recs, 'prodName', 'asc');
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

            $fld->FLD('code', 'varchar', 'caption=Код,tdClass=centered nowrap');
            $fld->FLD('productId', 'varchar', 'caption=Артикул');
            $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');

            //Начални количества и стойност
            $fld->FLD('startQuantity', 'double(smartRound,decimals=2)', 'caption=Начало на периода->Количество');
            $fld->FLD('startAmount', 'double(smartRound,decimals=2)', 'caption=Начало на периода->Стойност');

            //Доставени количества и стойност
            $fld->FLD('inQuantity', 'double(smartRound,decimals=2)', 'caption=Доставено->Количество');
            $fld->FLD('inAmount', 'double(smartRound,decimals=2)', 'caption=Доставено->Стойност');

            //Продадено количества и стойност
            $fld->FLD('outQuantity', 'double(smartRound,decimals=2)', 'caption=Продадено->Количество');
            $fld->FLD('outAmount', 'double(smartRound,decimals=2)', 'caption=Продадено->Стойност');

            //Крайно количества и стойност
            $fld->FLD('endQuantity', 'double(smartRound,decimals=2)', 'caption=Крайно->Количество');
            $fld->FLD('endAmount', 'double(smartRound,decimals=2)', 'caption=Крайно->Стойност');
        } else {
            $fld->FLD('code', 'varchar', 'caption=Код,tdClass=centered nowrap');
            $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
            $fld->FLD('measure', 'varchar', 'caption=Мярка,tdClass=centered');
            $fld->FLD('startQuantity', 'double(decimals=2)', 'caption=Начало на периода->Количество');
            $fld->FLD('startAmount', 'double(decimals=2)', 'caption=Начало на периода->Стойност');
            $fld->FLD('inQuantity', 'double(decimals=2)', 'caption=Доставено->Количество');
            $fld->FLD('inAmount', 'double(decimals=2)', 'caption=Доставено->Стойност');
            $fld->FLD('outQuantity', 'double(decimals=2)', 'caption=Продадено->Количество');
            $fld->FLD('outAmount', 'double(decimals=2)', 'caption=Продадено->Стойност');
            $fld->FLD('endQuantity', 'double(decimals=2)', 'caption=Крайно->Количество');
            $fld->FLD('endAmount', 'double(decimals=2)', 'caption=Крайно->Стойност');
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
        $Enum = cls::get('type_Enum', array('options' => array('yes' => 'Включено')));

        $row = new stdClass();

        if (isset($dRec->code)) {
            $row->code = $dRec->code;
        }

        if (isset($dRec->productId)) {
            $row->productId = cat_Products::getLinkToSingle($dRec->productId, 'name');
        }

        $row->measure = cat_UoM::fetchField($dRec->measureId, 'shortName');


        $row->startQuantity = $Double->toVerbal($dRec->startQuantity);
        $row->startQuantity = ht::styleNumber($row->startQuantity, $dRec->startQuantity);

        $row->startAmount = $Double->toVerbal($dRec->startAmount);
        $row->startAmount = ht::styleNumber($row->startAmount, $dRec->startAmount);

        $row->inQuantity = $Double->toVerbal($dRec->inQuantity);
        $row->inQuantity = ht::styleNumber($row->inQuantity, $dRec->inQuantity);

        $row->inAmount = $Double->toVerbal($dRec->inAmount);
        $row->inAmount = ht::styleNumber($row->inAmount, $dRec->inAmount);

        $row->outQuantity = $Double->toVerbal($dRec->outQuantity);
        $row->outQuantity = ht::styleNumber($row->outQuantity, $dRec->outQuantity);

        $row->outAmount = $Double->toVerbal($dRec->outAmount);
        $row->outAmount = ht::styleNumber($row->outAmount, $dRec->outAmount);

        $row->endQuantity = $Double->toVerbal($dRec->endQuantity);
        $row->endQuantity = ht::styleNumber($row->endQuantity, $dRec->endQuantity);

        $row->endAmount = $Double->toVerbal($dRec->endAmount);
        $row->endAmount = ht::styleNumber($row->endAmount, $dRec->endAmount);


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
        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $Enum = cls::get('type_Enum', array('options' => array('included' => 'Включено', 'off' => 'Изключено', 'only' => 'Само')));
        $Set = cls::get('type_Set', array('options' => array('available' => 'Положителна', 'neg' => 'Отрицателна', 'zero' => 'Ненулева')));


        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN date--><div>|Към дата|*: [#date#]</div><!--ET_END date-->
                                        <!--ET_BEGIN storeId--><div>|Склад|*: [#storeId#]</div><!--ET_END storeId-->
                                        <!--ET_BEGIN group--><div>|Групи|*: [#group#]</div><!--ET_END group-->
                                        <!--ET_BEGIN products--><div>|Артикули|*: [#products#]</div><!--ET_END products-->
                                        <!--ET_BEGIN availability--><div>|Наличност|*: [#availability#]</div><!--ET_END availability-->
                                        <!--ET_BEGIN totalProducts--><div>|Брой артикули|*: [#totalProducts#]</div><!--ET_END totalProducts-->
                                        <!--ET_BEGIN workingPdogresOn--><div>|Незавършено производство|*: [#workingPdogresOn#]</div><!--ET_END workingPdogresOn-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));

        $date = (is_null($data->rec->date)) ? dt::today() : $data->rec->date;

        $fieldTpl->append('<b>' . $Date->toVerbal($date) . '</b>', 'date');


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

        $res->measure = cat_UoM::fetch($dRec->measureId)->shortName;
    }

    /**
     * Допълва inQuantity и inAmount в $recs чрез входящите фактури от purchase_Invoices
     *
     * @param stdClass $rec - записът от справката, вкл. от и до дати
     * @param array &$recs - масив от записи, ключ: код на артикула
     */
    protected function fillInQuantitiesAndAmountsFromPurchases($rec, array &$recs)
    {
        $from = $rec->from . ' 00:00:00';
        $to = $rec->to . ' 23:59:59';

        $dQuery = purchase_InvoiceDetails::getQuery();

        // Коректно зададени EXT полета от purchase_Invoices
        $dQuery->EXT('invoiceDate', 'purchase_Invoices', 'externalName=date,externalKey=invoiceId');
        $dQuery->EXT('invoiceState', 'purchase_Invoices', 'externalName=state,externalKey=invoiceId');

        // Филтър по състояние (включваме само НЕ rejected и draft)
        $dQuery->in('invoiceState', array('rejected', 'draft'), true);

        // Филтър по период с параметризирана where
        $dQuery->where(array(
            "#invoiceDate >= '[#1#]' AND #invoiceDate <= '[#2#]'", $from, $to));

        // Преглед и попълване на количествата и сумите
        while ($dRec = $dQuery->fetch()) {
            $pRec = cat_Products::fetch($dRec->productId);
            if (!$pRec || !$pRec->code) continue;

            $code = $pRec->code;

            if (!isset($recs[$code])) continue;

            if (!$recs[$code]->productId) {
                $recs[$code]->productId = $pRec->id;
            }

            if (!$recs[$code]->prodName) {
                $recs[$code]->prodName = $pRec->name;
            }

            if (!$recs[$code]->measureId) {
                $recs[$code]->measureId = $pRec->measureId;
            }


            // Защита от нечислови стойности
            $inQty = is_numeric($dRec->quantity) ? (float)$dRec->quantity : 0;
            $inAmt = is_numeric($dRec->amount) ? (float)$dRec->amount : 0;

            $recs[$code]->inQuantity = (float)$recs[$code]->inQuantity + $inQty;
            $recs[$code]->inAmount = (float)$recs[$code]->inAmount + $inAmt;


        }
    }

    /**
     * Натрупва стойности в outQuantity и outAmount по код:
     * - от продажбите по фактури (sales_InvoiceDetails)
     * - от касови бележки (pos_ReceiptDetails)
     * Попълва и липсващи productId, prodName и measureId в $recs
     *
     * @param stdClass $rec - запис на справката (съдържа периодите)
     * @param array    &$recs - референтно подаван масив с артикули, индексиран по код
     */
    protected function fillOutQuantitiesAndAmountsFromSales($rec, &$recs)
    {
        // === Продажби по фактури ===
        $sQuery = sales_InvoiceDetails::getQuery();
        $sQuery->EXT('invoiceDate', 'sales_Invoices', 'externalName=date,externalKey=invoiceId');
        $sQuery->EXT('invoiceState', 'sales_Invoices', 'externalName=state,externalKey=invoiceId');

        $sQuery->in('invoiceState', array('rejected', 'draft'), true);
        $sQuery->where(["#invoiceDate >= '[#1#]' AND #invoiceDate <= '[#2#]'",
            $rec->from . ' 00:00:00', $rec->to . ' 23:59:59']);

        while ($dRec = $sQuery->fetch()) {
            $pRec = cat_Products::fetch($dRec->productId);
            if (!$pRec) continue;

            $code = $pRec->code;
            if (!isset($recs[$code])) continue;

            // Попълваме липсващи данни
            if (!$recs[$code]->productId) {
                $recs[$code]->productId = $pRec->id;
            }
            if (!$recs[$code]->prodName) {
                $recs[$code]->prodName = $pRec->name;
            }
            if (!$recs[$code]->measureId) {
                $recs[$code]->measureId = $pRec->measureId;
            }

            $recs[$code]->outQuantity += (float)$dRec->quantity;
            $recs[$code]->outAmount += (float)$dRec->amount;
        }

        // === Продажби по касови бележки ===
        $posQuery = pos_ReceiptDetails::getQuery();
        $posQuery->EXT('valior', 'pos_Receipts', 'externalName=valior,externalKey=receiptId');

        $posQuery->where([
            "#valior >= '[#1#]' AND #valior <= '[#2#]'",
            $rec->from . ' 00:00:00',
            $rec->to . ' 23:59:59'
        ]);

        while ($dRec = $posQuery->fetch()) {
            if ($dRec->productId) {
                $pRec = cat_Products::fetch($dRec->productId);
            } else continue;

            if (!$pRec) continue;

            $code = $pRec->code;
            if (!isset($recs[$code])) continue;

            // Попълваме липсващи данни
            if (!$recs[$code]->productId) {
                $recs[$code]->productId = $pRec->id;
            }
            if (!$recs[$code]->prodName) {
                $recs[$code]->prodName = $pRec->name;
            }
            if (!$recs[$code]->measureId) {
                $recs[$code]->measureId = $pRec->measureId;
            }

            $recs[$code]->outQuantity += (float)$dRec->quantity;
            $recs[$code]->outAmount += (float)$dRec->amount;
        }
    }

}
