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
    public $canAdd = 'no_one';
    
    
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
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да променя състоянието на документите
     *
     * @see plg_State2
     */
    public $canChangestate = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'borsa_Wrapper, plg_Created, plg_State2, plg_Sorting, plg_Modified, plg_RowTools2';
    
    
    /**
     * 
     */
    public function description()
    {
        $this->FLD('lotId', 'key(mvc=borsa_Lots,select=productName)', 'caption=Лот, mandatory, input=none');
        $this->FLD('from', 'varchar(32)', 'caption=От, mandatory, input=none');
        $this->FLD('to', 'varchar(32)', 'caption=До, mandatory, input=none');
        $this->FLD('qAviable', 'double(smartRound,decimals=2)', 'caption=Количество->Оферирано');
        $this->FLD('qBooked', 'double(smartRound,decimals=2)', 'caption=Количество->Запазено, input=none');
        $this->FLD('qConfirmed', 'double(smartRound,decimals=2)', 'caption=Количество->Потвърдено, input=none');
        
        $this->FNC('price', 'double(smartRound,decimals=2)', 'caption=Цена');
        $this->FNC('periodFromTo', 'varchar', 'caption=За период');
        
        $this->setDbUnique('lotId, from, to');
    }
    
    
    /**
     * 
     * @param borsa_Periods $mvc
     * @param stdClass $rec
     */
    function on_CalcPeriodFromTo($mvc, $rec)
    {
        $mask = 'd.m.Y';
        if ($rec->from == $rec->to) {
            $rec->periodFromTo = dt::mysql2verbal($rec->from, $mask);
        } else {
            $rec->periodFromTo = dt::mysql2verbal($rec->from, $mask) . ' - ' . dt::mysql2verbal($rec->to, $mask);
        }
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('modifiedOn', 'DESC');
    }
}
