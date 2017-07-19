<?php


/**
 * Клас 'plg_Clone' - Плъгин за клониране в листов и сингъл излгед
 *
 * @category  bgerp
 * @package   plg
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class plg_Clone extends core_Plugin
{
    
    
	/**
     * Извиква се след описанието на модела
     */
    public static function on_AfterDescription(&$invoker)
    {
        // Правата по подразбиране за екшъните
        setIfNot($invoker->canClonesysdata, 'admin, ceo');
        setIfNot($invoker->canCloneuserdata, 'user');
        setIfNot($invoker->canClonerec, 'user');
        $invoker->FLD('clonedFromId', 'key(mvc=doc_Containers)', 'input=hidden,forceField');
    }
    
    
    /**
     * Преди да се изпълни екшъна
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    { 
        // Ако ще клонираме полетата
        if ($action != 'clonefields') return ;
        
        // id на записа, който ще клонираме
        $id = Request::get('id', 'int');
        
        // Вземаме записа
        $rec = $mvc->fetch($id);
        
        // Очакваме да има такъв запис
        expect($rec);
        
        // Права за работа с екшън-а
        $mvc->requireRightFor('clonerec', $rec);
        
        // Подготвяме формата
        $data = new stdClass();
        $data->action = 'clone';
        
        // Подготвяме формата, но без да генерираме ивент, ивента ръчно ще го инвоукнем
        // след като сме махнали от река зададените полета
        $mvc->prepareEditForm_($data);
        $form = &$data->form;
        $form->setDefault('clonedFromId', $rec->id);
        
        // Проверяваме имали полета, които не искаме да се клонират
        $dontCloneFields = arr::make($mvc->fieldsNotToClone, TRUE);
        
        // Ако има махаме ги от $form->rec
        if(count($dontCloneFields)){
        	foreach ($dontCloneFields as $unsetField){
        		unset($form->rec->{$unsetField});
        	}
        }
        
        // Инвоукваме ръчно ивента за подготовка на формата, след като сме махнали от
        // $form->rec -а полетата, които не искаме да се копират, така ако в ивента
        // добавяме дефолти ще се запишат на чисто
        $mvc->invoke('AfterPrepareEditForm', array(&$data, &$data));
        
        // Задаваме екшъна
        $form->setAction($mvc, 'clonefields');
        
        // Инпутваме формата
        $form->input();
        
        // Генерираме събитие в $this, след въвеждането на формата
        $mvc->invoke('AfterInputEditForm', array($form));
        
        // Ако формата е изпратена без грешки
        if($form->isSubmitted()) {
            
            // Обекта, който ще се запишем
            $nRec = new stdClass();
            $nRec = clone $form->rec;
            unset($nRec->id);
         
            // Инвокваме фунцкцията, ако някой иска да променя нещо
            $mvc->invoke('BeforeSaveCloneRec', array($rec, &$nRec));
            
            // Маркираме записа като клониран
            $nRec->_isClone = TRUE;
            
            $fields = array();
            
            // Да няма дублиране на уникални полета
            if(!$mvc->isUnique($nRec, $fields)) {
                $data->form->setError($fields, "Вече съществува запис със същите данни");
            }
        }
        
        // Ако формата е изпратена без грешки
        if($form->isSubmitted()) {
        	
            // Ако няма проблем при записа
            if ($mvc->save($nRec)) {
                
            	// Инвокваме фунцкцията, ако някой иска да променя нещо
            	$mvc->invoke('AfterSaveCloneRec', array($rec, &$nRec));
            	
                // Ако е инстанция на core_Master
                if ($mvc instanceof core_Master) {
                    
                    // Редиректваме към сингъла
                    $redirectUrl = array($mvc, 'single', $nRec->id);
                } else {
                    
                    // Редиректваме към листовия излгед
                    $redirectUrl = array($mvc, 'list');
                }
                
                $mvc->logWrite('Клониране', $rec->id);
                $mvc->logWrite('Създаване с клониране', $nRec->id);
                
                // За да се редиректне към съответната страница
                $res = new Redirect($redirectUrl);
                
                return FALSE;
            } else {
                
                // Показваме съобщение за грешка
                core_Statuses::newStatus('|Грешка при клониране на запис', 'warning');
            }
        }
        
        // URL за бутона отказ
        $retUrl = getRetUrl();
        
        // Ако не зададено
        if (empty($retUrl)) {
            
            // Ако има сингъл
            if ($mvc instanceof core_Master) {
                $retUrl = array($mvc, 'single', $rec->id);
            } else {
                $retUrl = array($mvc, 'list');
            }
        }
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title=Запис на документа');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        if ($mvc instanceof core_Master) {
            $singleLink = $mvc->getLinkToSingle($id);
        } else {
            $singleLink = '|' . mb_strtolower($mvc->getTitle()) . '|*';
        }
        
        // Добавяме титлата на формата
        $form->title = 'Клониране на|* ' . $singleLink;
        
        // Рендираме опаковката
        $res = $mvc->renderWrapping($form->renderHtml());
        
        return FALSE;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Manager $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако има запис и има права
        if ($rec && $requiredRoles != 'no_one') {
        
            // Ако записа е на системен потребител
            if ($rec->createdBy == core_Users::SYSTEM_USER) {
                
            	if($action == 'edit') {
            		$requiredRoles = $mvc->getRequiredRoles('editsysdata', $rec, $userId);
            	}
            	
            	if($action == 'delete') {
            		$requiredRoles = $mvc->getRequiredRoles('deletesysdata', $rec, $userId);
            	}
            }
            
            // Ако ще се клонира
            if ($action == 'clonerec') {
                
                // Ако е създаден от системния потребител
                if ($rec->createdBy == core_Users::SYSTEM_USER) {
                    
                    // Проверява се дали има права да клонира системните данни
                    if (!$mvc->haveRightFor('clonesysdata', $rec)) {
                        $requiredRoles = 'no_one';
                    }
                } else {
                    
                    // Ако е създаден от потребител
                    
                    // Проверява се дали има права за клониране на данните
                    if (!$mvc->haveRightFor('cloneuserdata', $rec)) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се след преобразуването към вербални стойности на полетата на записа
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        // Ако е наследник на master, да не се показва бутона за клониране в листовия изглед, а само в сингъла
        if ($mvc instanceof core_Master) return ;
        
        // Ако се намираме в режим "печат", не показваме инструментите на реда
        if (Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('text', 'plain') || Mode::is('pdf')) return;
        
        // Ако листваме
        if (!arr::haveSection($fields, '-list')) return;
        
        // Ако нямаме права за клониране, да не се показва линка
        if (!$mvc->haveRightFor('clonerec', $rec)) return ;
        
        // Определяме в кое поле ще показваме инструментите
        $field = $mvc->rowToolsField ? $mvc->rowToolsField : 'id';
        
        // Съдържанието на полето
        $rowField = $row->$field;
        
        // Ако полето не обект
        if (!is_object($rowField)) {
            
            // Създаваме обекта
            $row->$field = new ET();
            
            // Добавяме номера на реда
            $row->$field->prepend($rowField, 'ROWTOOLS_CAPTION');
        }
        
        // Добавяме линк, който води до промяна на записа
        $row->$field->prepend($mvc->getCloneLink($rec), 'TOOLS');
    }
    
    
    /**
     * Връща линк за клониране
     * 
     * @param core_Mvc $mvc
     * @param core_ET $res
     * @param object $rec
     */
    public static function on_AfterGetCloneLink($mvc, &$res, $rec)
    {
        // URL' то за клониране
        $cloneUrl = array($mvc, 'cloneFields', $rec->id, 'ret_url' => TRUE);
        
        // Иконата за промяна
        $cloneSbf = sbf("img/16/clone.png");
        
        // Ако не е подадено заглавиет, създаваме линк с иконата
        $res = ht::createLink('<img src=' . $cloneSbf . ' width="16" height="16">', $cloneUrl, NULL, 'title=Клониране');
    }
    
    
    /**
     * След подготвяне на сингъл тулбара
     * 
     * @param core_Master $mvc
     * @param object $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {   
        // Ако имаме права за клониране, да се показва бутона
        if ($mvc->haveRightFor('clonerec', $data->rec)) {
            
            $singleTitle = tr($mvc->singleTitle);
            
            $singleTitle = mb_strtolower($singleTitle);
            
            // Добавяме бутон за клониране в сингъл изгледа
            $title = tr('|Клониране на|*' . ' ' . $singleTitle);
            $data->toolbar->addBtn('Клониране', array($mvc, 'cloneFields', $data->rec->id, 'ret_url' => array($mvc, 'single', $data->rec->id)), "ef_icon=img/16/clone.png,title={$title},row=2, order=19.1");
        }
    }
    
    
    /**
     * Метод клониращ детайлите
     */
    public static function cloneDetails($Details, $oldMasterId, $newMasterId)
    {
    	// Ако има изброени детайли за клониране
    	$Details = arr::make($Details, TRUE);
    	if(count($Details)){
    		$notClones = FALSE;
    			 
    		// За всеки от тях
    		foreach ($Details as $det){
    			$Detail = cls::get($det);
    			if(!isset($Detail->masterKey)) continue;
    	
    			if(method_exists($Detail, 'cloneDetails')){
    				$Detail->cloneDetails($oldMasterId, $newMasterId);
    			} else {
    	
    				// Клонираме записа и го свързваме към новия запис
    				$query = $Detail->getQuery();
    				$query->where("#{$Detail->masterKey} = {$oldMasterId}");
    				$query->orderBy('id', "ASC");
    				$dRecs = $query->fetchAll();

    				$dontCloneFields = arr::make($Detail->fieldsNotToClone, TRUE);
    				
    				if(is_array($dRecs)){
    					foreach($dRecs as $dRec){
    						$oldRec = clone $dRec;
    						$dRec->{$Detail->masterKey} = $newMasterId;
    						unset($dRec->id);
    	
    						// Ако има махаме ги от $form->rec
    						if(count($dontCloneFields)){
    							foreach ($dontCloneFields as $unsetField){
    								unset($dRec->{$unsetField});
    							}
    						}
    						
    						$Detail->invoke('BeforeSaveClonedDetail', array($dRec, $oldRec));
    	
    						if($Detail->isUnique($dRec, $fields)){
    									
    							// Записваме клонирания детайл
    							$Detail->save($dRec);
    							$Detail->invoke('AfterSaveClonedDetail', array($dRec, $oldRec));
    						} else {
    							$notClones = TRUE;
    						}
    					}
    				}
    			}
    		}
    			 
    		// Ако някой от записите не са клонирани защото са уникални сетваме предупреждение
    		if($notClones) {
    			core_Statuses::newStatus('Някои от детайлите не бяха клонирани, защото са уникални', 'warning');
    		}
    	}
    }
    
    
    /**
     * След клониране на записа
     * 
     * @param core_Mvc $mvc
     * @param stdClass $rec  - клонирания запис
     * @param stdClass $nRec - новия запис
     */
    public static function on_AfterSaveCloneRec($mvc, $rec, $nRec)
    {
    	$Details = $mvc->getDetailsToClone($rec);
    	$mvc->invoke('BeforeSaveCloneDetails', array($nRec, &$Details));
    	self::cloneDetails($Details, $rec->id, $nRec->id);
    }
    
    
    /**
     * Метод по подразбиране на детайлите за клониране
     */
    public static function on_AfterGetDetailsToClone($mvc, &$res, $rec)
    {
    	// Добавяме артикулите към детайлите за клониране
    	$res = arr::make($mvc->cloneDetails, TRUE);
    }
}
