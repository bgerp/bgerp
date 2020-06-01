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
class borsa_Bids extends core_Manager
{
    
    /**
     * Заглавие на модела
     */
    public $title = 'Оферти';
    
    
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
//     public $canChangestate = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'borsa_Wrapper, plg_state, plg_Created';
//     public $loadList = 'borsa_Wrapper, plg_RowTools2, plg_State2, plg_Created, plg_Modified, plg_Search, plg_Sorting';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
//     public $searchFields = 'pattern';
    
    
    public function description()
    {
        $this->FLD('lotId', 'key(mvc=borsa_Lots,select=name)', 'caption=Лот, mandatory');
        $this->FLD('periodId', 'key(mvc=borsa_Perionds,select=name)', 'caption=Период, mandatory');
        $this->FLD('price', 'double(smartRound,decimals=2)', 'caption=Цена');
        $this->FLD('quantity', 'double(smartRound,decimals=2)', 'caption=Количество');
        $this->FLD('companyId', 'key(mvc=borsa_Companies,select=name)', 'caption=Цена');
        $this->FLD('ip', 'ip', 'caption=IP,input=none');
        $this->FLD('brid', 'varchar(8)', 'caption=Браузър,input=none');
        $this->FLD('state', 'enum(draft=Чернова,active=Контиран,rejected=Оттеглен)', 'caption=Състояние,input=none');
    }
}
