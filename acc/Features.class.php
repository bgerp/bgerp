<?php



/**
 * Регистър за свойства на счетоводните пера. Записите в него се синхронизират с перото след негова промяна
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
    public $title = "Свойства";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'acc_WrapperSettings, plg_State2, plg_Search,
                     plg_Created, plg_Sorting';
    
    
    /**
     * Активен таб на менюто
     */
    public $menuPage = 'Счетоводство:Настройки';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,acc';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "id, itemId, feature, value, state";
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Свойство';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'acc, ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой може да променя състоянието на валутата
     */
    public $canChangestate = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да го редактира?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой може да го ресинхронизира?
     */
    public $canSync = 'ceo,accMaster';
    
    
    /**
     * Полета за търсене
     */
    public $searchFields = 'itemId, feature, value';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 40;
    

    /**
     * Масив в който се записват перата, които имат променени свойства по времето на хита
     */
    private $updatedFeaturesOnItem = array();


    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('itemId', 'key(mvc=acc_Items, select=titleLink)', 'caption=Перо,mandatory');
        $this->FLD('featureTitleId', 'key(mvc=acc_FeatureTitles, select=title)', 'caption=СвойствоИд,input=none,column=none,mandatory');

        $this->FNC('feature', 'varchar(80, ci)', 'caption=Свойство,mandatory');
        $this->FLD('value', 'varchar(80)', 'caption=Стойност,mandatory');
        
        $this->setDbUnique('itemId,featureTitleId');
        $this->setDbIndex('itemId');
    }


    /**
     * Извлича наименованието на признака от отделен модел
     */
    static function on_CalcFeature($mvc, $rec)
    {
        if($rec->featureTitleId) {
            $rec->feature = acc_FeatureTitles::getTitleById($rec->featureTitleId);
        }
    }


    /**
     * Изпълнява се преди записа
     * Ако липсва - записваме id-то на връзката към титлата
     */
    static function on_BeforeSave($mvc, &$id, $rec, $fields = NULL, $mode = NULL)
    {
        if($rec->feature && !isset($rec->featureTitleId)) {
            $rec->featureTitleId = acc_FeatureTitles::fetchIdByTitle($rec->feature);
        }
    }
    
    
    /**
     * Подредба на записите
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
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
        $self = cls::get(get_called_class());

        $itemRec = acc_Items::fetch($itemId);
        
        if(empty($itemRec)) return;
        
        $ItemClass = cls::get($itemRec->classId);
        
        // Класа трябва да поддържа 'acc_RegisterIntf'
        if(!cls::haveInterface('acc_RegisterIntf', $ItemClass)) return;
        
        core_Lg::push(EF_DEFAULT_LANGUAGE);
        $itemRec = $ItemClass->getItemRec($itemRec->objectId);
        core_Lg::pop();
        
        // Свойствата на обекта
        $features = $itemRec->features;
        
        // Ако свойствата не са масив ги пропускаме
        if(!is_array($features)) return;
        
        $updated = array();
        $now = dt::now();
        
        // За всяко свойство
        if(count($features)){
            
            $fields = array();
            
            foreach ($features as $feat => $value){
                
                // Ако няма стойност пропускаме
                if(empty($value) || empty($feat)) continue;
                
                $value = str_replace('&nbsp;', ' ', $value);
                $update = TRUE;
                
                $featId = acc_FeatureTitles::fetchIdByTitle($feat);

                // Подготвяме записа за добавяне/обновяване
                $rec = (object)array('itemId' => $itemId, 'featureTitleId' => $featId, 'value' => $value, 'state' => 'active', 'lastUpdated' => $now);
              
                // Ако не е уникален, значи ъпдейтваме свойство
                if(!$self->isUnique($rec, $fields, $exRec)){
                    $rec->id = $exRec->id;
                    
                    // Ако има такъв запис и той е със същата стойност не обновяваме
                    if($value == $exRec->value){
                    	$update = FALSE;
                        $self->updatedFeaturesOnItem[$itemId] = TRUE;
                    }
                }
                
                // Обновяване при нужда
                if($update){
                	$self->save($rec, NULL, 'REPLACE');
                }
                
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
        
        while($rec = $query->fetch("#state != 'closed'")){
            $rec->state = 'closed';
            $this->save($rec);
            $this->updatedFeaturesOnItem[$itemId] = TRUE;
        }
    }
    
    
    /**
     * Обновяване на свойствата на перото, ако обекта е перо
     */
    public static function syncFeatures($classId, $objectId)
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
        
        $query->groupBy("featureTitleId");
        
        while($rec = $query->fetch()){
            $options[$rec->feature] = $rec->feature;
        }
        
        return $options;
    }
    

    /**
     * Връща всички стойности свойства на зададените пера, ако не са зададени пера, връща всички
     *
     * @param  int      $featureTitleId     id на feature
     * @return array    $options            опции със стойности
     */
    public static function getFeatureValueOptions($featureTitleId)
    {
        $options = array();
        
        $query = static::getQuery();
        $query->where("#state = 'active'");
        
        $query->where("#featureTitleId = {$featureTitleId}");
          
        $query->groupBy("value");
        
        while($rec = $query->fetch()){
            $options[$rec->id] = $rec->value;
        }
        
        return $options;
    }

    
    /**
     * Връща масив с перата и свойствата, които имат
     *
     * @param array $itemsArr - списък с пера
     * @return array $res - всички с-ва които имат перата
     */
    public static function getFeaturesByItems($itemsArr = array())
    {
        $res = array();
        
        $query = self::getQuery();
        $query->where("#state = 'active'");
        
        if(count($itemsArr)){
            $query->in('itemId', $itemsArr);
        }
        
        while($rec = $query->fetch()){
            $res[$rec->itemId][$rec->feature] = $rec->value;
        }
        
        return $res;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if($mvc->haveRightFor('sync')){
    		$data->toolbar->addBtn('Синхронизиране', array($mvc, 'sync', 'ret_url' => TRUE), NULL, 'warning=Наистина ли искате да ресинхронизирате свойствата,ef_icon = img/16/arrow_refresh.png,title=Ресинхронизиране на свойствата на перата');
    	}
    }
    
    
    /**
     * Обновява списъците със свойства на номенклатурите от които е имало засегнати пера
     *
     * @param acc_Items $mvc
     */
    public static function on_Shutdown($mvc)
    {
        $lists = array();
        foreach($mvc->updatedFeaturesOnItem as $itemId => $true) {
            $iRec = acc_Items::fetch($itemId);
            $lists = arr::combine($lists, keylist::toArray($iRec->lists));
        }

        foreach($lists as $listId) {
            acc_Lists::updateFeatureList($listId);
        }
    }

    
    /**
     * Синхронизиране на таблицата със свойствата по крон
     */
    public function cron_SyncFeatures()
    {
    	// Синхронизира всички свойства на перата
    	$this->syncAllItems();
    }
    
    
    /**
     * Синхронизира всички пера
     */
    private function syncAllItems()
    {
    	$items = array();
    	 
    	core_Debug::$isLogging = FALSE;
    	
    	// Свойствата на кои пера са записани в таблицата
    	$query = $this->getQuery();
    	$query->show("itemId");
    	$query->groupBy('itemId');
    	while($rec = $query->fetch()){
    		$items[$rec->itemId] = $rec->itemId;
    	}
    	
    	// Ако има пера
    	if(count($items)){
    		foreach ($items as $itemId){
    			
    			// За всяко перо синхронизираме свойствата му
    			self::syncItem($itemId);
    		}
    	}
    	
    	core_Debug::$isLogging = TRUE;
    }
    
    
    /**
     * Синхронизиране на таблицата със свойствата
     */
    public function act_Sync()
    {
    	$this->requireRightFor('sync');
    	
    	// Синхронизира всички свойства на перата
    	$this->syncAllItems();
    	
    	// Записваме, че потребителя е разглеждал този списък
    	$this->logWrite("Синхронизиране на счетоводните свойства");
    	
    	// Редирект към списъка на свойствата
    	return new Redirect(array($this, 'list'), 'Всички свойства са синхронизирани успешно');
    }
}
