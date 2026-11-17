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
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_Roles extends core_Manager
{
    /**
     * Заглавие на модела
     */
    public $title = 'Роли';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Роля';
    
    
    /**
     * Статична променлива за съхранение на съществуващите роли в системата
     * (id -> Role, Role -> id)
     */
    public static $rolesArr;
    
    
    /**
     * Променлива - флаг, че изчислените роли за наследяване
     * и потребителските роли трябва да се преизчислят
     */
    public $recalcRoles = false;
    
    
    /**
     * Кой може да редактира системните роли
     */
    public $canEditsysdata = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Наследените роли, преди да редактираме формата
     */
    public $oldInheritRecs;
    
    
    /**
     * Колонки в списъчния изглед
     */
    public $listFields = 'id,role, inheritInput, inheritAdd, inheritRemove, type';
    
    
    public $loadList = 'plg_Sorting, plg_State2, plg_Created, plg_SystemWrapper, plg_RowTools2, plg_Search, core_UserTranslatePlg';
    
    
    public $searchFields = 'role, inherit, inheritInput, type';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('role', 'varchar(64)', 'caption=Роля,mandatory, translate=user|tr|transliterate');
        $this->FLD('inheritInput', 'keylist(mvc=core_Roles,select=role,groupBy=type,where=#type !\\= \\\'rang\\\' AND #type !\\= \\\'team\\\',orderBy=orderByRole)', 'caption=Наследяване,notNull,');
        $this->FLD('inherit', 'keylist(mvc=core_Roles,select=role,groupBy=type)', 'caption=Калкулирано наследяване,input=none,notNull');
        $this->FLD('inheritAdd', 'keylist(mvc=core_Roles,select=role,groupBy=type,orderBy=orderByRole)', 'caption=Допълнителни роли,notNull');
        $this->FLD('inheritRemove', 'keylist(mvc=core_Roles,select=role,groupBy=type,orderBy=orderByRole)', 'caption=Изключени роли,notNull,hint=Изключването важи само за наследяването през тази роля');
        $this->FLD('type', 'enum(job=Модул,team=Екип,rang=Ранг,system=Системна,position=Длъжност,external=Външен достъп)', 'caption=Тип,notNull');
        $this->XPR('orderByRole', 'int', "(CASE #type WHEN 'team' THEN 1 WHEN 'rang' THEN 2 WHEN 'job' THEN 3 WHEN 'position' THEN 4 ELSE 5 END)");
        
        $this->setDbUnique('role');
    }


    /**
     * Позволява общи политики да обвият записа след стандартната подготовка.
     *
     * @param stdClass $rec Подготвена роля.
     * @param string|array|null $fields Полета за запис.
     * @param string|null $mode Режим на native записа.
     * @return int|false|null Резултат от записа.
     */
    public function save_(&$rec, $fields = null, $mode = null)
    {
        $operation = function () use (&$rec, $fields, $mode) {
            return parent::save_($rec, $fields, $mode);
        };
        $this->invoke('PrepareSaveOperation', array(&$operation, $rec, $fields, $mode));

        return $operation();
    }
    
    
    /**
     * Добавя посочената роля, ако я няма
     */
    public static function addOnce($role, $inherit = null, $type = 'job')
    {
        expect($role);
        
        if (is_array($role)) {
            list($role, $inherit, $type) = array_pad($role, 3, null);
        }
        
        $rec = new stdClass();
        $rec->role = $role;
        $rec->type = $type;
        $rec->createdBy = -1;
        
        $Roles = cls::get('core_Roles');
        
        if (isset($inherit)) {
            $rec->inheritInput = $Roles->getRolesAsKeylist($inherit);
        }
        
        $exRec = $Roles->fetch(array("#role = '[#1#]'", $rec->role));

        if ($exRec) {
            $rec->id = $exRec->id;
            $rec->inheritInput = keylist::fromArray(arr::combine(keylist::toArray($rec->inheritInput ?? null), keylist::toArray($exRec->inheritInput ?? null)));
        }
        
        $Roles->save($rec);
        
        if (!$exRec) {
            $res = "<li class=\"debug-new\">Създаване на роля <b>{$role}</b></li>";
        } elseif (!empty($rec->id)) {
            if ($rec->inheritInput == $exRec->inheritInput) {
                $res = "<li class=\"debug-info\">Без промяна на роля <b>{$role}</b></li>";
            } else {
                $res = "<li class=\"debug-update\">Модифициране на роля <b>{$role}</b>  {$rec->inheritInput}  == {$exRec->inheritInput} </li>";
            }
        } else {
            $res = "<li class=\"debug-error\">Грешка при създаване на роля <b>{$role}</b></li>";
        }
        
        return $res;
    }
    
    
    /**
     * Зарежда ролите, ако все още не са заредени
     */
    public static function loadRoles()
    {
        if (!countR(self::$rolesArr)) {
            self::$rolesArr = core_Cache::get('core_Roles', 'allRoles', 1440, array('core_Roles'));
            
            if (!self::$rolesArr) {
                self::$rolesArr = [];
                $query = static::getQuery();

                while ($rec = $query->fetch()) {
                    if ($rec->role) {
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
            $query->in('id', $rolesArr, false, true);
        }
        
        $types = $query->getFieldType('type')->options;
        
        $res = array();
        
        foreach ($types as $t => $n) {
            $res[$t] = array();
        }
        
        while ($rec = $query->fetch()) {
            if ($rec->role) {
                $res[$rec->type][$rec->id] = $rec->role;
            }
        }
        
        return $res;
    }
    
    
    /**
     * Връща id-то на ролята според името и
     */
    public static function fetchByName($role)
    {
        self::loadRoles();
        
        return self::$rolesArr[$role] ?? null;
    }
    
    
    /**
     * Връща id-то на ролята според името и
     */
    public static function fetchById($roleId)
    {
        self::loadRoles();
        
        return self::$rolesArr[$roleId] ?? null;
    }
    
    
    /**
     * Създава списък с всички роли, достъпни по разрешените пътища на наследяване.
     *
     * @param mixed $roles keylist или масив от роли, където елементите са id-тата, наименованията или записите на ролите
     * @param array $current Натрупани роли от независими пътища
     * @param array $excluded Изключени роли само за текущите пътища
     * @param array|null $records Предложен граф за проверка преди запис; null използва базата
     *
     * @return array масив от първични ключове на роли
     */
    public static function expand($roles, &$current = array(), $excluded = array(), $records = null)
    {
        if (!is_array($roles)) {
            $roles = keylist::toArray($roles, true);
        }

        $byKey = array();
        if (isset($records)) {
            foreach ($records as $record) {
                $byKey[$record->id ?? 0] = $record;
                $byKey[$record->role ?? ''] = $record;
            }
        }
        $pending = array();
        foreach ($roles as $role) {
            $pending[] = array($role, $excluded);
        }
        $visited = array();

        while ($pending) {
            list($role, $blocked) = array_pop($pending);
            if (is_object($role)) {
                $rec = $role;
            } elseif (isset($records)) {
                $rec = $byKey[$role] ?? null;
            } elseif (is_numeric($role)) {
                $rec = static::fetch($role);
            } else {
                $rec = static::fetch(array("#role = '[#1#]'", $role));
            }
            
            // Изключената роля не предава и собствените си наследявания по този път.
            $roleId = $rec->id ?? null;
            if (!$roleId || isset($blocked[$roleId])) {
                continue;
            }

            $blocked += keylist::toArray($rec->inheritRemove ?? null);
            $visitKey = $roleId . ':' . keylist::fromArray($blocked);
            if (isset($visited[$visitKey])) {
                continue;
            }
            $visited[$visitKey] = true;
            $current[$roleId] = $roleId;

            // Един и същ възел може да е достъпен и по път с други изключвания.
            $inherited = keylist::toArray($rec->inheritInput ?? null) + keylist::toArray($rec->inheritAdd ?? null);
            foreach ($inherited as $child) {
                $pending[] = array($child, $blocked);
            }
        }
        
        return $current;
    }


    /**
     * Изчислява наследяванията с локалните добавки и изключвания.
     */
    public static function getInheritedRoles($rec)
    {
        $roles = keylist::toArray($rec->inheritInput ?? null) + keylist::toArray($rec->inheritAdd ?? null);
        $current = array();

        return static::expand($roles, $current, keylist::toArray($rec->inheritRemove ?? null));
    }
    
    
    /**
     * Връща keylist с всички роли от посочения тип
     */
    public static function getRolesByType($type, $result = 'keylist', $onlyActive = false)
    {
        $roleQuery = core_Roles::getQuery();
        
        if ($onlyActive) {
            $roleQuery->where("#state = 'active'");
        }
        
        $roleQuery->orderBy('orderByRole=ASC');

        $res = [];
        while ($roleRec = $roleQuery->fetch("#type = '{$type}'")) {
            $res[$roleRec->id] = $roleRec->id;
        }
        
        if ($result == 'keylist') {
            $res = keylist::fromArray($res);
        }
        
        return $res;
    }
    
    
    /**
     * Връща keylist с роли от вербален списък
     */
    public static function getRolesAsKeylist($roles)
    {
        // Ако входния аргумент е keylist - директно го връщаме
        if (keylist::isKeylist($roles)) {
            
            return $roles;
        }
        
        $rolesArr = arr::make($roles);
        
        $Roles = cls::get('core_Roles');

        $keylistArr = array();

        foreach ($rolesArr as $role) {
            $id = $Roles->fetchByName($role);

            expect($id || ($role == 'no_one'), $role);

            if (!isset($id)) {
                $id = -1;
            }

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
    public static function countRolesByType($roles)
    {
        $res = array();
        
        if (is_string($roles) && $roles) {
            if (!keylist::isKeylist($roles)) {
                //Вземаме всики типове роли
                $roles = self::getRolesAsKeylist($roles);
            }
            $roles = keylist::toArray($roles);
        } elseif (is_int($roles)) {
            $roles = array($roles => $roles);
        } else {
            expect(is_array($roles));
        }
        
        if (countR($roles)) {
            foreach ($roles as $id => $dummy) {
                $type = self::fetchField($id, 'type');
                
                if ($type) {
                    
                    //За всяко срещане на роля добавяме единица
                    $res[$type] = ($res[$type] ?? 0) + 1;
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
        
        while ($rec = $query->fetch()) {
            foreach (array('inheritInput', 'inheritAdd', 'inheritRemove') as $field) {
                $iArr = keylist::toArray($rec->{$field} ?? null);
                foreach ($rArr as $r) {
                    if ($r > 0) {
                        unset($iArr[$r]);
                    }
                }
                $rec->{$field} = keylist::fromArray($iArr);
            }
            $rec->inherit = keylist::fromArray(self::getInheritedRoles($rec));
            
            cls::get('core_Roles')->save_($rec, 'inheritInput,inheritAdd,inheritRemove,inherit');
        }
        
        foreach ($rArr as $r) {
            if ($r > 0) {
                self::delete($r);
            }
        }
    }
    
    
    /**
     * Заменя старите роли с новата при потребителите и наследяванията
     *
     * @param string|int|array $oldRoles      - име, ид или масив от стари роли
     * @param string           $newRole
     * @param bool             $deleteOldRole - дали старите роли да се изтрият от модела
     *
     * @return string
     */
    public static function replaceRole($oldRoles, $newRole, $deleteOldRole = false)
    {
        // Новата роля се създава, ако я няма
        $res = self::addOnce($newRole);
        $newId = self::fetchByName($newRole);
        expect($newId);

        // Старите роли могат да са подадени с име или ид, несъществуващите се пропускат
        $oldIds = $oldNames = array();
        foreach (arr::make($oldRoles) as $role) {
            $oldId = is_numeric($role) ? (self::fetchById($role) ? (int) $role : null) : self::fetchByName($role);
            if (empty($oldId) || $oldId == $newId) {
                continue;
            }
            $oldIds[$oldId] = $oldId;
            $oldNames[$oldId] = self::fetchById($oldId);
        }
        if (empty($oldIds)) {

            return $res;
        }

        // Ролите, наследяващи старите, започват да наследяват новата
        $Roles = cls::get('core_Roles');
        $rQuery = self::getQuery();
        $rQuery->likeKeylist('inheritInput', keylist::fromArray($oldIds));
        $rQuery->likeKeylist('inheritAdd', keylist::fromArray($oldIds), true);
        $rQuery->likeKeylist('inheritRemove', keylist::fromArray($oldIds), true);
        while ($rec = $rQuery->fetch()) {
            foreach (array('inheritInput', 'inheritAdd', 'inheritRemove') as $field) {
                $inheritArr = keylist::toArray($rec->{$field} ?? null);
                if (!array_intersect_key($inheritArr, $oldIds)) {
                    continue;
                }
                $inheritArr = array_diff_key($inheritArr, $oldIds);
                if (($rec->id ?? null) != $newId) {
                    $inheritArr[$newId] = $newId;
                }
                $rec->{$field} = keylist::fromArray($inheritArr);
            }
            $Roles->save($rec, 'inheritInput,inheritAdd,inheritRemove');
        }

        // На потребителите старите роли се сменят с новата
        $count = core_Users::replaceRolesForUsers($oldIds, $newId);

        if ($deleteOldRole) {
            self::removeRoles($oldIds);
        }

        // Преизчисляване на индиректните роли на ролите и потребителите
        self::rebuildRoles();
        core_Users::rebuildRoles();
        $oldNames = implode(', ', $oldNames);

        return $res . "<li class=\"debug-update\">Ролите <b>{$oldNames}</b> са заменени с <b>{$newRole}</b> при {$count} потребителя</li>";
    }
    
    
    /**
     * Редакцията на добавките не разрешава затваряне на системните роли.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'changestate' && ($rec->createdBy ?? null) == core_Users::SYSTEM_USER) {
            $requiredRoles = 'no_one';
        }
    }


    /**
     * Администраторът настройва само добавките и изключванията на системните роли.
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = $data->form ?? null;
        $oldRec = !empty($form->rec->id) ? $mvc->fetch((int) $form->rec->id) : null;
        if (($oldRec->createdBy ?? null) == core_Users::SYSTEM_USER) {
            foreach (array('role', 'type', 'inheritInput') as $field) {
                $form->rec->{$field} = $oldRec->{$field} ?? null;
                // Ограничените опции са само за формата, не за групирането на всички роли.
                $form->fields[$field]->type = clone $form->getFieldType($field);
                $form->setReadOnly($field);
            }
        } else {
            $form->setField('inheritAdd,inheritRemove', 'input=none');
        }
    }


    /**
     * Проверка за зацикляне след субмитване на формата. Разпъване на всички наследени роли
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec ?? null;
        if (!$form->isSubmitted()) {
            return;
        }

        $oldRec = !empty($rec->id) ? $mvc->fetch((int) $rec->id) : null;
        $isSystem = ($oldRec->createdBy ?? null) == core_Users::SYSTEM_USER;
        if ($isSystem) {
            // Игнорираме подадени защитени стойности и при подправена заявка.
            foreach ((array) $oldRec as $field => $value) {
                if ($field != 'inheritAdd' && $field != 'inheritRemove') {
                    $rec->{$field} = $value;
                }
            }
        } else {
            $rec->inheritAdd = $oldRec->inheritAdd ?? '';
            $rec->inheritRemove = $oldRec->inheritRemove ?? '';
        }
        
        // Ако формата е изпратена успешно
        if ($form->isSubmitted()) {
            
            // Шаблона за проверка на валидна роля
            // Да започва с буква(кирилица или латиница) или долна черта
            // Може да съдържа само: букви(кирилица или латиница), цифри, '_' и '-'
            $pattern = '/^[a-zА-Я\\_]{1}[a-z0-9А-Я\\_\\-]*$/iu';
            
            // Ако не е валидна роля
            if (!preg_match($pattern, $rec->role ?? '')) {
                
                // Сетваме грешка
                $form->setError('role', 'Некоректно име на роля|*: ' . $mvc->getVerbal($form->rec, 'role').' - |допустими са само: букви, цифри|*, "&nbsp_&nbsp", "&nbsp-&nbsp".');
            }
        }
        
        // Ако формата е субмитната и редактираме запис
        if ($form->isSubmitted() && !empty($rec->id)) {
            if (!$isSystem) {
                // Формата не предлага всички наследявания, например рангове и екипи.
                $suggestions = $form->getFieldType('inheritInput')->getSuggestions();
                $hiddenRoles = array_diff_key(keylist::toArray($oldRec->inheritInput ?? null), $suggestions);
                $rec->inheritInput = keylist::fromArray(keylist::toArray($rec->inheritInput ?? null) + $hiddenRoles);
            }

            $expandedRoles = self::getInheritedRoles($rec);
            if (isset($expandedRoles[$rec->id]) || keylist::isIn($rec->id, $rec->inheritAdd ?? '') || keylist::isIn($rec->id, $rec->inheritRemove ?? '')) {
                $form->setError($isSystem ? 'inheritAdd,inheritRemove' : 'inheritInput', '|Не може да се наследи или изключи текущата роля или да се създаде цикъл');
            } else {
                $rec->inherit = keylist::fromArray($expandedRoles);
            }
        }
    }
    
    
    /**
     * Изпълнява се при преобразуване на реда към вербални стойности
     */
    public function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        $rolesInputArr = keylist::toArray($rec->inheritInput ?? null) + keylist::toArray($rec->inheritAdd ?? null);
        $rolesArr = keylist::toArray($rec->inherit ?? null);
        $addRoles = '';

        foreach ($rolesArr as $roleId) {
            if (empty($rolesInputArr[$roleId])) {
                $addRoles .= ($addRoles ? ', ' : '') . $mvc->getVerbal($roleId, 'role');
            }
        }
        
        if ($addRoles) {
            $row->inheritInput .= "<div style='color:#666;'>" . tr('индиректно') . ': ' . $addRoles . '</div>';
        }
        
        $row->inheritInput = "<div style='max-width:400px;'>{$row->inheritInput}</div>";
    }
    
    
    /**
     * Преизчислява за всяка роля, всички наследени индеректно роли
     */
    public static function rebuildRoles()
    {
        $i = 0;
        $ind = 0;

        $maxI = self::count() + 1;

        $Roles = cls::get('core_Roles');

        do {
            $haveChanges = false;

            expect($i++ <= $maxI);
            
            $query = self::getQuery();
            
            while ($rec = $query->fetch()) {
                $calcRolesArr = self::getInheritedRoles($rec);
                
                $calcRolesKeylist = keylist::fromArray($calcRolesArr);
                
                if ($calcRolesKeylist != ($rec->inherit ?? '')) {
                    $rec->inherit = $calcRolesKeylist;
                    $haveChanges = true;
                    $Roles->save_($rec, 'inherit');
                    $ind++;
                }
            }
        } while ($haveChanges);
        
        return "<li> Преизчислени са {$ind} индиректни роли</li>";
    }
    
    
    /**
     * Получава управлението, когато в модела има промени
     */
    public static function haveChanges()
    {
        $Roles = cls::get('core_Roles');
        
        $Roles->recalcRoles = true;
        
        // Нулираме статичната променлива
        self::$rolesArr = null;
        
        // Изтриваме кеша
        core_Cache::remove('core_Roles', 'allRoles');
        core_Cache::remove(core_Users::ROLES_WITH_USERS_CACHE_ID, core_Users::ROLES_WITH_USERS_CACHE_ID);
    }
    
    
    /**
     * Виртуално добавяне на двата служебни потребителя
     */
    public static function fetch($cond, $fields = '*', $cache = true)
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
    public static function getVerbal($rec, $fieldName)
    {
        if (is_object($rec) && ($rec->id ?? null) === 0) {

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
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->showFields = 'search, type';
        
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->fields['type']->type->options = array('' => '') + $data->listFilter->fields['type']->type->options;
        $data->listFilter->setField('type', 'placeholderType=all');
        
        $data->listFilter->input('search, type');
        
        $rec = $data->listFilter->rec;
        
        if (!($rec->type ?? null)) {
            $rec->type = '';
        }
        
        if ($rec->type) {
            $data->query->where(array("#type = '[#1#]'", $rec->type));
        }
        
        // Сортиране на записите по name
        $data->query->orderBy('state', 'DESC');
        $data->query->orderBy('id', 'ASC');
    }
    
    
    /**
     * При шътдаун на скрипта преизчислява наследените роли и ролите на потребителите
     */
    public static function on_Shutdown($mvc)
    {
        if ($mvc->recalcRoles) {
            self::rebuildRoles();
            core_Users::rebuildRoles();
        }
        
        $mvc->recalcRoles = false;
    }
    
    
    /**
     * Изпълнява се след запис/промяна на роля
     */
    public static function on_AfterSave($mvc, &$id, $rec, $saveFields = null)
    {
        $mvc->haveChanges();
    }
    
    
    /**
     * Изпълнява се след запис/промяна на роля
     */
    public static function on_AfterDelete($mvc, &$id)
    {
        $mvc->haveChanges();
    }
    
    
    /**
     * Само за преход между старата версия
     */
    public function on_AfterSetupMVC($mvc, &$res)
    {
        self::addOnce('admin', null, 'system');
        self::addOnce('debug', null, 'system');
        self::addOnce(EF_ROLES_DEFAULT, null, 'system');
        self::addOnce('every_one', null, 'system');
    }
    
    
    public function loadSetupData()
    {
        // Подготвяме пътя до файла с данните
        $file = 'core/csv/Roles.csv';
        
        // Кои колонки ще вкарваме
        $fields = array(
            0 => 'role',
            1 => 'inheritInput',
            2 => 'type'
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
     * @param object     $rec
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
            
            return false;
        }
    }
}
