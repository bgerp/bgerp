<?php

/**
 * Клас 'doc_Wrapper'
 *
 * Поддържа системното меню и табовете на пакета 'doc'
 *
 * @category   Experta Framework
 * @package    doc
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id: $
 * @link
 * @since
 */
class doc_Wrapper extends core_Plugin
{
    
    
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('doc_Folders', 'Папки');
        $tabs->TAB('doc_Notifications', 'Известия');
        $tabs->TAB('doc_Email', 'Е-мейл');
        $tabs->TAB('doc_Tasks', 'Задачи');

        $tpl = $tabs->renderHtml($tpl, $invoker->className);
        
        $tpl->append(tr($invoker->title) . " » " , 'PAGE_TITLE');
    }
}