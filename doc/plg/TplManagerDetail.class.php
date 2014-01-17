<?php


/**
 * Клас 'doc_plg_TplManagerDetail' - помощен плъгин прикачащ се от doc_plg_TplManager към
 * всички детайли на модела. 
 * Той скрива определени полета от списъчния изглед на детайла, които са определени от шаблона
 *
 * За да скрива ненужните полета от формата на детайла, плъгина трябва да е прикачен от кода
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_plg_TplManagerDetail extends core_Plugin
{
    
    
	/**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$masterRec = $data->masterRec;
    	if(!$masterRec->template){
    		$templates = doc_TplManager::getTemplates($mvc->Master->getClassId());
			$masterRec->template = key($templates);
    	}
    	
    	$toggleFields = doc_TplManager::fetchField($masterRec->template, 'toggleFields');
    	if(count($toggleFields) && $toggleFields[$mvc->className] !== NULL){
    		
	    	// Полетата които трябва да се показват
	    	$fields = arr::make($toggleFields[$mvc->className]);
	    			
	    	// Всички полета, които могат да се скриват/показват
	    	$toggleFields = arr::make($mvc->toggleFields);
	    	$intersect = array_intersect_key($data->form->selectFields(""), $toggleFields);
	    				
	    	// Ако някое от полетата не трябва да се показва, то се скрива от формата
	    	foreach ($intersect as $k => $v){
	    		if(!in_array($k, $fields) && empty($v->mandatory)){
	    			$data->form->setField($k, 'input=none');
	    		}
	    	}
    	}
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    static function on_AfterPrepareListFields($mvc, &$data)
    {
    	$masterRec = $data->masterData->rec;
    	$toggleFields = doc_TplManager::fetchField($masterRec->template, 'toggleFields');
    		
    	if(count($toggleFields) && $toggleFields[$mvc->className] !== NULL){
    			
    		// Полетата които трябва да се показват
    		$fields = arr::make($toggleFields[$mvc->className]);
    			
    		// Всички полета, които могат да се скриват/показват
    		$toggleFields = arr::make($mvc->toggleFields);
    		$intersect = array_intersect_key($data->listFields, $toggleFields);
    			
    		foreach ($intersect as $k => $v){
    				
    			// За всяко от опционалните полета: ако не е избран да се показва, се маха
    			if(!in_array($k, $fields)){
    				unset($data->listFields[$k]);
    			}
    		}
    	}
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_BeforeRenderListToolbar($mvc, &$res, $data)
    {
    	// Маха се пушнатия език, за да може да се рендира тулбара нормално
    	core_Lg::pop();
    }
    
    
	/**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_AfterRenderListToolbar($mvc, &$res, $data)
    {
    	// След рендиране на тулбара отново се пушва езика на шаблона
    	$lang = doc_TplManager::fetchField($data->masterData->rec->template, 'lang');
    	core_Lg::push($lang);
    }
}