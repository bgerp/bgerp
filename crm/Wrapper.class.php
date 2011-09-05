<?php

/**
 * Клас 'crm_Wrapper'
 *
 * Опаковка на визитника
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

class crm_Wrapper extends core_Plugin
{
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => $invoker->className) );
        
        $tabs->TAB('crm_Companies', 'Фирми');
        $tabs->TAB('crm_Persons',   'Лица');
        $tabs->TAB('crm_Groups',    'Групи');
        $tabs->TAB('crm_Calendar',  'Календар');

        $tpl = $tabs->renderHtml($tpl, $invoker->className);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
    }
}