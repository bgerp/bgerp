<?php



/**
 * Клас 'hr_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'hr'
 *
 *
 * @category  all
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class hr_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('hr_EmployeeContracts', 'Назначения');
        $tabs->TAB('hr_Positions', 'Длъжности');
        $tabs->TAB('hr_Departments', 'Отдели');
        $tabs->TAB('hr_Shifts', 'Смени');
        $tabs->TAB('hr_WorkingCycles', 'Цикли');
        $tabs->TAB('hr_ContractTypes', 'Шаблони');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->className);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
    }
}