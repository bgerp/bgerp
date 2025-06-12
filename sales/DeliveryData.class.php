<?php


/**
 * Модел за продуктови рейтинги
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

            Mode::push('calcOnlyDeliveryPart', true);
            core_Debug::startTimer('GET_LOGISTIC_DATA');
            $logisticData = $Class->getLogisticData($rec);
            core_Debug::stopTimer('GET_LOGISTIC_DATA');
            Mode::pop('calcOnlyDeliveryPart');
            $countryIds[$logisticData['toCountry']] = $countries[$logisticData['toCountry']];

            $newRec = new stdClass();

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

        if(countR($sync['insert'])){
            $this->saveArray($sync['insert']);
        }

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
            $dQuery->where("#docClass =" . sales_Sales::getClassId());

            while($d1 = $dQuery->fetch()){
                self::$cacheRecs['recs'][$d1->containerId] = $d1;
            }
        }

        $res = array();
        $thisLocationRec = self::$cacheRecs['recs'][$containerId];
        foreach (self::$cacheRecs['recs'] as $locationRec){
            if ($containerId == $locationRec->containerId) continue;

            if ($thisLocationRec->countryId == self::$cacheRecs['bgCountryId']) {
                if ($locationRec->place === $thisLocationRec->place) {
                    $res[$locationRec->id] = $locationRec->id;
                }
            } else {
                // Иначе проверяваме дали countryId съвпада
                if ($locationRec->countryId === $thisLocationRec->countryId) {
                    $res[$locationRec->id] = $locationRec->id;
                }
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
        $query->in('id', $similarLocations);
        while($rec = $query->fetch()){
            try{
                $row = self::recToVerbal($rec);
                $Document = doc_Containers::getDocument($rec->containerId);

                $row->address = "{$row->countryId}, {$row->pCode} {$row->place}";
                $row->link = "<span class='state-{$rec->state} document-handler'>{$Document->getLink(0)} <small>{$row->address}</small></span>";

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
     *
     * @return float|NULL - готовност между 0 и 1, или NULL ако няма готовност
     */
    public static function calcSaleReadiness($saleRec)
    {

        // На не чакащите и не активни не се изчислява готовността
        if ($saleRec->state != 'pending' && $saleRec->state != 'active') {

            return;
        }

        // На бързите продажби също не се изчислява
        if (strpos($saleRec->contoActions, 'ship') !== false) {

            return;
        }

        // Взимане на договорените и експедираните артикули по продажбата (събрани по артикул)
        $Sales = sales_Sales::getSingleton();
        core_Debug::startTimer('GET_DEAL_DATA');
        Mode::push('onlySimpleDealInfo', true);
        $dealInfo = $Sales->getAggregateDealInfo($saleRec);
        Mode::pop('onlySimpleDealInfo');
        core_Debug::stopTimer('GET_DEAL_DATA');

        $agreedProducts = $dealInfo->get('products');
        $shippedProducts = $dealInfo->get('shippedProducts');

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

        $aJobQuery = planning_Jobs::getQuery();
        $aJobQuery->where("#saleId = {$saleRec->id}");
        $aJobQuery->in("state", array('active', 'stopped', 'wakeup'));
        $aJobQuery->show('id,productId');
        if(countR($notPublicIds)){
            $aJobQuery->in('productId', array_keys($notPublicIds));
        } else {
            $aJobQuery->where("1=2");
        }
        $activeJobArr = array();
        while($aRec = $aJobQuery->fetch()) {
            $activeJobArr[$aRec->productId] = $aRec->id;
        }

        // За всеки договорен артикул
        foreach ($agreedProducts as $pId => $pRec) {
            $productRec = $pRecs[$pId];
            if ($productRec->canStore != 'yes') continue;

            $price = (isset($pRec->discount)) ? ($pRec->price - ($pRec->discount * $pRec->price)) : $pRec->price;
            $amount = null;

            // Ако няма цена се гледа мениджърската себестойност за да не е 0
            if(empty($price)){
                $price = cat_Products::getPrimeCost($pId, $pRec->packagingId, 1, $saleRec->valior);
            }

            // Ако артикула е нестандартен и има приключено задание по продажбата и няма друго активно по нея
            $q = $pRec->quantity;

            $ignore = false;
            if ($productRec->isPublic == 'no') {

                // Сумира се всичко произведено и планирано по задания за артикула по сделката, които са приключени
                core_Debug::startTimer('GET_LOGISTIC_DATA');
                $closedJobQuery = planning_Jobs::getQuery();
                $closedJobQuery->where("#productId = {$pId} AND #state = 'closed' AND #saleId = {$saleRec->id}");
                $closedJobQuery->XPR('totalQuantity', 'double', 'SUM(#quantity)');
                $closedJobQuery->XPR('totalQuantityProduced', 'double', 'SUM(COALESCE(#quantityProduced, 0))');
                $closedJobQuery->show('totalQuantity,totalQuantityProduced');
                $closedJobCount = $closedJobQuery->count();
                $closedJobRec = $closedJobQuery->fetch();
                $activeJobId = $activeJobArr[$pId];
                core_Debug::stopTimer('GET_JOB_DATA');

                // Ако има приключени задания и няма други активни, се приема че е готово
                if ($closedJobCount && !$activeJobId) {

                    $q = $closedJobRec->totalQuantity;
                    $amount = $q * $price;

                    // Ако има експедирано и то е над 90% от заскалденото, ще се маха продажбата
                    if (isset($shippedProducts[$pId])) {
                        $produced = $closedJobRec->totalQuantityProduced;
                        if ($shippedProducts[$pId]->quantity >= ($produced * 0.9)) {
                            $quantityInStore = store_Products::getQuantities($productRec->id)->quantity;
                            if ($quantityInStore <= 1) {
                                $ignore = true;
                            }
                        }
                    }
                }
            }

            // Количеството е неекспедираното
            if ($ignore === true) {
                $quantity = 0;
            } else {
                if (isset($shippedProducts[$pId])) {
                    $quantity = $q - $shippedProducts[$pId]->quantity;
                } else {
                    $quantity = $q;
                }
            }

            // Ако всичко е експедирано се пропуска реда
            if ($quantity <= 0) {
                continue;
            }

            $totalAmount += $quantity * $price;

            if (is_null($amount)) {

                // Изчислява се колко от сумата на артикула може да се изпълни
                $quantityInStock = store_Products::getQuantities($pId, $saleRec->shipmentStoreId)->quantity;
                $quantityInStock = ($quantityInStock > $quantity) ? $quantity : (($quantityInStock < 0) ? 0 : $quantityInStock);

                $amount = $quantityInStock * $price;
            }

            // Събиране на изпълнената сума за всеки ред
            if (isset($amount)) {
                $readyAmount += $amount;
            }
        }

        // Готовността е процента на изпълнената сума от общата
        $readiness = (isset($readyAmount) && !empty($totalAmount)) ? @round($readyAmount / $totalAmount, 2) : null;

        // Подсигуряване че процента не е над 100%
        if ($readiness > 1) {
            $readiness = 1;
        }

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
}