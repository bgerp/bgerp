<?php 


/**
 * Разпределена файлова група
 * 
 * @category  bgerp
 * @package   distro
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class distro_Group extends core_Master
{
    
    
    /**
     * Заглавие на модела
     */
    public $title = 'Разпределени файлови групи';
    
    
    /**
     * 
     */
    public $singleTitle = 'Файлова група';
    
    
    /**
     * Път към картинка 16x16
     */
    public $singleIcon = 'img/16/distro.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'distro/tpl/SingleLayoutGroup.shtml';
    
    
    /**
     * Кой може да пуска синхронизирането
     */
    public $canSync = 'powerUser';
    
    
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
    public $loadList = 'distro_Wrapper, doc_DocumentPlg, doc_ActivatePlg, plg_RowTools2, plg_Search, plg_Printing, 
                        bgerp_plg_Blank, doc_SharablePlg, plg_Clone,doc_plg_SelectFolder';
    
    
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
    public $details = 'distro_Files, distro_Actions';
    
    
    /**
     * Името на кофата за файловете
     */
    public static $bucket = 'distroFiles';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title', 'varchar(128,ci)', 'caption=Заглавие, mandatory, width=100%');
        $this->FLD('repos', 'keylist(mvc=distro_Repositories, select=name, where=#state !\\= \\\'rejected\\\', select2MinItems=6)', 'caption=Хранилища, mandatory, width=100%, maxColumns=3');
    }
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'doc_UnsortedFolders';
    
    
    /**
     * 
     * @param string $path
     * 
     * @return NULL|integer
     */
    public function getGroupIdFromFolder($path)
    {
        $handleArr = doc_Containers::parseHandle($path);
        
        if ($handleArr === FALSE) return ;
        
        return $handleArr['id'];
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
     * @param distro_Group $mvc
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
            $reposArr = distro_Repositories::getReposArr();
            
            // Ако няма достъп до някой от тях
            if (empty($reposArr)) {
                
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
        
        // За да може да синхронизира файловете, трябва да има права за сингъла
        if ($action == 'sync') {
            if (!$mvc->haveRightFor('single', $rec, $userId)) {
                
                // Никой да не може
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
	/**
	 * 
     * Функция, която се извиква след активирането на документа
	 * 
	 * @param distro_Group $mvc
	 * @param stdObject $rec
	 */
    public static function on_AfterActivation($mvc, &$rec)
    {
        // Ако са избрани хранилища
        if ($rec->repos) {
            
            // Масив с хранилищата
            $reposArr = type_Keylist::toArray($rec->repos);
            
            // Обхождаме масива
            foreach ((array)$reposArr as $repoId) {
                
                // Активираме хранилището
                distro_Repositories::activateRepo($repoId);
                
                $subDirName = $mvc->getSubDirName($rec->id);
                
                // Създаваме директория в хранилището
                distro_Repositories::createDir($repoId, $subDirName);
            }
        }
    }
    
    
    /**
     * 
     * 
     * @param integer $id
     * 
     * @return string
     */
    public static function getSubDirName($id)
    {
        $rec = self::fetch($id);
        
        $title = $rec->title;
        
        $title = STR::utf2ascii($title);
        $title = preg_replace('/[\W]+/', ' ', $title);
        
        $title = trim($title);
        
        $subDir = self::getHandle($id) . ' - ' . $title;
        
        return $subDir;
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
     * Връща масив с хранилищата, които се използват в групата
     * 
     * @param integer $id
     * @param NULL|integer $userId
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
            $reposArr[$repoId] = distro_Repositories::getVerbal($repoId, 'name');
        }
        
        // Връщаме масива
        return $reposArr;
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
     * @param integer $id
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
	 * Връща прикачените файлове
     * 
     * @param object $rec - Запис
     * 
     * @return array
     */
    function getLinkedFiles($rec)
    {
        $resArr = array();
        
        if (!$rec->id) return $resArr;
        
        $fQuery = distro_Files::getQuery();
        $fQuery->where(array("#groupId = '[#1#]'", $rec->id));
        
        while($dfRec = $fQuery->fetch()) {
            if (!trim($dfRec->sourceFh)) continue;
            
            if ($resArr[$dfRec->sourceFh]) continue;
            
            $fRec = fileman_Files::fetchByFh($dfRec->sourceFh);
            $resArr[$dfRec->sourceFh] = fileman_Files::getVerbal($fRec, 'name');
        }
        
        return $resArr;
    }
    
    
    /**
     * Екшън за форсирано обновяване на хранилище
     * 
     * @return Redirect
     */
    function act_Sync()
    {
        $id = Request::get('id', 'int');
        
        $this->requireRightFor('sync', $id);
        
        expect($rec = $this->fetch($id));
        
        $this->logWrite('Синхронизиране на файловете', $id);
        
        $reposArr = type_Keylist::toArray($rec->repos);
		
        foreach ($reposArr as $repoId) {
            
            $Files = cls::get('distro_Files');
            
            $res = $Files->forceSync($rec->id, $repoId);
            
            if ($res === FALSE) {
                status_Messages::newStatus('|Грешка при свързване към хранилище|* ' . distro_Repositories::getLinkToSingle($repoId, 'name'), 'warning');
            } else {
                if (empty($res)) {
                    status_Messages::newStatus('|Хранилището|* ' . distro_Repositories::getLinkToSingle($repoId, 'name') . ' |е било синхронизирано|*');
                } else {
                    $msg = '';
                    
                    if ($res['addToDB']) {
                        $msg .= '|Добавени файлове от хранилището|*: ' . $res['addToDB'];
                    }
                    
                    if ($res['delFromDb']) {
                        $msg .= $msg ? "<br>" : $msg;
                        $msg .= '|Изтрити файлове от хранилището|*: ' . $res['delFromDb'];
                    }
                    
                    if ($res['absorbFromDb']) {
                        $msg .= $msg ? "<br>" : $msg;
                        $msg .= '|Свалени файлове в хранилището|*: ' . $res['absorbFromDb'];
                    }
                    
                    status_Messages::newStatus('|Действия в хранилището|*: ' . distro_Repositories::getLinkToSingle($repoId, 'name') . "<br>" . $msg);
                }
            }
        }
        
        $retUrl = getRetUrl();
        
        if (empty($retUrl)) {
            $retUrl = array($this, 'single', $id);
        }
        
        return new Redirect($retUrl);
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Използваме заглавието на първия документ в нишката или на originId
        $rec = $data->form->rec;
        if (!$rec->id) {
            
            $cid = NULL;
            
            //Ако имаме originId
            if ($rec->originId) {
            
                $cid = $rec->originId;
            } elseif ($rec->threadId) {
            
                // Ако добавяме коментар в нишката
                $cid = doc_Threads::fetch($rec->threadId)->firstContainerId;
            }
            
            if (isset($cid)) {
                $oDoc = doc_Containers::getDocument($cid);
                $oRow = $oDoc->getDocumentRow();
                $title = $oRow->recTitle ? $oRow->recTitle : $oRow->title;
                $rec->title = html_entity_decode($oRow->recTitle, ENT_COMPAT | ENT_HTML401, 'UTF-8');
            }
        }
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
     * След подготовка на тулбара на единичен изглед.
     * 
     * @param distro_Group $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if ($mvc->haveRightFor('sync', $data->rec)) {
            $data->toolbar->addBtn('Синхронизиране', array($mvc, 'sync', $data->rec->id, 'ret_url' => TRUE), 
                        "id='btn-syncRepo', ef_icon=img/16/update-icon.png", 
                        array('order'=>'30', 'row' => 2, 'title' => 'Форсира синхронизирането файловете в групата с хранилището'));
        }
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     * 
     * @param distro_Group $mvc
     * @param string $res
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        //Създаваме, кофа, където ще държим всички прикачени файлове
        $res .= fileman_Buckets::createBucket(self::$bucket, 'Качени файлове в дистрибутива', NULL, '300 MB', 'user', 'user');
    }
}
