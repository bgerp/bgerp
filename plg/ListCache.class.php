<?php

/**
 * Клас 'plg_ListCache' - Кешира листовия изглед на модела
 *
 * @category   Experta Framework
 * @package    plg
 * @author     Milen Georgiev
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 3
 * @since      v 0.1
 */
class plg_ListCache extends core_Plugin
{
    function on_BeforePrepareListRows($mvc, $res, $data)
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

    function on_AfterPrepareListRows($mvc, $res, $data)
    {
        if($mvc->className == 'core_Cache' || !$data->listCacheHnd ) return;

        core_Cache::set('ListCache', $data->listCacheHnd, $data->rows, 100, $data->listCacheDepends);
    }

}