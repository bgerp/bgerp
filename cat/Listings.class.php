<?php


/**
 * Листвани артикули
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Листвани артикули
 */
class cat_Listings extends core_Master
{
    /**
     * Дали се очаква в документа да има файлове
     */
    public $expectFiles = false;
    
    
    /**
     * Дали се очаква в документа да има други документи
     */
    public $expectDocs = false;
    
    
    /**
     * Заглавие
     */
    public $title = 'Листвания на артикули';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Листване на артикули';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, cat_Wrapper, doc_ActivatePlg, plg_Clone, doc_DocumentPlg, doc_plg_SelectFolder, cat_plg_AddSearchKeywords, plg_Search';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'folderId';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Lst';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = true;
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'cat_ListingDetails';
    
    
    /**
     * Кой е основния детайл
     */
    public $mainDetail = 'cat_ListingDetails';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'doc=Документ,title, folderId, modifiedOn,modifiedBy';
    
    
    /**
     * Кой може да го промени?
     */
    public $canEdit = 'listArt,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'listArt,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'listArt,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'listing,ceo';
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'cat/tpl/SingleLayoutListing.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '3.99|Търговия';
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'doc_UnsortedFolders,crm_ContragentAccRegIntf';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'cat_ListingDetails';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'title';
    
    
    /**
     * Икона за еденичен изглед
     */
    public $singleIcon = 'img/16/choose-icon.png';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar', 'mandatory,caption=Заглавие,ci');
        $this->FLD('type', 'enum(canSell=Продаваеми артикули,canBuy=Купуваеми артикули)', 'mandatory,caption=Артикули,notNull,value=canSell');
        $this->FLD('isPublic', 'enum(yes=Да,no=Не)', 'mandatory,caption=Публичен,input=none');
        $this->FLD('sysId', 'varchar', 'input=none');
        
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Допълнително->Валута');
        $this->FLD('vat', 'enum(yes=Включено,no=Без ДДС)', 'caption=Допълнително->ДДС');
        
        $this->setDbIndex('title,type');
        $this->setDbIndex('sysId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;
        
        if (isset($rec->id)) {
            if (cat_ListingDetails::fetchField("#listId = {$rec->id}")) {
                $form->setReadOnly('type');
                $form->setReadOnly('currencyId');
                $form->setReadOnly('vat');
            }
        }
        
        $Cover = doc_Folders::getCover($rec->folderId);
        if ($Cover->haveInterface('crm_ContragentAccRegIntf')) {
            $form->setDefault('currencyId', $Cover->getDefaultCurrencyId());
            $form->setDefault('vat', ($Cover->shouldChargeVat()) ? 'yes' : 'no');
        }
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        $row = new stdClass();
        $title = $this->getVerbal($rec, 'title');
        
        $row->title = $title . " №{$rec->id}";
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->recTitle = $row->title;
        $row->state = $rec->state;
        
        return $row;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'activate') {
            if (empty($rec->id)) {
                $requiredRoles = 'no_one';
            } else {
                if (!cat_ListingDetails::fetchField("#listId = {$rec->id}")) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        if (isset($rec->folderId)) {
            $Cover = doc_Folders::getCover($rec->folderId);
            $isPublic = ($Cover->haveInterface('crm_ContragentAccRegIntf')) ? 'no' : 'yes';
            if($rec->isPublic != $isPublic){
                $rec->isPublic = $isPublic;
                $mvc->save_($rec, 'isPublic');
            }
        }
    }
    
    
    /**
     * Кешира и връща всички листвани артикули за клиента
     *
     * @param int|stdClass $listId       - ид на лист
     * @param int|NULL     $storeId      - ид на склад
     * @param int|NULL     $limit        - ограничение
     * @param boolean      $onlyActive   - дали да са само активни артикулите
     *
     * @return array
     */
    public static function getAll($listId, $storeId = null, $limit = null, $onlyActive = false)
    {
        expect($listRec = cat_Listings::fetchRec($listId));
        
        $instock = null;
        
        // Ако е зададен склад
        if (isset($storeId)) {
            
            // Намиране на всички налични артикули в склада
            $pQuery = store_Products::getQuery();
            $pQuery->where("#storeId = {$storeId}");
            $pQuery->where('#quantity > 0');
            $pQuery->show('productId');
            $instock = arr::extractValuesFromArray($pQuery->fetchAll(), 'productId');
        }
        
        // Ако няма наличен кеш за контрагента, извлича се наново
        if (!isset(self::$cache[$listRec->id])) {
            self::$cache[$listRec->id] = array();
            
            // Кои са листваните артикули за контрагента
            $query = cat_ListingDetails::getQuery();
            $query->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
            $query->EXT('state', 'cat_Products', 'externalName=state,externalKey=productId');
            $query->EXT($listRec->type, 'cat_Products', "externalName={$listRec->type},externalKey=productId");
            $query->where("#listId = {$listRec->id} AND #{$listRec->type} = 'yes'");
            
            if($onlyActive === true){
                $query->where("#state = 'active'");
            }
            
            if (is_array($instock) && countR($instock)) {
                
                // Артикулите се подреждат така че наличните в склада да са по-напред
                $instock = implode(',', $instock);
                $query->XPR('instock', 'int', "(CASE WHEN #productId IN (${instock}) THEN 0 ELSE 1 END)");
                $query->orderBy('instock,id', 'ASC');
            } else {
                $query->orderBy('id', 'ASC');
            }
            
            // Ако има зададен лимит
            if (isset($limit)) {
                $query->limit($limit);
            }
            
            // Добавя се всеки запис, групиран според типа
            while ($rec = $query->fetch()) {
                $reff = (!empty($rec->reff)) ? $rec->reff : $rec->code;
                $obj = (object) array('productId' => $rec->productId, 'packagingId' => $rec->packagingId, 'reff' => $reff, 'moq' => $rec->moq, 'multiplicity' => $rec->multiplicity, 'code' => $rec->code);
                if (isset($rec->price)) {
                    if ($listRec->vat == 'yes') {
                        $vat = cat_Products::getVat($rec->productId);
                        $rec->price /= 1 + $vat;
                    }
                    
                    $rate = currency_CurrencyRates::getRate(null, $listRec->currencyId, null);
                    
                    $price = $rec->price * $rate;
                    $obj->price = $price;
                }
                
                self::$cache[$listRec->id][$rec->id] = $obj;
            }
        }
        
        // Връщане на кешираните данни
        return self::$cache[$listRec->id];
    }
    
    
    /**
     * Помощна ф-я връщаща намерения код според артикула и опаковката, ако няма опаковка
     * се връща първия намерен код
     *
     * @param mixed    $cClass      - ид на клас
     * @param int      $cId         - ид на контрагента
     * @param int      $productId   - ид на артикул
     * @param int|NULL $packagingId - ид на опаковка, NULL ако не е известна
     *
     * @return string|NULL - намерения код или NULL
     */
    public static function getReffByProductId($listId, $productId, $packagingId = null)
    {
        // Извличане на всичките листвани артикули
        $all = self::getAll($listId);
        
        // Намират се записите за търсения артикул
        $res = array_filter($all, function (&$e) use ($productId, $packagingId) {
            if (isset($packagingId)) {
                if ($e->productId == $productId && $e->packagingId == $packagingId) {
                    
                    return true;
                }
            } else {
                if ($e->productId == $productId) {
                    
                    return true;
                }
            }
            
            return false;
        });
        
        // Ако има намерен поне един запис се връща кода
        $firstFound = $res[key($res)];
        $reff = (is_object($firstFound)) ? (($firstFound->reff != $firstFound->code) ? $firstFound->reff : null) : null;
        
        // Връща се намерения код
        return $reff;
    }
    
    
    /**
     * Подредба на записите
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'search';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Сортиране на записите по num
        $data->query->orderBy('id');
    }
    
    
    /**
     * Изпълнява се преди оттеглянето на документа
     */
    protected static function on_BeforeReject(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        $id1 = cond_Parameters::fetchIdBySysId('salesList');
        $id2 = cond_Parameters::fetchIdBySysId('purchaseList');
        
        $cQuery = cond_ConditionsToCustomers::getQuery();
        $cQuery->in('conditionId', array($id1, $id2));
        $cQuery->where("#value = {$rec->id}");
        
        $found = array();
        while ($cRec = $cQuery->fetch()) {
            $found[] = '<b>' . cls::get($cRec->cClass)->getTitleById($cRec->cId) . '</b>';
        }
        
        if (countR($found)) {
            $implode = implode(', ', $found);
            core_Statuses::newStatus('Документа не може да се оттегли, защото е избран като търговско условие за|* ' . $implode, 'warning');
            
            return false;
        }
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->doc = $mvc->getLink($rec->id, 0);
    }
    
    
    /**
     * Обновяване на листите за продажба
     */
    public function cron_UpdateAutoLists()
    {
        core_Debug::$isLogging = false;
        
        $from = dt::addDays(-60, null, false);
        $today = dt::today();
        $now = dt::now();
        
        $cache = array();
        
        // Намират се последните продажби за месеца
        $query = sales_Sales::getQuery();
        $query->where("#valior >= '{$from}' AND #valior <= '{$today}' AND (#state = 'active' OR #state = 'closed')");
        $query->groupBy('folderId');
        $query->show('folderId');
        
        // Извличат се папките им
        $folders = arr::extractValuesFromArray($query->fetchAll(), 'folderId');
        $count = countR($folders);
        if (!$count) {
            
            return;
        }
        
        core_App::setTimeLimit($count * 3);
        
        // За всяка папка
        foreach ($folders as $folderId) {
            
            // Има ли зададено търговско условие за автоматичен лист
            $Cover = doc_Folders::getCover($folderId);
            if (!$Cover->haveInterface('crm_ContragentAccRegIntf')) {
                continue;
            }
            
            // Има ли зададено търговско условие
            $value = cond_Parameters::getParameter($Cover->getInstance(), $Cover->that, 'autoSalesMakeList');
            if ($value !== 'yes') {
                continue;
            }
            
            // Задаване на списъка като търговско условие, ако няма такова за контрагента
            $paramId = cond_Parameters::fetchIdBySysId('salesList');
            
            // Ако за тази папка има избран лист не се създава
            $condId = cond_ConditionsToCustomers::fetchByCustomer($Cover->getClassId(), $Cover->that, $paramId);
            $autoListId = cat_Listings::fetchField("#sysId = 'auto{$folderId}' AND #state != 'rejected'");
            
            if (!empty($condId) && empty($autoListId)) {
                continue;
            }
            
            $res = array();
            
            // Намират се всички продавани стандартни артикули от тази папка
            $dQuery = sales_SalesDetails::getQuery();
            $dQuery->XPR('count', 'int', 'count(#productId)');
            $dQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
            $dQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
            $dQuery->EXT('canSell', 'cat_Products', 'externalName=canSell,externalKey=productId');
            $dQuery->EXT('valior', 'sales_Sales', 'externalName=valior,externalKey=saleId');
            $dQuery->EXT('folderId', 'sales_Sales', 'externalName=folderId,externalKey=saleId');
            $dQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
            $dQuery->EXT('state', 'sales_Sales', 'externalName=state,externalKey=saleId');
            $dQuery->where("#valior >= '{$from}' AND #valior <= '{$today}' AND (#state = 'active' OR #state = 'closed')");
            $dQuery->where("#folderId = {$folderId} AND #canSell = 'yes' AND #isPublic = 'yes'");
            
            // Ограничаване по групи, ако има
            $groupList = cat_Setup::get('AUTO_LIST_ALLOWED_GROUPS');
            if (!empty($groupList)) {
                $dQuery->likeKeylist('groups', $groupList);
            }
            
            $dQuery->groupBy('productId,packagingId');
            $dQuery->show('productId,packagingId,code,count');
            $dQuery->orderBy('count,saleId', 'DESC');
            $all = $dQuery->fetchAll();
            
            if (!countR($all)) {
                continue;
            }
            $products = arr::extractSubArray($all, 'productId,packagingId');
            
            // Форсира се системен лист
            $listId = self::forceAutoList($folderId, $Cover);
            
            if ($listId && empty($condId)) {
                cond_ConditionsToCustomers::force($Cover->getClassId(), $Cover->that, $paramId, $listId);
            }
            
            $newDetails = array();
            
            // За всеки артикул, подготвят се детайлите
            foreach ($products as $obj) {
                if (array_key_exists($obj->productId, $newDetails)) {
                    continue;
                }
                
                if (!array_key_exists($obj->productId, $cache)) {
                    $cache[$obj->productId] = array('reff' => cat_Products::fetchField($obj->productId, 'code'));
                }
                
                $newDetails[$obj->productId] = (object) array('listId' => $listId,
                    'productId' => $obj->productId,
                    'reff' => $cache[$obj->productId]['reff'],
                    'modifiedOn' => $now,
                    'modifiedBy' => core_Users::SYSTEM_USER,
                    'packagingId' => $obj->packagingId);
            }
            
            $limit = cat_Setup::get('AUTO_LIST_PRODUCT_COUNT');
            
            // Взимат се първите N записа
            $newDetails = array_slice($newDetails, 0, $limit, true);
            
            // Досегашните записи на листа
            $lQuery = cat_ListingDetails::getQuery();
            $lQuery->where("#listId = {$listId}");
            $old = $lQuery->fetchAll();
            
            // Колко са новите записи
            $count = countR($newDetails);
            
            // Ако последно продаваните артикули са под максималния лимит
            // Идеята е ако има стари записи да не се изтрият докато, не се изместят от по нови
            if($count < $limit){
                
                // и има стари записи
                $products = array_keys($newDetails);
                $notInProducts = array_filter($old, function($a) use ($products) { return !in_array($a->productId, $products);});
                if(countR($notInProducts)){
                    asort($notInProducts);
                    
                    // Допълване на масива, със стари записи, докато се достигне лимите
                    foreach ($notInProducts as $oldRec){
                        if($count > $limit) break;
                        
                        $newDetails[$oldRec->productId] = $oldRec;
                    }
                }
            }
            
            // Синхронизиране на новите записи
            $res = arr::syncArrays($newDetails, $old, 'productId,packagingId', 'packagingId');
            
            // Инсърт на новите
            if (countR($res['insert'])) {
                cat_ListingDetails::saveArray($res['insert']);
            }
            
            // Ъпдейт на старите
            if (countR($res['update'])) {
                cat_ListingDetails::saveArray($res['update'], 'packagingId');
            }
            
            // Изтриване на тези дето не се срещат
            if (countR($res['delete'])) {
                $delete = implode(',', $res['delete']);
                cat_ListingDetails::delete("#id IN ({$delete})");
            }
        }
        
        core_Debug::$isLogging = true;
    }
    
    
    /**
     * Форсира автоматичния лист на потребителя
     *
     * @param int $folderId - ид на папка
     *
     * @return int $listid - ид на форсирания лист
     */
    private static function forceAutoList($folderId, $Cover)
    {
        $title = 'Списък от предишни продажби';
        $listId = cat_Listings::fetchField("#sysId = 'auto{$folderId}' AND #state != 'rejected'");
        if (!$listId) {
            $lRec = (object) array('title' => $title, 'type' => 'canSell', 'folderId' => $folderId, 'state' => 'active', 'isPublic' => 'no', 'sysId' => "auto{$folderId}");
            $lRec->currencyId = $Cover->getDefaultCurrencyId();
            $lRec->vat = ($Cover->shouldChargeVat()) ? 'yes' : 'no';
            $listId = self::save($lRec);
        }
        
        return $listId;
    }
    
    
    /**
     * Връща достъпните продаваеми артикули
     */
    public static function getProductOptions($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        expect($params['listId']);
        $options = array();
        
        $pQuery = cat_Products::getQuery();
        if (is_array($onlyIds)) {
            if (!countR($onlyIds)) {
                
                return array();
            }
            $ids = implode(',', $onlyIds);
            $pQuery->where("#id IN ({$ids})");
        } elseif (ctype_digit("{$onlyIds}")) {
            $pQuery->where("#id = ${onlyIds}");
        } else {
            $dQuery = cat_ListingDetails::getQuery();
            $dQuery->where("#listId = {$params['listId']}");
            $dQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
            $dQuery->EXT('state', 'cat_Products', 'externalName=state,externalKey=productId');
            
            $listType = self::fetchField($params['listId'], 'type');
            $dQuery->EXT($listType, 'cat_Products', "externalName={$listType},externalKey=productId");
            $dQuery->where("#state != 'closed' AND #state != 'rejected' AND #{$listType} = 'yes'");
            $dQuery->show('productId');
            
            $products = arr::extractValuesFromArray($dQuery->fetchAll(), 'productId');
            $pQuery->in('id', $products);
        }
        
        $pQuery->XPR('searchFieldXprLower', 'text', "LOWER(CONCAT(' ', COALESCE(#name, ''), ' ', COALESCE(#code, ''), ' ', COALESCE(#nameEn, ''), ' ', 'Art', #id))");
        
        if ($q) {
            if ($q{0} == '"') {
                $strict = true;
            }
            $q = trim(preg_replace("/[^a-z0-9\p{L}]+/ui", ' ', $q));
            $q = mb_strtolower($q);
            $qArr = ($strict) ? array(str_replace(' ', '.*', $q)) : explode(' ', $q);
            
            $pBegin = type_Key2::getRegexPatterForSQLBegin();
            foreach ($qArr as $w) {
                $pQuery->where(array("#searchFieldXprLower REGEXP '(" . $pBegin . "){1}[#1#]'", $w));
            }
        }
        
        if ($limit) {
            $pQuery->limit($limit);
        }
        
        $pQuery->show('id,name,code,isPublic,nameEn');
        while ($pRec = $pQuery->fetch()) {
            $options[$pRec->id] = cat_Products::getRecTitle($pRec, false);
        }
        
        return $options;
    }
}
