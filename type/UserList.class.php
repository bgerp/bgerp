<?php



/**
 * Keylist с избрани потребители
 *
 * Има следните атрибути:
 * - roles:         Избират се само потребители с някоя от посочените роли
 * - rolesForAll:   Поне една от ролите е необходима за да се покажат всички екипи и потребители
 *
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
class type_UserList extends type_Keylist
{
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        setIfNot($params['params']['mvc'], 'core_Users');
        setIfNot($params['params']['select'], 'nick');
        
        parent::init($params);
        
        setIfNot($this->params['roles'], 'executive,officer,manager,ceo');
        $this->params['roles'] = str_replace("|", ",", $this->params['roles']);
       
        setIfNot($this->params['rolesForAll'], 'user');
        $this->params['rolesForAll'] = str_replace("|", ",", $this->params['rolesForAll']);
    }
    
    
    /**
     * Подготвя опциите според зададените параметри.
     * Ако е посочен суфикс, извеждате се само интерфейсите
     * чието име завършва на този суфикс
     */
    private function prepareSuggestions($defUser =  NULL)
    {
        $mvc = cls::get($this->params['mvc']);
        
        $mvc->invoke('BeforePrepareSuggestions', array(&$this->suggestions, $this));
        
        if (isset($this->suggestions)) {
            
            return;
        }
        
        // Ако може да вижда всички екипи - показват се. Иначе вижда само своя екип
        if(!haveRole($this->params['rolesForAll'])) {
            $ownRoles = core_Users::getCurrent('roles');
            $ownRoles = self::toArray($ownRoles); 
        }
        
        $teams = core_Roles::getRolesByType('team');
        $teams = self::toArray($teams);

        $roles = core_Roles::getRolesAsKeylist($this->params['roles']);
        
        // id на текущия потребител
        $currUserId = core_Users::getCurrent();
        
        // Всички екипи в keylist
        $teamsKeylist = type_Keylist::fromArray($teams);
        
        // Заявка за да вземем всички запсии
        $uQueryAll = core_Users::getQuery();
        $uQueryAll->where("#state != 'rejected'");
        $uQueryAll->likeKeylist('roles', "{$teamsKeylist}");
        $uQueryAll->likeKeylist('roles', $roles);
        
        // Броя на групите
        $cnt = $uQueryAll->count();
        
        // Ако броя е под максимално допустимите или са избрани всичките
        if ((trim($this->params['autoOpenGroups']) == '*') || ($cnt < $this->params['maxOptForOpenGroups'])) {
            
            // Отваряме всички групи
            $openAllGroups = TRUE;
        }
        
        foreach($teams as $t) {  
            if(count($ownRoles) && !$ownRoles[$t]) continue;
            $group = new stdClass();
            $tRole = core_Roles::getVerbal($t, 'role');
            $group->title = tr('Екип') . " \"" . $tRole . "\"";
            $group->attr = array('class' => 'team');
            $group->group = TRUE;

            $this->suggestions[$t . ' team'] = $group;
            
            $uQuery = core_Users::getQuery();
            $uQuery->where("#state != 'rejected'");
            $uQuery->orderBy('nick');
            
            $uQuery->likeKeylist('roles', "|{$t}|");
            
            $uQuery->likeKeylist('roles', $roles);

            $teamMembers = 0;
            
            while($uRec = $uQuery->fetch()) {
                
                // Ако е сетнат параметъра да са отворени всички или е групата на текущия потребител
                if ($openAllGroups || ($uRec->id == $currUserId)) {
                    
                    // Вдигам флага да се отвори групата
                    $group->autoOpen = TRUE;
                    
                    // Отбелязваме, че поне една група е отворена
                    $haveOpenedGroup=TRUE;
                }
                
                $key = $uRec->id;
                if(!isset($this->suggestions[$key])) {
                    $teamMembers++;
                    $this->suggestions[$key] = core_Users::getVerbal($uRec, 'nick');
                }
            }

            if(!$teamMembers) {
                unset($this->suggestions[$t . ' team']);
            }
        }

        if(!$this->suggestions) {
            $group = new stdClass();
            $group->title = tr("Липсват потребители за избор");
            $group->attr = array('class' => 'team');
            $group->group = TRUE;
            $this->suggestions[] = $group; 
        }
        
        // Ако не е отворена нито една група
        if (!$haveOpenedGroup) {
            
            // Вземаме първата група
            $firstGroup = key($this->suggestions);
            
            // Ако е обект
            if ($firstGroup && is_object($this->suggestions[$firstGroup])) {
                
                // Вдигама флаг да се отвори
                $this->suggestions[$firstGroup]->autoOpen = TRUE;
            }
        }
        
        $mvc->invoke('AfterPrepareSuggestions', array(&$this->suggestions, $this));
     }
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $this->prepareSuggestions($value);

        $res = parent::renderInput_($name, $value, $attr);
        
        return $res;
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
        $this->prepareSuggestions();
        
        $suggestions = $this->suggestions;
        
        $retTypeArr = array();
        
        // Ако е зададен всички потребители
        if ($key == 'all_users') {
            
            // Обхождаме масива с предположенията
            foreach ($suggestions as $keySugg => $suggestion) {
                
                // Ако не е група
                if (!$suggestion->group) {
                    
                    // Добавяме в масива
                    $retTypeArr[$keySugg] = $keySugg;
                }
            }
        } else {
            
            // Масив с типовете
            $typeArr = type_Keylist::toArray($key);
            
            // Обхождаме типовете
            foreach ($typeArr as $t) {
                
                // Ако има предложение с този тип
                if ($suggestions[$t]) {
                    
                    // Добавяме масива
                    $retTypeArr[$t] = $t;
                }
            }    
        }
        
        // Връщаме keylist
        return type_Keylist::fromArray($retTypeArr);
    }
    
    
    /**
     * Връща ролите зададени в полето за избор
     */
    public function getRoles()
    {
    	return $this->params['roles'];
    }
}