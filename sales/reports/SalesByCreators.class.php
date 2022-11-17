<?php


/**
 * Мениджър на отчети за продажби по създател
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Продажби » Продажби по създател
 */
class sales_reports_SalesByCreators extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,debug';


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
    protected $groupByField ;


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields ;


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('from', 'date', 'caption=От,after=compare,single=none,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,mandatory');
        $fieldset->FLD('creators', 'users(rolesForAll=ceo|repAllGlobal, rolesForTeams=ceo|manager|repAll|repAllGlobal)', 'caption=Създател,single=none,mandatory,after=to');
        $fieldset->FLD('seeDelta', 'set(yes = )', 'caption=Делти,after=articleType,single=none');
        $fieldset->FLD('see', 'set(sales=Сделки, articles=Артикули)', 'caption=Покажи,maxRadio=2,after=articleType,single=none,silent');
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
        $suggestions = array();
        $form = $data->form;
        $rec = $form->rec;

        if (!core_Users::haveRole(array('ceo'))) {
            $form->setField('seeDelta', 'input=hidden');
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
        if (is_null($rec->crmGroup) && is_null($rec->contragent)) {
            $this->groupByField = '';
        }

        $recs = array();
        $salesWithShipArr = array();

        $contragentsId = array();

        $query = sales_Sales::getQuery();

        $query->where("#state != 'rejected'");

        $query->where("#valior >= '{$rec->from}' AND #valior <= '{$rec->to}'");

        if (isset($rec->creators)) {
            if ((min(array_keys(keylist::toArray($rec->creators))) >= 1)) {
                $creators = keylist::toArray($rec->creators);

                $query->in('createdBy', $creators);
            }
        }


        // Синхронизира таймлимита с броя записи //
        $rec->count = $query->count();

        $timeLimit = $query->count() * 0.05;

        if ($timeLimit >= 30) {
            core_App::setTimeLimit($timeLimit);
        }

        while ($sRec = $query->fetch()) {

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

            $fld->FLD('contragentId', 'varchar', 'caption=Създател');
            $fld->FLD('delta', 'double(decimals=2)', "smartCenter,caption=Делта");
            $fld->FLD('value', 'double(decimals=2)', 'smartCenter,caption=Продажби');

        } else {

            $fld->FLD('contragentId', 'varchar', 'caption=Създател');
            $fld->FLD('delta', 'double(decimals=2)', "smartCenter,caption=Делта");
            $fld->FLD('value', 'double(decimals=2)', 'smartCenter,caption=Продажби');
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

        $row->creator = core_Users::getTitleById($dRec->creator);

        $row->value = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->value) . '</b>';
        $row->value = ht::styleNumber($row->value, $dRec->value);

        $row->delta = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->delta) . '</b>';
        $row->delta = ht::styleNumber($row->delta, $dRec->delta);

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

        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN from--><div>|От|*: [#from#]</div><!--ET_END from-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                        <!--ET_BEGIN firstMonth--><div>|Месец 1|*: [#firstMonth#]</div><!--ET_END firstMonth-->
                                        <!--ET_BEGIN secondMonth--><div>|Месец 2|*: [#secondMonth#]</div><!--ET_END secondMonth-->
                                        <!--ET_BEGIN dealers--><div>|Търговци|*: [#dealers#]</div><!--ET_END dealers-->
                                        <!--ET_BEGIN contragent--><div>|Контрагент|*: [#contragent#]</div><!--ET_END contragent-->
                                        <!--ET_BEGIN crmGroup--><div>|Група контрагенти|*: [#crmGroup#]</div><!--ET_END crmGroup-->
                                        <!--ET_BEGIN group--><div>|Групи продукти|*: [#group#]</div><!--ET_END group-->
                                        <!--ET_BEGIN compare--><div>|Сравнение|*: [#compare#]</div><!--ET_END compare-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));



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

    }
}
