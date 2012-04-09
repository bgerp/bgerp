<?php



/**
 * Календар - регистър за датите
 *
 *
 * @category  bgerp
 * @package   crm
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class crm_Calendar extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Календар";
    
    
    /**
     * Класове за автоматично зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, crm_Wrapper, plg_Sorting';
    
    
    /**
     * Полетата, които ще видим в таблицата
     */
    var $listFields = 'date,event=Събитие,type';
    
    /**
     *  @todo Чака за документация...
     */
    // var $searchFields = '';
    
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'no_one';
    
    
    /**
     * Кой може да чете
     */
    var $canRead = 'crm,admin';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Име на фирмата
        $this->FLD('date', new type_Date(array('cellAttr' => 'nowrap')), 'caption=Дата');
        $this->FLD('type', 'varchar(32)', 'caption=Тип');
        $this->FLD('classId', 'class(select=title)', 'caption=Клас');
        $this->FLD('objectId', 'int', 'caption=Обект');
    }
    
    
    /**
     * Предизвиква обновяване на информацията
     */
    static function updateEventsPerObject($caller, $objectId)
    {
        $classId = $caller->getClassId();
        
        // Изтриване на събитията до момента
        $query = self::getQuery();
        
        // Вземаме събитията за посочения обект
        $callerCalSrc = cls::getInterface('crm_CalendarEventsSourceIntf', $caller);
        
        $events = $callerCalSrc->getCalendarEvents($objectId);
        $eventsCnt = 0;
        
        // Добавяме ги в календара
        if(count($events)) {
            foreach($events as $eRec) {
                $eRec->id = crm_Calendar::fetchField("#date = '{$eRec->date}' AND #type = '{$eRec->type}' AND #classId = {$classId} AND #objectId = {$objectId}", 'id');
                $eRec->classId = $classId;
                $eRec->objectId = $objectId;
                
                if(!$eRec->id) {
                    crm_Calendar::save($eRec);
                    $eventsCnt++;
                }
                
                $idList .= ($idList ? ',' : '') . ($eRec->id);
            }
            
            // Изтриваме събитията за този обект, които не са от списъка на току-що добавените
            crm_Calendar::delete("#classId = '{$classId}' AND #objectId = {$objectId} AND NOT(#id IN ({$idList}))");
        }
        
        return $eventsCnt;
    }
    
    
    /**
     * Предизвиква изтриване на информацията за дадения обект
     */
    static function deleteEventsPerObject($caller, $objectId)
    {
        $classId = $caller->getClassId();
        
        // Изтриване на събитията до момента
        $eventsCnt = crm_Calendar::delete("#classId = '{$classId}' AND #objectId = {$objectId}");
        
        return $eventsCnt;
    }
    
    
    /**
     * Прилага филтъра, така че да се показват записите след посочената дата
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy("#date");
        
        if($from = $data->listFilter->rec->from) {
            $data->query->where("#date >= date('$from')");
        }
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('from', 'date', 'caption=От,input,silent');
        $data->listFilter->setdefault('from', date('Y-m-d'));
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'from';
        
        $data->listFilter->input('from', 'silent');
    }
    
    
    /**
     * Конвертира един запис в разбираем за човека вид
     * Входният параметър $rec е оригиналният запис от модела
     * резултата е вербалният еквивалент, получен до тук
     */
    static function recToVerbal($rec)
    {
        $row = parent::recToVerbal($rec);
        
        $row->date = dt::mysql2verbal($rec->date, "d-m-Y, D");
        
        if(dt::isHoliday($rec->date)) {
            $row->date = "<div style='color:red'>" . $row->date . "</div>";
        }
        $inst = cls::getInterface('crm_CalendarEventsSourceIntf', $rec->classId);
        
        $row->event = $inst->getVerbalCalendarEvent($rec->type, $rec->objectId, $rec->date);
        
        $today = date('Y-m-d');
        $tommorow = date('Y-m-d', time() + 24 * 60 * 60);
        $dayAT = date('Y-m-d', time() + 48 * 60 * 60);
        
        if($rec->date == $today) {
            $row->ROW_ATTR['style'] .= 'background-color:#ffcc99;';
        } elseif($rec->date == $tommorow) {
            $row->ROW_ATTR['style'] .= 'background-color:#ccffff;';
        } elseif($rec->date == $dayAT) {
            $row->ROW_ATTR['style'] .= 'background-color:#ccffcc;';
        } elseif($rec->date < $today) {
            $row->ROW_ATTR['style'] .= 'background-color:#ccc;';
        }
        
        return $row;
    }
    
    
    /**
     * Добавяне на официалните празници от drdata_Holidays след инсталиране на календара
     */
    static function on_AfterSetupMvc($mvc, &$html)
    {
        $html .= drdata_Holidays::addHolidaysToCalendar();
    }


    # PHP Calendar (version 2.3), written by Keith Devens
    # http://keithdevens.com/software/php_calendar
    #  see example at http://keithdevens.com/weblog
    # License: http://keithdevens.com/software/license
    static function generateCalendar($year, $month, $days = array(), $day_name_length = 3, $month_href = NULL, $first_day = 0, $pn = array())
    {
        $first_of_month = gmmktime(0,0,0,$month,1,$year);
        #remember that mktime will automatically correct if invalid dates are entered
        # for instance, mktime(0,0,0,12,32,1997) will be the date for Jan 1, 1998
        # this provides a built in "rounding" feature to generate_calendar()

        $day_names = array(); #generate all the day names according to the current locale
        for($n=0,$t=(3+$first_day)*86400; $n<7; $n++,$t+=86400) #January 4, 1970 was a Sunday
            $day_names[$n] = ucfirst(gmstrftime('%A',$t)); #%A means full textual day name

        list($month, $year, $month_name, $weekday) = explode(',',gmstrftime('%m,%Y,%B,%w',$first_of_month));
        $weekday = ($weekday + 7 - $first_day) % 7; #adjust for $first_day
        $title   = htmlentities(ucfirst($month_name)).'&nbsp;'.$year;  #note that some locales don't capitalize month and day names

        #Begin calendar. Uses a real <caption>. See http://diveintomark.org/archives/2002/07/03
        @list($p, $pl) = each($pn); @list($n, $nl) = each($pn); #previous and next links, if applicable
        if($p) $p = '<span class="ppcalendar-prev">'.($pl ? '<a href="'.htmlspecialchars($pl).'">'.$p.'</a>' : $p).'</span>&nbsp;';
        if($n) $n = '&nbsp;<span class="ppcalendar-next">'.($nl ? '<a href="'.htmlspecialchars($nl).'">'.$n.'</a>' : $n).'</span>';
        $calendar = '<table class="ppcalendar">'."\n".
            '<caption class="ppcalendar-month">'.$p.($month_href ? '<a href="'.htmlspecialchars($month_href).'">'.$title.'</a>' : $title).$n."</caption>\n<tr>";

        if($day_name_length){ #if the day names should be shown ($day_name_length > 0)
            #if day_name_length is >3, the full name of the day will be printed
            foreach($day_names as $d)
                $calendar .= '<th abbr="'.htmlentities($d).'">'.htmlentities($day_name_length < 4 ? substr($d,0,$day_name_length) : $d).'</th>';
            $calendar .= "</tr>\n<tr>";
        }

        if($weekday > 0) $calendar .= '<td colspan="'.$weekday.'">&nbsp;</td>'; #initial 'empty' days
        for($day=1,$days_in_month=gmdate('t',$first_of_month); $day<=$days_in_month; $day++,$weekday++){
            if($weekday == 7){
                $weekday   = 0; #start a new week
                $calendar .= "</tr>\n<tr>";
            }
            if(isset($days[$day]) and is_array($days[$day])){
                @list($link, $classes, $content) = $days[$day];
                if(is_null($content))  $content  = $day;
                $calendar .= '<td'.($classes ? ' class="p'.htmlspecialchars($classes).'">' : '>').
                    ($link ? '<a href="'.htmlspecialchars($link).'">'.$content.'</a>' : $content).'</td>';
            }
            else $calendar .= "<td>$day</td>";
        }

        if($weekday != 7) $calendar .= '<td colspan="'.(7-$weekday).'">&nbsp;</td>'; #remaining "empty" days

        return $calendar."</tr>\n</table>\n";
    }
    

    static function calendar($year = '', $month = '', $data = '', $base_url ='')
    {
			$str = '';
			$month_list = array('january', 'febuary', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december');
			$day_list = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
			$day = 1;
			$today = 0;
 
			if($year == '' || $month == '')// just use current yeear & month
				{
					$year = date('Y');
					$month = date('M');
 
				}
 
 
			$flag = 0;
 
			for($i = 0; $i < 12; $i++)
				{
					if(strtolower($month) == $month_list[$i])
						{
							if(intval($year) != 0)
								{
									$flag = 1;
									$month_num = $i + 1;
									break;
								}
						}
				}
 
 
			if($flag == 0)
				{
					$year = date('Y');
					$month = date('F');
					$month_num = date('m');
				}
 
 
 
			$next_year = $year;
			$prev_year = $year;
 
			$next_month = intval($month_num) + 1;
			$prev_month = intval($month_num) - 1;
 
			if($next_month == 13)
				{
					$next_month = 'january';
					$next_year = intval($year) + 1;
				}
			else
				{
					$next_month = $month_list[$next_month -1];
 
				}
 
			if($prev_month == 0)
				{
					$prev_month = 'december';
					$prev_year = intval($year) - 1;
				}
			else
				{
					$prev_month = $month_list[$prev_month - 1];
				}
 
 
 
			if($year == date('Y') && strtolower($month) == strtolower(date('F')))
				{	// set the flag that shows todays date but only in the current month - not past or future...
					$today = date('j');
				}
 
			$days_in_month = date("t", mktime(0, 0, 0, $month_num, 1, $year));
 
			$first_day_in_month = date('D', mktime(0,0,0, $month_num, 1, $year)); 
 
 
 
			$str .= '<table class="pcalendar">';
 
			$str .= '<thead>';
 
			$str .= '<tr><th class="pcell-prev"><a href="' . $base_url . '/' . $prev_year . '/' . $prev_month . '">prev</a></th><th colspan="5">' . ucfirst($month) . ' ' . $year . '</th><th class="pcell-next"><a href="' . $base_url . '/' . $next_year . '/' . $next_month . '">next</a></th></tr>';
 
 
 
			$str .= '<tr>';
				for($i = 0; $i < 7;$i++)
					{
						$str .= '<th class="pcell-header">' . $day_list[$i] . '</th>';
					}
			$str .= '</tr>';
 
			$str .= '</thead>';
 
			// get the first day of the month
 
 
			$str .= '<tbody>';
 
 
 
				while($day < $days_in_month)
					{
						$str .= '<tr>';
 
 
 
								for($i = 0; $i < 7; $i ++)
									{
 
										$cell = '&nbsp;';
 
										if(isset($data[$day]))
											{
												$cell = $data[$day];
											}
 
										$class = '';
 
										if($i > 4)
											{
												$class = ' class="pcell-weekend" ';
											}
 
 
										if($day == $today)
											{
												$class = ' class="pcell-today"';
											}
 
										if(($first_day_in_month == $day_list[$i] || $day > 1) && ($day < $days_in_month))
											{
												$str .= '<td ' . $class . '><div style="font-size:0.7em;float:left;color:#777;font-weight:normal;">(12)</div><div class="pcell-number">' . $day . '</div></td>';
												$day++;
											}
										else
											{
 
														$str .= '<td ' . $class . '>&nbsp;</td>';
											}
									}
 
						$str .= '</tr>';
					}
 
 
			$str .= '</tbody>';
 
			$str .= '</table>';
 
			return $str;
		}


    function renderCalendar($year, $month, $data = array(), $header = NULL)
    {   
        // Таймстамп на първия ден на месеца
        $firstDayTms = mktime(0, 0, 0, $month, 1, $year);

        // Броя на дните в месеца (= на последната дата в месеца);
        $lastDay = date('t', $firstDayTms);
        
        // Днес
        $today = date('j-n-Y');

        for($i = 1; $i <= $lastDay; $i++) {
            $t = mktime(0, 0, 0, $month, $i, $year);
            $monthArr[date('W', $t)][date('N', $t)] = $i;
        }

        $html = "<table class='mc-calendar'>";        

        $html .= "<tr><td colspan='8' style='padding:0px;'>{$header}</td><tr>";

        // Добавяне на втория хедър
        $html .= "<tr><td>" . tr('Сд') . "</td>";
        foreach(dt::$weekDays as $wdName) {
            $wdName = tr($wdName);
            $html .= "<td class='mc-wd-name'>{$wdName}</td>";
        }
        $html .= '<tr>';

        foreach($monthArr as $weekNum => $weekArr) {
            $html .= "<tr>";
            $html .= "<td class='mc-week-nb'>$weekNum</td>";
            for($wd = 1; $wd <= 7; $wd++) {
                if($d = $weekArr[$wd]) {
                    if($data[$d]->isHoliday) {
                        $class = 'mc-holiday';
                    } elseif($wd == 6) {
                        $class = 'mc-saturday';
                    } elseif($wd == 7) {
                        $class = 'mc-sunday';
                    } else {
                        $class = '';
                    }

                    if($today == "{$d}-{$month}-{$year}") {
                        $class .= ' mc-today';
                    } 

                    $html .= "<td class='{$class} mc-day'>$d</td>";
                } else {
                    $html .= "<td class='mc-empty'>&nbsp;</td>";
                }
            }
            $html .= "</tr>";
        }

        $html .= "</table>";
        
        return $html;
    }




    /**
     * Рендира блока за портала на текущия потребител
     */
    static function renderPortal()
    {
        $month = Request::get('cal_month', 'int');
        $year  = Request::get('cal_year', 'int');

        if(!$month || $month < 1 || $month > 12 || !$year || $year < 1970 || $year > 2038) {
            $year = date('Y');
            $month = date('n');
        }
                // Добавяне на първия хедър
        $currentMonth = tr(dt::$months[$month-1]) . ", " .$year;
        $pm = $month-1;
        if($pm == 0) {
            $pm = 12;
            $py = $year-1;
        } else {
            $py = $year;
        }
        $prevMonth = tr(dt::$months[$pm-1]) . ", " .$py;

        $nm = $month+1;
        if($nm == 12) {
            $nm = 1;
            $ny = $year+1;
        } else {
            $ny = $year;
        }
        $nextMonth = tr(dt::$months[$nm-1]) . ", " .$ny;
        
        $link = $_SERVER['REQUEST_URI'];
        $nextLink = Url::addParams($link, array('cal_month' => $nm, 'cal_year' => $ny));
        $prevtLink = Url::addParams($link, array('cal_month' => $pm, 'cal_year' => $py));

        $header = "<table class='mc-header' width='100%' cellpadding='0'>
                <tr>
                    <td align='left'><a href='{$prevtLink}'>{$prevMonth}</a></td>
                    <td align='center'><b>{$currentMonth}</b></td>
                    <td align='right'><a href='{$nextLink}'>{$nextMonth}</a></td>
                </tr>
            </table>";

        return static::renderCalendar($year, $month, $data, $header);
    }

}