<?php



/**
 * Клас 'plg_PrevAndNext' - Добавя бутони за предишен и следващ във форма за редактиране
 * и при разглеждането на няколко записа
 *
 *
 * @category  bgerp
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_PrevAndNext extends core_Plugin
{
    
	
	/**
	 * След описанието на модела
	 */
    public static function on_AfterDescription($mvc)
    {
        $mvc->doWithSelected = arr::make($mvc->doWithSelected, TRUE);
        $mvc->doWithSelected['edit'] = 'Редактиране';
        if(cls::isSubclass($mvc, 'core_Master')) {
            $mvc->doWithSelected['browse'] = 'Преглед'; 
        }
    }

    
    /**
     * Промяна на бутоните
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareRetUrl($mvc, $data)
    {   
        $selKey = static::getModeKey($mvc);

        if(Mode::is($selKey)) {
            $Cmd = Request::get('Cmd');
            
            if (isset($Cmd['save_n_prev'])) {
                $data->retUrl = array($mvc, 'edit', 'id' => $data->buttons->prevId, 'PrevAndNext' => 'on', 'ret_url' => getRetUrl());
                
                return FALSE;
            } elseif (isset($Cmd['save_n_next'])) {
                $data->retUrl = array($mvc, 'edit', 'id' => $data->buttons->nextId, 'PrevAndNext' => 'on', 'ret_url' => getRetUrl());
                
                return FALSE;
            }
        }
    }
    
    
    /**
     * Позволява преглед на няколко избрани записа на техните сингли
     */
    public static function on_BeforeAction(core_Manager $mvc, &$res, $action)
    {
        if ($action == 'browse') {
        	
        	$mvc->requireRightFor('browse');
        	
	        $selKey = static::getModeKey($mvc);
			$id = Request::get('id', 'int');
	        
	        if($sel = Request::get('Selected')) {
				$data = new stdClass();
	        	
	            // Превръщаме в масив, списъка с избраниуте id-та
	            $selArr = arr::make($sel);
	
	            // Записваме масива в сесията, под уникален за модела ключ
	            Mode::setPermanent($selKey, $selArr);
	            
	            // Зареждаме id-то на първия запис за редактиране
	            expect(ctype_digit($id = $selArr[0]));
	            
	        } elseif(Request::get('PrevAndNext')) {
				
	            // Изтриваме в сесията, ако има избрано множество записи 
	            Mode::setPermanent($selKey, NULL);
	            
	        }
        	
            if(!is_object($data)) {
                $data = new stdClass();
            }
	        expect($data->rec = $mvc->fetch($id));
	            
	        // Трябва да има $rec за това $id
		      if(!($data->rec)) { 
		            
		        // Имаме ли въобще права за единичен изглед?
		        $mvc->requireRightFor('single');
		    }
		        
	        $mvc->requireRightFor('single', $data->rec);
				
	        $data->buttons = new stdClass();
        	$data->buttons->prevId = self::getNeighbour($mvc, $data->rec, -1);
        	$data->buttons->nextId = self::getNeighbour($mvc, $data->rec, +1);
        		
	        // Подготвяме данните за единичния изглед
		    $mvc->prepareSingle($data);
		        
		    // Рендираме изгледа
		    $tpl = $mvc->renderSingle($data);
		        
		    // Опаковаме изгледа
		    $tpl = $mvc->renderWrapping($tpl, $data);
		        
		    $res = $tpl;
		        
        	return FALSE;
   		}
    }
    
    /**
     * Връща id на съседния запис в зависимост next/prev
     *
     * @param stdClass $data
     * @param string $dir
     */
    private static function getNeighbour($mvc, $rec, $dir)
    { 
        $id = $rec->id;
        if(!$id) return;

        $selKey = static::getModeKey($mvc);
        $selArr = Mode::get($selKey);
		$res = NULL;

        if(count($selArr)) {
            $selId = array_search($id, $selArr);
            if($selId === FALSE) return;
            $selNeighbourId = $selId + $dir;
            $res = $selArr[$selNeighbourId];
        } 

        return $res;
    }
 
    
    /**
     * Преди подготовката на формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_BeforePrepareEditForm($mvc, &$res, &$data)
    {
        if($sel = Request::get('Selected')) {

            // Превръщаме в масив, списъка с избраниуте id-та
            $selArr = arr::make($sel);
             
            // Зареждаме id-то на първия запис за редактиране
            expect(ctype_digit($id = $selArr[0]));
            
            Request::push(array('id' => $id));  
            
        } 
    }
    
    
    /**
     * Подготовка на формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $selKey = static::getModeKey($mvc);
        
        $Cmd = Request::get('Cmd');
        
        $selArr = array();
        
        if(is_a($mvc, 'core_Detail')) {
            if($id = Request::get('id', 'int')) {
                $rec = $mvc->fetch($id);
                $key = $mvc->masterKey;
                if($key && ($masterId = $rec->{$key})) {
                    $query = $mvc->getQuery();
                    $query->orderBy('id');
                    while($dRec = $query->fetch("#{$key} = $masterId")) {
                        $selArr[] = $dRec->id;
                    }
                }
            }
        }

   
        if($sel = Request::get('Selected')) {
            // Превръщаме в масив, списъка с избраниуте id-та
            $selArr = arr::make($sel);
        }

        if(!empty($selArr)) {
 
            // Записваме масива в сесията, под уникален за модела ключ
            Mode::setPermanent($selKey, $selArr);
            
            // Зареждаме id-то на първия запис за редактиране
            if(!$id) {
                expect(ctype_digit($id = $selArr[0]), $selArr);
            }
            
            expect($exRec = $mvc->fetch($id));
            $data->form->rec = (object)arr::fillMissingKeys($exRec, $data->form->rec);
            $mvc->requireRightFor('edit', $data->form->rec);
            
        } elseif( !($data->form->cmd == 'save_n_next' || $data->form->cmd == 'save_n_prev' || Request::get('PrevAndNext'))) {
        	
            // Изтриваме в сесията, ако има избрано множество записи 
            Mode::setPermanent($selKey, NULL);
        }
        
        // Определяне на индикатора за текущ елемент
        if ($selArr = Mode::get($selKey)) {
            
            $id = Request::get('id', 'int');
            
            $pos = array_search($id, $selArr) + 1;
            $data->prevAndNextIndicator = $pos . '/' . count($selArr);
             
            $data->buttons = new stdClass();
            $data->buttons->prevId = self::getNeighbour($mvc, $data->form->rec, -1);
            $data->buttons->nextId = self::getNeighbour($mvc, $data->form->rec, +1);
        }
    }
    
    
    /**
     * Добавяне на бутони за 'Предишен' и 'Следващ'
     */
    public static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        $selKey = static::getModeKey($mvc);
        
        if($selArr = Mode::get($selKey)) {

            if(count($selArr) > 1) {
                if (isset($data->buttons->nextId)) {
                    $data->form->toolbar->addSbBtn('»»»', 'save_n_next', 'class=noicon fright,order=30, title = Следващ');
                } else {
                    $data->form->toolbar->addSbBtn('»»»', 'save_n_next', 'class=btn-disabled noicon fright,disabled,order=30, title = Следващ');
                }
                
                $data->form->toolbar->addFnBtn($data->prevAndNextIndicator, '', 'class=noicon fright,order=30');

                if (isset($data->buttons->prevId)) {
                    $data->form->toolbar->addSbBtn('«««', 'save_n_prev', 'class=noicon fright,order=30, title = Предишен');
                } else {
                    $data->form->toolbar->addSbBtn('«««', 'save_n_prev', 'class=btn-disabled noicon fright,disabled,order=30, title = Предишен');
                }
            }

            $data->form->setHidden('ret_url', Request::get('ret_url'));
        }
    }


	/**
     * След подготовка на тулбара на единичен изглед.
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
     	$selKey = static::getModeKey($mvc);
        
        if($selArr = Mode::get($selKey)) {
        	$action = Request::get('Act');
        	
        	if($action == 'browse' && count($selArr)) {
        		if (isset($data->buttons->nextId)) {
        			$data->toolbar->addBtn('»»»', array($mvc, 'browse', $data->buttons->nextId), 'class=noicon fright,title = Следващ');
        		} else {
        			$data->toolbar->addBtn('»»»', array(), 'class=btn-disabled noicon fright,disabled,title = Следващ');
        		}
        		
        		if (isset($data->buttons->prevId)) {
        			$data->toolbar->addBtn('«««', array($mvc, 'browse', $data->buttons->prevId), 'class=noicon fright', array('style' => 'margin-left:5px;', 'title' => 'Предишен'));
        		} else {
        			$data->toolbar->addBtn('«««', array(), 'class=btn-disabled noicon fright,disabled', array('style' => 'margin-left:5px;', 'title' => 'Предишен'));
        		}
        	}
        }
    }
    
    
    /**
     * Връща ключа за кеша, който се определя от сесията и модела
     */
    public static function getModeKey($mvc) 
    {
        return $mvc->className . '_PrevAndNext';
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *   
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($action == 'browse' && $requiredRoles != 'no_one') {
            if(!$mvc->haveRightFor('single', $rec, $userId)) {
                 $requiredRoles = 'no_one';
            } else {
                 $requiredRoles = $mvc->getRequiredRoles('single', $rec);
            }
        }
    }
}