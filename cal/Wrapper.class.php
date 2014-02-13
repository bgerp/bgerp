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
        $this->TAB('cal_Calendar', 'Календар', 'powerUser,admin');
        $this->TAB('cal_Tasks', 'Задачи', 'admin,doc,powerUser');
        $this->TAB('cal_Reminders', 'Напомняния', 'powerUser');
        $this->TAB('cal_Holidays', 'Празници', 'powerUser');
        $this->TAB('cal_Test', 'Тест', 'debuger');
        
       
        $this->title = 'Календар';

    }
}