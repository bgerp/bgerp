<?php


/**
 * Мениджър на отчети за стоки с нереални цени и тегла
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Склад » Стоки с нереални цени и тегла
 */
class store_reports_UnrealisticPricesAndWeights extends frame2_driver_TableData
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

        $fieldset->FLD('typeOfProducts', 'enum(public=Стандартни,npublic=Нестандартни)', 'caption=Тип артикули,maxRadio=2,columns=2,after=title,mandatory,single=none');

        $fieldset->FLD('period', 'time(suggestions=1 месец|3 месеца|6 месеца|1 година|5 години|10 години)', 'caption=Период, after=typeOfProducts,mandatory');

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

        $form->setDefault('typeOfProducts', 'npublic');
        $form->setDefault('period', '1 месец');

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

        $pQuery = cat_Products::getQuery();

        $startDate = dt::addSecs(-$rec->period, dt::now());

        $pQuery->where("#createdOn >= '{$startDate}'");

        $pQuery->where("#state = 'active' AND #canStore = 'yes'");

        if ($rec->typeOfProducts == 'public') {
            $pQuery->where("#isPublic = 'yes'");
        } else {
            $pQuery->where("#isPublic = 'no'");
        }
bp($pQuery->count(),$startDate);
        // Синхронизира таймлимита с броя записи
        $timeLimit = $pQuery->count() * 0.2;

        if ($timeLimit >= 30) {
            core_App::setTimeLimit($timeLimit);
        }

        $zeroProd = array();

        $transportVolumeParamId = cat_Params::force('transportVolume', 'transportVolume', 'varchar', null, '');
        $transportWeightParamId = cat_Params::force('transportWeight', 'transportWeight', 'varchar', null, '');
        $prodWeightParamId = cat_Params::force('weight', 'weight', 'varchar', null, '');
        $prodWeightKgParamId = cat_Params::force('weightKg', 'weight', 'varchar', null, '');

        while ($pRec = $pQuery->fetch()) {

            $prodTransWeight = $prodTransVolume = $realProdVol = $realProdWeight = $deviation = $deviationDensity = 0;

            //Обема на кашона
            $uomRec = cat_UoM::fetchBySinonim('кашон');
            $packRec = cat_products_Packagings::getPack($pRec->id, $uomRec->id);

            //Обем на кашона в куб.м.
            $packVolume = $packRec->sizeWidth * $packRec->sizeHeight * $packRec->sizeDepth;

            //Обем за единица продукт
            if ($packRec->quantity) {

                //Обем на артикула в куб.м за 1000 бр.
                $realProdVol = ($packVolume / $packRec->quantity) * 1000;

                //Тегло на тарата в кг за 1 артикул
                $realPackTara = $packRec->tareWeight / $packRec->quantity;

                //Тегло на артикула от параметъра в кг
                $prodWeight = cat_Products::getParams($pRec->id)[$prodWeightParamId] / 1000 ?? cat_Products::getParams($pRec->id)[$prodWeightKgParamId];
                $prodWeight = $prodWeight ?? 0;

                //Реално тегло на артикула в кг за 1000 бройки
                $realProdWeight = ($prodWeight + $realPackTara) * 1000;

            }

            $id = $pRec->id;
            try {
                // Транспортен обем на продукта от параметър "Транспортен обем" в куб.м за 1000 бр.
                $prodTransVolume = cat_Products::getParams($pRec->id)[$transportVolumeParamId];

                //Транспортно тегло от параметър "Транспортно тегло" в кг за 1000 бр
                $prodTransWeight = cat_Products::getParams($pRec->id)[$transportWeightParamId] * 1000;

            } catch (Exception $e) {

            }

            //Плътност
            if ($prodTransVolume) {
                $transDensity = round($prodTransWeight / $prodTransVolume, 3);
            }
            if ($realProdVol) {
                $realDensity = round($realProdWeight / $realProdVol, 3);
            }

            if ($realProdVol != 0 && $prodTransVolume != 0) {

                $deviation = abs(round(($realProdVol - $prodTransVolume) / (($realProdVol + $prodTransVolume) / 2), 2));
            }
            if ($realDensity != 0 && $transDensity != 0) {

                $deviationDensity = abs(round(($realDensity - $transDensity) / (($realDensity + $transDensity) / 2), 2));
            }

            if ($deviation) {
                $recs[$id] = (object)array(
                    'productId' => $pRec->id,                                      // Артикул

                    'prodVolume' => $prodTransVolume,                              // Транспортен обем
                    'realProdVol' => $realProdVol,                                 // Реален обем на артикула за 1000 бр
                    'deviation' => $deviation,                                     // Отклонение обем

                    'prodWeight' => $prodTransWeight,                              // Транспортно тегло
                    'realProdWeight' => $realProdWeight,                           // Реално тело на артикула за 1000 бр

                    'transDensity' => $transDensity,                                // Транспортна плътност
                    'realDensity' => $realDensity,                                 // Реална плътност
                    'deviationDensity' => $deviationDensity,                       // Отклонение плътност

                );
            }

//            if (!$realProdVol) {
//
//                $zeroProd[$id] = (object)array(
//                    'productId' => $pRec->id,                                      // Артикул
//                    'prodVolume' => $prodTransVolume,                              // Транспортен обем
//                    'prodWeight' => $prodTransWeight,                              // Транспортно тегло
//                    'packVolume' => $packVolume,                                   // Обем на кашона
//                    'realProdVol' => $realProdVol,                                 // Реален обем на артикула за 1000 бр
//                    'realProdWeight' => $realProdWeight,                           // Реално тело на артикула за 1000 бр
//                    'deviationDensity' => $deviationDensity,                       // Отклонение плътност
//                );
//
//            }

        }

        if (!empty($recs)) {

            arr::sortObjects($recs, 'deviation', 'desc');

            //  $recs = $recs + $zeroProd;
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

        $fld->FLD('productId', 'varchar', 'caption=Артикул');
        $fld->FLD('prodVolume', 'double(smartRound,decimals=2)', 'caption=Обем[m3]->Транс.');
        $fld->FLD('realProdVol', 'double(smartRound,decimals=3)', 'caption=Обем[m3]->Реален');
        $fld->FLD('deviation', 'double(smartRound,decimals=2)', 'caption=Обем[m3]->Отклонение');

        $fld->FLD('prodWeight', 'double(smartRound,decimals=2)', 'caption=Тегло[кг]->Транс.');
        $fld->FLD('realProdWeight', 'double(smartRound,decimals=3)', 'caption=Тегло[кг]->Реално');

        $fld->FLD('transDensity', 'double(smartRound,decimals=3)', 'caption=Плътност->Транс.');
        $fld->FLD('realDensity', 'double(smartRound,decimals=3)', 'caption=Плътност->Реално');
        $fld->FLD('deviationDensity', 'double(smartRound,decimals=3)', 'caption=Плътност->Отклонение');


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
        $Double->params['decimals'] = 3;

        $row = new stdClass();

        if (isset($dRec->productId)) {
            $row->productId = cat_Products::getHyperlink($dRec->productId);
        }

        $row->prodVolume = $Double->toVerbal($dRec->prodVolume);
        $row->prodWeight = $Double->toVerbal($dRec->prodWeight);
        $row->volumeWeight = $Double->toVerbal($dRec->volumeWeight);
        $row->realProdVol = $Double->toVerbal($dRec->realProdVol);
        $row->realProdWeight = $Double->toVerbal($dRec->realProdWeight);

        $row->transDensity = $Double->toVerbal($dRec->transDensity);
        $row->realDensity = $Double->toVerbal($dRec->realDensity);


        $row->deviation = core_Type::getByName('double(smartRound,decimals=2)')->toVerbal($dRec->deviation);
        $row->deviationDensity = core_Type::getByName('double(smartRound,decimals=2)')->toVerbal($dRec->deviationDensity);

        if (!$dRec->deviation) {

            $row->ROW_ATTR['class'] = 'state-closed';
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
        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 4;
        $Enum = cls::get('type_Enum', array('options' => array('public' => 'Стандартни', 'npublic' => 'Нестандартни')));


        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
								    <div class='small'>
								        <!--ET_BEGIN typeOfProducts--><div>|Тип артикули|*: [#typeOfProducts#]</div><!--ET_END typeOfProducts-->
                                        <!--ET_BEGIN minVolWeight--><div>|Минимално обемно тегло|*: [#minVolWeight#] kg</div><!--ET_END minVolWeight-->
                                        <!--ET_BEGIN maxVolWeight--><div>|Максимално обемно тегло|*: [#maxVolWeight#] kg</div><!--ET_END maxVolWeight-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));

        if ((isset($data->rec->typeOfProducts))) {
            $fieldTpl->append('<b>' . $Enum->toVerbal($data->rec->typeOfProducts) . '</b>', 'typeOfProducts');
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
        $Double->params['decimals'] = 3;
        $res->productId = cat_Products::fetch($dRec->productId)->name;
        $res->prodVolume = $Double->toVerbal($dRec->prodVolume);
        $res->prodWeight = $Double->toVerbal($dRec->prodWeight);
        $res->volumeWeight = $Double->toVerbal($dRec->volumeWeight);
    }
}
