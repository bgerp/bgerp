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
class type_UserOrRole extends type_Int
{
    
    
    /**
     * Клас за <td> елемент, който показва данни от този тип
     */
    var $tdClass = '';
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        parent::init($params);
        
        setIfNot($this->params['userSelect'], 'names');
        
        setIfNot($this->params['userRoles'], 'user');
        $this->params['userRoles'] = str_replace("|", ",", $this->params['userRoles']);
        
        setIfNot($this->params['userRolesForTeams'], 'ceo, admin');
        $this->params['userRolesForTeams'] = str_replace("|", ",", $this->params['userRolesForTeams']);
        
        setIfNot($this->params['userRolesForAll'], 'ceo, admin');
        $this->params['userRolesForAll'] = str_replace("|", ",", $this->params['userRolesForAll']);
        
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
        // Параметри за опциитео на потребителите
        $userParamsArr['params']['mvc'] = 'core_Users';
        $userParamsArr['params']['select'] = $this->params['userSelect'];
        $userParamsArr['params']['userRoles'] = $this->params['roles'];
        $userParamsArr['params']['rolesForTeams'] = $this->params['userRolesForTeams'];
        $userParamsArr['params']['rolesForAll'] = $this->params['userRolesForAll'];
        
        // Вземаме всички потребители
        $User = cls::get('type_User', $userParamsArr);
        $User->prepareOptions();
        $this->options = $User->options;
        
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
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $this->prepareOptions($value);
    
        if ($this->params['allowEmpty']) {
            
            $this->options = array(' ' => ' ') + $this->options;
        } elseif (empty($value)) {
            $value = core_Users::getCurrent();
        }
        
        foreach($this->options as $key => $optObj) {
            if($value == $optObj->value) {
                break;
            }
        }

        parent::setFieldWidth($attr);
        
        return ht::createSelect($name, $this->options, $key, $attr);
    }
    
    
    /**
     * Конвертира стойността от вербално представяне към int
     */
    function fromVerbal_($value)
    {
        $this->prepareOptions();
        
        return $this->options[$value]->value;
    }
    
    
    /**
     * Конвертира стойността към вербално представяне
     */
    function toVerbal_($value)
    {
        $this->prepareOptions();
        
        foreach($this->options as $key => $optObj) {
            if(isset($value) && $value == $optObj->value) {
                $exist = TRUE;
                break;
            }
        }
        
        if (!$exist) return NULL;
        
        return self::escape($this->options[$key]->title);
    }
    
    
    /**
     * Проверява дали подадения ключ го има в опциите и ако го няма връща първия възможен
     * 
     * @param string $key - Ключа от опциите
     * 
     * @return string - Стринг, с възможните стойности
     */
    function fitInDomain($key)
    {
        // Подготвяме опциите
        $this->prepareOptions();
        
        // Обхождаме опциите
        foreach ($this->options as $options) {
            
            // Вземаме стойността
            $val = $options->value;
            
            if ($val) {
                
                // Ако стойността е равна на търсената връщаме я
                if ($val == $key) return $val;
                
                // Ако има стойност и няма първа стойност сетваме я
                $firstVal = ($firstVal) ? $firstVal : $val;
            }
        }
        
        return $firstVal;
    }
    
    
    /**
     * Връща ID-то за allSysTeam
     */
    static function getAllSysTeamId()
    {
        $allSysTeams = 1-pow(2,31);
        
        return $allSysTeams;
    }
}
