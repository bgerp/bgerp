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
 *
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
    public $listFields = 'sysId, name, description';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsSingleField = 'name';


    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'admin, ceo';


    /**
     * Описание на модела (таблицата)
     */
    protected function description()
    {
        $this->FLD('sysId', 'varchar(16)', 'caption=Име->Съкратено, mandatory');
        $this->FLD('name', 'varchar(32)', 'caption=Име->Детайлно, mandatory');
        $this->FLD('description', 'richtext', 'caption=Описание');

        $this->setDbUnique('sysId');
    }


    /**
     * Връща id на профил по sysId
     *
     * @param string $sysId
     * @param boolean $force
     *
     * @return false|stdClass
     */
    public static function getIdFromSysId($sysId, $force = true)
    {
        if (!$sysId) {

            return false;
        }

        $rec = self::fetch(array("#sysId = '[#1#]'", $sysId));

        if (!$force && !$rec) {

            return false;
        }

        if ($rec && $force && $rec->state != 'active') {
            $rec->state = 'active';

            self::save($rec);
        }

        if (!$rec) {
            $rec = new stdClass();
            $rec->sysId = $sysId;
            $rec->name = $sysId;

            self::save($rec);
        }

        return $rec->id;
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
        $profileRec = self::fetchRec($profileId);
        
        $dArr = array();
        $dQuery = ztm_ProfileDetails::getQuery();
        $dQuery->EXT('type', 'ztm_Registers', 'externalName=type,externalKey=registerId');
        $dQuery->EXT('rState', 'ztm_Registers', 'externalName=state,externalKey=registerId');
        $dQuery->where("#profileId = '{$profileRec->id}'");
        $dQuery->where("#rState = 'active'");
        $dQuery->show('registerId,value,type');
        
        while ($dRec = $dQuery->fetch()) {
            if (in_array($dRec->type, array('int', 'float')) == 'int') {
                $dRec->value = (float) $dRec->value;
            }
            $dArr[$dRec->registerId] = $dRec->value;
        }
        
        $res = array();
        $query = ztm_Registers::getQuery();
        $query->where("#state = 'active'");
        $query->likeKeylist("profileIds", $profileRec->id);

        while ($rec = $query->fetch()) {
            if (in_array($rec->type, array('int', 'float')) == 'int') {
                $rec->default = (float) $rec->default;
            }
            
            $default = $rec->default;
            if (array_key_exists($rec->id, $dArr)) {
                $default = $dArr[$rec->id];
            }
            
            $res[$rec->id] = $default;
        }
        
        return (object) $res;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($rec && (($action == 'delete') || ($action == 'reject'))) {
            if (ztm_Devices::fetch(array("#state = 'active' && #profileId = '[#1#]'", $rec->id))) {
                $requiredRoles = 'no_one';
            }
        }
    }
}
