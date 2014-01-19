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
 * @copyright 2006 - 2014 Experta OOD
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
    public static function on_AfterDescription($mvc)
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
    public function on_AfterAttachDetails($mvc, $res, $details)
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
    protected static function checkApplicability($mvc)
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
     * Преди показване на форма за добавяне/промяна.
     *
     * @param sales_Sales $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$templates = doc_TplManager::getTemplates($mvc->getClassId());
    	(count($templates)) ? $data->form->setOptions('template', $templates) : $data->form->setReadOnly('template');
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, &$rec)
    {
    	// Ако няма шаблон, за шаблон се приема първия такъв за модела
    	if(!$rec->template){
			$templates = doc_TplManager::getTemplates($mvc->getClassId());
			$rec->template = key($templates);
		}
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_BeforeRenderSingleLayout($mvc, &$res, $data)
    {
    	// За текущ език се избира този на шаблона
		$lang = doc_TplManager::fetchField($data->rec->template, 'lang');
    	core_Lg::push($lang);
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_BeforeRenderSingleToolbar($mvc, &$res, $data)
    {
    	// Маха се пушнатия език, за да може да се рендира тулбара нормално
    	core_Lg::pop();
    }
    
    
	/**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_AfterRenderSingleToolbar($mvc, &$res, $data)
    {
    	// След рендиране на тулбара отново се пушва езика на шаблона
    	$lang = doc_TplManager::fetchField($data->rec->template, 'lang');
    	core_Lg::push($lang);
    }
    
    
	/**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	// Ако има избран шаблон то той се замества в еденичния изглед
    	$content = doc_TplManager::getTemplate($data->rec->template);
    	
    	if($mvc->templateFld){
    		
    		// Ако има посочен плейсхолдър където да отива шаблона, то той се използва
    		$tpl->replace($content, $mvc->templateFld);
    	} else {
    		
    		// Ако няма плейсхолър за шаблона, то се замества целия еденичен изглед
    		$tpl = $content;
    	}
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	// След като документа е рендиран, се възстановява нормалния език
    	core_Lg::pop();
    }
    
    
	/**
     * След подготовка на на единичния изглед
     */
    static function on_AfterPrepareSingle($mvc, &$res, &$data)
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
    	}
    }
}