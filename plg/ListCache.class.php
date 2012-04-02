<?php



/**
 * Клас 'plg_ListCache' - Кешира листовия изглед на модела
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class plg_ListCache extends core_Plugin
{
    
    
    /**
     * Извиква се преди подготовката на $data->recs и $data->rows за табличния изглед
     */
    function on_BeforePrepareListRows($mvc, &$res, $data)
    {
        if($mvc->className == 'core_Cache') return;
        
        if(count($data->recs)) {
            $data->listCacheHnd = md5(json_encode($data->recs));
            $data->listCacheDepends = arr::make($mvc->listCacheDepends);
            $data->listCacheDepends[] = $mvc;
            
            foreach($mvc->selectFields() as $fld) {
                if($fld->type->params['mvc']) {
                    $data->listCacheDepends[] = $fld->type->params['mvc'];
                }
            }
            
            $cachedData = core_Cache::get('ListCache', $data->listCacheHnd, 100, $data->listCacheDepends);
            
            if($cachedData !== FALSE) {
                $data->rows = $cachedData;
                
                return FALSE;
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    function on_AfterPrepareListRows($mvc, &$res, $data)
    {
        if($mvc->className == 'core_Cache' || !$data->listCacheHnd) return;
        
        core_Cache::set('ListCache', $data->listCacheHnd, $data->rows, 100, $data->listCacheDepends);
    }
}