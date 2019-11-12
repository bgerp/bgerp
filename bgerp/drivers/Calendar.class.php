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
        $calendarStateQueryClone->show('id');
        $lastCalendarEventId = $calendarStateQueryClone->fetch()->id;

        // Само бележки за текущия потребител или за всички потребители
        $resData->agendaData = new stdClass();
        $resData->agendaData->query = cal_Calendar::getQuery();
        $resData->agendaData->query->where("#users IS NULL OR #users = ''");
        $resData->agendaData->query->orLikeKeylist('users', $userId);
        
        // Последния запис в модела - за деактивиране на кеша
        $agendaStateQueryClone = clone $resData->agendaData->query;
        $agendaStateQueryClone->orderBy('createdOn', 'DESC');
        $agendaStateQueryClone->limit(1);
        $agendaStateQueryClone->show('id');
        $lastAgendaEventId = $agendaStateQueryClone->fetch()->id;
        
        $resData->cacheKey = md5($dRec->pages . '_' . $userId . '_' . Request::get('ajax_mode') . '_' . Mode::get('screenMode') . '_' . $resData->month . '_' . $resData->year . '_' . Request::get('calSearch') . '_' . core_Lg::getCurrent() . '_' . $lastCalendarEventId . '_' . $lastAgendaEventId);
        $resData->cacheType = 'Calendar';
        
        $resData->tpl = core_Cache::get($resData->cacheType, $resData->cacheKey);
        
        if (!$resData->tpl) {
            
            // Съдържание на клетките на календара
            $Calendar = cls::get('cal_Calendar');
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
            if(Request::get($Calendar->searchInputField)) {
                $from = dt::addDays(-30, $from);
                $to = dt::addDays(360, $to);
            }
            
            $resData->agendaData->query->where(array("#time >= '[#1#]' AND #time <= '[#2#]'", $from, $to));
            $Calendar->prepareListFields($resData->agendaData);
            $Calendar->prepareListFilter($resData->agendaData);
            $Calendar->prepareListRecs($resData->agendaData);
            $Calendar->prepareListRows($resData->agendaData);
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
            
            $data->tpl = new ET('<div class="clearfix21 portal">
                                    <div class="legend" id="calendarPortal" style="height:20px;">[#CAL_TITLE#]
                                    [#SEARCH_FORM#]
                                    </div>
                                    [#MONTH_CALENDAR#] <br> [#AGENDA#]
                                    </div>');
            
            if (!Mode::is('screenMode', 'narrow')) {
                $data->tpl->replace(tr('Календар'), 'CAL_TITLE');
            } else {
                $data->tpl->replace('&nbsp;', 'CAL_TITLE');
            }
            
            $data->tpl->replace($searchForm->renderHtml(), 'SEARCH_FORM');
            
            $data->tpl->replace(cal_Calendar::renderCalendar($data->year, $data->month, $data->cData, $header), 'MONTH_CALENDAR');
            
            $data->tpl->replace($Calendar->renderListTable($data->agendaData), 'AGENDA');
            
            $cacheLifetime = doc_Setup::get('CACHE_LIFETIME') ? doc_Setup::get('CACHE_LIFETIME') : 5;
            core_Cache::set($data->cacheType, $data->cacheKey, $data->tpl, $cacheLifetime);
        }
        
        return $data->tpl;
    }
}
