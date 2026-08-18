<?php


/**
 * Регистър за свойства на счетоводните пера. Записите в него се синхронизират с перото след негова промяна
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acc_Features extends core_Manager
{
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Свойства';
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'acc_WrapperSettings, plg_State2, plg_Search,plg_Created, plg_Sorting';
    
    
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
    public $listFields = 'id, itemId, feature, value, state';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Свойство';
    
    
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
     * По колко пера наведнъж се синхронизират
     */
    const SYNC_CHUNK_SIZE = 500;


    /**
     * Кеш на съществуващите свойства на перата: [itemId] => array(featureTitleId => rec)
     */
    private static $existingFeaturesCache = array();
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('itemId', 'key(mvc=acc_Items, select=titleLink)', 'caption=Перо,mandatory');
        $this->FLD('featureTitleId', 'key(mvc=acc_FeatureTitles, select=title)', 'caption=СвойствоИд,input=none,column=none,mandatory');
        $this->FNC('feature', 'varchar(80, ci)', 'caption=Свойство,mandatory,dependFromFields=featureTitleId');
        $this->FLD('value', 'varchar(80)', 'caption=Стойност,mandatory');
        
        $this->setDbUnique('itemId,featureTitleId');
        $this->setDbIndex('itemId');
    }
    
    
    /**
     * Извлича наименованието на признака от отделен модел
     */
    public static function on_CalcFeature($mvc, $rec)
    {
        if ($rec->featureTitleId) {
            $rec->feature = acc_FeatureTitles::fetchField($rec->featureTitleId, 'title');
        }
    }
    
    
    /**
     * Изпълнява се преди записа
     * Ако липсва - записваме id-то на връзката към титлата
     */
    public static function on_BeforeSave($mvc, &$id, $rec, $fields = null, $mode = null)
    {
        if (!empty($rec->feature) && !isset($rec->featureTitleId)) {
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
        if (!$itemId) {
            
            wp($itemId);
            
            return ;
        }
        
        $self = cls::get(get_called_class());
        
        $itemRec = acc_Items::fetch($itemId);
        
        if (empty($itemRec) || !cls::load($itemRec->classId, true)) {
            
            return;
        }
        
        // Класа трябва да поддържа 'acc_RegisterIntf'
        $ItemClass = cls::get($itemRec->classId);
        if (!cls::haveInterface('acc_RegisterIntf', $ItemClass)) {
            
            return;
        }
        
        core_Lg::push(core_Setup::get('EF_DEFAULT_LANGUAGE', true));
        $itemRec = $ItemClass->getItemRec($itemRec->objectId);
        core_Lg::pop();
        
        // Свойствата на обекта
        $features = $itemRec->features ?? null;
        
        // Ако свойствата не са масив ги пропускаме
        if (!is_array($features)) {
            
            return;
        }
        
        $updated = array();

        // За всяко свойство
        if (countR($features)) {

            // Съществуващите свойства на перото се извличат наведнъж, вместо по едно на свойство
            $existingArr = self::getExistingFeatures($itemId);

            foreach ($features as $feat => $value) {

                // Ако няма стойност пропускаме
                if (empty($value) || empty($feat)) {
                    continue;
                }

                $value = str_replace('&nbsp;', ' ', $value);
                $update = true;

                $featId = acc_FeatureTitles::fetchIdByTitle($feat);

                // Подготвяме записа за добавяне/обновяване
                $rec = (object) array('itemId' => $itemId, 'featureTitleId' => $featId, 'value' => $value, 'state' => 'active');

                // Ако вече има такова свойство, значи го ъпдейтваме
                $exRec = $existingArr[$featId] ?? null;
                if (isset($exRec)) {
                    $rec->id = $exRec->id;

                    // Ако има такъв запис и той е със същата стойност не обновяваме
                    if (($value == $exRec->value) && ($exRec->state == $rec->state)) {
                        $update = false;
                        $self->updatedFeaturesOnItem[$itemId] = true;
                    }
                }

                // Обновяване при нужда
                if ($update) {
                    $mode = !empty($rec->id) ? null : 'REPLACE';
                    $self->save($rec, null, $mode);

                    // Кешът се поддържа актуален, за да е верен при повторна синхронизация в същия хит
                    self::$existingFeaturesCache[$itemId][$featId] = clone $rec;
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
     * @param int   $itemId  - ид на перо
     * @param array $updated - масив с ъпдейтнати пера, ако е празен затваря всички свойства
     */
    private function closeStates($itemId, $updated = array())
    {
        $updatedArr = arr::make($updated, true);

        foreach (self::getExistingFeatures($itemId) as $rec) {
            if ($rec->state == 'closed') {
                continue;
            }

            if (isset($updatedArr[$rec->id])) {
                continue;
            }

            $rec->state = 'closed';
            $this->save($rec, 'state');
            $this->updatedFeaturesOnItem[$itemId] = true;
        }
    }
    
    
    /**
     * Обновяване на свойствата на перото, ако обекта е перо
     */
    public static function syncFeatures($classId, $objectId)
    {
        $itemRec = acc_Items::fetchItem($classId, $objectId);
        $itemId = $itemRec ? $itemRec->id : null;
        
        if ($itemId) {
            acc_Features::syncItem($itemId);
        }
    }
    
    
    /**
     * Връща всички свойства на зададените пера, ако не са зададени пера, връща всички
     *
     * @param array $array - масив с ид-та на пера
     *
     * @return array $options - опции със свойства
     */
    public static function getFeatureOptions($array)
    {
        $options = array();
        
        $query = static::getQuery();
        $query->where("#state = 'active'");
        
        if (countR($array)) {
            $query->in('itemId', $array);
        }
        
        $query->groupBy('featureTitleId');
        
        while ($rec = $query->fetch()) {
            $options[$rec->feature] = $rec->feature;
        }
        
        return $options;
    }
    
    
    /**
     * Връща всички стойности свойства на зададените пера, ако не са зададени пера, връща всички
     *
     * @param int $featureTitleId id на feature
     *
     * @return array $options            опции със стойности
     */
    public static function getFeatureValueOptions($featureTitleId)
    {
        $options = array();
        
        $query = static::getQuery();
        $query->where("#state = 'active'");
        
        $query->where("#featureTitleId = {$featureTitleId}");
        
        $query->groupBy('value');
        
        while ($rec = $query->fetch()) {
            $options[$rec->id] = $rec->value;
        }
        
        return $options;
    }
    
    
    /**
     * Връща масив с перата и свойствата, които имат
     *
     * @param array $itemsArr - списък с пера
     *
     * @return array $res - всички с-ва които имат перата
     */
    public static function getFeaturesByItems($itemsArr = array())
    {
        $res = array();
        
        $query = self::getQuery();
        $query->where("#state = 'active'");
        
        if (countR($itemsArr)) {
            $query->in('itemId', $itemsArr);
        }
        
        while ($rec = $query->fetch()) {
            $res[$rec->itemId][$rec->feature] = $rec->value;
        }
        
        return $res;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if ($mvc->haveRightFor('sync')) {
            $data->toolbar->addBtn('Синхронизиране', array($mvc, 'sync', 'ret_url' => true), null, 'warning=Наистина ли искате да синхронизирате свойствата,ef_icon = img/16/arrow_refresh.png,title=Ресинхронизиране на свойствата на перата');
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
        foreach ($mvc->updatedFeaturesOnItem as $itemId => $true) {
            $iRec = acc_Items::fetch($itemId);
            $lists = arr::combine($lists, keylist::toArray($iRec->lists));
        }
        
        foreach ($lists as $listId) {
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
        //core_Debug::$isLogging = false;
        
        // Свойствата на кои пера са записани в таблицата
        $query = $this->getQuery();
        $query->show('itemId');
        $query->groupBy('itemId');
        $query->where("#state != 'closed'");

        while ($rec = $query->fetch()) {
            if(!empty($rec->itemId)){
                $items[$rec->itemId] = $rec->itemId;
            } else {
                self::delete($rec->id);
            }
        }
        
        $count = countR($items);
        core_App::setTimeLimit($count * 0.7, false,600);

        // Ако има пера
        if ($count) {

            // Обработват се на порции, за да не се подува кеша на записите в паметта
            foreach (array_chunk($items, self::SYNC_CHUNK_SIZE) as $chunkArr) {
                $classIdsArr = self::cacheItemRecs($chunkArr);
                self::cacheExistingFeatures($chunkArr);

                foreach ($chunkArr as $itemId) {

                    // За всяко перо синхронизираме свойствата му
                    try{
                        self::syncItem($itemId);
                    } catch(core_exception_Expect $e){
                        reportException($e);
                    }
                }

                self::flushCachedRecs($classIdsArr);
            }
        }

        //core_Debug::$isLogging = true;
    }


    /**
     * Връща съществуващите в момента свойства на перото, групирани по ид на свойство
     *
     * @param int $itemId - ид на перо
     *
     * @return array      - масив от записи, ключиран по featureTitleId
     *
     * @author Ivelin Dimov <ivelin_pdimov@abv.bg>
     */
    private static function getExistingFeatures($itemId)
    {
        if (!array_key_exists($itemId, self::$existingFeaturesCache)) {
            self::cacheExistingFeatures(array($itemId));
        }

        return self::$existingFeaturesCache[$itemId];
    }


    /**
     * Зарежда с една заявка съществуващите свойства на посочените пера, за да не се прави
     * отделна заявка за всяко свойство на всяко перо
     *
     * @param array $itemsArr - ид-та на пера
     *
     * @return void
     *
     * @author Ivelin Dimov <ivelin_pdimov@abv.bg>
     */
    private static function cacheExistingFeatures($itemsArr)
    {
        if (!countR($itemsArr)) {

            return;
        }

        // Перата без свойства също се маркират, за да не се търсят повторно
        foreach ($itemsArr as $itemId) {
            self::$existingFeaturesCache[$itemId] = array();
        }

        $query = static::getQuery();
        $query->in('itemId', $itemsArr);

        // Само нужните колони - така не се смята и калкулируемото поле 'feature'
        $query->show('id,itemId,featureTitleId,value,state');

        while ($rec = $query->fetch()) {
            self::$existingFeaturesCache[$rec->itemId][$rec->featureTitleId] = $rec;
        }
    }


    /**
     * Зарежда наведнъж перата и изходните им обекти в кеша на моделите, за да не се
     * извличат един по един при синхронизацията
     *
     * @see core_Query::fetchAndCache()
     *
     * @param array $itemsArr - ид-та на пера
     *
     * @return array          - ид-та на класовете, чиито записи са кеширани
     *
     * @author Ivelin Dimov <ivelin_pdimov@abv.bg>
     */
    private static function cacheItemRecs($itemsArr)
    {
        $objectsByClassArr = array();

        // Перата се извличат с една заявка
        $itemQuery = acc_Items::getQuery();
        $itemQuery->in('id', $itemsArr);
        while ($iRec = $itemQuery->fetchAndCache()) {
            if (!empty($iRec->classId) && !empty($iRec->objectId)) {
                $objectsByClassArr[$iRec->classId][$iRec->objectId] = $iRec->objectId;
            }
        }

        // Кешира се и липсата на перо (има свойства на изтрити пера), за да не се търси после по едно
        $Items = cls::get('acc_Items');
        if (!is_array($Items->_cachedRecords)) {
            $Items->_cachedRecords = array();
        }

        foreach ($itemsArr as $itemId) {
            if (!isset($Items->_cachedRecords[$itemId . '|*'])) {
                $Items->_cachedRecords[$itemId . '|*'] = false;
            }
        }

        // За всеки клас изходните обекти също се извличат с една заявка
        foreach ($objectsByClassArr as $classId => $objectIdsArr) {
            if (!cls::load($classId, true)) continue;

            $Class = cls::get($classId);
            $oQuery = $Class->getQuery();
            $oQuery->in('id', $objectIdsArr);
            while ($oQuery->fetchAndCache()) {
                // Самото извличане пълни кеша на модела
            }
        }

        return array_keys($objectsByClassArr);
    }


    /**
     * Изчиства кеша на записите на перата и на посочените класове
     *
     * @param array $classIdsArr - ид-та на класове
     *
     * @return void
     *
     * @author Ivelin Dimov <ivelin_pdimov@abv.bg>
     */
    private static function flushCachedRecs($classIdsArr)
    {
        self::$existingFeaturesCache = array();
        cls::get('acc_Items')->_cachedRecords = array();

        foreach ($classIdsArr as $classId) {
            if (!cls::load($classId, true)) continue;

            cls::get($classId)->_cachedRecords = array();
        }
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
        $this->logWrite('Синхронизиране на счетоводните свойства');
        
        // Редирект към списъка на свойствата
        return new Redirect(array($this, 'list'), '|Всички свойства са синхронизирани успешно');
    }
}
