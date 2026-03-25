<?php


/**
 * Клас 'cat_products_Relations' - Релации между артикули
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class cat_products_Relations extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Релации между артикули';


    /**
     * Единично заглавие
     */
    public $singleTitle = 'Релация между артикули';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId1,productId2,relTypeId,state,createdOn,createdBy';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'cat_Wrapper, plg_RowTools2, plg_SaveAndNew, plg_Created, plg_State2, plg_Sorting';


    /**
     * Кой може да добавя
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да листва
     */
    public $canList = 'catEdit,ceo';


    /**
     * Кой може да редактира
     */
    public $canEdit = 'no_one';


    /**
     * Кой може да модифицира
     */
    public $canModify = 'catEdit,ceo';


    /**
     * Кой може да изтрива
     */
    public $canDelete = 'catEdit,ceo';


    /**
     * Кой може да променя състоянието
     */
    public $canChangestate = 'catEdit,ceo';


    /**
     * Предлог в формата за добавяне/редактиране
     */
    public $formTitlePreposition = 'на';


    /**
     * Брой записи на страница
     *
     * @var int
     */
    public $listItemsPerPage = 30;


    /**
     * Работен кеш
     */
    private $cacheRelations = array();


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('relTypeId', 'key(mvc=cat_RelationTypes,select=title,allowEmpty)', 'input,caption=Вид връзка,mandatory,silent,removeAndRefreshForm=productId2,oldFieldName=relType');
        $this->FLD('productId1', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,maxSuggestions=100,allowEmpty)', 'caption=Артикул 1,input=hidden,silent');
        $this->FLD('productId2', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,maxSuggestions=100,allowEmpty)', 'caption=Артикул 2,input=hidden');

        $this->setDbUnique('productId1,productId2,relTypeId');
        $this->setDbIndex('productId1');
        $this->setDbIndex('productId2');
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * Забранява изтриването на вече използвани сметки
     *
     * @param core_Mvc      $mvc
     * @param string        $requiredRoles
     * @param string        $action
     * @param stdClass|NULL $rec
     * @param int|NULL      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'modify' && isset($rec->productId)){
            $relationshipTypes = $mvc->getRelationshipOptions($rec->productId, null, 1);
            if(empty($relationshipTypes)){
                $requiredRoles = 'no_one';
            }

            if(isset($rec->id) && isset($rec->productId)){
                $fullRec = $mvc->fetch($rec->id, '*', false);
                if($rec->productId != $fullRec->productId1 && $rec->productId != $fullRec->productId2){
                    $requiredRoles = 'no_one';
                }
            }
        }
    }


    /**
     * Връща достъпните връзки за избор на артикула (кешира в хита)
     *
     * @param int|stdClass $productId - ид/запис на артикул
     * @param null|string $position   - left/right, null за и двете
     * @param null|int $limit         - лимит
     * @return array|mixed
     */
    private function getRelationshipOptions($productId, $position = null, $limit = null)
    {
        $productId = is_object($productId) ? $productId->id : $productId;

        if(!array_key_exists("{$productId}|{$position}|{$limit}", $this->cacheRelations)){
            $options = [];
            $groups = cat_Products::fetchField($productId, 'groups');
            $groupsArr = arr::make($groups, true);
            $groupsArrStr = implode(',', $groupsArr);

            // Ако артикулът има групи - извличат се връзките към него
            if(countR($groupsArr)) {
                $rQuery = cat_RelationTypes::getQuery();
                $rQuery->orderBy('saoOrder', 'ASC');
                if(!isset($position)){
                    $rQuery->setUnion("#group1GroupId IN ({$groupsArrStr})");
                    $rQuery->setUnion("#group2GroupId IN ({$groupsArrStr})");
                } elseif($position == 'left'){
                    $rQuery->where("#group1GroupId IN ({$groupsArrStr})");
                } elseif($position == 'right'){
                    $rQuery->where("#group2GroupId IN ({$groupsArrStr})");
                }
                if(isset($limit)){
                    $rQuery->limit($limit);
                }
                while($rRec = $rQuery->fetch()){
                    $options[$rRec->id] = $rRec->title;
                }
            }

            $this->cacheRelations["{$productId}|{$position}|{$limit}"] = $options;
        }

        return $this->cacheRelations["{$productId}|{$position}|{$limit}"];
    }


    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        $row->productId1 = cat_Products::getHyperlink($rec->productId1, true);
        $row->productId2 = cat_Products::getHyperlink($rec->productId2, true);

        // Добавяне на бутон за модифициране, заместващ стандартния за редакция
        $productId = ($rec->_masterProductId) ?? $rec->productId1;
        if($mvc->haveRightFor('modify', (object)array('id' => $rec->id, 'productId' => $productId))) {
            core_RowToolbar::createIfNotExists($row->_rowTools);
            $row->_rowTools->addLink('Редактиране', array($mvc, 'modify', 'id' => $rec->id, 'productId' => $productId, 'ret_url' => true), 'ef_icon=img/16/edit-icon.png, title=Редактиране на продуктова връзка');
        }

        $row->created = tr("|*{$row->createdOn} |от|* {$row->createdBy}");

        if(isset($fields['-detail'])){
            $groupId1 = $rec->group1GroupId;
            $groupId2 = $rec->group2GroupId;
        } else {
            $relType = cat_RelationTypes::fetch($rec->relTypeId);
            $groupId1 = $relType->group1GroupId;
            $groupId2 = $relType->group2GroupId;
        }

        list($rel1, $rel2) = explode(" ⬌ ", $row->relTypeId);
        $rel1 = ht::createHint($rel1, "Група|*: " . cat_Groups::getTitleById($groupId1), 'notice', false);
        $rel2 = ht::createHint($rel2, "Група|*: " . cat_Groups::getTitleById($groupId2), 'notice', false);
        $row->relTypeId = "{$rel1} ⬌ {$rel2}";
    }


    /**
     * Подготовка на връзките на артикула
     */
    public function prepareRelations_(&$data)
    {
        $relationshipTypes = $this->getRelationshipOptions($data->masterId, null, 1);
        $query = self::getQuery();
        $query->EXT('group1Name', 'cat_RelationTypes', 'externalName=group1Name,externalKey=relTypeId');
        $query->EXT('group2Name', 'cat_RelationTypes', 'externalName=group2Name,externalKey=relTypeId');
        $query->EXT('group1GroupId', 'cat_RelationTypes', 'externalName=group1GroupId,externalKey=relTypeId');
        $query->EXT('group2GroupId', 'cat_RelationTypes', 'externalName=group2GroupId,externalKey=relTypeId');
        $query->EXT('saoOrder', 'cat_RelationTypes', 'externalName=saoOrder,externalKey=relTypeId');

        $query->setUnion("#productId1 = {$data->masterId}");
        $query->setUnion("#productId2 = {$data->masterId}");
        $foundRecs = $query->fetchAll();

        if(!(countR($relationshipTypes) || countR($foundRecs))){
            $data->hide = true;
            return $data;
        }

        $data->TabCaption = 'Релации';
        $data->Tab = 'top';

        $prepareTab = Request::get($data->masterData->tabTopParam);
        if($prepareTab != 'Relations') {
            $data->hide = true;
            return;
        }

        // Подготовка на данните
        $data->recs = $data->rows = array();
        $this->prepareListFields($data);
        $fields = $this->selectFields();
        $fields['-list'] = true;
        $fields['-detail'] = true;

        foreach($foundRecs as $rec){
            $rec->_masterProductId = $data->masterId;
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = $this->recToVerbal($rec, $fields);
        }

        $groupedRows = array();

        foreach ($data->recs as $id => $rec) {
            if ($rec->productId1 == $data->masterId) {
                $otherGroupName = $rec->group2Name;
                $otherProductId = $rec->productId2;
            } elseif ($rec->productId2 == $data->masterId) {
                $otherGroupName = $rec->group1Name;
                $otherProductId = $rec->productId1;
            } else {
                continue;
            }

            if (!isset($groupedRows[$otherGroupName])) {
                $groupedRows[$otherGroupName] = array(
                    'groupName' => $otherGroupName,
                    'order' => $rec->saoOrder,
                    'rows' => array(),
                    'recs' => array(),
                    'productIds' => array(),
                    'count' => 0,
                );
            }

            $groupedRows[$otherGroupName]['rows'][$id] = $data->rows[$id];
            $groupedRows[$otherGroupName]['recs'][$id] = $rec;
            $groupedRows[$otherGroupName]['productIds'][$id] = $otherProductId;
            $groupedRows[$otherGroupName]['count']++;
        }

        // Подредба на групираните записи по група
        arr::sortObjects($groupedRows, 'order');
        $data->groupedRows = $groupedRows;
        if($this->haveRightFor('modify', (object)array('productId' => $data->masterId))){
            $data->addUrl = array($this, 'modify', 'productId' => $data->masterId, 'ret_url' => true);
        }

        return $data;
    }


    /**
     * Рендиране на връзките на артикула
     *
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public function renderRelations_(&$data)
    {
        if ($data->hide) return new core_ET("");

        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $title = tr('Релации с други артикули');
        $tpl->append($title, 'title');

        if (isset($data->addUrl)) {
            $addBtn = ht::createLink('', $data->addUrl, false, 'ef_icon=img/16/add.png,caption=Добавяне на нова продуктова връзка');
            $tpl->append($addBtn, 'title');
        }

        if (empty($data->groupedRows)) return $tpl;

        $tabKey = 'prodRelTabs_' . $data->masterId . '_' . substr(md5(implode('|', array_keys($data->groupedRows))), 0, 8);

        // Ключът е стабилен по masterId, за да се помни активният таб между reload/followRetUrl
        $storageKey = 'prodRelTabs_' . $data->masterId;
        $tabsTpl = new core_ET("
        <div id='[#TAB_KEY#]' class='product-rel-tabs-wrap product-rel-tabs-compact' data-storage-key='[#STORAGE_KEY#]'>
            <div class='product-rel-tabs-nav'>[#TAB_LINKS#]</div>
            <div class='product-rel-tabs-content'>[#TAB_PANES#]</div>
        </div>
    ");
        $tabsTpl->replace($tabKey, 'TAB_KEY');
        $tabsTpl->replace(ht::escapeAttr($storageKey), 'STORAGE_KEY');

        $tabLinks = '';
        $tabPanes = '';
        $tabN = 0;

        foreach ($data->groupedRows as $groupName => $groupData) {
            $tabN++;
            $paneId = $tabKey . '_pane_' . $tabN;
            $isActive = ($tabN == 1) ? ' active' : '';
            $count = $groupData['count'] ?? countR($groupData['rows']);

            $groupNameEsc = type_Varchar::escape($groupName);
            $groupNameAttr = ht::escapeAttr($groupName);

            $tabCaption = "{$groupNameEsc} <span class='product-rel-tab-count'>({$count})</span>";
            $tabLinks .= "<a href=\"#\" class=\"product-rel-tab{$isActive}\" data-pane=\"{$paneId}\" data-tab-key=\"{$groupNameAttr}\" onclick=\"return catProductsRelationsShowTab(this, '{$tabKey}');\">{$tabCaption}</a>";

            $tabData = clone $data;
            $tabData->rows = array();
            $tabData->recs = array();
            $tabData->listFields = arr::make('productId=Артикул,relTypeId=Релация,created=Създаване');

            foreach ($groupData['recs'] as $id => $rec) {
                $tabRec = is_object($rec) ? clone $rec : $rec;
                $tabRow = is_object($groupData['rows'][$id]) ? clone $groupData['rows'][$id] : $groupData['rows'][$id];

                // В колоната productId показваме "другия" артикул от релацията
                if ($rec->productId1 == $data->masterId) {
                    $tabRow->productId = $tabRow->productId2;
                    $tabRec->productId = $rec->productId2;
                } else {
                    $tabRow->productId = $tabRow->productId1;
                    $tabRec->productId = $rec->productId1;
                }

                $tabData->rows[$id] = $tabRow;
                $tabData->recs[$id] = $tabRec;
            }

            $paneTpl = new core_ET("<div id='[#PANE_ID#]' class='product-rel-tab-pane[#ACTIVE#]'>[#TABLE#]</div>");
            $paneTpl->replace($paneId, 'PANE_ID');
            $paneTpl->replace($isActive, 'ACTIVE');

            $listMvc = clone $this;
            $listMvc->FNC('productId', 'varchar', 'tdClass=leftCol');
            $listMvc->FNC('created', 'varchar', 'tdClass=small');

            $table = cls::get('core_TableView', array('mvc' => $listMvc));
            $this->invoke('BeforeRenderListTable', array($paneTpl, &$tabData));
            arr::sortObjects($tabData->rows, 'state', 'DESC');
            $details = $table->get($tabData->rows, $tabData->listFields);

            $paneTpl->replace($details, 'TABLE');
            $tabPanes .= $paneTpl->getContent();
        }

        $tabsTpl->replace($tabLinks, 'TAB_LINKS');
        $tabsTpl->replace($tabPanes, 'TAB_PANES');

        $tpl->append($tabsTpl, 'content');
        $tpl->push('cat/tpl/css/productRelStyles.scss', 'CSS');
        $tpl->push('cat/tpl/js/productRelationScripts.js', 'JS');
        jquery_Jquery::run($tpl, "catProductsRelationsInitTabsById('{$tabKey}');");

        return $tpl;
    }


    /**
     * Подготовка на филтър формата
     *
     * @param core_Mvc $mvc
     * @param object         $data
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->title = 'Търсене';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->FNC("productId", 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,maxSuggestions=100,allowEmpty)', 'caption=Артикул');

        $data->listFilter->showFields = 'productId,relTypeId';
        $data->listFilter->input(null, 'silent');
        $data->listFilter->input();

        if($filterRec = $data->listFilter->rec){
            if(!empty($filterRec->productId)){
                $data->query->where("#productId1 = {$filterRec->productId} OR #productId2 = {$filterRec->productId}");
            }

            if(!empty($filterRec->relTypeId)){
                $data->query->where("#relTypeId = {$filterRec->relTypeId}");
            }
        }

        $data->query->orderBy('id', 'DESC');
    }


    /**
     * Модифициране на записите
     */
    public function act_Modify()
    {
        $this->requireRightFor('modify');
        expect($productId = Request::get('productId', 'int'));
        $id = Request::get('id', 'int');
        $currentRec = isset($id) ? $this->fetch($id) : null;
        $this->requireRightFor('modify', (object)array('id' => $id, 'productId' => $productId));

        // Подготовка на формата
        $form = cls::get('core_Form');
        $form->title = core_Detail::getEditTitle('cat_Products', $productId, $this->singleTitle, $id);
        $form->FLD("productId", 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,maxSuggestions=100,allowEmpty)', 'caption=Артикул,silent,input=hidden');
        $form->FLD('relTypeId', 'key(mvc=cat_RelationTypes,select=title,allowEmpty)', 'input,caption=Релация,mandatory,silent,removeAndRefreshForm=otherProductId');
        if(!empty($currentRec->relTypeId)){
            $form->setDefault('relTypeId', $currentRec->relTypeId);
        }

        // Зареждане на достъпните релации
        $relationshipTypes = $this->getRelationshipOptions($productId);
        $form->setOptions('relTypeId', $relationshipTypes);
        if(countR($relationshipTypes) == 1){
            $form->setDefault('relTypeId', key($relationshipTypes));
            $form->setReadOnly('relTypeId');
        }

        $form->input(null, 'silent');
        $rec = &$form->rec;

        // Гледа се артикула от коя страна е, показва се другата
        $thisProductField = 'productId1';
        $otherProductField = 'productId2';
        if(isset($rec->relTypeId)){
            $relRec = cat_RelationTypes::fetch($rec->relTypeId);

            $relGroup1Arr = keylist::toArray($relRec->group1GroupId);
            $relGroup2Arr = keylist::toArray($relRec->group2GroupId);
            $productGroupArr = keylist::toArray(cat_Products::fetchField($rec->productId, 'groups'));
            $intersectedWithGroup1 = array_intersect_key($relGroup1Arr, $productGroupArr);
            $intersectedWithGroup2 = array_intersect_key($relGroup2Arr, $productGroupArr);

            $thisProductField = (countR($intersectedWithGroup2) && !countR($intersectedWithGroup1)) ? 'productId2' : 'productId1';
            $otherProductField = ($thisProductField == 'productId2') ? 'productId1' : 'productId2';

            $otherGroupIdFieldName = $otherProductField == 'productId1' ? 'group1GroupId' : 'group2GroupId';
            $otherGroupNameFieldName = $otherProductField == 'productId1' ? 'group1Name' : 'group2Name';
            $groupName = cat_Groups::getTitleById($relRec->{$otherGroupIdFieldName});
            $form->FLD("otherProductId", 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,maxSuggestions=100,allowEmpty)', "caption=|*{$relRec->{$otherGroupNameFieldName}}->|Група|*: {$groupName}->Артикул,placeholder=Всички от групата");
            $form->setFieldTypeParams('otherProductId', array('groups' => $relRec->{$otherGroupIdFieldName}));
            if(!empty($currentRec->{$otherProductField})){
                $form->setDefault('otherProductId', $currentRec->{$otherProductField});
                $form->setField('otherProductId', 'mandatory');
            }
        }
        $form->input();

        // При събмит на формата
        if ($form->isSubmitted()) {
            if($rec->productId == $rec->otherProductId){
                $form->setError('otherProductId', 'Не може да изберете същия артикул|*!');
            }

            // Ако е избран конкретен друг артикул - той, ако не всички от групата
            $relRec = cat_RelationTypes::fetch($rec->relTypeId);
            $otherProducts = array();
            if(empty($rec->otherProductId)){
                $pQuery = cat_Products::getQuery();
                $pQuery->where("#state NOT IN ('rejected', 'closed')");
                $pQuery->show('id');
                plg_ExpandInput::applyExtendedInputSearch('cat_Products', $pQuery, $relRec->{$otherGroupIdFieldName});
                while($pRec = $pQuery->fetch()){
                    $otherProducts[$pRec->id] = $pRec->id;
                }
            } else {
                $otherProducts[$rec->otherProductId] = $rec->otherProductId;
            }

            $count = count($otherProducts);
            if($count > 1){
                $form->setWarning('otherProductId', "Наистина ли искате да добавите релации към|* <b>{$count}</b> |артикула|*:");
            }

            if(!$form->gotErrors()){

                // Подготовка на записите
                $newRecs = array();
                $now = dt::now();
                $cu = core_Users::getCurrent();
                foreach ($otherProducts as $otherProductId) {
                    $newRec = (object)array("{$thisProductField}" => $productId, "{$otherProductField}" => $otherProductId, 'relTypeId' => $rec->relTypeId, 'createdOn' => $now, 'createdBy' => $cu);
                    $newRecs[] = $newRec;
                }

                // Ако е повече от 1 запис ще се добавят всичките, които не присъстват
                if(countR($newRecs) > 1){
                    $exQuery = $this->getQuery();
                    $exQuery->where("#{$thisProductField} = {$productId} AND #relTypeId = {$rec->relTypeId}");
                    $exRecs = $exQuery->fetchAll();

                    $synced = arr::syncArrays($newRecs, $exRecs, 'productId1,productId2,relTypeId', 'productId1,productId2,relTypeId');
                    $countNewRecs = countR($synced['insert']);
                    if($countNewRecs){
                        $this->saveArray($synced['insert']);
                    }

                    $msg = "Добавени връзки|*: <b>{$countNewRecs}</b>";
                } else {
                    $onlyRec = $newRecs[key($newRecs)];
                    $saveFields = null;
                    if(isset($id)){
                        $onlyRec->id = $id;
                        $saveFields = 'productId1,productId2,relTypeId';
                    }

                    $exRec = $fields = null;
                    if ($this->isUnique($onlyRec, $fields, $exRec)) {
                        $this->save($onlyRec, $saveFields);
                        $msg = isset($id) ? 'Релацията е редактирана|*!' : 'Добавена релация|*!';
                    } elseif(isset($id)) {
                        $this->delete($id);
                        $msg = 'Релацията вече съществува и текущият запис беше премахнат|*!';
                    } else {
                        $msg = 'Такава релация вече съществува|*!';
                    }
                }

                cat_Products::logWrite('Промяна на релации', $productId);
                followRetUrl(null, $msg);
            }
        }

        // Добавяне на бутони
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Запис на релациите');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');

        return $this->renderWrapping($form->renderHtml());
    }
}
