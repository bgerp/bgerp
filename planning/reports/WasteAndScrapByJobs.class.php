<?php


/**
 * Мениджър на отчети за отпадък и брак по задания
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Производство » Отпадък и брак по задания
 */
class planning_reports_WasteAndScrapByJobs extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, debug';


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
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'from, to';


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
            // Проверка на периоди
            if (isset($form->rec->from, $form->rec->to) && ($form->rec->from > $form->rec->to)) {
                $form->setError('from,to', 'Началната дата на периода не може да бъде по-голяма от крайната.');
            }
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
//bp();
        $recs = $jobsArr = array();

        $stateArr = array('active', 'wakeup', 'closed');

        // Изваждаме всички задания за периода без оттеглените и черновите
        $jobQuery = planning_Jobs::getQuery();

        $jobQuery->where(array("#activatedOn >= '[#1#]' AND #activatedOn <= '[#2#]'", $rec->from, $rec->to . ' 23:59:59'));
        $jobQuery->in('state', 'rejected, draft', true);
        while ($jobRec = $jobQuery->fetch()){

            //задания активирани в този период
            $jobsArr[$jobRec->containerId] = $jobRec;

        }

        //Изваждаме всички задачи в нишките на заданията от периода
        $taskQuery = planning_Tasks::getQuery();

        $taskQuery->in('state', $stateArr);

        $taskQuery->in('originId',array_keys($jobsArr));


        while ($taskRec = $taskQuery->fetch()){

            $prodWeigth = cat_Products::convertToUoM($taskRec->productId, 'kg');

          // bp($taskRec,$prodWeigth);
            $id = $jobsArr[$taskRec->originId]->id;

            // Запис в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object)array(

                    'jobId' => $jobsArr[$taskRec->originId]->id,                                             //Id на заданието
                    'jobArt' => $jobsArr[$taskRec->originId]->productId,                                     // Продукта по заданието
                    'scrappedQuantity' => $taskRec->scrappedQuantity,                                        // количество брак
                    'wasteQuantity' => $taskRec->totalQuantity - $taskRec->producedQuantity,
                    'prodWeight' => $prodWeigth,

                );
            } else {
                $obj = &$recs[$id];

                $obj->scrappedQuantity += $taskRec->scrappedQuantity;
                $obj->wasteQuantity += $taskRec->totalQuantity - $taskRec->producedQuantity;

            }
        }

        //bp($recs);

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
            $fld->FLD('jobId', 'varchar', 'caption=Задание');
            $fld->FLD('scrap', 'double(decimals=2)', 'caption=Отпадък');
            $fld->FLD('waste', 'double(decimals=2)', 'caption=Брак');

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
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 4;

        $row = new stdClass();

        $row->jobId = planning_Jobs::getHyperlink($dRec->jobId);

        $weight = !is_null($dRec->prodWeight) ? $dRec->prodWeight : '?';

        if (isset($dRec->prodWeight)) {
            $row->scrap = $Double->toVerbal($dRec->scrappedQuantity*$weight);
            $row->waste = $Double->toVerbal($dRec->wasteQuantity*$weight);
        }else{
            $row->scrap = '?';
            $row->waste = '?';
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
        $Double->params['decimals'] = 2;


        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN from--><div>|От|*: [#from#]</div><!--ET_END from-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));


        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->from) . '</b>', 'from');
        }

        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->to) . '</b>', 'to');
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
        $Enum = cls::get('type_Enum', array('options' => array('prod' => 'произв.', 'consum' => 'вл.')));

        $res->type = $Enum->toVerbal($dRec->consumedType);
    }

    /**
     * Рекурсивно извеждане на вложените материали
     *
     * @param stdClass $lastActivBomm
     * @return array $material
     *
     */

    private function getBaseMaterialFromBoms($lastActivBomm, &$arr, &$arr1)
    {

        //Вложени материали по рецепта (някои може да са заготовки т.е. да имат рецепти за влагане на по низши материали или заготовки)
        $bommMaterials = cat_Boms::getBomMaterials($lastActivBomm->id, $lastActivBomm->quantity);
        foreach ($bommMaterials as $baseMat) {
            $arr1[$baseMat->productId] = $baseMat->quantity;

        }


        foreach ($bommMaterials as $material) {
            if (cat_Products::getLastActiveBom($material->productId)) {
                $lastActivBomm = cat_Products::getLastActiveBom($material->productId);

                self::getBaseMaterialFromBoms($lastActivBomm, $arr, $arr1);

            } else {

                $id = $material->productId;

                $jobsQuantityMaterial = (double)$arr1[$lastActivBomm->productId] * $material->quantity / $lastActivBomm->quantity;

                if (!array_key_exists($id, $arr)) {
                    $arr[$id] = (object)array(
                        'productId' => $material->productId,
                        'quantity' => $jobsQuantityMaterial
                    );
                } else {
                    $obj = &$arr[$id];
                    $obj->quantity += $jobsQuantityMaterial;
                }
            }

        }

        return $arr;
    }

}
