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
    protected $summaryListFields ;


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
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {

        $fieldset->FLD('from', 'date', 'caption=От,refreshForm,after=title,single=none');
        $fieldset->FLD('to', 'date', 'caption=До,refreshForm,after=from,single=none');

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

        bp($rec);


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

            $fld->FLD('date', 'varchar', 'caption=Дата');

            $fld->FLD('allAutoDiscountContragent', 'double(decimals=2)', 'caption=Отстъпка');

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


        $d = substr(dt::mysql2verbal($dRec->waitingOn), 0, 8);

        $row->date = $d;

            $row->date = '<span class="fright">' . $d . '</span>';

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

