<?php


/**
 * Мениджър на отчети Време за работа със системата
 *
 * @category  bgerp
 * @package   hr
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Персонал » Време за работа със системата
 */
class hr_reports_TimeToWorkWithTheSystem extends frame2_driver_TableData
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
    protected $sortableListFields ;


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

        //Период
        $fieldset->FLD('from', 'date', 'caption=От,after=groups,removeAndRefreshForm,single=none,silent,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,removeAndRefreshForm,single=none,silent,mandatory');

        //Вътрешни Ip-та
        $fieldset->FLD('inIp', 'keylist()', 'caption=Вътрешни Ip-та,single=none,mandatory,after=to');

        //Максимално време за изчакване
        $fieldset->FLD('maxTimeWaiting', 'time(suggestions=|5 мин|10 мин|15 мин|20 мин)', 'caption=Мак. изчакване, after=inIp,mandatory,single=none,removeAndRefreshForm');

        //Потребители
        $fieldset->FLD('users', 'users(rolesForAll=ceo|repAllGlobal, rolesForTeams=ceo|manager|repAll|repAllGlobal)', 'caption=Потребители,single=none,mandatory,after=maxTimeWaiting');

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

        $form->setDefault('maxTimeWaiting', '10 мин');

        $arr = explode(',',core_Packs::getConfig('hr')->HR_COMPANIES_IP);

        $q = log_Ips::getQuery();
        $q -> in('ip',$arr);

        while ($ipRec = $q->fetch()){
            $suggestions[$ipRec->id] = $ipRec->ip;
        }

        $form->setSuggestions('inIp', $suggestions);

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

        $logDatQuery = log_Data::getQuery();

       // $logDatQuery->where("#ipId != 724");

        $logDatQuery->in('ipId', keylist::toArray($rec->inIp));

bp($logDatQuery->fetchAll(),$rec);


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
        $Double->params['decimals'] = 2;
        $Date = cls::get('type_Date');

        $row = new stdClass();


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
        $Enum = cls::get('type_Enum', array('options' => array('selfPrice' => 'политика"Себестойност"', 'catalog' => 'политика"Каталог"', 'accPrice' => 'Счетоводна')));
        $currency = 'лв.';

        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN from--><div>|От|*: [#from#]</div><!--ET_END from-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                        <!--ET_BEGIN jobses--><div>|Избрани задания|*: [#jobses#]</div><!--ET_END jobses-->
                                        <!--ET_BEGIN groups--><div>|Групи продукти|*: [#groups#]</div><!--ET_END groups-->
                                        <!--ET_BEGIN products--><div>|Артикули|*: [#products#]</div><!--ET_END products-->
                                        <!--ET_BEGIN pricesType--><div>|Стойност|*: [#pricesType#]</div><!--ET_END pricesType-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));

        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->from) . '</b>', 'from');
        }

        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->to) . '</b>', 'to');
        }

        if (isset($data->rec->pricesType)) {
            $fieldTpl->append('<b>' . $Enum->toVerbal($data->rec->pricesType) . '</b>', 'pricesType');
        }

        $marker = 0;
        if (isset($data->rec->groups)) {
            foreach (type_Keylist::toArray($data->rec->groups) as $group) {
                $marker++;

                $groupVerb .= (cat_Groups::getTitleById($group));

                if ((countR((type_Keylist::toArray($data->rec->groups))) - $marker) != 0) {
                    $groupVerb .= ', ';
                }
            }

            $fieldTpl->append('<b>' . $groupVerb . '</b>', 'groups');
        } else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'groups');
        }

        $marker = 0;
        if ($data->rec->option == 'yes') {
            if (isset($data->rec->products)) {
                foreach (type_Keylist::toArray($data->rec->products) as $product) {
                    $marker++;

                    $productVerb .= (cat_Products::getTitleById($product));

                    if ((countR((type_Keylist::toArray($data->rec->products))) - $marker) != 0) {
                        $productVerb .= ', ';
                    }
                }

                $fieldTpl->append('<b>' . $productVerb . '</b>', 'products');
            } else {
                $fieldTpl->append('<b>' . 'Всички' . '</b>', 'products');
            }
        }

        $marker = 0;
        if ($data->rec->option == 'no') {
            if (isset($data->rec->jobses)) {
                foreach (type_Keylist::toArray($data->rec->jobses) as $job) {
                    $marker++;

                    $jRec = planning_Jobs::fetch($job);

                    $jContainer = $jRec->containerId;

                    $Job = doc_Containers::getDocument($jContainer);

                    $handle = $Job->getHandle();

                    $singleUrl = $Job->getUrlWithAccess($Job->getInstance(), $job);

                    $jobVerb .= ht::createLink("#{$handle}", $singleUrl);

                    if ((countR((type_Keylist::toArray($data->rec->jobses))) - $marker) != 0) {
                        $jobVerb .= ', ';
                    }
                }

                $fieldTpl->append('<b>' . $jobVerb . '</b>', 'jobses');
            } else {
                $fieldTpl->append('<b>' . 'Всички' . '</b>', 'jobses');
            }
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

        $res->name = cat_Products::fetch($dRec->productId)->name;
        $res->measure = cat_UoM::fetchField(cat_Products::fetch($dRec->productId)->measureId, 'shortName');
    }


}
