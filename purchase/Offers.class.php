<?php



/**
 * Мениджър на оферти за покупки
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Оферти за покупки
 */
class purchase_Offers extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'Pqt';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Оферта от доставчик';
    
     
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,folderId, product, date, offer, sum, document';

    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/doc_table.png';
    
    
    /**
     * Заглавие
     */
    var $title = 'Оферти за покупки';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Rejected, plg_State2, plg_SaveAndNew, doc_plg_BusinessDoc, acc_plg_DocumentSummary,
						purchase_Wrapper, doc_DocumentPlg, doc_EmailCreatePlg, doc_ActivatePlg,
						plg_AutoFilter';

    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,purchase';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,purchase';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,purchase';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,purchase';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,purchase';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    /**
     * Поле за търсене
     */
    var $searchFields = 'folderId, threadId, containerId';
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "4.1|Логистика";
    
    
    var $filterDateField = 'date';
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	 // Контрагент
         $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Доставчик');
         $this->FLD('contragentId', 'int', 'input=hidden');
        
    	 $this->FLD('product', 'varchar', 'caption=Продукт,summary=amount');
    	 $this->FLD('sum', 'double', 'caption=Оферта->Цена, summary=amount');
    	 $this->FLD('date', 'date', 'caption=Оферта->Дата');
    	 $this->FLD('offer', 'richtext(bucket=Notes)', 'caption=Оферта->Детайли');
    	 $this->FLD('document', 'fileman_FileType(bucket=Notes)', 'caption=Оферта->Документ');
    }

    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields .= ',state';
        
        $data->listFilter->input('state', 'silent');
        
    	if($filterRec = $data->listFilter->rec){
        	if($filterRec->state){
        		$data->query->where(array("#state = '[#1#]'", $filterRec->state));
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
    static function on_AfterRecToVerbal ($mvc, &$row, $rec, $fields = array())
    {
    	$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    	
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща данните за адресата
     */
    static function getContragentData1($id)
    {
        //TODO
        
        return $contragentData;
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото наимей по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        //TODO
        $handle = purchase_Offers::getHandle($id);
        
        //Създаваме шаблона
        $tpl = new ET(tr("Предлагаме на вашето внимание нашата оферта: ") . '#[#handle#]');
        
        //Заместваме датата в шаблона
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('doc_ContragentDataIntf');
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     *
     * @param $threadId int ид на нишката
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
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
        $coverClass = doc_Folders::fetchCoverClassName($folderId);
    
        return cls::haveInterface('doc_ContragentDataIntf', $coverClass);
    }
    
    
    /**
     * @param int $id key(mvc=sales_Sales)
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
      	$rec = $this->fetch($id);
        
        $row = new stdClass();
        
        //Заглавие
        $row->title = "Оферта №{$rec->id}";
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        return $row;
    }
    
}
