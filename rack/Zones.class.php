<?php


/**
 * Модел за "Зони"
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_Zones extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Зони';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'rack_Wrapper,plg_Sorting,plg_Created,plg_State2,plg_RowTools2,plg_RefreshRows';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'ceo,rackMaster';
    
    
    /**
     * Кой може да редактира?
     */
    public $canEdit = 'ceo,rackMaster';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,rack';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,rackMaster';
    
    
    /**
     * Кой може да генерира нагласяния?
     */
    public $canOrderpickup = 'ceo,rack';
    
    
    /**
     * Работен кеш
     */
    protected static $movementCache = array();
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,rack';
    
    
    /**
     * Полета в листовия изглед
     */
    public $listFields = 'num=Зона,containerId,readiness,folderId=Папка,lineId=Линия,pendingHtml=@';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     *
     *  @var string
     */
    public $hideListFieldsIfEmpty = 'pendingHtml,folderId,lineId';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'rack_ZoneDetails';
    
    
    /**
     * Кой може да селектира документа
     */
    public $canSelectdocument = 'ceo,rack';
    
    
    /**
     * Кой може да премахва докумнета от зоната
     */
    public $canRemovedocument = 'ceo,rack';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'rack/tpl/SingleLayoutZone.shtml';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'num';
    
    
    /**
     * На колко време да се рефрешва лист изгледа
     */
    public $refreshRowsTime = 7000;
    

    /**
     * Шаблон за реда в листовия изглед
     */
    public $tableRowTpl = "[#ROW#][#ADD_ROWS#]\n";


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('num', 'int(max=100)', 'caption=Номер,mandatory');
        $this->FLD('description', 'text(rows=2)', 'caption=Описание');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,mandatory,remember,input=hidden');
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Документ,smartCenter,input=none');
        $this->FLD('readiness', 'percent', 'caption=Готовност,smartCenter,input=none');
        $this->FLD('groupId', 'key(mvc=rack_ZoneGroups,selec=name,allowEmpty)', 'caption=Група,placeholder=Без групиране');
        
        $this->setDbUnique('num,storeId');
        $this->setDbIndex('storeId');
        $this->setDbIndex('containerId');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
        if (isset($rec->containerId)) {
            $row->containerId = doc_Containers::getDocument($rec->containerId)->getLink(0);
        }
        
        if(isset($fields['-list'])){
            $rec->_isSingle = false;
            $pendingHtml = rack_ZoneDetails::renderInlineDetail($rec, $mvc);
            if (!empty($pendingHtml)) {
                $row->pendingHtml = $pendingHtml;
            }
        }
        $row->ROW_ATTR['class'] = $row->ROW_ATTR['class'] . " rack-zone-head";
        
        $row->num = $mvc->getHyperlink($rec->id);
        
        if(isset($rec->containerId)){
            $document = doc_Containers::getDocument($rec->containerId);
            $documentRec = $document->fetch();
            $row->folderId = doc_Folders::getFolderTitle($documentRec->folderId);
            
            if(isset($documentRec->{$document->lineFieldName})){
                $row->lineId = trans_Lines::getHyperlink($documentRec->{$document->lineFieldName}, 0);
            }
        }
        
        if(isset($fields['-list'])){
            if($mvc->haveRightFor('removedocument', $rec->id)){
                core_RowToolbar::createIfNotExists($row->_rowTools);
                $row->_rowTools->addLink('Премахване', array($mvc, 'removeDocument', $rec->id, 'ret_url' => true), 'ef_icon=img/16/gray-close.png,title=Премахване на документа от зоната,warning=Наистина ли искате да премахнете документа и свързаните движения|*?');
            }
            
            Mode::push('shortZoneName', true);
            $id = self::getRecTitle($rec);
            Mode::pop('shortZoneName');
            $row->num = ht::createElement("div", array('id' => $id), $row->num, true);
        }
        
        if(!empty($rec->description)){
            $description = $mvc->getVerbal($rec, 'description');
            $row->num = ht::createHint($row->num, $description);
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     *
     * @return bool|null
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if($mvc->haveRightFor('removedocument', $data->rec->id)){
            $data->toolbar->addBtn('Премахване', array($mvc, 'removeDocument', $data->rec->id, 'ret_url' => true), 'ef_icon=img/16/gray-close.png,title=Премахване на документа от зоната,warning=Наистина ли искате да премахнете документа и свързаните движения|*?');
        }
    }
    
    
    /**
     * Връща зоните към подадения склад
     *
     * @param int|NULL $storeId       - ид на склад     
     * @param boolean $onlyFree       - само наличното или цялото количество
     * 
     * @param boolean|NULL $groupable - Дали да се групират, null за всички
     * @param boolean|NULL $groupId   - конкретна група, по която да се филтрират
     * 
     * @return array $options
     */
    public static function getZones($storeId = null, $onlyFree = false, $groupable = null, $groupId = null)
    {
        $options = array();
        $query = self::getQuery();
        $query->where("#state != 'closed'");
        if ($onlyFree === true) {
            $query->where('#containerId IS NULL');
        }
        if (isset($storeId)) {
            $query->where("#storeId = {$storeId}");
        }
        
        if(isset($groupable)){
            expect(is_bool($groupable));
            if(isset($groupId)){
                $query->where("#groupId = {$groupId}");
            } else {
                $query->where("#groupId IS NULL");
            }
        }
        $query->orderBy('num', 'ASC');
        
        while ($rec = $query->fetch()) {
            $options[$rec->id] = self::getRecTitle($rec, false);
        }
        
        return $options;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $rec = self::fetchRec($rec);
        
        $num = self::getVerbal($rec, 'num');
        $groupName = null;
        if(!Mode::is('shortZoneName')){
            $groupName = (is_null($rec->groupId)) ? tr('Без група') : rack_ZoneGroups::getVerbal($rec->groupId, 'name');
        }
        
        $title = "Z-{$num}";
        if(!empty($groupName)){
            $title .= " ({$groupName})";
        }
        
        if ($escaped) {
            $title = type_Varchar::escape($title);
        }
        
        return $title;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;
        $form->setDefault('storeId', store_Stores::getCurrent('id', $rec ? $rec->storeId : null));
        
        // Ако има работен запис към зоната не може да се сменя склада
        if (isset($rec->containerId)) {
            $form->setReadOnly('storeId');
        }
        
        $form->setDefault('num', $mvc->getNextNumber($rec->storeId));
    }

    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            if(empty($form->rec->groupId)){
                $form->setWarning('groupId', 'Сигурни ли сте, че не искате да изберете група|*?');
            }
        }
    }
    
    
    /**
     * След рендиране на лист таблицата
     */
    protected static function on_AfterRenderListTable($mvc, &$tpl, &$data)
    {
        $tpl->push('rack/css/style.css', 'CSS');
        
        $tpl->push('rack/js/ZoneScripts.js', 'JS');
        jquery_Jquery::run($tpl, 'zoneActions();');
    }

    
    /**
     * След рендиране на singyla
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        $tpl->push('rack/css/style.css', 'CSS');
        
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        // По-хубаво заглавие на формата
        $rec = $data->form->rec;
        $data->form->title = core_Detail::getEditTitle('store_Stores', $rec->storeId, 'зона', $rec->id, tr('в склад'));
    }
    
    
    /**
     * Добавя филтър към перата
     *
     * @param acc_Items $mvc
     * @param stdClass  $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $storeId = store_Stores::getCurrent();
        $data->query->where("#storeId = {$storeId}");
        $data->title = 'Зони в склад|* <b style="color:green">' . store_Stores::getHyperlink($storeId, true) . '</b>';
        $data->query->orderBy('num', 'asc');
        
        // Добавяне на филтър по артикулите
        $data->listFilter->FLD('productId', "key2(mvc=cat_Products,storeId={$storeId},select=name,allowEmpty,selectSource=rack_Zones::getProductsInZones)", 'caption=Артикул,autoFilter,silent');
        $data->listFilter->FLD('grouping', "varchar", 'caption=Всички,autoFilter,silent');
        $groupingOptions = array('' => '', 'no' => tr('Без групиране'));
        
        // Добавяне на групите, както и самостоятелните зони
        $gQuery = rack_ZoneGroups::getQuery();
        while($gRec = $gQuery->fetch()){
            $groupingOptions[$gRec->id] = $gRec->name;
        }
        $singleZones = rack_Zones::getZones($storeId, false, false);
        foreach ($singleZones as $z1 => $zoneName) {
            $groupingOptions["s{$z1}"] = $zoneName;
        }
        
        $data->listFilter->setOptions('grouping', $groupingOptions);
        $data->listFilter->input(null, 'silent');
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->showFields = 'productId,grouping';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->input('productId,grouping');
        
        // Ако се филтрира по артикул
        if($filter = $data->listFilter->rec){
            if(isset($filter->productId)){
                
                // Оставят се само тези зони където се среща артикула
                $dQuery = rack_ZoneDetails::getQuery();
                $dQuery->EXT('storeId', 'rack_Zones', 'externalName=storeId,externalKey=zoneId');
                $dQuery->where("#productId={$filter->productId} AND #storeId = {$storeId}");
                $zoneIdsWithProduct = arr::extractValuesFromArray($dQuery->fetchAll(), 'zoneId');
                if(countR($zoneIdsWithProduct)){
                    $data->query->in('id', $zoneIdsWithProduct);
                } else {
                    $data->query->where("1=2");
                }
            }
            
            if(!empty($filter->grouping)){
                switch($filter->grouping){
                    case 'no':
                        $data->query->where("#groupId IS NULL");
                        break;
                    case is_numeric($filter->grouping):
                        $data->query->where("#groupId = {$filter->grouping}");
                        break;
                    case strpos($filter->grouping, 's'):
                        $id = trim($filter->grouping, 's');
                        $data->query->where("#id = {$id}");
                        break;
                }
            }
        }
    }
    
    
    /**
     * Филтър по артикулите в зоните
     */
    public static function getProductsInZones($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        $dQuery = rack_ZoneDetails::getQuery();
        $dQuery->EXT('storeId', 'rack_Zones', 'externalName=storeId,externalKey=zoneId');
        $dQuery->EXT('searchKeywords', 'cat_Products', 'externalName=searchKeywords,externalKey=productId');
        $dQuery->where("#storeId = {$params['storeId']}");
        $dQuery->groupBy('productId');
        
        if (is_array($onlyIds)) {
            if (!countR($onlyIds)) return array();
            
            $ids = implode(',', $onlyIds);
            expect(preg_match("/^[0-9\,]+$/", $onlyIds), $ids, $onlyIds);
            
            $dQuery->where("#productId IN (${ids})");
        } elseif (ctype_digit("{$onlyIds}")) {
            $dQuery->where("#productId = ${onlyIds}");
        }
        
        if ($q) {
            if ($q[0] == '"') {
                $strict = true;
            }
            $q = trim(preg_replace("/[^a-z0-9\p{L}]+/ui", ' ', $q));
            $q = mb_strtolower($q);
            
            if ($strict) {
                $qArr = array(str_replace(' ', '.*', $q));
            } else {
                $qArr = explode(' ', $q);
            }
            
            $pBegin = type_Key2::getRegexPatterForSQLBegin();
            foreach ($qArr as $w) {
                $dQuery->where(array("#searchKeywords REGEXP '(" . $pBegin . "){1}[#1#]'", $w));
            }
        }
        
        if ($limit) {
            $dQuery->limit($limit);
        }
        
        $dQuery->show('productId');
        
        $res = array();
        while ($rec = $dQuery->fetch()) {
            $res[$rec->productId] = cat_Products::getTitleById($rec->productId, false);
        }
        
        return $res;
    }
    
    
    /**
     * Избор на зона в документ
     *
     * @return void|core_ET
     */
    public function act_Selectdocument()
    {
        // Проверка на права
        $this->requireRightFor('selectdocument');
        expect($containerId = Request::get('containerId', 'int'));
        expect($document = doc_Containers::getDocument($containerId));
        $this->requireRightFor('selectdocument', (object) array('containerId' => $containerId));
        $documentRec = $document->fetch();
        $storeId = $documentRec->{$document->storeFieldName};
        
        // Подготовка на формата
        $form = cls::get('core_Form');
        $form->title = 'Събиране на редовете на|* ' . $document->getFormTitleLink();
        $form->info = tr('Склад|*: ') . store_Stores::getHyperlink($storeId, true);
        $form->FLD('zoneId', 'key(mvc=rack_Zones,select=name)', 'caption=Зона,mandatory');
        $zoneOptions = rack_Zones::getZones($storeId, true);
        $zoneId = rack_Zones::fetchField("#containerId = {$containerId}", 'id');
        if (!empty($zoneId) && !array_key_exists($zoneId, $zoneOptions)) {
            $zoneOptions[$zoneId] = $this->getRecTitle($zoneId);
        }
        $form->setOptions('zoneId', array('' => '') + $zoneOptions);
        $form->setDefault('zoneId', $zoneId);
        $form->setDefault('zoneId', key($zoneOptions));
        $form->input();
        
        // Изпращане на формата
        if ($form->isSubmitted()) {
            $fRec = $form->rec;
            
            // Присвояване на новата зона
            if (isset($fRec->zoneId)) {
                $zoneRec = $this->fetch($fRec->zoneId);
                $zoneRec->containerId = $containerId;
                $this->save($zoneRec);
                
                // Синхронизиране с детайла на зоната
                rack_ZoneDetails::syncWithDoc($zoneRec->id, $containerId);
                $this->updateMaster($zoneRec);
                
                // Ако групата е с групиране, се извличат всички зони от същата група
                if(isset($zoneRec->groupId)){
                    $selectedZones = self::getZones($storeId, false, true, $zoneRec->groupId);
                    $selectedZones = arr::make(array_keys($selectedZones), true);
                } else {
                    $selectedZones = $zoneRec->id;
                }
               
                // Генериране на движенията за нагласяне
                self::pickupOrder($storeId, $selectedZones);
            }
            
            // Старата зона се отчуждава от документа
            if ($zoneId != $fRec->zoneId && isset($zoneId)) {
                $zoneRec1 = $this->fetch($zoneId);
                $zoneRec1->containerId = null;
                $this->save($zoneRec1);
                rack_ZoneDetails::syncWithDoc($zoneRec1->id);
                
                $this->updateMaster($zoneRec1);
            }
            
            // Ако е избрана зона редирект към нея, иначе се остава в документа
            if (isset($fRec->zoneId)) {
                redirect(self::getUrlArr($fRec->zoneId));
            }
            
            followRetUrl();
        }
        
        // Добавяне на бутони
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/move.png, title = Запис на действието');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        // Записваме, че потребителя е разглеждал този списък
        $document->logInfo('Избор на зона');
        $tpl = $document->getInstance()->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);
        
        return $tpl;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'selectdocument' && isset($rec)) {
            if (empty($rec->containerId)) {
                $requiredRoles = 'no_one';
            } else {
                $document = doc_Containers::getDocument($rec->containerId);
                $selectedStoreId = store_Stores::getCurrent('id');
                if (!rack_Zones::fetchField("#storeId = {$selectedStoreId} AND #state != 'closed'")) {
                    $requiredRoles = 'no_one';
                } else {
                    $documentRec = $document->fetch("state,{$document->storeFieldName}");
                    if (!$document->haveRightFor('single') || !in_array($documentRec->state, array('draft', 'pending')) || $documentRec->{$document->storeFieldName} != $selectedStoreId) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
        
        if (($action == 'delete' || $action == 'changestate') && isset($rec)) {
            
            // Ако в зоната има редове или е използвана в движение, не може да се изтрива
            if (rack_ZoneDetails::fetchField("#zoneId = {$rec->id}") || rack_Movements::fetchField("LOCATE('|{$rec->id}|', #zoneList)")) {
                $requiredRoles = 'no_one';
            }
        }
        
        if($action == 'removedocument' && isset($rec->id)){
            if(empty($rec->containerId)){
                $requiredRoles = 'no_one';
            } else {
                if(rack_ZoneDetails::fetchField("#zoneId = {$rec->id} AND (#movementQuantity IS NOT NULL AND #movementQuantity != 0)")){
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Изчистване на зоната към която е закачен документа
     *
     * @param int $containerId
     */
    public static function clearZone($containerId)
    {
        // Към коя зона е в момента закачен документа
        $zoneRec = self::fetch("#containerId = {$containerId}");
        if (empty($zoneRec)) return;
        
        // Затваря движенията към зоната
        rack_Movements::closeByZoneId($zoneRec->id);
        
        // Рекалкулира к-та по зони на артикула
        $productArr = array();
        $dQuery = rack_ZoneDetails::getQuery();
        $dQuery->where("#zoneId = {$zoneRec->id}");
        while($dRec = $dQuery->fetch()){
            rack_ZoneDetails::delete($dRec->id);
            $productArr[$dRec->productId] = $dRec->productId;
        }
        
        rack_Products::recalcQuantityOnZones($productArr, $zoneRec->storeId);
        
        $zoneRec->containerId = null;
        self::save($zoneRec);
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     *
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
        $rec = $this->fetchRec($id);
        $ready = $count = 0;
        
        $dQuery = rack_ZoneDetails::getQuery();
        $dQuery->where("#zoneId = {$rec->id}");
        while ($dRec = $dQuery->fetch()) {
            if (!empty($dRec->documentQuantity) && round($dRec->documentQuantity, 4) == round($dRec->movementQuantity, 4)) {
                $ready++;
            }
            
            if (!empty(round($dRec->documentQuantity, 4)) || !empty(round($dRec->movementQuantity, 4))){
                $count++;
            }
        }
        
        $rec->readiness = ($count) ? $ready / $count : null;
        $this->save($rec, 'readiness');
    }
    
    
    /**
     * Изпълнява се след подготвянето на тулбара в листовия изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $storeId = store_Stores::getCurrent();
        if ($mvc->haveRightFor('orderpickup', (object) array('storeId' => $storeId))) {
            $data->toolbar->addBtn('Генериране на движения', array($mvc, 'orderpickup', 'storeId' => $storeId, 'ret_url' => true), 'ef_icon=img/16/arrow_refresh.png,title=Бързо нагласяне');
        }
    }
    
    
    /**
     * Кои са текущите движения в зоната
     *
     * @param int $zoneId
     * @param boolean $skipClosed
     *
     * @return array $res
     */
    public static function getCurrentMovementRecs($zoneId, $skipClosed = true)
    {
        if(!isset(self::$movementCache[$zoneId])){
            self::$movementCache[$zoneId] = array();
            $mQuery = rack_Movements::getQuery();
            $mQuery->where("LOCATE('|{$zoneId}|', #zoneList)");
            if($skipClosed === true){
                $mQuery->where("#state != 'closed'");
            }
            $mQuery->orderBy('id', 'DESC');
            
            while ($mRec = $mQuery->fetch()) {
                if (!empty($mRec->zones)) {
                    $zones = type_Table::toArray($mRec->zones);
                    $quantity = null;
                    foreach ($zones as $zObject) {
                        if ($zObject->zone == $zoneId) {
                            $quantity = $zObject->quantity;
                            break;
                        }
                    }
                    
                    $clone = clone $mRec;
                    $clone->_originalPackQuantity = $mRec->packQuantity;
                    $clone->quantity = $quantity;
                    $clone->packQuantity = $clone->quantity;
                    
                    self::$movementCache[$zoneId][$mRec->id] = $clone;
                }
            }
        }
        
        return self::$movementCache[$zoneId];
    }
    
    
    /**
     * Следващия номер на зона
     *
     * @param int $storeId
     *
     * @return float number
     */
    private function getNextNumber($storeId)
    {
        $query = $this->getQuery();
        $query->orderBy('#num', 'DESC');
        $lastRec = $query->fetch("#storeId = {$storeId}");
        
        $num = is_object($lastRec) ? $lastRec->num : 0;
        $num++;
        
        return $num;
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $data->listTableMvc->commonRowClass = 'zonesCommonRow zoneLighter';
        $data->listTableMvc->setFieldType('num', 'varchar');
    }
    
    
    /**
     * Избор на зона в документ
     *
     * @return void|core_ET
     */
    public function act_Orderpickup()
    {
        // Проверка на права
        $this->requireRightFor('orderpickup');
        expect($storeId = Request::get('storeId', 'int'));
        $this->requireRightFor('orderpickup', (object) array('storeId' => $storeId));
       
        // Групиране по групи на зоните
        $gQuery = rack_ZoneGroups::getQuery();
        $gQuery->orderBy('order', 'ASC');
        
        while($gRec = $gQuery->fetch()){
            $groupableZones = self::getZones($storeId, false, true, $gRec->id);
            if(countR($groupableZones)){
                $groupableZones = arr::make(array_keys($groupableZones), true);
                self::pickupOrder($storeId, $groupableZones);
            }
        }
        
        // Всички зони, които са без групиране
        $nonGroupableZones = array_keys(self::getZones($storeId, false, false));
        foreach ($nonGroupableZones as $zoneId){
            self::pickupOrder($storeId, $zoneId);
        }
        
        followRetUrl(null, 'Движенията са генерирани успешно');
    }
    
    
    /**
     * Генерира очакваните движения за зоните в склада
     * 
     * @param int $storeId - ид на склад
     * @param array|null $zoneIds - ид-та само на избраните зони
     */
    private static function pickupOrder($storeId, $zoneIds = null)
    {
        // Какви са очакваните количества
        $expected = self::getExpectedProducts($storeId, $zoneIds);
        $systemUserId = core_Users::SYSTEM_USER;
        
        // Изчистване на заявките към зоните
        $mQuery = rack_Movements::getQuery();
        $mQuery->where("#state = 'pending' AND #zoneList IS NOT NULL AND #createdBy = {$systemUserId} AND #modifiedBy = {$systemUserId}");
        $mQuery->likeKeylist('zoneList', $expected->zones);
        $mQuery->show('id');
            
        while ($mRec = $mQuery->fetch()) {
           rack_Movements::delete($mRec->id);
        }

        $floor = rack_PositionType::FLOOR;
        foreach ($expected->products as $pRec) {
            $BatchClass = batch_Defs::getBatchDef($pRec->productId);
            
            // Ако в зоната реда е с определена партидност - то трябва да се подадат само палетите с тази партидност.
            // Ако в зоната реда е без партидност, но продукта има партидност - се търсят само палетите, които са с празна партидност
            $batch = (is_object($BatchClass)) ? $pRec->batch : null;
            
            // Какви са наличните палети за избор
            $pallets = rack_Pallets::getAvailablePallets($pRec->productId, $storeId, $batch, true);
            
            $quantityOnPallets = arr::sumValuesArray($pallets, 'quantity');
            $requiredQuantityOnZones = array_sum($pRec->zones);
            
            // Ако к-то по палети е достатъчно за изпълнение, не се добавя ПОД-а, @TODO да се изнесе в mainP2Q
            if($quantityOnPallets < $requiredQuantityOnZones){
                $floorQuantity = rack_Products::getFloorQuantity($pRec->productId, $batch, $storeId);
                if ($floorQuantity) {
                    $pallets[$floor] = (object) array('quantity' => $floorQuantity, 'position' => $floor);
                }
            }
            
            $palletsArr = array();
            foreach ($pallets as $obj) {
                $palletsArr[$obj->position] = $obj->quantity;
            }
            
            if (!countR($palletsArr)) {
                continue;
            }
            
            // Какво е разпределянето на палетите
            $allocatedPallets = rack_MovementGenerator::mainP2Q($palletsArr, $pRec->zones);
            
            // Ако има генерирани движения се записват
            $movements = rack_MovementGenerator::getMovements($allocatedPallets, $pRec->productId, $pRec->packagingId, $pRec->batch, $storeId);
            
            // Движенията се създават от името на системата
            core_Users::forceSystemUser();
            foreach ($movements as $movementRec) {
                rack_Movements::save($movementRec);
            }
            core_Users::cancelSystemUser();
        }
    }
    
    
    /**
     * Премахване на документ от зоната
     *
     * @return void
     */
    public function act_Removedocument()
    {
        // Проверка на права
        $this->requireRightFor('removedocument');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('removedocument', $rec);
        
        $rec->containerId = null;
        $this->save($rec);
        rack_ZoneDetails::syncWithDoc($rec->id);
        
        // Изтриване на всички заявки към зоната
        $mQuery = rack_Movements::getQuery();
        $mQuery->where("LOCATE('|{$rec->id}|', #zoneList) AND #state = 'pending'");
        while($mRec = $mQuery->fetch()){
            rack_Movements::delete($mRec->id);
        }
        
        $this->updateMaster($rec);
        
        followRetUrl(null, 'Документът е премахнат от зоната');
    }
    
    
    /**
     * Връща очакваните артикули по зони с документи
     *
     * @param int $storeId
     * @param array|null $zoneIds - ид-та само на избраните зони
     *
     * @return stdClass $res
     */
    private static function getExpectedProducts($storeId, $zoneIds = null)
    {
        $res = (object)array('products' => array());
        $res->zones = (is_numeric($zoneIds)) ? array($zoneIds => $zoneIds) : ((is_array($zoneIds)) ? $zoneIds : array());
        
        $dQuery = rack_ZoneDetails::getQuery();
        $dQuery->EXT('storeId', 'rack_Zones', 'externalName=storeId,externalKey=zoneId');
        $dQuery->where("#documentQuantity IS NOT NULL AND #storeId = {$storeId}");
        if(isset($zoneIds)){
            $zoneIds = arr::make($zoneIds, true);
            $dQuery->in('zoneId', $zoneIds);
        }
        
        while ($dRec = $dQuery->fetch()) {
            
            // Участват само тези по които се очакват още движения
            $needed = $dRec->documentQuantity - $dRec->movementQuantity;
            if (empty($needed) || $needed < 0) {
                continue;
            }
            
            $key = "{$dRec->productId}|{$dRec->packagingId}|{$dRec->batch}";
            if (!array_key_exists($key, $res->products)) {
                $res->products[$key] = (object) array('productId' => $dRec->productId, 'packagingId' => $dRec->packagingId, 'zones' => array(), 'batch' => $dRec->batch);
                $res->zones[$dRec->zoneId] = $dRec->zoneId;
            }
            
            $res->products[$key]->zones[$dRec->zoneId] += ($dRec->documentQuantity - $dRec->movementQuantity);
        }
        
        return $res;
    }
    
    
    /**
     * Данни за бутона за зоната
     *
     * @param int $containerId
     * @return stdClass $res
     */
    public static function getBtnToZone($containerId)
    {
        $res = (object)array('caption' => 'Зона', 'url' => array(), 'attr' => '');
        $document = doc_Containers::getDocument($containerId);
        
        if ($zoneRec = rack_Zones::fetch("#containerId = {$containerId}")){
            $res->caption .= "|* " . rack_Zones::getTitleById($zoneRec);
        }
        
        if(empty($zoneRec)){
            $zoneOptions = rack_Zones::getZones($document->fetch()->{$document->storeFieldName}, true);
            if(rack_Zones::haveRightFor('selectdocument', (object)array('containerId' => $containerId))){
                $res->url = array('rack_Zones', 'selectdocument', 'containerId' => $containerId, 'ret_url' => true);
                $res->attr = "ef_icon=img/16/hand-point.png,title=Избор на зона за нагласяне";
            }
            if(empty($zoneOptions)){
                $res->attr .= ',error=Няма свободни зони в склада|*!';
            }
            
        } else{
            if (rack_Zones::haveRightFor('list')){
                $res->url = self::getUrlArr($zoneRec);
                $res->attr = "ef_icon=img/16/package.png,title=Към зоната";
            }
        }
        $res->attr = arr::make($res->attr, true);
        
        return $res;
    }
    
    
    /**
     * Връща Урл към списъка на зоните филтрирани по зона и група
     * 
     * @param mixed $zoneId - ид или запис на зона
     * @return array $url - урл-то
     */
    public static function getUrlArr($zoneId)
    {
        $zoneRec = self::fetchRec($zoneId);
        Mode::push('shortZoneName', true);
        $url = array('rack_Zones', 'list', '#' => rack_Zones::getRecTitle($zoneRec), 'ret_url' => true);
        Mode::pop('shortZoneName');
        
        if(isset($zoneRec->groupId)){
            $url['grouping'] = $zoneRec->groupId;
        }
        
        return $url;
    }
    
    
    /**
     * Функция по подразбиране, за връщане на хеша на резултата
     *
     * @param core_Mvc $mvc
     * @param string   $res
     * @param string   $status
     */
    protected function on_AfterGetContentHash($mvc, &$res, &$status)
    {
        $storeId = store_Stores::getCurrent();
        
        // Хеша е датата на последна модификация на движенията
        $mQuery = rack_Movements::getQuery();
        $mQuery->where("#storeId = {$storeId}");
        $mQuery->orderBy('modifiedOn', 'DESC');
        $mQuery->show('modifiedOn');
        $res = md5(trim($mQuery->fetch()->modifiedOn));
    }


    /**
     * Има ли запазени движения в зоната
     *
     * @param $containerId
     * @return bool
     */
    public static function hasRackMovements($containerId)
    {
        // Има ли нагласени количества за артикула в зоната?
        $zQuery = rack_ZoneDetails::getQuery();
        $zQuery->XPR('movementQuantityRound', 'varchar', 'ROUND(COALESCE(#movementQuantity, 0), 3)');
        $zQuery->EXT('containerId', 'rack_Zones', 'externalName=containerId,externalKey=zoneId');
        $zQuery->where("#containerId = {$containerId} AND #movementQuantityRound != 0");
        $zQuery->show('id');
        $rec = $zQuery->fetch();

        return is_object($rec);
    }
}
