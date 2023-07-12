<?php


/**
 * Складове в отдалечени системи
 *
 *
 * @category  bgerp
 * @package   sync
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2020 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Наличности в отдалечени складове
 */
class sync_StoreStocks extends sync_Helper
{
    /**
     * Заглавие
     */
    public $title = 'Наличности във външни складове';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'store_Wrapper,plg_Sorting,plg_StyleNumbers';


    /**
     * Единично заглавие
     */
    public $singleTitle = 'Наличност във външен склад';


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
    public $canList = 'admin,ceo,storeWorker';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';


    /**
     * Кой може да сменя състоянието?
     */
    public $canChangestate = 'admin';


    /**
     * Полета, които се виждат
     */
    public $listFields = 'remoteCode=Код,productId=Артикул,syncedStoreId,measureId=Мярка,quantity,reservedQuantity,expectedQuantity,freeQuantity,reservedQuantityMin,expectedQuantityMin,freeQuantityMin,dateMin,lastSynced=Синхронизиране';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('remoteCode', 'varchar', 'caption=Външен код');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name)', 'caption=Наш артикул,tdClass=nameCell,silent');
        $this->FLD('syncedStoreId', 'key(mvc=sync_Stores,select=remoteName)', 'caption=Външен склад,tdClass=storeCol leftAlign');
        $this->FLD('quantity', 'double(maxDecimals=3)', 'caption=Налично,tdClass=stockCol');
        $this->FLD('reservedQuantity', 'double(maxDecimals=3)', 'caption=Днес->Запазено,tdClass=horizonCol red');
        $this->FLD('expectedQuantity', 'double(maxDecimals=3)', 'caption=Днес->Очаквано,tdClass=horizonCol green');
        $this->FNC('freeQuantity', 'double(maxDecimals=3)', 'caption=Днес->Разполагаемо,tdClass=horizonCol');

        $this->FLD('reservedQuantityMin', 'double(maxDecimals=3)', 'caption=Минимално->Запазено,tdClass=horizonCol red');
        $this->FLD('expectedQuantityMin', 'double(maxDecimals=3)', 'caption=Минимално->Очаквано,tdClass=horizonCol green');
        $this->FNC('freeQuantityMin', 'double(maxDecimals=3)', 'caption=Минимално->Разполагаемо,tdClass=horizonCol');
        $this->FLD('dateMin', 'date', 'caption=Минимално->Дата');
        $this->FLD('lastSynced', 'datetime(format=smartTime)', 'caption=Синхронизиране');

        $this->setDbUnique('syncedStoreId,productId');
        $this->setDbIndex('productId');
        $this->setDbIndex('syncedStoreId');
    }


    /**
     * След подготовка на филтъра
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->setFieldType('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,hasProperties=canStore,allowEmpty,maxSuggestions=10,forceAjax)');
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'productId,syncedStoreId';

        $syncedStores = sync_Stores::getStoreOptions();
        $data->listFilter->setOptions('syncedStoreId', array('' => '') + $syncedStores);
        $data->listFilter->input();

        if($filter = $data->listFilter->rec){
            if(!empty($filter->syncedStoreId)){
                $data->query->where("#syncedStoreId = {$filter->syncedStoreId}");
                unset($data->listFields['syncedStoreId']);
            }

            if(!empty($filter->productId)){
                $data->query->where("#productId = {$filter->productId}");
            }
        }

        $data->listFilter->toolbar->addSbBtn('Филтриране', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    }


    /**
     * Синхронизиране на складовите наличности с външните системи
     */
    function act_sync()
    {
        requireRole('admin');
        $this->cron_SyncRemoteStocks();

        followRetUrl(null, 'Наличностите са синхронизирани успешно');
    }


    /**
     * Крон метод за синхронизиране на складовите наличности
     */
    public function cron_SyncRemoteStocks()
    {
        // Има ли зададени складове за синхронизиране
        $sQuery = sync_Stores::getQuery();
        $sQuery->where("#state = 'active'");
        $remoteStores = $sQuery->fetchAll();
        if(!countR($remoteStores)) return;

        // Настъпило ли е времето да се синхронизират
        $cMinute = dt::mysql2verbal(null, 'i');
        foreach ($remoteStores as $k => $storeRec){
            $i = $cMinute + $storeRec->id;
            if($i % $storeRec->syncTime != 0){
                unset($remoteStores[$k]);
            }
        }
        if(!countR($remoteStores)) return;

        // Извличане на стандартните ни складируеми артикули
        $ourProducts = array();
        $pQuery = cat_Products::getQuery();
        $pQuery->where("#canStore = 'yes' AND #isPublic = 'yes'");
        $pQuery->EXT('measureSysId', 'cat_UoM', 'externalName=sysId,externalKey=measureId');
        $pQuery->show('id,code,measureSysId');
        while($pRec = $pQuery->fetch()){
            $ourProducts[$pRec->code] = array('code' => $pRec->code, 'measureSysId' => $pRec->measureSysId, 'id' => $pRec->id);
        }

        // Всички мерки от системата
        $measureData = array();
        $mQuery = cat_UoM::getQuery();
        $mRecs = $mQuery->fetchAll();
        foreach($mRecs as $mRec){
            $measureData[$mRec->sysId] = $mRec->id;
        }

        // Групиране по урл-та
        $save = $storeByUrl = array();
        foreach ($remoteStores as $rStore){
            if(!array_key_exists($rStore->url, $storeByUrl)){
                $storeByUrl[$rStore->url] = array('users' => array(), 'remoteIds' => array(), 'remoteMap' => array());
            }
            $storeByUrl[$rStore->url]['users'][$rStore->createdBy] = $rStore->createdBy;
            $storeByUrl[$rStore->url]['remoteIds'][$rStore->remoteId] = $rStore->remoteId;
            $storeByUrl[$rStore->url]['remoteMap'][$rStore->remoteId] = $rStore->id;
        }

        $now = dt::now();
        foreach ($storeByUrl as $url => $remoteArr){
            $createdBy = key($remoteArr['users']);

            $authorizationId = sync_Stores::getUserAuthorizationIdByUrl($url, $createdBy);
            if(!isset($authorizationId)){
                sync_Stores::logErr("Липсва оторизация за синхронизиране с външни складове от: '{$url}'");
                continue;
            }

            // Има ли права потребителя добавил записа все още за оторизация
            $authRec = remote_Authorizations::fetchRec($authorizationId);
            if (!($authRec->data->lKeyCC && $authRec->data->rId)){
                remote_Authorizations::logErr("Потребителя вече няма оторизация за: '{$url}'");
                continue;
            }

            // Опит за извличане на складовите наличности от външната система
            $stockData = remote_BgerpDriver::sendQuestion($authRec, 'store_Products', 'getStocks', array('stores' => $remoteArr['remoteIds']));
            if(!(is_array($stockData) && countR($stockData))) continue;

            foreach ($stockData as $arr){
                // Ако в тази система няма артикул с този, код то няма да се извлича
                if(!array_key_exists($arr['code'], $ourProducts)) continue;

                // Ако има се проверява дали мерките са различни
                if($arr['measureSysId'] != $ourProducts[$arr['code']]['measureSysId']){

                    // Ако са различни прави се опит за конверсия, ако не може значи не е този артикул
                    $ratio = cat_UoM::convertValue(1, $measureData[$arr['measureSysId']], $measureData[$ourProducts[$arr['code']]['measureSysId']]);
                    if($ratio === false) continue;

                    foreach (array('quantity', 'reservedQuantity', 'expectedQuantity', 'reservedQuantityMin', 'expectedQuantityMin') as $fld){
                        if(isset($arr[$fld])){
                            $arr[$fld] *= $ratio;
                        }
                    }
                }

                // Добавяне на мапнатия запис
                $obj = (object)array('remoteCode' => $arr['code'], 'productId' => $ourProducts[$arr['code']]['id'], 'syncedStoreId' => $remoteArr['remoteMap'][$arr['storeId']], 'lastSynced' => $now);
                foreach (array('quantity', 'reservedQuantity', 'expectedQuantity', 'reservedQuantityMin', 'expectedQuantityMin', 'dateMin') as $fld){
                    $obj->{$fld} = $arr[$fld];
                }
                $save[] = $obj;
            }
        }

        // Синхронизиране на старите с новите записи
        $query = self::getQuery();
        $exRecs = $query->fetchAll();
        $arrRes = arr::syncArrays($save, $exRecs, 'syncedStoreId,productId', 'quantity,reservedQuantity,expectedQuantity,reservedQuantityMin,expectedQuantityMin,dateMin');

        // Добавяне на новите записи
        if(countR($arrRes['insert'])){
            $this->saveArray($arrRes['insert']);
        }

        // Обновяване на новите записи
        if(countR($arrRes['update'])){
            $this->saveArray($arrRes['update'], 'id,quantity,reservedQuantity,expectedQuantity,reservedQuantityMin,expectedQuantityMin,dateMin,lastSynced');
        }

        // Изтриване на тези, които вече не се срещат
        if (countR($arrRes['delete'])) {
            $delete = implode(',', $arrRes['delete']);
            $this->delete("#id IN ({$delete})");
        }

        foreach ($remoteStores as $remoteRec){
            $remoteRec->lastSynced = $now;
            sync_Stores::save($remoteRec, 'lastSynced');
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows($mvc, $data)
    {
        // Ако няма никакви записи - нищо не правим
        if (!countR($data->recs)) return;

        foreach ($data->rows as $id => &$row) {
            $rec = &$data->recs[$id];

            $pRec = cat_Products::fetch($rec->productId, 'measureId,state');
            $row->productId = cat_Products::getVerbal($rec->productId, 'name');
            $icon = cls::get('cat_Products')->getIcon($rec->productId);
            $row->productId = ht::createLink($row->productId, cat_Products::getSingleUrlArray($rec->productId), false, "ef_icon={$icon}");
            $row->measureId = cat_UoM::getTitleById($pRec->measureId);
            $row->syncedStoreId = sync_Stores::getDisplayTitle($rec->syncedStoreId, true);

            $rec->freeQuantity = $rec->quantity - $rec->reservedQuantity + $rec->expectedQuantity;
            $row->freeQuantity = $mvc->getFieldType('freeQuantity')->toVerbal($rec->freeQuantity);

            $rec->freeQuantityMin = $rec->quantity - $rec->reservedQuantityMin + $rec->expectedQuantityMin;
            $row->freeQuantityMin = $mvc->getFieldType('freeQuantityMin')->toVerbal($rec->freeQuantityMin);
            $row->ROW_ATTR['class'] = "state-{$pRec->state}";
        }
    }


    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (haveRole('admin')) {
            $data->toolbar->addBtn('Синхронизиране', array($mvc, 'sync', 'ret_url' => true), 'title=Синхронизиране на наличностите във външни системи,ef_icon=img/16/arrow_refresh.png');
        }
    }
}