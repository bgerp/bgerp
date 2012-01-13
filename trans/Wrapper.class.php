<?php


/**
 * Клас 'trans_Wrapper'
 *
 * Поддържа системното меню на пакета trans
 *
 *
 * @category  bgerp
 * @package   trans
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class trans_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'trans') );
        
        $tabs->TAB('trans_DeliveryTerms', 'Условия на доставка');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->tabName ? $invoker->tabName : $invoker->className);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
    }
}