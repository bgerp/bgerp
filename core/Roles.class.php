<?php



/**
 * С каква роля да получават новите потребители по подразбиране?
 */
defIfNot('EF_ROLES_DEFAULT', 'user');


/**
 * Клас 'core_Roles' - Мениджър за ролите на потребителите
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Roles extends core_Manager
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Роли';
    
    /**
     * Статична променлива за съхранение на съществуващите роли в системата
     * (id -> Role, Role -> id)
     */
    static $rolesArr;
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('role', 'varchar(64)', 'caption=Роля,mandatory');
        $this->FLD('inherit', 'keylist(mvc=core_Roles,select=role,groupBy=type)', 'caption=Наследяване,notNull');
        $this->FLD('type', 'enum(job=Модул,team=Екип,rang=Ранг,system=Системна,position=Длъжност)', 'caption=Тип,notNull');
        
        $this->setDbUnique('role');
        
        $this->load('plg_Created,plg_SystemWrapper,plg_RowTools');
    }
    
    
    /**
     * Добавя посочената роля, ако я няма
     */
    static function addRole($role, $inherit = NULL, $type = 'job')
    {
        expect($role);
        $rec = new stdClass();
        $rec->role = $role;
        $rec->type = $type;
        $rec->createdBy = -1;
        
        $Roles = cls::get('core_Roles');
        
        if(isset($inherit)) {
            $rec->inherit = $Roles->keylistFromVerbal($inherit);
        }
        
        $rec->id = $Roles->fetchField("#role = '{$rec->role}'", 'id');
        
        $id = $rec->id;
        
        $Roles->save($rec);
        
        return !isset($id);
    }
    
    
    /**
     * При запис инвалидираме кешовете
     */
    static function on_BeforeSave($mvc, &$id, $rec)
    {
        self::$rolesArr = NULL;
        core_Cache::remove('core_Roles', 'allRoles');
    }
    
    
    /**
     * Зарежда ролите, ако все още не са заредени
     */
    static function loadRoles()
    {
        if(!count(self::$rolesArr)) {
            
            self::$rolesArr = core_Cache::get('core_Roles', 'allRoles', 1440, array('core_Roles'));
            
            if(!self::$rolesArr) {
                
                $query = static::getQuery();
                
                while($rec = $query->fetch()) {
                    if($rec->role) {
                        self::$rolesArr[$rec->role] = $rec->id;
                        self::$rolesArr[$rec->id] = $rec->role;
                    }
                }
                
                core_Cache::set('core_Roles', 'allRoles', self::$rolesArr, 1440, array('core_Roles'));
            }
        }
    }
    
    
    /**
     * Връща id-то на ролята според името и
     */
    static function fetchByName($role)
    {
        self::loadRoles();
        
        return self::$rolesArr[$role];
    }
    
    
    /**
     * Създава рекурсивно списък с всички роли, които наследява посочената роля
     *
     * @param mixed $roles роля или масив от роли, зададени с запис/ключ/име
     * @return array масив от първични ключове на роли
     */
    static function expand($roles, $current = array())
    {
        if (!is_array($roles)) {
            $roles = array($roles);
        }
        
        foreach ($roles as $role) {
            if (is_object($role)) {
                $rec = $role;
            } elseif (is_numeric($role)) {
                $rec = static::fetch($role);
            } else {
                $rec = static::fetch("#role = '{$role}'");
            }
            
            if ($rec && !isset($current[$rec->id])) {
                $current[$rec->id] = $rec->id;
                $parentRoles = arr::make($rec->inherit, TRUE);
                $current += static::expand($parentRoles, $current);
            }
        }
        
        return $current;
    }
    
    
    /**
     * Връща всички роли от посочения тип
     */
    static function getRolesByType($type)
    {
        $roleQuery = core_Roles::getQuery();
        
        while($roleRec = $roleQuery->fetch("#type = '{$type}'")) {
            $res[$roleRec->id] = $roleRec->id;
        }
        
        return type_Keylist::fromArray($res);
    }
    
    
    /**
     * Само за преход между старата версия
     */
    function on_AfterSetupMVC($mvc, &$res)
    {
        
        if (!$this->fetch("#role = 'admin'")) {
            $rec = new stdClass();
            $rec->role = 'admin';
            $rec->type = 'system';
            $this->save($rec);
            $res .= "<li> Добавена роля 'admin'";
        }
        
        if (!$this->fetch("#role = '" . EF_ROLES_DEFAULT . "'")) {
            $rec = new stdClass();
            $rec->role = EF_ROLES_DEFAULT;
            $rec->type = 'system';
            $this->save($rec);
            $res .= "<li> Добавена роля '" . EF_ROLES_DEFAULT . "'";
        }
        
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
    static function keylistFromVerbal($roles)
    {
        $rolesArr = arr::make($roles);
        
        $Roles = cls::get('core_Roles');
        
        foreach($rolesArr as $role) {
            $id = $Roles->fetchByName($role);
            expect($id, $role);
            $keylist .= '|' . $id;
        }
        
        $keylist .= '|';
        
        return $keylist;
    }
    
    
    /**
     * Изпълнява се след запис/промяна на роля
     */
    static function on_AfterSave($mvc, $id, $rec)
    {
        self::$rolesArr = NULL;
    }
    
    
    /**
     * Изпълнява се след изтриване на роля/роли
     */
    static function on_AfterDelete($mvc, &$res, $query, $cond)
    {
        self::$rolesArr = NULL;
    }
}