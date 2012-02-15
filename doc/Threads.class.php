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
    var $loadList = 'plg_Created,plg_Modified,plg_State,doc_Wrapper, plg_Select, expert_Plugin,plg_Sorting';
    
    
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
    var $listFields = 'hnd=Номер,title,author=Автор,last=Последно,allDocCnt=Документи,createdOn=Създаване';
    
    
    /**
     * Какви действия са допустими с избраните редове?
     */
    var $doWithSelected = 'open=Отваряне,close=Затваряне,reject=Оттегляне,move=Преместване';
    
    /**
     * Данните на адресанта, с най - много попълнени полета
     */
    static $contragentData = NULL;
    
    
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
    function on_AfterPrepareListTitle($mvc, $res, $data)
    {
        expect($data->folderId = Request::get('folderId', 'int'));
        
        $title = new ET("<div style='font-size:18px;'>[#user#] » [#folder#] ([#folderCover#])</div>");
        
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


        if(Request::get('Rejected')) {
            $title->append("&nbsp;<font class='state-rejected'>&nbsp;[" . tr('оттеглени') . "]&nbsp;</font>", 'folder');
        }
        
        
        $title->replace($user, 'user');
        
       
         
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
        
        // Показваме или само оттеглените или всички останали нишки
        if(Request::get('Rejected')) {
            $data->query->where("#state = 'rejected'");
        } else {
            $data->query->where("#state != 'rejected' || #state IS NULL");
        }
        
        // Изчистване на нотификации, свързани с промени в тази папка
        $url = array('doc_Threads', 'list', 'folderId' => $folderId);
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
        if(empty($rec->firstContainerId)) return;
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
     * Екшън за преместване на тред
     */
    function exp_Move($exp)
    {
        if($selected = Request::get('Selected')) {
            $selArr = arr::make($selected);
            Request::push(array('threadId' => $selArr[0]));
        }

        // TODO RequireRightFor
        
        $exp->DEF('#threadId=Нишка', 'key(mvc=doc_Threads)', 'fromRequest');
        $exp->DEF('#Selected=Избрани', 'varchar', 'fromRequest');
        
        $exp->functions['doc_threads_fetchfield'] = 'doc_Threads::fetchField';
        $exp->functions['getcompanyfolder'] = 'crm_Companies::getCompanyFolder';
        
        $exp->DEF('dest=Преместване към', 'enum(exFolder=Съществуваща папка, 
                                                newCompany=Нова папка на фирма,
                                                newPerson=Нова папка на лице)', 'maxRadio=4,columns=1', 'value=exFolder');
        
        $exp->question("#dest", "Моля, посочете къде да бъде преместена нишката:", TRUE, 'title=Ново място за нишката');
        
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
        
        // Данъчен номер на фирмата
        $exp->DEF('#vatId', 'drdata_VatType', 'caption=Данъчен №,remember=info,width=100%');
        
        // Допълнителна информация
        $exp->DEF('#info', 'richtext', 'caption=Бележки,height=150px');
        
        $exp->question("#company,#country,#pCode,#place,#address,#email,#tel,#fax,#website,#vatId,#website", "Моля, въведете контактните данни на фирмата:", "#dest == 'newCompany'", 'title=Преместване на нишка с документи');
        
        $exp->rule('#folderId', "getCompanyFolder(#company, #country, #pCode, #place, #address, #email, #tel, #fax, #website, #vatId)", TRUE);
        
        $exp->ASSUME('#folderId', "doc_Threads_fetchField(#threadId, 'folderId')", TRUE);
        
        $exp->question("#folderId", "Моля, изберете папка:", "#dest == 'exFolder'", 'title=Избор на папка за нишката');
        
        $result = $exp->solve('#folderId');
        
        if($result == 'SUCCESS') {
            $threadId = $exp->getValue('threadId');
            $folderId = $exp->getValue('folderId');
            $selected = $exp->getValue('Selected');
            
            $selArr = arr::make($selected);
            
            if(!count($selArr)) {
                $selArr[] = $threadId;
            }
            
            foreach($selArr as $threadId) {
                $this->move($threadId, $folderId);
            }
        }
        
        // Поставя в под формата, първия постинг в треда
        // TODO: да се замени с интерфейсен метод
        if($threadId = $exp->getValue('threadId')) {
            $threadRec = self::fetch($threadId);
            $originTpl = new ET("<div style='display:table'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr("Първи документ в нишката") . "</b></div>[#DOCUMENT#]</div>");
            $document = doc_Containers::getDocument($threadRec->firstContainerId);
            $docHtml = $document->getDocumentBody();
            $originTpl->append($docHtml, 'DOCUMENT');
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
            
            
            $sharedArr = arr::combine($sharedArr, $dcRec->shared);
        }
        
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
            $data->toolbar->addBtn('Нов...', array($mvc, 'ShowDocMenu', 'folderId' => $data->folderId), 'id=btnAdd,class=btn-add');
            $data->toolbar->addBtn('Кош', array($mvc, 'list', 'folderId' => $data->folderId, 'Rejected' => 1), 'id=binBtn,class=btn-bin,order=50');
        }
    }
    
    
    /**
     * Извиква се след изчисляване на ролите необходими за дадено действие
     */
    function on_AftergetRequiredRoles($mvc, $res, $action, $rec)
    {
        if($action == 'open') {
            if($rec->state == 'closed') {
                $res = $mvc->getRequiredRoles('single', $rec);
            } else {
                $res = 'no_one';
            }
        }
        
        if($action == 'close') {
            if($rec->state == 'opened') {
                $res = $mvc->getRequiredRoles('single', $rec);
            } else {
                $res = 'no_one';
            }
        }
        
        if($action == 'reject') {
            if($rec->state == 'opened' || $rec->state == 'closed') {
                $res = $mvc->getRequiredRoles('single', $rec);
            } else {
                $res = 'no_one';
            }
        }
        
        if($action == 'move') {
            $res = $mvc->getRequiredRoles('single', $rec);
        }
        
        if($action == 'single') {
            if(doc_Folders::haveRightToFolder($rec->folderId)) {
                $res = 'user';
            } elseif(type_Keylist::isIn(core_Users::getCurrent(), $rec->shared)) {
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
     * Връща данните, които са най - нови и с най - много записи
     */
    function getContragentData($threadId)
    {
        $query = doc_Containers::getQuery();
        $query->where("#state != 'rejected'");
        $query->where("#threadId = '{$threadId}'");
        $query->orderBy('createdOn', 'DESC');
        
        while ($rec = $query->fetch()) {
            $className = Cls::getClassName($rec->docClass);
            
            if (cls::haveInterface('doc_ContragentDataIntf', $className)) {
                $contragentData = $className::getContragentData($rec->docId);
            }
            
            self::checkBestContragentData($contragentData);
        }
        
        unset(self::$contragentData['cnt']);
        
        return (object) self::$contragentData;
    }
    
    
    /**
     * Проверява за най - добрата възможност на данните
     */
    function checkBestContragentData($contragentData)
    {
        if (!$contragentData) return;
        
        $contragentData = self::clearArray($contragentData);
        
        $points = self::calcPoints($contragentData);
        
        if ($points > self::$contragentData['cnt']) {
            self::$contragentData = $contragentData;
            self::$contragentData['cnt'] = $points;
        }
    }
    
    
    /**
     * Изчиства полетата с празни стойности на подадения масив
     */
    static function clearArray($arr)
    {
        $arr = (array) $arr;
        
        if (count($arr)) {
            foreach ($arr as $key => $value) {
                if (str::trim($value)) {
                    $newArr[$key] = $value;
                }
            }
        }
        
        return $newArr;
    }
    
    
    /**
     * Изчислява точките на подадения масив
     */
    static function calcPoints($data)
    {
        $data = (array) $data;
        $points = count($data);
        
        if ($data['email']) $points++;
        
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

        foreach($docArr as $id => $class) {
            
            $mvc = cls::get($class);
            
            if($mvc->canAddToFolder($folderId, '') && $mvc->haveRightFor('add')) {
                $tpl->append(ht::createBtn($mvc->singleTitle, array($class, 'add', 'folderId' => $folderId), NULL, NULL, "style=background-image:url(" . sbf($mvc->singleIcon, '') . ");"));

                $tpl->append('<br>');
            }
        }

        return $this->renderWrapping($tpl);
    }

}
