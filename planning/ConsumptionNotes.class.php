<?php


/**
 * Клас 'planning_ConsumptionNotes' - Документ за Протокол за влагане в производството
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_ConsumptionNotes extends deals_ManifactureMaster
{
    /**
     * Заглавие
     */
    public $title = 'Протоколи за влагане в производство';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Mcn';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'acc_TransactionSourceIntf=planning_transaction_ConsumptionNote';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_plg_StoreFilter, deals_plg_SaveValiorOnActivation, planning_Wrapper, acc_plg_DocumentSummary, acc_plg_Contable,
                    doc_DocumentPlg, plg_Printing, plg_Clone, deals_plg_SetTermDate,deals_plg_EditClonedDetails,cat_plg_AddSearchKeywords, plg_Search, store_plg_StockPlanning';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'storeId,note';
    
    
    /**
     * Кой има право да чете?
     */
    public $canConto = 'ceo,planning,store';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo,planning,store';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,planning,store';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,planning,store';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,planning,store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,planning,store';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Протокол за влагане в производство';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'planning/tpl/SingleLayoutConsumptionNote.shtml';
    
    
    /**
     * Файл за единичния изглед в мобилен
     */
    public $singleLayoutFileNarrow = 'planning/tpl/SingleLayoutConsumptionNoteNarrow.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '3.5|Производство';
    
    
    /**
     * Детайл
     */
    public $details = 'planning_ConsumptionNoteDetails';
    
    
    /**
     * Кой е главния детайл
     *
     * @var string - име на клас
     */
    public $mainDetail = 'planning_ConsumptionNoteDetails';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'planning_ConsumptionNoteDetails';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/produce_in.png';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn,valior,deadline,modifiedOn';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        parent::setDocumentFields($this);
        $this->FLD('departmentId', 'key(mvc=planning_Centers,select=name,allowEmpty)', 'caption=Ц-р на дейност,before=note');
        $this->FLD('useResourceAccounts', 'enum(yes=Да,no=Не)', 'caption=Детайлно влагане,notNull,default=yes,maxRadio=2,before=note');
        $this->setField('storeId', 'placeholder=Само услуги');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $form->setDefault('useResourceAccounts', planning_Setup::get('CONSUMPTION_USE_AS_RESOURCE'));
        
        $folderCover = doc_Folders::getCover($rec->folderId);
        if ($folderCover->isInstanceOf('planning_Centers')) {
            $form->setDefault('departmentId', $folderCover->that);
        }

        if(isset($rec->id)){
            if(planning_ConsumptionNoteDetails::getStorableProductsCount($rec->id)){
                $form->setField('storeId', 'mandatory');
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->useResourceAccounts = ($rec->useResourceAccounts == 'yes') ? 'Артикулите ще бъдат вкарани в производството по артикули' : 'Артикулите ще бъдат вложени в производството сумарно';
        $row->useResourceAccounts = tr($row->useResourceAccounts);
        
        if (isset($rec->departmentId)) {
            $row->departmentId = planning_Centers::getHyperlink($rec->departmentId, true);
        }

        if(empty($rec->storeId)){
            $row->storeId = ht::createHint("<i style='color:blue'>" . tr('Не е посочен') . "</i>", 'В протокола могат да се избират само услуги|*!');
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = &$data->rec;
        
        if ($rec->state == 'active' && planning_ReturnNotes::haveRightFor('add', (object) array('originId' => $rec->containerId, 'threadId' => $rec->threadId))) {
            $data->toolbar->addBtn('Връщане', array('planning_ReturnNotes', 'add', 'originId' => $rec->containerId, 'storeId' => $rec->storeId, 'ret_url' => true), null, 'ef_icon = img/16/produce_out.png,title=Връщане на артикули от производството');
        }
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
        $rec = $this->fetchRec($id);
        $dQuery = planning_ConsumptionNoteDetails::getQuery();
        $dQuery->where("#noteId = {$id}");
        $dQuery->show('productId, quantity');
        
        $warning = deals_Helper::getWarningForNegativeQuantitiesInStore($dQuery->fetchAll(), $rec->storeId, $rec->state);
        
        return $warning;
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
        // Може да добавяме или към нишка в която има задание
        if (planning_Jobs::fetchField("#threadId = {$threadId} AND (#state = 'active' || #state = 'stopped' || #state = 'wakeup')")) {
            
            return true;
        }
        
        // Може да добавяме или към нишка в която има задание
        if (planning_Tasks::fetchField("#threadId = {$threadId} AND (#state = 'active' || #state = 'stopped' || #state = 'wakeup')")) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        // Ако записа е клониран не правим нищо
        if ($rec->_isClone === true) return;

        // ако има източник ПО, копират се вложените неща по нея
        if(isset($rec->originId)){
            $origin = doc_Containers::getDocument($rec->originId);
            $dQuery = planning_ProductionTaskProducts::getQuery();
            $dQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
            $dQuery->where("#taskId = {$origin->that} AND #totalQuantity != 0 AND #type = 'input'");
            if(isset($rec->storeId)){
                $dQuery->where("#storeId = {$rec->storeId} OR #storeId IS NULL");
            } else {
                $dQuery->where("#canStore = 'no'");
            }

            while($dRec = $dQuery->fetch()){
                $newRec = (object)array('noteId' => $rec->id, 'productId' => $dRec->productId, 'packagingId' => $dRec->packagingId, 'quantityInPack' => $dRec->quantityInPack, 'quantity' => $dRec->totalQuantity);
                planning_ConsumptionNoteDetails::save($newRec);
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'add' && isset($rec)) {
            if (isset($rec->originId)) {
                $origin = doc_Containers::getDocument($rec->originId);
                if(!$origin->isInstanceOf('planning_Tasks')){
                    $requiredRoles = 'no_one';
                } else {
                    $state = $origin->fetchField('state');
                    if (in_array($state, array('rejected', 'draft', 'closed', 'waiting', 'stopped', 'pending'))) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
    }
}
