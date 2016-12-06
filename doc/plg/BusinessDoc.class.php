<?php
/**
 * Клас 'doc_plg_BusinessDoc'
 *
 * Плъгин за избор на папка в която да се въздава документ.
 * Класа трябва да има метод getCoversAndInterfacesForNewDoc който връща масив от интерфейси
 * на които трябва да отговарят папките които могат да са корици на документи, или име на клас.
 * След това се рендира форма за избор на запис от всеки клас отговарящ на
 * интерфейса. Трябва да се определи точно една папка, не е позволено да се 
 * изберат повече от една. След като папката се уточни се отива в екшъна за
 * добавяне на нов запис в мениджъра на документа
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_plg_BusinessDoc extends core_Plugin
{
    
    
    /**
     * След инициализирането на модела
     * 
     * @param core_Mvc $mvc
     * @param core_Mvc $data
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        // Проверка за приложимост на плъгина към зададения $mvc
        static::checkApplicability($mvc);
        setIfNot($mvc->alwaysForceFolderIfEmpty, FALSE);
    }
    
    
    /**
     * Преди всеки екшън на мениджъра-домакин
     *
     * @param core_Manager $mvc
     * @param core_Et $tpl
     * @param core_Mvc $data
     */
    public static function on_BeforeAction(core_Mvc $mvc, &$tpl, $action)
    {
        if ($action != 'add') {
            // Плъгина действа само при добавяне на документ
            return;
        }
        
        // Ако нямаме сесия - да създадем 
        requireRole('user');
        
        if (!$mvc->haveRightFor($action)) {
            // Няма права за този екшън - не правим нищо - оставяме реакцията на мениджъра.
            return;
        }
        
    	if (Request::get('folderId', 'key(mvc=doc_Folders)') ||
            Request::get('threadId', 'key(mvc=doc_Threads)') ||
            Request::get('cloneId', 'key(mvc=doc_Containers)') ||
    		($mvc->alwaysForceFolderIfEmpty === FALSE && Request::get('originId', 'key(mvc=doc_Containers)'))) {
            // Има основание - не правим нищо
            
            return;
        }
        
        // Генериране на форма за основание
        $form = static::prepareReasonForm($mvc);
        
        // Ако няма форма - не правим нищо
        if(!$form) return;
        
        // Формата се инпутва
        $form->input();
        if ($form->isSubmitted()) {
            if ($p = self::getReasonParams($form)) {
                $tpl = new Redirect(
                
                	// Редирект към създаването на документа в ясната папка
                    toUrl(array($mvc, $action) + $p + array('ret_url' => static::getRetUrl($mvc)))
                );
                return FALSE;
            }
        }
        
        // Ако няма поне едно поле key във формата
        if(!count($form->selectFields("#key"))){ 
        	
        	redirect(array($mvc, 'list'), FALSE, '|Не може да се добави документ в папка, защото възможните списъци за избор са празни');
        }
        
        $form->title = 'Избор на папка';
        $form->toolbar->addSbBtn('Напред', 'default', array('class' => 'btn-next fright'), 'ef_icon = img/16/move.png, title=Продължи нататък');

        $form = $form->renderHtml();
        $tpl = $mvc->renderWrapping($form);
        
        // ВАЖНО: спираме изпълнението на евентуални други плъгини
        return FALSE;
    }
    
    
	/**
     * Помощен метод за определяне на URL при успешен запис или отказ
     * 
     * @param core_Mvc $mvc
     * @return array
     */
    protected static function getRetUrl(core_Mvc $mvc)
    {
        if (!$retUrl = getRetUrl()) {
            
            // Ако има права за листване
            if ($mvc->haveRightFor('list')) {
                $retUrl = array($mvc, 'list');
            } else {
                $retUrl = FALSE;
            }
        }
        
        return $retUrl;
    }
    
    
    /**
     * Подготвя формата за избор на папка
     * @param core_Mvc $mvc
     * @return core_Form $form
     */
    private static function prepareReasonForm(core_Mvc $mvc)
    {
    	// Между какви корици трябва да се избира
    	$interfaces = $mvc::getCoversAndInterfacesForNewDoc();
    	
    	// Ако няма корици се прескача плъгина
    	if(!count($interfaces)) return NULL;
    	
    	// Ако има '*' се показват всички класове които могат да са корици
    	if(in_array('*', $interfaces)){
    		$interfaces = array('doc_FolderIntf');
    	}
    	
    	// Намират се всички класове отговарящи на тези интерфейси
    	$coversArr = array();
    	foreach ($interfaces as $index => $int){
    		
    		// Ако иднекса е число приемаме, че е зададен интерфейс иначе приемаме, че е име на клас
    		if(is_numeric($index)){
    			$coversArr +=  core_Classes::getOptionsByInterface($int);
    		} else {
    			$clsRec = core_Classes::fetch("#name = '{$int}'", 'id,name');
    			$coversArr +=  array($clsRec->id => $clsRec->name);
    		}
    	}
    	
    	// Подготовка на формата за избор на папка
    	$form = cls::get('core_Form');
    	self::getFormFields($mvc, $form, $coversArr);
    	
    	return $form;
    }
    
    
    /**
     * Подготвя полетата на формата
     */
	private static function getFormFields(core_Mvc $mvc, &$form, $coversArr)
    {
    	foreach ($coversArr as $coverId){
    		 
    		// Подадената корица, трябва да е съществуващ 
    		// клас и да може да бъде корица на папка
    		if(cls::load($coverId, TRUE)){
    			if(cls::haveInterface('doc_FolderIntf', $coverId)){
    				
    				// Създаване на поле за избор от дадения клас
    				$Class = cls::get($coverId);
    				list($pName, $coverName) = explode('_', $coverId);
    				$coverName = $pName . strtolower(rtrim($coverName, 's')) . "Id";
    			    
                    $coverClassId = core_Classes::getId($coverId);
                    $query = doc_Folders::getQuery();
                    $query->where("#coverClass = {$coverClassId} AND #state != 'rejected' AND #state != 'closed'");
                    $mvc->RestrictQueryOnlyFolderForDocuments($query);
                    $query->orderBy('#modifiedOn', 'DESC');
    				$newOptions = array();

                    while($rec = $query->fetchAndCache()) {
                    	$oRec = (object)array('folderId' => $rec->id);
                    	if($oId = Request::get('originId')){
                    		$oRec->originId = $oId;
                    	}
                    	if($mvc->haveRightFor('add', $oRec)){
                    		$newOptions[$rec->coverId] = $rec->title;
                    	}
                    }

    		        if ($newOptions) {
    				    // Ако има достъпни корици, слагат се като опции
    				    $form->FNC($coverName, "key(mvc={$coverId},allowEmpty)", "input,caption={$Class->singleTitle},width=100%,key");
    				    $form->setOptions($coverName, $newOptions);
    			    }
    				 
    				if(!count($newOptions)){
    					// Ако няма нито една достъпна корица, полето става readOnly
    					$form->FNC($coverName, "varchar", "input,caption={$Class->singleTitle},width=100%");
    					$form->setReadOnly($coverName);
    					continue;
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * Връща ид-то на избраната папка,
     * проверява дали е избрана само една папка
     */
    private static function getReasonParams(core_Form $form)
    {
    	$selectedField = $value = NULL;
    	$errFields = array();
    	
    	// Обхождат се всички попълнени полета
    	$fields = $form->selectFields('');
    	foreach ($fields as $name => $fld){
    		$fldValue = $form->rec->{$name};
    		if($fldValue){
	    		if(!$value){
	    			$value = $fldValue;
	    			$selectedField = $fld->type->params['mvc'];
	    		} else {
	    			$errFields[] = $name;
	    		}
    		}
    	}

    	// Ако няма избран нито един обект, се показва грешка
		if(!$selectedField){
    		$form->setError(',', 'Не е избрана папка');
    		return;
    	}

    	// Ако има избран повече от един обект, се показва грешка
    	if(count($errFields)){
    		array_unshift($errFields, $selectedField);
    		$form->setError(implode(',', $errFields), 'Попълнете само едно от посочените полета');
    		return;
    	}
    	
    	$params = array('folderId' => $selectedField::forceCoverAndFolder($value));
    	
    	// Да се подават и другите параметри от урл-то
    	foreach(getCurrentUrl() as $key => $value){
    		if($key != 'App' && $key != 'Ctr' && $key != 'Act' && $key != 'Cmd'){
    			if(!$form->getField($key, FALSE)){
    				$params[$key] = $value;
    			}
    		}
    	}
   
    	// При избран точно един обект се форсира неговата папка и се връща
    	return $params;
    }
    
    
	/**
     * Проверява дали този плъгин е приложим към зададен мениджър
     */
    protected static function checkApplicability(core_Mvc $mvc)
    {
        // Прикачане е допустимо само към наследник на core_Manager ...
        if (!$mvc instanceof core_Manager) {
            return FALSE;
        }
        
        // ... към който е прикачен doc_DocumentPlg
        $plugins = arr::make($mvc->loadList);

        if (isset($plugins['doc_DocumentPlg'])) {
            return FALSE;
        } 
        
        return TRUE;
    }
}
