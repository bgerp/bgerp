<?php

/**
 * С каква роля да получават новите потребители по подразбиране?
 */
defIfNot('EF_ROLES_DEFAULT', 'user');


/**
 *  Клас 'core_Roles' - Мениджър за ролите на потребителите
 *
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class core_Roles extends core_Manager
{
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Роли';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('role', 'varchar(64)', 'caption=Роля,mandatory');
        $this->FLD('inherit', 'keylist(mvc=core_Roles,select=role)', 'caption=Наследяване,notNull');
        
        $this->setDbUnique('role');
        
        $this->load('plg_Created,plg_SystemWrapper,plg_RowTools');
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function setupMVC()
    {
        $res = parent::setupMVC();
        
        if (!$this->fetch("#role = 'admin'")) {
            $rec->role = 'admin';
            $this->save($rec);
            $res .= "<li> Добавена роля 'admin'";
        }
        
        if (!$this->fetch("#role = '" . EF_ROLES_DEFAULT . "'")) {
            $rec = NULL;
            $rec->role = EF_ROLES_DEFAULT;
            $this->save($rec);
            $res .= "<li> Добавена роля '" . EF_ROLES_DEFAULT . "'";
        }
        
        return $res;
    }
    
    
    /**
     * Добавя посочената толя, ако я няма
     */
    function addRole($role, $inherit = NULL)
    {
        expect($role);
        $rec = new stdClass();
        $rec->role = $role;
        $Roles = cls::get('core_Roles');
        
        if(isset($inherit)) {
            $rec->inherit = $Roles->keylistFromVerbal($inherit);
        }
        
        return $Roles->save($rec, NULL, 'ignore');
    }
    
    
    /**
     * Зарежда ролите, ако все още не са заредени
     */
    function loadRoles()
    {
        if(!$this->rolesArr) {
            
            $query = $this->getQuery();
            
            while($rec = $query->fetch()) {
                if($rec->role) {
                    $this->rolesArr[$rec->role] = $rec->id;
                }
            }
        }
    }
    
    
    /**
     * Връща id-то на ролята според името и
     */
    function fetchByName($role)
    {
        $this->loadRoles();
        
        return $this->rolesArr[$role];
    }
    
    
    /**
     * Създава рекурсивно списък със всички роли, които наследява посочената роля
     */
    function expand($role, &$roles)
    {
        $roles[$role] = $role;
        $rec = $this->fetch("#role = '{$role}'");
        
        expect($rec, "Липсваща рола: {$role}");
        
        $newRoles = arr::make($rec->inherit, TRUE);
        
        foreach ($newRoles as $r) {
            if (!$roles[$r]) {
                $this->expand($r, $roles);
            }
        }
    }
    
    
    /**
     * Само за преход между старата версия
     */
    function on_AfterSetupMVC($mvc, $html)
    {
        $query = $mvc->getQuery();
        
        while($rec = $query->fetch()) {
            if($rec->inherit && $rec->inherit{0} != '|') {
                $roleId = $mvc->fetchByName($rec->inherit);
                
                if($roleId) {
                    $rec->inherit = "|" . $roleId . "|";
                    $mvc->save($rec);
                }
            }
        }
    }
    
    
    /**
     * Връща keylist с роли от вербален списък
     */
    function keylistFromVerbal($roles)
    {
        $rolesArr = arr::make($roles);
        
        $Roles = cls::get('core_Roles');
        
        foreach($rolesArr as $role) {
            $id = $Roles->fetchByName($role);
            expect($id);
            $keylist .= '|' . $id;
        }
        
        $keylist .= '|';
        
        return $keylist;
    }
    
    
    /**
     * Изпълнява се след запис/промяна на роля
     */
    function on_AfterSave($mvc, $id, $rec)
    {
        unset($mvc->rolesArr);
    }
    
    
    /**
     * Изпълнява се след изтриване на роля/роли
     */
    function on_AfterDelete($mvc, $res, $query, $cond)
    {
        unset($mvc->rolesArr);
    }
}