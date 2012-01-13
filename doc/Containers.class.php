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
    var $loadList = 'plg_Created, plg_Modified,plg_RowTools,doc_Wrapper,plg_State';
    
    
    /**
     * Заглавие
     */
    var $title = "Документи в нишките";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "created=Създаване,document=Документи,createdOn=";
    
    
    
    /**
     * За конвертиране на съществуащи MySQL таблици от предишни версии
     */
    var $oldClassName = 'doc_ThreadDocuments';
    
    function description()
    {
        // Мастери - нишка и папка
        $this->FLD('folderId' , 'key(mvc=doc_Folders)', 'caption=Папки');
        $this->FLD('threadId' , 'key(mvc=doc_Threads)', 'caption=Нишка');
        
        // Документ
        $this->FLD('docClass' , 'class(interface=doc_DocumentIntf)', 'caption=Документ->Клас');
        $this->FLD('docId' , 'int', 'caption=Документ->Обект');
        $this->FLD('handle' , 'varchar', 'caption=Документ->Манипулатор');
        
        // Достъп
        $this->FLD('shared' , 'keylist(mvc=core_Users, select=nick)', 'caption=Споделяне');
        
        // Индекси за бързодействие
        $this->setDbIndex('folderId');
        $this->setDbIndex('threadId');
    }
    
    
    
    /**
     * Филтрира по id на нишка (threadId)
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
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
    function on_AfterPrepareListFilter($mvc, $res, $data)
    {
        expect($data->threadId = Request::get('threadId', 'int'));
        expect($data->threadRec = doc_Threads::fetch($data->threadId));
        
        $data->folderId = $data->threadRec->folderId;
        
        doc_Threads::requireRightFor('read', $data->threadRec);
    }
    
    
    
    /**
     * Подготвя титлата за единичния изглед на една нишка от документи
     */
    function on_AfterPrepareListTitle($mvc, $res, $data)
    {
        $title = new ET("[#user#] » [#folder#] » [#threadTitle#]");
        
        $document = $mvc->getDocument($data->threadRec->firstContainerId);
        
        $docRow = $document->getDocumentRow();
        
        $docTitle = $docRow->title;
        
        $title->replace($docTitle, 'threadTitle');
        
        $folder = doc_Folders::getTitleById($data->folderId);
        
        $folderRec = doc_Folders::fetch($data->folderId);
        
        $title->replace(ht::createLink($folder, array('doc_Threads', 'list', 'folderId' => $data->folderId)), 'folder');
        
        $user = core_Users::fetchField($folderRec->inCharge, 'nick');
        
        $title->replace($user, 'user');
        
        $data->title = $title;
    }
    
    
    
    /**
     * Добавя div със стил за състоянието на треда
     */
    function on_AfterRenderListTable($mvc, $tpl, $data)
    {
        $state = $data->threadRec->state;
        $tpl = new ET("<div class='thread-{$state}'>[#1#]</div>", $tpl);
    }
    
    
    
    /**
     * Подготвя някои вербални стойности за полетата на контейнера за документ
     * Използва методи на интерфейса doc_DocumentIntf, за да вземе тези стойности
     * директно от документа, който е в дадения контейнер
     */
    function on_AfterRecToVerbal($mvc, $row, $rec, $fields = NULL)
    {
        $document = $mvc->getDocument($rec->id);
        
        $docRow = $document->getDocumentRow();
        
        $data = $document->prepareDocument();
        
        $row->created = new ET( "<center><div style='font-size:0.8em'>[#1#]</div><div style='margin:10px;'>[#2#]</div>[#3#]<div></div></center>",
        ($row->createdOn),
        avatar_Plugin::getImg($docRow->authorId, $docRow->authorEmail),
        $docRow->author );
        
        if($data->rec->state != 'rejected') {
            
            if(cls::haveInterface('email_DocumentIntf', $document->className)) {
                $data->toolbar->addBtn('Имейл', array('email_Sent', 'send', 'containerId' => $rec->id), 'target=_blank,class=btn-email');
            }
            
            if($document->instance->className == 'email_Message') {
                $data->toolbar->addBtn('Отговор', array('doc_Postings', 'add', 'originId' => $rec->id), 'class=btn-posting');
            } else {
                $data->toolbar->addBtn('Коментар', array('doc_Postings', 'add', 'originId' => $rec->id), 'class=btn-posting');
            }
        }
        
        $row->ROW_ATTR['id'] = $document->getHandle();
        
        // Рендираме изгледа
        $row->document = $document->renderDocument($data);
        $row->document->removeBlocks();
        $row->document->removePlaces();
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    public function on_AfterPrepareListToolbar($mvc, $data)
    {
    	$data->toolbar->addBtn('Съобщение', array('doc_Postings', 'add', 'threadId'=>$data->threadId), 'id=btnAdd,class=btn-posting');
        
        $data->toolbar->addBtn('Задача', array('doc_Tasks', 'add', 'threadId'=>$data->threadId), 'class=btn-task');

        if($data->threadRec->state == 'opened') {
            $data->toolbar->addBtn('Затваряне', array('doc_Threads', 'close', 'threadId'=>$data->threadId), 'class=btn-close');
        } elseif($data->threadRec->state == 'closed' || empty($data->threadRec->state)) {
            $data->toolbar->addBtn('Отваряне', array('doc_Threads', 'open', 'threadId'=>$data->threadId), 'class=btn-open');
        }
        $data->toolbar->addBtn('Преместване', array('doc_Threads', 'move', 'threadId'=>$data->threadId, 'ret_url' => TRUE), 'class=btn-move');
    }
    
    
    
    /**
     * Създава нов контейнер за документ от посочения клас
     * Връща $id на новосъздадения контейнер
     */
    function create($class, $threadId, $folderId)
    {
        $className = cls::getClassName($class);
        $rec->docClass = core_Classes::fetchIdByName($className);
        $rec->threadId = $threadId;
        $rec->folderId = $folderId;
        
        self::save($rec);
        
        return $rec->id;
    }
    
    
    
    /**
     * Обновява информацията в контейнера според информацията в документа
     * Ако в контейнера няма връзка към документ, а само мениджър на документи - създава я
     *
     * @param int $id key(mvc=doc_Containers)
     */
    function update_($id)
    {
        expect($rec = doc_Containers::fetch($id), $id);
        
        $docMvc = cls::get($rec->docClass);
        
        //$rec->shared = $docMvc->getSharedUsers($rec);
        
        if(!$rec->docId) {
            expect($rec->docId = $docMvc->fetchField("#containerId = {$id}", 'id'));
            $mustSave = TRUE;
        }
        
        $fields = 'state,folderId,threadId,containerId';
        
        $docRec = $docMvc->fetch($rec->docId, $fields);
        
        foreach(arr::make($fields) as $field) {
            if($rec->{$field} != $docRec->{$field}) {
                $rec->{$field} = $docRec->{$field};
                $mustSave = TRUE;
            }
        }
        
        if($mustSave) {
            $bSaved = doc_Containers::save($rec);
        }
    }
    
    
    
    /**
     * Предизвиква обновяване на треда, след всяко обновяване на контейнера
     */
    function on_AfterSave($mvc, $id, $rec, $fields = NULL)
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
     * @return object
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
                $rec->handle = static::protectHandle($rec->handle);
            } while (!is_null(static::getByHandle($rec->handle)));
            
            expect($rec->handle);
            
            // Записваме току-що генерирания манипулатор в контейнера. Всеки следващ 
            // опит за вземане на манипулатор ще връща тази записана стойност.
            static::save($rec);
        }
        
        return $rec->handle;
    }
    
    
    /**
     *
     */
    protected static function protectHandle($prefix)
    {
        $handle = $prefix . str::getRand('AAA');
        $handle = strtoupper($handle);
        
        return $handle;
    }
}