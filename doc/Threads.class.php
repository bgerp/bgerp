<?php


/**
 * Клас 'doc_Folders' - Папки с нишки от документи
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_Threads extends core_Manager
{
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created,plg_Modified,plg_State,doc_Wrapper, plg_Select, expert_Plugin';
    
    
    /**
     * Заглавие
     */
    var $title = "Нишки от документи";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'hnd=Номер,title,author=Автор,last=Последно,allDocCnt=Документи,createdOn=Създаване';
    
    
    
    /**
     * Описание на модела на нишкитев от контейнери за документи
     */
    function description()
    {
        // Информация за нишката
        $this->FLD('folderId' , 'key(mvc=doc_Folders,select=title,silent)', 'caption=Папки');
        $this->FLD('title' , 'varchar(128)', 'caption=Заглавие');
        $this->FLD('state' , 'enum(opened,waiting,closed,rejected)', 'caption=Състояние,notNull');
        $this->FLD('allDocCnt' , 'int', 'caption=Брой документи->Всички');
        $this->FLD('pubDocCnt' , 'int', 'caption=Брой документи->Публични');
        $this->FLD('last' , 'datetime(format=smartTime)', 'caption=Последно');
        
        // Ключ към първия контейнер за документ от нишката
        $this->FLD('firstContainerId' , 'key(mvc=doc_Containers)', 'caption=Начало,input=none,column=none,oldFieldName=firstThreadDocId');
        
        // Достъп
        $this->FLD('shared' , 'keylist(mvc=core_Users, select=nick)', 'caption=Споделяне');
        
        // Манипулатор на нишката (thread handle)
        $this->FLD('handle', 'varchar(32)', 'caption=Манипулатор');
        
        // Индекс за по-бързо селектиране по папка
        $this->setDbIndex('folderId');
    }
    
    
    
    /**
     * Подготвя титлата на папката с теми
     */
    function on_AfterPrepareListTitle($mvc, $res, $data)
    {
        expect($data->folderId = Request::get('folderId', 'int'));
        
        $title = new ET("<div class='rowtools' style='font-size:0.9em;'><div class='l'>[#user#] » [#folder#]</div> <div class='r'>&nbsp;[#folderCover#]</div></div>");
        
        $folder = doc_Folders::getTitleById($data->folderId);
        
        $folderRec = doc_Folders::fetch($data->folderId);
        
        $title->append(ht::createLink($folder, array('doc_Threads', 'list', 'folderId' => $data->folderId)), 'folder');
        
        if(Request::get('Rejected')) {
            $title->append("&nbsp;<font class='state-rejected'>&nbsp;[" . tr('оттеглени'). "]&nbsp;</font>", 'folder');
        }

        $user = core_Users::fetchField($folderRec->inCharge, 'nick');
        
        $title->replace($user, 'user');
        
        // "Корица" на папката
        $fRec = doc_Folders::fetch($data->folderId);

        $typeMvc = cls::get($fRec->coverClass);
        
        $attr['class'] = 'linkWithIcon';
        $attr['style'] = 'background-image:url(' . sbf($typeMvc->singleIcon) . ');';
        
        if($typeMvc->haveRightFor('single', $fRec->coverId)) {
            $cover = ht::createLink($typeMvc->singleTitle, array($typeMvc, 'single', $fRec->coverId), NULL, $attr);
        } else {
            $attr['style'] .= 'color:#777;';
            $cover = ht::createElement('span', $attr, $typeMvc->singleTitle);
        }
        
        $title->replace($cover, 'folderCover');

        $data->title = $title;
    }
    
    
    
    /**
     * Филтрира по папка и ако е указано показва само оттеглените записи
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        expect($folderId = Request::get('folderId', 'int'));
        
        doc_Folders::requireRightFor('single');
        
        expect($folderRec = doc_Folders::fetch($folderId));
        
        doc_Folders::requireRightFor('single', $folderRec);
        
        $data->query->where("#folderId = {$folderId}");
        
        $data->query->orderBy('#state=ASC,#last=DESC');
        
        $url = array('doc_Threads', 'list', 'folderId' => $folderId);
        
        if(Request::get('Rejected')) {
            $data->query->where("#state = 'rejected'");
        } else {
            $data->query->where("#state != 'rejected' || #state IS NULL");
        }
        
        
        bgerp_Notifications::clear($url);
    }
    
    
    



    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $docProxy = doc_Containers::getDocument($rec->firstContainerId);
        
        $docRow = $docProxy->getDocumentRow();
        
        $attr['class'] .= 'linkWithIcon';
        $attr['style'] = 'background-image:url(' . sbf($docProxy->instance->singleIcon) . ');';
        
        $row->title = ht::createLink($docRow->title,
        array('doc_Containers', 'list',
            'threadId' => $rec->id,
            'folderId' => $rec->folderId),
        NULL, $attr);
        $row->author = $docRow->author;
        
        $row->hnd = "<div class='rowtools'>";
        
        $row->hnd .= "<div style='padding-right:5px;' class='l'><div class=\"stateIndicator state-{$docRow->state}\"></div></div> <div class='r'>";
        
        $row->hnd .= $rec->handle ? substr($rec->handle, 0, strlen($rec->handle)-3) : $docProxy->getHandle();
        
        $row->hnd .= '</div>';
        
        $row->hnd .= '</div>';


    }
    
    
    
    /**
     * Създава нов тред
     */
    function create($folderId)
    {
        $rec->folderId = $folderId;
        
        self::save($rec);
        
        return $rec->id;
    }
    
    
    
    /**
     * Тестов екшън за преместване на нишка в друга папка.
     *
     * @access private
     */
    function act_MoveTest()
    {
        $id = Request::get('id', 'key(mvc=doc_Threads)');
        $folderId = Request::get('folderId', 'key(mvc=doc_Folders)');
        
        static::move($id, $folderId);
    }
    
    
    
    /**
     * Екшън за преместване на тред
     */
    function exp_Move($exp)
    {
        $exp->DEF('#threadId=Нишка', 'key(mvc=doc_Threads)', 'fromRequest');
        
        $exp->functions['doc_threads_fetchfield'] = 'doc_Threads::fetchField';
        
        $exp->DEF('dest=Преместване към', 'enum(exFolder=Съществуваща папка, 
                                                newCompany=Нова папка на фирма,
                                                newPerson=Нова папка на лице)', 'maxRadio=4,columns=1', 'value=exFolder');
        
        $exp->question("#dest", "Моля, посочете къде да бъде преместена нишката:", TRUE, 'title=Ново място за нишката');
        
        $exp->DEF('#folderId=Папка', 'key(mvc=doc_Folders, select=title)', 'width=500px');
        
        $exp->ASSUME('#folderId', "doc_Threads_fetchField(#threadId, 'folderId')", TRUE);
        
        $exp->question("#folderId", "Моля, изберете папка:", "#dest == 'exFolder'", 'title=Избор на папка за нишката');
        
        $result = $exp->solve('#folderId');
        
        if($result == 'SUCCESS') {
            $threadId = $exp->getValue('threadId');
            $folderId = $exp->getValue('folderId');
            
            $this->move($threadId, $folderId);
        }
        
        return $result;
    }
    
    
    
    /**
     * Преместване на нишка от в друга папка.
     *
     * @param int $id key(mvc=doc_Threads)
     * @param int $destFolderId key(mvc=doc_Folders)
     * @return boolean
     */
    public static function move($id, $destFolderId)
    {
        // Подсигуряваме, че нишката, която ще преместваме, както и папката, където ще я 
        // преместваме съществуват.
        expect($currentFolderId = static::fetchField($id, 'folderId'));
        expect(doc_Folders::fetchField($destFolderId, 'id') == $destFolderId);
        
        // Извличаме doc_Cointaners на този тред
        /* @var $query core_Query */
        $query = doc_Containers::getQuery();
        $query->where("#threadId = {$id}");
        $query->show('id, docId, docClass');
        
        while ($rec = $query->fetch()) {
            $doc = doc_Containers::getDocument($rec->id);
            
            /*
             *  Преместваме оригиналния документ. Плъгина @link doc_DocumentPlg ще се погрижи да
             *  премести съответстващия му контейнер.
             */
            expect($rec->docId);
            $doc->instance->save(
            (object)array(
                'id' => $rec->docId,
                'folderId' => $destFolderId,
            )
            );
        }
        
        // Преместваме самата нишка
        if (doc_Threads::save(
        (object)array(
            'id' => $id,
            'folderId' => $destFolderId
        )
        )) {
            
            // Нотифицираме новата и старата папка за настъпилото преместване
            
            // $currentFolderId сега има една нишка по-малко
            doc_Folders::updateFolderByContent($currentFolderId);
            
            // $destFolderId сега има една нишка повече
            doc_Folders::updateFolderByContent($destFolderId);
            
            //
            // Добавяме нови правила за рутиране на базата на току-що направеното преместване.
            //
            // expect($firstContainerId = static::fetchField($id, 'firstContainerId'));
            //email_Router::updateRoutingRules($firstContainerId, $destFolderId);
        }
    }
    
    
    
    /**
     * Обновява информацията за дадена тема.
     * Обикновенно се извиква след промяна на doc_Containers
     */
    function updateThread_($id)
    {
        // Вземаме записа на треда
        $rec = doc_Threads::fetch($id, NULL, FALSE);
        
        $dcQuery = doc_Containers::getQuery();
        $dcQuery->orderBy('#createdOn');
        
        // Публични документи в треда
        $rec->pubDocCnt = $rec->allDocCnt = 0;
        
        while($dcRec = $dcQuery->fetch("#threadId = {$id}")) {
            
            if(!$firstDcRec) {
                $firstDcRec = $dcRec;
            }
            
            $lastDcRec = $dcRec;
            
            if($dcRec->state != 'hidden') {
                $rec->pubDocCnt++;
            }
            
            $rec->allDocCnt++;
            
            $sharedArr = arr::combine($sharedArr, $dcRec->shared);
        }
        
        if($firstDcRec) {
            // Първи документ в треда
            $rec->firstContainerId = $firstDcRec->id;
            
            // Последния документ в треда
            $rec->last = $lastDcRec->createdOn;
            
            // Състояние по подразбиране на треда
            if(!$rec->state) {
                $rec->state = 'closed';
            }
            
            doc_Threads::save($rec, 'last, allDocCnt, pubDocCnt, firstContainerId, state');
        } else {
            $this->delete($id);
        }
        
        doc_Folders::updateFolderByContent($rec->folderId);
    }
    
    
    
    /**
     * Само за дебуг
     */
    function act_Update()
    {
        requireRole('admin');
        expect(isDebug());
        set_time_limit(200);
        $query = $this->getQuery();
        
        while($rec = $query->fetch()) {
            $this->updateThread($rec->id);
        }
    }
    
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    function on_AfterPrepareListToolbar($mvc, $res, $data)
    {
        
        // Бутони за разгледане на всички оттеглени тредове
        if(Request::get('Rejected')) {
            $data->toolbar->removeBtn('*');
            $data->toolbar->addBtn('Всички', array($mvc, 'folderId' => $data->folderId), 'id=listBtn,class=btn-list');
        } else {
            $data->toolbar->addBtn('MO', array('acc_Articles', 'add', 'folderId' => $data->folderId, 'ret_url' => TRUE));
            $data->toolbar->addBtn('LBT', array('lab_Tests', 'add', 'folderId' => $data->folderId, 'ret_url' => TRUE));
            $data->toolbar->addBtn('Задача', array('doc_Tasks', 'add', 'folderId' => $data->folderId, 'ret_url' => TRUE));
            $data->toolbar->addBtn('Кош', array($mvc, 'list', 'folderId' => $data->folderId, 'Rejected' => 1), 'id=binBtn,class=btn-bin,order=50');
        }
    }
    
    
    
    /**
     * Намира нишка по манипулатор на нишка.
     *
     * @param string $handle манипулатор на нишка
     * @return int key(mvc=doc_Threads) NULL ако няма съответена на манипулатора нишка
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
     * Генерира и връща манипулатор на нишка.
     *
     * @param int $id key(mvc=doc_Threads)
     * @return string манипулатора на нишката
     */
    public static function getHandle($id)
    {
        $rec = static::fetch($id, 'id, handle, firstContainerId');
        
        expect($rec);
        
        if (!$rec->handle) {
            expect($rec->firstContainerId);
            
            $rec->handle = doc_Containers::getHandle($rec->firstContainerId);
            
            expect($rec->handle);
            
            // Записваме току-що генерирания манипулатор в данните на нишката. Всеки следващ 
            // опит за вземане на манипулатор на тази нишка ще връща тази записана стойност
            static::save($rec);
        }
        
        return $rec->handle;
    }
    
    
    
    /**
     * Отваря треда
     */
    function act_Open()
    {
        expect($id = Request::get('threadId', 'int'));
        
        expect($rec = $this->fetch($id));
        $this->requireRightFor('single', $rec);
        
        $rec->state = 'opened';
        
        $this->save($rec);
        
        $this->updateThread($rec->id);
        
        return new Redirect(array('doc_Containers', 'list', 'threadId' => $id));
    }
    
    
    
    /**
     * Затваря треда
     */
    function act_Close()
    {
        expect($id = Request::get('threadId', 'int'));
        
        expect($rec = $this->fetch($id));
        
        $this->requireRightFor('single', $rec);
        
        $rec->state = 'closed';
        
        $this->save($rec);
        
        $this->updateThread($rec->id);
        
        return new Redirect(array('doc_Containers', 'list', 'threadId' => $id));
    }
}
