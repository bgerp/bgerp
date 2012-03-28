<?php



/**
 * Клас 'plg_RefreshRows' - Ajax обновяване на табличен изглед
 *
 *
 * @category  all
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_RefreshRows extends core_Plugin
{
    
    
    /**
     * Добавя след таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_AfterRenderListTable($mvc, &$tpl)
    {
        $ajaxMode = Request::get('ajax_mode');
        
        if ($ajaxMode) {
            
            $status = $tpl->getContent();
            
            $statusHash = md5($status);
            
            $savedName = "REFRESH_ROWS_" . md5(toUrl(getCurrentUrl()));
            $savedHash = Mode::get($savedName);
            
            if(empty($savedHash)) $savedHash = md5($savedHash);
            
            if($statusHash != $savedHash) {
                
                Mode::setPermanent($savedName, $statusHash);
                
                $res->content = $status;
                
                echo json_encode($res);
            }
            
            die;
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