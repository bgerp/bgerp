<?php

/**
 * Клас 'plg_RefreshRows' - Ajax обновяване на табличен изглед
 *
 *
 * @category   Experta Framework
 * @package    plg
 * @author     Milen Georgiev
 * @copyright  2006-2009 Experta Ltd.
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class plg_RefreshRows extends core_Plugin
{
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_AfterRenderListTable($mvc, $tpl)
    {
        $ajaxMode = Request::get('ajax_mode');
        
        if ($ajaxMode) {
            $page = $tpl->getContent();
            
            echo $page;
            
            exit;
        } else {
            $params = $_GET;
            unset($params['virtual_url']);
            $params['ajax_mode'] = 1;
            $url = toUrl($params);
            
            // Ако не е зададено, рефрешът се извършва на всеки 60 секунди
            $time = $mvc->refreshRowsTime ? $mvc->refreshRowsTime : 60000;
            
            $tpl->appendOnce("setTimeout(function(){ajaxRefreshContent('" . $url . "', {$time},'rowsContainer');}, {$time});", 'ON_LOAD');
            $tpl->prepend("<div id='rowsContainer'>");
            $tpl->append("</div>");
        }
    }
}