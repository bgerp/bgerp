<?php

/**
 * Клас 'plg_Sorting' - Сортиране на колоните в табличния изглед
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
class plg_Sorting extends core_Plugin
{
    
    
    /**
     *  Извиква се след поготовката на колоните ($data->listFields)
     */
    function on_AfterPrepareListFields($mvc, $data)
    {
        if($sort = Request::get('Sort')) {
            
            list($field, $direction) = explode('|', $sort, 2);
        }
        
        $data->listFields = arr::make($data->listFields, TRUE);
        
        if(count($data->listFields)) {
            foreach($data->listFields as $f => $caption) {
                
                if($mvc->fields[$f]) {
                    if($mvc->fields[$f]->sortingLike) {
                        $dbField = $mvc->fields[$f]->sortingLike;   
                    } else {
                        $dbField = $f;
                    }
                    
                    if(!$mvc->fields[$f]->notSorting) {
                        if(!$direction || $direction == 'none' || ($f != $field) ) {
                            $data->plg_Sorting->fields[$f] = 'none';
                        } elseif ($direction == 'up') {
                            $data->plg_Sorting->fields[$f] = 'up';
                            $data->query->orderBy("#{$dbField}", 'ASC');
                        } elseif ($direction == 'down') {
                            $data->plg_Sorting->fields[$f] = 'down';
                            $data->query->orderBy("#{$dbField}", 'DESC');
                        } else {
                            error('Неправилно сортиране', $field);
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     *  Извиква се след рендирането на таблицата от табличния изглед
     */
    function on_BeforeRenderListTable($mvc, $tpl, $data)
    {
        if(count($data->recs) && count($data->plg_Sorting->fields)) {
            foreach($data->plg_Sorting->fields as $field => $direction) {
                switch($direction) {
                    case 'none':
                        $img = 'img/icon_sort.gif';
                        $sort = $field . '|up';
                        break;
                    case 'up':
                        $img = 'img/icon_sort_up.gif';
                        $sort = $field . '|down';
                        break;
                    case 'down':
                        $img = 'img/icon_sort_down.gif';
                        $sort = $f . '|none';
                        break;
                    default:
                    expect(FALSE, $direction);
                }
                
                $data->listFields[$field] .= "|*<a href='" .
                url::addParams($_SERVER['REQUEST_URI'], array("Sort" => $sort)) .
                "' style='' ><img  src=" . sbf($img) .
                " width='16' height='16' border='0' alt='*' style='float:right;'></a>";
            }
        }
    }
}