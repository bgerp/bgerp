<?php



/**
 * Технологичен клас за импортиране на артикули
 * от бизнес навигатор в cat_Products
 * За целта е нужно да се подаде csv файл или стойнсоти разделени
 * с запетая във вида:
 * 
 * [Код], [Название], [Мерна единица], [Номенклатура: код], [Номенклатура: име], [Article String Code]
 * 
 * Имената в [Номенклатура: име]  представляват групите към които
 * артикула ще участва. Ако няма група с това име в системата се
 * създава нова и се връща ид-то и
 * 
 * [Мерна еденица]: ако няма мерна еденица в системата с такова име
 * се създава нова и се връща ид-то и
 * 
 * Еквивалентите на тези полета в cat_Products са;
 * Название = name
 * [Мерна еденица] = [measureId] (ид на подадената еденица след импортирането)
 * [Article String Code] = [code]  след като се премахнат  "[" и "]"
 * [Номенклатура: име] = [groups]  (ид на групата след импортирането)
 * [Article String Code] = [bnavCode]
 *
 * @category  bgerp
 * @package   bnav
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bnav_BnavImporter extends core_Manager {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'bgerp_ImportIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Импорт от бизнес навигатор";
    
    
    /**
     * Кои полета ще бъдат импортирани
     */
    private static $importFields = "name,measureId,groups,bnavCode";
    
    
    /*
     * Имплементация на bgerp_ImportIntf
     */
    
    
    /**
     * Функция връщаща полетата в които ще се вкарват данни в мениджъра-дестинация
     */
    public function getFields()
    {
    	$fields = array();
    	$Dmanager = $this->getDestinationManager();
    	$Dfields = $Dmanager->selectFields();
    	$selFields = arr::make(static::$importFields, TRUE);
    	foreach($Dfields as $name => $fld){
    		if(isset($selFields[$name])){
    			$fields[$name] = $fld->caption;
    		}
    	}
    	
    	return $fields;
    }
    
    
    /**
     * Инпортиране на csv-файл в cat_Products
     * @param unknown_type $rec
     */
    public function import($rows, $fields)
    {
    	$html = '';
    	
    	core_Debug::startTimer('import');
    	
    	// Импортиране на групите и мерните еденици
    	$params = static::importParams($rows, $fields, $html);
    	
    	// импортиране на продуктите
    	static::importProducts($rows, $params, $fields, $html);
    	
    	core_Debug::stopTimer('import');
    	return $html . "Общо време: " . round(core_Debug::$timers['import']->workingTime, 2) ." s<br />";
    }
    
    
    /**
     * Филтрира продуктовите групи и създава масив 
     * с неповтарящи се групи
     * @param array $rows - масив получен от csv файл или текст
     * @return array $newGroups - масив с групи
     */
    static function filterImportParams($rows, $fields)
    {
    	$newMeasures = $newGroups = array();
	    foreach($rows as $row) {
	    	if($row[0] !== NULL){
		    	$groupIndex = $fields['groups'];
		    	$measureIndex = $fields['measureId'];
			    if(!array_key_exists($row[$groupIndex], $newGroups)){
		    		$rowArr = array('title' => $row[$groupIndex], 'sysId' => $row[3]);
			    	$newGroups[$row[$groupIndex]] = $rowArr;
			    }
			    		
			    if(!array_key_exists($row[$measureIndex], $newMeasures)){
			    	$newMeasures[$row[$measureIndex]] = $row[$measureIndex];
			    }
	    	}
	    }
    	
    	return array('groups' => $newGroups, 'measures' => $newMeasures);
    }
    
    
    /**
     * Импортиране на групите от csv-то( ако ги няма )
     * @param array $rows - масив получен от csv файл или текст
     * @param string $html - Съобщение
     * @return array $groups - масив с ид-та на всяка 
     * група от системата
     */
    static function importParams(&$rows, $fields, &$html)
    {
    	$params = static::filterImportParams($rows, $fields);
    	
    	$addedMeasures = $updatedMeasures = $addedGroups = $updatedGroups = 0;
    	$measures = $groups = array();
    	
    	// Импортиране на групите
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
    	
    	
    	// Импортиране на мерните еденици
    	foreach($params['measures'] as $measure){
    		$nRec = new stdClass();
    		$nRec->name = $measure;
    		$nRec->shortName = $measure;
    		$uomQuery = cat_UoM::getQuery();
    		$id = cat_UoM::ifExists($measure);
    		if(!$id){
    			$id = cat_UoM::save($nRec);
    			$addedMeasures++;
    		}
    		
    		$measures[$measure] = $id;
    	}
    	
    	$html .= "Добавени {$addedGroups} нови групи, Обновени {$updatedGroups} съществуващи групи<br>";
    	$html .= "Добавени {$addedMeasures} нови мерни еденици<br/>";
    	
    	return array('groups' => $groups, 'measures' => $measures);
    }
    
    
    /**
     * Импортиране на артикулите
     * @param array $rows - хендлър на csv файл-а
     * @param array $params - Масив с външни ключове на полета
     * @param string $html - съобщение
     */
    static function importProducts($rows, $params, $fields, &$html)
    {
    	$added = $updated = 0;
    	
    	foreach($rows as $row) { 
	    	$rec = new stdClass();
	    	$rec->name = mysql_real_escape_string($fields['name']);
	    	$rec->measureId = $params['measures'][$fields['measureId']];
	    	$code = trim(str_replace(array("[", "]"), "", $fields['bnavCode']));
	    	$rec->code = $code;
	    	$rec->bnavCode = $fields['bnavCode'];
	    	$rec->groups = "|{$params['groups'][$row[4]]}|";
	    	if($rec->id = cat_Products::fetchField(array("#code = '[#1#]'", $code), 'id')){
	    		$updated++;
	    	} else {
	    		cat_Products::fetchField(array("#code = '[#1#]'", $code), 'id');
	    		$added++;
	    	}
	    	
	    	cat_Products::save($rec);
    	}
    	
    	$html .= "Добавени {$added} нови артикула, Обновени {$updated} съществуващи артикула<br/>";
    }
    
    
    /**
     * Връща мениджъра към който се импортират продуктите
     */
	public function getDestinationManager($name = FALSE)
    {
    	if($name){
    		return 'cat_Products';
    	} 
    	
    	return cls::get('cat_Products');
    }
}