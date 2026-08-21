<?php


/**
 * Клас 'planning_DisassemblyNote' - Документ за протокол за разпад
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_DisassemblyNote extends planning_ProductionDocument
{
    /**
     * Протоколът за разпад не проверява за по-нов производствен документ
     */
    protected $checkNewerProductionDocument = false;


    /**
     * Заглавие
     */
    public $title = 'Протоколи за разпад';


    /**
     * Абревиатура
     */
    public $abbr = 'Mdn';


    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'acc_TransactionSourceIntf=planning_transaction_DisassemblyNote';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_plg_StoreFilter, change_Plugin, doc_SharablePlg, deals_plg_SaveValiorOnActivation, planning_Wrapper, acc_plg_DocumentSummary, acc_plg_Contable,
                    doc_DocumentPlg, doc_plg_MasterRevision, plg_Printing, plg_Clone, bgerp_plg_Blank, deals_plg_SetTermDate, plg_Sorting, cat_plg_AddSearchKeywords, plg_Search, store_plg_StockPlanning, cat_plg_DisassemblyDoc, cond_plg_DefaultValues';


    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array('allocationBy' => 'lastDocUser|lastDoc');


    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'valior,expenses,detailOrderBy,deadline,note';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'storeId,inputStoreId,note';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,production,store,planningAll';


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
     * Кои роли може да променят активен протокол
     */
    public $canChangerec = 'ceo,production,store';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Протокол за разпад';


    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'planning/tpl/SingleLayoutDisassemblyNote.shtml';


    /**
     * Групиране на документите
     */
    public $newBtnGroup = '3.5|Производство';


    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/protocol_decay.png';


    /**
     * Детайл
     */
    public $details = 'planning_DisassemblyNoteDetails';


    /**
     * Кой е главния детайл
     *
     * @var string - име на клас
     */
    public $mainDetail = 'planning_DisassemblyNoteDetails';


    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'planning_DisassemblyNoteDetails';


    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior, title=Документ,inputStoreId=От склад, storeId=В склад, folderId, deadline, createdOn, createdBy';


    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'deadline,storeId';


    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn,valior,modifiedOn';


    /**
     * Нужно ли е да има детайл, за да стане на 'Заявка'
     */
    public $requireDetailForPending = false;


    /**
     * Поле за подредбата на детайла
     */
    public $detailOrderByField = 'detailOrderBy';


    /**
     * Цените за разпределянето са към вальора (@see cat_plg_DisassemblyDoc)
     */
    public $disassemblyDateField = 'valior';


    /**
     * Кои роли може да пипат редовете на контиран протокол (@see deals_ManifactureDetail)
     */
    public $requiredRolesToEditWhenActive = 'ceo,planningMaster';


    /**
     * Описание на модела
     */
    public function description()
    {
        // Преди setDocumentFields(), за да излезе storeId веднага след тях
        $this->FLD('inputStoreId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Влагане (на артикула за разпад)->ОТ склад,input,silent,placeholder=Незавършено производство,mandatory');
        $this->FLD('expenses', 'percent(min=0)', 'caption=Влагане (на артикула за разпад)->Реж. разходи');
        // Без „Ваш №“ - протоколът няма контрагент, а оттам и ценови лист
        $this->FLD('detailOrderBy', 'enum(auto=Автоматично,creation=Ред на създаване,code=Код)', 'caption=Влагане (на артикула за разпад)->Подреждане по,notNull,value=auto');

        $this->FLD('bomId', 'key(mvc=cat_DisassemblyBoms,select=title)', 'caption=Рецепта за разпад,input=none');

        parent::setDocumentFields($this);

        // Вальорът се връща пред групата - setDocumentFields го оставя след нея
        $this->setField('valior', 'mustOrder,before=inputStoreId');
        $this->setField('storeId', 'caption=Произвеждане (заприхождаване на произведените артикули)->В склад,silent');

        $this->setField('deadline', 'caption=Информация->Срок до');
        $this->setField('note', 'caption=Информация->Бележки,after=deadline');
        $this->setDbIndex('state');
    }


    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка -
     * само в нишка, чийто пръв документ е Задание за Разпад (type=disassembly)
     *
     * @param int $threadId
     *
     * @return bool
     */
    public static function canAddToThread($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        if (!is_object($firstDoc) || !$firstDoc->isInstanceOf('planning_Jobs')) {

            return false;
        }

        return $firstDoc->fetchField('type') == 'disassembly';
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'add' && isset($rec)) {
            if (empty($rec->originId)) {
                $requiredRoles = 'no_one';
            } else {
                $originDoc = doc_Containers::getDocument($rec->originId);
                if (!$originDoc->isInstanceOf('planning_Jobs')) {
                    $requiredRoles = 'no_one';
                } else {
                    $jobRec = $originDoc->fetch('state,type,productId');
                    if ($jobRec->type != 'disassembly' || in_array($jobRec->state, array('rejected', 'draft', 'waiting', 'stopped', 'pending'))) {
                        $requiredRoles = 'no_one';
                    } else {
                        $productRec = cat_Products::fetch($jobRec->productId, 'canStore,canConvert,generic');
                        if($productRec->canStore != 'yes' || $productRec->generic == 'yes' || $productRec->canConvert != 'yes') {
                            $requiredRoles = 'no_one';
                        }
                    }
                }
            }
        }

    }


    /**
     * Изпълнява се след създаване на нов запис - автоматично се добавя детайл
     * за влагане на артикула от Заданието за разпад, с цялото планирано к-во
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        // Ако записа е клониран не правим нищо - клонираните детайли идват от cloneDetails
        if (($rec->_isClone ?? null) === true) return;

        $jobRec = static::getJobRec($rec);
        if (!$jobRec) return;

        $dRec = (object) array(
            'noteId'         => $rec->id,
            'type'           => 'input',
            'isMainInput'    => 'yes',
            'productId'      => $jobRec->productId,
            'packagingId'    => $rec->mainInputPackagingId ?? $jobRec->packagingId,
            'quantityInPack' => $rec->mainInputQuantityInPack ?? $jobRec->quantityInPack,
            'quantity'       => $rec->mainInputQuantity ?? $jobRec->quantity,
        );

        if (isset($rec->inputStoreId) && cat_Products::fetchField($jobRec->productId, 'canStore') == 'yes') {
            $dRec->storeId = $rec->inputStoreId;
        }

        planning_DisassemblyNoteDetails::save($dRec);

        // Произведените артикули се пренасят от активната рецепта за разпад
        $bomRec = cat_DisassemblyBoms::getLastActiveBom($jobRec->productId);
        if (is_object($bomRec)) {
            $rec->bomId = $bomRec->id;
            $mvc->save_($rec, 'bomId');
            static::transferBomDetails($rec, $bomRec, $dRec->quantity);
        }
    }


    /**
     * Пренася произведените артикули от рецептата, мащабирани спрямо вложеното
     * количество. Редовете на 0% също се пренасят - заприхождават се на нулева стойност
     *
     * @param stdClass $rec           - записът на протокола
     * @param stdClass $bomRec        - записът на рецептата
     * @param float    $inputQuantity - вложеното количество (в основна мярка)
     *
     * @return void
     */
    private static function transferBomDetails($rec, $bomRec, $inputQuantity)
    {
        if (empty($bomRec->quantity) || empty($inputQuantity)) return;

        $ratio = $inputQuantity / $bomRec->quantity;

        // Процентите на рецептата са към вальора на протокола
        $statuses = array();
        $percentsArr = cat_plg_DisassemblyDoc::getPercents(cls::get('cat_DisassemblyBoms'), $bomRec->id, $rec->valior, $statuses);

        $noQuantityArr = array();
        foreach ($percentsArr as $bomDetailId => $obj) {
            $bomDetailRec = cat_DisassemblyBomDetails::fetch($bomDetailId, 'productId,packagingId,quantityInPack,quantity');

            // Закръгля се по мярката; ред без количество няма какво да заприходи
            $quantity = $bomDetailRec->quantity * $ratio;
            $round = cat_UoM::fetchField(cat_Products::fetchField($bomDetailRec->productId, 'measureId'), 'round');
            if (isset($round)) {
                $quantity = round($quantity, $round);
            }

            if (empty($quantity)) {
                $noQuantityArr[$bomDetailRec->productId] = cat_Products::getTitleById($bomDetailRec->productId);
                continue;
            }

            $dRec = (object) array(
                'noteId'         => $rec->id,
                'type'           => 'production',
                'productId'      => $bomDetailRec->productId,
                'packagingId'    => $bomDetailRec->packagingId,
                'quantityInPack' => $bomDetailRec->quantityInPack,
                'quantity'       => $quantity,
                'quantityFromBom' => $quantity,
                'percentFromBom' => $obj->percent,
                'costPercent'    => ($rec->allocationBy == 'manual') ? $obj->percent : null,
            );

            if (isset($rec->storeId) && cat_Products::fetchField($bomDetailRec->productId, 'canStore') == 'yes') {
                $dRec->storeId = $rec->storeId;
            }

            // Автоматично генерираната партида се записва само на "редактиран" ред
            // (@see batch_plg_DocumentMovementDetail::on_AfterCreate/on_AfterSave)
            $dRec->isEdited = true;

            // Наливането не е редакция на ред - без версии (@see doc_plg_DetailRevisions)
            $dRec->_skipDetailRevision = true;
            planning_DisassemblyNoteDetails::save($dRec);
        }

        // Пропуснатото не бива да остава незабелязано
        if (isset($statuses['error'])) {
            core_Statuses::newStatus('Процентите от рецептата не са прехвърлени|*: |' . $statuses['error'], 'warning');
        }

        if (countR($noQuantityArr)) {
            core_Statuses::newStatus('От рецептата не са прехвърлени артикули, чието количество при това влагане се закръгля до нула|*: <b>' . implode(', ', $noQuantityArr) . '</b>', 'warning');
        }
    }


    /**
     * Зарежда наново произведените артикули от активната рецепта, заедно с режима и
     * политиката. Редът за влагане не се пипа - той е базата за мащабирането
     * (@see planning_DirectProductionNote::act_fillNote)
     */
    public function act_Reloadfrombom()
    {
        requireRole('debug');
        expect($id = Request::get('id', 'int'));
        expect($rec = static::fetch($id));
        $this->requireRightFor('edit', $rec);

        planning_DisassemblyNoteDetails::delete("#noteId = {$rec->id} AND #type = 'production'");

        $jobRec = static::getJobRec($rec);
        $bomRec = is_object($jobRec) ? cat_DisassemblyBoms::getLastActiveBom($jobRec->productId) : null;
        if (!is_object($bomRec)) {
            followRetUrl(null, '|Редовете са изтрити|*, |но артикулът няма активна рецепта за разпад|*!', 'warning');

            return;
        }

        $rec->bomId = $bomRec->id;
        $rec->allocationBy = $bomRec->allocationBy;
        $rec->priceListId = $bomRec->priceListId;
        $rec->detailOrderBy = $bomRec->detailOrderBy;
        $this->save_($rec, 'bomId,allocationBy,priceListId,detailOrderBy');

        $inputQuantity = planning_DisassemblyNoteDetails::fetchField("#noteId = {$rec->id} AND #type = 'input' AND #isMainInput = 'yes'", 'quantity');
        static::transferBomDetails($rec, $bomRec, $inputQuantity);

        followRetUrl(null, '|Редовете са заредени наново от рецептата');
    }




    /**
     * Трябва да има поне един произведен артикул
     */
    protected function getErrorWhenTryingToConto($rec, $action = 'conto')
    {
        $rec = $this->fetchRec($rec);

        if (!planning_DisassemblyNoteDetails::count("#noteId = {$rec->id} AND #type = 'production' AND #quantity != 0")) {
            return 'Не може да контирате протокола, защото няма посочени произведени артикули|*!';
        }

        return static::getPercentsError($rec);
    }


    /**
     * Защо процентите не стават за контиране - едно правило за всички пътища към
     * журнала (@see getErrorWhenTryingToConto, @see planning_transaction_DisassemblyNote)
     *
     * @param mixed $rec - ид или запис на протокол
     *
     * @return string|null
     */
    public static function getPercentsError($rec)
    {
        $rec = static::fetchRec($rec);

        // Ред на 0% не пречи - заприхождава се на нулева стойност
        $statuses = array();
        cat_plg_DisassemblyDoc::getPercents(cls::get(get_called_class()), $rec, $rec->valior, $statuses);

        return $statuses['error'] ?? null;
    }


    /**
     * Възстановяването реконтира, затова минава през същите проверки
     */
    protected static function on_BeforeRestore(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        if ($errorMsg = $mvc->getErrorWhenTryingToConto($rec, 'restore')) {
            core_Statuses::newStatus($errorMsg, 'error');

            return false;
        }
    }


    /**
     * Връща планираните наличности - артикулът за разпад (type=input) излиза от
     * склада си, произведените от разпада артикули (type=production) влизат в
     * своя. Дефолтната имплементация на store_plg_StockPlanning не различава
     * посоката по ред, затова се налага собствена реализация (@see
     * planning_DirectProductionNote::getPlannedStocks)
     *
     * @param stdClass|int $rec
     *
     * @return array
     */
    public function getPlannedStocks($rec)
    {
        $res = array();
        $id = is_object($rec) ? $rec->id : $rec;
        $rec = $this->fetch($id, '*', false);

        [$dateIn, $dateOut] = static::calcPlannedDates($this, $rec);

        $dQuery = planning_DisassemblyNoteDetails::getQuery();
        $dQuery->EXT('canConvert', 'cat_Products', 'externalName=canConvert,externalKey=productId');
        $dQuery->EXT('generic', 'cat_Products', 'externalName=generic,externalKey=productId');
        $dQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
        $dQuery->XPR('totalQuantity', 'double', 'SUM(#quantity)');
        $dQuery->where("#noteId = {$rec->id} AND #storeId IS NOT NULL AND #canStore = 'yes'");
        $dQuery->groupBy('productId,storeId,type');

        while ($dRec = $dQuery->fetch()) {
            $res[] = static::buildPlannedStockEntry($dRec, $dateIn, $dateOut);
        }

        return $res;
    }



    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        $jobRec = self::getJobRec($rec);
        if(is_object($jobRec)){
            // В Заданието за разпад ролите на storeId/inputStores са разменени
            $form->setDefault('inputStoreId', $jobRec->storeId);

            if(!empty($jobRec->inputStores)){
                $inputStores = keylist::toArray($jobRec->inputStores);
                if(countR($inputStores) == 1){
                    $form->setDefault('storeId', key($inputStores));
                }
            }

            // Бързо въвеждане на к-во/опаковка на влагания артикул, само при добавяне
            if(empty($rec->id)){
                $form->FNC('mainInputProductId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax,titleFld=name,forceOpen)', 'caption=Влагане (на артикула за разпад)->Артикул,input,before=inputStoreId,mandatory');
                $form->setFieldTypeParams('mainInputProductId', array('onlyIn' => array($jobRec->productId)));
                $form->setDefault('mainInputProductId', $jobRec->productId);
                $form->setReadOnly('mainInputProductId');

                $packs = cat_Products::getPacks($jobRec->productId, $jobRec->packagingId);
                $form->FNC('mainInputPackagingId', 'key(mvc=cat_UoM,select=shortName,select2MinItems=0)', 'caption=Влагане (на артикула за разпад)->Мярка,input,after=mainInputProductId');
                $form->setOptions('mainInputPackagingId', $packs);
                $form->setDefault('mainInputPackagingId', $jobRec->packagingId);

                $form->FNC('mainInputPackQuantity', 'double(min=0)', 'caption=Влагане (на артикула за разпад)->Количество,input,mandatory,after=mainInputPackagingId');
                $form->setDefault('mainInputPackQuantity', round($jobRec->quantity / $jobRec->quantityInPack, 5));

                // От активната рецепта; начинът се презаписва - плъгинът вече е сложил своя
                $bomRec = cat_DisassemblyBoms::getLastActiveBom($jobRec->productId);
                if (is_object($bomRec)) {
                    $form->setDefault('expenses', $bomRec->expenses);
                    $form->setDefault('detailOrderBy', $bomRec->detailOrderBy);
                    $rec->allocationBy = $bomRec->allocationBy;
                    $rec->priceListId = $bomRec->priceListId;
                    cat_plg_DisassemblyDoc::setPriceListField($form);
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
        if ($rec->state == 'active' && (!empty($rec->_updateMaster) || !empty($rec->_recontoAfterEdit))) {

            $success = false;
            try {
                $success = acc_Journal::reconto($rec->containerId);
            } catch (core_exception_Expect $e) {
                reportException($e);
            }

            // И при провалено реконтиране - иначе следващият запис виси на ключа
            $lockKey = "doc_Threads_Update_Item_{$rec->threadId}_" . core_Users::getCurrent();
            core_Locks::release($lockKey);

            if($success){
                if(!empty($rec->_recontoAfterEdit)){
                    $mvc->logWrite('Реконтиране след промяна', $rec->id);
                }

                core_Statuses::newStatus("Протоколът е реконтиран|*!");
            }
        }

        if (in_array($rec->state, array('active', 'rejected'))) {
            planning_Jobs::updateDisassembledQuantity($rec->originId);
        }
    }


    /**
     * Подготовка на бутоните на единичния изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        if (haveRole('debug') && $rec->state != 'rejected' && $mvc->haveRightFor('edit', $rec)) {
            $data->toolbar->addBtn('Очаквано', array($mvc, 'reloadfrombom', $rec->id, 'ret_url' => true), null, 'ef_icon=img/16/bug.png,title=Зареждане наново на произведените артикули от активната рецепта за разпад,row=2,warning=Произведените артикули ще бъдат заменени с тези от активната рецепта за разпад|*!');
        }
    }


    /**
     * Рендираме общия изглед за 'Single'
     */
    public function renderSingle_($data)
    {
        $tpl = parent::renderSingle_($data);
        $tpl->push('planning/tpl/styles.css', 'CSS');

        return $tpl;
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(isset($rec->inputStoreId)){
            $row->inputStoreId = store_Stores::getHyperlink($rec->inputStoreId, true);
        }

        // Извън режима "по ценова политика" записаната политика е бездейна
        if($rec->allocationBy == 'price' && !empty($rec->priceListId)){
            $row->priceListId = price_Lists::getHyperlink($rec->priceListId, true);
        } else {
            unset($row->priceListId);
        }

        if(!empty($rec->bomId)){
            $row->bomId = cat_DisassemblyBoms::getLink($rec->bomId, 0);
        }
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        $rec = &$form->rec;

        if($form->isSubmitted()) {
            if(isset($rec->mainInputPackagingId)){
                $jobRec = self::getJobRec($rec);
                $productInfo = cat_Products::getProductInfo($jobRec->productId);
                $rec->mainInputQuantityInPack = isset($productInfo->packagings[$rec->mainInputPackagingId]) ? $productInfo->packagings[$rec->mainInputPackagingId]->quantity : 1;
                $rec->mainInputQuantity = $rec->mainInputPackQuantity * $rec->mainInputQuantityInPack;
            }

            // Флагът е на формата, не на записа, и се вдига при клониране (@see plg_Clone)
            if(isset($rec->id) && empty($form->_cloneForm)){
                if($rec->state == 'active'){
                    $exRec = $mvc->fetch($rec->id, '*', false);

                    // Мастър полетата, които влизат в журнала
                    if($rec->valior != $exRec->valior || $rec->expenses != $exRec->expenses || $rec->allocationBy != $exRec->allocationBy || $rec->priceListId != $exRec->priceListId){

                        // По подадения запис. Ако изчисленото става ръчно, е същото
                        $errorMsg = empty($rec->_fillCostPercents) ? $mvc->getErrorWhenTryingToConto($rec, 'reconto') : null;
                        if($errorMsg){
                            $form->setError('valior,expenses', $errorMsg);
                        } else {
                            $rec->_recontoAfterEdit = true;
                            $form->setWarning('valior,expenses', "Документа ще бъде реконтиран след запис, поради променени данни!");
                        }
                    }
                }
            }
        }
    }
}
