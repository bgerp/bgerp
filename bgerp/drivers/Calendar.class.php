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
    public $interfaces = 'bgerp_PortalBlockIntf';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
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
     * @param null|integer $userId
     *
     * @return stdClass
     */
    public function prepare($dRec, $userId = null)
    {
        $resData = new stdClass();
        
        if (empty($userId)) {
            expect($userId = core_Users::getCurrent());
        }
        
        $tPageVar = 'P_Cal_Tasks_Future_' . $dRec->originIdCalc;
        
        $resData->month = Request::get('cal_month', 'int');
        $resData->month = str_pad($resData->month, 2, '0', STR_PAD_LEFT);
        $resData->year  = Request::get('cal_year', 'int');
        
        if(!$resData->month || $resData->month < 1 || $resData->month > 12 || !$resData->year || $resData->year < 1970 || $resData->year > 2038) {
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
        
        // Последния запис в модела - за деактивиране на кеша
        $calendarStateQueryClone = clone $resData->calendarState->query;
        $calendarStateQueryClone->orderBy('createdOn', 'DESC');
        $calendarStateQueryClone->limit(1);
        $lastCalendarEventRec = serialize($calendarStateQueryClone->fetch());
        
        // Само бележки за текущия потребител или за всички потребители
        $resData->agendaData = new stdClass();
        $resData->agendaData->query = cal_Calendar::getQuery();
        $resData->agendaData->query->where("#users IS NULL OR #users = ''");
        $resData->agendaData->query->orLikeKeylist('users', $userId);
        
        // Последния запис в модела - за деактивиране на кеша
        $agendaStateQueryClone = clone $resData->agendaData->query;
        $agendaStateQueryClone->orderBy('createdOn', 'DESC');
        $agendaStateQueryClone->limit(1);
        $lastAgendaEventRec = serialize($agendaStateQueryClone->fetch());
        
        // Съдържание на клетките на календара
        $Calendar = cls::get('cal_Calendar');
        
        $Calendar->searchInputField .= '_' . $dRec->originIdCalc;
        
        $resData->cacheKey = md5($dRec->id . '_' . $dRec->modifiedOn . '_' . $dRec->pages . '_' . $userId . '_' . Mode::get('screenMode') . '_' . $resData->month . '_' . $resData->year . '_' . Request::get($Calendar->searchInputField) . '_' . core_Lg::getCurrent() . '_' . $lastCalendarEventRec. '_' . $lastAgendaEventRec . '_' . dt::now(false));
        $resData->cacheType = 'Calendar';
        
        $resData->tpl = core_Cache::get($resData->cacheType, $resData->cacheKey);
        
        if (!$resData->tpl) {
            
            $Calendar->prepareListRecs($resData->calendarState);
            if (is_array($resData->calendarState->recs)) {
                $resData->cData = array();
                foreach($resData->calendarState->recs as $id => $rec) {
                    
                    $time = dt::mysql2timestamp($rec->time);
                    $i = (int) date('j', $time);
                    
                    if(!isset($resData->cData[$i])) {
                        $resData->cData[$i] = new stdClass();
                    }
                    
                    list ($d, $t) = explode(" ", $rec->time);
                    
                    if($rec->type == 'holiday' || $rec->type == 'non-working' || $rec->type == 'workday') {
                        $time = dt::mysql2timestamp($rec->time);
                        $i = (int) date('j', $time);
                        if(!isset($resData->cData[$i])) {
                            $resData->cData[$i] = new stdClass();
                        }
                        $resData->cData[$i]->type = $rec->type;
                    
                    } elseif($rec->type == 'working-travel') {
                        $resData->cData[$i]->html = "<img style='height10px;width:10px;' src=". sbf('img/16/working-travel.png') .">&nbsp;";
                    } elseif($rec->type == 'leaves') {
                        $resData->cData[$i]->html = "<img style='height10px;width:10px;' src=". sbf('img/16/leaves.png') .">&nbsp;";
                    } elseif($rec->type == 'sick') {
                        $resData->cData[$i]->html = "<img style='height10px;width:10px;' src=". sbf('img/16/sick.png') .">&nbsp;";
                    } elseif($rec->type == 'workday') {
                        // Нищо не се прави
                    } elseif($rec->type == 'task' || $rec->type == 'reminder'){
                        if ($arr[$d] != 'active') {
                            if($rec->state == 'active' || $rec->state == 'waiting') {
                                $resData->cData[$i]->html = "<img style='height10px;width:10px;' src=". sbf('img/16/star_2.png') .">&nbsp;";
                            } else {
                                $resData->cData[$i]->html = "<img style='height10px;width:10px;' src=". sbf('img/16/star_grey.png') .">&nbsp;";
                            }
                        }
                    }
                }
            }
            
            for($i = 1; $i <= 31; $i++) {
                if(!isset($resData->cData[$i])) {
                    $resData->cData[$i] = new stdClass();
                }
                $resData->cData[$i]->url = toUrl(array('cal_Calendar', 'day', 'from' => "{$i}.{$resData->month}.{$resData->year}"));;
            }
            
            // Съдържание на списъка със събития
            
            // От вчера
            $previousDayTms = mktime(0, 0, 0, date('m'), date('j')-1, date('Y'));
            $from = dt::timestamp2mysql($previousDayTms);
            
            // До вдругиден
            $afterTwoDays = mktime(0, 0, -1, date('m'), date('j')+3, date('Y'));
            $to = dt::timestamp2mysql($afterTwoDays);
            
            // Подготвяме данните за бележника
            $Calendar = cls::get('cal_Calendar');
            if (Request::get($Calendar->searchInputField)) {
                $from = dt::addDays(-30, $from);
                $to = dt::addDays(360, $to);
            }
            
            $resData->agendaData->query->where(array("#time >= '[#1#]' AND #time <= '[#2#]'", $from, $to));
            $Calendar->prepareListFields($resData->agendaData);
            $Calendar->prepareListFilter($resData->agendaData);
            $Calendar->prepareListRecs($resData->agendaData);
            $Calendar->prepareListRows($resData->agendaData);
        }
        
        $pArr = array();
        $pArr['tPageVar'] = $tPageVar;
        $pArr['search'] = Request::get($Calendar->searchInputField);
        $resData->EventsData = $this->prepareCalendarEvents($userId, $pArr);
        
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
            $select = ht::createSelect('dropdown-cal', $data->monthOptions->opt, $data->monthOptions->currentM, array('onchange' => "javascript:location.href = this.value;", 'class' => 'portal-select'));
            
            $header = "<table class='mc-header' style='width:100%'>
                    <tr>
                    <td class='aleft'><a href='{$data->monthOptions->prevtLink}'>{$data->monthOptions->prevMonth}</a></td>
                    <td class='centered'><span class='metro-dropdown-portal'>{$select}</span>
                    <td class='aright'><a href='{$data->monthOptions->nextLink}'>{$data->monthOptions->nextMonth}</a></td>
                    </tr>
                    </table>";
            
            $searchForm = $Calendar->getForm();
            bgerp_Portal::prepareSearchForm($Calendar, $searchForm);
            
            $data->tpl = new ET(tr('|*<div class="clearfix21 portal">
                                        <div class="legend" id="calendarPortal">[#CAL_TITLE#]
                                            [#SEARCH_FORM#]
                                        </div>
                                        <div style="font-size: 0.9em">
                                        <!--ET_BEGIN NOW--><div style="color:#5f1f3e; font-style: italic; background-color:#ffc;">
                                            [#NOW_DATE#]
                                            <div>[#NOW#]</div>
                                        </div><!--ET_END NOW-->
                                            
                                            <!--ET_BEGIN NEXT_DAY_OTHER--><div style="background-color:#efc;color:#070;font-style: italic;">[#NEXT_DAY_OTHER_DATE#][#NEXT_DAY_OTHER#]</div><!--ET_END NEXT_DAY_OTHER-->
                                            </div>
                                        [#MONTH_CALENDAR#] <br> [#AGENDA#]

                                        <!--ET_BEGIN FUTURE--><div>[#NFUTURE_DATE#]</div>[#FUTURE#]<!--ET_END FUTURE-->
                                    </div>'
                            
                                    ));
            
            $tArr = $data->EventsData;
            
            $today = dt::now(false);
            $tomorrow = dt::addDays(1, $today, false);
            $nextDay = dt::addDays(2, $today, false);
            
            $format = Mode::is('screenMode', 'narrow') ? 'd-M-year, D': 'd F-YEAR, l';
            
            // Показваме събитията близките дни
            foreach ((array)$tArr['now'] as $tDate => $tRowArr) {
                
                $dStr = dt::mysql2verbal($tDate, $format, null, false);
                
                if ($today == $tDate) {
                    $dVerb = tr('Днес');
                } elseif ($tDate == $tomorrow) {
                    $dVerb = tr('Утре');
                }elseif ($tDate == $nextDay) {
                    $dVerb = tr('Вдругиден');
                } else {
                    $dVerb = '';
                }
                
                $dVerb .= $dVerb ? ', ' : '';
                $dVerb .= $dStr;
                
                Mode::set('ysn', true);
                $res = (object)array('day' => $dVerb);
                $Calendar->invoke('AfterPrepareGroupDate', array(&$res, $tDate));
                
                $dVerb = $res->day;
                
                $this->invoke('AfterPrepareDateName', array(&$dVerb, $tDate));
                
                $dBlock = $data->tpl->getBlock('NOW');
                $dBlock->replace($dVerb, 'NOW_DATE');
                
                foreach ($tRowArr as $tRow) {
                    $dBlock->append($tRow->title, 'NOW');
                }
                
                $dBlock->removeBlocks();
                $dBlock->append2master();
            }
            
            // Показваме събитията за в бъдеще
            $data->tpl->append($tArr['future'], 'FUTURE');
            $data->tpl->replace(tr('По-нататък'), 'NFUTURE_DATE');
            
            if (!Mode::is('screenMode', 'narrow')) {
                $data->tpl->replace(tr('Календар'), 'CAL_TITLE');
            } else {
                $data->tpl->replace('&nbsp;', 'CAL_TITLE');
            }
            
            $data->tpl->replace($searchForm->renderHtml(), 'SEARCH_FORM');
            
            $data->tpl->replace(cal_Calendar::renderCalendar($data->year, $data->month, $data->cData, $header), 'MONTH_CALENDAR');
            
            $data->tpl->replace($Calendar->renderListTable($data->agendaData), 'AGENDA');
            
            $data->tpl->push('js/PortalSearch.js', 'JS');
            jquery_Jquery::run($data->tpl, 'portalSearch();', true);
            jquery_Jquery::runAfterAjax($data->tpl, 'portalSearch');
            
            $cacheLifetime = doc_Setup::get('CACHE_LIFETIME') ? doc_Setup::get('CACHE_LIFETIME') : 5;
            core_Cache::set($data->cacheType, $data->cacheKey, $data->tpl, $cacheLifetime);
        }
        
        return $data->tpl;
    }
    
    
    /**
     * Връща типа на блока за портала
     *
     * @return string - other, tasks, notifications, calendar, recently
     */
    public function getBlockType()
    {
        return 'calendar';
    }
    
    
    /**
     * Помощна функция за вземане на събитията за съответни дни
     * 
     * @param null|integer $userId
     * @param array $pArr
     * 
     * @return array
     */
    protected function prepareCalendarEvents($userId = null, $pArr = array())
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        $query = cal_Tasks::getQuery();
        
        if ($pArr['search']) {
            plg_Search::applySearch($pArr['search'], $query);
        }
        
        $query->where("#state != 'rejected'");
        $query->where("#state != 'draft'");
        
        $query->likeKeylist('assign', $userId);
        
        $query->where("#timeStart IS NOT NULL");
        $query->orWhere("#timeEnd IS NOT NULL");
        
        $today = dt::now(false);
        $todayF = $today . ' 00:00:00';
        $query->where(array("#expectationTimeStart >= '[#1#]'", $todayF));
        $query->orWhere(array("#expectationTimeEnd >= '[#1#]'", $todayF));
        
        $query->XPR('expectationTimeOrder', 'datetime', "IF((#expectationTimeStart < '{$todayF}'), #expectationTimeEnd, #expectationTimeStart)");
        
        $query->orderBy('expectationTimeOrder', 'ASC');
        
        // Намираме работните дни, така че да останат 3 работни дни винаги
        $nWorkDay = cal_Calendar::nextWorkingDay(dt::addDays(-1, $today, false), null, 1);
        $endWorkingDayCnt = (dt::daysBetween($nWorkDay, $today)) ? 3 : 2;
        $endWorkingDay = cal_Calendar::nextWorkingDay($today, null, $endWorkingDayCnt);
        $endWorkingDay .= ' 23:59:59';
        
        // Задачите в бъдеще
        $fQuery = clone $query;
        $fQuery->where(array("#expectationTimeOrder >= '[#1#]'", $endWorkingDay));
        
        // Задачите за близко бъдеще
        $query->where(array("#expectationTimeOrder <= '[#1#]'", $endWorkingDay));
        
        $resArr = array();
        while ($rec = $query->fetch()) {
            list($orderDate) = explode(' ', $rec->expectationTimeOrder);
            $resArr['now'][$orderDate][$rec->id] = $this->getRowForTask($rec);
        }
        
        $Tasks = cls::get('cal_Tasks');
        
        $fTasks = new stdClass();
        $fTasks->query = $fQuery;
        
        $Tasks->listItemsPerPage = 5;
        $fTasks->usePortalArrange = false;
        $fTasks->listFields = 'title,progress';
        
        $Tasks->prepareListPager($fTasks);
        
        if ($pArr['tPageVar']) {
            $fTasks->pager->pageVar = $pArr['tPageVar'];
        }
        
        $Tasks->prepareListFields($fTasks);
        $Tasks->prepareListFilter($fTasks);
        $Tasks->prepareListRecs($fTasks);
        $Tasks->prepareListRows($fTasks);
        
        if ($fTasks->recs) {
            $fTpl = new ET("[#table#][#pager#]");
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
     * 
     * @return stdClass
     */
    protected function getRowForTask($rec)
    {
        $Tasks = cls::get('cal_Tasks');
        
        // Полета, които ще вербализираме
        $f = array('title', 'progress');
        
        $rToVerb = cal_Tasks::recToVerbal($rec, $f);
        
        $subTitle = $Tasks->getDocumentRow($rec->id)->subTitle;
        $subTitle = "<div class='threadSubTitle'>{$subTitle}</div>";
        
        $title = str::limitLen(type_Varchar::escape($rec->title), 60, 30, ' ... ', true);
        $rToVerb->title = ht::createLink($title, cal_Tasks::getSingleUrlArray($rec->id), null, array('ef_icon' => $Tasks->getIcon($rec->id)));
        
        $rToVerb->title->append("<span style='float: right;'>" . $rToVerb->progress . '</span>');
        
        $rToVerb->title->append($subTitle);
        
        return $rToVerb;
    }
}
