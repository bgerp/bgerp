<?php


/**
 * Клас 'doc_Search' - Търсене в документната система
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_Search extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Търсене на документи';
    
    
    /**
     * Зареждане на плъгини
     */
    public $loadList = 'doc_Wrapper, plg_Search, plg_State';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой има достъп до списъчния изглед
     */
    public $canList = 'powerUser';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title=Заглавие,author=Автор,createdOn=Създаване,hnd=Номер,modifiedOn=Модифициране||Modified';
    
    
    /**
     * @see plg_Search
     */
    public $searchInId = false;
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     *
     * Задаваме NULL за да избегнем обновяването на ключовите думи на контейнера след всеки
     * запис. Ключовите думи в контейнер се обновяват по различен механизъм - при промяна на
     * съотв. документ (@see doc_Containers::update_())
     */
    public $searchFields = null;
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $DC = cls::get('doc_Containers');
        
        $this->fields = $DC->fields;
        $this->dbTableName = $DC->dbTableName;
        $this->dbIndexes = $DC->dbIndexes;
    }
    
    
    /**
     * Изпълнява се след подготовката на филтъра за листовия изглед
     * Обикновено тук се въвеждат филтриращите променливи от Request
     */
    public static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->title = 'Търсене на документи';
        $data->listFilter->FNC('scopeFolderId', 'key2(mvc=doc_Folders, allowEmpty, , maxSuggestions=5)', ' silent,width=100%,caption=Обхват');
        $data->listFilter->FNC('fromDate', 'date', 'input,silent,caption=От,width=140px, placeholder=Дата');
        $data->listFilter->FNC('toDate', 'date', 'input,silent,caption=До,width=140px, placeholder=Дата');
        $data->listFilter->FNC('author', 'type_Users(rolesForAll=user)', 'caption=Автор');
        $data->listFilter->FNC('withMe', 'enum(,shared_with_me=Споделени с мен, liked_from_me=Харесани от мен)', 'caption=Само, placeholder=Всички');
        
        $data->listFilter->getField('state')->type->options = array('all' => 'Всички') + $data->listFilter->getField('state')->type->options;
        $data->listFilter->setField('search', 'caption=Ключови думи');
        $data->listFilter->setField('docClass', 'caption=Вид документ,placeholder=Всички');
        
        $data->listFilter->setDefault('author', 'all_users');
        
        $data->listFilter->showFields = 'search, scopeFolderId, docClass,  author, withMe, state, fromDate, toDate';
        $data->listFilter->toolbar->addSbBtn('Търсене', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->input(null, 'silent');
        
        $filterRec = $data->listFilter->rec;
        
        $isFiltered =
        !empty($filterRec->search) ||
        !empty($filterRec->scopeFolderId) ||
        !empty($filterRec->docClass) ||
        !empty($filterRec->withMe) ||
        !empty($filterRec->state) ||
        !empty($filterRec->fromDate) ||
        !empty($filterRec->toDate) ||
        $filterRec->author != 'all_users';
        
        // Флаг, указващ дали се филтрира
        $mvc->isFiltered = $isFiltered;
        
        // Ако формата е субмитната
        if ($isFiltered && ($filterRec->fromDate || $filterRec->toDate)) {
            
            // Ако са попълнени полетата От и До
            if ($filterRec->fromDate && $filterRec->toDate) {
                
                // Ако До е след От
                if ($filterRec->toDate < $filterRec->fromDate) {
                    
                    // Имената на полетата
                    $fromDateCaption = $data->listFilter->getField('fromDate')->caption;
                    $toDateCaption = $data->listFilter->getField('toDate')->caption;
                    
                    // Сетваме грешката
                    $data->listFilter->setError('toDate', 'Края на периода за търсене не може да е преди началото му');
                }
            }
            
            // Днешната дата
            $now = dt::now(false);
            
            // Ако се търси в бъдеще
            if ($filterRec->fromDate && $filterRec->fromDate > $now) {
                
                // Сетваме грешката
                $data->listFilter->setError('fromDate', 'Не може да се търси в бъдеще');
            }
        }
        
        // Ако се търси по документите на някой потребител, без да се гледа много 
        if ($isFiltered && !$filterRec->fromDate && !$filterRec->toDate && !$data->listFilter->ignore && !$data->query->isSlowQuery) {
            if (empty($filterRec->search) && empty($filterRec->scopeFolderId)) {
                if (!empty($filterRec->docClass) && (!strpos($filterRec->author, '-1')) && plg_Search::isBigTable($data->query)) {
                    $data->query->isSlowQuery = true;
                }
            }
        }
        
        if ($data->query->isSlowQuery && !$data->listFilter->ignore) {
            if (!$filterRec->fromDate && !$filterRec->toDate) {
                $data->listFilter->setWarning('search, fromDate, toDate', 'Заявката за търсене е много обща и вероятно ще се изпълни бавно. Добавете още думи или я ограничете по дати');
                $dFrom = dt::addMonths(-1, null, false);
                $dFrom = cls::get('type_Date')->toVerbal($dFrom);
                Request::push(array('fromDate' => $dFrom));
            }
        }
        
        // Има зададен условия за търсене - генерираме SQL заявка.
        if ($isFiltered && !$data->listFilter->gotErrors()) {
            
            // Ако някой ще направи обработки преди вземането на резултата
            $mvc->invoke('BeforePrepareSearhQuery', array($data, $filterRec));
            
            // Търсене на определен тип документи
            $SearchDocument = null;
            if (!empty($filterRec->docClass)) {
                $data->query->where(array('#docClass = [#1#]', $filterRec->docClass));
                
                if (cls::load($filterRec->docClass)) {
                    
                    // Ако търсения документ е счетоводен
                    $Doc = cls::get($filterRec->docClass);
                    if (cls::haveInterface('acc_TransactionSourceIntf', $Doc)) {
                        
                        // И има поле за вальор
                        if ($Doc->getField($Doc->valiorFld, false)) {
                            
                            // Искаме да показваме и вальора
                            $SearchDocument = $Doc;
                            $data->query->EXT($SearchDocument->valiorFld, $Doc->className, "externalName={$SearchDocument->valiorFld},externalKey=docId");
                            arr::placeInAssocArray($data->listFields, array($SearchDocument->valiorFld => 'Вальор'), 'createdOn');
                            $mvc->FNC($SearchDocument->valiorFld, 'date');
                        }
                    }
                }
            }
            
            // Търсене по дата на създаване на документи (от-до)
            if (!empty($filterRec->fromDate) && !empty($filterRec->toDate)) {
                $where = "(#createdOn >= '[#1#]' AND #createdOn <= '[#2#] 23:59:59') OR (#modifiedOn >= '[#1#]' AND #modifiedOn <= '[#2#] 23:59:59')";
                
                // Ако търсим по документ с вальор, добавяме вальора в търсенето по дата
                if ($SearchDocument instanceof core_Mvc) {
                    $where .= " OR (#{$SearchDocument->valiorFld} >= '[#1#]' AND #{$SearchDocument->valiorFld} <= '[#2#] 23:59:59')";
                }
                
                $data->query->where(array($where, $filterRec->fromDate, $filterRec->toDate));
            }
            
            // Търсене по дата на създаване на документи (от-до)
            if (!empty($filterRec->fromDate)) {
                $where = "(#createdOn >= '[#1#]') AND (#modifiedOn >= '[#1#]')";
                
                // Ако търсим по документ с вальор, добавяме вальора в търсенето по дата
                if ($SearchDocument instanceof core_Mvc) {
                    $where = "({$where}) OR (#{$SearchDocument->valiorFld} >= '[#1#]')";
                }
                
                $data->query->where(array($where, $filterRec->fromDate));
            }
            
            if (!empty($filterRec->toDate)) {
                $where = "(#createdOn <= '[#1#] 23:59:59') AND (#modifiedOn <= '[#1#] 23:59:59')";
                
                // Ако търсим по документ с вальор, добавяме вальора в търсенето по дата
                if ($SearchDocument instanceof core_Mvc) {
                    $where = "({$where}) OR (#{$SearchDocument->valiorFld} <= '[#1#] 23:59:59')";
                }
                
                $data->query->where(array($where, $filterRec->toDate));
            }
            
            if ($filterRec->scopeFolderId) {
                $data->query->where(array("#folderId = '[#1#]'", $filterRec->scopeFolderId));
            }
            
            // Ограничаване на търсенето до избрана папка
            if (!empty($filterRec->scopeFolderId) && doc_Folders::haveRightFor('single', $filterRec->scopeFolderId)) {
                $restrictAccess = false;
            } else {
                $restrictAccess = true;
            }
            
            // Ако е избран автор или не са избрани всичките
            if (!empty($filterRec->author) && $filterRec->author != 'all_users' && (strpos($filterRec->author, '|-1|') === false)) {
                
                // Масив с всички избрани автори
                $authorArr = keylist::toArray($filterRec->author);
                
                $firstTime = true;
                
                // Обхождаме масива
                foreach ($authorArr as $author) {
                    if ($firstTime) {
                        // Добавяме в запитването
                        $data->query->where("#createdBy = '{$author}'");
                    } else {
                        $data->query->orWhere("#createdBy = '{$author}'");
                    }
                    
                    $firstTime = false;
                }
            }
            
            // Ако не е избрано състояние или не са избрани всичките
            if (!empty($filterRec->state) && $filterRec->state != 'all') {
                
                // Добавяме запитването
                $data->query->where(array("#state = '[#1#]'", $filterRec->state));
            }
            
            // Ако не търсим оттеглените документи, тогава да не се показват
            if ($filterRec->state != 'rejected') {
                
                // Избягваме търсенето в оттеглените документи
                $data->query->where("#state != 'rejected'");
            }
            
            // id на текущия потребител
            $currUserId = core_Users::getCurrent();
            
            if ($filterRec->withMe && ($currUserId > 0)) {
                
                // Ако ще се показват само харесаните от текущия потребител
                if ($filterRec->withMe == 'liked_from_me') {
                    // Всички харесвания
                    $data->query->EXT('likedBy', 'doc_Likes', 'externalName=createdBy, remoteKey=containerId');
                    $data->query->where(array("#likedBy = '[#1#]'", $currUserId));
                } elseif ($filterRec->withMe == 'shared_with_me') {
                    $data->query->EXT('sharedBy', 'doc_ThreadUsers', 'externalName=userId, remoteKey=containerId');
                    $data->query->EXT('relation', 'doc_ThreadUsers', 'externalName=relation, remoteKey=containerId');
                    $data->query->where("#relation = 'shared'");
                    $data->query->where(array("#sharedBy = '[#1#]'", $currUserId));
                }
            }
            
            if ($restrictAccess) {
                // Ограничаване на заявката само до достъпните нишки
                doc_Threads::restrictAccess($data->query, $currUserId);
                
                // Създател
                $data->query->orWhere("#createdBy = '{$currUserId}'");
            }
            
            // Експеримент за оптимизиране на бързодействието
            $data->query->orderBy('#modifiedOn=DESC');

            $aArr = type_UserList::toArray($filterRec->author); 
            if (countR($aArr) == 1 && is_numeric(reset($aArr))) {
                $data->query->useIndex('created_by');
            } elseif ($filterRec->scopeFolderId) {
                $data->query->useIndex('folder_id');
            }

            /**
             * Останалата част от заявката - търсенето по ключови думи - ще я допълни plg_Search
             */
            
            // Ако ще се филтира по състояни и текущия потребител (автор)
            if ($filterRec->state) {
                $url = array($mvc, 'state' => $filterRec->state);
                
                $url2 = array($mvc);
                if ($filterRec->docClass) {
                    $url2['docClass'] = $filterRec->docClass;
                }
                $url2['state'] = $filterRec->state;
                
                if ($filterRec->author) {
                    $url2['author'] = Request::get('author');
                }
                
                // Ако се филтрира по текущия автор
                if ($filterRec->author && type_Keylist::isIn(core_Users::getCurrent(), $filterRec->author)) {
                    $url['author'] = core_Users::getCurrent();
                }
                $url2['fromDate'] = $filterRec->fromDate;
                
                // Изтриваме нотификацията, ако има такава, създадена от текущия потребител и със съответното състояние
                bgerp_Notifications::clear($url);
                
                // Изтриваме нотификацията, ако има такава, създадена от текущия потребител и със съответното състояние и за съответния документ
                bgerp_Notifications::clear($url2);
            }
        } else {
            // Няма условия за търсене - показваме само формата за търсене, без данни
            $data->query->where('0 = 1');
        }
        
        $data->query->useCacheForPager = true;
    }
    
    
    /**
     * Ако се търси манипулатор на файл, да се редиректне към сингъла му
     *
     * @param plg_Search $mvc
     * @param object     $data
     * @param object     $filtreRec
     */
    public function on_BeforePrepareSearhQuery($mvc, $data, $filtreRec)
    {
        // Тримваме търсенето
        $search = trim($filtreRec->search);
        
        // Ако няма търсене
        if (!$search) {
            
            return;
        }
        
        // Ако не е начало на манипулатор на документ
        if ($search[0] != '#') {
            
            return ;
        }
        
        // Вземаме информацията за документа
        $info = doc_RichTextPlg::getFileInfo($search);
        
        // Ако няма информация, да не се изпълнява
        if ($info && $info['className'] && $info['id']) {
            $className = $info['className'];
            
            $rec = $className::fetchByHandle($info);
            
            // Ако имаме права за сингъла и ако има такъв документ, да се редиректне там
            redirect($className::getSingleUrlArray($rec->id));
        } else {
            $search = ltrim($search, '#');
            
            $rec = cat_Products::fetch(array("#code = '[#1#]'", $search));
            
            if ($rec && ($singleUrl = cat_Products::getSingleUrlArray($rec->id))) {
                redirect($singleUrl);
            }
        }
    }
    
    
    /**
     * След извличане на записите от базата данни
     */
    public function on_AfterPrepareListRecs($mvc, $data)
    {
        if (count($data->recs) == 0) {
            
            return;
        }
        
        foreach ($data->recs as $id => &$rec) {
            if (cls::load($rec->docClass, true)) {
                $DocClass = cls::get($rec->docClass);
                $rec->state = doc_Threads::fetchField($rec->threadId, 'state');
            } else {
                $rec->state = 'closed';
            }
        }
    }
    
    
    /**
     * След подготовка на записите
     */
    public function on_AfterPrepareListRows($mvc, $data)
    {
        if (count($data->recs) == 0) {
            
            return;
        }
        
        foreach ($data->recs as $i => &$rec) {
            $row = $data->rows[$i];
            
            // $folderRec = doc_Folders::fetch($rec->folderId);
            // $folderRow = doc_Folders::recToVerbal($folderRec);
            // $row->folderId = $folderRow->title;
            
            try {
                $doc = doc_Containers::getDocument($rec->id);
                $row->docLink = $doc->getLink(64, array('Q' => $data->listFilter->rec->search));
            } catch (core_exception_Expect $exp) {
                $row->docLink = $row->title = "<b style='color:red;'>" . tr('Грешка') . '</b>';
            }
        }
    }
    
    
    /**
     * Преди рендиране на лист таблицата
     */
    public function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        if(Mode::get('screenMode') == 'narrow') {
            $data->listTableMvc->FLD('title', 'varchar', 'tdClass=largeNarrowCell');
            $data->listTableMvc->FLD('author', 'varchar', 'tdClass=nowrap');
        }

        if (!$mvc->isFiltered) {
            
            return false;
        }
    }


    /**
     * След подготовка на заглавието
     */
    public static function on_AfterPrepareListTitle($mvc, $data)
    {
        $data->title = null;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass     $row Това ще се покаже
     * @param stdClass     $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        try {
            $docProxy = doc_Containers::getDocument($rec->id);
            $docRow = $docProxy->getDocumentRow();
        } catch (core_Exception_Expect $expect) {
            
            return;
        }
        
        $attr = array();
        $attr['ef_icon'] = $docProxy->getIcon();
        
        $handle = $rec->handle ? substr($rec->handle, 0, strlen($rec->handle) - 3) : $docProxy->getHandle();
        
        if (mb_strlen($docRow->title) > doc_Threads::maxLenTitle) {
            $attr['title'] = '|*' . $docRow->title;
        }
        $linkUrl = array($docProxy->className, 'single', $docProxy->that);
        
        $search = Request::get('search');
        if (trim($search)) {
            $linkUrl['Q'] = $search;
        }
        
        // Удебеляване на документи, променени след последното виждане
        if ($rec->modifiedOn > bgerp_Recently::getLastDocumentSee($rec->id)) {
            $attr['class'] .= " tUnsighted";
        }
        
        $row->title = ht::createLink(
            
            str::limitLen($docRow->title, doc_Threads::maxLenTitle),
            $linkUrl,
            null,
            
            $attr
        
        );
        
        if ($docRow->authorId > 0) {
            $row->author = crm_Profiles::createLink($docRow->authorId);
        } else {
            $row->author = $docRow->author;
        }
        
        $row->hnd = "<div onmouseup='selectInnerText(this);' class=\"state-{$docRow->state} document-handler\">#{$handle}</div>";
    }
    
    
    /**
     * Обновява ключовите думи на контейнери
     *
     * @param bool $bEmptyOnly TRUE - само контейнерите, на които им липсват ключови думи
     *
     * @return int брой на контейнерите с реално обновени ключови думи
     */
    public static function updateSearchKeywords($bEmptyOnly = false)
    {
        /* @var $self doc_Containers */
        $self = cls::get(get_called_class());
        
        /* @var $query core_Query */
        $query = static::getQuery();
        $query->show('id, docId, docClass');
        
        if ($bEmptyOnly) {
            $query->where("#searchKeywords IS NULL OR #searchKeywords = ''");
        }
        
        $numUpdated = 0;
        
        while ($rec = $query->fetch()) {
            $docMvc = cls::get($rec->docClass);
            if (isset($docMvc->searchFields) && !empty($rec->docId)) {
                $searchKeywords = $docMvc->getSearchKeywords($rec->docId);
                if ($searchKeywords != $rec->searchKeywords) {
                    $rec->searchKeywords = $searchKeywords;
                    
                    // Записваме без да предизвикваме събитие за запис
                    if ($self->save_($rec)) {
                        $numUpdated++;
                    }
                }
            }
        }
        
        return $numUpdated;
    }
    
    
    /**
     * След сетъп на модела
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        if (Request::get('updateKeywords')) {
            if ($n = $mvc::updateSearchKeywords()) {
                $res .= "<li style=\"color: green;\">Обновени ключовите думи на <b>{$n}</b> контейнер(а)</li>";
            }
        }
    }
}
