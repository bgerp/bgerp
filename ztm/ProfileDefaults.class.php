<?php


/**
 * Детайл на профил в Zontromat
 *
 *
 * @category  bgerp
 * @package   ztm
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 
 * @title     Детайл на профил в Zontromat
 */
class ztm_ProfileDefaults extends core_Detail
{
    public $title = 'Детайл на профил в Zontromat';
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Регистър';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ztm, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ztm, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ztm, ceo';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'ztm, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ztm, ceo';
    public $canSingle = 'ztm, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    public $canReject = 'ztm, ceo';
    public $canRestore = 'ztm, ceo';
    
    
    /**
     * Кой може да променя състоянието на документите
     *
     * @see plg_State2
     */
    public $canChangestate = 'ztm, ceo';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'profileId';
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'ztm_Wrapper, plg_Rejected, plg_Created, plg_State2, plg_RowTools2, plg_Modified, plg_Sorting';
    
    
    /**
     *
     * @var string
     */
    public $listFields = 'profileId,registerId, value';
    
    /**
     * Описание на модела (таблицата)
     */
    protected function description()
    {
        $this->FLD('profileId','key(mvc=ztm_Profiles, select=name)','caption=Профил,mandatory,smartCenter');
        $this->FLD('registerId','key(mvc=ztm_RegistersDef, select=name)','caption=Регистър');
        $this->FLD('value', 'varchar(32)', 'caption=Стойност');
        
        $this->setDbUnique('profileId, registerId');
        
    }
    
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
      
        $data->toolbar->removeBtn('btnAdd');
        
    }
    
    
    
}