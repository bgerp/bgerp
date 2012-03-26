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
 * @category  all
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
    function init($params)
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
    private function prepareOptions()
    {
        if (isset($this->options)) {
            return;
        }
        
        // Вариант 1: Потребителя няма права да вижда екипите
        // Тогава евентуално можем да покажем само една опция, и тя е с текущия потребител
        if(!haveRole($this->params['rolesForTeams'])) {
            if(haveRole($this->params['roles'])) {
                $user = '|' . core_Users::getCurrent() . '|';
                $name = core_Users::getCurrent('names');
                $this->options = array($user => $name);
            } else {
                $this->options = array();
            }
            
            return;
        } else {
            
            $uQuery = core_Users::getQuery();
            $uQuery->where("#state = 'active'");
            
            // Потребителите, които ще покажем, трябва да имат посочените роли
            $roles = core_Roles::keylistFromVerbal($this->params['roles']);
            $uQuery->likeKeylist('roles', $roles);
            
            // Масива, където ще пълним опциите
            $this->options = array();
            
            if(haveRole($this->params['rolesForAll'])) {
                // Показваме всички екипи
                $teams = core_Roles::getRolesByType('team');
                
                // Добавя в началото опция за избор на всички потребители на системата
                $all = new stdClass();
                $all->title = "Всички";
                $all->attr = array('style' => 'background-color:#ffc;');
                $uQueryCopy = clone($uQuery);
                $allUsers = '';
                
                while($uRec = $uQueryCopy->fetch()) {
                    $allUsers .= $allUsers ? '|' . $uRec->id : $uRec->id;
                }
                $all->keylist = "|{$allUsers}|-1|";
                $this->options['all_users'] = $all;
            } else {
                // Показваме само екипите на потребителя
                $teams = core_Users::getUserRolesByType(NULL, 'team');
            }
            
            $teams = type_Keylist::toArray($teams);
            
            foreach($teams as $t) {
                $group = new stdClass();
                $group->title = "Екип \"" . core_Roles::fetchField($t, 'role') . "\"";
                $group->attr = array('class' => 'team');
                
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
                    $this->options[$t . ' team']->keylist = "|{$teamMembers}|";
                } else {
                    unset($this->options[$t . ' team']);
                }
            }
        }
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", $attr = array())
    {
        $this->prepareOptions();
        
        if(empty($value)) {
            $value = '|' . core_Users::getCurrent() . '|';
        }
        
        if(haveRole('test1')) {
            
            bp($this->options, $value);
        }
        foreach($this->options as $key => $optObj) {
            if($value == $optObj->keylist) {
                break;
            }
        }
        
        return ht::createSelect($name, $this->options, $key, $attr);
    }
    
    
    /**
     * Конвертира стойността от вербална към (int) - ключ към core_Interfaces
     */
    function fromVerbal_($value)
    {
        $this->prepareOptions();
        
        return $this->options[$value]->keylist;
    }
    
    
    /**
     * Конвертира стойността от вербална към (int) - ключ към core_Interfaces
     */
    function toVerbal_($value)
    {
        $this->prepareOptions();
        
        foreach($this->options as $key => $optObj) {
            if($value == $optObj->keylist) {
                break;
            }
        }
        
        return $this->options[$key]->title;
    }
}