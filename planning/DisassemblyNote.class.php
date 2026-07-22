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
class planning_DisassemblyNote extends deals_ManifactureMaster
{
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
    public $loadList = 'plg_RowTools2, store_plg_StoreFilter, doc_SharablePlg, deals_plg_SaveValiorOnActivation, planning_Wrapper, acc_plg_DocumentSummary, acc_plg_Contable,
                    doc_DocumentPlg, plg_Printing, plg_Clone, bgerp_plg_Blank, deals_plg_SetTermDate, plg_Sorting, cat_plg_AddSearchKeywords, plg_Search, store_plg_StockPlanning';


    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'detailOrderBy,note';


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
     * Допълнителен CSS клас на listTopContainer
     */
    public $listTopContainerHtmlClass = 'twoColsFilter';


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
     * Може ли да се редактират активирани документи
     */
    public $canEditActivated = true;


    /**
     * Кои роли се изискват да може да се редактира, когато е активиран
     */
    public $requiredRolesToEditWhenActive = 'ceo,planningMaster';


    /**
     * Описание на модела
     */
    public function description()
    {
        // Декларирани преди parent::setDocumentFields(), за да се появи storeId
        // (добавено там без after=/before=) естествено веднага след тях
        $this->FLD('inputStoreId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Влагане (на артикула за разпад)->ОТ склад,input,silent,placeholder=Незавършено производство,mandatory');
        $this->FLD('expenses', 'percent(min=0)', 'caption=Влагане (на артикула за разпад)->Реж. разходи');
        $this->FLD('detailOrderBy', 'enum(auto=Автоматично,creation=Ред на създаване,code=Код,reff=Ваш №)', 'caption=Влагане (на артикула за разпад)->Подреждане по,notNull,value=auto');

        parent::setDocumentFields($this);

        // Връщаме вальора на мястото му отпреди тази група (той също се добавя
        // без after=/before= в setDocumentFields, затова без това щеше да остане след нея)
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
     * Може ли документа да се добави в посочената папка (не може да е начало на нишка)
     */
    public static function canAddToFolder($folderId)
    {
        return false;
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

        if ($action == 'edit' && isset($rec)) {
            if($rec->state == 'active'){
                if(!haveRole($mvc->requiredRolesToEditWhenActive, $userId)){
                    $requiredRoles = 'no_one';
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
    }


    /**
     * Изпълнява се преди контиране на документа - трябва да има поне един
     * артикул за произвеждане, и всички произвеждани артикули да имат склад
     */
    public static function on_BeforeConto(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);

        if (!planning_DisassemblyNoteDetails::count("#noteId = {$rec->id} AND #type = 'production' AND #quantity != 0")) {
            core_Statuses::newStatus('Не може да контирате протокола, защото няма посочени произведени артикули|*!', 'error');

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

        $date = !empty($rec->{$this->termDateFld}) ? $rec->{$this->termDateFld} : (!empty($rec->{$this->valiorFld}) ? $rec->{$this->valiorFld} : null);
        $horizonAdd = store_Setup::get('PLANNED_DATE_ADDITIVE_IF_IN_THE_PAST');
        $dateIn = $date;
        if (empty($date) || $date < dt::today()) {
            $dateIn = dt::addSecs($horizonAdd, dt::now());
        }
        $dateOut = empty($date) ? $rec->createdOn : $date;

        $dQuery = planning_DisassemblyNoteDetails::getQuery();
        $dQuery->EXT('canConvert', 'cat_Products', 'externalName=canConvert,externalKey=productId');
        $dQuery->EXT('generic', 'cat_Products', 'externalName=generic,externalKey=productId');
        $dQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
        $dQuery->XPR('totalQuantity', 'double', 'SUM(#quantity)');
        $dQuery->where("#noteId = {$rec->id} AND #storeId IS NOT NULL AND #canStore = 'yes'");
        $dQuery->groupBy('productId,storeId,type');

        while ($dRec = $dQuery->fetch()) {
            $genericProductId = null;
            if ($dRec->generic == 'yes') {
                $genericProductId = $dRec->productId;
            } elseif ($dRec->canConvert == 'yes') {
                $genericProductId = planning_GenericMapper::fetchField("#productId = {$dRec->productId}", 'genericProductId');
            }

            $quantityIn = $quantityOut = null;
            if ($dRec->type == 'input') {
                $detailDate = $dateOut;
                $quantityOut = $dRec->totalQuantity;
            } else {
                $detailDate = $dateIn;
                $quantityIn = $dRec->totalQuantity;
            }

            $res[] = (object) array('storeId' => $dRec->storeId,
                'productId'                   => $dRec->productId,
                'date'                        => $detailDate,
                'quantityIn'                  => $quantityIn,
                'quantityOut'                 => $quantityOut,
                'genericProductId'            => $genericProductId);
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
            // За Разпад ролите на storeId/inputStores в Заданието са разменени -
            // влаганият артикул идва от storeId, а произведените от inputStores
            $form->setDefault('inputStoreId', $jobRec->storeId);

            if(!empty($jobRec->inputStores)){
                $inputStores = keylist::toArray($jobRec->inputStores);
                if(countR($inputStores) == 1){
                    $form->setDefault('storeId', key($inputStores));
                }
            }

            // Бързо въвеждане на к-во/опаковка на основния влаган артикул (от Заданието),
            // само при добавяне - вместо да се пренася само цялото планирано к-во
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
            try {
                $success = acc_Journal::reconto($rec->containerId);
                if($success){
                    $lockKey = "doc_Threads_Update_Item_{$rec->threadId}_" . core_Users::getCurrent();
                    core_Locks::release($lockKey);
                    core_Statuses::newStatus("Протоколът е реконтиран|*!");
                }
            } catch (core_exception_Expect $e) {
                reportException($e);
            }
        }

        if (in_array($rec->state, array('active', 'rejected'))) {
            planning_Jobs::updateDisassembledQuantity($rec->originId);
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

            if(isset($rec->id)){
                if($rec->state == 'active'){
                    $exRec = $mvc->fetch($rec->id, '*', false);
                    if($rec->valior != $exRec->valior || $rec->expenses != $exRec->expenses){
                        $rec->_recontoAfterEdit = true;
                        $form->setWarning('valior,expenses', "Документа ще бъде реконтиран след запис, поради променени данни!");
                    }
                }
            }
        }
    }
}
