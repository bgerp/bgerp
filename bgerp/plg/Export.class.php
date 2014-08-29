<?php



/**
 * Плъгин за експортиране на данни
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_plg_Export extends core_Plugin
{
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		defIfNot($mvc->canExport, 'ceo');
	}
	
	
	/**
	 * Извиква се след подготовката на toolbar-а за табличния изглед
	 */
	static function on_AfterPrepareListToolbar(core_Mvc $mvc, &$data)
	{
		if($mvc->haveRightFor('export')){
			$data->toolbar->addBtn('Експорт', array($mvc, 'export', 'ret_url' => TRUE), 'ef_icon=img/16/export.png');
		}
	}
    
	
    /**
     * Функция връщаща опции с всички драйвери които могат да се прикачват
     * към мениджъра
     * 
     * @return array $options - масив с възможни драйвъри
     */
    public static function getImportDrivers(core_Mvc $mvc)
    {
    	$options = array();
    	$drivers = core_Classes::getOptionsByInterface('bgerp_ExportIntf');
    	
    	foreach ($drivers as $id => $driver){
    		$Driver = cls::get($id);
    		if($Driver->isApplicable($mvc)){
    			$options[$id] = $Driver->title;
    		}
    	}
    	
    	return $options;
    }
    
    
    /**
     * Преди всеки екшън на мениджъра-домакин
     */
    public static function on_BeforeAction(core_Mvc $mvc, &$tpl, $action)
    {
    	if($action == 'export'){
    		
    		// Проверка за права
    		$mvc->requireRightFor('export');
    		 
    		// Трябва да има инсталиран поне един драйвър за експорт
    		$options = self::getImportDrivers($mvc);
    		
    		// Подготвяме формата
    		$form = cls::get('core_Form');
    		$form->method = 'GET';
    		$form->title = "Експортиране на {$mvc->title}";
    		$form->FNC('driver', 'class(interface=bgerp_ExportIntf,allowEmpty,select=title)', 'input,caption=Драйвър,mandatory,silent', array('attr' => array('onchange' => "addCmdRefresh(this.form);this.form.submit()")));
    		
    		// Ако има опции за избор на драйвър слагаме ги, иначе правим полето readOnly
    		if(count($options)){
    			$form->setOptions('driver', $options);
    		} else {
    			$form->setreadOnly('driver');
    		}
    		
    		// Инпутваме тихите полета
    		$form->input(NULL, 'silent');
    		
    		// Ако е избран драйвър, той добавя полета към формата
    		if($form->rec->driver){
    			$Driver = cls::get($form->rec->driver);
    			$Driver->prepareExportForm($form);
    		}
    		
    		// Инпут на формата
    		$form->input();
    		
    		// Драйвера проверява формата
    		if($Driver){
    			$Driver->checkExportForm($form);
    		}
    		
    		// Ако формата е събмитната
    		if($form->isSubmitted()){
    			$Driver = cls::get($form->rec->driver);
    			$Driver->export($form->rec);
    			followRetUrl();
    		}
    		 
    		// Добавяне на туулбара
    		if(count($options)){
    			$form->toolbar->addSbBtn('Експорт', 'default', array('class' => 'btn-next'), 'ef_icon = img/16/export.png');
    		} else {
    			$form->toolbar->addBtn('Експорт', array(), 'error=Няма налични драйвъри за експорт');
    		}
    		
    		$form->toolbar->addBtn('Отказ', array($this, 'list'), 'ef_icon = img/16/close16.png');
    		$form = $form->renderHtml();
    		
    		$tpl = $mvc->renderWrapping($form);
    		
    		return FALSE;
    	}
    }
}