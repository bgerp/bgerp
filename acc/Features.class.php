<?php



/**
 * Регистър за свойства на счетоводните пера. Записите в него се синхронизират с перото
 * след негова промяна
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_Features extends core_Manager
{
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Свойства";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'acc_WrapperSettings, plg_State2, plg_Search,
    				 plg_Created, plg_Sorting, plg_ExportCsv';
    
    
    /**
     * Активен таб на менюто
     */
    var $menuPage = 'Счетоводство:Настройки';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,acc';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "id, itemId, feature, value, state";
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Свойство';
    
   
    /**
     * Кой има право да чете?
     */
    var $canRead = 'acc, ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'no_one';
    
    
    /**
	 * Кой може да променя състоянието на валутата
	 */
    var $canChangestate = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой може да го редактира?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Полета за търсене
     */
    var $searchFields = 'itemId, feature, value';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 40;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('itemId', 'key(mvc=acc_Items, select=titleLink)', 'caption=Перо,mandatory');
    	$this->FLD('feature', 'varchar(80, ci)', 'caption=Свойство,mandatory');
    	$this->FLD('value', 'varchar(80)', 'caption=Стойност,mandatory');
    	
    	$this->setDbUnique('itemId,feature');
    }
    
    
	/**
     * Подредба на записите
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->view = 'horizontal';
    	$data->listFilter->showFields = 'search';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        
    	// Сортиране на записите по num
        $data->query->orderBy('id');
    }
    
    
    /**
     * Синхронизира свойствата на перата
     */
    public static function syncItem($itemId)
    {
    	$itemRec = acc_Items::fetch($itemId);
    	if(empty($itemRec)) return;
    	
    	$ItemClass = cls::get($itemRec->classId);
    	
    	// Класа трябва да поддържа 'acc_RegisterIntf'
    	if(!cls::haveInterface('acc_RegisterIntf', $ItemClass)) return;
    	
    	$itemRec = $ItemClass->getItemRec($itemRec->objectId);
    	
    	// Свойствата на обекта
    	$features = $itemRec->features;
    	
    	// Ако свойствата не са масив ги пропускаме
    	if(!is_array($features)) return;
    	
    	$self = cls::get(get_called_class());
    	$updated = array();
    	
    	// За всяко свойство
    	if(count($features)){
    		
	    	$fields = array();
	    	foreach ($features as $feat => $value){
	    		
	    		// Ако няма стойност пропускаме
	    		if(empty($value)) continue;
	    		
	    		$value = str_replace('&nbsp;', ' ', $value);
	    		
	    		// Подготвяме записа за добавяне/обновяване
	    		$rec = (object)array('itemId' => $itemId, 'feature' => $feat, 'value' => $value, 'state' => 'active', 'lastUpdated' => $now);
	    		
	    		// Ако не е уникален, значи ъпдейтваме свойство
	    		if(!$self->isUnique($rec, $fields, $exRec)){
	    			$rec->id = $exRec->id;
	    		}
	    		//@TODO ДА НЕ СЕ ЗАПИСВА АКО СА СЪЩИТЕ
	    		// Запис/обновяване
	    		$self->save($rec);
	    		
	    		// Запомняме всички обновени свойства
	    		$updated[] = $rec->id;
	    	}
    	}
    	
    	// Затваряме състоянието на тези, свойства, които са махнати
    	$self->closeStates($itemId, $updated);
    }
    
    
    /**
     * Всички не ъпдейтнати свойства на перото стават в състояние затворено
     * 
     * @param int $itemId - ид на перо
     * @param array $updated - масив с ъпдейтнати пера, ако е празен затваря всички свойства
     */
    private function closeStates($itemId, $updated = array())
    {
    	$query = $this->getQuery();
    	$query->where("#itemId = {$itemId}");
    	if(count($updated)){
    		$query->notIn('id', $updated);
    	}
    	
    	$query->show('id,state');
    	
    	while($rec = $query->fetch()){
    		$rec->state = 'closed';
    		$this->save($rec);
    	}
    }
    
    
	/**
     * Обновяване на свойствата на перото, ако обекта е перо
     */
    public function syncFeatures($classId, $objectId)
    {
    	$itemId = acc_Items::fetchItem($classId, $objectId)->id;
    	
    	if($itemId){
        	acc_Features::syncItem($itemId);
        }
    }
    
    
    /**
     * Връща всички свойства на зададените пера, ако не са зададени пера, връща всички
     * 
     * @param array $array - масив с ид-та на пера
     * @return array $options - опции със свойства
     */
    public static function getFeatureOptions($array)
    {
    	$options = array();
    	
    	$query = static::getQuery();
    	$query->where("#state = 'active'");
    	if(count($array)){
    		$query->in('itemId', $array);
    	}
    	
    	$query->groupBy("feature");
    	while($rec = $query->fetch()){
    		$options[$rec->feature] = static::getVerbal($rec, 'feature');
    	}
    	
    	return $options;
    }
}