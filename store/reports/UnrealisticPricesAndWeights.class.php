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

        $fieldset->FLD('minVolWeight', 'double', 'notNull,caption=Минималнo тегло на куб. дециметър,after=typeOfProduckts,single=none');
        $fieldset->FLD('maxVolWeight', 'double', 'notNull,caption=Максималнo тегло на куб. дециметър,after=minVolWeight,single=none');

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



        if ($rec->typeOfProducts == 'public'){
            $pQuery->where("#isPublic = 'yes'");
        }else{
            $pQuery->where("#isPublic = 'no'");
        }

        // Синхронизира таймлимита с броя записи
        $timeLimit = $pQuery->count() * 0.05;

        if ($timeLimit >= 30) {
            core_App::setTimeLimit($timeLimit);
        }

        $zeroProd = array();

        while ($pRec = $pQuery->fetch()){

            $prodTransWeight = $prodTransVolume = $volumeWeight = 0;

            $id = $pRec->id;
            try {
                $prodTransVolume = cat_Products::getTransportVolume($pRec->id,1000); //Вземаме количество 1000 понеже функцията го връща в куб.метри, и така става в литри
                $prodTransWeight = cat_Products::getTransportWeight($pRec->id,1);
            }catch (Exception $e){
                ;
            }


            if (!$prodTransVolume || !$prodTransWeight){

                $zeroProd[$id] = (object)array(
                    'productId' => $pRec->id,                                      // Артикул
                    'prodVolume' => $prodTransVolume*1000,                         // Транспортен обем
                    'prodWeight' => $prodTransWeight,                              // Транспортно тегло


                );

                continue;
            }

            $volumeWeight = $prodTransWeight/($prodTransVolume);

            if ($volumeWeight > $rec->minVolWeight && $volumeWeight < $rec->maxVolWeight) continue;

            // Запис в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object)array(
                    'productId' => $pRec->id,                                      // Артикул
                    'prodVolume' => $prodTransVolume,                         // Транспортен обем
                    'prodWeight' => $prodTransWeight,                              // Транспортно тегло
                    'volumeWeight' => $volumeWeight,                               // Обемно тегло

                );
            }



        }
        $recs = $recs+$zeroProd;

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
        $fld->FLD('prodVolume', 'varchar', 'caption=Тр. обем');
        $fld->FLD('prodWeight', 'varchar', 'caption=Тр. тегло');
        $fld->FLD('volumeWeight', 'varchar', 'caption=Обемно тегло');


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

        if (isset($dRec->productId)) {
            $row->productId = cat_Products::getHyperlink($dRec->productId, 'name');
        }

        $row->prodVolume = $Double->toVerbal($dRec->prodVolume);
        $row->prodWeight = $Double->toVerbal($dRec->prodWeight);
        $row->volumeWeight = $Double->toVerbal($dRec->volumeWeight);

        if (!$dRec->volumeWeight){

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
        $Double->params['decimals'] = 2;

        $res->quantyti = $dRec->blQuantity;

        $res->measure = cat_UoM::fetch(cat_Products::fetch($dRec->productId)->measureId)->shortName;
    }
}
