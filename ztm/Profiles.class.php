<?php
/**
 * Master на профили в Zontromat
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
 
 * @title     Профили в Zontromat
 */
class ztm_Profiles extends core_Master
{
    public $title = 'Профили в Zontromat';
    
    
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
     * Кой може да го разглежда?
     */
    public $canList = 'ztm, ceo';
    
    
    /**
     * Кой има право да го разглежда?
     */
    public $canSingle = 'ztm, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой има право да го оттегля?
     */
    public $canReject = 'ztm, ceo';
    
    
    /**
     * Кой има право да го възстановява?
     */
    public $canRestore = 'ztm, ceo';
    
    
    /**
     * Кой може да променя състоянието на документите
     *
     * @see plg_State2
     */
    public $canChangestate = 'ztm, ceo';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'ztm_ProfileDetails';    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'ztm_Wrapper, plg_Rejected, plg_Created, plg_State2, plg_RowTools2, plg_Modified, plg_Sorting';
    
    
    /**
     *
     * @var string
     */
    public $listFields = 'name, description';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Описание на модела (таблицата)
     */
    protected function description()
    {
        $this->FLD('name', 'varchar(32)', 'caption=Име');
        $this->FLD('description', 'richtext', 'caption=Описание');
    }
    
    
    /**
     * Връща първоначалния отговор
     *
     * @param int $profileId
     * 
     * @return stdClass $res
     */
    public static function getDefaultRegisterValues($profileId)
    {
        $dArr = array();
        $dQuery = ztm_ProfileDetails::getQuery();
        $dQuery->EXT('type', 'ztm_Registers', 'externalName=type,externalKey=registerId');
        $dQuery->where("#profileId = '{$profileId}'");
        $dQuery->show('registerId,value,type');
        while($dRec = $dQuery->fetch()){
            if(in_array($dRec->type, array('int', 'int/float', 'float')) == 'int'){
                $dRec->value = (float)$dRec->value;
            }
            $dArr[$dRec->registerId] = $dRec->value;
        }
        
        $res = array();
        $query = ztm_Registers::getQuery();
        
        while($rec = $query->fetch()){
            if(in_array($rec->type, array('int', 'int/float', 'float')) == 'int'){
                $rec->default = (float)$rec->default;
            }
            
            $default = $rec->default;
            if(array_key_exists($rec->id, $dArr)){
                $default = $dArr[$rec->id];
            }
            
            $res[$rec->id] = $default;
        }
        
        return (object)$res;
    }
}