<?php


/**
 * Драйвер за показване на календара
 *
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Календар
 */
class bgerp_drivers_Calendar extends core_BaseClass
{
    /**
     * Максимален брой блокове, които да могат да се поакзват в портала
     */
    public $maxCnt = 1;
    
    
    public $interfaces = 'bgerp_PortalBlockIntf';
    
    protected $priorityMap = array(
            'low' => 'low|normal|high|critical',
            'normal' => 'normal|high|critical',
            'high' => 'high|critical',
            'critical' => 'critical',
    );
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('fTasksPerPage', 'int(min=1, max=50)', 'caption=Показване на задачите в бъдеще->Редове, mandatory');
        $fieldset->FLD('fTasksDays', 'time(suggestions=1 месец|3 месеца|6 месеца)', 'caption=Показване на задачите в бъдеще->Дни, mandatory');
        $fieldset->FLD('hideClosedTasks', 'time(suggestions=8 часа|16 часа|24 часа)', 'caption=Скриване на приключените задачи->След');
        $fieldset->FLD('taskPriority', 'enum(low=Нисък,normal=Нормален,high=Спешен,critical=Критичен)', 'caption=Минимален приоритет за включване->Задачи');
        $fieldset->FLD('remPriority', 'enum(low=Нисък,normal=Нормален,high=Спешен,critical=Критичен)', 'caption=Минимален приоритет за включване->Напомняния');
    }
    
    
    /**
     * Може ли вградения обект да се избере
     *
     * @param NULL|int $userId
     *
     * @return bool
     */
    public function canSelectDriver($userId = null)
    {
        return true;
    }
    
    
    /**
     * Подготвя данните
     *
     * @param stdClass $dRec
     * @param null|int $userId
     *
     * @return stdClass
     */
    public function prepare($dRec, $userId = null)
    {
        $resData = new stdClass();
        
        if (empty($userId)) {
            expect($userId = core_Users::getCurrent());
        }
        
        $resData->month = Request::get('cal_month', 'int');
        $resData->month = str_pad($resData->month, 2, '0', STR_PAD_LEFT);
        $resData->year = Request::get('cal_year', 'int');
        
        if (!$resData->month || $resData->month < 1 || $resData->month > 12 || !$resData->year || $resData->year < 1970 || $resData->year > 2038) {
            $resData->year = date('Y');
            $resData->month = date('m');
        }
        
        $resData->monthOptions = cal_Calendar::prepareMonthOptions();
        
        //От началото на месеца
        $from = "{$resData->year}-{$resData->month}-01 00:00:00";
        
        // До последния ден за месеца
        $lastDay = date('d', mktime(12, 59, 59, $resData->month + 1, 0, $resData->year));
        $to = "{$resData->year}-{$resData->month}-{$lastDay} 23:59:59";
        
        $resData->calendarState = new stdClass();
        $resData->calendarState->query = cal_Calendar::getQuery();
        
        // Само събитията за текущия потребител или за всички потребители
        $resData->calendarState->query->where("#users IS NULL OR #users = ''");
        $resData->calendarState->query->orLikeKeylist('users', $userId);
        $resData->calendarState->query->where(array("#time >= '[#1#]' AND #time <= '[#2#]'", $from, $to));
        
        // Само бележки за текущия потребител или за всички потребители
        // Последния запис в модела - за деактивиране на кеша
        $resData->agendaData = new stdClass();
        
        // Съдържание на клетките на календара
        $Calendar = cls::get('cal_Calendar');
        
        $sInputField = bgerp_Portal::getPortalSearchInputFieldName($Calendar->searchInputField, $dRec->originIdCalc);
        $resData->cacheKey = $this->getCacheKey($dRec, $userId);
        $resData->cacheType = $this->getCacheTypeName($userId);
        
        $resData->tpl = core_Cache::get($resData->cacheType, $resData->cacheKey);
        
        if (!$resData->tpl) {
            $Calendar->searchInputField = bgerp_Portal::getPortalSearchInputFieldName($Calendar->searchInputField, $dRec->originIdCalc);
            
            $Calendar->prepareListRecs($resData->calendarState);
            if (is_array($resData->calendarState->recs)) {
                $resData->cData = array();
                foreach ($resData->calendarState->recs as $rec) {
                    $time = dt::mysql2timestamp($rec->time);
                    $i = (int) date('j', $time);
                    
                    if (!isset($resData->cData[$i])) {
                        $resData->cData[$i] = new stdClass();
                    }
                    
                    list($d, $t) = explode(' ', $rec->time);
                    
                    if ($rec->type == 'holiday' || $rec->type == 'non-working' || $rec->type == 'workday') {
                        $time = dt::mysql2timestamp($rec->time);
                        $i = (int) date('j', $time);
                        if (!isset($resData->cData[$i])) {
                            $resData->cData[$i] = new stdClass();
                        }
                        $resData->cData[$i]->type = $rec->type;
                    } elseif ($rec->type == 'working-travel') {
                        $resData->cData[$i]->html = "<img style='height10px;width:10px;' src=". sbf('img/16/working-travel.png') .'>&nbsp;';
                    } elseif ($rec->type == 'leaves') {
                        $resData->cData[$i]->html = "<img style='height10px;width:10px;' src=". sbf('img/16/leaves.png') .'>&nbsp;';
                    } elseif ($rec->type == 'sick') {
                        $resData->cData[$i]->html = "<img style='height10px;width:10px;' src=". sbf('img/16/sick.png') .'>&nbsp;';
                    } elseif ($rec->type == 'workday') {
                        // Нищо не се прави
                    } elseif ($rec->type == 'task' || $rec->type == 'reminder') {
                        if ($rec->state == 'active' || $rec->state == 'waiting') {
                            $resData->cData[$i]->html = "<img style='height10px;width:10px;' src=". sbf('img/16/star_2.png') .'>&nbsp;';
                        } else {
                            $resData->cData[$i]->html = "<img style='height10px;width:10px;' src=". sbf('img/16/star_grey.png') .'>&nbsp;';
                        }
                    }
                }
            }
            
            for ($i = 1; $i <= 31; $i++) {
                if (!isset($resData->cData[$i])) {
                    $resData->cData[$i] = new stdClass();
                }
                $resData->cData[$i]->url = toUrl(array('cal_Calendar', 'day', 'from' => "{$i}.{$resData->month}.{$resData->year}"));
            }
            
            // Съдържание на списъка със събития

            $pArr = array();
            $pArr['tPageVar'] = $this->getPageVar($dRec->originIdCalc);
            $pArr['search'] = Request::get($sInputField);
            $pArr['tPerPage'] = $dRec->fTasksPerPage ? $dRec->fTasksPerPage : 5;
            $pArr['fTasksDays'] = $dRec->fTasksDays ? $dRec->fTasksDays : core_DateTime::SECONDS_IN_MONTH;
            $pArr['hideClosedTasks'] = isset($dRec->hideClosedTasks) ? $dRec->hideClosedTasks : 86400;
            $pArr['taskPriority'] = $dRec->taskPriority;
            $pArr['remPriority'] = $dRec->remPriority;

            $pArr['_userId'] = $userId;
            $today = dt::now(false);
            $pArr['_todayF'] = $today . ' 00:00:00';

            // Намираме работните дни, така че да останат 3 работни дни винаги
            $nWorkDay = cal_Calendar::nextWorkingDay(dt::addDays(-1, $today, false), $userId, 1);
            $endWorkingDayCnt = (dt::daysBetween($nWorkDay, $today)) ? 3 : 2;
            $pArr['_endWorkingDay'] = cal_Calendar::nextWorkingDay($today, $userId, $endWorkingDayCnt);
            $pArr['_endWorkingDay'] .= ' 23:59:59';
            $pArr['fTasksDays'] = dt::addSecs($pArr['fTasksDays'], $pArr['_endWorkingDay']);

            $dDif = dt::daysBetween($pArr['_endWorkingDay'], $today);
            if ($dDif > 4) {
                $pArr['_endWorkingDay'] = dt::addDays(4, $today . ' 23:59:59');
            }

            $resData->EventsData = $this->prepareCalendarEvents($userId, $pArr);
        }

        return $resData;
    }
    
    
    /**
     * Рендира данните
     *
     * @param stdClass $data
     *
     * @return core_ET
     */
    public function render($data)
    {
        if (!$data->tpl) {
            $Calendar = cls::get('cal_Calendar');
            $select = ht::createSelect('dropdown-cal', $data->monthOptions->opt, $data->monthOptions->currentM, array('onchange' => 'javascript:location.href = this.value;', 'class' => 'portal-select'));
            
            $header = "<table class='mc-header' style='width:100%'>
                    <tr>
                    <td class='aleft'><a href='{$data->monthOptions->prevtLink}'>{$data->monthOptions->prevMonth}</a></td>
                    <td class='centered'><span class='metro-dropdown-portal'>{$select}</span>
                    <td class='aright'><a href='{$data->monthOptions->nextLink}'>{$data->monthOptions->nextMonth}</a></td>
                    </tr>
                    </table>";
            
            $searchForm = $Calendar->getForm();
            bgerp_Portal::prepareSearchForm($Calendar, $searchForm);
            
            $data->tpl = new ET(tr(
                
                '|*<div class="clearfix21 portal newCalendar">
                                        <div class="legend" id="calendarPortal">[#CAL_TITLE#]
                                            [#SEARCH_FORM#]
                                        </div>
                                        <div>
                                        <!--ET_BEGIN NOW-->
                                        
                                            <div class="[#NOW_CLASS_NAME#] portal-cal-day">
                                                <span class="title [#NOW_DATE_CLASS#]">[#NOW_DATE#]</span>
                                                [#NOW#]
                                            </div>
                                        <!--ET_END NOW-->    
                                        </div>
                                        [#MONTH_CALENDAR#]
                                        
                                        <!--ET_BEGIN FUTURE--><div class="portal-cal-day" style="padding: 5px; border-top: none">[#FUTURE_DATE#]</div>[#FUTURE#]<!--ET_END FUTURE-->
                                    </div>'
                                    ));
            
            $tArr = $data->EventsData;
            
            $today = dt::now(false);
            $tomorrow = dt::addDays(1, $today, false);
            $nextDay = dt::addDays(2, $today, false);
            
            $format = Mode::is('screenMode', 'narrow') ? 'd-M-year, D': 'd M-year, D';
            
            ksort($tArr['now']);

            $noEvent = '<small style="vertical-align:text-top">' . tr('Няма събития') . '</small>';
            
            $lastKey = null;
            
            if (!empty($tArr['now'])) {
                end($tArr['now']);
                $lastKey = key($tArr['now']);
            }
            
            if ((!isset($lastKey)) || ($lastKey == $today) || ($lastKey == $tomorrow)) {
                $lastKey = $nextDay;
            }
            
            $dCnt = 0;
            while (true) {
                $d = dt::addDays($dCnt, $today, false);
                if ($d == $lastKey) {
                    break;
                }
                
                if ($dCnt++ > 10) {
                    break;
                }
                
                if (!$tArr['now'][$d]) {
                    $tArr['now'][$d][] = (object)array('title' => $noEvent);
                }
            }
            
            ksort($tArr['now']);
            
            // Показваме събитията близките дни
            foreach ((array) $tArr['now'] as $tDate => $tRowArr) {

                $dStr = dt::mysql2verbal($tDate, $format, null, null, false);
                
                if ($today == $tDate) {
                    $dVerb = tr('Днес');
                    $nowClassName = 'portal-cal-today';
                } elseif ($tDate == $tomorrow) {
                    $dVerb = tr('Утре');
                    $nowClassName = 'portal-cal-tomorrow';
                } elseif ($tDate == $nextDay) {
                    $dVerb = tr('Вдругиден');
                    $nowClassName = 'portal-cal-nextday';
                } else {
                    $dVerb = '';
                    $nowClassName = 'portal-cal-after';
                }
                
                $nowDateClass = cal_Calendar::getColorOfDay($tDate. " 00:00:00");
                $nowDateClass = $nowDateClass ? $nowDateClass : 'workday';
                
                $dVerb .= $dVerb ? ', ' : '';
                $dVerb .= $dStr;
                
                $res = (object) array('day' => $dVerb);
                $Calendar->invoke('AfterPrepareGroupDate', array(&$res, $tDate));
                
                $dVerb = $res->day;
                
                $dBlock = $data->tpl->getBlock('NOW');
                
                $dBlock->replace($dVerb, 'NOW_DATE');
                $dBlock->replace($nowDateClass, 'NOW_DATE_CLASS');
                $dBlock->replace($nowClassName, 'NOW_CLASS_NAME');
                
                if ($tRowArr['events']) {
                    $dBlock->append('<span class="subTitle"><small style="vertical-align:text-top">' . tr('Празнуваме||Celebrate') . ':</small>' . $tRowArr['events'] . '</span>', 'NOW');
                    unset($tRowArr['events']);
                }
                
                foreach ($tRowArr as $tRow) {
                    $dBlock->append('<div class="subTitle">' . $tRow->title . '</div>', 'NOW');
                }
                
                $dBlock->removeBlocks();
                $dBlock->append2master();
            }
            
            // Показваме събитията за в бъдеще
            if ($tArr['future']) {
                $data->tpl->append($tArr['future'], 'FUTURE');
                $data->tpl->replace(tr('По-нататък'), 'FUTURE_DATE');
            }
            
            if (!Mode::is('screenMode', 'narrow')) {
                $data->tpl->replace(tr('Календар'), 'CAL_TITLE');
            } else {
                $data->tpl->replace('&nbsp;', 'CAL_TITLE');
            }
            
            $data->tpl->replace($searchForm->renderHtml(), 'SEARCH_FORM');
            
            $data->tpl->replace(cal_Calendar::renderCalendar($data->year, $data->month, $data->cData, $header), 'MONTH_CALENDAR');
            
            $data->tpl->push('js/PortalSearch.js', 'JS');
            jquery_Jquery::run($data->tpl, 'portalSearch();', true);
            jquery_Jquery::runAfterAjax($data->tpl, 'portalSearch');
            
            $cacheLifetime = doc_Setup::get('CACHE_LIFETIME') ? doc_Setup::get('CACHE_LIFETIME') : 5;
            core_Cache::set($data->cacheType, $data->cacheKey, $data->tpl, $cacheLifetime);
        }
        
        return $data->tpl;
    }
    
    
    /**
     * Връща заглавието за таба на съответния блок
     *
     * @param stdClass $dRec
     *
     * @return string
     */
    public function getBlockTabName($dRec)
    {
        return tr('Календар');
    }
    
    
    /**
     * Помощна функция за вземане на събитията за съответни дни
     *
     * @param null|int $userId
     * @param array    $pArr
     *
     * @return array
     */
    protected function prepareCalendarEvents($userId = null, $pArr = array())
    {
        $resArr = $this->prepareTasksCalendarEvents($pArr);
        
        setIfNot($resArr['now'], array());
        
        $this->prepareRemindersCalendarEvents($pArr, $resArr['now']);
        
        $this->prepareHolidaysCalendarEvents($pArr, $resArr['now']);

        if (!is_array($resArr['now'])) {
            $resArr['now'] = array();
        }
        ksort($resArr['now']);

        // Подреждаме събитията по часове
        foreach ($resArr['now'] as &$rArr) {
            if (!is_array($rArr) || empty($rArr)) {
                continue;
            }
            
            ksort($rArr);
        }

        return $resArr;
    }
    
    
    /**
     * Помощна функция за вземане на всички задачи за съответния период
     *
     * @param array $pArr
     *
     * @return array
     */
    protected function prepareTasksCalendarEvents($pArr)
    {
        $query = cal_Tasks::getQuery();

        if ($pArr['taskPriority']) {
            expect($this->priorityMap[$pArr['taskPriority']]);
            $priorityArr = explode('|', $this->priorityMap[$pArr['taskPriority']]);
            $query->orWhereArr('priority', $priorityArr);
        }
        
        if ($pArr['search']) {
            plg_Search::applySearch($pArr['search'], $query);
        }
        
        $query->where("#state != 'rejected'");
        $query->where("#state != 'draft'");

        $query->likeKeylist('assign', $pArr['_userId']);
        
        $query->where('#timeStart IS NOT NULL');
        $query->orWhere('#timeEnd IS NOT NULL AND (#timeStart IS NOT NULL OR #timeDuration IS NOT NULL)');
        
        $todayF = $pArr['_todayF'];
        $query->where(array("#expectationTimeStart >= '[#1#]'", $todayF));
        $query->orWhere(array("#expectationTimeEnd >= '[#1#]'", $todayF));

        $query->XPR('expectationTimeOrder', 'datetime', "IF((#expectationTimeStart < '{$todayF}' OR #expectationTimeStart IS NULL), #expectationTimeEnd, #expectationTimeStart)");
        
        $query->orderBy('expectationTimeOrder', 'ASC');

        // Задачите в бъдеще
        $fQuery = clone $query;
        $fQuery->where(array("#expectationTimeOrder >= '[#1#]'", $pArr['_endWorkingDay']));
        $fQuery->where(array("#expectationTimeOrder <= '[#1#]'", $pArr['fTasksDays']));

        if (isset($pArr['hideClosedTasks'])) {
            $query->where(array("(#state != 'closed' AND #state != 'stopped') OR (#timeClosed >= '[#1#]')", dt::subtractSecs($pArr['hideClosedTasks'])));
        }
        
        // Задачите за близко бъдеще
        $query->where(array("#expectationTimeOrder <= '[#1#]'", $pArr['_endWorkingDay']));

        $resArr = array();
        $i = 0;
        while ($rec = $query->fetch()) {
            list($orderDate, $orderH) = explode(' ', $rec->expectationTimeOrder);
            $orderH .= ' ' . ++$i;
            $resArr['now'][$orderDate][$orderH] = $this->getRowForTask($rec, $pArr['_userId']);
        }
        
        $Tasks = cls::get('cal_Tasks');
        $Tasks->addRowClass = false;
        
        $fTasks = new stdClass();
        $fTasks->query = $fQuery;
        
        $Tasks->listItemsPerPage = $pArr['tPerPage'];
        $fTasks->listFields = 'title, progress';
        
        $Tasks->prepareListPager($fTasks);
        
        if ($pArr['tPageVar']) {
            $fTasks->pager->pageVar = $pArr['tPageVar'];
        }
        
        $Tasks->prepareListFields($fTasks);
        $Tasks->prepareListFilter($fTasks);
        $Tasks->prepareListRecs($fTasks);
        $Tasks->prepareListRows($fTasks);

        foreach ($fTasks->recs as $id => $fRec) {
            $fTasks->rows[$id] = $this->getRowForTask($fRec, $pArr['_userId'], false, true);
        }
        
        if ($fTasks->recs) {
            $fTpl = new ET('[#table#][#pager#]');
            $fTpl->replace($Tasks->renderListTable($fTasks), 'table');
            $fTpl->replace($Tasks->renderListPager($fTasks), 'pager');
            $resArr['future'] = $fTpl;
        }

        return $resArr;
    }
    
    
    /**
     * Помощна функция за вземане на вербалните стойности на запис за задача
     *
     * @param stdClass $rec
     * @param null|integer $userId
     *
     * @return stdClass
     */
    protected function getRowForTask($rec, $userId = null, $appendProgress = true, $showDate = false)
    {
        $Tasks = cls::get('cal_Tasks');
        
        // Полета, които ще вербализираме
        $f = array('title', 'progress');
        
        $rToVerb = cal_Tasks::recToVerbal($rec, $f);

        $dRow = $Tasks->getDocumentRow($rec->id);

        $subTitle = "<span class='threadSubTitle'> {$dRow->subTitleNoTime}</span>";

        $linkArr = array('ef_icon' => $Tasks->getIcon($rec->id));

        if ($dRow->subTitleDateRec) {
            $rec->title = $this->removeDateAndHoursFromTitle($rec->title, $dRow->subTitleDateRec);
            $rec->title = $this->removeDateAndHoursFromTitle($rec->title, $rec->timeStart);
            $rec->title = $this->removeDateAndHoursFromTitle($rec->title, $rec->timeEnd);
            $rec->title = $this->removeDateAndHoursFromTitle($rec->title, $rec->expectationTimeEnd);
            $rec->title = $this->removeDateAndHoursFromTitle($rec->title, $rec->expectationTimeStart);

            $time = dt::mysql2verbal($dRow->subTitleDateRec, 'H:i');

            if (!$showDate) {
                if ($time != '00:00') {
                    $rec->title =  $time . ' ' . $rec->title;
                }
            } else {
                if ($dRow->subTitleDateRec) {
                    $title = str::limitLen(type_Varchar::escape($rec->title), 60, 30, ' ... ', true);
                    $date = dt::mysql2verbal($dRow->subTitleDateRec, 'smartDate');
                    $title =  $date . ' ' . $title;
                    if ($time != '00:00') {
                        $linkArr['title'] = $time;
                    }
                }
            }
        } else {
            $rec->title = $this->removeDateAndHoursFromTitle($rec->title, '1970-01-01 00:00:00');
        }

        if (!$showDate) {
            $title = str::limitLen(type_Varchar::escape($rec->title), 60, 30, ' ... ', true);
        }

        // Добавяме стил, ако има промяна след последното разглеждане
        if ($rec->modifiedOn > bgerp_Recently::getLastDocumentSee($rec->containerId, $userId, false)) {
            $linkArr['class'] = 'tUnsighted';
        }
        
        if (doc_Threads::fetchField($rec->threadId, 'state') == 'opened') {
            $linkArr['class'] .= ' state-opened';
        }
        
        $title = cal_Tasks::prepareTitle($title, $rec);

        $rToVerb->title = ht::createLink($title, cal_Tasks::getSingleUrlArray($rec->id), null, $linkArr);

        if ($appendProgress) {
            $rToVerb->title->append(' ' . $rToVerb->progress);
        }
        
        $rToVerb->title->append($subTitle);

        return $rToVerb;
    }
    
    
    /**
     * Премахва варииациите на датата и часа от подадения стринг
     * 
     * @param string $title
     * @param datetime $date
     * 
     * @return string
     */
    protected function removeDateAndHoursFromTitle($title, $date)
    {
        $time = dt::mysql2verbal($date, 'H:i');
        $time = preg_quote($time, '/');

        $timeC = dt::mysql2verbal($date, 'H,i');
        $timeC = preg_quote($timeC, '/');

        $timeD = dt::mysql2verbal($date, 'H,i');
        $timeD = preg_quote($timeD, '/');

        $timeN  = dt::mysql2verbal($date, 'G:i');
        $timeN = preg_quote($timeN, '/');
        
        $timeH = dt::mysql2verbal($date, 'H');
        $timeH = preg_quote($timeH, '/');
        
        $timeHN = dt::mysql2verbal($date, 'G');
        $timeHN = preg_quote($timeHN, '/');
        
        $dateA = dt::mysql2verbal($date, 'd.m.y');
        $dateA = preg_quote($dateA, '/');
        
        $dateB = dt::mysql2verbal($date, 'd.m.Y');
        $dateB = preg_quote($dateB, '/');
        
        $t = "\s*(ч\.?|h\.?)";
        $y = "\s*(г\.?|y\.?|год.?)";
        $x = "(\s*-\s*)*";

        $regExp = "/({$x}{$time}{$t}*{$x})|({$x}{$timeC}{$t}*{$x})|({$x}{$timeD}{$t}*{$x})|({$x}{$timeN}{$t}*{$x})|({$x}{$timeHN}{$t}+{$x})|({$x}{$dateB}{$y}*{$x})|({$x}{$dateA}[^0-9]{$y}*{$x})/ui";
        
        $title = preg_replace($regExp, ' ', $title . ' ');
        $title = preg_replace('/\s{1,}/u', ' ', $title);
        
        $title = trim($title);

        return $title;
    }
    
    
    /**
     * Помощна функция за вземане на всички напомняния за съответния период
     *
     * @param array $pArr
     * @param array $rArrNow
     *
     * @return array
     */
    protected function prepareRemindersCalendarEvents($pArr, &$rArrNow)
    {
        $Reminders = cls::get('cal_Reminders');
        $query = $Reminders->getQuery();
        
        if ($pArr['remPriority']) {
            expect($this->priorityMap[$pArr['remPriority']]);
            $priorityArr = explode('|', $this->priorityMap[$pArr['remPriority']]);
            $query->orWhereArr('priority', $priorityArr);
        }
        
        if ($pArr['search']) {
            plg_Search::applySearch($pArr['search'], $query);
        }
        
        $query->where("#state != 'rejected'");
        $query->where("#state != 'draft'");
        
        $query->likeKeylist('sharedUsers', $pArr['_userId']);
        
        $todayF = $pArr['_todayF'];
        
        $query->where(array("#calcTimeStart >= '[#1#]'", $todayF));
        $query->where(array("#calcTimeStart <= '[#1#]'", $pArr['_endWorkingDay']));
        
        $query->orderBy('calcTimeStart', 'ASC');
        
        $query->show('title,state,modifiedOn,containerId,calcTimeStart');
        
        $i = 1000;
        while ($rec = $query->fetch()) {
            list($orderDate, $orderH) = explode(' ', $rec->calcTimeStart);
            $orderH .= ' ' . ++$i;
            $tRec = $Reminders->recToVerbal($rec, 'title');
            if ($Reminders->haveRightFor('single', $rec)) {
                
                $tRec->title = $this->removeDateAndHoursFromTitle($tRec->title, $rec->calcTimeStart);
                $tRec->title = ' ' . dt::mysql2verbal($rec->calcTimeStart, 'H:i', null, true) . ' ' . $tRec->title;
                
                $linkArr = array('ef_icon' => $Reminders->getIcon($rec->id));

                // Добавяме стил, ако има промяна след последното разглеждане
                if ($rec->modifiedOn > bgerp_Recently::getLastDocumentSee($rec->containerId, $pArr['_userId'], false)) {
                    $linkArr['class'] = 'tUnsighted';
                }
                
                $title = ht::createLink($tRec->title, $Reminders->getSingleUrlArray($rec->id), null, $linkArr);
            }
            
            $rArrNow[$orderDate][$orderH] = (object) array('title' => $title);
        }
    }
    
    
    /**
     * Помощна функция за вземане на всички събития за съответния период
     *
     * @param array $pArr
     * @param array $rArrNow
     *
     * @return array
     */
    protected function prepareHolidaysCalendarEvents($pArr, &$rArrNow)
    {
        $Calendar = cls::get('cal_Calendar');
        $query = $Calendar->getQuery();
        
        $query->where("#users IS NULL OR #users = ''");
        $query->orLikeKeylist('users', $pArr['_userId']);
        
        if ($pArr['search']) {
            plg_Search::applySearch($pArr['search'], $query);
        }
        
        $query->where("#state != 'rejected'");
        
        $query->where(array("#time >= '[#1#]' AND #time <= '[#2#]'", $pArr['_todayF'], $pArr['_endWorkingDay']));
        
        $query->where(array("#key NOT LIKE 'REM-%'"));
        $query->where(array("#key NOT LIKE 'TSK-%'"));
        
        $query->orderBy('time', 'ASC');
        
        $cEventsTypeArr = array();
        
        $i = 10000;
        while ($rec = $query->fetch()) {
            list($orderDate, $orderH) = explode(' ', $rec->time);

            $oTime = '';
            if ($orderH == '00:00:00') {
                $orderH = 30;
            } else {
                $oTim = dt::mysql2verbal($rec->time, 'H:i');
            }
            
            $orderH .= ' ' . ++$i;
            $expandType = strtolower($Calendar->blockExpandTypes);
            $expandTypeArr = arr::make($expandType, true);
            $type = strtolower($rec->type);

            if ($pArr['search'] || $expandTypeArr[$type] || $type[0] == '_') {
                if ($oTim) {
                    $rec->title = $oTim . ' ' . $rec->title;
                }

                $cRec = $Calendar->recToVerbal($rec, 'title');
                $rArrNow[$orderDate][$orderH] = (object) array('title' => $cRec->event);
            } else {
                $type = strtolower($rec->type);
                
                if (isset($cEventsTypeArr[$orderDate][$type])) {
                    continue;
                }
                $cEventsTypeArr[$orderDate][$type] = $orderH;
            }
        }
        
        foreach ($cEventsTypeArr as $orderDate => $typeArr) {
            foreach ($typeArr as $type => $orderH) {
                
                $uniqId = 'uniq-' . $orderDate . '-' . $type;
                
                $url = toUrl(array('bgerp_drivers_Calendar', 'getHolidayInfo', 'date' => $orderDate, 'type' => $type, 'uniqId' => $uniqId), 'local');
                
                $eventImg = ht::createElement('img', array('src' => sbf("img/16/{$type}.png", '')));
                $event = ht::createElement('span', array('class' => 'tooltip-arrow-link', 'data-url' => $url), $eventImg, true);
                $event = "<span class='additionalInfo-holder'><span class='additionalInfo' id='{$uniqId}'></span>{$event}</span>";
                
                $rArrNow[$orderDate]['events'] .= '&nbsp;' . $event;
            }
        }
    }
    
    
    /**
     * Показва информация за перото по Айакс
     */
    public function act_GetHolidayInfo()
    {
        requireRole('powerUser');
        
        expect(Request::get('ajax_mode'));
        
        $date = Request::get('date');
        $type = Request::get('type');
        $uniqId = Request::get('uniqId');
        
        $Calendar = cls::get('cal_Calendar');
        $query = $Calendar->getQuery();
        
        $query->where("#users IS NULL OR #users = ''");
        $query->orLikeKeylist('users', core_Users::getCurrent());
        
        $query->where("#state != 'rejected'");
        
        $query->where(array("#time >= '[#1#]' AND #time <= '[#2#]'", $date . ' 00:00:00', $date . ' 23:59:59'));
        
        $query->where(array("LOWER(#type) = '[#1#]'", strtolower($type)));
        
        $query->orderBy('time', 'ASC');
        
        $res = '';
        
        while ($rec = $query->fetch()) {
            $res .= '<tr><td>' . $Calendar->recToVerbal($rec, 'title')->event . '</td></tr>';
        }
        
        if ($res) {
            $res = '<table>' . $res . '</table>';
        }
        
        $resObj = new stdClass();
        $resObj->func = 'html';
        $resObj->arg = array('id' => $uniqId, 'html' => $res, 'replace' => true);
        
        return array($resObj);
    }
    
    
    /**
     * Името на стойността за кеша
     *
     * @param integer $userId
     *
     * @return string
     */
    public function getCacheTypeName($userId = null)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        return 'Portal_Calendar_' . $userId;
    }
    
    
    /**
     * Помощна функция за вземане на ключа за кеша
     *
     * @param stdClass $dRec
     * @param null|integer $userId
     *
     * @return string
     */
    public function getCacheKey($dRec, $userId = null)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        $cArr = bgerp_Portal::getPortalCacheKey($dRec, $userId);
        
        $Calendar = cls::get('cal_Calendar');
        
        $sInputField = bgerp_Portal::getPortalSearchInputFieldName($Calendar->searchInputField, $dRec->originIdCalc);
        
        $cSearchVal = Request::get($sInputField);
        $cSearchVal = isset($cSearchVal) ? $cSearchVal : '';
        $cArr[] = $cSearchVal;
        
        $month = Request::get('cal_month', 'int');
        $month = $month ? $month : date('m');
        $cArr[] = $month;

        $year = Request::get('cal_year', 'int');
        $year = $year ? $year : date('Y');
        $cArr[] = $year;
        
        $tPagaVar = $this->getPageVar($dRec->originIdCalc);
        $tPageVal = Request::get($tPagaVar);
        $tPageVal = isset($tPageVal) ? $tPageVal : 1;
        $cArr[] = $tPageVal;
        
        $cQuery = cal_Tasks::getQuery();
        $cQuery->where(array("#createdBy = '[#1#]'", $userId));
        $cQuery->orderBy('modifiedOn', 'DESC');
        $cQuery->limit(1);
        $cQuery->show('modifiedOn, id, containerId');
        $cRec = $cQuery->fetch();
        if ($cRec) {
            $cArr[] = $cRec->modifiedOn;
        }
        if ($cRec->containerId) {
            $cArr[] = bgerp_Recently::getLastDocumentSee($cRec->containerId, $userId, false);
        }
        
        $agendaStateQuery = cal_Calendar::getQuery();
        $agendaStateQuery->where("#users IS NULL OR #users = ''");
        $agendaStateQuery->orLikeKeylist('users', $userId);
        $agendaStateQuery->orderBy('createdOn', 'DESC');
        $agendaStateQuery->limit(1);
        $lastAgendaEventRec = serialize($agendaStateQuery->fetch());
        $cArr[] = $lastAgendaEventRec;
        
        $from = "{$year}-{$month}-01 00:00:00";
        $lastDay = date('d', mktime(12, 59, 59, $month + 1, 0, $year));
        $to = "{$year}-{$month}-{$lastDay} 23:59:59";
        $calendarStateQueryClone = cal_Calendar::getQuery();
        $calendarStateQueryClone->where("#users IS NULL OR #users = ''");
        $calendarStateQueryClone->orLikeKeylist('users', $userId);
        $calendarStateQueryClone->where(array("#time >= '[#1#]' AND #time <= '[#2#]'", $from, $to));
        $calendarStateQueryClone->orderBy('createdOn', 'DESC');
        $calendarStateQueryClone->limit(1);
        $lastCalendarEventRec = serialize($calendarStateQueryClone->fetch());
        $cArr[] = $lastCalendarEventRec;
        
        return md5(implode('|', $cArr));
    }
    
    
    /**
     * Помощна функция за вземане на името за страниране за задачите
     *
     * @param integer $oIdCalc
     * 
     * @return string
     */
    protected function getPageVar($oIdCalc)
    {
        return 'P_Cal_Tasks_Future_' . $oIdCalc;
    }
}
