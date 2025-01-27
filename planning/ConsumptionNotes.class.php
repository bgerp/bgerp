<?php


/**
 * Клас 'planning_ConsumptionNotes' - Документ за Протокол за влагане в производството
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2024 Experta OOD
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
    public $loadList = 'plg_RowTools2, store_plg_StoreFilter, doc_SharablePlg, store_plg_Request, deals_plg_SaveValiorOnActivation, planning_Wrapper, acc_plg_DocumentSummary, acc_plg_Contable,
                    doc_DocumentPlg, plg_Printing, plg_Clone, deals_plg_SetTermDate,deals_plg_EditClonedDetails,change_Plugin,cat_plg_AddSearchKeywords, plg_Search, store_plg_StockPlanning';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'storeId,note';


    /**
     * Кой има право да чете?
     */
    public $canConto = 'ceo,consumption,store';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo,consumption,store';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,consumption,store, planningAll';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,consumption,store';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,consumption,store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,consumption,store';
    
    
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
        $this->FLD('departmentId', 'key(mvc=planning_Centers,select=name,allowEmpty)', 'caption=Допълнително->Ц-р на дейност,after=receiver');
        $this->FLD('description', 'richtext(bucket=Notes,rows=2)', 'caption=Информация за ремонта->Извършено,after=departmentId,input=none');
        $this->FLD('sender', 'varchar', 'caption=Допълнително->Предал');
        $this->FLD('receiver', 'varchar', 'caption=Допълнително->Получил');
        $this->FLD('useResourceAccounts', 'enum(yes=Да,no=Не)', 'caption=Допълнително->Детайлно влагане,notNull,default=yes,maxRadio=2');
        $this->setField('storeId', 'placeholder=Само услуги');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        $folderCover = doc_Folders::getCover($rec->folderId);
        if ($folderCover->isInstanceOf('planning_Centers')) {
            $form->setDefault('departmentId', $folderCover->that);
        }

        if(isset($rec->id)){
            if(planning_ConsumptionNoteDetails::getStorableProductsCount($rec->id)){
                $form->setField('storeId', 'mandatory');
            }
        }

        if(empty($rec->id)){
            $showSenderAndReceiver = planning_Setup::get('SHOW_SENDER_AND_RECEIVER_SETTINGS');
            if($showSenderAndReceiver == 'yesDefault'){
                $form->setDefault('sender', core_Users::getCurrent('names'));
            }
        }

        $showSenderAndReceiver = true;
        $showSenderAndReceiverSetting = planning_Setup::get('SHOW_SENDER_AND_RECEIVER_SETTINGS');
        if($showSenderAndReceiverSetting == 'no'){
            $form->setField('sender', 'input=none');
            $form->setField('receiver', 'input=none');
            $showSenderAndReceiver = false;
        }

        if(isset($rec->originId)){
            $origin = doc_Containers::getDocument($rec->originId);
            if($origin->isInstanceOf('cal_Tasks')){
                $form->setField('description', "input,mandatory,changable");
                $form->setField('sender', 'caption=Информация за ремонта->Извършил,input');
                $form->setField('receiver', 'caption=Информация за ремонта->Приел,input');
                $form->setDefault('useResourceAccounts', "no");
                $form->setField('useResourceAccounts', 'input=hidden');
                $showSenderAndReceiver = true;
            }
        }
        $form->setDefault('useResourceAccounts', planning_Setup::get('CONSUMPTION_USE_AS_RESOURCE'));
        if($showSenderAndReceiver){
            $mvc->setEmployeesOptions($form);
        }

        if($jobRec = static::getJobRec($rec)){
            $rec->_inputStores = keylist::toArray($jobRec->inputStores);
            $selectableStores = bgerp_plg_FLB::getSelectableFromArr('store_Stores', $rec->_inputStores);
            if(countR($selectableStores) == 1){
                $form->setDefault('storeId', key($selectableStores));
            }
        }
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        if($form->isSubmitted()){
            if($rec->state == 'draft' || empty($rec->state)){
                if(is_array($rec->_inputStores) && countR($rec->_inputStores)){
                    if(empty($rec->storeId)){
                        if(countR($rec->_inputStores)){
                            $form->setWarning('storeId', 'Не е избран склад при очакван такъв по Задание|*!');
                        }
                    } elseif(!in_array($rec->storeId, $rec->_inputStores)) {
                        $form->setWarning('storeId', 'Избраният склад не е от очакваните по Задание|*!');
                    }
                }
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
        $row->useResourceAccounts = ($rec->useResourceAccounts == 'yes') ? 'Артикулите ще бъдат вложени в производството поотделно' : 'Артикулите ще бъдат вложени в производството сумарно';
        $row->useResourceAccounts = tr($row->useResourceAccounts);
        
        if (isset($rec->departmentId)) {
            $row->departmentId = planning_Centers::getHyperlink($rec->departmentId, true);
        }

        if(empty($rec->storeId)){
            $row->storeId = ht::createHint("<i style='color:blue'>" . tr('Не е посочен') . "</i>", 'В протокола могат да се избират само услуги|*!');
        }

        $row->protocolTitle = tr("протокол за влагане в производство");
        $row->receiverCaption = tr("Получил");
        $row->senderCaption = tr("Предал");
        if($rec->originId){
            $origin = doc_Containers::getDocument($rec->originId);
            if($origin->isInstanceOf('cal_Tasks')){
                $row->protocolTitle = tr("Протокол за извършен ремонт");
                $row->receiverCaption = tr("Приел ремонта");
                $row->senderCaption = tr("Извършил ремонта");
            }
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
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        // Ако записа е клониран не правим нищо
        if ($rec->_isClone === true) return;

        // ако има източник ПО, копират се вложените неща по нея
        if(isset($rec->originId)){
            $origin = doc_Containers::getDocument($rec->originId);
            if($origin->isInstanceOf('planning_Tasks')){
                $dQuery = planning_ProductionTaskProducts::getQuery();
                $dQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
                $dQuery->where("#taskId = {$origin->that} AND #totalQuantity != 0 AND #type = 'input'");
                if(isset($rec->storeId)){
                    $dQuery->where("(#storeId = {$rec->storeId} OR #storeId IS NULL) AND #canStore != 'no'");
                }

                while($dRec = $dQuery->fetch()){
                    $newRec = (object)array('noteId' => $rec->id, 'productId' => $dRec->productId, 'packagingId' => $dRec->packagingId, 'quantityInPack' => $dRec->quantityInPack, 'quantity' => $dRec->totalQuantity * $dRec->quantityInPack);
                    if($genericProductId = planning_GenericProductPerDocuments::getRec('planning_ProductionTaskProducts', $dRec->id)){
                        $newRec->_genericProductId = $genericProductId;
                    }
                    planning_ConsumptionNoteDetails::save($newRec);
                }
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
                if(!$mvc->canAddToOriginId($rec->originId, $userId)){
                    $requiredRoles = 'no_one';
                }
            }
        }
    }


    /**
     * Помощна ф-я връщаща има ли активни ПВ в посочената нишка
     * (ако нишката е обръврзана със задание - всички нишки към него)
     *
     * @param int $threadId - ид на нишка
     * @param int|null $productId - ид на артикул, null ако търсим само бройка
     * @return bool
     */
    public static function existActivatedInThread($threadId, $productId = null)
    {
        // Намиране на заданието от нишката
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        if($firstDoc->isInstanceOf('planning_Jobs')){
            $threadArr = planning_Jobs::getJobLinkedThreads($firstDoc->that);
        } elseif($firstDoc->isInstanceOf('planning_Tasks')){
            $jobId = planning_Jobs::fetchField("#containerId={$firstDoc->fetchField('originId')}");
            $threadArr = planning_Jobs::getJobLinkedThreads($jobId);
        } else {
            $threadArr = array($threadId => $threadId);
        }

        // Ако има артикул се търси има ли в нишките влагане на конкретния артикул
        if(isset($productId)){
            $cQuery = planning_ConsumptionNoteDetails::getQuery();
            $cQuery->EXT('state', 'planning_ConsumptionNotes', 'externalName=state,externalKey=noteId');
            $cQuery->EXT('threadId', 'planning_ConsumptionNotes', 'externalName=threadId,externalKey=noteId');
            $cQuery->in("threadId", $threadArr);
            $cQuery->where("#productId = {$productId} AND #state = 'active'");

            return $cQuery->count() > 0;
        }

        // Ако няма гледа се просто има ли контирани ПВ
        $cQuery = planning_ConsumptionNotes::getQuery();
        $cQuery->where("#state = 'active'");
        $cQuery->in("threadId", $threadArr);

        return $cQuery->count() > 0;
    }


    /**
     * Преди оттегляне, ако има затворени пера в транзакцията, не може да се оттегля
     */
    public static function on_BeforeReject($mvc, &$res, $id)
    {
        if(!store_Setup::canDoShippingWhenStockIsNegative()){
            $rec = $mvc->fetchRec($id);
            if($rec->useResourceAccounts == 'yes') {

                // Проверка дали оттеглянето ще доведе до отрицателни к-ва в незавършеното производство
                $detailQuantities = array();
                $dQuery = planning_ConsumptionNoteDetails::getQuery();
                $dQuery->where("#noteId = {$rec->id}");
                while($dRec = $dQuery->fetch()){
                    $detailQuantities[$dRec->productId] += -1 * $dRec->quantity;
                }

                if ($error = planning_WorkInProgress::getContoRedirectError($detailQuantities, false)){
                    core_Statuses::newStatus($error, 'error');
                    return false;
                }
            }
        }
    }
}
