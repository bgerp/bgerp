<?php



/**
 * Клас 'plg_Sorting' - Сортиране на колоните в табличния изглед
 *
 * Ако в свойствата на $mvc има $mvc->defaultSorting = 'field=up/down'
 * това поле се сортира по подразбиране в началото
 * Допълнителни атрибути в дефиницията на поле:
 * о sortingLike=[име на поле] при сортиране се използват стойностите на посоченото поле, вмето тези на текущото
 * о notSorting - премахва възможността за сортиране по това поле
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
class plg_Sorting extends core_Plugin
{
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    function on_AfterPrepareListFields($mvc, $data)
    {
        if($sort = Request::get('Sort')) {
            
            list($field, $direction) = explode('|', $sort, 2);
        } elseif($sort = $mvc->defaultSorting) {
            
            list($field, $direction) = explode('=', $sort, 2);
        }
        
        $data->listFields = arr::make($data->listFields, TRUE);
        
        if(count($data->listFields)) {
            foreach($data->listFields as $f => $caption) {
                
                if(empty($caption)) continue;
                
                if($mvc->fields[$f]) {
                    if($mvc->fields[$f]->sortingLike) {
                        $dbField = $mvc->fields[$f]->sortingLike;
                    } elseif($mvc->fields[$f]->kind != 'FNC') {
                        $dbField = $f;
                    } else {
                        continue;
                    }
                    
                    if(!$mvc->fields[$f]->notSorting) {
                        if(!$direction || $direction == 'none' || ($f != $field)) {
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
     * Извиква се след рендирането на таблицата от табличния изглед
     */
    function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        if(count($data->recs) && count($data->plg_Sorting->fields)) {
            foreach($data->plg_Sorting->fields as $field => $direction) {
                switch($direction) {
                    case 'none' :
                        $img = 'img/icon_sort.gif';
                        $sort = $field . '|up';
                        break;
                    case 'up' :
                        $img = 'img/icon_sort_up.gif';
                        $sort = $field . '|down';
                        break;
                    case 'down' :
                        $img = 'img/icon_sort_down.gif';
                        $sort = $f . '|none';
                        break;
                    default :
                    expect(FALSE, $direction);
                }
                
                $fArr = explode('->', $data->listFields[$field]);
                $lastF = &$fArr[count($fArr)-1];
                if($lastF{0} == '@') {
                    $startChar = '@';
                    $lastF = substr($lastF, 1);
                } else {
                    $startChar = '';
                }
                $lastF = $startChar . "|*<div class='rowtools'><div class='l'>|" . $lastF . "|*</div><a class='r' href='" .
                url::addParams($_SERVER['REQUEST_URI'], array("Sort" => $sort)) .
                "' ><img  src=" . sbf($img) .
                " width='16' height='16' border='0' alt='*'></a></div>";
                
                $data->listFields[$field] = implode('->', $fArr);
            }
        }
    }
}