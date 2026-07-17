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
    public $singleIcon = 'img/16/page_paste.png';


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
    public $listFields = 'valior, title=Документ, storeId=В склад, folderId, deadline, createdOn, createdBy';


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
     * Полета, които при клониране да не са попълнени
     */
    public $fieldsNotToClone = 'debitAmount';


    /**
     * Може ли да се редактират активирани документи
     */
    public $canEditActivated = true;


    /**
     * Описание на модела
     */
    public function description()
    {
        parent::setDocumentFields($this);

        $this->setField('storeId', 'caption=Произвеждане (заприхождаване на произведените артикули)->В склад,silent,removeAndRefreshForm');
        $this->FLD('inputStoreId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Влагане (на артикула за разпад)->ОТ склад,input,silent,placeholder=Незавършено производство,after=storeId');
        $this->FLD('expenses', 'percent(min=0)', 'caption=Влагане (на артикула за разпад)->Реж. разходи,after=Информация');
        $this->FLD('detailOrderBy', 'enum(auto=Автоматично,creation=Ред на създаване,code=Код,reff=Ваш №)', 'caption=Влагане (на артикула за разпад)->Подреждане по,notNull,value=auto');

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
    }


    /**
     * Изпълнява се след създаване на нов запис - автоматично се добавя детайл
     * за влагане на артикула от Заданието за разпад, с цялото планирано к-во
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        // Ако записа е клониран не правим нищо - клонираните детайли идват от cloneDetails
        if (($rec->_isClone ?? null) === true) {

            return;
        }

        $jobRec = static::getJobRec($rec);
        if (!$jobRec) return;

        $dRec = (object) array(
            'noteId'         => $rec->id,
            'type'           => 'input',
            'isMainInput'    => 'yes',
            'productId'      => $jobRec->productId,
            'packagingId'    => $jobRec->packagingId,
            'quantityInPack' => $jobRec->quantityInPack,
            'quantity'       => $jobRec->quantity,
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
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        $jobRec = self::getJobRec($rec);
        if(is_object($jobRec)){
            $form->setDefault('storeId', $jobRec->storeId);
        }
    }
}
