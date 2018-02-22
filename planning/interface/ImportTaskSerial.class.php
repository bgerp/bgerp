<?php



/**
 * Помощен клас-имплементация на интерфейса import_DriverIntf
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Импорт по серийни номера от задачи
 */
class planning_interface_ImportTaskSerial extends planning_interface_ImportDriver 
{
    
    
    /**
     * Заглавие
     */
    public $title = "Импорт по серийни номера от задачи";
    
    
    /**
     * Добавя специфични полета към формата за импорт на драйвера
     *
     * @param core_Manager $mvc
     * @param core_FieldSet $form
     * @return void
     */
    public function addImportFields($mvc, core_FieldSet $form)
    {
    	$form->FLD('text', 'richtext(rows=4)', 'caption=Серийни номера от задачи->Номера,mandatory');
    	$form->rec->serials = array();
    }
    
    
    /**
     * Проверява събмитнатата форма
     *
     * @param core_Manager $mvc
     * @param core_FieldSet $form
     * @return void
     */
    public function checkImportForm($mvc, core_FieldSet $form)
    {
    	$error = array();
    	$rec = $form->rec;
    	$serials = preg_replace('/[\s|\,|\;]+/', "\n", $rec->text);
    	$serials = explode("\n", trim($rec->text));
    	
    	$validSerials = array();
    	foreach ($serials as $serial){
    		$serialTrimmed = trim(ltrim($serial, '0'));
    		if(empty($serialTrimmed)) continue;
    		$serialRec = planning_TaskSerials::fetch(array("#serial = '[#1#]'", $serialTrimmed));
    		if(empty($serialRec)){
    			$error[] = "|*<b>{$serial}</b> |несъществуващ сериен номер|*";
    		} else {
    			$rec->serials[] = $serialRec;
    		}
    		
    		if(array_key_exists($serialTrimmed, $validSerials)){
    			$error[] = "|*<b>{$serial}</b> |серийният номер се повтаря|*";
    		} else {
    			$validSerials[$serialTrimmed] = $serial;
    		}
    	}
    	
    	if(count($error)){
    		$error = implode('</br>', $error);
    		$form->setError('text', $error);
    	} else {
    		$rec->importRecs = $this->getImportRecs($mvc, $rec);
    	}
    }
    
    
    /**
     * Връща записите, подходящи за импорт в детайла
     *
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
    private function getImportRecs(core_Manager $mvc, $rec)
    {
    	$recs = array();
    	if(!is_array($rec->serials)) return $recs;
    	$serials = arr::extractSubArray($rec->serials, 'productId,quantityInPack,packagingId,serial');
    	
    	foreach ($serials as $key => $sRec){
    		$key = "{$sRec->productId}|{$sRec->packagingId}|{$sRec->quantityInPack}";
    		if(!array_key_exists($key, $recs)){
    			$sRec->{$mvc->masterKey} = $rec->{$mvc->masterKey};
    			$sRec->quantity = 0;
    			$sRec->isEdited = TRUE;
    			$recs[$key] = $sRec;
    		}
    		
    		$recs[$key]->quantity += $sRec->quantityInPack;
    	}
    	
    	return $recs;
    }
    
    
    /**
     * Може ли драйвера за импорт да бъде избран
     *
     * @param   core_Manager    $mvc        - клас в който ще се импортира
     * @param   int|NULL        $masterId   - ако импортираме в детайл, id на записа на мастъра му
     * @param   int|NULL        $userId     - ид на потребител
     *
     * @return boolean          - може ли драйвера да бъде избран
     */
    public function canSelectDriver(core_Manager $mvc, $masterId = NULL, $userId = NULL)
    {
    	//$res = $mvc instanceof planning_ReturnNoteDetails;
    	$res = FALSE;
    	
    	return $res;
    }
}