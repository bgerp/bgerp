<?php 


/**
 * Мениджър за шаблони, които ще се използват от документи
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_TplManager extends core_Master
{
    
    
    /**
     * Заглавие
     */
    public $title = "Мениджър на шаблони";
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Шаблон";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_SaveAndNew, plg_Modified, doc_Wrapper, doc_ActivatePlg, plg_RowTools';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,admin';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'ceo,admin';
    
    
    /**
    * Кой може да го разглежда?
    */
    public $canList = 'ceo,admin';


    /**
    * Кой може да го изтрива?
    */
    public $canDelete = 'ceo,admin';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin';

	
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,admin';


    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo,admin';

    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, name, docClassId, createdBy, modifiedOn';

    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, mandatory, width=100%');
        $this->FLD('docClassId', 'class(interface=doc_DocumentIntf,select=title)', "caption=Клас, width=100%");
        $this->FLD('content', 'text', "caption=Текст,column=none, width=100%");
        
        $this->setDbUnique('name,docClass');
    }
    
    
    /**
     * Връща всички шаблони за посочения клас
     * @param int $classId - ид на клас
     * @return array $options - опции за шаблоните на документа
     */
    public static function getTemplates($classId)
    {
    	expect(core_Classes::fetch($classId));
    	
    	$options = array();
    	$query = static::getQuery();
    	$query->where("#docClassId = {$classId}");
    	while($rec = $query->fetch()){
    		$options[$rec->id] = $rec->name;
    	}
    	
    	return $options;
    }
}         