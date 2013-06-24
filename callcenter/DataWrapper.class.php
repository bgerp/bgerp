<?php


/**
 * Поддържа системното меню и табове-те на пакета 'callcenter'
 *
 * @category  bgerp
 * @package   callcenter
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class callcenter_DataWrapper extends callcenter_Wrapper
{
    
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));

        $tabs->TAB('callcenter_InternalNum', 'Вътрешни номера');
        $tabs->TAB('callcenter_ExternalNum', 'Външни номера');

        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        
        $mvc->currentTab = 'Данни';
    }
}