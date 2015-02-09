<?php



/**
 * Клас 'techno2_SpecificationFolders' - Корици на папки спецификации
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno2_SpecificationFolders extends core_Master
{
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    //public $interfaces = 'cat_ProductFolderCoverIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created,plg_Rejected,techno2_Wrapper,plg_State,doc_FolderPlg,plg_RowTools,plg_Search';
    
    
    /**
     * Да се създаде папка при създаване на нов запис
     */
    public $autoCreateFolder = 'instant';
   
    
    /**
     * Заглавие
     */
    public $title = "Спецификации";
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'name, description';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Спецификация';
    
    
    /**
     * Път към картинка 16x16
     */
    public $singleIcon = 'img/16/project-archive.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'techno2/tpl/SingleLayoutSpecificationFolders.shtml';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id=№,name,meta=Свойства,inCharge,access,shared,createdOn,createdBy';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'powerUser';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'techno,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'techno,ceo';
    
    
    /**
     * Кой може да го види?
     */
    public $canSingle = 'powerUser';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'techno,ceo';
    
    
    /**
     * Кой може да го оттегли?
     */
    public $canReject = 'techno,ceo';
    
    
    /**
     * Кой може да го възстанови?
     */
    public $canRestore = 'techno,ceo';
    
    
    /**
     * Кой има права Rip
     */
    public $canWrite = 'techno,ceo';

    
    /**
     * Описание на полетата на модела
     */
    public function description()
    {
        $this->FLD('name' , 'varchar(128)', 'caption=Наименование,mandatory');
        $this->FLD('description' , 'richtext(rows=3)', 'caption=Описание');
        
        // Свойства присъщи на продуктите в групата
        $this->FLD('meta', 'set(canSell=Продаваеми,
                                canBuy=Купуваеми,
                                canStore=Складируеми,
                                canConvert=Вложими,
                                fixedAsset=Дълготрайни активи,
        						canManifacture=Производими,
        						waste=Отпаден)', 'caption=Свойства->Списък,columns=2');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Връща мета дефолт мета данните на папката
     * 
     * @param int $id - ид на спецификация папка
     * @return array $meta - масив с дефолт мета данни
     */
    public function getDefaultMeta($id)
    {
    	$meta = type_Set::toArray($this->fetchField($id, 'meta'));
    	
    	return $meta;
    }
}