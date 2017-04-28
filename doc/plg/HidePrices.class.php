<?php



/**
 * Клас 'doc_plg_HidePrices' сквиращ ценови полета, които са посочени в
 * променливата 'priceFields'. Само потребителите с определени права могат
 * да виждат полетата, останалите виждат празни колони.
 * 
 * Плъгина може да се прикачи както към Master така и към Detail.
 * Дава възможност с дефинирането на метод 'hidePriceFields' да се направи
 * скриване специфично за модела.
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_plg_HidePrices extends core_Plugin
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
     * Дали потребителя може да вичжда чувствителната информация
     */
    protected static function canSeePriceFields($rec)
    {
    	// Ако има изброените роли, може да вижда цените
    	if(haveRole('seePrice')) return TRUE;
    	
    	// Ако е контрактор, и е инсталиран пакета за контрактови и имаме тред
    	if(core_Users::haveRole('partner') && core_Packs::isInstalled('colab') && $rec->threadId){
    		
    		// Ако контрактора може да види треда от външната част, то може и да види цялата ценова информация
    		$threadRec = doc_Threads::fetch($rec->threadId);
    		if(colab_Threads::haveRightFor('single', $threadRec)){
    			
    			return TRUE;
    		}
    	}
    	
    	// Ако горните не са изпълнени, потребителя няма право да вижда цените/сумите по документите
    	return FALSE;
    }
    
    
    /**
     * След рендиране на изгледа се скриват ценовите данни от мастъра
     * ако потребителя няма права
     */
    public static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
    	if(self::canSeePriceFields($data->rec)) return;
    	
    	$mvc->hidePriceFields($data);
    }
    
    
    /**
     * Преди подготовка на сингъла
     */
    public static function on_BeforePrepareSingle(core_Mvc $mvc, &$res, $data)
    {
    	if(self::canSeePriceFields($data->rec)) return;
    	
    	// Флаг да не се подготвя общата сума
    	$data->noTotal = TRUE;
    }
    
    
    /**
     * След рендиране на детайлите се скриват ценовите данни от резултатите
     * ако потребителя няма права
     */
    public static function on_AfterPrepareDetail($mvc, $res, &$data)
    {
    	if(self::canSeePriceFields($data->masterData->rec)) return;
    	
    	$mvc->hidePriceFields($data);
    	
    	// Флаг да не се подготвя общата сума
    	$data->noTotal = TRUE;
    }
    
    
    /**
     * Ф-я скриваща всички вербални полета от мастъра или детайла, които
     * са посочени във променливата 'priceFields'
     */
    public static function on_AfterHidePriceFields($mvc, $res, &$data)
    {
    	$priceFields = arr::make($mvc->priceFields);
    	
    	if(count($data->rows)){
    		foreach ($data->rows as $row){
	    		self::unsetPriceFields($row, $priceFields);
    		}
    	}
    	
    	if($data->row){
    		self::unsetPriceFields($data->row, $priceFields);
    	}
    	
        if(!$data) {
            $data = new stdClass();
        }
    }
    
    
    /**
     * Ф-я махаща всички полета от вербален запис, които са маркирани
     */
	private static function unsetPriceFields(&$row, $fields)
    {
    	if(count($fields)){
	    	foreach ($fields as $name){
	    		unset($row->{$name});
	    	}
    	}
    }
}