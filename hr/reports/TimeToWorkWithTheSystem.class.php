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
        $fieldset->FLD('inIp', 'keylist()', 'caption=Вътрешни Ip-та,single=none,after=to');

        //Максимално време за изчакване
        $fieldset->FLD('maxTimeWaiting', 'time(suggestions=|5 мин|10 мин|15 мин|20 мин)', 'caption=Макс. изчакване, after=inIp,mandatory,removeAndRefreshForm');

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
        $form->setDefault('inIp', $suggestions);
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

        // Филтрираме по време
        if ($rec->from || $rec->to) {
            $dateRange = array();

            if ($rec->from) {
                $dateRange[0] = $rec->from;
            }

            if ($rec->to) {
                $dateRange[1] = $rec->to;
            }

            if (countR($dateRange) == 2) {
                sort($dateRange);
            }

            if ($dateRange[0]) {
                if (!strpos($dateRange[0], ' ')) {
                    $dateRange[0] .= ' 00:00:00';
                }
                $dateRange[0] = dt::mysql2timestamp($dateRange[0]);
                $logDatQuery->where(array("#time >= '[#1#]'", $dateRange[0]));
            }

            if ($dateRange[1]) {
                if (!strpos($dateRange[1], ' ')) {
                    $dateRange[1] .= ' 23:59:59';
                }
                $dateRange[1] = dt::mysql2timestamp($dateRange[1]);
                $logDatQuery->where(array("#time <= '[#1#]'", $dateRange[1]));
            }
        }

        $logDatQuery->where("#userId > 0");

        $logDatQuery->in('userId',keylist::toArray($rec->users));

        $logDatQuery->orderBy('time', 'ASC');

        $ipType = $oldIpType = null;

        $iPInArr = keylist::toArray($rec->inIp);
        $workingTime = $lastWorkTime = $lastWorkHash = array();

        while ($lRec = $logDatQuery->fetch()){

            $oldIpType = $ipType;

            $minutesToAdd = 0;

            $ipType = (in_array($lRec->ipId,$iPInArr)) ? 'office':'home';

            $hash = md5($lRec->type . $lRec->actionCrc . $lRec->classCrc . $lRec->objectId);
            $minute = (integer)($lRec->time / 60);

            //Зареждаме данните от първия запис като начални
            if(is_null($workingTime[$lRec->userId][$ipType]) &&
                is_null($lastWorkTime[$lRec->userId][$ipType]) &&
                    is_null($lastWorkHash[$lRec->userId][$ipType])){

                $lastWorkTime[$lRec->userId][$ipType] = $minute;
                $lastWorkHash[$lRec->userId][$ipType] = $hash;
                $workingTime[$lRec->userId][$ipType] = 0;
                continue;
            }
            $minutesToAdd = $minute - $lastWorkTime[$lRec->userId][$ipType];

            if($minutesToAdd <= 0)continue;

            //аковремето на престой е по-малко от заложения минумум за престой и $ipType = 'home'
            //приемаме, че това е кратковременно включване от телефона и връщаме $ipType = 'office'
            if(($rec->maxTimeWaiting/60 >= $minutesToAdd) && ($ipType == 'home') && ($oldIpType == 'office')){
                $ipType = 'office';
            }

            if ($ipType == 'home')continue;

            //Ако $hash === $lastWorkHash[$lRec->userId][$ipType] приемаме че,
            // потрбителя прави рефреш на ресурса и не включваме  записа
            if($hash === $lastWorkHash[$lRec->userId][$ipType]){
                continue;
            }
            $lastWorkHash[$lRec->userId][$ipType] = $hash;


            expect($minutesToAdd >= 0,'Некоректен запис, или подредба на масива');

            if($minutesToAdd > $rec->maxTimeWaiting/60){
                $minutesToAdd = 1;
            }

            $workingTime[$lRec->userId][$ipType] += $minutesToAdd;
            $lastWorkTime[$lRec->userId][$ipType] = $minute;

        }

        foreach ($workingTime as $key => $val){
            $recs[$key] = (object)array(

                'userId' => $key,
                'home' => $val['home'],
                'office' => $val['office']

            );

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

            $fld->FLD('userId', 'varchar', 'caption=Потребител');
            $fld->FLD('office', 'int', 'caption=Офис[min],smartCenter');
            //$fld->FLD('home', 'int', 'caption=Отдалечено[min],smartCenter');

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

        $personId = crm_Profiles::fetch("#userId =$dRec->userId")->personId;

        $row->userId = crm_Persons::fetch($personId)->name;

        $row->userId .= ' ['.crm_Profiles::createLink($dRec->userId).']';

        $row->office = ($dRec->office);
       // $row->home = ($dRec->home);


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
                                        <!--ET_BEGIN users--><div>|Избрани потребители|*: [#users#]</div><!--ET_END users-->
                                        <!--ET_BEGIN maxTimeWaiting--><div>|Макс. изчакване|*: [#maxTimeWaiting#]</div><!--ET_END maxTimeWaiting-->
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
        if (isset($data->rec->users)) {
            foreach (type_Keylist::toArray($data->rec->users) as $user) {
                $marker++;

                $userVerb .= crm_Profiles::createLink($user);

                if ((countR((type_Keylist::toArray($data->rec->groups))) - $marker) != 0) {
                    $userVerb .= ', ';
                }
            }

            //$fieldTpl->append('<b>' . $userVerb . '</b>', 'users');
        } else {
           // $fieldTpl->append('<b>' . 'Всички' . '</b>', 'users');
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