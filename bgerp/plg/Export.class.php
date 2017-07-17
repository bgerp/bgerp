<?php



/**
 * Плъгин за експортиране на данни
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
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
        
        /**
         * @todo Чака за документация...
         */
        defIfNot($mvc->canExport, 'ceo, admin');
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    public static function on_AfterPrepareListToolbar(core_Mvc $mvc, &$data)
    {
        if($mvc->haveRightFor('export') && self::getExportDrivers($mvc)){
        	$url = getCurrentUrl();
        	$url['export'] = TRUE;
        	
            $data->toolbar->addBtn('Експорт', $url, 'ef_icon=img/16/export.png, row=2');
        }
    }
    
    
    /**
     * Преди подготовката на записите
     */
    public static function on_BeforePrepareListPager($mvc, &$res, $data)
    {
    	if(Request::get('export', 'int')){
    		$nQuery = clone $data->query;
    		$mvc->invoke('AfterPrepareExportQuery', array($nQuery));
    		$recs = $nQuery->fetchAll();
    		$mvc->invoke('AfterPrepareExportRecs', array(&$recs));
    	    
    		$userId = core_Users::getCurrent();
    		core_Cache::remove($mvc->className, "exportRecs{$userId}");
    		core_Cache::set($mvc->className, "exportRecs{$userId}", $recs, 20);
    	
    		$retUrl = toUrl(array($mvc, 'list'), 'local');
    	
    		redirect(array($mvc, 'export', 'ret_url' => $retUrl));
    	}
    }
    
    
    /**
     * Функция връщаща опции с всички драйвери които могат да се прикачват
     * към мениджъра
     *
     * @return array $options - масив с възможни драйвъри
     */
    public static function getExportDrivers(core_Mvc $mvc)
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
            
            if ($selected = Request::get('Selected')) {
                
                $selectedArr = type_Set::toArray($selected);
                foreach ($selectedArr as &$selId) {
                    $selId = (int) $selId;
                    expect(is_int($selId));
                }
                if (!empty($selectedArr)) {
                    $selected = implode(',', $selectedArr);
                    $query = $mvc->getQuery();
                    $query->in("id", $selected);
                    
                    $recs = $query->fetchAll();
                }
                
                core_App::setTimeLimit(count($recs) / 100);

                $cu = core_Users::getCurrent();
                core_Cache::set($mvc->className, "exportRecs{$cu}", $recs, 20);
            }
            
            // Проверка за права
            $mvc->requireRightFor('export');
            
            // Трябва да има инсталиран поне един драйвър за експорт
            $options = self::getExportDrivers($mvc);
            
            // Подготвяме формата
            $form = cls::get('core_Form');
            $form->method = 'GET';
            $form->title = "Експортиране на {$mvc->title}";
            $form->FNC('driver', 'class(interface=bgerp_ExportIntf,allowEmpty,select=title)', "input,caption=Формат,mandatory,silent", array('attr' => array('onchange' => "addCmdRefresh(this.form);this.form.submit()")));
   
            // Ако има опции за избор на драйвър слагаме ги, иначе правим полето readOnly
            if(count($options)){
                $form->setOptions('driver', array('' => '') + $options);
                if(count($options) == 1) {
                    $form->setDefault('driver', key($options));
                }
            } else {
                $form->setReadOnly('driver');
            }
             
            // Инпутваме тихите полета
            $form->input(NULL, 'silent');
            
            // Ако е избран драйвър, той добавя полета към формата
            if($form->rec->driver){
                $Driver = cls::get($form->rec->driver);
                $Driver->mvc = $mvc;
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
                $Driver = cls::get($form->rec->driver, array('mvc1' => $mvc));
                $Driver->mvc = $mvc;
                
                $content = $Driver->export($form->rec);
                
                if(!$content){
                	$tpl = new Redirect(array($mvc, 'list'), '|Няма налични данни за експорт', 'warning');
                	
                	return FALSE;
                }
                
                $name = $Driver->getExportedFileName();
                
                // Записваме файла в системата
                $fh = fileman::absorbStr($content, 'exportCsv', $name);
                	
                // Редирект към лист изгледа,  ако не е зададено друго урл за редирект
                $tpl = new Redirect(array('fileman_Files', 'single', $fh), '|Файлът е експортиран успешно');
                
                return FALSE;
            }
            
            $form->toolbar->addSbBtn('Експорт', 'default', array('class' => 'btn-next'), 'ef_icon = img/16/export.png');
            $form->toolbar->addBtn('Отказ', array($this, 'list'), 'ef_icon = img/16/close-red.png');
         
            $form = $form->renderHtml();
          
            $tpl = $mvc->renderWrapping($form);
            
            return FALSE;
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'export'){
    	
    		// Ако няма налични драйвери за експорт за този мениджър не можем да експортираме
    		if(!self::getExportDrivers($mvc) && !$mvc->hasPlugin('plg_ExportCsv')){
    			
    			$requiredRoles = 'no_one';
    		}
    	}
    }
}