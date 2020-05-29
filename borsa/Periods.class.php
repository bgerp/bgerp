<?php 

/**
 * 
 *
 * @category  bgerp
 * @package   borsa
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class borsa_Periods extends core_Manager
{
    
    /**
     * Заглавие на модела
     */
    public $title = 'Периоди';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'borsa, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'borsa, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'borsa, ceo';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'borsa, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'borsa, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'borsa, ceo';
    
    
//     /**
//      * Кой може да променя състоянието на документите
//      *
//      * @see plg_State2
//      */
//     public $canChangestate = 'borsa, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'borsa_Wrapper, plg_Created';
//     public $loadList = 'borsa_Wrapper, plg_RowTools2, plg_State2, plg_Created, plg_Modified, plg_Search, plg_Sorting';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
//     public $searchFields = 'pattern';
    
    
    public function description()
    {
        $this->FLD('lotId', 'key(mvc=borsa_Lots,select=name)', 'caption=Лот, mandatory');
        $this->FLD('from', 'varchar(32)', 'caption=От, mandatory');
        $this->FLD('to', 'varchar(32)', 'caption=До, mandatory');
        $this->FLD('qAviable', 'double(smartRound,decimals=2)', 'caption=Количество->Оферирано');
        $this->FLD('qBooked', 'double(smartRound,decimals=2)', 'caption=Количество->Запазено');
        $this->FLD('qConfirmed', 'double(smartRound,decimals=2)', 'caption=Количество->Потвърдено');
        
        $this->FNC('price', 'double(smartRound,decimals=2)', 'caption=Цена');
        
        $this->setDbUnique('lotId, from, to');
    }
}
