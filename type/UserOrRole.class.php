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
    }
    
    
    /**
     * Подготвя опциите според зададените параметри.
     */
    public function prepareOptions()
    {
        parent::prepareOptions();
        
        // Ако има съответната роля за виждане на ролите
        if (haveRole($this->params['rolesForAllRoles'])) {
            
            // Добавяме неизбираемо поле за оли
            $group = new stdClass();
            $group->title = tr('Роли');
            $group->attr = array('class' => 'role');
            $group->group = TRUE;
            $this->options['roles'] = $group;
            
            $allSysTeam = self::getAllSysTeamId();
            
            // Вземаме всички роли
            $rQuery = core_Roles::getQuery();
            while($rec = $rQuery->fetch()) {
                $roleObj = new stdClass();
                $roleObj->title = $rec->role;
                $roleObj->id = $rec->id;
                $roleObj->value = $allSysTeam + $rec->id;
                $this->options['r_' . $rec->id] = $roleObj;
            }
            
            // Ако има права за избор на цялата система, добавяме съответния избор
            if (haveRole($this->params['rolesForAllSysTeam'])) {
                $roleObj = new stdClass();
                $roleObj->title = tr("Цялата система");
                $roleObj->value = $allSysTeam;
                $roleObj->attr = array('clas' => 'all-sys-team');
                $this->options['r_' . 'allSysTeam'] = $roleObj;
            }
        }
    }
    
    
    /**
     * Връща ID-то за allSysTeam
     */
    public static function getAllSysTeamId()
    {
        $allSysTeams = 1-pow(2,31);
        
        return $allSysTeams;
    }
}
