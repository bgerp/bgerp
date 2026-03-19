<?php

/**
 * Клас 'change_History - История на версията на обектите
 *
 * @category  bgerp
 * @package   change
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class change_History extends core_Manager
{


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,objectId=Обект,validFrom,validTo,data,createdOn,createdBy';


    /**
     * Заглавие
     */
    public $title = 'История на обекти';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'debug';


    /**
     * Кой може да го листва?
     */
    public $canList = 'admin';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, plg_Rejected, plg_Select, doc_Wrapper, plg_Sorting';


    /**
     * Име на перманентните данни
     */
    const PERMANENT_SAVE_NAME = 'changeHistory';


    /**
     * константа определяща ид-то на текущаъа версия от историята (ако не идва от нея)
     */
    const CURRENT_VERSION_ID = '_ID_';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('classId', 'class', 'caption=Документ->Клас,silent');
        $this->FLD('objectId', 'int', 'caption=Документ->Обект,tdClass=leftCol,silent');
        $this->FLD('validFrom', 'datetime(format=d.m.y H:i:s)', 'caption=Дата->От');
        $this->FLD('validTo', 'datetime(format=d.m.y H:i:s)', 'caption=Дата->До');
        $this->FLD('data', 'blob(serialize, compress)', 'caption=Версия,tdClass=td-clamp');

        // Истинска дата/автор на СЪЗДАВАНЕ на версията
        $this->FLD('versionCreatedOn', 'datetime(format=d.m.y H:i:s)', 'caption=Версия->Създадена,silent');
        $this->FLD('versionCreatedBy', 'user', 'caption=Версия->Създадена от,silent');

        $this->FLD('state', 'enum(active=Активен,rejected=Оттеглен)', 'caption=Състояние,notNull=active');

        $this->setDbIndex('classId,objectId');
        $this->setDbIndex('classId,objectId,validFrom');
        $this->setDbIndex('validFrom');
        $this->setDbIndex('validTo');
    }
    
    
    /**
     * Backfill на metadata на версиите за стари history записи
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        $createdOnColName = str::phpToMysqlName('createdOn');
        $createdByColName = str::phpToMysqlName('createdBy');
        $versionCreatedOnColName = str::phpToMysqlName('versionCreatedOn');
        $versionCreatedByColName = str::phpToMysqlName('versionCreatedBy');

        $query = "UPDATE {$mvc->dbTableName} SET {$versionCreatedOnColName} = {$createdOnColName} WHERE {$versionCreatedOnColName} IS NULL";
        $mvc->db->query($query);

        $query = "UPDATE {$mvc->dbTableName} SET {$versionCreatedByColName} = {$createdByColName} WHERE {$versionCreatedByColName} IS NULL";
        $mvc->db->query($query);
    }


    /**
     * Коя е текущата версия от мнодежството:
     * версии в историята + съществуващия запис + новия запис
     *
     * @param mixed $classId
     * @param int $objectId
     * @param stdClass $oldRec
     * @param stdClass $newRec
     * @param array $saveFields
     * @return mixed
     */
    public static function getCurrentRec($classId, $objectId, $oldRec, $newRec = null, &$saveFields = array())
    {
        $Class = cls::get($classId);
        $classId = $Class->getClassId();
        $loggableFields = arr::make($Class->loggableFields, true);
        
        $metaFields = array(
            'versionCreatedOn' => 'versionCreatedOn',
            'versionCreatedBy' => 'versionCreatedBy',
        );

        // Оттегляне на съществуващите записи с това ид
        if(isset($newRec)){
            $eQuery = static::getQuery();
            $eQuery->where("#classId = '{$classId}' AND #objectId = '{$objectId}' AND #state = 'active' AND #validFrom = '{$newRec->validFrom}'");
            while($eRec = $eQuery->fetch()){
                $eRec->state = 'rejected';
                static::save($eRec, 'state');
            }
        }

        // Извличаните на съществуващите версии
        $arr = $validFromByNow = $validToMap = array();
        $query = static::getQuery();
        $query->where("#classId = '{$classId}' AND #objectId = '{$objectId}'");
        while($rec = $query->fetch()){
            $arr[$rec->id] = $rec;
            $validFromByNow[$rec->validFrom] = $rec;
        }

        // Към тях се добавят текущия и новия запис
        foreach (array('m' => $oldRec, 'n' => $newRec) as $k => $r1){
            if(!isset($r1)) continue;

            $data = new stdClass();
            foreach ($loggableFields as $logFld){
                $data->{$logFld} = $r1->{$logFld};
            }

            $arr[$k] = (object)array(
                'id' => $k,
                'data' => $data,
                'classId' => $classId,
                'objectId' => $objectId,
                'validFrom' => $r1->validFrom,
                'state' => 'active',
                'versionCreatedOn' => !empty($r1->versionCreatedOn) ? $r1->versionCreatedOn : $r1->createdOn,
                'versionCreatedBy' => !empty($r1->versionCreatedBy) ? $r1->versionCreatedBy : $r1->createdBy,
            );
        }

        // Ако текущия запис вече идва от историята - него
        if(array_key_exists($oldRec->validFrom, $validFromByNow)){
            unset($arr['m']);
        }

        /* Правим един масив $validFrom => $id, в който са включени всички записи от историята, текущия от Мениджъра с
         * ИД = 'M' и новият, който е подготвен за запис с ИД = 'N'. Подреждаме масива по validFrom .
         * Ако имаме запис от Историята, който е със същото време validFrom като записа от мениджъра,
         * махаме от масива записа от мениджъра (това е заради възможността текущия запис да е сложен по-рано
         * в историята с начало в бъдещето).
         *
         * 1. Попълване на масив id => validFrom за всички записи от Мениджъра, Историята и евентуално Нов запис
         * 2. Подреждане на масива и определяне на текущия запис.
         * 3. Записване в Историята на всички записи, които не са записани (нямат число за id) и не са текущи.
         * 4. Записване на текущия запис в мениджъра.
         * 5. Изчисляване на всички validTo и записването им.
         */
        arr::sortObjects($arr, 'validFrom', 'ASC');
        $loggableFields['validTo'] = 'validTo';
        
        $saveMetaFields = $metaFields;

        $now = dt::now();
        $currentRec = null;
        foreach ($arr as &$r){
            if($now >= $r->validFrom){
                $currentRec = $r;
            }
        }

        if($currentRec->id == 'n'){

            // Ако текущия запис е новия, записваме съществуващия в историята
            if(isset($arr['m'])){
                unset($arr['m']->id);
                $oldId = static::save($arr['m']);
                $arr[$oldId] = $arr['m'];
            }
        } elseif($currentRec->id == 'm' && isset($arr['n'])){

            // Ако съществуващия запис е текущия и има нов запис
            unset($arr['n']->id);
            $newId = static::save($arr['n']);
            $arr[$newId] = $arr['n'];
            $saveFields = $loggableFields + $saveMetaFields;
            unset($arr['n']);
        } else {

            // Ако текущия запис е някой от историята
            // До тук се стига ако след ЧЕТЕНЕ текущия запис не е актуален, и има бъдещ такъв в модела
            // Записване на съществуващия запис в историята (ако има такъв)
            if(isset($arr['m'])){
                unset($arr['m']->id);
                $oldId = static::save($arr['m']);
                $arr[$oldId] = $arr['m'];
                unset($arr['m']);
            }

            // Записване на новия запис в историята (ако има такъв)
            if(isset($arr['n'])){
                unset($arr['n']->id);
                $newId = static::save($arr['n']);
                $arr[$newId] = $arr['n'];
                unset($arr['n']);
            }

            $loggableFields['validFrom'] = 'validFrom';
            $saveFields = $loggableFields + $saveMetaFields;
        }

        // Преизчисляване на валидността на множеството от записи
        $count = 0;
        arr::sortObjects($arr, 'validFrom', 'ASC');
        $arrVal = array_values($arr);

        $saveArr = array();
        foreach ($arr as $k => &$r){
            if(isset($arrVal[$count + 1])){
                $r->validTo = $arrVal[$count + 1]->validFrom;
            } else {
                $r->validTo = null;
            }
            $validToMap[$r->validFrom] = $r->validTo;
            $count++;

            if(is_numeric($k)){
                $saveArr[$k] = $r;
            }
        }

        cls::get(get_called_class())->saveArray($saveArr, 'id,validTo');

        // Във върнатите данни добавяме и валидността, да се обновят данните в мениджъра
        $currentRec->data->validFrom = $currentRec->validFrom;
        $currentRec->data->validTo = $validToMap[$currentRec->validFrom];
        $currentRec->data->versionCreatedOn = !empty($currentRec->versionCreatedOn) ? $currentRec->versionCreatedOn : $currentRec->createdOn;
        $currentRec->data->versionCreatedBy = !empty($currentRec->versionCreatedBy) ? $currentRec->versionCreatedBy : $currentRec->createdBy;

        return $currentRec->data;
    }


    /**
     * Подготовка на филтър формата
     *
     * @param core_Mvc $mvc
     * @param StdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->showFields = 'classId,objectId,validFrom';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input(null, 'silent');
        $data->listFilter->input();
        $data->query->orderBy('validFrom', 'DESC');

        if($filter = $data->listFilter->rec){
            if(!empty($filter->classId)){
                $data->query->where("#classId = {$filter->classId}");
            }
            if(!empty($filter->objectId)){
                $data->query->where("#objectId = {$filter->objectId}");
            }
            if(!empty($filter->date)){
                $data->query->where("#date >= {$filter->date}");
            }
        }
    }


    /**
     * Обработка по вербалното представяне на данните
     */
    protected static function on_AfterRecToVerbal(core_Mvc $mvc, &$row, $rec, $fields = array())
    {
        $now = dt::now();

        try{
            $row->objectId = cls::get($rec->classId)->getHyperlink($rec->objectId, true);
        } catch(core_exception_Expect $e){

        }

        if($rec->state == 'rejected') {
            $row->ROW_ATTR['class'] = "state-rejected";
        } elseif($rec->validFrom > $now){
            $row->ROW_ATTR['class'] = "state-draft";
        } else {
            $row->ROW_ATTR['class'] = "state-active";
        }
    }


    /**
     * Връща записа какъвто е бил към датата
     *
     * @param mixed $class
     * @param int|stdClass $id
     * @param datetime|null $date
     * @return stdClass
     */
    public static function getRecOnDate($class, $id, $date = null)
    {
        $Class = cls::get($class);
        $date = $date ?? dt::now();
        $rec = $Class->fetchRec($id);

        $res = clone $rec;
        $historyQuery = self::getQuery();
        $historyQuery->where("#classId = {$Class->getClassId()} AND #objectId = {$rec->id} AND #validFrom <= '{$date}' AND ('{$date}' < #validTo OR #validTo IS NULL) AND #state = 'active'");
        $historyQuery->orderBy("validFrom", 'DESC');
        $historyRec = $historyQuery->fetch();

        // Ако има запис в историята - ще се заместят текущите данни с кешираните
        if(is_object($historyRec)){
            foreach ((array)$historyRec->data as $cFld => $cVal){
                $res->{$cFld} = $cVal;
            }
            $res->validFrom = $historyRec->validFrom;
            $res->validTo = $historyRec->validTo;

            // И metadata за създаването на версията
            $res->versionCreatedOn = !empty($historyRec->versionCreatedOn) ? $historyRec->versionCreatedOn : $historyRec->createdOn;
            $res->versionCreatedBy = !empty($historyRec->versionCreatedBy) ? $historyRec->versionCreatedBy : $historyRec->createdBy;
        }

        return $res;
    }
    
    
    /**
     * Преизчислява validTo на всички активни версии за обекта
     *
     * @param mixed $class
     * @param int $objectId
     * @return void
     */
    public static function recalcActiveVersionValidTo($class, $objectId)
    {
        $Class = cls::get($class);
        $classId = $Class->getClassId();

        $query = self::getQuery();
        $query->where("#classId = '{$classId}' AND #objectId = '{$objectId}' AND #state = 'active'");

        $arr = array();
        while ($rec = $query->fetch()) {
            $arr[$rec->id] = $rec;
        }

        if (!countR($arr)) {
            return;
        }

        arr::sortObjects($arr, 'validFrom', 'ASC');
        $vals = array_values($arr);

        $saveArr = array();
        $count = count($vals);
        for ($i = 0; $i < $count; $i++) {
            $vals[$i]->validTo = isset($vals[$i + 1]) ? $vals[$i + 1]->validFrom : null;
            $saveArr[$vals[$i]->id] = $vals[$i];
        }

        cls::get(get_called_class())->saveArray($saveArr, 'id,validTo');
    }


    /**
     * Подготовка на детайла
     *
     * @param stdClass $data
     */
    public function prepareDetail_($data)
    {
        $tabParam = 'Tab';
        if(!cls::haveInterface('doc_FolderIntf', $data->masterMvc)){
            $tabParam = $data->masterData->tabTopParam;
        }
        $prepareTab = Request::get($tabParam);

        // Подготовка на записите
        $query = self::getQuery();
        $query->where("#classId = {$data->masterMvc->getClassId()} AND #objectId = {$data->masterId} AND #state != 'rejected'");
        $count = $query->count();

        // Ако няма реални записи в историята, не показваме detail "Версии"
        if (!$count) {
            $data->hide = true;
            return;
        }

        $data->TabCaption = tr('Версии');

        if($prepareTab != 'change_History') {
            $data->hide = true;
            return;
        }

        $masterRec = $data->masterData->rec;
        $data->rows = $data->recs = array();
        while($rec = $query->fetch()){
            $data->recs[$rec->validFrom] = $rec;
        }

        // Ако текущата версия е от историята - взима се тя, ако не показва се в историята
        if(!array_key_exists($masterRec->validFrom, $data->recs)){
            $currentCreatedOn = !empty($masterRec->versionCreatedOn)
                ? $masterRec->versionCreatedOn
                : (!empty($masterRec->createdOn) ? $masterRec->createdOn : $masterRec->validFrom);

            $currentCreatedBy = !empty($masterRec->versionCreatedBy)
                ? $masterRec->versionCreatedBy
                : (!empty($masterRec->createdBy) ? $masterRec->createdBy : null);

            $data->recs[$masterRec->validFrom] = (object) array(
                'validFrom' => $masterRec->validFrom,
                'validTo'   => $masterRec->validTo,
                'classId'   => $data->masterMvc->getClassId(),
                'objectId'  => $masterRec->id,
                'isCurrent' => true,
                'createdOn' => $currentCreatedOn,
                'createdBy' => $currentCreatedBy,
                'versionCreatedOn' => $currentCreatedOn,
                'versionCreatedBy' => $currentCreatedBy,
            );
        } else {
            $data->recs[$masterRec->validFrom]->isCurrent = true;
        }

        $now = dt::now();

        arr::sortObjects($data->recs, 'validFrom', 'DESC');
        $count = countR($data->recs);

        foreach ($data->recs as $rec) {
            $rec->count = $count;

            if (!empty($rec->versionCreatedOn)) {
                $rec->createdOn = $rec->versionCreatedOn;
            }
            if (!empty($rec->versionCreatedBy)) {
                $rec->createdBy = $rec->versionCreatedBy;
            }

            $row = $this->recToVerbal($rec);

            $data->recs[$rec->id] = $rec;

            $row->validFrom = core_Type::getByName('date')->toVerbal(substr($rec->validFrom, 0, 10));
            if (!empty($rec->validTo)) {
                $row->validTo = core_Type::getByName('date')->toVerbal(substr($rec->validTo, 0, 10));
            }

            $row->date = $row->validFrom;
            $row->count = core_Type::getByName('int')->toVerbal($rec->count);

            if($rec->validFrom > $now){
                $row->ROW_ATTR['class'] = "state-draft";
            } elseif(!empty($rec->isCurrent)){
                $row->ROW_ATTR['class'] = "state-active";
            } else {
                $row->ROW_ATTR['class'] = "state-closed";
            }

            // Моливче само за текущата версия
            if (!empty($rec->isCurrent)) {
                $editRec = (object) array('id' => $data->masterId);
                $row->validFrom = change_plg_History::appendValidFromEditIcon($data->masterMvc, $editRec, $row->validFrom);
            }

            // Подготовка на бутоните за избор
            $versionId = !empty($rec->isCurrent) ? static::CURRENT_VERSION_ID : $rec->id;
            if($this->isSelected($data->masterMvc->getClassId(), $data->masterId, $versionId)){
                $icon = 'img/16/checkbox_yes.png';
                $action = 'deselect';
                $title = 'Отказ от версията';
            } else {
                $icon = 'img/16/checkbox_no.png';
                $action = 'select';
                $title = 'Избор на версията';
            }

            $link = array(
                $this,
                'log',
                'classId' => $rec->classId,
                'objectId' => $rec->objectId,
                'versionId' => $versionId,
                'tab' => Request::get('Tab'),
                'action' => $action,
                'verString' => $rec->count
            );
            $row->count = ht::createLink('', $link, null, "ef_icon={$icon},title={$title}")->getContent() . $row->count;
            $row->created = "{$row->createdOn} " . tr('от||by') . " {$row->createdBy}";

            $data->rows[$rec->id] = $row;
            $count--;
        }

        return $data;
    }


    /**
     * Рендиране на детайла
     *
     * @param stdClass $data
     * @return core_ET $resTpl
     */
    public function renderDetail_($data)
    {
        $tpl = new core_ET('');
        if($data->hide) return $tpl;

        // Рендиране на таблицата с оборудването
        $data->listFields = arr::make('validFrom=От,created=Създаване,count=Вер.');

        $listTableMvc = clone $this;
        $table = cls::get('core_TableView', array('mvc' => $listTableMvc));
        $tpl->append($table->get($data->rows, $data->listFields));

        $resTpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $resTpl->append($tpl, 'content');

        $title = tr("Версии");
        if(change_History::haveRightFor('list')){
            $title .= ht::createLink('', array('change_History', 'list', 'classId' => $data->masterMvc->getClassId(), 'objectId' => $data->masterId), false, 'ef_icon=img/16/funnel.png,title=Преглед на версиите в историята')->getContent();
        }
        $resTpl->append($title, 'title');

        return $resTpl;
    }


    /**
     * Екшън за избиране/отказване на съответната версия
     */
    public function act_log()
    {
        // Изискваме да има права
        requireRole('user');

        expect($classId = Request::get('classId', 'int'));
        expect($objectId = Request::get('objectId', 'int'));
        expect($action = Request::get('action'));
        expect($versionId = Request::get('versionId', 'varchar'));
        expect($tab = Request::get('tab', 'varchar'));
        expect($verString = Request::get('verString'));

        $Class = cls::get($classId);
        $cRec = $Class->fetch($objectId);
        expect($Class->haveRightFor('single', $objectId) || doc_Threads::haveRightFor('single', $cRec->threadId));

        // Масив с всички избрани версии за съответния документ
        $selectedArr = self::getSelectedVersionsArr($classId, $objectId);

        if ($action == 'deselect') {
            if ($selectedArr[$versionId]) {
                unset($selectedArr[$versionId]);
            }
        } else {
            $selectedArr[$versionId] = array('date' => dt::now(), 'verString' => $verString);
        }

        $this->updateSelectedVersion($classId, $objectId, $selectedArr);
        $link = array($Class, 'single', $cRec->id, 'Tab' => $tab);

        return new Redirect($link);
    }


    /**
     * Връща масив с всички избрани версии
     *
     * @param int $classId - id на класа
     * @param int $objectId   - id на документа
     *
     * @return array - Масив с избраните версии
     */
    public static function getSelectedVersionsArr($classId, $objectId)
    {
        // Вземаме масива за версиите
        $versionArr = mode::get(static::PERMANENT_SAVE_NAME);
        $versionArr = is_array($versionArr) ? $versionArr : array();
        if (!$classId || !$objectId) return $versionArr;

        return array_key_exists("{$classId}_{$objectId}",  $versionArr) ? $versionArr["{$classId}_{$objectId}"] : array();
    }


    /**
     * Задава в сесията масива със селектирани версии
     *
     * @param int  $classId
     * @param int  $objectId
     * @param array  $arr
     */
    private function updateSelectedVersion($classId, $objectId, $arr)
    {
        // Вземаме глобалния масив за всички документи
        $allVersionArr = Mode::get(static::PERMANENT_SAVE_NAME);
        $allVersionArr = is_array($allVersionArr) ? $allVersionArr : array();

        arr::sortObjects($arr, 'date', 'ASC');
        $allVersionArr["{$classId}_{$objectId}"] = array_slice($arr, -2, 2, true);

        Mode::setPermanent(static::PERMANENT_SAVE_NAME, $allVersionArr);
    }


    /**
     * Дали текущата версия е избрана в сесията
     *
     * @param int $classId
     * @param int $objectId
     * @param int $versionId
     * @return bool
     */
    private function isSelected($classId, $objectId, $versionId)
    {
        $arr = self::getSelectedVersionsArr($classId, $objectId);

        return array_key_exists($versionId, $arr);
    }
}