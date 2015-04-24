<?php


/**
 * Клас 'planning_ConsumptionNormDetails'
 *
 * Детайли на мениджър на детайлите на протокола за влагане
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_ConsumptionNoteDetails extends deals_ManifactureDetail
{
    
    
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'mp_ConsumptionNoteDetails';
	
	
    /**
     * Заглавие
     */
    public $title = 'Детайли на протокола за влагане';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Продукт';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'noteId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_SaveAndNew, plg_Created, planning_Wrapper, plg_RowNumbering, plg_AlignDecimals';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, planning';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, planning';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, planning';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, planning';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, packQuantity';
    
        
    /**
     * Активен таб
     */
    public $currentTab = 'Протоколи->Влагане';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Какви продукти да могат да се избират в детайла
     *
     * @var enum(canManifacture=Производими,canConvert=Вложими)
     */
    protected $defaultMeta = 'canConvert';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=planning_ConsumptionNotes)', 'column=none,notNull,silent,hidden,mandatory');
        
        parent::setDetailFields($this);
        
        // Само вложими продукти
        $this->setDbUnique('noteId,productId,classId');
    }
    
    
    /**
     * След извличане на записите от базата данни
     */
    public static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
    	if(!count($data->recs)) return;
    	
    	if($data->masterData->rec->state != 'draft') return;
    	
    	foreach ($data->rows as $id => $row){
    		$rec = $data->recs[$id];
    		
    		// Проверка дали артикула не е ресурс
    		if(!planning_ObjectResources::getResource($rec->classId, $rec->productId)){
    			
    			$row->productId = "<span style='color:#9A5919' title = '" . tr('Артикула трябва да стане ресурс за да се контира документа') . "'>{$row->productId}</span>";
    			
    			// Ако не е ресурс и имаме права поставямя бутони за добавяне като ресурс
    			if(cls::haveInterface('planning_ResourceSourceIntf', $rec->classId)){
    				if(planning_ObjectResources::haveRightFor('add', (object)array('classId' => $rec->classId, 'objectId' => $rec->productId))){
    					$retUrl = array($mvc->Master, 'resave', $rec->noteId);
    					$row->productId .= " " . ht::createLink('', array('planning_ObjectResources', 'NewResource', 'classId' => $rec->classId, 'objectId' => $rec->productId, 'ret_url' => $retUrl), FALSE, 'ef_icon=img/16/star_1.png,title=Създаване като нов ресурс');
    					$row->productId .= " " . ht::createLink('', array('planning_ObjectResources', 'add', 'classId' => $rec->classId, 'objectId' => $rec->productId, 'ret_url' => $retUrl), FALSE, 'ef_icon=img/16/find.png,title=Връзване към съществуващ ресурс');
    				}
    			}
    		}
    	}
    }
}