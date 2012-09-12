<?php



/**
 * Клас 'cal_Wrapper'
 *
 * Опаковка на календара
 *
 *
 * @category  bgerp
 * @package   cal
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cal_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        
        
        $this->TAB('cal_Calendar', 'Календар', 'cal,admin');
        $this->TAB('cal_Day', 'Ден');
        $this->TAB('cal_Week', 'Седмица');
        $this->TAB('cal_Month', 'Месец');
        $this->TAB('cal_Year', 'Година');
        $this->TAB('cal_Tasks', 'Задачи', 'admin,doc');
        $this->TAB('cal_Holidays', 'Празници', 'user');
       
        $this->title = 'Календар';

    }
}