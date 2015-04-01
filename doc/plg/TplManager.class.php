<?php


/**
 * Клас 'doc_plg_TplManager'
 *
 * Плъгин за  който позволява на даден мениджър да си избира шаблон
 * за единичния изглед качен в doc_TplManager. Ако има избран шаблон
 * от формата то този изглед се избира по подразбиране а не единичния
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_plg_TplManager extends core_Plugin
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
        
        // Добавя поле за избор на шаблон, ако няма
        if(empty($mvc->fields['template'])){
        	$mvc->FLD('template', "key(mvc=doc_TplManager,select=name)", 'caption=Допълнително->Шаблон');
        }
    }
    
    
    /**
     * Изпълнява се след закачане на детайлите
     */
    public static function on_AfterAttachDetails(core_Mvc $mvc, &$res, $details)
    {
    	if($mvc->details){
        	$details = arr::make($mvc->details);
        	
        	// На всеки детайл от модела му се прикача 'doc_plg_TplManagerDetail' (ако го няма)
        	foreach($details as $Detail){
        		if($mvc->$Detail instanceof $Detail){
        			$plugins = $mvc->$Detail->getPlugins();
        			if(empty($plugins['doc_plg_TplManagerDetail'])){
        				$mvc->$Detail->load('doc_plg_TplManagerDetail');
        			}
        		}
        	}
        }
    }
    
    
	/**
     * Проверява дали този плъгин е приложим към зададен мениджър
     * 
     * @param core_Mvc $mvc
     * @return boolean
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
    
    
    
    /**
     * Метод връщащ темплейта на документа, ако го няма връща ид-то на първия възможен
     * темплейт за този тип документи
     */
    public static function on_AfterGetTemplate(core_Mvc $mvc, &$res, $id)
    {
    	$rec = is_object($id) ? $id : $mvc->fetch($id);
    	expect($rec);
    	
    	if(empty($rec->template)){
    		$templates = doc_TplManager::getTemplates($mvc->getClassId());
    		$res = key($templates);
    	} else {
    		$res = $rec->template;
    	}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm(core_Mvc $mvc, &$data)
    {
    	$templates = doc_TplManager::getTemplates($mvc->getClassId());
    	(count($templates)) ? $data->form->setOptions('template', $templates) : $data->form->setReadOnly('template');
    }
    
    
    /**
     * Изпълнява се преди преобразуването към вербални стойности на полетата на записа
     */
    protected static function on_BeforeRecToVerbal($mvc, &$row, $rec)
    {
    	if(is_object($rec)){
    		if($rec->id){
    			
    			// Ако няма шаблон, за шаблон се приема първия такъв за модела
    			$rec->template = $mvc->getTemplate($rec->id);
    			$rec->tplLang = doc_TplManager::fetchField($rec->template, 'lang');
    			
				core_Lg::push($rec->tplLang);
    		}
    	}
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_BeforeRenderSingleLayout(core_Mvc $mvc, &$res, $data)
    {
    	// За текущ език се избира този на шаблона
		$lang = doc_TplManager::fetchField($data->rec->template, 'lang');
    	core_Lg::push($lang);
    	
    	// Ако ще се замества целия сингъл, подменяме го елегантно
    	if(!$mvc->templateFld){
    		$data->singleLayout = doc_TplManager::getTemplate($data->rec->template);
    	}
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_BeforeRenderSingleToolbar(core_Mvc $mvc, &$res, $data)
    {
    	// Маха се пушнатия език, за да може да се рендира тулбара нормално
    	core_Lg::pop();
    }
    
    
	/**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleToolbar(core_Mvc $mvc, &$res, $data)
    {
    	// След рендиране на тулбара отново се пушва езика на шаблона
    	$lang = doc_TplManager::fetchField($data->rec->template, 'lang');
    	core_Lg::push($lang);
    }
    
    
	/**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout(core_Mvc $mvc, &$tpl, $data)
    {
    	// Ако има посочен плейсхолдър където да отива шаблона, то той се използва
    	if($mvc->templateFld){
    		$content = doc_TplManager::getTemplate($data->rec->template);
    		$tpl->replace($content, $mvc->templateFld);
    	}
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_AfterRenderSingle(core_Mvc $mvc, &$tpl, $data)
    {
    	// След като документа е рендиран, се възстановява нормалния език
    	core_Lg::pop();
    }
    
    
	/**
     * След подготовка на на единичния изглед
     */
    public static function on_AfterPrepareSingle(core_Mvc $mvc, &$res, &$data)
    {
    	// Ако има избран шаблон
    	if($data->rec->template){
    		$toggleFields = doc_TplManager::fetchField($data->rec->template, 'toggleFields');
    		
    		// Ако има данни, за кои полета да се показват от мастъра
    		if(count($toggleFields) && $toggleFields['masterFld'] !== NULL){
    			
    			// Полетата които трябва да се показват
    			$fields = arr::make($toggleFields['masterFld']);
    			
    			// Всички полета, които могат да се скриват/показват
    			$toggleFields = arr::make($mvc->toggleFields);
    			
    			// Намират се засичането на двата масива с полета
    			$intersect = array_intersect_key((array)$data->row, $toggleFields);
    			
    			foreach ($intersect as $k => $v){
    				
    				// За всяко от опционалните полета: ако не е избран да се показва, се маха
    				if(!in_array($k, $fields)){
    					unset($data->row->$k);
    				}
    			}
    		}
    		
    		// Ако има скриптов клас за шаблона, подаваме му данните
    		if($Script = doc_TplManager::getTplScriptClass($data->rec->template)){
    			$Script->modifyMasterData($mvc, $data);
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
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	if($rec->tplLang){
			core_Lg::pop();
			 
			// Заместваме вербалното състояние и име с тези според езика на текущата сесия
			if($mvc->getFieldType('state', FALSE)){
				$row->state = $mvc->getFieldType('state')->toVerbal($rec->state);
			}
			$row->singleTitle = tr($mvc->singleTitle);
    	}
    }
}