<?php


/**
 * Избор на Меню
 *
 *
 * @category  bgerp
 * @package   hr
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Избор на Меню
 */
class hr_Menu extends core_Master
{
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Menu';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Дневно меню';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,folderId, product, date, offer, sum, document';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/doc_table.png';
    
    
    /**
     * Заглавие
     */
    public $title = 'Избор на меню';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Rejected, plg_State2, plg_SaveAndNew, doc_plg_BusinessDoc, acc_plg_DocumentSummary,
						hr_Wrapper,plg_Clone, doc_DocumentPlg, doc_ActivatePlg';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo';
    
    
    /**
     * Име на документа в бързия бутон за добавяне в папката
     */
    public $buttonInFolderTitle = 'Меню';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Поле за търсене
     */
    public $searchFields = 'folderId, threadId, containerId';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '5.8|Човешки ресурси';
    
    public $filterDateField = 'date';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'sum,date';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        // Контрагент
        $this->FLD('companyId', 'key(mvc=catering_Companies, select=companyId)', 'caption=Фирма');
        
        // $this->FLD('contragentClassId', 'key(mvc=catering_Companies, select=companyId)', 'caption=Фирма');
        // $this->FLD('contragentId', 'int', 'input=hidden');
        
        $this->FLD('product', 'varchar', 'caption=Продукт,summary=amount');
        $this->FLD('sum', 'double', 'caption=Оферта->Цена, summary=amount');
        $this->FLD('date', 'date', 'caption=Оферта->Дата');
        $this->FLD('offer', 'richtext(bucket=Notes)', 'caption=Оферта->Детайли');
        $this->FLD('doc', 'fileman_FileType(bucket=Notes)', 'caption=Оферта->Документ,oldFieldName=document');
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    public function on_AfterPrepareListFilter($mvc, $data)
    {
        // Показваме само това поле. Иначе и другите полета
        // на модела ще се появят
        // $data->listFilter->showFields .= ',state';
        
        // $data->listFilter->input('state', 'silent');
        
        // if($filterRec = $data->listFilter->rec){
        // if($filterRec->state){
        // $data->query->where(array("#state = '[#1#]'", $filterRec->state));
        // }
        // }
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     *
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getCoversAndInterfacesForNewDoc()
    {
        return array(
            'crm_ContragentAccRegIntf'
        );
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     *
     * @param $threadId int
     *            ид на нишката
     */
    public static function canAddToThread($threadId)
    {
        // Добавяме тези документи само в персонални папки
        $threadRec = doc_Threads::fetch($threadId);
        
        return self::canAddToFolder($threadRec->folderId);
    }
    
    
    /**
     * Може ли документ-оферта да се добави в посочената папка?
     * Документи-оферта могат да се добавят само в папки с корица контрагент.
     *
     * @param $folderId int
     *            ид на папката
     *
     * @return bool
     */
    public static function canAddToFolder($folderId)
    {
        $coverClass = doc_Folders::fetchCoverClassName($folderId);
        
        return cls::haveInterface('crm_ContragentAccRegIntf', $coverClass);
    }
    
    
    /**
     *
     * @param int $id
     *                key(mvc=sales_Sales)
     *
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        // Заглавие
        $row->title = "Оферта №{$rec->id}";
        
        // Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        // Състояние
        $row->state = $rec->state;
        
        // id на създателя
        $row->authorId = $rec->createdBy;
        
        return $row;
    }
}
