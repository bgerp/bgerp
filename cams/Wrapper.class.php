<?php



/**
 * Клас 'cams_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'Core'
 *
 *
 * @category  bgerp
 * @package   cams
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cams_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        Mode::set('pageMenu', 'Наблюдение');
        
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('cams_Cameras', 'Камери');
        $tabs->TAB('cams_Records', 'Записи');
        $tabs->TAB('cams_Positions', 'Позиции');
        
        // $tpl = $tabs->renderHtml($tpl, $invoker->className);
        $tpl = $tabs->renderHtml($tpl, $invoker->tabName ? $invoker->tabName : $invoker->className);
        
        $tpl->replace('', 'NAV_BAR');
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
    }
}