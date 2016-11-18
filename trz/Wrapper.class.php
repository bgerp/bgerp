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
        $this->TAB('trz_Payroll', 'Ведомост', 'ceo,trz');
        $this->TAB('trz_SalaryPayroll', 'Заработка->Сума', 'ceo,trz');
        $this->TAB('trz_SalaryIndicators', 'Заработка->КПЕ', 'ceo,trz');
        $this->TAB('trz_SalaryRules', 'Заработка->Правила', 'ceo,trz');
        $this->TAB('trz_Bonuses', 'Премии', 'ceo,trz');
        $this->TAB('trz_Sickdays', 'Болнични', 'ceo,trz');
        $this->TAB('trz_Requests', 'Отпуски->Молби', 'ceo,trz');
        $this->TAB('trz_Trips', 'Командировки', 'ceo,trz');
        $this->TAB('trz_Fines', 'Удръжки', 'ceo,trz');
        
              
        $this->title = 'ТРЗ « Персонал';
        Mode::set('menuPage', 'Персонал:ТРЗ');
    }
}