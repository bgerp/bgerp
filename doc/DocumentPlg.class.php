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
    static $stateArr = array('draft' => 'Чернова',
        'pending' => 'Чакащо',
        'active' => 'Активирано',
        'opened' => 'Отворено',
        'waiting' => 'Чакащо',
        'closed' => 'Приключено',
        'hidden' => 'Скрито',
        'rejected' => 'Оттеглено',
        'stopped' => 'Спряно',
        'wakeup' => 'Събудено',
        'free' => 'Освободено');
    
    
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
     * Подготвя иконата за единичния изглед
     */
    function on_AfterPrepareSingle($mvc, $res, $data)
    {
        $data->row->iconStyle = 'background-image:url(' . sbf($mvc->singleIcon) . ');';
    }
    
    
    /**
     * Добавя бутон за оттегляне
     */
    function on_AfterPrepareSingleToolbar($mvc, $res, $data)
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
        
        if (isset($data->rec->id) && $mvc->haveRightFor('reject') && ($data->rec->state == 'rejected')) {
            $data->toolbar->removeBtn("*");
            $data->toolbar->addBtn('Въстановяване', array(
                    $mvc,
                    'restore',
                    $data->rec->id,
                    'ret_url' => TRUE
                ),
                'id=btnRestore,class=btn-restore,warning=Наистина ли желаете да възстановите документа?,order=32');
        }
    }
    
    
    /**
     * Добавя бутон за показване на оттеглените записи
     */
    function on_AfterPrepareListToolbar($mvc, $res, $data)
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
    function on_AfterPrepareListTitle($mvc, $res, $data)
    {
        if(Request::get('Rejected')) {
            $data->title = new ET(tr($data->title));
            $data->title->append("&nbsp;<font class='state-rejected'>&nbsp;[" . tr('оттеглени') . "]&nbsp;</font>");
        }
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal(&$invoker, &$row, &$rec, $fields)
    {
        $row->ROW_ATTR['class'] .= " state-{$rec->state}";
        $row->STATE_CLASS .= " state-{$rec->state}";
        
        $row->modifiedDate = dt::mysql2verbal($rec->modifiedOn, 'd-m-Y');
        $row->createdDate = dt::mysql2verbal($rec->createdOn, 'd-m-Y');
        
        if($fields['-single']) {
            if(!$row->ident) {
                $row->ident = '#' . $invoker->abbr . $rec->id;
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
    function on_BeforePrepareListRecs($mvc, $res, $data)
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
        if(!isset($rec->id)) {
            
            // ... този документ няма ключ към папка и нишка, тогава
            // извикваме метода за рутиране на документа
            if(!isset($rec->folderId) || !isset($rec->threadId)) {
                $mvc->route($rec);
            }
            
            // ... този документ няма ключ към контейнер, тогава 
            // създаваме нов контейнер за документите от този клас 
            // и записваме връзка към новия контейнер в този документ
            if(!isset($rec->containerId)) {
                $rec->containerId = doc_Containers::create($mvc, $rec->threadId, $rec->folderId);
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
    function on_AfterSave($mvc, $id, $rec, $fields = NULL)
    {
        $containerId = $rec->containerId ? $rec->containerId : $mvc->fetchField($rec->id, 'containerId');
        
        if($containerId) {
            doc_Containers::update($containerId);
        }
    }
    
    
    /**
     * Ако в документа няма код, който да рутира документа до папка/тред,
     * долния код, рутира документа до "Несортирани - [заглавие на класа]"
     */
    function on_AfterRoute($mvc, $res, $rec)
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
            $rec->threadId = doc_Threads::create($rec->folderId);
        }
        
        // Ако нямаме контейнер - създаваме нов контейнер за 
        // този клас документи в определения тред
        if(!$rec->containerId) {
            $rec->containerId = doc_Containers::create($mvc, $rec->threadId, $rec->folderId);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function on_AfterGetUnsortedFolder($mvc, $res)
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
    function on_AfterGetDocumentTitle($mvc, $res, $rec)
    {
        if(!$res) {
            $res = $mvc->getRecTitle($rec);
        }
    }
    
    
    /**
     * Когато действието е предизвикано от doc_Thread изглед, тогава
     * връщането е към single изгледа, който от своя страна редиректва към
     * треда, при това с фокус на документа
     */
    function on_BeforePrepareRetUrl($mvc, $res, $data)
    {
        $retUrl = getRetUrl();
        
        if($retUrl['Ctr'] == 'doc_Containers' && is_a($mvc, 'core_Master')) {
            $data->retUrl = toUrl(array($mvc, 'single', $data->form->rec->id));
            
            return FALSE;
        }
    }
    
    
    /**
     * Смяна статута на 'rejected'
     *
     * @return core_Redirect
     */
    function on_BeforeAction($mvc, $res, $action)
    {
        if($action == 'single' && !(Request::get('Printing'))) {
            
            expect($id = Request::get('id', 'int'));
            
            $mvc->requireRightFor('single');
            
            // Логваме, че този потребител е отворил този документ
            $rec = $mvc->fetch($id);
            
            // Изтриваме нотификацията, ако има такава, свързани с този документ
            $url = array($mvc, 'single', 'id' => $id);
            bgerp_Notifications::clear($url);
            
            if($rec->threadId) {
                if(doc_Threads::haveRightFor('read', $rec->threadId)) {
                    
                    $hnd = $mvc->getHandle($rec->id);
                    $res = new Redirect(array('doc_Containers', 'list', 'threadId' => $rec->threadId, 'docId' => $hnd, '#' => $hnd));
                    
                    return FALSE;
                }
            }
        }
        
        if($action == 'reject') {
            
            $id = Request::get('id', 'int');
            
            $mvc->requireRightFor('reject');
            
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
                    
                    $res = new Redirect(array('doc_Threads', 'folderId' => $tRec->folderId));
                }
            }
            
            return FALSE;
        }
        
        if($action == 'restore') {
            
            $id = Request::get('id', 'int');
            
            $rec = $mvc->fetch($id);
            
            if (isset($rec->id) && $mvc->haveRightFor('reject') && ($rec->state == 'rejected')) {
                
                $mvc->reject($rec->id, 'restore');
                
                // Ако възстановяваме първия постинг на нишката, то цялата ниша се възстановява
                $tRec = doc_Threads::fetch($rec->threadId);
                
                if($tRec->firstContainerId == $rec->containerId) {
                    $tRec->state = 'closed';
                    doc_Threads::save($tRec);
                }
            }
            
            $res = new Redirect(array($mvc, 'single', $rec->id));
            
            return FALSE;
        }
    }
    
    
    /**
     * Отеегля документа. Реализация по падразбиране на метода на модела
     */
    function on_AfterReject($mvc, $res, $id, $mode = 'reject')
    {
        if(!$res) {
            $rec = $mvc->fetch($id);
            
            if($mode == 'reject') {
                $rec->brState = $rec->state;
                $rec->state = 'rejected';
            } else {
                expect($mode == 'restore');
                $rec->state = $rec->brState;
            }
            
            $mvc->save($rec, 'state,brState');
            
            $mvc->log($mode, $rec->id);
            
            return TRUE;
        }
    }
    
    
    /**
     * Връща манупулатора на документа
     */
    function on_AfterGetHandle($mvc, &$hnd, $id)
    {
        if(!$hnd) {
            $hnd = $mvc->abbr . $id;
        }
    }
    
    
    /**
     * Подготвя полетата threadId и folderId, ако има originId и threadId
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        // В записа на формата "тихо" трябва да са въведени от Request originId, threadId или folderId
        $rec = $data->form->rec;
        
        if($rec->id) {
            $exRec = $mvc->fetch($rec->id);
            $mvc->threadId = $exRec->threadId;
        }
        
        // Ако имаме $originId - намираме треда
        if($rec->originId) {
            expect($oRec = doc_Containers::fetch($rec->originId, 'threadId,folderId'));
            
            $rec->threadId = $oRec->threadId;
            $rec->folderId = $oRec->folderId;
            
            $data->form->layout = $data->form->renderLayout();
            $tpl = new ET("<div style='display:table'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr("Оригинален документ") . "</b></div>[#DOCUMENT#]</div>");
            
            // TODO: да се замени с интерфейсен метод
            
            $document = doc_Containers::getDocument($rec->originId);
            
            $docHtml = $document->getDocumentBody();
            
            $tpl->append($docHtml, 'DOCUMENT');
            
            $data->form->layout->append($tpl);
        } elseif($rec->threadId) {
            $rec->folderId = doc_Threads::fetchField($rec->threadId, 'folderId');
        }
        
        if($rec->threadId) {
            doc_Threads::requireRightFor('single', $rec->threadId);
        } else {
            if(!$rec->folderId) {
                $rec->folderId = $mvc->GetUnsortedFolder();
            }
            doc_Folders::requireRightFor('single', $rec->folderId);
        }
        
        if($rec->threadId) {
            
            $thRec = doc_Threads::fetch($rec->threadId);
            $thRow = doc_Threads::recToVerbal($thRec);
            $data->form->title = '|*' . $mvc->singleTitle . ' |в|* ' . $thRow->title ;
        } elseif ($rec->folderId) {
            $fRec = doc_Folders::fetch($rec->folderId);
            $fRow = doc_Folders::recToVerbal($fRec);
            $data->form->title = '|*' . $mvc->singleTitle . ' |в|* ' . $fRow->title ;
        }
    }
    
    
    /**
     * HTML или plain text изгледа на документ при изпращане по емайл.
     *
     * Използва single view на мениджъра на документа.
     *
     * @param core_Mvc $mvc мениджър на документа
     * @param int $id първичния ключ на документа - key(mvc=$mvc)
     * @param string $mode `plain` или `html`
     * @access private
     */
    function on_AfterGetDocumentBody($mvc, $res, $id, $mode = 'html')
    {
        expect($mode == 'plain' || $mode == 'html');
        
        // Създаваме обекта $data
        $data = new stdClass();
        
        // Трябва да има $rec за това $id
        expect($data->rec = $mvc->fetch($id));
        
        // Запомняме стойността на обкръжението 'printing' и 'text'
        $isPrinting = Mode::get('printing');
        $textMode = Mode::get('text');
        
        // Емулираме режим 'printing', за да махнем singleToolbar при рендирането на документа
        Mode::set('printing', TRUE);
        
        // Задаваме `text` режим според $mode. singleView-то на $mvc трябва да бъде генерирано
        // във формата, указан от `text` режима (plain или html)
        Mode::set('text', $mode);
        
        // Подготвяме данните за единичния изглед
        $mvc->prepareSingle($data);
        
        // Рендираме изгледа
        $res = $mvc->renderSingle($data)->removePlaces();
        
        // Връщаме старата стойност на 'printing'
        Mode::set('printing', $isPrinting);
        Mode::set('text', $textMode);
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
            }
            
            if(($action == 'edit') && ($rec->state != 'draft')) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function on_AfterPrepareDocument($mvc, $data, $id)
    {
        if($data) return;
        
        // Създаваме обекта $data
        $data = new stdClass();
        
        // Трябва да има $rec за това $id
        expect($data->rec = $mvc->fetch($id));
        
        // Подготвяме данните за единичния изглед
        $mvc->prepareSingle($data);
        
        return $data;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function on_AfterRenderDocument($mvc, $tpl, $id, $data)
    {
        email_Log::viewed($data->rec->containerId);
        
        if($tpl) return;
        
        $tpl = $mvc->renderSingle($data);
    }
    
    
    /**
     * Изпълянва се, ако нямама дефиниран метод getContragentData
     */
    function on_AfterGetContragentData($mvc, $data, $id)
    {
        
        return NULL;
    }


    /**
     * Реализация по подразбиране на интерфейсния метод ::getThreadState()
     * 
     * TODO: Тук трябва да се направи проверка, дали документа е изпратен или отпечатан
     *       и само тогава да се приема състоянието за затворено
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
    function on_AfterCanAddToFolder($mvc, $res, $folderId, $folderClass)
    { 
        $res = TRUE;
    }


    /**
     * Реализация по подразбиране на интерфейсния метод ::canAddToFolder()     
     */
    function on_AfterCanAddToThread($mvc, $res, $threadId, $firstClass)
    { 
        $res = !($mvc->onlyFirstInThread);
    }

}
