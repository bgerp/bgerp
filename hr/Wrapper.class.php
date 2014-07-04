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
        $this->TAB('hr_EmployeeContracts', 'Назначения', 'ceo,hr');
        $this->TAB(array('hr_Departments', 'list', 'Chart'=> 'List'), 'Структура->Таблица', 'ceo,hr');
        $this->TAB(array('hr_Departments', 'list', 'Chart'=> 'Structure'), 'Структура->Графика', 'ceo,hr');
        $this->TAB('hr_Professions', 'Професии','admin,hr');
        $this->TAB('hr_WorkingCycles', 'График', 'ceo,dma,hr');
        $this->TAB('hr_ContractTypes', 'Данни', 'ceo,hr');
        
        $this->title = 'Персонал';
    }
}