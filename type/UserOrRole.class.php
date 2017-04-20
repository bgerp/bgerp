<?php


/**
 * Клас  'type_UserOrRole' - Възможност за избиране на потребители или роля
 *
 *
 * @category  ef
 * @package   type
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_UserOrRole extends type_User
{
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        parent::init($params);
        
        setIfNot($this->params['mvc'], 'core_Users');
        
        setIfNot($this->params['userSelect'], 'names');
        $this->params['select'] = $this->params['userSelect'];
        
        setIfNot($this->params['userRoles'], 'user');
        $this->params['userRoles'] = str_replace("|", ",", $this->params['userRoles']);
        $this->params['roles'] = $this->params['userRoles'];
        
        setIfNot($this->params['userRolesForTeams'], 'ceo, admin');
        $this->params['userRolesForTeams'] = str_replace("|", ",", $this->params['userRolesForTeams']);
        $this->params['rolesForTeams'] = $this->params['userRolesForTeams'];
        
        setIfNot($this->params['userRolesForAll'], 'ceo, admin');
        $this->params['userRolesForAll'] = str_replace("|", ",", $this->params['userRolesForAll']);
        $this->params['rolesForAll'] = $this->params['userRolesForAll'];
        
        setIfNot($this->params['rolesForAllSysTeam'], 'ceo, admin');
        $this->params['rolesForAllSysTeam'] = str_replace("|", ",", $this->params['rolesForAllSysTeam']);
        
        setIfNot($this->params['rolesForAllRoles'], 'ceo, admin');
        $this->params['rolesForAllRoles'] = str_replace("|", ",", $this->params['rolesForAllRoles']);
        
        if ($this->params['rolesType']) {
            $this->params['rolesType'] = str_replace("|", ",", $this->params['rolesType']);
        }
    }
    
    
    /**
     * Подготвя опциите според зададените параметри.
     */
    public function prepareOptions()
    {
        $this->prepareSelOpt = FALSE;
        
        $this->handler = md5(serialize($this->params) . '|' . core_Users::getCurrent());
        
        $this->options = parent::prepareOptions();
        
        // Ако има съответната роля за виждане на ролите
        if (haveRole($this->params['rolesForAllRoles'])) {
            
            // Добавяме неизбираемо поле за оли
            $group = new stdClass();
            $group->title = tr('Роли');
            $group->attr = array('class' => 'role');
            $group->group = TRUE;
            $this->options['roles'] = $group;
            
            // Ако има права за избор на цялата система, добавяме съответния избор
            if (haveRole($this->params['rolesForAllSysTeam'])) {
                
                $allSysTeam = self::getAllSysTeamId();
                
                $roleObj = new stdClass();
                $roleObj->title = tr("За всички потребители");
                $roleObj->value = $allSysTeam;
                $roleObj->attr = array('clas' => 'all-sys-team');
                $this->options['r_' . 'allSysTeam'] = $roleObj;
            }
            
            // Вземаме всички роли
            $rQuery = core_Roles::getQuery();
            
            $rQuery->where("#state != 'closed'");
            
            if ($this->params['rolesType']) {
                $this->params['rolesType'] = arr::make($this->params['rolesType']);
                $rQuery->orWhereArr('type', $this->params['rolesType']);
            }
            
            while($rec = $rQuery->fetch()) {
                $roleObj = new stdClass();
                $roleObj->title = core_Roles::getVerbal($rec, 'role');
                $roleObj->id = $rec->id;
                $roleObj->value = self::getSysRoleId($rec->id);
                $this->options['r_' . $rec->id] = $roleObj;
            }
        }
        
        $this->prepareSelOpt = TRUE;
        
        $this->prepareSelectOpt($this->options);
        
        return $this->options;
    }
    
    
    /**
     * 
     * @param string $value
     * 
     * @see type_User::toVerbal_()
     * 
     * @return string
     */
    function toVerbal_($value)
    {
        if ($value < 0) {
            $this->params['mvc'] = &cls::get('core_Roles');
            $this->params['select'] = 'role';
        }
        
        return parent::toVerbal_($value);
    }
    
    
    /**
     * 
     * @param string $value
     * 
     * @see type_User::fromVerbal_()
     * 
     * @return string
     */
    function fromVerbal_($value)
    {
        $key = self::getKeyFromTitle($value);
        
        if (!$key) {
            $key = $value;
        }
        
        list($type, $id) = explode('_', $key);
        
        if ($type == 'r') {
            $value = self::getSysRoleId($id);
        }
        
        return parent::fromVerbal_($value);
    }
    
    
    
    /**
     * 
     * 
     * @param string $value
     * 
     * @see type_User::fetchVal()
     * 
     * @return object
     */
    protected function fetchVal(&$value)
    {
        if ($value < 0) {
            $roleId = self::getRoleIdFromSys($value);
            
            return core_Roles::fetch((int)$roleId);
        }
        
        return parent::fetchVal($value);
    }
    
    
    /**
     * @see type_User::renderInput_()
     * 
     * @param string $name
     * @param string $value
     * @param array $attr
     * 
     * @return core_ET
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        if ($value < 0) {
            $value = self::getRoleIdFromSys($value);
            
            if ($value === 0) {
                $value = 'allSysTeam';
            }
            
            $attr['value'] = 'r_' . $value;
            $value = '';
        }
        
        return parent::renderInput_($name, $value, $attr);
    }
    
    
    /**
     * Връща ID-то за allSysTeam
     * 
     * @return integer
     */
    public static function getAllSysTeamId()
    {
        static $allSysTeams = 0;
        
        if (!$allSysTeams) {
            $allSysTeams = 1-pow(2,31);
        }
        
        return $allSysTeams;
    }
    
    
    /**
     * Връща id за групата базирано на allSysTeam
     * 
     * @param integer $roleId
     * 
     * @return integer
     */
    public static function getSysRoleId($roleId)
    {
        $allSysTeam = self::getAllSysTeamId();
        
        $nRoleId = $allSysTeam + $roleId;
        
        return $nRoleId;
    }
    
    
    /**
     * Връща id на запис от модел core_Roles от id-то определено от getSysRoleId()
     * 
     * @param integer $sysRoleId
     * 
     * @return int|NULL
     */
    public static function getRoleIdFromSys($sysRoleId)
    {
        if ($sysRoleId >= 0) return NULL;
        
        $allSysTeam = self::getAllSysTeamId();
        
        $roleId = (int)($sysRoleId - $allSysTeam);
        
        return $roleId;
    }
    
    
    /**
     * Връща ключа на опциията за тази стойност
     * 
     * @param string|integer $userOrRole
     * 
     * @return NULL|string
     */
    public static function getOptVal($userOrRole)
    {
        if (strpos($userOrRole, '_')) return $userOrRole;
        
        if (!$userOrRole) return ;
        
        $inst = cls::get(get_called_class());
        
        // Няма нужда да се подготвят опциите за ролите, когато се търси потребител
        if ($userOrRole >= 0) {
            
            // Опитваме се да определим потребителя с групата му
            $userTeamsArr = self::getUserFromTeams($userOrRole);
            
            if (!empty($userTeamsArr)) {
                reset($userTeamsArr);
                $userGroupId = key($userTeamsArr);
                
                if ($userGroupId) return $userGroupId;
            }
            
            $inst->params['rolesForAllRoles'] = 'no_one';
        }
        
        $inst->prepareOptions();
        
        foreach ((array)$inst->options as $optVal => $vals) {
            if ($vals->value == $userOrRole) {
                
                return $optVal;
            }
        }
    }
    
    
    /**
     * 
     * 
     * @param mixed $key
     * 
     * @return mixed
     */
    public function prepareKey($key)
    {
        // Позволените са латински цифри и _
        $key = preg_replace('/[^0-9\_\-]/i', '', $key);
        
        return $key;
    }
}
