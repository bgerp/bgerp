<?php

/**
 * Клас 'fileman_Wrapper' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    fileman
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class fileman_Wrapper extends core_Plugin
{
    
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        $tabs->TAB('fileman_Files', 'Файлове');
        $tabs->TAB('fileman_Versions', 'Версии');
        $tabs->TAB('fileman_Buckets', 'Кофи');
        $tabs->TAB('fileman_Download', 'Сваляния');
        $tabs->TAB('fileman_Data', 'Данни');
        $tabs->TAB('fileman_Get', 'Вземания');
        $tabs->TAB('fileman_Mime2Ext', 'MIME');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->className);
        
        $tpl->append(tr($invoker->title) . " » " . tr('Хранилище') . " » ", 'PAGE_TITLE');
    }
}