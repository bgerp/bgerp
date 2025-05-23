<?php


/**
 * Клас 'ibex_Register' - Данни от енергийната борса
 *
 *
 * @category  bgerp
 * @package   ibex
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class ibex_Register extends core_Manager
{
    /**
     * Кой има право да променя?
     */
    protected $canEdit = 'admin';
    
    
    /**
     * Кой има право да добавя?
     */
    protected $canAdd = 'admin';


    /**
     * Кой може да редактира данните, добавени от системата
     */
    public $canDeletesysdata = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    protected $canList = 'admin, debug';
    
    
    /**
     * Кой има право да го изтрие?
     */
    protected $canDelete = 'admin';
    
    
    /**
     * Заглавие
     */
    public $title = 'Данни от енергийната борса';
    
    
    /**
     * Плъгините и враперите, които ще се използват
     */
    public $loadList = 'plg_Created,plg_Sorting,plg_RowTools2';


    /**
     * Описание
     */
    public function description()
    {
        $this->FLD('date', 'date', 'caption=Дата');
        $this->FLD('kind', 'varchar(32)', 'caption=Вид,smartCenter');
        $this->FLD('price', 'double(decimals=2)', 'caption=Стойност');

        $this->setDbIndex('date,kind');
    }


  /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if($rec->kind == '00-24') {
            $row->ROW_ATTR['style'] = "background-color:#ffcc99;";
        }
    }

    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        // Подготовка на филтъра
        $data->query->orderBy('#date=DESC,#kind=DESC');
    }



    /**
     * Извличане по разписание на данните от пазара
     */
    public function cron_Retrieve()
    {
        core_App::setTimeLimit(200);

        $date = dt::now(false);
        
        for($i = -1; $i < 30; $i++) {
            
            $date = dt::addDays(-1 * $i, null, false);

            if($this->count("#date = '{$date}'") < 25) {

                $rows = $this->retrieve2($date);
           
                if($rows === false) continue;

                foreach($rows as $hour => $price) {
                    $rec = new stdClass();
                    $rec->date = $date;
                    $rec->kind = $hour;
                    $rec->price = $price;
                    
                    if(!$this->fetch("#date = '{$rec->date}' AND #kind = '{$rec->kind}' && #price = {$rec->price}")) {
                        $this->save($rec);
                    }
                }
            }
        }
    }


    /**
     * Извлича цените от страницата на IBEX
     */
    private function retrieve2($date)
    {
        static $text;
 
        if(!isset($text)) {
            $text = core_Url::loadUrl("https://ibex.bg/%d0%b4%d0%b0%d0%bd%d0%bd%d0%b8-%d0%b7%d0%b0-%d0%bf%d0%b0%d0%b7%d0%b0%d1%80%d0%b0/%d0%bf%d0%b0%d0%b7%d0%b0%d1%80%d0%b5%d0%bd-%d1%81%d0%b5%d0%b3%d0%bc%d0%b5%d0%bd%d1%82-%d0%b4%d0%b5%d0%bd-%d0%bd%d0%b0%d0%bf%d1%80%d0%b5%d0%b4/day-ahead-prices-and-volumes-v2-0/");
        }

        $text = preg_replace("/\\s/", '', $text);
        $date = date("d.m.Y", dt::mysql2timestamp($date));
        
        $prices = [];

        for($i = 0; $i < 24; $i++) {
            $hour = str_pad($i, 2, '0', STR_PAD_LEFT);
            
            $left = "<td class='column-time_part'>{$hour}:00:00</td><td class='column-date_part'>{$date}</td><td class='column-price_eur'>";
            $left = preg_replace("/\\s/", '', $left);
 
            $right = "</td>";

            $to = str_pad((((int) $hour) + 1), 2, '0',  STR_PAD_LEFT);
            $key = $hour . '-' . $to;
            
            $cut = trim(str::cut($text, $left, $right));

            if(strlen($cut) == 0) return false;

            $prices[$key] = (float) str::cut($text, $left, $right) * 1.95583;

            if($prices[$key] === false) return false;
        }
 
        return $prices;
    }


    /**
     * Остарял вариант!
     * Осъществява извличането на данните
     */
    private function retrieve($date)
    {
        $day = 8;

        $url = "http://www.ibex.bg/download-prices-volumes-data-table.php?date={$date}&lang=bg";
 
        $content = file_get_contents($url);
        
        $res = array();

        if($content) {
            $fx = fileman::absorbStr($content, 'IbexBG', $date . '.xls');
            $rows = msoffice_Excel::getRows($fx);
            
            $res['00-24'] = $rows[2][$day];
            
            $dbl = $this->getFieldtype('price');
            foreach($rows as $i => $week) {
                if($i >=13 && $week[1] == 'BGN/MWh') {
                    list($from, $to) = explode('-', $week[0]);
                    $from = str_pad($from, 2, '0', STR_PAD_LEFT);
                    $to   = str_pad($to, 2, '0', STR_PAD_LEFT);
                    if(ord(substr($to, -1)) > ord('9')) {
                        $to = str_pad($to, 3, '0', STR_PAD_LEFT);
                    }

                    $price = $dbl->fromVerbal($week[$day]);
                    
                    if($price !== null) { 
                        $res[$from . '-' . $to] =  $price;
                    }
                }
            }
        }
        
        if(!countR($res)) {
            self::logWarning("Не извличам нищо от " . $url);
        }

        return $res;
    }
}
