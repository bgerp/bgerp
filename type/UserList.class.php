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
     * 
     */
    protected $keySep = '_';
    
    
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
     * 
     * @return array
     */
    public function prepareSuggestions($ids = NULL)
    {
        // Ако не е зададен параметъра
        if(!isset($this->params['maxOptForOpenGroups'])) {
            $conf = core_Setup::getConfig();
            $maxOpt = $conf->_data['CORE_MAX_OPT_FOR_OPEN_GROUPS'];
            if(!isset($maxOpt)) {
                $maxOpt = CORE_MAX_OPT_FOR_OPEN_GROUPS;
            } 
            setIfNot($this->params['maxOptForOpenGroups'], $maxOpt);
        }

        $mvc = cls::get($this->params['mvc']);
        
        $mvc->invoke('BeforePrepareSuggestions', array(&$this->suggestions, $this));
        
        if (isset($this->suggestions)) {
            
            return $this->suggestions;
        }

        // извличане на потребителите с информация
        $pQuery = crm_Profiles::getQuery();
        $pQuery->show("id");
        while($rec = $pQuery->fetch("#stateInfo IS NOT NULL")) {
            $dayBefore = strstr(dt::addDays(1,$rec->sateDateFrom), " ", TRUE);
            if($dayBefore == strstr(dt::now(), " ", TRUE)){
                $iUsers[$rec->id] = $rec->id;
            }
        }
        $this->info = $iUsers;

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
        $uQueryAll->where("#state != 'rejected' AND #state != 'draft'");
        $uQueryAll->likeKeylist('roles', "{$teamsKeylist}");
        $uQueryAll->likeKeylist('roles', $roles);
        
        // Броя на групите
        $cnt = $uQueryAll->count();
      
        // Ако броя е под максимално допустимите или са избрани всичките
        if ((trim($this->params['autoOpenGroups']) == '*') || ($cnt < $this->params['maxOptForOpenGroups'])) {
            
            // Отваряме всички групи
            $openAllGroups = TRUE;
        }
 
        $userArr = core_Users::getRolesWithUsers();
    
        $rolesArr = type_Keylist::toArray($roles);
        
        foreach($teams as $t) {  
            if(count($ownRoles) && !$ownRoles[$t]) continue;
            $group = new stdClass();
            $tRole = core_Roles::getVerbal($t, 'role');
            $group->title = tr('Екип') . " \"" . $tRole . "\"";
            $group->attr = array('class' => 'team');
            $group->group = TRUE;

            $this->suggestions[$t . ' team'] = $group;
            
            $teamMembers = 0;
            
            foreach((array)$userArr[$t] as $uId) {
                    
                $uRec = $userArr['r'][$uId];
                if ($uRec->state == 'rejected' || $uRec->state == 'draft') continue;
                
                if (!empty($rolesArr)) {
                    if (!type_Keylist::isIn($rolesArr, $uRec->roles)) continue;
                }
                
                $uRec->id = $uId;

                // Ако е сетнат параметъра да са отворени всички или е групата на текущия потребител
                if ($openAllGroups) {
                    
                    // Вдигам флага да се отвори групата
                    $group->autoOpen = TRUE;
                    
                    // Отбелязваме, че поне една група е отворена
                    $haveOpenedGroup=TRUE;
                }
                
                $key = $this->getKey($t, $uId);
                if(!isset($this->suggestions[$key])) {
                    $teamMembers++;
                    $this->suggestions[$key] =  html_entity_decode(core_Users::getVerbal($uRec, 'nick'));
                    if(EF_USSERS_EMAIL_AS_NICK) {
                        $this->suggestions[$key] =  html_entity_decode($this->suggestions[$key]);
                    }
                }

                if($uId == core_Users::getCurrent() && !$ids) {
                    $group->autoOpen = TRUE;
                    $haveOpenedGroup = TRUE;
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
            if ($firstGroup && is_object($this->suggestions[$firstGroup]) && !$ids) {
                
                // Вдигама флаг да се отвори
                $this->suggestions[$firstGroup]->autoOpen = TRUE;
            }
        }
        
        $mvc->invoke('AfterPrepareSuggestions', array(&$this->suggestions, $this));
    }
    
    
    /**
     * @param mixed $value
     * 
     * @see type_Keylist::fromVerbal_()
     * 
     * @return mixed
     */
    function fromVerbal_($value)
    {
        if (is_array($value)) {
            $nValArr = array();
            foreach ($value as $v) {
                
                $userId = $this->getUserIdFromKey($v);
                
                $nValArr[$userId] = $userId;
            }
            
            $value = $nValArr;
        }
        
        return parent::fromVerbal_($value);
    }
    
    
    /**
     * Рендира HTML инпут поле
     * 
     * @param string $name
     * @param string $value
     * @param array|NULL $attr
     * 
     * @see type_Keylist::renderInput_()
     * 
     * @return core_ET
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $this->prepareSuggestions($value);
        
        if ($value) {
            
            $teams = core_Roles::getRolesByType('team');
            
            if(is_array($value)) {
                $value = $this->fromArray($value);
            }
            
            $valuesArr = explode($value{0}, trim($value, $value{0}));
            
            $nValArr = array();
            
            foreach ($valuesArr as $uId) {
                $roles = core_Users::fetchField($uId, 'roles');
                
                $dArr = $this->getDiffArr($teams, $roles);
                $rolesArr = $dArr['same'];
                
                // Потребителят да е избран само в първата група, която участва
                if ($rolesArr) {
                    $rId = key($rolesArr);
                    $key = $this->getKey($rId, $uId);
                    $nValArr[$key] = $key;
                }
            }
            
            $value = $nValArr;
        }
    
        $res = parent::renderInput_($name, $value, $attr);
        
        return $res;
    }
    
    
    /**
     * Преобразува от масив с индекси ключовете към keylist
     * 
     * @param array $value
     * 
     * @see type_Keylist::fromArray()
     * 
     * @return string
     */
    static function fromArray($value)
    {
        $res = '';
        
        if (is_array($value) && !empty($value)) {

            // Сортираме ключовете на масива, за да има
            // стринга винаги нормализиран вид - от по-малките към по-големите
            ksort($value);

            foreach ($value as $id => $val)
            {
                if (empty($id) && empty($val)) continue;
                
                $res .= "|" . $id;
            }
            
            $res = $res . "|";
        }
        
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
        return $this->fromArray($retTypeArr);
    }
    
    
    /**
     * Връща ролите зададени в полето за избор
     */
    public function getRoles()
    {
        
    	return $this->params['roles'];
    }
    
    
    /**
     * Връща ключа от ид на ролята и потребителя
     * 
     * @param integer $roleId
     * @param integer $uId
     * 
     * @return string
     */
    protected function getKey($roleId, $uId)
    {
        $key = $roleId . $this->keySep . $uId;
        
        return $key;
    }
    
    
    /**
     * Връща id на потребителя, от подадения стринг
     * 
     * @param string $key
     * 
     * @return integer
     */
    protected function getUserIdFromKey($key)
    {
        list($roleId, $userId) = explode($this->keySep, $key);
        
        if (!isset($userId)) {
            $userId = $roleId;
        }
        
        return $userId;
    }
}
