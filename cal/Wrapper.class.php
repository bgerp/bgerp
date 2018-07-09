<?php


/**
 * Клас 'cal_Wrapper'
 *
 * Опаковка на календара
 *
 *
 * @category  bgerp
 * @package   cal
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class cal_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $from = Request::get('from', 'date');
        
        if (!$from) {
            $from = dt::verbal2mysql();
        }
        
        $from = dt::mysql2verbal($from, 'd.m.Y', null, false);
        
        $this->TAB(array('cal_Calendar', 'list',  'from' => $from), 'Календар->Списък', 'powerUser,admin');
        $this->TAB(array('cal_Calendar', 'day',  'from' => $from), 'Календар->Ден', 'powerUser,admin');
        $this->TAB(array('cal_Calendar', 'week',  'from' => $from), 'Календар->Седмица', 'powerUser,admin');
        $this->TAB(array('cal_Calendar', 'month',  'from' => $from), 'Календар->Месец', 'powerUser,admin');
        $this->TAB(array('cal_Calendar', 'year',  'from' => $from), 'Календар->Година', 'powerUser,admin');
        
        $this->TAB('cal_Tasks', 'Задачи', 'admin,doc,powerUser');
        
        //$this->TAB('cal_TaskConditions', 'Задачи', 'admin,doc,powerUser');
        $this->TAB('cal_Reminders', 'Напомняния', 'powerUser');
        $this->TAB('cal_Holidays', 'Празници', 'admin');
        $this->TAB('cal_Test', 'Тест', 'debuger');
        
        
        $this->title = 'Календар';
    }
}
