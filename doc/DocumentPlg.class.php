<?php



/**
 * Клас 'doc_DocumentPlg'
 *
 * Плъгин за мениджърите на документи
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_DocumentPlg extends core_Plugin
{
    static $stateArr = array(
        'draft'    => 'Чернова',
        'pending'  => 'Чакащо',
        'active'   => 'Активирано',
        'opened'   => 'Отворено',
        'closed'   => 'Приключено',
        'hidden'   => 'Скрито',
        'rejected' => 'Оттеглено',
        'stopped'  => 'Спряно',
        'wakeup'   => 'Събудено',
        'free'     => 'Освободено');
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        expect($mvc instanceof core_Master, 'Документите трябва да са наследници на core_Master', get_class($mvc));
        
        // Добавяме полета свързани с организацията на документооборота
        $mvc->FLD('folderId' , 'key(mvc=doc_Folders,select=title)', 'caption=Папка,input=none,column=none,silent,input=hidden');
        $mvc->FLD('threadId', 'key(mvc=doc_Threads)', 'caption=Нишка->Топик,input=none,column=none,silent,input=hidden');
        $mvc->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Нишка->Документ,input=none,column=none,oldFieldName=threadDocumentId');
        $mvc->FLD('originId', 'key(mvc=doc_Containers)',
            'caption=Нишка->Оригинал,input=hidden,column=none,silent,oldFieldName=originContainerId');
        
        // Ако липсва, добавяме поле за състояние
        if (!$mvc->fields['state']) {
            plg_State::setStateField($mvc);
                
         //    $mvc->FLD('state',
         //           cls::get('type_Enum', array('options' => self::$stateArr)),
         //        'caption=Състояние,column=none,input=none');
        }
        
        // Ако липсва, добавяме поле за съхранение на състоянието преди reject
        if (!$mvc->fields['brState']) {
            $mvc->FLD('brState',
                cls::get('type_Enum', array('options' => self::$stateArr)),
                'caption=Състояние преди оттегляне,column=none,input=none');
        }
        
        // Добавя интерфейс за папки
        $mvc->interfaces = arr::make($mvc->interfaces);
        setIfNot($mvc->interfaces['doc_DocumentIntf'], 'doc_DocumentIntf');
        
        // Добавя поле за последно използване
        if(!isset($mvc->fields['lastUsedOn'])) {
            $mvc->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none');
        }
        
        // Добавяне на полета за created
        $mvc->FLD('createdOn', 'datetime(format=smartTime)', 'caption=Създаване->На, notNull, input=none');
        $mvc->FLD('createdBy', 'key(mvc=core_Users)', 'caption=Създаване->От, notNull, input=none');
        
        // Добавяне на полета за modified
        $mvc->FLD('modifiedOn', 'datetime(format=smartTime)', 'caption=Модифициране->На,input=none');
        $mvc->FLD('modifiedBy', 'key(mvc=core_Users)', 'caption=Модифициране->От,input=none');
        
        // Ако има cid и Tab, показваме детайлите
        if (Request::get('Cid') && Request::get('Tab')) {
            
            // Ако има данни, преобразуваме ги в масив
            $mvc->details = arr::make($mvc->details);
            
            // Детайлите
            $mvc->details['Send'] = 'log_Documents';
            $mvc->details['Open'] = 'log_Documents';
            $mvc->details['Download'] = 'log_Documents';
            $mvc->details['Forward'] = 'log_Documents';
            $mvc->details['Print'] = 'log_Documents';
            $mvc->details['Changed'] = 'log_Documents';
            $mvc->details['Used'] = 'log_Documents';
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на единичния изглед
     * Подготвя иконата за единичния изглед
     */
    function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        $data->row->iconStyle = 'background-image:url("' . sbf($mvc->getIcon($data->rec->id), '', Mode::is('text', 'xhtml') || Mode::is('printing')) . '");';
        
        if (Request::get('Printing') && empty($data->__MID__)) {
            $data->__MID__ = log_Documents::saveAction(
                array(
                    'action'      => log_Documents::ACTION_PRINT, 
                    'containerId' => $data->rec->containerId,
                )
            );
        }
    }
    
    
    /**
     * Добавя бутони
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        if (isset($data->rec->id) && $mvc->haveRightFor('reject', $data->rec) && ($data->rec->state != 'rejected')) {
            $data->toolbar->addBtn('Оттегляне', array(
                    $mvc,
                    'reject',
                    $data->rec->id,
                    'ret_url' => TRUE
                ),
                'id=btnDelete,class=btn-reject,warning=Наистина ли желаете да оттеглите документа?,order=32,title=Оттегляне на документа');
        }
        
        if (isset($data->rec->id) && $mvc->haveRightFor('restore', $data->rec) && ($data->rec->state == 'rejected')) {
            $data->toolbar->removeBtn("*");
            $data->toolbar->addBtn('Възстановяване', array(
                    $mvc,
                    'restore',
                    $data->rec->id,
                    'ret_url' => TRUE
                ),
                'id=btnRestore,warning=Наистина ли желаете да възстановите документа?,order=32', 'ef_icon = img/16/restore.png');
        }
        
        //Бутон за добавяне на коментар 
        if (($data->rec->state != 'draft') && ($data->rec->state != 'rejected')) {
            
            if (TRUE) {
                
                $retUrl = array($mvc, 'single', $data->rec->id);
                
                // Бутон за създаване на коментар
                $data->toolbar->addBtn('Коментар', array(
                        'doc_Comments',
                        'add',
                        'originId' => $data->rec->containerId,
                        'ret_url'=>$retUrl
                    ),
                    'onmouseup=saveSelectedTextToSession()', 'ef_icon = img/16/comment_add.png,title=Добавяне на коментар към документа');
            }
        } else {
            //Ако сме в състояние чернова, тогава не се показва бутона за принтиране
            //TODO да се "премахне" и оптимизира
            $data->toolbar->removeBtn('btnPrint');
        }

        //Добавяме бутон за клониране ако сме посочили, кои полета ще се клонират
        if (($mvc->cloneFields) && ($data->rec->id)) {
            
            // Ако не е чернова
            if ($data->rec->state != 'draft') {
                
                // Ако имаме права за клониране
                if ($mvc->haveRightFor('clone', $data->rec->id)) {
                    
                    $retUrl = array($mvc, 'single', $data->rec->id);
                
                    // Бутон за клониране
                    $data->toolbar->addBtn('Копие', array(
                            $mvc,
                            'add',
                            'cloneId' => $data->rec->containerId,
                            'clone' => 'clone',
                            'ret_url'=>$retUrl
                        ),
                        'order=14, row=2', 'ef_icon = img/16/page_copy.png,title=Клониране на документа');    
                }
            }
        }

        if($mvc->haveRightFor('list') && $data->rec->state != 'rejected') { 
            // Бутон за листване на всички обекти от този вид
            $data->toolbar->addBtn('Всички', array(
                    $mvc,
                    'list',
                    'ret_url'=>$retUrl
                ),
                'id=btnAll,ef_icon=img/16/application_view_list.png, order=18, row=2, title=' . tr('Всички ' . mb_strtolower($mvc->title)));    

        }
    }
    
    
    /**
     * Добавя бутон за показване на оттеглените записи
     */
    function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if(Request::get('Rejected')) {
            $data->toolbar->removeBtn('*');
            $data->toolbar->addBtn('Всички', array($mvc), 'id=listBtn', 'ef_icon = img/16/application_view_list.png');
        } else {

            $data->rejectedCnt = $data->rejQuery->count();
            
            if($data->rejectedCnt) {
                $data->toolbar->addBtn("Кош|* ({$data->rejectedCnt})", array($mvc, 'list', 'Rejected' => 1), 'id=binBtn,class=btn-bin,order=50');
            }
        }
    }
    
    
    /**
     * Добавя към титлата на списъчния изглед "[оттеглени]"
     */
    function on_AfterPrepareListTitle($mvc, &$res, $data)
    {
        if(Request::get('Rejected')) {
            $data->title = new ET('[#1#]', tr($data->title));
            $data->title->append("&nbsp;<font class='state-rejected'>&nbsp;[" . tr('оттеглени') . "]&nbsp;</font>");
        }
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal(&$invoker, &$row, &$rec, $fields = array())
    {
        $row->ROW_ATTR['class'] .= " state-{$rec->state}";
        $row->STATE_CLASS .= " state-{$rec->state}";
        
        $row->modifiedDate = dt::mysql2verbal($rec->modifiedOn, 'd.m.Y');
        $row->createdDate = dt::mysql2verbal($rec->createdOn, 'd.m.Y');
        
        //$fields = arr::make($fields);
        
        if($fields['-single']) {
            if(!$row->ident) {
                $row->ident = '#' . $invoker->getHandle($rec->id);
            }
            
            if(!$row->singleTitle) {
                $row->singleTitle = tr($invoker->singleTitle);
            }

            if($rec->state == 'rejected') {
                $tpl = new ET(tr(' от [#user#] на [#date#]'));
                $row->state .= $tpl->placeArray(array('user' => $row->modifiedBy, 'date' => dt::mysql2Verbal($rec->modifiedOn)));
            }
            
           // bp($row);
        }
    }
    
    
    /**
     * Преди подготовка на данните за табличния изглед правим филтриране
     * на записите, които са (или не са) оттеглени и сортираме от нови към стари
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        if($data->query) {
            if(Request::get('Rejected')) {
                $data->query->where("#state = 'rejected'");
            } else {
                $data->rejQuery = clone($data->query);
                $data->query->where("#state != 'rejected' || #state IS NULL");
                $data->rejQuery->where("#state = 'rejected'");
            }
        }
        
        $data->query->orderBy('#createdOn', 'DESC');
    }
    
    
    /**
     * Изпълнява се преди запис
     */
    function on_BeforeSave($mvc, $id, $rec, $fields = NULL)
    {
        // Ако създаваме нов документ и ...
        if(!$rec->id) {
            
            // ... този документ няма ключ към папка и нишка, тогава
            // извикваме метода за рутиране на документа
            if(!isset($rec->folderId) || !isset($rec->threadId)) {
                $mvc->route($rec);
            }
            
            // ... този документ няма ключ към контейнер, тогава 
            // създаваме нов контейнер за документите от този клас 
            // и записваме връзка към новия контейнер в този документ
            if(!isset($rec->containerId)) {
                $rec->containerId = doc_Containers::create($mvc, $rec->threadId, $rec->folderId, $rec->createdOn);
            }
            
            // Задаваме началното състояние по подразбиране
            if (!$rec->state) {
                $rec->state = $mvc->firstState ? $mvc->firstState : 'draft';
            }
            
            // Задаваме стойностите на created полетата
            $rec->createdBy = Users::getCurrent() ? Users::getCurrent() : 0;
            $rec->createdOn = dt::verbal2Mysql();
        }
        
        // Задаваме стойностите на полетата за последно модифициране
        $rec->modifiedBy = Users::getCurrent() ? Users::getCurrent() : 0;
        $rec->modifiedOn = dt::verbal2Mysql();
    }
    
    
    /**
     * Изпълнява се след запис на документ.
     * Ако е може се извиква обновяването на контейнера му
     */
    static function on_AfterSave($mvc, &$id, $rec, $fields = NULL)
    {
        try {
            
            // Опитваме се да запишем файловете от документа в модела
            doc_Files::saveFile($mvc, $rec);    
        } catch (Exception $e) {
            
            // Ако възникне грешка при записването
            doc_Files::log("Грешка при записване на файла с id={$id}");
        }
        
        // Изтрива от кеша html представянето на документа
        $key = 'Doc' . $rec->id . '%';
        core_Cache::remove($mvc->className, $key);
        
        // Намира контейнера на документа
        $containerId = $rec->containerId ? $rec->containerId : $mvc->fetchField($rec->id, 'containerId');
            
        // Възстановяваме (ако е необходимо) нишката ПРЕДИ да създадем/обновим контейнера
        // Това гарантира, че абонатите на оттеглени нишки все пак ще получат нотификация за
        // новопристигналия документ
        if ($rec->threadId && $rec->state != 'rejected') {
            doc_Threads::restoreThread($rec->threadId);
        }
        
        // Ако е намерен контейнера - обновява го
        if($containerId) {
            doc_Containers::update($containerId);
        }
        
        if($rec->state != 'draft'){
        	
	    	$usedDocuments = $mvc->getUsedDocs($rec->id);
	    	if(count($usedDocuments)){
	    		$Log = cls::get('log_Documents');
	    		foreach($usedDocuments as $used){
	    			if($rec->state == 'rejected'){
	    				$Log::cancelUsed($used->class, $used->id, $mvc, $rec->id);
	    			} else {
	    				$Log::used($used->class, $used->id, $mvc, $rec->id);
	    			}
	    		}
	    	}
        }
    }
    
    
    /**
     * Ако в документа няма код, който да рутира документа до папка/тред,
     * долния код, рутира документа до "Несортирани - [заглавие на класа]"
     */
    function on_AfterRoute($mvc, &$res, $rec)
    {
        // Ако имаме контейнер, но нямаме тред - определяме треда от контейнера
        if($rec->containerId && !$rec->threadId) {
            $tdRec = doc_Containers::fetch($rec->containerId);
            $rec->threadId = $tdRec->threadId;
        }
        
        // Ако имаме тред, но нямаме папка - определяме папката от контейнера
        if($rec->threadId && !$rec->folderId) {
            $thRec = doc_Threads::fetch($rec->threadId);
            $rec->folderId = $thRec->folderId;
        }
        
        // Ако нямаме папка - форсираме папката по подразбиране за този клас
        if(!$rec->folderId) {
            $rec->folderId = $mvc->getDefaultFolder();
        }
        
        // Ако нямаме тред - създаваме нов тред в тази папка
        if(!$rec->threadId) {
            $rec->threadId = doc_Threads::create($rec->folderId, $rec->createdOn);
        }
        
        // Ако нямаме контейнер - създаваме нов контейнер за 
        // този клас документи в определения тред
        if(!$rec->containerId) {
            $rec->containerId = doc_Containers::create($mvc, $rec->threadId, $rec->folderId, $rec->createdOn);
        }
    }
    
    
    /**
     * Дефолт имплементация на метода $doc->getDefaultFolder()
     *
     * Ако $mvc->defaultFolder != FALSE, тогава връща папка 'куп' с име - $mvc->defaultFolder или заглавието на класа
     * Ако $mvc->defaultFolder === FALSE или нямаме достъп до папката 'куп', тогава се връща основната папка за всеки потребител
     * 
     * @param core_Mvc $mvc
     * @param int $folderId
     * @param int $userId
     * @param boolean $bForce FALSE - не прави опит да създаде папката по подразбиране, в случай
     *                                че тя не съществува
     */
    function on_AfterGetDefaultFolder($mvc, &$folderId, $userId = NULL, $bForce = TRUE)
    {
        if (!$folderId) {
            if($mvc->defaultFolder !== FALSE) {
                $unRec = new stdClass();
                $unRec->name = $mvc->defaultFolder ? $mvc->defaultFolder : $mvc->title;
                
                $folderId = doc_UnsortedFolders::forceCoverAndFolder($unRec, $bForce);
            }

            // Ако текущия потребител няма права за тази папка, или тя не е определена до сега,
            // То 'Unsorted' папката е дефолт папката на потребителя
            if(!$folderId || !doc_Folders::haveRightFor('single', $folderId)) {
                $folderId = doc_Folders::getDefaultFolder($userId);
            }
        }
    }
    
    
    /**
     * Ако няма метод в документа, долния код сработва за да осигури титла за нишката
     */
    function on_AfterGetDocumentTitle($mvc, &$res, $rec, $escaped = TRUE)
    {
        if(!$res) {
            $res = $mvc->getRecTitle($rec, $escaped);
        }
    }
    
    
    /**
     * Когато действието е предизвикано от doc_Thread изглед, тогава
     * връщането е към single изгледа, който от своя страна редиректва към
     * треда, при това с фокус на документа
     */
    function on_BeforePrepareRetUrl($mvc, &$res, $data)
    {
        $retUrl = getRetUrl();
        
        if($retUrl['Ctr'] == 'doc_Containers' && is_a($mvc, 'core_Master') && $data->form->rec->id > 0) {
            $data->retUrl = toUrl(array($mvc, 'single', $data->form->rec->id));
            
            return FALSE;
        }
    }
    
    
    /**
     * Смяна статута на 'rejected'
     *
     * @return core_Redirect
     */
    function on_BeforeAction($mvc, &$res, $action)
    {
        if ($action == 'single' && !(Request::get('Printing'))) {
            
            expect($id = Request::get('id', 'int'));
            
            //$mvc->requireRightFor('single');
            
            expect($rec = $mvc->fetch($id));
            
            // Изтриваме нотификацията, ако има такава, свързани с този документ
            $url = array($mvc, 'single', 'id' => $id);
            bgerp_Notifications::clear($url);
            
            if($rec->threadId) {
                if(doc_Threads::haveRightFor('single', $rec->threadId)) {
                    
                    $hnd = $mvc->getHandle($rec->id);
                    $url = array('doc_Containers', 'list', 'threadId' => $rec->threadId, 'docId' => $hnd, 'Q' => Request::get('Q'), 'Cid' => Request::get('Cid'), '#' => $hnd);
                    
                    // Ако има подаден таб
                    if ($tab = Request::get('Tab')) {
                        
                        // Добавяме таба
                        $url['Tab'] = $tab;
                        
                        // Добавяме нова котва към детайлите на таба
                        $url['#'] = 'detailTabs';
                    }
                    
                    // Ако има страница на документа
                    if ($P = Request::get('P_log_Documents')) {
                        
                        // Добавяме страницата
                        $url['P_log_Documents'] = $P;
                    }
                    
                    if($nid = Request::get('Nid', 'int')) {
                        $url['Nid'] = $nid;
                    }
                    $res = new Redirect($url);
                    
                    return FALSE;
                }
            }
        }
        
        if ($action == 'reject') {
            
            $id  = Request::get('id', 'int');
            $rec = $mvc->fetch($id);
            
            if (isset($rec->id) && $rec->state != 'rejected' && $mvc->haveRightFor('reject', $rec)) {
                // Оттегляме документа + нишката, ако се налага
                if ($mvc->reject($rec)) {
                    $tRec = doc_Threads::fetch($rec->threadId);
                    
                    // Ако оттегляме първия документ в нишка, то оттегляме цялата нишка
                    if ($tRec->firstContainerId == $rec->containerId) {
                        $bSuccess = doc_Threads::rejectThread($rec->threadId);
                    }
                }
            }
                
            // Пренасочваме контрола
            if (!$res = getRetUrl()) {
                $res = array($mvc, 'single', $id);
            }
            
            $res = new Redirect($res); //'OK';
                
            return FALSE;
        }
        
        if ($action == 'restore') {
            
            $id  = Request::get('id', 'int');
            $rec = $mvc->fetch($id);
            
            if ($rec->state == 'rejected' && $mvc->haveRightFor('reject', $rec)) {
                // Възстановяваме документа + нишката, ако се налага
                if ($mvc->restore($rec)) {
                    $tRec = doc_Threads::fetch($rec->threadId);
                    
                    // Ако възстановяваме първия документ в нишка, то възстановяваме цялата нишка
                    if ($tRec->firstContainerId == $rec->containerId) {
                        doc_Threads::restoreThread($rec->threadId);
                    }
                }
                    
                // Пренасочваме контрола
                if (!$res = getRetUrl()) {
                    $res = array($mvc, 'single', $id);
                }
                
                $res = new Redirect($res); //'OK';
                
            }             
            
            return FALSE;
        }
    }
    
    
    /**
     * Оттегляне на документ
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param int|stdClass $id
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        $rec = $id;
        if (!is_object($rec)) {
            $rec = $mvc->fetch($id);
        }
        
        if ($rec->state == 'rejected') {
            return;
        }
        
        $rec->brState = $rec->state;
        $rec->state = 'rejected';
        
        $res = static::updateDocumentState($mvc, $rec);
    }
    
    
    /**
     * Възстановяване на оттеглен документ
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param int $id
     */
    public static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
    {
        $rec = $id;
        if (!is_object($rec)) {
            $rec = $mvc->fetch($id);
        }
        
        if ($rec->state != 'rejected') {
            return FALSE;
        }
        
        $rec->state = $rec->brState;
        
        $res = static::updateDocumentState($mvc, $rec);
    }


    /**
     * Запис на състоянието на документ в БД
     *
     * Използва се от @link doc_DocumentPlg::on_AfterReject() и
     * @link doc_DocumentPlg::on_AfterRestore()
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @return boolean
     */
    protected static function updateDocumentState($mvc, $rec)
    {
        if (!$mvc->save($rec, 'state, brState, containerId, modifiedOn, modifiedBy')) {

            return FALSE;
        }
        
        // Ако състоянието е било чернова, не е нужно да се минава от там,
        // защото не е добавена нотификация и няма нужда да се чисти
        if ($rec->brState != 'draft') {
            
            // Премахваме този документ от нотификациите
            $keyUrl = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
            bgerp_Notifications::setHidden($keyUrl, $rec->state == 'rejected' ? 'yes':'no');
        
            // Премахваме документа от "Последно"
            bgerp_Recently::setHidden('document', $rec->containerId, $rec->state == 'rejected' ? 'yes':'no');
        }
        
        $mvc->log($rec->state == 'rejected' ? 'reject' : 'restore', $rec->id);
        
        return TRUE;
    }
    
    
    /**
     * Връщана документа
     */
    function on_AfterGetHandle($mvc, &$hnd, $id)
    {
        if (is_object($id)) {
            $id = $id->id;
        }

        if(!$hnd) {
            $hnd = $mvc->abbr . $id;
        }
    }
    
    
    /**
     * Реализация по подразбиране на doc_DocumentIntf::fetchByHandle()
     * 
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @param array $parsedHandle
     */
    public static function on_AfterFetchByHandle($mvc, &$rec, $parsedHandle)
    {
        if (empty($rec)) {
            $rec = $mvc::fetch($parsedHandle['id']);
        }
    }
    
    
    function on_AfterGetContainer($mvc, &$res, $id)
    {
        $classId = core_Classes::getId($mvc);
        
        if (is_object($id)) {
            $id = $id->id;
        }
        
        $res = doc_Containers::fetch("#docId = {$id} AND #docClass = {$classId}");
    }
    
    
    /**
     * Връща линк към документа
     */
    function on_AfterGetLink($mvc, &$link, $id, $maxLength = FALSE, $attr = array())
    {
        $iconStyle = 'background-image:url(' . sbf($mvc->singleIcon, '') . ');';
        $url       = array($mvc, 'single', $id);
        if($attr['Q']) {
            $url['Q'] = $attr['Q'];
            unset($attr['Q']);
        }
        $row       = $mvc->getDocumentRow($id);
        $handle    = $mvc->getHandle($id);
        $type      = mb_strtolower($mvc->singleTitle);
        $rec       = $mvc->fetch($id);

        if($maxLength > 0) {
            $row->title = "#{$handle} - " . str::limitLen($row->title, $maxLength);
        } elseif($maxLength === 0) {
            $row->title = "#{$handle}";
        }

        if(!doc_Threads::haveRightFor('single', $rec->threadId) && !$mvc->haveRightFor('single', $rec)) {
            $url =  array();
        }
        
        $attr['class'] .= ' linkWithIcon';
        $attr['style'] .= $iconStyle;

        $link = ht::createLink("{$row->title}", $url, NULL, $attr);
    }
    
    
    /**
     * Подменя URL-то да сочи към single' а на документа. От там се редиректва в нишката.
     */
    function on_AfterPrepareRetUrl($mvc, $data)
    {
        //Ако създаваме копие, редиректваме до създаденото копие
        if (is_object($data->form) && $data->form->isSubmitted()) {
            //TODO променя URL'то когато записваме и нов имейл
            $data->retUrl = array($mvc, 'single', $data->form->rec->id);
        }
    }
    
    
    /**
     * Подготвя полетата threadId и folderId, ако има originId и threadId
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $rec = $data->form->rec;
        
        // Ако редактираме запис
        // В записа на формата "тихо" трябва да са въведени от Request originId, threadId или folderId   
        if($rec->id) { 
            $exRec = $mvc->fetch($rec->id);
            $mvc->threadId = $exRec->threadId;
            
            //Изискваме да има права
            doc_Threads::requireRightFor('single', $mvc->threadId);
        } elseif (Request::get('clone') && ($cloneId = Request::get('cloneId', 'int'))) {
            
            // Ако създаваме копие 
            
            // Данните за документната система
            $containerRec = doc_Containers::fetch($cloneId);
            
            // Очакваме да има такъв запис
            expect($containerRec);
            
            // Очакваме да имаме права за клониране
            expect($mvc->haveRightFor('clone', $containerRec->docId), 'Нямате права за създаване на копие');
            
            // Добавяме id' тата на нишките и папките
            $rec->threadId = $containerRec->threadId;
            $rec->folderId = $containerRec->folderId;
            
            // Първия запис в threada
            $firstContainerId = doc_Threads::fetchField($rec->threadId, 'firstContainerId');
            
            // Ако копираме първия запис в треда, тогава създаваме нов тред
            if ($firstContainerId == $cloneId) {
                
                // Премахваме id' то на треда за да се създаде нов
                unset($rec->threadId);
            }
            
            // Записите от БД
            $mvcRec = $mvc::fetch("#containerId = '{$cloneId}'");
            
            // Задаваме originId, на оригиналния документ originId
            $rec->originId = $mvcRec->originId;
            
            //Създаваме масив с всички полета, които ще клонираме
            $cloneFieldsArr = arr::make($mvc->cloneFields);
            
            // Ако има полета за клониране
            if (count($cloneFieldsArr)) {
                
                // Обхождаме всичките
                foreach ($cloneFieldsArr as $cloneField) {
                    
                    // Заместваме съдържанието на всички полета със записите от БД
                    $rec->$cloneField = $mvcRec->$cloneField;
                }
            }
        } elseif ($rec->originId) {
            
            // Ако имаме $originId
           
            expect($oRec = doc_Containers::fetch($rec->originId));
            
            // Трябва да имаме достъп до нишката на оригиналния документ
            doc_Threads::requireRightFor('single', $oRec->threadId);
            
            $rec->threadId = $oRec->threadId;
            $rec->folderId = $oRec->folderId;
            
            $data->form->layout = $data->form->renderLayout();
            $tpl = new ET("<div class='preview-holder'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr("Оригинален документ") . "</b></div><div class='scrolling-holder'>[#DOCUMENT#]</div></div>");
            
            // TODO: да се замени с интерфейсен метод
            
            $document = doc_Containers::getDocument($rec->originId);
            
            $docHtml = $document->getInlineDocumentBody();
            
            $tpl->append($docHtml, 'DOCUMENT');
            
            $data->form->layout->append($tpl);
        }
        
        if($rec->threadId) {
            doc_Threads::requireRightFor('single', $rec->threadId);
            $rec->folderId = doc_Threads::fetchField($rec->threadId, 'folderId');
        }
        
        if(!$rec->folderId) {
            $rec->folderId = $mvc->getDefaultFolder();
        }
        
        if(!$rec->threadId){
        	expect(doc_Folders::haveRightToFolder($rec->folderId));
        }
        
        $mvc->invoke('AfterPrepareDocumentLocation', array($data->form));
    }

    
	/**
     * Подготовка на бутоните на формата за добавяне/редактиране.
     *
     * @param core_Manager $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        if (empty($data->form->rec->id) && $data->form->rec->threadId && $data->form->rec->originId) {
            $folderId = ($data->form->rec->folderId) ? $data->form->rec->folderId : doc_Threads::fetchField('folderId');
        	
            if($mvc->canAddToFolder($folderId) && $mvc->onlyFirstInThread !== FALSE){
            	$data->form->toolbar->addSbBtn('Нова нишка', 'save_new_thread', 'id=btnNewThread,order=9.99985','ef_icon = img/16/save_and_new.png');
            }
        }
    }

    
    /**
     *
     */
    static function on_AfterInputEditForm($mvc, $form)
    {   
        //Добавяме текст по подразбиране за титлата на формата
        if ($form->rec->folderId) {
            $fRec = doc_Folders::fetch($form->rec->folderId);
            $title = tr(mb_strtolower($mvc->singleTitle)) . ' |в|* ' . doc_Folders::recToVerbal($fRec)->title;
        }
        
        $rec = $form->rec;
        
        $in = ' |в|* ';

        if($form->rec->id) {
            $form->title = 'Редактиране на|* ';
        } else {
            if(Request::get('clone')) {
                $form->title = 'Копие на|* ';
            } else {
                if($rec->threadId) {
                    $form->title = 'Добавяне на|* ';
                    $in = ' |към|* ';
                } else {
                    $form->title = 'Създаване на|* ';
                }
            }
        }
        
        if($rec->threadId) {
            $thRec = doc_Threads::fetch($form->rec->threadId);
            
            if($thRec->firstContainerId != $form->rec->containerId) {
                $title = tr(mb_strtolower($mvc->singleTitle)) . $in . doc_Threads::recToVerbal($thRec)->title;
            }
        }
       
        $form->title .= $title;
        
    	if($form->isSubmitted()){
	        if($form->cmd == 'save_new_thread' && $rec->threadId){
		        unset($rec->threadId);
		    }
        }
     }
    
     
    /**
     * Рендиране на документи за вътрешно представяне
     */
    function on_AfterGetInlineDocumentBody($mvc, &$res, $id, $mode = 'html', $options = NULL)
    {
        expect($mode == 'plain' || $mode == 'html' || $mode == 'xhtml');
        
        // Задаваме `text` режим според $mode. singleView-то на $mvc трябва да бъде генерирано
        // във формата, указан от `text` режима (plain или html)
        Mode::push('text', $mode);
        
        if (!Mode::is('text', 'html')) {
            
            // Временна промяна на текущия потребител на този, който е активирал документа
            $bExitSudo = core_Users::sudo($mvc->getContainer($id)->activatedBy);
        }
        
        // Ако възникне изключение
        try {
            // Подготвяме данните за единичния изглед
            $data = $mvc->prepareDocument($id, $options);
            
            $data->noToolbar = !$options->withToolbar;
            
            $res  = $mvc->renderDocument($id, $data);
        } catch (Exception $e) {
            
            // Ако сме в SUDO режим
            if ($bExitSudo) {
                
                // Възстановяване на текущия потребител
                core_Users::exitSudo();
            }
            
            expect(FALSE, $e);
        }
        
        // Ако сме в SUDO режим
        if ($bExitSudo) {
            
            // Възстановяване на текущия потребител
            core_Users::exitSudo();
        }
        
        // Връщаме старата стойност на 'printing' и 'text'
        Mode::pop('text');
    }
    
    
    /**
     * HTML или plain text изгледа на документ при изпращане по емайл.
     *
     * Това е реализацията по подразбиране на интерфейсния метод doc_DocumentIntf::getDocumentBody()
     * Използва single view на мениджъра на документа.
     *
     * @param core_Mvc $mvc мениджър на документа
     * @param core_ET $res генерирания текст под формата на core_ET шаблон
     * @param int $id първичния ключ на документа - key(mvc=$mvc)
     * @param string $mode `plain` или `html`
     * @access private
     */
    function on_AfterGetDocumentBody($mvc, &$res, $id, $mode = 'html', $options = NULL)
    {
        expect($mode == 'plain' || $mode == 'html' || $mode == 'xhtml');
        
        // Емулираме режим 'printing', за да махнем singleToolbar при рендирането на документа
        Mode::push('printing', TRUE);
                
        // Задаваме `text` режим според $mode. singleView-то на $mvc трябва да бъде генерирано
        // във формата, указан от `text` режима (plain или html)
        Mode::push('text', $mode);
        
        if (!Mode::is('text', 'html')) {
            // Временна промяна на текущия потребител на този, който е активирал документа
            $bExitSudo = core_Users::sudo($mvc->getContainer($id)->activatedBy);
        }
        
        // Ако възникне изключение
        try {
            // Подготвяме данните за единичния изглед
            $data = $mvc->prepareDocument($id, $options);
            $res  = $mvc->renderDocument($id, $data);
        } catch (Exception $e) {
            
            // Ако сме в SUDO режим
            if ($bExitSudo) {
                
                // Възстановяване на текущия потребител
                core_Users::exitSudo();
            }
            
            expect(FALSE, $e);
        }
        
        // Ако сме в SUDO режим
        if ($bExitSudo) {
            
            // Възстановяване на текущия потребител
            core_Users::exitSudo();
        }
        
        // Връщаме старата стойност на 'printing' и 'text'
        Mode::pop('text');
        Mode::pop('printing');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * Забранява изтриването на вече използвани сметки
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако добавяме
        if ($action == 'add') {
            
            // Ако има нишка
            if ($rec->threadId) {
                
                // Ако няма права за добавяне в нишката
                if($mvc->canAddToThread($rec->threadId) === FALSE){
    	            
                    // Никой не може да добавя
    				$requiredRoles = 'no_one';
    			}        
            } elseif ($rec->folderId) {
                
                // Ако създаваме нова нишка
                
                // Ако няма права за добавяне в папката
                if($mvc->canAddToFolder($rec->folderId) === FALSE){
                    
                    // Никой не може да добавя
    				$requiredRoles = 'no_one';
    			}    
            }
        }
		
    	if ($rec->id) {
            $oRec = $mvc->fetch($rec->id);
            
            if($action == 'delete') {
                $requiredRoles = 'no_one';
            } elseif(($action == 'edit') && ($oRec->state != 'draft')) {
                $requiredRoles = 'no_one';
            } elseif ($action == 'reject'  || $action == 'restore') {
                if (doc_Threads::haveRightFor('single', $oRec->threadId, $userId)) {
                    if($requiredRoles != 'no_one'){
                    	$requiredRoles = 'powerUser';
                    }
                } else {
                    $requiredRoles = 'no_one';
                } 
            } elseif ($action == 'single') { 
                if (doc_Threads::haveRightFor('single', $oRec->threadId, $userId)) {
                    $requiredRoles = 'user';
                }
            } elseif ($action == 'clone') {
                
                // Ако клонираме
                
                // id на първия документ
                $firstContainerId = doc_Threads::fetch($oRec->threadId)->firstContainerId;
                
                // Ако е първи документ в нишката
                if ($firstContainerId == $oRec->containerId) {
                    
                    // Проверяваме за сингъл права в папката
                    $haveRightForClone = doc_Folders::haveRightFor('single', $oRec->folderId);
                } else {
                    
                    // За останалите, проверяваме за сингъл в нишката
                    $haveRightForClone = doc_Threads::haveRightFor('single', $oRec->threadId);
                }
                
                // Ако един от двата начина върне, че имаме права
                if ($haveRightForClone) {
                
                    // Задаваме права
                    $requiredRoles = 'powerUser';
                }
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function on_AfterPrepareDocument($mvc, &$data, $id, $options = NULL)
    {
        if ($data) return;
        
        // Създаваме обекта $data
        $data = new stdClass();
        
        if (is_object($id)) {
            $id = $id->id;
        }
        
        // Ако сме подали $rec'a в опциите, с променени данни (за бласта)
        if ($options->rec->id == $id) {
        
            // Използваме rec'а в опциите
            $data->rec = $options->rec;    
        } else {
            
            // Трябва да има $rec за това $id
            expect($data->rec = $mvc->fetch($id));
        }
        
//        $data->cacheKey = 'Doc' . $data->rec->id . Mode::get('text') . Mode::get('printing');
//        $data->threadCachedView = core_Cache::get($mvc->className, $data->cacheKey);
        
//        if($data->threadCachedView === FALSE) {
            // Подготвяме данните за единичния изглед
            $mvc->prepareSingle($data);
//        }
        
        // MID се генерира само ако :
        //     o подготвяме документа за изпращане навън - !Mode::is('text', 'html')
        //     o има зададен екшън - log_Documents::hasAction()
        if (!Mode::is('text', 'html') && log_Documents::hasAction()) {
            if (!isset($options->rec->__mid)) {
                
                // Ако няма стойност
                if (!isset($data->__MID__)) {
                    
                    // Тогава да се запише нов екшън
                    $data->__MID__ = log_Documents::saveAction(
                        array('containerId' => $data->rec->containerId)
                    );    
                }
                
                if (is_object($options)) {
                    
                    // Ако не е обект, създаваме го
                    if (!is_object($options->rec)) $options->rec = new stdClass();
                    
                    $options->rec->__mid = $data->__MID__;
                }
            }
        }
        
        if (!isset($data->__MID__) && isset($options->rec->__mid)) {
            $data->__MID__ = $options->rec->__mid;
        }
    }
    
    
    /**
     * Кешира и използва вече кеширани рендирани изгледи на документи
     */
    function on_AfterRenderDocument($mvc, &$tpl, $id, $data)
    {
        if($tpl) return;
        
//        if($data->threadCachedView === FALSE) {
            $tpl = $mvc->renderSingle($data);
            if ($data->rec->_resending) {
                $tpl->append(tr($data->rec->_resending), '_resending');    
            }
            $tpl->removeBlocks();
            $tpl->removePlaces();
            
//            if(in_array($data->rec->state, array('closed', 'rejected', 'active', 'waiting', 'open'))) {
//                core_Cache::set($mvc->className, $data->cacheKey, $tpl, isDebug() ?  0.1 : 5);
//            }
//        } else {
//            $tpl = $data->threadCachedView;
//        }
        
        // Заместване на MID. Това няма да се изпълни ако сме в Print Preview. Не може да се
        // премести и в on_AfterRenderSingle, защото тогава ще се кешира стойността на MID,
        // което е неприемливо
        $tpl->content = str_replace(static::getMidPlace(), $data->__MID__, $tpl->content);
    }


    function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        if (Request::get('Printing')) {
            // Заместваме MID само ако сме в Print Preview!
            //  
            // Причината е, резултата от този метод (а следователно и конкретната стокност на MID)
            // в някои случаи се кешира, а това не бива да се случва!
            $tpl->content = str_replace(static::getMidPlace(), $data->__MID__, $tpl->content);
        }
    }
    
    
    /**
     * Изпълнява се, акодефиниран метод getContragentData
     */
    function on_AfterGetContragentData($mvc, $data, $id)
    {
        
        return NULL;
    }
    
    
    /**
     * Изпълнява се, ако е дефиниран метод getContragentData
     */
    function on_AfterGetDefaultEmailBody($mvc, $data, $id)
    {
        
        return NULL;
    }
    
    
    /**
     * Реализация по подразбиране на интерфейсния метод ::getThreadState()
     *
     * TODO: Тук трябва да се направи проверка, дали документа е изпратен или отпечатан
     * и само тогава да се приема състоянието за затворено
     */
    function on_AfterGetThreadState($mvc, &$state, $id)
    {   
        if(!$state) {
            $state = 'closed';
        } 
    }
    
    
    /**
     * Реализация по подразбиране на интерфейсния метод ::getShared()
     * За фунцкии, които не използват doc_SharablePlg
     */
    function on_AfterGetShared($mvc, &$shared, $id)
    {  
        
    }  
    
        
    /**
     * Реализация по подразбиране на интерфейсния метод ::canAddToFolder()
     */
    function on_AfterCanAddToFolder($mvc, &$res, $folderId)
    {
        $res = TRUE;
    }
    
    
    /**
     * Реализация по подразбиране на интерфейсния метод ::canAddToThread()
     */
    function on_AfterCanAddToThread($mvc, &$res, $threadId)
    {
        $res = !($mvc->onlyFirstInThread);
    }
    
    
    /**
     * Връща името на документа с разширение .pdf и стойности 'off'
     *
     * @return array $res - Масив с типа (разширението) на файла и стойност указваща дали е избрана 
     *                      по подразбиране
     */
    function on_AfterGetPossibleTypeConvertings($mvc, &$res, $id)
    {
        //Превръщаме $res в масив
        $res = (array)$res;
        
        //Вземаме данните
        $rec = $mvc::fetch($id);
        
        //Обхождаме всички полета
        foreach ($mvc->fields as $field) {
            
            //Проверяваме дали е инстанция на type_RIchtext
            if ($field->type instanceof type_Richtext) {
                
                //Името на полето
                $fieldName = $field->name;
                
                //Имената на намерените документи
                $names = doc_RichTextPlg::getAttachedDocs($rec->$fieldName);

                if (count($names)) {
                    foreach ($names as $name=>$doc) {
                        $res += $doc['mvc']->getTypeConvertingsByClass($doc['rec']->id);
                    }
                }
            }
        }
    }
    
    
    /**
     * Метод по подразбиране за връщане на възможните файлове за прикачване.
     * По подразбиране всики имат възможност за прикачане на pdf.
     */
    function on_AfterGetTypeConvertingsByClass($mvc, &$res, $id)
    {
        //Превръщаме $res в масив
        $res = (array)$res;
        
        // Вземаме манипулатора на файла
        $name = $mvc->getHandle($id);
        
        //Името на файла е с големи букви, както са документите
        $name = strtoupper($name) . '.pdf';
        
        //Задаваме полето за избор, да не е избран по подразбиране
        $res[$name] = 'off';
    }
    
    
    /**
     * Реализация по подразбиране на doc_DocumentIntf::convertTo()
     * 
     * @param core_Mvc $mvc
     * @param array $res масив с манипулатор на файл (@see fileman)
     * @param int $id първичен ключ на документа
     * @param string $type формат, в който да се генерира съдържанието на док.
     * @param string $fileName име на файл, в който да се запише резултата
     */
    static function on_AfterConvertTo($mvc, &$res, $id, $type, $fileName = NULL)
    {
        // Преобразуваме в масив
        $res = (array)$res;
        
        if (!isset($fileName)) {
            expect($mvc->abbr, 'Липсва зададена абревиатура за документния клас ' . get_class($mvc));
            
            $fileName = strtoupper($mvc->abbr);
            if (!empty($type)) {
                $fileName .= '.' . $type;
            }
        }
        
        switch (strtolower($type)) {
            case 'pdf':
                log_Documents::pushAction(
                    array(
                        'action' => log_Documents::ACTION_PDF,
                        'containerId' => $mvc->getContainer($id)->id,
                    )
                );
                
                Mode::push('pdf', TRUE);

                $html = $mvc->getDocumentBody($id, 'xhtml');
                
                Mode::pop('pdf');

                log_Documents::popAction();
                
                //Манипулатора на новосъздадения pdf файл
                $fileHnd = doc_PdfCreator::convert($html, $fileName);
                
                if ($fileHnd) {
                    $res[$fileHnd] = $fileHnd;
                }
                
            break;
        }
    }
    
    
	/**
     * Конвертира документа към pdf файл и връща манипулатора му
     *
     * @param string $fileName - Името на файла, без разширението
     * @param string $type     - Разширението на файла
     *
     * return array $res - Масив с fileHandler' и на документите
     * @deprecated
     * @see doc_DocumentIntf::convertTo()
     */
    function on_AfterConvertDocumentAsFile($mvc, &$res, $id, $fileName, $type)
    {
        //Превръщаме $res в масив
        $res = (array)$res;
        
        if (strtolower($type) != 'pdf') return ;
        
        //Емулираме режим 'printing', за да махнем singleToolbar при рендирането на документа
        Mode::push('printing', TRUE);
        
        //Емулираме режим 'xhtml', за да покажем статичните изображения
        Mode::push('text', 'xhtml');
        
        //Вземаме информация за документа, от имена на файла - името на класа и id' to
        $fileInfo = doc_RichTextPlg::getFileInfo($fileName);
        
        //Ако не може да се намери информация, тогава се прескача
        if (!$fileInfo) return;
        
        //Името на класа
        $className = $fileInfo['className'];
        
        //Вземаме containerId' то на документа
        $containerId = $className::fetchField($fileInfo['id'], 'containerId');
        
        //Ако няма containerId - прескачаме
        if (!$containerId) return;
        
        //Вземаме документа
        $document = doc_Containers::getDocument($containerId);
        
        //Данните на документа
        $data = $document->prepareDocument();
        
        //Рендираме документа
        $html = $document->renderDocument($data);
        
        $html = $mvc->getDocumentBody($id, 'xhtml');
        
        //Манипулатора на новосъздадения pdf файл
        $fh = doc_PdfCreator::convert($html, $fileName);
        
        //масив с всички pdf документи и имената им
        $res[$fh] = $fh;
        
        //Връщаме старата стойност на 'text'
        Mode::pop('text');
        
        //Връщаме старата стойност на 'printing'
        Mode::pop('printing');         
    }
        
    
    /**
     * @todo Чака за документация...
     */
    function on_AfterGetSearchKeywords($mvc, &$res, $id)
    {
        if ($res) {
            
            return;
        }
        
        if(is_object($id)) {
            $rec = $id;
        } else {
            $rec = $mvc->fetch($id);
        }
        
        $res = plg_Search::getKeywords($mvc, $rec);
    }
    
    
    static function getMidPlace() 
    {
        return '__MID__';        
    }
    
    
    /**
     * Метод по подразбиране, за намиране на прикачените файлове в документите.
     * Извиква се само ако метода getAttachments($id) не съществува в класа
     */
    function on_AfterGetAttachments($mvc, &$res, $rec)
    {
        // Ако не е обект, тогава вземаме записите за съответния документ
        if (!is_object($rec)) {
            $rec = $mvc->fetch($rec);
        }
        
        // Ако има тяло
        if ($rec->body) {
            
            // Вземаме всички прикачени файлове
            $res = fileman_RichTextPlg::getFiles($rec->body);    
        }
        
        return $res;
    }
    
    
   /**
    * Метод по подразбиране за намиране на прикачените файлове в документ
    * 
    * @param object $mvc - 
    * @param array $res - Масив с откритете прикачените файлове
    * @param integer $rec - 
    */
    function on_AfterGetLinkedFiles($mvc, &$res, $rec)
    {
        if (is_object($rec)) {
            $id = $rec->id;
        } else {
            $id = $rec;
        }
        // Вземаме документа
        $data = $mvc->prepareDocument($id);

        // Намираме прикачените файлове
        $res = array_merge(fileman_RichTextPlg::getFiles($data->rec->body), (array)$res);
    }
    
    
    public static function on_AfterGetLinkedDocuments($mvc, &$res, $id)
    {
        core_Users::sudo($mvc->getContainer($id)->activatedBy);
        
        // Вземаме документа
        $data = $mvc->prepareDocument($id);
        
        // Намираме прикачените документи
        $attachedDocs = doc_RichTextPlg::getAttachedDocs($data->rec->body);
        if (count($attachedDocs)) {
            $attachedDocs = array_keys($attachedDocs);
            $attachedDocs = array_combine($attachedDocs, $attachedDocs);    
        }
        
        $res = array_merge($attachedDocs, (array)$res);
        
        core_Users::exitSudo();
    }


    /**
     * Връща URL към единичния изглед на мастера
     */
    function on_AfterGetRetUrl($mvc, &$res, $rec)
    {
        $master = $mvc->getMasterMvc($rec);
        $masterKey = $mvc->getMasterKey($rec);

        $url = array($master, 'single', $rec->{$masterKey});

        $res = $url;
    }
    
    
    /**
     * Прихваща извикването на AfterSaveLogChange в change_Plugin
     * 
     * @param core_MVc $mvc
     * @param array $recsArr - Масив със записаните данни
     */
    function on_AfterSaveLogChange($mvc, $recsArr)
    {
        // Отбелязване в лога
        log_Documents::changed($recsArr);
    }
    
    
    /**
     * Връща документа, породил зададения документ - реализация по подразбиране
     *
     * @param core_Mvc $mvc
     * @param object $origin
     * @param int|object $id
     * @param string $intf
     * @return NULL|core_ObjectReference
     */
    public static function on_AfterGetOrigin(core_Mvc $mvc, &$origin, $rec, $intf = NULL)
    {
        if (isset($origin)) {
            return;
        }
        
        $rec = $mvc->fetchRec($rec);
        
        $origin = doc_Threads::getFirstDocument($rec->threadId);
    }
    
    
    /**
     * Реализация по подразбиране на метод getDescendants()
     * 
     * Метода връща референции към документите (от всевъзможни типове), които са от същата нишка
     * 
     * @param core_Mvc $mvc
     * @param array $chain масив от core_ObjectReference
     * @param int $originDocId key(mvc=$mvc)
     */
    public static function on_AfterGetDescendants(core_Mvc $mvc, &$chain, $originDocId)
    {
        $chain = array();
        
        /* @var $query core_Query */
        $query = doc_Containers::getQuery();
        
        $threadId   = $mvc->fetchField($originDocId, 'threadId');
        $docClassId = $mvc->getClassId();
        
        $chainContainers = $query->fetchAll("
            #threadId = {$threadId} 
            AND #docId <> {$originDocId} 
            AND #docClass <> {$docClassId}
        ", 'id, originId');
        
        foreach ($chainContainers as $cc) {
            $chain[] = doc_Containers::getDocument($cc->id);
        }
    }
    
    
    /**
     * Реализация по подразбиране на интерфейсния метод ::getUsedDocs()
     * Намира всички цитирания на документи в полета Richtext
     * и ги подготвя във вид подходящ за маркиране като използвани
     */
    function on_AfterGetUsedDocs($mvc, &$res, $id)
    {
    	$rec = $mvc->fetch($id);
    	$docs = doc_RichTextPlg::getDocsInRichtextFields($mvc, $rec);
    	if(count($docs)){
	    	foreach ($docs as $doc){
	    		$res[] = (object)array('class' => $doc['mvc'], 'id' => $doc['rec']->id);
	    	}
    	}
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    function on_AfterGetAllowedFolders($mvc, &$res)
    {
    	if(empty($res)){
    		return NULL;
    	} 
    }
    
    
    /**
     * Дефолт метод филтриращ опциите от корици на папка в които
     * може да се постави даден документ
     * @param core_Mvc $coverClass - Корица на папка за която филтрираме записите
     */
    function on_AfterGetCoverOptions($mvc, &$res, $coverClass)
    {
    	if(empty($res)){
    		$res = $coverClass::makeArray4Select(NULL, "#state != 'rejected'");
    	}
    }
    
    
	/**
     * Метод по подразбиране
     * Връща иконата на документа
     */
    function on_AfterGetIcon($mvc, &$res, $id = NULL)
    {
        if(!$res) { 
            $res = $mvc->singleIcon;
        }
    }
}
