<?php 

/**
 * Мениджира детайлите на събирания на документите
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
class rack_Journals extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Архив на движенията';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Движение по документ';
    
    
    /**
     * Страница от менюто
     */
    public $pageMenu = 'Документи';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, plg_AlignDecimals2, rack_Wrapper, plg_SelectPeriod, plg_Search, plg_Sorting';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'zoneId';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, admin, rack';
    
    
    /**
     * Кой има право бързо да добавя?
     */
    public $canOrderpickup = 'ceo, admin, rack';
    
    
    /**
     * Кой може да активира?
     */
    public $canActivate = 'ceo, admin, rack';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета в листовия изглед
     */
    public $listFields = 'zoneId,containerId,productId,packagingId,packQuantity,palletId,operation,state,createdOn,createdBy,closedOn';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'zoneId,operation,productId';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     *
     *  @var string
     */
    public $hideListFieldsIfEmpty = 'closedOn';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('zoneId', 'key(mvc=rack_Zones)', 'caption=Зона,silent,mandatory');
        $this->FLD('operation', 'enum(take=Нагласяне,put=Връщане)', 'caption=Действие,mandatory,silent,input=hidden');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'tdClass=productCell leftCol wrap,caption=Артикул,silent,mandatory,removeAndRefreshForm=packagingId|quantityInPack|quantity|palletId');
        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка', 'smartCenter,mandatory,input=none');
        $this->FNC('packQuantity', 'double(Min=0)', 'caption=Количество,input,smartCenter,mandatory');
        $this->FLD('palletId', 'key(mvc=rack_Pallets,select=id)', 'caption=Палет,placeholder=От пода,input=none,silent,removeAndRefreshForm');
        
        $this->FLD('quantity', 'double', 'caption=Количество,input=none');
        $this->FLD('quantityInPack', 'double', 'input=none');
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Документ,input=none');
        $this->FLD('state', 'enum(active=Активно,closed=Приключено,pending=Заявка)', 'caption=Състояние,input=none,notNull,value=pending');
        $this->FLD('closedOn', 'datetime', 'caption=Приключено,input=none');
        
        $this->setDbIndex('zoneId');
        $this->setDbIndex('containerId');
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        if (empty($rec->palletId)) return;
        
        if($rec->state != 'closed'){
            $palletRec = rack_Pallets::fetch($rec->palletId);
            $sign = ($rec->operation == 'take') ? -1 : 1;
            
            $palletRec->quantity += $sign * $rec->quantity;
            rack_Pallets::save($palletRec, 'quantity');
        }
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     */
    protected static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (!isset($rec->quantity) || empty($rec->quantityInPack)) return;
        
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне
     */
    protected static function on_AfterPrepareEditToolbar($mvc, $data)
    {
        $data->form->toolbar->addSbBtn('Активиране', 'active', 'id=activate, order=9.99980', 'ef_icon = img/16/lightning.png,title=Активиране на документа');
        $data->form->toolbar->renameBtn('save', 'Заявка');
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
        
        $productOptions = ($rec->operation == 'take') ? cat_Products::getByProperty('canStore') : self::getTakenProducts($rec->zoneId);
        $form->setOptions('productId', array('' => '') + $productOptions);
        
        // При избран артикул
        if(isset($rec->productId)){
            $zoneRec = rack_Zones::fetch($rec->zoneId);
            $form->setField('packagingId', 'input');
            $form->setField('palletId', 'input');
            $packs = cat_Products::getPacks($rec->productId);
            $form->setOptions('packagingId', $packs);
            $form->setDefault('packagingId', key($packs));
            
            // Наличните палети за избор
            $pallets = rack_Pallets::getPalletOptions($rec->productId, $zoneRec->storeId);
            $form->setOptions('palletId', array('' => '') + $pallets);
            $measureId = cat_UoM::getShortName(cat_Products::fetchField($rec->productId, 'measureId'));
            
            // Ако операцията е връщане
            if($rec->operation == 'put'){
                $inZoneQuantity = $zoneRec->summaryData[$rec->productId]->journalQuantity;
                $inZoneQuantityVerbal = core_Type::getByName('double(smartRound)')->toVerbal($inZoneQuantity);
                $form->setField('palletId', 'placeholder=На пода');
                $form->info = tr("Нагласено|* <b>{$inZoneQuantityVerbal}</b> |{$measureId}|*");
                $form->rec->_checkQuantity = $inZoneQuantity;
                $form->rec->_checkWarning = "Сигурни ли сте, че искате да върнете повече отколкото е нагласено в зоната|*?";
            } else {
                
                // Ако операцията е нагласяне
                if (isset($rec->palletId)){
                    $availableQuantity = rack_Pallets::fetchField($rec->palletId, 'quantity');
                    $suffix = 'в палет|* ' . rack_Pallets::getRecTitle($rec->palletId);
                } else {
                    if ($storeProductRecId = store_Products::fetchField("#storeId = {$zoneRec->storeId} AND #productId = {$rec->productId}")){
                        $availableQuantity = rack_Products::fetchField($storeProductRecId, 'quantityNotOnPallets');
                    }
                    $availableQuantity = empty($availableQuantity) ? 0 : $availableQuantity;
                    $suffix = 'на пода|*';
                }
                $availableVerbal = core_Type::getByName('double(smartRound)')->toVerbal($availableQuantity);
                $availableVerbal = ht::styleIfNegative($availableVerbal, $availableQuantity);
                
                $form->info = tr("Налично|* <b>{$availableVerbal}</b> |{$measureId}|* |{$suffix}");
                $form->rec->_checkQuantity = $availableQuantity;
                $form->rec->_checkWarning = "Сигурни ли сте, че искате да нагласите повече отколкото е налично|*?";
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->FLD('document', 'varchar', 'caption=Документ,silent');
        $data->listFilter->FLD('from', 'date', 'caption=От,silent');
        $data->listFilter->FLD('to', 'date', 'caption=До,silent');
        $data->listFilter->setFieldTypeParams('zoneId', array('allowEmpty' => true));
        $data->listFilter->setField('operation', 'input=none');
        $data->listFilter->view = 'horizontal';
        
        if(isset($data->masterMvc)){
            $data->query->where("#state != 'closed'");
            $data->listFilter->showFields = 'search,document,selectPeriod';
        } else {
            $data->listFilter->showFields = 'search,zoneId,document,selectPeriod';
        }
        
        $data->listFilter->input('from,to,zoneId,search,document,selectPeriod');
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        
        if ($fRec = $data->listFilter->rec) {
            if (isset($fRec->from)) {
                $data->query->where("#createdOn >= '{$fRec->from} 00:00:00'");
            }
            if (isset($fRec->to)) {
                $data->query->where("#createdOn <= '{$fRec->to} 23:59:59'");
            }
            if (isset($fRec->zoneId)) {
                $data->query->where("#zoneId = {$fRec->zoneId}");
            }
            
            // Филтър по документ
            if (!empty($fRec->document)) {
                $document = doc_Containers::getDocumentByHandle($fRec->document);
                if(is_object($document)){
                    $containerId = $document->fetchField('containerId');
                    $zoneId = rack_Zones::fetchField("#containerId = {$containerId}");
                    $where = ($zoneId) ? "#containerId = {$containerId} || #zoneId = {$zoneId}" : "#containerId = {$containerId}";
                    $data->query->where($where);
                } else {
                    $data->query->where("1 != 1");
                }
            }
        }
    }
    
    
    /**
     * Активиране на движение-заявка
     */
    public function act_Activate()
    {
        $this->requireRightFor('activate');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('activate', $rec);
        
        $rec->state = 'active';
        $this->save($rec, 'state');
        
        return followRetUrl(null, 'Движението е активирано|*!');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if($form->isSubmitted()){
            $rec = $form->rec;
            $packRec = cat_products_Packagings::getPack($rec->productId, $rec->packagingId);
            $rec->quantityInPack = (is_object($packRec)) ? $packRec->quantity : 1;
            $rec->quantity = $rec->quantityInPack * $rec->packQuantity;
            
            $rec->state = ($form->cmd == 'active') ? 'active' : 'pending';
            if($rec->quantity > $rec->_checkQuantit && !empty($rec->_checkWarning)){
                $form->setWarning('packQuantity', $rec->_checkWarning);
            }
        }
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $rec = $data->form->rec;
        
        if (isset($rec->operation)) {
            $operation = ($rec->operation == 'take') ? 'Нагласяне' : 'Връщане';
            $data->form->title = $operation . " на артикул в зона|* " . cls::get('rack_Zones')->getFormTitleLink($rec->zoneId);
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->ROW_ATTR['class'] = "state-{$rec->state}";
        
        deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        
        if(empty($rec->palletId)){
            $row->palletId = ($rec->operation == 'take') ? tr('От пода') : tr('На пода');
            $row->palletId = "<span class='quiet'>{$row->palletId}</span>";
        } else {
            $row->palletId = rack_Pallets::getRecTitle($rec->palletId);
        }
        
        if($mvc->haveRightFor('activate', $rec)){
            core_RowToolbar::createIfNotExists($row->_rowTools);
            $row->_rowTools->addLink('Активиране', array($mvc, 'activate', $rec->id, 'ret_url' => true), array('ef_icon' => 'img/16/lightning.png', 'title' => 'Активиране на движението'));
        }
        
        $backgroundColor = ($rec->operation == 'take') ? '#e6ffe0' : '#cce3fe';
        $row->operation = "<span style='padding:3px;display:block;background-color:{$backgroundColor};border:solid 1px #aaa;text-align:center;font-size:0.9em' class='cCode222'>{$row->operation}</span>";
        $row->zoneId = rack_Zones::getHyperlink($rec->zoneId, true);
        
        $containerId = (isset($rec->containerId)) ? $rec->containerId : rack_Zones::fetchField($rec->zoneId, 'containerId');
        if($containerId){
            $row->containerId = doc_Containers::getDocument($containerId)->getLink(0);
        }
    }
    
    
    /**
     * Салдото на нагласените артикули
     * 
     * @param int $zoneId  - зона
     * @return array $recs - записи
     */
    public static function getSummaryRecs($zoneId)
    {
        $recs = array();
        $query = static::getQuery();
        $query->where("#zoneId = {$zoneId} AND #state != 'closed'");
        $query->EXT('measureId', 'cat_Products', 'externalName=measureId,externalKey=productId');
        
        while($rec = $query->fetch()){
            if(!array_key_exists($rec->productId, $recs)){
                $recs[$rec->productId] = (object)array('productId' => $rec->productId, 'measureId' => $rec->measureId);
            }
            
            $sign = ($rec->operation == 'take') ? 1 : -1;
            $recs[$rec->productId]->quantity += $sign * $rec->quantity;
        }
        
        return $recs;
    }
    
    
    /**
     * Кои са нагласените артикули в зоната
     * 
     * @param int $zoneId
     * @return array $options
     */
    private static function getTakenProducts($zoneId)
    {
        $options = array();
        $query = self::getQuery();
        $query->where("#zoneId = {$zoneId} AND #state = 'active' AND #operation = 'take'");
        $query->show('productId');
        
        while($rec = $query->fetch()){
            $options[$rec->productId] = cat_Products::getTitleById($rec->productId, FALSE);
        }
        
        return $options;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'add' && isset($rec)){
            $zoneRec = rack_Zones::fetch($rec->zoneId);
            if ($zoneRec->state != 'active'){
                $requiredRoles = 'no_one';
            } elseif($rec->operation == 'put'){
                $taken = self::getTakenProducts($rec->zoneId);
                if(!count($taken)){
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if ($action == 'activate' && isset($rec)){
            if($rec->state != 'pending'){
                $requiredRoles = 'no_one';
            }
        }
        
        if($action == 'orderpickup' && isset($rec)){
            if(empty($rec->storeId) || !store_Stores::haveRightFor('select', $rec->storeId)){
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Преди подготовката на полетата за листовия изглед
     */
    public static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
        if(isset($data->masterMvc)){
            unset($data->listFields['zoneId'], $data->listFields['state'], $data->listFields['containerId'], $data->listFields['closedOn']);
            $data->query->orderBy('#createdOn', 'DESC');
        } else {
            $data->query->XPR('order', 'int', "(CASE #state WHEN 'active' THEN 2 WHEN 'pending' THEN 1 WHEN 'closed' THEN 3 END)");
            $data->query->orderBy('#order=ASC,#createdOn=DESC');
        }
    } 
    
    
    /**
     * Затваря движенията и ги запомня за архива
     * 
     * @param int $zoneId
     * @param int $containerId
     * 
     * @return void
     */
    public static function closeRecs($zoneId, $containerId)
    {
        $document = doc_Containers::getDocument($containerId);
        $activatedOn = $document->fetchField('activatedOn');
        
        $query = self::getQuery();
        $query->where("#zoneId = {$zoneId} AND #state != 'closed'");
        while($rec = $query->fetch()){
            $rec->closedOn = $activatedOn;
            $rec->containerId = $containerId;
            $rec->state = 'closed';
            self::save($rec);
        }
    }
    
    
    /**
     * Рендиране на таблицата с движенията в състояние заявка
     * 
     * @param int $zoneId
     * @return string $res
     */
    public static function getPendingTableHtml($zoneId)
    {
        $self = cls::get(get_called_class());
        $data = (object)array('recs' => array(), 'rows' => array(), 'listTableMvc' => $self);
        $data->listFields = arr::make("productId=Артикул,packagingId=Опаковка,packQuantity=Количество,palletId=Палет,operation=Действие,createdOn=Създадено", true);
        
        // Извличане на движенията-заявка
        $query = self::getQuery();
        $query->where("#zoneId = {$zoneId} AND #state = 'pending'");
        while($rec = $query->fetch()){
            $row = self::recToVerbal($rec);
            $row->createdOn = $row->createdOn . " " . tr('от||by') .  " " . $row->createdBy;
            
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = $row;
        }
        
        // Рендиране на таблицата
        $res = new core_ET("");
        if(count($data->rows)){
            $table = cls::get('core_TableView', array('mvc' => $data->listTableMvc, 'tableClass' => 'simpleTable'));
            $self->invoke('BeforeRenderListTable', array($res, &$data));
            
            $res->append($table->get($data->rows, $data->listFields));
            $res->append("style='width:100%;'", 'TABLE_ATTR');
        }
        
        return $res->getContent();
    }
    
    
    
    
    
    /**
     * Избор на зона в документ
     *
     * @return void|core_ET
     */
    function act_OrderPickup()
    {
        // Проверка на права
        rack_Journals::requireRightFor('orderpickup');
        $storeId = Request::get('storeId', 'int');
        rack_Journals::requireRightFor('orderpickup', (object)array('storeId' => $storeId));
        
        // Подготовка на формата
        $form = $this->prepareOrderPickupForm($storeId);
        $form->input();
        
        // Изпращане на формата
        if($form->isSubmitted()){
            $fRec = $form->rec;
            $state = ($form->cmd == 'activate') ? 'active' : 'pending';
            $packRec = cat_products_Packagings::getPack($fRec->productId, $fRec->packagingId);
            $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
            
            $toSave = array();
            $saveRec = (object)array('operation' => 'take', 'productId' => $fRec->productId, 'packagingId' => $fRec->packagingId, 'quantityInPack' => $quantityInPack, 'state' => $state, 'palletId' => $fRec->palletId);
            $arr = (array)$fRec;
            
            foreach ($arr as $name => $value) {
                if(strpos($name, "quantity|") === false) continue;
                if(empty($value)) continue;
                list(,$zoneId) = explode("|", $name);
               
                if(!deals_Helper::checkQuantity($saveRec->packagingId, $value, $warning)){
                    $form->setWarning($name, $warning);
                }
                
                $cloneRec = clone $saveRec;
                $cloneRec->zoneId = $zoneId;
                $cloneRec->quantity = $cloneRec->quantityInPack * $value;
                $toSave[] = $cloneRec;
            }
            
            if(!$form->gotErrors()){
                foreach ($toSave as $rec) {
                    $this->save($rec);
                }
                
                followRetUrl();
            }
        }
        
        // Добавяне на бутони
        $form->toolbar->addSbBtn('Заявка', 'save', 'ef_icon = img/16/move.png, title = Заявяване на движенията');
        $form->toolbar->addSbBtn('Активиране', 'activate', 'ef_icon = img/16/lightning.png, title = Активиране на движенията');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        // Записваме, че потребителя е разглеждал този списък
        $this->logInfo('Нагласяне на артикули в склад');
        $tpl = $this->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);
        
        return $tpl;
        bp($storeId);
    }
    
    
    private function prepareOrderPickupForm($storeId)
    {
        $form = cls::get('core_Form');
        $form->title = 'Склад|*: ' . cls::get('store_Stores')->getFormTitleLink($storeId);
        $rec = &$form->rec;
        
        // Всички зони от склада с документи към тях
        $zones = $products = $productOptions = array();
        $zQuery = rack_Zones::getQuery();
        $zQuery->where("#storeId = {$storeId} AND #state != 'closed' AND #containerId IS NOT NULL");
        while($zRec = $zQuery->fetch()){
            $zones[$zRec->id] = $zRec;
            
            // Извличат се само тези артикули, по които се очаква да има нагласяне
            foreach ($zRec->summaryData as $zpRec) {
                $rest = $zpRec->dQuantity - $zpRec->jQuantity;
                if($rest <= 0) continue;
                if(!array_key_exists($zpRec->productId, $products)){
                    $products[$zpRec->productId] = 0;
                    $productOptions[$zpRec->productId] = cat_Products::getTitleById($zpRec->productId, false);
                }
                $products[$zpRec->productId] += $rest;
            }
        }
        
        // Показване на очакваните артикули за нагласяне
        $form->FLD('productId', 'int', 'caption=Артикул,silent,removeAndRefreshForm=packagingId|palletId,mandatory');
        $form->setOptions('productId', array('' => '') + $productOptions);
        $form->input(null, 'silent');
        
        // Възможност за бързо въвеждане на количества по зоните
        foreach ($zones as $zId => $zoneRec) {
            $caption = rack_Zones::getTitleById($zId) . " / #" . doc_Containers::getDocument($zoneRec->containerId)->getHandle();
            $form->FLD("quantity|{$zId}", 'double', "caption=Нагласяне по зони->|*{$caption}");
        }
        
        // Ако е избран артикул
        if(isset($rec->productId)){
            $quantity = core_Type::getByName('double(smartRound)')->toVerbal($products[$rec->productId]);
            $measureId = tr(cat_UoM::getShortName(cat_Products::fetchField($rec->productId, 'measureId')));
            $form->info = "<b>{$quantity}</b> {$measureId}";
            
            // Наличните му опаковки
            $form->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка', 'mandatory,after=productId');
            $packs = cat_Products::getPacks($rec->productId);
            $form->setOptions('packagingId', $packs);
            $form->setDefault('packagingId', key($packs));
           
            // Наличните палети за избор
            $form->FLD('palletId', 'key(mvc=rack_Pallets,select=id)', 'caption=Палет,placeholder=От пода,after=packagingId');
            $pallets = rack_Pallets::getPalletOptions($rec->productId, $storeId);
            $form->setOptions('palletId', array('' => '') + $pallets);
        }
        
        return $form;
    }
}