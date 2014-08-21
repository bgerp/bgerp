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
class type_User extends type_Key
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
     */
    public function prepareOptions()
    {
        $mvc = cls::get($this->params['mvc']);
        
        $mvc->invoke('BeforePrepareKeyOptions', array(&$this->options, $this));
        
        if (isset($this->options)) {
            
            return;
        }
        
        // Вариант 1: Потребителя няма права да вижда екипите
        // Тогава евентуално можем да покажем само една опция, и тя е с текущия потребител
        if(!haveRole($this->params['rolesForTeams'])) {
            if(haveRole($this->params['roles'])) {
                $user = core_Users::getCurrent();
                $name = core_Users::getCurrent('names', TRUE);
                $this->options = array($user => $name);
            } else {
                $this->options = array();
            }
            
            return;
        } else {
            
            $uQuery = core_Users::getQuery();
            $uQuery->where("#state = 'active'");
            $uQuery->orderBy("#names", 'ASC');
            
            // Потребителите, които ще покажем, трябва да имат посочените роли
            $roles = core_Roles::getRolesAsKeylist($this->params['roles']);
            $uQuery->likeKeylist('roles', $roles);
            
            if(haveRole($this->params['rolesForAll'])) {
                // Показваме всички екипи
                $teams = core_Roles::getRolesByType('team');
            } else {
                // Показваме само екипите на потребителя
                $teams = core_Users::getUserRolesByType(NULL, 'team');
            }
            
            $teams = keylist::toArray($teams);
            
            $this->options = array();
            
            foreach($teams as $t) {
                $group = new stdClass();
                $tRole = core_Roles::getVerbal($t, 'role');
                $group->title = tr('Екип') . " \"" . $tRole . "\"";
                $group->attr = array('class' => 'team');
                $group->group = TRUE;
                $group->keylist = $this->options[$t . ' team'] = $group;
                
                $uQueryCopy = clone($uQuery);
                
                $uQueryCopy->likeKeylist('roles', "|{$t}|");
                
                $teamMembers = '';
                
                $part = $this->params['select'];
                
                while($uRec = $uQueryCopy->fetch()) {
                    $key = $t . '_' . $uRec->id;
                    if(!$this->options[$key]) {
                        $this->options[$key] = new stdClass();
                    }
                    $this->options[$key]->title = $uRec->$part;
                    $this->options[$key]->value = $uRec->id;
                    
                    $teamMembers .= $teamMembers ? '|' . $uRec->id : $uRec->id;
                }
                
                if($teamMembers) {
                    // $this->options[$t. ' team']->keylist = "|{$teamMembers}|";
                } else {
                    unset($this->options[$t . ' team']);
                }
            }
        }
        
        $mvc->invoke('AfterPrepareKeyOptions', array(&$this->options, $this));
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $this->prepareOptions();
        
        if ($this->params['allowEmpty']) {
            
            $this->options = array('' => '&nbsp;') + $this->options;
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
     * Конвертира стойността от вербална към (int) - ключ към core_Interfaces
     */
    function fromVerbal_($value)
    {
        $this->prepareOptions();
        
        return $this->options[$value]->value;
    }
    
    
    /**
     * Конвертира стойността от вербална към (int) - ключ към core_Interfaces
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
                if (!$firstVal) $firstVal = $val;    
            }
        }
        
        return $firstVal;
    }
    
    
    /**
     * Връща масив с групите със съответния потребители
     * 
     * @param integer $userId
     * 
     * @return array
     * @see type_Users::getUserFromTeams
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
}
