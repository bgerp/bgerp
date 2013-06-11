<?php



/**
 * Плъгин за импорт на данни от Бизнес навигатор
 * 
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_plg_Import extends core_Plugin
{
	/**
     * Извиква се след описанието на модела
     * 
     * @param core_Mvc $mvc
     */
    function on_AfterDescription(core_Mvc $mvc)
    {
    	// Проверка за приложимост на плъгина към зададения $mvc
        if(!static::checkApplicability($mvc)) return;
    }
	
    
	/**
     * Проверява дали този плъгин е приложим към зададен мениджър
     * 
     * @param core_Mvc $mvc
     * @return boolean
     */
    protected static function checkApplicability($mvc)
    {
    	// Прикачане е допустимо само към наследник на cat_Products ...
        if (!$mvc instanceof core_Manager) {
            return FALSE;
        }
        
        return TRUE;
    }
    
    
	/**
   	 * Обработка на SingleToolbar-a
   	 */
   	static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(haveRole('admin')){
    		$importOptions = core_Classes::getOptionsByInterface('bgerp_ImportIntf');
    		if(count($importOptions)){
    			$url = array($mvc, 'import', 'retUrl' => TRUE);
    			$data->toolbar->addBtn('Импорт', $url, NULL, 'ef_icon=img/16/import16.png');
    		}
    	}
    }
    
    
	/**
     * Преди всеки екшън на мениджъра-домакин
     *
     * @param core_Manager $mvc
     * @param core_Et $tpl
     * @param core_Mvc $data
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
    	if ($action == 'import') {
    		$importOptions = core_Classes::getOptionsByInterface('bgerp_ImportIntf');
    		expect(count($importOptions), 'Няма инсталиран драйвър за импортиране');
    		
    		// Генерираме форма за основание и "обличаме" я във wrapper-а на $mvc.
        	$form = static::prepareImportForm();
        	$rec = $form->input();
        	if($form->isSubmitted()){
        		if(empty($rec->csvFile) && empty($rec->text)){
        			$form->setError('csvFile,text', 'Неможе и двете полета да са празни');
        		}
        		
        		if(!$form->gotErrors()) {
        			$ImportClass = cls::get($rec->importClass);
        			$res = $ImportClass->import($rec->csvFile, $rec->text);
        			return Redirect(array($ImportClass->getDestinationManager(), 'list'), 'FALSE', $res);
        		}
        	}
        	
        	$form->toolbar->addSbBtn('Запис', 'save', array('class'=>'btn-next btn-move'));
        	$form->toolbar->addBtn('Отказ', array($mvc, 'list'), array('class'=>'btn-cancel'));
        	
        	$form = $form->renderHtml();
        	$tpl = $mvc->renderWrapping($form);
        	
        	return FALSE;
        }
    }
    
    
	/**
     * 
     * Enter description here ...
     */
    private static function prepareImportForm()
    {
    	$form = cls::get('core_Form');
    	$form->title = 'Импортиране';
    	$form->FNC('importClass', 'class(interface=bgerp_ImportIntf,select=title)', 'caption=Технолог,input,mandatory');
        $form->FNC('text', 'text(rows=3)', 'caption=Текст,input,width=35em');
    	$form->FNC('csvFile', 'fileman_FileType(bucket=bnav_importCsv)', 'caption=CSV Файл,input');
       
    	return $form;
    }
}

