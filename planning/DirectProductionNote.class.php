<?php


/**
 * Клас 'planning_DirectProductionNote' - Документ за производство
 *
 *
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_DirectProductionNote extends planning_ProductionDocument
{
    /**
     * Заглавие
     */
    public $title = 'Протоколи за производство';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Mpn';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'acc_TransactionSourceIntf=planning_transaction_DirectProductionNote,acc_AllowArticlesCostCorrectionDocsIntf,label_SequenceIntf=planning_interface_ProductionNoteImpl';
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_plg_StoreFilter, deals_plg_SaveValiorOnActivation, planning_Wrapper, acc_plg_DocumentSummary, acc_plg_Contable,
                    doc_DocumentPlg, plg_Printing, plg_Clone, bgerp_plg_Blank,doc_plg_HidePrices, deals_plg_SetTermDate, plg_Sorting,cat_plg_AddSearchKeywords, plg_Search, store_plg_StockPlanning';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'debitAmount';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'productId,storeId,inputStoreId,expenseItemId,note';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,planning,store,production';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,planning,store,production';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,planning,store,production';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,planning,store,production';
    
    
    /**
     * Кой има право да контира?
     */
    public $canConto = 'ceo,planning,store,production';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo,planning,store,production';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Протокол за производство';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'planning/tpl/SingleLayoutDirectProductionNote.shtml';
    
    
    /**
     * Детайл
     */
    public $details = 'planning_DirectProductNoteDetails';
    
    
    /**
     * Кой е главния детайл
     *
     * @var string - име на клас
     */
    public $mainDetail = 'planning_DirectProductNoteDetails';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'planning_DirectProductNoteDetails';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/page_paste.png';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior, title=Документ, productId, packQuantity=К-во, packagingId=Мярка,storeId=В склад,expenseItemId=Разход за, folderId, deadline, createdOn, createdBy';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'deadline,expenseItemId,storeId';
    
    
    /**
     * Какво движение на партида поражда документа в склада
     *
     * @param out|in|stay - тип движение (излиза, влиза, стои)
     */
    public $batchMovementDocument = 'in';
    
    
    /**
     * Нужно ли е да има детайл, за да стане на 'Заявка'
     */
    public $requireDetailForPending = false;
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, valior,modifiedOn';
    
    
    /**
     * Плейдхолдър където да се показват партидите
     */
    public $batchPlaceholderField = 'batch';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        parent::setDocumentFields($this);
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,mandatory,before=storeId,removeAndRefreshForm=packagingId|quantityInPack|quantity|packQuantity|additionalMeasureId|additionalMeasureQuantity,silent');
        $this->FLD('jobQuantity', 'double(smartRound)', 'caption=Задание,input=hidden,after=productId');
        
        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка', 'mandatory,input=hidden,before=packQuantity,silent,removeAndRefreshForm=additionalMeasureId|additionalMeasureQuantity');
        $this->FNC('packQuantity', 'double(Min=0,smartRound)', 'caption=Количество,input,mandatory,after=jobQuantity');

        $this->FLD('expenses', 'percent(Min=0)', 'caption=Реж. разходи,after=packQuantity');
        $this->FLD('quantityInPack', 'double(smartRound)', 'input=none,notNull,value=1');
        $this->FLD('quantity', 'double(smartRound,Min=0)', 'caption=Количество,input=none');
        
        $this->FLD('additionalMeasureId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Втора мярка->Избор', 'input=none,after=expenses');
        $this->FLD('additionalMeasureQuantity', 'double(Min=0,smartRound)', 'caption=Втора мярка->Количество,input=none');
        
        $this->setField('deadline', 'caption=Информация->Срок до');
        $this->setField('storeId', 'caption=Информация->Засклаждане в,silent,removeAndRefreshForm');
        $this->FLD('inputStoreId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Информация->Влагане от,input');
        $this->FLD('debitAmount', 'double(decimals=2)', 'input=none');
        $this->FLD('expenseItemId', 'acc_type_Item(select=titleNum,allowEmpty,lists=600,allowEmpty)', 'input=none,after=expenses,caption=Разходен обект / Продажба->Избор');
        
        $this->setField('note', 'caption=Информация->Бележки,after=deadline');
        $this->FLD('equalizePrimeCost', 'enum(yes=Да,no=Не)', 'caption=Допълнително->Изравняване на сб-ст,notNull,value=yes,after=deadline,autohide=any');

        $this->setDbIndex('productId');
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     */
    protected static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (empty($rec->quantity) || empty($rec->quantityInPack)) {
            
            return;
        }
        
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * Подготвя формата за редактиране
     */
    public function prepareEditForm_($data)
    {
        parent::prepareEditForm_($data);
        $form = &$data->form;
        $rec = $form->rec;
        
        $originDoc = doc_Containers::getDocument($form->rec->originId);
        $originRec = $originDoc->rec();
        
        $storeId = $originRec->storeId;
        $saleId = $originRec->saleId;
        if($originDoc->isInstanceOf('planning_Tasks')){
            $jobRec = doc_Containers::getDocument($originRec->originId)->fetch();
            $storeId = $jobRec->storeId;
            $saleId = $jobRec->saleId;
            $productOptions = planning_ProductionTaskProducts::getOptionsByType($originDoc->that, 'production');
        } else {
            $jobRec = $originDoc->fetch();
            $productOptions = array($originRec->productId => cat_Products::getTitleById($originRec->productId, false));
        }
        
        $form->setDefault('storeId', $storeId);
        $form->setOptions('productId', $productOptions);
        $form->setDefault('productId', key($productOptions));

        if(isset($rec->productId)){
            $packs = cat_Products::getPacks($rec->productId);

            // Ако артикула не е складируем, скриваме полето за мярка
            $productRec = cat_Products::fetch($rec->productId, 'canStore,fixedAsset,canConvert,measureId');

            $secondMeasureDerivitives = array();
            $measureDerivitives = cat_UoM::getSameTypeMeasures($productRec->measureId);
            if($secondMeasureId = cat_products_Packagings::getSecondMeasureId($rec->productId)){
                $secondMeasureDerivitives = cat_UoM::getSameTypeMeasures($secondMeasureId);
            }

            if($originDoc->isInstanceOf('planning_Jobs')){
                $originPackId = $originRec->packagingId;
                $form->setDefault('jobQuantity', $originRec->quantity);
                $quantityFromTasks = planning_Tasks::getProducedQuantityForJob($originRec->id);

                $quantityToStore = $quantityFromTasks - $originRec->quantityProduced;
                if ($quantityToStore > 0) {
                    $form->setDefault('packQuantity', $quantityToStore / $originRec->quantityInPack);
                }
            } else {
                
                // Ако задачата е за крайния артикул записваме к-то му от заданието
                if($rec->productId == $jobRec->productId){
                    $form->setDefault('jobQuantity', $jobRec->quantity);
                }
                
                $info = planning_ProductionTaskProducts::getInfo($originDoc->that, $rec->productId, 'production');
                $producedQuantity = $originDoc->fetchField('producedQuantity');
                $info->totalQuantity -= $producedQuantity;
                $originPackId = $info->packagingId;

                $form->setDefault('packagingId', $info->packagingId);
                if ($info->totalQuantity > 0) {
                    $form->setDefault('packQuantity', $info->totalQuantity);
                }
            }

            if(!array_key_exists($originPackId, $secondMeasureDerivitives)){
                $packs = array_diff_key($packs, $secondMeasureDerivitives);
            } else {
                $packs = array_diff_key($packs, $measureDerivitives);
            }

            $form->setOptions('packagingId', $packs);
            $form->setDefault('packagingId', $originRec->packagingId);
            if ($productRec->canStore == 'no') {
                $measureShort = cat_UoM::getShortName($rec->packagingId);
                $form->setField('packQuantity', "unit={$measureShort}");
                
                // Ако артикула е нескладируем и не е вложим и не е ДА, показваме полето за избор на разходно перо
                if ($productRec->canConvert == 'no' && $productRec->fixedAsset == 'no') {
                    $form->setField('expenseItemId', 'input');
                }
                
                // Ако заданието, към което е протокола е към продажба, избираме я по дефолт
                if (empty($rec->id) && isset($saleId)) {
                    $saleItem = acc_Items::fetchItem('sales_Sales', $saleId);
                    $form->setDefault('expenseItemId', $saleItem->id);
                }
                
                $form->setField('storeId', 'input=none');
                $form->setField('inputStoreId', array('caption' => 'Допълнително->Влагане от'));
            } else {
                $form->setField('packagingId', 'input');
                if(cat_Products::haveDriver($rec->productId, 'planning_interface_StageDriver')){
                    $form->setField('inputStoreId', 'mandatory');
                }
            }
            
            $bomRec = cat_Products::getLastActiveBom($rec->productId, 'production,sales');
            if (isset($bomRec->expenses)) {
                $form->setDefault('expenses', $bomRec->expenses);
            }
            
            // Ако има избрана опаковка
            if(isset($rec->packagingId)){
                $additionalMeasures = cat_Products::getPacks($rec->productId, true);

                // Ако е опаковка и има други мерки различно от основната избират се те
                $packType = cat_UoM::fetchField($rec->packagingId, 'type');
                if($packType == 'uom'){
                    $similarMeasures = cat_UoM::getSameTypeMeasures($rec->packagingId);
                } else {
                    $similarMeasures = cat_UoM::getSameTypeMeasures($productRec->measureId);
                }

                // От допълнителните мерки махам подобните на тези от главната опаковка/мярка
                unset($similarMeasures['']);
                unset($additionalMeasures[$rec->packagingId]);
                $additionalMeasures = array_diff_key($additionalMeasures, $similarMeasures);
                $additionalMeasureCount = countR($additionalMeasures);

                // Показване на избор на допълнителната мярка
                if($additionalMeasureCount){
                    $form->setField('additionalMeasureQuantity', 'input');
                    $secondMeasureId = key($additionalMeasures);
                    if($additionalMeasureCount == 1){
                        $form->setField('additionalMeasureId', 'input=hidden');
                        $form->setField('additionalMeasureQuantity', "unit=" . cat_UoM::getShortName($secondMeasureId));
                    } else {
                        $form->setField('additionalMeasureId', 'input');
                        $form->setOptions('additionalMeasureId', $additionalMeasures);
                    }
                    
                    $form->setDefault('additionalMeasureId', $secondMeasureId);
                }
            }

            // Дали да се изравнява себестойностт-а с тази от драйвера
            $equalizePrimeCost = null;
            if($bomRec = cat_Products::getLastActiveBom(4329, 'production,sales')){
                if($bomRec->isComplete != 'auto'){
                    $equalizePrimeCost = ($bomRec->isComplete == 'yes') ? 'no' : 'yes';
                }
            }

            if(empty($equalizePrimeCost)){
                $completeBomDefault = cat_Setup::get('DEFAULT_BOM_IS_COMPLETE');
                $equalizePrimeCost = ($completeBomDefault == 'yes') ? 'no' : 'yes';
            }
            $form->setDefault('equalizePrimeCost', $equalizePrimeCost);
        }
        
        $form->setDefault('storeId', store_Stores::getCurrent('id', false));
        
        return $data;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        
        if ($form->isSubmitted()) {
            
            // Проверка на к-то
            $warning = null;
            if (!deals_Helper::checkQuantity($rec->packagingId, $rec->packQuantity, $warning)) {
                $form->setWarning('packQuantity', $warning);
            }
            
            if(empty($rec->additionalMeasureQuantity)){
                $rec->additionalMeasureId = null;
            }
            
            // Проверка на допълнителната мярка
            if(!empty($rec->additionalMeasureQuantity) && !empty($rec->additionalMeasureId)){
                if (!deals_Helper::checkQuantity($rec->additionalMeasureId, $rec->additionalMeasureQuantity, $warning)) {
                    $form->setWarning('additionalMeasureQuantity', $warning);
                }
            }
            
            $productInfo = cat_Products::getProductInfo($form->rec->productId);
            if (!isset($productInfo->meta['canStore'])) {
                $rec->storeId = null;
            } else {
                $rec->dealId = null;
            }

            $measureDerivities = cat_Uom::getSameTypeMeasures($productInfo->productRec->measureId);
            $rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
            if(!empty($rec->additionalMeasureId) && !empty($rec->additionalMeasureQuantity)){

                if(array_key_exists($rec->additionalMeasureId, $measureDerivities)){
                    $additionalMeasureQuantity = cat_Uom::convertValue($rec->additionalMeasureQuantity, $rec->additionalMeasureId, $productInfo->productRec->measureId);
                    $rec->quantityInPack = $additionalMeasureQuantity / $rec->packQuantity;
                }
            }

            $rec->quantity = $rec->packQuantity * $rec->quantityInPack;
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(isset($fields['-single'])){
            $row->productId = cat_Products::getAutoProductDesc($rec->productId, null, 'short', 'internal');
        } else {
            $row->productId = cat_Products::getShortHyperlink($rec->productId, null, 'short', 'internal');
        }
        
        $productRec = cat_Products::fetch($rec->productId, 'measureId');
        $shortUom = cat_UoM::getShortName($productRec->measureId);
        $row->quantity .= " {$shortUom}";
        
        if (isset($rec->debitAmount)) {
            $baseCurrencyCode = acc_Periods::getBaseCurrencyCode($rec->valior);
            $row->debitAmount .= " <span class='cCode'>{$baseCurrencyCode}</span>, " . tr('без ДДС');
        }
        if (isset($rec->expenseItemId)) {
            $row->expenseItemId = acc_Items::getVerbal($rec->expenseItemId, 'titleLink');
        }
        
        $row->subTitle = (isset($rec->storeId)) ? 'Засклаждане на продукт' : 'Производство на услуга';
        $row->subTitle = tr($row->subTitle);

        $quantityInPack = $rec->quantityInPack;
        if(!empty($rec->additionalMeasureId)){
            if($rec->additionalMeasureId == $productRec->measureId){

                // Ако втората мярка е основната показваме оригиналното к-во в опаковка
                $packRec = cat_products_Packagings::getPack($rec->productId, $rec->packagingId);
                $quantityInPack = (is_object($packRec)) ? $packRec->quantity : 1;
                $row->additionalMeasureId = ht::createHint($row->additionalMeasureId, "Това количество ще се отчете в производството");
            }
        }

        deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $quantityInPack);
        
        if (isset($rec->inputStoreId)) {
            $row->inputStoreId = store_Stores::getHyperlink($rec->inputStoreId, true);
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'add') {
            if (isset($rec)) {
               
                // Трябва да има ориджин
                if (empty($rec->originId)) {
                    $requiredRoles = 'no_one';
                } else {
                    
                    // Ориджина трябва да е задание за производство
                    $originDoc = doc_Containers::getDocument($rec->originId);
                   
                    if (!$originDoc->isInstanceOf('planning_Jobs') && !$originDoc->isInstanceOf('planning_Tasks')) {
                        $requiredRoles = 'no_one';
                    } else {
                      
                        // Което не е чернова или оттеглено
                        $state = $originDoc->fetchField('state');
                        if ($state == 'rejected' || $state == 'draft' || $state == 'closed') {
                            $requiredRoles = 'no_one';
                        } else {
                            
                            // Ако артикула от заданието не е производим не можем да добавяме документ
                            $productId = $originDoc->fetchField('productId');
                            $productRec = cat_Products::fetch($productId, 'canManifacture,generic');
                            if ($productRec->canManifacture != 'yes' || $productRec->generic == 'yes') {
                                $requiredRoles = 'no_one';
                            } else {
                                if($originDoc->isInstanceOf('planning_Jobs')){
                                    if(planning_Tasks::fetch("#originId = {$originDoc->fetchField('containerId')} AND #productId = {$productId} AND #state != 'draft' && #state != 'rejected'")){
                                      
                                      //@todo да се върне
                                      //$requiredRoles = 'no_one';
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Ако екшъна е за задаване на дебитна сума
        if ($action == 'adddebitamount') {
            $requiredRoles = $mvc->getRequiredRoles('conto', $rec, $userId);
            if ($requiredRoles != 'no_one') {
                if (isset($rec)) {
                    if (planning_DirectProductNoteDetails::fetchField("#noteId = {$rec->id}", 'id')) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
        
        // При опит за форсиране на документа, като разходен обект
        if ($action == 'forceexpenseitem' && isset($rec->id)) {
            if ($requiredRoles != 'no_one') {
                $pRec = cat_Products::fetch($rec->productId, 'canStore,canConvert,fixedAsset');
                if ($pRec->canStore == 'no') {
                    if ($pRec->canConvert == 'yes' || $pRec->fixedAsset == 'yes') {
                        $requiredRoles = 'no_one';
                    } else {
                        $expenseItemId = acc_Items::forceSystemItem('Неразпределени разходи', 'unallocated', 'costObjects')->id;
                        if (isset($rec->expenseItemId) && $rec->expenseItemId != $expenseItemId) {
                            $requiredRoles = 'no_one';
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * Намира количествата за влагане от задачите
     *
     * @param stdClass $rec
     *
     * @return array $res
     */
    protected function getDefaultDetails($rec)
    {
        $rec = $this->fetchRec($rec);
        $origin = doc_Containers::getDocument($rec->originId);
        if($origin->isInstanceOf('planning_Tasks')){
            $details = $this->getDefaultDetailsFromTasks($rec);
        } else {
            $details = $this->getDefaultDetailsFromBom($rec);
        }
        
        // Връщаме намерените дефолтни детайли
        return $details;
    }
    
    
    /**
     * Намира количествата за влагане от задачите
     *
     * @param stdClass $rec
     *
     * @return array $details
     */
    protected function getDefaultDetailsFromTasks($rec)
    {
        $details = array();
        $origin = doc_Containers::getDocument($rec->originId);
        $aQuery = planning_ProductionTaskProducts::getQuery();
        $aQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
        
        $aQuery->where("#taskId = {$origin->that} AND #type != 'production' AND #canStore = 'yes' AND #totalQuantity != 0");
        if(isset($rec->inputStoreId)){
            $aQuery->where("#storeId IS NULL OR #storeId = '{$rec->inputStoreId}'");
        }
        
        // Събираме ги в масив
        while ($aRec = $aQuery->fetch()) {
            $obj = new stdClass();
            $obj->productId = $aRec->productId;
            $obj->type = ($aRec->type == 'input') ? 'input' : 'pop';
            $obj->quantityInPack = 1;
            $obj->quantity = $aRec->totalQuantity;
            $obj->packagingId = cat_Products::fetchField($obj->productId, 'measureId');
            $obj->measureId = $obj->packagingId;
            $obj->storeId = $aRec->storeId;
            
            $index = $obj->productId . '|' . $obj->type;
            $details[$index] = $obj;
        }
        
        // Връщаме намерените детайли
        return $details;
    }
    
    
    /**
     * Връща дефолт детайлите на документа, които съотвестват на ресурсите
     * в последната активна рецепта за артикула
     *
     * @param stdClass $rec - запис
     *
     * @return array $details - масив с дефолтните детайли
     */
    protected function getDefaultDetailsFromBom($rec)
    {
        $details = array();
        $originRec = doc_Containers::getDocument($rec->originId)->rec();
        
        // Ако артикула има активна рецепта
        $bomId = cat_Products::getLastActiveBom($rec->productId, 'production,instant,sales')->id;
        
        // Ако ням рецепта, не могат да се определят дефолт детайли за влагане
        if (!$bomId) {
            
            return $details;
        }
        
        // К-ко е произведено до сега и колко ще произвеждаме
        $quantityProduced = $originRec->quantityProduced;
        $quantityToProduce = $rec->quantity + $quantityProduced;
        
        // Извличаме информацията за ресурсите в рецептата за двете количества
        $bomInfo1 = cat_Boms::getResourceInfo($bomId, $quantityProduced, dt::now());
        $bomInfo2 = cat_Boms::getResourceInfo($bomId, $quantityToProduce, dt::now());
        
        // За всеки ресурс
        foreach ($bomInfo2['resources'] as $index => $resource) {
            
            // Задаваме данните на ресурса
            $dRec = new stdClass();
            $dRec->productId = $resource->productId;
            $dRec->type = $resource->type;
            $dRec->packagingId = $resource->packagingId;
            $dRec->quantityInPack = $resource->quantityInPack;
          
            // Дефолтното к-во ще е разликата между к-та за произведеното до сега и за произведеното в момента
            $roundQuantity = $resource->propQuantity - $bomInfo1['resources'][$index]->propQuantity;
            
            $uomRec = cat_UoM::fetch($dRec->packagingId, 'roundSignificant,round');
            $dRec->quantity = core_Math::roundNumber($roundQuantity, $uomRec->round, $uomRec->roundSignificant);
            $dRec->quantityFromBom = $dRec->quantity;
            
            $pInfo = cat_Products::getProductInfo($resource->productId);
            $dRec->measureId = $pInfo->productRec->measureId;
            $index = $dRec->productId . '|' . $dRec->type;
            $details[$index] = $dRec;
        }
        
        // Връщаме генерираните детайли
        return $details;
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        // Ако записа е клониран не правим нищо
        if ($rec->_isClone === true) {
            
            return;
        }
        
        $details = $mvc->getDefaultDetails($rec);
        if(countR($details)) {
             foreach ($details as $dRec) {
                $dRec->noteId = $rec->id;
                
                // Склада за влагане се добавя само към складируемите артикули, които не са отпадъци
                if (empty($dRec->storeId) && isset($rec->inputStoreId)) {
                     if (cat_Products::fetchField($dRec->productId, 'canStore') == 'yes' && $dRec->type != 'pop') {
                         $dRec->storeId = $rec->inputStoreId;
                     }
                }
                    
                planning_DirectProductNoteDetails::save($dRec);
            }
       }
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
        // При активиране/оттегляне
        if ($rec->state == 'active' || $rec->state == 'rejected') {
            $origin = doc_Containers::getDocument($rec->originId);
            if($origin->isInstanceOf('planning_Tasks')){
                $origin->updateMaster();
            } else {
                planning_Jobs::updateProducedQuantity($rec->originId);
            }
            
            doc_DocumentCache::threadCacheInvalidation($rec->threadId);
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        
        if ($rec->state == 'active') {
            if (planning_DirectProductNoteDetails::fetchField("#noteId = {$rec->id}")) {
                if (cat_Boms::haveRightFor('add', (object) array('productId' => $rec->productId, 'originId' => $rec->originId))) {
                    $bomUrl = array($mvc, 'createBom', $data->rec->id);
                    $data->toolbar->addBtn('Рецепта', $bomUrl, null, 'ef_icon = img/16/add.png,title=Създаване на нова рецепта по протокола');
                }
            }
        }
        
        if ($data->toolbar->haveButton('btnConto')) {
            if ($mvc->haveRightFor('adddebitamount', $rec)) {
                $data->toolbar->removeBtn('btnConto');
                $attr = (!haveRole('seePrice,ceo') && !self::getDefaultDebitPrice($rec)) ? array('error' => 'Документът не може да бъде контиран, защото артикула няма себестойност') : ((!haveRole('seePrice,ceo') ? array('warning' => 'Наистина ли желаете документът да бъде контиран') : array()));
                $data->toolbar->addBtn('Контиране', array($mvc, 'addDebitAmount', $rec->id, 'ret_url' => array($mvc, 'single', $rec->id)), 'id=btnConto,ef_icon = img/16/tick-circle-frame.png,title=Контиране на протокола за производство', $attr);
            }
        }
    }
    
    
    /**
     * Връща дефолтната себестойност за артикула
     *
     * @param mixed stdClass $rec
     *
     * @return mixed $price
     */
    private static function getDefaultDebitPrice($rec)
    {
        $quantity = !empty($rec->jobQuantity) ? $rec->jobQuantity : $rec->quantity;
        $valior = (!empty($rec->valior)) ? $rec->valior : dt::now();
        
        return cat_Products::getPrimeCost($rec->productId, $rec->packagingId, $quantity, $valior);
    }
    
    
    /**
     * Екшън изискващ подаване на себестойност, когато се опитваме да произведем артикул, без да сме специфицирали неговите материали
     */
    public function act_addDebitAmount()
    {
        // Проверка на параметрите
        $this->requireRightFor('adddebitamount');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('adddebitamount', $rec);
        
        $form = cls::get('core_Form');
        $url = $this->getSingleUrlArray($id);
        $docTitle = ht::createLink($this->getTitleById($id), $url, false, "ef_icon={$this->singleIcon},class=linkInTitle");
        
        // Подготовка на формата
        $form->title = "Въвеждане на себестойност за|* <b style='color:#ffffcc;'>{$docTitle}</b>";
        $form->info = tr('Не може да се определи себестойността, защото няма посочени материали');
        $form->FLD('debitPrice', 'double(min=0)', 'caption=Ед. себест-ст,mandatory');
        
        // Ако драйвера може да върне себестойност тя е избрана по дефолт
        $defPrice = self::getDefaultDebitPrice($rec);
        if (isset($defPrice)) {
            $form->setDefault('debitPrice', $defPrice);
        }
        
        $baseCurrencyCode = acc_Periods::getBaseCurrencyCode($rec->valior);
        $form->setField('debitPrice', "unit=|*{$baseCurrencyCode} |без ДДС|*");
        $form->input();
        
        if (!haveRole('seePrice,ceo')) {
            if (isset($defPrice)) {
                $form->method = 'GET';
                $form->cmd = 'save';
            } else {
                followRetUrl(null, 'Документът не може да бъде контиран, защото няма себестойност', 'error');
            }
        }
        
        if ($form->isSubmitted()) {
            $amount = $form->rec->debitPrice * $rec->quantity;
            
            // Ъпдейъваме подадената себестойност
            $rec->debitAmount = $amount;
            $this->save($rec, 'debitAmount');
            $this->logWrite('Задаване на себестойност', $rec->id);
            
            $controUrl = $this->getContoUrl($id);
            $controUrl['ret_url'] = $this->getSingleUrlArray($rec->id);
            
            // Редирект към екшъна за контиране
            redirect($controUrl);
        }
        
        $form->toolbar->addSbBtn('Контиране', 'save', 'ef_icon = img/16/tick-circle-frame.png, title = Контиране на документа');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        $tpl = $this->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);
        
        return $tpl;
    }
    
    
    /**
     * Екшън създаващ нова рецепта по протокола
     */
    public function act_CreateBom()
    {
        cat_Boms::requireRightFor('add');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        cat_Boms::requireRightFor('add', (object) array('productId' => $rec->productId, 'originId' => $rec->originId));
        
        // Подготвяме детайлите на рецептата
        $dQuery = planning_DirectProductNoteDetails::getQuery();
        $dQuery->where("#noteId = {$id}");
        
        $recsToSave = array();
        
        while ($dRec = $dQuery->fetch()) {
            $index = "{$dRec->productId}|{$dRec->type}";
            if (!array_key_exists($index, $recsToSave)) {
                $recsToSave[$index] = (object) array('resourceId' => $dRec->productId,
                    'type' => $dRec->type,
                    'propQuantity' => 0,
                    'packagingId' => $dRec->packagingId,
                    'quantityInPack' => $dRec->quantityInPack);
            }
            
            $recsToSave[$index]->propQuantity += $dRec->quantity;
            if ($dRec->quantityInPack < $recsToSave[$index]->quantityInPack) {
                $recsToSave[$index]->quantityInPack = $dRec->quantityInPack;
                $recsToSave[$index]->packagingId = $dRec->packagingId;
            }
        }
        
        foreach ($recsToSave as &$pRec) {
            $pRec->propQuantity /= $pRec->quantityInPack;
        }
        
        // Създаваме новата рецепта
        $newId = cat_Boms::createNewDraft($rec->productId, $rec->quantity, $rec->originId, $recsToSave, null, $rec->expenses);
        
        // Записваме, че потребителя е разглеждал този списък
        cat_Boms::logWrite('Създаване на рецепта от протокол за производство', $newId);
        
        // Редирект
        return new Redirect(array('cat_Boms', 'single', $newId), '|Успешно е създадена нова рецепта');
    }
    
    
    /**
     * Документа винаги може да се активира, дори и да няма детайли
     */
    public static function canActivate($rec)
    {
        $rec = static::fetchRec($rec);
        
        if (isset($rec->id)) {
            $input = planning_DirectProductNoteDetails::fetchField("#noteId = {$rec->id} AND #type = 'input'", 'id');
            $pop = planning_DirectProductNoteDetails::fetchField("#noteId = {$rec->id} AND #type = 'pop'", 'id');
            if ($pop && !$input) {
                
                return false;
            }
        }
        
        return true;
    }
    
    
    /**
     * Извиква се след като документа стане разходен обект
     */
    protected static function on_AfterForceCostObject($mvc, $rec)
    {
        // Реконтиране на документа
        acc_Journal::reconto($rec->containerId);
    }
    
    
    /**
     * Списък с артикули върху, на които може да им се коригират стойностите
     *
     * @param mixed $id     - ид или запис
     * @param mixed $forMvc - за кой мениджър
     * 
     * @return array $products        - масив с информация за артикули
     *               o productId       - ид на артикул
     *               o name            - име на артикула
     *               o quantity        - к-во
     *               o amount          - сума на артикула
     *               o inStores        - масив с ид-то и к-то във всеки склад в който се намира
     *               o transportWeight - транспортно тегло на артикула
     *               o transportVolume - транспортен обем на артикула
     */
    public function getCorrectableProducts($id, $forMvc)
    {
        $products = array();
        $rec = $this->fetchRec($id);
        
        $products[$rec->productId] = (object) array('productId' => $rec->productId,
            'quantity' => $rec->quantity,
            'name' => cat_Products::getTitleById($rec->productId, false),
            'amount' => $rec->quantity);
        
        if ($transportWeight = cat_Products::getTransportWeight($rec->productId, 1)) {
            $products[$rec->productId]->transportWeight = $transportWeight;
        }
        
        if ($transportVolume = cat_Products::getTransportVolume($rec->productId, 1)) {
            $products[$rec->productId]->transportVolume = $transportVolume;
        }
        
        if (isset($rec->storeId)) {
            $products[$rec->productId]->inStores[$rec->storeId] = $rec->quantity;
        }
        
        return $products;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     *
     * @return bool
     */
    public static function canAddToThread($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        
        // или към нишка на продажба/артикул/задание
        return $firstDoc->isInstanceOf('planning_Tasks') || $firstDoc->isInstanceOf('planning_Jobs') || $firstDoc->isInstanceOf('sales_Sales');
    }
    
    
    /**
     * Създаване на протокол за производство на артикул
     * Ако може след създаването ще зареди артикулите от активната рецепта и/или задачите
     *
     * @throws core_exception_Expect
     * @param int       $jobId     - ид на задание
     * @param int       $productId - ид на артикул
     * @param float     $quantity  - к-во за произвеждане
     * @param datetime  $valior    - вальор
     * @param array $fields    - допълнителни параметри
     *                         ['storeId']       - ид на склад за засклаждане
     *                         ['expenseItemId'] - ид на перо на разходен обект
     *                         ['expenses']      - режийни разходи
     *                         ['batch']         - партиден номер
     *                         ['inputStoreId']  - дефолтен склад за влагане
     * @return int
     */
    public static function createDraft($jobId, $productId, $quantity, $valior = null, $fields = array())
    {
        $rec = new stdClass();
        expect($jRec = planning_Jobs::fetch($jobId), 'Няма такова задание');
        expect($jRec->state != 'rejected' && $jRec->state != 'draft', 'Заданието не е активно');
        expect($productRec = cat_Products::fetch($productId, 'canManifacture,canStore,fixedAsset,canConvert'));
        $rec->valior = ($valior) ? $valior : dt::today();
        $rec->valior = dt::verbal2mysql($rec->valior);
        $rec->originId = $jRec->containerId;
        $rec->threadId = $jRec->threadId;
        $rec->productId = $productId;
        expect($productRec->canManifacture = 'yes', 'Артикулът не е производим');
        
        $Double = cls::get('type_Double');
        expect($rec->quantity = $Double->fromVerbal($quantity));
        if ($productRec->canStore == 'yes') {
            expect($fields['storeId'], 'За складируем артикул е нужен склад');
            expect(store_Stores::fetch($fields['storeId']), "Несъществуващ склад {$fields['storeId']}");
            $rec->storeId = $fields['storeId'];
        } else {
            if ($rec->canConvert == 'yes') {
                $rec->expenseItemId = acc_CostAllocations::getUnallocatedItemId();
            } else {
                expect($fields['expenseItemId'], 'Няма разходен обект');
                expect(acc_Items::fetch($fields['expenseItemId']), 'Няма такова перо');
                $rec->expenseItemId = $fields['expenseItemId'];
            }
        }
        
        if (isset($fields['inputStoreId'])) {
            expect(store_Stores::fetch($fields['inputStoreId']), "Несъществуващ склад за влагане {$fields['inputStoreId']}");
            $rec->inputStoreId = $fields['inputStoreId'];
        }
        
        if (isset($fields['expenses'])) {
            expect($fields['expenses']);
            expect($fields['expenses'] >= 0 && $fields['expenses'] <= 1);
            $rec->expenses = $fields['expenses'];
        }
        
        if (isset($fields['batch'])) {
            if (core_Packs::isInstalled('batch')) {
                expect($Def = batch_Defs::getBatchDef($productId), 'Опит за задаване на партида на артикул без партида');
                $msg = null;
                if (!$Def->isValid($fields['batch'], $quantity, $msg)) {
                    expect(false, tr($msg));
                }
                
                $rec->batch = $Def->normalize($fields['batch']);
                $rec->isEdited = true;
            }
        }
        
        // Създаване на запис
        self::route($rec);
        
        return self::save($rec);
    }
    
    
    /**
     * АПИ метод за добавяне на детайл към протокол за производство
     *
     * @throws core_exception_Expect
     * @param int      $id             - ид на артикул
     * @param int      $productId      - ид на продукт
     * @param int      $packagingId    - ид на опаковка
     * @param float    $packQuantity   - к-во опаковка
     * @param float    $quantityInPack - к-во в опаковка
     * @param bool     $isWaste        - дали е отпадък или не
     * @param int|NULL $storeId        - ид на склад, или NULL ако е от незавършеното производство
     * @return void
     */
    public static function addRow($id, $productId, $packagingId, $packQuantity, $quantityInPack, $isWaste = false, $storeId = null)
    {
        // Проверки на параметрите
        expect($noteRec = self::fetch($id), "Няма протокол с ид {$id}");
        expect($noteRec->state == 'draft', 'Протокола трябва да е чернова');
        expect($productRec = cat_Products::fetch($productId, 'canConvert,canStore'), "Няма артикул с ид {$productId}");
        if ($isWaste) {
            expect($productRec->canConvert == 'yes', 'Артикулът трябва да е вложим');
            expect($productRec->canStore == 'yes', 'Артикулът трябва да е складируем');
        } else {
            expect($productRec->canConvert == 'yes', 'Артикулът трябва да е вложим');
        }
        
        expect($packagingId, 'Няма мярка/опаковка');
        expect(cat_UoM::fetch($packagingId), "Няма опаковка/мярка с ид {$packagingId}");
        
        if ($productRec->canStore != 'yes') {
            expect(empty($storeId), 'За нескладируем артикул не може да се подаде склад');
        }
        
        if (isset($storeId)) {
            expect(store_Stores::fetch($storeId), 'Невалиден склад');
        }
        
        $packs = cat_Products::getPacks($productId);
        expect(isset($packs[$packagingId]), "Артикулът не поддържа мярка/опаковка с ид {$packagingId}");
        
        $Double = cls::get('type_Double');
        expect($quantityInPack = $Double->fromVerbal($quantityInPack), "Невалидно к-во {$quantityInPack}");
        expect($packQuantity = $Double->fromVerbal($packQuantity), "Невалидно к-во {$packQuantity}");
        $quantity = $quantityInPack * $packQuantity;
       
        // Подготовка на записа
        $rec = (object) array('noteId' => $id,
            'type' => ($isWaste) ? 'pop' : 'input',
            'productId' => $productId,
            'packagingId' => $packagingId,
            'quantityInPack' => $quantityInPack,
            'quantity' => $quantity,
        );
        
        if (isset($storeId)) {
            $rec->storeId = $storeId;
        }
        
        planning_DirectProductNoteDetails::save($rec);
    }
    
    
    /**
     * Какво да е предупреждението на бутона за контиране
     *
     * @param int    $id         - ид
     * @param string $isContable - какво е действието
     *
     * @return NULL|string - текста на предупреждението или NULL ако няма
     */
    public function getContoWarning_($id, $isContable)
    {
        $warning = null;
        $dQuery = planning_DirectProductNoteDetails::getQuery();
        $dQuery->where("#noteId = {$id} AND #storeId IS NOT NULL");
        
        $productsWithNegativeQuantity = array();
        while ($dRec = $dQuery->fetch()) {
            $available = deals_Helper::getAvailableQuantityAfter($dRec->productId, $dRec->storeId, $dRec->quantity);
            if ($available < 0) {
                $productsWithNegativeQuantity[$dRec->storeId][] = cat_Products::getTitleById($dRec->productId, false);
            }
        }
        
        if (countR($productsWithNegativeQuantity)) {
            $warning = 'Контирането на документа ще доведе до отрицателни количества по|*: ';
            foreach ($productsWithNegativeQuantity as $storeId => $products) {
                $warning .= implode(', ', $products) . ', |в склад|* ' . store_Stores::getTitleById($storeId) . ' |и|* ';
            }
            
            $warning = rtrim($warning, ' |и|* ');
        }
        
        return $warning;
    }
    
    
    /**
     * Връща масив от използваните нестандартни артикули в протокола
     *
     * @param int $id - ид на протокола
     *
     * @return array $res - масив с използваните документи
     *               ['class'] - инстанция на документа
     *               ['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
        $usedDocs = parent::getUsedDocs_($id);
        $rec = $this->fetchRec($id);
        $usedDocs[$rec->productId] = cat_Products::fetchField($rec->productId, 'containerId');
        
        return $usedDocs;
    }


    /**
     * Връща планираните наличности
     *
     * @param stdClass $rec
     * @return array
     *       ['productId']        - ид на артикул
     *       ['storeId']          - ид на склад, или null, ако няма
     *       ['date']             - на коя дата
     *       ['quantityIn']       - к-во очаквано
     *       ['quantityOut']      - к-во за експедиране
     *       ['genericProductId'] - ид на генеричния артикул, ако има
     *       ['reffClassId']      - клас на обект (различен от този на източника)
     *       ['reffId']           - ид на обект (различен от този на източника)
     */
    public function getPlannedStocks($rec)
    {
        $res = array();
        $id = is_object($rec) ? $rec->id : $rec;
        $rec = $this->fetch($id, '*', false);
        $date = !empty($rec->{$this->termDateFld}) ? $rec->{$this->termDateFld} : (!empty($rec->{$this->valiorFld}) ? $rec->{$this->valiorFld} : $rec->createdOn);

        $canStore = cat_Products::fetchField($rec->productId, 'canStore');
        if($canStore == 'yes'){
            $res[] = (object)array('storeId'          => $rec->storeId,
                                   'productId'        => $rec->productId,
                                   'date'             => $date,
                                   'quantityIn'       => $rec->quantity,
                                   'quantityOut'      => null,
                                   'genericProductId' => null);
        }

        $dQuery = planning_DirectProductNoteDetails::getQuery();
        $dQuery->EXT('canConvert', 'cat_Products', "externalName=canConvert,externalKey=productId");
        $dQuery->EXT('generic', 'cat_Products', "externalName=generic,externalKey=productId");
        $dQuery->EXT('canStore', 'cat_Products', "externalName=canStore,externalKey=productId");
        $dQuery->XPR('totalQuantity', 'double', "SUM(#quantity)");
        $dQuery->where("#noteId = {$rec->id} AND #storeId IS NOT NULL AND #type = 'input' AND #canStore = 'yes'");
        $dQuery->groupBy('productId');

        while ($dRec = $dQuery->fetch()) {
            $genericProductId = null;
            if($dRec->generic == 'yes'){
                $genericProductId = $dRec->productId;
            } elseif($dRec->canConvert == 'yes'){
                $genericProductId = planning_GenericMapper::fetchField("#productId = {$dRec->productId}", 'genericProductId');
            }

            $res[] = (object)array('storeId'          => $dRec->storeId,
                                   'productId'        => $dRec->productId,
                                   'date'             => $date,
                                   'quantityIn'       => null,
                                   'quantityOut'      => $dRec->totalQuantity,
                                   'genericProductId' => $genericProductId);
        }

        return $res;
    }
}
