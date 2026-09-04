<?php


/**
 * Клас 'store_TransfersDetails'
 *
 * Детайли на мениджър на детайлите на междускладовите трансфери (@see store_Transfers)
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_TransfersDetails extends doc_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на междускладовите трансфери';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'transferId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, doc_plg_DetailRevisions, plg_Sorting, store_Wrapper, cat_plg_LogPackUsage, deals_plg_ImportDealDetailProduct, plg_RowNumbering, plg_AlignDecimals2, plg_PrevAndNext,plg_SaveAndNew,cat_plg_ShowCodes,store_plg_TransportDataDetail';


    /**
     * В кои състояния на мастъра промяната на ред е нова ревизия (@see doc_plg_DetailRevisions)
     */
    public $detailRevisionsStates = 'pending';


    /**
     * Кой има право да импортира?
     */
    public $canImport = 'ceo, store';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, store';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, packQuantity=К-во, requestedQuantity=Заяв., loadedQuantity=Изпр., executedQuantity=Получ., weight=Тегло, volume=Обем, transUnitId = ЛЕ';
    
    
    /**
     * Активен таб
     */
    public $currentTab = 'Междускладови трансфери';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Да се показва ли кода като в отделна колона
     */
    public $showCodeColumn = true;
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'requestedQuantity,loadedQuantity,executedQuantity,weight,volume,transUnitId,transUnitQuantity';


    /**
     * Може ли да се импортират цени
     */
    public $allowPriceImport = false;


    /**
     * Полета, които се експортват
     */
    public $exportToMaster = 'quantity, productId=code';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('transferId', 'key(mvc=store_Transfers)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,hasProperties=canStore,hasnotProperties=generic,maxSuggestions=100,forceAjax,titleFld=name,forceOpen)', 'class=w100,caption=Артикул,mandatory,silent,refreshForm,tdClass=productCell leftCol wrap,oldFieldName=newProductId');
        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка,mandatory,smartCenter,input=hidden,tdClass=small-field nowrap');
        $this->FLD('quantity', 'double', 'caption=Количество,input=none');
        $this->FLD('quantityInPack', 'double(decimals=2)', 'input=none,column=none');
        $this->FNC('packQuantity', 'double(min=0)', 'caption=Количество,input,mandatory,tdClass=mainQuantityCol');

        // К-та по етапи - в основна мярка, като #quantity (@see store_Transfers::getQuantityFieldName)
        $this->FLD('requestedQuantity', 'double', 'caption=Заявено,smartCenter,tdClass=stageCol');
        $this->FLD('loadedQuantity', 'double', 'caption=Изпратено,smartCenter,tdClass=stageCol');
        $this->FLD('executedQuantity', 'double', 'caption=Получено,smartCenter,tdClass=stageCol');

        $this->setDbIndex('productId');
    }


    /**
     * В кой етап на заявката е мастърът - за сканирането по тегловен баркод
     *
     * @param int $masterId
     *
     * @return string|null - loading|execution|NULL
     *
     * @see wbarcode_plg_AddByBarcode
     */
    public function getWbarcodeScanStage_($masterId)
    {
        $masterRec = store_Transfers::fetchRec($masterId, 'state,pendingStage');
        if (empty($masterRec) || $masterRec->state != 'pending' || empty($masterRec->pendingStage)) {

            return null;
        }

        return $masterRec->pendingStage;
    }


    /**
     * В етап сканираното к-во се натрупва към к-то на самия етап, а не към последно въведеното
     *
     * @param stdClass $rec
     *
     * @return float|NULL - к-то в опаковки или NULL, ако мастърът не е в етап
     *
     * @see wbarcode_plg_AddByBarcode
     */
    public function getWbarcodeRowQuantity_($rec)
    {
        $fieldName = store_Transfers::getQuantityFieldName($rec->{$this->masterKey});
        if ($fieldName == 'requestedQuantity') {

            return null;
        }

        $quantityInPack = !empty($rec->quantityInPack) ? $rec->quantityInPack : 1;

        // Празната колона на етапа значи начало от нула, а не от к-то на предходния етап
        return isset($rec->{$fieldName}) ? $rec->{$fieldName} / $quantityInPack : 0;
    }


    /**
     * Въведеното к-во се записва и в колоната на текущия етап на мастъра
     */
    protected static function on_BeforeSave($mvc, &$id, $rec, $fields = null, $mode = null)
    {
        if (!empty($rec->_skipDetailRevision) || !empty($rec->_skipStageQuantity) || !empty($fields) || !isset($rec->quantity)) {

            return;
        }

        $fieldName = store_Transfers::getQuantityFieldName($rec->{$mvc->masterKey});
        $rec->{$fieldName} = $rec->quantity;
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     */
    protected static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (!isset($rec->quantity) || empty($rec->quantityInPack)) {
            
            return;
        }
        
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * В чернова етапите още не са започнали - показва се само текущото к-во
     */
    protected static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
        $state = $data->masterData->rec->state ?? null;
        if (!isset($state) && !empty($data->masterId)) {
            $state = $mvc->Master->fetchField($data->masterId, 'state');
        }

        if ($state == 'draft') {
            unset($data->listFields['requestedQuantity'], $data->listFields['loadedQuantity'], $data->listFields['executedQuantity']);
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (empty($rec->quantityInPack) || !empty($rec->_stageQuantitiesInPacks)) {

            return;
        }
        $rec->_stageQuantitiesInPacks = true;

        // К-тата по етапи се пазят в основна мярка, а се показват в опаковки, като #packQuantity.
        // Обръща се и в записа, защото plg_AlignDecimals2 подравнява по стойността от него
        foreach (array('requestedQuantity', 'loadedQuantity', 'executedQuantity') as $fieldName) {
            if (isset($rec->{$fieldName})) {
                $rec->{$fieldName} /= $rec->quantityInPack;
                $row->{$fieldName} = $mvc->getFieldType($fieldName)->toVerbal($rec->{$fieldName});
            }
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'add' || $action == 'edit' || $action == 'delete' || $action == 'import') && isset($rec)) {

            // Импортът минава през store_Transfers::addRow, който иска чернова
            $allowedStates = arr::make($mvc->detailRevisionsStates, true);
            $allowedStates['draft'] = 'draft';
            if ($action == 'import') {
                $allowedStates = array('draft' => 'draft');
            }

            if (!in_array($mvc->Master->fetchField($rec->transferId, 'state'), $allowedStates)) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * След обработка на записите от базата данни
     */
    protected static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        if (countR($data->rows)) {
            foreach ($data->rows as $i => &$row) {
                $rec = &$data->recs[$i];
                
                $singleUrl = cat_Products::getSingleUrlArray($rec->productId);
                $row->productId = cat_Products::getVerbal($rec->productId, 'name');
                $row->productId = ht::createLinkRef($row->productId, $singleUrl);
                
                if (empty($rec->quantity) && !Mode::isReadOnly()) {
                    $row->ROW_ATTR['style'] = ' background-color:#f1f1f1;color:#777';
                    $row->ROW_ATTR['class'] = (!empty($row->ROW_ATTR['class']) ? $row->ROW_ATTR['class'] : '') . ' zeroQuantityRow';
                }
                
                // Показваме подробната информация за опаковката при нужда
                deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
            }
        }
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        if (!countR($data->recs)) {
            
            return;
        }
        
        foreach ($data->rows as $id => $row) {
            $rec = $data->recs[$id];

            $deliveryDate = !empty($data->masterData->rec->deliveryTime) ? $data->masterData->rec->deliveryTime : $data->masterData->rec->valior;
            deals_Helper::getQuantityHint($row->packQuantity, $mvc, $rec->productId, $data->masterData->rec->fromStore, $rec->quantity, $data->masterData->rec->state, $deliveryDate);
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        
        $form->setField('requestedQuantity, loadedQuantity, executedQuantity', 'input=none');

        // В етап на заявката се въвежда к-то на текущия етап, а предходните са само за информация
        if (!empty($rec->transferId)) {
            $fieldName = store_Transfers::getQuantityFieldName($rec->transferId);

            if ($fieldName != 'requestedQuantity') {

                // При опресняване на формата к-тата ги няма в записа - взимат се от базата
                $dbRec = !empty($rec->id) ? $mvc->fetch($rec->id) : null;
                $quantityInPack = !empty($dbRec->quantityInPack) ? $dbRec->quantityInPack : 1;

                // Празно поле изчиства к-то на етапа - задължително е само ако няма к-во
                // от предходен етап, към което да се върне редът
                $rec->packQuantity = isset($dbRec->{$fieldName}) ? $dbRec->{$fieldName} / $quantityInPack : null;
                $prevQuantity = isset($dbRec) ? static::getPrevStageQuantity($dbRec, $fieldName) : null;
                $form->setField('packQuantity', array('caption' => $mvc->getField($fieldName)->caption, 'mandatory' => !isset($prevQuantity)));
                $form->setField('packQuantity', 'focus');
            }
        }

        // Логистичната секция се свива, ако е празна - за детайлите режимът на
        // plg_PrevAndNext е винаги включен и store_plg_TransportDataDetail не слага autohide
        foreach (array('weight', 'netWeight', 'tareWeight', 'volume', 'transUnitId', 'transUnitQuantity') as $fld) {
            if ($form->getField($fld, false)) {
                $form->setField($fld, 'autohide');
            }
        }

        if(empty($rec->productId)){
            $form->setField('packagingId', 'input=none');
        }
        if (isset($rec->id)) {
            $form->setReadOnly('productId');
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        $rec = &$form->rec;
        
        if (!empty($rec->productId)) {
            $masterRec = store_Transfers::fetch($rec->transferId, 'fromStore,deliveryTime,valior');
            $deliveryDate = !empty($masterRec->deliveryTime) ? $masterRec->deliveryTime : $masterRec->valior;
            $storeInfo = deals_Helper::checkProductQuantityInStore($rec->productId, $rec->packagingId ?? null, $rec->packQuantity ?? null, $masterRec->fromStore, $deliveryDate);
            $form->info = $storeInfo->formInfo;
            
            $packs = cat_Products::getPacks($rec->productId, $rec->packagingId ?? null);
            $form->setField('packagingId', 'input');
            $form->setOptions('packagingId', $packs);
            $form->setDefault('packagingId', key($packs));
        }

        if ($form->isSubmitted()) {
            $stageField = !empty($rec->transferId) ? store_Transfers::getQuantityFieldName($rec->transferId) : 'requestedQuantity';
            $inStage = ($stageField != 'requestedQuantity');

            // В етап празното поле изчиства к-то на етапа, а редът се връща към предходния
            $oldRec = ($inStage && !empty($rec->id)) ? $mvc->fetch($rec->id) : null;
            $clearStage = !empty($oldRec) && !isset($rec->packQuantity);

            if ($clearStage) {
                $prevQuantity = static::getPrevStageQuantity($oldRec, $stageField);
                $rec->{$stageField} = null;
                $rec->quantity = isset($prevQuantity) ? $prevQuantity : $oldRec->quantity;
                $rec->_skipStageQuantity = true;
            } else {
                if (empty($rec->packQuantity)) {
                    $form->setWarning('packQuantity', 'Въведено е количество|* <b>0</b>?');
                }

                // Проверка на к-то
                $warning = null;
                if (!deals_Helper::checkQuantity($rec->packagingId, $rec->packQuantity, $warning)) {
                    $form->setWarning('packQuantity', $warning);
                }

                $pInfo = cat_Products::getProductInfo($rec->productId);
                $rec->quantityInPack = !empty($pInfo->packagings[$rec->packagingId]) ? $pInfo->packagings[$rec->packagingId]->quantity : 1;

                $rec->quantity = $rec->packQuantity * $rec->quantityInPack;
            }

            // Ако к-то на етапа и нищо друго в реда не са променени - няма нова ревизия
            if ($inStage && !empty($oldRec)) {
                $newQuantity = $clearStage ? null : $rec->quantity;
                $oldQuantity = $oldRec->{$stageField} ?? null;

                $isSame = (isset($newQuantity) == isset($oldQuantity));
                if ($isSame && isset($newQuantity)) {
                    $isSame = (round($newQuantity, 5) == round($oldQuantity, 5));
                }

                if ($isSame && !static::isRowChangedOutsideQuantity($mvc, $form, $rec, $oldRec)) {
                    $rec->_skipDetailRevision = true;
                }
            }
        }

        static::setPrevStageFields($mvc, $form);
    }


    /**
     * К-тата от предходните етапи - само за информация. Добавят се след въвеждането,
     * за да не участват в него, и се показват в текущата опаковка
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     *
     * @return void
     */
    private static function setPrevStageFields($mvc, $form)
    {
        $rec = $form->rec;
        if (empty($rec->transferId)) {

            return;
        }

        $stageField = store_Transfers::getQuantityFieldName($rec->transferId);
        if ($stageField == 'requestedQuantity') {

            return;
        }

        // При опресняване на формата к-тата ги няма в записа - взимат се от базата
        $dbRec = !empty($rec->id) ? $mvc->fetch($rec->id) : null;

        $packagingId = $rec->packagingId ?? ($dbRec->packagingId ?? null);
        $quantityInPack = !empty($dbRec->quantityInPack) ? $dbRec->quantityInPack : 1;
        if (!empty($rec->productId) && !empty($packagingId)) {
            $pInfo = cat_Products::getProductInfo($rec->productId);
            $quantityInPack = !empty($pInfo->packagings[$packagingId]) ? $pInfo->packagings[$packagingId]->quantity : 1;
        }

        $unit = !empty($packagingId) ? cat_UoM::getShortName($packagingId) : null;
        $prevFields = ($stageField == 'loadedQuantity') ? array('requestedQuantity') : array('requestedQuantity', 'loadedQuantity');

        // Етап без к-во излиза с 0, за да се вижда, че по него не е минавало
        foreach ($prevFields as $prevField) {
            $quantity = isset($dbRec->{$prevField}) ? $dbRec->{$prevField} / $quantityInPack : 0;

            $params = "caption={$mvc->getField($prevField)->caption},input,before=packQuantity";
            $params .= isset($unit) ? ",unit={$unit}" : '';

            // Типът е като на самите колони - при smartRound нулата се вербализира
            // до '0' и readOnly полето излиза празно (@see ht::createSmartSelect)
            $form->FNC("prev{$prevField}", 'double', $params);
            $form->setReadOnly("prev{$prevField}", $quantity);
        }
    }


    /**
     * К-то от последния попълнен етап преди текущия
     *
     * @param stdClass $rec
     * @param string   $stageField - поле на текущия етап
     *
     * @return float|NULL
     */
    private static function getPrevStageQuantity($rec, $stageField)
    {
        $stages = array('executedQuantity', 'loadedQuantity', 'requestedQuantity');
        $prevFields = array_slice($stages, array_search($stageField, $stages) + 1);

        foreach ($prevFields as $fieldName) {
            if (isset($rec->{$fieldName})) {

                return $rec->{$fieldName};
            }
        }

        return null;
    }
    
    
    /**
     * Променено ли е нещо в реда, извън количеството - сравняват се полетата от формата
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     * @param stdClass  $rec    - записът от формата
     * @param stdClass  $oldRec - записът от преди редакцията
     *
     * @return bool
     */
    private static function isRowChangedOutsideQuantity($mvc, $form, $rec, $oldRec)
    {
        $skipFields = arr::make('packQuantity,quantity,quantityInPack,requestedQuantity,loadedQuantity,executedQuantity', true);

        foreach ($form->selectFields("#input != 'none'") as $name => $fld) {
            if (isset($skipFields[$name]) || empty($mvc->fields[$name])) {
                continue;
            }

            $newValue = $rec->{$name} ?? null;
            $oldValue = $oldRec->{$name} ?? null;

            if ($mvc->getFieldType($name) instanceof type_Double) {
                if (round((float) $newValue, 5) != round((float) $oldValue, 5)) {

                    return true;
                }
            } elseif ((string) $newValue !== (string) $oldValue) {

                return true;
            }
        }

        return false;
    }
    
    
    /**
     * Метод по пдоразбиране на getRowInfo за извличане на информацията от реда
     */
    protected static function on_AfterGetRowInfo($mvc, &$res, $rec)
    {
        $rec = $mvc->fetchRec($rec);
        $toStoreId = store_Transfers::fetchField($rec->transferId, 'toStore');
        $res->operation['in'] = $toStoreId;
    }


    /**
     * Импортиране на артикул генериран от ред на csv файл
     *
     * @param int   $masterId - ид на мастъра на детайла
     * @param array $row      - Обект представляващ артикула за импортиране
     *                        ->code - код/баркод на артикула
     *                        ->quantity - К-во на опаковката или в основна мярка
     *                        ->price - цената във валутата на мастъра, ако няма се изчислява директно
     *                        ->pack - Опаковката
     *                        ->batch - Партида ако има
     *
     * @return mixed - резултата от експорта
     */
    public function import($masterId, $row)
    {
        $pRec = cat_Products::getByCode($row->code);
        $pRec->packagingId = (isset($row->pack)) ? $row->pack : $pRec->packagingId;
        $packRec = cat_products_Packagings::getPack($pRec->productId, $pRec->packagingId);
        $quantityInPack  = is_object($packRec) ? $packRec->quantity : 1;

        return store_Transfers::addRow($masterId, $pRec->productId, $pRec->packagingId, $row->quantity, $quantityInPack, $row->batch);
    }


    /**
     * След извличане на експорт на полетата за csv
     *
     * @param $mvc
     * @param $fieldset
     * @return void
     */
    protected static function on_AfterGetCsvExportDetailFieldset($mvc, &$fieldset)
    {
        deals_Helper::getExportCsvProductFieldset($mvc, $fieldset);
    }


    /**
     * Взимане на детайлите за експорт в csv
     *
     * @param $mvc
     * @param $masterRec
     * @param $expandedRecs
     * @param $detailFields
     * @param $fieldset
     * @return void
     */
    protected static function on_AfterGetCsvExportDetailRecs($mvc, $masterRec, &$expandedRecs, &$fieldset)
    {
        deals_Helper::addCsvExportProductRecs4Master($mvc, $masterRec, $expandedRecs);
    }
}
