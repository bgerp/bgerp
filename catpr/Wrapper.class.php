<?php

/**
 * Клас 'cat_wrapper_Prices'
 *
 * "Опаковка" на изгледа на ценовия раздел в каталога
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
class catpr_Wrapper extends core_Plugin
{
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'catpr') );
        
        $tabs->TAB('catpr_Costs', 'Себестойност');
        $tabs->TAB('catpr_Pricegroups', 'Групи продукти');
        $tabs->TAB('catpr_Discounts', 'Класове клиенти');
        $tabs->TAB('catpr_Pricelists', 'Ценоразписи');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->tabName ? $invoker->tabName : $invoker->className);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
    }
}
