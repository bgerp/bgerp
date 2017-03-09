<?php



/**
 * Клас 'doc_Prototypes' - Модел за шаблонни документи
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
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
    public $title = "Шаблонни документи";
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Шаблонен документ";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "docId,title,sharedWithRoles,sharedWithUsers,sharedFolders,state,modifiedOn,modifiedBy";
    
    
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
    public $canAdd  = 'officer';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit  = 'officer';
    
    
    /**
     * Кой може да редактира
     */
    public $canDelete  = 'no_one';
    
    
    /**
     * Кой може да оттегля
     */
    public $canReject  = 'no_one';
    
    
    /**
     * Кой може да възстановява
     */
    public $canRestore  = 'no_one';


    /**
     * Кой е текущия таб
     */
	public $currentTab = "Нишка";


    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('title', 'varchar', 'caption=Заглавие,mandatory');
    	$this->FLD('originId', 'key(mvc=doc_Containers)', 'caption=Документ,mandatory,input=hidden,silent');
    	$this->FLD('classId', 'class(interface=doc_PrototypeSourceIntf)', 'caption=Документ,mandatory,input=hidden,silent');
    	$this->FLD('docId', 'int', 'caption=Документ,mandatory,input=hidden,silent,tdClass=leftColImportant');
    	$this->FLD('driverClassId', 'class', 'caption=Документ,input=hidden');
    	$this->FLD('sharedWithRoles', 'keylist(mvc=core_Roles,select=role,groupBy=type,orderBy=orderByRole)', 'caption=Споделяне->Роли');
    	$this->FLD('sharedWithUsers', 'userList', 'caption=Споделяне->Потребители');
    	$this->FLD('sharedFolders', 'keylist(mvc=doc_Folders,select=title,maxSuggestions=1000,prepareQuery=doc_Prototypes->filterFolders)', 'caption=Споделяне->Папки,input=none');
    	$this->FLD('fields', 'blob(serialize, compress)', 'input=none');
    	$this->FLD('state', 'enum(active=Активирано,rejected=Оттеглено,closed=Затворено)','caption=Състояние,column=none,input=none,notNull,value=active');
    	
    	$this->setDbUnique('classId,title');
    	$this->setDbUnique('originId');
    	$this->setDbUnique('classId,docId');
    	$this->setDbIndex('classId,docId,driverClassId');
    }
    
    
    /**
     * Филтриране на папките, така че да се показват само тези във които документа може да се добави
     * 
     * @param type_Keylist $type
     * @param core_Query $query
     */
    public function filterFolders($type, $query)
    {
    	$query->where("#coverClass IN ({$type->params['coverKeys']}) AND #state != 'rejected' AND #state != 'closed'");
    	doc_Folders::restrictAccess($query);
    	$query->limit(1001);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	// Кога може да се добавя
    	if($action == 'add' && isset($rec)){
    		if(isset($rec->originId)){
    			$doc = doc_Containers::getDocument($rec->originId);
    			
    			// Документа трябва да има нужния интерфейс
    			if(!$doc->haveInterface('doc_PrototypeSourceIntf')){
    				$requiredRoles = 'no_one';
    			} else {
    				
    				// Да няма шаблон и да не е направил запис в журнала
    				if($mvc->fetch("#originId = {$rec->originId}")){
    					$requiredRoles = 'no_one';
    				} elseif(acc_Journal::fetchByDoc($doc->getClassId(), $doc->that)){
    					$requiredRoles = 'no_one';
    				} elseif(!$doc->canBeTemplate()){
    					$requiredRoles = 'no_one';
    				} elseif(acc_Items::fetchItem($doc->getInstance(), $doc->that)){
    					$requiredRoles = 'no_one';
    				}
    			}
    		} else {
    			
    			// Ако няма ориджин не може
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	// Кога може да се добавя и редактира
    	if(($action == 'add' || $action == 'edit') && isset($rec->originId)){
    		if($requiredRoles != 'no_one'){
    			$doc = doc_Containers::getDocument($rec->originId);
    			$state = $doc->fetchField('state');
    			
    			// Трябва потребителя да има достъп до документа
    			if(!$doc->haveRightFor('single')){
    				$requiredRoles = 'no_one';
    				
    				// И документа да не е оттеглен
    			} elseif($state == 'rejected' || $state == 'closed'){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
    	}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = $data->form;
    	expect($origin = doc_Containers::getDocument($form->rec->originId));
    	
    	$form->setDefault('title', $origin->getTitleById());
    	$form->setDefault('classId', $origin->getClassId());
    	$form->setDefault('docId', $origin->that);
    	
    	// Попълване на полето за драйвер за по-бързо търсене
    	if($origin->getInstance() instanceof embed_Manager){
    		$form->setDefault('driverClassId', $origin->fetchField($origin->driverClassField));
    	} elseif($origin->getInstance() instanceof core_Embedder){
    		$form->setDefault('driverClassId', $origin->fetchField($origin->innerClassField));
    	}
    	
    	// За споделени папки се взимат само тези във които документа може да бъде създаден
    	if(cls::existsMethod($origin->className, 'getCoversAndInterfacesForNewDoc')){
    		$coverArr = doc_plg_SelectFolder::getAllowedCovers($origin->getInstance());
    		
    		// Хак за артикулите
    		if($origin->isInstanceOf('cat_Products')){
    			$personId = crm_Persons::getClassId();
    			$companyId = crm_Companies::getClassId();
    			$coverArr[$personId] = $personId;
    			$coverArr[$companyId] = $companyId;
    		}
    		
    		if(is_array($coverArr) && count($coverArr)){
    			$coverKeys = implode(',', array_keys($coverArr));
    			$form->setField('sharedFolders', 'input');
    			$form->setFieldTypeParams('sharedFolders', array("coverKeys" => $coverKeys));
    		}
    	}
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	// След като се създаде шаблон, оригиналния документ минава в състояние шаблон
    	$nRec = (object)array('id' => $rec->docId, 'state' => 'template');
    	cls::get($rec->classId)->save_($nRec, 'state');
    }
    
    
    /**
     * 
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(isset($fields['-list'])){
    		$row->docId = doc_Containers::getDocument($rec->originId)->getLink(0);
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
    	if(!$rec = self::fetch(array("#originId = [#1#]", $containerId))) return;
    	
    	$origin = doc_Containers::getDocument($containerId);
    	
    	// Ако оригиналния документ се оттегли, оттегля се и шаблона
    	$state = $origin->fetchField('state');
    	$newState = ($state == 'rejected') ? 'rejected' : (($state == 'closed') ? 'closed' : 'active');
    	self::save((object)array('id' => $rec->id, 'state' => $newState), 'state');
    }
    
    
    /**
     * Намира наличните шаблони за документа
     * 
     * @param mixed $class  - документ
     * @param mixed $driver - драйвер, ако има
     * @return array $arr   - намерените шаблони
     */
    public static function getPrototypes($class, $driver = NULL, $folderId = NULL)
    {
    	$arr = array();
    	$Class = cls::get($class);
    	
    	// Намират се всички активни шаблони за този клас/драйвер
    	$query = self::getQuery();
    	$condition = "#classId = {$Class->getClassId()} AND #state = 'active'";
    	if(isset($driver)){
    		$Driver = cls::get($driver);
    		$condition .= " AND #driverClassId = '{$Driver->getClassId()}'";
    	}
    	
    	// Ако е подадена и папка се взимат всички които са до тази папка или са до всички папки
    	if(isset($folderId)){
    		$condition .= " AND (#sharedFolders IS NULL OR LOCATE('|{$folderId}|', #sharedFolders))";
    	}
    	
    	$query->where($condition);
    	$cu = core_Users::getCurrent();
    	
    	// Ако потребителя не е 'ceo'
    	if(!haveRole('ceo', $cu)){
    		
    		// Търсят се само шаблоните, които не са споделени с никой
    		$where = "(#sharedWithRoles IS NULL AND #sharedWithUsers IS NULL)";
    		
    		// или са споделени с текущия потребител
    		$where .= " OR LOCATE('|{$cu}|', #sharedWithUsers)";
    		
    		// или са споделени с някоя от ролите му
    		$userRoles = core_Users::fetchField($cu, 'roles');
    		$userRoles = keylist::toArray($userRoles);
    		foreach ($userRoles as $roleId){
    			$where .= " OR LOCATE('|{$roleId}|', #sharedWithRoles)";
    		}
    		
    		// Добавяне на ограниченията към заявката
    		$query->where($where);
    	}
    	
    	// Ако има записи, се връщат ид-та на документите
    	while($rec = $query->fetch()){
    		$arr[$rec->docId] = $rec->title;
    	}
    	
    	// Връщане на намерените шаблони
    	return $arr;
    }

    
    /**
     * Създаване на шаблон + смяна на състоянието на документа в 'шаблон'
     * 
     * @param string $title                 - име на шаблона
     * @param mixed $class                  - клас на документа
     * @param int $docId                    - ид на документа
     * @param int|NULL $driverClassId       - ид на класа на драйвера
     * @param string|NULL $sharedWithRoles  - споделени роли
     * @param string|NULL $sharedWithUsers  - споделени потребители
     */
    public static function add($title, $class, $docId, $driverClassId = NULL, $sharedWithRoles = NULL, $sharedWithUsers = NULL)
    {
    	$Class = cls::get($class);
    	
    	$rec = (object)array('title'           => $title,
    						 'originId'        => $Class->fetchField($docId, 'containerId'),
    						 'classId'         => $Class->getClassId(),
    			             'docId'           => $docId,
    			             'driverClassId'   => $driverClassId,
    						 'sharedWithRoles' => $sharedWithRoles,
    						 'sharedWithUsers' => $sharedWithUsers,
    			             'state'           => 'active',
    	);
    	
    	cls::get(get_called_class())->isUnique($rec, $fields, $exRec);
    	if($exRec){
    		$rec->id = $rec->id;
    	}
    	
    	doc_Prototypes::save($rec);
    }
}