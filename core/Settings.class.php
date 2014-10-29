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
//    protected $canAdd = 'no_one';
    protected $canAdd = 'debug';
    
    
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
        $this->FLD('userOrRole', 'userOrRole', 'caption=Потребител/и');
        $this->FLD('data', 'blob(serialize, compress)', 'caption=Потребител/и');
        
        $this->setDbUnique('key, userOrRole');
    }
    
    
    /**
     * Добавя бутон в тулбара, който отваря формата за персонализиране
     * 
     * @param core_Toolbar $toolbar
     * @param string $key
     * @param string $className
     * @param integer $userOrRole
     * @param string $title
     */
    public static function addBtn(core_Toolbar $toolbar, $key, $className, $userOrRole = NULL, $title = 'Персонализиране')
    {
        $url = self::getModifyUrl($key, $className, $userOrRole);
        
        // Добавяме бутона, който сочи към екшъна за персонализиране
        $toolbar->addBtn($title, $url, 'ef_icon=img/16/customize.png,row=2');
    }
    
    
    /**
     * 
     * 
     * @param string $key
     * @param string $className
     * @param integer $userOrRole
     */
    public static function getModifyUrl($key, $className, $userOrRole = NULL)
    {
        // Защитаваме get параметрите
        Request::setProtected(array('_key', '_className', '_userOrRoles'));
        
        $url = toUrl(array('core_Settings', 'modify', '_key' => $key, '_className' => $className, '_userOrRoles' => $userOrRole, 'ret_url' => TRUE));
        
        return $url;
    }
    
    
    /**
     * Екшън за модифициране на данни
     */
    protected function act_Modify()
    {
        // Очакваме да е логнат потребител
        requireRole('user');
        
        // Необходими стойности от URL-то
        $key = Request::get('_key');
        $className = Request::get('_className');
        $userOrRoles = Request::get('_userOrRoles');
        
        // Инстанция на класа, който е подаден кат
        $class = cls::get($className);
        
        // Очакваме да има права за модифициране на записа за съответния потребител
        expect($class->canModifySettings($key, $userOrRoles));
        
        // Създаваме празна форма
        $form = cls::get('core_Form');
        
        // Добавяме необходимите полета
        $form->FNC('_userOrRole', 'userOrRole', 'caption=Потребител, input=input, silent', array('attr' => array('onchange' => "addCmdRefresh(this.form);this.form.submit()")));
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
        
        $form->title = 'Персонализиране';
        
        // Вземаме стойностите за този потребител/роля
        $valsArr = self::fetchKeyNoMerge($key, $form->rec->_userOrRole);
        
        // Добавяме стойностите по подразбиране
        foreach ($valsArr as $valKey => $val) {
            $form->setDefault($valKey, $val);
        }
        
        // Извикваме интерфейсната функция
        $class->prepareForm($form);
        
        // Ако формата е рефрешната
        if (($form->cmd == 'refresh')) {
            
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
        
        // Инпутваме формата
        $form->input();
        
        // Ако няма грешки във формата
        if ($form->isSubmitted()) {
            
            // Очакваме да има права за модифицирана на съответния запис
            expect($class->canModifySettings($key, $form->rec->userOrRoles));
            
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
                
                // Ако няма стойност или стойността е default за enum поле, да се премахне от масива
                if ((!$value) || ($value == 'default' && ($form->fields[$valKey]->type instanceof type_Enum))) {
                    unset($recArr[$valKey]);
                }
            }
            
            // Записваме данните
            self::setValues($key, (array)$recArr, $userOrRole);
            
            return new Redirect($retUrl);
        }
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close16.png');
        
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
    protected static function prepareKey($key)
    {
        $key = str::convertToFixedKey($key, 16, 4);
        
        return $key;
    }
    
    
    /**
     * Записва стойностите за ключа и потребителя/роля
     * 
     * @param string $key
     * @param array $valArr
     * @param integer $userOrRole
     */
    protected static function setValues($key, $valArr, $userOrRole = NULL)
    {
        $userOrRole = self::prepareUserOrRole($userOrRole);
        
        // Ограничаваме дължината на ключа
        $key = self::prepareKey($key);
        
        // Стария запис
        $oldRec = static::fetch(array("#key = '[#1#]' AND #userOrRole = '{$userOrRole}'", $key));
        
        // Ако няма стойности, изтриваме записа
        if (!$valArr) {
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
     * Взема записите само за зададения потребител/роля
     * 
     * @param string $key
     * @param integer $userOrRole
     * 
     * @return array
     */   
    protected function fetchKeyNoMerge($key, $userOrRole = NULL)
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
     * Подготвяме потребителя или ролята
     * 
     * @param integer $userOrRole
     * 
     * @return integer
     */
    function prepareUserOrRole($userOrRole)
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
           
        // Рендираме изгледа
        $res = $data->cClass->renderWrapping($tpl, $data);
        
        // За да не се изпълнява по - нататък
        return FALSE;
    }
}
