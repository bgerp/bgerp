<?php



/**
 * Драйвър за импортиране на артикули
 * в cat_Products, изпозва се плъгина bgerp_plg_Import той подава 
 * на драйвъра масив от полета извлечени от csv файл, и масив от
 * полета от модела на кои колони от csv данните съответстват
 * 
 *   - Преди импортирването на артикулите се по импортират при нужда
 *     Групите и Мерните единици и категориите. 
 *     
 *   - При импортирането на артикули имената на групите и мерките се заменят с
 *     техните ид-та от системата.
 * 
 * CSV колона:           
 * –––––––––––––––––––––
 * [Име] 		- име на артикула
 * [Код]    	- код на артикула	       
 * [Мерки]      - вербално име на мярка, ако няма такава се създава нова 
 * [Групи]      - Групи в които да се добави артикула ( разделени с '|' ако са повече от един)
 * 
 * 
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @deprecated
 */
class cat_BaseImporter extends core_Manager {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'bgerp_ImportIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Импорт от артикули";
    
    
    /**
     * Към кои мениджъри да се показва драйвъра
     */
	static $applyOnlyTo = 'cat_Products';
	
	
    /**
     * Кои полета от cat_Products ще получат стойностти от csv-то
     */
    private static $importFields = "name,code,measureId,groups";
    
    
    /*
     * Имплементация на bgerp_ImportIntf
     */
    
    
	/**
     * Инициализиране драйвъра
     */
    function init($params = array())
    {
    	$this->mvc = $params['mvc'];
    }
    
    
    /**
     * Функция, връщаща полетата в които ще се вкарват данни
     * в мениджъра-дестинация
     */
    public function getFields()
    {
    	$fields = array();
    	
    	// Взимат се всички полета на мениджъра, в който ще се импортира
    	$cloneMvc = clone $this->mvc;
    	$Dfields = $cloneMvc->selectFields();
    	$selFields = arr::make(self::$importFields, TRUE);
    	
    	// За всяко поле посочено в, проверява се имали го като поле
    	// ако го има се добавя в масива с неговото наименование
    	foreach($Dfields as $name => $fld){
    		if(isset($selFields[$name])){
    			if($name == 'code'){
    				$fld->mandatory = 'mandatory';
    			}
    			$captionArr = explode('->', $fld->caption);
    			$arr = array('caption' => "Колони->{$captionArr[0]}", 'mandatory' => $fld->mandatory);
    			$fields[$name] = $arr;
    		}
    	}
    	$fields['category'] = array('caption' => 'Папка->Категория', 'mandatory' => 'mandatory', 'type' => 'key(mvc=cat_Categories,select=name,allowEmpty)', 'notColumn' => 'notColumn');
    	$fields['meta'] = array('caption' => 'Папка->Свойства', 'mandatory' => 'mandatory', 'type' => 'set(canSell=Продаваем,canBuy=Купуваем,canStore=Складируем, canConvert=Вложим,fixedAsset=Дълготраен актив,canManifacture=Производим)', 'notColumn' => 'notColumn');
    	
    	return $fields;
    }
    
    
    /**
     * Инпортиране на csv-файл в cat_Products
     * @param array $rows - масив с обработени csv данни,
     * 					 получен от Експерта в bgerp_Import
     * @param array $fields - масив с съответстията на колоните от csv-то и
     * полетата от модела array[{поле_oт_модела}] = {колона_от_csv}
     * @return string $html - съобщение с резултата
     */
    public function import($rows, $fields)
    {
    	$html = '';
    	
    	// Начало на таймера
    	core_Debug::startTimer('import');
    	
    	// Импортиране на групите и мерните единици
    	$params = $this->importParams($rows, $fields, $html);
    	
    	// импортиране на продуктите
    	$this->importProducts($rows, $params, $fields, $html);
    	
    	// Стоп на таймера
    	core_Debug::stopTimer('import');
    	
    	// Връща се резултата от импортирането, с изтеклото време
    	return $html . "Общо време: " . round(core_Debug::$timers['import']->workingTime, 2) ." с<br />";
    }
    
    
    /**
     * Връща мениджъра към който се импортират продуктите
     */
	public function getDestinationManager()
    {
    	return cls::get('cat_Products');
    }
    
    
    /**
     * Филтрира продуктовите групи и създава масив с неповтарящи се групи
     * @param array $rows - масив получен от csv файл или текст
     * @return array $fields - масив със съотвествия на полетата
     */
    private function filterImportParams($rows, $fields)
    {
    	// Намира се на кой индекс стои името на групата, Мярката и категорията
    	$groupIndex = $fields['groups'];
    	$measureIndex = $fields['measureId'];
    	
    	$newMeasures = $newGroups = $newCategories = array();
	    foreach($rows as $row) {
	    	foreach (array('groupIndex' => 'newGroups', 'measureIndex' => 'newMeasures') as $indName => $inArr){
	    		
	    		// Групираме по стойност
	    		if(!in_array($row[${$indName}], ${$inArr})){
	    			${$inArr}[] = $row[${$indName}];
	    		}
	    	}
	    }
	    
	    // Връщат се масив съдържащ уникалните групи, мерни единици и категории
	    $res = array('groups' => $newGroups, 'measures' => $newMeasures);
		
    	return $res;
    }
    
    
    /**
     * Импортиране на групите от csv-то(ако ги няма)
     * @param array $rows - масив получен от csv файл или текст
     * @return array $fields - масив със съответствия
     * @param string $html - Съобщение
     * @return array  масив със съответствия група от системата
     */
    private function importParams(&$rows, $fields, &$html)
    {
    	$params = $this->filterImportParams($rows, $fields);
    	
    	$measures = $groups = array();
    	$addedCategories = $addedMeasures = $addedGroups = $updatedGroups = 0;
    	
    	// Импортиране на мерните единици
    	foreach($params['measures'] as $measure){
    		if(!$id = cat_UoM::fetchBySinonim($measure)->id){
    			$id = cat_UoM::save((object)array('name' => $measure, 'shortName' => $measure));
    			$addedMeasures++;
    		}
    	
    		$measures[$measure] = $id;
    	}
    	
    	// Импортиране на групите
    	foreach($params['groups'] as $gr){
            
            $dev = csv_Lib::getDevider($gr);
 
    		$grArr = explode($dev, $gr);
    		foreach ($grArr as $markerName){
    			if($markerName === '') continue;
    			
    			$name = plg_Search::normalizeText($markerName);
    			$query = cat_Groups::getQuery();
    			plg_Search::applySearch($name, $query);
    			
    			if(!$id = cat_Groups::fetchField(array("#searchKeywords LIKE '%[#1#]%'", $name), 'id')){
    				$id = cat_Groups::save((object)array('name' => $markerName));
    				$addedGroups++;
    			}
    			
    			$groups[$markerName] = $id;
    		}
    	}
    	
    	$html .= "Добавени {$addedGroups} нови групи, Обновени {$updatedGroups} съществуващи маркера<br>";
    	$html .= "Добавени {$addedMeasures} нови мерни единици<br/>";
    	
    	$res = array('groups' => $groups, 'measures' => $measures);
    	
    	return $res;
    }
    
    
    /**
     * Импортиране на артикулите
     * @param array $rows - парсирани редове от csv файл-а
     * @param array $params - Масив с външни ключове на полета
     * @param array $fields - масив със съответствия
     * @param string $html - съобщение
     */
    private function importProducts($rows, $params, $fields, &$html)
    {
    	$added = $updated = 0;
    	
    	foreach($rows as $row) {
    		
	    	$rec = new stdClass();
	    	$rec->name = $row[$fields['name']];
	    	$rec->measureId = $params['measures'][$row[$fields['measureId']]];
	    	$rec->code = $row[$fields['code']];
	    	$rec->folderId = cat_Categories::forceCoverAndFolder($fields['category']);
	    	$rec->meta = $fields['meta'];
	    	
	    	$markerIds = array();
            $dev = csv_Lib::getDevider($row[$fields['groups']]);
	    	$groups = explode($dev, $row[$fields['groups']]);
	    	foreach ($groups as $grName){
	    		if($grName === '') continue;
	    		$index = $params['groups'][$grName];
	    		$markerIds[$index] = $index;
	    	}
	    	
	    	if(count($markerIds)){
	    		$rec->groups = keylist::fromArray($markerIds);
	    	}
	    	
	    	if($rec->id = cat_Products::fetchField(array("#code = '[#1#]'", $code), 'id')){
	    		$updated++;
	    	} else {
	    		$added++;
	    	}
	    	
	    	// Обработка на записа преди импортиране
	    	$this->mvc->invoke('BeforeImportRec', array(&$rec));
	    	cat_Products::save($rec);
    	}
    	
    	$html .= "Добавени {$added} нови артикула, Обновени {$updated} съществуващи артикула<br/>";
    }

    
    /**
     * Драйвъра може да се показва само към инстанция на cat_Products
     */
    public static function isApplicable($className)
    {
        
//     	return $className == self::$applyOnlyTo;
    }
}