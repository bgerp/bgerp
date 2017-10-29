<?php



/**
 * Календар - всички събития
 *
 *
 * @category  bgerp
 * @package   cal
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cal_Calendar extends core_Master
{
    
    
    /**
     * Заглавие
     */
    public $title = "Календар на събития и празници";
    
    
    /**
     * Класове за автоматично зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools, cal_Wrapper, plg_Sorting, plg_State, plg_GroupByDate, plg_Printing, plg_Search';
    
    
    /**
     * полета от БД по които ще се търси
     */
    public $searchFields = 'title';
    
    
    /**
     * Как се казва полето за пълнотекстово търсене
     */
    public $searchInputField = 'calSearch';
    

    /**
     * Името на полито, по което плъгина GroupByDate ще групира редовете
     */
    public $groupByDateField = 'time';
    

    /**
     * Полетата, които ще видим в таблицата
     */
    public $listFields = 'time,event=Събитие';

    
    /**
     * Кой може да пише
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой може да чете
     */
    public $canRead = 'powerUser';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'powerUser';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'powerUser';
    
	
    /**
     * Кой има право да го види?
     */
    public $canView = 'powerUser';
    
    
    // Масив с цветове за събитията
    static $colors = array( "#610b7d", 
				    	"#1b7d23",
				    	"#4a4e7d",
				    	"#7d6e23", 
				    	"#33757d",
				    	"#211b7d", 
				    	"#72147d",
				    	"Violet",
				    	"Green",
				    	"DeepPink ",
				    	"MediumVioletRed",
				    	"#0d777d",
				    	"Indigo",
				    	"#7d1c24",
				    	"DarkSlateBlue",
				    	"#7b237d", 
				    	"DarkMagenta ",
				    	"#610b7d", 
				    	"#1b7d23",
				    	"#4a4e7d",
				    	"#7d6e23", 
				    	"#33757d",
				    	"#211b7d", 
				    	"#72147d",
				    	"Violet",
				    	"Green",
				    	"DeepPink ",
				    	"MediumVioletRed",
				    	"#0d777d",
				    	"Indigo",
				    	"#7d1c24",
				    	"DarkSlateBlue",
				    	"#7b237d", 
				    	"DarkMagenta ");
    
    
    //Начална стойнности за начало на деня
    static	$tr = 8;
    	
    //Начална стойност за края на деня
    static	$tk = 18;
    
    // Дните от седмицата
    static $weekDays = array('Понеделник', 'Вторник', 'Сряда', 'Четвъртък', 'Петък', 'Събота', 'Неделя');
    
    // Дните от седмицата на английски
    static $weekDaysEn = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
    
    // Масив с часове в деня
    static $hours = array( "allDay" => "Цял ден");
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        // Уникален ключ за събитието
        $this->FLD('key', 'varchar(40)', 'caption=Ключ');

        // Дата на събититието
        $this->FLD('time', 'datetime(format=smartTime)', 'caption=Време,tdClass=portal-date');
        
        // Продължителност на събитието
        $this->FLD('duration', 'time', 'caption=Продължителност');

        // Тип на събититето. Той определя и иконата на събититето
        $this->FLD('type', 'varchar(32)', 'caption=Тип');
        
        // За кои потребители се отнася събитието. Празно => за всички
        $this->FLD('users', 'keylist(mvc=core_Users,title=nick)', 'caption=Потребители');

        // Заглавие на събитието
        $this->FLD('title', 'varchar', 'caption=Заглавие');

        // Приоритет 1=Нисък, 2=Нормален, 3=Висок, 4=Критичен, 0=Никакъв (приключена задача)
        $this->FLD('priority', 'int', 'caption=Приоритет,notNull,value=1');

        // Локално URL към обект, даващ повече информация за събитието
        $this->FLD('url',  'varchar', 'caption=Url,column=none');
        
        // Дали събитието се отнася за целия ден
        $this->FLD('allDay', 'enum(yes=Да,no=Не)', 'caption=Цял ден?');
        
        // Индекси
         $this->setDbUnique('key');
    }


    /**
     * Обновява събитията в календара
     *
     * @param  array     $events      Масив със събития
     * @param  date      $fromDate    Начало на периода за който се отнасят събитията
     * @param  date      $fromDate    Край на периода за който се отнасят събитията
     * @param  string    $prefix      Префикс на ключовете за събитията от този източник
     * 
     * @return array                  Статус на операцията, който съдържа:
     *      о ['updated'] броя на обновените събития
     * 
     */
    public static function updateEvents($events, $fromDate, $toDate, $prefix)
    {
        $query    = self::getQuery();
        $fromTime = $fromDate . ' 00:00:00';
        $toTime   = $toDate   . ' 23:59:59';

        $query->where("#time >= '{$fromTime}' AND #time <= '{$toTime}' AND #key LIKE '{$prefix}%'");
        
        // Извличаме съществуващите събития за този префикс
        $exEvents = array();
        while($rec = $query->fetch()) {
            $exEvents[$rec->key] = $rec;
        }
 
        // Инициализираме резултатния масив
        $res = array(
            'new' => 0,
            'updated' => 0,
            'deleted' => 0
            );

        // Обновяваме информацията за новопостъпилите събития
        if(count($events)) {
            foreach($events as $e) {
          
                if(!trim($e->users)) {
                    unset($e->users);
                }
                if(($e->id = $exEvents[$e->key]->id) ||
                   ($e->id = self::fetchField(array("#key = '[#1#]'", $e->key), 'id')) ) {
                    unset($exEvents[$e->key]);
                    $res['updated']++;
                } else {
                    $res['new']++;
                }

                // Ако ->url е масив, от него правим локално url без протекция
                $e->url = toUrl($e->url, 'local', FALSE);

                self::save($e);
            }
        }

        // Изтриваме старите записи, които не са обновени
        foreach($exEvents as $e) {
            self::delete("#key = '{$e->key}'");
            $res['deleted']++;
        }
        
        return $res;
    }

    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
    	$cu = core_Users::getCurrent();

    	$data->listFilter->view = 'horizontal';
    	
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('from', 'date', 'caption=От,input,silent, width = 150px,autoFilter');
        $data->listFilter->FNC('selectedUsers', 'users(rolesForAll = ceo|hrMaster, rolesForTeams = manager|hr, showClosedGroups)', 'caption=Потребител,input,silent,autoFilter');
        $data->listFilter->setdefault('from', date('Y-m-d'));
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        if(strtolower(Request::get('Act')) == 'show'){
            
            $data->listFilter->showFields = $mvc->searchInputField;
            
            bgerp_Portal::prepareSearchForm($mvc, $data->listFilter);
        } elseif ($data->action === "list"){
            // Показваме само това поле. Иначе и другите полета 
            // на модела ще се появят
        	$data->listFilter->showFields = "from, {$mvc->searchInputField}, selectedUsers";
        } else{
        	$data->listFilter->showFields = 'from, selectedUsers';
        }
        
        $data->listFilter->input('selectedUsers, from', 'silent');
        
        $data->query->orderBy("#time=ASC,#priority=DESC");
        
        if($data->action == 'list' || $data->action == 'day' || $data->action == 'week'){
	        if($from = $data->listFilter->rec->from) {
	        	
	            $data->query->where("#time >= date('$from')");
	          	        
	       }
        }

      	if(!$data->listFilter->rec->selectedUsers) {
      	
		  $data->listFilter->rec->selectedUsers = 
		  keylist::fromArray(arr::make(core_Users::getCurrent('id'), TRUE));
      	}  
		
      	$data->query->likeKeylist('users', $data->listFilter->rec->selectedUsers);
	    $data->query->orWhere('#users IS NULL OR #users = ""');
 
    }

    
    protected static function on_AfterRenderWrapping($mvc, &$tpl)
    {
    	$tpl->push('cal/tpl/style.css', 'CSS');
    	$tpl->push('cal/js/mouseEvent.js', 'JS');
    	
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'day' || $action == 'week' || $action == 'month' || $action == 'year'){
	    	 $requiredRoles = 'user';
        }
    }
    
    
    /**
     * Конвертира един запис в разбираем за човека вид
     * Входният параметър $rec е оригиналният запис от модела
     * резултата е вербалният еквивалент, получен до тук
     */
    public static function recToVerbal(&$rec, $fields = NULL)
    {
        if(isset($fields) && $fields['-list']) {
            $fields += arr::make('time,duration,type,title,priority', TRUE);
            $row = parent::recToVerbal_($rec, $fields);
        } else {
    	    $row = parent::recToVerbal_($rec);
        }

    	$lowerType = strtolower($rec->type);
       
        $url = parseLocalUrl($rec->url, FALSE);
       
        $cUrl = getCurrentUrl();
        // TODO да стане с интерфейс
        $isLink = TRUE;
        $mvc = cls::get($url['Ctr']);
        
        // проверка, този клас mvc ли е?
        if($mvc instanceof core_Mvc) {
            
            $class = $url['Ctr'];
            // записа има ли достъп до екшъните му
            switch ($url['Act']) {
                case 'Single':
                    $isLink = $class::haveRightFor('single', $url['id']);
                    break;
                case 'list':
                    $isLink = $class::haveRightFor('list');
                    break;
                case 'default':
                    $isLink = $class::haveRightFor('default');
                    break;
            }
        }
       
        // TODO
        // картинката
        $attr = array();
        if(!strpos($rec->type, '/')) {
         	$attr['ef_icon'] = "img/16/{$lowerType}.png";
         	
         	$i = "img/16/{$lowerType}.png";
         	$img = "<img class='calImg' src=". sbf($i) .">&nbsp;";
         	
    	} elseif($rec->type = 'reminder') {
         	$attr['ef_icon'] = "img/16/alarm_clock.png";
         	
         	$i = "img/16/alarm_clock.png";
         	$img = "<img class='calImg' src=". sbf($i) .">&nbsp;";
    	} else { 
            $attr['ef_icon'] = $rec->type;
            
            $i = $rec->type;
            $img = "<img class='calImg' src=". sbf($i) .">&nbsp;";
        }

        $attr = ht::addBackgroundIcon($attr);

        if($rec->priority <= 0) {
            $attr['style'] .= 'color:#aaa;text-decoration:line-through;';
        }

        // TODO
        // правим линк за изгледите
        if($isLink){
            $row->event = ht::createLink($row->title, $url, NULL, $attr);
            
            if($cUrl['Act'] == "day" || $cUrl['Act'] == "week" || $cUrl['Act'] == "month"){
                if($rec->type == 'leaves' || $rec->type == 'sick' || $rec->type == 'task' || $rec->type == 'working-travel'){
                    $row->event = "<div class='task'>" . $img . ht::createLink("<p class='state-{$rec->state}'>".$row->title . "</p>", $url, NULL)."</div>";
                } else{
                    $row->event = "<div class='holiday-title'>" . $img . ht::createLink("<p class='calWeek'>".$row->title . "</p>", $url, NULL)."</div>";
                }
            }
        // или ако нямаме достъп, правим елемент
        } else {
            $addEnd = FALSE;
            if ($url['Ctr'] == 'crm_Persons' || $url['Ctr'] == 'hr_Leaves' || $url['Ctr'] == 'hr_Sickdays' || $url['Ctr'] == 'hr_Trips') {
                $row->event = ht::createElement("span", $attr, $row->title);
                $addEnd = TRUE;
            }
            
            if($url['Ctr'] == 'crm_Persons' && ($url['id'])) {
                
                $pRec = crm_Persons::fetch($url['id']);
                
                if ($pRec->inCharge) {
                    $row->event .= ' (' . crm_Profiles::createLink($pRec->inCharge) . ')';
                }
            }
            
            if ($addEnd) {
                $row->event = "<div title='{$row->title}' style='margin-bottom: 5px;font-style=normal;'>" . $row->event . "</div>";
            }
        }
        
        // TODO
        $today     = date('Y-m-d');
        $tommorow  = date('Y-m-d', time() + 24 * 60 * 60);
        $dayAT = date('Y-m-d', time() + 48 * 60 * 60);
        $yesterday = date('Y-m-d', time() - 24 * 60 * 60);
      
        list($rec->date,) = explode(' ', $rec->time);

        $row->date = dt::mysql2verbal($rec->time, 'd.m.Y');        

        if($rec->date == $today) {
            $row->ROW_ATTR['style'] .= 'background-color:#ffc;';
        } elseif($rec->date == $tommorow) {
            $row->ROW_ATTR['style'] .= 'background-color:#efc;';
        } elseif($rec->date == $dayAT) {
            $row->ROW_ATTR['style'] .= 'background-color:#dfc;';
        } elseif($rec->date == $yesterday) {
            $row->ROW_ATTR['style'] .= 'background-color:#eee;';
        } elseif($rec->date > $today) {
            $row->ROW_ATTR['style'] .= 'background-color:#cfc;';
        } elseif($rec->date < $yesterday) {
            $row->ROW_ATTR['style'] .= 'background-color:#ddd;';
        }

        
        return $row;
    }


    /**
     * Рендира календар за посочения месец
     *
     * @param int  $year Година
     * @param int  $month Месец
     * @param array $data  Масив с данни за дните в месеца
     *     о  $data[...]->isHoliday - дали е празник
     *     о  $data[...]->url - URL, където трябва да сочи посочения ден
     *     о  $data[...]->html - съдържание на клетката, осен датата
     * @param string $header - заглавие на календара
     *
     * @return string
     */
    public static function renderCalendar($year, $month, $data = array(), $header = NULL)
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

        $html .= "<tr><td colspan='8' style='padding:0px;'>{$header}</td></tr>";

        // Добавяне на втория хедър
        $html .= "<tr><td>" . tr('Сд') . "</td>";
        foreach(dt::$weekDays as $wdName) {
            $wdName = tr($wdName);
            $html .= "<td class='mc-wd-name'>{$wdName}</td>";
        }
        $html .= '</tr>';
       
        foreach($monthArr as $weekNum => $weekArr) {
            $html .= "<tr>";
            $html .= "<td class='mc-week-nb'>$weekNum</td>";
            for($wd = 1; $wd <= 7; $wd++) {
                if($d = $weekArr[$wd]) { 
                    if($data[$d]->type == 'holiday') {  
                        $class = 'mc-holiday';
                    } elseif(($wd == 6 || ($data[$d]->type == 'non-working' && $wd >= 4) ) && ($data[$d]->type != 'workday')) {
                        $class = 'mc-saturday';
                    } elseif(($wd == 7 || ($data[$d]->type == 'non-working' && $wd < 4) ) && ($data[$d]->type != 'workday')) {
                        $class = 'mc-sunday';
                    } else {
                        $class = '';
                    }
                    if($today == "{$d}-{$month}-{$year}") {
                        $class .= ' mc-today';
                    }
                    
                    if($data[$d]->type == '0'){
                    	$class .= ' rest'; 
                    }elseif($data[$d]->type == '1'){
                    	$class .= ' first';
                    }elseif($data[$d]->type == '2'){
                    	$class .= ' second';
                    }elseif($data[$d]->type == '3'){
                    	$class .= ' third';
                    }elseif($data[$d]->type == '4'){
                    	$class .= ' diurnal';
                    }elseif($data[$d]->type == '5'){
                    	$class .= ' leave';
                    }elseif($data[$d]->type == '6'){
                    	$class .= ' sick';
                    }elseif($data[$d]->type == '7'){
                    	$class .= ' traveling';
                    }
                    
                                        
                    // URL към което сочи деня
                    $url = $data[$d]->url;
                 
                    // Съдържание на клетката, освен датата
                    $content = $data[$d]->html;

                    $html .= "<td class='{$class} mc-day' onclick='document.location=\"{$url}\"'>{$content}$d</td>";

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
    public static function renderPortal()
    {

        $month = Request::get('cal_month', 'int');
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $year  = Request::get('cal_year', 'int');
        
        if(!$month || $month < 1 || $month > 12 || !$year || $year < 1970 || $year > 2038) {
            $year = date('Y');
            $month = date('n');
        }
    
        $monthOpt = self::prepareMonthOptions();

        $select = ht::createSelect('dropdown-cal', $monthOpt->opt, $monthOpt->currentM, array('onchange' => "javascript:location.href = this.value;", 'class' => 'portal-select'));
     
        // правим заглавието на календара, 
        // който ще се състои от линк-селект-линк
        // като линковете ще са един месец напред и назад в зависимост от избраната стойност в селекта
        $header = "<table class='mc-header' style='width:100%'>
        <tr>
        <td class='aleft'><a href='{$monthOpt->prevtLink}'>{$monthOpt->prevMonth}</a></td>
        <td class='centered'><span class='metro-dropdown-portal'>{$select}</span>
        <td class='aright'><a href='{$monthOpt->nextLink}'>{$monthOpt->nextMonth}</a></td>
        </tr>
        </table>";
        
        // Съдържание на клетките на календара 
	       
        //От началото на месеца
        $from = "{$year}-{$month}-01 00:00:00";
        
        // До последния ден за месеца
        $lastDay = date('d', mktime(12, 59, 59, $month + 1, 0, $year));
        $to = "{$year}-{$month}-{$lastDay} 23:59:59";
       
        // Подготвяме заглавието на таблицата
        //$state->title = tr("Календар");

        $state = new stdClass();
        $state->query = self::getQuery();
        
    
        // Само събитията за текущия потребител или за всички потребители
        $cu = core_Users::getCurrent();
        $state->query->where("#users IS NULL OR #users = ''");
        $state->query->orLikeKeylist('users', "|$cu|");

        $state->query->where("#time >= '{$from}' AND #time <= '{$to}'");

        $Calendar = cls::get('cal_Calendar');
        $Calendar->prepareListFields($state);
        $Calendar->prepareListFilter($state);
        $Calendar->prepareListRecs($state); 
        $Calendar->prepareListRows($state);

        // Подготвяме лентата с инструменти
        $Calendar->prepareListToolbar($state);

        if (is_array($state->recs)) {
            $data = array();
            foreach($state->recs as $id => $rec) {
                
                $time = dt::mysql2timestamp($rec->time);
                $i = (int) date('j', $time);
                
                if(!isset($data[$i])) {
                    $data[$i] = new stdClass();
                     
                }
                 
                list ($d, $t) = explode(" ", $rec->time);
            
                if($rec->type == 'holiday' || $rec->type == 'non-working' || $rec->type == 'workday') {
                    $time = dt::mysql2timestamp($rec->time);
                    $i = (int) date('j', $time);
                    if(!isset($data[$i])) {
                        $data[$i] = new stdClass();
                      
                    }
                    $data[$i]->type = $rec->type;
                   
                } elseif($rec->type == 'working-travel') { 
                    
                    $data[$i]->html = "<img style='height10px;width:10px;' src=". sbf('img/16/working-travel.png') .">&nbsp;";

                } elseif($rec->type == 'leaves') { 
                    
                    $data[$i]->html = "<img style='height10px;width:10px;' src=". sbf('img/16/leaves.png') .">&nbsp;";

                } elseif($rec->type == 'sick') { 
                    
                    $data[$i]->html = "<img style='height10px;width:10px;' src=". sbf('img/16/sick.png') .">&nbsp;";

                } elseif($rec->type == 'workday') {
                
                } elseif($rec->type == 'task' || $rec->type == 'reminder'){

                	if ($arr[$d] != 'active') { 
                		if($rec->state == 'active' || $rec->state == 'waiting') { 
                			$data[$i]->html = "<img style='height10px;width:10px;' src=". sbf('img/16/star_2.png') .">&nbsp;";
                		} else {
                			$data[$i]->html = "<img style='height10px;width:10px;' src=". sbf('img/16/star_grey.png') .">&nbsp;";
                		}
                	}  
                }
            }
        }
        
        for($i = 1; $i <= 31; $i++) {
            if(!isset($data[$i])) {
                $data[$i] = new stdClass();
            }
            $data[$i]->url = toUrl(array('cal_Calendar', 'day', 'from' => "{$i}.{$month}.{$year}"));;
        }
        
        $tpl = new ET("[#MONTH_CALENDAR#] <br> [#AGENDA#]");

        $tpl->replace(static::renderCalendar($year, $month, $data, $header), 'MONTH_CALENDAR');


        // Съдържание на списъка със събития

        // От вчера 
        $previousDayTms = mktime(0, 0, 0, date('m'), date('j')-1, date('Y'));
        $from = dt::timestamp2mysql($previousDayTms);

        // До вдругиден
        $afterTwoDays = mktime(0, 0, -1, date('m'), date('j')+3, date('Y'));
        $to = dt::timestamp2mysql($afterTwoDays);
       
        $state = new stdClass();
        $state->query = self::getQuery();

        // Само събитията за текущия потребител или за всички потребители
        $cu = core_Users::getCurrent();
        $state->query->where("#users IS NULL OR #users = ''");
        $state->query->orLikeKeylist('users', "|$cu|");
        
        $Calendar = cls::get('cal_Calendar');
        if(Request::get($Calendar->searchInputField)) {
            $from = dt::addDays(-30, $from);
            $to = dt::addDays(360, $to);
        }

        $state->query->where("#time >= '{$from}' AND #time <= '{$to}'");

        $Calendar->prepareListFields($state);
        $Calendar->prepareListFilter($state); 
        $Calendar->prepareListRecs($state); 
        $Calendar->prepareListRows($state);
        
        $tpl->replace($Calendar->renderListTable($state), 'AGENDA');

        return $tpl;
    }
    
    
    /**
     * Намира какъв е типа на деня (празник, работен, не работен, събота, неделя)
     * @param datetime $date - mySQL формат на дата (гггг-мм-дд чч:мм:сс)
     * @param string $country
     * @return stdClass isHoliday - TRUE|FALSE
     *                  type - string|NULL
     *                  title - string|NULL
     */
    public static function getDayStatus($date, $country = 'bg')
    {
        $t = dt::mysql2timestamp($date);
        
        $dayOfWeek = date('N', $t);
  
        $time = date("Y-m-d 00:00:00", $t);

    	$query = self::getQuery();
    	$type = strtoupper($country);
    	
    	if ($type == 'BG') {
            $query->where("#time = '{$time}' AND (#type = 'holiday' OR #type = 'non-working' OR #type = 'workday')");
    	} else {
    	    $query->where("#time = '{$time}' AND #type = '{$type}'");
    	}
    	
        $rec = $query->fetch();
        $res = new stdClass();

        if($rec->type == "holiday"){
            
    	    $res->isHoliday = TRUE;    
            $res->specialDay = 'holiday';
            $res->title = $rec->title;
            
    	} elseif ($rec->type == "{$type}") {
    	    
    	    $res->isHoliday = TRUE;    
    	    $res->specialDay = $country;
    	    $res->stitle = $rec->title;
    	    
        } elseif ($rec->type == "non-working"){
            
    	    $res->isHoliday = TRUE;    
    	    $res->specialDay = 'non-working';
    	    $res->title = $rec->title;
    	    
    	} elseif($rec->type == "workday"){
    	    
    	    $res->isHoliday = FALSE;    
    	    $res->specialDay = 'workday';
    	    $res->title = $rec->title;
    	    
    	} elseif ($dayOfWeek == 6 && ($rec->type !== "holiday" || $rec->type !== "{$type}")) {
    	    
    	    $res->isHoliday = TRUE;    
    	    $res->specialDay =  'saturday';
    	    $res->title = 'Събота';
    	    
    	} elseif ($dayOfWeek == 7 && ($rec->type !== "holiday" || $rec->type !== "{$type}")) {
    	    
    	    $res->isHoliday = TRUE;   
    	    $res->specialDay = 'sunday';
    	    $res->title = 'Неделя';
    	    
    	} else {
    	    
    	    $res->isHoliday = FALSE;
    	}
    	
    	return $res;
    }


    /**
     * Дали датата е почивен?
     */
    static function isHoliday($date, $country = 'bg')
    {
        $status = self::getDayStatus($date, $country);
        
        return $status->isHoliday == TRUE;
    }


    /**
     * Намира първият работен ден, започвайки от посочения и
     * движейки се напред (1) или назад (-1)
     */
    static function nextWorkingDay($date = NULL, $direction = 1, $country = 'bg')
    {
        if (!$date) {
            $date = dt::addDays($direction);
        }
        
        while (self::isHoliday($date)) {
            $date = dt::addDays($direction, $date);
        }
        
        return $date;
    }

    
    /**
     * Функция показваща събитията за даден ден
     */
    public function act_Day()
    {
    	self::requireRightFor('day');
    	
    	$this->currentTab = "Календар->Ден";
    	    	
    	$data = new stdClass();
    	$data->query = $this->getQuery();
    	$data->action = 'day';
    	$this->prepareListFilter($data);
       	
    	$layout = 'cal/tpl/SingleLayoutDays.shtml';
    	$tpl = self::renderLayoutDay($layout, $data);
    	$tpl->append($this->renderListFilter($data), 'ListFilter');
    	
    	// Рендираме страницата
    	return  $this->renderWrapping($tpl);
 
    }

    
    /**
     * Показва събитията за цяла произволна седмица
     */
    public function act_Week()
    {
    	self::requireRightFor('week');
    	
    	$this->currentTab = "Календар->Седмица";
    	
    	$data = new stdClass();
    	$data->query = $this->getQuery();
    	$data->action = 'week';
    	$this->prepareListFilter($data);

        $layout = 'cal/tpl/SingleLayoutWeek.shtml';
        $tpl = self::renderLayoutWeek($layout, $data);
        $tpl->append($this->renderListFilter($data), 'ListFilter');

   		
   		// Рендираме страницата
        return $this->renderWrapping($tpl);
    }


    /**
     * Показва събитията за целия месец
     */
    public function act_Month()
    {
    	self::requireRightFor('month');
    	
    	$this->currentTab = "Календар->Месец";
    	
    	$data = new stdClass();
    	$data->query = $this->getQuery();
    	$data->action = 'month';
    	$this->prepareListFilter($data);
             
        $layout = 'cal/tpl/SingleLayoutMonth.shtml';
        $tpl = self::renderLayoutMonth($layout, $data);
        $tpl->append($this->renderListFilter($data), 'ListFilter');

    	// Рендираме страницата
        return $this->renderWrapping($tpl);

    }

    
    /**
     * Общ поглед върху всички събития през годината
     */
    public function act_Year()
    {
    	self::requireRightFor('year');
    	
    	$this->currentTab = "Календар->Година";
    	
    	$data = new stdClass();
    	$data->query = $this->getQuery();
    	$data->action = 'year';
    	$this->prepareListFilter($data);
             
        $layout = 'cal/tpl/SingleLayoutYear.shtml';
        $tpl = self::renderLayoutYear($layout, $data);
        $tpl->append($this->renderListFilter($data), 'ListFilter');
        
        return $this->renderWrapping($tpl);
    }
    
    
    /**
     * Генерираме масив с часовете на деня
     */
    public static function generateHours()
    {
    
        for($i = 0; $i < 24; $i++){
        	self::$hours[$i] = str_pad($i, 2, "0", STR_PAD_LEFT). ":00";
        }
        
        return self::$hours;
    }
    
    
    /**
     * Генерираме масив с дните и масив за обратна връзка
     */
	public static function generateWeek($data)
    {
    	$fromFilter = $data->listFilter->rec->from;
    	$fromFilter = explode("-", $fromFilter);
  
        for($i = 0; $i < 7; $i++){
        	$days[$i] = dt::mysql2Verbal(date("Y-m-d", mktime(0, 0, 0, $fromFilter[1], $fromFilter[2] + $i - 3, $fromFilter[0])),'l'). "<br>".
        				dt::mysql2Verbal(date("Y-m-d", mktime(0, 0, 0, $fromFilter[1], $fromFilter[2] + $i - 3, $fromFilter[0])),'d.m.Y');
        	$dates[date("Y-m-d", mktime(0, 0, 0, $fromFilter[1], $fromFilter[2] + $i - 3, $fromFilter[0]))] = "d" . $i;
        	
        	// Помощен масив за javaScripta
        	$dateJs["date".$i."Js"] = date("d.m.Y", mktime(0, 0, 0, $fromFilter[1], $fromFilter[2] + $i - 3, $fromFilter[0]));
        	$dayWeek[$i] = date("N", mktime(0, 0, 0, $fromFilter[1], $fromFilter[2] + $i - 3, $fromFilter[0]));
           	 
        	// Помощен масив за css
        	$tdCssClass["c".$i] = 'calWeekTime';
            $tdCssClass["c".$i] .= ' ' . static::getColorOfDay(date("Y-m-d 00:00:00", mktime(0, 0, 0, $fromFilter[1],  $fromFilter[2] + $i - 3, $fromFilter[0])));
        	
        }
       
        return (object) array('days'=>$days, 'dates'=> $dates, 'dateJs'=>$dateJs, 'dayWeek'=> $dayWeek, 'tdCssClass'=>$tdCssClass);
      
    }
    
    
    /**
     * Генерираме масив масива на месеца => номер на седмицата[ден от седмицата][ден]
     */
	public static function generateMonth($data)
    {
    	$fromFilter = $data->listFilter->rec->from;
    	$fromFilter = explode("-", $fromFilter);
    	
    	// Таймстамп на първия ден на месеца
        $firstDayTms = mktime(0, 0, 0, $fromFilter[1], 1, $fromFilter[0]);
        
        // Броя на дните в месеца
        $lastDay = date('t', $firstDayTms);
        
        // Днешната дата без часа
        $today = dt::now($full = FALSE);
        $today = explode("-", $today);

        for($i = 1; $i <= $lastDay; $i++) {
            $t = mktime(0, 0, 0, $fromFilter[1], $i, $fromFilter[0]);
            
            $isToday = ($i == $today[2] && $fromFilter[1] == $today[1] && $fromFilter[0] == $today[0]);
            
            $monthArr[date('W', $t)]["d".date('N', $t)] = $i;
            
            // Поможен масив за javaScript-а
            $dateJs[date('W', $t)]["date".date('N', $t)."Js"] = date("d.m.Y", $t);
            
            // Помощен масив за css
            $tdCssClass[date('W', $t)]["now".date('N', $t)] = $isToday ? 'mc-today' : 'mc-day';
            $tdCssClass[date('W', $t)]["now".date('N', $t)] .= ' ' . static::getColorOfDay(date("Y-m-d 00:00:00", mktime(0, 0, 0, $fromFilter[1], $i, $fromFilter[0])));

        }
       
        return (object) array('monthArr'=>$monthArr, 'dateJs'=> $dateJs, 'tdCssClass'=>$tdCssClass);
    }
    
    
    /**
     * Генерираме масива за годината
     */
    public static function generateYear()
    {
    	$fromFilter = $from = Request::get('from');
    	$fromFilter = explode(".", $fromFilter);
    	
	    for($m = 1; $m <= 12; $m++){
	    	
			// Таймстамп на първия ден на месеца
			$firstDayTms = mktime(0, 0, 0, $m, 1, $fromFilter[2]);
			
		    // Броя на дните в месеца
	    	$lastDay = date('t', $firstDayTms);
	
	    	// Днешната дата без час
	    	$today = dt::now($full = FALSE);
        	$today = explode("-", $today);
	
			
			for($i = 1; $i <= $lastDay; $i++) {
				$t = mktime(0, 0, 0, $m, $i, $fromFilter[2]);
				
				$isToday = ($i == $today[2] && $m == $today[1] && $fromFilter[2] == $today[0]);
								
				$yearArr[$m][date('W', $t)]["d".date('N', $t)] = $i;
				
				// Помощен масив за javaScript-а
				$dateJs[$m][date('W', $t)]["date".date('N', $t)."Js"] = date("d.m.Y", $t);
				
				// Помощен масив за css
				$tdCssClass[$m][date('W', $t)]["now".date('N', $t)] = $isToday ? 'mc-today' : 'mc-day';
				$tdCssClass[$m][date('W', $t)]["now".date('N', $t)] .= ' ' . static::getColorOfDay(date("Y-m-d 00:00:00", $t));
			}
		}
		
		return (object)array('yearArr'=>$yearArr, 'dateJs'=> $dateJs, 'tdCssClass'=>$tdCssClass);
    }
    
    
    
    public static function endTask($hour, $duration)
    {
    
	 	$taskEnd = ((strstr($hour, ":", TRUE) * 3600) + (substr(strstr($hour, ":"),1) * 60) + $duration) / 3600;
	    		
	    $taskEndH = floor($taskEnd);
	    $taskEndM =  ($taskEnd - $taskEndH) * 60;
	    
		if(substr($taskEndM,1) === FALSE){
			$taskEndM = $taskEndM . '0';
		}
	
		// Краен час: минути на събитието 
	    $taskEndHour = $taskEndH . ":" . $taskEndM;
    	
	    return $taskEndHour;
    }

    
    
    /**
     * По зададена mysql-ска дата връща цвят според типа й:
     * черен - работни дни
     * червен - официални празници
     * тъмно зелено - събота
     * свето зелено - неделя
     */
    public static function getColorOfDay($date)
    {
      	// Разбиваме подадената дата
    	$day = dt::mysql2Verbal($date, 'd');
        $month = dt::mysql2Verbal($date, 'm');
        $year = dt::mysql2Verbal($date, 'Y');
        
        // Взимаме кой ден от седмицата е 1=пон ... 7=нед
        $weekDayNo = date('N', mktime(0, 0, 0, $month, $day, $year));
    	
        $dateType = self::getDayStatus($date, 'bg');

        // Ако е събота или неделя, пресвояваме цвят
    	if($weekDayNo == "6" && $dateType->specialDay !== 'workday'){
    	    
    		$class = 'saturday'; // '#006030';
    	}elseif($weekDayNo == "7" && $dateType->specialDay !== 'workday'){
    	    
    		$class = 'sunday'; // 'green';
    	}

    	if ($dateType->specialDay == 'holiday'){
    	    
    		$class = 'holiday';
    	}elseif($dateType->specialDay == 'workday' && ($weekDayNo == "6" || $weekDayNo == "7")){
    	    
    		$class = 'workday';
    	}elseif($dateType->specialDay == 'non-working' && $weekDayNo >= "4"){
    	    
    		$class = 'saturday non-working';
    	}elseif($dateType->specialDay == 'non-working' && $weekDayNo < "4"){
    	    
    		$class = 'sunday non-working';
    	}
   	
        return $class;
    }


    /**
     * Връща времето на следващото събитие след $after, от редица от събития, определена с начало, период и напасване
     *
     * @param datetime  $startOn    Начало на редицата 
     * @param int       $period     Време на периода в секунди
     * @param string    $ajust1     Напасване 1
     * @param string    $ajust2     Напасване 2
     * @param datetime  $after      Времето, след което се търси първото събитие от редицата
     * @return datetime
     */
    public static function getNextTime($startOn, $period, $ajust1, $ajust2, $after = NULL)
    {   
        // Ако не е зададено, търси се следващото време след сега
        if(!$after) {
            $after = dt::now();
        }
        
        expect($period > 0, $period);

        $diff = dt::mysql2timestamp($after) - dt::mysql2timestamp($startOn);
        
        expect($diff > 0, $diff, $after, $startOn);

        $periodsCnt = (int) ($diff / $period);

        $res = dt::addSecs($period * $periodsCnt, $startOn);
        
 
        // Връщаме малко назад, докато получим първия период, преди $after
        while($res > $after) {
            $res = dt::addSecs(-$period, $res);
        }
        
        // Даваме сега малко напред, за да хванем първия момент, след $after
        while($res < $after) {
            $res = dt::addSecs($period, $res);
        }
        
        // До тук сме определили точната дата. Сега трябва да я напаснем
        
        // Изчисляваме първото напасване
        if($ajust1) {
            $res1 = self::ajustDay($res, $ajust1);
        }
        
        // Изчисляваме второто напасване
        if($ajust2) {
            $res2 = self::ajustDay($res, $ajust2);
        }

        // Определяме, кое напасване е по-близко
        if($res1 && $res2) {
            
            if(abs(dt::mysql2timestamp($res) - dt::mysql2timestamp($res1)) > abs(dt::mysql2timestamp($res) - dt::mysql2timestamp($res2))) {
                $res = $res2;
            } else {
                $res = $res1;
            }
        } elseif($res1) {
            $res = $res1;
        } elseif($res2) {
            $res = $res2;
        }

        return $res;
    }


    /**
     * Настройва деня, според модификатора
     */
    public static function ajustDay($day, $ajust)
    {
        list($direction, $type) = explode('-', $ajust);
        
        $direction = strtolower($direction);

        if($direction == 'thisornext') {
            $d = 24*60*60;
        } else {
            expect($direction == 'thisorprev', $direction);
            $d = -24*60*60;
        }


        while(!self::isDayType($day, $type)) {
            $day = dt::addSecs($d, $day);
        }

        return $day;
    }

    
    /**
     *
     */
    public static function isDayType($day, $type)
    {   
        $t = dt::mysql2timestamp($day);
        
        list($day, ) = explode(' ', $day);
        
        switch(strtolower($type)) {
            case 'mon' : 
                $res = date("N", $t) == 1;
                break;
            case 'tue' : 
                $res = date("N", $t) == 2;
                break;
            case 'wed' : 
                $res = date("N", $t) == 3;
                break;
            case 'thu' : 
                $res = date("N", $t) == 4;
                break;
            case 'fri' : 
                $res = date("N", $t) == 5;
                break;
            case 'sat' : 
                $res = date("N", $t) == 6;
                break;
            case 'sun' : 
                $res = date("N", $t) == 7;
                break;
            case 'weekend' : 
                $res = date("N", $t) == 6 || date("N", $t) == 7;
                break;
            case 'notweekend' : 
                $res = date("N", $t) != 6 && date("N", $t) != 7;
                break;
            case 'nonworking':
                $status = self::getDayStatus($day);
                $res = ($status->isHoliday || $status->specialDay == 'non-working' || $status->specialDay == 'weekend');
                break;
            case 'working':
                $status = self::getDayStatus($day);
                $res = ($status->specialDay == 'working') || (!$status->isHoliday && $status->specialDay != 'non-working' && $status->specialDay != 'weekend');
                break;
           default:
               expect(FALSE, $type);
        }

        return $res;
    }


    function act_Test()
    {
        requireRole('admin');

        $ajustOpt = array(
            '' => '',
            'ThisOrNext-Mon' => "Напред, Понеделник",
            'ThisOrNext-Tue' => "Напред, Вторник",
            'ThisOrNext-Wed' => "Напред, Сряда",
            'ThisOrNext-Thu' => "Напред, Четвъртък",
            'ThisOrNext-Fri' => "Напред, Петък",
            'ThisOrNext-Sat' => "Напред, Събота",
            'ThisOrNext-Sun' => "Напред, Неделя",
            'ThisOrNext-Working' => "Напред, Работен ден",
            'ThisOrNext-NonWorking' => "Напред, Неработен ден",
            'ThisOrNext-NotWeekend' => "Напред, Не-Уикенд",
            'ThisOrNext-Weekend' => "Напред, Уикенд",

            'ThisOrPrev-Mon' => "Назад, Понеделник",
            'ThisOrPrev-Tue' => "Назад, Вторник",
            'ThisOrPrev-Wed' => "Назад, Сряда",
            'ThisOrPrev-Thu' => "Назад, Четвъртък",
            'ThisOrPrev-Fri' => "Назад, Петък",
            'ThisOrPrev-Sat' => "Назад, Събота",
            'ThisOrPrev-Sun' => "Назад, Неделя",
            'ThisOrPrev-Working' => "Назад, Работен ден",
            'ThisOrPrev-NonWorking' => "Назад, Неработен ден",
            'ThisOrPrev-NotWeekend' => "Назад, Не-Уикенд",
            'ThisOrPrev-Weekend' => "Назад, Уикенд",
            

            );

        $form = cls::get('core_Form');
        $form->FLD('startOn', 'datetime', 'caption=Начало,mandatory');
        $form->FLD('period', 'time(suggestions=1 ден|1 седмица|1 месец|2 дена|2 седмици|2 месеца|3 седмици|1 месец|2 месецa|3 месецa|4 месецa|5 месецa|6 месецa|12 месецa|24 месецa,min=86400)', 'caption=Период,mandatory');
        $form->FLD('ajust1', 'enum()', 'caption=Напасване');
        $form->FLD('ajust2', 'enum()', 'caption=Или по-близо');
        $form->FLD('after', 'datetime', 'caption=След');
        
        $form->setOptions('ajust1', $ajustOpt);
        $form->setOptions('ajust2', $ajustOpt);

        $rec = $form->input();

        if($form->isSubmitted()) {
            $res = self::getNextTime($rec->startOn, $rec->period, $rec->ajust1, $rec->ajust2, $rec->after);
            
            $form->info = "<b style='color:green'>Следващото събитие е на: " . $res . "</b>";
        }
        
        $form->title = "Тестване на периодичност";

        
        $form->toolbar->addSbBtn("Тест");

        return $form->renderHtml();
    }
    
    
    /**
     * Изчисляваме работните дни между две дати
     * @param datetime $leaveFrom
     * @param datetime $leaveTo
     * 
     * Връща масив с броя на почивните, работните дни в периода
     */
    public static function calcLeaveDays($leaveFrom, $leaveTo)
    {
    	
    	$nonWorking = $workDays = $allDays = 0;
    	
    	$curDate = date("Y-m-d H:i:s", strtotime("{$leaveFrom}"));
    	$date1 = trim(date("Y-m-d", strtotime("{$leaveFrom}")));
    	$date2 = trim(date("Y-m-d ", strtotime("{$leaveTo}")));  
    	
    	$hours1 = trim(date("H:i:s", strtotime("{$leaveFrom}")));
    	$hours2 = trim(date("H:i:s", strtotime("{$leaveTo}")));  
    	
    	if ($date1 == $date2){
    	    $date1Type = self::getDayStatus($date1, 'bg');
    	    if($date1Type->specialDay  == FALSE || $dateType->specialDay  == 'workday') {
    	        $workDays++;
    	    } else {
    	        $nonWorking++;
    	    }
    	    $allDays++;
    	} else { 
        	while($curDate <= $leaveTo){

        		$dateType = self::getDayStatus($curDate, 'bg');
        		$testArray [$curDate] = $dateType;
    
        		if($dateType->specialDay  == FALSE || $dateType->specialDay  == 'workday') {
        			$workDays++;
        		} else {
        		    $nonWorking++;
        		}
        		    		
        		$curDate = dt::addDays(1, $curDate); 
        		
        		$allDays++;
        	}
        	
        	if(($hours1 == $hours2) && $dateType->specialDay  != TRUE ) {
        	    $workDays -= 1;
        	}
    	}

    	return (object) array('nonWorking'=>$nonWorking, 'workDays'=>$workDays, 'allDays'=>$allDays, 'testArray'=>$testArray);
    }

    
    /**
     * Взима кой е селектирания потребител от филтъра
     */
    public static function getSelectedUsers($data)
    {        
        $selectUser = $data->listFilter->rec->selectedUsers;
       
    
    	if($selectUser == NULL){
    		$selectUser = '|' . core_Users::getCurrent() . '|';
    	}
    	
    	return $selectUser;
    }
  
    
    /**
     * Намира началната и крайната дата за деня.
     * Взима данни от филтъра
     */
    public static function getFromToDay($data)
    {
     	
        // От началото на деня
        $from['fromDate'] = $data->listFilter->rec->from. " 00:00:00";
       
        // До края на същия ден
        $from['toDate'] = $data->listFilter->rec->from. " 23:59:59";

        
        return $from;
    }
    
    
    /**
     * Намира началната и крайната дата за седмицата.
     * Взима данни от филтъра
     * Избрания ден от филтъра се приема за текущ и 
     * седмицата се определя спрямо него 
     */
    public static function getFromToWeek($data)
    {
    	$fromFilter = $data->listFilter->rec->from;
    	$fromFilter = explode("-", $fromFilter);

    	// От началото на седмицата
        $from['fromDate'] = date("Y-m-d 00:00:00", mktime(0, 0, 0, $fromFilter[1], $fromFilter[2] - 3, $fromFilter[0]));
       
        // До края на седмицата
        $from['toDate'] = date("Y-m-d 23:59:59", mktime(0, 0, 0, $fromFilter[1], $fromFilter[2] + 3, $fromFilter[0]));
        
        return $from;
    }
    
    
    /**
     * Намира началната и крайната дата на месеца
     * Взима данни от филтъра
     */
	public static function getFromToMonth($data)
    {
    	$fromFilter = $data->listFilter->rec->from;
    	$fromFilter = explode("-", $fromFilter);
    	
        // Таймстамп на първия ден на месеца
        $firstDayTms = mktime(0, 0, 0, $fromFilter[1], 1, $fromFilter[0]);
        
        // Броя на дните в месеца
        $lastDay = date('t', $firstDayTms);
        
    	// От началото на седмицата
        $from['fromDate'] = date("Y-m-d 00:00:00", $firstDayTms);
       
        // До края на седмицата
        $from['toDate'] = date("Y-m-t 23:59:59", $firstDayTms);
        
        return $from;
    }
    
    
    /**
     * Намира началната и крайната дата за годината
     * Поличава данни от URL-то
     */
    public static function getFromToYear()
    {
    	$fromFilter = Request::get('from');
    	$fromFilter = explode(".", $fromFilter);

    	// Таймстамп на първия ден на месеца
		$lastDayTms = mktime(0, 0, 0, 12, 31, $fromFilter[2]);
		
		// От началото на месеца
		$from['fromDate'] = date("Y-m-d 00:00:00", mktime(0, 0, 0, 1, 1, $fromFilter[2]));
		
		// До края на месеца
		$from['toDate'] = date('Y-m-t 23:59:59', $lastDayTms);
       
    	return $from;
    }
    
    
    /**
     * Взима данните от филтъра
     * Датата и селектирания потребител
     */
    public static function getFromFilter($data){
    	    	
    	$state['from'] = $data->listFilter->rec->from;
    	$state['selectedUsers'] = self::getSelectedUsers($data);
    	
    	return $state;
    }
    
    
    /**
     * Намира каква е иконата според състоянието на задачата
     */
    public static function getIconByType($type, $key)
    {
    	// Картинката за задачите
		if($type == 'task'){
			$idTask = str_replace("TSK-", " ", $key);
			$idTask = str_replace("-Start", " ", $idTask);
			$idTask = str_replace("-End", " ", $idTask);
			$getTask = cls::get('cal_Tasks');
			$imgTask = $getTask->getIcon(trim($idTask));

			$img = "<img class='calImg' src=". sbf($imgTask) .">&nbsp;";
		
		} elseif($type == 'end-date'){
			$img = "<img class='calImg'  src=". sbf('img/16/end-date.png') .">&nbsp;";

		} elseif($type == 'reminder'){ 
		    $img = "<img class='calImg'  src=". sbf('img/16/alarm_clock.png') .">&nbsp;";
		
		} else {
	    	$type = strtolower($type);
	    	$img = "<img class='calImg'  src=". sbf('img/16/'.$type.'.png') .">&nbsp;";
			
		}
			
		return $img;
    }
   
    
    /**
     * Генерира заявката към базата данни
     */
    public static function prepareState($fromDate, $toDate, $selectedUsers)
    {
    	
    	// Извличане на събитията за целия месец
		$state = new stdClass();
		$state->query = self::getQuery();
      
		// Кой ни е текущия потребител? 
		// Показване на календара и събитията според потребителя
		$state->query->where("#time >= '{$fromDate}' AND #time <= '{$toDate}'");
		$state->query->LikeKeylist('users', $selectedUsers);
        $state->query->orWhere('#users IS NULL OR #users = ""');
        
        $state->query->orderBy('time', 'ASC');  
        
		// Ако са избрани, кои събития да се показват
        $showHoliday = cal_Setup::get('SHOW_HOLIDAY_TYPE');
        if ($showHoliday) {
            $showHolidaysArr = type_Set::toArray($showHoliday);
            
            $state->query->in('type', $showHolidaysArr);
        }
        
		// Ако няма да се показва никое събитие
        if ($showHoliday === FALSE) {
            $state->query->where("1=2");
        }
        
		while($rec = $state->query->fetch()){
			$recState[] = $rec;
		}
 		
		return $recState;
    }
    
    
    /**
     * Генерира заявката към базата данни за екшън Година
     */
	public static function prepareStateYear($fromDate, $toDate, $selectedUsers, $type)
    {
    	
    	// Извличане на събитията за целия месец
		$state = new stdClass();
		$state->query = self::getQuery();
      
		// Кой ни е текущия потребител? 
		// Показване на календара и събитията според потребителя
		$state->query->where("#time >= '{$fromDate}' AND #time <= '{$toDate}' AND #type = '{$type}'");
		$state->query->LikeKeylist('users', $selectedUsers);
        $state->query->orWhere('#users IS NULL OR #users = ""');
        
        $state->query->orderBy('time', 'ASC');  
		
		while($rec = $state->query->fetch()){
			$recState[] = $rec;
		}
 		
		return $recState;
    }
    
    
    /**
     * Подготвя записите от базата данни за екшън Ден
     */
    public static function prepareRecDay($data)
    {
        $date = self::getFromFilter($data);
    	$selectedUsers = self::getSelectedUsers($data);
    	$from = self::getFromToDay($data);

     	// Масив с информация за деня
        $dates[dt::mysql2verbal($date['from'], 'Y-m-d')] = "tasktitle";

     	// От началото на деня
        $fromDate = $from['fromDate'];
       
        // До края на същия ден
        $toDate = $from['toDate'];
        
        $stateDay = self::prepareState($fromDate, $toDate, $selectedUsers);
        
      
        if(is_array($stateDay)){
            
	        foreach($stateDay as $rec){
	            $row = new stdClass();
	            $row = self::recToVerbal($rec);
	            
			    // Деня, за който взимаме събитията
			    $dayKey = $dates[dt::mysql2verbal($rec->time, 'Y-m-d')];
			     
			    // Начален час на събитието
			    $hourKey = dt::mysql2verbal($rec->time, 'G');
			
			    // Ако събитието е отбелязано да е активно през целия ден
			    if($rec->allDay == "yes")  $hourKey = "allDay";
			    
			    if($hourKey <= self::$tr && $hourKey != "allDay") self::$tr = $hourKey;
			    
			    if($hourKey >= self::$tk && $hourKey != "allDay") self::$tk = $hourKey;

	     	    // Картинката за задачите
	     		$img = self::getIconByType($rec->type, $rec->key);
	     		
	     		$rec->title = type_Varchar::escape($rec->title);

	     		$dayData[$hourKey][$dayKey] .= $row->event;

	     	}
        }
     	
     	return $dayData;
    }
    
    
    /**
     * Подготвя записите от базата данни за екшън Седмица
     */
    public static function prepareRecWeek($data)
    {
    	$date = self::getFromFilter($data);
    	$selectedUsers = self::getSelectedUsers($data);
    	$from = self::getFromToWeek($data);
    	$weekArr = self::generateWeek($data);
        	
     	// От началото на деня
        $fromDate = $from['fromDate'];
       
        // До края на същия ден
        $toDate = $from['toDate'];
        
        $stateWeek = self::prepareState($fromDate, $toDate, $selectedUsers);
        
        if(is_array($stateWeek)){
	        foreach($stateWeek as $rec){
	            $row = new stdClass();
	            $row = self::recToVerbal($rec);
	        	
	        	// Деня, за който взимаме събитията
			    $dayKey = $weekArr->dates[dt::mysql2verbal($rec->time, 'Y-m-d')];
			     
			    // Начален час на събитието
			    $hourKey = dt::mysql2verbal($rec->time, 'G');
			
			    // Ако събитието е отбелязано да е активно през целия ден
			    if($rec->allDay == "yes")  $hourKey = "allDay";
			    
			    if($hourKey <= self::$tr && $hourKey != "allDay") self::$tr = $hourKey;
			    
			    if($hourKey >= self::$tk && $hourKey != "allDay") self::$tk = $hourKey;
			    
			    // Линк към събитието
	     		$url = parseLocalUrl($rec->url, FALSE);
	               
	     		// Ид-то на събитието
	    		$id = substr(strrchr($rec->url, "/"),1);
	    		
	     	    // Картинката за задачите
	            $img = self::getIconByType($rec->type, $rec->key);
	            
	            $rec->title = type_Varchar::escape($rec->title);
	            
	            $weekData[$hourKey][$dayKey] .= $row->event;

	        }
        }
       
        return $weekData;
    }
    
    
    /**
     * Подготвя записите от базата данни за екшън Месец
     */
    public static function prepareRecMonth($data)
    {
    	$date = self::getFromFilter($data);
    	$selectedUsers = self::getSelectedUsers($data);
    	$from = self::getFromToMonth($data);
    	$monthDate = self::generateMonth($data);
      
     	// От началото на деня
        $fromDate = $from['fromDate'];
       
        // До края на същия ден
        $toDate = $from['toDate'];
        
        $stateMonth = self::prepareState($fromDate, $toDate, $selectedUsers);
      
        if(is_array($stateMonth)){
	        foreach($stateMonth as $rec){
	            $row = new stdClass();
	            $row = self::recToVerbal($rec);
			     
			    // Начален час на събитието
			    $hourKey = dt::mysql2verbal($rec->time, 'G');
			    
			    // Разбиваме това време на: ден, месец и година
	            $recDay = dt::mysql2Verbal($rec->time, 'j');
				$recMonth = dt::mysql2Verbal($rec->time, 'm');
				$recYear = dt::mysql2Verbal($rec->time, 'Y');
				
				// Таймстамп на всеки запис
				$recT = mktime(0, 0, 0, $recMonth, $recDay, $recYear);
				
				// В коя седмица е този ден
				$weekKey = date('W', $recT);
				
			 	// Деня, за който взимаме събитията
			    $dayKey = "d".date('N', $recT);
			    
			    // Ако събитието е отбелязано да е активно през целия ден
			    if($rec->allDay == "yes")  $hourKey = "allDay";
			    
			    if($hourKey <= self::$tr && $hourKey != "allDay") self::$tr = $hourKey;
			    
			    if($hourKey >= self::$tk && $hourKey != "allDay") self::$tk = $hourKey;
			    
			    $monthDate->monthArr[$weekKey][$dayKey] .= $row->event;
	        }
        }
       
        return $monthDate;
    }
    
    
    /**
     * Подготвя записите от базата данни за екшън Година
     */
    public static function prepareRecYear($data)
    {
    	$from = self::getFromToYear();
    	$yearDate = self::generateYear();
    	$date = self::getFromFilter($data);
    	$selectedUsers = self::getSelectedUsers($data);

     	// От началото на деня
        $fromDate = $from['fromDate'];
       
        // До края на същия ден
        $toDate = $from['toDate'];
        
        // TODO всеки ден от отпуската
        $stateYearLeave = self::prepareStateYear($fromDate, $toDate, $selectedUsers, $type = 'leave');
    
        if(is_array($stateYearLeave)){
	        foreach($stateYearLeave as $rec){
	        	
				// Разбиваме това време на: ден, месец и година
				$recDay = dt::mysql2Verbal($rec->time, 'j');
				$recMonth = dt::mysql2Verbal($rec->time, 'n');
				$recYear = dt::mysql2Verbal($rec->time, 'Y');
				
				// Таймстамп на всеки запис
				$recT = mktime(0, 0, 0, $recMonth, $recDay, $recYear);
						
				// В коя седмица е този ден
				$weekKey = date('W', $recT);
				
				// Кой ден от седмицата е
				$dayKey = "d".date('N', $recT);
				
				// Добавяме звезда там където имаме събитие
				$yearDate->yearArr[$recMonth][$weekKey][$dayKey] = "<img class='starImg' src=". sbf('img/16/star_3.png') .">" . $recDay;
	        }
        }
        
        $stateYear = self::prepareStateYear($fromDate, $toDate, $selectedUsers, $type = 'task');
    
        if(is_array($stateYear)){
	        foreach($stateYear as $rec){
	        	
				// Разбиваме това време на: ден, месец и година
				$recDay = dt::mysql2Verbal($rec->time, 'j');
				$recMonth = dt::mysql2Verbal($rec->time, 'n');
				$recYear = dt::mysql2Verbal($rec->time, 'Y');
				
				// Таймстамп на всеки запис
				$recT = mktime(0, 0, 0, $recMonth, $recDay, $recYear);
						
				// В коя седмица е този ден
				$weekKey = date('W', $recT);
				
				// Кой ден от седмицата е
				$dayKey = "d".date('N', $recT);
				
				// Добавяме звезда там където имаме събитие
				$yearDate->yearArr[$recMonth][$weekKey][$dayKey] = "<img class='starImg' src=". sbf('img/16/star_2.png') .">" . $recDay;
	        }
        }

        return $yearDate;
    }
    
    
    /**
     * Създава линкове за предишен и следващ месец
     */
    public static function prepareMonhtHeader($data)
    {
    	
    	//$date = $data->listFilter->rec->from;
    	$date = explode("-", $data); 
	 
        // Разбиваме я на ден, месец и година
        $day = $date[2];
        $month = $date[1];
        $year = $date[0];
    	
        if(!$month || $month < 1 || $month > 12 || !$year || $year < 1970 || $year > 2038) {
            $year = date('Y');
            $month = date('n');
        }

        // Добавяне на първия хедър
        $currentMonth = tr(dt::$months[$month-1]) . " " . $year;

        $pm = $month-1;
        if($pm == 0) {
            $pm = 12;
            $py = $year-1;
        } else {
            $py = $year;
        }
        $prevMonth = tr(dt::$months[$pm-1]) . " " .$py;

        $nm = $month+1;
        if($nm == 13) {
            $nm = 1;
            $ny = $year+1;
        } else {
            $ny = $year;
        }
        $nextMonth = tr(dt::$months[$nm-1]) . " " .$ny;
        
        $urlMonth = toUrl(array('cal_Calendar', 'month'));
        
        $headerLink['nextLink'] = toUrl(array('cal_Calendar', 'month', 'from' => $day . '.' . $nm . '.' . $ny), 'absolute');
        
        $headerLink['prevtLink'] = toUrl(array('cal_Calendar', 'month', 'from' => $day . '.' . $pm . '.' . $py), 'absolute');
        
        $headerLink['currentMonth'] = $currentMonth;
        $headerLink['nextMonth'] = $nextMonth;
        $headerLink['prevMonth'] = $prevMonth;

        return $headerLink;
    }
    
    
    /**
     * Изчислява номера(/номерата, 
     * ако избраната седмица в екшън Седмица обхваща дни
     * от две седмици) на седмицата
     */
    public static function prepareWeekNumber($data)
    {
    	$fromFilter = $data->listFilter->rec->from;
    	$fromFilter = explode("-", $fromFilter);
    	 
        // Номера на седмицата
        $weekNbFrom = date('W', mktime(0, 0, 0, $fromFilter[1], $fromFilter[2] - 3, $fromFilter[0]));
        $weekNbTo = date('W', mktime(0, 0, 0, $fromFilter[1], $fromFilter[2] + 3, $fromFilter[0]));
        
	    if($weekNbFrom == $weekNbTo){
	        	
	    	$weekNb = $weekNbFrom;
	    } else {
	        	
	        $weekNb = $weekNbFrom . "/" . $weekNbTo;
	    }
	    
	    return $weekNb;
    }
    
    
    /**
     * 
     */
    public static function prepareMonthOptions()
    {  
        $month = Request::get('cal_month', 'int');
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $year  = Request::get('cal_year', 'int');
        
        if(!$month || $month < 1 || $month > 12 || !$year || $year < 1970 || $year > 2038) {
            $year = date('Y');
            $month = date('n');
        }
     
        // Добавяне на първия хедър
        $currentMonth = tr(dt::$months[$month-1]) . " " . $year; 
        $nextLink = $prevtLink = $prev = $next = $current =  getCurrentUrl();
        
        // генериране на един месец назад
        $pm = $month-1;
        if($pm == 0) {
            $pm = 12;
            $py = $year-1;
        } else {
            $py = $year;
        }
        // името на месеца и годината
        $prevMonth = tr(dt::$months[$pm-1]) . " " .$py;
        // генериране на линк към него
        $prevtLink['cal_month'] = $pm;
        $prevtLink['cal_year'] = $py;
        $prevtLink = toUrl($prevtLink) . "#calendarPortal";
 
        // генериране на един месец напред
        $nm = $month+1;
        if($nm == 13) {
            $nm = 1;
            $ny = $year+1;
        } else {
            $ny = $year;
        }
        // името на месеца и годината
        $nextMonth = tr(dt::$months[$nm-1]) . " " .$ny;
        // генериране на линк към него
        $nextLink['cal_month'] = $nm;
        $nextLink['cal_year'] = $ny;
        $nextLink = toUrl($nextLink) . "#calendarPortal";
        
        // взимаме текущия месец и го добавяме и него
        $now = dt::today();
        $today = getCurrentUrl();
        $monthToday =  date("m", dt::mysql2timestamp($now));
        $yearToday  = date("Y", dt::mysql2timestamp($now));
        $today['cal_month'] = $monthToday;
        $today['cal_year'] = $yearToday;
        $today = toUrl($today) . "#calendarPortal";
       
        $thisMonth =  tr(dt::$months[$monthToday -1]) . " " . $yearToday ;
   
        $options = array();
        $attr['value'] = $today;
        $attr['style'] .= 'color:#00F;';
        $options[$today] = (object) array('title' => $thisMonth, 'attr' => $attr);
       
        // правим масив с 3 месеца назад от текущия месец,
        // които е подготовка за нашия select
        // за value има линк към съответния месец
        // а за стойност има името намесеца и съответната година
        // генерираме го в низходящ ред, за да са подредени месеците хронологично
        for ($i = 3 ; $i >= 1; $i--){
            $prev = getCurrentUrl();
            $pm = $month-$i;
            if($pm == 0) {
                $pm = 12;
                $py = $year-1;
            } elseif($pm <= 0){
                $pm = 12 + $pm;
                $py = $year-1;
            } else {
                $py = $year;
            }
            $prev['cal_month'] = $pm;
            $prev['cal_year'] = $py;
            $prev = toUrl($prev) . "#calendarPortal";
            $prevM = tr(dt::$months[$pm-1]) . " " .$py;
            $options[$prev] = $prevM;
        
            if($prevM == $thisMonth) {
                $attr['value'] = $prevM;
                $attr['style'] .= 'color:#00F;';
                $options[$prev] = (object) array('title' => $prevM, 'attr' => $attr);
        
                unset($options[$today]);
            }
             
        }

        // добавяме текущия месец къммасива
        // за него не ни е нужен линк
        $curLink = getCurrentUrl();
        $currentM = tr(dt::$months[$month-1]) . " " . $year;
        $curLink['cal_month'] = $monthToday;
        $curLink['cal_year'] = $yearToday;
        $curLink = toUrl($curLink) . "#calendarPortal";
        
        $options[$currentMonth] = $currentM;

        if($currentMonth == $thisMonth) {
            $attr['value'] = $currentM;
            $attr['style'] .= 'color:#00F;';
           
            $options[$curLink] = (object) array('title' => $currentM, 'attr' => $attr);
           
            unset($options[$today]);
        }
        
        // правим масив с 9 месеца напред от текущия месец,
        // които е подготовка за нашия select
        // за value има линк към съответния месец
        // а за стойност има името на месеца и съответната година
        // генерираме го във възходящ ред, за да са подредени месеците хронологично
        $k = 1;
        for ($j = 1; $j <= 9; $j ++) {
            $next = getCurrentUrl();
            $nm = $month+$j;
             
            if($nm == 13) {
                $nm = 1;
                $ny = $year+1;
            } elseif($nm >= 14) {
                $nm = 1 + $k++;
                $ny = $year+1;
            } else {
                $ny = $year;
            }
            $next['cal_month'] = $nm;
            $next['cal_year'] = $ny;
            $next = toUrl($next) . "#calendarPortal";
            $nextM = tr(dt::$months[$nm-1]) . " " .$ny ;
             
            $options[$next] = $nextM;
        
            if($nextM == $thisMonth) { 
                $attr['value'] = $nextM;
                $attr['style'] .= 'color:#00F;';
                $options[$next] = (object) array('title' => $nextM, 'attr' => $attr);
        
                unset($options[$today]);
            }
        
        }
   
        return (object) array('opt' => $options, 'currentM' =>$currentMonth,  
                              'prevtLink'=>$prevtLink, 'nextLink'=>$nextLink, 
                              'nextMonth'=>$nextMonth,'prevMonth' =>$prevMonth);
    }
    
    
    /**
     * Замествания по шаблона на екшън Ден
     */
    public static function renderLayoutDay($layout, $data)
    {
    	$dayData = self::prepareRecDay($data);
    	$isToday = self::isToday($data);
    	$dayHours = self::generateHours();
    
    	// Текущото време на потребителя
     	$nowTime = strstr(dt::now(), " ");
    	
    	// Рендираме деня
    	$tpl = new ET(tr('|*' . getFileContent($layout)));
        
    	$url = toUrl(array('cal_Tasks', 'add'));
    	
    	$jsFnc = "
    	function createTask(dt)
    	{
    		document.location = '{$url}?timeStart=' + dt;
		}";
    	    	
    	$jsDblFnc = "
    	function createDblTask(dt)
    	{
    		document.location = '{$url}?timeStart=' + dt;
		}";

    	
    	$tpl->appendOnce($jsFnc, 'SCRIPTS');
    	$tpl->appendOnce($jsDblFnc, 'SCRIPTS');
     
    	foreach(self::$hours as $h => $t){
    		
    		if($h === 'allDay' || ($h >= self::$tr && $h <= self::$tk)){
    			$tUrl = str_replace('Цял ден', '', $t);
	    		$hourArr = $dayData[$h]; 
	    		$hourArr['time'] = $t;
//	    		$hourArr['timeJs'] = $h;
	    		
	    		$hourArr['dateJs'] = dt::mysql2verbal($data->listFilter->rec->from, 'd.m.Y');
	    		
	    		if ($h != 'allDay') {
	    			$hourArr['dateJs'] .= '+'.$t;
	    		} else {
	    			$hourArr['dateJs'];
	    		}
	 
	    		// Определяме класа на клетката, за да стане на зебра
	    		if($h % 2 == 0 && $h !== 'allDay' && ($h != $nowTime || $h != $isToday)){
	    			$classTd = 'calDayN';
	    			$classTr = 'calDayC';	    
			    }elseif($h % 2 == 0 && $h !== 'allDay' && $isToday == FALSE && $h != $nowTime){
			    	$classTd = 'calDayN';
			    	$classTr = 'calDayC';
			    }elseif($h == $nowTime && $isToday && $h % 2 == 0){
			     	$classTd = 'mc-todayN';
			     	$classTr = 'calDayC';
			    }elseif($h == $nowTime && $isToday && $h % 2 != 0 && $h != 0){
				    $classTd = 'mc-todayD';
				    $classTr = 'calDayD';
			    }else{
			    	$classTd = 'calDay';
			    	$classTr = 'calDayD';
			    }
	    		
			    // Взимаме блока от шаблона
	    		$cTpl = $tpl->getBlock("COMMENT_LI");
	    		$cTpl->replace($classTr, 'colTr');
	    		$cTpl->replace($classTd, 'now');
	    		
	    		
			    if(Request::get('Task')){
			    	$from = Request::get('from');
			    	$active = trim(substr($from, strpos($from, " ")));
			    	
			    	if($t == $active){
			    		$cTpl->replace('activeHour', 'fromTask');
			    	}
			    }
    		   
	    		// За да сработи javaSkript–а за всяка картинак "+", която ще показваме
			    // задаваме уникално ид
			    for($j = 0; $j < 26; $j++){
					// Линкове на картинката
					$aHrefs["href".$j] = "<img class='calWeekAdd' id=$h$j src=".sbf('img/16/add1-16.png')." title='Създаване на нова задача'>";
			    } 
			      
			    // Заместваме всички масиви
				$cTpl->placeArray($aHrefs);
			    $cTpl->placeArray($hourArr);
	    		
	    		//Връщаме към мастера
	    		$cTpl->append2master();
    		}
   		}
 
   		$currentDate = self::getFromFilter($data);
   	    $currentDateDay = dt::mysql2Verbal($currentDate['from'], 'd F Y, l');
    
        // Заместваме титлата на страницата
    	$tpl->replace($currentDateDay, 'title');

    	$titleColor = static::getColorOfDay($currentDate['from']. " 00:00:00");
    	$tpl->replace($titleColor, 'colTitle');
    	
    	return $tpl;
    }
    
    
    /**
     * Замествания по шаблона на екшън Седмица
     */
    public static function renderLayoutWeek($layout, $data)
    {
    	$weekData = self::prepareRecWeek($data);
    	
    	$weekArr = self::generateWeek($data);
   
    	$isToday = self::isToday($data);
    	
    	$weekHours = self::generateHours();
  
    	// Текущото време на потребителя
     	$nowTime = strstr(dt::now(), " ");
    	
    	// Рендиране на седмицата	
        $tpl = new ET(tr('|*' . getFileContent($layout)));
        
        $urlWeek = toUrl(array('cal_Tasks', 'add'));
    	
    	$jsFnc = "
    	function createWeekTask(dt)
    	{
    		document.location = '{$urlWeek}?timeStart=' + dt;
		}";
    	
    	$jsDblFnc = "
    	function createDblWeekTask(dt)
    	{
    		document.location = '{$urlWeek}?timeStart=' + dt;
		}";
    	
    	$urlCal = toUrl(array('cal_Calendar', 'week'));
    	$jsCalFnc = "
    	function goToWeekDate(dt)
    	{
    		document.location = '{$urlCal}?from=' + dt;
		}";
    	
    	$tpl->appendOnce($jsFnc, 'SCRIPTS');
    	$tpl->appendOnce($jsDblFnc, 'SCRIPTS');
    	$tpl->appendOnce($jsCalFnc, 'SCRIPTS');

   		foreach(self::$hours as $h => $t){
   		
   			// Ограничаваме часовета в таблицата до цел ден и най-малкия и най-големия час
   			if($h === 'allDay' || ($h >= self::$tr && $h <= self::$tk)){
    		$hourArr = $weekData[$h];
    		$hourArr['time'] = $t;
    		if($h === 'allDay'){
    			$hourArr['timeJs'];
    		} else {
    			$hourArr['timeJs'] = '+'.$t;
    		}
           
    		// Взимаме блока от шаблона
    		$cTpl = $tpl->getBlock("COMMENT_LI");
   			
   			// Определяме класа на клетката, за да стане на зебра
    		if($h % 2 == 0 && $h !== 'allDay' && ($h != $nowTime || $h != $isToday)){
    			$classTd = 'calWeekN';
    			$classTr = 'calDayC';
			    $classToday = 'calWeekN';		    
		    }elseif($h == $nowTime && $isToday && $h % 2 == 0){
		    	$classTd = 'calWeekN';
		     	$classToday = 'mc-todayN';
		     	$classTr = 'calDayC';
		     	
		    }elseif($h == $nowTime && $isToday && $h % 2 != 0 && $h != 0){
			    $classToday = 'mc-todayD';
			    $classTd = 'calWeek';
			    $classTr = 'calDayD';
			    
		    }else{
		    	$classTd = 'calWeek';
		    	$classTr = 'calDayD';
		    	$classToday = 'calWeek';
		    }
		     		
    		$cTpl->replace($classTr, 'colTr');
    		$cTpl->replace($classToday, 'now');
    		$cTpl->replace($classTd, 'col');
    		
    	
    		$cTpl->placeArray($hourArr);
    		
		     // За да сработи javaSkript–а за всяка картинак "+", която ще показваме
		     // задаваме уникално ид
		    for($j = 0; $j < 26; $j++){
		
		    	if($h == '0') {
		    		$h = '24';
		    	} 
		    	/*elseif($h == 'allDay'){
		    		$h = '25';
		    	}*/
		    	
				// Линкове на картинката
				$aHrefs["href".$j] = "<img class='calWeekAdd' id=$h$j src=".sbf('img/16/add1-16.png')." title ='Създаване на нова задача'>";
		     }

             // Заместваме всички масиви в шаблона
		     $cTpl->placeArray($aHrefs);
		     $cTpl->placeArray($hourArr);
     
    		   			
            // Връщаме се към мастера
    		$cTpl->append2master();
   			}
   		}
        
   		$weekNb = self::prepareWeekNumber($data);
    	
        // Заглавие на страницата
    	$tpl->replace(tr('Събития за седмица') . ' » ' . $weekNb, 'title');
    	
    	// Рендираме масивите с дните и javaScript масива
    	$tpl->placeArray($weekArr->days);
    	$tpl->placeArray($weekArr->dateJs);
    	$tpl->placeArray($weekArr->tdCssClass);
    	
    	return $tpl;
    }
    
    
    /**
     * Замествания по шаблона на екшън Месец
     */
    public static function renderLayoutMonth($layout, $data)
    {
    	$monthData = self::prepareRecMonth($data);
    	$monthArr = self::generateMonth($data);
    	
    	// Зареждаме шаблона
        $tpl = new ET(tr('|*' . getFileContent($layout)));
        
        $urlMonth = toUrl(array('cal_Calendar', 'week'));
    	
    	$jsFnc = "
    	function createMonthLink(dt)
    	{
    		document.location = '{$urlMonth}?from=' + dt;
		}";
    	
    	$tpl->appendOnce($jsFnc, 'SCRIPTS');

    	
        foreach($monthData->monthArr as $weekNum => $weekArr) {
        	
        	$cTpl = $tpl->getBlock("COMMENT_LI");
        	
        	$cTpl->placeArray($monthArr->colorTitle[$weekNum]);
        	$cTpl->placeArray($monthArr->tdCssClass[$weekNum]);
        	$cTpl->placeArray($monthArr->dateJs[$weekNum]);
           
        	$cTpl->replace($weekNum, 'weekNum');
        	$cTpl->placeArray($weekArr);
        	
            $cTpl->append2master();
         }
         
        if(core_Lg::getCurrent() == 'en'){
        	$tpl->placeArray(static::$weekDaysEn);
        }
        $tpl->placeArray(static::$weekDays);
        
        $date = $data->listFilter->rec->from;
        $link = static::prepareMonhtHeader($date); 
      
        // Добавяне на първия хедър
        $tpl->replace($link['prevtLink'], 'prevtLink');
        $tpl->replace($link['prevMonth'], 'prevMonth');
        $tpl->replace($link['currentMonth'], 'currentMonth');
        $tpl->replace($link['nextLink'], 'nextLink');
        $tpl->replace($link['nextMonth'], 'nextMonth');
        
        // Заглавието на страницата
    	$tpl->replace(tr('Събития за месец') . ' » '. tr($link['currentMonth']), 'title');
    	
    	return $tpl;
    }
    
    
    /**
     * Замествания по шаблона на екшън Година
     */
    public static function renderLayoutYear($layout, $data)
    {
    	
    	$yearData = self::prepareRecYear($data);
    	$yearArr = self::generateYear($data);
    	
    	$fromFilter = $from = Request::get('from');
    	$fromFilter = explode(".", $fromFilter);
    	
	    // Зареждаме шаблона
        $tpl = new ET(tr('|*' . getFileContent($layout)));
        
        $urlYear = toUrl(array('cal_Calendar', 'week'));
    	
    	$jsFnc = "
    	function createLink(dt)
    	{
    		document.location = '{$urlYear}?from=' + dt;
		}";
    	
    	$tpl->appendOnce($jsFnc, 'SCRIPTS');
       
    	foreach($yearData->yearArr as $monthNum => $monthArr) {
    	
    		foreach($monthArr as $weekNum => $weekArr){
    			
				$tpl->replace(dt::getMonth($monthNum, 'F'), 'month'.$monthNum);
				$block = "COMMENT_LI{$monthNum}";
				
				$lTpl = $tpl->getBlock("COMMENT_LI{$monthNum}");
							      
				$lTpl->replace($weekNum, 'weekNum');
				$lTpl->placeArray($weekArr);
				$lTpl->placeArray($yearArr->dateJs[$monthNum][$weekNum]);
				$lTpl->placeArray($yearArr->tdCssClass[$monthNum][$weekNum]);
				$lTpl->append2master();
						         
    		}
         }

        // Заглавието на страницата
    	$tpl->replace(tr('Събития за година') . ' » '. $fromFilter[2], 'title');

    	// Имената на дните от седмицата
    	if(core_Lg::getCurrent() == 'en'){
    		$tpl->placeArray(dt::$weekDaysShortEn );
    	}
        $tpl->placeArray(dt::$weekDays);
        
        return $tpl;
    }
    
    
    /**
     * Проверява дали избраната дата от филтъра е днешния ден
     */
    public static function isToday($data)
    {
    	$from = self::getFromFilter($data);
    
       	$fromA = $from['from'];
        $fromA = explode("-", $fromA);
        
        $today = dt::now($full = FALSE);
        $today = explode("-", $today);
        
    	$isToday = ($fromA[2]== $today[2] && $fromA[1] == $today[1] && $fromA[0] == $today[0]);
    	
    	return $isToday;
    }
    
    
    public static function prepareLinkOrElement($rec)
    {
        $lowerType = strtolower($rec->type);
         
        $url = parseLocalUrl($rec->url, FALSE);
         
        // TODO да стане с интерфейс
        $isLink = TRUE;
        $mvc = cls::get($url['Ctr']);
        
        if($mvc instanceof core_Mvc) {
        
            $class = $url['Ctr'];
            switch ($url['Act']) {
                case 'Single':
                    $isLink = $class::haveRightFor('single', $url['id']);
                    break;
                case 'list':
                    $isLink = $class::haveRightFor('list');
                    break;
                case 'default':
                    $isLink = $class::haveRightFor('default');
                    break;
            }
        }
        
         
        // TODO
        $attr = array();
        if(!strpos($rec->type, '/')) {
            $attr['ef_icon'] = "img/16/{$lowerType}.png";
        } elseif($rec->type = 'reminder') {
            $attr['ef_icon'] = "img/16/alarm_clock.png";
        } else {
            $attr['ef_icon'] = $rec->type;
        }
        
        $attr = ht::addBackgroundIcon($attr);
        
        if($rec->priority <= 0) {
            $attr['style'] .= 'color:#aaa;text-decoration:line-through;';
        }
        // TODO
        if($isLink){
            $event = ht::createLink($rec->title, $url, NULL, $attr);
        } else {
            
            if ($url['Ctr'] == 'crm_Persons' || $url['Ctr'] == 'hr_Leaves' || $url['Ctr'] == 'hr_Sickdays' || $url['Ctr'] == 'hr_Trips') {
                $event = ht::createElement("span", $attr, $rec->title);
            }
            
            if($url['Ctr'] == 'crm_Persons' && ($url['id'])) {
                $pRec = crm_Persons::fetch($url['id']);
        
                if ($pRec->inCharge) {
                    $event .= ' (' . crm_Profiles::createLink($pRec->inCharge) . ')';
                }
            }
        }
        
        return $event;
    }

}
