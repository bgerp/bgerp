<?php


/**
 * 
 * 
 * @category  ef
 * @package   plg
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class plg_LoginWrapper extends plg_SystemWrapper
{
    
    
    /**
     * 
     * @see plg_ProtoWrapper::on_AfterRenderWrapping()
     */
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));

        $tabs->TAB('core_Logs', 'Общ');
        $tabs->TAB('core_LoginLog', 'Логин');

        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        
        $mvc->currentTab = 'Логове';
    }
}