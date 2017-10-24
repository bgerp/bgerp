<?php


/**
 * Персонализиране на обект от страна на потребителя
 *
 * @category  bgerp
 * @package   custom
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @deprecated
 */
class custom_Settings extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Персонализиране";
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'debug';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'no_one';
    

    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created';
    
    
    /**
     * Кой може да модофицира
     */
    var $canModify = 'powerUser';
    
    
    /**
     * Кой може да модифицира по-подразбиране за всички
     */
    var $canModifydefault = 'admin';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('userId', 'user', 'caption=Потрбител, input=none');
        $this->FLD('classId', 'class(interface=custom_SettingsIntf)', 'caption=Обект->Клас, silent, input=none');
        $this->FLD('objectId', 'int', 'caption=Обект->ID, silent, input=none');
        $this->FLD('property', 'varchar(32)', 'caption=Свойство->Име, input=none');
        $this->FLD('value', 'varchar(256)', 'caption=Свойство->Стойност, input=none');
        
        $this->setDbUnique('userId, classId, objectId, property');
    }
    
    
    /**
     * Добавя бутон в тулбара, който отваря формата за персонализиране
     * 
     * @param core_Toolbar $toolbar
     * @param integer $classId
     * @param integer $objectId
     * @param string $title
     */
    static function addBtn(core_Toolbar $toolbar, $classId, $objectId, $title = 'Персонализиране')
    {
        // Защитаваме get параметрите
        Request::setProtected(array('classId', 'objectId'));
        
        // Добавяме бутона, който сочи към екшъна за персонализиране
        $toolbar->addBtn($title, array('custom_Settings', 'modify', 'classId' => $classId, 'objectId' => $objectId, 'ret_url' => TRUE), 'ef_icon=img/16/customize.png,row=2,class=fright');
    }
    
    
    /**
     * Връща масив с потребители и стойностите за съответното свойство
     * 
     * @param integer $classId
     * @param integer $objectId
     * @param string $property
     * @param string $value
     */
    static function fetchUsers($classId, $objectId, $property, $value = NULL)
    {
        $userPropertiesArr = array();
        
        // Всички записи за класа и обекта
        // Със съответното свойство
        $query = static::getQuery();
        $query->where("#classId = '{$classId}'");
        $query->where("#objectId = '{$objectId}'");
        $query->where(array("#property = '[#1#]'", $property));
        
        // Ако е подадена стойност за свойствовото
        if ($value) {
            $query->where(array("#value = '[#1#]'", $value));
        }
        
        while ($rec = $query->fetch()) {
            
            // Добавяме името на свойството и стойността му в масива
            $userPropertiesArr[$rec->userId] = $rec->value;
        }
        
        return $userPropertiesArr;
    }
    
    
    /**
     * Въща масив с всички стойности на свойствата за даден клас и документ за съответния потребител
     * 
     * @param integer $classId
     * @param integer $objectId
     * @param integer $userId
     * 
     * @return array
     */
    static function fetchValues($classId, $objectId, $userId = NULL, $forAll = TRUE)
    {
        $propertiesArr = array();
        
        // Ако не е подаден потребител, да е за текущия
        if (!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        // Взема всички свойства за обекта от класа
        // За съответния потребител
        // Или за всички потребители
        // Като първо са за текущия потребител
        $query = static::getQuery();
        $query->where("#classId = '{$classId}'");
        $query->where("#objectId = '{$objectId}'");
        $query->where("#userId = '{$userId}'");
        
        if ($forAll) {
            $query->orWhere("#userId = '-1'");
        }
        
        while ($rec = $query->fetch()) {
            
            // Ако е добавен в масива и в момента се опитваме да добавим настойки по подразбиране
            if (isset($propertiesArr[$rec->property]) && ($rec->userId == -1)) continue;
            
            // Добавяме името на свойството и стойността му в масива
            $propertiesArr[$rec->property] = $rec->value;
        }
        
        return $propertiesArr;
    }
    
    
    /**
     * Екшън за модифициране на настройките на обекта
     */
    function act_Modify()
    {
        // Трябва да има права за модифициране
        $this->requireRightFor('modify');
        
        // id на класа
        $classId = Request::get('classId');
        
        // id на обекта
        $objectId = Request::get('objectId', 'int');
        
        // Очакваме да има подаден обейт и клас
        expect(($classId && $objectId));
        
        // Инстанция на класа
        $class = cls::get($classId);
        
        // Запис за отози обект
        $cRec = $class->fetch($objectId);
        
        // Трябва да има такъв запис
        expect($cRec);
    
        // Опитваме се да определим ret_url-то
        $retUrl = getRetUrl();
        if (!$retUrl) {
            if (($class instanceof core_Master) && $class->haveRightFor('single', $cRec)) {
                $retUrl = array($class, 'single', $cRec->id);
            } elseif (($class instanceof core_Manager) && $class->haveRightFor('list', $cRec)) {
                $retUrl = array($class, 'list', $cRec->id);
            } else {
                $retUrl = array($class);
            }
        }
        
        // Ако в класа не са дефинирани права за модифициране
        if (!$class->canModify) {
            
            // Използваме правата зададени в този клас
            $class->canModify = $this->canModify;
        }
        
        // Очакваме да има права за модифициране на даден запис
        $class->requireRightFor('modify', $cRec);
        
        // Създаваме формата за модифициране
        $form = cls::get('core_Form');
        $form->title = 'Персонализиране';
        
        // Добавяме функционални полета, към формата
        $form->FNC('classId', 'class(interface=custom_SettingsIntf)', 'input=none, silent');
        $form->FNC('objectId', 'int', 'input=none, silent');
        $form->FNC('userId', 'user', 'input=none');
        
        // Текущия потребител да е избран по подразбиране
        $currUserId = core_Users::getCurrent();
        $form->setDefault('userId', $currUserId);
        
        // Инпутваме silent полетата
        $form->input(NULL, TRUE);
        
        // Вземаме всички зададени свойства от текущия потребител за текущия обект и лице
        $propertiesArrFromUser = static::fetchValues($form->rec->classId, $form->rec->objectId, $currUserId, FALSE);
        
        // Сетваме по подразбиране стойностите на зададените свойства
        foreach ((array)$propertiesArrFromUser as $property => $value) {
            $form->setDefault($property, $value);
        }
        
        // Извикваме интерфейсния метод за подготвяне на формата
        $class->prepareCustomizationForm($form);
        
        // Вземаме всички зададени свойства от админа потребител за текущия обект и лице
        $propertiesArrFromAdmin = static::fetchValues($form->rec->classId, $form->rec->objectId, -1, FALSE);
        
        // Ако сме в мобилен режим, да не е хинт
        $paramType = Mode::is('screenMode', 'narrow') ? 'unit' : 'hint';
        
        // Сетваме по подразбиране стойностите на зададените свойства
        foreach ((array)$propertiesArrFromAdmin as $property => $value) {
            
            // Вербалната стойност
            $verbalVal = $form->getFieldType($property, FALSE)->toVerbal($value);
            
            // Променяме хинта
            $form->setParams($property, array($paramType => '|*<br>|По подразбиране|*: ' . "{$verbalVal}"));
        }
        
        // Инпутваме данните
        $form->input();
        
        // Ако формата е събмитната
        if ($form->isSubmitted()) {
            
            // Извикваме интерфейсния метод за проверка на данните
            $class->checkCustomizationForm($form);
        }
        
        // Ако няма грешки във формата е изпратена
        if ($form->isSubmitted()) {
            
            // Ако е натиснат бутона да се запиши за всички
            if ($form->cmd == 'save_default') {
                
                // Променяме id-то
                $form->rec->userId = -1;
            }
            
            // Премхаваме от масива, данните, които не са въведени от интерфейса
            $recsArr = (array)$form->rec;
            unset($recsArr['userId']);
            unset($recsArr['classId']);
            unset($recsArr['objectId']);
            
            // Обхождаме останалите данни от масива
            foreach ((array)$recsArr as $property => $value) {
                
                // Ако имат стойност по подразбиране
                if ($value == 'default' || $value == '' || is_null($value)) {
                    
                    // 
                    $allPropertiesArr = array_merge((array)$propertiesArrFromAdmin, (array)$propertiesArrFromUser);
                    
                    // Ако има запис в модела
                    if (isset($allPropertiesArr[$property])) {
                        
                        // Изтриваме всички запсиси за съответния потребител
                        $deleteWhere = "#classId = '{$form->rec->classId}' AND #objectId = '{$form->rec->objectId}' AND #property = '[#1#]' AND (#userId = '{$form->rec->userId}'";
                        
                        // Ако ще се изтиват за "всички"
                        if ($form->rec->userId == -1) {
                            
                            // Да се изтрие и за текущия потребител
                            $deleteWhere .= " OR #userId = '{$currUserId}'";
                        }
                        $deleteWhere .= ")";
                        $this->delete(array($deleteWhere, $property));
                    }
                } else {
                    
                    // Създаваме запис със стойностите и свойстваме
                    $nRec = new stdClass();
                    $nRec->classId = $form->rec->classId;
                    $nRec->objectId = $form->rec->objectId;
                    $nRec->userId = $form->rec->userId;
                    $nRec->property = $property;
                    $nRec->value = $value;
                    
                    // Заменяме предишните данни
                    $this->save($nRec, NULL, 'REPLACE');
                    
                    // Ако ще се добавя за "всички" потребители
                    if ($form->rec->userId == -1) {
                        
                        // Изтриваме настройките за текущия потребител
                        $this->delete(array("#classId = '{$form->rec->classId}' AND #objectId = '{$form->rec->objectId}' AND #property = '[#1#]' AND #userId = '{$currUserId}'", $property));
                    }
                }
            }
            
            return new Redirect($retUrl);
        }
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png');
        
        // Ако в класа не са дефинирани права за модифициране по подразбиране
        if (!$class->canModifydefault) {
            
            // Използваме правата зададени в този клас
            $class->canModifydefault = $this->canModifydefault;
        }
        
        // Ако има права за модифициране за всички
        if ($class->haveRightFor('modifydefault')) {
            $form->toolbar->addSbBtn('Запис по подразбиране', 'save_default',
            	'id=save-default, order=100,class=fright', 'ef_icon = img/16/disk.png, warning=Наистина ли искате да промените настройките по-подразбиране за всички?');
        }
        
        // Добавяме класа
        $data = new stdClass();
        $data->cClass = $class;
        
        return $this->renderWrapping($form->renderHtml(), $data);
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
