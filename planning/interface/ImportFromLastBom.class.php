<?php



/**
 * Помощен клас-имплементация на интерфейса import_DriverIntf
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Импорт на артикули от последната работна рецепта
 */
class planning_interface_ImportFromLastBom extends import_drivers_Proto 
{
    
 
	/**
	 * Към кои класове може да се добавя драйвера
	 *
	 * @var string - изброените класове или празен клас за всички
	 */
    protected $allowedClasses = 'planning_ConsumptionNoteDetails';
    
    
    /**
     * Кой може да избира драйвъра
     */
    protected $canSelectDriver = 'ceo,planning,store';
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'import_DriverIntf';
    
    
    /**
     * Заглавие
     */
    public $title = "Импорт на артикули от последната работна рецепта";
    
    
    /**
     * Добавя специфични полета към формата за импорт на драйвера
     *
     * @param core_Manager $mvc
     * @param core_FieldSet $form
     * @return void
     */
    public function addImportFields($mvc, core_FieldSet $form)
    {
    	$rec = &$form->rec;
    	$rec->detailsDef = array();
    	$masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
    	expect($bomId = self::getLastActiveBom($masterRec));
    	$form->info = tr('По рецепта') . " " . cat_Boms::getHyperlink($bomId, TRUE);
    	$firstDoc = doc_Threads::getFirstDocument($masterRec->threadId);
    	
		// Взимате се материалите за производството на к-то от заданието
		$details = cat_Boms::getBomMaterials($bomId, $firstDoc->fetchField('quantity'), $masterRec->storeId);
		foreach ($details as $dRec){
			$dRec->caption = cat_Products::getTitleById($dRec->productId);
			$dRec->caption = str_replace(',', ' ', $dRec->caption);
			
			// Подготовка на полетата
			$key = "{$dRec->productId}|{$dRec->packagingId}";
			$shortUom = cat_UoM::getShortName($dRec->packagingId);
			$form->FLD($key, "double(Min=0)","input,caption={$dRec->caption}->К-во,unit={$shortUom}");
			$form->setDefault($key, $dRec->quantity / $dRec->quantityInPack);
			$rec->detailsDef[$key] = $dRec;
		}
    }
    
    /**
     * Връща записите, подходящи за импорт в детайла.
     * Съответстващия 'importRecs' метод, трябва да очаква
     * същите данни (@see import_DestinationIntf)
     *
     * @see import_DriverIntf
     * @param array $recs
     * 		o productId        - ид на артикула
     * 		o quantity         - к-во в основна мярка
     * 		o quantityInPack   - к-во в опаковка
     * 		o packagingId      - ид на опаковка
     * 		o batch            - дефолтна партида, ако може
     * 		o notes            - забележки
     * 		o $this->masterKey - ид на мастър ключа
     *
     * @return void
     */
    public function getImportRecs(core_Manager $mvc, $rec)
    {
    	$recs = array();
    	if(!is_array($rec->detailsDef)) return $recs;
    	foreach ($rec->detailsDef as $key => $dRec){
    
    		// Ако има въведено количество записва се
    		if(!empty($rec->{$key})){
    			unset($dRec->id);
    			$dRec->quantity = $rec->{$key} * $dRec->quantityInPack;
    			$dRec->noteId = $rec->{$mvc->masterKey};
    			$dRec->isEdited = TRUE;
    			$recs[] = $dRec;
    		}
    	}
    	
    	return $recs;
    }
    
    
    /**
     * Намира последната работна рецепта
     * 
     * @param stdClass $masterRec
     * @return id|NULL
     */
    private static function getLastActiveBom($masterRec)
    {
    	// Опит за намиране на първата работна рецепта
    	$firstDoc = doc_Threads::getFirstDocument($masterRec->threadId);
    	if(!$firstDoc->isInstanceOf('planning_Jobs')) return FALSE;
    	$productId = $firstDoc->fetchField('productId');
    	$bomId = cat_Products::getLastActiveBom($productId, 'production');
    	$bomId = (!empty($bomId)) ? $bomId : cat_Products::getLastActiveBom($productId, 'sales');
    	 
    	// И по артикула има рецепта
    	$bomId = cat_Products::getLastActiveBom($productId, 'production');
    	$bomId = (!empty($bomId)) ? $bomId : cat_Products::getLastActiveBom($productId, 'sales');
    	
    	// Ако има рецепта, проверява се има ли редове в нея
    	if(!empty($bomId)){
    		$details = cat_Boms::getBomMaterials($bomId, $firstDoc->fetchField('quantity'), $masterRec->storeId);
    		if(count($details)) return $bomId;
    	}
    	
    	return FALSE;
    }
    
    
    /**
     * Може ли драйвера за импорт да бъде избран
     *
     * @param core_Manager $mvc - клас в който ще се импортира
     * @param int|NULL $userId  - ид на потребител
     * @return boolean          - може ли драйвера да бъде избран
     */
    public function canSelectDriver(core_Manager $mvc, $rec, $userId = NULL)
    {
    	$result = parent::canSelectDriver($mvc, $rec, $userId);
    	if($result === TRUE){
    		$masterRec = $mvc->Master->fetchRec($rec);
    		$bomId = self::getLastActiveBom($masterRec);
    		if(empty($bomId)) return FALSE;
    	}
    	 
    	return $result;
    }
}