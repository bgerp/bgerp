<?php


/**
 * Клас 'store_InventoryNoteSummary'
 *
 * Детайли на мениджър на детайлите на протоколите за инвентаризация (@see store_InventoryNotes)
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_InventoryNoteSummary extends doc_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на протокола за инвентаризация';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'артикул за опис';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'noteId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_GroupByField, store_Wrapper,plg_AlignDecimals2,plg_Search,plg_Created';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'productId';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има достъп до листовия изглед
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой има право да променя начисляването?
     */
    public $canSetresponsibleperson = 'ceo, storeMaster, inventory';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'code=Код, productId, measureId=Мярка,blQuantity, quantity=Количество->Установено,delta,charge,groupName';
    
    
    /**
     * По кое поле да се групира
     */
    public $groupByField = 'groupName';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'groupName,charge';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'blQuantity';
    
    
    /**
     * Брой записи на страница
     *
     * @var int
     */
    public $listItemsPerPage = null;
    
    
    /**
     * Полета, които се експортват
     */
    public $exportToMaster = 'blQuantity, quantity, delta';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=store_InventoryNotes)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Продукт,mandatory,silent,removeAndRefreshForm=groups,tdClass=large-field');
        $this->FLD('blQuantity', 'double', 'caption=Количество->Очаквано,input=none,notNull,value=0');
        $this->FLD('quantity', 'double(smartRound)', 'caption=Количество->Установено,input=none,size=100');
        $this->FNC('delta', 'double', 'caption=Количество->Разлика');
        $this->FLD('groups', 'keylist(mvc=cat_Groups,select=name)', 'caption=Групи');
        $this->FLD('charge', 'user', 'caption=Начет');
        $this->FLD('modifiedOn', 'datetime(format=smartTime)', 'caption=Модифициране||Modified->На,input=none,forceField');
        
        $this->setDbUnique('noteId,productId');
    }
    
    
    /**
     * Подготовка на Детайлите
     */
    public function prepareDetail_($data)
    {
        if (!Mode::is('printing')) {
            $data->TabCaption = 'Обобщение';
            $data->Tab = 'top';
        }
        
        $tab = Request::get($data->masterData->tabTopParam, 'varchar');
        if ($tab == '' || $tab == get_called_class() || Mode::is('printing')) {
            parent::prepareDetail_($data);
        }
    }
    
    
    /**
     * Заявка за редовете за начет към МОЛ
     *
     * @param int $noteId - ид на протокол
     *
     * @return core_Query $query - заявка
     */
    public static function getResponsibleRecsQuery($noteId)
    {
        // Връщаме заявка селектираща само редовете с количество, и избран МОЛ за начет
        $query = static::getQuery();
        $query->where("#noteId = {$noteId}");
        $query->where('#quantity IS NOT NULL');
        $query->where('#charge IS NOT NULL');
        $query->XPR('diff', 'double', 'ROUND(#quantity - #blQuantity, 2)');
        $query->where('#diff < 0');
        
        return $query;
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     */
    protected static function on_CalcDelta(core_Mvc $mvc, $rec)
    {
        if (!isset($rec->blQuantity) || !isset($rec->quantity)) {
            
            return;
        }
        
        $rec->delta = round($rec->quantity - $rec->blQuantity, 6);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->code = $rec->verbalCode;
        $row->ROW_ATTR['id'] = "row->{$rec->id}";
        
        if (!Mode::isReadOnly()) {
            $row->productId = cat_Products::getVerbal($rec->productId, 'name');
            $row->productId = ht::createLinkRef($row->productId, cat_Products::getSingleUrlArray($rec->productId));
        }
        
        // Записваме датата на модифициране в чист вид за сравнение при инвалидирането на кеширането
        $row->groupName = $rec->groupName;
        
        if (Mode::is('blank')) {
            $packs = cat_Products::getPacks($rec->productId);
            $measureId = key($packs);
        } else {
            $measureId = cat_Products::fetchField($rec->productId, 'measureId');
        }
        
        $row->measureId = cat_UoM::getShortName($measureId);
        
        if (!isset($rec->quantity) && !Mode::is('printing')) {
            $row->ROW_ATTR['class'] = ' note-product-row-no-quantity';
            
           if (store_InventoryNoteDetails::haveRightFor('add', (object) array('noteId' => $rec->noteId, 'productId' => $rec->productId))) {
               
               
               $url = array('store_InventoryNoteDetails', 'add', 'noteId' => $rec->noteId, 'productId' => $rec->productId, 'ret_url' => array('store_InventoryNotes', 'single', $rec->noteId));
               $row->quantity = ht::createLink('', $url, false, 'ef_icon=img/16/edit.png,title=Задаване на установено количество');
            }
        }
    }
    
    
    /**
     * Рендира разликата
     *
     * @param stdClass $rec - запис
     *
     * @return core_ET - стойноста на клетката
     */
    public static function renderDeltaCell($rec)
    {
        $rec = static::fetchRec($rec);
        $Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
        $deltaRow = $Double->toVerbal($rec->delta);
        if ($rec->delta > 0) {
            $deltaRow = "+{$deltaRow}";
        }
        
        $class = ($rec->delta < 0) ? 'red' : (($rec->delta > 0) ? 'green' : 'quiet');
        $deltaRow = "<span class='{$class}'>{$deltaRow}</span>";
        
        return new core_ET($deltaRow);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'setresponsibleperson' && isset($rec)) {
            $requiredRoles = store_InventoryNotes::getRequiredRoles('edit', $rec->noteId);
            
            if (!isset($rec->delta) || (isset($rec->delta) && $rec->delta >= 0)) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * След рендиране на името на групата
     *
     * @see plg_GroupByField
     *
     * @param core_Mvc $mvc             - модела
     * @param string   $res             - името на групата
     * @param stdClass $data            - датата
     * @param string   $groupName       - вътршното представяне на групата
     * @param string   $groupVerbalName - текущото вербално име на групата
     */
    protected static function on_AfterRenderGroupName($mvc, &$res, $data, $groupName, $groupVerbalName)
    {
        $blankUrl = array();
        $masterRec = $data->masterData->rec;
        if ($masterRec->state != 'rejected') {
            if (!Mode::is('printing') && !Mode::is('text', 'xhtml') && !Mode::is('pdf') && !Mode::is('blank')) {
                if (store_InventoryNotes::haveRightFor('single', $masterRec)) {
                    $blankUrl = array('store_InventoryNotes', 'getBlankForm', $masterRec->id, 'ret_url' => true, "{$mvc->groupByField}" => $groupName);
                }
            }
        }
        
        // Ако можем добавяме към името на раздела бутон за принтиране на бланка само за артикулите с въпросната група
        if (countR($blankUrl)) {
            $title = "Принтиране на бланка за|* '{$groupName}'";
            $link = ht::createLink('', $blankUrl, false, "target=_blank,title={$title},ef_icon=img/16/print_go.png");
            $res .= " <span style='margin-left:7px'>{$link}</span>";
        }
    }
    
    
    /**
     * Помощна ф-я връщаща линкове към заявките в които участва артикула
     *
     * @param datetime $valior
     * @param int  $storeId
     *
     * @return array $res
     */
    private static function getPendingDocuments($valior, $storeId)
    {
        $res = array();

        $valior = dt::addDays(-1, $valior);
        $valior = dt::verbal2mysql($valior, false);
        $query = store_StockPlanning::getQuery();
        $query->where("#storeId = {$storeId} AND #date <= '{$valior}'");
        while ($sourceRec = $query->fetch()) {
            $Source = cls::get($sourceRec->sourceClassId);
            $state = $Source->fetchField($sourceRec->sourceId, 'state');
            if(in_array($state, array('pending', 'draft'))){
                $link = cls::haveInterface('doc_DocumentIntf', $Source) ? $Source->getLink($sourceRec->sourceId, 0) : $Source->getHyperlink($sourceRec->sourceId, true);
                $res[$sourceRec->productId][] = $link;
            }
        }

        return $res;
    }
    
    
    /**
     *  Преди рендиране на лист таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        if (!$data->rows) {
            
            return;
        }
        
        $data->listTableMvc->FLD('code', 'varchar', 'tdClass=small-field nowrap');
        $data->listTableMvc->FLD('measureId', 'varchar', 'tdClass=small-field nowrap');
        $data->listTableMvc->setField('charge', 'tdClass=charge-td');
        $masterRec = $data->masterData->rec;
        
        // Намиране на всички заявки ако има
        $pendingDocuments = self::getPendingDocuments($masterRec->valior, $masterRec->storeId);
        
        $filterByGroup = false;
        if (Mode::get('blank')) {
            $data->listTableMvc->FLD('quantitySum', 'varchar');
            $data->listTableMvc->setField('quantitySum', 'tdClass=large-field');
            
            $filterName = Request::get($mvc->groupByField, 'varchar');
            if ($filterName) {
                $filterByGroup = true;
            }
        } else {
            $data->listTableMvc->FLD('quantitySum', 'double');
            if (!Mode::get('printing')) {
                $Pager = cls::get('core_Pager', array('itemsPerPage' => 200));
                $Pager->setPageVar($data->masterMvc->className, $data->masterId);
                $Pager->itemsCount = countR($data->rows);
                $data->pager = $Pager;
            }
        }
        
        foreach ($data->rows as $id => &$row) {
            $rec = &$data->recs[$id];
            
            if (isset($rec)) {
                $row->delta = static::renderDeltaCell($rec);
                $row->delta = "<div id='delta{$rec->id}'>{$row->delta}</div>";
            }
            
            if (isset($data->pager) && !$data->pager->isOnPage()) {
                unset($data->rows[$id]);
                continue;
            }
            
            if ($filterByGroup === true && isset($filterName)) {
                if ((!$row instanceof core_ET) && isset($rec)) {
                    if ($rec->{$mvc->groupByField} != $filterName) {
                        unset($data->rows[$id]);
                        continue;
                    }
                } else {
                    $fId = "|{$filterName}";
                    if ($id != $fId) {
                        unset($data->rows[$id]);
                        continue;
                    }
                }
            }
            
            if (isset($rec) && $rec->isBatch !== true) {
                $row->charge = static::renderCharge($rec);

                // Рендиране на заявките, в които участва артикула
                if (countR($pendingDocuments[$rec->productId]) && !Mode::isReadOnly()) {

                    $btn = ht::createFnBtn('', null, null, array('class' => 'more-btn linkWithIcon warningContextMenu', 'title' => 'Документи, които са запазили количества от артикула'));
                    $bodyLayout = new ET("<div class='clearfix21 modal-toolbar'>[#LI#]</div>");
                    foreach ($pendingDocuments[$rec->productId] as $link) {
                        $block = new core_ET("<div style='padding: 3px 5px 2px 0px;'>[#1#]</div>");
                        $block->replace($link, '1');
                        $block->removeBlocksAndPlaces();
                        $bodyLayout->append($block, 'LI');
                    }

                    $layoutHtml = new core_ET('[#btn#][#text#][#productId#]');
                    $layoutHtml->replace($btn, 'btn');
                    $layoutHtml->replace($bodyLayout, 'text');
                    $layoutHtml->replace($row->productId, 'productId');
                    $layoutHtml->removeBlocksAndPlaces();
                    $row->productId = $layoutHtml;
                }
            }
            
            $row->blQuantity = ht::styleIfNegative($row->blQuantity, $rec->blQuantity);
        }
        
        plg_RowTools2::on_BeforeRenderListTable($mvc, $res, $data);
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        if ($data->masterData->rec->state == 'rejected' || Mode::isReadOnly()) {
            
            return;
        }
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png,title=Филтриране на данните');
        $data->listFilter->FLD('threadId', 'key(mvc=doc_Threads)', 'input=hidden');
        $data->listFilter->setDefault('threadId', $data->masterData->rec->threadId);
        $data->listFilter->showFields = 'search';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->input();
        
        // При активен протокол не се показват непроменяните редове
        if($data->masterData->rec->state == 'active'){
            $data->query->where('#quantity IS NOT NULL');
        }
    }
    
    
    /**
     * Форсира запис
     *
     * @param int $noteId    - ид на протокол
     * @param int $productId - ид на артикула
     *
     * @return int - ид на форсирания запис
     */
    public static function force($noteId, $productId)
    {
        // Ако има запис връщаме го
        if ($rec = store_InventoryNoteSummary::fetch("#noteId = {$noteId} AND #productId = {$productId}")) {
            
            return $rec->id;
        }
        
        $sRec = (object) array('noteId' => $noteId,
            'productId' => $productId,
            'groups' => cat_Products::fetchField($productId, 'groups'));
        
        // Ако няма запис, създаваме го
        return self::save($sRec);
    }
    
    
    /**
     * Екшън за смяна на начисляването
     */
    public function act_SetResponsibleperson()
    {
        $this->requireRightFor('setresponsibleperson');
        
        if (!$id = Request::get('id', 'int')) {
            core_Statuses::newStatus('|Невалиден ред|*!', 'error');
            
            return status_Messages::returnStatusesArray();
        }
        
        if (!$rec = $this->fetch($id)) {
            core_Statuses::newStatus('|Невалиден ред|*!', 'error');
            
            return status_Messages::returnStatusesArray();
        }
        
        $userId = Request::get('userId', 'int');
        $this->requireRightFor('setresponsibleperson', $rec);
        if (!$userId) {
            $userId = null;
        }
        
        // Сменяме начина на начисляване
        $rec->charge = $userId;
        $rec->modifiedOn = dt::now();
        
        // Опитваме се да запишем
        if ($this->save($rec)) {
            
            // Ако сме в AJAX режим
            if (Request::get('ajax_mode')) {
                
                // Заместваме клетката по AJAX за да визуализираме промяната
                $resObj = new stdClass();
                $resObj->func = 'html';
                $resObj->arg = array('id' => "charge{$rec->id}", 'html' => static::renderCharge($rec), 'replace' => true);
                
                $res = array_merge(array($resObj));
                
                return $res;
            }
        }
        
        redirect(array('store_InventoryNotes', 'single', $rec->noteId));
    }
    
    
    /**
     * Преди подготовката на полетата за листовия изглед
     */
    protected static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
        if (Mode::get('blank')) {
            unset($data->listFields['delta']);
            unset($data->listFields['charge']);
            unset($data->listFields['quantity']);
            
            if (Request::get('showBlQuantities') !== '1') {
                unset($data->listFields['blQuantity']);
            }
            
            $data->listFields['quantitySum'] = 'Количество';
        }
    }
    
    
    /**
     * След извличане на записите от базата данни
     */
    protected static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
        if (!countR($data->recs)) {
            
            return;
        }

        // Извличаме наведнъж записите за всички артикули в протокола
        $allProducts =  arr::extractValuesFromArray($data->recs, 'productId');
        $productIds = array_values($allProducts);
        
        $pQuery = cat_Products::getQuery();
        $pQuery->show('isPublic,code,name,createdOn,nameEn');
        $pQuery->in('id', $productIds);
        $tmpRecs = $pQuery->fetchAll();
        
        // Добавяме в река данни така че да ни е по-лесно за филтриране
        $Varchar = core_Type::getByName('varchar');
        foreach ($data->recs as &$rec) {
            
            // Взимаме записа от кеша
            $pRec = $tmpRecs[$rec->productId];
            cat_Products::setCodeIfEmpty($pRec);

            // Вербализираме и нормализираме кода, за да можем да подредим по него
            $rec->orderCode = $pRec->code;
            $rec->verbalCode = $Varchar->toVerbal($pRec->code);
            $rec->orderName = $Varchar->toVerbal(cat_Products::getDisplayName($pRec));
        }
    }
    
    
    /**
     * Рендира колонката за начисляване на МОЛ-а
     */
    public static function renderCharge($rec)
    {
        $rec = static::fetchRec($rec);
        $charge = '';
        
        $masterRec = store_InventoryNotes::fetch($rec->noteId);
        
        $responsibles = array();
        $chiefs = keylist::toArray(store_Stores::fetchField($masterRec->storeId, 'chiefs'));
        $rec->charge = self::fetchField($rec->id, 'charge');
        
        if (isset($rec->charge)) {
            $chiefs[$rec->charge] = $rec->charge;
        }
        
        foreach ($chiefs as $c) {
            $responsibles[$c] = core_Users::getVerbal($c, 'nick');
        }
        
        $responsibles = array('' => '') + $responsibles;
        
        if ($masterRec->state == 'draft') {
            if (!Mode::isReadOnly() && !Mode::is('blank')) {
                if (static::haveRightFor('setresponsibleperson', $rec)) {
                    $attr = array();
                    $attr['class'] = 'toggle-charge';
                    $attr['data-url'] = toUrl(array('store_InventoryNoteSummary', 'setResponsiblePerson', $rec->id), 'local');
                    $attr['title'] = 'Избор на материално отговорно лице';
                    
                    $charge = ht::createSelect('charge', $responsibles, $rec->charge, $attr);
                    $charge->removePlaces();
                }
            }
        } else {
            if ((isset($rec->delta) && $rec->delta <= 0 && isset($rec->charge))) {
                $charge = crm_Profiles::createLink($rec->charge);
            }
        }
        
        if ($masterRec->state == 'draft' && $charge !== '') {
            $charge = "<span id='charge{$rec->id}'>{$charge}</span>";
        }
        
        return $charge;
    }
    
    
    /**
     * Филтриране на записите по подходящ начин
     * 
     * @param mixed $selectedGroups
     * @param array $recs
     * @param string $codeFld
     * @param string $nameFld
     * @param string $groupFld
     * @param boolean $expand
     * @return void
     */
    public static function filterRecs($selectedGroups, &$recs, $codeFld = 'orderCode', $nameFld = 'orderName', $groupFld = 'groups', $expand = false)
    {
        // Ако няма записи не правим нищо
        if (!is_array($recs)) {
            
            return;
        }
        
        $ordered = array();
        
        // Вербализираме и подреждаме групите
        $groups = keylist::toArray($selectedGroups);
        cls::get('cat_Groups')->invoke('AfterMakeArray4Select', array(&$groups));
       
        // За всеки маркер
        foreach ($groups as $grId => $groupName) {
            
            if($expand === true){
                $desc = cat_Groups::getDescendantArray($grId);
                $desc = keylist::toArray($desc);
            } else {
                $desc = array($grId => $grId);
            }
            cls::get('cat_Groups')->invoke('AfterMakeArray4Select', array(&$desc));
            
            uasort($desc, function($a, $b) {
                return mb_strlen($b) - mb_strlen($a);
            });
            
            foreach ($desc as $dId => $dName){
                
                // Отделяме тези записи, които съдържат текущия маркер
                $res = array_filter($recs, function (&$e) use ($dId, $dName, $groupFld) {
                    
                    if (keylist::isIn($dId, $e->{$groupFld})) {
                        $e->groupName = $dName;
                        $e->_groupId = $dId;
                        return true;
                    }
                    
                    return false;
                });
               
                // Ако има намерени резултати
                if (countR($res) && is_array($res)) {
                    
                    // От $recs се премахват отделените записи, да не се обхождат отново
                    // добавяме артикулите към подредените
                    $recs = array_diff_key($recs, $res);
                    $ordered += $res;
               }
            }
        }
        
        // Правилна подредба
        uasort($ordered, function ($a, $b) use ($codeFld, $nameFld) {
             if ($a->groupName == $b->groupName) {
                  $orderProductBy = cat_Groups::fetchField($a->_groupId, 'orderProductBy');
                  $field = ($orderProductBy === 'code') ? $codeFld : $nameFld;
                  $result = strcasecmp($a->{$field}, $b->{$field});
             } else {
                  $result = $a->groupName > $b->groupName;
             }
                
             return $result;
        });
        
        // В $recs трябва да са останали несортираните
        $rest = $recs;
        if (countR($rest) && is_array($rest)) {
            
            // Ще ги показваме в маркер 'Други'
            foreach ($rest as &$r1) {
                $r1->groupName = tr('Други');
            }
            
            // Подреждаме ги по код
            arr::sortObjects($rest, $codeFld);
            
            // Добавяме ги най-накрая
            $ordered += $rest;
        }
        
        // Заместваме намерените записи
        $recs = $ordered;
    }
    
    
    /**
     * Подготвя редовете във вербална форма.
     * Правим кеширане на всичко в $data->rows,
     * и само променените записи ще ги подготвяме наново
     *
     * @param stdClass $data
     */
    public function prepareListRows_(&$data)
    {
        // Филтрираме записите
        $expand = ($data->masterData->rec->expandGroups == 'yes') ? true : false;
        self::filterRecs($data->masterData->rec->groups, $data->recs, 'orderCode', 'orderName', 'groups', $expand);
        
        // Подготвяме ключа за кеширане
        $key = store_InventoryNotes::getCacheKey($data->masterData->rec);
        
        // Проверяваме имали кеш за $data->rows
        $cache = core_Cache::get("{$this->Master->className}_{$data->masterData->rec->id}", $key);
        $cacheRows = !empty($data->listFilter->rec->search) ? false : true;
        if (!empty($data->listFilter->rec->search) || Mode::is('printing')) {
            $cacheRows = false;
            $cache = false;
        }
        
        if (empty($cache)) {
            
            // Ако няма кеш подготвяме $data->rows стандартно
            $data = parent::prepareListRows_($data);
            if (Mode::is('blank')) {
                $callExpandRows = (Request::get('showBatches', 'int')) ? true : false;
            } else {
                $callExpandRows = true;
            }
            
            if ($callExpandRows === true) {
                cls::get('store_InventoryNoteDetails')->invoke('ExpandRows', array(&$data->recs, &$data->rows, $data->masterData->rec));
            }
            
            $cache1 = array();
            if (is_array($data->rows)) {
                foreach ($data->rows as $id => $sRow) {
                    $sRec = $data->recs[$id];
                    if ($sRec->isBatch !== true) {
                        $cache1[$id] = $sRec->productId;
                    }
                }
            }
            
            $uRec = (object) array('id' => $data->masterId, 'cache' => json_encode($cache1));
            $data->masterMvc->save_($uRec);
            
            if ($cacheRows === true) {
                $nCache = (object) array('recs' => $data->recs, 'rows' => $data->rows);
                core_Cache::set("{$this->Master->className}_{$data->masterData->rec->id}", $key, $nCache, 1440);
            }
        }
        
        if (empty($data->listFilter->rec->search) && !Mode::is('blank')) {
            $cached = core_Cache::get("{$this->Master->className}_{$data->masterData->rec->id}", $key);
            $data->recs = $cached->recs;
            $data->rows = $cached->rows;
        }
        
        Mode::setPermanent("InventoryNoteLastSavedRow{$data->masterId}", null);
        
        // Връщаме $data
        return $data;
    }
    
    
    /**
     * След генериране на ключовите думи
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        if (isset($rec->productId)) {
            $pRec = cat_Products::fetch($rec->productId, 'isPublic,code');
            $code = cat_Products::getVerbal($pRec, 'code');
            $res .= ' ' . plg_Search::normalizeText($code);
        }
    }
    
    
    /**
     * Рекалкулиране на количествата
     *
     * @param int $id
     */
    public static function recalc($id)
    {
        expect($id);
        $rec = self::fetch($id);
        $query = store_InventoryNoteDetails::getQuery();
        $query->where("#noteId = {$rec->noteId} AND #productId = {$rec->productId}");
        $query->XPR('sumQuantity', 'double', 'SUM(#quantity)');
        $query->show('sumQuantity,quantity');
        
        $quantity = $query->fetch()->sumQuantity;
        $rec->quantity = round($quantity, 4);
        
        cls::get('store_InventoryNoteSummary')->save($rec, 'quantity');
    }
    
    
    /**
     * Изпълнява се след подготвянето на тулбара в листовия изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if (store_InventoryNoteDetails::haveRightFor('add', (object) array('noteId' => $data->masterId))) {
            $data->toolbar->addBtn('Импорт', array('store_InventoryNoteDetails', 'import', 'noteId' => $data->masterId, 'ret_url' => true), 'title=Добавяне на артикули от група,ef_icon=img/16/cart_go.png');
        }
    }
}
