<?php

/**
 * Клас 'rfid_Wrapper'
 *
 * Поддържа системното меню и табовете на пакета 'rfid'
 *
 * @category   bgerp
 * @package    rfid
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id: Guess.php,v 1.29 2009/04/09 22:24:12 dufuz Exp $
 * @link
 * @since
 */
class rfid_Wrapper extends core_Plugin
{
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('rfid_Events', 'Събития');
        $tabs->TAB('rfid_Tags', 'Карти');
        $tabs->TAB('rfid_Readers', 'Четци');
        $tabs->TAB('rfid_Holders', 'Обекти');
        $tabs->TAB('rfid_Ownerships', 'Собственици');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->className);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
    }
}