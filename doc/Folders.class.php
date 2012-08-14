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
class doc_Folders extends core_Master
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created,plg_Rejected,doc_Wrapper,plg_State,doc_FolderPlg,plg_Search, doc_ContragentDataIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Папки с нишки от документи";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,title,type=Тип,inCharge=Отговорник,threads=Нишки,last=Последно';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'user';
    
    
    /**
     * Кой може да добавя?
     */
    var $canAdd = 'no_one';

    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'user';
    
    
    /**
     * полета от БД по които ще се търси
     */
    var $searchFields = 'title';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Папка';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Определящ обект за папката
        $this->FLD('coverClass' , 'class(interface=doc_FolderIntf)', 'caption=Корица->Клас');
        $this->FLD('coverId' , 'int', 'caption=Корица->Обект');
        
        // Информация за папката
        $this->FLD('title' , 'varchar(255,ci)', 'caption=Заглавие');
        $this->FLD('status' , 'varchar(128)', 'caption=Статус');
        $this->FLD('state' , 'enum(active=Активно,opened=Отворено,rejected=Оттеглено)', 'caption=Състояние');
        $this->FLD('allThreadsCnt', 'int', 'caption=Нишки->Всички');
        $this->FLD('openThreadsCnt', 'int', 'caption=Нишки->Отворени');
        $this->FLD('last' , 'datetime(format=smartTime)', 'caption=Последно');
        
        $this->setDbUnique('coverId,coverClass');
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('users', 'users', 'caption=Потребител,input,silent');
        $data->listFilter->FNC('order', 'enum(pending=Първо чакащите,last=Сортиране по "последно")', 'caption=Подредба,input,silent');
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'users,order,search';
        $data->listFilter->input('users,order,search', 'silent');
    }
    
    
    /**
     * Действия преди извличането на данните
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        if(!$data->listFilter->rec->users) {
            $data->listFilter->rec->users = '|' . core_Users::getCurrent() . '|';
        }
        
        if(!$data->listFilter->rec->search) {
            $data->query->where("'{$data->listFilter->rec->users}' LIKE CONCAT('%|', #inCharge, '|%')");
            $data->query->orLikeKeylist('shared', $data->listFilter->rec->users);
            $data->title = 'Папките на |*<font color="green">' .
            $data->listFilter->fields['users']->type->toVerbal($data->listFilter->rec->users) . '</font>';
        } else {
            $data->title = 'Търсене във всички папки на |*<font color="green">"' .
            $data->listFilter->fields['search']->type->toVerbal($data->listFilter->rec->search) . '"</font>';
        }
        
        switch($data->listFilter->rec->order) {
            case 'last' :
                $data->query->orderBy('#last', 'DESC');
            case 'pending' :
            default :
            $data->query->orderBy('#state=DESC,#last=DESC');
        }
    }
    
    
    /**
     * Връща информация дали потребителя има достъп до посочената папка
     */
    static function haveRightToFolder($folderId, $userId = NULL)
    {
        if(!($folderId > 0)) return FALSE;

        $rec = doc_Folders::fetch($folderId);
        
        return doc_Folders::haveRightToObject($rec, $userId);
    }
    
    
    /**
     * Дали посоченият (или текущият ако не е посочен) потребител има право на достъп до този обект
     * Обекта трябва да има полета inCharge, access и shared
     */
    static function haveRightToObject($rec, $userId = NULL)
    {
        if(!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        // Вземаме членовете на екипа на потребителя (TODO:)
        $teamMembers = core_Users::getTeammates($userId);
        
        // 'ceo' има достъп до всяка папка
        if(haveRole('ceo')) return TRUE;
        
        // Всеки има право на достъп до папката за която отговаря
        if($rec->inCharge === $userId) return TRUE;
        
        // Всеки има право на достъп до папките, които са му споделени
        if(strpos($rec->shared, '|' . $userId . '|') !== FALSE) return TRUE;
        
        // Всеки има право на достъп до общите папки
        if($rec->access == 'public') return TRUE;
        
        // Дали обекта има отговорник - съекипник
        $fromTeam = strpos($teamMembers, '|' . $rec->inCharge . '|') !== FALSE;
        
        // Ако папката е екипна, и е на член от екипа на потребителя, и потребителя е manager или officer - има достъп
        if($rec->access == 'team' && $fromTeam && core_Users::haveRole('manager,officer,executive', $userId)) return TRUE;
        
        // Ако собственика на папката има права 'manager' или 'ceo' отказваме достъпа
        if(core_Users::haveRole('manager,ceo', $rec->inCharge)) return FALSE;
        
        // Ако папката е лична на член от екипа, и потребителя има права 'manager' - има достъп
        if($rec->access == 'private' && $fromTeam && haveRole('manager')) return TRUE;
        
        // Ако никое от горните не е изпълнено - отказваме достъпа
        return FALSE;
    }
    
    
    /**
     * След преобразуване към вербални данни на записа
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        
        $openThreads = $mvc->getVerbal($rec, 'openThreadsCnt');
        
        if($rec->openThreadsCnt) {
            $row->threads = "<span style='float-right; background-color:#aea;padding:1px;border:solid 1px #9d9;'>$openThreads</span>";
        }
        
        $row->threads .= "<span style='float:right;'>&nbsp;&nbsp;&nbsp;" . $mvc->getVerbal($rec, 'allThreadsCnt') . "</span>";
        
        $attr['class'] = 'linkWithIcon';
        $row->title = str::limitLen($row->title, 48);
        
        if($mvc->haveRightFor('single', $rec)) {
            // Иконката на папката според достъпа и
            
            switch($rec->access) {
                case 'secret' :
                    $img = 'folder_key.png';
                    break;
                case 'private' :
                    $img = 'folder_user.png';
                    break;
                case 'team' :
                case 'public' :
                default :
                $img = 'folder-icon.png';
            }
            
            $attr['style'] = 'background-image:url(' . sbf('img/16/' . $img) . ');';
            $row->title = ht::createLink($row->title, array('doc_Threads', 'list', 'folderId' => $rec->id), NULL, $attr);
        } else {
            $attr['style'] = 'color:#777;background-image:url(' . sbf('img/16/lock.png') . ');';
            $row->title = ht::createElement('span', $attr, $row->title);
        }
        
        $typeMvc = cls::get($rec->coverClass);
        
        $attr['style'] = 'background-image:url(' . sbf($typeMvc->singleIcon) . ');';
        
        if($typeMvc->haveRightFor('single', $rec->coverId)) {
            $row->type = ht::createLink(tr($typeMvc->singleTitle), array($typeMvc, 'single', $rec->coverId), NULL, $attr);
        } else {
            $attr['style'] .= 'color:#777;';
            $row->type = ht::createElement('span', $attr, $typeMvc->singleTitle);
        }
    }
    
    
    /**
     * Обновява информацията за съдържанието на дадена папка
     */
    static function updateFolderByContent($id)
    {
        // Извличаме записа на папката
        $rec = doc_Folders::fetch($id);
        
        // Запомняме броя на отворените теми до сега
        $exOpenThreadsCnt = $rec->openThreadsCnt;
        
        $thQuery = doc_Threads::getQuery();
        $rec->openThreadsCnt = $thQuery->count("#folderId = {$id} AND state = 'opened'");
        
        if($rec->openThreadsCnt) {
            $rec->state = 'opened';
        } else {
            $rec->state = 'active';
        }
        
        $thQuery = doc_Threads::getQuery();
        $rec->allThreadsCnt = $thQuery->count("#folderId = {$id} AND #state != 'rejected'");
        
        $thQuery = doc_Threads::getQuery();
        $thQuery->orderBy("#last", 'DESC');
        $thQuery->limit(1);
        $lastThRec = $thQuery->fetch("#folderId = {$id} AND #state != 'rejected'");
        
        $rec->last = $lastThRec->last;
        
        doc_Folders::save($rec, 'last,allThreadsCnt,openThreadsCnt,state');
        
        // Генерираме нотификация за потребителите, споделили папката
        // ако имаме повече отворени теми от преди
        if($exOpenThreadsCnt < $rec->openThreadsCnt) {
            
            $msg = tr('Отворени теми в') . " \"$rec->title\"";
            
            $url = array('doc_Threads', 'list', 'folderId' => $id);
            
            $userId = $rec->inCharge;
            
            $priority = 'normal';
            
            bgerp_Notifications::add($msg, $url, $userId, $priority);
            
            if($rec->shared) {
                foreach(type_Keylist::toArray($rec->shared) as $userId) {
                    bgerp_Notifications::add($msg, $url, $userId, $priority);
                }
            }
        } elseif($exOpenThreadsCnt > 0 && $rec->openThreadsCnt == 0) {
            // Изчистване на нотификации за отворени теми в тази папка
            $url = array('doc_Threads', 'list', 'folderId' => $rec->id);
            bgerp_Notifications::clear($url, '*');
        }
    }
    
    
    /**
     * Обновява информацията за корицата на посочената папка
     */
    static function updateByCover($id)
    {
        $rec = doc_Folders::fetch($id);
        
        if(!$rec) return;
        
        $coverMvc = cls::get($rec->coverClass);
        
        if(!$rec->coverId) {
            expect($coverRec = $coverMvc->fetch("#folderId = {$id}"));
            $rec->coverId = $coverRec->id;
            $mustSave = TRUE;
        } else {
            expect($coverRec = $coverMvc->fetch($rec->coverId));
        }
        
        $coverRec->title = $coverMvc->getFolderTitle($coverRec->id, FALSE);
        
        if($coverRec->state != 'rejected') {
            $coverRec->state = $rec->state;
        }
        
        $fields = 'title,state,inCharge,access,shared';
        
        foreach(arr::make($fields) as $field) {
            if($rec->{$field} != $coverRec->{$field}) {
                $rec->{$field} = $coverRec->{$field};
                $mustSave = TRUE;
            }
        }
        
        if($mustSave) {
            static::save($rec);
        }
    }
    
    
    /**
     * Създава празна папка за посочения тип корица
     * и връща нейното $rec->id
     */
    static function createNew($coverMvc)
    {
        $rec = new stdClass();
        $rec->coverClass = core_Classes::fetchIdByName($coverMvc);
        
        // Задаваме няколко параметъра по подразбиране за 
        $rec->status = '';
        $rec->allThreadsCnt = 0;
        $rec->openThreadsCnt = 0;
        $rec->last = dt::verbal2mysql();
        
        static::save($rec);
        
        return $rec->id;
    }
    
    
    /**
     * Изпълнява се след начално установяване(настройка) на doc_Folders
     * @todo Да се махне
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        $query = $mvc->getQuery();
        
        while($rec = $query->fetch()) {
            if(($rec->state != 'active') && ($rec->state != 'rejected') && ($rec->state != 'opened') && ($rec->state != 'closed')) {
                $rec->state = 'active';
                $mvc->save($rec, 'state');
                $res .= "<li style='color:red'> $rec->title - active";
            }
        }
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     */
    static function getContragentData($id)
    {
        //Вземаме данните за ковъра от папката
        $folder = doc_Folders::fetch($id, 'coverClass, coverId');
        
        //id' то на класа, който е ковър на папката
        $coverClass = $folder->coverClass;
        
        //Ако класа поддържа интерфейса doc_ContragentDataIntf 
        if (cls::haveInterface('doc_ContragentDataIntf', $coverClass)) {
            //Името на класа
            $className = Cls::get($coverClass);
            
            //Контрагентните данни, взети от класа
            $contragentData = $className::getContragentData($folder->coverId);
        }
        
        return $contragentData;
    }
    
    
    /**
     * Добавя към заявка необходимите условия, така че тя да връща само папките, достъпни за
     * даден потребител.
     *
     * @param core_Query $query
     * @param int $userId key(mvc=core_Users)
     */
    static function restrictAccess($query, $userId = NULL)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        $teammates = type_Keylist::toArray(core_Users::getTeammates($userId));
        $ceos      = core_Users::getByRank('ceo');
        $managers  = core_Users::getByRank('manager');
        
        // Подчинените в екипа (използва се само за мениджъри)
        $subordinates = array_diff($teammates, $ceos, $managers);
        
        foreach (array('teammates', 'ceos', 'managers', 'subordinates') as $v) {
            if (${$v}) {
                ${$v} = implode(',', ${$v});
            } else {
                ${$v} = FALSE;
            }
        }
        
        $conditions = array(
            "#folderAccess = 'public'",           // Всеки има достъп до публичните папки
            "#folderShared LIKE '%|{$userId}|%'", // Всеки има достъп до споделените с него папки
            "#folderInCharge = {$userId}",        // Всеки има достъп до папките, на които е отговорник
        );
        
        if ($teammates) {
            // Всеки има достъп до екипните папки, за които отговаря негов съекипник
            $conditions[] = "#folderAccess = 'team' AND #folderInCharge IN ({$teammates})";
        }
        
        switch (true) {
            case core_Users::haveRole('ceo') :
            // CEO вижда всичко с изключение на private и secret папките на другите CEO
            if ($ceos) {
                $conditions[] = "#folderInCharge NOT IN ({$ceos})";
            }
            break;
            case core_Users::haveRole('manager') :
            // Manager вижда private папките на подчинените в екипите си
            if ($subordinates) {
                $conditions[] = "#folderAccess = 'private' AND #folderInCharge IN ({$subordinates})";
            }
            break;
        }
        
        if ($query->mvc->className != 'doc_Folders') {
            // Добавя необходимите полета от модела doc_Folders
            $query->EXT('folderAccess', 'doc_Folders', 'externalName=access,externalKey=folderId');
            $query->EXT('folderInCharge', 'doc_Folders', 'externalName=inCharge,externalKey=folderId');
            $query->EXT('folderShared', 'doc_Folders', 'externalName=shared,externalKey=folderId');
        } else {
            $query->XPR('folderAccess', 'varchar', '#access');
            $query->XPR('folderInCharge', 'varchar', '#inCharge');
            $query->XPR('folderShared', 'varchar', '#shared');
        }
        
        $query->where(core_Query::buildConditions($conditions, 'OR'));
    }
    
    
    /**
     * Връща езика на папката от държавата на визитката
     *
     * @param int $id - id' то на папката
     *
     * @return string $lg - Двубуквеното означение на предполагаемия език на имейла
     */
    static function getLanguage($id)
    {
        //Ако няма стойност, връщаме
        if (!$id) return ;
        
        //id' то на класа, който е корица
        $coverClassId = doc_Folders::fetchField($id, 'coverClass');
        
        //Името на корицата на класа
        $coverClass = cls::getClassName($coverClassId);
        
        //Ако корицата е Лице или Фирма
        if (($coverClass == 'crm_Persons') || ($coverClass == 'crm_Companies')) {
            
            //Вземаме държавата
            $classRec = $coverClass::fetch("#folderId = '{$id}'", 'country');
            
            //Ако има въведена държава
            if ($classRec->country) {
                //Проверяваме дали е българия
                $country = $coverClass::getVerbal($classRec, 'country');
                
                //Ако държавата е българия
                if (strtolower($country) == 'bulgaria') {
                    $lg = 'bg';
                } else {
                    $lg = 'en';
                }
                
                return $lg;
            }
        }
    }


    /**
     * Връща папката по подразбиране за текущия потребител
     * Ако има дефинирана 'корпоративна' сметка за имейли, то папката е корпоративната имейл-кутия на потребителя
     * В противен случай, се връща куп със заглавие 'Документите на {Names}'
     */
    static function getDefaultFolder($userId = NULL)
    {   
        if(!$userId) {
            $names = core_Users::getCurrent('names');
            $nick  = core_Users::getCurrent('nick');
        } else {
            $names = core_Users::fetchField($userId, 'names');
            $nick  = core_Users::fetchField($userId, 'nick');
        }
        
        $rec = new stdClass();
        $rec->inCharge = $userId;
        $rec->access = 'private';

        $corpAccRec = email_Accounts::getCorporateAcc();

        if($corpAccRec) {
            $rec->email = "{$nick}@{$corpAccRec->domain}";
            $rec->accountId = $corpAccRec->id;
            $folderId = email_Inboxes::forceCoverAndFolder($rec);
        } else {
            $rec->name = "Документите на {$nick}";
            $folderId = doc_UnsortedFolders::forceCoverAndFolder($rec);
        }

        return $folderId;
    }
}
