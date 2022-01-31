<?php


/**
 * Персонализиране на обект от страна на потребителя
 *
 * @category  ef
 * @package   core
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class core_Settings extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Персонализиране';
    
    
    /**
     * Кой има право да го променя?
     */
    protected $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    protected $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    protected $canList = 'debug';
    
    
    /**
     * Кой има право да изтрива?
     */
    protected $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Modified,plg_SystemWrapper';
    
    
    /**
     * Кой може да модифицира
     */
//    protected $canModify = 'powerUser';
    
    
    /**
     * Кой може да модифицира по-подразбиране за всички
     */
//    protected $canModifydefault = 'admin';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('key', 'varchar(16)', 'caption=Ключ');
        $this->FLD('objectId', 'int', 'caption=Обект, input=none');
        $this->FLD('userOrRole', 'userOrRole(rolesType=team, showRolesFirst=admin)', 'caption=Потребител/Роля');
        $this->FLD('data', 'blob(serialize, compress)', 'caption=Данни');
        
        $this->setDbUnique('key, objectId, userOrRole');
        
        $this->setDbIndex('key');
        $this->setDbIndex('key, objectId');
        $this->setDbIndex('key, userOrRole');
        $this->setDbIndex('key, userOrRole, objectId');
        $this->setDbIndex('userOrRole');
    }
    
    
    /**
     * Добавя бутон в тулбара, който отваря формата за персонализиране
     *
     * @param core_Toolbar $toolbar
     * @param string       $key
     * @param string       $className
     * @param int|NULL     $userOrRole
     * @param string       $title
     * @param array        $params
     */
    public static function addBtn(core_Toolbar $toolbar, $key, $className, $userOrRole = null, $title = 'Персонализиране', $params = array())
    {
        $url = self::getModifyUrl($key, $className, $userOrRole);
        
        // Добавяме бутона, който сочи към екшъна за персонализиране
        $toolbar->addBtn($title, $url, 'ef_icon=img/16/customize.png', $params);
    }
    
    
    /**
     * Връща URL, което сочи към модифициране на записа
     *
     * @param string   $key
     * @param string   $className
     * @param int|NULL $userOrRole
     *
     * @return string
     */
    public static function getModifyUrl($key, $className, $userOrRole = null)
    {
        $userOrRole = self::prepareUserOrRole($userOrRole);
        
        $userOrRole = type_UserOrRole::getOptVal($userOrRole);
        
        // Защитаваме get параметрите
        Request::setProtected(array('_key', '_className', '_userOrRole'));
        
        $url = toUrl(array('core_Settings', 'modify', '_key' => $key, '_className' => $className, '_userOrRole' => $userOrRole, 'ret_url' => true));
        
        return $url;
    }
    
    
    /**
     * Връща всички данни отговарящи за ключа, като ги мърджва.
     * С по-голям приоритет са данните въведени за текущия потребител
     *
     * @param string      $key          - Ключа
     * @param int|NULL    $userOrRole   - Роля или потребител
     * @param bool        $fetchForUser - Дали да се фечва и за потребителия
     * @param string|NULL $type         - Име на роля
     *
     * @return array
     */
    public static function fetchKey($key, $userOrRole = null, $fetchForUser = true, $type = null)
    {
        // Подготвяме ключа и потребителя/групата
        $userOrRole = self::prepareUserOrRole($userOrRole);
        
        list(, $objectId) = explode('::', $key);
        
        $key = self::prepareKey($key);
        
        static $allResArr = array();
        
        // Ако стойността е извличана преди, връщаме я
        $keyHash = md5($key . '|' . $userOrRole . '|' . $fetchForUser . '|' . $type . '|' . $objectId);
        if (isset($allResArr[$keyHash])) {
            
            return $allResArr[$keyHash];
        }
        
        $allResArr[$keyHash] = array();
        
        $rolesArr = array();
        $rolesArrSysId = array();
        $orToPrevious = false;
        
        $query = self::getQuery();
        
        if (isset($objectId)) {
            $query->where(array("#key = '[#1#]' AND #objectId = '[#2#]'", $key, $objectId));
        } else {
            $query->where(array("#key = '[#1#]'", $key));
        }
        
        // Ако е потребител
        if ($userOrRole > 0) {
            
            // Всички групи, в които участва текущия потребител
            if ($type) {
                $rolesList = core_Users::getUserRolesByType($userOrRole, $type);
            } else {
                $rolesList = core_Users::getRoles($userOrRole);
            }
            
            $rolesArr = type_Keylist::toArray($rolesList);
            
            if ($fetchForUser) {
                // Също и текущия потребител
                $query->where("#userOrRole = {$userOrRole}");
                $orToPrevious = true;
            }
        } elseif ($userOrRole < 0) {
            
            // Ако е група
            
            // Всички роли, които наследява групата
            $roleId = type_UserOrRole::getRoleIdFromSys($userOrRole);
            if ($roleId) {
                $rolesArr = core_Roles::expand($roleId);
            }
        }
        
        // Добавяме ролята за цялата система
        $rolesArr[0] = 0;
        
        // Добавяме всички групи в условието
        if ($rolesArr) {
            $rolesArrSysId = array_map(array('type_UserOrRole', 'getSysRoleId'), $rolesArr);
            
            $uWhere = '#userOrRole IN (' . implode(',', $rolesArrSysId) . ')';
            
            if ($orToPrevious) {
                $query->orWhere($uWhere);
            } else {
                $query->where($uWhere);
            }
        }
        
        // С по-голям приоритет са данните въведени от потребителя
        $query->orderBy('userOrRole', 'DESC');
        
        // Обхождаме всички записи и добавяме в масива
        while ($rec = $query->fetch()) {
            if (!$rec->data) {
                continue;
            }
            foreach ((array) $rec->data as $property => $val) {
                if (isset($allResArr[$keyHash][$property])) {
                    continue;
                }
                $allResArr[$keyHash][$property] = $val;
            }
        }
        
        return $allResArr[$keyHash];
    }
    
    
    /**
     * Връща данните за всички потребители, които имат някаква стойност
     *
     * @param string $key
     * @param string $property
     * @param string $value
     *
     * @return array
     */
    public static function fetchUsers($key, $property = null, $value = null)
    {
        list(, $objectId) = explode('::', $key);
        
        // Подготвяме ключа
        $key = self::prepareKey($key);
        
        // Ако данните са били извлечени, само ги връщаме
        $hashStr = md5($key . '|' . $property . '|' . $value . '|' . $objectId);
        static $resArr = array();
        if (isset($resArr[$hashStr])) {
            
            return $resArr[$hashStr];
        }
        
        // Вземаме всички роли и потребителите, които ги имат
        $userRolesArr = core_Users::getRolesWithUsers();
        
        $fetched = array();
        
        $query = self::getQuery();
        
        if (isset($objectId)) {
            $query->where(array("#key = '[#1#]' AND #objectId = '[#2#]'", $key, $objectId));
        } else {
            $query->where(array("#key = '[#1#]'", $key));
        }
        
        // С по-голям приоритет са данните въведени от потребителя
        $query->orderBy('userOrRole', 'DESC');
        
        while ($rec = $query->fetch()) {
            if (!$rec->data) {
                continue;
            }
            
            // Определяме потребителите
            if ($rec->userOrRole < 0) {
                $roleId = type_UserOrRole::getRoleIdFromSys($rec->userOrRole);
                $userArr = $userRolesArr[$roleId];
            } else {
                $userArr = arr::make($rec->userOrRole, true);
            }
            
            // Обхождаме резултатите
            foreach ((array) $rec->data as $prop => $val) {
                
                // Ако е зададено точно определено свойство, извличаме само него
                $use = true;
                if ($property) {
                    if ($prop != $property) {
                        $use = false;
                    }
                }
                
                // Ако е зададено точно опретелена стойност, извличаме само него
                if ($use && $value) {
                    if ($val != $value) {
                        $use = false;
                    }
                }
                
                // Обхождаме всички потребители и добавяме стойности и свойства за тях
                foreach ((array) $userArr as $userId) {
                    
                    // Ако има стойност, да не се добавя повторно
                    if (isset($fetched[$userId][$prop])) {
                        continue;
                    }
                    
                    // Добавяме в масива с извлечените
                    $fetched[$userId][$prop] = $val;
                    
                    // Ако не трябва да се добавя
                    if (!$use) {
                        continue;
                    }
                    
                    // Добавяме към резултатния масив
                    $resArr[$hashStr][$userId][$prop] = $val;
                }
            }
        }
        
        return $resArr[$hashStr];
    }
    
    
    /**
     * Взема записите само за зададения потребител/роля
     *
     * @param string   $key
     * @param int|NULL $userOrRole
     *
     * @return array
     */
    public static function fetchKeyNoMerge($key, $userOrRole = null)
    {
        $dataVal = array();
        
        list(, $objectId) = explode('::', $key);
        
        $key = self::prepareKey($key);
        
        $userOrRole = self::prepareUserOrRole($userOrRole);
        
        if (isset($objectId)) {
            $rec = self::fetch(array("#key = '[#1#]' AND #userOrRole = '[#2#]' AND #objectId = '[#3#]'", $key, $userOrRole, $objectId));
        } else {
            $rec = self::fetch(array("#key = '[#1#]' AND #userOrRole = '[#2#]'", $key, $userOrRole));
        }
        
        // Ако има запис връщаме масива с данните
        if ($rec) {
            $dataVal = (array) $rec->data;
        }
        
        return $dataVal;
    }
    

    /**
     * Екшън за модифициране на данни
     */
    protected function act_Modify()
    {
        // Очакваме да е логнат потребител
        requireRole('user');
        
        Request::setProtected(array('_key, _className, _userOrRole'));
        
        // Необходими стойности от URL-то
        $key = Request::get('_key');
        $className = Request::get('_className');
        $userOrRole = Request::get('_userOrRole');
        
        // Инстанция на класа, който е подаден кат
        $class = cls::get($className);
        
        // Създаваме празна форма
        $form = cls::get('core_Form');
        $form->title = 'Персонализиране';
        
        // Добавяме необходимите полета
        $form->FNC('_userOrRole', 'userOrRole(rolesType=team, showRolesFirst=admin)', 'caption=Потребител, input=input, silent', array('attr' => array('onchange' => 'addCmdRefresh(this.form);this.form.submit()')));
        $form->FNC('_key', 'varchar', 'input=none, silent');
        $form->FNC('_className', 'varchar', 'input=none, silent');
        
        // Опитваме се да определим ret_url-то
        $retUrl = getRetUrl();
        if (!$retUrl) {
            if ($class->haveRightFor('list')) {
                $retUrl = array($class, 'list');
            }
        }
        
        // Инпутваме silent полетата, за да се попълнята
        $form->input(null, 'silent');
        
        // Очакваме да има права за модифициране на записа за съответния потребител
        expect($class->canModifySettings($key, $form->rec->_userOrRole));
        
        // Вземаме стойностите за този потребител/роля
        $valsArr = self::fetchKeyNoMerge($key, $form->rec->_userOrRole);

        // Добавяме стойностите по подразбиране
        foreach ($valsArr as $valKey => $val) {
            $form->setDefault($valKey, $val);
        }

        // Извикваме интерфейсната функция
        $class->prepareSettingsForm($form);

        $currCu = core_Users::getCurrent();
        
        $cuForAll = null;
        if (haveRole($form->fields['_userOrRole']->type->params['rolesForAllSysTeam'])) {
            $cuForAll = $currCu;
        }

        // Ако в някое поле е зададено, че това е опция за всички потребители и кой може да го променя
        $uSettingForAllArr = array();
        $sForAllFieldArr = $form->selectFields('#settingForAll');
        foreach ($sForAllFieldArr as $fName => $fOpt) {
            if (!isset($fOpt->settingForAll)) {
                continue;
            }
            
            if (trim($fOpt->settingForAll) && ($fOpt->settingForAll != 'settingForAll')) {
                $uSettingForAllArr[$fName] = type_Keylist::toArray($fOpt->settingForAll);
                if ($cuForAll) {
                    $uSettingForAllArr[$fName][$cuForAll] = $cuForAll;
                }
            } else {
                $uSettingForAllArr[$fName] = '*';
            }
        }
        
        // Ключа може да е променен в интерфейсния метод
        $key = $form->rec->_key;

        // Ако е избран потребител, а не роля
        if ($form->rec->_userOrRole > 0) {
            
            // Настройките по-подразбиране за потребителя, без неговите промени
            $mergeValsArr = self::fetchKey($key, $form->rec->_userOrRole, false, 'team');
            
            if ($mergeValsArr) {
                $defaultStr = 'По подразбиране|*: ';
                
                // Ако сме в мобилен режим, да не е хинт
                $paramType = Mode::is('screenMode', 'narrow') ? 'unit' : 'hint';
                
                foreach ((array) $mergeValsArr as $valKey => $val) {
                    if (!$form->fields[$valKey]->type) {
                        continue;
                    }
                    
                    $defVal = $form->fields[$valKey]->type->toVerbal($val);
                    
                    // Сетваме стойност по подразбиране
                    $form->setParams($valKey, array($paramType => $defaultStr . $defVal));
                }
            }
        }
        
        // Ако формата е рефрешната
        if (($form->cmd == 'refresh')) {
            
            // Вкарваме данните в рекуеста за да ги има в `$form->rec` след инпута
            
            // Вкарваме всички записи от стойностите на rec в рекуеста
            $recsArr = (array) $form->rec;
            unset($recsArr['_userOrRole']);
            unset($recsArr['_key']);
            unset($recsArr['_className']);
            Request::push((array) $recsArr);
            
            // Ако има записани стойности, вкарваме и тях
            if ($valsArr) {
                Request::push($valsArr);
            }
        }
        
        // Стойностите да се инпутват с правата на избрания потребител
        $sudo = false;
        if (($form->rec->_userOrRole > 0) && ($form->rec->_userOrRole != core_Users::getCurrent())) {
            $sudo = core_Users::sudo($form->rec->_userOrRole);
        }
        
        $allSystemId = type_UserOrRole::getAllSysTeamId();
        
        // Задаваме стойностите на полетата и ги забраняваме за промяна, ако е задено и текущия потребителя няма права
        $sudoCu = core_Users::getCurrent();
        if (!empty($uSettingForAllArr)) {
            $sForAll = self::fetchKeyNoMerge($key, $allSystemId);
            foreach ($uSettingForAllArr as $fName => $users) {
                if (isset($sForAll[$fName])) {
                    $form->setDefault($fName, $sForAll[$fName]);
                }
                
                if (is_array($users)) {
                    if (!$users[$sudoCu] && !$users[$currCu] && ($allSystemId != $form->rec->_userOrRole)) {
                        $form->setReadOnly($fName);
                    }
                }
            }
        }

        try {
            // Инпутваме формата
            $form->input();
        } catch (core_exception_Expect $e) {
            if ($sudo) {
                core_Users::exitSudo();
                $sudo = false;
            }
        }

        if ($sudo) {
            core_Users::exitSudo();
        }
        
        // Ако няма грешки във формата
        if ($form->isSubmitted()) {
            
            // Очакваме да има права за модифицирана на съответния запис
            expect($class->canModifySettings($key, $form->rec->_userOrRole));
            
            // Извикваме интерфейсната функция за проверка на формата
            $class->checkSettingsForm($form);
        }
        
        // Ако няма грешки във формата
        if ($form->isSubmitted()) {
            
            // Масив с всички данни
            $recArr = (array) $form->rec;
            
            // Вземаме ключа и потребителя и премахваме необходимите стойности
            $key = $recArr['_key'];
            unset($recArr['_key']);
            $userOrRole = $recArr['_userOrRole'];
            unset($recArr['_userOrRole']);
            unset($recArr['_className']);
            
            $sForAllValArr = null;

            // Премахваме всички празни стойности или default от enum
            foreach ((array) $recArr as $valKey => $value) {

                // Ако тази опция е за всички потребители
                if (!empty($uSettingForAllArr) && $uSettingForAllArr[$valKey] && ($allSystemId != $form->rec->_userOrRole)) {
                    $sForAllValArr[$valKey] = $value;
                    unset($recArr[$valKey]);
                }
                
                $instanceOfEnum = (boolean) ($form->fields[$valKey]->type instanceof type_Enum);
                
                // Ако няма стойност или стойността е default за enum поле, да се премахне от масива
                if ((!$value && !$instanceOfEnum && ($value !== 0)) || ($value == 'default' && $instanceOfEnum)) {
                    unset($recArr[$valKey]);
                }

                // Ако е ричтекст сравняваме уеднаквяваме новия ред преди да сравним
                if ($form->fields[$valKey]->type instanceof type_Richtext) {
                    $origVals = core_Packs::getAllConfigVals();
                    $recComp = preg_replace('/(\r\n)|(\n\r)/', "\n", $recArr[$valKey]);
                    $valComp = preg_replace('/(\r\n)|(\n\r)/', "\n", $origVals[$valKey]);

                    if ($recComp == $valComp) {
                        unset($recArr[$valKey]);
                    }
                }
            }

            // Записваме данните
            self::setValues($key, (array) $recArr, $userOrRole);
            
            // Записване данните, които се отнасят за всички потребители
            if (isset($sForAllValArr)) {
                $oldSArr = self::fetchKeyNoMerge($key, $allSystemId);
                
                foreach ($sForAllValArr as $k => $v) {
                    $oldSArr[$k] = $v;
                }
                
                self::setValues($key, (array) $oldSArr, $allSystemId);
            }
            
            list(, $objectId) = explode('::', $key);
            $pKey = self::prepareKey($key);
            if (isset($objectId)) {
                $rec = self::fetch(array("#key = '[#1#]' AND #userOrRole = '[#2#]' AND #objectId = '[#3#]'", $pKey, $userOrRole, $objectId));
            } else {
                $rec = self::fetch(array("#key = '[#1#]' AND #userOrRole = '[#2#]'", $pKey, $userOrRole));
            }
            
            $this->logWrite('Промяна на настройките', $rec);
            
            return new Redirect($retUrl);
        }
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png');
        
        // Добавяме класа
        $data = new stdClass();
        $data->cClass = $class;
        
        return $this->renderWrapping($form->renderHtml(), $data);
    }
    
    
    /**
     * Подготвяме ключа, като ограничаваме дължината до 16 символа
     *
     * @param string $key
     *
     * @return string
     */
    public static function prepareKey($key)
    {
        $key = str::convertToFixedKey($key, 16, 4);
        
        return $key;
    }
    
    
    /**
     * Записва стойностите за ключа и потребителя/роля
     *
     * @param string   $key
     * @param array    $valArr
     * @param int|NULL $userOrRole
     * @param bool     $mergeVals
     */
    public static function setValues($key, $valArr, $userOrRole = null, $mergeVals = false)
    {
        $userOrRole = self::prepareUserOrRole($userOrRole);
        
        list(, $objectId) = explode('::', $key);
         
        // Ограничаваме дължината на ключа
        $key = self::prepareKey($key);
        
        if (isset($objectId)) {
            $oldRec = self::fetch(array("#key = '[#1#]' AND #userOrRole = '[#2#]' AND #objectId = '[#3#]'", $key, $userOrRole, $objectId));
        } else {
            $oldRec = self::fetch(array("#key = '[#1#]' AND #userOrRole = '[#2#]'", $key, $userOrRole));
        }
        
        if ($mergeVals && $oldRec) {
            $valArr = array_merge((array) $oldRec->data, (array) $valArr);
        }

        // Ако няма стойности, изтриваме записа
        if (!$valArr && $oldRec) {
            self::delete($oldRec->id);
            
            return ;
        }
        
        // Ако няма стар запис
        if (!$oldRec) {
            
            // Създаваме нов
            $nRec = new stdClass();
            $nRec->key = $key;
            $nRec->userOrRole = $userOrRole;
            $nRec->objectId = $objectId;
        } else {
            
            // Използваме стария запис
            $nRec = $oldRec;
        }
        
        $nRec->data = $valArr;
         
        // Записваме новите данни
        self::save($nRec);
    }
    
    
    /**
     * Подготвяме потребителя или ролята
     *
     * @param int|NULL $userOrRole
     *
     * @return int
     */
    protected static function prepareUserOrRole($userOrRole)
    {
        // Ако не е подаден, използваме текущия потребител
        if (!$userOrRole) {
            $userOrRole = core_Users::getCurrent();
        }
        
        // Ако е системата, използваме всички
        if ($userOrRole == -1) {
            $userOrRole = type_UserOrRole::getAllSysTeamId();
        }
        
        return $userOrRole;
    }
    
    
    /**
     * Променяме wrapper' а да сочи към врапера на търсения клас
     *
     * @param core_Mvc $mvc
     * @param core_Et  $res
     * @param core_Et  $tpl
     * @param object   $data
     */
    public function on_BeforeRenderWrapping($mvc, &$res, &$tpl, $data = null)
    {
        if (!$data->cClass) {
            
            return ;
        }
        
        // Ако текущия потребител е контрактор, показваме обвивката на външната част
        if (core_Users::haveRole('partner')) {
            plg_ProtoWrapper::changeWrapper($this, 'cms_ExternalWrapper');
            $mvc->currentTab = 'Профил';
        } else {
            // Рендираме изгледа
            $res = $data->cClass->renderWrapping($tpl, $data);
            
            // За да не се изпълнява по - нататък
            return false;
        }
    }
    
    
    /**
     * Връща масив с всички перонализации за посочената константа
     * Ключовете на масива са потребителите или ролите, а стойностите - стойностите на константата
     *
     * @param string $constName името на константата
     * @param string $key
     *
     * @return array
     */
    public static function fetchPersonalConfig($constName, $key, $userOrRole = null)
    {
        $res = array();
        $key = self::prepareKey($key); 
        $query = self::getQuery();
        $query->where(array("#key = '[#1#]'", $key));
        
       
        $query->orderBy('userOrRole', 'DESC');
        
        if($userOrRole === null) {
            $userOrRole = core_Users::getCurrent();
        }

        if(is_int($userOrRole)) {
            $query->where("#userOrRole = {$userOrRole}");
        }

        while ($rec = $query->fetch()) {
            if (isset($rec->data[$constName])) {
                $res[$rec->userOrRole] = $rec->data[$constName];
            }
        }
        
        return $res;
    }
}
