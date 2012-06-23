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
 * @since     v 0.11
 */
class doc_Threads extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created,plg_Modified,plg_State,doc_Wrapper, plg_Select, expert_Plugin,plg_Sorting';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'user';
    
    
    /**
     * Заглавие
     */
    var $title = "Нишки от документи";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Нишка от документи";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'hnd=Номер,title=Заглавие,author=Автор,last=Последно,allDocCnt=Документи,createdOn=Създаване,modifiedOn';
    
    
    /**
     * Какви действия са допустими с избраните редове?
     */
    var $doWithSelected = 'open=Отваряне,close=Затваряне,reject=Оттегляне,move=Преместване';
    
    /**
     * Данните на адресанта, с най - много попълнени полета
     */
    static $contragentData = NULL;
    
    
    /**
     * Описание на модела на нишките от контейнери за документи
     */
    function description()
    {
        // Информация за нишката
        $this->FLD('folderId', 'key(mvc=doc_Folders,select=title,silent)', 'caption=Папки');
       // $this->FLD('title', 'varchar(255)', 'caption=Заглавие');
        $this->FLD('state', 'enum(opened,waiting,closed,rejected)', 'caption=Състояние,notNull');
        $this->FLD('allDocCnt', 'int', 'caption=Брой документи->Всички');
        $this->FLD('pubDocCnt', 'int', 'caption=Брой документи->Публични');
        $this->FLD('last', 'datetime(format=smartTime)', 'caption=Последно');
        
        // Ключ към първия контейнер за документ от нишката
        $this->FLD('firstContainerId' , 'key(mvc=doc_Containers)', 'caption=Начало,input=none,column=none,oldFieldName=firstThreadDocId');
        
        // Достъп
        $this->FLD('shared' , 'keylist(mvc=core_Users, select=nick)', 'caption=Споделяне');
        
        // Манипулатор на нишката (thread handle)
        $this->FLD('handle', 'varchar(32)', 'caption=Манипулатор');
        
        // Индекс за по-бързо избиране по папка
        $this->setDbIndex('folderId');
    }
    
    
    /**
     * Екшън за оттегляне на тредове
     */
    function act_Reject()
    {
        if($selected = Request::get('Selected')) {
            $selArr = arr::make($selected);
            
            foreach($selArr as $id) {
                if($this->haveRightFor('single', $id)) {
                    Request::push(array('id' => $id, 'Selected' => FALSE));
                    $res = Request::forward();
                    Request::pop();
                }
            }
        } else {
            expect($id = Request::get('id', 'int'));
            expect($rec = $this->fetch($id));
            $this->requireRightFor('single', $rec);
            $fDoc = doc_Containers::getDocument($rec->firstContainerId);
            
            Request::push(array('id' => $fDoc->that, 'Ctr' => $fDoc->className, 'Act' => 'Reject'));
            $res = Request::forward();
            Request::pop();
        }
        
        return $res;
    }
    
    
    /**
     * Подготвя титлата на папката с теми
     */
    static function on_AfterPrepareListTitle($mvc, &$res, $data)
    {
        expect($data->folderId = Request::get('folderId', 'int'));
        
        $title = new ET("<div style='font-size:18px;'>[#user#] » [#folder#] ([#folderCover#])</div>");
        
        // Папка и корица
        $folderRec = doc_Folders::fetch($data->folderId);
        $folderRow = doc_Folders::recToVerbal($folderRec);
        $title->replace($folderRow->title, 'folder');
        $title->replace($folderRow->type, 'folderCover');
        
        // Потребител
        if($folderRec->inCharge > 0) {
            $user = core_Users::fetchField($folderRec->inCharge, 'nick');
        } else {
            $user = '@system';
        }
        $title->replace($user, 'user');
        
        if(Request::get('Rejected')) {
            $title->append("&nbsp;<font class='state-rejected'>&nbsp;[" . tr('оттеглени') . "]&nbsp;</font>", 'folder');
        }
        
        $title->replace($user, 'user');
        
        $data->title = $title;

        $mvc->title = '|*' . doc_Folders::getTitleById($folderRec->id) . '|' ;
    }
    
    
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('search', 'varchar', 'caption=Ключови думи,input,silent,recently');
        $data->listFilter->FNC('order', 'enum(open=Първо отворените, recent=По последно, create=По създаване, numdocs=По брой документи)', 'allowEmpty,caption=Подредба,input,silent');
        $data->listFilter->setField('folderId', 'input=hidden,silent');
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Търсене', 'default', 'id=filter,class=btn-filter');
        
        $data->listFilter->showFields = 'folderId,search,order';

        $data->listFilter->input(NULL, 'silent');
    }
    
    
    /**
     * Филтрира по папка и ако е указано показва само оттеглените записи
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        expect($folderId = $data->listFilter->rec->folderId);
        
        doc_Folders::requireRightFor('single');
        
        expect($folderRec = doc_Folders::fetch($folderId));
        
        doc_Folders::requireRightFor('single', $folderRec);
        
        $mvc::applyFilter($data->listFilter->rec, $data->query);
        
        $data->query->orderBy('#state=ASC,#last=DESC');
        
        // Показваме или само оттеглените или всички останали нишки
        if(Request::get('Rejected')) {
            $data->query->where("#state = 'rejected'");
        } else {
            $data->query->where("#state != 'rejected' OR #state IS NULL");
        }
        
        // Изчистване на нотификации, свързани с промени в тази папка
        $url = array('doc_Threads', 'list', 'folderId' => $folderId);
        bgerp_Notifications::clear($url);
        bgerp_Recently::add('folder', $folderId);
    }
    
    
    /**
     * Налага данните на филтъра като WHERE /GROUP BY / ORDER BY клаузи на заявка
     *
     * @param stdClass $filter
     * @param core_Query $query
     */
    static function applyFilter($filter, $query)
    {
        if (!empty($filter->folderId)) {
            $query->where("#folderId = {$filter->folderId}");
        }
        
        // Налагане на условията за търсене
        if (!empty($filter->search)) {
            $query->EXT('containerSearchKeywords', 'doc_Containers', 'externalName=searchKeywords');
            $query->where(
            	  '`' . doc_Containers::getDbTableName() . '`.`thread_id`' . ' = ' 
                . '`' . static::getDbTableName() . '`.`id`');
            
            plg_Search::applySearch($filter->search, $query, 'containerSearchKeywords');
            
            $query->groupBy('`doc_threads`.`id`');
        }
        
        // Подредба - @TODO
        switch ($filter->order) {
            case 'open':
                $query->XPR('isOpened', 'int', "IF(#state = 'opened', 0, 1)");
                $query->orderBy('#isOpened');
                break;
            case 'recent':
                $query->orderBy('#last=DESC');
                break;
            case 'create':
                $query->orderBy('#createdOn=DESC');
                break;
            case 'numdocs':
                $query->orderBy('#allDocCnt=DESC');
                break;
        }
        
        $query->orderBy('#state=ASC,#last=DESC');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if(empty($rec->firstContainerId)) return;
        $docProxy = doc_Containers::getDocument($rec->firstContainerId);
        
        $docRow = $docProxy->getDocumentRow();
        
        $attr['class'] .= 'linkWithIcon';
        $attr['style'] = 'background-image:url(' . sbf($docProxy->instance->singleIcon) . ');';
        
        $row->title = ht::createLink(str::limitLen($docRow->title, 70),
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
    static function create($folderId, $createdOn)
    {
        $rec = new stdClass();
        $rec->folderId = $folderId;
        $rec->createdOn = $createdOn;
        
        self::save($rec);
        
        return $rec->id;
    }
    
    
    /**
     * Екшън за преместване на тред
     */
    function exp_Move($exp)
    {
        if($selected = Request::get('Selected')) {
            $selArr = arr::make($selected);
            Request::push(array('threadId' => $selArr[0]));
        }
        
        $threadId = Request::get('threadId', 'int');
        
        if($threadId) {
            $this->requireRightFor('single', $threadId);

            $tRec = $this->fetch($threadId);
        }
        
        // TODO RequireRightFor
        $exp->DEF('#threadId=Нишка', 'key(mvc=doc_Threads)', 'fromRequest');
        $exp->DEF('#Selected=Избрани', 'varchar', 'fromRequest');
        
        $exp->functions['doc_threads_fetchfield'] = 'doc_Threads::fetchField';
        $exp->functions['getcompanyfolder'] = 'crm_Companies::getCompanyFolder';
        $exp->functions['getcontragentdata'] = 'doc_Threads::getContragentData';
        $exp->functions['getquestionformoverest'] = 'doc_Threads::getQuestionForMoveRest';
        
        $exp->DEF('dest=Преместване към', 'enum(exFolder=Съществуваща папка, 
                                                newCompany=Нова папка на фирма,
                                                newPerson=Нова папка на лице)', 'maxRadio=4,columns=1', 'value=exFolder');
        
        if(count($selArr) > 1) {
            $exp->question("#dest", "Моля, посочете къде да бъдат преместени нишките:", TRUE, 'title=Преместване на нишки от документи');
        } else {
            if($tRec->allDocCnt > 1) {
                $exp->question("#dest", "Моля, посочете къде да бъде преместена нишката:", TRUE, 'title=Преместване на нишка от документи');
            } else {
                $exp->question("#dest", "Моля, посочете къде да бъде преместен документа:", TRUE, 'title=Преместване на документ в нова папка');
            }
        }
        
        $exp->DEF('#folderId=Папка', 'key(mvc=doc_Folders, select=title)', 'width=500px');
        
        // Информация за фирма и представител
        $exp->DEF('#company', 'varchar(255)', 'caption=Фирма,width=100%,mandatory,remember=info');
        $exp->DEF('#names', 'varchar', 'caption=Лице,width=100%,mandatory,remember=info');
        
        // Адресни данни
        $exp->DEF('#country', 'key(mvc=drdata_Countries,select=commonName,allowEmpty)', 'caption=Държава,remember');
        $exp->DEF('#pCode', 'varchar(255)', 'caption=П. код,recently');
        $exp->DEF('#place', 'varchar(255)', 'caption=Град,width=100%');
        $exp->DEF('#address', 'varchar(255)', 'caption=Адрес,width=100%');
        
        // Комуникации
        $exp->DEF('#email', 'emails', 'caption=Имейл,width=100%');
        $exp->DEF('#tel', 'drdata_PhoneType', 'caption=Телефони,width=100%');
        $exp->DEF('#fax', 'drdata_PhoneType', 'caption=Факс,width=100%');
        $exp->DEF('#website', 'url', 'caption=Web сайт,width=100%');
        
        // Стойности по подразбиране при нова папка на фирма или лице
        $exp->ASSUME('#email', "getContragentData(#threadId, 'email')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#country', "getContragentData(#threadId, 'countryId')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#company', "getContragentData(#threadId, 'company')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#tel', "getContragentData(#threadId, 'tel')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#fax', "getContragentData(#threadId, 'fax')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#pCode', "getContragentData(#threadId, 'pCode')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#place', "getContragentData(#threadId, 'place')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#address', "getContragentData(#threadId, 'address')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#web', "getContragentData(#threadId, 'web')", "#dest == 'newCompany' || #dest == 'newPerson'");
        
        $exp->SUGGESTIONS('#company', "getContragentData(#threadId, 'companyArr')", "#dest == 'newCompany' || #dest == 'newPerson'");
        
        // Данъчен номер на фирмата
        $exp->DEF('#vatId', 'drdata_VatType', 'caption=Данъчен №,remember=info,width=100%');
        
        // Допълнителна информация
        $exp->DEF('#info', 'richtext', 'caption=Бележки,height=150px');
        
        $exp->question("#company,#country,#pCode,#place,#address,#email,#tel,#fax,#website,#vatId,#website", "Моля, въведете контактните данни на фирмата:", "#dest == 'newCompany'", 'title=Преместване на нишка с документи');
        
        $exp->rule('#folderId', "getCompanyFolder(#company, #country, #pCode, #place, #address, #email, #tel, #fax, #website, #vatId)", TRUE);
        
        $exp->ASSUME('#folderId', "doc_Threads_fetchField(#threadId, 'folderId')", TRUE);
        
        $exp->question("#folderId", "Моля, изберете папка:", "#dest == 'exFolder'", 'title=Избор на папка за нишката');
        
        // От какъв клас е корицата на папката където е изходния тред?
        $exp->DEF('#moveRest=Преместване на всички', 'enum(yes=Да,no=Не)');
        $exp->rule('#askMoveRest', "getQuestionForMoveRest(#threadId)", TRUE);
        $exp->question("#moveRest", "=#askMoveRest", '#askMoveRest', 'title=Групово преместване');
        $exp->rule("#moveRest", "'no'", '!(#askMoveRest)');
        $exp->rule("#moveRest", "'no'", '#Selected');
        
        $result = $exp->solve('#folderId,#moveRest');
        
        if($result == 'SUCCESS') {
            $threadId = $exp->getValue('threadId');
            $this->requireRightFor('single', $threadId);
            $folderId = $exp->getValue('folderId');
            $selected = $exp->getValue('Selected');
            $moveRest = $exp->getValue('moveRest');
            $threadRec = doc_Threads::fetch($threadId);
            
            if($moveRest == 'yes') {
                $doc = doc_Containers::getDocument($threadRec->firstContainerId);
                $msgRec = $doc->fetch();
                $msgQuery = email_Incomings::getQuery();
                
                while($mRec = $msgQuery->fetch("#folderId = {$threadRec->folderId} AND #state != 'rejected' AND LOWER(#fromEml) = LOWER('{$msgRec->fromEml}')")) {
                    $selArr[] = $mRec->threadId;
                }
            } else {
                $selArr = arr::make($selected);
            }
            
            if(!count($selArr)) {
                $selArr[] = $threadId;
            }
            
            foreach($selArr as $threadId) {
                $this->move($threadId, $folderId);
            }
            
            // Изходяща папка
            $folderFromRec = doc_Folders::fetch($threadRec->folderId);
            $folderFromRow = doc_Folders::recToVerbal($folderFromRec);
            
            // Входяща папка
            $folderToRec = doc_Folders::fetch($folderId);
            $folderToRow = doc_Folders::recToVerbal($folderToRec);
            
            $exp->message = count($selArr) . " нишки от {$folderFromRow->title} са преместени в {$folderToRow->title}";
        }
        
        // Поставя  под формата, първия постинг в треда
        // TODO: да се замени с интерфейсен метод
        if($threadId = $exp->getValue('threadId')) {
            $threadRec = self::fetch($threadId);
            $originTpl = new ET("<div style='display:table'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr("Първи документ в нишката") . "</b></div>[#DOCUMENT#]</div>");
            $document = doc_Containers::getDocument($threadRec->firstContainerId);
            $docHtml = $document->getDocumentBody();
            $originTpl->append($docHtml, 'DOCUMENT');
            
            if(!$exp->midRes) {
                $exp->midRes = new stdClass();
            }
            $exp->midRes->afterForm = $originTpl;
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
            expect($rec->docId, $rec);
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
     * @todo Чака за документация...
     */
    static function getQuestionForMoveRest($threadId)
    {
        $threadRec = doc_Threads::fetch($threadId);
        $folderRec = doc_Folders::fetch($threadRec->folderId);
        $coverClassName = cls::getClassName($folderRec->coverClass);
        
        if($coverClassName == 'doc_UnsortedFolders' || $coverClassName == 'email_Inboxes') {
            $doc = doc_Containers::getDocument($threadRec->firstContainerId);
            
            if($doc->className == 'email_Incomings') {
                $msgRec = $doc->fetch();
                $msgQuery = email_Incomings::getQuery();
                $sameEmailMsgCnt =
                $msgQuery->count("#folderId = {$folderRec->id} AND #state != 'rejected' AND LOWER(#fromEml) = LOWER('{$msgRec->fromEml}')") - 1;
                
                if($sameEmailMsgCnt > 0) {
                    $res = "Желаете ли и останалите {$sameEmailMsgCnt} имейл-а от {$msgRec->fromEml}, намиращи се в {$folderRec->title} също да бъдат преместени?";
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * Обновява информацията за дадена тема.
     * Обикновено се извиква след промяна на doc_Containers
     */
    function updateThread_($id)
    {
        // Вземаме записа на треда
        $rec = doc_Threads::fetch($id, NULL, FALSE);
        
        // Запазваме общия брой документи
        $exAllDocCnt = $rec->allDocCnt;
        
        $dcQuery = doc_Containers::getQuery();
        $dcQuery->orderBy('#createdOn');
        
        // Публични документи в треда
        $rec->pubDocCnt = $rec->allDocCnt = 0;

        
        while($dcRec = $dcQuery->fetch("#threadId = {$id}")) {
            
            if(!$firstDcRec) {
                $firstDcRec = $dcRec;
            }
            
            // Не броим оттеглените документи
            if($dcRec->state != 'rejected') {
                $lastDcRec = $dcRec;
                
                if($dcRec->state != 'hidden') {
                    $rec->pubDocCnt++;
                }
                
                $rec->allDocCnt++;
            }
            
        }

        $rec->shared = type_Keylist::fromArray(doc_ThreadUsers::getShared($rec->id));

        if($firstDcRec) {
            // Първи документ в треда
            $rec->firstContainerId = $firstDcRec->id;
            
            // Последния документ в треда
            $rec->last = $lastDcRec->createdOn;
            
            // Ако имаме добавяне/махане на документ от треда, тогава състоянието му
            // се определя от последния документ в него
            if($rec->allDocCnt != $exAllDocCnt) {
                if($lastDcRec) {
                    $doc = doc_Containers::getDocument($lastDcRec->id);
                    $newState = $doc->getThreadState();
                    
                    if($newState) {
                        $rec->state = $newState;
                    }
                }
            }
            
            // Състоянието по подразбиране за треда е затворено
            if(!$rec->state) {
                $rec->state = 'closed';
            }
            
            doc_Threads::save($rec, 'last, allDocCnt, pubDocCnt, firstContainerId, state, shared, modifiedOn, modifiedBy');

        } else {
            $this->delete($id);
        }
        
        doc_Folders::updateFolderByContent($rec->folderId);
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        
        // Бутони за разгледане на всички оттеглени тредове
        if(Request::get('Rejected')) {
            $data->toolbar->removeBtn('*');
            $data->toolbar->addBtn('Всички', array($mvc, 'folderId' => $data->folderId), 'id=listBtn,class=btn-list');
        } else {
            $data->toolbar->addBtn('Нов...', array($mvc, 'ShowDocMenu', 'folderId' => $data->folderId), 'id=btnAdd,class=btn-add');
            $data->toolbar->addBtn('Кош', array($mvc, 'list', 'folderId' => $data->folderId, 'Rejected' => 1), 'id=binBtn,class=btn-bin,order=50');
        }
    }
    
    
    /**
     * Извиква се след изчисляване на ролите необходими за дадено действие
     */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec, $userId = NULL)
    {
        if($action == 'open') {
            if($rec->state == 'closed') {
                $res = $mvc->getRequiredRoles('single', $rec, $userId);
            } else {
                $res = 'no_one';
            }
        }
        
        if($action == 'close') {
            if($rec->state == 'opened') {
                $res = $mvc->getRequiredRoles('single', $rec, $userId);
            } else {
                $res = 'no_one';
            }
        }
        
        if($action == 'reject') {
            if($rec->state == 'opened' || $rec->state == 'closed') {
                $res = $mvc->getRequiredRoles('single', $rec, $userId);
            } else {
                $res = 'no_one';
            }
        }
        
        if($action == 'move') {
            $res = $mvc->getRequiredRoles('single', $rec, $userId);
        }

        if($action == 'single') {
            if(doc_Folders::haveRightToFolder($rec->folderId, $userId)) {
                $res = 'user';
            } elseif(type_Keylist::isIn($userId, $rec->shared)) {
                $res = 'user';
            } else {
                $res = 'no_one';
            }
        }
    }
    
    
    /**
     * Намира нишка по манипулатор на нишка.
     *
     * @param string $handle манипулатор на нишка
     * @return int key(mvc=doc_Threads) NULL ако няма съответна на манипулатора нишка
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
        if($selected = Request::get('Selected')) {
            
            foreach(arr::make($selected) as $id) {
                $R = cls::get('core_Request');
                Request::push(array('threadId' => $id, 'Selected' => FALSE));
                Request::forward();
                Request::pop();
            }
            
            followRetUrl();
        }
        
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
        if($selected = Request::get('Selected')) {
            
            foreach(arr::make($selected) as $id) {
                $R = cls::get('core_Request');
                Request::push(array('threadId' => $id, 'Selected' => FALSE));
                Request::forward();
                Request::pop();
            }
            
            followRetUrl();
        }
        
        expect($id = Request::get('threadId', 'int'));
        
        expect($rec = $this->fetch($id));
        
        $this->requireRightFor('single', $rec);
        
        $rec->state = 'closed';
        
        $this->save($rec);
        
        $this->updateThread($rec->id);
        
        return new Redirect(array('doc_Containers', 'list', 'threadId' => $id));
    }
    
    
    /**
     * Намира контрагента с който се комуникира по тази нишка
     * Връща данните, които са най - нови и с най - много записи
     */
    static function getContragentData($threadId, $field = NULL)
    {
        static $cashe;
        
        if(!$bestContragentData = $cashe[$threadId]) {
            $query = doc_Containers::getQuery();
            $query->where("#state != 'rejected'");
            $query->where("#threadId = '{$threadId}'");
            $query->orderBy('createdOn', 'DESC');
            
            // Текущо най-добрата оценка за данни на контрагент
            $bestRate = 0;
            
            while ($rec = $query->fetch()) {
                $className = Cls::getClassName($rec->docClass);
                
                if (cls::haveInterface('doc_ContragentDataIntf', $className)) {
                    $contragentData = $className::getContragentData($rec->docId);
                    
                    $rate = self::calcPoints($contragentData);
                    
                    if($rate > $bestRate) {
                        $bestContragentData = clone($contragentData);
                        $bestRate = $rate;
                    }
                }
            }
            
            //Вземаме данните на потребителя от папката
            //След като приключим обхождането на треда
            $folderId = doc_Threads::fetchField($threadId, 'folderId');
            
            $contragentData = doc_Folders::getContragentData($folderId);
            
            if($contragentData) {
                $rate = self::calcPoints($contragentData) + 4;
            } else {
                $rate = 0;
            }
            
            if($rate > $bestRate) {
                if($bestContragentData->company == $contragentData->company) {
                    foreach(array('tel', 'fax', 'email', 'web', 'address', 'person') as $part) {
                        if($bestContragentData->{$part}) {
                            setIfNot($contragentData->{$part}, $bestContragentData->{$part});
                        }
                    }
                }
                
                $bestContragentData = $contragentData;
                $bestRate = $rate;
            }
            
            // Попълваме вербалното или индексното представяне на държавата, ако е налично другото
            if($bestContragentData->countryId && !$bestContragentData->country) {
                $bestContragentData->country = drdata_Countries::fetchField($bestContragentData->countryId, 'commonName');
            }
            
            // Попълваме вербалното или индексното представяне на фирмата, ако е налично другото
            if($bestContragentData->companyId && !$bestContragentData->company) {
                $bestContragentData->company = crm_Companies::fetchField($bestContragentData->companyId, 'name');
            }
            
            // Попълваме вербалното или индексното представяне на държавата, ако е налично другото
            if(!$bestContragentData->countryId && $bestContragentData->country) {
                $bestContragentData->countryId = drdata_Countries::fetchField(array("#commonName LIKE '%[#1#]%'", $bestContragentData->country), 'id');
            }
            
            if(!$bestContragentData->countryId && $bestContragentData->country) {
                $bestContragentData->countryId = drdata_Countries::fetchField(array("#formalName LIKE '%[#1#]%'", $bestContragentData->country), 'id');
            }
            
            $cashe[$threadId] = $bestContragentData;
        }
        
        if($field) {
            return $bestContragentData->{$field};
        } else {
            return $bestContragentData;
        }
    }
    
    
    /**
     * Изчислява точките на подадения масив
     */
    static function calcPoints($data)
    {
        $dataArr = (array) $data;
        $points = 0;
        
        foreach($dataArr as $key => $value) {
            if(!is_scalar($value) || empty($value)) continue;
            $len = max(0.5, min(mb_strlen($value) / 20, 1));
            $points += $len;
        }
        
        if($dataArr['company']) $points += 3;
        
        return $points;
    }
    
    
    /**
     * Показва меню от възможности за добавяне на нови документи към посочената нишка
     * Очаква folderId
     */
    function act_ShowDocMenu()
    {
        expect($folderId = Request::get('folderId', 'int'));
        
        doc_Folders::requireRightFor('single', $folderId);
        
        $tpl = new ET();
        
        $docArr = core_Classes::getOptionsByInterface('doc_DocumentIntf');
        
        $tpl->append("\n<h3>" . tr('Добавяне на нов документ в папката') . ":</h3>");
        $tpl->append("\n<table>");

        foreach($docArr as $id => $class) {
            
            $mvc = cls::get($class);
            

            if($mvc->canAddToFolder($folderId, '') && $mvc->haveRightFor('add')) {
                $tpl->append("\n<tr><td>");
                $tpl->append(ht::createBtn($mvc->singleTitle, array($class, 'add', 'folderId' => $folderId, 'ret_url' => TRUE), NULL, NULL, "class=linkWithIcon,style=background-image:url(" . sbf($mvc->singleIcon, '') . ");width:100%;text-align:left;"));
                
                $tpl->append("</td></tr>");
            }

            

        }

        $tpl->append("\n</table>");

        return $this->renderWrapping($tpl);
    }
    
    
    /**
     * Връща всички външни за системата имейл адреси, които са свързани с даден тред:
     * - тези на изпращачите на писма към него
     * - тези към които са адресирани писма от треда
     * - тези към които са изпратени писма от треда
     */
    static function getExternalEmails($id)
    {
        $result =
        email_Incomings::getExternalEmails($id)
        + email_Outgoings::getExternalEmails($id)
        + email_Sent::getExternalEmails($id);
        
        $folderId = static::fetchField($id, 'folderId');
        
        $cd = doc_Folders::getContragentData($folderId);
        
        if ($cd && $cd->email) {
            $result[$cd->email] = $cd->email;
        }
        
        return $result;
    }
    
    
    /**
     * Добавя към заявка необходимите условия, така че тя да връща само достъпните нишки.
     *
     * В резултат заявката ще селектира само достъпните за зададения потребител нишки които са
     * в достъпни за него папки (@see doc_Folders::restrictAccess())
     *
     * @param core_Query $query
     * @param int $userId key(mvc=core_Users) текущия по подразбиране
     */
    static function restrictAccess($query, $userId = NULL)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        doc_Folders::restrictAccess($query, $userId);
        
        if ($query->mvc->className != 'doc_Threads') {
            // Добавя необходимите полета от модела doc_Threads
            $query->EXT('threadShared', 'doc_Threads', 'externalName=shared,externalKey=threadId');
        } else {
            $query->XPR('threadShared', 'varchar', '#shared');
        }
        
        $query->orWhere("#threadShared LIKE '%|{$userId}|%'");
    }
    
    
    /**
     * Връща езика на нишката
     *
     * @param int $id - id' то на нишката
     *
     * @return string $lg - Двубуквеното означение на предполагаемия език на имейла
     */
    static function getLanguage($id)
    {
        //Ако няма стойност, връщаме
        if (!$id) return ;
        
        //Записа на нишката
        $threadRec = doc_Threads::fetch($id);
        
        //id' то на контейнера на първия документ в треда
        $firstContId = $threadRec->firstContainerId;
        
        //Документа
        $oDoc = doc_Containers::getDocument($firstContId);
        
        //Името на класа
        $className = $oDoc->className;
        
        //Вземаме записите на класа
        $classRec = $className::fetch($oDoc->that);
        
        //Връщаме езика
        return $classRec->lg;
    }

    
    /**
     * Връща титлата на нишката, която е заглавието на първия документ в нишката
     */
    static function getThreadTitle($id)
    {
        $rec = self::fetch($id);
        $document = doc_Containers::getDocument($rec->firstContainerId);
        $docRow = $document->getDocumentRow();
        
        return $docRow->title;
    }
}
