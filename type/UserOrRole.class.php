<?php


/**
 * Клас  'type_UserOrRole' - Възможност за избиране на потребители или роля
 *
 *
 * @category  ef
 * @package   type
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class type_UserOrRole extends type_User
{
    /**
     * Инициализиране на обекта
     */
    public function init($params = array())
    {
        parent::init($params);
        
        setIfNot($this->params['mvc'], 'core_Users');
        
        setIfNot($this->params['userSelect'], 'names');
        $this->params['select'] = $this->params['userSelect'];
        
        setIfNot($this->params['userRoles'], 'user');
        $this->params['userRoles'] = str_replace('|', ',', $this->params['userRoles']);
        $this->params['roles'] = $this->params['userRoles'];
        
        setIfNot($this->params['userRolesForTeams'], 'ceo, admin');
        $this->params['userRolesForTeams'] = str_replace('|', ',', $this->params['userRolesForTeams']);
        $this->params['rolesForTeams'] = $this->params['userRolesForTeams'];
        
        setIfNot($this->params['userRolesForAll'], 'ceo, admin');
        $this->params['userRolesForAll'] = str_replace('|', ',', $this->params['userRolesForAll']);
        $this->params['rolesForAll'] = $this->params['userRolesForAll'];
        
        setIfNot($this->params['rolesForAllSysTeam'], 'ceo, admin');
        $this->params['rolesForAllSysTeam'] = str_replace('|', ',', $this->params['rolesForAllSysTeam']);
        
        setIfNot($this->params['rolesForAllRoles'], 'ceo, admin');
        $this->params['rolesForAllRoles'] = str_replace('|', ',', $this->params['rolesForAllRoles']);
        
        if ($this->params['rolesType']) {
            $this->params['rolesType'] = str_replace('|', ',', $this->params['rolesType']);
        }
        
        setIfNot($this->params['additionalRoles'], 'partner, distributor, agent');
        $this->params['additionalRoles'] = str_replace('|', ',', $this->params['additionalRoles']);

        setIfNot($this->params['rolesForDomains'], 'no_one');
        $this->params['rolesForDomains'] = str_replace('|', ',', $this->params['rolesForDomains']);
    }
    
    
    /**
     * Подготвя опциите според зададените параметри.
     */
    public function prepareOptions($value = null)
    {
        $this->prepareSelOpt = false;
        
        $this->handler = md5(serialize($this->params) . '|' . core_Users::getCurrent());
        
        $this->options = parent::prepareOptions();
        
        $oArr = array();

        // Ако има съответната роля за виждане на ролите
        if (haveRole($this->params['rolesForAllRoles'])) {
            
            // Добавяме неизбираемо поле за роли
            $group = new stdClass();
            $group->title = tr('Роли');
            $group->attr = array('class' => 'role');
            $group->group = true;
            $oArr['roles'] = $group;
            
            // Ако има права за избор на цялата система, добавяме съответния избор
            if (haveRole($this->params['rolesForAllSysTeam'])) {
                $allSysTeam = self::getAllSysTeamId();
                
                $roleObj = new stdClass();
                $roleObj->title = tr('Всички потребители');
                $roleObj->value = $allSysTeam;
                $roleObj->attr = array('clas' => 'all-sys-team');
                $oArr['r_' . 'allSysTeam'] = $roleObj;
            }
            
            // Вземаме всички роли
            $rQuery = core_Roles::getQuery();

//             $rQuery->where("#state != 'closed'");
            
            if ($this->params['rolesType']) {
                $this->params['rolesType'] = arr::make($this->params['rolesType']);
                $rQuery->orWhereArr('type', $this->params['rolesType']);
            }
            
            while ($rec = $rQuery->fetch()) {
                $roleObj = new stdClass();
                $roleObj->title = core_Roles::getVerbal($rec, 'role');
                $roleObj->id = $rec->id;
                $roleObj->value = self::getSysRoleId($rec->id);
                $oArr['r_' . $rec->id] = $roleObj;
            }
        }
        
        if (!empty($oArr)) {
            setIfNot($this->options, array());
            
            if ($this->params['showRolesFirst'] && haveRole($this->params['showRolesFirst'])) {
                
                if ($this->options) {
                    $fk = key($this->options);
                    if ($this->options[$fk] && ($this->options[$fk]->value == core_Users::getCurrent())) {
                        $oArr = array($fk => $this->options[$fk]) + $oArr;
                        unset($this->options[$fk]);
                    }
                }
                
                $this->options = $oArr + $this->options;
            } else {
                $this->options += $oArr;
            }
        }

        // Ако има съответната роля за виждане на домейните
        if (haveRole($this->params['rolesForDomains'])) {

            $oArr = array();

            // Добавяме неизбираемо поле за домейни
            $group = new stdClass();
            $group->title = tr('Домейни');
            $group->attr = array('class' => 'role');
            $group->group = true;
            $oArr['domains'] = $group;

            // Вземаме всички домейни
            $rQuery = cms_Domains::getQuery();

            while ($rec = $rQuery->fetch()) {
                $roleObj = new stdClass();
                $roleObj->title = cms_Domains::getRecTitle($rec);
                $roleObj->id = $rec->id;
                $roleObj->value = self::getDomainRoleId($rec->id);
                $oArr['d_' . $rec->id] = $roleObj;
            }

            if (countR($oArr) > 1) {
                $this->options += $oArr;
            }
        }

        $this->prepareSelOpt = true;
        
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
    public function toVerbal_($value)
    {
        $sel = $this->params['select'];
        $mvc = $this->params['mvc'];

        if ($value < 0) {

            if (self::getTypeFromId($value) == 'domain') {
                $this->params['mvc'] = cls::get('cms_Domains');
                $this->params['select'] = 'domain';
            } else {
                $this->params['mvc'] = cls::get('core_Roles');
                $this->params['select'] = 'role';
            }
        }

        $res = parent::toVerbal_($value);
        
        $this->params['mvc'] = $mvc;
        $this->params['select'] = $sel;
        
        return $res;
    }
    
    
    /**
     *
     * @param string $value
     *
     * @see type_User::fromVerbal_()
     *
     * @return string
     */
    public function fromVerbal_($value)
    {
        $key = self::getKeyFromTitle($value);

        if (!$key) {
            $key = $value;
        }

        list($type, $id) = explode('_', $key);
        
        if (($type == 'r') || ($type == 'd')) {

            $rType = ($type == 'd') ? 'domain' : 'role';

            $value = self::getSysRoleId($id, $rType);
        }

        return parent::fromVerbal_($value);
    }


    /**
     * Помощна функция за вземане на типа спрямо `id`
     *
     * @param integer $id
     *
     * return string
     */
    protected static function getTypeFromId($id)
    {
        if ($id > 0) {

            return 'user';
        }

        $domainsSysId = self::getAllSysTeamId('domain');
//        $rolesSysId = self::getAllRolesSysId('roles');

        if ($id > $domainsSysId) {

            return 'domain';
        }

        return 'role';
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
            $type = '';
            $recId = self::getRoleIdFromSys($value, $type);

            $class = 'core_Roles';
            if ($type == 'domain') {
                $class = 'cms_Domains';
            }

            return $class::fetch((int) $recId);
        }
        
        return parent::fetchVal($value);
    }
    
    
    /**
     * @see type_User::renderInput_()
     *
     * @param string $name
     * @param string $value
     * @param array  $attr
     *
     * @return core_ET
     */
    public function renderInput($name, $value = '', &$attr = array())
    {
        if ($value < 0) {
            $type = '';
            $value = self::getRoleIdFromSys($value, $type);

            if ($value === 0) {
                $value = 'allSysTeam';
            }

            $t = $type == 'domain' ? 'd_' : 'r_';

            $attr['value'] = $t . $value;
            $value = '';
        }

        return parent::renderInput($name, $value, $attr);
    }


    /**
     * Връща ID-то за allSysTeam
     *
     * @return int
     */
    public static function getAllSysTeamId($type = 'role')
    {
        static $allSysTeamsArr = array();
        
        if (!$allSysTeamsArr[$type]) {
            $pow = pow(2, 31);

            if ($type == 'role') {
                $allSysTeamsArr[$type] = 1 - $pow;
            }

            if ($type == 'domain') {
                $allSysTeamsArr[$type] = 1 - ($pow / 2);
            }
        }

        if ($allSysTeamsArr[$type] >= 0) {
            wp($allSysTeamsArr);
            if ($type == 'role') {
                $allSysTeamsArr[$type] = -2147483647;
            }
            if ($type == 'domain') {
                $allSysTeamsArr[$type] = -1073741823;
            }
        }

        return $allSysTeamsArr[$type];
    }
    
    
    /**
     * Връща id за групата базирано на allSysTeam
     *
     * @param int $roleId
     * @param string $type
     *
     * @return int
     */
    public static function getSysRoleId($roleId, $type = 'role')
    {
        $roleId = (int) $roleId;

        $allSysTeam = self::getAllSysTeamId($type);

        $nRoleId = $allSysTeam + $roleId;

        return $nRoleId;
    }


    /**
     * Връща id за групата базирано на allSysTeam
     *
     * @param int $roleId
     *
     * @return int
     */
    public static function getDomainRoleId($roleId)
    {
        $roleId = (int) $roleId;

        $allSysTeam = self::getAllSysTeamId('domain');

        $nRoleId = $allSysTeam + $roleId;

        return $nRoleId;
    }
    
    
    /**
     * Връща id на запис от модел core_Roles от id-то определено от getSysRoleId()
     *
     * @param int $sysRoleId
     *
     * @return int|NULL
     */
    public static function getRoleIdFromSys($sysRoleId, &$type = null)
    {
        $sysRoleId = (int) $sysRoleId;

        if ($sysRoleId >= 0) {
            
            return;
        }

        $type = self::getTypeFromId($sysRoleId);

        $allSysTeam = self::getAllSysTeamId($type);
        
        $roleId = (int) ($sysRoleId - $allSysTeam);

        return $roleId;
    }
    
    
    /**
     * Връща ключа на опциията за тази стойност
     *
     * @param string|int $userOrRole
     *
     * @return NULL|string
     */
    public static function getOptVal($userOrRole)
    {
        if (strpos($userOrRole, '_')) {
            
            return $userOrRole;
        }
        
        if (!$userOrRole) {
            
            return ;
        }
        
        $inst = cls::get(get_called_class());
        
        // Няма нужда да се подготвят опциите за ролите, когато се търси потребител
        if ($userOrRole >= 0) {
            
            // Опитваме се да определим потребителя с групата му
            $userTeamsArr = self::getUserFromTeams($userOrRole);
            
            if (!empty($userTeamsArr)) {
                reset($userTeamsArr);
                $userGroupId = key($userTeamsArr);
                
                if ($userGroupId) {
                    
                    return $userGroupId;
                }
            }
            
            $inst->params['rolesForAllRoles'] = 'no_one';
        }
        
        $inst->prepareOptions();
        
        foreach ((array) $inst->options as $optVal => $vals) {
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
