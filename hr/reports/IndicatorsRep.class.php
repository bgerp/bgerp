<?php


/**
 * Мениджър на отчети за Индикаторите за ефективност
 *
 * @category  bgerp
 * @package   hr
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Персонал » Индикатори за ефективност
 */
class hr_reports_IndicatorsRep extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'manager,ceo';
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'person';
    
    
    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck = 'docId';
    
    
    /**
     * Полета с възможност за промяна
     */
    protected $changeableFields = 'periods,indocators,formula,fromDate,toDate';
    
    
    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields = 'indicatorId,value';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('periods', 'key(mvc=acc_Periods,select=title,allowEmpty)', 'caption=Период->Месец,after=title,removeAndRefreshForm=fromDate|toDate');
        $fieldset->FLD('fromDate', 'date(format=smartTime)', 'caption=Период->От,after=periods,silent,removeAndRefreshForm=periods');
        $fieldset->FLD('toDate', 'date(format=smartTime)', 'caption=Период->До,after=fromDate,silent,removeAndRefreshForm=periods');
        $fieldset->FLD('indocators', 'keylist(mvc=hr_IndicatorNames,select=name,allowEmpty)', 'caption=Настройки->Индикатори,after=toDate');
        $fieldset->FLD('formula', 'text(rows=2)', 'caption=Настройки->Формула,after=indocators,single=none');
        $fieldset->FLD('personId', 'keylist(mvc=core_Users,select=nick)', 'caption=Настройки->Потребители,after=formula,single=none');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver   $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = &$data->form;
        if(empty($form->rec->fromDate) && empty($form->rec->toDate)){
            $periodToday = acc_Periods::fetchByDate(dt::now());
            $form->setDefault('periods', $periodToday->id);
        }

        $cu = core_Users::getCurrent();
        $form->setSuggestions('formula', hr_IndicatorNames::getFormulaSuggestions());

        // Само потребителите с по-нисък ранг може да бъдат избрани
        $activeArr = $closedArr = $rejectedArr = array();
        $allUsers = core_Users::getUsersByRoles('powerUser', null, 'active,closed,rejected');
        $uQuery = core_Users::getQuery();
        $uQuery->in('id', array_keys($allUsers));
        while($uRec = $uQuery->fetch()){
            if(core_Users::compareRangs($uRec->id, $cu) < 0 || $uRec->id == $cu){
                if($uRec->state == 'active'){
                    $activeArr[$uRec->id] = "{$uRec->nick} ($uRec->names)";
                } elseif($uRec->state == 'closed'){
                    $closedArr[$uRec->id] = "{$uRec->nick} ($uRec->names)";
                } elseif($uRec->state == 'rejected'){
                    $rejectedArr[$uRec->id] = "{$uRec->nick} ($uRec->names)";
                }
            }
        }

        // Групиране на намерените потребители по състояние
        $filteredUsers = array('а' => (object) array('group' => true, 'title' => tr('Активни потребители'))) + $activeArr;
        if(countR($closedArr)){
            $filteredUsers += array('c' => (object) array('group' => true, 'title' => tr('Затворени потребители'))) + $closedArr;
        }
        if(countR($rejectedArr)){
            $filteredUsers += array('r' => (object) array('group' => true, 'title' => tr('Заличени потребители'))) + $rejectedArr;
        }
        $form->setSuggestions('personId', $filteredUsers);
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param tremol_FiscPrinterDriverWeb $Driver
     * @param peripheral_Devices     $Embedder
     * @param core_Form         $form
     */
    protected static function on_AfterInputEditForm($Driver, $Embedder, &$form)
    {
        if ($form->isSubmitted()){
            $rec = $form->rec;

            if(empty($rec->periods) && empty($rec->fromDate) && empty($rec->toDate)){
                $form->setError('periods,fromDate,toDate', 'Трябва да бъде избран период');
            }

            if(!empty($rec->periods) && (!empty($rec->fromDate) || !empty($rec->toDate))){
                $form->setError('periods,fromDate,toDate', 'Трябва или да е избран точен месец, или конкретни дати|*!');
            }

            if(!empty($rec->fromDate) && !empty($rec->toDate)){
                if($rec->fromDate > $rec->toDate){
                    $form->setError('fromDate,toDate', 'Началната дата е по-голяма от крайната|*!');
                }
            }
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

        if(!empty($rec->periods)){
            $periodRec = acc_Periods::fetch($rec->periods);
            list($start, $end) = array($periodRec->start, $periodRec->end);
        } else {
            $start = !empty($rec->fromDate) ? $rec->fromDate : null;
            $end = !empty($rec->toDate) ? $rec->toDate : null;
        }

        // Ако има избрани потребители, взимат се те. Ако няма всички потребители
        $users = (!empty($rec->personId)) ? keylist::toArray($rec->personId) : array_keys(core_Users::getUsersByRoles('powerUser', null, 'active,closed,rejected'));

        // Извличат се ид-та на визитките на избраните потребители
        $pQuery = crm_Profiles::getQuery();
        $pQuery->in('userId', $users);
        $pQuery->show('personId');
        $personIds = arr::extractValuesFromArray($pQuery->fetchAll(), 'personId');
        
        // Извличане на индикаторите за посочените дати, САМО за избраните лица
        $query = hr_Indicators::getQuery();
        if(!empty($start)){
            $query->where("#date >= '{$start}'");
        }
        if(!empty($end)){
            $query->where("#date <= '{$end}'");
        }


        $query->in('personId', $personIds);
        
        // Ако са посочени индикатори извличат се само техните записи
        if (!empty($rec->indocators)) {
            $indicators = keylist::toArray($rec->indocators);
            $query->in('indicatorId', $indicators);
        }
        
        $context = array();
        $personNames = array();
        
        // за всеки един индикатор
        while ($recIndic = $query->fetch()) {
            $key = "{$recIndic->personId}|{$recIndic->indicatorId}";
            $keyContext = "{$recIndic->personId}|formula";
            
            // Пропускат се индикаторите от оттеглени документи
            if (isset($recIndic->docClass, $recIndic->docId)) {
                if (cls::load($recIndic->docClass, true)) {
                    $Doc = cls::get($recIndic->docClass);
                    if ($Doc->getField('state', false)) {
                        $state = $Doc->fetchField($recIndic->docId, 'state');
                        if ($state == 'rejected') {
                            continue;
                        }
                    }
                }
            }
            
            // Добавя се към масива, ако го няма
            if (!array_key_exists($key, $recs)) {
                if (!array_key_exists($recIndic->personId, $personNames)) {
                    $personNames[$recIndic->personId] = mb_strtolower(trim(crm_Persons::fetchField($recIndic->personId, 'name')));
                }
                
                $recs[$key] = (object) array('num' => 0,
                    'date' => $recIndic->date,
                    'docId' => $recIndic->docId,
                    'person' => $recIndic->personId,
                    'indicatorId' => $recIndic->indicatorId,
                    'value' => $recIndic->value,
                    'personName' => $personNames[$recIndic->personId],
                );
            } else {
                $obj = &$recs[$key];
                $obj->value += $recIndic->value;
            }
            
            $iName = hr_IndicatorNames::fetchField($recIndic->indicatorId, 'name');
            
            $context[$recIndic->personId]['$' . $iName] += $recIndic->value;
        }
        
        if (!empty($rec->formula)) {
            foreach ($context as $pId => $arr) {
                $recs["{$pId}|formula"] = (object) array('person' => $pId, 'personName' => $personNames[$pId], 'indicatorId' => 'formula', 'context' => $arr);
            }
        }
        
        // Ако има такива сортираме ги по име
        uasort($recs, function ($a, $b) {
            if ($a->personName == $b->personName) {
                
                return $a->indicatorId < $b->indicatorId ? -1 : 1;
            }
            
            return $a->personName < $b->personName ? -1 : 1;
        });
        
        $num = 1;
        $total = array();
        foreach ($recs as $r) {
            $r->num = $num;
            $num++;
            
            if ($r->indicatorId == 'formula') {
                if (!array_key_exists($r->indicatorId, $total)) {
                    $total[$r->indicatorId] = $r->context;
                } else {
                    if (is_array($r->context)) {
                        foreach ($r->context as $k => $v) {
                            $total[$r->indicatorId][$k] += $v;
                        }
                    }
                }
            } else {
                $total[$r->indicatorId] += $r->value;
            }
        }
        
        foreach ($total as $ind => $val) {
            $r = new stdClass();
            $r->person = 0;
            $r->indicatorId = $ind;
            
            if (is_array($val)) {
                $r->context = $val;
            } else {
                $r->value = $val;
            }
            
            $num++;
            $r->num = $num;
            
            $recs['0|' . $ind] = $r;
        }
        
        return $recs;
    }
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec    - записа
     * @param bool     $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        $fld->FLD('num', 'varchar', 'caption=№');
        $fld->FLD('person', 'varchar', 'caption=Служител');
        $fld->FLD('indicatorId', 'varchar', 'caption=Показател');
        $fld->FLD('value', 'double(smartRound,decimals=2)', 'smartCenter,caption=Стойност');
        
        return $fld;
    }
    
    
    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec  - записа
     * @param stdClass $dRec - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $Int = cls::get('type_Int');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $row = new stdClass();
        
        // Линк към служителя
        if (isset($dRec->person)) {
            if ($dRec->person > 0) {
                $userId = crm_Profiles::fetchField("#personId = '{$dRec->person}'", 'userId');
                $nick = crm_Profiles::createLink($userId)->getContent();
                $row->person = crm_Persons::fetchField($dRec->person, 'name') . ' (' . $nick .')';
            } else {
                $row->person = 'Общо';
            }
        }
        
        if (isset($dRec->num)) {
            $row->num = $Int->toVerbal($dRec->num);
        }
        
        if (isset($dRec->indicatorId)) {
            if ($dRec->indicatorId != 'formula') {
                $row->indicatorId = hr_IndicatorNames::fetchField($dRec->indicatorId, 'name');
            } elseif ($rec->formula) {
                $row->indicatorId = tr('Формула');
                
                $value = self::calcFormula($rec->formula, $dRec->context);
                $row->value = (is_numeric($value)) ? '<b>' . $Double->toVerbal($value) . '</b>' : "<small style='font-style:italic;color:red;'>{$value}</small>";
            }
        }
        
        if (isset($dRec->value) && empty($row->value)) {
            $row->value = $Double->toVerbal($dRec->value);
            $row->value = ht::styleNumber($row->value, $dRec->value);

            $haveRight = hr_Indicators::haveRightFor('list');
            $url = array('hr_Indicators', 'list', 'indicatorId' => $dRec->indicatorId);

            if(!empty($rec->periods)){
                $start = acc_Periods::fetchField($rec->periods, 'start');
                $date = new DateTime($start);
                $url['period'] = $date->format('Y-m-01');

                if (!empty($dRec->person)) {
                    $url['personId'] = $dRec->person;
                }

                if ($haveRight !== true) {
                    core_Request::setProtected('period,personId,indicatorId,force');
                    $url['force'] = true;
                }

                if (!Mode::isReadOnly()) {
                    $row->value = ht::createLinkRef($row->value, toUrl($url), false, 'target=_blank,title=Към документите формирали записа');
                }

                if ($haveRight !== true) {
                    core_Request::removeProtected('period,personId,indicatorId,force');
                }
            }
        }
        
        return $row;
    }
    
    
    /**
     * Калкулира формулата
     *
     * @param string $formula
     * @param array  $context
     *
     * @return string $value
     */
    private static function calcFormula($formula, $context)
    {
        $newContext = self::fillMissingIndicators($context, $formula);
        if (($expr = str::prepareMathExpr($formula, $newContext)) !== false) {
            $value = str::calcMathExpr($expr, $success);
            
            if ($success === false) {
                $value = tr('Невъзможно изчисление');
            }
        } else {
            $value = tr('Некоректна формула');
        }
        
        return $value;
    }
    
    
    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver      - драйвер
     * @param stdClass            $res         - резултатен запис
     * @param stdClass            $rec         - запис на справката
     * @param stdClass            $dRec        - запис на реда
     * @param core_BaseClass      $ExportClass - клас за експорт (@see export_ExportTypeIntf)
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        $res->person = ($dRec->person) ? crm_Persons::fetchField($dRec->person, 'name') : tr('Общо');
        if ($dRec->indicatorId != 'formula') {
            $res->indicatorId = hr_IndicatorNames::getTitleById($dRec->indicatorId);
        } else {
            $res->indicatorId = tr('Формула');
            $res->value = static::calcFormula($rec->formula, $dRec->context);
        }
    }
    
    
    /**
     * Допълване на липсващите индикатори от формулата с такива със стойност 0
     *
     * @param array  $context
     * @param string $formula
     *
     * @return array $arr
     */
    private static function fillMissingIndicators($context, $formula)
    {
        $arr = array();
        $formulaIndicators = hr_Indicators::getIndicatorsInFormula($formula);
        if (!countR($formulaIndicators)) {
            
            return $arr;
        }
        
        foreach ($formulaIndicators as $name) {
            $key = '$' . $name;
            if (!array_key_exists($key, $context)) {
                $context[$key] = 0;
            }
        }
        
        return $context;
    }
    
    
    /**
     * След вербализирането на данните
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $row
     * @param stdClass            $rec
     * @param array               $fields
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
        // Вербализиране на избраните потребители
        if (isset($rec->personId)) {
            $persons = keylist::toArray($rec->personId);
            $rec->persons = $persons;
            foreach ($persons as $userId => &$nick) {
                $nick = crm_Profiles::createLink($userId)->getContent();
            }
            $row->persons = implode(', ', $persons);
        }
        
        if (isset($rec->formula)) {
            $row->formula = '<b>' . core_Type::getByName('text')->toVerbal($rec->formula) . '</b>';
        }
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<!--ET_BEGIN persons--><fieldset class='detail-info'><legend class='groupTitle'><small><b>|Потребители|*</b></small></legend><div>[#persons#]</div><!--ET_END persons--></fieldset>
								<!--ET_BEGIN formula--><fieldset class='detail-info'><legend class='groupTitle'><small><b>|Формула|*</b></small></legend><div><small>[#formula#]</small></div><!--ET_END formula--></fieldset><!--ET_END BLOCK-->"));

        foreach (array('indocators', 'formula', 'persons') as $fld) {
            if (!empty($data->rec->{$fld})) {
                $fieldTpl->append($data->row->{$fld}, $fld);
            }
        }
        
        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
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
        if(isset($rec->periods)){
            $periodRec = acc_Periods::fetch($rec->periods);

            return array('from' => $periodRec->start, 'to' => $periodRec->end);
        }

        return array('from' => $rec->fromDate, 'to' => $rec->toDate);
    }
}
