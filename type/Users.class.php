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
class type_Users extends type_Keylist
{
    /**
     * Инициализиране на обекта
     */
    public function init($params = array())
    {
        setIfNot($params['params']['mvc'], 'core_Users');
        setIfNot($params['params']['select'], 'names');
        
        parent::init($params);
        
        setIfNot($this->params['roles'], 'executive,officer,manager,ceo');
        $this->params['roles'] = str_replace('|', ',', $this->params['roles']);
        
        setIfNot($this->params['rolesForTeams'], 'officer,manager,ceo');
        $this->params['rolesForTeams'] = str_replace('|', ',', $this->params['rolesForTeams']);
        
        setIfNot($this->params['rolesForAll'], 'ceo');
        $this->params['rolesForAll'] = str_replace('|', ',', $this->params['rolesForAll']);




        // Кой може да избира системяни потребител
        setIfNot($this->params['rolesForSystem'], 'no_one');
        $this->params['rolesForSystem'] = str_replace('|', ',', $this->params['rolesForSystem']);

        // Кой може да избира анонимния потребител
        setIfNot($this->params['rolesForAnonym'], 'no_one');
        $this->params['rolesForAnonym'] = str_replace('|', ',', $this->params['rolesForAnonym']);

        // Кой може да избира "Всички лица" - като "Всички потребители" но без анонимните или системните - за регистрирани потребители
        setIfNot($this->params['rolesForAllUsers'], 'no_one');
        $this->params['rolesForAllUsers'] = str_replace('|', ',', $this->params['rolesForAllUsers']);

        // Кой може да избира "Всички лица" - като "Всички потребители" но без системните - за регистрирани потребители + анонимния
        setIfNot($this->params['rolesForAllHuman'], 'no_one');
        $this->params['rolesForAllHuman'] = str_replace('|', ',', $this->params['rolesForAllHuman']);

        // Кой може да избира "Всички лица" - като "Всички потребители" но без системните - за регистрирани потребители + анонимния
        setIfNot($this->params['rolesForAllTeams'], 'no_one');
        $this->params['rolesForAllTeams'] = str_replace('|', ',', $this->params['rolesForAllTeams']);

        setIfNot($this->params['cuFirst'], 'yes');
    }
    
    
    /**
     * Подготвя опциите според зададените параметри.
     * Ако е посочен суфикс, извеждате се само интерфейсите
     * чието име завършва на този суфикс
     */
    public function prepareOptions($value = null)
    {
        core_Debug::log('Start user options');
        
        $mvc = cls::get($this->params['mvc']);
        
        if (!isset($this->options)) {
            
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
                    $key = static::getUserWithFirstTeam($cu);
                    $this->options[$key] = new stdClass();
                    $this->options[$key]->title = core_Users::getCurrent('names') . ' (' . type_Nick::normalize(core_Users::getCurrent('nick')) . ')';
                    $this->options[$key]->keylist = '|' . $cu . '|';
                } else {
                    $this->options = array();
                }
                
                return;
            }
            
            $uQuery = core_Users::getQuery();
            $uQuery->orderBy('nick', 'ASC');
            
            // Потребителите, които ще покажем, трябва да имат посочените роли
            $roles = core_Roles::getRolesAsKeylist($this->params['roles']);
            $uQuery->likeKeylist('roles', $roles);
            
            // Масива, където ще пълним опциите
            $this->options = array();
            
            $removeClosedGroups = true;
            if ($this->params['showClosedGroups']) {
                $removeClosedGroups = false;
            }

            if (haveRole($this->params['rolesForAll']) || haveRole($this->params['rolesForAllUsers']) || haveRole($this->params['rolesForAllHuman']) || haveRole($this->params['rolesForAllTeams'])) {
                // Показваме всички екипи
                $teams = core_Roles::getRolesByType('team', 'keylist', $removeClosedGroups);

                $uQueryCopy = clone($uQuery);
                $allUsers = '';
                while ($uRec = $uQueryCopy->fetchAndCache()) {
                    $allUsers .= $allUsers ? '|' . $uRec->id : $uRec->id;
                }

                // Избор на всички потребители
                if (haveRole($this->params['rolesForAll'])) {
                    // Добавя в началото опция за избор на всички потребители на системата
                    $all = new stdClass();
                    $all->title = tr('Всички потребители');
                    $all->attr = array('class' => 'all-users', 'style' => 'color:#777;');

                    $all->keylist = keylist::normalize("|{$allUsers}|-1|0|");
                    $this->options['all_users'] = $all;
                }

                // Избор на всички хора
                if (haveRole($this->params['rolesForAllUsers'])) {
                    // Добавя в началото опция за избор на всички потребители на системата
                    $all = new stdClass();
                    $all->title = tr('Всички лица');
                    $all->attr = array('class' => 'all-reg-users', 'style' => 'color:#777;');

                    $all->keylist = keylist::normalize("|{$allUsers}|");
                    $this->options['all_reg_users'] = $all;
                }

                // Избор на всички хора
                if (haveRole($this->params['rolesForAllHuman'])) {
                    // Добавя в началото опция за избор на всички потребители на системата
                    $all = new stdClass();
                    $all->title = tr('Всички хора');
                    $all->attr = array('class' => 'all-human', 'style' => 'color:#777;');

                    $all->keylist = keylist::normalize("|{$allUsers}|0|");
                    $this->options['all_human'] = $all;
                }

                // Избор на всички хора
                if (haveRole($this->params['rolesForAllTeams'])) {
                    $uQueryPU = core_Users::getQuery();
                    $uQueryPU->orderBy('nick', 'ASC');

                    // Потребителите, които ще покажем, трябва да имат посочените роли
                    $roles = core_Roles::getRolesAsKeylist('powerUser');
                    $uQueryPU->likeKeylist('roles', $roles);

                    $allPowerUsers = '';
                    while ($uRec = $uQueryPU->fetchAndCache()) {
                        $allPowerUsers .= $allPowerUsers ? '|' . $uRec->id : $uRec->id;
                    }

                    // Добавя в началото опция за избор на всички потребители на системата
                    $all = new stdClass();
                    $all->title = tr('Всички екипи');
                    $all->attr = array('class' => 'all-teams', 'style' => 'color:#777;');

                    $all->keylist = keylist::normalize("|{$allPowerUsers}|");
                    $this->options['all_teams'] = $all;
                }
            } else {
                // Показваме само екипите на потребителя
                $teams = core_Users::getUserRolesByType(null, 'team', 'keylist', $removeClosedGroups);
            }

            // Избор само на системния потребител
            if (haveRole($this->params['rolesForSystem'])) {

                // Добавя в началото опция за избор на всички потребители на системата
                $system = new stdClass();
                $system->title = tr(core_Users::fetchField(-1, 'nick'));
                $system->attr = array('class' => 'system-user', 'style' => 'color:#777;');

                $system->keylist = "|-1|";
                $this->options['system_user'] = $system;
            }

            // Избор на анонимния потребител
            if (haveRole($this->params['rolesForAnonym'])) {

                // Добавя в началото опция за избор на всички потребители на системата
                $anonym = new stdClass();
                $anonym->title = tr(core_Users::fetchField(0, 'nick'));
                $anonym->attr = array('class' => 'anonym-user', 'style' => 'color:#777;');

                $anonym->keylist = "|0|";
                $this->options['anonym_user'] = $anonym;
            }

            $teams = keylist::toArray($teams);
            
            $rolesArr = type_Keylist::toArray($roles);
            
            $userArr = core_Users::getRolesWithUsers();
            
            $cuRecArr = array();
            
            foreach ($teams as $t) {
                $group = new stdClass();
                $tRole = core_Roles::fetchById($t);
                $group->title = tr('Екип') . ' "' . $tRole . '"';
                $group->attr = array('class' => 'team', 'style' => 'background-color:#000;color:#fc0');
                
                $this->options[$t . ' team'] = $group;
                
                $teamMembers = '';
                
                $haveTeamMembers = false;
                
                foreach ((array) $userArr[$t] as $uId) {
                    $uRec = $userArr['r'][$uId];
                    $uRec->id = $uId;
                    
                    if (!empty($rolesArr)) {
                        if (!type_Keylist::isIn($rolesArr, $uRec->roles)) {
                            continue;
                        }
                    }
                    
                    if ($uRec->state != 'rejected') {
                        $key = $t . '_' . $uId;
                        $this->options[$key] = new stdClass();
                        $this->options[$key]->title = $uRec->nick . ' (' . $uRec->names . ')';
                        $this->options[$key]->keylist = '|' . $uId . '|';
                        $haveTeamMembers = true;
                    } else {
                        $rejected .= $rejected ? '|' . $uId : $uId;
                    }
                    
                    $teamMembers .= $teamMembers ? '|' . $uId : $uId;
                    
                    if ($this->params['cuFirst'] == 'yes' && empty($cuRecArr)) {
                        if ($this->options[$key] && ($uId == $cu)) {
                            $cuRecArr[$key] = $this->options[$key];
                        }
                    }
                }
                
                if ($haveTeamMembers) {
                    // Добавка за да има все пак разлика между един потребител и екип,
                    // в който само той е участник
                    if (strpos($teamMembers, '|') === false) {
                        $teamMembers = "{$teamMembers}|{$teamMembers}";
                    }
                    $this->options[$t . ' team']->keylist = keylist::normalize("|{$teamMembers}|");
                } else {
                    unset($this->options[$t . ' team']);
                }
            }
            
            if (!empty($cuRecArr)) {
                $this->options = $cuRecArr + $this->options;
            }

            // Добавка за оттеглените потребители
            if ($rejected) {
                $key = 'rejected';
                $this->options[$key] = new stdClass();
                $this->options[$key]->title = tr('Оттеглени потребители');
                $this->options[$key]->keylist = '|' . $rejected . '|';
                $this->options[$key]->attr = array('class' => 'team');
            }
        }
        
        if (isset($this->params['filter'])) {
            call_user_func($this->params['filter'], $this);
        }
        
        core_Debug::log('Stop user options');
        
        return $this->options;
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        $this->prepareOptions();
        
        if (empty($value)) {
            $value = '|' . core_Users::getCurrent() . '|';
        }
        
        $haveMatch = false;
        
        foreach ($this->options as $key => $optObj) {
            
            if ($value == $optObj->keylist || $key == $value) {
                $haveMatch = true;
                
                break;
            }
        }
        
        if (!$haveMatch && $value) {
            $key = $this->getClosestKey($value);
            if (isset($key)) {
                $haveMatch = true;
            }
        }
        
        if (!$haveMatch) {
            $key = null;
        }
        
        parent::setFieldWidth($attr);
        
        return ht::createSelect($name, $this->options, $key, $attr);
    }
    
    
    /**
     * Връща най-близката възможна опция за $value от $this->options
     * 
     * @param string $value
     * 
     * @return NULL|string
     */
    protected function getClosestKey($value)
    {
        $key = null;
        
        $bestMatchArr = array();
        $vArr = $this->toArray($value);
        if (countR($vArr) > 1) {
            $isAll = (stripos($value, '-1') === false) ? false : true;
            foreach ($this->options as $key => $optObj) {
                if ($isAll) {
                    if ((stripos($optObj->keylist, '-1') !== false) || (stripos($key, '-1') !== false)) {
                        $bestMatchArr[$key] = 1;
                    }
                } else {
                    $kArr = $this->toArray($optObj->keylist);
                    
                    $bestMatchArr[$key] = countR(array_diff($kArr, $vArr)) + countR(array_diff($vArr, $kArr));
                }
            }
            
            if (!empty($bestMatchArr)) {
                asort($bestMatchArr);
                $key = key($bestMatchArr);
                $haveMatch = true;
            }
        }
        
        return $key;
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
        // Подготвяме опциите
        $this->prepareOptions();
        
        // Ако подадения тип не е в опциите
        if (!$typeObj = $this->options[$key]) {
            
            // Вземаме първия от масива
            $typeObj = reset($this->options);
        }
        
        // Връщаме ключа
        return $typeObj->keylist;
    }
    
    
    /**
     * Конвертира стойността от вербална към (int) - ключ към core_Interfaces
     */
    public function fromVerbal_($value)
    {
        $this->prepareOptions();
        
        if (isset($value) && !$this->options[$value]) {
            if (strpos($value, '_')) {
                list($gr, $value) = explode('_', $value);
            }
            if (is_numeric($value)) {
                foreach ($this->options as $key => $opt) {
                    if (strpos($key, '_')) {
                        list($gr, $usr) = explode('_', $key);
                        if ($usr == $value) {
                            
                            return $opt->keylist;
                        }
                    }
                }
            }
            
            $this->error = 'Некоректна стойност';
            
            return false;
        }
        
        return $this->options[$value]->keylist;
    }
    
    
    /**
     * Конвертира стойността от вербална към (int) - ключ към core_Interfaces
     */
    public function toVerbal_($value)
    {
        $this->prepareOptions();
        
        foreach ($this->options as $key => $optObj) {
            if (isset($value) && $value == $optObj->keylist) {
                $exist = true;
                break;
            }
        }
        
        if (!$exist) {
            
            return;
        }
        
        return self::escape($this->options[$key]->title);
    }
    
    
    /**
     * Връща масив с групите със съответния потребители
     *
     * @param int $userId
     *
     * @return array
     *
     * @see type_User::getUserFromTeams
     */
    public static function getUserFromTeams($userId = null)
    {
        $arr = array();
        
        // Ако не е подаден потребител
        if (!$userId) {
            
            // Вземаме текущия
            $userId = core_Users::getCurrent();
        }
        
        // Всички екипи, в които участва
        $teams = core_Users::getUserRolesByType($userId, 'team');
        $teams = keylist::toArray($teams);
        
        // Обхождаме екипите
        foreach ($teams as $team) {
            
            // Група с потребителя
            $user = $team . '_' . $userId;
            
            // Добавяме в масива
            $arr[$user] = $user;
        }
        
        return $arr;
    }
    
    
    /**
     * Връща стринг с първия екип и потребителя в който участва потребителя
     *
     * @param int $userId
     *
     * @return string
     */
    public static function getUserWithFirstTeam($userId = null)
    {
        // Ако не е подаден потребител
        if (!$userId) {
            
            // Вземаме текущия
            $userId = core_Users::getCurrent();
        }
        
        // Масив с всички екипи, в които участва потребителя
        $userTeamsArr = static::getUserFromTeams($userId);
        
        reset($userTeamsArr);
        
        $firstTeamUser = key($userTeamsArr);
        
        return $firstTeamUser;
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

        return array();
    }
}
