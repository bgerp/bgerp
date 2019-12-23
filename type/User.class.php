<?php


/**
 * Keylist с избрани потребители. Могат да се избират или самостоятелни потребители или цели екипи
 *
 * Има следните атрибути:
 * - roles:         Избират се само потребители с някоя от тази роля
 * - rolesForTeams: Поне една от тях е необходима за да се покажат всички потребители от екипите, на които той е член
 * - rolesForAll:  Поне една от ролите е необходима за да се покажат всички екипи и потребители
 * Когато се записват като стринг в атрибута, ролите могат да бъдат разделени с вертикална черта
 *
 *
 * @category  ef
 * @package   type
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see       core_Users
 */
class type_User extends type_Key
{
    /**
     * Инициализиране на обекта
     */
    public function init($params = array())
    {
        setIfNot($params['params']['mvc'], 'core_Users');
        setIfNot($params['params']['select'], 'nick');
        
        parent::init($params);
        
        setIfNot($this->params['roles'], 'executive,officer,manager,ceo');
        $this->params['roles'] = str_replace('|', ',', $this->params['roles']);
        
        setIfNot($this->params['rolesForTeams'], 'officer,manager,ceo');
        $this->params['rolesForTeams'] = str_replace('|', ',', $this->params['rolesForTeams']);
        
        setIfNot($this->params['rolesForAll'], 'ceo');
        $this->params['rolesForAll'] = str_replace('|', ',', $this->params['rolesForAll']);
        
        setIfNot($this->params['cuFirst'], 'yes');
    }
    
    
    /**
     * Подготвя опциите според зададените параметри.
     */
    public function prepareOptions($value = null)
    {
        $mvc = cls::get($this->params['mvc']);
        
        if (!$this->options) {
            $this->options = array();
        }
        
        if (empty($this->options)) {
            $part = $this->params['select'];
            expect($part);
            
            // Към екипните роли добавяме ролите за всички потребители
            if ($this->params['rolesForAll'] && $this->params['rolesForAll'] != 'no_one') {
                $rolesForAll = arr::make($this->params['rolesForAll'], true);
                $rolesForTeams = arr::make($this->params['rolesForTeams'], true);
                
                $rolesForTeams += $rolesForAll;
                
                $this->params['rolesForTeams'] = implode(',', $rolesForTeams);
            }
            
            $cu = core_Users::getCurrent();
            
            // Вариант 1: Потребителя няма права да вижда екипите
            // Тогава евентуално можем да покажем само една опция, и тя е с текущия потребител
            if (!haveRole($this->params['rolesForTeams'])) {
                if (haveRole($this->params['roles'])) {
                    $userIdKey = self::getUserFromTeams($cu);
                    
                    $userIdKey = reset($userIdKey);
                    
                    if (!$this->options[$userIdKey]) {
                        $this->options[$userIdKey] = new stdClass();
                    }
                    $this->options[$userIdKey]->title = core_Users::getCurrent($part);
                    $this->options[$userIdKey]->value = $cu;
                }
            } else {
                $uQuery = core_Users::getQuery();
                if ($value > 0) {
                    if ($value && (strpos($value, '_') !== false)) {
                        list(, $userId) = explode('_', $value);
                        
                        if ($userId) {
                            $value = $userId;
                        }
                    }
                    
                    $uQuery->where("#state = 'active' OR #state = 'blocked' OR #state = 'closed' OR #id = {$value}");
                } else {
                    $uQuery->where("#state = 'active' OR #state = 'blocked' OR #state = 'closed'");
                }
                $uQuery->orderBy('nick', 'ASC');
                
                // Потребителите, които ще покажем, трябва да имат посочените роли
                $roles = core_Roles::getRolesAsKeylist($this->params['roles']);
                $uQuery->likeKeylist('roles', $roles);
                
                $removeClosedGroups = true;
                if ($this->params['showClosedGroups']) {
                    $removeClosedGroups = false;
                }
                
                if (haveRole($this->params['rolesForAll'])) {
                    
                    // Показваме всички екипи
                    $teams = core_Roles::getRolesByType('team', 'keylist', $removeClosedGroups);
                } else {
                    // Показваме само екипите на потребителя
                    $teams = core_Users::getUserRolesByType(null, 'team', 'keylist', $removeClosedGroups);
                }
                
                $teams = keylist::toArray($teams);
                
                $cuRecArr = array();
                
                if (haveRole($this->params['rolesForTeams']) && $this->params['additionalRoles']) {
                    foreach (arr::make($this->params['additionalRoles']) as $rName) {
                        $rId = core_Roles::fetchByName($rName);
                        if (!$rId) {
                            continue;
                        }
                        
                        $teams[$rId] = $rId;
                    }
                }
                
                foreach ($teams as $t) {
                    $group = new stdClass();
                    $tRole = core_Roles::getVerbal($t, 'role');
                    $group->title = tr('Екип') . ' "' . $tRole . '"';
                    $group->attr = array('class' => 'team');
                    $group->group = true;
                    $this->options[$t . ' team'] = $group;
                    
                    $uQueryCopy = clone($uQuery);
                    
                    $uQueryCopy->likeKeylist('roles', "|{$t}|");
                    
                    $teamMembers = '';
                    
                    while ($uRec = $uQueryCopy->fetch()) {
                        $key = $t . '_' . $uRec->id;
                        if (!$this->options[$key]) {
                            $this->options[$key] = new stdClass();
                        }
                        
                        if ($part && $this->params['useSelectAsTitle']) {
                            $this->options[$key]->title = $uRec->$part;
                        } else {
                            $this->options[$key]->title = type_Nick::normalize($uRec->nick) . ' (' . $uRec->names . ')';
                        }
                        
                        $this->options[$key]->value = $uRec->id;
                        
                        $teamMembers .= $teamMembers ? '|' . $uRec->id : $uRec->id;
                        
                        if ($this->params['cuFirst'] == 'yes' && empty($cuRecArr)) {
                            if ($this->options[$key] && ($uRec->id == $cu)) {
                                $cuRecArr[$key] = $this->options[$key];
                            }
                        }
                    }
                    
                    if ($teamMembers) {
                        $this->options[$t. ' team']->keylist = "|{$teamMembers}|";
                    } else {
                        unset($this->options[$t . ' team']);
                    }
                }
                
                if (!empty($cuRecArr)) {
                    $this->options = $cuRecArr + $this->options;
                }
            }
        }
        
        $this->options = parent::prepareOptions();
        
        if (isset($this->params['filter'])) {
            call_user_func($this->params['filter'], $this);
        }
        
        return $this->options;
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
        $key = preg_replace('/[^0-9\_]/i', '', $key);
        
        return $key;
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    public function renderInput($name, $value = '', &$attr = array())
    {
        if (is_null($value) && !$this->params['allowEmpty']) {
            $value = core_Users::getCurrent();
        }
        
        if (!empty($value)) {
            $value = self::getUserFromTeams($value);
            
            $value = reset($value);
            
            $this->params['reserve'] = $value;
        }
        
        return parent::renderInput($name, $value, $attr);
    }
    
    
    /**
     *
     *
     * @param string $value
     *
     * @return object
     */
    protected function fetchVal(&$value)
    {
        if ($value && (strpos($value, '_') !== false)) {
            list(, $userId) = explode('_', $value);
            
            if ($userId) {
                $value = $userId;
            }
        }
        
        return parent::fetchVal($value);
    }
    
    
    /**
     * Проверява дали подадения ключ го има в опциите и ако го няма връща първия възможен
     *
     * @param string $key - Ключа от опциите
     *
     * @return string - Стринг, с възможните стойности
     */
    public function fitInDomain($key)
    {
        $firstVal = null;
        
        // Подготвяме опциите
        $this->prepareOptions();
        
        // Обхождаме опциите
        foreach ($this->options as $options) {
            
            // Вземаме стойността
            $val = $options->value;
            
            if ($val) {
                
                // Ако стойността е равна на търсената връщаме я
                if ($val == $key) {
                    
                    return $val;
                }
                
                // Ако има стойност и няма първа стойност сетваме я
                if (!$firstVal) {
                    $firstVal = $val;
                }
            }
        }
        
        return $firstVal;
    }
    
    
    /**
     * Връща масив с групите със съответния потребители
     *
     * @param int|NULL $userId
     *
     * @return array
     *
     * @see type_Users::getUserFromTeams
     */
    public static function getUserFromTeams($userId = null)
    {
        $arr = array();
        
        // Ако не е подаден потребител
        if (!$userId) {
            
            // Вземаме текущия
            $userId = core_Users::getCurrent();
        }
        
        if (!strpos($userId, '_')) {
            // Всички екипи, в които участва
            $teams = core_Users::getUserRolesByType($userId, 'team');
            $teams = keylist::toArray($teams);
            
            $me = cls::get(get_called_class());
            if ($me->params['additionalRoles'] && empty($teams)) {
                foreach (arr::make($me->params['additionalRoles']) as $rName) {
                    $rId = core_Roles::fetchByName($rName);
                    if (!$rId) {
                        continue;
                    }
                    
                    $teams[$rId] = $rId;
                }
            }
            
            // Обхождаме екипите
            foreach ($teams as $team) {
                
                // Група с потребителя
                $user = $team . '_' . $userId;
                
                // Добавяме в масива
                $arr[$user] = $user;
            }
        }
        
        if (!$teams && !$arr && $userId) {
            $arr[$userId] = $userId;
        }
        
        return $arr;
    }
    
    
    /**
     * Връща възможните стойности за ключа
     *
     * @param int $id
     *
     * @return array
     */
    public function getAllowedKeyVal($id)
    {
        return self::getUserFromTeams($id);
    }
}
