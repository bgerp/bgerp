<?php


/**
 * Персонализиране на обект от страна на потребителя
 *
 * @category  ef
 * @package   core
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_Settings extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = "Персонализиране";
    
    
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
        $this->FLD('userOrRole', 'userOrRole(rolesType=team)', 'caption=Потребител/и');
        $this->FLD('data', 'blob(serialize, compress)', 'caption=Потребител/и');
        
        $this->setDbUnique('key, userOrRole');
    }
    
    
    /**
     * Добавя бутон в тулбара, който отваря формата за персонализиране
     * 
     * @param core_Toolbar $toolbar
     * @param string $key
     * @param string $className
     * @param integer|NULL $userOrRole
     * @param string $title
     * @param array $params
     */
    public static function addBtn(core_Toolbar $toolbar, $key, $className, $userOrRole = NULL, $title = 'Персонализиране', $params = array())
    {
        $url = self::getModifyUrl($key, $className, $userOrRole);
        
        // Добавяме бутона, който сочи към екшъна за персонализиране
        $toolbar->addBtn($title, $url, 'ef_icon=img/16/customize.png', $params);
    }
    
    
    /**
     * Връща URL, което сочи към модифициране на записа
     * 
     * @param string $key
     * @param string $className
     * @param integer|NULL $userOrRole
     * 
     * @return string
     */
    public static function getModifyUrl($key, $className, $userOrRole = NULL)
    {
        $userOrRole = self::prepareUserOrRole($userOrRole);
        
        $userOrRole = type_UserOrRole::getOptVal($userOrRole);
        
        // Защитаваме get параметрите
        Request::setProtected(array('_key', '_className', '_userOrRole'));
        
        $url = toUrl(array('core_Settings', 'modify', '_key' => $key, '_className' => $className, '_userOrRole' => $userOrRole, 'ret_url' => TRUE));
        
        return $url;
    }
    
    
    /**
     * Връща всички данни отговарящи за ключа, като ги мърджва.
     * С по-голям приоритет са данните въведени за текущия потребител
     * 
     * @param string $key - Ключа
     * @param integer|NULL $userOrRole - Роля или потребител
     * @param boolean $fetchForUser - Дали да се фечва и за потребителия
     * @param string|NULL $type - Име на роля
     * 
     * @return array
     */
    public static function fetchKey($key, $userOrRole = NULL, $fetchForUser = TRUE, $type = NULL)
    {
        // Подготвяме ключа и потребителя/групата
        $userOrRole = self::prepareUserOrRole($userOrRole);
        $key = self::prepareKey($key);
        
        static $allResArr = array();
        
        // Ако стойността е извличана преди, връщаме я
        $keyHash = md5($key . '|' . $userOrRole . '|' . $fetchForUser . '|' . $type);
        if (isset($allResArr[$keyHash])) return $allResArr[$keyHash];
        
        $allResArr[$keyHash] = array();
        
        $rolesArr = array();
        $rolesArrSysId = array();
        $orToPrevious = FALSE;
        
        $query = self::getQuery();
        
        $query->where(array("#key = '[#1#]'", $key));
        
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
                $orToPrevious = TRUE;
            }
        } else if ($userOrRole < 0) {
            
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
            
            $uWhere = "#userOrRole IN (" . implode(',', $rolesArrSysId) . ")";
            
            if ($orToPrevious) {
                $query->orWhere($uWhere);
            } else {
                $query->where($uWhere);
            }
        }
        
        // С по-голям приоритет са данните въведени от потребителя
        $query->orderBy('userOrRole', 'DESC');
        
        // Обхождаме всички записи и добавяме в масива
        while($rec = $query->fetch()) {
            if (!$rec->data) continue;
            foreach ((array)$rec->data as $property => $val) {
                if (isset($allResArr[$keyHash][$property])) continue;
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
    public static function fetchUsers($key, $property = NULL, $value = NULL)
    {
        // Подготвяме ключа
        $key = self::prepareKey($key);
        
        // Ако данните са били извлечени, само ги връщаме
        $hashStr = md5($key . '|' . $property . '|' . $value);
        static $resArr = array();
        if (isset($resArr[$hashStr])) return $resArr[$hashStr];
        
        // Вземаме всички роли и потребителите, които ги имат
        $userRolesArr = core_Users::getRolesWithUsers();
        
        $fetched = array();
        
        $query = self::getQuery();
        $query->where(array("#key = '[#1#]'", $key));
        
        // С по-голям приоритет са данните въведени от потребителя
        $query->orderBy('userOrRole', 'DESC');
        
        while ($rec = $query->fetch()) {
            if (!$rec->data) continue;
            
            // Определяме потребителите
            if ($rec->userOrRole < 0) {
                $roleId = type_UserOrRole::getRoleIdFromSys($rec->userOrRole);
                $userArr = $userRolesArr[$roleId];
            } else {
                $userArr = arr::make($rec->userOrRole, TRUE);
            }
            
            // Обхождаме резултатите
            foreach ((array)$rec->data as $prop => $val) {
                
                // Ако е зададено точно определено свойство, извличаме само него
                $use = TRUE;
                if ($property) {
                    if ($prop != $property) $use = FALSE;
                }
                
                // Ако е зададено точно опретелена стойност, извличаме само него
                if ($use && $value) {
                    if ($val != $value) $use = FALSE;
                }
                
                // Обхождаме всички потребители и добавяме стойности и свойства за тях
                foreach ((array)$userArr as $userId) {
                    
                    // Ако има стойност, да не се добавя повторно
                    if (isset($fetched[$userId][$prop])) continue;
                    
                    // Добавяме в масива с извлечените
                    $fetched[$userId][$prop] = $val;
                    
                    // Ако не трябва да се добавя
                    if (!$use) continue;
                    
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
     * @param string $key
     * @param integer|NULL $userOrRole
     * 
     * @return array
     */   
    public static function fetchKeyNoMerge($key, $userOrRole = NULL)
    {
        $dataVal = array();
        
        $key = self::prepareKey($key);
        
        $userOrRole = self::prepareUserOrRole($userOrRole);
        
        // Вземаме записа
        $rec = self::fetch(array("#key = '[#1#]' AND #userOrRole = '{$userOrRole}'", $key));
        
        // Ако има запис връщаме масива с данните
        if ($rec) {
            $dataVal = (array)$rec->data;
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
        $form->FNC('_userOrRole', 'userOrRole(rolesType=team)', 'caption=Потребител, input=input, silent', array('attr' => array('onchange' => "addCmdRefresh(this.form);this.form.submit()")));
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
        $form->input(NULL, 'silent');
        
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
        
        // Ключа може да е променен в интерфейсния метод
        $key = $form->rec->_key;
        
        // Ако е избран потребител, а не роля
        if ($form->rec->_userOrRole > 0) {
        
            // Настройките по-подразбиране за потребителя, без неговите промени
            $mergeValsArr = self::fetchKey($key, $form->rec->_userOrRole, FALSE, 'team');
            
            if ($mergeValsArr) {
                
                $defaultStr = 'По подразбиране|*: ';
                
                // Ако сме в мобилен режим, да не е хинт
                $paramType = Mode::is('screenMode', 'narrow') ? 'unit' : 'hint';
                
                foreach ((array)$mergeValsArr as $valKey => $val) {
                    
                    if (!$form->fields[$valKey]->type) continue;
                    
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
            $recsArr = (array)$form->rec;
            unset($recsArr['_userOrRole']);            
            unset($recsArr['_key']);            
            unset($recsArr['_className']);            
            Request::push((array)$recsArr);
            
            // Ако има записани стойности, вкарваме и тях
            if ($valsArr) {
                Request::push($valsArr);
            }
        }
        
        // Стойностите да се инпутват с правата на избрания потребител
        $sudo = FALSE;
        if (($form->rec->_userOrRole > 0) && ($form->rec->_userOrRole != core_Users::getCurrent())) {
            $sudo = core_Users::sudo($form->rec->_userOrRole);
        }
        
        try {
            // Инпутваме формата
            $form->input();
        } catch (core_exception_Expect $e) {
            
            if ($sudo) {
                core_Users::exitSudo();
                $sudo = FALSE;
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
            $recArr = (array)$form->rec;
            
            // Вземаме ключа и потребителя и премахваме необходимите стойности
            $key = $recArr['_key'];
            unset($recArr['_key']);
            $userOrRole = $recArr['_userOrRole'];
            unset($recArr['_userOrRole']);
            unset($recArr['_className']);
            
            // Премахваме всички празни стойности или defaul от enum
            foreach ((array)$recArr as $valKey => $value) {
                
                $instanceOfEnum = (boolean)($form->fields[$valKey]->type instanceof type_Enum);
                
                // Ако няма стойност или стойността е default за enum поле, да се премахне от масива
                if ((!$value && !$instanceOfEnum) || ($value == 'default' && $instanceOfEnum)) {
                    unset($recArr[$valKey]);
                }
            }
            
            // Записваме данните
            self::setValues($key, (array)$recArr, $userOrRole);
            
            $pKey = self::prepareKey($key);
            $rec = self::fetch(array("#key = '[#1#]' AND #userOrRole = '[#2#]'", $pKey, $userOrRole));
            
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
     * @param string $key
     * @param array $valArr
     * @param integer|NULL $userOrRole
     * @param boolean $mergeVals
     */
    public static function setValues($key, $valArr, $userOrRole = NULL, $mergeVals = FALSE)
    {
        $userOrRole = self::prepareUserOrRole($userOrRole);
        
        // Ограничаваме дължината на ключа
        $key = self::prepareKey($key);
        
        // Стария запис
        $oldRec = static::fetch(array("#key = '[#1#]' AND #userOrRole = '{$userOrRole}'", $key));
        
        if ($mergeVals && $oldRec) {
            $valArr = array_merge((array)$oldRec->data, (array)$valArr);
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
     * @param integer|NULL $userOrRole
     * 
     * @return integer
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
     * @param core_Et $res
     * @param core_Et $tpl
     * @param object $data
     */
    function on_BeforeRenderWrapping($mvc, &$res, &$tpl, $data=NULL)
    {
        if (!$data->cClass) return ;
       
        // Ако текущия потребител е контрактор, показваме обвивката на външната част
        if(core_Users::haveRole('partner')){
        	plg_ProtoWrapper::changeWrapper($this, 'cms_ExternalWrapper');
        	$mvc->currentTab = 'Профил';
        } else {
        	// Рендираме изгледа
        	$res = $data->cClass->renderWrapping($tpl, $data);
        	
        	// За да не се изпълнява по - нататък
        	return FALSE;
        }
    }
}
