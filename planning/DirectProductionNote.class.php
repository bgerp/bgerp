<?php


/**
 * Клас 'planning_DirectProductionNote' - Документ за производство
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2023 Experta OOD
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
    public $canList = 'ceo,production,store';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,production,store';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,production,store';


    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,production';


    /**
     * Кой има право да контира?
     */
    public $canConto = 'ceo,production,store';


    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo,production,store';


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

        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка', 'mandatory,input=hidden,before=packQuantity,silent,removeAndRefreshForm=additionalMeasureId|additionalMeasureQuantity|packQuantity|quantityInPack|quantity');
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
        if (empty($rec->quantity) || empty($rec->quantityInPack)) return;

        $rec->packQuantity = core_Math::roundNumber($rec->quantity / $rec->quantityInPack);
    }


    /**
     * Подготвя формата за редактиране
     */
    public function prepareEditForm_($data)
    {
        parent::prepareEditForm_($data);
        $form = &$data->form;
        $rec = $form->rec;
        $form->setDefault('valior', dt::today());

        $originDoc = doc_Containers::getDocument($form->rec->originId);
        $originRec = $originDoc->rec();

        $storeId = $originRec->storeId;
        $saleId = $originRec->saleId;
        if($originDoc->isInstanceOf('planning_Tasks')){
            $defaultOriginPackField = 'measureId';
            $jobRec = doc_Containers::getDocument($originRec->originId)->fetch();
            $storeId = ($originRec->storeId) ? $originRec->storeId : $jobRec->storeId;
            $saleId = $jobRec->saleId;
            $productOptions = planning_ProductionTaskProducts::getOptionsByType($originDoc->that, 'production');
            unset($productOptions[$jobRec->productId]);
        } else {
            $defaultOriginPackField = 'packagingId';
            $jobRec = $originDoc->fetch();
            $productOptions = array($originRec->productId => cat_Products::getTitleById($originRec->productId, false));
        }

        $form->setDefault('storeId', $storeId);
        $form->setOptions('productId', $productOptions);
        $form->setDefault('productId', key($productOptions));
        $originPackId = $originRec->packagingId;

        if(isset($rec->productId)){
            if($rec->productId != $jobRec->productId){
                $form->setField('inputStoreId', 'input=none');
            }

            $secondMeasureDerivatives = array();
            $productRec = cat_Products::fetch($rec->productId, 'canStore,fixedAsset,canConvert,measureId');
            if($rec->productId == $jobRec->productId){
                $packs = cat_Products::getPacks($rec->productId, false, $jobRec->secondMeasureId);
                if($jobRec->secondMeasureId){
                    $secondMeasureDerivatives = cat_UoM::getSameTypeMeasures($jobRec->secondMeasureId);
                }

                $originalPacks = $packs;
                if(!array_key_exists($originPackId, $secondMeasureDerivatives)){
                    $packs = array_diff_key($packs, $secondMeasureDerivatives);
                } else {
                    $packs = array_intersect_key($packs, $secondMeasureDerivatives);
                }

                $defaultPack = $originRec->{$defaultOriginPackField};
            } else {
                $packs = cat_Products::getPacks($rec->productId);

                if($originDoc->isInstanceOf('planning_Tasks')){
                    $pInfo = planning_ProductionTaskProducts::getInfo($originRec->id, $rec->productId, 'production');
                    $defaultPack = ($originRec->productId == $rec->productId) ? $pInfo->measureId : $pInfo->packagingId;
                } else {
                    $defaultPack = key($packs);
                }
            }

            $form->setOptions('packagingId', $packs);
            $form->setDefault('packagingId', $defaultPack);
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
                $form->setField('storeId', 'mandatory');
                $form->setField('packagingId', 'input');
                if(cat_Products::haveDriver($rec->productId, 'planning_interface_StepProductDriver')){
                    $form->setField('inputStoreId', 'mandatory');
                }
            }

            $bomRec = cat_Products::getLastActiveBom($rec->productId, 'production,sales');
            $defaultOverheadCost = $bomRec->expenses;
            if(!isset($defaultOverheadCost)){
                if($defaultOverheadCostArr = cat_Products::getDefaultOverheadCost($rec->productId)){
                    $defaultOverheadCost = $defaultOverheadCostArr['overheadCost'];
                }
            }
            $form->setDefault('expenses', $defaultOverheadCost);

            // Ако има избрана опаковка
            if(isset($rec->packagingId)){

                // Ако в заданието е оказано да се отчита във втора мярка
                if($jobRec->secondMeasureId && $rec->productId == $jobRec->productId){

                    // Ако заданието е във втора мярка, и се произвежда в някоя от производните ѝ
                    if(array_key_exists($rec->packagingId, $secondMeasureDerivatives)){
                        $additionalMeasures = array_diff_key($originalPacks, $secondMeasureDerivatives);

                        $pQuery = cat_products_Packagings::getQuery();
                        $pQuery->EXT('type', 'cat_UoM', 'externalName=type,externalKey=packagingId');
                        $pQuery->where("#type = 'uom' AND #productId = {$rec->productId}");
                        $pQuery->notIn('packagingId', array_keys($secondMeasureDerivatives));
                        $pQuery->show('packagingId');
                        $ignoreMeasureArr = arr::extractValuesFromArray($pQuery->fetchAll(), 'packagingId');
                        $additionalMeasures = array_diff_key($additionalMeasures, $ignoreMeasureArr);
                    } else {
                        $additionalMeasures = array_intersect_key($originalPacks, $secondMeasureDerivatives);
                    }
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

                        $form->setField('additionalMeasureQuantity', 'mandatory');
                        $form->setDefault('additionalMeasureId', $secondMeasureId);
                    }
                }
            }

            // Дали да се изравнява себестойностт-а с тази от драйвера
            $equalizePrimeCost = null;
            if($bomRec = cat_Products::getLastActiveBom($rec->productId, 'production,sales')){
                if($bomRec->isComplete != 'auto'){
                    $equalizePrimeCost = ($bomRec->isComplete == 'yes') ? 'no' : 'yes';
                }
            }

            if(empty($equalizePrimeCost)){
                $completeBomDefault = cat_Setup::get('DEFAULT_BOM_IS_COMPLETE');
                $equalizePrimeCost = ($completeBomDefault == 'yes') ? 'no' : 'yes';
            }
            $form->setDefault('equalizePrimeCost', $equalizePrimeCost);

            if($originDoc->isInstanceOf('planning_Jobs')){
                $form->setDefault('jobQuantity', $originRec->quantity);
                $quantityFromTasks = planning_Tasks::getProducedQuantityForJob($originRec->id);
                $productMeasures = cat_UoM::getSameTypeMeasures($productRec->measureId);

                $quantityToStore = $quantityFromTasks - $originRec->quantityProduced;
                if ($quantityToStore > 0) {
                    if(array_key_exists($rec->packagingId, $productMeasures)){
                        $defQuantity = cat_UoM::convertValue($quantityToStore, $originRec->packagingId, $rec->packagingId);
                    } else{
                        $packRec = cat_products_Packagings::getPack($rec->productId, $rec->packagingId);
                        $inPackQuantity = (is_object($packRec)) ? $packRec->quantity : 1;
                        $quantityInBaseMeasure = $quantityToStore * $originRec->quantityInPack;
                        $defQuantity = $quantityInBaseMeasure / $inPackQuantity;
                    }

                    $form->setDefault('packQuantity', $defQuantity);
                }
            } else {
                // Ако задачата е за крайния артикул записваме к-то му от заданието
                if($rec->productId == $jobRec->productId){
                    $form->setDefault('jobQuantity', $originRec->totalQuantity - $originRec->producedQuantity);
                }

                $info = planning_ProductionTaskProducts::getInfo($originDoc->that, $rec->productId, 'production');
                $originRec = $originDoc->fetch();
                $originPackId = ($rec->productId == $originRec->productId) ? $info->measureId : $info->packagingId;
                $form->setDefault('packagingId', $originPackId);
                $toProduce = round($info->totalQuantity - $info->producedQuantity - $info->scrappedQuantity, 4);
                $originPackRec = cat_products_Packagings::getPack($rec->productId, $originPackId);
                $originPackQuantity = is_object($originPackRec) ? $originPackRec->quantity : 1;
                $toProduceInBaseQuantity = $toProduce * $originPackQuantity;

                if ($toProduceInBaseQuantity > 0) {
                    $packRec = cat_products_Packagings::getPack($rec->productId, $rec->packagingId);
                    $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
                    $toProduce = $toProduceInBaseQuantity / $quantityInPack;
                    $form->setDefault('packQuantity', $toProduce);
                }
            }
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

            $rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;

            // Ако има въведена втора мярка
            if(!empty($rec->additionalMeasureId) && !empty($rec->additionalMeasureQuantity)){
                $measureDerivities = cat_Uom::getSameTypeMeasures($productInfo->productRec->measureId);
                $secondMeasureType = cat_UoM::fetchField($rec->additionalMeasureId, 'type');

                // Ако втората мярка е производна на основната, обръща се в основната
                if(array_key_exists($rec->additionalMeasureId, $measureDerivities)){
                    $additionalMeasureQuantity = cat_Uom::convertValue($rec->additionalMeasureQuantity, $rec->additionalMeasureId, $productInfo->productRec->measureId);
                    $rec->quantityInPack = $additionalMeasureQuantity / $rec->packQuantity;
                } elseif($secondMeasureType == 'packaging'){
                    $origin = doc_Containers::getDocument($rec->originId);
                    $originRec = $origin->fetch();

                    // Ако втората мярка е опаковка, смята се колко е в основната мярка
                    $quantityInPack = ($productInfo->packagings[$rec->additionalMeasureId]) ? $productInfo->packagings[$rec->additionalMeasureId]->quantity : 1;
                    if($origin->isInstanceOf('planning_Tasks')){
                        if($originRec->labelPackagingId == $rec->additionalMeasureId){
                            $quantityInPack = isset($originRec->labelQuantityInPack) ? $originRec->labelQuantityInPack : planning_Tasks::getDefaultQuantityInLabelPackagingId($rec->productId, $originRec->measureId, $originRec->labelPackagingId, $originRec->id);
                        }
                    }

                    $additionalMeasureQuantity = $rec->additionalMeasureQuantity * $quantityInPack;
                    $rec->quantityInPack = $additionalMeasureQuantity / $rec->packQuantity;
                }
            }

            $rec->quantity = round($rec->packQuantity * $rec->quantityInPack, 5);

            // Проверка на количеството на втората мярка
            if(!empty($rec->additionalMeasureId)){
                if($warning = $mvc->checkAdditionalMeasureQuantity($rec)){
                    $form->setWarning('additionalMeasureQuantity', $warning);
                }
            }
        }
    }


    /**
     * Проверка има ли разминаване спрямо очакваното отношение между мерките
     *
     * @param $rec              - ид на запис
     * @return string|null $msg - съобщение на предупреждението
     */
    private function checkAdditionalMeasureQuantity($rec)
    {
        $productRec = cat_Products::fetch($rec->productId, 'measureId');
        $origin = doc_Containers::getDocument($rec->originId);
        $jobRec = ($origin->isInstanceOf('planning_Tasks')) ? doc_Containers::getDocument($origin->fetchField('originId'))->fetch() : $origin->fetch();
        if(empty($jobRec->secondMeasureId)) return null;

        $secondMeasureDerivitives = cat_UoM::getSameTypeMeasures($jobRec->secondMeasureId);

        // Ако се произвежда в някоя от вторите мерки
        if(array_key_exists($rec->packagingId, $secondMeasureDerivitives)){
            $additionalQuantity = cat_UoM::convertValue($rec->packQuantity, $rec->packagingId, $jobRec->secondMeasureId);

            $packRec = cat_products_Packagings::getPack($rec->productId, $jobRec->secondMeasureId);
            $coefficient = is_object($packRec) ? $packRec->quantity : 1;
            $expectedQuantity = $coefficient * $additionalQuantity;
            $additionalMeasureType = cat_UoM::fetchField($rec->additionalMeasureId, 'type');

            // Ако втората мярка е мярка
            if($additionalMeasureType == 'uom'){
                $equivalentMeasureId = $productRec->measureId;
                $expectedEquivalentQuantityInMeasure = cat_UoM::convertValue($expectedQuantity, $productRec->measureId, $rec->additionalMeasureId);
                $additionalQuantity = cat_UoM::convertValue($rec->additionalMeasureQuantity, $rec->additionalMeasureId,  $productRec->measureId);
            } else {
                $originRec = $origin->fetch();

                $equivalentMeasureId = $rec->additionalMeasureId;
                $addPackRec = cat_products_Packagings::getPack($rec->productId, $rec->additionalMeasureId);
                $quantityInPack = is_object($addPackRec) ? $addPackRec->quantity : 1;

                // Ако втората мярка е опаковка
                if($origin->isInstanceOf('planning_Tasks')){
                    if($originRec->labelPackagingId == $rec->additionalMeasureId){
                        $quantityInPack = isset($originRec->labelQuantityInPack) ? $originRec->labelQuantityInPack : planning_Tasks::getDefaultQuantityInLabelPackagingId($rec->productId, $originRec->measureId, $originRec->labelPackagingId, $originRec->id);
                    }
                }

                $expectedEquivalentQuantityInMeasure = $expectedQuantity / $quantityInPack;
                $additionalQuantity = $rec->additionalMeasureQuantity * $quantityInPack;
            }
        } else {
            // Ако не се произвежда директно във втора мярка.
            $packRec = cat_products_Packagings::getPack($rec->productId, $jobRec->secondMeasureId);
            $secondMeasureQuantityInPack = is_object($packRec) ? $packRec->quantity : 1;

            $additionalQuantity = cat_UoM::convertValue($rec->additionalMeasureQuantity, $rec->additionalMeasureId, $jobRec->secondMeasureId);
            $expectedQuantity = $rec->quantity / $secondMeasureQuantityInPack;
            $equivalentMeasureId = $rec->additionalMeasureId;
            $expectedEquivalentQuantityInMeasure = cat_UoM::convertValue($expectedQuantity, $jobRec->secondMeasureId, $rec->additionalMeasureId);
        }

        $diff = abs(core_Math::diffInPercent($additionalQuantity, $expectedQuantity));
        $allowedDiff = planning_Setup::get('PNOTE_SECOND_MEASURE_TOLERANCE_WARNING') * 100;

        // Ако разликата е над допустимата, показва се предупреждение
        if($diff > $allowedDiff){
            $expectedSecondMeasureVerbal = core_Type::getByName('double(smartRound)')->toVerbal($expectedEquivalentQuantityInMeasure);
            $equivalentMeasureIdVerbal = cat_UoM::getShortName($equivalentMeasureId);

            $msg = "Има разминаване от над |*{$allowedDiff} %, |спрямо очакваното от|* <b>{$expectedSecondMeasureVerbal} |{$equivalentMeasureIdVerbal}|*</b>";

            return $msg;
        }

        return null;
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(isset($fields['-single'])){
            $row->productId = cat_Products::getAutoProductDesc($rec->productId, null, 'short', 'internal');

            if(core_Packs::isInstalled('rack')){
                $canStore = cat_Products::fetchField($rec->productId, 'canStore');
                if($canStore == 'yes'){
                    $showLink = !(core_Packs::isInstalled('batch') && batch_Defs::getBatchDef($rec->productId) );
                    if($showLink){

                        // Бутон за палетиране
                        if($palletImgLink = rack_Pallets::getFloorToPalletImgLink($rec->storeId, $rec->productId, $rec->packagingId, $rec->packQuantity, null, $rec->containerId)){
                            $row->productId = $palletImgLink->getContent() . $row->productId;
                        }
                    }
                }
            }

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

            // Ако има втора мярка, показване на информацията за нея
            $additionalMeasureType = cat_UoM::fetchField($rec->additionalMeasureId, 'type');
            if($rec->additionalMeasureId == $productRec->measureId || $additionalMeasureType != 'uom'){
                if($additionalMeasureType != 'uom'){
                    deals_Helper::getPackInfo($row->additionalMeasureId, $rec->productId, $rec->additionalMeasureId);
                }
                $row->additionalMeasureId = ht::createHint($row->additionalMeasureId, "Това количество ще се отчете в производството");
            }

            if($additionalQuantityWarning = $mvc->checkAdditionalMeasureQuantity($rec)){
                $row->additionalMeasureQuantity = ht::createHint($row->additionalMeasureQuantity, $additionalQuantityWarning, 'warning', false);
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
                        if (in_array($state, array('rejected', 'draft', 'waiting', 'stopped', 'pending'))) {
                            $requiredRoles = 'no_one';
                        } else {
                            if($originDoc->isInstanceOf('planning_Jobs')){

                                // Ако заданието е за производим артикул само тогава да може да се пуска протокол от него
                                $productId = $originDoc->fetchField('productId');
                                $productRec = cat_Products::fetch($productId, 'canManifacture,generic');
                                if ($state == 'closed' || $productRec->canManifacture != 'yes' || $productRec->generic == 'yes') {
                                    $requiredRoles = 'no_one';
                                }
                            } elseif($originDoc->isInstanceOf('planning_Tasks')){

                                // Ако протокола е към финална ПО без допълнителни артикули да не се показва бутона
                                $originRec = $originDoc->fetch('isFinal,productId');
                                if($originRec->isFinal == 'yes'){
                                    $producedCount4FinalTask = planning_ProductionTaskProducts::count("#taskId = {$originDoc->that} AND #type = 'production'");
                                    if($producedCount4FinalTask == 1){
                                        $requiredRoles = 'no_one';
                                    }
                                }

                                if($state == 'closed' && $requiredRoles != 'no_one'){
                                    if(!planning_Tasks::isProductionAfterClosureAllowed($originDoc->that, $userId, 'taskPostProduction,ceo,production')){
                                        $requiredRoles = 'no_one';
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

        // Ако протокола е за крайния артикул
        if(static::isForJobProductId($rec)) {
            $detailsFromBom = $this->getDefaultDetailsFromBom($rec);

            // Какво е вложено до момента в заданието
            $jobRec =  static::getJobRec($rec);
            $details2 = planning_Jobs::getDefaultProductionDetailsFromConvertedByNow($jobRec);
            $details = array();

            // Сумират се очакваните детайли по рецепта и реално вложеното
            if(countR($details2)){
                foreach ($details2 as $d2){
                    $d2->_realData = true;

                    if(array_key_exists("{$d2->productId}|{$d2->type}", $detailsFromBom)){
                        $d2->quantityFromBom = $detailsFromBom["{$d2->productId}|{$d2->type}"]->quantityFromBom;
                        $d2->quantity = $d2->quantityFromBom;
                        unset($detailsFromBom["{$d2->productId}|{$d2->type}"]);

                    } else {
                        $d2->quantity = 0;
                    }

                    $key = "{$d2->productId}|{$d2->packagingId}|{$d2->type}|{$d2->storeId}";
                    $obj = clone $d2;
                    if(!array_key_exists($key, $details)){
                        $obj->quantityExpected = 0;
                        $details[$key] = $obj;
                    }

                    if(!empty($d2->batch)){
                        $details[$key]->batches[$d2->batch] = $d2->quantityExpected;
                    }

                    $details[$key]->quantityExpected += $d2->quantityExpected;
                    if(empty($d2->quantityFromBom)){
                        $details[$key]->quantity += $d2->quantityExpected;
                    }
                }
            }

            if(countR($detailsFromBom)) {
                foreach ($detailsFromBom as $d3) {
                    $key = "{$d3->productId}|{$d3->packagingId}|{$d3->type}|{$d3->storeId}";
                    if(!array_key_exists($key, $details)){
                        $obj1 = clone $d3;
                        $obj1->quantity = $obj1->quantityFromBom;
                        $obj1->quantityExpected = null;
                        $obj1->quantityFromBom = 0;
                        $details[$key] = $obj1;
                        $details[$key]->quantityFromBom += $d3->quantityFromBom;
                    }
                }
            }

            // Ако е избрано с приоритет очакваното количество, то се попълва
            if(planning_Setup::get('PRODUCTION_NOTE_PRIORITY') == 'expected'){
                foreach ($details as &$d3){
                    if(isset($d3->quantityExpected) && isset($d3->quantityFromBom)){
                        $d3->quantity = $d3->quantityExpected;
                    }
                }
            }
        } elseif($origin->isInstanceOf('planning_Tasks')){
            $details = array();
        } else {
            $details = $this->getDefaultDetailsFromBom($rec);
        }

        // Връщаме намерените дефолтни детайли
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
            if($quantityProduced){
                $roundQuantity = $resource->propQuantity - $bomInfo1['resources'][$index]->propQuantity;
            } else {
                $roundQuantity = $resource->propQuantity;
            }

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
        if ($rec->_isClone === true) return;

        $details = $mvc->getDefaultDetails($rec);
        if(countR($details)) {
            foreach ($details as $dRec) {
                $dRec->noteId = $rec->id;

                // Склада за влагане се добавя само към складируемите артикули, които не са отпадъци
                if (empty($dRec->storeId) && isset($rec->inputStoreId) && $dRec->_realData !== true) {
                    if (cat_Products::fetchField($dRec->productId, 'canStore') == 'yes' && $dRec->type == 'input') {
                        $dRec->storeId = $rec->inputStoreId;
                    }
                }

                if($dRec->_realData === true){
                    $dRec->autoAllocate = false;
                    $dRec->_clonedWithBatches = true;
                }

                planning_DirectProductNoteDetails::save($dRec);
                if(is_array($dRec->batches)){
                    batch_BatchesInDocuments::saveBatches('planning_DirectProductNoteDetails', $dRec->id, $dRec->batches, true);
                }
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
                    $data->toolbar->addBtn('Рецепта', $bomUrl, null, 'warning=Наистина ли желаете да създадете нова работна рецепта от протокола|*!,ef_icon = img/16/add.png,title=Създаване на нова рецепта по протокола');
                }
            }
        }

        if ($data->toolbar->haveButton('btnConto')) {
            if (!$data->toolbar->isErrorBtn('btnConto')) {
                if ($mvc->haveRightFor('adddebitamount', $rec)) {
                    $data->toolbar->removeBtn('btnConto');
                    $attr = array();
                    if(!haveRole('seePrice,ceo')){
                        $attr['warning'] = 'Наистина ли желаете документът да бъде контиран|*?';
                        $defaultPrimeCost = self::getDefaultDebitPrice($rec);
                        if(!isset($defaultPrimeCost)){
                            $attr['error'] = 'Документът не може да бъде контиран, защото на артикула не може да се изчисли себестойност|*!';
                        }
                    }
                    $data->toolbar->addBtn('Контиране', array($mvc, 'addDebitAmount', $rec->id, 'ret_url' => array($mvc, 'single', $rec->id)), 'id=btnConto,ef_icon = img/16/tick-circle-frame.png,title=Контиране на протокола за производство', $attr);
                }
            }
        }

        if(haveRole('debug') && $rec->state != 'rejected'){
            $data->toolbar->addBtn('Зареди очакваното', array($mvc, 'fillNote', $rec->id, 'ret_url' => true), null, 'ef_icon = img/16/bug.png,title=Зареди очакваните количества,row=2');
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
        if(!static::isForJobProductId($rec)) return 0;

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

            $contoUrl = $this->getContoUrl($id);
            $contoUrl['ret_url'] = $this->getSingleUrlArray($rec->id);

            // Редирект към екшъна за контиране
            redirect($contoUrl);
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
     * @param mixed $id          - ид или запис
     * @param mixed $forMvc      - за кой мениджър
     * @param string  $option    - опции
     *
     * @return array $products         - масив с информация за артикули
     *               o productId       - ид на артикул
     *               o name            - име на артикула
     *               o quantity        - к-во
     *               o amount          - сума на артикула
     *               o inStores        - масив с ид-то и к-то във всеки склад в който се намира
     *               o transportWeight - транспортно тегло на артикула
     *               o transportVolume - транспортен обем на артикула
     */
    public function getCorrectableProducts($id, $forMvc, $option = null)
    {
        $products = array();
        $rec = $this->fetchRec($id);

        if($option == 'storable'){
            $canStore = cat_Products::fetchField($rec->productId, 'canStore');
            if($canStore != 'yes') return $products;
        }

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


    /**
     * Връща заданието към което е протокола за производство.
     * Ако е към ПО, намира заданието към което е тя
     *
     * @param $id
     * @return mixed
     */
    public static function getJobRec($id)
    {
        $rec = static::fetchRec($id);

        $originDoc = doc_Containers::getDocument($rec->originId);
        if ($originDoc->isInstanceOf('planning_Tasks')) {
            $jobRec = doc_Containers::getDocument($originDoc->fetchField('originId'))->fetch();
        } else {
            $jobRec = $originDoc->fetch();
        }

        return $jobRec;
    }


    /**
     * Дали артикула от протокола е същия, като този от заданието
     *
     * @param $id
     * @return bool
     */
    public static function isForJobProductId($id)
    {
        $rec = static::fetchRec($id);
        $jobRec = static::getJobRec($rec);

        return $rec->productId == $jobRec->productId;
    }


    /**
     * @todo тестов екшън
     */
    public function act_fillNote()
    {
        requireRole('debug');
        expect($id = Request::get('id', 'int'));
        expect($rec = static::fetch($id));

        planning_DirectProductNoteDetails::delete("#noteId = {$rec->id}");
        static::on_AfterCreate($this, $rec);

        followRetUrl(null, 'Записите са заредени от начало');
    }


    /**
     * Връща позволените партиди за заприхождаване в документа
     *
     * @param $id
     * @return array|null $options
     */
    public function getAllowedInBatches_($id)
    {
        $rec = static::fetchRec($id);

        // Ако ще се произвежда артикулът от заданиет, наличните партиди за заприхождаване са тези от заданието
        if(planning_DirectProductionNote::isForJobProductId($rec)) {
            $jobRec = static::getJobRec($rec);
            $options = cls::get('planning_Jobs')->getAllowedBatchesForJob($jobRec->containerId);

            return $options;
        }

        return null;
    }


    /**
     * Изпълнява се преди контиране на документа
     */
    protected static function on_BeforeConto(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        if (planning_DirectProductNoteDetails::fetchField("#noteId = {$rec->id} AND #productId = {$rec->productId} AND #storeId IS NOT NULL")) {
            core_Statuses::newStatus('Произвежданият артикул не може да бъде влаган директно от склад в същия протокол|*!', 'error');

            return false;
        }
    }
}
