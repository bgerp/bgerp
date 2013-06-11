<?php



/**
 * Плъгин за импорт на данни от Бизнес навигатор
 * 
 *
 * @category  bgerp
 * @package   bnav
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bnav_Plugin extends core_Plugin
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
        
        // Добавяне на необходимите полета
        $mvc->FLD('bnavCode', 'varchar(150)', 'caption=Код БН,remember=info,width=15em,mandatory');
		
        if($mvc->fields['eanCode']){
        	
        	// Полето се слага след баркода на продукта
        	$mvc->fields = array_slice($mvc->fields, 0, 4, true) +
            array('bnavCode' => $mvc->fields['bnavCode']) +
            array_slice($mvc->fields, 4, NULL, true);
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
    	// Прикачане е допустимо само към наследник на cat_Products ...
        if (!$mvc instanceof cat_Products) {
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
    		$url = array($mvc, 'import', 'retUrl' => TRUE);
    		$data->toolbar->addBtn('Импорт', $url, NULL, 'ef_icon=img/16/import16.png');
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
    	if($action == 'test'){
    		$hnd = 'o441Hq';
    		$tpl = static::import($hnd);
    		
    		return FALSE;
    	}
    	
    	
    	if($action == 'test2'){
    		
    		$l = cat_Groups::delete("#id > 11");
    		$li = cat_Products::delete("#id > 23");
    		$lir = cat_UoM::delete("#id > 40");
    		bp($l,$li, $lir);
    		return FALSE;
    	}
    	
    	if ($action == 'import') {
    		
    		// Генерираме форма за основание и "обличаме" я във wrapper-а на $mvc.
        	$form = static::prepareImportForm();
        	$form->input();
        	if($form->isSubmitted()){
        		$res = static::import($form->rec->csvFile);
        		return Redirect(array('cat_Products', 'list'), 'FALSE', $res);
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
    	$form->title = 'Импортиране от бизнес навигатор';
        $form->FNC('csvFile', 'fileman_FileType(bucket=bnav_importCsv)', 'caption=CSV Файл,input,mandatory');
    	return $form;
    }
    
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $rec
     */
    private static function import($hnd)
    {
    	$html = '';
    	core_Debug::startTimer('import');
    	$params = static::importParams($hnd, $html);
    	static::importProducts($hnd, $params, $html);
    	core_Debug::stopTimer('import');
    	
    	return $html . "Общо време: " . core_Debug::$timers['import']->workingTime ." s<br />";
    }
    
    
    /**
     * Филтрира продуктовите групи и създава масив 
     * с неповтарящи се групи
     * @param array $arr - масив получен от csv файл
     * @return array $newGroups - масив с групи
     */
    static function filterImportParams(&$hnd)
    {
    	$i = 0;
    	$newMeasures = $newGroups = array();
    	$path = fileman_Files::fetchByFh($hnd, 'path');
    	
    	if(($handle = fopen($path, "r")) !== FALSE) {
	    	while (($csvRow = fgetcsv($handle, 5000, ",")) !== FALSE) {
	    		if($i != 0){
		    		if(!array_key_exists($csvRow[4], $newGroups)){
	    				$rowArr = array('title' =>$csvRow[4], 'sysId' =>$csvRow[3]);
		    			$newGroups[$csvRow[4]] = $rowArr;
		    		}
		    		
		    		if(!array_key_exists($csvRow[2], $newMeasures)){
		    			$newMeasures[$csvRow[2]] = $csvRow[2];
		    		}
	    		}
	    		$i++;
	    	}
    	}
    	
    	return array('groups' => $newGroups, 'measures' => $newMeasures);
    }
    
    
    /**
     * Импортиране на групите от csv-то( ако ги няма )
     * @param array $arr - масив получен от csv файл-а
     * @param string $html - Съобщение
     * @return array $groups - масив с ид-та на всяка 
     * група от системата
     */
    static function importParams(&$hnd, &$html)
    {
    	$params = static::filterImportParams($hnd);
    	
    	$addedMeasures = $updatedMeasures = $addedGroups = $updatedGroups = 0;
    	$measures = $groups = array();
    	
    	foreach($params['groups'] as $gr){
    		$nRec = new stdClass();
    		$nRec->name = $gr['title'];
    		$nRec->sysId = $gr['sysId'];
    		
    		if($rec = cat_Groups::fetch("#name = '{$gr['title']}'")){
    			$nRec->id = $rec->id;
    			$updatedGroups++;
    		} else {
    			$addedGroups++;
    		}
    			
    		$groups[$gr['title']] = cat_Groups::save($nRec);
    	}
    	
    	foreach($params['measures'] as $measure){
    		$nRec = new stdClass();
    		$nRec->name = $measure;
    		$nRec->shortName = $measure;
    		$uomQuery = cat_UoM::getQuery();
    		$id = cat_UoM::ifExists($measure);
    		if(!$id){
    			$id = cat_UoM::save($nRec);
    			$updatedMeasures++;
    		} else {
    			$addedMeasures++;
    		}
    		
    		$measures[$measure] = $id;
    	}
    	
    	$html .= "Добавени {$addedGroups} нови групи, Обновени {$updatedGroups} съществуващи групи<br>";
    	$html .= "Добавени {$addedMeasures} нови мерни еденици<br/>";
    	
    	return array('groups' => $groups, 'measures' => $measures);
    }
    
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $hnd
     * @param unknown_type $params
     * @param unknown_type $html
     */
    static function importProducts($hnd, $params, &$html)
    {
    	$i = $added = $updated = 0;
    	$path = fileman_Files::fetchByFh($hnd, 'path');
    	
    	if(($handle = fopen($path, "r")) !== FALSE) {
	    	while (($csvRow = fgetcsv($handle, 5000, ",")) !== FALSE) {
	    		if($i != 0){
	    			$rec = new stdClass();
	    			$rec->name = mysql_real_escape_string($csvRow[1]);
	    			$rec->measureId = $params['measures'][$csvRow[2]];
	    			$code = str_replace(array("[", "]"), "", $csvRow[5]);
	    			$rec->code = $code;
	    			$rec->bnavCode = $csvRow[5];
	    			$rec->groups = "|{$params['groups'][$csvRow[4]]}|";
	    			if($rec->id = cat_Products::fetchField(array("#code = '[#1#]'", $code), 'id')){
	    				$updated++;
	    			} else {
	    				$added++;
	    			}
	    			cat_Products::save($rec);
	    		}
	    		$i++;
	    	}
    	}
    	
    	$html .= "Добавени {$added} нови артикула, Обновени {$updated} съществуващи артикула<br/>";
    }
}