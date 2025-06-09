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
        $containers = $toSave = array();
        $salesClassId = sales_Sales::getClassId();
        $shipmentClassId = store_ShipmentOrders::getClassId();
        $cQuery = doc_Containers::getQuery();
        $cQuery->where("#docClass IN ({$salesClassId}, {$shipmentClassId}) AND #state IN ('active', 'pending')");
        $cQuery->show('id, folderId, state, docClass');
        while($rec = $cQuery->fetch()){
            $containers[$rec->docClass][$rec->id] = $rec->id;
        }

        $fullRecs = array();
        $sQuery = sales_Sales::getQuery();
        $sQuery->in("containerId", $containers[$salesClassId]);
        while($sRec = $sQuery->fetch()){
            $sRec->_classId = $salesClassId;
            $fullRecs[$sRec->containerId] = $sRec;
        }

        $shQuery = store_ShipmentOrders::getQuery();
        $shQuery->where("#state = 'pending'");
        $shQuery->in("containerId", $containers[$shipmentClassId]);
        while($shRec = $shQuery->fetch()){
            $shRec->_classId = $shipmentClassId;
            $fullRecs[$shRec->containerId] = $shRec;
        }
        $docCount = countR($fullRecs);

        // Извличане на данните за доставка
        core_App::setTimeLimit(0.2 * $docCount, false, 300);
        $countryIds = array();
        foreach ($fullRecs as $rec){
            $Class = cls::get($rec->_classId);

            Mode::push('calcOnlyDeliveryPart', true);
            core_Debug::startTimer('GET_LOGISTIC_DATA');
            $logisticData = $Class->getLogisticData($rec);
            core_Debug::stopTimer('GET_LOGISTIC_DATA');
            Mode::pop('calcOnlyDeliveryPart');

            if(!array_key_exists($logisticData['toCountry'], $countryIds)){
                $countryIds[$logisticData['toCountry']] = drdata_Countries::getIdByName($logisticData['toCountry']);
            }

            $newRec = new stdClass();
            $newRec->countryId = $countryIds[$logisticData['toCountry']];
            $newRec->place = $logisticData['toPlace'];
            $newRec->pCode = $logisticData['toPCode'];
            $newRec->address = $logisticData['toAddress'];
            $newRec->containerId = $rec->containerId;
            $newRec->classId = $rec->_classId;
            $toSave[$rec->containerId] = $newRec;
        }

        // Синхронизиране на съществуващите записи с новите
        $eQuery = self::getQuery();
        $exRecs = $eQuery->fetchAll();
        $sync = arr::syncArrays($toSave, $exRecs, 'containerId', 'countryId,place,pCode,address');

        core_Debug::log("GET GET_LOGISTIC_DATA " . round(core_Debug::$timers["GET_LOGISTIC_DATA"]->workingTime, 6));

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
}