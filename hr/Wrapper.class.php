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
 * @copyright 2006 - 2012 Experta OOD
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
                
        $this->TAB('hr_EmployeeContracts', 'Назначения', 'admin,hr');
        $this->TAB('hr_Positions', 'Длъжности', 'admin,hr');
        $this->TAB('hr_Departments', 'Отдели', 'admin,hr');
        $this->TAB('hr_Shifts', 'Смени','admin,hr');
        $this->TAB('hr_WorkingCycles', 'Цикли', 'admin,dma');
        $this->TAB('hr_ContractTypes', 'Шаблони', 'admin,hr');
        
        $this->title = 'Персонал';
    }
}