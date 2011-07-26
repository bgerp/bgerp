<?php

/**
 * Клас 'drdata_Wrapper'
 *
 * Поддържа системното меню и табовете на пакета 'Core'
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id: $
 * @link
 * @since
 */
class drdata_Wrapper extends core_Plugin
{
    
    
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('drdata_Countries', 'Страни');
        $tabs->TAB('drdata_IpToCountry', 'IP-to-Country');
        $tabs->TAB('drdata_DialCodes', 'Тел. кодове');
        $tabs->TAB('drdata_Vats', 'ЗДДС №');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->className);
        
        $tpl->append(tr($invoker->title) . " » Данни » ", 'PAGE_TITLE');
    }
}