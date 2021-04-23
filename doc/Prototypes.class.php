<?php


/**
 * Клас 'doc_Prototypes' - Модел за шаблонни документи
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_Prototypes extends core_Manager
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2,plg_Created,plg_Modified,doc_Wrapper,plg_Rejected';
    
    
    /**
     * Заглавие
     */
    public $title = 'Шаблонни документи';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Шаблонен документ';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'docId,titleCalc,sharedWithRoles,sharedWithUsers,sharedFolders,state,modifiedOn,modifiedBy';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     *
     *  @var string
     */
    public $hideListFieldsIfEmpty = 'sharedWithRoles,sharedWithUsers,sharedFolders';
    
    
    /**
     * Кой може да разглежда
     */
    public $canList = 'admin';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'officer';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'officer';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'officer';
    
    
    /**
     * Кой може да редактира
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да оттегля
     */
    public $canReject = 'no_one';
    
    
    /**
     * Кой може да възстановява
     */
    public $canRestore = 'no_one';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FNC('titleCalc', 'varchar', 'caption=Заглавие');
        $this->FLD('originId', 'key(mvc=doc_Containers)', 'caption=Документ,mandatory,input=hidden,silent');
        $this->FLD('classId', 'class(interface=doc_PrototypeSourceIntf)', 'caption=Документ,mandatory,input=hidden,silent');
        $this->FLD('docId', 'int', 'caption=Документ,mandatory,input=hidden,silent,tdClass=leftColImportant');
        $this->FLD('driverClassId', 'class', 'caption=Документ,input=hidden');
        $this->FLD('sharedFolders', 'key2(mvc=doc_Folders, name=title, allowEmpty)', 'caption=Споделяне->Папка');
        $this->FLD('sharedWithRoles', 'keylist(mvc=core_Roles,select=role,groupBy=type,orderBy=orderByRole)', 'caption=Споделяне->Роли');
        $this->FLD('sharedWithUsers', 'userList', 'caption=Споделяне->Потребители');
        $this->FLD('fields', 'blob(serialize, compress)', 'input=none');
        $this->FLD('state', 'enum(active=Активирано,rejected=Оттеглено,closed=Затворено)', 'caption=Състояние,column=none,input=none,notNull,value=active');
        
        $this->setDbUnique('originId');
        $this->setDbUnique('classId,docId');
        $this->setDbIndex('classId,docId,driverClassId');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        // Кога може да се добавя
        if ($action == 'add' && isset($rec)) {
            if (isset($rec->originId)) {
                $doc = doc_Containers::getDocument($rec->originId);
                
                // Документа трябва да има нужния интерфейс
                if (!$doc->haveInterface('doc_PrototypeSourceIntf')) {
                    $requiredRoles = 'no_one';
                } else {
                    
                    // Да няма шаблон и да не е направил запис в журнала
                    if ($mvc->fetch("#originId = {$rec->originId}")) {
                        $requiredRoles = 'no_one';
                    } elseif (acc_Journal::fetchByDoc($doc->getClassId(), $doc->that)) {
                        $requiredRoles = 'no_one';
                    } elseif (!$doc->canBeTemplate()) {
                        $requiredRoles = 'no_one';
                    } elseif (acc_Items::fetchItem($doc->getInstance(), $doc->that)) {
                        $requiredRoles = 'no_one';
                    }
                }
            } else {
                
                // Ако няма ориджин не може
                $requiredRoles = 'no_one';
            }
        }
        
        // Кога може да се добавя и редактира
        if (($action == 'add' || $action == 'edit') && isset($rec->originId)) {
            if ($requiredRoles != 'no_one') {
                $doc = doc_Containers::getDocument($rec->originId);
                $state = $doc->fetchField('state');
                
                // Трябва потребителя да има достъп до документа
                if (!$doc->haveRightFor('single')) {
                    $requiredRoles = 'no_one';
                
                // И документа да не е оттеглен
                } elseif ($state == 'rejected' || $state == 'closed') {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('createdOn', 'DESC');
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (!empty($data->toolbar->buttons['btnAdd'])) {
            $data->toolbar->removeBtn('btnAdd');
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        
        $origin = doc_Containers::getDocument($form->rec->originId);
        $form->setDefault('classId', $origin->getClassId());
        $form->setDefault('docId', $origin->that);
        
        // Попълване на полето за драйвер за по-бързо търсене
        if ($origin->getInstance() instanceof embed_Manager) {
            $form->setDefault('driverClassId', $origin->fetchField($origin->driverClassField));
        } elseif ($origin->getInstance() instanceof core_Embedder) {
            $form->setDefault('driverClassId', $origin->fetchField($origin->innerClassField));
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        
        if ($form->isSubmitted()) {
            if (isset($rec->sharedFolders)) {
                $origin = doc_Containers::getDocument($rec->originId);
                if (!$origin->getInstance()->canAddToFolder($rec->sharedFolders)) {
                    $form->setError('sharedFolders', 'Документа не може да бъде създаден в избраната папка');
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        // След като се създаде шаблон, оригиналния документ минава в състояние шаблон
        $nRec = (object) array('id' => $rec->docId, 'state' => 'template');
        cls::get($rec->classId)->save_($nRec, 'state');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($fields['-list'])) {
            $origin = doc_Containers::getDocument($rec->originId);
            if(cls::existsMethod($origin->getInstance(), 'getPrototypeTitle')){
                $row->titleCalc = $origin->getPrototypeTitle();
            } else {
                $row->titleCalc = ht::createHint($origin->getTitleById(), 'Документа вече не може да бъде шаблонен', 'error', false);
            }

            $row->docId = $origin->getLink(0);
            $row->ROW_ATTR['class'] = "state-{$rec->state}";
        }
    }
    
    
    /**
     * Синхронизиране на шаблона с оригиналния документ
     *
     * @param int $containerId - ид на контейнер
     */
    public static function sync($containerId)
    {
        if (!$rec = self::fetch(array('#originId = [#1#]', $containerId))) {
            
            return;
        }
        
        $origin = doc_Containers::getDocument($containerId);
        
        // Ако оригиналния документ се оттегли, оттегля се и шаблона
        $state = $origin->fetchField('state');
        $newState = ($state == 'rejected') ? 'rejected' : (($state == 'closed') ? 'closed' : 'active');
        self::save((object) array('id' => $rec->id, 'state' => $newState), 'state');
    }
    
    
    /**
     * Намира наличните шаблони за документа
     *
     * @param mixed $class  - документ
     * @param mixed $driver - драйвер, ако има
     *
     * @return array $arr   - намерените шаблони
     */
    public static function getPrototypes($class, $driver = null, $folderId = null)
    {
        $arr = array();
        $Class = cls::get($class);
        
        // Намират се всички активни шаблони за този клас/драйвер
        $query = self::getQuery();
        $condition = "#classId = {$Class->getClassId()} AND #state = 'active'";
        if (isset($driver) && cls::load($driver, true)) {
            $Driver = cls::get($driver);
            $condition .= " AND #driverClassId = '{$Driver->getClassId()}'";
        }
        
        // Ако е подадена и папка се взимат всички които са до тази папка или са до всички папки
        if (isset($folderId)) {
            $condition .= " AND (#sharedFolders IS NULL OR #sharedFolders = {$folderId})";
        }
        
        $query->where($condition);
        $cu = core_Users::getCurrent();
        
        // Ако потребителя не е 'ceo'
        if (!haveRole('ceo', $cu)) {
            
            // Търсят се само шаблоните, които не са споделени с никой
            $where = '(#sharedWithRoles IS NULL AND #sharedWithUsers IS NULL)';
            
            // или са споделени с текущия потребител
            $where .= " OR LOCATE('|{$cu}|', #sharedWithUsers)";
            
            // или са споделени с някоя от ролите му
            $userRoles = core_Users::fetchField($cu, 'roles');
            $userRoles = keylist::toArray($userRoles);
            foreach ($userRoles as $roleId) {
                $where .= " OR LOCATE('|{$roleId}|', #sharedWithRoles)";
            }
            
            // Добавяне на ограниченията към заявката
            $query->where($where);
        }
        
        // Ако има записи, се връщат ид-та на документите
        while ($rec = $query->fetch()) {
            $arr[$rec->docId] = $Class->getPrototypeTitle($rec->docId);
        }
        
        asort($arr);
        
        // Връщане на намерените шаблони
        return $arr;
    }
    
    
    /**
     *
     *
     * @param mixed       $class
     * @param int         $docId
     * @param string|NULL $field
     *
     * @return stdClass|string
     */
    public static function getProtoRec($class, $docId, $field = null)
    {
        $Class = cls::get($class);
        $cond = array("#classId = '[#1#]' AND #docId = '[#2#]'", $Class->getClassId(), $docId);
        
        if (!empty($field)) {
            
            return self::fetchField($cond, $field);
        }
        
        return self::fetch($cond);
    }
    
    
    /**
     * Създаване на шаблон + смяна на състоянието на документа в 'шаблон'
     *
     * @param mixed       $class           - клас на документа
     * @param int         $docId           - ид на документа
     * @param int|NULL    $driverClassId   - ид на класа на драйвера
     * @param string|NULL $sharedWithRoles - споделени роли
     * @param string|NULL $sharedWithUsers - споделени потребители
     */
    public static function add($class, $docId, $driverClassId = null, $sharedWithRoles = null, $sharedWithUsers = null)
    {
        $Class = cls::get($class);
        
        $rec = (object) array(
            'originId' => $Class->fetchField($docId, 'containerId'),
            'classId' => $Class->getClassId(),
            'docId' => $docId,
            'driverClassId' => $driverClassId,
            'sharedWithRoles' => $sharedWithRoles,
            'sharedWithUsers' => $sharedWithUsers,
            'state' => 'active',
        );
        
        $fields = array();
        $exRec = null;
        cls::get(get_called_class())->isUnique($rec, $fields, $exRec);
        if ($exRec) {
            $rec->id = $rec->id;
        }
        
        doc_Prototypes::save($rec);
    }
}
