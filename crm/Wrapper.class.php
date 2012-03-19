<?php



/**
 * Клас 'crm_Wrapper'
 *
 * Опаковка на визитника
 *
 *
 * @category  all
 * @package   crm
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class crm_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => $invoker->className));
        
        $tabs->TAB('crm_Companies', 'Фирми');
        $tabs->TAB('crm_Persons', 'Лица');
        $tabs->TAB('crm_Groups', 'Групи');
        $tabs->TAB('crm_Calendar', 'Календар');
        $tabs->TAB('crm_Locations', 'Локации');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->className);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
    }
}