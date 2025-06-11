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
    public $listFields = 'id,containerId,countryId,place,pCode,address';


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
        $this->FLD('address', 'varchar(255)', 'caption=Адрес,class=contactData,export=Csv');

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

            if($Class instanceof sales_Sales){
                core_Debug::startTimer('GET_READY_PERCENTAGE');
                sales_reports_ShipmentReadiness::calcSaleReadiness($rec);
                core_Debug::stopTimer('GET_READY_PERCENTAGE');
            }


            $countryIds[$logisticData['toCountry']] = $countries[$logisticData['toCountry']];

            $newRec = new stdClass();
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
        $sync = arr::syncArrays($toSave, $exRecs, 'containerId', 'countryId,place,pCode,address');

        core_Debug::log("GET GET_LOGISTIC_DATA " . round(core_Debug::$timers["GET_LOGISTIC_DATA"]->workingTime, 6));
        core_Debug::log("GET GET_READY_PERCENTAGE " . round(core_Debug::$timers["GET_READY_PERCENTAGE"]->workingTime, 6));

        if(countR($sync['insert'])){
            $this->saveArray($sync['insert']);
        }

        if(countR($sync['update'])){
            $this->saveArray($sync['update'], 'id,countryId,place,pCode,address');
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

                $link = new core_ET("<div style='float:left;padding-bottom:2px;padding-top: 2px;'>[#link#]</div>");
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
}