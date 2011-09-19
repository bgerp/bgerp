<?php

/**
 * Клас 'common_Wrapper'
 *
 * Поддържа системното меню и табовете на пакета 'Core'
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
class cat_Wrapper extends core_Plugin
{
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'cat') );
        
        $tabs->TAB('cat_Products', 'Продукти');
        $tabs->TAB('cat_Categories', 'Категории');
        $tabs->TAB('cat_Groups', 'Групи');
        $tabs->TAB('cat_Params', 'Параметри');
        $tabs->TAB('cat_Packagings', 'Опаковки');
        $tabs->TAB('cat_Prices', 'Цени');
        $tabs->TAB('cat_Pricelists', 'Ценоразписи');
        $tabs->TAB('cat_UoM', 'Мерки');

        if (haveRole("admin,cat")) {
        }
        
        $tpl = $tabs->renderHtml($tpl, $invoker->tabName ? $invoker->tabName : $invoker->className);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
    }
}