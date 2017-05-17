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
    public $loadList = 'plg_RowTools2, plg_SaveAndNew, plg_Created, planning_Wrapper, plg_RowNumbering, plg_AlignDecimals2,
                        planning_plg_ReplaceEquivalentProducts, plg_PrevAndNext';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,planning,store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,planning,store';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,planning,store';
    
    
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
     */
    protected $defaultMeta = 'canConvert,canStore';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=planning_ConsumptionNotes)', 'column=none,notNull,silent,hidden,mandatory');
        
        parent::setDetailFields($this);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
    	if(!count($data->rows)) return;
    	 
    	foreach ($data->rows as $id => &$row){
    		$rec = $data->recs[$id];
    	
    		$warning = deals_Helper::getQuantityHint($rec->productId, $data->masterData->rec->storeId, $rec->quantity);
    		if(strlen($warning) && $data->masterData->rec->state == 'draft'){
    			$row->packQuantity = ht::createHint($row->packQuantity, $warning, 'warning', FALSE);
    		}
    	}
    }
}