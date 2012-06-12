<?php



/**
 * Клас 'doc_ThreadRefreshPlg' - Ajax обновяване на нишка
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class doc_ThreadRefreshPlg extends core_Plugin
{
    
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $ajaxMode = Request::get('ajax_mode');
        
        $threadsLastModify = Mode::get("THREADS_LAST_MODIFY");

        $threadId = $data->threadRec->id;

        $lastModify = $data->threadRec->modifiedOn;

        if(($threadsLastModify[$threadId] == $lastModify) && $ajaxMode) { // bp($threadsLastModify, $lastModify);
            header("Expires: Sun, 19 Nov 1978 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");

            die;
        } else {
            $data->lastRefresh = $threadsLastModify[$threadId];
            $threadsLastModify[$threadId] = $lastModify;
            Mode::setPermanent("THREADS_LAST_MODIFY", $threadsLastModify);
        }
    }
    
    /**
     * Добавя след таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforeRenderWrapping($mvc, &$res, &$tpl, $data)
    {   
        if($data->action != 'list') return;

        $ajaxMode = Request::get('ajax_mode');

        if ($ajaxMode) {

             
            $status = $tpl->getContent();
                 
            $res->content = $status;

            if($data->lastRefresh && count($data->recs)) {
                $lastRec = end($data->recs);
                foreach($data->recs as $id => $r) {
                    if($r->modifiedOn > $data->lastRefresh) {
                        $docId = $data->rows[$id]->ROW_ATTR['id'];
                        $script .= "\n flashDoc('{$docId}');";
                        if($lastRec->id == $r->id) {
                            $script .= "window.scroll(0, 1000000);";
                        }
                    }
                }
                
                $res->script = $script;
            }

            header("Expires: Sun, 19 Nov 1978 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            echo json_encode($res);
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