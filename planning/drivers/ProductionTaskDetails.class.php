<?php


/**
 * Клас 'planning_drivers_ProductionTaskDetails'
 *
 * Детайли на драйверите за за задачи за производство
 *
 * @category  bgerp
 * @package   tasks
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_drivers_ProductionTaskDetails extends tasks_TaskDetails
{
    
	
	/**
     * Заглавие
     */
    public $title = 'Детайли на задачите за производство';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Прогрес';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'taskId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_RowNumbering, plg_AlignDecimals2, plg_SaveAndNew, plg_Rejected, plg_Modified, plg_Created, plg_LastUsedKeys, plg_Sorting';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    var $lastUsedKeys = 'employees,fixedAsset';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'RowNumb=Пулт,operation,code,quantity,weight,employees,fixedAsset,modified=Модифицирано';
    

    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Кои колони да скриваме ако янма данни в тях
     */
    public $hideListFieldsIfEmpty = 'code,weight,employees,fixedAsset';
    
    
    /**
     * Активен таб на менюто
     */
    public $currentTab = 'Задачи';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD("taskId", 'key(mvc=planning_Tasks)', 'input=hidden,silent,mandatory,caption=Задача');
    	$this->FLD('code', 'bigint', 'caption=Код,input=none');
    	$this->FLD('operation', 'varchar', 'silent,caption=Операция,input=none,removeAndRefreshForm=code');
    	$this->FLD('quantity', 'double', 'caption=К-во,mandatory');
    	$this->FLD('weight', 'cat_type_Weight', 'caption=Тегло');
    	$this->FLD('employees', 'keylist(mvc=planning_HumanResources,select=code,makeLinks)', 'caption=Работници');
    	$this->FLD('fixedAsset', 'key(mvc=planning_AssetResources,select=code)', 'caption=Машина,input=none');
    	 
    	$this->setDbUnique('code');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$data->form->rec;
    	
    	// Добавяме последните данни за дефолтни
    	$query = $mvc->getQuery();
    	$query->where("#taskId = {$rec->taskId}");
    	$query->orderBy('id', 'DESC');
    	 
    	// Задаваме последно въведените данни
    	if($lastRec = $query->fetch()){
    		$form->setDefault('operation', $lastRec->operation);
    		$form->setDefault('employees', $lastRec->employees);
    		$form->setDefault('fixedAsset', $lastRec->fixedAsset);
    	}
    	
    	// Ако в мастъра са посочени машини, задаваме ги като опции
    	if(isset($data->masterRec->fixedAssets)){
    		$keylist = $data->masterRec->fixedAssets;
    		$arr = keylist::toArray($keylist);
    			
    		foreach ($arr as $key => &$value){
    			$value = planning_AssetResources::getVerbal($key, 'code');
    		}
    		$form->setOptions('fixedAsset', array('' => '') + $arr);
    		$form->setField('fixedAsset', 'input');
    	}
    }
    

    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	 
    	if($form->isSubmitted()){
    		
    		// Ако няма код и операцията е 'произвеждане' задаваме дефолтния код
    		if($rec->operation == 'production'){
    			if(empty($rec->code)){
    				$rec->code = $mvc->getDefaultCode();
    			}
    		}
    	}
    }


    /**
     * Връща следващия най-голям свободен код
     *
     * @return int $code - код
     */
    private function getDefaultCode()
    {
    	// Намираме последния въведен код
    	$query = self::getQuery();
    	$query->XPR('maxCode', 'int', 'MAX(#code)');
    	$code = $query->fetch()->maxCode;
    	 
    	// Инкрементираме кода, докато достигнем свободен код
    	$code++;
    	while(self::fetch("#code = '{$code}'")){
    		$code++;
    	}
    	 
    	return $code;
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	if(isset($rec->fixedAsset)){
    		$singleUrl = planning_AssetResources::getSingleUrlArray($rec->fixedAsset);
    		$row->fixedAsset = ht::createLink($row->fixedAsset, $singleUrl);
    	}
    	 
    	$row->modified = "<div class='centered'>" . $mvc->getFieldType('modifiedOn')->toVerbal($rec->modifiedOn);
    	$row->modified .= " " . tr('от') . " " . $row->modifiedBy . "</div>";
    	 
    	if(isset($rec->code)){
    		$row->code = "<b>{$row->code}</b>";
    	}
    	 
    	$row->ROW_ATTR['class'] .= " state-{$rec->state}";
    	if($rec->state == 'rejected'){
    		$row->ROW_ATTR['title'] = tr('Оттеглено от') . " " . core_Users::getVerbal($rec->modifiedBy, 'nick');
    	}
    	
    	if(isset($row->code)){
    		$row->code = "<div class='centered'>{$row->code}</div>";
    	}
    }
}