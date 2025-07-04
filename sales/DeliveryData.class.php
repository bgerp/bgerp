<?php


/**
 * Помощен модел за данните за доставка на договора
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sales_DeliveryData extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Доставка на Продажби и ЕН';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper, plg_Sorting';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Кой може да изчислява една готовност?
     */
    public $canRecalcreadiness = 'debug';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';


    /**
     * Полета, които се виждат
     */
    public $listFields = 'id,containerId,countryId,place,pCode,address,readiness';


    /**
     * Работен кеш?
     */
    private static $cacheRecs = array('recs' => array(), 'bgCountryId' => null);


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('containerId', 'key(mvc=doc_Containers,select=id)', 'caption=Документ');
        $this->FLD('countryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,default=Bg)', 'caption=Държава,remember,class=contactData,silent,export=Csv');
        $this->FLD('pCode', 'varchar(16)', 'caption=П. код,recently,class=pCode,export=Csv');
        $this->FLD('place', 'varchar(64)', 'caption=Град,class=contactData,hint=Населено място: град или село и община,export=Csv');
        $this->FLD('address', 'varchar(255)', 'caption=Адрес,class=contactData');
        $this->FLD('readiness', 'percent', 'caption=Готовност,recently,class=pCode');

        $this->setDbUnique('containerId');
    }


    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(isset($rec->containerId)){
            $Document = doc_Containers::getDocument($rec->containerId);
            $row->containerId = $Document->getLink(0);
            $row->containerId = "<span class= 'state-{$Document->fetchField('state')} document-handler'>{$row->containerId}</span>";
        }

        if($mvc->haveRightFor('recalcreadiness', $rec)){
            $row->readiness .= ht::createLink('', array($mvc, 'recalcreadiness', $rec->id), false, 'ef_icon=img/16/arrow_refresh.png');
        }
    }


    /**
     * Синхронизиране на запис
     *
     * @param int $containerId
     * @return int
     */
    public static function sync($containerId)
    {
        $Doc = doc_Containers::getDocument($containerId);

        $logisticData = $Doc->getLogisticData();
        $newRec = new stdClass();
        $newRec->countryId = drdata_Countries::getIdByName($logisticData['toCountry']);
        $newRec->place = $logisticData['toPlace'];
        $newRec->pCode = $logisticData['toPCode'];
        $newRec->address = $logisticData['toAddress'];
        $newRec->containerId = $containerId;

        return self::save($newRec, null, 'REPLACE');
    }


    /**
     * Кешират се данните за доставка
     * @return void
     */
    function cron_CacheDeliveryData()
    {
        $toSave = array();
        $salesClassId = sales_Sales::getClassId();
        $shipmentClassId = store_ShipmentOrders::getClassId();

        $countries = array();
        $cQuery = drdata_Countries::getQuery();
        while ($cRec = $cQuery->fetch()) {
            $countries[$cRec->commonName] = $cRec->id;
        }

        $fullRecs = array();
        $sQuery = sales_Sales::getQuery();
        $sQuery->in("state", array('active', 'pending'));
        while($sRec = $sQuery->fetch()){
            $sRec->_classId = $salesClassId;
            $fullRecs[$sRec->containerId] = $sRec;
        }

        $shQuery = store_ShipmentOrders::getQuery();
        $shQuery->where("#state = 'pending'");
        while($shRec = $shQuery->fetch()){
            $shRec->_classId = $shipmentClassId;
            $fullRecs[$shRec->containerId] = $shRec;
        }
        $docCount = countR($fullRecs);

        // Извличане на данните за доставка
        core_App::setTimeLimit(0.2 * $docCount, false, 300);
        $countryIds = array();

        core_Debug::$isLogging = false;
        foreach ($fullRecs as $rec){
            $Class = cls::get($rec->_classId);

            // Изчисляване на логистичната информация
            Mode::push('calcOnlyDeliveryPart', true);
            core_Debug::startTimer('GET_LOGISTIC_DATA');
            $logisticData = $Class->getLogisticData($rec);
            core_Debug::stopTimer('GET_LOGISTIC_DATA');
            Mode::pop('calcOnlyDeliveryPart');
            $countryIds[$logisticData['toCountry']] = $countries[$logisticData['toCountry']];

            $newRec = new stdClass();

            // Изчисляване на готовността за експедиция
            if($Class instanceof sales_Sales){
                core_Debug::startTimer('GET_READY_SALE_PERCENTAGE');
                $newRec->readiness = self::calcSaleReadiness($rec);
                core_Debug::stopTimer('GET_READY_SALE_PERCENTAGE');
            } elseif($Class instanceof store_ShipmentOrders){
                core_Debug::startTimer('GET_READY_EXP_PERCENTAGE');
                $newRec->readiness = self::calcSoReadiness($rec);
                core_Debug::stopTimer('GET_READY_EXP_PERCENTAGE');
            }

            $newRec->countryId = $countryIds[$logisticData['toCountry']];
            $newRec->place = $logisticData['toPlace'];
            $newRec->pCode = $logisticData['toPCode'];
            $newRec->address = $logisticData['toAddress'];
            $newRec->containerId = $rec->containerId;
            $newRec->classId = $rec->_classId;
            $toSave[$rec->containerId] = $newRec;
        }
        core_Debug::$isLogging = true;

        // Синхронизиране на съществуващите записи с новите
        $eQuery = self::getQuery();
        $exRecs = $eQuery->fetchAll();
        $sync = arr::syncArrays($toSave, $exRecs, 'containerId', 'countryId,place,pCode,address,readiness');

        core_Debug::log("GET GET_LOGISTIC_DATA " . round(core_Debug::$timers["GET_LOGISTIC_DATA"]->workingTime, 6));
        core_Debug::log("GET GET_READY_SALE_PERCENTAGE " . round(core_Debug::$timers["GET_READY_SALE_PERCENTAGE"]->workingTime, 6));
        core_Debug::log("GET GET_READY_EXP_PERCENTAGE " . round(core_Debug::$timers["GET_READY_EXP_PERCENTAGE"]->workingTime, 6));
        core_Debug::log("GET GET_DEAL_DATA " . round(core_Debug::$timers["GET_DEAL_DATA"]->workingTime, 6));

        core_Debug::log("GET GET_JOB_DATA " . round(core_Debug::$timers["GET_JOB_DATA"]->workingTime, 6));
        core_Debug::log("GET GET_SALE_DETAIL_DATA " . round(core_Debug::$timers["GET_SALE_DETAIL_DATA"]->workingTime, 6));
        core_Debug::log("GET GET_SALE_ENTRIES " . round(core_Debug::$timers["GET_SALE_ENTRIES"]->workingTime, 6));

        // Добавят се новите записи
        if(countR($sync['insert'])){
            $this->saveArray($sync['insert']);
        }

        // Обновяват се старите записи
        if(countR($sync['update'])){
            $this->saveArray($sync['update'], 'id,countryId,place,pCode,address,readiness');
        }
    }


    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->FLD('documentId', 'varchar', 'caption=Документ или контейнер, silent');
        $data->listFilter->showFields = 'documentId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();
        $data->query->orderBy('containerId', 'DESC');

        if ($rec = $data->listFilter->rec) {
            if (!empty($rec->documentId)) {

                // Търсене и на последващите документи
                if ($document = doc_Containers::getDocumentByHandle($rec->documentId)) {
                    $data->query->where("#containerId = {$document->fetchField('containerId')}");
                } elseif(type_Int::isInt($rec->documentId)){
                    $data->query->where("#containerId = {$rec->documentId}");
                }
            }

            if (!empty($rec->folder)) {
                $data->query->where("#folderId = {$rec->folder}");
            }
        }
    }


    /**
     * Връща другите активни и чакащи договори за същата локация на доставка
     *
     * @param int $containerId
     * @return array
     */
    public static function findDealsWithSameLocation($containerId)
    {
        if(!countR(self::$cacheRecs['recs'])){
            self::$cacheRecs['bgCountryId'] = drdata_Countries::getIdByName('Bulgaria');
            $dQuery = sales_DeliveryData::getQuery();
            $dQuery->EXT('state', 'doc_Containers', 'externalName=state,externalKey=containerId');
            $dQuery->EXT('docClass', 'doc_Containers', 'externalName=docClass,externalKey=containerId');
            $dQuery->in('state', array('pending', 'active'));
            $dQuery->where("#readiness IS NOT NULL AND #readiness > 0 AND #docClass =" . sales_Sales::getClassId());

            while($d1 = $dQuery->fetch()){
                self::$cacheRecs['recs'][$d1->containerId] = $d1;
            }
        }

        $res = array();
        $thisLocationRec = self::$cacheRecs['recs'][$containerId];
        foreach (self::$cacheRecs['recs'] as $locationRec){

            // Ако е за същата локация или няма готовност - няма да се гледат
            if ($containerId == $locationRec->containerId) continue;

            // Остават тези, чиято готовност е до +/- 20% от тази на договора
            $minReadiness = max(0, $thisLocationRec->readiness - 0.2);
            $maxReadiness = min(1, $thisLocationRec->readiness + 0.2);
            if($locationRec->readiness < $minReadiness || $locationRec->readiness > $maxReadiness) continue;

            $isSimilar = false;
            if ($thisLocationRec->countryId == self::$cacheRecs['bgCountryId']) {
                if ($locationRec->place === $thisLocationRec->place) {
                    $isSimilar = true;
                }
            } else {
                // Иначе проверяваме дали countryId съвпада
                if ($locationRec->countryId === $thisLocationRec->countryId) {
                    $isSimilar = true;
                }
            }

            if($isSimilar){
                $res[$locationRec->id] = (object)array('locationId' => $locationRec->id, 'readiness' => $locationRec->readiness, 'locationName' => $locationRec->locationName);
            }
        }

        return $res;
    }


    /**
     * Показва информация за резервираните количества
     */
    public function act_showDeliveryInfo()
    {
        requireRole('powerUser');
        expect($containerId = Request::get('containerId', 'int'));
        expect($replaceField = Request::get('replaceField', 'varchar'));

        $similarLocations = self::findDealsWithSameLocation($containerId);
        $links = '';
        $query = self::getQuery();
        $query->EXT('state', 'doc_Containers', 'externalName=state,externalKey=containerId');
        $query->in('id', array_keys($similarLocations));
        while($rec = $query->fetch()){
            try{
                $row = self::recToVerbal($rec);
                $Document = doc_Containers::getDocument($rec->containerId);

                $row->address = "{$row->countryId}, {$row->pCode} {$row->place}";
                $row->link = "<span class='state-{$rec->state} document-handler'>{$Document->getLink(0)} <small>{$row->address}</small></span> <small>{$row->readiness}</small>";

                $link = new core_ET("<div style='float:left;padding-bottom:2px;padding-top: 2px;' class='nowrap'>[#link#]</div>");
                $link->placeObject($row);
                $links .= $link->getContent();
            } catch(core_exception_Expect $e){
            }
        }

        $tpl = new core_ET($links);

        if (Request::get('ajax_mode')) {
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => $replaceField, 'html' => $tpl->getContent(), 'replace' => true);

            return array($resObj);
        }

        return $tpl;
    }


    /**
     * Изчислява готовността на продажбата
     *
     * @param stdClass $saleRec - запис на продажба
     * @param null|string $explain - описание на изчислението
     *
     * @return float|NULL - готовност между 0 и 1, или NULL ако няма готовност
     */
    public static function calcSaleReadiness($saleRec, &$explain = null)
    {
        // На не чакащите и не активни не се изчислява готовността
        if ($saleRec->state != 'pending' && $saleRec->state != 'active') return;

        // На бързите продажби също не се изчислява
        if (strpos($saleRec->contoActions, 'ship') !== false) return;

        $explain .= "Изчисляване <hr />";
        // Взимане на договорените и експедираните артикули по продажбата (събрани по артикул)
        $Sales = sales_Sales::getSingleton();
        core_Debug::startTimer('GET_DEAL_DATA');
        Mode::push('onlySimpleDealInfo', true);
        $dealInfo = $Sales->getAggregateDealInfo($saleRec);
        Mode::pop('onlySimpleDealInfo');
        core_Debug::stopTimer('GET_DEAL_DATA');

        $agreedProducts = $dealInfo->get('products');
        $shippedProducts = $dealInfo->get('shippedProducts');

        $explain .= "<li> Договорени: " . countR($agreedProducts) . " - Експедирани: " . countR($shippedProducts);

        $totalAmount = 0;
        $readyAmount = null;

        $productIds = arr::extractValuesFromArray($agreedProducts, 'productId');

        $pQuery = cat_Products::getQuery();
        $pQuery->show('canStore,isPublic');
        if(countR($productIds)){
            $pQuery->in('id', $productIds);
        } else {
            $pQuery->where("1=2");
        }
        $pRecs = $pQuery->fetchAll();
        $notPublicIds = array_filter($pRecs, function($pRec) {
            return $pRec->isPublic == 'no';
        });

        $explain .= "<li> от тях нестандартни: " . countR($notPublicIds);

        $aJobQuery = planning_Jobs::getQuery();
        $aJobQuery->where("#saleId = {$saleRec->id}");
        $aJobQuery->in("state", array('active', 'stopped', 'wakeup'));
        $aJobQuery->show('id,productId');
        if(countR($notPublicIds)){
            $aJobQuery->in('productId', array_keys($notPublicIds));
        } else {
            $aJobQuery->where("1=2");
        }
        $activeJobArr = $closedJobArr = array();
        while($aRec = $aJobQuery->fetch()) {
            $activeJobArr[$aRec->productId] = $aRec->id;
        }

        core_Debug::startTimer('GET_JOB_DATA');
        $cJobQuery = planning_Jobs::getQuery();
        $cJobQuery->where("#state = 'closed' AND #saleId = {$saleRec->id}");
        $cJobQuery->XPR('totalQuantity', 'double', 'SUM(#quantity)');
        $cJobQuery->XPR('totalQuantityProduced', 'double', 'SUM(COALESCE(#quantityProduced, 0))');
        $cJobQuery->XPR('totalCount', 'double', 'COUNT(#id)');
        $cJobQuery->show('productId,totalQuantity,totalQuantityProduced,totalCount');
        $cJobQuery->groupBy('productId');
        if(countR($notPublicIds)){
            $cJobQuery->in('productId', array_keys($notPublicIds));
        } else {
            $cJobQuery->where("1=2");
        }
        while($cRec = $cJobQuery->fetch()) {
            $closedJobArr[$cRec->productId] = $cRec;
        }
        core_Debug::stopTimer('GET_JOB_DATA');

        // За всеки договорен артикул
        $explain .= "<li> Обикаляме артикулите";

        foreach ($agreedProducts as $pId => $pRec) {
            $productRec = $pRecs[$pId];
            if ($productRec->canStore != 'yes') continue;

            $price = (isset($pRec->discount)) ? ($pRec->price - ($pRec->discount * $pRec->price)) : $pRec->price;
            $amount = null;

            // Ако няма цена се гледа мениджърската себестойност за да не е 0
            if(empty($price)){
                try{
                    $price = cat_Products::getPrimeCost($pId, $pRec->packagingId, 1, $saleRec->valior);
                } catch(core_exception_Expect $e){
                    wp($e, $saleRec, $pId);
                }
            }

            // Ако артикула е нестандартен и има приключено задание по продажбата и няма друго активно по нея
            $q = $pRec->quantity;

            $explain .= "<li> Артикул: Art{$pId} с к-во {$q} и цена {$price}";
            $ignore = false;
            if ($productRec->isPublic == 'no') {
                $explain .= "<li>--- e нестандартен";

                // Сумира се всичко произведено и планирано по задания за артикула по сделката, които са приключени
                $closedJobRec = $closedJobArr[$pId];
                $activeJobId = $activeJobArr[$pId];

                // Ако има приключени задания и няма други активни, се приема че е готово
                if ($closedJobRec->totalCount && !$activeJobId) {
                    $explain .= "<li>------ има приключени, но няма активни задания";

                    $q = $closedJobRec->totalQuantity;
                    $amount = $q * $price;
                    $explain .= "<li>------ сумата му е {$q} * {$price}";

                    // Ако има експедирано и то е над 90% от заскалденото, ще се маха продажбата
                    if (isset($shippedProducts[$pId])) {
                        $explain .= "<li>------ има и експедирано";

                        $produced = $closedJobRec->totalQuantityProduced;
                        if ($shippedProducts[$pId]->quantity >= ($produced * 0.9)) {
                            $explain .= "<li>------ експедираното {$shippedProducts[$pId]->quantity} е над " . $produced * 0.9 . " при произведено {$closedJobRec->totalQuantityProduced}";

                            $quantityInStore = store_Products::getQuantities($productRec->id)->quantity;
                            if ($quantityInStore <= 1) {
                                $explain .= "<li>------ и няма наличност - ПРОПУСКА СЕ";
                                $ignore = true;
                            }
                        }
                    }
                } else {
                    $explain .= "<li>------ приключени задания '{$closedJobRec->totalCount}', активно '{$activeJobId}'";
                }
            } else {
                $explain .= "<li>------ e стандартен";
            }

            // Количеството е неекспедираното
            if ($ignore === true) {
                $explain .= "<li>------ няма да му се търси количеството";
                $quantity = 0;
            } else {
                if (isset($shippedProducts[$pId])) {
                    $quantity = $q - $shippedProducts[$pId]->quantity;

                    $explain .= "<li>------ неекспедираното е {$quantity}";
                } else {
                    $quantity = $q;
                    $explain .= "<li>------ договореното е {$quantity}";
                }
            }

            // Ако всичко е експедирано се пропуска реда
            if ($quantity <= 0) {
                $explain .= "<li>------  ще се пропуска";
                continue;
            }

            $totalAmount += $quantity * $price;

            if (is_null($amount)) {

                // Изчислява се колко от сумата на артикула може да се изпълни
                $quantityInStock = store_Products::getQuantities($pId, $saleRec->shipmentStoreId)->quantity;
                $quantityInStock = ($quantityInStock > $quantity) ? $quantity : (($quantityInStock < 0) ? 0 : $quantityInStock);

                $amount = $quantityInStock * $price;
                $explain .= "<li>------ НЯМА задания, сумата му е  наличното {$quantityInStock} * {$price}";
            }

            // Събиране на изпълнената сума за всеки ред
            if (isset($amount)) {
                $explain .= "<li>------ ще участва в готовността със сума {$amount}";
                $readyAmount += $amount;
            } else {
                $explain .= "<li>------ няма сума";
            }

            $explain .= "<hr />";
        }

        // Готовността е процента на изпълнената сума от общата
        $readiness = (isset($readyAmount) && !empty($totalAmount)) ? @round($readyAmount / $totalAmount, 2) : null;

        $explain .= "<li>------ Готова сума: {$readyAmount}, обща сума: {$totalAmount}";

        // Подсигуряване че процента не е над 100%
        if ($readiness > 1) {
            $readiness = 1;
        }
        $explain .= "<li>------ Готовността е: {$readiness}";

        // Връщане на изчислената готовност или NULL ако не може да се изчисли
        return $readiness;
    }


    /**
     * Изчислява готовността на експедиционното нареждане
     *
     * @param stdClass $soRec - запис на ЕН
     *
     * @return float|NULL - готовност между 0 и 1, или NULL ако няма готовност
     */
    public static function calcSoReadiness($soRec)
    {
        // На не чакащите не се изчислява готовност
        if ($soRec->state != 'pending') {

            return;
        }

        // Намират се детайлите на ЕН-то
        $dQuery = store_ShipmentOrderDetails::getQuery();
        $dQuery->where("#shipmentId = {$soRec->id}");
        $dQuery->show('shipmentId,productId,packagingId,quantity,quantityInPack,price,discount,showMode');

        // Детайлите се сумират по артикул
        $all = deals_Helper::normalizeProducts(array($dQuery->fetchAll()));

        $totalAmount = 0;
        $readyAmount = null;

        // За всеки се определя колко % може да се изпълни
        foreach ($all as $pId => $pRec) {
            $price = (isset($pRec->discount)) ? ($pRec->price - ($pRec->discount * $pRec->price)) : $pRec->price;
            if(empty($price)){
                $price = cat_Products::getPrimeCost($pId, $pRec->packagingId, 1, $soRec->valior);
            }


            $totalAmount += $pRec->quantity * $price;

            // Определя се каква сума може да се изпълни
            $quantityInStock = store_Products::getQuantities($pId, $soRec->storeId)->quantity;
            $quantityInStock = ($quantityInStock > $pRec->quantity) ? $pRec->quantity : (($quantityInStock < 0) ? 0 : $quantityInStock);

            $amount = $quantityInStock * $price;

            if (isset($amount)) {
                $readyAmount += $amount;
            }
        }

        // Готовността е процент на изпълнената сума от общата
        $readiness = (isset($readyAmount)) ? @round($readyAmount / $totalAmount, 2) : null;

        // Връщане на изчислената готовност или NULL ако не може да се изчисли
        return $readiness;
    }


    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (haveRole('debug')) {
            $cronRec = core_Cron::getRecForSystemId('CacheSalesDeliveryData');
            $url = array('core_Cron', 'ProcessRun', str::addHash($cronRec->id), 'forced' => 'yes');
            $data->toolbar->addBtn('Обновяване', $url, 'title=Обновяване на модела,ef_icon=img/16/arrow_refresh.png,target=cronjob');
        }
    }


    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if($action == 'recalcreadiness' && isset($rec)){
            $Doc = doc_Containers::getDocument($rec->containerId);
            if(!$Doc->isInstanceOf('sales_Sales')){
                $res = 'no_one';
            } else {
                $state = $Doc->fetchField('state');
                if(!in_array($state, array('pending', 'active'))){
                    $res = 'no_one';
                }
            }
        }
    }

    function act_recalcreadiness()
    {
        $this->requireRightFor('recalcreadiness');
        expect($id = Request::get('id', 'int'));
        expect($rec = self::fetch($id));
        $this->requireRightFor('recalcreadiness', $rec);

        $Sale = doc_Containers::getDocument($rec->containerId);
        $explain = "<li>" . $Sale->getHyperlink(0);
        $readiness = self::calcSaleReadiness($Sale->fetch(), $explain);

        echo $explain;

        bp($readiness);
    }
}