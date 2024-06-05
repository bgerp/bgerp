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
        $period = Request::get('selectPeriod');

        if (!isset($period) || (Request::get('Ctr') != 'cal_Calendar')) {
            $period = 'today';
        }

        $this->TAB(array('cal_Calendar', 'list',  'selectPeriod' => $period), 'Календар->Списък', 'powerUser');
        $this->TAB(array('cal_Calendar', 'day',  'selectPeriod' => $period), 'Календар->Ден', 'powerUser');
        $this->TAB(array('cal_Calendar', 'week',  'selectPeriod' => $period), 'Календар->Седмица', 'powerUser');
        $this->TAB(array('cal_Calendar', 'month',  'selectPeriod' => $period), 'Календар->Месец', 'powerUser');
        $this->TAB(array('cal_Calendar', 'year',  'selectPeriod' => $period), 'Календар->Година', 'powerUser');
        
        $this->TAB('cal_Tasks', 'Задачи', 'admin,doc,powerUser');
        
        //$this->TAB('cal_TaskConditions', 'Задачи', 'admin,doc,powerUser');
        $this->TAB('cal_Reminders', 'Напомняния', 'powerUser');
        $this->TAB('cal_Holidays', 'Празници', 'admin');
        $this->TAB('cal_Test', 'Тест', 'debuger');
        
        
        $this->title = 'Календар';
    }
}
