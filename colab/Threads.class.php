<?php


/**
 * Прокси на 'colab_Threads' позволяващ на партньор в роля 'partner' да има достъп до нишките в споделените
 * му папки, ако първия документ в нишката е видим за партньори, и папката е спдоелена към партньора той може да
 * види нишката. При Отваряне на нишката вижда само тези документи, които са видими за партньори
 *
 * @category  bgerp
 * @package   colab
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.12
 */
class colab_Threads extends core_Manager
{
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Споделени нишки';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Нишка';
    
    
    /**
     * 10 секунди време за опресняване на нишката
     */
    public $refreshRowsTime = 60000;
    
    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    public $loadList = 'cms_ExternalWrapper,Threads=doc_Threads,plg_RowNumbering,Containers=doc_Containers, doc_ThreadRefreshPlg, plg_RefreshRows';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'RowNumb=№,title=Заглавие,author=Автор,partnerDocLast=Последно,hnd=Номер,partnerDocCnt=Документи,createdOn=Създаване';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'title';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'partner';
    
    
    /**
     * Кой има право да чете?
     */
    public $canSingle = 'partner';
    
    
    /**
     * Кой има право да листва всички профили?
     */
    public $canList = 'partner';
    
    
    /**
     * Инстанция на doc_Threads
     */
    public $Threads;
    
    
    /**
     * Инстанция на doc_Threads
     */
    public $Containers;
    
    
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        // Задаваме за полета на проксито, полетата на оригинала
        $mvc->fields = cls::get('doc_Threads')->selectFields();
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        // Изискваме да е логнат потребител
        requireRole('user');
    }
    
    
    /**
     * Екшън по подразбиране е Single
     */
    public function act_Default()
    {
        // Редиректваме
        return new Redirect(array($this, 'list'));
    }
    
    
    /**
     * Подготвя достъпа до единичния изглед на една споделена нишка към контрактор
     */
    public function act_Single()
    {
        $this->forceProxy($this->className);

        expect($id = Request::get('threadId', 'key(mvc=doc_Threads)'));
        
        if (core_Users::isPowerUser()) {
            if (doc_Threads::haveRightFor('single', $id)) {
                
                return new Redirect(array('doc_Containers', 'list', 'threadId' => $id));
            }
        }
        
        $this->requireRightFor('single');
        Mode::set('currentExternalTab', 'cms_Profiles');
        $this->currentTab = 'Нишка';
        
        // Създаваме обекта $data
        $data = new stdClass();
        $data->action = 'single';
        $data->listFields = 'created=Създаване,document=Документи';
        $data->threadId = $id;
        $data->threadRec = $this->Threads->fetch($id);
        $data->folderId = $data->threadRec->folderId;
        
        // Трябва да можем да гледаме сингъла на нишката:
        // Трябва папката и да е споделена на текущия потребител и документа начало на нишка да е видим
        $this->requireRightFor('single', $data->threadRec);
        
        // Ако има папка записва се като активна
        colab_Folders::setLastActiveContragentFolder($data->folderId);

        $data->threadRec->partnerDocLast = (empty($data->threadRec->partnerDocLast)) ? '0000-00-00' : $data->threadRec->partnerDocLast;
        bgerp_Recently::add('document', $data->threadRec->firstContainerId, null, ($data->threadRec->state == 'rejected') ? 'yes' : 'no');
        $otherDocChanges = doc_Threads::fetch(array("#id != '[#1#]' AND #folderId = '[#2#]' AND #state != 'rejected' AND #partnerDocLast > '[#3#]'",
            $data->threadRec->id, $data->threadRec->folderId, $data->threadRec->partnerDocLast));
        if (!$otherDocChanges) {
            bgerp_Recently::add('folder', $data->threadRec->folderId, null, ($data->threadRec->state == 'rejected') ? 'yes' : 'no');
        }

        // Показваме само неоттеглените документи, чиито контейнери са видими за партньори
        $cu = core_Users::getCurrent();
        $sharedUsers = colab_Folders::getSharedUsers($data->folderId);
        $sharedUsers[$cu] = $cu;
        $sharedUsers = implode(',', $sharedUsers);
        
        $data->query = $this->Containers->getQuery();
        $data->query->where("#threadId = {$id}");
        $data->query->where("#visibleForPartners = 'yes'");
        $data->query->where("#state != 'draft' || (#state = 'draft' AND #createdBy  IN ({$sharedUsers}))");
        $data->query->where("#state != 'rejected' || (#state = 'rejected' AND #createdBy  IN ({$sharedUsers}))");
        $data->query->orderBy('createdOn,id', 'ASC');
        
        $this->prepareTitle($data);
        
        if (!isset($data->recs)) {
            $data->recs = array();
        }
        
        // Извличаме записите
        while ($rec = $data->query->fetch()) {
            $data->recs[$rec->id] = $rec;
        }
        
        // Вербализираме записите
        if (countR($data->recs)) {
            doc_HiddenContainers::prepareDocsForHide($data->recs);
            foreach ($data->recs as $id => $rec) {
                $data->rows[$id] = $this->Containers->recToVerbal($rec, arr::combine($data->listFields, '-list'));
            }
        }
        
        $this->Containers->prepareListToolbar($data);
        
        // Рендираме лист изгледа на контейнера
        $tpl = $this->Containers->renderList_($data);
        
        // Опаковаме изгледа
        $tpl = $this->renderWrapping($tpl, $data);

        if (!Request::get('ajax_mode')) {
            // Записваме, че потребителя е разглеждал този списък
            $this->Containers->logInAct('Листване', null, 'read');
        }

        return $tpl;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (!Request::get('Rejected')) {
            $documents = colab_Setup::get('CREATABLE_DOCUMENTS_LIST');
            $documents = keylist::toArray($documents);
            if (is_array($documents)) {
                foreach ($documents as $docId) {
                    $Doc = cls::get($docId);
                    
                    if ($Doc->haveRightFor('add', (object) array('folderId' => $data->folderId))) {
                        $data->toolbar->addBtn($Doc->singleTitle, array($Doc, 'add', 'folderId' => $data->folderId, 'ret_url' => true), "ef_icon={$Doc->singleIcon}");
                    }
                }
            }
        }
        
        doc_Threads::addBinBtnToToolbar($data);
        
        if (Request::get('Rejected')) {
            $data->toolbar->removeBtn('*', 'with_selected');
            $data->toolbar->addBtn('Всички', array('colab_Threads', 'list', 'folderId' => $data->folderId), 'id=listBtn', 'ef_icon = img/16/application_view_list.png');
        }
    }
    
    
    /**
     *
     *
     * @see core_Manager::act_List()
     */
    public function act_List()
    {
        $this->forceProxy($this->className);

        $folderId = Request::get('folderId', 'int');
        
        if (core_Users::isPowerUser()) {
            if ($folderId && doc_Folders::haveRightFor('single', $folderId)) {
                
                return new Redirect(array('doc_Threads', 'list', 'folderId' => $folderId));
            }
        }
        
        Mode::set('currentExternalTab', 'cms_Profiles');
        
        // Ако има папка записва се като активна
        if (isset($folderId) && colab_Folders::haveRightFor('list', (object) array('folderId' => $folderId))) {
            colab_Folders::setLastActiveContragentFolder($folderId);
        }

        $folderRec = doc_Folders::fetch($folderId);
        bgerp_Recently::add('folder', $folderId, null, ($folderRec->state == 'rejected') ? 'yes' : 'no');

        return parent::act_List();
    }
    
    
    /**
     * Подготовка на заглавието на нишката
     */
    public function prepareTitle(&$data)
    {
        $title = new ET("<div class='path-title'>[#folder#] ([#folderCover#])<!--ET_BEGIN threadTitle--> » [#threadTitle#]<!--ET_END threadTitle--></div>");
        
        $data->folderId = ($data->folderId) ? $data->folderId : Request::get('folderId', 'key(mvc=doc_Folders)');
        
        $folderTitle = doc_Folders::getVerbal($data->folderId, 'title');
        if (colab_Threads::haveRightFor('list', $data)) {
            $folderTitle = ht::createLink($folderTitle, array('colab_Threads', 'list', 'folderId' => $data->folderId), false, 'ef_icon=img/16/folder-icon.png');
        }
        $coverType = doc_Folders::recToVerbal(doc_Folders::fetch($data->folderId))->type;
        $title->replace($folderTitle, 'folder');
        $title->replace($coverType, 'folderCover');
        
        if ($data->threadRec->firstContainerId) {
            $document = $this->Containers->getDocument($data->threadRec->firstContainerId);
            $docRow = $document->getDocumentRow();
            $docTitle = str::limitLen($docRow->title, 70);
            $title->replace($docTitle, 'threadTitle');
        }
        
        $data->title = $title;
    }
    
    
    /**
     * Подготвя редовете във вербална форма
     */
    public function prepareListRows_(&$data)
    {
        $data->rows = array();
        
        if (countR($data->recs)) {
            foreach ($data->recs as $id => $rec) {
                $row = $this->Threads->recToVerbal($rec);
                
                $docProxy = doc_Containers::getDocument($rec->firstContainerId);
                $docRow = $docProxy->getDocumentRow();
                
                $row->title = $docRow->title;
                if ($this->haveRightFor('single', $rec)) {
                    $class = '';
                    $lastThreadSee = bgerp_Recently::getLastDocumentSee($rec->firstContainerId, null, true);
                    if ($lastThreadSee && ($rec->partnerDocLast > $lastThreadSee)) {
                        $class = ',class=tUnsighted';
                    }

                    $row->title = ht::createLink($docRow->title, array($this, 'single', 'threadId' => $id), false, "ef_icon={$docProxy->getIcon()},title=Разглеждане на нишката{$class}");
                }
                
                if ($docRow->subTitle) {
                    $row->title .= "\n<div class='threadSubTitle'>{$docRow->subTitle}</div>";
                }
                
                $row->allDocCnt = $row->partnerDocCnt;
                $data->rows[$id] = $row;
            }
        }
    }
    
    
    /**
     * Преди подготовка на данните за табличния изглед правим филтриране
     * на записите, които са (или не са) оттеглени и сортираме от нови към стари
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        if (isset($data->query)) {
            if (Request::get('Rejected')) {
                $data->query->where("#state = 'rejected'");
            } else {
                $data->rejQuery->where("#state = 'rejected'");
                $data->query->where("#state != 'rejected' OR #state IS NULL");
            }
        }
    }
    
    
    /**
     * Подготвя формата за филтриране
     */
    public function prepareListFilter_($data)
    {
        parent::prepareListFilter_($data);
        
        $data->listFilter->FNC('search', 'varchar', 'caption=Ключови думи,input,silent,recently,inputmode=search');
        $data->listFilter->setField('folderId', 'input=hidden,silent');
        $data->listFilter->FNC(
            'order',
            'enum(' . doc_Threads::filterList . ')',
                'allowEmpty,caption=Подредба,input,silent,autoFilter'
        );
        $data->listFilter->FNC('documentClassId', 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', 'caption=Вид документ,input,recently');
        
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Търсене', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->showFields = 'folderId,search,order,documentClassId';
        
        $data->listFilter->input(null, 'silent');
        
        $documentsInThreadOptions = doc_Threads::getDocumentTypesOptionsByFolder($data->listFilter->rec->folderId, true);
        if (countR($documentsInThreadOptions)) {
            $documentsInThreadOptions = array_map('tr', $documentsInThreadOptions);
            $data->listFilter->setOptions('documentClassId', $documentsInThreadOptions);
        } else {
            $data->listFilter->setReadOnly('documentClassId');
        }
        
        // По кое поле за последно да се подреждат
        $data->listFilter->rec->LastFieldName = 'partnerDocLast';
        
        doc_Threads::applyFilter($data->listFilter->rec, $data->query);
        $data->listFilterAddedFields = array();
        $Cover = doc_Folders::getCover($data->listFilter->rec->folderId);
        $Cover->invoke('AfterPrepareThreadFilter', array(&$data->listFilter, &$data->query, &$data->listFilterAddedFields));
        $data->rejQuery = clone($data->query);
        
        if(core_Users::isContractor() && !haveRole('powerPartner')){
            unset($data->listFields['partnerDocCnt']);
        }


        // Ако има търсене, рефрешването да е след по-дълго време
        if (isset($data->listFilter->rec->search)) {
            $this->refreshRowsTime = 600000; // 10 мин.
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        // Ако пакета 'colab' не е инсталиран, никой не може нищо
        if (!core_Packs::isInstalled('colab')) {
            $requiredRoles = 'no_one';
            
            return;
        }
        
        if ($action == 'list' && isset($rec->folderId)) {
            if ($rec->folderState == 'rejected') {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'list') {
            if (is_null($userId)) {
                $requiredRoles = 'no_one';
            } else {
                $folderId = Mode::get('lastFolderId');
                if(is_object($rec) && isset($rec->folderId)){
                    $folderId = $rec->folderId;
                } else {
                    if($reqFolderId = Request::get('folderId', 'key(mvc=doc_Folders)')){
                        $folderId = $reqFolderId;
                    }
                }

                $sharedFolders = colab_Folders::getSharedFolders($userId);
                
                if (!in_array($folderId, $sharedFolders)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if ($action == 'single' && isset($rec)) {
            if (is_null($userId)) {
                $requiredRoles = 'no_one';
            } else {
                // Трябва папката на нишката да е споделена към текущия партньор
                $sharedFolders = colab_Folders::getSharedFolders($userId);
                if (!in_array($rec->folderId, $sharedFolders)) {
                    $requiredRoles = 'no_one';
                }
            }
            
            if ($rec->visibleForPartners != 'yes') {
                $requiredRoles = 'no_one';
            }
            
            if(isset($userId) && !haveRole('powerPartner', $userId)){
                if(!empty($rec->createdBy) && ($rec->createdBy != $userId && !keylist::isIn($userId, $rec->shared))){
                    $requiredRoles = 'no_one';
                } elseif(empty($rec->createdBy)) {
                    $email = core_Users::fetchField($userId, 'email');
                    if(!is_object($rec)){
                        $requiredRoles = 'no_one';
                    } elseif(!$mvc->canNonPowerPartnerSeeAnonymDocument($rec, $email)){
                        $requiredRoles = 'no_one';
                    }
                }
            }
            
            if ($rec->visibleForPartners != 'yes') {
                if (!core_Users::haveRole('partner', $userId)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if ($requiredRoles != 'no_one') {
            // Ако потребителя няма роля партньор, не му е работата тук
            if (!core_Users::haveRole('partner', $userId)) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Връща асоциирана db-заявка към MVC-обекта
     *
     * @return core_Query
     */
    public function getQuery_($params = array())
    {
        if (empty($params['folderId'])) {
            expect($folderId = Request::get('folderId', 'key(mvc=doc_Folders)'));
        } else {
            $folderId = $params['folderId'];
        }
        
        $cu = core_Users::getCurrent();
        $sharedFolders = colab_Folders::getSharedFolders();
        
        $params['where'][] = "#folderId = {$folderId}";
        $res = $this->Threads->getQuery($params);
        $res->where("#visibleForPartners = 'yes'");
        $res->in('folderId', $sharedFolders);
        
        if(!haveRole('powerPartner', $cu)){
            $res->where("#createdBy = '{$cu}' || #createdBy = '0' || LOCATE('|{$cu}|', #shared)");
            
            // От записите създадени от анонимен потребител ще се проверява имейла му дали съвпада с този на текущия
            $availableRecs = array();
            $cuEmail = core_Users::fetchField($cu, 'email');
            $dummyQuery = clone $res;
            $dummyQuery->show('id,firstContainerId,createdBy');
            while ($rec = $dummyQuery->fetchAndCache()) {
                if($rec->createdBy == 0 && !$this->canNonPowerPartnerSeeAnonymDocument($rec, $cuEmail)) continue;
                $availableRecs[$rec->id] = $rec->id;
            }
            
            // Ако обикновенният партньор вижда само част от документи, ограничава се заявката
            if(countR($availableRecs)) {
                $res->in("id", $availableRecs);
            } else {
                $res->where("1 = 2");
            }
        }
        
        return $res;
    }
    
    
    /**
     * Може ли обикновен партньор да види документа
     * 
     * @param stdClass $threadRec
     * @param string $cuEmail
     * 
     * @return boolean
     */
    private function canNonPowerPartnerSeeAnonymDocument($threadRec, $cuEmail)
    {
        $docProxy = doc_Containers::getDocument($threadRec->firstContainerId);
        $docRow = $docProxy->getDocumentRow();
        
        return strtolower(trim($docRow->author)) == strtolower(trim($cuEmail));
    }
    
    
    /**
     * Изпълнява се след подготовката на листовия изглед
     */
    protected static function on_AfterPrepareListTitle($mvc, &$res, $data)
    {
        $mvc->prepareTitle($data);

        $url = array('colab_Threads', 'list', 'folderId' => Request::get('folderId', 'int'));
        bgerp_Notifications::clear($url);
    }
    
    
    /**
     * Връща хеша за листовия изглед. Вика се от bgerp_RefreshRowsPlg
     *
     * @param string $status
     *
     * @return string
     *
     * @see plg_RefreshRows
     */
    public static function getContentHash_(&$status)
    {
        doc_Threads::getContentHash_($status);
    }
}
