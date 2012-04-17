<?php



/**
 * Календар - регистър за датите
 *
 *
 * @category  bgerp
 * @package   cal
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cal_Agenda extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Календар";
    
    
    /**
     * Класове за автоматично зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, cal_Wrapper, plg_Sorting, plg_State';
    
    
    /**
     * Полетата, които ще видим в таблицата
     */
    var $listFields = 'date,event=Събитие,type,url';
    
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
    var $canRead = 'cal,admin';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Уникален ключ за събитието
        $this->FLD('key', 'varchar(24)', 'caption=Ключ');

        // Дата на събититието
        $this->FLD('date', new type_Date(array('cellAttr' => 'class="portal-date"', 'format' => 'smartTime')), 'caption=Дата');

        // Тип на събититето. Той определя и иконата на събититето
        $this->FLD('type', 'varchar(32)', 'caption=Тип');
        
        // За кои потребители се отнася събитието. Празно => за всички
        $this->FLD('users', 'keylist(mvc=core_Users,title=nick)', 'caption=Потребители');

        // Информация за събитието

        $this->FLD('title', 'varchar', 'caption=Заглавие');
        $this->FLD('url',  'varchar', 'caption=Url,column=none');
        $this->FLD('html', 'html', 'caption=HTML допълнение,column=none');
        $this->FLD('allDay', 'enum(yes=Да,no=Не)', 'caption=Цял ден?');
        
        // Индекси
        $this->setDbUnique('key,type,date');
        $this->setDbIndex('key');

    }
    
    
    /**
     * Добавя или обновява информация за събитие
     */
    static function mergeEvents($key, $events = array())
    {
        // Днешна MySql дата, към началото на деня
        $today = date('Y-m-d 00:00:00');
        
        // Инициализираме резултатния масив
        $res = array(
            'new' => 0,
            'updated' => 0,
            'rejected' => 0, 
            'deleted' => 0
            );
        
        // Правим ключа с подходяща максиламна дължина
        $key = str::convertToFixedKey($key, 24, 8);
        
        $query = static::getQuery();

        $query->where("#key = '{$key}'");
        
        // Извличаме съществуващите записи за този ключ
        while($rec = $query->fetch()) {
            $exRecs[$rec->date . '|' . $rec->type] = $rec;
        }
        
        if(count($events)) {
            // Циклим по новите събития, за да определим, кои от тях са съществуващи до сега
            foreach($events as &$rec) {
                $hnd = $rec->date . '|' . $rec->type;
                
                // Ако събитието е съществуващо - то отива за обновяване
                if($exRecs[$hnd]) {
                    $rec->id = $exRecs[$hnd]->id;
                    unset($exRecs[$hnd]);
                }
            }

            // Добавяме или обновяваме новите събития
            foreach($events as $rec) {
                $rec->key = $key;
                if($rec->id) {
                    $res['updated']++;
                } else {
                    $res['new']++;
                }
                static::save($rec);
            }
        }

        // Изтриваме или оттегляме старите събития
        if(count($exRecs)) {
            foreach($exRecs as $rec) {
                if($rec->date < $today) {
                    if($rec->state != 'rejected') {
                        $rec->state = 'rejected';
                        static::save($rec);
                        $res['rejected']++;
                    }
                } else {
                    static::delete($rec->id);
                    $res['deleted']++;
                }
            }
        }
    }
    
    
    /**
     * Предизвиква изтриване на информацията за посоченото от ключа събитие
     */
    static function deleteEvent($key)
    {        
        // Изтриване на събитията до момента
        cal_Agenda::delete("#key = {$key}");
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
        
        //$row->date = dt::mysql2verbal($rec->date, "d-m-Y, D");
        
        $url = getRetUrl($rec->url);
        $attr['class'] = 'linkWithIcon';
        $attr['style'] = 'background-image:url(' . sbf("drdata/icons/{$rec->type}.png") . ');';
        $row->event = ht::createLink($row->title, $url, NULL, $attr);

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
        // $html .= drdata_Holidays::addHolidaysToCalendar();
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
    static function renderCalendar($year, $month, $data = array(), $header = NULL)
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
        
        
        // Съдържание на календара
        
        // Таймстамп на първия ден на месеца
        $firstDayTms = mktime(0, 0, 0, $month, 1, $year);
        
        // От първия ден за месеца
        $from = dt::timestamp2mysql($firstDayTms);
        
        // До последния ден за месеца
        $to = date('Y-m-t 23:59:59', $firstDayTms);
        
        $state = new stdClass();
        $state->query = self::getQuery();
        $state->query->where("#date >= '{$from}' AND #date <= '{$to}'");
        $state->query->orderBy("#date=ASC");

        $Calendar = cls::get('cal_Agenda');
        $Calendar->prepareListFields($state);
        $Calendar->prepareListRecs($state);
        $Calendar->prepareListRows($state);
        

        // Подготвяме заглавието на таблицата
        //$state->title = tr("Календар");
        
        // Подготвяме лентата с инструменти
        $Calendar->prepareListToolbar($state);
        
        foreach($state->recs as $id => $rec) {
            if($rec->type == 'holiday' || $rec->type == 'non-working') {
                $time = dt::mysql2timestamp($rec->date);
                $data[(int) date('j', $time)]->isHoliday = TRUE;
            }
            if( date('Y-m-d', $time) < date('Y-m-d') ) {
                // unset($state->rows[$id]);  
            }
        }
 
        for($i = 1; $i <= 31; $i++) {            
            $data[$i]->url = toUrl(array('cal_Agenda', 'list', 'from' => "{$i}-{$month}-{$year}"));;
        }

        $tpl = new ET("[#MONTH_CALENDAR#] <br> [#AGENDA#]");

        $tpl->replace(static::renderCalendar($year, $month, $data, $header), 'MONTH_CALENDAR');
        $tpl->replace($Calendar->renderListTable($state), 'AGENDA');

        return $tpl;
    }

}