<?php 


/**
 * Действия в разпределена група файлове
 * 
 * @category  bgerp
 * @package   distro
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class distro_Actions extends embed_Manager
{
    
    /**
     * Заглавие
     */
    public $title = "Действия";
    

    /**
     * Интерфейс на драйверите
     */
    public $driverInterface = 'distro_ActionsDriverIntf';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Дейстие";

    
    /**
     * Разглеждане на листов изглед
     */
    public $canSingle = 'powerUser';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'distro_Wrapper, plg_Created, plg_State';

    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin';
        

    /**
     * Кой може да пише?
     */
    public $canWrite = 'powerUser';
        

    /**
     * Кой може да изтрива?
     */
    public $canDelete = 'no_one';
        

    /**
     * Кой може да оттегля?
     */
    public $canReject = 'no_one';
        

    /**
     * Кой може да редактира?
     */
    public $canEdit = 'no_one';


    /**
     * Колонки в листовия изглед
     */
    public $listFields = 'Info=Информация, createdOn=Стартирано->На, createdBy=Стартирано->От, completedOn=Приключено||Completed->На';

    
    /**
     * Ключ, който ще се използва за мастер
     * Класа не е наследник на детайлите, но се използва като такъв
     */
    protected $masterKey = 'groupId';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('groupId', 'key(mvc=distro_Group, select=title)', 'caption=Група, silent, input=hidden');
        $this->FLD('repoId', 'key(mvc=distro_Repositories, select=name)', 'caption=Хранилище, silent, input=hidden');
        $this->FLD('fileId', 'key(mvc=distro_Files, select=name)', 'caption=Файл, silent, input=hidden');
	    $this->FLD('completedOn', 'datetime(format=smartTime)', 'caption=Приключено->На,input=none');
    }
    
    
    /**
     * Добавя екшъните от драйверите
     * 
     * @param core_RowToolbar $rowTools
     * @param stdObject $fRec
     */
    public static function addActionToFile(&$rowTools, $fRec)
    {
        foreach ((array) self::getAvailableDriverOptions() as $driverId => $name) {
            
            if (!cls::load($driverId, TRUE)) continue ;
            $driverInst = cls::getInterface('distro_ActionsDriverIntf', $driverId);
            
            if (!$driverInst->canMakeAction($fRec->groupId, $fRec->repoId, $fRec->id, $fRec->name, $fRec->md5)) continue ;

            $me = cls::get(get_called_class());
            
            core_Request::setProtected(array($me->driverClassField, 'groupId', 'repoId', 'fileId'));
            
            $params = $driverInst->getLinkParams();
            
            $rowTools->addLink($driverInst->title, array('distro_Actions', 'add', $me->driverClassField => $driverId, 'groupId' => $fRec->groupId, 'repoId' => $fRec->repoId, 'fileId' => $fRec->id, 'ret_url' => TRUE), $params);
        }
    }
    
    
    /**
     * Извиква драйвера за абсорбиране на файл
     * 
     * @param stdObject $fRec
     */
    public static function addToRepo($fRec)
    {
        $driverInst = cls::getInterface('distro_ActionsDriverIntf', 'distro_AbsorbDriver');
        
        if (!$driverInst || !$driverInst->canMakeAction($fRec->groupId, $fRec->repoId, $fRec->id, $fRec->name, $fRec->md5)) return ;
        
        $me = cls::get(get_called_class());
        
        $rec = new stdClass();
        $rec->groupId = $fRec->groupId;
        $rec->repoId = $fRec->repoId;
        $rec->fileId = $fRec->id;
        $rec->{$me->driverClassField} = distro_AbsorbDriver::getClassId();
        
        self::save($rec);
    }
    
    
    /**
     * Връща стринг, който ще се използва за прихващане на грешки при пускане на процесите
     * 
     * @param integer $id
     * @param integer $repoId
     * 
     * @return string
     */
    protected static function getErrHandleExec($id, $repoId)
    {
        $errUrl = self::getErrUrl($id);
        
        $errUrl = escapeshellarg($errUrl);
        
        $errPath = self::getErrFilePath($id, $repoId);
        
        $errPath = escapeshellarg($errPath);
        
        $errExec = "2> {$errPath}; if [ -s {$errPath} ]; then wget -q --spider --no-check-certificate {$errUrl}; else rm {$errPath}; fi";
        
        return $errExec;
    }
    
    
    /**
     * Връща пътя на файла за грешките
     * 
     * @param integer $id
     * @param integer $repoId
     * 
     * @return string
     */
    protected static function getErrFilePath($id, $repoId)
    {
        $path = distro_Repositories::getErrPath($repoId);
        
        $hash = md5($id . '|' . $repoId);
        
        $hash = substr($hash, 0, 8);
        
        $errPath = $path . $hash . '.txt';
        
        return $errPath;
    }
    
    
    /**
     * Връща URL-то за прихващане на грешки
     * 
     * @param integer $id
     * 
     * @return string
     */
    protected static function getErrUrl($id)
    {
        $url = toUrl(array(get_called_class(), 'error', $id), 'absolute');
        
        return $url;
    }
    
    
    /**
     * Нотифицира за грешки
     * Праща нотификация до инициатора на събитието
     * Сменя състояниет
     * 
     * @param stdObject $rec
     */
    protected function notifyErr($rec)
    {
        // Нотифицираме инициатора на екшъна
        if ($rec->createdBy > 0) {
            
            $driverInst = $this->getDriver($rec);
        
            $title = $driverInst ? mb_strtolower($driverInst->title) : 'действие';
        
            $msg = '|Грешка при|* |' . $title;
        
            bgerp_Notifications::add($msg, array('distro_Group', 'single', $rec->groupId), $rec->createdBy);
        }
        
        $rec->state = 'rejected';
        $rec->StopExec = TRUE;
        
        $this->save($rec, 'state');
    }
    
    
    /**
     * След приключване на обработката
     */
    function act_Callback()
    {
        $id = Request::get('id', 'int');
        
        $rec = $this->fetch($id);
        
        if ($rec->state == 'rejected') return ;
        
        if ($rec->state == 'closed') return ;
        
        $rec->state = 'closed';
        $rec->completedOn = dt::now();
        $rec->StopExec = TRUE;
        
        $this->save($rec, 'state, completedOn');
        
        // Нотифицираме драйвера, че е приключило
        $Driver = $this->getDriver($rec->id);
        $Driver->afterProcessFinish($rec);
    }
    
    
    /**
     * Записва грешката от екшъна и нотифицира инициатора
     */
    function act_Error()
    {
        $id = Request::get('id', 'int');
        
        $rec = $this->fetch($id);
        
        if ($rec->state == 'rejected') return ;
        
        $this->notifyErr($rec);

        $rec->completedOn = dt::now();
        $rec->StopExec = TRUE;
        $this->save($rec, 'completedOn');
        
        // Записваме в лога
        $ssh = distro_Repositories::connectToRepo($rec->repoId);
        
        expect($ssh);
        
        $errFile = $this->getErrFilePath($rec->id, $rec->repoId);
        
        try {
            // Опитваме се да вземем съдържанието на файла с грешките
            // След това изтриваме файла
            $content = @$ssh->getContents($errFile);
            $ssh->exec("rm " . escapeshellarg($errFile));
        } catch (core_exception_Expect $e) {
            
            return ;
        }
        
        // Логваме грешката в системата
        if ($tContent = trim($content)) {
            $this->logWarning($tContent, $rec->id);
        }
        
        return ;
    }
    
    /**
     * Подготвяме  общия изглед за 'List'
     * Понеже не е наследник на core_Detail, но искаме да го ползваме като детайл
     */
    public function prepareDetail_($data)
    {
        $data->masterKey = $this->masterKey;
        
        // Очакваме да masterKey да е зададен
        expect($data->masterKey);
        expect($data->masterMvc instanceof core_Master);
        
        // Подготвяме заявката за детайла
        $this->prepareDetailQuery($data);
        
        // Подготвяме полетата за показване
        $this->prepareListFields($data);
        
        // Подготвяме филтъра
        $this->prepareListFilter($data);
        
        // Подготвяме заявката за резюме/обощение
        $this->prepareListSummary($data);
        
        // Подготвяме навигацията по страници
        $this->prepareListPager($data);
        
        // Името на променливата за страниране на детайл
        if (is_object($data->pager)) {
            $data->pager->setPageVar($data->masterMvc->className, $data->masterId, $this->className);
            $data->pager->addToUrl = array('#' => $data->masterMvc->getHandle($data->masterId));
        }

        // Подготвяме редовете от таблицата
        $this->prepareListRecs($data);
        
        // Подготвяме вербалните стойности за редовете
        $this->prepareListRows($data);
     
        // Подготвяме лентата с инструменти
        $this->prepareListToolbar($data);

        return $data;
    }
    
    
    /**
     * Рендираме общия изглед за 'List'
     * Понеже не е наследник на core_Detail, но искаме да го ползваме като детайл
     */
    public function renderDetail_($data)
    {
        if(!isset($data->listClass)) {
            $data->listClass = 'listRowsDetail';
        }

        if (!isset($this->currentTab)) {
            $this->currentTab = $data->masterMvc->title;
        }
        
        // Рендираме общия лейаут
        $tpl = $this->renderDetailLayout($data);
        
        // Попълваме формата-филтър
        $tpl->append($this->renderListFilter($data), 'ListFilter');
        
        // Попълваме обобщената информация
        $tpl->append($this->renderListSummary($data), 'ListSummary');
        
        // Попълваме таблицата с редовете
        setIfNot($data->listTableMvc, clone $this);
        $tpl->append($this->renderListTable($data), 'ListTable');
        
        // Попълваме таблицата с редовете
        $tpl->append($this->renderListPager($data), 'ListPagerTop');
        
        // Попълваме долния тулбар
        $tpl->append($this->renderListToolbar($data), 'ListToolbar');
        
        return $tpl;
    }
    
    
    /**
     * Подготвя заявката за данните на детайла
     * Понеже не е наследник на core_Detail, но искаме да го ползваме като детайл
     */
    protected function prepareDetailQuery_($data)
    {
        // Създаваме заявката
        $data->query = $this->getQuery();
        
        // Добавяме връзката с мастер-обекта
        $data->query->where("#{$data->masterKey} = {$data->masterId}");
        
        return $data;
    }
    
    
    /**
     * Създаване на шаблона за общия List-изглед
     * Понеже не е наследник на core_Detail, но искаме да го ползваме като детайл
     */
    protected function renderDetailLayout_($data)
    {
        $className = cls::getClassName($this);
        
        // Шаблон за листовия изглед
        $listLayout = new ET("
            <div class='clearfix21 {$className}'>
            	<div class='listTopContainer clearfix21'>
                    [#ListFilter#]
                </div>
                [#ListPagerTop#]
                [#ListTable#]
                [#ListSummary#]
                [#ListToolbar#]
            </div>
        ");
        
        return $listLayout;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     * 
     * @param distro_Actions $mvc
     * @param stdObject $data
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        // Премахваме бутона за добавяне от тулбара
    	$data->toolbar->removeBtn('btnAdd');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param distro_Actions $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        core_Request::setProtected(array($mvc->driverClassField, 'groupId', 'repoId', 'fileId'));
        
        $data->form->setHidden($mvc->driverClassField);
        
        if ($data->form->isSubmitted() && $data->form->rec->fileId) {
            
            $rec = $data->form->rec;
            
            $driverInst = $mvc->getDriver($rec);
            
            if ($driverInst) {
                $fRec = distro_Files::fetch($data->form->rec->fileId);
                
                if (!$driverInst->canMakeAction($fRec->groupId, $fRec->repoId, $fRec->id, $fRec->name, $fRec->md5)) {
                    $data->form->setError($mvc->driverClassField, 'Не може да се направи това действие');
                }
            }
        }
    }


    /**
     * След подготовката на заглавието на формата
     */
    public static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $data->form->title = "Действия с файла";
        
        if ($data->form->rec->repoId) {
            $repoName = distro_Repositories::getVerbal($data->form->rec->repoId, 'name');
            $data->form->title .= ' в хранилище|* ' . $repoName;
        }
    }
    
    
    /**
     * След успешен запис
     * 
     * @param distro_Actions $mvc
     * @param stdObject $res
     * @param stdObject $rec
     */
    public static function on_AfterSave(core_Manager $mvc, $res, $rec)
    {
        // Ако ще се пускат обработки на файла
        if (!$rec->StopExec) {
            $driverInst = $mvc->getDriver($rec);
            if ($driverInst) {
                $command = $driverInst->getActionStr($rec);
                
                $errExec = $mvc::getErrHandleExec($rec->id, $rec->repoId);
            
                if ($errExec) {
                    $command = '(' . $command . ') ' . $errExec;
                }
                
                $ssh = distro_Repositories::connectToRepo($rec->repoId);
                
                if (!$ssh) {
                    $mvc->notifyErr($rec);
                }
                
                $callBackUrl = toUrl(array($mvc, 'Callback', $rec->id), TRUE);
                
                $ssh->exec($command, $output, $errors, $callBackUrl);
                
                if ($eTrim = trim($errors)) {
                    $mvc->notifyErr($rec);
                    $mvc->logWarning($errors, $rec->id);
                }
            }
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     *
     * @param distro_Actions $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('createdOn', 'DESC');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param distro_Actions $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $driver = $mvc->getDriver($rec);
        
        $row->Info = tr($driver->title) . ' ' . tr('на') . ' "' . $mvc->getVerbal($rec, 'fileId') . '" ' . tr('от') . ' "' . $mvc->getVerbal($rec, 'repoId') . '"';
    }
}
