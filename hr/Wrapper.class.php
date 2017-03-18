<?php



/**
 * Клас 'hr_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'hr'
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class hr_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на опаковката с табове
     */
    function description()
    {
        $this->TAB('hr_Indicators', 'Заплащане->Индикатори', 'ceo,hr');
        $this->TAB('hr_Payroll', 'Заплащане->Ведомост','ceo,hr');

        $this->TAB('hr_EmployeeContracts', 'Документи->Договори', 'ceo,hr');
        $this->TAB('hr_Leaves', 'Документи->Отпуски', 'ceo,hr');
        $this->TAB('hr_Sickdays', 'Документи->Болнични', 'ceo,hr');
        $this->TAB('hr_Trips', 'Документи->Командировки', 'ceo,hr');
        $this->TAB('hr_Bonuses', 'Документи->Премии', 'ceo,hr');
        $this->TAB('hr_Deductions', 'Документи->Удръжки', 'ceo,hr');

        //$this->TAB('hr_Departments', 'Структура->Отдели', 'ceo,hr,admin');
        $this->TAB(array('hr_Departments', 'list', 'Chart'=> 'List'), 'Структура->Таблица', 'ceo,hr');
        $this->TAB(array('hr_Departments', 'list', 'Chart'=> 'Structure'), 'Структура->Графика', 'ceo,hr');
        $this->TAB('hr_Positions', 'Структура->Длъжности','ceo,hr,admin');
        $this->TAB('hr_WorkingCycles', 'Структура->Цикли', 'ceo,hr,admin');
        $this->TAB('hr_ContractTypes', 'Структура->Шаблони', 'ceo,hr,admin');
        
        $this->title = 'Персонал';
    }
}