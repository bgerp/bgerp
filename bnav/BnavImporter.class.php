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
    
    
    /*
     * Имплементация на bgerp_ImportIntf
     */
    
    
    /**
     * Инпортиране на csv-файл в cat_Products
     * @param unknown_type $rec
     */
    public function import($hnd, $text)
    {
    	$html = '';
    	expect($hnd || $text);
    	
    	$rows = $this->getRows($hnd, $text);
    	
    	core_Debug::startTimer('import');
    	
    	// Импортиране на групите и мерните еденици
    	$params = static::importParams($rows, $html);
    	
    	// импортиране на продуктите
    	static::importProducts($rows, $params, $html);
    	
    	core_Debug::stopTimer('import');
    	return $html . "Общо време: " . round(core_Debug::$timers['import']->workingTime, 2) ." s<br />";
    }
    
    
    /**
     * Връща подадените редове за импортиране от 
     * csv файл или от текст
     * @param string $hnd - хендлър към качен csv
     * @param text $text - ръчно въведен csv текст
     * Поне едното от $hnd и $text трябва да е зададено
     * @return array $rows - масив с обработените входни данни
     */
    private function getRows($hnd, $text)
    {
    	$rows = array();
    	if($hnd){
    		$i = 0;
    		$path = fileman_Files::fetchByFh($hnd, 'path');
    		if(($handle = fopen($path, "r")) !== FALSE) {
		    	while (($csvRow = fgetcsv($handle, 5000, ",")) !== FALSE) {
		    		if($i != 0){
		    			$rows[] = $csvRow;
		    		}
		    		$i++;
		    	}
    		}
    	} 
    	
    	if($text){
    		$textArr = explode(PHP_EOL, $text);
    		foreach($textArr as $line){
    			$rows[] = explode(',', $line);
    		}
    	}
    	
    	return $rows;
    }
    
    
    /**
     * Филтрира продуктовите групи и създава масив 
     * с неповтарящи се групи
     * @param array $rows - масив получен от csv файл или текст
     * @return array $newGroups - масив с групи
     */
    static function filterImportParams($rows)
    {
    	$newMeasures = $newGroups = array();
	    foreach($rows as $row) {
		    if(!array_key_exists($row[4], $newGroups)){
	    		$rowArr = array('title' => $row[4], 'sysId' => $row[3]);
		    	$newGroups[$row[4]] = $rowArr;
		    }
		    		
		    if(!array_key_exists($row[2], $newMeasures)){
		    	$newMeasures[$row[2]] = $row[2];
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
    static function importParams(&$rows, &$html)
    {
    	$params = static::filterImportParams($rows);
    	
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
    		} else {
    			$updatedMeasures++;
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
    static function importProducts($rows, $params, &$html)
    {
    	$added = $updated = 0;
    	
    	foreach($rows as $row) { 
	    	$rec = new stdClass();
	    	$rec->name = mysql_real_escape_string($row[1]);
	    	$rec->measureId = $params['measures'][$row[2]];
	    	$code = trim(str_replace(array("[", "]"), "", $row[5]));
	    	$rec->code = $code;
	    	$rec->bnavCode = $row[5];
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
	public function getDestinationManager()
    {
    	return cls::get('cat_Products');
    }
}