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
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @see       core_Users
 */
class type_Users extends type_Keylist
{
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        setIfNot($params['params']['mvc'], 'core_Users');
        setIfNot($params['params']['select'], 'names');
        
        parent::init($params);
        
        setIfNot($this->params['roles'], 'executive,officer,manager,ceo');
        $this->params['roles'] = str_replace("|", ",", $this->params['roles']);
        
        setIfNot($this->params['rolesForTeams'], 'officer,manager,ceo');
        $this->params['rolesForTeams'] = str_replace("|", ",", $this->params['rolesForTeams']);
        
        setIfNot($this->params['rolesForAll'], 'ceo');
        $this->params['rolesForAll'] = str_replace("|", ",", $this->params['rolesForAll']);
    }
    
    
    /**
     * Подготвя опциите според зададените параметри.
     * Ако е посочен суфикс, извеждате се само интерфейсите
     * чието име завършва на този суфикс
     */
    public function prepareOptions($defUser =  NULL)
    {
        $mvc = cls::get($this->params['mvc']);
        
        $mvc->invoke('BeforePrepareKeyOptions', array(&$this->options, $this));
        
        if (isset($this->options)) {
            
            return $this->options;
        }
        
        // Вариант 1: Потребителя няма права да вижда екипите
        // Тогава евентуално можем да покажем само една опция, и тя е с текущия потребител
        if(!haveRole($this->params['rolesForTeams'])) {
            if(haveRole($this->params['roles'])) {
                $key = static::getUserWithFirstTeam(core_Users::getCurrent());
                $this->options[$key] = new stdClass();
                $this->options[$key]->title = core_Users::getCurrent('names');
                $this->options[$key]->keylist = '|' . core_Users::getCurrent() . '|';
            } else {
                $this->options = array();
            }
            
            return;
        } else {
            
            $uQuery = core_Users::getQuery();
            $uQuery->orderBy("#names", 'ASC');

            $defUser = trim($defUser, '|');
            if(is_numeric($defUser) && $defUser > 0) { 
                $defUserCond = " || (#id = {$defUser})";
            }
            
            if ($defUser != 'all' || $defUser != 'all_users') {
                $uQuery->where("(#state != 'rejected'){$defUserCond}");
            }
            
            // Потребителите, които ще покажем, трябва да имат посочените роли
            $roles = core_Roles::getRolesAsKeylist($this->params['roles']);
            $uQuery->likeKeylist('roles', $roles);
            
            // Масива, където ще пълним опциите
            $this->options = array();
            
            if(haveRole($this->params['rolesForAll'])) {
                // Показваме всички екипи
                $teams = core_Roles::getRolesByType('team');
                
                // Добавя в началото опция за избор на всички потребители на системата
                $all = new stdClass();
                $all->title = tr("Всички потребители");
                $all->attr = array('class' => 'all-users', 'style' => 'color:#777;');
                $uQueryCopy = clone($uQuery);
                $allUsers = '';
                
                while($uRec = $uQueryCopy->fetch()) {
                    $allUsers .= $allUsers ? '|' . $uRec->id : $uRec->id;
                }
                $all->keylist = "|{$allUsers}|-1|0";
                $this->options['all_users'] = $all;
            } else {
                // Показваме само екипите на потребителя
                $teams = core_Users::getUserRolesByType(NULL, 'team');
            }
            
            $teams = keylist::toArray($teams);
            
            foreach($teams as $t) {
                $group = new stdClass();
                $tRole = core_Roles::getVerbal($t, 'role');
                $group->title = tr('Екип') . " \"" . $tRole . "\"";
                $group->attr = array('class' => 'team', 'style' => 'background-color:#000;color:#fc0');
                
                $this->options[$t . ' team'] = $group;
                
                $uQueryCopy = clone($uQuery);
                
                $uQueryCopy->likeKeylist('roles', "|{$t}|");
                
                $teamMembers = '';
                
                while($uRec = $uQueryCopy->fetch()) {
                    $key = $t . '_' . $uRec->id;
                    $this->options[$key] = new stdClass();
                    $this->options[$key]->title = $uRec->names;
                    $this->options[$key]->keylist = '|' . $uRec->id . '|';
                    
                    $teamMembers .= $teamMembers ? '|' . $uRec->id : $uRec->id;
                }
                
                if($teamMembers) {
                    // Добавка за да има все пак разлика между един потребител и екип,
                    // в който само той е участник
                    if(strpos($teamMembers, '|') === FALSE) {
                        $teamMembers = "{$teamMembers}|{$teamMembers}";
                    }
                    $this->options[$t . ' team']->keylist = "|{$teamMembers}|";
                } else {
                    unset($this->options[$t . ' team']);
                }
            }
        }
        
        $mvc->invoke('AfterPrepareKeyOptions', array(&$this->options, $this));
        
        return $this->options;
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $this->prepareOptions($value);
        
        if(empty($value)) {
            $value = '|' . core_Users::getCurrent() . '|';
        }

        foreach($this->options as $key => $optObj) {
            if($value == $optObj->keylist || $key == $value) {
                break;
            }
        }

        parent::setFieldWidth($attr);

        return ht::createSelect($name, $this->options, $key, $attr);
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
    function fromVerbal_($value)
    {
        $this->prepareOptions('all');
       
        if (isset($value) && !$this->options[$value]) {
            $this->error = 'Некоректна стойност';
            
            return FALSE;
        }
        
        return $this->options[$value]->keylist;
    }
    
    
    /**
     * Конвертира стойността от вербална към (int) - ключ към core_Interfaces
     */
    function toVerbal_($value)
    {
        $this->prepareOptions();
        
        foreach($this->options as $key => $optObj) {
            if(isset($value) && $value == $optObj->keylist) {
                $exist = TRUE;
                break;
            }
        }
        
        if (!$exist) return NULL;
        
        return self::escape($this->options[$key]->title);
    }
    
    
    /**
     * Връща масив с групите със съответния потребители
     * 
     * @param integer $userId
     * 
     * @return array
     * @see type_User::getUserFromTeams
     */
    static function getUserFromTeams($userId=NULL)
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
     * @param integer $userId
     * 
     * @return string
     */
    static function getUserWithFirstTeam($userId=NULL)
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
}
