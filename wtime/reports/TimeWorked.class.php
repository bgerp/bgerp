<?php


/**
 * Мениджър на отчети Отчитане на работното време
 *
 * @category  bgerp
 * @package   wtime
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2026 Experta OOD
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
     * Дефолтен етикет на таба за графиката
     */
    protected $chartTabCaption = 'Обобщение';


    /**
     * Активиране на таб с графика
     */
    protected $enableChartTab = true;
    

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
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;

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
        $series = Mode::get('series');
        if ($export === false) {

            $fld->FLD('personName', 'varchar', 'caption=Служител,tdClass=leftCol');
            $fld->FLD('metric', 'varchar', 'caption=Показател,tdClass=center');

            for ($i = 0; $i < countR($rec->data->periodDates); $i++) {
                $d = dt::addDays($i, $rec->data->periodDates[1]); // Y-m-d
                $code = sprintf('d%02d', $i + 1);
                $caption = dt::mysql2verbal($d, 'd.m');
                $fld->FLD($code, 'varchar', "caption={$caption},tdClass=center,smartCenter");
            }

        } elseif($series == 'summary') {
            $fld->FLD('personName', 'varchar','caption=Служител,tdClass=leftCol');
            $fld->FLD('workingDays', 'int','caption=Работни дни');
            $fld->FLD('restDays', 'int','caption=Почивни дни');
            $fld->FLD('paidLeave', 'int','caption=Отпуска');
            $fld->FLD('sickDays', 'int','caption=Болнични');
            $fld->FLD('tripDays', 'int','caption=Командировка');
            $fld->FLD('homeOfficeDays', 'int','caption=Хоумофис');
            $fld->FLD('hours', 'int','caption=Часове');
        } else{
            $fld->FLD('personName', 'varchar', 'caption=Служител,tdClass=leftCol');
            $fld->FLD('date', 'date', 'caption=Дата,tdClass=center');
            $fld->FLD('shift', 'varchar', 'caption=Смяна,tdClass=center');
            $fld->FLD('time', 'varchar', 'caption=Време,tdClass=center');
            $fld->FLD('percent', 'percent', 'caption=Процент,tdClass=center');
        }

        return $fld;
    }


    /**
     * Връща редовете, които ще се експортират от справката
     *
     * @param stdClass       $rec         - запис
     * @param core_BaseClass $ExportClass - клас за експорт (@see export_ExportTypeIntf)
     *
     * @return array                      - записите за експорт
     */
    protected function getRecsForExport($rec, $ExportClass)
    {
        $recsToExport = array();
        $series = Mode::get('series');

        if($series == 'detailed'){
            if (empty($rec->data->recs) || empty($rec->data->periodDates)) {
            return $recsToExport;
            }
        
            // Групиране на записите по personId
            $recsByPerson = array();
            foreach ($rec->data->recs as $dataRec) {
                $personId = $dataRec->personId;
                if (!isset($recsByPerson[$personId])) {
                    $recsByPerson[$personId] = array();
                }
                $recsByPerson[$personId][$dataRec->rowType] = $dataRec;
            }
            
            // За всеки човек и всяка дата, създава експортен запис
            foreach ($recsByPerson as $personId => $typeRecs) {
                $shiftRec = $typeRecs['shift'] ?? null;
                $onsiteRec = $typeRecs['onsite'] ?? null;
                $opsRec = $typeRecs['ops'] ?? null;
                
                if (!$shiftRec) continue;
                
                $personName = $shiftRec->personName; 
                
                foreach ($rec->data->periodDates as $ymd) {
                    $exportRec = new stdClass();
                    $exportRec->personId = $personId;
                    $exportRec->personName = $personName;
                    $exportRec->date = $ymd;
                    $exportRec->shift = $shiftRec->shiftsInPeriod[$ymd] ?? null;
                    $exportRec->time = $onsiteRec->onSiteTimeByDate[$ymd] ?? 0;
                    $exportRec->percent = $opsRec->opsMinutesByDate[$ymd] ?? 0;
                    $recsToExport[] = $exportRec;
                }
            }
        // при експорт на общата справка 
        } elseif ($series == 'summary') {
            $summary = $this->getSummary($rec);
            foreach ($summary as $fldId => $dRec){
                $dRec->hours = round($dRec->workingMinutes / 3600);
                if($fldId === 0){
                    $dRec->personName = 'Общо';
                }
            }
            $recsToExport = $summary;
        }
        
        return $recsToExport;
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
        // Форматиране на времето като часове вместо като timestamp    
        if (empty($dRec->time)) {
            $dRec->time = null;
        } else {
            $dRec->time = core_type::getByName('time(noSmart, uom=hours)')->toVerbal($dRec->time);
        }
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
        $singleUrl = crm_Persons::getSingleUrlArray($dRec->personId);
        $personNameTpl = ht::createLink($dRec->personName ,$singleUrl);
        $row->personName = "<div style='text-align:center;font-weight:600;'>{$personNameTpl}</div>";

        // Показател
        $metricByType = ['shift' => 'смяна', 'onsite' => 'време', 'ops' => '%'];
        $row->metric = $metricByType[$dRec->rowType] ?? '';

        // Нормализира към МИНУТИ; приема сек/ "mm:ss"/"hh:mm"/"hh:mm:ss"
        $toMinutes = function ($val) {
            if ($val === null || $val === '' || $val === 0) return 0;
            
            // Чисто число -> приемаме секунди (както идват от getPersonsTimeInPeriod)
            if (is_int($val) || (is_string($val) && ctype_digit($val))) {
                return (int)floor(((int)$val)); 
            }

            $s = trim((string)$val);
            if (strpos($s, ':') === false) {
                // fallback: опит за секунди
                if (is_numeric($s)) return (int)floor(((int)$s));
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
        function ($minutes) {
            if (!is_numeric($minutes) || $minutes <= 0) return '-';
            $pct = round(($minutes / 480) * 100, 1);
            
            return ($pct > 0) ? ($pct . '%') : '-';
        };

        // Попълване на дневните колони
        if (!isset($rec->data->periodDates) || !is_array($rec->data->periodDates)) {
            return $row;
        }
        // Подготовка на кеш за отпуски/болнични/командировки/хоумофис
        if (!isset($rec->data->dayFlags)) {
            $personsInGroups = array();
            if (isset($rec->data->persons) && is_array($rec->data->persons)) {
                $personsInGroups = $rec->data->persons;
            } elseif (isset($rec->data->recs) && is_array($rec->data->recs)) {
                foreach ($rec->data->recs as $r) {
                    $personsInGroups[$r->personId] = true;
                }
            }

            $periodDates = $rec->data->periodDates;
            $rec->data->dayFlags = array(
                'leave' => self::getPersonDayFlags($personsInGroups, $periodDates, 'hr_Leaves', 'leaveFrom', 'leaveTo'),
                'sick'  => self::getPersonDayFlags($personsInGroups, $periodDates, 'hr_Sickdays', 'startDate', 'toDate'),
                'trip'  => self::getPersonDayFlags($personsInGroups, $periodDates, 'hr_Trips', 'startDate', 'toDate'),
                'home'  => self::getPersonDayFlags($personsInGroups, $periodDates, 'hr_HomeOffice', 'startDate', 'toDate'),
            );
        }

        $i = 0;
        foreach ($rec->data->periodDates as $ymd) {
            $i++;
            $code = sprintf('d%02d', $i);
           if ($dRec->rowType === 'shift') {
                $val = trim((string)($dRec->shiftsInPeriod[$ymd] ?? ''));
                if ($val === '') {
                    $row->{$code} = '-';
                }
                // Не правим многократни DB заявки тук — използваме предварително заредения кеш
                $isLeaveDay = isset($rec->data->dayFlags['leave'][$dRec->personId][$ymd]);
                $isSickDay  = isset($rec->data->dayFlags['sick'][$dRec->personId][$ymd]);
                $isTripDay  = isset($rec->data->dayFlags['trip'][$dRec->personId][$ymd]);
                $isHomeOfficeDay = isset($rec->data->dayFlags['home'][$dRec->personId][$ymd]);

                if ($isLeaveDay || $isSickDay || $isTripDay || $isHomeOfficeDay) {
                    $row->{$code} = $val;
                } else {
                    $colorHex = null;
                    if ($shiftId = hr_Shifts::getShift($ymd, $dRec->personId)) {
                        $colorHex = hr_Shifts::fetchField($shiftId, 'color');
                    }
                    if ($colorHex) {
                        $attr = array('style' => "background-color:{$colorHex}; width:100%; padding:2px 3px");
                        $row->{$code} = ht::createElement('span', $attr, $val);
                    } else {
                        $row->{$code} = $val;
                    }
                }
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
        $personsShifts = array();
        
        // За всеки човек и всяка дата намираме смяната чрез hr_Shifts::getShift()
        foreach ($personsInGroups as $personId => $personName) {
            $scheduleId = planning_Hr::getSchedule($personId);

            foreach ($dates as $ymd) {
                // Взимаме смяната (id) за деня
                $shiftId = hr_Shifts::getShift($ymd, $personId, $scheduleId);
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
     * Зарежда бърза матрица с дните за активен запис от таблица с периоди.
     *
     * @param array  $personsInGroups [personId => personName]
     * @param array  $dates           ['Y-m-d', ...]
     * @param string $mvcClass        MVC class name (hr_Leaves, hr_Sickdays, hr_Trips, hr_HomeOffice)
     * @param string $fromField       start date field name
     * @param string $toField         end date field name
     * @return array                  [personId][Y-m-d] => true
     */
    protected static function getPersonDayFlags($personsInGroups, $dates, $mvcClass, $fromField, $toField)
    {
        $result = array();

        if (empty($personsInGroups) || empty($dates)) {
            return $result;
        }

        $personIds = array_keys($personsInGroups);
        $normDates = array();
        $minDate = null;
        $maxDate = null;
        foreach ($dates as $d) {
            $ymd = dt::verbal2mysql($d, false);
            $normDates[$ymd] = true;
            if ($minDate === null || $ymd < $minDate) {
                $minDate = $ymd;
            }
            if ($maxDate === null || $ymd > $maxDate) {
                $maxDate = $ymd;
            }
        }

        $q = $mvcClass::getQuery();
        $q->in('personId', $personIds);
        $q->where("#{$toField} >= '{$minDate}'");
        $q->where("#{$fromField} <= '{$maxDate}'");
        $q->where("#state = 'active'");
        $q->show("personId,{$fromField},{$toField}");

        while ($rec = $q->fetch()) {
            $personId = (int)$rec->personId;
            $start = dt::verbal2mysql($rec->{$fromField}, false);
            $end = dt::verbal2mysql($rec->{$toField}, false);

            for ($d = $start; ; $d = dt::addDays(1, $d, false)) {
                if (isset($normDates[$d])) {
                    $result[$personId][$d] = true;
                }
                if ($d >= $end) {
                    break;
                }
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

            $result[$pId][$ymd] = (int)$rec->onSiteTime;
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
        $leaveDays = self::getPersonDayFlags($personsInGroups, $dates, 'hr_Leaves', 'leaveFrom', 'leaveTo');

        foreach ($leaveDays as $personId => $days) {
            foreach ($days as $ymd => $_) {
                $personsShiftsInPeriod[$personId][$ymd] = 'Отп.';
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
        $sickDays = self::getPersonDayFlags($personsInGroups, $dates, 'hr_Sickdays', 'startDate', 'toDate');

        foreach ($sickDays as $personId => $days) {
            foreach ($days as $ymd => $_) {
                $personsShiftsInPeriod[$personId][$ymd] = 'Б';
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
        $tripDays = self::getPersonDayFlags($personsInGroups, $dates, 'hr_Trips', 'startDate', 'toDate');

        foreach ($tripDays as $personId => $days) {
            foreach ($days as $ymd => $_) {
                $personsShiftsInPeriod[$personId][$ymd] = 'К';
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
        $homeOfficeDays = self::getPersonDayFlags($personsInGroups, $dates, 'hr_HomeOffice', 'startDate', 'toDate');

        foreach ($homeOfficeDays as $personId => $days) {
            foreach ($days as $ymd => $_) {
                $personsShiftsInPeriod[$personId][$ymd] = 'Х';
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

        $q->where(array("#createdOn >= '{$fromStart}' AND #createdOn <= '{$toEnd}'"));

        // 3) Филтър: поне един employee да е в $personsInGroups
        $ors = array();
        foreach (array_keys($personsInGroups) as $pId) {
            $pId = (int)$pId;
            $ors[] = "LOCATE('|{$pId}|', CONCAT('|', #employees, '|'))";
        }
        $q->where($ors ? '(' . implode(' OR ', $ors) . ')' : '1=0');

        // 4) Акумулация по ключ "<personId>|<Y-m-d>"
        $qArr = $q->fetchAll();
        // Подготовка на данни преди цикъла

        //Извличане и индексиране на Задачите
        $taskIds = arr::extractValuesFromArray($qArr, 'taskId');
        $taskQuery = planning_Tasks::getQuery();
        $taskQuery->in('id', $taskIds);
        $taskQuery->show('id,originId,isFinal,productId,measureId,indPackagingId,labelPackagingId,indTimeAllocation,quantityInPack,labelQuantityInPack');
        $tasks = $taskQuery->fetchAll();
        
        $originIds = $jobProductMap = $uomMap = $measureIds = [];
        $originIds = arr::extractValuesFromArray($tasks, 'originId');
        
        // Извличане на Заданията
        $jobsQuery = planning_Jobs::getQuery();
        if(countR($originIds)){
            $jobsQuery->in('containerId', $originIds);
            $jobsQuery->show('containerId,productId');
        } else{
            $jobsQuery->where('1 = 2');
        }
        $jobsList = $jobsQuery->fetchAll();
        foreach ($jobsList as $jRec) {
                $jobProductMap[$jRec->containerId] = $jRec->productId;
        }
        
        // Извличане на мерните единици
        $measureIds = arr::extractValuesFromArray($tasks, 'measureId');

        if (!empty($measureIds)) {
            $uomQuery = cat_UoM::getQuery();
            $uomQuery->in('id', $measureIds);
            $uomQuery->show('id,type');
            $uoms = $uomQuery->fetchAll();
            foreach ($uoms as $uom) {
                $uomMap[$uom->id] = $uom->type;
            }
        }
        $arr = array();
        foreach($qArr as $id => $qRec) {
            // Ако задачата липсва в базата данни, пропускаме записа
            if (!isset($tasks[$qRec->taskId])) continue;

            $currentTask = $tasks[$qRec->taskId];
            $quantity = $qRec->quantity;

            if (in_array($qRec->type, array('production', 'scrap'))) {

                // Взимаме продуктовото ID на заданието от масив
                $jobProductId = $jobProductMap[$currentTask->originId] ?? null;

                // Ако артикула е артикула от заданието и операцията е финална или артикула е този от операцията за междинен етап
                if (($tasks[$qRec->taskId]->isFinal == 'yes' && $qRec->productId == $jobProductId) || $qRec->productId == $tasks[$qRec->taskId]->productId) {
                    
                    // Проверка дали мерната единица е стандартна (uom)
                    $measureType = isset($uomMap[$currentTask->measureId]) ? $uomMap[$currentTask->measureId] : null;
                    $isMeasureUom = (isset($currentTask->measureId) && $measureType == 'uom');
                    if ($isMeasureUom) {
                        if ($tasks[$qRec->taskId]->indPackagingId == $tasks[$qRec->taskId]->measureId) {
                            $quantity /= $tasks[$qRec->taskId]->quantityInPack;
                        }
                    }
                    if ($tasks[$qRec->taskId]->measureId != $tasks[$qRec->taskId]->indPackagingId) {
                        if (!empty($currentTask->labelQuantityInPack)) {
                            $indQuantityInPack = $currentTask->labelQuantityInPack;
                            if ($isMeasureUom) {
                                $indQuantityInPack = $indQuantityInPack * $currentTask->quantityInPack;
                            }
                            $quantity = ($quantity / $indQuantityInPack);
                        } elseif ($indQuantityInPack = cat_products_Packagings::getPack($qRec->productId, $tasks[$qRec->taskId]->indPackagingId, 'quantity')) {
                            $quantity = ($quantity / $indQuantityInPack);
                        }
                    }
                }
            }
            // Разпределяне на изработената норма поравно между всички служители в екипа
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


    /**
     * Рендиране на графиката
     *
     * @param stdCLass $rec
     * @param stdCLass $data
     * @return core_ET $tpl
     */
    protected function renderChart($rec, &$data)
    {
        $fields = array('workingDays' => 'Работни дни', 'restDays' => 'Почивни дни', 'paidLeave' => 'Отпуска', 'sickDays' => 'Болнични', 'tripDays' => 'Командировка', 'homeOfficeDays' => 'Хоумофис', 'hours' => 'Часове');
        $summary = $this->getSummary($rec);

        $fieldset = new core_FieldSet();
        $fieldset->FLD('userId', 'varchar');
        foreach ($fields as $key => $fldName) {
            $fieldset->FLD($key, 'int', 'smartCenter');
        }
        $fields = array('userId' => 'Служител') + $fields;
        $table = cls::get('core_TableView', array('mvc' => $fieldset));

        $Time = cls::get('type_Time');
        $Time->params['noSmart'] = true;
        $Time->params['uom'] = 'hours';

        $rows = array();

        foreach ($summary as $personId => $rowData) {
            $isTotal = ($personId === 0);
            $row = new stdClass();

            foreach ($fields as $fKey => $fName) {
                if ($fKey == 'userId') {
                    if ($isTotal) {
                        $row->{$fKey} = "<b>" . tr('Общо') . "</b>";
                    } else {
                        $personUrl = crm_Persons::getSingleUrlArray($personId);
                        $row->{$fKey} = ht::createLink($rowData->personName, $personUrl);
                    }
                } elseif ($fKey == 'hours') {
                    $hours = ($rowData->workingMinutes > 0) ? $Time->toVerbal($rowData->workingMinutes) : '-';
                    $row->{$fKey} = $isTotal ? "<b>{$hours}</b>" : $hours;
                } else {
                    $val = $rowData->{$fKey};
                    $row->{$fKey} = $isTotal ? "<b>{$val}</b>" : $val;
                }
            }
            $rows[] = $row;
        }
        $table = $table->get($rows, $fields);

        return $table;
    }


    /**
     * Изгражда обобщените данни по служител и общо
     *
     * @param stdClass $rec
     * @return array $summary
     * 
     */
    protected function getSummary($rec)
    {
        $params = array('workingDays', 'restDays', 'paidLeave', 'sickDays', 'tripDays', 'homeOfficeDays', 'hours');
        $obj = new stdClass;
        foreach ($params as $paramFld) {
            $obj->{$paramFld} = 0;
        }

        $summary = array(0 => clone $obj);
        $personsInGroups = array();
        foreach ($rec->data->recs as $dataRec) {
            $personsInGroups[$dataRec->personId] = true;
        }

        $leaveDays = self::getPersonDayFlags($personsInGroups, $rec->data->periodDates, 'hr_Leaves', 'leaveFrom', 'leaveTo');
        $sickDays = self::getPersonDayFlags($personsInGroups, $rec->data->periodDates, 'hr_Sickdays', 'startDate', 'toDate');
        $tripDays = self::getPersonDayFlags($personsInGroups, $rec->data->periodDates, 'hr_Trips', 'startDate', 'toDate');
        $homeOfficeDays = self::getPersonDayFlags($personsInGroups, $rec->data->periodDates, 'hr_HomeOffice', 'startDate', 'toDate');

        foreach ($rec->data->recs as $dataRec) {
            $personId = $dataRec->personId;
            if (!array_key_exists($personId, $summary)) {
                $clone = clone $obj;
                $clone->personName = $dataRec->personName;
                $summary[$personId] = $clone;
            }

            if ($dataRec->rowType !== 'shift') {
                continue;
            }

            foreach ($rec->data->periodDates as $ymd) {
                $shift = trim((string)($dataRec->shiftsInPeriod[$ymd] ?? ''));
                $isLeaveDay = isset($leaveDays[$personId][$ymd]);
                $isSickDay = isset($sickDays[$personId][$ymd]);
                $isTripDay = isset($tripDays[$personId][$ymd]);
                $isHomeOfficeDay = isset($homeOfficeDays[$personId][$ymd]);

                if ($isLeaveDay) {
                    $summary[$personId]->paidLeave++;
                    $summary[0]->paidLeave++;
                } elseif ($isSickDay) {
                    $summary[$personId]->sickDays++;
                    $summary[0]->sickDays++;
                } elseif ($isTripDay) {
                    $summary[$personId]->tripDays++;
                    $summary[0]->tripDays++;
                } elseif ($isHomeOfficeDay) {
                    $summary[$personId]->homeOfficeDays++;
                    $summary[0]->homeOfficeDays++;
                } elseif ($shift === '' || $shift === '-') {
                    $summary[$personId]->restDays++;
                    $summary[0]->restDays++;
                } else {
                    $summary[$personId]->workingDays++;
                    $summary[0]->workingDays++;
                }
            }
        }
        foreach ($rec->data->recs as $dataRec) {
            if (!isset($summary[$dataRec->personId])) {
                continue;
            }
            if ($dataRec->rowType === 'onsite') {
                foreach ($dataRec->onSiteTimeByDate as $minutes) {
                    $summary[$dataRec->personId]->workingMinutes += (int)$minutes;
                    $summary[0]->workingMinutes += (int)$minutes;
                }
            }
            if ($dataRec->rowType === 'ops') {
                foreach ($dataRec->opsMinutesByDate as $minutes) {
                    $summary[$dataRec->personId]->taskMinutes += (int)$minutes;
                    $summary[0]->taskMinutes += (int)$minutes;
                }
            }
        }

        // Преместване на общото накрая
        $totalData = $summary[0];
        unset($summary[0]);
        $summary[0] = $totalData;

        return $summary;
    }

    
    /**
     *  Връща сериите за експорт
     * 
     * @param stdClass $rec
     * @return array
     */
    public function getSeriesForExport($rec){
        
        return array('detailed' => 'Детайлно', 'summary' => 'Обобщено');
    }
}