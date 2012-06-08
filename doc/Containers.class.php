<?php



/**
 * Клас 'doc_Containers' - Контейнери за документи
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_Containers extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_Modified,plg_RowTools,doc_Wrapper,plg_State, plg_RefreshRows';
    
    
    /**
     * 10 секунди време за опресняване на нишката
     */
    var $refreshRowsTime = 30000;


    /**
     * Заглавие
     */
    var $title = "Документи в нишките";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "created=Създаване,document=Документи";
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'doc_ThreadDocuments';
    
    
    /**
     * @todo Чака за документация...
     */
    var $listItemsPerPage = 100;
    
    
    /**
     * @todo Чака за документация...
     */
    var $canList = 'user';
    
    
    /**
     * @todo Чака за документация...
     */
    var $canAdd  = 'no_one';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Мастери - нишка и папка
        $this->FLD('folderId' , 'key(mvc=doc_Folders)', 'caption=Папки');
        $this->FLD('threadId' , 'key(mvc=doc_Threads)', 'caption=Нишка');
        
        // Документ
        $this->FLD('docClass' , 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', 'caption=Документ->Клас');
        $this->FLD('docId' , 'int', 'caption=Документ->Обект');
        $this->FLD('handle' , 'varchar', 'caption=Документ->Манипулатор');
        $this->FLD('searchKeywords', 'text', 'notNull,column=none, input=none');
        
        $this->FLD('activatedBy', 'key(mvc=core_Users)', 'caption=Активирано от, input=none');
        
        // Индекси за бързодействие
        $this->setDbIndex('folderId');
        $this->setDbIndex('threadId');
        $this->setDbUnique('docClass, docId');
    }
    
    
    /**
     * Филтрира по id на нишка (threadId)
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $threadId = Request::get('threadId', 'int');
        
        if($threadId) {
            $data->query->where("#threadId = {$threadId}");
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на филтъра за листовия изглед
     * Обикновено тук се въвеждат филтриращите променливи от Request
     */
    static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        expect($data->threadId = Request::get('threadId', 'int'));
        expect($data->threadRec = doc_Threads::fetch($data->threadId));
        
        $data->folderId = $data->threadRec->folderId;
        
        doc_Threads::requireRightFor('single', $data->threadRec);
        
        bgerp_Recently::add('document', $data->threadRec->firstContainerId);
        
        $data->query->orderBy('#createdOn');
    }
    
    
    /**
     * Подготвя титлата за единичния изглед на една нишка от документи
     */
    static function on_AfterPrepareListTitle($mvc, &$res, $data)
    {
        $title = new ET("<div style='font-size:18px'>[#user#] » [#folder#] ([#folderCover#]) » [#threadTitle#]</div>");
        
        // Папка и корица
        $folderRec = doc_Folders::fetch($data->folderId);
        $folderRow = doc_Folders::recToVerbal($folderRec);
        $title->replace($folderRow->title, 'folder');
        $title->replace($folderRow->type, 'folderCover');
        // Потребител
        if($folderRec->inCharge) {
            $user = core_Users::fetchField($folderRec->inCharge, 'nick');
        } else {
            $user = '@system';
        }
        $title->replace($user, 'user');
        
        // Заглавие на треда
        $document = $mvc->getDocument($data->threadRec->firstContainerId);
        $docRow = $document->getDocumentRow();
        $docTitle = str::limitLen($docRow->title, 70);
        $title->replace($docTitle, 'threadTitle');
        
        $mvc->title = '|*' . str::limitLen($docRow->title, 20) . ' « ' . doc_Folders::getTitleById($folderRec->id) .'|';

        $data->title = $title;
    }
    
    
    /**
     * Добавя div със стил за състоянието на треда
     */
    static function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        $state = $data->threadRec->state;
        $tpl = new ET("<div class='thread-{$state}'>[#1#]</div>", $tpl);
        
        // Изчистване на нотификации за отворени теми в тази папка
        $url = array('doc_Containers', 'list', 'threadId' => $data->threadRec->id);
        bgerp_Notifications::clear($url);


        $tpl->appendOnce("flashHashDoc(1);", 'ON_LOAD');
    }
    
    
    /**
     * Подготвя някои вербални стойности за полетата на контейнера за документ
     * Използва методи на интерфейса doc_DocumentIntf, за да вземе тези стойности
     * директно от документа, който е в дадения контейнер
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = NULL)
    {
        $document = $mvc->getDocument($rec->id);
        $docRow = $document->getDocumentRow();
        
        $data = $document->prepareDocument();
        
        $row->created = new ET("<center><div style='font-size:0.8em;margin-top:5px;'>[#3#]</div>
                                        <div style='font-size:0.8em;margin:5px;margin-bottom:10px;'>[#1#]</div>
                                        <div style='margin:10px;'>[#2#]</div></center>",
            ($row->createdOn),
            avatar_Plugin::getImg($docRow->authorId, $docRow->authorEmail),
            str::limitLen($docRow->author, 32));
        
        // визуализиране на обобщена информация от лога
        $row->created->append(doc_Log::getSummary($rec->id, $rec->threadId));
        
        $row->ROW_ATTR['id'] = $document->getHandle();
        
        // Рендираме изгледа
        $row->document = $document->renderDocument($data);
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, $data)
    {
        if($data->threadRec->state != 'rejected') {
            $data->toolbar->addBtn('Нов...', array($mvc, 'ShowDocMenu', 'threadId'=>$data->threadId), 'id=btnAdd,class=btn-add');
            
            if($data->threadRec->state == 'opened') {
                $data->toolbar->addBtn('Затваряне', array('doc_Threads', 'close', 'threadId'=>$data->threadId), 'class=btn-close');
            } elseif($data->threadRec->state == 'closed' || empty($data->threadRec->state)) {
                $data->toolbar->addBtn('Отваряне', array('doc_Threads', 'open', 'threadId'=>$data->threadId), 'class=btn-open');
            }
            $data->toolbar->addBtn('Преместване', array('doc_Threads', 'move', 'threadId'=>$data->threadId, 'ret_url' => TRUE), 'class=btn-move');
        }
    }
    
    
    /**
     * Създава нов контейнер за документ от посочения клас
     * Връща $id на новосъздадения контейнер
     */
    static function create($class, $threadId, $folderId, $createdOn)
    {
        $className = cls::getClassName($class);
        
        $rec = new stdClass();
        $rec->docClass  = core_Classes::fetchIdByName($className);
        $rec->threadId  = $threadId;
        $rec->folderId  = $folderId;
        $rec->createdOn = $createdOn;
        
        self::save($rec);
        
        return $rec->id;
    }
    
    
    /**
     * Обновява информацията в контейнера според информацията в документа
     * Ако в контейнера няма връзка към документ, а само мениджър на документи - създава я
     *
     * @param int $id key(mvc=doc_Containers)
     */
    static function update_($id)
    {
        expect($rec = doc_Containers::fetch($id), $id);
        
        $docMvc = cls::get($rec->docClass);
        
        
        if(!$rec->docId) {
            expect($rec->docId = $docMvc->fetchField("#containerId = {$id}", 'id'));
            $mustSave = TRUE;
        }
        
        $fields = 'state,folderId,threadId,containerId';
        
        $docRec = $docMvc->fetch($rec->docId, $fields);
        
        if ($docRec->searchKeywords = $docMvc->getSearchKeywords($docRec->id)) {
            $fields .= ',searchKeywords';
        }
                
        foreach(arr::make($fields) as $field) {
            if($rec->{$field} != $docRec->{$field}) {
                $rec->{$field} = $docRec->{$field};
                $mustSave = TRUE;
            }
        }

        // Дали документа се активира в момента, и кой го активира
        if(empty($rec->activatedBy) && $rec->state != 'draft' && $rec->state != 'rejected') {
            
            $rec->activatedBy = core_Users::getCurrent();
            
            $flagJustActived = TRUE;
            $mustSave = TRUE;
        }

        if($mustSave) {
            
            $bSaved = doc_Containers::save($rec);

            // Ако този документ носи споделяния на нишката, добавяме ги в списъка с отношения
            if($rec->state != 'draft' && $rec->state != 'rejected') {
                $shared = $docMvc->getShared($rec->docId);
                doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, $shared);
                doc_ThreadUsers::addSubscribed($rec->threadId, $rec->containerId, $rec->createdBy);
            } elseif ($rec->state == 'rejected') {
                doc_ThreadUsers::removeContainer($rec->containerId);
            }

            if($flagJustActived) {
                // Подготвяме няколко стринга, за употреба по-после
                $docSingleTitle = mb_strtolower($docMvc->singleTitle);  
                $docHnd = $docMvc->getHandle($rec->docId);
                $threadTitle = str::limitLen(doc_Threads::getThreadTitle($rec->threadId), 90);
                $nick = core_Users::getCurrent('nick');
                $nick = str_replace(array('_', '.'), array(' ', ' '), $nick);
                $nick = mb_convert_case($nick, MB_CASE_TITLE, 'UTF-8');
                 
                // Нотифицираме всички споделени потребители на този контейнер
                $sharedArr = type_Keylist::toArray($shared);
                if(count($sharedArr)) {
                    $message = "{$nick} сподели {$docSingleTitle} в \"{$threadTitle}\"";
                    $url = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
                    $customUrl = array('doc_Containers', 'list', 'threadId' => $rec->threadId, 'docId' => $docHnd, '#' => $docHnd);
                    $priority = 'normal';
                    foreach($sharedArr as $userId) {
                        bgerp_Notifications::add($message, $url, $userId, $priority, $customUrl);
                        $notifiedUsers[$userId] = $userId;
                    }
                }

                // Нотифицираме всички абонати на дадената нишка
                $subscribed = doc_ThreadUsers::getSubscribed($rec->threadId);
                $subscribedArr = type_Keylist::toArray($subscribed);
                if(count($subscribedArr)) {
                    $message = "{$nick} добави  {$docSingleTitle} в \"{$threadTitle}\"";
                    $url = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
                    $customUrl = array('doc_Containers', 'list', 'threadId' => $rec->threadId, 'docId' => $docHnd, '#' => $docHnd);
                    $priority = 'normal';
                    foreach($subscribedArr as $userId) {
                        if($userId > 0 && $userId != $cu && (!$notifiedUsers[$userId]) && 
                            doc_Threads::haveRightFor('single', $rec->threadId, $userId)) {
                            bgerp_Notifications::add($message, $url, $userId, $priority, $customUrl);
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * Предизвиква обновяване на треда, след всяко обновяване на контейнера
     */
    static function on_AfterSave($mvc, $id, $rec, $fields = NULL)
    {
        if($rec->threadId && $rec->docId) {
            doc_Threads::updateThread($rec->threadId);
        }
    }
    
    
    /**
     * Връща обект-пълномощник приведен към зададен интерфейс
     *
     * @param mixed int key(mvc=doc_Containers) или обект с docId и docClass
     * @param string $intf
     * @return core_ObjectReference
     */
    static function getDocument($id, $intf = NULL)
    {
        if (!is_object($id)) {
            $rec = doc_Containers::fetch($id, 'docId, docClass');
            
            // Ако няма id на документ, изчакваме една-две секунди, 
            // защото може този документ да се създава точно в този момент
            if(!$rec->docId) sleep(1);
            $rec = doc_Containers::fetch($id, 'docId, docClass');
            
            if(!$rec->docId) sleep(1);
            $rec = doc_Containers::fetch($id, 'docId, docClass');
        } else {
            $rec = $id;
        }
        
        expect($rec, $id);
        
        return new core_ObjectReference($rec->docClass, $rec->docId, $intf);
    }
    
    
    static function getDocumentByHandle($handle, $intf = NULL)
    {
        $rec = doc_RichTextPlg::parseDocHandle($handle);
        
        if (!$rec) {
            return FALSE;
        }
        
        return static::getDocument((object)$rec, $intf);
    }
    
    
    /**
     * Намира контейнер на документ по негов манипулатор.
     *
     * @param string $handle манипулатор на документ
     * @return int key(mvc=doc_Containers) NULL ако няма съответен на манипулатора контейнер
     */
    public static function getByHandle($handle)
    {
        $id = static::fetchField(array("#handle = '[#1#]'", $handle), 'id');
        
        if (!$id) {
            $id = NULL;
        }
        
        return $id;
    }
    
    
    /**
     * Генерира и връща манипулатор на документ.
     *
     * @param int $id key(mvc=doc_Container)
     * @return string манипулатора на документа
     */
    public static function getHandle($id)
    {
        $rec = static::fetch($id, 'id, handle, docId, docClass');
        
        expect($rec);
        
        if (!$rec->handle) {
            $doc = static::getDocument($rec, 'doc_DocumentIntf');
            $rec->handle = $doc->getHandle();
            
            do {
                $rec->handle = email_util_ThreadHandle::protect($rec->handle);
            } while (!is_null(static::getByHandle($rec->handle)));
            
            expect($rec->handle);
            
            // Записваме току-що генерирания манипулатор в контейнера. Всеки следващ 
            // опит за вземане на манипулатор ще връща тази записана стойност.
            static::save($rec);
        }
        
        return $rec->handle;
    }
    
    
    /**
     * Потребителите, с които е споделен документ
     *
     * @param int $id key(mvc=doc_Containers) първ. ключ на контейнера на документа
     * @return string keylist(mvc=core_Users)
     * @see doc_DocumentIntf::getShared()
     */
    public static function getShared($id)
    {
        $doc = static::getDocument($id, 'doc_DocumentIntf');
        
        return $doc->getShared();
    }
    
    
    /**
     * Състоянието на документ
     *
     * @param int $id key(mvc=doc_Containers) първ. ключ на контейнера на документа
     * @return string състоянието на документа
     */
    public static function getDocState($id)
    {
        $doc = static::getDocument($id, 'doc_DocumentIntf');
        
        $row = $doc->getDocumentRow();
        
        return $row->state;
    }
    
    
    /**
     * Екшън за активиране на постинги
     */
    function act_Activate()
    {
        $containerId = Request::get('containerId');
        
        //Очакваме да име
        expect($containerId);
        
        //Документна
        $document = doc_Containers::getDocument($containerId);
        $class = $document->className;
        
        // Очакваме да има такъв запис
        expect($rec = $class::fetch("#containerId='{$containerId}'"));
        
        // Очакваме потребителя да има права за активиране
        $class::haveRightFor('activation', $rec);
        
        //Променяме състоянието
        $recAct = new stdClass();
        $recAct->id = $rec->id;
        $recAct->state = 'active';
        
        //Записваме данните в БД
        $class::save($recAct);
        
        //Редиректваме към сингъла на съответния клас, от къде се прехвърляме към треда
        redirect(array($class, 'single', $rec->id));
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Send()
    {
        $containerId = Request::get('containerId');
        
        //Очакваме да име
        expect($containerId);
        
        //Документна
        $document = doc_Containers::getDocument($containerId);
        $class = $document->className;
        
        // Очакваме да има такъв запис
        expect($rec = $class::fetch("#containerId='{$containerId}'"));
        
        // Очакваме потребителя да има права за активиране
        $class::haveRightFor('send', $rec);
        
        //Ако нямаме въведен имейл, тогава се редиректва в страницата за изпращане, където можем да въведем съответното поле
        if (!$rec->email) {
            
            $link = array('email_Sent', 'send', 'containerId' => $rec->id);
            
            return new Redirect($link);
        }
        
        //id' то на пощенската кутия на потребителя, който е логнат
        $boxFrom = email_Inboxes::getUserEmailId();
        
        $tpl = '<div style="padding: 1em;">';
        
        //Опциите при изпращане
        $options = NULL;
        
        $Send = cls::get('email_Sent');
        
        //Изпращане на имейл-а
        if ($id = $Send->send($rec->containerId, $rec->email, $rec->subject, $boxFrom, $options)) {
            $tpl = "Успешно изпращане до {$rec->email}";
        } else {
            $tpl = "Проблем при изпращане до {$rec->email}";
        }
        
        $tpl .= ''
        . '<div style="margin-top: 1em;">'
        .    '<input type="button" value="Затваряне" onclick="window.close();" />'
        . '</div>';
        
        $tpl .= '</div>';
        
        return $tpl;
    }
    
    
    /**
     * Показва меню от възможности за добавяне на нови документи,
     * достъпни за дадената нишка. Очаква threadId
     */
    function act_ShowDocMenu()
    {
        expect($threadId = Request::get('threadId', 'int'));
        
        doc_Threads::requireRightFor('single', $threadId);
        
        $tpl = new ET();        
        $tpl->append("\n<h3>" . tr('Добавяне на нов документ в нишката') . ":</h3>");
        $tpl->append("\n<table>");
        
        $docArr = core_Classes::getOptionsByInterface('doc_DocumentIntf');
        
        foreach($docArr as $id => $class) {
            
            $mvc = cls::get($class);
            
            if($mvc->canAddToThread($threadId, '') && $mvc->haveRightFor('add')) {
               
                $tpl->append("\n<tr><td>");

                $attr = "class=linkWithIcon,style=background-image:url(" . sbf($mvc->singleIcon, '') . ");width:100%;text-align:left;";
 
                $tpl->append(ht::createBtn($mvc->singleTitle, array($class, 'add', 'threadId'=>$threadId, 'ret_url' => TRUE), NULL, NULL, $attr));
                
                $tpl->append("</td></tr>");
            }
        }
        
        $tpl->append("\n</table>");

        return $this->renderWrapping($tpl);
    }
    
    
    /**
     * Връща абревиатурата на всички класов, които имплементират doc_DocumentIntf
     */
    static function getAbrr()
    {
        //Проверяваме дали записа фигурира в кеша
        $abbr = core_Cache::get('abbr', 'allClass', 1440, array('core_Classes', 'core_Interfaces'));
        
        //Ако няма
        if (!$abbr) {
            
            //id' то на интерфейса doc_DocumentIntf
            $docIntfId = core_Interfaces::fetchField("#name='doc_DocumentIntf'");
            
            $query = core_Classes::getQuery();
            
            //Обикаляме всички записи, които имплементират doc_DocumentInrf
            while ($allClasses = $query->fetch("#interfaces LIKE '%|{$docIntfId}|%'")) {
                //Името на класа
                $className = $allClasses->name;
                
                //id' то на класа
                $id = $allClasses->id;
                
                //Създаваме инстанция на класа в масив
                $instanceArr[$id] = cls::get($className);
                
                //Създаваме масив с абревиатурата и името на класа                
                $abbr[strtoupper($instanceArr[$id]->abbr)] = $className;
            }
            
            //Записваме масива в кеша
            core_Cache::set('abbr', 'allClass', $abbr, 1440, array('core_Classes', 'core_Interfaces'));
        }
        
        //Връщаме резултата
        return $abbr;
    }
    
    
    /**
     * Връща езика на контейнера
     *
     * @param int $id - id' то на контейнера
     *
     * @return string $lg - Двубуквеното означение на предполагаемия език на имейла
     */
    static function getLanguage($id)
    {
        //Ако няма стойност, връщаме
        if (!$id) return ;
        
        //Записите на контейнера
        $doc = doc_Containers::getDocument($id);
        
        //Вземаме записите на класа
        $docRec = $doc->fetch();
        
        //Връщаме езика
        return $docRec->lg;
    }
}
