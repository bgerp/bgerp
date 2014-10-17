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
             
        $this->TAB('trz_SalaryPayroll', 'Заплати->Ведомост', 'ceo,trz');
        $this->TAB('trz_SalaryIndicators', 'Заплати->Показатели', 'ceo,trz');
        $this->TAB('trz_SalaryRules', 'Заплати->Правила', 'ceo,trz');
        $this->TAB('trz_Bonuses', 'Премии', 'ceo,trz');
        $this->TAB('trz_Sickdays', 'Болнични', 'ceo,trz');
        $this->TAB('trz_Requests', 'Отпуски->Молби', 'ceo,trz');
        $this->TAB('trz_Orders', 'Отпуски->Заповеди', 'ceo,trz');
        $this->TAB('trz_Trips', 'Командировки', 'ceo,trz');
        $this->TAB('trz_Fines', 'Глоби', 'ceo,trz');
        
              
        $this->title = 'ТРЗ « Персонал';
        Mode::set('menuPage', 'Персонал:ТРЗ');
    }
}