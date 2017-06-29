<?php 


/**
 * Действия в разпределена файлова група
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
     * Дали да се създаде директория (ако липсва), при пускане на процеси
     */
    public $checkAndCreateDir = TRUE;
    
    
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
     * Брой записи на страница
     */
    public $listItemsPerPage = 10;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('groupId', 'key(mvc=distro_Group, select=title)', 'caption=Група, silent, input=hidden');
        $this->FLD('repoId', 'key(mvc=distro_Repositories, select=name)', 'caption=Хранилище, silent, input=hidden');
        $this->FLD('fileId', 'key(mvc=distro_Files, select=name)', 'caption=Файл, silent, input=hidden');
	    $this->FLD('completedOn', 'datetime(format=smartTime)', 'caption=Приключено->На,input=none');
	    $this->FLD('fileName', 'varchar', 'caption=Име на файл,input=none');
	    $this->FLD('fileSourceFh', 'fileman_FileType(bucket=' . distro_Group::$bucket . ')', 'caption=Файл,input=none');
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
            
            $rec = new stdClass();
            $rec->groupId = $fRec->groupId;
            $rec->fileId = $fRec->id;
            $rec->repoId = $fRec->repoId;
            
            if (!self::haveRightFor('add', $rec)) return ;
            
            if (!cls::load($driverId, TRUE)) continue ;
            $driverInst = cls::getInterface('distro_ActionsDriverIntf', $driverId);
            
            if (!$driverInst->canMakeAction($fRec->groupId, $fRec->repoId, $fRec->id, $fRec->name, $fRec->md5)) continue ;

            $me = cls::get(get_called_class());
            
            core_Request::setProtected(array($me->driverClassField, 'groupId', 'repoId', 'fileId'));
            
            $params = $driverInst->getLinkParams();
            
            $url = array('distro_Actions', 'add', $me->driverClassField => $driverId, 'groupId' => $fRec->groupId, 'repoId' => $fRec->repoId, 'fileId' => $fRec->id, 'ret_url' => TRUE);
            
            if ($driverInst->canForceSave()) {
                $url['CfDrv'] = core_Request::getSessHash($driverId);
            }
            
            $rowTools->addLink($driverInst->class->title, $url, $params);
        }
    }
    
    
    /**
     * Извиква драйвера за абсорбиране на файл
     * 
     * @param stdObject $fRec
     * @param string $driverName
     * @param bollean $onlyCallback
     */
    public static function addToRepo($fRec, $driverName = 'distro_AbsorbDriver', $onlyCallback = FALSE)
    {
        $driverInst = cls::getInterface('distro_ActionsDriverIntf', $driverName);
        
        if (!$driverInst || !$driverInst->canMakeAction($fRec->groupId, $fRec->repoId, $fRec->id, $fRec->name, $fRec->md5)) return ;
        
        $me = cls::get(get_called_class());
        
        $rec = new stdClass();
        $rec->groupId = $fRec->groupId;
        $rec->repoId = $fRec->repoId;
        $rec->fileId = $fRec->id;
        $rec->{$me->driverClassField} = $driverName::getClassId();
        $rec->OnlyCallback = $onlyCallback;
        
        self::save($rec);
    }
    
    
    /**
     * Връща линк към файла или името му
     * 
     * @param stdObjec $rec
     * 
     * @return string
     */
    public function getFileName($rec)
    {
        if ($fRec = distro_Files::fetch($rec->fileId)) {
            if ($fRec->sourceFh) {
                $fileName = distro_Files::getVerbal($fRec, 'sourceFh');
            } else {
                $fileName = '"' . $this->getVerbal($rec, 'fileId') . '"';
            }
        } else {
            if ($rec->fileSourceFh) {
                $fileName = $this->getVerbal($rec, 'fileSourceFh');
            } else {
                $fileName = '"' . $this->getVerbal($rec, 'fileName') . '"';
            }
        }
        
        return $fileName;
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
        
        $fRec = distro_Files::fetch($rec->fileId);
        
        if ($fRec) {
            if ($rec->createdBy > 0) {
                $sudoUser = core_Users::sudo($rec->createdBy);
            }
            
            distro_Files::save($fRec, 'modifiedOn, modifiedBy');
            
            // Обновяваме и групата
            $DGroup = cls::get('distro_Group');
            $DGroup->touchRec($rec->groupId);
            $gRec = $DGroup->fetch($rec->groupId);
            doc_Files::saveFile($DGroup, $gRec);
            
            core_Users::exitSudo($sudoUser);
        }
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
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param distro_Actions $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако ще добавяме/редактираме записа
        if ($action == 'add') {
            
            // Ако има master
            if (($masterKey = $mvc->masterKey) && ($rec->$masterKey)) {
                
                // Ако няма права за добавяне на детайл
                if (!$mvc->Master->canAddDetail($rec->$masterKey)) {
                    
                    // Да не може да добавя
                    $requiredRoles = 'no_one';
                }
            }
        }
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
        
        $rec = $data->form->rec;
        
        $driverInst = $mvc->getDriver($rec);
        
        $fRec = distro_Files::fetch($rec->fileId);
        
        // Ако е зададено да се форсира записването
        if ($driverInst) {
            if ($rec->fileId && $driverInst->canForceSave() && !$data->form->isSubmitted()) {
                
                expect(core_Request::getSessHash(core_Request::get($mvc->driverClassField, 'int')) === Request::get('CfDrv'));
                
                $retUrl = getRetUrl();
                if (empty($retUrl)) {
                    $retUrl = array('distro_Group', 'single', $rec->groupId);
                }
                
                $mvc->requireRightFor('Add', $data->form->rec, NULL, $retUrl);
                
                $mvc->addToRepo($fRec, $driverInst->className);
                
                redirect($retUrl);
            }
        }
        
        $data->form->setHidden($mvc->driverClassField);
        
        if ($data->form->isSubmitted() && $data->form->rec->fileId) {
            
            if ($driverInst) {
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
    public static function on_AfterSave($mvc, $res, $rec)
    {
        // Ако ще се пускат обработки на файла
        if (!$rec->StopExec) {
            
            if (!$rec->OnlyCallback) {
                $driverInst = $mvc->getDriver($rec);
                if ($driverInst) {
                    $command = $driverInst->getActionStr($rec);
                    
                    $errExec = $mvc::getErrHandleExec($rec->id, $rec->repoId);
                    
                    // Ако преди всяка заявка ще се създава директорията, ако липсва
                    if ($mvc->checkAndCreateDir) {
                        $subDirName = distro_Group::getSubDirName($rec->groupId);
                        $mkDir = distro_Repositories::getMkdirExec($rec->repoId, $subDirName);
                        
                        $command = $mkDir . '; ' . $command;
                    }
                    
                    if ($errExec) {
                        $command = '(' . $command . ') ' . $errExec;
                    }
                    
                    $ssh = distro_Repositories::connectToRepo($rec->repoId);
                
                    if (!$ssh) {
                        $mvc->notifyErr($rec);
                
                        return ;
                    }
                    
                    $callBackUrl = toUrl(array($mvc, 'Callback', $rec->id), TRUE);
                    
                    $mvc->logDebug("Стартирана команда: {$command}", $rec->id);
                    
                    $ssh->exec($command, $output, $errors, $callBackUrl);
                    
                    if ($eTrim = trim($errors)) {
                        $mvc->notifyErr($rec);
                        $mvc->logWarning($errors, $rec->id);
                    }
                }
            } else {
                
                // Ако трябва да се извика само калбек функцията
                Request::forward(array('Ctr' => $mvc->className, 'Act' => 'Callback', 'id' => $rec->id));
            }
        }
    }
    
    
    /**
     * След успешен запис
     * 
     * @param distro_Actions $mvc
     * @param stdObject $res
     * @param stdObject $rec
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        $fRec = distro_Files::fetch($rec->fileId);
        setIfNot($rec->fileName, $fRec->name);
        setIfNot($rec->fileSourceFh, $fRec->sourceFh);
    }
    
    
    /**
     * Подготовка на филтър формата
     *
     * @param distro_Actions $mvc
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
        
        $file = $mvc->getFileName($rec);
        
        $row->Info = tr($driver->title) . ' ' . tr('на') . ' ' . $file . ' ' . tr('от') . ' ' . distro_Repositories::getLinkToSingle($rec->repoId, 'name');
    }
}
