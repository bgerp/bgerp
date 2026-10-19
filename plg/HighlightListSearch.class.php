<?php


/**
 * Клас 'plg_HighlightListSearch' - Засветява резултатите при търсене в листовите изгледи
 *
 *
 * @category  ef
 * @package   plg
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class plg_HighlightListSearch extends core_Plugin
{
    public function on_AfterRenderList($mvc, $res, $data)
    {
        // Име на полето по което се търси
        $field = ($mvc->searchFilterField ?? null) ?: 'search';
        
        // Оцветяваме ако има търсене. Масив (напр. search[]=...) не е валидно търсене
        if (($q = Request::get($field)) && !is_array($q)) {
            plg_Search::highlight($res, $q, $mvc->className);
        }
    }
}
