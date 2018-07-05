<?php



/**
 * Видове палети
 *
 *
 * @category  bgerp
 * @package   pallet
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pallet_PalletTypes extends core_Manager
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'store_PalletTypes';
    
    
    /**
     * Заглавие
     */
    public $title = 'Видове палети';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_LastUsedKeys, pallet_Wrapper, plg_RowTools2';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,pallet';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,pallet';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,pallet';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,pallet';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,pallet';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,pallet';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,pallet';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,title,width=Широчина,depth=Дълбочина,height=Височина,maxWeight=Макс. тегло';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar(16)', 'caption=Заглавие,mandatory');
        $this->FLD('width', 'double(decimals=2)', 'caption=Палет->Широчина [м]');
        $this->FLD('depth', 'double(decimals=2)', 'caption=Палет->Дълбочина [м]');
        $this->FLD('height', 'double(decimals=2)', 'caption=Палет->Височина [м]');
        $this->FLD('maxWeight', 'double(decimals=2)', 'caption=Палет->Тегло [kg]');
    }
}
