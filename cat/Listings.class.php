<?php



/**
 * Ценови политики
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Ценови политики
 */
class cat_Listings extends core_Master
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Листвания на артикули';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Листване на артикули";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, cat_Wrapper, doc_ActivatePlg, plg_Search, doc_DocumentPlg, doc_plg_SelectFolder';
                    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'folderId';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Li";
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'cat_ListingDetails';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title, folderId, createdOn, createdBy';
    
    
    /**
     * Кой може да го промени?
     */
    public $canEdit = 'cat,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'cat,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'cat,ceo';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'cat,ceo';
   
    
    /**
     * Поле за връзка към единичния изглед
     */
    public $rowToolsSingleField = 'title';

    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'cat/tpl/SingleLayoutListing.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "3.99|Търговия";
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'doc_UnsortedFolders,crm_ContragentAccRegIntf';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('title', 'varchar', 'mandatory,caption=Заглавие');
    	$this->FLD('isPublic', 'enum(yes=Да,no=Не)', 'mandatory,caption=Публичен,input=none');
    	
    	$this->setDbUnique('title');
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	$row = new stdClass();
    	$title = $this->getVerbal($rec, 'title');
    	 
    	$row->title    = tr($this->singleTitle) . " \"{$title}\"";
    	$row->authorId = $rec->createdBy;
    	$row->author   = $this->getVerbal($rec, 'createdBy');
    	$row->recTitle = $row->title;
    	$row->state    = $rec->state;
    
    	return $row;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'activate'){
    		if(empty($rec->id)){
    			$requiredRoles = 'no_one';
    		} else {
    			if(!cat_ListingDetails::fetchField("#listId = {$rec->id}")){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass $rec
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	if(isset($rec->folderId)){
    		$Cover = doc_Folders::getCover($rec->folderId);
    		$rec->isPublic = ($Cover->haveInterface('crm_ContragentAccRegIntf')) ? 'no' : 'yes';
    	}
    }
}