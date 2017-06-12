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
    var $loadList = 'plg_Created, plg_Modified,plg_RowTools,doc_Wrapper,plg_State, doc_ThreadRefreshPlg';
    
    
    /**
     * 10 секунди време за опресняване на нишката
     */
    var $refreshRowsTime = 10000;


    /**
     * Заглавие
     */
    var $title = "Документи в нишките";
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Документ в нишка";
    
    
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
    var $listItemsPerPage = 1000;
    
    
    /**
     * @todo Чака за документация...
     */
    var $canList = 'powerUser';
    
    
    /**
     * @todo Чака за документация...
     */
    var $canAdd  = 'no_one';
    
    
    /**
     * Масив с всички абревиатури и съответните им класове
     */
    static $abbrArr = NULL;
    
    
    /**
     * Име на променливата, където ще се запазват документите които да/не се показват
     */
    static $modShowName = 'showHiddenDocumentsArr';
    
    
    /**
     * Кой може да добавя документ
     * @see doc_RichTextPlg
     */
    var $canAdddoc = 'user';
    

    /**
     * Флаг, че заявките, които са към този модел лимитирани до 1 запис, ще са HIGH_PRIORITY
     */
    public $highPriority = TRUE;


    /**
     * 
     */
    const REPAIR_SYSTEM_ID = 'repairDocuments';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Мастери - нишка и папка
        $this->FLD('folderId' , 'key(mvc=doc_Folders)', 'caption=Папки');
        $this->FLD('threadId' , 'key(mvc=doc_Threads)', 'caption=Нишка');
        $this->FLD('originId' , 'key(mvc=doc_Containers)', 'caption=Основание');
        
        // Документ
        $this->FLD('docClass' , 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', 'caption=Документ->Клас');
        $this->FLD('docId' , 'int', 'caption=Документ->Обект');
        $this->FLD('searchKeywords', 'text(collate=ascii_bin)', 'notNull,column=none,input=none');
        
        // Кой е активирал документа?
        $this->FLD('activatedBy', 'key(mvc=core_Users)', 'caption=Активирано от, input=none');
        
        // Дали документа е видим за партньори
        $this->FLD('visibleForPartners', 'enum(no=Не, yes=Да)', 'caption=Видим за партньори');
        
        // Индекси за бързодействие
        $this->setDbIndex('folderId');
        $this->setDbIndex('threadId');
        $this->setDbIndex('state');
        $this->setDbIndex('createdBy');
        $this->setDbIndex('createdOn');
        $this->setDbIndex('modifiedOn');
        $this->setDbUnique('docClass, docId');
        $this->setDbIndex('searchKeywords', NULL, 'FULLTEXT');
    }
    
    
    /**
     * Добавя info запис в log_Data
     * 
     * @param string $action
     * @param integer $objectId
     * @param integer $lifeDays
     * 
     * @see core_Mvc::logRead($action, $objectId, $lifeDays)
     */
    public static function logRead($action, $objectId = NULL, $lifeDays = 180)
    {
        if (self::logToDocument('read', $action, $objectId, $lifeDays)) {
            
            return ;
        }
        
        return parent::logRead($action, $objectId, $lifeDays);
    }
    
    
    /**
     * Добавя info запис в log_Data
     * 
     * @param string $action
     * @param integer $objectId
     * @param integer $lifeDays
     * 
     * @see core_Mvc::logWrite($action, $objectId, $lifeDays)
     */
    public static function logWrite($action, $objectId = NULL, $lifeDays = 360)
    {
        if (self::logToDocument('write', $action, $objectId, $lifeDays)) {
            
            return ;
        }
        
        return parent::logWrite($action, $objectId, $lifeDays);
    }
    
    
    /**
     * 
     * 
     * @param string $type
     * @param string $action
     * @param integer|NULL $objectId
     * @param integer|NULL $lifeDays
     */
    protected static function logToDocument($type, $action, $objectId, $lifeDays)
    {
        if (!$objectId) return ;
        
        $allowedType = array('read', 'write');
        
        if (!in_array($type, $allowedType)) {
            
            return ;
        }
        
        try {
            $type = strtolower($type);
            $type = ucfirst($type);
            
            $fncName = 'log' . $type;
            
            $doc = doc_Containers::getDocument($objectId);
            
            $doc->getInstance()->{$fncName}($action, $doc->that, $lifeDays);
            
            return TRUE;
        } catch (core_exception_Expect $e) {
            
            reportException($e);
        }
    }
    
    
    /**
     * Връща линк към подадения обект
     * 
     * @param integer $objId
     * 
     * @return core_ET
     */
    public static function getLinkForObject($objId)
    {
        if (!$objId) {
            
            return ht::createLink(get_called_class(), array());
        }
        
        try {
            $doc = self::getDocument($objId);
            
            return $doc->getLinkForObject();
        } catch (core_exception_Expect $e) {
            
            return parent::getLinkForObject($objId);
        }
    }
    
    
    /**
     * 
     * 
     * @param integer $id
     * @param boolean $escape
     */
    public static function getTitleForId_($id, $escaped = TRUE)
    {
        try {
            $doc = self::getDocument($id);
            
            return $doc->getTitleForId($escaped);
        } catch (core_exception_Expect $e) {
            
            return parent::getTitleForId_($id, $escaped);
        }
    }
    
    
    /**
     * Логва действието
     * 
     * @param string $msg
     * @param NULL|stdClass|integer $rec
     * @param string $type
     */
    function logInAct($msg, $rec = NULL, $type = 'write')
    {
        if (is_numeric($rec)) {
            $rec = $this->fetch($rec);
        }
        
        if (($type == 'read') && ($threadId = Request::get('threadId', 'int')) && ($msg == 'Листване')) {
            log_Data::add($type, 'Разглеждане на нишка', 'doc_Threads', $threadId);
        } else {
            parent::logInAct($msg, $rec, $type);
        }
    }
    
    
    /**
     * Регенерира ключовите думи, ако е необходимо
     * 
     * @param boolean $force
     * 
     * @return array
     */
    public static function regenerateSerchKeywords($force = FALSE)
    {
        $docContainers = cls::get('doc_Containers');
        $query = self::getQuery();
        $query->groupBy('docClass');
        
        $resArr = array();
        
        while ($rec = $query->fetch()) {
            
            if (!$rec->docClass) continue;
            
            if (!cls::load($rec->docClass, TRUE)) continue;
            
            $clsInst = cls::get($rec->docClass);
            
            if (!$clsInst) continue;
            
            $plugins = $clsInst->getPlugins();
            
            if (!isset($plugins['plg_Search'])) continue;
            
    		$clsQuery = $clsInst->getQuery();
    		$clsQuery->show('searchKeywords, containerId');
    		
    		$i = 0;
    		while ($cRec = $clsQuery->fetch()) {
    			try {
    			    // Ако новите ключови думи не отговарят на старите, записваме ги
    			    $generatedKeywords = $clsInst->getSearchKeywords($cRec);
    			    if (!$force && ($generatedKeywords == $cRec->searchKeywords)) continue;
    			    $cRec->searchKeywords = $generatedKeywords;
    				$clsInst->save_($cRec, 'searchKeywords');
    				
    				if (!$cRec->containerId) continue;
    				
    				// Записваме ключовите думи в контейнера
    				$contRec = doc_Containers::fetch($cRec->containerId, 'searchKeywords');
    				if (!$contRec) continue;
    				$contRec->searchKeywords = $generatedKeywords;
    				$docContainers->save_($contRec, 'searchKeywords');
    				$i++;
    			} catch(core_exception_Expect $e) {
    			    reportException($e);
        			continue;
        		}
    		}
    		
    		if ($i) {
    		    $resArr[$rec->docClass] = $i;
    		    $resArr[0] += $i;
    		}
        }
        
        return $resArr;
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
        
        // Изчаква до 2 секунди, ако firstContainerId не е обновен
        for ($i = 0; $i < 10; $i++) {
            if (!$data->threadRec->firstContainerId) {
                usleep(200000);
                $data->threadRec = doc_Threads::fetch($data->threadId, '*', FALSE);
            } else {
                
                break;
            }
        }
        
        expect($data->threadRec->firstContainerId, 'Проблемен запис на нишка', $data->threadRec);
       
        bgerp_Recently::add('document', $data->threadRec->firstContainerId, NULL, ($data->threadRec->state == 'rejected') ? 'yes' : 'no');
        
        $data->query->orderBy('#createdOn, #id');
        
    	$threadId = Request::get('threadId', 'int');
        
        if($threadId) {
            $data->query->where("#threadId = {$threadId}");
        }
    }
    
    
    /**
     * Подготвя титлата за единичния изглед на една нишка от документи
     */
    static function on_AfterPrepareListTitle($mvc, &$res, $data)
    {
        $title = new ET("<div class='path-title'>[#user#] » [#folder#] ([#folderCover#]) » [#threadTitle#]</div>");
        
        // Папка и корица
        $folderRec = doc_Folders::fetch($data->folderId);
        $folderRow = doc_Folders::recToVerbal($folderRec);
        
        if ($folderRec->state == 'closed') {
            $folderRow->title = ht::createHint($folderRow->title, 'Документът се намира в затворена папка', 'warning');
        }
        
        $title->replace($folderRow->title, 'folder');
        $title->replace($folderRow->type, 'folderCover');
        // Потребител
        if($folderRec->inCharge) {
            $user = crm_Profiles::createLink($folderRec->inCharge);
        } else {
            $user = core_Setup::get('SYSTEM_NICK');
        }
        $title->replace($user, 'user');
        
        try {
            // Заглавие на треда
            $document = $mvc->getDocument($data->threadRec->firstContainerId);
            $docRow = $document->getDocumentRow();
            $docTitle = str::limitLenAndHyphen($docRow->title, 70);
            $title->replace($docTitle, 'threadTitle');
            
            $mvc->title = '|*' . str::limitLen($docRow->title, 20) . ' « ' . doc_Folders::getTitleById($folderRec->id) .'|';
            
            $data->title = $title;
        } catch (ErrorException $e) {
            $data->title = 'Грешка при показване';
        }
    }
    
    
    /**
     * Добавя div със стил за състоянието на треда
     */
    static function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        if(isDebug() && Request::get('_info')) {  
                    
            $tR = $data->threadRec;
        
            Debug::log("Thread: $tR->id, folderId={$tR->folderId}, firstContainerId={$tR->firstContainerId}");

            if(is_array($data->recs)) {
                foreach($data->recs as $rec) {
                    $dMvc = cls::get($rec->docClass);
                    $dRec = $dMvc->fetch($rec->docId);
                    Debug::log("Container: $rec->id, threadId = {$rec->threadId}, folderId = {$rec->folderId}, docClass={$rec->docClass}, docId={$rec->docId}, docContainerId = {$dRec->containerId}, docThreadId = {$dRec->threadId}, docFolderId = {$dRec->folderId}");
                }
            }
        }

        $state = $data->threadRec->state;
        $tpl = new ET("<div class='thread-{$state} single-thread'>[#1#]</div>", $tpl);
        
        // Изчистване на нотификации за отворени теми в тази папка
        $url = array('doc_Containers', 'list', 'threadId' => $data->threadRec->id);
        bgerp_Notifications::clear($url);
        
        jquery_Jquery::run($tpl, "flashHashDoc(flashDocInterpolation);", TRUE);

        jquery_Jquery::run($tpl, "setThreadElemWidth();");
        jquery_Jquery::runAfterAjax($tpl, "setThreadElemWidth");
        
        // Ако е избран някой документ, го отваряме временно - да не скрит
        if ($docId = Request::get('docId')) {
            $dRec = FALSE;
            try {
                $doc = self::getDocumentByHandle($docId);
                
                if ($doc && $doc->instance instanceof core_Mvc) {
                    $dRec = $doc->fetch();
                }
                
                if ($dRec && $dRec->state != 'rejected') {
                    doc_HiddenContainers::showOrHideDocument($dRec->containerId, FALSE, TRUE);
                }
            } catch (ErrorException $e) {
                // Нищо не се прави
            }
        }
    }
    
    
    /**
     * Подготвя някои вербални стойности за полетата на контейнера за документ
     * Използва методи на интерфейса doc_DocumentIntf, за да вземе тези стойности
     * директно от документа, който е в дадения контейнер
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = NULL)
    {
    	try {
            try {
                $document = $mvc->getDocument($rec->id);
                $docRow = $document->getDocumentRow();
            } catch ( core_Exception_Expect $expect) {
                // Ако имаме клас на документа, обаче липсва ключ към конкретен документ
                // Правим опит да го намерим по обратния начин - чрез $containerId в записа на документа
                if($rec->docClass && !$rec->docId && cls::load($rec->docClass, TRUE)) {
                    $docMvc = cls::get($rec->docClass);
                    if($rec->docId = $docMvc->fetchField("#containerId = {$rec->id}", 'id')) {
                        $mvc->save($rec);
                        $document = $mvc->getDocument($rec->id);
                        $docRow = $document->getDocumentRow();
                    }
                }
            }
        } catch (core_Exception_Expect $expect) {
            // Възникнала е друга грешка при прочитането на документа
            // Не се предвижда коригиращо действие
        }

        if($docRow) {
  
            $q = Request::get('Q');
            
            // Ако е задеден да не се скрива документа или ако се търси в него
            $hidden = (boolean) (doc_HiddenContainers::isHidden($rec->id));
            
            $row->ROW_ATTR['id'] = $document->getDocumentRowId();
       
            if (!$hidden) {
            	
            	$row->document = doc_DocumentCache::getCache($rec, $document);
 				$retUrl = array($document->className, 'single', $document->that);
 				$retUrl = $retUrl + self::extractDocParamsFromUrl();
 				Mode::push('ret_url', $retUrl);
 				
            	if($row->document) {
                    Debug::log("+++ Get from Cache $rec->id");
                } else {
                    Mode::push('saveObjectsToCid', $rec->id);
            		$data = $document->prepareDocument();
            		doc_UsedInDocs::addToChecked($rec->id);
            		Mode::pop('saveObjectsToCid');
                    $row->ROW_ATTR['onMouseUp'] = "saveSelectedTextToSession('" . $document->getHandle() . "', 'onlyHandle');";
                    
                    // Добавяме линк за скриване на документа
                    if (doc_HiddenContainers::isHidden($rec->id) === FALSE) {
                        $hideLink = self::getLinkForHideDocument($document, $rec->id);
                        $data->row->DocumentSettings = new ET($data->row->DocumentSettings);
                        $data->row->DocumentSettings->append($hideLink);
                    }
                    
                    $row->document = $document->renderDocument($data);

                    doc_DocumentCache::setCache($rec, $document, $row->document);
                    Debug::log("+++ Render $rec->id");
            	} 
                
                // Оцветяване на търсенето
                if($q) {
                    $row->document = plg_Search::highlight($row->document, $q);
                }
                
                Mode::pop('ret_url');
            } else {
                $row->document = self::renderHiddenDocument($rec->id);
            }
            
            $row->created = str::limitLen($docRow->author, 32);
        } else {
            if(isDebug()) {
                if(!$rec->docClass) {
                    $debug = 'Липсващ $docClass ';
                }
                if(!$rec->docId) {
                    $debug .= 'Липсващ $docId ';
                }
                if(!$document) {
                    $debug .= 'Липсващ $document ';
                }
            }

            $row->document = new ET("<h2 style='color:red'>[#1#]</h2><p>[#2#]</p>", tr('Грешка при показването на документа'), $debug);
        }
        
        $row->created = type_Nick::normalize($row->created);
        
        if ($rec->createdBy > 0) {
        	$row->created = crm_Profiles::createLink($rec->createdBy);
        }
        
        if (!$hidden) {

            if($docRow->authorId > 0 || ($docRow->authorEmail && !($rec->createdBy > 0))) {
                $avatar = avatar_Plugin::getImg($docRow->authorId, $docRow->authorEmail);
            } else {
                $avatar = avatar_Plugin::getImg($rec->createdBy, $docRow->authorEmail);
            }

            if(Mode::is('screenMode', 'narrow')) {
                $row->created = new ET("<div class='profile-summary'><div class='fleft'><div class='fleft'>[#2#]</div><div class='fleft'><span>[#3#]</span>[#1#]</div></div><div class='fleft'>[#HISTORY#]</div><div class='clearfix21'></div></div>",
                    $mvc->getVerbal($rec, 'createdOn'),
                    $avatar,
                    $row->created);
                    
                // визуализиране на обобщена информация от лога
            } else {
                $row->created = new ET("<table class='wide-profile-info'><tr><td><div class='name-box'>[#3#]</div>
                                                    <div class='date-box'>[#1#]</div></td></tr>
                                                    <tr><td class='gravatar-box'>[#2#]</td></tr><tr><td>[#HISTORY#]</td></tr></table>",
                    $mvc->getVerbal($rec, 'createdOn'),
                    $avatar,
                    $row->created);
                    
                // визуализиране на обобщена информация от лога
            }
            
            if (core_Users::isPowerUser()) {
                $row->created->append(doclog_Documents::getSummary($rec->id, $rec->threadId), 'HISTORY');
            }
        } else {
            
            if (Mode::is('screenMode', 'narrow')) {
                $nCreated = new ET("<div style='margin-bottom: 5px;'>
                                        <span class='fleft'>[#nameBox#]</span>
                                        <span class='fright'>[#dateBox#]</span>
                                        <span class='clearfix21'></span>
                                    </div>");
            } else {
                $nCreated = new ET("<table class='wide-profile-info'>
                                		<tr>
                                			<td><div class='name-box'>[#nameBox#]</div>
                                                <div class='date-box'>[#dateBox#]</div>
                                            </td>
                                        </tr>
                                	</table>");
            }
            
            $nCreated->replace($row->created, 'nameBox');
            $nCreated->replace($mvc->getVerbal($rec, 'createdOn'), 'dateBox');
            
            $row->created = $nCreated;
        }
        

        if (Mode::is('screenMode', 'narrow')) {
            $row->document = new ET($row->document); 
            $row->document->prepend($row->created);
        }
    }
    
    
    /**
     * При мобилен изглед оставяме само колонката "документ"
     */
    function on_BeforeRenderListTable($mvc, $tpl, $data)
    {   
        if(Mode::is('screenMode', 'narrow')) {
            $data->listFields = array('document' => 'Документ');
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	$folderState = doc_Folders::fetchField($data->threadRec->folderId, 'state');
    	if($data->threadRec->state != 'rejected' && $folderState != 'closed') {
        	
        	if(doc_Threads::haveRightFor('newdoc', $data->threadId)){
        		$data->toolbar->addBtn('Нов...', array($mvc, 'ShowDocMenu', 'threadId' => $data->threadId), 'id=btnAdd', array('ef_icon'=>'img/16/star_2.png','title'=>'Създаване на нов документ в нишката'));
        	}
            
        	if(doc_Threads::haveRightFor('single', $data->threadRec)){
        		
        		if($data->threadRec->state == 'opened') {
        			$data->toolbar->addBtn('Затваряне', array('doc_Threads', 'close', 'threadId' => $data->threadId), 'ef_icon = img/16/close.png', 'title=Затваряне на нишката');
        		} elseif($data->threadRec->state == 'closed' || empty($data->threadRec->state)) {
        			$data->toolbar->addBtn('Отваряне', array('doc_Threads', 'open', 'threadId' => $data->threadId), 'ef_icon = img/16/open.png', 'title=Отваряне на нишката');
        		}
        	}
            
        	if(doc_Threads::haveRightFor('move', $data->threadRec)){
        		$data->toolbar->addBtn('Преместване', array('doc_Threads', 'move', 'threadId' => $data->threadId, 'ret_url' => TRUE), 'ef_icon = img/16/move.png', 'title=Преместване на нишката в нова папка');
        	}
        	
        	if(doc_Threads::haveRightFor('single', $data->threadRec)){
        	    $data->toolbar->addBtn('Напомняне', array('cal_Reminders', 'add', 'threadId' => $data->threadId, 'ret_url' => TRUE), 'ef_icon=img/16/alarm_clock_add.png', 'title=Създаване на ново напомняне');
        	}
        }
        
        // Ако има права за настройка на папката, добавяме бутона
        $key = doc_Threads::getSettingsKey($data->threadId);
        $userOrRole = core_Users::getCurrent();
        if (doc_Threads::canModifySettings($key, $userOrRole)) {
            core_Settings::addBtn($data->toolbar, $key, 'doc_Threads', $userOrRole, 'Настройки', array('class' => 'fright', 'row' => 2,'title'=>'Персонални настройки на нишката'));
        }
    }
    
    
    /**
     * Създава нов контейнер за документ от посочения клас
     * Връща $id на новосъздадения контейнер
     */
    static function create($class, $threadId, $folderId, $createdOn, $createdBy)
    {
        $className = cls::getClassName($class);
        
        $rec = new stdClass();
        $rec->docClass  = core_Classes::getId($className);
        $rec->threadId  = $threadId;
        $rec->folderId  = $folderId;
        $rec->createdOn = $createdOn;
        $rec->createdBy = $createdBy;
        
        self::save($rec);
        
        return $rec->id;
    }
    
    
    /**
     * Обновява информацията в контейнера според информацията в документа
     * Ако в контейнера няма връзка към документ, а само мениджър на документи - създава я
     *
     * @param int $id key(mvc=doc_Containers)
     */
    static function update_($id, $updateAll=TRUE)
    {
        expect($rec = doc_Containers::fetch($id), $id);
        
        $docMvc = cls::get($rec->docClass);
        
        // В записа на контейнера попълваме ключа към документа
        if(!$rec->docId) {
            expect($rec->docId = $docMvc->fetchField("#containerId = {$id}", 'id'));
            $mustSave = TRUE;
        }
        
        $docRec = $docMvc->fetch($rec->docId);
        
        if ($rec->docClass && $rec->docId) {
            
            if ($docMvc->isVisibleForPartners($docRec)) {
                $rec->visibleForPartners = 'yes';
            } else {
                $rec->visibleForPartners = 'no';
            }
            
            $mustSave = TRUE;
        }

        // Обновяването е възможно при следните случаи
        // 1. Създаване на документа, след запис на документа
        // 2. Промяна на състоянието на документа (активиране, оттегляне, възстановяване)
        // 3. Промяна на папката на документа
        
        $fields = 'state,folderId,threadId,containerId,originId';
        
        if ($docRec->searchKeywords = $docMvc->getSearchKeywords($docRec)) {
            $fields .= ',searchKeywords';
        }
        
        $updateField = NULL;
        $fieldsArr = arr::make($fields);
        foreach($fieldsArr as $field) {
            
            if (!$updateAll && ($field != 'containerId')) {
                $updateField[$field] = $field;
            }
            
            if($rec->{$field} != $docRec->{$field}) {
                $rec->{$field} = $docRec->{$field};
                $mustSave = TRUE;
            }
        }
        
        // Дали документа се активира в момента, и кой го активира
        if(empty($rec->activatedBy) && $rec->state != 'draft' && $rec->state != 'rejected') {
            
            $rec->activatedBy = core_Users::getCurrent();
            
            if (!$updateAll) {
                $updateField['activatedBy'] = 'activatedBy';
            }
            
            $flagJustActived = TRUE;
            $mustSave = TRUE;
        }
        
        if($mustSave) {
            
            doc_Containers::save($rec, $updateField);

            // Ако този документ носи споделяния на нишката, добавяме ги в списъка с отношения
            if($rec->state != 'draft' && $rec->state != 'rejected') {
                $shared = $docMvc->getShared($rec->docId);
                doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, $shared);
                doc_ThreadUsers::addSubscribed($rec->threadId, $rec->containerId, $rec->createdBy);
            } elseif ($rec->state == 'rejected') {
                doc_ThreadUsers::removeContainer($rec->containerId);
            }
            
            if($rec->threadId && $rec->docId) {
                // Предизвиква обновяване на треда, след всяко обновяване на контейнера
                doc_Threads::updateThread($rec->threadId);
            }
            
            
            // Нотификации на абонираните и споделените потребители
            if($flagJustActived && !Mode::is('isMigrate')) {
                
                // Масис със споделените потребители
                $sharedArr = keylist::toArray($shared);
                
                // Нотифицираме споделените
                self::addNotifications($sharedArr, $docMvc, $rec, 'сподели', FALSE);
                
                // Всички абонирани потребилите
                $subscribedArr = doc_ThreadUsers::getSubscribed($rec->threadId);
                
                // Всички споделени потребители в цялата нишка
                $oldSharedArr = doc_ThreadUsers::getShared($rec->threadId);
                
                $subscribedArr += $oldSharedArr;
                
                $subscribedWithoutSharedArr = array_diff($subscribedArr, $sharedArr);
                
                // Нотифицираме абонираните потребители
                self::addNotifications($subscribedWithoutSharedArr, $docMvc, $rec, 'добави');
                
                
                // Нотифицира потребителите, които са свързани с документа
                // и са избрали съответната настройка за нотификация
                $usersArrForNotify = $docMvc->getUsersArrForNotifyInDoc($docRec);
                
                if ($usersArrForNotify) {
                    
                    // Кои потребители ще се нотифицират за отворена нишка
                    $fRec = doc_Folders::fetch($rec->folderId);
                    $notifyForOpenInFolder = doc_Folders::getUsersArrForNotify($fRec);
                    
                    // Премахваме всички потребители, които ще се нотифицират за отворена нишка
                    // и са абонирани в нишката и ще получат нотификация от там
                    $usersArrForNotify = array_diff($usersArrForNotify, $notifyForOpenInFolder);
                    $usersArrForNotify = array_diff($usersArrForNotify, $subscribedArr);
                    $usersArrForNotify = array_diff($usersArrForNotify, $sharedArr);
                    
                    if ($usersArrForNotify) {
                        
                        // Премахваме всички потребители, които не желаят да получават нотификаци
                        $key = doc_Folders::getSettingsKey($rec->folderId);
                        $usersArr = core_Settings::fetchUsers($key, 'personalEmailIncoming', 'no');
                        
                        if ($usersArr) {
                            $usersArr = array_keys($usersArr);
                            $usersArr = arr::make($usersArr, TRUE);
                            
                            $usersArrForNotify = array_diff($usersArrForNotify, $usersArr);
                        }
                        
                        if ($usersArrForNotify) {
                            self::addNotifications($usersArrForNotify, $docMvc, $rec, 'добави');
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * Добавя/премахва нотификации
     * 
     * @param object $dRec
     * @param string $oldSharedUsers
     * @param string $newSharedUsers
     */
    public static function changeNotifications($dRec, $oldSharedUsers, $newSharedUsers)
    {
        if (($oldSharedUsers == $newSharedUsers)) return ;
        
        expect($rec = self::fetch($dRec->containerId), $dRec->containerId);
        
        $docMvc = cls::get($rec->docClass);
        
        // Масис със споделените потребители
        $sharedKeylist = type_Keylist::diff($newSharedUsers, $oldSharedUsers);
        
        $sharedArr = type_Keylist::toArray($sharedKeylist);
        
        $keyUrl = array('doc_Containers', 'list', 'threadId' => $dRec->threadId);
        
        // Премахваме контейнера от достъпните
        doc_ThreadUsers::removeContainer($rec->id);
        
        if ($sharedArr) {
            
            // Нотифицираме споделените
            self::addNotifications($sharedArr, $docMvc, $rec, 'сподели', FALSE, $dRec->priority);
            
            foreach ($sharedArr as $userId) {
                
                // Добавяме документа в "Последно" за новия потребител
                bgerp_Recently::setHidden('document', $rec->id, 'no', $userId);
            }
        }
        
        $removedUsers = type_Keylist::diff($oldSharedUsers, $newSharedUsers);
        
        $removedUsersArr = type_Keylist::toArray($removedUsers);
        
        if ($removedUsersArr) {
            
            foreach ($removedUsersArr as $userId) {
                
                // Добавяме документа в нотификациите за новия потреибител
                bgerp_Notifications::setHidden($keyUrl, 'yes', $userId);
                
                // Добавяме документа в "Последно" за новия потребител
                bgerp_Recently::setHidden('document', $rec->id, 'yes', $userId);
            }
        }
    }
    
    
    /**
     * 
     * @param array $usersArr
     * @param string $settingsKey
     * @param string $property
     * @param integer|NULL $threadId
     */
    protected static function prepareUsersArrForNotifications(&$usersArr, $settingsKey, $property, $threadId = NULL)
    {
        $settingsNotifyArr = core_Settings::fetchUsers($settingsKey, $property);
        
        // В зависимост от настройкие добавяме или премахваме от списъка за нотифициране
        foreach ((array)$settingsNotifyArr as $userSettingsId => $sArr) {
            if ($sArr[$property] == 'no') {
                unset($usersArr[$userSettingsId]);
            } else if ($sArr[$property] == 'yes') {
                // Ако има права за сингъла на нишката тогава може да се нотифицира
                if ($threadId && doc_Threads::haveRightFor('single', $threadId, $userSettingsId)) {
                    $usersArr[$userSettingsId] = $userSettingsId;
                }
            }
        }
    }
    
    
    /**
     * Добавя нотификация за съответното действие на потребителите
     * 
     * @param array $usersArr - Масив с потребителите, които да се нотифицират
     * @param core_Mvc $docMvc - Класа на документа
     * @param object $rec - Запис за контейнера
     * @param string $action - Действието
     * @param boolean $checkThreadRight - Дали да се провери за достъп до нишката
     * @param string $priority - Приоритет на нотификацията
     */
    static function addNotifications($usersArr, $docMvc, $rec, $action='добави', $checkThreadRight=TRUE, $priority='normal')
    {
        // Не правим нотификации, ако в документа е посочена ролята на текущия потребител
        if(isset($docMvc->muteNotificationsBy) && haveRole($docMvc->muteNotificationsBy)) {

            return;
        }
        
        // Ако няма да се споделя, а ще се добавя или променя
        if ($action != 'сподели') {
            
            // Определяме дали е начало на нишка
            $isFirst = FALSE;
            if ($rec->threadId) {
                $tRec = doc_Threads::fetch($rec->threadId);
                if (!$tRec->firstContainerId || ($tRec->firstContainerId == $rec->id)) {
                    $isFirst = TRUE;
                }
            }
            
            $oUsersArr = $usersArr;
            
            // Ако глобално в настройките е зададено да се нотифицира или не
            $docSettings = doc_Setup::get('NOTIFY_FOR_NEW_DOC');
            if ($docSettings == 'no') {
                $usersArr = array();
            } elseif ($docSettings == 'yes') {
                $usersArr = core_Users::getByRole('powerUser');
            }
            
            $pSettingsKey = crm_Profiles::getSettingsKey();
            
            // Ако е зададено в персоналните настройки на потребителя за всички папки
            self::prepareUsersArrForNotifications($usersArr, $pSettingsKey, 'DOC_NOTIFY_FOR_NEW_DOC', $rec->threadId);
            
            // Ако е избран вид документ за който да се спре или дава нотификация
            $pSettingsNotifyArr = core_Settings::fetchUsers($pSettingsKey);
            $globalNotifyStr = doc_Setup::get('NOTIFY_NEW_DOC_TYPE');
            $globalNotifyStrStop = doc_Setup::get('STOP_NOTIFY_NEW_DOC_TYPE');
            
            $clsId = $docMvc->getClassId();
            
            foreach ((array)$oUsersArr as $oUserId) {
                
                if ($oUserId < 1) continue;
                
                // Ако ще се нотифицира за съответния документ
                $settings =  $pSettingsNotifyArr[$oUserId]['DOC_NOTIFY_NEW_DOC_TYPE'];
                if (!isset($settings)) {
                    $settings = $globalNotifyStr;
                }
                $settingsArr = type_Keylist::toArray($settings);
                if (isset($settingsArr[$clsId])) {
                    $usersArr[$oUserId] = $oUserId;
                }
                
                // Ако няма да се нотифицира за съответния документ, премахваме потребителя
                $settingsStop =  $pSettingsNotifyArr[$oUserId]['DOC_STOP_NOTIFY_NEW_DOC_TYPE'];
                if (!isset($settingsStop)) {
                    $settingsStop = $globalNotifyStrStop;
                }
                $settingsStopArr = type_Keylist::toArray($settingsStop);
                if (isset($settingsStopArr[$clsId])) {
                    unset($usersArr[$oUserId]);
                }
            }
            
            // Ако е зададено в настройките на папката
            self::prepareUsersArrForNotifications($usersArr, doc_Folders::getSettingsKey($rec->folderId), 'newDoc', $rec->threadId);
            
            if ($isFirst) {
                // Ако е зададено в настройките на папката да се известява за нова тема
                self::prepareUsersArrForNotifications($usersArr, doc_Folders::getSettingsKey($rec->folderId), 'newThread', $rec->threadId);
            }
            
            // Ако е зададено в настройките на нишката
            self::prepareUsersArrForNotifications($usersArr, doc_Threads::getSettingsKey($rec->threadId), 'notify', $rec->threadId);
        }
        
        // Ако няма потребители за нотифирциране
        if (!$usersArr) return ;
        
        static $threadTitleArr = array();
        
        // Броя на потребителите, които ще се показват в съобщението на нотификацията
        $maxUsersToShow = 2;
        
        // Масив с нотифицираниете потребители
        // За предпазване от двойно нотифициране
        static $notifiedUsersArr = array();
        
        // Преобразуваме в масив, ако не е
        $usersArr = arr::make($usersArr);
        
        // Ник на текущия потребител
        $currUserNick = core_Users::getCurrent('nick');
        
        // Подготвяме ника
        $currUserNick = type_Nick::normalize($currUserNick);
        
        // id на текущия потребител
        $currUserId = core_Users::getCurrent();
        
        // Ако заглавието на нишката не е определяна преди
        if (!$threadTitleArr[$rec->threadId]) {
            
            // Определяме заглавието и добавяме в масива
            $threadTitleArr[$rec->threadId] = str::limitLen(doc_Threads::getThreadTitle($rec->threadId, FALSE), doc_Threads::maxLenTitle);
        }
        
        // Кой линк да се използва за изичстване на нотификация
        $url = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
        
        // Къде да сочи линка при натискане на нотификацията
        $customUrl = array($docMvc, 'single', $rec->docId);
        
        // Обхождаме масива с всички потребители, които ще имат съответната нотификация
        foreach((array)$usersArr as $userId) {
            
            // Ако текущия потребител, е някой от системните, няма да се нотифицира
            if ($userId < 1) continue; 
            
            // Ако текущия потребител няма debug роля, да не получава нотификация за своите действия
            if ($currUserId == $userId) continue;
            
            // Ако потребителя, вече е бил нотифициран
            if ($notifiedUsersArr[$userId]) continue;
            
            // Ако е зададено да се проверява и няма права до сингъла на нишката, да не се нотифицира
            if ($checkThreadRight && !doc_Threads::haveRightFor('single', $rec->threadId, $userId)) continue;
            
            // Вземаме всички, които са писали в нишката след последното виждане
            $authorArr = static::getLastAuthors($url, $userId, $rec->threadId);
            
            // Ника на текущия потребител
            $currUserNickMsg = $currUserNick;
            
            // Сингъл типа на документиа
            $docTitle = $docMvc->singleTitle;
            
            // Името да е в долния регистър
            $docTitle = mb_strtolower($docTitle);
            
            // Генерираме съобщението
            $message = "{$currUserNickMsg} |{$action}|* |{$docTitle}|*";
            
            // Други добавки от съответния потребител
            $currUserOther = '';
            
            // Ако текущия потребител е добавил повече от един документ
            if ($authorArr[$currUserId] > 1) {
                
                // В зависимост от текста определяме началния текст
                if ($action != 'добави') {
                    $currUserOther = 'и добави';
                } else {
                    $currUserOther = 'и';
                }
                
                // В зависимост от броя документи, определяме текста
                if ($authorArr[$currUserId] == 2) {
                    $currUserOther .= " друг документ";
                } elseif ($authorArr[$currUserId] > 2) {
                    $currUserOther .= " други документи";
                }
                
                // Добавяме текста към съобщението
                $message .= " |{$currUserOther}|*";
            }
            
            // Добавяме останалата част от съобщението
            $threadTitle = $threadTitleArr[$rec->threadId];
            
            if (!trim($threadTitle)) {
                $threadTitle = '[' . tr('Липсва заглавие') . ']';
            }
            
            $message .= " |в|* \"{$threadTitle}\"";
            
            // Никове, на другите потребители, които са добавили нещо
            $otherNick = '';
            
            // Да няма допълнителна нотификация за добавени документи от
            // текущия потребител и потребителя, който ще се нотифицира
            unset($authorArr[$currUserId]);
            unset($authorArr[$userId]);
            
            // Флаг, който указва, че има добавени повече от един документ за някой потребител
            $haveMore = FALSE;
            
            // Нулираме брояча
            $usersCount = 0;
            
            // Обхождаме всички останали потребители в масива
            foreach ((array)$authorArr as $author => $count) {
                
                // Увеличаваме брояча
                $usersCount++;
                
                // Ако сме достигнали максималния лимит, прекъсваме
                if ($usersCount > $maxUsersToShow) break;
                
                // Вземаме ника на автора
                $uNick = static::getUserNick($author);
                
                // Ако е добавил повече от един документ, от последтово виждане
                if ($count > 1) {
                    
                    // Вдигаме флага
                    $haveMore = TRUE;
                }
                
                // Добавяме към другите никове
                $otherNick .= $uNick . ', ';
            }
            
            // Ако има други потребители, които са добавили нещо преди последното виждане
            if ($otherNick) {
                
                // Премахваме от края
                $otherNick = rtrim($otherNick, ', ');
                
                // Ограничаваме дължината
                $otherNick = str::limitLen($otherNick, 50);
                
                // Броя на авторите, които са добавили нещо
                $cntAuthorArr = count($authorArr);
                
                // Ако има други, които са добавили документи
                if ($cntAuthorArr > $maxUsersToShow) {
                    
                    // Добавяме съобщението
                    $otherNick .= ' |и други|*';
                }
                
                // В зависимост от броя на документите и авторите, определяме стринга
                if ($cntAuthorArr > 1) {
                    $msgText = 'също добавиха документи';
                } elseif ($haveMore) {
                    $msgText = 'също добави документи';
                } else {
                    $msgText = 'също добави документ';
                }
                
                // Събираме стринга
                $messageN = $message . '. ' . $otherNick . " |{$msgText}";
            } else {
                
                $messageN = $message;
            }

            // Нотифицираме потребителя
            bgerp_Notifications::add($messageN, $url, $userId, $priority, $customUrl);
            
            // Добавяме в масива, за да не се нотифицара повече
            $notifiedUsersArr[$userId] = $userId;
        }
    }
    
    
    /**
     * Връща ника за съответния потребител
     * 
     * @param integer $userId - id на потребител
     * 
     * @return string
     */
    static function getUserNick($userId)
    {
        // Вземаме ника на потребителя
        $nick = core_Users::getNick($userId);
        
        // Обработваме ника
        $nick = type_Nick::normalize($nick);
        
        return $nick;
    }
    
    
    /**
     * Връща масив с всички потребители( и броя на документите),
     * които са писали след последното виждане
     * от съответния потребител
     * 
     * @param array $url
     * @param integer $userId
     * @param integer $threadId
     * 
     * @return array
     */
    static function getLastAuthors($url, $userId, $threadId)
    {
        // Време на последното виждане, за съответния потребител
        $lastClosedOn = bgerp_Notifications::getLastClosedTime($url, $userId);
        
        // Ако няма време на последно затваряне
        if (!$lastClosedOn) {
            
            // Вадим от текущото време, зададените секунди за търсене преди
            $lastClosedOn = dt::subtractSecs(bgerp_Notifications::NOTIFICATIONS_LAST_CLOSED_BEFORE);
        }
        
        // Вземаме всички записи
        // Които не са чернови или оттеглени
        // И са променени след последното разглеждане
        $query = static::getQuery();
        $query->where(array("#modifiedOn > '[#1#]'", $lastClosedOn));
        $query->where(array("#threadId = '[#1#]'", $threadId));
        $query->where("#state != 'draft'");
        $query->where("#state != 'rejected'");
        $query->orderBy('modifiedOn', 'DESC');
        
        // Масив с потребителите
        $authorArr = array();
        
        while($rec = $query->fetch()) {
            
            // Увеличаваме броя на документите за съответния потребител, който е активирал документа
            $authorArr[$rec->activatedBy]++;
        }
        
        return $authorArr;
    }
    
    
    /**
     * Проверява дали има документ в нишката след подадената дата от съответния клас
     * 
     * @param integer $threadId
     * @param date $date
     * @param integer $classId
     */
    static function haveDocsAfter($threadId, $date=NULL, $classId=NULL)
    {
        // Ако не е подадена дата, да се използва текущото време
        if (!$date) {
            $date = dt::now();
        }
        
        // Първия документ, в нишката, който не е оттеглен
        $query = static::getQuery();
        $query->where(array("#threadId = '[#1#]'", $threadId));
        $query->where("#state != 'rejected'");
        
        // Създадене след съответната дата
        $query->where(array("#createdOn > '[#1#]'", $date));
        
        // Ако е зададен от кой клас да е документа
        if ($classId) {
            $query->where(array("#docClass = '[#1#]'", $classId));
        }
        
        $rec = $query->fetch();
        
        return $rec;
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
            if (!$rec->docId) sleep((int) BGERP_DOCUMENT_SLEEP_TIME);
            $rec = doc_Containers::fetch($id, 'docId, docClass');
            
            if (!$rec->docId) sleep((int) BGERP_DOCUMENT_SLEEP_TIME);
            $rec = doc_Containers::fetch($id, 'docId, docClass');
        } else {
            $rec = $id;
        }
        
        expect($rec->docClass);
        expect($rec->docId);
        
        return new core_ObjectReference($rec->docClass, $rec->docId, $intf);
    }
    
    
    /**
     * Документ по зададен хендъл
     * 
     * @param string $handle Inv478, Eml57 и т.н.
     * @param string $intf интерфейс
     * @return core_ObjectReference|FALSE
     */
    static function getDocumentByHandle($handle, $intf = NULL)
    {
        if (!is_array($handle)) {
            $handle = self::parseHandle($handle);
        }
        
        if (!$handle) {
            // Невалиден хендъл
            return FALSE;
        }
        
        //Проверяваме дали сме открили клас. Ако не - връщаме FALSE
        if (!$mvc = self::getClassByAbbr($handle['abbr'])) {
            return FALSE;
        }
        
        //Ако нямаме запис за съответното $id връщаме FALSE
        if (!$docRec = $mvc::fetchByHandle($handle)) {
            return FALSE;
        }
        
        return static::getDocument((object)array('docClass' => $mvc, 'docId' => $docRec->id), $intf);
    }
    
    
    /**
     * 
     * 
     * @param string $handle
     * 
     * @return array|FALSE
     */
    public static function parseHandle($handle)
    {
        $handle = trim($handle);
        
        if (!preg_match(doc_RichTextPlg::$identPattern, $handle, $matches)) {
            return FALSE;
        }
        
        return $matches;
    }
    
    
    /**
     * Намира контейнер на документ по негов манипулатор.
     *
     * @param string $handle манипулатор на документ
     * @return int key(mvc=doc_Containers) NULL ако няма съответен на манипулатора контейнер
     * 
     * @deprecated
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
     * Връща заглавието на документ
     */
    static function getDocTitle($id) 
    {
        $doc = static::getDocument($id, 'doc_DocumentIntf');
        
        try {
            $docRow = $doc->getDocumentRow();
            $title = $docRow->title;
        }  catch (core_Exception_Expect $expect) {
            $title = '?????????????????????????????????????';
        }
        
        return $title;
    }
    
    
    /**
     * Екшън за активиране на постинги
     */
    function act_Activate()
    {
        $containerId = Request::get('containerId', 'int');
        
        //Очакваме да име
        expect($containerId);
        
        //Документна
        $document = doc_Containers::getDocument($containerId);
        $class = $document->className;
        
        // Инстанция на класа
        $clsInst = cls::get($class);
        
        // Очакваме да има такъв запис
        expect($rec = $class::fetch("#containerId='{$containerId}'"));
        
        // Очакваме потребителя да има права за активиране
        $clsInst->requireRightFor('activate', $rec);
        
        //Променяме състоянието
        $recAct = new stdClass();
        $recAct->id = $rec->id;
        $recAct->state = 'active';
        
        // Извикваме фунцкията
        if($clsInst->invoke('BeforeActivation', array(&$recAct))){
        	        	
        	//Записваме данните в БД
        	$clsInst->save($recAct);
        	
        	$document->instance->logWrite('Активиране', $document->that);
        	
        	$rec->state = 'active';
        	$clsInst->invoke('AfterActivation', array(&$rec));
        }
        
        // Редиректваме към сингъла на съответния клас, от къде се прехвърляме 		//към треда
        return new Redirect(array($class, 'single', $rec->id));
    }
    
    
    /**
     * Показва меню от възможности за добавяне на нови документи,
     * достъпни за дадената нишка. Очаква threadId
     */
    function act_ShowDocMenu()
    {
        expect($threadId = Request::get('threadId', 'int'));
        
        doc_Threads::requireRightFor('newdoc', $threadId);
        
        $rec = (object) array('threadId' => $threadId);
        
        $tpl = doc_Containers::getNewDocMenu($rec);
       	
        return $this->renderWrapping($tpl);
    }



    /**
     * Връща акордеаон-меню за добавяне на нови документи
     * Очаква или $rec->threadId или $rec->folderId
     */
    static function getNewDocMenu($rec)
    {
        // Определяме заглавието на нишката или папката
        if ($rec->threadId) {
            $thRec = doc_Threads::fetch($rec->threadId);
            $title = doc_Threads::recToVerbal($thRec)->onlyTitle;
        } else {
            $title = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
        }

        // Извличане на потенциалните класове на нови документи
        $docArr = core_Classes::getOptionsByInterface('doc_DocumentIntf');
  
        if(is_array($docArr) && count($docArr)) {
        	$docArrSort = array();
            foreach($docArr as $id => $class) {
                    
                $mvc = cls::get($class);
                
                list($order, $group) = explode('|', $mvc->newBtnGroup);
                
                // debug::log('Start HaveRight:' . $mvc->className);

                if($mvc->haveRightFor('add', $rec)) {
                    $ind = $order * 10000 + $i++;
                    $docArrSort[$ind] = array($group, $mvc->singleTitle, $class);
                }

                // debug::log('End HaveRight:' . $mvc->className);

            }
            
            // Сортиране
            ksort($docArrSort);
 
            // Групиране
            $btns = array();
            foreach($docArrSort as $id => $arr) {
                $btns[$arr[0]][$arr[1]] = $arr[2];
            }
        
            // Генериране на изгледа
            $tpl = new ET();
            
            // Ако сме в нишка
            if ($rec->threadId) {
                $text = tr("Нов документ в") . " " . $title;
            } else {
                $text = tr("Нова тема в") . " " . $title;
            }
            
            $tpl->append("\n<div class='listTitle'>" . $text . "</div>");
            $tpl->append("<div class='accordian noSelect'><ul>");
            
            $active = 'active';
            
            foreach($btns as $group => $bArr) {
                
                // Превеждаме групата
                $group = tr($group);
                
                $tpl->append("<li class='btns-title {$active} '><img class='btns-icon plus' src=". sbf('img/16/toggle1.png') ."><img class='btns-icon minus' src=". sbf('img/16/toggle2.png') .">&nbsp;{$group}</li>");
                $tpl->append("<li class='dimension'>");
                foreach($bArr as $btn => $class) {
                    $mvc = cls::get($class);
                    
                    $tpl->append(new ET("<div class='btn-group'>[#1#]</div>", ht::createBtn($mvc->singleTitle, 
                        array($class, 'add', 
                            'threadId' => $rec->threadId, 'folderId' => $rec->folderId, 'ret_url' => TRUE), 
                            NULL, NULL, "ef_icon={$mvc->singleIcon},style=width:100%;text-align:left;")));
                }
                
                $tpl->append("</li>"); 
                $active = '';
            }

            $tpl->append("</ul></div>");
            
            $tpl->push('doc/tpl/style.css', 'CSS');
            $tpl->push('doc/js/accordion.js', 'JS');
            jquery_Jquery::run($tpl, "accordionRenderAndCollapse();");
        } else {

            $tpl = tr("Няма възможност за добавяне на документ в") . " " . $title;
        }

        return $tpl;
    }
    
    
    /**
     * Връща абревиатурата на всички класов, които имплементират doc_DocumentIntf
     */
    static function getAbbr()
    {
        if (!self::$abbrArr) {
            self::setAbrr();
        }
        
        return self::$abbrArr;
    }
    
    
    /**
     * Задава абревиатурата на всички класов, които имплементират doc_DocumentIntf
     */
    static function setAbrr()
    {
        //Проверяваме дали записа фигурира в кеша
        $abbrArr = core_Cache::get('abbr', 'allClass', 14400, array('core_Classes', 'core_Interfaces'));
        
        //Ако няма
        if (!$abbrArr) {
            
            $docClasses = core_Classes::getOptionsByInterface('doc_DocumentIntf');

            //Обикаляме всички записи, които имплементират doc_DocumentInrf
            foreach ($docClasses as $id => $className) {
                
                //Създаваме инстанция на класа в масив
                $instanceArr[$id] = cls::get($className);
                
                $abbr = strtoupper($instanceArr[$id]->abbr);
				
                expect(i18n_Charset::is7Bit($abbr), $abbr, $abbrArr[$abbr], $className);
                expect(!$abbrArr[$abbr], $abbr, $abbrArr[$abbr], $className);
                
                // Ако няма абревиатура
                if (!trim($abbr)) continue;
                
                //Създаваме масив с абревиатурата и името на класа                
                $abbrArr[$abbr] = $className;
            }
            
            //Записваме масива в кеша
            core_Cache::set('abbr', 'allClass', $abbrArr, 14400, array('core_Classes', 'core_Interfaces'));
        }
        
        self::$abbrArr = $abbrArr;
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
        // Ако няма стойност, връщаме
        if (!$id) return ;
        
        // Записите на контейнера
        $doc = doc_Containers::getDocument($id);
        
        $lg = $doc->getLangFromRec();
        
        // Връщаме езика
        return $lg;
    }
    
    
    /**
     * Връща линка на папката във вербален вид
     * 
     * @param array $params - Масив с частите на линка
     * @param $params['Ctr'] - Контролера
     * @param $params['Act'] - Действието
     * @param $params['id'] - id' то на сингъла
     * 
     * @return core_ET|FALSE - Линк
     */
    static function getVerbalLink($params)
    {
        try {
            // Опитваме се да вземем инстанция на класа
            $ctrInst = cls::get($params['Ctr']);
            
            // Ако метода съществува в съответия клас
            if (method_exists($ctrInst, 'getVerbalLinkFromClass')) {
                
                // Вземаме линковете от класа
                $res = $ctrInst->getVerbalLinkFromClass($params['id']); 

                return $res;
            }
                
             // Проверяваме дали е число
            expect(is_numeric($params['id']));
            
            // Вземаме записите
            $rec = $ctrInst->fetch($params['id']);
            
            // Кое поле е избрано да се показва, като текст
            $field = $ctrInst->rowToolsSingleField;

            // Очакваме да имаме права за съответния екшън
            expect($rec && $ctrInst->haveRightFor('single', $rec));
        } catch (core_exception_Expect $e) {
            
            // Ако възникне някаква греша
            return FALSE;
        }
        
        try {
            // Ако не е зададено поле
            if ($field) {
            
                // Стойността на полето на текстовата част
                $title = $ctrInst->getVerbal($params['id'], $field);
            } else {
            
                // Използваме името на модула
                $title = ($ctrInst->singleTitle) ? $ctrInst->singleTitle : $ctrInst->title;
            
                // Добавяме id на фирмата
                $title .= ' #' . $rec->id;
            }
        } catch (core_exception_Expect $e) {
            
            reportException($e);
            // Ако възникне някаква греша
            return FALSE;
        }

        // Ако мода е xhtml
        if (Mode::is('text', 'xhtml')) {
            
            $res = new ET("<span class='linkWithIcon' style=\"" . ht::getIconStyle($ctrInst->singleIcon) . "\"> [#1#] </span>", $title);
        } elseif (Mode::is('text', 'plain')) {
            
            // Ескейпваме плейсхолдърите и връщаме титлата
            $res = core_ET::escape($title);
        } else {
            
            //Атрибути на линка
            $attr = array();
            $attr['ef_icon'] =  $ctrInst->singleIcon;    
            $attr['target'] = '_blank';    
            
            //Създаваме линк
            $res = ht::createLink($title, $params, NULL, $attr); 
        }
        
        return $res;
    }
    
    
    public static function getClassByAbbr($abbr)
    {
        $abbrArr = static::getAbbr();
        $abbr    = strtoupper($abbr);
        
        foreach ($abbrArr as $a=>$className) {
            if (strtoupper($a) == $abbr) {
                $docManager = cls::get($className);
                
                expect(cls::haveInterface('doc_DocumentIntf', $docManager));
                
                return $docManager;
            }
        }
        
        return NULL;
    }
    
    
    /**
     * Поправка на структурата на контейнерите
     * 
     * @param datetime $from
     * @param datetime $to
     * @param integer $delay
     * 
     * @return array
     */
    public static function repair($from = NULL, $to = NULL, $delay = 10)
    {
        // Изкючваме логването
        $isLoging = core_Debug::$isLogging;
        core_Debug::$isLogging = FALSE;
        
        // Данни за папката за несортирани
        $unsortedCoverClassId = core_Classes::getId('doc_UnsortedFolders');
        $defaultFolderId = doc_Folders::fetchField("#coverClass = '{$unsortedCoverClassId}'", 'id');
                
        $query = self::getQuery();
        
        // Подготвяме данните за търсене
        doc_Folders::prepareRepairDateQuery($query, $from, $to, $delay);
        
        $query->where("#folderId IS NULL");
        $query->orWhere("#threadId IS NULL");
        $query->orWhere("#docClass IS NULL");
        $query->orWhere("#docId IS NULL");
        $query->orWhere("#visibleForPartners IS NULL");
        $query->orWhere("#searchKeywords IS NULL");
        $query->orWhere("#searchKeywords = ''");
        
        $query->limit(500);

        $resArr = array();
        
        while($rec = $query->fetch()) {
            $isDel = FALSE;
            try {
                $docId = FALSE;
                $mustUpdate = TRUE;
                
                // Ако няма id на папката
                if (!isset($rec->folderId)) {
                    
                    // Опитваме се да определим от нишката
                    if ($rec->threadId) {
                        $rec->folderId = doc_Threads::fetchField("#id = '{$rec->threadId}'", 'folderId', FALSE);
                    }
                    
                    // Ако не е определена използваме папката за несортирани
                    if (!isset($rec->folderId)) {
                        $rec->folderId = $defaultFolderId;
                    }
                    
                    if (self::save($rec, 'folderId')) {
                        self::logNotice('Поправено folderId', $rec->id);
                        $resArr['folderId']++;
                    }
                }
                
                // Ако няма нишка
                if (!isset($rec->threadId)) {
                    
                    // Опитваме се да намерим id-то на нишката от нишките
                    $rec->threadId = doc_Threads::fetchField("#firstContainerId = '{$rec->id}' && #folderId = '{$rec->folderId}'", 'id', FALSE);
                    
                    // Ако не може създаваме нова нишка
                    if (!isset($rec->threadId)) {
                        $rec->threadId = doc_Threads::create($rec->folderId, $rec->createdOn, $rec->createdBy);
                    }
                
                    if (self::save($rec, 'threadId')) {
                        self::logNotice('Поправено threadId', $rec->id);
                        $resArr['threadId']++;
                    }
                }
                
                // Ако няма id на класа на документа
                if (!isset($rec->docClass)) {
                    
                    self::repairDocClass($rec);
                    $resArr['docClass']++;
                }
                
                // Ако няма id на документа
                if (!isset($rec->docId) && isset($rec->docClass)) {
                    
                    $isDel = self::repairDocId($rec);
                    
                    $resArr['docId']++;
                    
                    if ($isDel) {
                        $resArr['del_cnt']++;
                    }
                }
                
                // Обновяваме документа, за да се поправят другите полета
                if (!$isDel) {
                    
                    // Ако ще се обновява само visibleForPartners, няма нужда да се обновява целия контейнер
                    $updateOnlyVisible = FALSE;
                    if (!isset($rec->visibleForPartners) && $rec->docClass && $rec->docId) {
                        if (cls::load($rec->docClass, TRUE)) {
                            try {
                                $docMvc = cls::get($rec->docClass);
                                $docRec = $docMvc->fetch($rec->docId);
                                $updateOnlyVisible = TRUE;
                            } catch (ErrorException $e) {
                                $updateOnlyVisible = FALSE;
                            }
                        }
                    }
                    
                    if ($updateOnlyVisible) {
                        self::logNotice('Обновяване на visibleForPartners', $rec->id);
                        if ($docMvc->isVisibleForPartners($docRec)) {
                            $rec->visibleForPartners = 'yes';
                        } else {
                            $rec->visibleForPartners = 'no';
                        }
                        
                        self::save($rec, 'visibleForPartners');
                        
                        $resArr['updateVisibleForPartners']++;
                    } else {
                        self::update($rec->id);
                        $resArr['updateContainers']++;
                        self::logNotice('Обновяване на контейнера', $rec->id);
                    }
                }
            } catch (ErrorException $e) {
                reportException($e);
            }
        }
        
        // Ако е зададено да се поправят всички стойности
        if (doc_Setup::get('REPAIR_ALL') == 'yes') {
            $resArr += self::repairAll($from, $to, $delay);
        }

        // Връщаме старото състояние за ловговането в дебъг
        core_Debug::$isLogging = $isLoging;
        
        return $resArr;
    }
    
    
    /**
     * Поправка на развалените полета за състояние
     * 
     * @param datetime $from
     * @param datetime $to
     * @param integer $delay
     * 
     * @return array
     */
    public static function repairAll($from = NULL, $to = NULL, $delay = 10)
    {
        $resArr = array();
        $query = self::getQuery();
        
        doc_Folders::prepareRepairDateQuery($query, $from, $to, $delay);
        
        while ($rec = $query->fetch()) {
            try {
                
                // Ако нишката е грешна (няма такъв запис)
                
                $tRec = FALSE;
                $oldThreadId = $rec->threadId;
                
                if ($rec->threadId) {
                    $tRec = doc_Threads::fetch($rec->threadId, '*', FALSE);
                }
                
                if (!$tRec && $rec->folderId) {
                    
                    // Опитваме се да намерим id-то на нишката от нишките
                    $rec->threadId = doc_Threads::fetchField("#firstContainerId = '{$rec->id}' && #folderId = '{$rec->folderId}'", 'id', FALSE);
                    
                    if ($rec->threadId) {
                        $tRec = doc_Threads::fetch($rec->threadId, '*', FALSE);
                    }
                    
                    // Ако не може създаваме нова нишка
                    if (!$tRec) {
                        $rec->threadId = doc_Threads::create($rec->folderId, $rec->createdOn, $rec->createdBy);
                        $tRec = doc_Threads::fetch($rec->threadId, '*', FALSE);
                    }
                    
                    if (self::save($rec, 'threadId')) {
                        self::logNotice("Променена нишка от {$oldThreadId} на {$rec->threadId}", $rec->id);
                        $resArr['threadId']++;
                    }
                }
                
                // Ако папката е грешна (не съвпада с папката в нишката)
                if ($tRec) {
                    if ($rec->folderId != $tRec->folderId) {
                        self::logNotice("Променена папка от {$rec->folderId} на {$tRec->folderId}", $rec->id);
                        $rec->folderId = $tRec->folderId;
                        
                        if (self::save($rec, 'folderId')) {
                            $resArr['folderId']++;
                        }
                    }
                }
                
                if ($rec->folderId && $fRec = doc_Folders::fetch($rec->folderId, '*', FALSE)) {
                    try {
                        
                        // Поправяме документите, които няма инстанция или липсва запис за тях
                        if (cls::load($rec->docClass, TRUE)) {
                            $inst = cls::get($rec->docClass);
                            
                            if (!cls::haveInterface('doc_DocumentIntf', $inst)) {
                                // Поправка на id на документа, ако ненаследява съответния интерфейс за документи
                                self::repairDocClass($rec);
                                $resArr['docClass']++;
                            }
                            
                            // Ако е счупено docId на документа
                            if (!$rec->docId || !$inst->fetch($rec->docId, '*', FALSE)) {
                                $isDel = self::repairDocId($rec);
                    
                                $resArr['docId']++;
                                
                                if ($isDel) {
                                    $resArr['del_cnt']++;
                                } else {
                                    self::update($rec->id);
                                    $resArr['updateContainers']++;
                                }
                            }
                        } else {
                            
                            // Поправка на id на документа, ако е счупен
                            self::repairDocClass($rec);
                            $resArr['docClass']++;
                        }
                    } catch (ErrorException $e) {
                        reportException($e);
                        
                        continue;
                    }
                }
                
                // Оправяме състоянието на документа
                
                if (!$rec->docClass || !$rec->docId) continue;
                
                try {
                    $clsInst = cls::get($rec->docClass);
                    $iRec = $clsInst->fetch($rec->docId, 'state', FALSE);
                    
                    if (!isset($iRec->state)) continue;
                    
                    if ($iRec->state == $rec->state) continue;
                    
                    $oldState = $rec->state;
                    $rec->state = $iRec->state;
                    
                    if (self::save($rec, 'state')) {
                        $resArr['state']++;
                        self::logNotice("Променено състояние на документа от {$oldState} на {$rec->state}", $rec->id);
                        self::update($rec->id);
                    }
                } catch (core_exception_Expect $e) {
                    
                    continue;
                }
            } catch(ErrorException $e) {
                reportException($e);
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Поправка на структурата на документите
     * 
     * @param datetime $from
     * @param datetime $to
     * @param integer $delay
     * 
     * @return array
     */
    public static function repairDoc($from = NULL, $to = NULL, $delay = 10)
    {
        $resArr = array();
        
        // Изкючваме логването
        $isLoging = core_Debug::$isLogging;
        core_Debug::$isLogging = FALSE;
        
        // Всички документи, които имат интерфейса
        $clsArr = core_Classes::getOptionsByInterface('doc_DocumentIntf', 'name');
        
        foreach ($clsArr as $clsId => $clsName) {
            if (!cls::load($clsName, TRUE)) continue ;
            
            $clsInst = cls::get($clsName);
            
            $dQuery = $clsInst->getQuery();
            
            // Подготвяме данните за търсене
            doc_Folders::prepareRepairDateQuery($dQuery, $from, $to, $delay);
            
            $repairAll = TRUE;
            if (doc_Setup::get('REPAIR_ALL') != 'yes') {
                $dQuery->where("#folderId IS NULL OR #folderId = ''");
                $dQuery->orWhere("#threadId IS NULL OR #threadId = ''");
                $dQuery->orWhere("#containerId IS NULL OR #containerId = ''");
                $repairAll = FALSE;
            }
            
            try {
                while ($dRec = $dQuery->fetch()) {
                    
                    $delete = FALSE;
                    
                    // Поправяме containerId
                    if (!$dRec->containerId) {
                        if ($dRec->state == 'rejected') {
                            $delete = TRUE;
                            $reason = 'Няма containerId на оттеглен документ';
                        }
                    }
                    
                    if (!$delete) {
                        
                        // Опитваме се да сравним със записа в doc_Containers
                        if (($docClassId = core_Classes::getId($clsInst)) && ($cId = self::fetchField(array("#docId = '[#1#]' AND #docClass = '[#2#]'", $dRec->id, $docClassId), 'id', FALSE))) {
                            
                            $update = TRUE;
                            if ($repairAll) {
                                
                                if ($cId == $dRec->containerId) {
                                    $update = FALSE;
                                }
                            }
                            
                            if ($update) {
                                $resArr['containerId']++;
                                $clsInst->logNotice("Обновен containerId на документа", $dRec->id);
                                $dRec->containerId = $cId;
                                
                                try {
                                    $clsInst->save($dRec, 'containerId');
                                } catch (ErrorException $e) {
                                    reportException($e);
                                }
                            }
                        } else {
                            // Да не се изтрива, защото може и да е на наследниците или бащите
//                             $delete = TRUE;
                        }
                    }
                    
                    if (!$dRec->containerId) {
                        $delete = TRUE;
                        $reason = 'Няма containerId';
                    }
                    
                    // Поправяме originId
                    if (!$delete && $repairAll && $dRec->originId) {
                        
                        // Ако originId липсва в контейнерите
                        if (!$oCRec = doc_Containers::fetch($dRec->originId, '*', FALSE)) {
                            $resArr['originId']++;
                            $clsInst->logNotice("Нилиран originId на документа", $dRec->id);
                            $dRec->originId = NULL;
                            
                            try {
                                $clsInst->save_($dRec, 'originId');
                            } catch (ErrorException $e) {
                                reportException($e);
                            }
                        }
                    }
                    
                    // Поправяме threadId
                    if (!$delete && !$dRec->threadId) {
                        if ($dRec->state == 'rejected') {
                            $delete = TRUE;
                            $reason = 'Няма threadId на оттеглен документ';
                        }
                    }
                    
                    if (!$delete) {
                        if ($dRec->containerId && ($threadId = self::fetchField($dRec->containerId, 'threadId', FALSE))) {
                            
                            $update = TRUE;
                            if ($repairAll) {
                            
                                if ($threadId == $dRec->threadId) {
                                    $update = FALSE;
                                }
                            }
                            
                            if ($update) {
                                $resArr['threadId']++;
                                $clsInst->logNotice("Обновен threadId на документа", $dRec->id);
                                $dRec->threadId = $threadId;
                                try {
                                    $clsInst->save($dRec, 'threadId');
                                } catch (ErrorException $e) {
                                    reportException($e);
                                }
                            }
                        } else {
                            $delete = TRUE;
                            $reason = 'Няма запис в doc_Threads за containerId';
                        }
                        
                    }
                    
                    // Поправяме folderId
                    if (!$delete) {
                        if (!$dRec->folderId && $dRec->state == 'rejected') {
                            $delete = TRUE;
                            $reason = 'Няма folderId на оттеглен документ';
                        }
                        
                        if (!$delete) {
                            $folderId = NULL;
                            
                            if ($dRec->containerId) {
                                $folderId = self::fetchField($dRec->containerId, 'folderId', FALSE);
                            }
                            
                            if (!$folderId && $dRec->threadId) {
                                $folderId = doc_Threads::fetchField($dRec->threadId, 'folderId', FALSE);
                            
                            }
                            
                            if ($folderId) {
                                
                                $update = TRUE;
                                if ($repairAll) {
                                
                                    if ($folderId == $dRec->folderId) {
                                        $update = FALSE;
                                    }
                                }
                                
                                if ($update) {
                                    $resArr['folderId']++;
                                    $clsInst->logNotice("Обновен folderId на документа", $dRec->id);
                                    $dRec->folderId = $folderId;
                                    try {
                                        $clsInst->save($dRec, 'folderId');
                                    } catch (ErrorException $e) {
                                        reportException($e);
                                    }
                                }
                            } else {
                                $delete = TRUE;
                                $reason = 'Не може да се определи folderId';
                            }
                        }
                    }
                    
                    // Ако не може да се поправи - премахваме записа
                    if ($delete) {
                        try {
                            
                            $delMsg = 'Изтрит документ' . ' - ' . $reason;
                            if ($clsInst instanceof core_Master) {
                                $dArr = arr::make($clsInst->details, TRUE);
                                
                                // Изтирваме детайлите за документа
                                if (!empty($dArr)) {
                                    
                                    $delDetCnt = 0;
                                    
                                    foreach ($dArr as $detail) {
                                        if (!cls::load($detail, TRUE)) continue;
                                        
                                        $detailInst = cls::get($detail);
                                        if (!($detailInst->Master instanceof $clsInst)) continue;
                                        
                                        if ($detailInst->masterKey) {
                                            $delDetCnt += $detailInst->delete(array("#{$detailInst->masterKey} = '[#1#]'", $dRec->id));
                                        }
                                    }
                                    $delMsg = "Изтрит документ и детайлите към него ({$delDetCnt})" . ' - ' . $reason;
                                }
                            }
    
                            $clsInst->logInfo($delMsg, $dRec->id);
                            $resArr['del_cnt']++;
                            $clsInst->delete($dRec->id);
                        } catch (ErrorException $e) {
                            reportException($e);
                        }
                    }
                }
            } catch (Exception $e) {
                reportException($e);
            }
        }
        
        // Връщаме старото състояние за ловговането в дебъг
        core_Debug::$isLogging = $isLoging;
        
        return $resArr;
    }
    
    
    /**
     * Помощна функция за поправка на docClass
     * 
     * @param stdObject $rec
     */
    protected static function repairDocClass($rec)
    {
        // id' то на интерфейса
        $Interfaces = cls::get('core_Interfaces');
        $documentIntfId = $Interfaces->fetchByName('doc_DocumentIntf');
        
        // Намираме всички докуемнти със съответния интерфейс
        $cQuery = core_Classes::getQuery();
        $cQuery->where("#state = 'active' AND #interfaces LIKE '%|{$documentIntfId}|%'");
        
        $haveRec = FALSE;
        
        while ($cRec = $cQuery->fetch()) {
            if (cls::load($cRec->name, TRUE)) {
                $clsInst = cls::get($cRec->name);
                
                // Ако има запис за съответния контейнер в мениджъра на докуемнта
                if ($docId = $clsInst->fetchField("#containerId = {$rec->id}", 'id', FALSE)) {
                    
                    self::logNotice("Променено ид на документ от {$rec->docClass} на {$cRec->id}", $rec->id);
                    
                    $rec->docClass = $cRec->id;
        
                    self::save($rec, 'docClass');
                    
                    $haveRec = TRUE;
                    
                    break;
                }
            }
        }
        
        if (!$haveRec) {
            self::logNotice("Не може да се намери 'docClass' за {$rec->docClass}", $rec->id);
            self::repairDocId($rec);
        }
    }
    
    
    /**
     * Помощна функция за поправка на id на документи
     * 
     * @param stdObject $rec
     * 
     * @return boolean
     */
    protected static function repairDocId($rec)
    {
        $isDel = FALSE;

        if (cls::load($rec->docClass, TRUE)) {
        
            $docClass = cls::get($rec->docClass);
        
            // Ако класа може да се използва за документ
            if (($docClass instanceof core_Mvc) && cls::haveInterface('doc_DocumentIntf', $docClass)) {
                
                $docId = $docClass->fetchField("#containerId = '{$rec->id}'", 'id', FALSE);
                
                if ($docId) {
                    self::logNotice("Променено docId от {$rec->docId} на {$docId}", $rec->id);
                    $rec->docId = $docId;
                    self::save($rec, 'docId');
                } else {
                    if ($rec->id) {
                        
                        // Ако не може да се намери съответен документ, изтриваме го
                        if (self::delete($rec->id)) {
                            $isDel = TRUE;
                            self::logNotice('Премахнат документ, който не може да бъде възстановен', $rec->id);
                        }
                    }
                }
            }
        } else {
            if (self::delete($rec->id)) {
                $isDel = TRUE;
                self::logNotice('Премахнат документ, който не може да бъде възстановен', $rec->id);
            }
        }
        
        return $isDel;
    }
    
    
    /**
     * Проверява дали даден тип документ, се съдържа в нишката
     * 
     * @param integer $threadId - id от doc_Threads
     * @param sting $documentName - Името на класа на документа
     */
    static function checkDocumentExistInThread($threadId, $documentName) 
    {
        // Името на документа с малки букви
        $documentName = strtolower($documentName);
        
        // Вземаме id' то на класа
        $documentClassId = core_Classes::fetch("LOWER(#name) = '{$documentName}'")->id;
        
        // Ако има такъв запис, връщаме TRUE
        return (boolean)static::fetch("#threadId = '{$threadId}' AND #docClass = '{$documentClassId}' AND #state != 'rejected'");
    }
    
    
    /**
     * 
     */
    public static function on_AfterSave($mvc, &$id, $rec)
    {
        // Обновяваме записите за файловете
        doc_Files::updateRec($rec);
    }
    
    
    /**
     * Оттегля всички контейнери в зададена нишка. Това включва оттеглянето и на реалните документи,
     * съдържащи се в контейнерите.
     * 
     * @param int $threadId
     * @return array $rejectedIds - ид-та на документите оттеглени, при оттеглянето на треда
     */
    public static function rejectByThread($threadId)
    {
        $query = static::getQuery();
        
        $query->where("#threadId = {$threadId}");
        $query->where("#state <> 'rejected'");
        
        // Подреждаме ги последно модифициране
        $query->orderBy("#modifiedOn" , 'DESC');
        
        $rejectedIds = array();
        while ($rec = $query->fetch()) {
            try{
            	$doc = static::getDocument($rec);
            	$doc->reject();
            	
            	doc_Prototypes::sync($rec->id);
            	doc_HiddenContainers::showOrHideDocument($rec->id, TRUE);
            } catch(core_exception_Expect $e){
            	continue;
            }
            
            // Запомняме ид-та на контейнерите, които сме оттеглили
            $rejectedIds[] = $rec->id;
        }
       
        return $rejectedIds;
    }
    
    
    /**
     * Възстановява всички контейнери в зададена нишка. Това включва възстановяването и на 
     * реалните документи, съдържащи се в контейнерите.
     * 
     * @param int $threadId
     */
    public static function restoreByThread($threadId)
    {
        // При възстановяване на треда, гледаме кои контейнери са били оттеглени със него
    	$rejectedInThread = doc_Threads::fetchField($threadId, 'rejectedContainersInThread');
        
        /* @var $query core_Query */
        $query = static::getQuery();
        
        $query->where("#threadId = {$threadId}");
        $query->where("#state = 'rejected'");
        $query->orderBy("#id", ASC);
        
        // Ако има документи оттеглени със треда
        if(count($rejectedInThread)){
        	
        	// Възстановяваме само тези контейнери от тях
        	$query->in('id', $rejectedInThread);
        	$recs = $query->fetchAll();
			$recs = array_replace(array_flip($rejectedInThread), $recs);
		} else {
			$recs = $query->fetchAll();
		}
        
        if(count($recs)){
        	foreach ($recs as $rec){
        		try{
        			$doc = static::getDocument($rec);
        			$doc->restore();
        			
        			doc_Prototypes::sync($rec->id);
        			doc_HiddenContainers::showOrHideDocument($rec->id, NULL);
        		} catch(core_exception_Expect $e){
        			continue;
        		}
        	}
        }
    }
    
    
    /**
     * Връща контрагент данните на контейнера
     */
    static function getContragentData($id)
    {
        // Записа
        $rec = static::fetch($id);
        
        // Класа
        $class = cls::get($rec->docClass);
        
        // Контрагент данните
        $contragentData = $class::getContragentData($rec->docId);

        return $contragentData;
    }
    
    
    /**
     * Връща линк към сингъла на документа
     * 
     * @param int $id - id на документа
     * 
     * @return string - Линк към документа
     */
    static function getLinkForSingle($id)
    {
        // Ако не е чило, връщаме
        if (!is_numeric($id)) return ;

        // Документа
        $doc = doc_Containers::getDocument($id);
        
        // Полетата на документа във вербален вид
        $docRow = $doc->getDocumentRow();
        
        // Ако има права за сингъла на документа
        if ($doc->haveRightFor('single')) {
            
            // Да е линк към сингъла
            $url = array($doc, 'single', $doc->that);
        } else {
            
            // Ако няма права, да не е линк
            $url = array();
        }
        
        // Атрибутеите на линка
        $attr = array();
        $attr['ef_icon'] = $doc->getIcon($doc->that);
        $attr['title'] = 'Документ|*: ' . $docRow->title;
        
        // Документа да е линк към single' а на документа
        $res = ht::createLink(str::limitLen($docRow->title, 35), $url, NULL, $attr);
        
        return $res;
    }
    
    
    /**
     * Връща масив с всички id' та на документите в нишката
     * 
     * @param mixed $thread - id на нишка или масив с id-та на нишка
     * @param string $state - Състоянието на документите
     * @param string $order - ASC или DESC подредба по дата на модифициране или да не се подреждат
     * 
     * @return array - Двумерен масив с нишките и документите в тях
     */
    static function getAllDocIdFromThread($thread, $state=NULL, $order=NULL)
    {
        $arr = array();
        
        // Вземаме всички документи от нишката
        $query = static::getQuery();
        
        // Ако е подаден масив
        if (is_array($thread)) {
            
            if (empty($thread)) return $arr;
            
            // За всички нишки
            $query->orWhereArr("threadId", $thread);
        } else {
            
            if (!$thread) return $arr;
            
            // За съответната нишка
            $query->where("#threadId = '{$thread}'");
        }
        
        // Ако е зададено състояние
        if ($state) {
            
            // Да се вземат документи от съответното състояние
            $query->where(array("#state = '[#1#]'", $state));
        }
        
        // Ако състоянието не е оттеглено
        if ($state != 'rejected') {
            
            // Оттеглените документи да не се вземат в предвид
            $query->where(array("#state != 'rejected'"));
        }
        
        // Ако е зададена подреба
        if ($order) {
            
            // Използваме я
            $query->orderBy('modifiedOn', $order);
        }
        
        // Обхождаме резултатите
        while ($rec = $query->fetch()) {
            
            // Ако няма клас или документ - прескачаме
            if (!$rec->docClass || !$rec->docId) continue;
            
            // Инстанция на класа
            $cls = core_Cls::get($rec->docClass);
            
            // Ако нямаме права за сингъла на документа, прескачаме
            if (!$cls->haveRightFor('single', $rec->docId)) continue;
            
            // Добавяме в масива
            $arr[$rec->threadId][$rec->id] = $rec;
        }
        
        return $arr;
    }
    
    
    /**
     * Връща URL за добавяне на документ
     * 
     * @param string $callback
     * 
     * @return URL
     */
    static function getUrLForAddDoc($callback)
    {
        // Защитаваме променливите
        Request::setProtected('callback');
        
        // Задаваме линка
        $url = array('doc_Containers', 'AddDoc', 'callback' => $callback);
        
        return toUrl($url);
    }
    
    
    /**
     * Екшън, който редиректва към качването на файл в съответния таб
     */
    function act_AddDoc()
    {
        $callback = Request::get('callback', 'identifier');
        
        // Защитаваме променливите
        Request::setProtected('callback');
        
        // Името на класа
        $class = 'doc_Log';
        
        // Вземаме екшъна
        $act = 'addDocDialog';
        
        // URL-то да сочи към съответния екшън и клас
        $url = array($class, $act, 'callback' => $callback);
        
        return new Redirect($url);
    }
    
    
    /**
     * Нотифицира за неизпратени имейли или чернови документи
     */
    function cron_notifyForIncompleteDoc()
    {
        $this->notifyForIncompleteDoc();
    }
    
    
    /**
     * Нотифицира за неизпратени имейли или чернови документи
     */
    function notifyForIncompleteDoc()
    {
        // Конфигураця
        $conf = core_Packs::getConfig('doc');
        
        // Текущото време
        $now = dt::now();
        
        // Масив с датите между които ще се извлича
        $dateRange = array();
        $dateRange[0] = dt::subtractSecs($conf->DOC_NOTIFY_FOR_INCOMPLETE_FROM, $now); 
        $dateRange[1] = dt::subtractSecs($conf->DOC_NOTIFY_FOR_INCOMPLETE_TO, $now); 
        
        // Подреждаме масива
        sort($dateRange);
        
        // Всички документи създадени от потребителите и редактирани между датите
        $query = static::getQuery();
        $query->where(array("#modifiedOn >= '[#1#]'", $dateRange[0]));
        $query->where(array("#modifiedOn <= '[#1#]'", $dateRange[1]));
        $query->where("#createdBy > 0");
        
        // Инстанция на класа
        $Outgoings = cls::get('email_Outgoings');
        
        // id на класа
        $outgoingsClassId = $Outgoings->getClassId();
        
        // Само черновите
        $query->where("#state = 'draft'");
        
        // Или, ако са имейли, активните
        $query->orWhere(array("#state = 'active' AND #docClass = '[#1#]'", $outgoingsClassId));
        
        // Групираме по създаване и състояние
        $query->groupBy('createdBy, state');
        
        $authorTemasArr = array();
        
        while ($rec = $query->fetch()) {
            
            // Масив с екипите на съответния потребител
            $authorTemasArr[$rec->createdBy] = type_Users::getUserFromTeams($rec->createdBy);
            
            // Вземаме първия екип, в който участва
            $firstTeamAuthor = key($authorTemasArr[$rec->createdBy]);
            
            // URL-то което ще служи за премахване на нотификацията
            $urlArr = array('doc_Search', 'state' => $rec->state, 'author' => $rec->createdBy);
            
            // Ако е чернова
            if ($rec->state == 'draft') {
                
                // Съобщение
                $message = "|Имате създадени, но неактивирани документи";
                
                // Линк, където ще сочи нотификацията
                $customUrl = array('doc_Search', 'state' => 'draft', 'author' => $firstTeamAuthor);
            } else {
                
                // Съобщение
                $message = "|Имате активирани, но неизпратени имейли";
                
                // Линк, където ще сочи нотификацията
                $customUrl = array('doc_Search', 'state' => 'active', 'docClass' => $outgoingsClassId, 'author' => $firstTeamAuthor);
            }
            
            // Добавяме нотификация
            bgerp_Notifications::add($message, $urlArr, $rec->createdBy, 'normal', $customUrl);
        }
        
        return ;
    }
    
    
	/**
     * Изпълнява се след създаването на модела
	 * 
	 * @param unknown_type $mvc
	 * @param unknown_type $res
	 */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        // Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = 'notifyForIncompleteDoc';
        $rec->description = 'Нотифициране за незавършени действия с документите';
        $rec->controller = $mvc->className;
        $rec->action = 'notifyForIncompleteDoc';
        $rec->period = 60;
        $rec->offset = mt_rand(0,40);
        $rec->delay = 0;
        $rec->timeLimit = 200;
        $res .= core_Cron::addOnce($rec);
        
        // Данни за работата на cron
        $rec1 = new stdClass();
        $rec1->systemId = 'notifyForDraftBusinessDoc';
        $rec1->description = 'Нотифициране за неактивирани бизнес документи';
        $rec1->controller = $mvc->className;
        $rec1->action = 'notifyDraftBusinessDoc';
        $rec1->period = 43200;
        $rec1->offset = rand(4260, 4380); // от 71h до 73h
        $rec1->delay = 0;
        $rec1->timeLimit = 200;
        $res .= core_Cron::addOnce($rec1);
        
        
        // Данни за работата на cron за поправка на документи
        $repRec = new stdClass();
        $repRec->systemId = self::REPAIR_SYSTEM_ID;
        $repRec->description = 'Поправка на папки, нишки и контейнери';
        $repRec->controller = $mvc->className;
        $repRec->action = 'repair';
        $repRec->period = 5;
        $repRec->offset = 0;
        $repRec->delay = 0;
        $repRec->timeLimit = 200;
        $res .= core_Cron::addOnce($repRec);
    }
    
    
    /**
     * Изпращане на нотификации за всички чернови бизнес документи
     */
    public function cron_notifyDraftBusinessDoc()
    {
    	// Намираме бизнес документите
    	$docs = core_Classes::getOptionsByInterface('bgerp_DealIntf');
    	
    	// Извличаме всички потребители в системата
    	$userQuery = core_Users::getQuery();
    	$userQuery->show('id');
    	
    	$conf = core_Packs::getConfig('doc');
    	$now = dt::now();
    	$offset = -1 * $conf->DOC_NOTIFY_FOR_INCOMPLETE_BUSINESS_DOC;
    	$from = dt::addSecs($offset, $now);
    	$from = $fromDate = dt::verbal2mysql($from, FALSE);
        $from .= ' 00:00:00';
    	
    	$authorTemasArr = array();
    	
    	// За всеки потребител
    	while($uRec = $userQuery->fetch()){
    		$notArr = array();
    		
    		$authorTemasArr[$uRec->id] = type_Users::getUserFromTeams($uRec->id);
    		$firstTeamAuthor = key($authorTemasArr[$uRec->id]);
    		
    		// Проверяваме от всеки документ създаден от този потребител
    		foreach ($docs as $id => $name){
    		
    			// Преброяваме колко чернови има от всеки вид
    			$docQuery = $name::getQuery();
    			$docQuery->where("#state = 'draft'");
    			$docQuery->where("#createdBy = {$uRec->id}");
    			$docQuery->where("#modifiedOn >= '{$from}'");
    			
    			$count = $docQuery->count();
    			
    			// Ако бройката е по-голяма от 0, записваме в масива
    			if($count != 0){
    				$notArr[$id] = $count;
    			}
    		}
    		
    		// Ако има неактивирани бизнес документи
    		if(count($notArr)){
    			foreach ($notArr as $clsId => $count){
    				$customUrl = $url = array('doc_Search', 'docClass' =>  $clsId, 'state' => 'draft', 'author' => $firstTeamAuthor, 'fromDate' => $fromDate);
    				 
    				if($count == 1){
    					$name = cls::get($clsId)->singleTitle;
    					$str = 'Имате създаден, но неактивиран';
    				} else {
    					$name = cls::get($clsId)->title;
    					$str = 'Имате създадени, но неактивирани';
    				}
    				
    				$name = mb_strtolower($name);
    				
    				$msg = "|{$str}|* {$count} {$name}";
    				 
    				// Създаваме нотификация към потребителя с линк към филтрирани неговите документи
    				bgerp_Notifications::add($msg, $url, $uRec->id, 'normal', $customUrl);
    			}
    		}
    	}
    }
    
    
    /**
     * Екшън за поправка на развалените папки, нишки или контейнери
     */
    function act_Repair()
    {
        requireRole('admin');
        
        $retUrl = getRetUrl();
        
        // Вземаме празна форма
        $form = cls::get('core_Form');
        
        $form->FNC('repair', 'enum(all=Всички, folders=Папки, threads=Нишки, containers=Контейнери, cover=Корици, doc=Документи)', 'caption=На, input=input, mandatory');
        $form->FNC('from', 'datetime', 'caption=От, input=input');
        $form->FNC('to', 'datetime', 'caption=До, input=input');
        
        $form->input('repair, from, to', TRUE);
        
        if ($form->isSubmitted()) {
            
            $conf = core_Packs::getConfig('doc');
            
            $Size = cls::get('fileman_FileSize');
            
            $memoryLimit = ini_get('memory_limit');
            $memoryLimitB = $Size->fromVerbal($memoryLimit);
            
            $newMemLimit = "1024M";
            $newMemLimitB = $Size->fromVerbal($newMemLimit);
            
            if ($newMemLimitB > $memoryLimitB) {
                ini_set("memory_limit", $newMemLimit);
            }
            
            core_App::setTimeLimit(1200);
            
            // Ако са объркани датите
            if (isset($form->rec->from) && isset($form->rec->to) && ($form->rec->from > $form->rec->to)) {
                $from = $form->rec->from;
                $form->rec->from = $form->rec->to;
                $form->rec->to = $from;
            }
            
            $repArr = array();
            
            // В зависимост от избраната стойност поправяме документите
            if ($form->rec->repair == 'folders' || $form->rec->repair == 'all') {
                $repArr['folders'] = doc_Folders::repair($form->rec->from, $form->rec->to, $conf->DOC_REPAIR_DELAY);
            }
            
            if ($form->rec->repair == 'threads' || $form->rec->repair == 'all') {
                $repArr['threads'] = doc_Threads::repair($form->rec->from, $form->rec->to, $conf->DOC_REPAIR_DELAY);
            }
            
            if ($form->rec->repair == 'containers' || $form->rec->repair == 'all') {
                $repArr['containers'] = doc_Containers::repair($form->rec->from, $form->rec->to, $conf->DOC_REPAIR_DELAY);
            }
            
            if ($form->rec->repair == 'cover' || $form->rec->repair == 'all') {
                $repArr['cover'] = doc_Folders::repairCover($form->rec->from, $form->rec->to, $conf->DOC_REPAIR_DELAY);
            }
            
            if ($form->rec->repair == 'doc' || $form->rec->repair == 'all') {
                $repArr['doc'] = doc_Containers::repairDoc($form->rec->from, $form->rec->to, $conf->DOC_REPAIR_DELAY);
            }
            
            // Резултат след поправката
            $res = '';
            foreach ($repArr as $name => $repairedArr) {
                if (!empty($repairedArr)) {
                    
                    if ($name == 'folders') {
                        $res .= "<li class='green'>Поправки в папките: </li>\n";
                    } elseif ($name == 'threads') {
                        $res .= "<li class='green'>Поправки в нишките: </li>\n";
                    } elseif ($name == 'doc') {
                        $res .= "<li class='green'>Поправки в документите: </li>\n";
                    } elseif ($name == 'cover') {
                        $res .= "<li class='green'>Поправки в кориците: </li>\n";
                    } else {
                        $res .= "<li class='green'>Поправки в контейнерите: </li>\n";
                    }
                    
                    foreach ((array)$repairedArr as $field => $cnt) {
                        if ($field == 'del_cnt') {
                            $res .= "\n<li class='green'>Изтирите са {$cnt} записа</li>";
                        } else {
                            $res .= "\n<li>Поправени развалени полета '{$field}' - {$cnt} записа</li>";
                        }
                    }
                }
            }
            
            if (empty($res)) {
                $res = 'Няма документи за поправяне';
            }
            
            return new Redirect($retUrl, $res);
        }
        
        $form->title = 'Поправка в документите';
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Поправи', 'repair', 'ef_icon = img/16/hammer_screwdriver.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png');
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Поправяне на ключовете в документите
     */
    function act_RepairKeywords()
    {
        requireRole('admin');
        
        core_App::setTimeLimit(600);
        
        $force = Request::get('force');
        
        $rArr = self::regenerateSerchKeywords($force);
        
        $retUrl = getRetUrl();
        
        if (!$retUrl) {
            $retUrl = array('core_Packs');
        }
        
        if (empty($rArr)) {
            $msg = '|Няма документи за ре-индексиране';
        } else {
            $cnt = $rArr[0];
            if ($cnt == 1) {
                $msg = "|Ре-индексиран|* 1 |документ";
            } else {
                $msg = "|Ре-индексирани|* {$cnt} |документа";
            }
        }
        
        return new Redirect($retUrl, $msg);
    }
    
    
    /**
     * Функция, която се изпълнява от крона и стартира процеса на изпращане на blast
     */
    function cron_Repair()
    {
        $cronPeriod = core_Cron::getPeriod(self::REPAIR_SYSTEM_ID);
        
        $cronPeriod *= 2;
        
        $from = dt::subtractSecs($cronPeriod);
        $to = dt::now();
        
        $conf = core_Packs::getConfig('doc');
        
        $repArr = array();
        $repArr['folders'] = doc_Folders::repair($from, $to, $conf->DOC_REPAIR_DELAY);
        $repArr['threads'] = doc_Threads::repair($from, $to, $conf->DOC_REPAIR_DELAY);
        $repArr['containers'] = doc_Containers::repair($from, $to, $conf->DOC_REPAIR_DELAY);
        
        return $repArr;
    }
    
    
    /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareListRecs($mvc, &$res, $data)
    {
        // Подготвяме документите, които да са скрити
        doc_HiddenContainers::prepareDocsForHide($data->recs);
    }
    
    
    /**
     * Показва скрития документ
     */
    function act_ShowDocumentInThread()
    {
        $id = Request::get('id', 'int');
        
        $ajaxMode = (boolean) Request::get('ajax_mode');
        
        return self::showOrHideDocumentInThread($id, 'show', $ajaxMode);
    }
    
    
    /**
     * Скрива показания документ
     */
    function act_HideDocumentInThread()
    {
        $id = Request::get('id', 'int');
        
        $ajaxMode = (boolean) Request::get('ajax_mode');
        
        return self::showOrHideDocumentInThread($id, 'hide', $ajaxMode);
    }
    
    
    /**
     * Функция, която се използва от екшъните за скриване/показване на документ
     * 
     * @param string $id
     * @param string $act
     * 
     * @return object
     */
    protected static function showOrHideDocumentInThread($id, $act='show', $ajaxMode = FALSE)
    {
        $hideDoc = ($act == 'show') ? FALSE : TRUE;
        $rec = self::fetch($id);
        
        expect($rec);
        
        $document = self::getDocument($id);
        
        if (!doc_Threads::haveRightFor('single', $rec->threadId)) {
            
            expect($document->haveRightFor('single'));
        }
        
        doc_HiddenContainers::showOrHideDocument($id, $hideDoc);
        
        if ($ajaxMode) {
            
            $resStatus = self::getDocumentForAjaxShow($id);
    		
    		return $resStatus;
        } else {
            
            return new Redirect(array($document, 'single', $document->that, 'showOrHide' => $act));
        }
    }
    
    
    /**
     * Рендира съдържанието на скрит документ
     * 
     * @param core_ObjectReference $document
     * @param integer $id
     * 
     * @return NULL|core_ET
     */
    protected static function getLinkForHideDocument($document, $id)
    {
        if ($document->haveRightFor('single') || doc_Threads::haveRightFor('single', $dRec->threadId)) {
            $url = array(get_called_class(), 'HideDocumentInThread', $id);
            
            $attr = array();
            $attr['ef_icon'] = 'img/16/toggle2.png';
            $attr['class'] = 'settings-hide-document';
            $attr['title'] = 'Скриване на документа в нишката';
            $attr['onclick'] = 'return startUrlFromDataAttr(this, true);';
            $attr['data-url'] = toUrl($url, 'local');

            $showDocument = ht::createLink('', $url, NULL, $attr);
            
            return $showDocument;
        }
    }
    
    
    /**
     * Връща рендиран документ, който може да се използва по AJAX
     * 
     * @param integer $id
     * 
     * @return array
     */
    public static function getDocumentForAjaxShow($id)
    {
        $rec = self::fetch($id);
        
        $row = self::recToVerbal($rec);

        $document = self::getDocument($rec->id);
        
        $rowId = $document->getDocumentRowId();
        
        $html = '<td>' . $row->document . '</td>';
        
        if (Mode::is('screenMode', 'wide')) {
            $html = '<td>' . $row->created . '</td>' . $html;
        }
        
        $htmlArg = array('id' => $rowId, 'html' => $html, 'replace' => TRUE);
        
        // Добавяме CSS и JS файловете
        if ($row && $row->document instanceof core_ET) {
            $cssArr = $row->document->getArray('CSS', FALSE);
            foreach ($cssArr as $css) {
                $htmlArg['css'][] = page_Html::getFileForAppend($css);
            }
            
            $jsArr = $row->document->getArray('JS', FALSE);
            foreach ($jsArr as $js) {
                $htmlArg['js'][] = page_Html::getFileForAppend($js);
            }
        }
        
        $resObj = new stdClass();
		$resObj->func = "html";
		$resObj->arg = $htmlArg;
        
        $resStatus = array($resObj);
        
        // Да предизвикаме релоад след връщане назад
        $resObjReload = new stdClass();
        $resObjReload->func = 'forceReloadAfterBack';
        $resStatus[] = $resObjReload;
        
        // Добавя всички функции в масива, които ще се виката
        $runAfterAjaxArr = $row->document->getArray('JQUERY_RUN_AFTER_AJAX');
        if (!empty($runAfterAjaxArr)) {
            
            $runAfterAjaxArr = array_unique($runAfterAjaxArr);
            foreach ((array)$runAfterAjaxArr as $runAfterAjax) {
                $resObjAjax = new stdClass();
                $resObjAjax->func = $runAfterAjax;
                
                $resStatus[] = $resObjAjax;
            }
        }
        
        return $resStatus;
    }
    
    
    /**
     * Рендира съдържанието на скрит документ
     * 
     * @param integer $id
     * 
     * @return core_ET
     */
    protected static function renderHiddenDocument($id)
    {
        $tpl = new ET("[#/doc/tpl/DocumentsSingleLayoutHeader.shtml#]
        					<div>
                            	<b>[#docTitle#]</b>
                            </div>
                        [#/doc/tpl/DocumentsSingleLayoutFooter.shtml#]");
        
        $document = self::getDocument($id);
        $dRec = $document->rec();
        $dRow = $document->getInstance()->recToVerbal($dRec, array('state', '-single'));
        
        $iconStyle = 'background-image:url(' . sbf($document->getIcon(), '"') . ');';
        $tpl->replace($iconStyle, 'iconStyle');
        
        $docTitle = self::getDocTitle($id);
        $tpl->replace($docTitle, 'docTitle');
        
        if ($document->haveRightFor('single') || doc_Threads::haveRightFor('single', $dRec->threadId)) {
            $url = array(get_called_class(), 'ShowDocumentInThread', $id);
            
            $attr = array();
            $attr['ef_icon'] = 'img/16/toggle1.png';
            $attr['class'] = 'settings-show-document';
            $attr['title'] = 'Показване на целия документ';
            $attr['onclick'] = 'return startUrlFromDataAttr(this, true);';
            $attr['data-url'] = toUrl($url, 'local');

            $showDocument = ht::createLink('', $url, NULL, $attr);
            
            $dRow->DocumentSettings = new ET($dRow->DocumentSettings);
            $dRow->DocumentSettings->append($showDocument);
        }
        $dRow->STATE_CLASS .= ' hidden-document';
        $tpl->placeObject($dRow);
        
        $tpl->removeBlocks();
        $tpl->removePlaces();
        
        return $tpl;
    }
    
    
    /**
     * Извличане от урл, на всички параметри специфични за индивидуалните документи
     * 
     * @param array|NULL - дадено урл или текущото ако е NULL
     * @return array $arr - масив с намерените урл-параметри
     */
    public static function extractDocParamsFromUrl($url = NULL)
    {
    	$arr = array();
    	$url = (is_array($url)) ? $url : getCurrentUrl();
    	
    	// Обхождаме параметрите от масива и търсим само нужните ни
    	if(is_array($url)){
    		foreach ($url as $key => $val){
    			if(strpos($key, 'Tab') !== FALSE || $key == 'P_doclog_Documents' || $key == 'Q' || $key == 'Cid' || $key == 'P' || strpos($key, 'P_') !== FALSE || $key == 'Nid' || $key == 'Sid'){
    				$arr[$key] = $val;
    			}
    		}
    	}
    	
    	return $arr;
    }
    
    
    /**
     * 'Докосва' всички документи, които имат посочения документ за ориджин
     * 
     * @param int $originId
     * @return void
     */
    public static function touchDocumentsByOrigin($originId)
    {
    	$query = doc_Containers::getQuery();
    	$query->where(array("#originId = [#1#]", $originId));
    	$query->show('docClass,docId');
    	while($rec = $query->fetch()){
    		if(cls::load($rec->docClass, TRUE)){
    			cls::get($rec->docClass)->touchRec($rec->docId);
    		}
    	}
    }
    
    
    /**
     * Връща всички потребители абонирани за документа.
     * Споделените в документа + създателя + активаторът + харесалите документа
     * 
     * @param int $containerId        - ид на контейнер на документ
     * @param boolean $ignorePartners - да се игнорират ли потребителите с роля партньор или не
     * @return array $subscribed      - масив с абонираните потребители
     */
    public static function getSubscribedUsers($containerId, $ignorePartners = TRUE)
    {
    	// Кои са абонираните потребители
    	$subscribed = array();
    	
    	// Намират се експлицитно споделените потребители в документа
    	expect($doc = doc_Containers::getDocument($containerId));
    	$shared = keylist::toArray($doc->getShared());
    	$subscribed = $subscribed + $shared;
    	
    	// Към тях се добавя създателя на документа
    	$createdBy = $doc->fetchField('createdBy');
    	$subscribed += array($createdBy => $createdBy);
    	
    	// Ако има активатор на документа, добавя се и той
    	if($doc->getInstance()->getField('activatedBy', FALSE)){
    		if($activatedBy = $doc->fetchField('activatedBy')){
    			$subscribed += array($activatedBy => $activatedBy);
    		}
    	}
    	
    	// Намират се и потребителите харесали документа
    	$likeQuery = doc_Likes::getQuery();
    	$likeQuery->where("#containerId = {$containerId}");
    	$likeQuery->show('createdBy');
    	$likedArray = arr::extractValuesFromArray($likeQuery->fetchAll(), 'createdBy');
    	if(count($likedArray)){
    		$subscribed = $subscribed + $likedArray;
    	}
    	
    	// Игнориране на партньорите от списъка, ако е указано
    	if($ignorePartners === TRUE){
    		foreach ($subscribed as $userId => $v){
    			if(core_Users::haveRole('partner', $userId)){
    				unset($subscribed[$userId]);
    			}
    		}
    	}
    	
    	// Връщане на абонираните потребители
    	return $subscribed;
    }
    
    
    /**
     * Нотифициране на абонираните потребители за документа
     * 
     * @param int $containerId - ид на контейнера
     * @param string $msg      - съобщение за нотифициране
     * @param array|NULL $url  - съобщение за нотифициране
     * @return void
     */
    public static function notifyToSubscribedUsers($containerId, $msg, $url = NULL)
    {
    	// Намиране на споделените потребители в документа
    	$sharedUsers = doc_Containers::getSubscribedUsers($containerId);
    	if(!count($sharedUsers)) return;
    	
    	if(!isset($url)){
    		$doc = doc_Containers::getDocument($containerId);
    		$url = array($doc->getInstance(), 'single', $doc->that);
    		unset($url['ret_url']);
    	}
    	
    	// На всеки от абонираните потребители се изпраща нотификацията
    	foreach ($sharedUsers as $userId){
    		bgerp_Notifications::add($msg, $url, $userId);
    	}
    }
}