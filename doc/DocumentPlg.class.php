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
        'waiting'  => 'Чакащо',
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
        // Добавяме полета свързани с организацията на документооборота
        $mvc->FLD('folderId' , 'key(mvc=doc_Folders,select=title)', 'caption=Папка,input=none,column=none,silent,input=hidden');
        $mvc->FLD('threadId', 'key(mvc=doc_Threads)', 'caption=Нишка->Топик,input=none,column=none,silent,input=hidden');
        $mvc->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Нишка->Документ,input=none,column=none,oldFieldName=threadDocumentId');
        $mvc->FLD('originId', 'key(mvc=doc_Containers)',
            'caption=Нишка->Оригинал,input=hidden,column=none,silent,oldFieldName=originContainerId');
        
        // Ако липсва, добавяме поле за състояние
        if (!$mvc->fields['state']) {
            $mvc->FLD('state',
                cls::get('type_Enum', array('options' => self::$stateArr)),
                'caption=Състояние,column=none,input=none');
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
    }
    
    
    /**
     * Изпълнява се след подготовката на единичния изглед
     * Подготвя иконата за единичния изглед
     */
    function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        $data->row->iconStyle = 'background-image:url("' . sbf($mvc->singleIcon, '', Mode::is('text', 'xhtml') || Mode::is('printing')) . '");';
        
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
                'id=btnDelete,class=btn-reject,warning=Наистина ли желаете да оттеглите документа?,order=32');
        }
        
        if (isset($data->rec->id) && $mvc->haveRightFor('reject', $data->rec) && ($data->rec->state == 'rejected')) {
            $data->toolbar->removeBtn("*");
            $data->toolbar->addBtn('Възстановяване', array(
                    $mvc,
                    'restore',
                    $data->rec->id,
                    'ret_url' => TRUE
                ),
                'id=btnRestore,class=btn-restore,warning=Наистина ли желаете да възстановите документа?,order=32');
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
                    'class=btn-posting');
            }
        } else {
            //Ако сме в състояние чернова, тогава не се показва бутона за принтиране
            //TODO да се "премахне" и оптимизира
            $data->toolbar->removeBtn('btnPrint');
        }
        
        //Добавяме бутон за клониране ако сме посочили, кои полета ще се клонират
        if (($mvc->cloneFields) && ($data->rec->id)) {
            if (($data->rec->state != 'draft') && ($mvc->haveRightFor('clone'))) {
                $retUrl = array($mvc, 'single', $data->rec->id);
                
                // Бутон за клониране
                $data->toolbar->addBtn('Копие', array(
                        $mvc,
                        'add',
                        'originId' => $data->rec->containerId,
                        'Clone' => 'clone',
                        'ret_url'=>$retUrl
                    ),
                    'class=btn-clone, order=14');
            }
        }
    }
    
    
    /**
     * Добавя бутон за показване на оттеглените записи
     */
    function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if(Request::get('Rejected')) {
            $data->toolbar->removeBtn('*');
            $data->toolbar->addBtn('Всички', array($mvc), 'id=listBtn,class=btn-list');
        } else {
            $data->toolbar->addBtn('Кош', array($mvc, 'list', 'Rejected' => 1), 'id=binBtn,class=btn-bin,order=50');
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
        
        $row->modifiedDate = dt::mysql2verbal($rec->modifiedOn, 'd-m-Y');
        $row->createdDate = dt::mysql2verbal($rec->createdOn, 'd-m-Y');
        
        //$fields = arr::make($fields);
        
        if($fields['-single']) {
            if(!$row->ident) {
                $row->ident = '#' . $invoker->getHandle($rec->id);
            }
            
            if(!$row->singleTitle) {
                $row->singleTitle = tr($invoker->singleTitle);
            }
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
                $data->query->where("#state != 'rejected' || #state IS NULL");
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
    static function on_AfterSave($mvc, $id, $rec, $fields = NULL)
    {
        // Изтрива от кеша html представянето на документа
        $key = 'Doc' . $rec->id . '%';
        core_Cache::remove($mvc->className, $key);
        
        // Намира контейнера на документа
        $containerId = $rec->containerId ? $rec->containerId : $mvc->fetchField($rec->id, 'containerId');
        
        // Ако е намерен контейнера - обновява го
        if($containerId) {
            doc_Containers::update($containerId);
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
            $rec->folderId = $mvc->getUnsortedFolder();
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
     * Дефолт имплементация на метода $doc->getUnsortedFolder()
     *
     * Връща или съсдава папка от тип "Кюп", която има име -
     * заглавието на мениджъра на документите
     */
    function on_AfterGetUnsortedFolder($mvc, &$res)
    {
        if (!$res) {
            $unRec = new stdClass();
            $unRec->name = $mvc->title;
            $res = doc_UnsortedFolders::forceCoverAndFolder($unRec);
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
        if($action == 'single' && !(Request::get('Printing'))) {
            
            expect($id = Request::get('id', 'int'));
            
            //$mvc->requireRightFor('single');
            
            // Логваме, че този потребител е отворил този документ
            $rec = $mvc->fetch($id);
            
            // Изтриваме нотификацията, ако има такава, свързани с този документ
            $url = array($mvc, 'single', 'id' => $id);
            bgerp_Notifications::clear($url);
            
            if($rec->threadId) {
                if(doc_Threads::haveRightFor('single', $rec->threadId)) {
                    
                    $hnd = $mvc->getHandle($rec->id);
                    $url = array('doc_Containers', 'list', 'threadId' => $rec->threadId, 'docId' => $hnd, '#' => $hnd);
                    
                    if($nid = Request::get('Nid', 'int')) {
                        $url['Nid'] = $nid;
                    }
                    $res = new Redirect($url);
                    
                    return FALSE;
                }
            }
        }
        
        if($action == 'reject') {
            
            $id = Request::get('id', 'int');
            
            $mvc->requireRightFor('reject', $id);
            
            $rec = $mvc->fetch($id);
            
            $mvc->requireRightFor('reject', $rec);
            
            $res = new Redirect(array($mvc, 'single', $id));
            
            if($rec->state != 'rejected') {
                
                $mvc->reject($rec->id);
                
                // Ако оттегляме първия постинг на нишката, то цялата ниша се оттегля
                $tRec = doc_Threads::fetch($rec->threadId);
                
                if($tRec->firstContainerId == $rec->containerId) {
                    
                    $cQuery = doc_Containers::getQuery();
                    
                    while($cRec = $cQuery->fetch("#threadId = {$rec->threadId}")) {
                        
                        if($rec->containerId == $cRec->id) continue;
                        
                        if($cRec->state != 'rejected') {
                            $document = doc_Containers::getDocument($cRec->id);
                            $document->reject();
                        }
                    }
                    
                    $tRec->state = 'rejected';
                    
                    doc_Threads::save($tRec);
                    
                    // Обновяваме съдържанието на папката
                    doc_Folders::updateFolderByContent($tRec->folderId);
                    
                    $res = new Redirect(array('doc_Threads', 'folderId' => $tRec->folderId));
                }
            }
            
            return FALSE;
        }
        
        if($action == 'restore') {
            
            $id = Request::get('id', 'int');
            
            $rec = $mvc->fetch($id);
            
            if (isset($rec->id) && $mvc->haveRightFor('reject', $rec) && ($rec->state == 'rejected')) {
                
                $mvc->reject($rec->id, 'restore');
                
                // Ако възстановяваме първия постинг на нишката, то цялата ниша се възстановява
                $tRec = doc_Threads::fetch($rec->threadId);
                
                if($tRec->firstContainerId == $rec->containerId) {
                    
                    $cQuery = doc_Containers::getQuery();
                    
                    while($cRec = $cQuery->fetch("#threadId = {$rec->threadId}")) {
                        
                        if($rec->containerId == $cRec->id) continue;
                        
                        if($cRec->state == 'rejected') {
                            $document = doc_Containers::getDocument($cRec->id);
                            $document->reject('restore');
                        }
                    }
                    
                    $tRec->state = 'closed';
                    doc_Threads::save($tRec);
                }
                
                // Обновяваме съдържанието на папката
                doc_Threads::updateThread($rec->threadId);
            }
            
            $res = new Redirect(array($mvc, 'single', $rec->id));
            
            return FALSE;
        }
    }
    
    
    /**
     * документа. Реализация пона метода на модела
     */
    function on_AfterReject($mvc, &$res, $id, $mode = 'reject')
    {
        if(!$res) {
            $rec = $mvc->fetch($id);
            
            if($mode == 'reject') {
                if($rec->state != 'rejected') {
                    $rec->brState = $rec->state;
                    $rec->state = 'rejected';
                }
            } else {
                expect($mode == 'restore');
                
                if($rec->state == 'rejected') {
                    $rec->state = ($rec->brState == 'rejected') ? 'closed' : $rec->brState;
                }
            }
            
            $mvc->save($rec, 'state,brState');
            
            $mvc->log($mode, $rec->id);
            
            return TRUE;
        }
    }
    
    
    /**
     * Връщана документа
     */
    function on_AfterGetHandle($mvc, &$hnd, $id)
    {
        if(!$hnd) {
            $hnd = $mvc->abbr . $id;
        }
    }
    
    
    function on_AfterGetContainer($mvc, &$res, $id)
    {
        $classId = core_Classes::getId($mvc);
        
        $res = doc_Containers::fetch("#docId = {$id} AND #docClass = {$classId}");
    }
    
    
    /**
     * Връща линк към документа
     */
    function on_AfterGetLink($mvc, &$link, $id, $maxLength = FALSE)
    {
        $iconStyle = 'background-image:url(' . sbf($mvc->singleIcon, '') . ');';
        $url       = array($mvc, 'single', $id);
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
        
        $link = ht::createLink("{$row->title}", $url, NULL, 'class=linkWithIcon,style=' . $iconStyle);
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
    function on_AfterPrepareEditForm($mvc, $data)
    {
        $rec = $data->form->rec;
        
        //Ако редактираме запис
        // В записа на формата "тихо" трябва да са въведени от Request originId, threadId или folderId   
        if($rec->id) {
            $exRec = $mvc->fetch($rec->id);
            $mvc->threadId = $exRec->threadId;
            
            //Изискваме да има права
            doc_Threads::requireRightFor('single', $mvc->threadId);
            
            //Ако създаваме копие    
        } elseif (Request::get('Clone') && ($rec->originId)) {
            //Данните за документната система
            $containerRec = doc_Containers::fetch($rec->originId, 'threadId, folderId');
            expect($containerRec);
            $threadId = $containerRec->threadId;
            $folderId = $containerRec->folderId;
            
            //Първия запис в threada
            $firstContainerId = doc_Threads::fetchField($threadId, 'firstContainerId');
            
            //Ако няма folderId или нямаме права за запис в папката, тогава използваме имейл-а на текущия потребител
            if ((!$folderId) || (!doc_Folders::haveRightFor('single', $folderId))) {
                $user->email = email_Inboxes::getUserEmail();
                $folderId = email_Inboxes::forceCoverAndFolder($user);
            }
            
            //Ако копираме първия запис в треда, тогава създаваме нов тред
            if ($firstContainerId == $rec->originId) {
                //Премахваме id' то на треда за да се създаде нов
                unset($rec->threadId);
            } else {
                //Изискваме да има права в треда
                doc_Threads::requireRightFor('single', $threadId);
                
                //Присвояваме id' то на треда където се клонира, ако не е първия запис
                $rec->threadId = $threadId;
            }
            
            //Записите от БД
            $mvcRec = $mvc::fetch("#containerId = '{$rec->originId}'");
            
            //Създаваме масив с всички полета, които ще клонираме
            $cloneFieldsArr = arr::make($mvc->cloneFields);
            
            if (count($cloneFieldsArr)) {
                foreach ($cloneFieldsArr as $cloneField) {
                    //Заместваме съдържанието на всички полета със записите от БД
                    $rec->$cloneField = $mvcRec->$cloneField;
                }
            }
            
            //Записваме id' то на папката
            $rec->folderId = $folderId;
            
            // Ако имаме $originId и не създаваме копие - намираме треда
        
        } elseif ($rec->originId) {
            expect($oRec = doc_Containers::fetch($rec->originId, 'threadId,folderId'));
            
            // Трябва да имаме достъп до нишката на оригиналния документ
            doc_Threads::requireRightFor('single', $oRec->threadId);
            
            $rec->threadId = $oRec->threadId;
            $rec->folderId = $oRec->folderId;
            
            $data->form->layout = $data->form->renderLayout();
            $tpl = new ET("<div style='display:table'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr("Оригинален документ") . "</b></div>[#DOCUMENT#]</div>");
            
            // TODO: да се замени с интерфейсен метод
            
            $document = doc_Containers::getDocument($rec->originId);
            
            $docHtml = $document->getDocumentBody();
            
            $tpl->append($docHtml, 'DOCUMENT');
            
            $data->form->layout->append($tpl);
        }
        
        if($rec->threadId) {
            doc_Threads::requireRightFor('single', $rec->threadId);
            $rec->folderId = doc_Threads::fetchField($rec->threadId, 'folderId');
        }
        
        if(!$rec->folderId) {
            
            //Ако сме задали папката по подразбиране да е текущата папка
            if ($mvc->defaultFolder == 'inbox') {
                
                //id' то на текущия потребител
                $currUserId = email_Inboxes::getUserEmailId();
                
                //id' то на папката
                $rec->folderId = email_Inboxes::forceCoverAndFolder($currUserId);   
            } else {
                $rec->folderId = $mvc->GetUnsortedFolder();
            }
        }
        
        //Ако нямаме права, тогава използваме папката на потребителя
        if (!doc_Folders::haveRightFor('single', $rec->folderId)) {
            $user->email = email_Inboxes::getUserEmail();
            $rec->folderId = email_Inboxes::forceCoverAndFolder($user);
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
            $title = mb_strtolower($mvc->singleTitle) . ' |в|* ' . doc_Folders::recToVerbal($fRec)->title;
        }
        
        if($rec->threadId) {
            $thRec = doc_Threads::fetch($form->rec->threadId);
            
            if($thRec->firstContainerId != $form->rec->containerId) {
                $title = mb_strtolower($mvc->singleTitle) . ' |към|* ' . doc_Threads::recToVerbal($thRec)->title;
            }
        }

        if($form->rec->id) {
            $form->title = 'Редактиране на|* ';
        } else {
            if(Request::get('Clone') && ($rec->originId)) {
                $form->title = 'Копие на|* ';
            } else {
                $form->title = 'Нов|* ';
            }
        }
        
        $form->title .= $title;
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
        
        // Подготвяме данните за единичния изглед
        $data = $mvc->prepareDocument($id, $options);
        $res  = $mvc->renderDocument($id, $data);
        
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
        if ($rec->id) {
            $rec = $mvc->fetch($rec->id);
            
            if($action == 'delete') {
                $requiredRoles = 'no_one';
            } elseif(($action == 'edit') && ($rec->state != 'draft')) {
                $requiredRoles = 'no_one';
            } elseif ($action == 'reject' || $action == 'edit' || $action == 'restore') {
                if (doc_Threads::haveRightFor('single', $rec->threadId, $userId)) {
                    $requiredRoles = 'user';    
                } else {
                    $requiredRoles = 'no_one';
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
        
        // Трябва да има $rec за това $id
        expect($data->rec = $mvc->fetch($id));
        
        $data->cacheKey = 'Doc' . $data->rec->id . Mode::get('text') . Mode::get('printing');
        $data->threadCachedView = core_Cache::get($mvc->className, $data->cacheKey);
        
        if($data->threadCachedView === FALSE) {
            // Подготвяме данните за единичния изглед
            $mvc->prepareSingle($data);
        }
        
        // MID се генерира само ако :
        //     o подготвяме документа за изпращане навън - !Mode::is('text', 'html')
        //     o има зададен екшън - log_Documents::hasAction()
        if (!Mode::is('text', 'html') && log_Documents::hasAction()) {
            if (!isset($options->__mid)) {
                $data->__MID__ = log_Documents::saveAction(
                    array('containerId' => $data->rec->containerId)
                );
                if (is_object($options)) {
                    $options->__mid = $data->__MID__;
                }
            }
        }
    }
    
    
    /**
     * Кешира и използва вече кеширани рендирани изгледи на документи
     */
    function on_AfterRenderDocument($mvc, &$tpl, $id, $data)
    {
        if($tpl) return;
        
        if($data->threadCachedView === FALSE) {
            $tpl = $mvc->renderSingle($data);
            $tpl->removeBlocks();
            $tpl->removePlaces();
            
            if(in_array($data->rec->state, array('closed', 'rejected', 'active', 'waiting', 'open'))) {
                core_Cache::set($mvc->className, $data->cacheKey, $tpl, isDebug() ?  1 : 24 * 60 * 3);
            }
        } else {
            $tpl = $data->threadCachedView;
        }
        
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
     * Изпълнява се, акодефиниран метод getContragentData
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
        $state = 'closed';
    }
    
    
    /**
     * Реализация по подразбиране на интерфейсния метод ::getShared()
     */
    function on_AfterGetShared($mvc, &$shared, $id)
    {
        $shared = NULL;
    }
    
    
    /**
     * Реализация по подразбиране на интерфейсния метод ::canAddToFolder()
     */
    function on_AfterCanAddToFolder($mvc, &$res, $folderId, $folderClass)
    {
        $res = TRUE;
    }
    
    
    /**
     * Реализация по подразбиране на интерфейсния метод ::canAddToFolder()
     */
    function on_AfterCanAddToThread($mvc, &$res, $threadId, $firstClass)
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
                    foreach ($names as $name) {
                        
                        //Името на файла е с големи букви, както са документите
                        $name = strtoupper($name) . '.pdf';
                        
                        //Задаваме полето за избор, да не е избран по подразбиране
                        $res[$name] = 'off';
                    }
                }
            }
        }
    }
    
    
    /**
     * Реализация по подразбиране на doc_DocumentIntf::convertTo()
     * 
     * @param core_Mvc $mvc
     * @param string $res манипулатор на файл (@see fileman)
     * @param int $id първичен ключ на документа
     * @param string $type формат, в който да се генерира съдържанието на док.
     * @param string $fileName име на файл, в който да се запише резултата
     */
    static function on_AfterConvertTo($mvc, &$res, $id, $type, $fileName = NULL)
    {
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
                
                $html = $mvc->getDocumentBody($id, 'xhtml');
                
                log_Documents::popAction();
                
                //Манипулатора на новосъздадения pdf файл
                $res = doc_PdfCreator::convert($html, $fileName);
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
        
        $rec = $mvc->fetch($id);
        
        $res = plg_Search::getKeywords($mvc, $rec);
    }
    
    
    static function getMidPlace() 
    {
        return '__MID__';        
    }
}
