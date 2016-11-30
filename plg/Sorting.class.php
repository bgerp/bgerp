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
    public static function on_AfterPrepareListFields($mvc, $data)
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
                    } elseif($mvc->fields[$f]->kind != 'FNC' && strtolower(get_class($mvc->fields[$f]->type)) == 'type_key') {
                        $type = $mvc->fields[$f]->type;
                        if(($kField = $type->params['select']) && ($kMvc = $type->params['mvc'])) {
                            $dbField = $f . '_' . 'sort';
                        } else {
                            continue;
                        }
                    } elseif($mvc->fields[$f]->kind != 'FNC' && strtolower(get_class($mvc->fields[$f]->type)) == 'type_key2') {
                        $type = $mvc->fields[$f]->type;
                        if(($kField = $type->params['titleFld']) && ($kMvc = $type->params['mvc'])) {
                            $dbField = $f . '_' . 'sort';
                        } else {
                            continue;
                        }
                    } elseif($mvc->fields[$f]->kind != 'FNC' && !is_a($mvc->fields[$f]->type, 'type_Keylist') ) {
                        $dbField = $f;
                    } else {
                        continue;
                    }
                    
                    if(!$mvc->fields[$f]->notSorting) {
                        if(!$direction || $direction == 'none' || ($f != $field)) {
                            $data->plg_Sorting->fields[$f] = 'none';
                        } elseif ($direction == 'up') {
                            $data->plg_Sorting->fields[$f] = 'up';
                            if(strpos($dbField, '_sort')) {
                                $data->query->EXT($dbField, $kMvc, "externalName={$kField},externalKey={$f}");
                            }
                            $data->query->orderBy("#{$dbField}", 'ASC');
                        } elseif ($direction == 'down') {
                            $data->plg_Sorting->fields[$f] = 'down';
                            if(strpos($dbField, '_sort')) {
                                $data->query->EXT($dbField, $kMvc, "externalName={$kField},externalKey={$f}");
                            }
                            $data->query->orderBy("#{$dbField}", 'DESC');
                        } else {
                            error('@Неправилно сортиране', $field);
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * Извиква се след рендирането на таблицата от табличния изглед
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        if(count($data->recs) && count($data->plg_Sorting->fields)) {
        	
        	// Ако сме в режим принтиране не правим нищо
        	if (Mode::is('printing') || Mode::is('pdf') || Mode::is('text', 'xhtml')) return;
        	
            foreach($data->plg_Sorting->fields as $field => $direction) {
                
                // Ако няма такова поле, в тези, които трябва да показваме - преминаваме към следващото
                if(!$data->listFields[$field]) continue;

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
                        $sort = $field . '|none';
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
                
                $currUrl = getCurrentUrl();
                $currUrl["Sort"] = $sort;
                
                // Ако мениджъра е детайл на документ, добавяме и хендлъра на мастъра му в урл-то
                // за да може да отидем директно на самия документ в нишката
                if($mvc instanceof core_Detail){
                	if(cls::haveInterface('doc_DocumentIntf', $mvc->Master) && isset($data->masterId)){
                		$currUrl["#"] = $mvc->Master->getHandle($data->masterId);
                	}
                }
                 
                if(isset($mvc->fields[$field]) && $mvc->fields[$field]->type->getTdClass() == 'rightCol') {
                	$lastF = ltrim($lastF, '|*');
                    $fArr[count($fArr)-1] = $startChar . "|*<div class='rowtools'>" . "<a class='l' href='" .
                    ht::escapeAttr(toUrl($currUrl)) .
                    "' ><img  src=" . sbf($img) .
                    " width='16' height='16' alt='sort' class='sortBtn'></a>" . "<div class='l'>|{$lastF}|*</div></div>";  
                } else {
                	$lastF = ltrim($lastF, '|*');
                    $fArr[count($fArr)-1] = $startChar . "|*<div class='rowtools'><div class='l'>|" . $lastF . "|*</div><a class='r' href='" .
                    ht::escapeAttr(toUrl($currUrl)) .
                    "' ><img  src=" . sbf($img) .
                    " width='16' height='16' alt='sort' class='sortBtn'></a></div>";
                }
               
                $data->listFields[$field] = implode('->', $fArr);
            }
        }
    }
}