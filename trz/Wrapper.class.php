<?php



/**
 * ТРЗ - опаковка
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class trz_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
             
        $this->TAB('trz_Salaries', 'Заплати', 'admin,trz');
        $this->TAB('trz_Bonuses', 'Премии', 'admin,trz');
        $this->TAB('trz_Sickdays', 'Болнични', 'admin,trz');
        $this->TAB('trz_Leaves', 'Отпуски', 'admin,trz');
        $this->TAB('trz_Fines', 'Глоби', 'admin,trz');
        $this->TAB('trz_Payrolls', 'Ведомост за заплати', 'admin,trz');
        
              
        $this->title = 'ТРЗ « Персонал';
        Mode::set('menuPage', 'Персонал:ТРЗ');
    }
}