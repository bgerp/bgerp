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
    public $loadList = 'plg_RowTools, plg_SaveAndNew, plg_Created, planning_Wrapper, plg_RowNumbering, plg_AlignDecimals2';
    
    
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
}