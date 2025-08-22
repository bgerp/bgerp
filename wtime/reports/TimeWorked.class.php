<?php


/**
 * Мениджър на отчети Отчитане на работното време
 *
 * @category  bgerp
 * @package   wtime
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Персонал » Отчитане на работното време
 */
class wtime_reports_TimeWorked extends frame2_driver_TableData
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
    protected $groupByField = 'personName' ;


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields;


    /**
     * Кои полета са за избор на период
     */
    protected $periodFields = 'periods';


    /**
     * Добавя полетата на драйвъра към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {

        //Период
        $fieldset->FLD('periods', 'key(mvc=acc_Periods,select=title)', 'caption=Месец,after=title,single=none');

        //Потребители
        $fieldset->FLD('crmGroup', 'keylist(mvc=crm_Groups,select=name)', 'caption=Потребители->Група потребители,placeholder=Всички,mandatory,after=periods,single=none');

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

        // Нужни са избрани групи и период (acc_Periods)
        if (empty($rec->crmGroup) || empty($rec->periods)) {
            return $recs;
        }

        // 1) Служителите от групите
        $personsInGroups = self::getPersonIdsFromCrmGroups($rec->crmGroup, true);
        if (empty($personsInGroups)) {
            return $recs;
        }

        // 2) Дните в периода (включително)
        $perRec = acc_Periods::fetch($rec->periods);
        if (!$perRec) return $recs;

        $from = isset($perRec->start) ? $perRec->start : (isset($perRec->from) ? $perRec->from : null);
        $to   = isset($perRec->end)   ? $perRec->end   : (isset($perRec->to)   ? $perRec->to   : null);
        if (!$from || !$to) return $recs;

        $from = dt::verbal2mysql($from, false);
        $to   = dt::verbal2mysql($to,   false);

        $dates = array();
        $k = 0;
        for ($d = $from; ; $d = dt::addDays(1, $d, false)) {
            $k++;
            $dates[$k] = $d;      // пазим поредност + Y-m-d
            if ($d >= $to) break; // включително
        }

        if (is_object($data)) {
            $data->periodDates = $dates;          // списък с дати за периода
            $data->persons     = $personsInGroups;  // [personId => name]
        }

        // 3) Намираме смените и времето за всеки ден
        $personsShiftsInPeriod = self::getPersonsShiftsInPeriod($personsInGroups, $dates);  // [pId][Y-m-d] => shiftName|null

        //Отчитане на отпуските
        $personsShiftsInPeriod = self::getPersonsLeavesDaysInPeriod($personsInGroups, $dates,$personsShiftsInPeriod );

        //Отчитане на болничните
        $personsShiftsInPeriod = self::getPersonsSickDaysInPeriod($personsInGroups, $dates,$personsShiftsInPeriod );

        $personsTimeInPeriod   = self::getPersonsTimeInPeriod($personsInGroups, $dates);    // [pId][Y-m-d] => seconds

        // 4) По 3 реда на човек: 'shift', 'onsite', 'ops'
        foreach ($personsInGroups as $pId => $pName) {

            // a) ред „смяна“
            $recs[] = (object) array(
                'rowType'        => 'shift',
                'personId'       => $pId,
                'personName'     => $pName,
                'shiftsInPeriod' => isset($personsShiftsInPeriod[$pId]) ? $personsShiftsInPeriod[$pId] : array(),
            );

            // b) ред „време“ (секунди)
            $recs[] = (object) array(
                'rowType'          => 'onsite',
                'personId'         => $pId,
                'personName'       => $pName,
                'onSiteTimeByDate' => isset($personsTimeInPeriod[$pId]) ? $personsTimeInPeriod[$pId] : array(),
            );

            // c) ред „%“ (минутите ще ги превърнем в % при вербализация)
            //    за удобство пазим минутите (сек/60)
            $opsMinutes = array();
            if (!empty($personsTimeInPeriod[$pId])) {
                foreach ($personsTimeInPeriod[$pId] as $ymd => $sec) {
                    $opsMinutes[$ymd] = (int) round($sec / 60);
                }
            }
            $recs[] = (object) array(
                'rowType'          => 'ops',
                'personId'         => $pId,
                'personName'       => $pName,
                'opsMinutesByDate' => $opsMinutes,
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

        $fld->FLD('personName', 'varchar', 'caption=Потребител,tdClass=leftCol');
        $fld->FLD('metric', 'varchar', 'caption=Показател,tdClass=center');

        for ($i = 0; $i < countR($rec->data->periodDates); $i++) {
            $d = dt::addDays($i, $rec->data->periodDates[1]); // Y-m-d
            $code = sprintf('d%02d', $i + 1);
            $caption = dt::mysql2verbal($d, 'd.m');
            $fld->FLD($code, 'varchar', "caption={$caption},tdClass=center,smartCenter");
        }

        return $fld;
    }
    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     * @param stdClass $dRec
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $row = new stdClass();

        $row->personName = "<div style='text-align:center;font-weight:600;'>{$dRec->personName}</div>";


        // Показател
        $metricByType = ['shift' => 'смяна', 'onsite' => 'време', 'ops' => '%'];
        $row->metric = $metricByType[$dRec->rowType] ?? '';

        // Форматирания
        $fmtHm = function ($seconds) {
            if (!is_numeric($seconds) || $seconds <= 0) return 'nd';
            $minutes = (int) round($seconds / 60);
            $h = (int) floor($minutes / 60);
            $m = $minutes % 60;
            return sprintf('%d:%02d', $h, $m);
        };
        $fmtPct = function ($minutes) {
            if (!is_numeric($minutes) || $minutes <= 0) return 'nd';
            $pct = round(($minutes / 480) * 100, 1);
            return ($pct > 0) ? ($pct . '%') : 'nd';
        };

        // Колоните са изградени по $rec->data->periodDates
        if (!isset($rec->data->periodDates) || !is_array($rec->data->periodDates)) {
            return $row;
        }

        // За всяка дата от периода – попълваме колоните d01, d02, ...
        $i = 0;
        foreach ($rec->data->periodDates as $ymd) {
            $i++;
            $code = sprintf('d%02d', $i);

            if ($dRec->rowType === 'shift') {
                $val = trim((string) ($dRec->shiftsInPeriod[$ymd] ?? ''));
                $row->{$code} = ($val !== '') ? $val : 'nd';

            } elseif ($dRec->rowType === 'onsite') {
                $seconds = (int) ($dRec->onSiteTimeByDate[$ymd] ?? 0);
                $row->{$code} = $fmtHm($seconds);

            } else { // 'ops'
                $mins = (int) ($dRec->opsMinutesByDate[$ymd] ?? 0);
                $row->{$code} = $fmtPct($mins);
            }
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
        $Time = cls::get('type_Time');
        $Users = cls::get('type_Users'); $Enum = cls::get('type_Enum', array('options' => array('selfPrice' => 'политика"Себестойност"', 'catalog' => 'политика"Каталог"', 'accPrice' => 'Счетоводна')));

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

        if (isset($data->rec->maxTimeWaiting)) {
            $fieldTpl->append('<b>' . $Time->toVerbal($data->rec->maxTimeWaiting) . '</b>', 'maxTimeWaiting');
        }

        if (isset($data->rec->users)) {

            $fieldTpl->append('<b>' . $Users->toVerbal($data->rec->users) . '</b>', 'users');
        } else {
            $fieldTpl->append('<б>' . 'Всички' . '</б>', 'users');
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

    /**
     * Връща смяната за всеки служител по дни от периода.
     *
     * @param array $personsInGroups [personId => personName]
     * @param array $dates           ['Y-m-d', ...] (вкл. граници)
     * @param bool  $returnIds       true => връща id на смяната; false => име на смяната
     * @return array                 [personId][Y-m-d] => shiftId|shiftName|null
     */
    protected static function getPersonsShiftsInPeriod($personsInGroups, $dates, $returnIds = false)
    {
        $result = array();

        if (empty($personsInGroups) || empty($dates)) {
            return $result;
        }

        // Нормализиране на датите и сет за бърз достъп
        $normDates = array();
        foreach ($dates as $d) {
            $ymd = dt::verbal2mysql($d, false); // 'Y-m-d'
            $normDates[$ymd] = true;
        }

        // Инициализация: null за всички комбинации [personId][date]
        foreach ($personsInGroups as $personId => $personName) {
            foreach ($dates as $ymd ) {
                $result[$personId][$ymd] = null;
            }
        }

        // За всеки човек и всяка дата намираме смяната чрез hr_Shifts::getShift()
        foreach ($personsInGroups as $personId => $personName) {
            foreach ($dates as $ymd) {


                // Взимаме смяната (id) за деня
                $shiftId = hr_Shifts::getShift($ymd, $personId);

                if (!$shiftId) {
                    // няма смяна за този ден
                    $result[$personId][$ymd] = null;
                    continue;
                }

                // В зависимост от флага - id или име на смяната
                $result[$personId][$ymd] = $returnIds
                    ? $shiftId
                    : hr_Shifts::getVerbal($shiftId, 'name');
            }
        }

        return $result;
    }

    /**
     * Връща времето на място (onSite) за всеки служител по дни от периода.
     *
     * @param array $personsInGroups [personId => personName]
     * @param array $dates           ['Y-m-d', ...] (вкл. граници)
     * @return array                 [personId][Y-m-d] => onSiteSeconds (int)
     */
    protected static function getPersonsTimeInPeriod($personsInGroups, $dates)
    {
        $result = array();

        if (empty($personsInGroups) || empty($dates)) {
            return $result;
        }

        // Нормализиране на датите и подготовка на бърз lookup
        $normDates = array();
        $minDate = null; $maxDate = null;
        foreach ($dates as $d) {
            $ymd = dt::verbal2mysql($d, false);   // 'Y-m-d'
            $normDates[$ymd] = true;
            if ($minDate === null || $ymd < $minDate) $minDate = $ymd;
            if ($maxDate === null || $ymd > $maxDate) $maxDate = $ymd;
        }

        $personIds = array_keys($personsInGroups);

        // Инициализация: 0 секунди за всички комбинации
        foreach ($personIds as $pId) {
            foreach ($normDates as $ymd => $_) {
                $result[$pId][$ymd] = 0;
            }
        }

        // Четене от wtime_Summary
        $q = wtime_Summary::getQuery();

        $q->in('personId', $personIds);
        $q->where("#date >= '{$minDate}' AND #date <= '{$maxDate}'");
        $q->show('personId,date,onSiteTime');  // onSiteTime е в МИНУТИ

        while ($rec = $q->fetch()) {
            $pId = (int)$rec->personId;
            $ymd = $rec->date;

            // Филтър само за подадените конкретни дни (ако интервалът е по-широк)
            if (!isset($normDates[$ymd])) continue;

            // Превръщаме в секунди, за да се ползва директно от твоето форматиране
            $minutes = (int)$rec->onSiteTime;
            $seconds = $minutes * 60;

            $result[$pId][$ymd] = $seconds;
        }

        return $result;
    }

    /**
     * Добавя отпуските за всеки служител по дни от периода.
     *
     * @param array $personsInGroups [personId => personName]
     * @param array $dates           ['Y-m-d', ...] (вкл. граници)
     * @return array
     */
    protected static function getPersonsLeavesDaysInPeriod($personsInGroups, $dates, $personsShiftsInPeriod)
    {

        // За всеки човек и всяка дата проверяваме дали е бил отпуск на датата
        foreach ($personsInGroups as $personId => $personName) {
            foreach ($dates as $ymd) {

                // Взимаме смяната (id) за деня
                $isLeaveDay = hr_Leaves::getLeaveDay($ymd, $personId);

                if ($isLeaveDay) {
                    $personsShiftsInPeriod[$personId][$ymd] = 'Отп.';
                }
            }
        }

        return $personsShiftsInPeriod;

    }

    /**
     * Добавя болничните за всеки служител по дни от периода.
     *
     * @param array $personsInGroups [personId => personName]
     * @param array $dates           ['Y-m-d', ...] (вкл. граници)
     * @return array
     */
    protected static function getPersonsSickDaysInPeriod($personsInGroups, $dates, $personsShiftsInPeriod)
    {

        // За всеки човек и всяка дата проверяваме да ли е бил болничен на датата
        foreach ($personsInGroups as $personId => $personName) {
            foreach ($dates as $ymd) {

                // Взимаме смяната (id) за деня
                $isSickDay = hr_Sickdays::getSickDay($ymd, $personId);

                if ($isSickDay) {
                    $personsShiftsInPeriod[$personId][$ymd] = 'Б';
                }
            }
        }

        return $personsShiftsInPeriod;

    }


    /**
     * Връща personId-тата и имената на всички лица, които са в подадените CRM групи.
     *
     * @param string $crmGroupKeylist keylist от crm_Groups (една или повече групи)
     * @param bool   $activeOnly       само активните
     * @return int[]                  [personId => personId]
     */
    protected static function getPersonIdsFromCrmGroups($crmGroupKeylist, $activeOnly = true)
    {
        if (empty($crmGroupKeylist)) {
            return array();
        }

        $groupIds = keylist::toArray($crmGroupKeylist);
        if (empty($groupIds)) {
            return array();
        }

        // 1) Всички лица, чието #groupList съдържа поне една от групите
        $q = crm_Persons::getQuery();

        $q->show('id,groupList,name');

        if ($activeOnly) {
            $q->where("#state ='active'");
        }else{
            $q->where("#state !='rejected'");
        }

        $ors = array();

        foreach ($groupIds as $gId) {
            $gId = (int)$gId;
            $ors[] = "LOCATE('|{$gId}|', CONCAT('|', #groupList, '|'))";
        }

        $q->where('(' . implode(' OR ', $ors) . ')');

        $personIds = array();
        while ($p = $q->fetch()) {
            $personIds[$p->id] = $p->name;
        }
        if (empty($personIds)) {
            return array();
        }

        return $personIds; // [personId => personId]
    }
}