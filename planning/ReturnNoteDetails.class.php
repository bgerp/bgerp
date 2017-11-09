<?php


/**
 * Клас 'planning_ReturnNoteDetails'
 *
 * Детайли на мениджър на детайлите на протокола за връщане
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_ReturnNoteDetails extends deals_ManifactureDetail
{
    
	
    /**
     * Заглавие
     */
    public $title = 'Детайли на протокола за връщане';


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
                        planning_plg_ReplaceEquivalentProducts, plg_PrevAndNext,cat_plg_ShowCodes';
    
    
    /**
     * Какво движение на партида поражда документа в склада
     *
     * @param out|in|stay - тип движение (излиза, влиза, стои)
     */
    public $batchMovementDocument = 'in';
    
    
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
    public $currentTab = 'Протоколи->Връщане';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Какви продукти да могат да се избират в детайла
     */
    protected $defaultMeta = 'canStore';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=planning_ReturnNotes)', 'column=none,notNull,silent,hidden,mandatory');
        
        parent::setDetailFields($this);
    }
}