<?php



/**
 * С каква роля да получават новите потребители по подразбиране?
 */
defIfNot('EF_ROLES_DEFAULT', 'user');


/**
 * Клас 'core_Roles' - Мениджър за ролите на потребителите
 *
 *
 * @category  bgerp
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
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
     * Наименование на единичния обект
     */
    var $singleTitle = "Роля";
    

    /**
     * Статична променлива за съхранение на съществуващите роли в системата
     * (id -> Role, Role -> id)
     */
    static $rolesArr;
    
    
    /**
     * Променлива - флаг, че изчислените роли за наследяване 
     * и потребителските роли трябва да се преизчислят
     */
    var $recalcRoles = FALSE;
    

    /**
     * Кой може да редактира системните роли
     */
    var $canEditsysdata = 'no_one';
    

    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'admin';
	
    /**
     * Наследените роли, преди да редактираме формата
     */
    var $oldInheritRecs;
    

    /**
     * Колонки в списъчния изглед
     */
    var $listFields = 'id,role, inheritInput, type';
    
    
    /**
     * 
     */
     public $loadList = 'plg_Sorting, plg_State2, plg_Created, plg_SystemWrapper, plg_RowTools2';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('role', 'varchar(64)', 'caption=Роля,mandatory,translate');
        $this->FLD('inheritInput', 'keylist(mvc=core_Roles,select=role,groupBy=type,where=#type !\\= \\\'rang\\\' AND #type !\\= \\\'team\\\',orderBy=orderByRole)', 'caption=Наследяване,notNull,');
        $this->FLD('inherit', 'keylist(mvc=core_Roles,select=role,groupBy=type)', 'caption=Калкулирано наследяване,input=none,notNull');
        $this->FLD('type', 'enum(job=Модул,team=Екип,rang=Ранг,system=Системна,position=Длъжност,external=Външен достъп)', 'caption=Тип,notNull');
        $this->XPR('orderByRole', 'int', "(CASE #type WHEN 'team' THEN 1 WHEN 'rang' THEN 2 WHEN 'job' THEN 3 WHEN 'position' THEN 4 ELSE 5 END)");

        $this->setDbUnique('role');
    }
    
    
    /**
     * 
     */
    function act_test()
    {
        requireRole('admin');
        $S = cls::get('doc_Setup');
        $S->addPartnerRole1();
    }
    
    
    /**
     * Добавя посочената роля, ако я няма
     */
    static function addOnce($role, $inherit = NULL, $type = 'job')
    {
        expect($role);
        
        if(is_array($role)) {
            list($role, $inherit, $type) = $role;
        }

        $rec = new stdClass();
        $rec->role = $role;
        $rec->type = $type;
        $rec->createdBy = -1;
        
        $Roles = cls::get('core_Roles');
        
        if(isset($inherit)) {
            $rec->inheritInput = $Roles->getRolesAsKeylist($inherit);
        }
        
        $exRec = $Roles->fetch(array("#role = '[#1#]'", $rec->role));
        
        if($exRec) {
            $rec->id = $exRec->id;
            $rec->inheritInput = keylist::fromArray(arr::combine(keylist::toArray($rec->inheritInput), keylist::toArray($exRec->inheritInput)));
        }
        
        $Roles->save($rec);
        
        if(!$exRec) {
            $res = "<li class=\"debug-new\">Създаване на роля <b>{$role}</b></li>";
        } elseif($rec->id) {
            if($rec->inheritInput  == $exRec->inheritInput) {
                $res = "<li class=\"debug-info\">Без промяна на роля <b>{$role}</b></li>";
            } else {
                $res = "<li class=\"debug-update\">Модифициране на роля <b>{$role}</b>  $rec->inheritInput  == $exRec->inheritInput </li>";
            }
        } else {
            $res = "<li class=\"debug-error\">Грешка при създаване на роля <b>{$role}</b></li>";
        }

        return $res;
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
     * Връща масив от групирани по тип опции за ролите
     * 
     * @param array $rolesArr
     */
    public static function getGroupedOptions($rolesArr = array())
    {
        $query = self::getQuery();
        
        $query->where("#state != 'closed'");
        
        if (!empty($rolesArr)) {
            $query->in('id', $rolesArr, FALSE, TRUE);
        }
        
        $types = $query->getFieldType('type')->options;
        
        $res = array();

        foreach($types as $t => $n) {
            $res[$t] = array();
        }

        while($rec = $query->fetch()) {
            if($rec->role) {
                $res[$rec->type][$rec->id] = $rec->role;
            }
        }

        return $res;
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
     * Връща id-то на ролята според името и
     */
    static function fetchById($roleId)
    {
        self::loadRoles();
        
        return self::$rolesArr[$roleId];
    }
  

    /**
     * Създава рекурсивно списък с всички роли, които наследява посочената роля
     *
     * @param mixed $roles keylist или масив от роли, където елементите са id-тата, наименованията или записите на ролите
     * @return array масив от първични ключове на роли
     */
    static function expand($roles, &$current = array())
    {
        if (!is_array($roles)) {
            $roles = keylist::toArray($roles, TRUE);
        }
       
        foreach ($roles as $role) {
            if (is_object($role)) {
                $rec = $role;
            } elseif (is_numeric($role)) {
                $rec = static::fetch($role); 
            } else {
                $rec = static::fetch("#role = '{$role}'");
            }

            // Прескачаме насъсществуващите роли
            if(!$rec) continue;
            
            if ($rec && !isset($current[$rec->id])) {
                $current[$rec->id] = $rec->id;
                $current += static::expand($rec->inherit, $current);
            }
        }
        
        return $current;
    }
    
    
    /**
     * Връща keylist с всички роли от посочения тип
     */
    static function getRolesByType($type, $result = 'keylist', $onlyActive = FALSE)
    {
        $roleQuery = core_Roles::getQuery();
        
        if($onlyActive) {
            $roleQuery->where("#state = 'active'");
        }

        $roleQuery->orderBy("orderByRole=ASC");

        while($roleRec = $roleQuery->fetch("#type = '{$type}'")) {
            $res[$roleRec->id] = $roleRec->id;
        }
        
        if($result == 'keylist') {
            $res = keylist::fromArray($res);
        }

        return $res;
    }


    /**
     * Връща keylist с роли от вербален списък
     */
    static function getRolesAsKeylist($roles)
    {
        // Ако входния аргумент е keylist - директно го връщаме
        if(keylist::isKeylist($roles)) {

            return $roles;
        }

        $rolesArr = arr::make($roles);
        
        $Roles = cls::get('core_Roles');
        
        foreach($rolesArr as $role) {
            $id = $Roles->fetchByName($role);
            expect($id, $role);
            $keylistArr[$id] = $id;
        }
        
        $keylist = keylist::fromArray($keylistArr);
        
        return $keylist;
    }
    
    
    /**
     * Връща масив с броя на всички типове, които се срещат
     * 
     * @paramt keyList, array или list $roles - id' тата на ролите
     * 
     * @return array $rolesArr - Масив с всички типове и броя срещания
     */
    static function countRolesByType($roles) 
    {
        $res = array();

        if(is_string($roles) && $roles) {
            if(!keylist::isKeylist($roles)) {
                //Вземаме всики типове роли
                $roles = self::getRolesAsKeylist($roles);
            }
            $roles = keylist::toArray($roles);
        } elseif(is_int($roles)) {
            $roles = array($roles => $roles);
        } else {
            expect(is_array($roles));
        }
        
        if(count($roles)) {
            foreach ($roles as $id => $dummy) {
                
                $type = self::fetchField($id, 'type');

                if ($type) {
                    
                    //За всяко срещане на роля добавяме единица
                    $res[$type] += 1 ;
                }
            }
        }

        return $res;
    }


    /**
     * Премахва посочените във входния масив роли
     */
    public static function removeRoles($rArr)
    {
        $query = self::getQuery();

        while($rec = $query->fetch()) {
            $iArr = keylist::toArray($rec->inheritInput);
            foreach($rArr as $r) {
                if($r > 0) {
                    unset($iArr[$r]);
                }
            }
            $rec->inheritInput = keylist::fromArray($iArr);
            $rec->inherit = keylist::fromArray(self::expand($iArr));

            $query->mvc->save_($rec, 'inheritInput,inherit');
        }

        foreach($rArr as $r) {
            if($r>0) {
                self::delete($r);
            }
        }
    }


    /**
     * Проверка за зацикляне след субмитване на формата. Разпъване на всички наследени роли
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        
        // Ако формата е изпратена успешно
        if ($form->isSubmitted()) {
            
            // Шаблона за проверка на валидна роля
            // Да започва с буква(кирилица или латиница) или долна черта
            // Може да съдържа само: букви(кирилица или латиница), цифри, '_' и '-'
            $pattern = "/^[a-zА-Я\\_]{1}[a-z0-9А-Я\\_\\-]*$/iu";
            
            // Ако не е валидна роля
            if (!preg_match($pattern, $rec->role)) {
                
                // Сетваме грешка
                $form->setError('role', 'Некоректно име на роля|*: ' . $mvc->getVerbal($form->rec, 'role').' - |допустими са само: букви, цифри|*, "&nbsp_&nbsp", "&nbsp-&nbsp".');    
            }
        }
        
        // Ако формата е субмитната и редактираме запис
        if ($form->isSubmitted() && ($rec->id)) {
            
            if($rec->inheritInput || $rec->inherit) {
                
                $expandedRoles = self::expand($form->rec->inheritInput);
                
                // Ако има грешки
                if ($expandedRoles[$rec->id]) {
                    $form->setError('inherit', "|Не може да се наследи роля, която е или наследява текущата роля");  
                } else {
                    $rec->inherit = keylist::fromArray($expandedRoles);
                }

            }
         }
    }


    /**
     * Изпълнява се при преобразуване на реда към вербални стойности
     */
    function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        $rolesInputArr = keylist::toArray($rec->inheritInput);
        $rolesArr      = keylist::toArray($rec->inherit);

        foreach($rolesArr as $roleId) {

            if(!$rolesInputArr[$roleId]) {
                $addRoles .= ($addRoles ? ', ' : '') . $mvc->getVerbal($roleId, 'role');
            }
        }

        if($addRoles) {

            $row->inheritInput .= "<div style='color:#666;'>" . tr("индиректно") . ": " . $addRoles . "</div>";
        }

        $row->inheritInput = "<div style='max-width:400px;'>{$row->inheritInput}</div>";

    }


    /**
     * Преизчислява за всяка роля, всички наследени индеректно роли
     */
    static function rebuildRoles()
    {
        $i = 0;

        $maxI = self::count() + 1;

        $Roles = cls::get('core_Roles');
        
        do {
            
            $haveChanges = FALSE;
            
            expect($i++ <= $maxI);
            
            $query = self::getQuery();

            while($rec = $query->fetch()) {
                
                $calcRolesArr = self::expand($rec->inheritInput);
                
                $calcRolesKeylist = keylist::fromArray($calcRolesArr);

                if(($calcRolesKeylist || $rec->inherit) && ($calcRolesKeylist != $rec->inherit)) {
                    $rec->inherit = $calcRolesKeylist;
                    $haveChanges = TRUE;
                    $Roles->save_($rec, 'inherit');
                    $ind++;
                }
            }

        } while($haveChanges);
        
        return "<li> Преизчислени са $ind индиректни роли</li>";
    }
    

    /**
     * Получава управлението, когато в модела има промени
     */
    static function haveChanges()
    {
        $Roles = cls::get('core_Roles');

        $Roles->recalcRoles = TRUE;

        // Нулираме статичната променлива
        self::$rolesArr = NULL;
        
        // Изтриваме кеша
        core_Cache::remove('core_Roles', 'allRoles');
        core_Cache::remove(core_Users::ROLES_WITH_USERS_CACHE_ID, core_Users::ROLES_WITH_USERS_CACHE_ID);
    }


    /**
     * Виртуално добавяне на двата служебни потребителя
     */
    static function fetch($cond, $fields = '*', $cache = TRUE)
    {
        if ($cond === 0) {
            $res = new stdClass();
            $res->name = 'За всички потребители';
            $res->id = 0;
        } else {
            $res = parent::fetch($cond, $fields, $cache);
        }

        return $res;
    }


    /**
     * Превръща стойността на посоченото поле във вербална
     */
    static function getVerbal($rec, $fieldName)
    {
        if ($rec->id === 0) {
            
            return tr($rec->name);
        }
        
        return parent::getVerbal($rec, $fieldName);
    }
    
    
    /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        // Сортиране на записите по name
        $data->query->orderBy('state', 'DESC');
        $data->query->orderBy('id', 'ASC');
    }


    /**
     * При шътдаун на скрипта преизчислява наследените роли и ролите на потребителите
     */
    static function on_Shutdown($mvc)
    {
        if($mvc->recalcRoles) {
            self::rebuildRoles();
            core_Users::rebuildRoles();
        }

        $mvc->recalcRoles = FALSE;
    }


    /**
     * Изпълнява се след запис/промяна на роля
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        $mvc->haveChanges();
    }
    

    /**
     * Изпълнява се след запис/промяна на роля
     */
    static function on_AfterDelete($mvc, &$id)
    {
        $mvc->haveChanges();
    }
    
    
    /**
     * Само за преход между старата версия
     */
    function on_AfterSetupMVC($mvc, &$res)
    {
        self::addOnce('admin', NULL, 'system');
        self::addOnce('debug', NULL, 'system');
        self::addOnce(EF_ROLES_DEFAULT, NULL, 'system');
        self::addOnce('every_one', NULL, 'system');
    }
    
    
    /**
     * 
     */
    public function loadSetupData()
    {
        // Подготвяме пътя до файла с данните 
        $file = "core/csv/Roles.csv";
        
        // Кои колонки ще вкарваме
        $fields = array(
            0 => "role",
            1 => "inheritInput",
            2 => "type"
        );
        
        // Импортираме данните от CSV файла. 
        // Ако той не е променян - няма да се импортират повторно 
        $cntObj = csv_Lib::importOnce($this, $file, $fields, array(), array('delimiter' => '|'));
        
        // Записваме в лога вербалното представяне на резултата от импортирането 
        $res = $cntObj->html;
        
        return $res;
    }
    
    
    /**
     * Изпълнява се преди импортирването на данните
     * 
     * @param core_Roles $mvc
     * @param object $rec
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
        $rolesArr = explode(',', $rec->inheritInput);
        
        foreach ($rolesArr as &$name) {
            $name = trim($name);
        }
        
        try {
            $rec->inheritInput = self::getRolesAsKeylist($rolesArr);
        } catch (core_exception_Expect $e) {
            
            reportException($e);
            return FALSE;
        }
    }
}
