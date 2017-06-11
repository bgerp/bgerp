<?php



/**
 * Клас 'doc_Search' - Търсене в документната система
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_Search extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Търсене на документи";
    
    
    /**
     * Зареждане на плъгини
     */
    var $loadList = 'doc_Wrapper, plg_Search, plg_State';
    
    
    /**
     * Кой може да добавя
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има достъп до списъчния изглед
     */
    var $canList = 'powerUser';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'title=Заглавие,author=Автор,createdOn=Създаване,hnd=Номер,modifiedOn=Модифициране||Modified';
    
    
    /**
     * @see plg_Search
     */
    public $searchInId = FALSE;
    
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     *
     * Задаваме NULL за да избегнем обновяването на ключовите думи на контейнера след всеки
     * запис. Ключовите думи в контейнер се обновяват по различен механизъм - при промяна на
     * съотв. документ (@see doc_Containers::update_())
     */
    var $searchFields = NULL;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $DC = cls::get('doc_Containers');
        
        $this->fields = $DC->fields;
        $this->dbTableName = $DC->dbTableName;
        $this->dbIndexes   = $DC->dbIndexes;
    }
    
    
    /**
     * Изпълнява се след подготовката на филтъра за листовия изглед
     * Обикновено тук се въвеждат филтриращите променливи от Request
     */
    static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->title = 'Tърсене на документи';
        $data->listFilter->FNC('scopeFolderId', 'enum(0=Всички папки)', 'input=none,silent,width=100%,caption=Обхват');
        $data->listFilter->FNC('fromDate', 'date', 'input,silent,caption=От,width=140px, placeholder=Дата');
        $data->listFilter->FNC('toDate', 'date', 'input,silent,caption=До,width=140px, placeholder=Дата');
        $data->listFilter->FNC('author', 'type_Users(rolesForAll=user)', 'caption=Автор');
        $data->listFilter->FNC('liked', 'enum(no_matter=Без значение, someone=От някого, me=От мен)', 'caption=Харесвания');
        
        $conf = core_Packs::getConfig('doc');
        $lastFoldersArr = bgerp_Recently::getLastFolderIds($conf->DOC_SEARCH_FOLDER_CNT);
        
        // Търсим дали има посочена папка
        $searchFolderId = Request::get('scopeFolderId', 'int');
        if (($searchFolderId) && (doc_Folders::haveRightFor('single', $searchFolderId))) {
            $lastFoldersArr[$searchFolderId] = $searchFolderId;
        }
        
        $scopeField = $data->listFilter->getField('scopeFolderId');
        
        foreach ($lastFoldersArr as $folderId) {
            $folderTitle = doc_Folders::fetchField($folderId, 'title');
            if (!$folderTitle) continue;
            $scopeField->type->options[$folderId] = '|*' . $folderTitle;
        }
        
        $data->listFilter->setField('scopeFolderId', 'input');
    	
        $data->listFilter->getField('state')->type->options = array('all' => 'Всички') + $data->listFilter->getField('state')->type->options;
    	$data->listFilter->setField('search', 'caption=Ключови думи');
        $data->listFilter->setField('docClass', 'caption=Вид документ,placeholder=Всички');
        
        $data->listFilter->setDefault('author', 'all_users');
        
        $data->listFilter->showFields = 'search, scopeFolderId, docClass,  author, liked, state, fromDate, toDate';
        $data->listFilter->toolbar->addSbBtn('Търсене', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->input(NULL, 'silent');
        
    	$filterRec = $data->listFilter->rec;
        
        $isFiltered =
        !empty($filterRec->search) ||
        !empty($filterRec->scopeFolderId) ||
        !empty($filterRec->docClass) ||
        !empty($filterRec->fromDate) ||
        !empty($filterRec->liked) ||
        !empty($filterRec->state) ||
        !empty($filterRec->fromDate) ||
        !empty($filterRec->toDate) ||
        $filterRec->author != 'all_users';
        
        // Флаг, указващ дали се филтрира
        $mvc->isFiltered = $isFiltered;
        
        // Ако формата е субмитната
        if($isFiltered && ($filterRec->fromDate || $filterRec->toDate)) {
            
            // Ако са попълнени полетата От и До
            if ($filterRec->fromDate && $filterRec->toDate) {
                
                // Ако До е след От
                if ($filterRec->toDate < $filterRec->fromDate) {
                    
                    // Имената на полетата
                    $fromDateCaption = $data->listFilter->getField('fromDate')->caption;
                    $toDateCaption = $data->listFilter->getField('toDate')->caption;
                    
                    // Сетваме грешката
                    $data->listFilter->setError('toDate', "Края на периода за търсене не може да е преди началото му");
                }    
            }
            
            // Днешната дата
            $now = dt::now(FALSE);
            
            // Ако се търси в бъдеще
            if ($filterRec->fromDate && $filterRec->fromDate > $now) {
                
                // Сетваме грешката
                $data->listFilter->setError('fromDate', "Не може да се търси в бъдеще");    
            }
        }
        
        // Има зададен условия за търсене - генерираме SQL заявка.
        if($isFiltered && !$data->listFilter->gotErrors()) {
            
            // Ако някой ще направи обработки преди вземането на резултата
            $mvc->invoke('BeforePrepareSearhQuery', array($data, $filterRec));
            
            // Търсене на определен тип документи
            $SearchDocument = NULL;
            if (!empty($filterRec->docClass)) {
                $data->query->where(array('#docClass = [#1#]', $filterRec->docClass));
                
                if(cls::load($filterRec->docClass)){
                	
                	// Ако търсения документ е счетоводен
                	$Doc = cls::get($filterRec->docClass);
                	if(cls::haveInterface('acc_TransactionSourceIntf', $Doc)){
                		
                		// И има поле за вальор
                		if($Doc->getField($Doc->valiorFld, FALSE)){
                			
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
            	$where =  "(#createdOn >= '[#1#]' AND #createdOn <= '[#2#] 23:59:59') OR (#modifiedOn >= '[#1#]' AND #modifiedOn <= '[#2#] 23:59:59')";
            	
            	// Ако търсим по документ с вальор, добавяме вальора в търсенето по дата
            	if($SearchDocument instanceof core_Mvc){
            		$where .= " OR (#{$SearchDocument->valiorFld} >= '[#1#]' AND #{$SearchDocument->valiorFld} <= '[#2#] 23:59:59')";
            	}
            	
                $data->query->where(array($where, $filterRec->fromDate, $filterRec->toDate));
            }
            
            // Търсене по дата на създаване на документи (от-до)
            if (!empty($filterRec->fromDate)) {
            	$where = "NOT (#createdOn < '[#1#]') AND NOT(#modifiedOn < '[#1#]')";
            	
            	// Ако търсим по документ с вальор, добавяме вальора в търсенето по дата
            	if($SearchDocument instanceof core_Mvc){
            		$where = "({$where}) OR NOT(#{$SearchDocument->valiorFld} < '[#1#]')";
            	}
            	
               $data->query->where(array($where, $filterRec->fromDate));
            }
            
            if (!empty($filterRec->toDate)) {
            	$where = "NOT (#createdOn > '[#1#] 23:59:59') AND NOT (#modifiedOn > '[#1#] 23:59:59')";
            	
            	// Ако търсим по документ с вальор, добавяме вальора в търсенето по дата
            	if($SearchDocument instanceof core_Mvc){
            		$where = "({$where}) OR NOT (#{$SearchDocument->valiorFld} > '[#1#] 23:59:59')";
            	}
            	
                $data->query->where(array($where, $filterRec->toDate));
            }
            
            // Ограничаване на търсенето до избрана папка
            if (!empty($filterRec->scopeFolderId) && doc_Folders::haveRightFor('single', $filterRec->scopeFolderId)) {
                $data->query->where(array("#folderId = '[#1#]'", $filterRec->scopeFolderId));
                $restrictAccess = FALSE;
            } else {
                $restrictAccess = TRUE;
            }
            
            // Ако е избран автор или не са избрани всичките
            if (!empty($filterRec->author) && $filterRec->author != 'all_users' && (strpos($filterRec->author, '|-1|') === FALSE)) {
                
                // Масив с всички избрани автори
                $authorArr = keylist::toArray($filterRec->author);
                
                $firstTime = TRUE;
                // Обхождаме масива
                foreach ($authorArr as $author) {
                    
                    if ($firstTime) {
                        // Добавяме в запитването
                        $data->query->where("#createdBy = '{$author}'");      
                    } else {
                        $data->query->orWhere("#createdBy = '{$author}'");      
                    }
                    
                    $firstTime = FALSE;
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
            
            if ($filterRec->liked && $filterRec->liked != 'no_matter') {
                
                // Всички харесвания
                $data->query->EXT('likedCid', 'doc_Likes', 'externalName=containerId');
                $data->query->where("#likedCid = #id");
                
                // Харесвания от текущия потребител
                if ($filterRec->liked == 'me') {
                    if ($currUserId > 0) {
                        $data->query->EXT('likedBy', 'doc_Likes', 'externalName=createdBy');
                        $data->query->where("#likedBy = {$currUserId}");
                    }
                }
            }
            
            if($restrictAccess) {
                // Ограничаване на заявката само до достъпните нишки
                doc_Threads::restrictAccess($data->query, $currUserId);
            }
            
            // Създател
            $data->query->orWhere("#createdBy = '{$currUserId}'");
            
            // Експеримент за оптимизиране на бързодействието
            $data->query->orderBy('#modifiedOn=DESC');
            
            /**
             * Останалата част от заявката - търсенето по ключови думи - ще я допълни plg_Search
             */
            
            // Ако ще се филтира по състояни и текущия потребител (автор)
            if ($filterRec->state) {
                
                $url = array($mvc, 'state' => $filterRec->state);
                
                $url2 = array($mvc);
                if($filterRec->docClass){
                	$url2['docClass'] = $filterRec->docClass;
                }
                $url2['state'] = $filterRec->state;
                
                if ($filterRec->author){
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
            $data->query->where("0 = 1");
        }

        $data->query->useCacheForPager = TRUE;
    }
    
    
    /**
     * Ако се търси манипулатор на файл, да се редиректне към сингъла му
     * 
     * @param plg_Search $mvc
     * @param object $data
     * @param object $filtreRec
     */
    function on_BeforePrepareSearhQuery($mvc, $data, $filtreRec)
    {
        // Тримваме търсенето
        $search = trim($filtreRec->search);
        
        // Ако няма търсене
        if (!$search) return;
        
        // Ако не е начало на манипулатор на документ
        if ($search{0} != '#') return ;
        
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
    function on_AfterPrepareListRecs($mvc, $data)
    {
        if (count($data->recs) == 0) {
            return;
        }
		
        foreach ($data->recs as $id => &$rec) {
        	if(cls::load($rec->docClass, TRUE)){
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
    function on_AfterPrepareListRows($mvc, $data)
    {
        if (count($data->recs) == 0) {
            return;
        }
        
        foreach ($data->recs as $i=>&$rec) {
            $row = $data->rows[$i];
            // $folderRec = doc_Folders::fetch($rec->folderId);
            // $folderRow = doc_Folders::recToVerbal($folderRec);
            // $row->folderId = $folderRow->title;
            
            try {
                $doc = doc_Containers::getDocument($rec->id);
                $row->docLink = $doc->getLink(64, array('Q' => $data->listFilter->rec->search));
                
            } catch (core_exception_Expect $exp) {
                $row->docLink = $row->title = "<b style='color:red;'>" . tr('Грешка') . "</b>";
            }
        }
    }
    
    
    /**
     * Преди рендиране на лист таблицата
     */
    function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        if (!$mvc->isFiltered) {
            
            return FALSE;
        }
    }
    
    /**
     * След подготовка на заглавието
     */
    static function on_AfterPrepareListTitle($mvc, $data)
    {
        $data->title = null;
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
        try {
            $docProxy = doc_Containers::getDocument($rec->id);
            $docRow = $docProxy->getDocumentRow();
        } catch (core_Exception_Expect $expect) {
    
            return;
        }
        
        $attr = array();
        $attr['ef_icon'] = $docProxy->getIcon();
        
        $handle = $rec->handle ? substr($rec->handle, 0, strlen($rec->handle)-3) : $docProxy->getHandle();
        
        if(mb_strlen($docRow->title) > doc_Threads::maxLenTitle) {
            $attr['title'] = $docRow->title;
        }
        $linkUrl = array($docProxy->className, 'single', $docProxy->that);
        
        $search = Request::get('search');
        if (trim($search)) {
            $linkUrl['Q'] = $search;
        }
        
        $row->title = ht::createLink(str::limitLen($docRow->title, doc_Threads::maxLenTitle),
            $linkUrl,
            NULL, $attr);
    
        if($docRow->authorId>0) {
            $row->author = crm_Profiles::createLink($docRow->authorId);
        } else {
            $row->author = $docRow->author;
        }
    
        $row->hnd = "<div onmouseup='selectInnerText(this);' class=\"state-{$docRow->state} document-handler\">#{$handle}</div>";
    }
    
    
    /**
     * Обновява ключовите думи на контейнери
     * 
     * @param boolean $bEmptyOnly TRUE - само контейнерите, на които им липсват ключови думи
     * @return int брой на контейнерите с реално обновени ключови думи
     */
    static function updateSearchKeywords($bEmptyOnly = FALSE)
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
    static function on_AfterSetupMVC($mvc, &$res)
    {
        if (Request::get('updateKeywords')) {
            if ($n = $mvc::updateSearchKeywords()) {
                $res .= "<li style=\"color: green;\">Обновени ключовите думи на <b>{$n}</b> контейнер(а)</li>";
            }
        }
    }
}