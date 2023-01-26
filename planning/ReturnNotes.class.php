<?php


/**
 * Клас 'planning_ReturnNotes' - Документ за Протокол за връщане
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
class planning_ReturnNotes extends deals_ManifactureMaster
{
    /**
     * Заглавие
     */
    public $title = 'Протоколи за връщане от производство';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Mrn';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'acc_TransactionSourceIntf=planning_transaction_ReturnNote';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, deals_plg_SaveValiorOnActivation, store_plg_StockPlanning, store_plg_Request, store_plg_StoreFilter, planning_Wrapper, acc_plg_DocumentSummary, acc_plg_Contable,
                    doc_DocumentPlg, plg_Printing, plg_Clone, plg_Sorting,deals_plg_EditClonedDetails,cat_plg_AddSearchKeywords, plg_Search';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'storeId,note';


    /**
     * До потребители с кои роли може да се споделя документа
     *
     * @var string
     * @see store_StockPlanning
     */
    public $stockPlanningDirection = 'in';


    /**
     * Кой има право да чете?
     */
    public $canConto = 'ceo,consumption,store';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,consumption,store';
    
    
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
    public $singleTitle = 'Протокол за връщане от производство';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'planning/tpl/SingleLayoutReturnNote.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '3.51|Производство';
    
    
    /**
     * Детайл
     */
    public $details = 'planning_ReturnNoteDetails';
    
    
    /**
     * Кой е главния детайл
     *
     * @var string - име на клас
     */
    public $mainDetail = 'planning_ReturnNoteDetails';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'planning_ReturnNoteDetails';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/produce_out.png';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo,consumption,store';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, valior,deadline,modifiedOn';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        parent::setDocumentFields($this);
        $this->FLD('departmentId', 'key(mvc=planning_Centers,select=name,allowEmpty)', 'caption=Ц-р на дейност,before=note');
        $this->FLD('useResourceAccounts', 'enum(yes=Да,no=Не)', 'caption=Детайлно връщане,notNull,default=yes,maxRadio=2,before=note');
        $this->setField('storeId', 'placeholder=Само услуги,silent,removeAndRefreshForm=quantity');
    }
    
    
    /**
     * Кои детайли да се клонират с промяна
     *
     * @param stdClass $rec
     * @return array $res
     *          ['recs'] - записи за промяна
     *          ['detailMvc] - модел от който са
     */
    public function getDetailsToCloneAndChange_($rec)
    {
        $Detail = cls::get($this->mainDetail);
        $id = $rec->clonedFromId;

        if (isset($rec->originId) && empty($rec->id)) {
            $origin = doc_Containers::getDocument($rec->originId);
            if ($origin->isInstanceOf('planning_ConsumptionNotes')) {
                $Detail = cls::get('planning_ConsumptionNoteDetails');
                $id = $origin->that;
            }
        }

        $dQuery = $Detail->getQuery();
        $dQuery->where("#{$Detail->masterKey} = {$id}");
        $dQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
        if(!isset($rec->storeId)){
            $dQuery->where("#canStore = 'no'");
        }

        $res = array('recs' => $dQuery->fetchAll(), 'detailMvc' => $Detail);

        return $res;
    }
    
    
    /**
     * Подготвя данните (в обекта $data) необходими за единичния изглед
     */
    public function prepareEditForm_($data)
    {
        parent::prepareEditForm_($data);
        
        $form = &$data->form;
        $rec = &$form->rec;
        
        // Ако ориджина е протокол за влагане
        if (isset($rec->originId) && empty($rec->id)) {
            $origin = doc_Containers::getDocument($rec->originId);
            if ($origin->isInstanceOf('planning_ConsumptionNotes')) {
                $data->action = 'clone';
                
                return $data;
            }
        }

        if(isset($rec->id)){
            if(planning_ReturnNoteDetails::getStorableProductsCount($rec->id)){
                $form->setField('storeId', 'mandatory');
            }
        }

        return $data;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $form->setDefault('useResourceAccounts', planning_Setup::get('CONSUMPTION_USE_AS_RESOURCE'));
        $form->setDefault('valior', dt::today());

        $folderCover = doc_Folders::getCover($rec->folderId);
        if ($folderCover->isInstanceOf('planning_Centers')) {
            $form->setDefault('departmentId', $folderCover->that);
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
        $row->useResourceAccounts = ($rec->useResourceAccounts == 'yes') ? 'Артикулите ще бъдат изписани от незавършеното производство един по един' : 'Артикулите ще бъдат изписани от незавършеното производството сумарно';
        $row->useResourceAccounts = tr($row->useResourceAccounts);
        
        if (isset($rec->departmentId)) {
            $row->departmentId = planning_Centers::getHyperlink($rec->departmentId, true);
        }

        if(empty($rec->storeId)){
            $row->storeId = ht::createHint("<i style='color:blue'>" . tr('Не е посочен') . "</i>", 'В протокола могат да се избират само услуги|*!');
        }
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
        if (planning_Tasks::fetchField("#threadId = {$threadId} AND (#state = 'active' || #state = 'stopped' || #state = 'wakeup' || #state = 'closed' || #state = 'pending')")) {

            return true;
        }

        return false;
    }
}
