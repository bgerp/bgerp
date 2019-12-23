<?php


/**
 * Клас 'hr_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'hr'
 *
 *
 * @category  bgerp
 * @package   hr
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class hr_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на опаковката с табове
     */
    public function description()
    {
        $this->TAB('hr_Indicators', 'Заплащане->Индикатори', 'ceo,hrMaster');
        $this->TAB('hr_Payroll', 'Заплащане->Ведомост', 'ceo,hrMaster');
        $this->TAB('hr_IndicatorNames', 'Заплащане->Видове индикатори', 'debug,admin');
        
        $this->TAB('hr_EmployeeContracts', 'Документи->Договори', 'ceo,hrMaster');
        $this->TAB('hr_Leaves', 'Документи->Отпуски', 'ceo, hr, hrMaster, admin');
        $this->TAB('hr_Sickdays', 'Документи->Болнични', 'ceo,hrMaster');
        $this->TAB('hr_Trips', 'Документи->Командировки', 'ceo,hrMaster');
        $this->TAB('hr_Bonuses', 'Документи->Премии', 'ceo,hrMaster');
        $this->TAB('hr_Deductions', 'Документи->Удръжки', 'ceo,hrMaster');
        
        $this->TAB(array('hr_Departments', 'list', 'Chart' => 'List'), 'Структура->Таблица', 'ceo,hrMaster');
        $this->TAB(array('hr_Departments', 'list', 'Chart' => 'Structure'), 'Структура->Графика', 'ceo,hrMaster');
        $this->TAB('hr_Positions', 'Структура->Длъжности', 'ceo,hrMaster,admin');
        $this->TAB('hr_WorkingCycles', 'Структура->Цикли', 'ceo,hrMaster,admin');
        $this->TAB('hr_ContractTypes', 'Структура->Шаблони', 'ceo,hrMaster,admin');
        
       
        $this->TAB('hr_FormCv', 'Подбор->Форма CV', 'ceo,hrMaster');
        $this->TAB('hr_WorkPreff', 'Подбор->Опции за подбор', 'ceo,hrMaster');
        
        $this->title = 'Персонал';
    }
}
