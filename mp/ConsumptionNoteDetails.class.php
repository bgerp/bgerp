<?php


/**
 * Клас 'mp_ConsumptionNormDetails'
 *
 * Детайли на мениджър на детайлите на протокола за влагане
 *
 * @category  bgerp
 * @package   mp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class mp_ConsumptionNoteDetails extends deals_ManifactureDetail
{
    
    
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
    public $loadList = 'plg_RowTools, plg_SaveAndNew, plg_Created, mp_Wrapper, plg_RowNumbering, plg_AlignDecimals';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, mp';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, mp';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, mp';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, mp';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, measureId, quantity';
    
        
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
        $this->FLD('noteId', 'key(mvc=mp_ConsumptionNotes)', 'column=none,notNull,silent,hidden,mandatory');
        
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
    	
    	foreach ($data->rows as $id => $row){
    		$rec = $data->recs[$id];
    		
    		// Проверка дали артикула не е ресурс
    		if(!mp_ObjectResources::getResource($rec->classId, $rec->productId)){
    			
    			$row->productId = "<span class='red' title = " . tr('Артикула трябва да стане ресурс, за да се контира документа') . ">{$row->productId}</span>";
    			
    			// Ако не е ресурс и имаме права поставямя бутони за добавяне като ресурс
    			if(cls::haveInterface('mp_ResourceSourceIntf', $rec->classId)){
    				if(mp_ObjectResources::haveRightFor('add', (object)array('classId' => $rec->classId, 'objectId' => $rec->productId))){
    					$retUrl = array($mvc->Master, 'resave', $rec->noteId);
    					$row->productId .= " " . ht::createLink('', array('mp_ObjectResources', 'NewResource', 'classId' => $rec->classId, 'objectId' => $rec->productId, 'ret_url' => $retUrl), FALSE, 'ef_icon=img/16/star_1.png,title=Създаване като нов ресурс');
    					$row->productId .= " " . ht::createLink('', array('mp_ObjectResources', 'add', 'classId' => $rec->classId, 'objectId' => $rec->productId, 'ret_url' => $retUrl), FALSE, 'ef_icon=img/16/find.png,title=Връзване към съществуващ ресурс');
    				}
    			}
    		}
    	}
    }
}