<?php 


/**
 * Разпределена група файлове
 * 
 * @category  bgerp
 * @package   distro
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class distro_Group extends core_Master
{
    


    /**
     * Да се забрани ли кеширането на документа
     */
    public $preventCache = TRUE;
    
    
    /**
     * Заглавие на модела
     */
    public $title = 'Разпределени групи файлове';
    
    
    /**
     * 
     */
    public $singleTitle = 'Група файлове';
    
    
    /**
     * Път към картинка 16x16
     */
    public $singleIcon = 'img/16/distro.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'distro/tpl/SingleLayoutGroup.shtml';
    
    
    /**
     * Полета, които ще се клонират
     */
    public $cloneFields = 'repos';
    
    
    /**
     * Кой има право да клонира?
     */
    public $canClone = 'admin';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'powerUser';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    public $canReject = 'powerUser';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'distro_Wrapper, doc_DocumentPlg, doc_ActivatePlg, plg_RowTools, plg_Search, plg_Printing, bgerp_plg_Blank, doc_SharablePlg';
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Dst';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "18.8|Други"; 
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'id';
    

    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'title, repos';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'distro_Files';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие, mandatory, width=100%');
        $this->FLD('repos', 'keylist(mvc=fileman_Repositories, select=verbalName)', 'caption=Хранилища, mandatory, width=100%, maxColumns=3');
//        $this->FLD('rules', 'key(mvc=distro_Automation, select=type)', "caption=Правила, tile=Правила за автоматизация, width=100%");
        
        $this->setDbUnique('title');
    }
    
    
	/**
     * Може ли документа да се добави в посочената папка?
     */
    public static function canAddToFolder($folderId)
    {
        // Ако няма права за добавяне
        if (!static::haveRightFor('add')) {
            
            // Да не може да добавя
            return FALSE;
        }
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     */
	public static function canAddToThread($threadId)
    {
        // Ако няма права за добавяне
        if (!static::haveRightFor('add')) {
            
            // Да не може да добавя
            return FALSE;
        }
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако добавяме или променяме запис
        if ($action == 'add' || $action == 'edit') {
            
            // Вземаме всички хранилища
            $reposArr = fileman_Repositories::getReposArr();
            
            // Ако няма достъп до някой от тях
            if (!fileman_Repositories::canAccessToSomeRepo($reposArr)) {
                
                // Никой да не може да добавя
                $requiredRoles = 'no_one';
            }
        }
        
        // Ако ще разглеждаме сингъла на документа
        if ($action == 'single') {
            
            // Ако нямаме права в нишката
            if (!doc_Threads::haveRightFor('single', $rec->threadId)) {
                
                // Никой да не може
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
	/**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Вземаме всички хранилища
        $reposArr = fileman_Repositories::getReposArr();
        
        // Вземаем хранилищата до които имаме достъп
        $reposArr = fileman_Repositories::getAccessedReposArr($reposArr);
        
        // Ако има хранилища
        if (!empty($reposArr)) {
            
            // Задаваме ги
            $data->form->setSuggestions('repos', $reposArr);
        } else {
            
            // Хранилищата да не могат да се изберат
            $data->form->setField('repos', 'input=none');
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        // Ако формата е изпратена
        if ($form->isSubmitted()) {
            
            // Вземаме заглавието
            $title = $form->rec->title;
            
            // Нормализираме заглавието
            $title = fileman_Files::normalizeFileName($title);
            
            // Проверяваме дали записа е с уникално име
            if (!static::isUniqueTitle($title)) {
                
                // Ако редактираме записа
                if ($form->rec->id) {
                    
                    // Вземаме записа от модела
                    $fRec = $mvc->fetch($form->rec->id);
                    
                    // Ако титлата не е променена
                    if ($fRec->title == $title) {
                        
                        // Сетваме флага
                        $sameEditing = TRUE;
                    }
                }
                
                // Ако флага не е сетнат
                if (!$sameEditing) {
                    
                    // Добавяме грешка
                    $form->setError('title', 'Съществува група с това заглавие|*: ' . $title);
                }
            } else {
                
                // Задаваме титлата да е нормализираната
                $form->rec->title = $title;
            }
        }
    }
    
    
    /**
     * Проверява дали титлата на документа е уникална
     * 
     * @param string $title - Заглавие/титла на докуемнта
     * 
     * @return boolean
     */
    static function isUniqueTitle($title)
    {    
        // Ако не е сетната титлата или имаме такъв запис
        if (!$title || (static::fetch(array("#title = '[#1#]'", $title)))) {
            
            // Връщаме FALSE
            return FALSE;
        }
        
        return TRUE;
    }
    
    
	/**
	 * 
     * Функция, която се извиква след активирането на документа
	 * 
	 * @param unknown_type $mvc
	 * @param unknown_type $rec
	 */
    public static function on_AfterActivation($mvc, &$rec)
    {
        // Ако са избрани хранилища
        if ($rec->repos) {
            
            // Масив с хранилищата
            $reposArr = type_Keylist::toArray($rec->repos);
            
            // Обхождаме масива
            foreach ((array)$reposArr as $repoId) {
                
                // Създаваме директория в хранилището
                fileman_Repositories::createDirInRepo($repoId, $rec->title);
                
                // Активираме хранилището
                fileman_Repositories::activateRepo($repoId);
            }
        }
    }
    
    
    /**
     * Връща заглавието за записа
     * 
     * @param integer $id - id на записа
     */
    static function getGroupTitle($id)
    {
        // Вземаме заглавието
        $title = static::fetchField($id, 'title');
        
        return $title;
    }
    
    
    /**
     * Проверява дали може да се добави в детайла
     * 
     * @param integer $id - id на записи
     * @param integer $userId - id на потребител
     * 
     * @return boolean - Ако имаме права
     */
    static function canAddDetail($id, $userId=NULL)
    {
        // Ако няма id
        if (!$id) return FALSE;
            
        // Вземаме записа
        $rec = static::fetch($id);
        
        // Ако състоянието не е актвино
        if ($rec->state != 'active') {
            
            return FALSE;
        }
        
        // Ако имаме достъп до сингъла на документа
        if (static::haveRightFor('single', $rec, $userId)) {
                
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Връща масив с хранилищата до които имаме достъп
     * 
     * @param unknown_type $id
     * @param unknown_type $userId
     * 
     * @return array 
     */
    static function getReposArr($id, $userId=NULL)
    {
        // Вземаме записа
        $rec = static::fetch($id);
        
        // Масив с хранилищатата
        $reposArr = type_Keylist::toArray($rec->repos);
        
        // Обхождаме масива
        foreach ((array)$reposArr as $repoId) {
            
            // Добавяме вербалното име в масива
            $reposArr[$repoId] = fileman_Repositories::getRepoName($repoId);
        }
        
        // Връщаме масива
        return $reposArr;
    }
    
    
    /**
     * Връща масив с актвитните групи и хранилищата
     * 
     * @return array - Двуемерен масив с id на записа, id на хранилището и заглавието на групата
     */
    static function getActiveGroupArr()
    {
        // Вземаме всички активни групи, подредени в обратен ред
        $query = static::getQuery();
        $query->where("#state = 'active'");
        $query->orderBy('id', 'DESC');
        
        // Двумерния масив, който ще връщаме
        $pathArr = array();
        
        // Обхождаме резултата
        while($rec = $query->fetch()) {
            
            // Вземаме хранилищата
            $reposArr = type_Keylist::toArray($rec->repos);
            
            // Ако няма хранилище, прескачаме
            if (empty($reposArr)) continue;
            
            // Обхождаме масива с хранилищата
            foreach ((array)$reposArr as $repoId) {
                
                // Добавяме в масива
                $pathArr[$rec->id][$repoId] = $rec->title;
            }
        }
        
        return $pathArr;
    }
    
    
	/**
     * Реализация  на интерфейсния метод ::getThreadState()
     */
    static function getThreadState($id)
    {
        
        return 'opened';
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     * 
     * @param unknown_type $id
     */
    function getDocumentRow($id)
    {
        // Ако няма id
        if(!$id) return;
        
        // Вземаме записа
        $rec = $this->fetch($id);
        
        // Вземаме вербалните данни
        $row = new stdClass();
        $row->title = $this->getVerbal($rec, 'title');
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->authorId = $rec->createdBy;
        $row->recTitle = $rec->title;
        
        return $row;
    }
    
    
    /**
     * 
     * 
     * @param core_Master $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareSingle($mvc, $res, &$data)
    {
        // Вземаме масива с детайлите
        $detailsArr = arr::make($mvc->details);
        
        // Обхождаме записите
        foreach ($detailsArr as $className) {
            
            try {
                // Инстанция на класа
                $inst = core_Cls::get($className);
                
                // Ако има запис в детайла
                if ($inst->haveRec($inst, $data->rec->id)) {
                    
                    // Премахваме хранилищата
                    unset($data->row->repos);
                    
                    // Прекъсваме
                    break;
                }
            } catch (core_exception_Expect $e) {
                
                continue;
            }
        }
    }
    
    
    /**
     * След добавяне/изтриване в детайла
     * 
     * @param distro_Group $mvc
     * @param integer $id
     * @param core_Manager $detailMvc
     */
    protected static function on_AfterUpdateDetail(core_Manager $mvc, $id, core_Manager $detailMvc)
    {
        // Вземаме записа за мастера на детайла
        $rec = $mvc->fetch($id);
        
        // Променяме времето на последно използване
        $rec->lastUsedOn = dt::verbal2mysql();
        
        // Записваме
        $mvc->save($rec);
    }
    
    
    /**
     * Връща масив с документите и файловете, които могат да се добавят
     * 
     * @param integer $id
     * 
     * @return array
     */
    static function getFilesForAdd($id)
    {
        // Масива, който ще връщаме
        $docAndFilesArr = array();
        
        // Вземаме записа
        $rec = static::fetch($id);
        
        // Вземаме id'тата на всички документи от нишката
        $allDocThreadIdArr = doc_Containers::getAllDocIdFromThread($rec->threadId, 'active');
        
        // Вземаме записа за текущата нишка
        $allDocIdArr = $allDocThreadIdArr[$rec->threadId];
        
        // Премахваме id' то на този документ
        unset($allDocIdArr[$rec->containerId]);
        
        // Ако има повече от 1 елемент в масива
        if (count((array)$allDocIdArr) > 1) {
            
            // Обръщаме масива
            $allDocIdArr = array_reverse($allDocIdArr, TRUE);
        }
        
        // Обхождаме масива
        foreach ((array)$allDocIdArr as $docId => $docRec) {
            
            // Вземаме класа на документа
            $class = doc_Containers::getDocument($docId);
            
            // Ако има съответния интерфейс
            if (cls::haveInterface('distro_AddFilesIntf', $class->instance)) {
                
                // Вземаме всички файлове
                $docAndFilesArr[$docId] = $class->getInstance()->getFilesArr($class->that);
            }
        }
        
        return $docAndFilesArr;
    }
}
