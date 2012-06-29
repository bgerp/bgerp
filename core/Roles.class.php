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
     * Наследените роли, преди да редактираме формата
     */
    var $oldInheritRecs;
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('role', 'varchar(64)', 'caption=Роля,mandatory');
        $this->FLD('inherit', 'keylist(mvc=core_Roles,select=role,groupBy=type, prepareQuery=core_Roles::removeRangsInQuery)', 'caption=Наследяване,notNull');
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
     * Връща масив с броя на всички типове, които се срещат
     * 
     * @paramt keyList $roles - id' тата на ролите
     * 
     * @return array $rolesArr - Масив с всички типове и броя срещания
     */
    static function getRolesTypeArr($roles) 
    {
        if (!$roles) return ;
        
        //Вземаме всики типове роли
        $rolesType = static::getRolesTypes($roles); 
        
        //Разделяме ги в масив
        $typeArr = (explode('|', $rolesType));
        
        foreach ($typeArr as $type) {
            
            if ($type) {
                
                //За всяко срещане на роля добавяме единица
                $rolesTypeArr[$type] += 1 ;
            }
        }

        return $rolesTypeArr;
    }
    
    
    /**
     * Връща всички типове на ролите и техните наследници
     * 
     * @paramt keyList $roles - id' тата на ролите
     * 
     * @return string $type - 
     */
    static function getRolesTypes($roles)
    {
        //Масив с всички id' та
        $rolesArr = type_Keylist::toArray($roles);
        
        foreach ($rolesArr as $role) {
            
            //Записите за съответната роля
            $rolesRec = core_Roles::fetch($role);
            
            //Ако ролята има наследници
            if ($rolesRec->inherit) {
                
                //Вземаме всички типове на наследниците
                $type .= static::getRolesTypes($rolesRec->inherit);
            }
                
            //Вземаме всички типове на ролята
            $type .= $rolesRec->type .  "|";
        }

        return $type;
    }
    
    
    /**
     * При подготвяне на заявката, задава да не се показват ролити от тип ранг.
     * Използва се при създаване на роля, да няма възможност за наследяване на роли от тип ранг.
     */
    static function removeRangsInQuery($mvc, $query)
    {
        $query->where("#type != 'rang'");    
    }

    
    /**
     * 
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
        $id = $form->rec->id;
        
        // Ако формата е субмитната и редактираме запис
        if ($form->isSubmitted() && ($id)) {
            
            // Всички наследени роли на съответния запис
            $mvc->oldInheritRecs = $mvc->fetchField($form->rec->id, 'inherit');
            
            if ($mvc->oldInheritRecs == $form->rec->inherit) return ;

            // Проверява роля да не наследява себе си
            if (type_Keylist::isIn($id, $form->rec->inherit)) {
                $role = $mvc->getVerbal($form->rec, 'role');
                $form->setError('inherit', "|Не може да се наследи ролята, която редактирате:|* '{$role}'");
            }
            
            // Проверяваме роля да не наследява роля, на която е родител
            if (!$form->gotErrors()) {
                
                // Масив с наследените роли
                $inheritArr = type_Keylist::toArray($form->rec->inherit);
                
                // За всяка наследена роля
                foreach ($inheritArr as $inhertId) {
                    
                    // Намираме вземаме всички роли, на които е родител
                    $rolesArr = self::getRolesArr($inhertId);
                    
                    // Ако текущата роля, е родител
                    if ($rolesArr[$id]) {
                        
                        // Показваме съобщение за грешка
                        $role = core_Roles::getVerbal($inhertId, 'role');
                        
                        // Записваме всички наследници
                        $errorInh .= ($errorInh) ? ', ' . $role : $role;
                    }
                }
                
                // Ако има грешки
                if ($errorInh) {
                    $form->setError('inherit', "|Не може да се наследи роля, която наследява текущата роля:|* '{$errorInh}'");  
                }    
            }
        }
    }
    
    
    /**
     * При запис инвалидираме кешовете
     */
    static function on_BeforeSave($mvc, &$id, $rec)
    {
        // Ако добавяме нов запис
        if (!$rec->id) {
            
            // Всички наследени роли ги преобразуваме в масив
            $recInhArr = type_Keylist::toArray($rec->inherit);
            
            // Масив с наследените роли
            $inhArr = array();
            
            // Обхождаме масива с наследените роли
            foreach ($recInhArr as $inhId) {
                
                // Добавяме в масива всички наследени роли на съответната наследена роля
                $inhArr += self::getRolesArr($inhId);
            }
            
            // Променяма записа за наследените роли
            $rec->inherit = type_Keylist::fromArray($inhArr);
        }
        
        // Нулираме статичната променлива
        self::$rolesArr = NULL;
        
        // Изтриваме кеша
        core_Cache::remove('core_Roles', 'allRoles');
    }
    
    
    /**
     * Изпълнява се след запис/промяна на роля
     */
    static function on_AfterSave($mvc, $id, $rec)
    {
        // Ако има промени
        if ($mvc->oldInheritRecs != $rec->inherit) {
            
            // Масив с наследените роли, преди промяната
            $oldInheritRecsArr = type_Keylist::toArray($mvc->oldInheritRecs);
            
            // Масив с наследените роли и техните наследници преди промяната
            $oldInheritRecsArrExt = array();
            
            // Масив с наследените роли и техните наследници след промяната
            $newInheritRecsArrExt = array();
            
            // Обхождаме всички наследени роли преди промяната
            foreach ($oldInheritRecsArr as $oldInhRecId) {
                
                // Проверяваме дали имат наследници
                $oldInheritRecsArrExt += self::getRolesArr($oldInhRecId);
            }
            
            // Масив с наследениете роли, след промяната 
            $newInheritRecsArr = type_Keylist::toArray($rec->inherit);
            
            // Обхождаме всички наследени роли след промяната
            foreach ($newInheritRecsArr as $newInhRecId) {
                
                // Проверяваме дали имат наследници
                $newInheritRecsArrExt += self::getRolesArr($newInhRecId);
            }

            // Масив с изтритите роли
            $delInheritRecsArr = array_diff($oldInheritRecsArrExt, $newInheritRecsArrExt);
            
            // Масив с добавените роли
            $addInheritRecsArr = array_diff($newInheritRecsArrExt, $oldInheritRecsArrExt);
            
            // Заявка към таблицата с потребители
            $query = core_Users::getQuery();

            // Да се вземата потребителите, които имат съответната роля
            $query->where("#roles LIKE '%|{$rec->id}|%'");
            
            // Обикаля всички потребители, които имат от съответната роля
            while ($uRec = $query->fetch()) {
                
                // Ролите на съответния потребител
                $uRolesArr = type_Keylist::toArray($uRec->roles);
                
                // Обикаляме ролите, които трябва да се изтрият
                foreach ($delInheritRecsArr as $delVal) {
                    
                    // Изтриваме ролята
                    unset($uRolesArr[$delVal]);
                }
                
                // Обикаляме ролите, които трябва да се добавят
                foreach ($addInheritRecsArr as $addVal) {
                    
                    // Добавяме ролята
                    $uRolesArr[$addVal] = $addVal;
                }
    
                $nRec = new stdClass();
                
                // id' то на потребителя, който ще обновим
                $nRec->id = $uRec->id;
                
                // Новите роли на потребителя
                $nRec->roles = type_Keylist::fromArray($uRolesArr);
                
                // Обновяваме записа
                core_Users::save($nRec);
            }
            
            // Обновява всички роли при промяна на избраната, която се явява родител
            self::updateAllRolesDuringEdit($rec->id, $mvc->oldInheritRecs);
        }
        
        // Нулираме статичната променлива
        self::$rolesArr = NULL;
        
        // Изтриваме кеша
        core_Cache::remove('core_Roles', 'allRoles');
    }
    
    
	/**
     * Изпълнява се преди изтриване на роля/роли
     */
    static function on_BeforeDelete($mvc, &$res, $query, $cond)
    {
        // Нулираме статичната променлива
        self::$rolesArr = NULL;
        
        // Изтриваме кеша
        core_Cache::remove('core_Roles', 'allRoles');
        
        // Ако заявката е празна, кода не се изпъклнява
        if (!$cond) return ;
        
        // Масив с всички роли, които ще се изтриват
        $delRecsArr = self::getRolesArr($cond);

        // Заяка към таблицата с потребителите
        $query = core_Users::getQuery();
        
        // Вземаме всички потребители, използват текущата роля
        $query->where("#roles LIKE '%|{$cond}|%'");
        
        // Обикаляме всички открити потребители
        while ($uRec = $query->fetch()) {

            // Ролите, които има потребителя
            $uRolesArr = type_Keylist::toArray($uRec->roles);
            
            // Обикаляме всички роли, които ще се изтриват
            foreach ($delRecsArr as $delVal) {
                
                // Изтриваме съответната роля
                unset($uRolesArr[$delVal]);
            }
            
            $nRec = new stdClass();
            
            // id' то на потребителя, който ще обновим
            $nRec->id = $uRec->id;
            
            // Новите роли на потребителя
            $nRec->roles = type_Keylist::fromArray($uRolesArr);
            
            // Обновяваме записа
            core_Users::save($nRec);
        }
        
        // Изтрива всички роли при промяна на избраната, която се явява родител
        self::updateAllRolesDuringDel($cond);
    }
    
    
    /**
     * Изпълнява се след изтриване на роля/роли
     */
    static function on_AfterDelete($mvc, &$res, $query, $cond)
    {
        // Нулираме статичната променлива
        self::$rolesArr = NULL;
        
        // Изтриваме кеша
        core_Cache::remove('core_Roles', 'allRoles');
    }
    
    
    /**
     * Връща масив с всички роли (наследени роли + текущата роля), на подаденото id
     * 
     * @param integer $id - id' то на ролята, за която ще се търсят наследените роли
     * 
     * @return array $allRolesArr - Масив с всички роли
     */
    static function getRolesArr($id) 
    {
        // Масив с всички наследени роли
        $allRolesArr = array();

        // Вземаме всички наследени роли и текущата роля под формата на стринг
        $res = self::getInheritRoles($id);
        
        // Разделяме ролите в масив
        $resArr = (explode('|', $res));
        
        // Обхождаме масива
        foreach ($resArr as $r) {
            
            // Ако има запис
            if ($r) {
                
                // Добавяме го в масива с всички роли
                $allRolesArr[$r] = $r;    
            }
        }
        
        return $allRolesArr;
    }
    
    
    /**
     * Рекурсивна функция за отркриване на всички наследени роли, в дадена роля
     * 
     * @param integer $id - id' то на ролята, за която ще се търсят наследените роли
     * 
     * @return string $allRoles - Стринг с всички роли, включително и зададената
     */
    static function getInheritRoles($id)
    {
        // Вземаме записа за съответната роля
        $roleRec = core_Roles::fetch($id);//bp(core_Roles::fetch(81), $roleRec);
        
        // Ако няма запис, връщаме
        if (!$roleRec) return ;
        
        // Ако има наследени роли
        if ($roleRec->inherit) {
            
            //Преобразуваме в масив всички наследени роли
            $inhArr = type_Keylist::toArray($roleRec->inherit);
            
            // Обхождаме масива
            foreach ($inhArr as $inh) {
                
                // Ако някоя функция наследява себе си. За да не се получи "безкраен" цикъл
                if ($id == $inh) continue; 
                
                // Извикваме рекурсивно функцията, за да може да вземем наследените роли на наследниците
                $allRoles .= self::getInheritRoles($inh);
            }
        }
        
        // Добавяме текущото id, към стринга
        $allRoles .= $id . '|';
        
        return $allRoles;
    }
    
    
    /**
     * Изтрива всички роли при промяна на избраната, която се явява родител
     * 
     * @param integer $id - id' то на записа, който се изтрива
     * 
     * @access private
     */
    static function updateAllRolesDuringDel($id)
    {
        // Записа, който ще се изтрие /родител/
        $deletingRec = core_Roles::fetch($id);
        
        // Масив с всички наследени роли
        $inhDeletingRec = type_Keylist::toArray($deletingRec->inherit);
        
        // Заяка към таблицата с ролите
        $query = core_Roles::getQuery();
        
        // Вземаме всички роли, които наследяват текущата
        $query->where("#inherit LIKE '%|{$id}|%'");
        
        // Обикаляме всички открити роли /наследници/
        while ($rolesRec = $query->fetch()) {
            
            // Масив с ролите, които наследява
            $inheritArr = type_Keylist::toArray($rolesRec->inherit);
            
            // Обикаляме всички наследени роли на ролята, която се изтрива
            foreach ($inhDeletingRec as $inhRecId) {
                
                // Премахваме ролята от масива на наследниците
                unset($inheritArr[$inhRecId]);
            }
            
            // Премахваме ролята на родителя от масива с наследените роли на наследниците
            unset($inheritArr[$id]);
            
            // Записваме редактираните данние
            $nRec = new stdClass();
            $nRec->id = $rolesRec->id;
            $nRec->inherit = type_Keylist::fromArray($inheritArr); 

            core_Roles::save($nRec);
        }
    }
    
    
    /**
     * Обновява всички роли при промяна на избраната, която се явява родител
     * 
     * @param integer $id - id' то на записа, който се променя
     * @param type_Keylist $oldInheritRecs - Наследените роли преди да се запишат данните
     * 
     * @access private
     */
    static function updateAllRolesDuringEdit($id, $oldInheritRecs)
    {
        // Роля, която се редактира
        $editingRec = core_Roles::fetch($id);
        
        // Масив с наследениете роли, след промяната 
        $newInheritRecsArr = type_Keylist::toArray($editingRec->inherit);

        // Масив с наследените роли, преди промяната
        $oldInheritRecsArr = type_Keylist::toArray($oldInheritRecs);
        
        // Масив с изтритите роли
        $delInheritRecsArr = array_diff($oldInheritRecsArr, $newInheritRecsArr);
        
        // Масив с добавените роли
        $addInheritRecsArr = array_diff($newInheritRecsArr, $oldInheritRecsArr);
        
        // Заяка към таблицата с ролите
        $query = core_Roles::getQuery();
        
        // Вземаме всички роли, които наследяват текущата
        $query->where("#inherit LIKE '%|{$id}|%'");
        
        // Обикаляме всички открити роли /наследници/
        while ($rolesRec = $query->fetch()) {
            
            // Ролите на съответния потребител
            $uRolesArr = type_Keylist::toArray($rolesRec->inherit);
            
            // Обикаляме ролите, които трябва да се изтрият
            foreach ($delInheritRecsArr as $delVal) {
                
                // Изтриваме ролята
                unset($uRolesArr[$delVal]);
            }
            
            // Обикаляме ролите, които трябва да се добавят
            foreach ($addInheritRecsArr as $addVal) {
                
                // Добавяме ролята
                $uRolesArr[$addVal] = $addVal;
            }
            
            // Записваме редактираните данние
            $nRec = new stdClass();
            $nRec->id = $rolesRec->id;
            $nRec->inherit = type_Keylist::fromArray($uRolesArr); 

            core_Roles::save($nRec);
        }
    }
}