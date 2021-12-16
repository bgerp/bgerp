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

        $fieldset->FLD('minVolWeight', 'double', 'notNull,caption=Тегло на куб. дециметър->Мин.,after=typeOfProduckts,single=none');
        $fieldset->FLD('maxVolWeight', 'double', 'notNull,caption=Тегло на куб. дециметър->Макс.,after=minVolWeight,single=none');

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

        $form->setDefault('typeOfProducts', 'public');

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

        $pQuery->where("#state = 'active' AND #canStore = 'yes'");
        //$pQuery -> in('id',array(95,546));

        if ($rec->typeOfProducts == 'public') {
            $pQuery->where("#isPublic = 'yes'");
        } else {
            $pQuery->where("#isPublic = 'no'");
        }

        // Синхронизира таймлимита с броя записи
        $timeLimit = $pQuery->count() * 0.2;

        if ($timeLimit >= 30) {
            core_App::setTimeLimit($timeLimit);
        }

        $zeroProd = array();

        while ($pRec = $pQuery->fetch()) {

            $prodTransWeight = $prodTransVolume = $volumeWeight = $prodVol= $deviation = 0;

            //Обема на кашона
            $uomRec = cat_UoM::fetchBySinonim('кашон');
            $packRec = cat_products_Packagings::getPack($pRec->id,$uomRec->id);

            //Обем на кашона в куб.м.
            $packVolume = $packRec->sizeWidth*$packRec->sizeHeight*$packRec->sizeDepth;

            //Обем за единица продукт
            if ($packRec->quantity){
                $prodVol = ($packVolume / $packRec->quantity)*1000;
            }

            $id = $pRec->id;
            try {
                $transportVolumeId = cat_Params::force('transportVolume', 'transportVolume', 'varchar', null, '');
                $prodTransVolume = cat_Products::getParams($pRec->id)[$transportVolumeId]; //Вземаме количество 1000 понеже функцията го връща в куб.метри, и така става в литри
                //$prodTransVolume = cat_Products::getTransportVolume($pRec->id, 1000); //Вземаме количество 1000 понеже функцията го връща в куб.метри, и така става в литри

                $prodTransWeight = cat_Products::getTransportWeight($pRec->id, 1);
            } catch (Exception $e) {

            }

            if ($prodVol != 0 && $prodTransVolume != 0 ){

                $deviation = abs(round(($prodVol - $prodTransVolume) / (($prodVol + $prodTransVolume) / 2), 2));
            }


            if ($deviation){
                $prodVolumeDeviation[$id] = (object)array(
                    'productId' => $pRec->id,                                      // Артикул
                    'deviation' => $deviation,                                     // Отклонение
                    'prodVolume' => $prodTransVolume,                              // Транспортен обем
                    'prodWeight' => $prodTransWeight,                              // Транспортно тегло
                    'packVolume' => $packVolume,                                   // Обем на кашона
                    'prodVol' => $prodVol,                                         // Реален обем на артикула за 1000 бр
                );
            }


            //bp($prodTransVolume,$prodVol,$deviation,$prodVolumeDeviation);

            if (!$prodVol) {

                $zeroProd[$id] = (object)array(
                    'productId' => $pRec->id,                                      // Артикул
                    'prodVolume' => $prodTransVolume * 1000,                       // Транспортен обем
                    'prodWeight' => $prodTransWeight,                              // Транспортно тегло
                    'packVolume' => $packVolume,                                   // Обем на кашона
                    'prodVol' => $prodVol,                                         // Реален обем на артикула за 1000 бр
                    'deviation' => $deviation,                                     // Отклонение
                );

                continue;
            }

            if ($prodTransVolume){
                $volumeWeight = $prodTransWeight / ($prodTransVolume);
            }


            if ($volumeWeight > $rec->minVolWeight && $volumeWeight < $rec->maxVolWeight) continue;

            // Запис в масива
//            if (!array_key_exists($id, $recs)) {
//                $recs[$id] = (object)array(
//                    'productId' => $pRec->id,                                      // Артикул
//                    'prodVolume' => $prodTransVolume,                              // Транспортен обем
//                    'prodWeight' => $prodTransWeight,                              // Транспортно тегло
//                    'volumeWeight' => $volumeWeight,                               // Обемно тегло
//                    'packVolume' => $packVolume,                                   // Обем на кашона
//                    'prodVol' => $prodVol,                                         // Реален обем на артикула за 1000 бр
//                    'deviation' => $deviation,                                     // Отклонение
//
//                );
//            }


        }


    //    $recs = $recs + $zeroProd;

        $recs = $prodVolumeDeviation;
        arr::sortObjects($recs, 'deviation', 'desc');
        $recs = $recs + $zeroProd;


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
        $fld->FLD('prodVolume', 'double(smartRound,decimals=2)', 'caption=Тр. обем');
        $fld->FLD('prodWeight', 'double(smartRound,decimals=2)', 'caption=Тр. тегло');
        $fld->FLD('volumeWeight', 'varchar', 'caption=Обемно тегло');
        //$fld->FLD('packVolume', 'double(smartRound,decimals=2)', 'caption=Обем->На каш.');
        $fld->FLD('prodVol', 'double(smartRound,decimals=3)', 'caption=Обем->Реален(арт)');
        $fld->FLD('deviation', 'double(smartRound,decimals=2)', 'caption=Отклонение');

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
       // $row->packVolume = $Double->toVerbal($dRec->packVolume);
        $row->prodVol = $Double->toVerbal($dRec->prodVol);
        $row->deviation = $Double->toVerbal($dRec->deviation);

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

        if ((isset($data->rec->minVolWeight))) {
            $fieldTpl->append('<b>' . $Double->toverbal($data->rec->minVolWeight) . '</b>', 'minVolWeight');
        }

        if ((isset($data->rec->maxVolWeight))) {
            $fieldTpl->append('<b>' . $Double->toverbal($data->rec->maxVolWeight) . '</b>', 'maxVolWeight');
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
