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
        
        // Добавя поле за избор на шаблон, ако няма ,where=#docClassId \\= \\'{$mvc->getClassId()}\\'
        if(empty($mvc->fields['template'])){
        	$mvc->FLD('template', "key(mvc=doc_TplManager,select=name)", 'caption=Допълнително->Шаблон');
        }
        
        setIfNot($mvc->templateFld, 'SINGLE_CONTENT');
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
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_BeforeRenderSingleLayout($mvc, &$res, $data)
    {
    	if(!$data->rec->template){
			$templates = doc_TplManager::getTemplates($mvc->getClassId());
			$data->rec->template = key($templates);
		}
		
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
    	$tpl->replace($content, $mvc->templateFld);
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	// След като документа е рендиран, се възстановява нормалния език
    	core_Lg::pop();
    }
}