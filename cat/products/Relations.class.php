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
    public $listFields = 'productId1,productId2,relTypeId,state,modifiedOn,modifiedBy,createdOn,createdBy';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'cat_Wrapper, plg_RowTools2, plg_SaveAndNew, plg_Created, plg_State2, plg_Sorting, plg_Select, plg_Modified';


    /**
     * Кой може да добавя
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да инвалидира кеша
     */
    public $canInvalidate = 'catEdit,ceo';


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
     * Кеш на артикули, които са им променени релациите
     */
    protected $updatedProducts = array();


    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'productId1,productId2';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('relTypeId', 'key(mvc=cat_RelationTypes,select=title,allowEmpty)', 'input,caption=Вид,mandatory,silent,removeAndRefreshForm=productId2,oldFieldName=relType');
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
            $groupsArr = keylist::toArray($groups);
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
        $isExternal = Mode::is('wrapper', 'cms_page_External');
        if(!$isExternal){
            $row->productId1 = cat_Products::getHyperlink($rec->productId1, true);
            $row->productId2 = cat_Products::getHyperlink($rec->productId2, true);
        } else {
            $row->productId1 = cat_Products::getVerbal($rec->productId1, 'name');
            $row->productId2 = cat_Products::getVerbal($rec->productId2, 'name');
            unset($row->ROW_ATTR['class']);
        }

        // Добавяне на бутон за модифициране, заместващ стандартния за редакция
        $productId = ($rec->_masterProductId) ?? $rec->productId1;
        if(!Mode::is('noToolbar')){
            if($mvc->haveRightFor('modify', (object)array('id' => $rec->id, 'productId' => $productId))) {
                core_RowToolbar::createIfNotExists($row->_rowTools);
                $row->_rowTools->addLink('Редактиране', array($mvc, 'modify', 'id' => $rec->id, 'productId' => $productId, 'ret_url' => true), 'ef_icon=img/16/edit-icon.png, title=Редактиране на продуктова връзка');
            }
        }

        $row->created = tr("|*{$row->createdOn} |от|* {$row->createdBy}");
        $row->relTypeId = cat_RelationTypes::getRelTypeInfo($rec->relTypeId);
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
        $query->EXT('group1Info', 'cat_RelationTypes', 'externalName=group1Info,externalKey=relTypeId');
        $query->EXT('group2Info', 'cat_RelationTypes', 'externalName=group2Info,externalKey=relTypeId');
        $query->EXT('show1InExternal', 'cat_RelationTypes', 'externalName=show1InExternal,externalKey=relTypeId');
        $query->EXT('show2InExternal', 'cat_RelationTypes', 'externalName=show2InExternal,externalKey=relTypeId');
        $query->EXT('saoOrder', 'cat_RelationTypes', 'externalName=saoOrder,externalKey=relTypeId');

        $isExternal = Mode::is('wrapper', 'cms_page_External');

        if (Mode::is('renderExternalRelation')) {
            $query->where("#state = 'active'");
        }

        $query->setUnion("#productId1 = {$data->masterId}");
        $query->setUnion("#productId2 = {$data->masterId}");
        $query->orderBy('id', 'ASC');
        $foundRecs = $query->fetchAll();

        if (!(countR($relationshipTypes) || countR($foundRecs))) {
            $data->hide = true;
            return $data;
        }

        $data->TabCaption = 'Релации';
        $data->Tab = 'top';

        if (empty($data->forceCalc)) {
            $prepareTab = Request::get($data->masterData->tabTopParam);
            if ($prepareTab != 'Relations') {
                $data->hide = true;
                return;
            }
        }

        // Подготовка на суровите данни
        $data->recs = $data->rows = array();
        $this->prepareListFields($data);
        $fields = $this->selectFields();
        $fields['-list'] = true;
        $fields['-detail'] = true;

        foreach ($foundRecs as $rec) {
            $rec->_masterProductId = $data->masterId;
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = $this->recToVerbal($rec, $fields);
        }

        $groupedRows = array();

        foreach ($data->recs as $id => $rec) {
            if ($rec->productId1 == $data->masterId) {
                $otherGroupName = $rec->group2Name;
                $otherProductId = $rec->productId2;
                $groupNameInfo = $rec->group2Info;
                $showInExternal = $rec->show2InExternal;
            } elseif ($rec->productId2 == $data->masterId) {
                $otherGroupName = $rec->group1Name;
                $otherProductId = $rec->productId1;
                $groupNameInfo = $rec->group1Info;
                $showInExternal = $rec->show1InExternal;
            } else {
                continue;
            }

            // Ако не трябва да се показва във външната част - да не се показва
            if ($isExternal && $showInExternal !== 'yes') continue;

            $groupKey = $rec->relTypeId . '|' . $otherGroupName;

            if (!isset($groupedRows[$groupKey])) {
                $groupedRows[$groupKey] = array(
                    'groupId' => $rec->relTypeId,
                    'groupName' => core_Type::getByName('varchar')->toVerbal($otherGroupName),
                    'relType' => cat_RelationTypes::getRelTypeInfo($rec->relTypeId),
                    'order' => $rec->saoOrder,
                    'rows' => array(),
                    'recs' => array(),
                    'productIds' => array(),
                    'count' => 0,
                );

                if (!empty($groupNameInfo)) {
                    $groupedRows[$groupKey]['info'] = core_Type::getByName('richtext')->toVerbal($groupNameInfo);
                }
            }

            $groupedRows[$groupKey]['rows'][$id] = $data->rows[$id];
            $groupedRows[$groupKey]['recs'][$id] = $rec;
            $groupedRows[$groupKey]['productIds'][$id] = $otherProductId;
            $groupedRows[$groupKey]['count']++;
        }

        arr::sortObjects($groupedRows, 'order');
        $data->groupedRows = $groupedRows;

        if ($this->haveRightFor('modify', (object)array('productId' => $data->masterId))) {
            $data->addUrl = array($this, 'modify', 'productId' => $data->masterId, 'ret_url' => true);
        }

        if ($this->haveRightFor('invalidate', (object)array('productId' => $data->masterId))) {
            $data->invalidateUrl = array($this, 'invalidate', 'productId' => $data->masterId, 'ret_url' => true);
        }

        if (empty($data->groupedRows)) {
            return $data;
        }

        // Подготовка на табовете за рендиране
        $data->tabKey = 'prodRelTabs_' . $data->masterId . '_' . substr(md5(implode('|', array_keys($data->groupedRows))), 0, 8);
        $data->storageKey = 'prodRelTabs_' . $data->masterId;
        $data->imageSize = array('width' => 40, 'height' => 40);

        $isExternalKey = $isExternal ? ("{$isExternal}|" . cms_Domains::getPublicDomain()->id) : "{$isExternal}" . core_Lg::getCurrent();
        $cacheKey = "relTabs{$isExternalKey}";
        $cachedTabs = core_Cache::get("{$this->className}_{$data->masterId}", $cacheKey, 120);
        $isFromCache = is_array($cachedTabs);

        if (!$isFromCache) {
            $tabs = array();
            $activeTabInfo = '';

            $eshopProducts = $productIds = $productRecs = array();
            if ($isExternal) {
                foreach ($data->groupedRows as $groupData1) {
                    $productIds = array_merge($groupData1['productIds'], $productIds);
                }

                $productIds = array_unique($productIds);

                $pQuery = cat_Products::getQuery();
                if (countR($productIds)) {
                    $pQuery->in('id', $productIds);
                } else {
                    $pQuery->where('1=2');
                }
                $pQuery->show('code');
                $productRecs = $pQuery->fetchAll();

                $domainId = cms_Domains::getPublicDomain()->id;
                $eQuery = eshop_ProductDetails::getQuery();
                $eQuery->EXT('domainId', 'eshop_Products', 'externalName=domainId,externalKey=eshopProductId');
                $eQuery->where("#domainId = '{$domainId}' AND #state != 'closed'");
                if (countR($productIds)) {
                    $eQuery->in('productId', $productIds);
                } else {
                    $eQuery->where("1=2");
                }

                while ($eRec = $eQuery->fetch()) {
                    $eshopProducts[$eRec->productId] = $eRec;
                }
            }

            $tabN = 0;
            foreach ($data->groupedRows as $groupKey => $groupData) {
                $tabN++;
                $paneId = $data->tabKey . '_pane_' . $tabN;
                $count = $groupData['count'] ?? countR($groupData['rows']);

                $groupInfo = '';
                if (!$isExternal && !empty($groupData['relType'])) {
                    $groupInfo = $groupData['relType'];
                }
                if (!empty($groupData['info'])) {
                    $groupInfo .= ($groupInfo ? ' ' : '') . $groupData['info'];
                }

                if ($tabN == 1) {
                    $activeTabInfo = $groupInfo;
                }

                $tabData = new stdClass();
                $tabData->rows = array();
                $tabData->recs = array();
                $tabData->listFields = arr::make('productId=Артикул,created=Създаване');
                if ($isExternal) {
                    $tabData->listFields = arr::make('img=|*&nbsp;,productId=Артикул,code=Кат. №,price=Цена,btn=Поръчка');
                }

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

                    if ($isExternal) {
                        if (array_key_exists($tabRec->productId, $eshopProducts)) {
                            $eshopProductId = $eshopProducts[$tabRec->productId]->eshopProductId;
                            $tabRow->productId = eshop_ProductDetails::getPublicProductTitle($eshopProductId, $tabRec->productId, false);
                            $tabRow->productId = ht::createLink($tabRow->productId, eshop_Products::getUrl($eshopProductId));

                            $eshopRec = eshop_Products::fetch($eshopProductId);
                            $thumb = eshop_Products::getProductThumb($eshopRec, $data->imageSize['width'], $data->imageSize['height'], true);
                            $tabRow->img = $thumb->createImg(array('class' => 'eshopNearProductThumb'))->getContent();

                            $pRecClone = clone $eshopProducts[$tabRec->productId];
                            $minData = eshop_ProductDetails::getMinPackagingAndQuantity($pRecClone);
                            $pRecClone->packagingId = $minData['packagingId'];
                            $pRecClone->quantityInPack = $minData['quantity'];
                            $pRecClone->_listView = true;

                            $dRow = eshop_ProductDetails::getExternalRow($pRecClone);
                            $tabRow->price = $dRow->catalogPrice;
                            $tabRow->btn = $dRow->btn;
                        } else {
                            $thumb = new thumb_Img(getFullPath('eshop/img/noimage' . (cms_Content::getLang() == 'bg' ? 'bg' : 'en') . '.png'), $data->imageSize['width'], $data->imageSize['height'], 'path');
                            $preview = cat_Products::getParams($tabRec->productId, 'preview');
                            if (!empty($preview)) {
                                $path = fileman::fetchByFh($preview, 'path');
                                if (file_exists($path)) {
                                    $thumb = new thumb_Img($preview, $data->imageSize['width'], $data->imageSize['height']);
                                }
                            }
                            $tabRow->img = $thumb->createImg(array('class' => 'eshopNearProductThumb'))->getContent();
                        }

                        $tabRow->code = $productRecs[$tabRec->productId]->code ?? "Art{$tabRec->productId}";
                    } else {

                        $relQuery = cat_products_Relations::getQuery();
                        $relQuery->EXT('isSymmetric', 'cat_RelationTypes', 'externalName=isSymmetric,externalKey=relTypeId');
                        $relQuery->where("(#productId1 = {$tabRec->productId} OR #productId2 = {$tabRec->productId}) AND #isSymmetric = 'yes' AND #relTypeId != {$tabRec->relTypeId}");
                        $relQuery->XPR('count', 'int', "COUNT(#id)");
                        $relQuery->show('count, relTypeId');
                        $foundRec = $relQuery->fetch();

                        if($foundRec->count){
                            $countAnalogVerbal = core_Type::getByName('int')->toVerbal($foundRec->count);
                            $singleUrlArray = cat_Products::getSingleUrlArray($tabRec->productId);
                            if(countR($singleUrlArray)){
                                $containerId = cat_Products::fetchField($tabRec->productId, 'containerId');
                                $singleUrlArray["TabTop{$containerId}"] = 'Relations';
                            }

                            $suffix = $foundRec->count == 1 ? tr('аналог') : tr('аналози');
                            $tabRow->productId .= "  <span style='float:right;'> " . ht::createLink("[{$countAnalogVerbal}]", $singleUrlArray, false, "class=analogBtn,data-tab-name={$tabRec->productId}_{$foundRec->relTypeId}")->getContent() . " {$suffix}";
                        }
                    }

                    $tabData->rows[$id] = $tabRow;
                    $tabData->recs[$id] = $tabRec;
                }

                arr::sortObjects($tabData->rows, 'state', 'DESC');
                $tabData->listFields = core_TableView::filterEmptyColumns($tabData->rows, $tabData->listFields, 'price,btn');

                $tabs[] = array(
                    'uniqueStr' => "{$data->masterId}_{$groupData['groupId']}",
                    'groupKey' => $groupKey,
                    'groupName' => $groupData['groupName'],
                    'groupInfo' => $groupInfo,
                    'count' => $count,
                    'paneId' => $paneId,
                    'isActive' => ($tabN == 1),
                    'tabData' => $tabData,
                );
            }

            $cachedTabs = array(
                'tabs' => $tabs,
                'activeTabInfo' => $activeTabInfo,
            );

            core_Cache::set("{$this->className}_{$data->masterId}", $cacheKey, $cachedTabs, 120);
        }

        $data->tabs = $cachedTabs['tabs'];
        $data->activeTabInfo = $cachedTabs['activeTabInfo'];

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
        $tpl = new core_ET("[#content#]");
        if ($data->hide) return $tpl;

        $isExternal = Mode::is('wrapper', 'cms_page_External');

        if (!$isExternal) {
            $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
            $title = tr('Релации с други артикули');
            $tpl->append($title, 'title');

            if (isset($data->addUrl)) {
                $addBtn = ht::createLink('', $data->addUrl, false, 'ef_icon=img/16/add.png,caption=Добавяне на нова продуктова връзка');
                $tpl->append($addBtn, 'title');
            }

            if (isset($data->invalidateUrl)) {
                $invalidateBtn = ht::createLink('', $data->invalidateUrl, false, 'ef_icon=img/16/arrow_refresh.png,caption=Опресняване на кешираните данни');
                $tpl->append($invalidateBtn, 'title');
            }
        }

        if (empty($data->tabs)) {
            $tpl->append("<i>" . tr("Няма") . "</i>", 'content');

            return $tpl;
        }

        $tabsTpl = new core_ET("
        <div id='[#TAB_KEY#]' class='product-rel-tabs-compact' data-storage-key='[#STORAGE_KEY#]'>
            <div class='tab-row'>[#TAB_LINKS#]</div>
            <div class='product-rel-tabs-info'>[#ACTIVE_TAB_INFO#]</div>
            <div class='product-rel-tabs-content'>[#TAB_PANES#]</div>
        </div>
    ");
        $tabsTpl->replace($data->tabKey, 'TAB_KEY');
        $tabsTpl->replace($data->storageKey, 'STORAGE_KEY');

        $tabLinks = '';
        $tabPanes = '';

        foreach ($data->tabs as $tab) {
            $isActiveClass = $tab['isActive'] ? ' active' : '';
            $groupNameAttr = ht::escapeAttr($tab['groupName']);
            $tabInfoAttr = ht::escapeAttr($tab['groupInfo']);
            $tabCaption = "{$tab['groupName']} <span class='product-rel-tab-count'>({$tab['count']})</span>";
            $tabLinks .= "<a href=\"#\" class=\"product-rel-tab tab{$isActiveClass}\" data-pane=\"{$tab['paneId']}\" data-tab-key=\"{$groupNameAttr}\" data-info=\"{$tabInfoAttr}\" data-unique=\"{$tab['uniqueStr']}\" onclick=\"return catProductsRelationsShowTab(this, '{$data->tabKey}');\">{$tabCaption}</a>";

            $paneTpl = new core_ET("<div id='[#PANE_ID#]' class='product-rel-tab-pane[#ACTIVE#]'>[#TABLE#]</div>");
            $paneTpl->replace($tab['paneId'], 'PANE_ID');
            $paneTpl->replace($isActiveClass, 'ACTIVE');

            $listMvc = clone $this;
            $listMvc->FNC('productId', 'varchar', 'tdClass=leftCol relProductCol');
            $listMvc->FNC('created', 'varchar', 'tdClass=small relCol');
            $listMvc->FNC('code', 'varchar', 'tdClass=small relCol');
            $listMvc->FNC('price', 'varchar', 'tdClass=small relCol');
            $listMvc->FNC('btn', 'varchar', 'tdClass=small relCol');
            $listMvc->FNC('img', 'varchar', 'tdClass=small relCol relImgCol');
            $listMvc->FNC('analogs', 'varchar', 'tdClass=small-field');

            $table = cls::get('core_TableView', array('mvc' => $listMvc));
            $tabData = $tab['tabData'];

            $this->invoke('BeforeRenderListTable', array($paneTpl, &$tabData));
            $details = $table->get($tabData->rows, $tabData->listFields);
            $paneTpl->append($details, 'TABLE');

            $tabPanes .= $paneTpl->getContent();
        }

        $tabsTpl->replace($data->activeTabInfo, 'ACTIVE_TAB_INFO');
        $tabsTpl->replace($tabLinks, 'TAB_LINKS');
        $tabsTpl->replace($tabPanes, 'TAB_PANES');

        $tpl->append($tabsTpl, 'content');
        $tpl->push('cat/tpl/css/productRelStyles.scss', 'CSS');
        $tpl->push('cat/tpl/js/productRelationScripts.js', 'JS');
        jquery_Jquery::run($tpl, "catProductsRelationsInitTabsById('{$data->tabKey}');");
        jquery_Jquery::run($tpl, 'makeTooltipFromTitle();');

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
        $productsNotAllowed = array($productId => $productId);

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

            // Ако релацията е симетрична се проверява дали двата артикула вече не са обвързани
            if($relRec->isSymmetric == 'yes'){
                $exQuery = $this->getQuery();
                $exQuery->where("#{$otherProductField} = {$productId} AND #relTypeId = {$rec->relTypeId}");
                $exQuery->show("{$thisProductField}");
                $productsNotAllowed = arr::extractValuesFromArray($exQuery->fetchAll(), $thisProductField);
            }

            // Ако се редактира запис - полето е ключ само за промяна на този запис
            if(!empty($currentRec->{$otherProductField})){
                $form->FLD("otherProductId", 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,maxSuggestions=100,allowEmpty)', "caption=|*{$relRec->{$otherGroupNameFieldName}}->|Група|*: {$groupName}->Артикул,placeholder=Всички от групата");
                $form->setFieldTypeParams('otherProductId', array('groups' => $relRec->{$otherGroupIdFieldName}));
                $form->setDefault('otherProductId', $currentRec->{$otherProductField});
                $form->setField('otherProductId', 'mandatory');
            } else {
                // Ако не се редактира запис то се показват недобавените артикули от групата
                $form->FLD("otherProducts", 'keylist(mvc=cat_Products,select=name,allowEmpty)', "caption=|*{$relRec->{$otherGroupNameFieldName}}->|Група|*: {$groupName}->Артикул,placeholder=Всички от групата");

                // Кои са вече добавените артикули - тях ги махаме
                $exQuery = self::getQuery();
                $exQuery->where("#relTypeId = {$relRec->id} AND #{$thisProductField} = {$productId}");
                $exQuery->show($otherProductField);
                $productsNotAllowed += arr::extractValuesFromArray($exQuery->fetchAll(), $otherProductField);

                // Зареждане само на артикулите от тази група
                $otherProductOptions = array();
                $pQuery = cat_Products::getQuery();
                $pQuery->where("#state = 'active'");
                $pQuery->show('id,isPublic,name,nameEn,code');
                plg_ExpandInput::applyExtendedInputSearch('cat_Products', $pQuery, $relRec->{$otherGroupIdFieldName});
                $pQuery->notIn('id', $productsNotAllowed);
                while($pRec = $pQuery->fetch()) {
                    $otherProductOptions[$pRec->id] = cat_Products::getRecTitle($pRec, false);
                }
                if(countR($otherProductOptions)){
                    $form->setSuggestions('otherProducts', array('' => '') + $otherProductOptions);
                } else {
                    $form->setReadOnly('otherProducts');
                    $form->setError('otherProducts', 'Всички артикули от групата са вече добавени|*!');
                }
                $form->rec->_allProductsInGroup = $otherProductOptions;
            }
        }
        $form->input();

        // При събмит на формата
        if ($form->isSubmitted()) {
            if($rec->productId == $rec->otherProductId){
                $form->setError('otherProductId', 'Не може да изберете същия артикул|*!');
            }

            // Ако е избран конкретен друг артикул - той, ако не всички от групата
            $otherProducts = array();

            if(!empty($rec->otherProducts)){
                $otherProducts = keylist::toArray($rec->otherProducts);
            } elseif(!empty($rec->otherProductId)){
                $otherProducts[$rec->otherProductId] = $rec->otherProductId;
                if(array_key_exists($rec->otherProductId, $productsNotAllowed)){
                    $form->setError('otherProductId', "Двата артикула са вече свързани в симетрична релация|*!");
                }
            } elseif(!empty($rec->_allProductsInGroup)){
                $otherProducts = array_combine(array_keys($rec->_allProductsInGroup), array_keys($rec->_allProductsInGroup));
            }

            $count = count($otherProducts);
            if($count > 100){
                $form->setWarning('otherProductId', "Не може да добавите повече от|* 100 |артикула|*!");
            }

            if(!$form->gotErrors()){
                // Подготовка на записите
                $this->updatedProducts[$productId] = $productId;
                $newRecs = array();
                $now = dt::now();
                $cu = core_Users::getCurrent();
                foreach ($otherProducts as $otherProductId) {
                    $this->updatedProducts[$otherProductId] = $otherProductId;
                    $newRec = (object)array("{$thisProductField}" => $productId, "{$otherProductField}" => $otherProductId, 'relTypeId' => $rec->relTypeId, 'createdOn' => $now, 'createdBy' => $cu, 'modifiedOn' => $now, 'modifiedBy' => $cu);
                    $newRecs[] = $newRec;
                }

                // Ако е повече от 1 запис ще се добавят всичките, които не присъстват
                $count = countR($newRecs);
                if($count > 1){
                    $exQuery = $this->getQuery();
                    $exQuery->where("#{$thisProductField} = {$productId} AND #relTypeId = {$rec->relTypeId}");
                    $exRecs = $exQuery->fetchAll();

                    $synced = arr::syncArrays($newRecs, $exRecs, 'productId1,productId2,relTypeId', 'productId1,productId2,relTypeId');
                    $countNewRecs = countR($synced['insert']);
                    if($countNewRecs){
                        $this->saveArray($synced['insert']);
                    }

                    $msg = "Добавени връзки|*: <b>{$countNewRecs}</b>";
                } elseif($count == 1) {
                    $onlyRec = $newRecs[key($newRecs)];
                    $saveFields = null;
                    if(isset($id)){
                        $onlyRec->id = $id;
                        $saveFields = 'productId1,productId2,relTypeId,modifiedOn,modifiedBy';
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
                } else {
                    $msg = 'Не са добавени нови релации|*!';
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


    /**
     * Подготовка за показването на връзките във външната част
     *
     * @param int $productId            - ид на артикул
     * @param stdClass $eshopProductRec - ид на е-артикул
     * @return stdClass $data           - подготовените данни
     */
    public static function prepareExternalData($productId, $eshopProductRec)
    {
        $rec = cat_Products::fetch($productId);
        $data = new stdClass();
        $data->masterMvc = cls::get('cat_Products');
        $data->masterId = $productId;
        $data->masterData = new stdClass();
        $data->masterData->rec = $rec;
        $data->masterData->row = cat_Products::recToVerbal($rec);
        $data->masterData->tabTopParam = "TabTop{$rec->containerId}";
        $data->forceCalc = true;

        $me = cls::get(get_called_class());
        Mode::push('noToolbar', true);
        Mode::push('renderExternalRelation', true);
        $me->prepareRelations($data);
        Mode::pop('renderExternalRelation');
        Mode::pop('noToolbar', true);

        return $data;
    }


    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = null, $mode = null)
    {
        // Кои артикули с променени - заопашават се за инвалидиране на кеша на табовете
        $mvc->updatedProducts[$rec->productId1] = $rec->productId1;
        $mvc->updatedProducts[$rec->productId2] = $rec->productId2;
    }


    /**
     * След изтриване на запис
     */
    protected static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
        // Кои артикули са с изтрити релации - заопашават се за инвалидиране на кеша на табовете
        foreach ($query->getDeletedRecs() as $rec) {
            $mvc->updatedProducts[$rec->productId1] = $rec->productId1;
            $mvc->updatedProducts[$rec->productId2] = $rec->productId2;
        }
    }


    /**
     * Изчиства записите, заопашени за запис
     */
    public static function on_Shutdown($mvc)
    {
        // Триене на кеша на артикулите с променени връзки
        if(countR($mvc->updatedProducts)){
            foreach ($mvc->updatedProducts as $productId){
                core_Cache::removeByType("cat_products_Relations_{$productId}");
            }
        }
    }


    /**
     * Модифициране на записите
     */
    public function act_Invalidate()
    {
        $this->requireRightFor('invalidate');
        expect($productId = Request::get('productId', 'int'));
        core_Cache::removeByType("cat_products_Relations_{$productId}");

        followRetUrl(null, 'Данните за релациите са опреснени');
    }
}
