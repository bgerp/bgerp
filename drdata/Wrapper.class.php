<?php



/**
 * Клас 'drdata_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'Core'
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class drdata_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('drdata_Countries', 'Страни');
        $tabs->TAB('drdata_Domains', 'Домейни');
        $tabs->TAB('drdata_Holidays', 'Празници');
        $tabs->TAB('drdata_IpToCountry', 'IP-to-Country');
        $tabs->TAB('drdata_DialCodes', 'Тел. кодове');
        $tabs->TAB('drdata_Vats', 'ЗДДС №');
        $tabs->TAB('drdata_Mvr', 'МВР');
        $tabs->TAB('drdata_DistrictCourts', 'Съдилища');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->className);
        
        $tpl->prepend(tr($invoker->title) . " » Данни » ", 'PAGE_TITLE');
    }
}