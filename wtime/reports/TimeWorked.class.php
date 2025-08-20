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
    protected $groupByField;


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

        // 1) Имена на служителите от групите (само активни)
        $namesByPerson = self::getPersonsNamesFromGroups($rec->crmGroup, true);
        if (empty($namesByPerson)) {
            return $recs;
        }

        // 2) Дните в периода (включително)
        $perRec = acc_Periods::fetch($rec->periods); // <-- без списък от полета
        if (!$perRec) {
            return $recs;
        }
        $from = isset($perRec->start) ? $perRec->start : (isset($perRec->from) ? $perRec->from : null);
        $to   = isset($perRec->end)   ? $perRec->end   : (isset($perRec->to)   ? $perRec->to   : null);
        if (!$from || !$to) {
            return $recs;
        }
        $from = dt::verbal2mysql($from, 'date');
        $to   = dt::verbal2mysql($to,   'date');

        $dates = array();
        for ($d = $from; ; $d = dt::addDays(1, $d, false)) {
            $dates[] = $d;
            if ($d >= $to) break;
        }

        if (is_object($data)) {
            $data->periodDates = $dates;          // списък с дати за периода
            $data->persons     = $namesByPerson;  // [personId => name]
        }

        // 3) Матрица [Y-m-d][personId] => име на смяна (или null)
        $matrix = self::getShiftsMatrixByGroupAndPeriod($rec->crmGroup, $rec->periods, /*returnIds=*/false);

        // 4) Строим записи по човек: days[date] = shiftName|null
        foreach ($namesByPerson as $pId => $name) {
            $days = array();
            foreach ($dates as $ymd) {
                $days[$ymd] = isset($matrix[$ymd][$pId]) ? $matrix[$ymd][$pId] : null;
            }
         //   bp($namesByPerson,$matrix,$days  );
            $recs[$pId] = (object)array(
                'pId'   => $pId,
                'pName' => $name,
                'days'  => $days,   // тук са смените по дни
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

        $fld->FLD('person', 'varchar', 'caption=Потребител,tdClass=leftCol');
        $fld->FLD('metric', 'varchar', 'caption=Показател,tdClass=center');

        $base = '2025-05-01';
        for ($i = 0; $i < 5; $i++) {
            $d = dt::addDays($i, $base, false); // Y-m-d
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

        // Името да стои само на първия ред (смени); центриране визуално
        if ($dRec->rowType === 'shift') {
            $row->person = "<div style='text-align:center;font-weight:600;'>{$dRec->userName}</div>";
        } else {
            $row->person = '';
        }

        // Показател
        $metricByType = ['shift' => 'смени', 'onsite' => 'часове', 'ops' => '%'];
        $row->metric = $metricByType[$dRec->rowType] ?? '';

        // 5-те дати
        $dates = [];
        $base = '2025-05-01';
        for ($i = 0; $i < 5; $i++) {
            $dates[] = dt::addDays($i, $base, false); // Y-m-d
        }

        // Форматиране
        $fmtHm = function ($seconds) {
            if (!is_numeric($seconds) || $seconds <= 0) return '';
            $minutes = (int) round($seconds / 60);
            $h = (int) floor($minutes / 60);
            $m = $minutes % 60;
            return sprintf('%d:%02d', $h, $m);
        };
        $fmtPct = function ($minutes) {
            if (!is_numeric($minutes) || $minutes < 0) return '';
            $pct = round(($minutes / 480) * 100, 1);
            return ($pct > 0) ? ($pct . '%') : '';
        };

        for ($i = 0; $i < 5; $i++) {
            $code = sprintf('d%02d', $i + 1);
            $ymd  = $dates[$i];

            $day = isset($dRec->days[$ymd]) ? (object)$dRec->days[$ymd]
                : (object)['shift' => null, 'onsiteSeconds' => null, 'opsMinutes' => null];

            $shift = (string)($day->shift ?? '');
            $ons   = (int)($day->onsiteSeconds ?? 0);
            $ops   = (int)($day->opsMinutes ?? 0);

            if ($dRec->rowType === 'shift') {
                $val = $shift ?: '';
            } elseif ($dRec->rowType === 'onsite') {
                $val = $fmtHm($ons);
            } else { // ops
                $val = $fmtPct($ops);
            }

            // Червено: ако НЕ е "П" и точно един от (часове, %) е попълнен,
            // и сме на ред "часове" или "%".
            $isRest = ($shift === 'П');
            $hasOn  = ($ons > 0);
            $hasOp  = ($ops > 0);
            $warn   = (!$isRest) && (($hasOn xor $hasOp)) && ($dRec->rowType !== 'shift');

            if ($warn && $val !== '') {
                $val = "<span style='color:#c00;font-weight:600'>{$val}</span>";
            }

            $row->{$code} = $val;
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
     * Връща матрица със смяната за всеки ден и всеки служител в групата за даден период.
     *
     * @param string $crmGroupKeylist  keylist от crm_Groups (mandatory)
     * @param int    $periodId         ид на acc_Periods (mandatory)
     * @param bool   $returnIds        ако е TRUE -> връща id на смяната; иначе име
     *
     * @return array [Y-m-d][personId] => shiftName|shiftId|null
     */
    protected static function getShiftsMatrixByGroupAndPeriod($crmGroupKeylist, $periodId, $returnIds = false)
    {
        // --- 0) Валидиране
        if (empty($crmGroupKeylist) || empty($periodId)) {
            return array();
        }

        // --- 1) Граници на периода от acc_Periods
        $perRec = acc_Periods::fetch($periodId); // <-- без списък от полета
        if (!$perRec) return array();

        // Поддържаме различни имена на полетата (в зависимост от версията)
        $from = isset($perRec->start) ? $perRec->start : (isset($perRec->from) ? $perRec->from : null);
        $to   = isset($perRec->end)   ? $perRec->end   : (isset($perRec->to)   ? $perRec->to   : null);
        if (!$from || !$to) return array();

        $from = dt::verbal2mysql($from, 'date');
        $to   = dt::verbal2mysql($to,   'date');

        // --- 2) Резолюция на групите -> personId (уникални)
        $personIds = array();
        $groupIds = keylist::toArray($crmGroupKeylist);

        foreach ($groupIds as $gId) {
            $usersArr = array();

            // Опит 1: crm_Groups::getUsers($gId)
            if (method_exists('crm_Groups', 'getUsers')) {
                $usersArr = (array) crm_Groups::getUsers($gId);
            }
            // Опит 2: crm_Groups::getGroupUsersArr($gId)
            elseif (method_exists('crm_Groups', 'getGroupUsersArr')) {
                $usersArr = (array) crm_Groups::getGroupUsersArr($gId);
            }

            foreach ($usersArr as $userId) {
                $pId = crm_Profiles::fetchField("#userId = {$userId}", 'personId');
                if ($pId) {
                    $personIds[$pId] = $pId; // uniq
                }
            }
        }

        if (empty($personIds)) {
            return array();
        }

        // По желание: филтрираме само активни служители, ако има такъв модел
        if (class_exists('hr_Employees')) {
            $q = hr_Employees::getQuery();
            $q->in('personId', array_keys($personIds));
            $q->where("#state = 'active'");
            $q->show('personId');

            $active = array();
            while ($e = $q->fetch()) {
                $active[$e->personId] = $e->personId;
            }
            // пресичане
            $personIds = array_intersect_key($personIds, $active);
            if (empty($personIds)) return array();
        }

        // --- 3) Изчисляваме смяната за всеки ден и човек
        $res = array();

        for ($d = $from; ; $d = dt::addDays(1, $d, false)) {
            $res[$d] = array();

            foreach ($personIds as $pid) {
                $shiftId = hr_Shifts::getShift($d, $pid);   // id или null

                if ($returnIds) {
                    $res[$d][$pid] = $shiftId ?: null;
                } else {
                    $res[$d][$pid] = $shiftId ? hr_Shifts::fetchField($shiftId, 'name') : null;
                }
            }

            if ($d >= $to) break; // включително
        }

        return $res;
    }

    /**
     * Връща personId-тата на всички лица, които са в подадените CRM групи.
     *
     * @param string $crmGroupKeylist keylist от crm_Groups (една или повече групи)
     * @param bool   $activeOnly      ако има hr_Employees – само активните
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
        $q->show('id,groupList');
        $q->where("#state != 'rejected'");

        // WHERE LOCATE('|<gId>|', CONCAT('|', #groupList, '|')) за всяка група (OR)
        $ors = array();
        foreach ($groupIds as $gId) {
            $gId = (int)$gId;
            $ors[] = "LOCATE('|{$gId}|', CONCAT('|', #groupList, '|'))";
        }
        $q->where('(' . implode(' OR ', $ors) . ')');

        $personIds = array();
        while ($p = $q->fetch()) {
            $personIds[$p->id] = $p->id;
        }
        if (empty($personIds)) {
            return array();
        }

        // 2) По желание: само активни служители
        if ($activeOnly && class_exists('hr_Employees')) {
            $eq = hr_Employees::getQuery();
            $eq->in('personId', array_keys($personIds));
            $eq->where("#state = 'active'");
            $eq->show('personId');

            $active = array();
            while ($e = $eq->fetch()) {
                $active[$e->personId] = true;
            }
            // пресичане
            $personIds = array_intersect_key($personIds, $active);
        }

        return $personIds; // [personId => personId]
    }

    /**
     * Връща [personId => име] за лицата от подадените групи (сортирано по име).
     *
     * @param string $crmGroupKeylist keylist от crm_Groups
     * @param bool   $activeOnly
     * @return array [personId => name]
     */
    protected static function getPersonsNamesFromGroups($crmGroupKeylist, $activeOnly = true)
    {
        $ids = self::getPersonIdsFromCrmGroups($crmGroupKeylist, $activeOnly);
        if (empty($ids)) return array();

        $names = array();
        foreach ($ids as $pid) {

            $names[$pid] = crm_Persons::fetchField($pid, 'name');

        }

        asort($names);

        return $names;
    }

}