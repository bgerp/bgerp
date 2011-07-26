<?php

/**
 * Клас 'hr_Wrapper'
 *
 * Поддържа системното меню и табовете на пакета 'hr'
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id: Guess.php,v 1.29 2009/04/09 22:24:12 dufuz Exp $
 * @link
 * @since
 */
class hr_Wrapper extends core_Plugin
{
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
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