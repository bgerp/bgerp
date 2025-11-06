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
    protected $sortableListFields;


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
    protected $groupByField = 'personName';


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
        $fieldset->FLD('crmGroup', 'keylist(mvc=crm_Groups,select=name)', 'caption=Група служители,placeholder=Избери група,mandatory,after=periods,single=none');

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

        $currentPeriod = acc_Periods::fetchByDate(dt::today());
        if ($currentPeriod) {
            $form->setDefault('periods', $currentPeriod->id);
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

        // Нужни са избрани групи и период (acc_Periods)
        if (empty($rec->crmGroup) || empty($rec->periods)) {
            return $recs;
        }

        // 1) Служителите от групите
        $personsInGroups = crm_Persons::getPersonIdsFromCrmGroups($rec->crmGroup, true);
        if (empty($personsInGroups)) {
            return $recs;
        }

        // 2) Дните в периода (включително)
        $perRec = acc_Periods::fetch($rec->periods);
        if (!$perRec) return $recs;

        $from = isset($perRec->start) ? $perRec->start : (isset($perRec->from) ? $perRec->from : null);
        $to = isset($perRec->end) ? $perRec->end : (isset($perRec->to) ? $perRec->to : null);
        if (!$from || !$to) return $recs;

        $from = dt::verbal2mysql($from, false);
        $to = dt::verbal2mysql($to, false);

        $dates = array();
        $k = 0;
        for ($d = $from; ; $d = dt::addDays(1, $d, false)) {
            $k++;
            $dates[$k] = $d;      // пазим поредност + Y-m-d
            if ($d >= $to) break; // включително
        }

        if (is_object($data)) {
            $data->periodDates = $dates;          // списък с дати за периода
            $data->persons = $personsInGroups;  // [personId => name]
        }

        // Намираме смените за всеки ден
        $personsShiftsInPeriod = self::getPersonsShiftsInPeriod($personsInGroups, $dates);  // [pId][Y-m-d] => shiftName|null

        //Отчитане на хоумофис дните
        $personsShiftsInPeriod = self::getPersonsHomeOfficeDaysInPeriod($personsInGroups, $dates, $personsShiftsInPeriod);

        //Отчитане на командировките
        $personsShiftsInPeriod = self::getPersonsTripDaysInPeriod($personsInGroups, $dates, $personsShiftsInPeriod);

        //Отчитане на отпуските
        $personsShiftsInPeriod = self::getPersonsLeavesDaysInPeriod($personsInGroups, $dates, $personsShiftsInPeriod);

        //Отчитане на болничните
        $personsShiftsInPeriod = self::getPersonsSickDaysInPeriod($personsInGroups, $dates, $personsShiftsInPeriod);

        //Изчисляване на времето за всеки ден
        $personsTimeInPeriod = self::getPersonsTimeInPeriod($personsInGroups, $dates);    // [pId][Y-m-d] => seconds

        //Изчисляване на заработките
        $personsProgressInPeriod = self::getProgressInPeriod($personsInGroups, $dates);


        // 4) По 3 реда на човек: 'shift', 'onsite', 'ops'
        foreach ($personsInGroups as $pId => $pName) {

            // a) ред „смяна“
            $recs[] = (object)array(
                'rowType' => 'shift',
                'personId' => $pId,
                'personName' => $pName,
                'shiftsInPeriod' => isset($personsShiftsInPeriod[$pId]) ? $personsShiftsInPeriod[$pId] : array(),
            );

            // b) ред „време“ (секунди)
            $recs[] = (object)array(
                'rowType' => 'onsite',
                'personId' => $pId,
                'personName' => $pName,
                'onSiteTimeByDate' => isset($personsTimeInPeriod[$pId]) ? $personsTimeInPeriod[$pId] : array(),
            );

            // c) ред „%“ (минутите ще ги превърнем в % при вербализация)
            //    за удобство пазим минутите (сек/60)
            $opsMinutes = array();
            if (!empty($personsTimeInPeriod[$pId])) {
                foreach ($personsTimeInPeriod[$pId] as $ymd => $sec) {
                    $opsMinutes[$ymd] = (int)round($sec / 60);
                }
            }
            $recs[] = (object)array(
                'rowType' => 'ops',
                'personId' => $pId,
                'personName' => $pName,
                'opsMinutesByDate' => isset($personsProgressInPeriod[$pId]) ? $personsProgressInPeriod[$pId] : array(),
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

            $fld->FLD('personName', 'varchar', 'caption=Потребител,tdClass=leftCol');
            $fld->FLD('metric', 'varchar', 'caption=Показател,tdClass=center');

            for ($i = 0; $i < countR($rec->data->periodDates); $i++) {
                $d = dt::addDays($i, $rec->data->periodDates[1]); // Y-m-d
                $code = sprintf('d%02d', $i + 1);
                $caption = dt::mysql2verbal($d, 'd.m');
                $fld->FLD($code, 'varchar', "caption={$caption},tdClass=center,smartCenter");
            }

        } else {


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
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;

        // type_Time за готово форматиране като часове:минути
        $Time = cls::get('type_Time');
        $Time->params['noSmart'] = true;   // да не „умничи“
        $Time->params['uom'] = 'hours';    // входът е в МИНУТИ, изходът е ч:мм

        $row = new stdClass();

        $row->personName = "<div style='text-align:center;font-weight:600;'>{$dRec->personName}</div>";

        // Показател
        $metricByType = ['shift' => 'смяна', 'onsite' => 'време', 'ops' => '%'];
        $row->metric = $metricByType[$dRec->rowType] ?? '';


        // Нормализира към МИНУТИ; приема сек/ "mm:ss"/"hh:mm"/"hh:mm:ss"
        $toMinutes = function ($val) {
            if ($val === null || $val === '' || $val === 0) return 0;

            // Чисто число -> приемаме секунди (както идват от getPersonsTimeInPeriod)
            if (is_int($val) || (is_string($val) && ctype_digit($val))) {
                return (int)floor(((int)$val) / 60);
            }

            $s = trim((string)$val);
            if (strpos($s, ':') === false) {
                // fallback: опит за секунди
                if (is_numeric($s)) return (int)floor(((int)$s) / 60);
                return 0;
            }

            $p = array_map('intval', explode(':', $s));
            if (count($p) === 3) {               // hh:mm:ss
                return $p[0] * 60 + $p[1];       // игнорираме секундите
            } elseif (count($p) === 2) {         // mm:ss ИЛИ hh:mm
                $a = $p[0];
                $b = $p[1];
                // Хеуристика: ако първата част е >= 60 => mm:ss, иначе hh:mm
                return ($a >= 60) ? $a : ($a * 60 + $b);
            }

            return 0;
        };

        // Форматирания за други редове
        $fmtPct = function ($minutes) {
            if (!is_numeric($minutes) || $minutes <= 0) return '-';
            $pct = round(($minutes / 480) * 100, 1);
            return ($pct > 0) ? ($pct . '%') : '-';
        };

        // Попълване на дневните колони
        if (!isset($rec->data->periodDates) || !is_array($rec->data->periodDates)) {
            return $row;
        }

        $i = 0;
        foreach ($rec->data->periodDates as $ymd) {
            $i++;
            $code = sprintf('d%02d', $i);

            if ($dRec->rowType === 'shift') {
                $val = trim((string)($dRec->shiftsInPeriod[$ymd] ?? ''));
                $row->{$code} = ($val !== '') ? $val : '-';

            } elseif ($dRec->rowType === 'onsite') {
                // Нормализираме към МИНУТИ и използваме type_Time::toVerbal()
                $raw = $dRec->onSiteTimeByDate[$ymd] ?? 0;     // може да е сек или "mm:ss"
                $mins = $toMinutes($raw);
                $row->{$code} = ($mins > 0) ? $Time->toVerbal($mins) : '-';

            } else { // 'Прогрес'
                $mins = ($dRec->opsMinutesByDate[$ymd] / 60) / 480 * 100;
                $row->{$code} = $Double->toVerbal($mins);
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
        $Users = cls::get('type_Users');
        $Enum = cls::get('type_Enum', array('options' => array('selfPrice' => 'политика"Себестойност"', 'catalog' => 'политика"Каталог"', 'accPrice' => 'Счетоводна')));

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
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'users');
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
     * @param array $dates ['Y-m-d', ...] (вкл. граници)
     * @param bool $returnIds true => връща id на смяната; false => име на смяната
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
     * @param array $dates ['Y-m-d', ...] (вкл. граници)
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
        $minDate = null;
        $maxDate = null;
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
     * @param array $dates ['Y-m-d', ...] (вкл. граници)
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
     * @param array $dates ['Y-m-d', ...] (вкл. граници)
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
     * Добавя командировките за всеки служител по дни от периода.
     *
     * @param array $personsInGroups [personId => personName]
     * @param array $dates ['Y-m-d', ...] (вкл. граници)
     * @return array
     */
    protected static function getPersonsTripDaysInPeriod($personsInGroups, $dates, $personsShiftsInPeriod)
    {

        // За всеки човек и всяка дата проверяваме да ли е бил болничен на датата
        foreach ($personsInGroups as $personId => $personName) {
            foreach ($dates as $ymd) {

                // Взимаме смяната (id) за деня
                $isTripDay = hr_Trips::getTripDay($ymd, $personId);

                if ($isTripDay) {
                    $personsShiftsInPeriod[$personId][$ymd] = 'К';
                }
            }
        }

        return $personsShiftsInPeriod;

    }

    /**
     * Добавя хоумофис дните за всеки служител по дни от периода.
     *
     * @param array $personsInGroups [personId => personName]
     * @param array $dates ['Y-m-d', ...] (вкл. граници)
     * @return array
     */
    protected static function getPersonsHomeOfficeDaysInPeriod($personsInGroups, $dates, $personsShiftsInPeriod)
    {

        // За всеки човек и всяка дата проверяваме да ли е бил болничен на датата
        foreach ($personsInGroups as $personId => $personName) {
            foreach ($dates as $ymd) {

                // Взимаме смяната (id) за деня
                $isHomeOfficeDay = hr_HomeOffice::getHomeOfficeDay($ymd, $personId);

                if ($isHomeOfficeDay) {
                    $personsShiftsInPeriod[$personId][$ymd] = 'Х';
                }
            }
        }

        return $personsShiftsInPeriod;

    }

    /**
     * Намира отработените минути ден по ден за подадения период.
     *
     * @param array $personsInGroups [personId => personName]
     * @param array $dates ['Y-m-d', ...] (вкл. граници)
     * @return array                 [personId][Y-m-d] => минути | null
     */
    protected static function getProgressInPeriod($personsInGroups, $dates)
    {
        if (empty($dates)) {
            return array();
        }

        // 1) Период от първата до последната дата (вкл.)
        $from = dt::verbal2mysql(reset($dates), false);
        $to = dt::verbal2mysql(end($dates), false);
        $fromStart = $from . ' 00:00:00';
        $toEnd = $to . ' 23:59:59';

        // 2) Заявка: само нужните полета, само в периода
        $q = planning_ProductionTaskDetails::getQuery();
        // $q->show('createdOn,employees,norm');
        $q->where(array("#createdOn >= '[#1#]' AND #createdOn <= '[#2#]'", $fromStart, $toEnd));

        // 3) Филтър: поне един employee да е в $personsInGroups
        $ors = array();
        foreach (array_keys($personsInGroups) as $pid) {
            $pid = (int)$pid;
            $ors[] = "LOCATE('|{$pid}|', CONCAT('|', #employees, '|'))";
        }
        $q->where($ors ? '(' . implode(' OR ', $ors) . ')' : '1=0');

        // 4) Акумулация по ключ "<personId>|<Y-m-d>"
        $arr = array();


        while ($qRec = $q->fetch()) {

            $quantity = $qRec->quantity;

            if (in_array($qRec->type, array('production', 'scrap'))) {
                $taskRec = planning_Tasks::fetch($qRec->taskId, 'originId,isFinal,productId,measureId,indPackagingId,labelPackagingId,indTimeAllocation,quantityInPack,labelQuantityInPack');
                $jobProductId = planning_Jobs::fetchField("#containerId = {$taskRec->originId}", 'productId');

                // Ако артикула е артикула от заданието и операцията е финална или артикула е този от операцията за междинен етап
                if (($taskRec->isFinal == 'yes' && $qRec->productId == $jobProductId) || $qRec->productId == $taskRec->productId) {

                    $isMeasureUom = (isset($taskRec->measureId) && cat_UoM::fetchField($taskRec->measureId, 'type') == 'uom');
                    if ($isMeasureUom) {
                        if ($taskRec->indPackagingId == $taskRec->measureId) {
                            $quantity /= $taskRec->quantityInPack;
                        }
                    }

                    if ($taskRec->measureId != $taskRec->indPackagingId) {
                        if (!empty($taskRec->labelQuantityInPack)) {
                            $indQuantityInPack = $taskRec->labelQuantityInPack;
                            if ($isMeasureUom) {
                                $indQuantityInPack = $indQuantityInPack * $taskRec->quantityInPack;
                            }
                            $quantity = ($quantity / $indQuantityInPack);
                        } elseif ($indQuantityInPack = cat_products_Packagings::getPack($qRec->productId, $taskRec->indPackagingId, 'quantity')) {
                            $quantity = ($quantity / $indQuantityInPack);
                        }
                    }
                }
            }

            $eArr = keylist::toArray($qRec->employees);
            if (!$eArr) continue;

            // Норма: първата част преди '|'
            $normParts = explode('|', $qRec->norm, 2);
            $norm = (int)($normParts[0] ?? 0);

            $empCount = countR($eArr);
            $perEmp = ($empCount > 0) ? ($norm * $quantity / $empCount) : 0;

            $ymd = dt::verbal2mysql($qRec->createdOn, false);

            foreach ($eArr as $employee) {
                $key = $employee . '|' . $ymd;
                $arr[$key] = ($arr[$key] ?? 0) + $perEmp; // избягва Notice при първо натрупване
            }
        }

        // 5) Матрица [personId][Y-m-d] => минути (или null, ако няма записи)
        $personsProgressInPeriod = array();
        foreach ($personsInGroups as $personId => $personName) {
            foreach ($dates as $ymd) {
                $key = $personId . '|' . $ymd; // $dates вече са 'Y-m-d'
                $personsProgressInPeriod[$personId][$ymd] = isset($arr[$key]) ? $arr[$key] : null;
            }
        }

        return $personsProgressInPeriod;
    }


    /**
     * Връща периода на справката - ако има такъв
     *
     * @param stdClass $rec
     * @return array
     *          ['from'] - начало на период
     *          ['to']   - край на период
     */
    protected function getPeriodRange($rec)
    {
        if (isset($rec->periods)) {
            $periodRec = acc_Periods::fetch($rec->periods);

            return array('from' => $periodRec->start, 'to' => $periodRec->end);
        }

        return array('from' => $rec->fromDate, 'to' => $rec->toDate);
    }
}