<?php



/**
 * Клас 'doc_DocumentPlg'
 *
 * Плъгин за мениджърите на документи
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_DocumentPlg extends core_Plugin
{
    
    
    /**
     * Плъгини, които да се закачат
     */
    public $loadInMvc = 'doc_LikesPlg';
    
    
    /**
     * Състояния
     */
    static $stateArr = array(
        'draft'    => 'Чернова',
        'pending'  => 'Заявка',
    	'waiting'  => 'Чакащо',
        'active'   => 'Активирано',
        'opened'   => 'Отворено',
        'closed'   => 'Приключено',
        'hidden'   => 'Скрито',
        'rejected' => 'Оттеглено',
        'stopped'  => 'Спряно',
        'wakeup'   => 'Събудено',
        'free'     => 'Освободено',
        'template' => 'Шаблон');
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        expect($mvc instanceof core_Master, 'Документите трябва да са наследници на core_Master', get_class($mvc));
        
        // Добавяме полета свързани с организацията на документооборота
        $mvc->FLD('folderId' , 'key(mvc=doc_Folders,select=title)', 'caption=Папка,input=none,column=none,silent,input=hidden');
        $mvc->FLD('threadId', 'key(mvc=doc_Threads)', 'caption=Нишка->Топик,input=none,column=none,silent,input=hidden');
        $mvc->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Нишка->Документ,input=none,column=none,oldFieldName=threadDocumentId');
        $mvc->FLD('originId', 'key(mvc=doc_Containers)',
            'caption=Нишка->Оригинал,input=hidden,column=none,silent,oldFieldName=originContainerId');
        
        // Ако липсва, добавяме поле за състояние
        if (!$mvc->fields['state']) {
            plg_State::setStateField($mvc);
        }
        
        // Ако липсва, добавяме поле за съхранение на състоянието преди reject
        if (!$mvc->fields['brState']) {
            $mvc->FLD('brState',
                cls::get('type_Enum', array('options' => self::$stateArr)),
                'caption=Състояние преди оттегляне,column=none,input=none');
        }
        
        // Добавя интерфейс за папки
        $mvc->interfaces = arr::make($mvc->interfaces);
        setIfNot($mvc->interfaces['doc_DocumentIntf'], 'doc_DocumentIntf');
        setIfNot($mvc->interfaces['acc_RegisterIntf'], 'acc_RegisterIntf');
        
        // Добавя поле за последно използване
        if(!isset($mvc->fields['lastUsedOn'])) {
            $mvc->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none');
        }
        
        // Добавяне на полета за created
        $mvc->FLD('createdOn', 'datetime(format=smartTime)', 'caption=Създаване||Created->На, notNull, input=none');
        $mvc->FLD('createdBy', 'key(mvc=core_Users)', 'caption=Създаване||Created->От||By, notNull, input=none');
        
        // Добавяне на полета за modified
        $mvc->FLD('modifiedOn', 'datetime(format=smartTime)', 'caption=Модифициране||Modified->На,input=none');
        $mvc->FLD('modifiedBy', 'key(mvc=core_Users)', 'caption=Модифициране||Modified->От||By,input=none');
        
        if (!$mvc->fields['activatedOn']) {
        	$mvc->FLD('activatedOn', 'datetime(format=smartTime)', 'caption=Активиране||Activated->На,input=none');
        }
        
        if (!$mvc->fields['activatedBy']) {
        	$mvc->FLD('activatedBy', 'key(mvc=core_Users)', 'caption=Активиране||Activated->От||By,input=none');
        }
        
        // Ако има cid и Tab, показваме детайлите
        if (Request::get('Cid') && Request::get('Tab')) {
            
            // Ако има данни, преобразуваме ги в масив
            $mvc->details = arr::make($mvc->details);
            
            // Детайлите
            $mvc->details['Send'] = 'doclog_Documents';
            $mvc->details['Open'] = 'doclog_Documents';
            $mvc->details['Download'] = 'doclog_Documents';
            $mvc->details['Forward'] = 'doclog_Documents';
            $mvc->details['Print'] = 'doclog_Documents';
            $mvc->details['Changed'] = 'doclog_Documents';
            $mvc->details['Used'] = 'doclog_Documents';
            $mvc->details['History'] = 'doclog_Documents';
        }
        
        if (Request::get('Sid', 'int')){
        	$mvc->details = arr::make($mvc->details);
        	$mvc->details['Expenses'] = 'doc_ExpensesSummary';
        }
        
        
        // Дали могат да се принтират оттеглените документи
        setIfNot($mvc->printRejected, FALSE);
        
        // Дали може да се редактират активирани документи
        setIfNot($mvc->canEditActivated, FALSE);
        
        setIfNot($mvc->canExportdoc, 'user');
        setIfNot($mvc->canForceexpenseitem, 'ceo,acc,purchase');
        setIfNot($mvc->canPsingle, 'user');
        setIfNot($mvc->pendingQueue, array());
        setIfNot($mvc->canPending, 'no_one');
        setIfNot($mvc->requireDetailForPending, TRUE);
        
        $mvc->setDbIndex('state');
        $mvc->setDbIndex('folderId');
        $mvc->setDbIndex('threadId');
        $mvc->setDbIndex('containerId');
        $mvc->setDbIndex('originId');
        
        // Закачане на плъгина за счетоводни пера, ако вече не е закачен
        $plugins = $mvc->getPlugins();
        if(!isset($plugins['acc_plg_Registry'])){
        	$mvc->load('acc_plg_Registry');
        }
        
        if ($mvc->fetchFieldsBeforeDelete) {
            $mvc->fetchFieldsBeforeDelete .= ',';
        }
        $mvc->fetchFieldsBeforeDelete = 'containerId';
    }
    
    
    /**
     * След промяна в журнала със свързаното перо
     */
    public static function on_AfterJournalItemAffect($mvc, $rec, $item)
    {
    	$listId = acc_Lists::fetchBySystemId('costObjects')->id;
    	if(keylist::isIn($listId, $item->lists)){
    		doc_ExpensesSummary::updateSummary($rec->containerId, $item, TRUE);
    	}
    }
    
    
    /**
     * Дефолт имплементация на getItemRec
     * @see acc_RegisterIntf
     */
    public static function on_AfterGetItemRec($mvc, &$res, $id)
    {
    	if(!empty($res)) return;
    	
    	$result = NULL;
    	
    	if ($rec = $mvc->fetch($id)) {
    		$row = $mvc->getDocumentRow($rec->id);
    		$num = "#" . $mvc->getHandle($rec->id);
    		
    		$result = (object)array(
    				'num'      => $num,
    				'title'    => $row->recTitle,
    				'features' => array('Папка' => doc_Folders::getVerbal($rec->folderId, 'title')),
    		);
    	}
    	
    	$res = $result;
    }
    
    
    /**
     * Дефолт имплементация на getItemInUse
     * @see acc_RegisterIntf
     */
    public static function on_AfterItemInUse($mvc, &$res, $id)
    {
    	
    }
    
    
    /**
     * 
     * 
     * @param core_Mvc $mvc
     * @param NULL|string $res
     * @param integer $id
     */
    function on_AfterGetLangFromRec($mvc, &$res, $id)
    {
        
    }
    
    
    /**
     * Изпълнява се след подготовката на единичния изглед
     * Подготвя иконата за единичния изглед
     * 
     * @param core_Mvc $mvc
     * @param object $res
     * @param object $data
     */
    function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
        $data->row->iconStyle = 'background-image:url("' . sbf($mvc->getIcon($data->rec->id), '', Mode::is('text', 'xhtml') || Mode::is('printing')) . '");';
        $data->row->LetterHead = $mvc->getLetterHead($data->rec, $data->row);

        if($data->rec->state != 'rejected'){
        	$tRec = doc_Threads::fetch($data->rec->threadId);
        	$info = tr('Счетоводното отразяване на тази стопанска операция е спряно');
        	
        	if($tRec->firstContainerId == $data->rec->containerId) {
        		if(doc_Threads::haveRightFor('startthread', $tRec)){
        			$info = tr('Счетоводното отразяване на стопанските операции в тази нишка е спряно');
        			$info .=  ". " .tr('За да го включите, натиснете') . " ";
        			$data->row->STOPPED_INFO = $info . ht::createLink('Пускане', array('doc_Threads', 'startthread', 'id' => $data->rec->threadId, 'retUrl' => TRUE), 'Наистина ли искате да включите счетоводното отразяване на всички спрени документи в нишката|*?', 'class=small-padding-icon, ef_icon=img/16/stock_data_next.png, title=Пускане на счетоводното отразяване на всички спрени документи в нишката');
        		}
        	} 
        	
        	if($data->rec->state == 'stopped' && cls::haveInterface('acc_TransactionSourceIntf', $mvc)){
        		if(empty($data->row->STOPPED_INFO)){
        			$data->row->STOPPED_INFO = $info;
        		}
        	}
        }
    }
    
    
    /**
     * Изпълнява се преди подготовката на единичния изглед
     * Пушва екшъна за принтиране в лога
     * 
     * @param core_Mvc $mvc
     * @param object $res
     * @param object $data
     */
    function on_BeforePrepareSingle($mvc, &$res, $data)
    {
        if (Request::get('Printing') && empty($data->__MID__)) {
            $data->__MID__ = doclog_Documents::saveAction(
                array(
                    'action'      => doclog_Documents::ACTION_PRINT, 
                    'containerId' => $data->rec->containerId,
                )
            );
        }
        
        $data->tabTopParam = "TabTop{$data->rec->containerId}";
    }
    
    
    /**
     * Добавя бутони
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        $retUrl = array($mvc, 'single', $data->rec->id);
        
        if (isset($data->rec->id) && $mvc->haveRightFor('reject', $data->rec) && ($data->rec->state != 'rejected')) {
            $data->toolbar->addBtn('Оттегляне', array(
                    $mvc,
                    'reject',
                    $data->rec->id
                ),
                "id=btnDelete{$data->rec->containerId},class=fright,warning=Наистина ли желаете да оттеглите документа?, row=2, order=40,title=" . tr("Оттегляне на документа"),  'ef_icon = img/16/reject.png');
        }
        
        if (isset($data->rec->id) && $mvc->haveRightFor('restore', $data->rec) && ($data->rec->state == 'rejected')) {
            $data->toolbar->removeBtn("*", (($mvc->printRejected) ? 'btnPrint' : NULL));
            $data->toolbar->addBtn('Възстановяване', array(
                    $mvc,
                    'restore',
                    $data->rec->id
                ),
                "id=btnRestore{$data->rec->containerId},warning=Наистина ли желаете да възстановите документа?,order=32,title=" . tr("Възстановяване на документа"), 'ef_icon = img/16/restore.png');
        }
        
        if (($data->rec->state != 'draft') && ($data->rec->state != 'rejected')) {
            
        	// Бутон за добавяне на коментар
            if(isset($data->rec->threadId) && doc_Comments::haveRightFor('add', (object)array('originId' => $data->rec->containerId, 'threadId' => $data->rec->threadId, 'folderId' => $data->rec->folderId))){
            	// Бутон за създаване на коментар
            	$data->toolbar->addBtn('Коментар', array(
            			'doc_Comments',
            			'add',
            			'originId' => $data->rec->containerId,
            			'ret_url'=> $retUrl
            	),
            			'onmouseup=saveSelectedTextToSession("' . $mvc->getHandle($data->rec->id) . '")', 'ef_icon = img/16/comment_add.png,title=' . tr('Добавяне на коментар към документа'));
            }
            
            // Добавяме бутон за създаване на задача
            if (cal_Tasks::haveRightFor('add') && $data->rec->containerId) {
                
                $doc = doc_Containers::getDocument($data->rec->containerId);
                
                if ($doc->haveRightFor('single')) {
                    $data->toolbar->addBtn('Задача', array(
                            'cal_Tasks',
                            'AddDocument',
                            'foreignId' => $data->rec->containerId,
                            'ret_url'=> $retUrl
                    ), 'ef_icon = img/16/task-normal.png, title=' . tr('Създаване на задача от документа'));
                }
            }
        } else {
            //TODO да се "премахне" и оптимизира
            if($data->rec->state == 'draft' || ($data->rec->state == 'rejected' && $data->rec->brState == 'draft') || ($data->rec->state == 'rejected' && $data->rec->brState != 'draft' && $mvc->printRejected === FALSE)){
            	$data->toolbar->removeBtn('btnPrint');
            }
        }
        
        if($mvc->haveRightFor('list') && $data->rec->state != 'rejected') { 
        	
        	// По подразбиране бутона всички се показва на втория ред на тулбара
        	setIfNot($mvc->allBtnToolbarRow, 3);
        	
        	$title = $mvc->getTitle();
        	$title = tr($title);
        	$title = mb_strtolower($title);
        	
            // Бутон за листване на всички обекти от този вид
            $data->toolbar->addBtn('Всички', array(
                    $mvc,
                    'list',
                    'ret_url'=>$retUrl
                ),
                "class=btnAll,ef_icon=img/16/application_view_list.png, order=18, row={$mvc->allBtnToolbarRow}, title=" . tr('Всички') . ' ' . $title);

        }
        
        if ($mvc->haveRightFor('single', $data->rec) && !core_Users::haveRole('partner')) {
            $historyCnt = log_Data::getObjectCnt($mvc, $data->rec->id);
            
            if ($historyCnt) {
                $data->toolbar->addBtn("История|* ({$historyCnt})", doclog_Documents::getLinkToSingle($data->rec->containerId, doclog_Documents::ACTION_HISTORY),
                "id=btnHistory{$data->rec->containerId}, row=3, order=19.5,title=" . tr('История на документа'),  'ef_icon = img/16/book_open.png');
            }
        }
        
        if ($mvc->haveRightFor('exportdoc', $data->rec)) {
            Request::setProtected(array('classId', 'docId'));
            $data->toolbar->addBtn("Сваляне", toUrl(array('bgerp_E', 'export', 'classId' => $mvc->getClassId(), 'docId' => $data->rec->id)),
                            "id=btnDownloadDoc{$data->rec->containerId}, row=2, order=19.6,title=" . tr('Сваляне на документа'),  'ef_icon = img/16/down16.png');
            Request::removeProtected(array('classId', 'docId'));
        }
        
        // Дали документа може да бъде разоден обект
        if ($mvc->haveRightFor('forceexpenseitem', $data->rec)) {
        	$data->toolbar->addBtn('Разходен обект', array($mvc, 'forceexpenseitem', $data->rec->id),
        			"warning=Наистина ли искате да направите документа разходен обект?, row=3,title=" . tr("Маркиране на документа като разходен обект"),  'ef_icon = img/16/pin.png');
        }
        
        if ($data->rec->threadId && ($tRec = doc_Threads::fetch($data->rec->threadId))) {
            if ($tRec->firstContainerId == $data->rec->containerId) {
                if (log_System::haveRightFor('list')) {
                    $data->toolbar->addBtn('Логове', array('log_Data', 'list', 'class' => 'doc_Threads', 'object' => $data->rec->threadId), 'ef_icon=img/16/memo.png, title=Разглеждане на логовете на нишката, order=19, row=3');
                }
            }
        }
    }
    
    
    /**
     * Добавя бутон за показване на оттеглените записи
     */
    function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if(Request::get('Rejected')) {
            $data->toolbar->removeBtn('*');
            $data->toolbar->addBtn('Всички', array($mvc), 'id=listBtn', 'ef_icon = img/16/application_view_list.png');
        } else {
            if(isset($data->rejQuery)) {
                $data->rejectedCnt = $data->rejQuery->count();

                if($data->rejectedCnt) {
                    $data->rejQuery->orderBy('#modifiedOn', 'DESC');
                    $data->rejQuery->limit(1);
                    $lastRec = $data->rejQuery->fetch();
                    $color = dt::getColorByTime($lastRec->modifiedOn);
                    $curUrl = getCurrentUrl();
                    $curUrl['Rejected'] = 1;
                    if(isset($data->pager->pageVar)) {
                        unset($curUrl[$data->pager->pageVar]);
                    }
                    $data->toolbar->addBtn("Кош|* ({$data->rejectedCnt})", $curUrl, "id=binBtn,class=btn-bin fright,order=50,row=2", "ef_icon = img/16/bin_closed.png,style=color:#{$color};" );
                }
            }
        }
    }
    
    
    /**
     * Добавя към титлата на списъчния изглед "[оттеглени]"
     */
    function on_AfterPrepareListTitle($mvc, &$res, $data)
    {
        if(Request::get('Rejected')) {
            $data->title = new ET('[#1#]', tr($data->title));
            $data->title->append("&nbsp;<span class='state-rejected'>&nbsp;[" . tr('оттеглени') . "]&nbsp;</span>");
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     * 
     * @param core_Manager $mvc
     * @param stdObject $data
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $viewAccess = FALSE;
        
        if ($mvc->haveRightFor('viewpsingle')) {
            $viewAccess = TRUE;
        }
        
        doc_Threads::restrictAccess($data->query, NULL, $viewAccess);
    }
    
    
    /**
     * 
     * 
     * @param core_Master $mvc
     * @param NULL|array $res
     * @param integer|stdClass $id
     */
    public static function on_AfterGetSingleUrlArray($mvc, &$res, $id)
    {
        if (!isset($res) || (is_array($res) && empty($res))) {
            if ($mvc->haveRightFor('viewpsingle', $id)) {
                
                if (is_object($id)) {
                    $id = $id->id;
                }
                
                $res = $mvc->GetUrlWithAccess($id);
            }
        }
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal(&$invoker, &$row, &$rec, $fields = array())
    {
        $row->ROW_ATTR['class'] .= " state-{$rec->state}";
        $row->STATE_CLASS .= " state-{$rec->state}";
        
        $row->modifiedDate = dt::mysql2verbal($rec->modifiedOn, 'd.m.Y');
        $row->createdDate = dt::mysql2verbal($rec->createdOn, 'd.m.Y');
        
        if($fields['-single']) {
            if(!$row->ident) {
                $row->ident = '#' . $invoker->getHandle($rec->id);
            }
            
            if(!$row->singleTitle) {
                $row->singleTitle = tr($invoker->singleTitle);
            }
            
            // Ако документа е скрит и е оттеглен, показваме от кого
            if(doc_HiddenContainers::isHidden($rec->containerId)){
            	if($rec->state == 'rejected') {
            		$tpl = new ET(tr('|* |от|* [#user#] |на|* [#date#]'));
            		$row->state .= $tpl->placeArray(array('user' => crm_Profiles::createLink($rec->modifiedBy), 'date' => dt::mysql2Verbal($rec->modifiedOn)));
            	}
            }
            
            if (Mode::is('screenMode', 'narrow')) {
            	unset($row->state);
            }
        }
        
        if($fields['-list']){
            if($rec->folderId) {
        	    $row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
            
        	    if($invoker->hasPlugin('plg_RowTools2')){
        	    	if(doc_Folders::haveRightFor('single', $rec->folderId)) {
        	    		core_RowToolbar::createIfNotExists($row->_rowTools);
        	    
        	    		$folderRec = doc_Folders::fetch($rec->folderId, 'title,openThreadsCnt');
        	    		$folderTitle = doc_Folders::getVerbal($folderRec, 'title');
        	    		$icon = ($folderRec->openThreadsCnt) ? 'img/16/folder-g.png' : 'img/16/folder-y.png';
        	    
        	    		$row->_rowTools->addLink(tr("Папка"), array('doc_Threads', 'list', 'folderId' => $rec->folderId), array('order' => 19, 'ef_icon' => $icon, 'title' => "Отваряне на папка|* \"{$folderTitle}\""));
        	    	}
        	    }
            }
        }
    }
    
    
    /**
     * Преди подготовка на данните за табличния изглед правим филтриране
     * на записите, които са (или не са) оттеглени и сортираме от нови към стари
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        if($data->query) {
            if(Request::get('Rejected')) {
                $data->query->where("#state = 'rejected'");
            } else {
                $data->rejQuery = clone($data->query);
                $data->query->where("#state != 'rejected' OR #state IS NULL");
                $data->rejQuery->where("#state = 'rejected'");
            }
           
            $data->query->orderBy('#createdOn', 'DESC');
       }
        
    }
    
    
    /**
     * 
     * 
     * @param core_Master $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_BeforePrepareListRows($mvc, &$res, $data)
    {
        Mode::push('forListRows', TRUE);
    }
    
    
    /**
     * 
     * 
     * @param core_Master $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareListRows($mvc, &$res, $data)
    {
        Mode::pop('forListRows');
    }
    
    
    /**
     * Изпълнява се преди запис
     */
    function on_BeforeSave($mvc, $id, $rec, $fields = NULL)
    {
        // Ако създаваме нов документ и ...
        if(!$rec->id) {
        	
            // ... този документ няма ключ към папка и нишка, тогава
            // извикваме метода за рутиране на документа
            if(!isset($rec->folderId) || !isset($rec->threadId)) {
                $mvc->route($rec);
            }
            // ... този документ няма ключ към контейнер, тогава 
            // създаваме нов контейнер за документите от този клас 
            // и записваме връзка към новия контейнер в този документ
            if(!isset($rec->containerId)) {
                $rec->containerId = doc_Containers::create($mvc, $rec->threadId, $rec->folderId, $rec->createdOn, $rec->createdBy);
            }
            
            // Задаваме началното състояние по подразбиране
            if (!$rec->state) {
                $rec->state = $mvc->firstState ? $mvc->firstState : 'draft';
            }
            
            // Задаваме стойностите на created полетата
            if (!isset($rec->createdBy)) {
                $rec->createdBy = Users::getCurrent() ? Users::getCurrent() : 0;
            }
            
            if (!isset($rec->createdOn)) {
                $rec->createdOn = dt::verbal2Mysql();
            }
        }
        
        // Задаваме стойностите на полетата за последно модифициране
        $rec->modifiedBy = Users::getCurrent() ? Users::getCurrent() : 0;
        $rec->modifiedOn = dt::verbal2Mysql();
    }
    
    
    /**
     * Изпълнява се след запис на документ.
     * Ако е може се извиква обновяването на контейнера му
     */
    static function on_AfterSave($mvc, &$id, $rec, $fields = NULL)
    {
        $fields = arr::make($fields, TRUE);
        try {
            
            // Опитваме се да запишем файловете от документа в модела
            doc_Files::saveFile($mvc, $rec);    
        } catch (core_exception_Expect $e) {
            
            reportException($e);
            
            // Ако възникне грешка при записването
            $mvc->logErr("Грешка при записване на файла", $id);
        }
        
        // Изтрива от кеша html представянето на документа
        // $key = 'Doc' . $rec->id . '%';
        // core_Cache::remove($mvc->className, $key);
        
        // Намира контейнера на документа
        $containerId = $rec->containerId ? $rec->containerId : $mvc->fetch($rec->id)->containerId;
            
        // Възстановяваме (ако е необходимо) нишката ПРЕДИ да създадем/обновим контейнера
        // Това гарантира, че абонатите на оттеглени нишки все пак ще получат нотификация за
        // новопристигналия документ
        if ($rec->threadId && $rec->state != 'rejected') {
            doc_Threads::restoreThread($rec->threadId);
        }
       
        // Ако е намерен контейнера - обновява го
        if($containerId) {
            
            $updateAll = TRUE;
            
            if ($fields && !isset($fields['modifiedOn'])) {
                $updateAll = FALSE;
            }

            doc_Containers::update($containerId, $updateAll);
        }
        
        // Само при активиране и оттегляне, се обновяват използванията на документи в документа
        if($rec->state == 'active' || $rec->state == 'rejected' && !Mode::is('MassImporting')){
            
            $usedDocuments = $mvc->getUsedDocs($rec->id);
            foreach((array)$usedDocuments as $usedCid){
                $uDoc = doc_Containers::getDocument($usedCid);
                
                if($rec->state == 'rejected'){
                    doclog_Used::remove($containerId, $usedCid);
                    $msg = 'Премахнато използване';
                } else {
                    doclog_Used::add($containerId, $usedCid);
                    $msg = 'Използване на документа';
                }
                
                $uDoc->instance->logRead($msg, $uDoc->that);
            }
        }
        
        if ($rec->pendingSaved === TRUE) {
            $rec->pendingSaved = FALSE;
        	$mvc->pendingQueue[$rec->id] = $rec;
        	$mvc->invoke('AfterSavePendingDocument', array($rec));
        }
    }
    
    
    /**
     * Изпълнява се при шътдаун
     */
    public static function on_Shutdown($mvc)
    {
    	if(count($mvc->pendingQueue)) {
    		foreach ($mvc->pendingQueue as $rec){
    			
    			// Подготвяме потребителите, които ще получат нотификация за заявката
    			if ($rec->folderId) {
    				$fRec = doc_Folders::fetch($rec->folderId);
    				$notifyArr = array($fRec->inCharge => $fRec->inCharge);
    				 
    				// Настройките на пакета
    				$notifyPendingConf = doc_Setup::get('NOTIFY_PENDING_DOC');
    				if ($notifyPendingConf == 'no') {
    					$notifyArr = array();
    				} elseif ($notifyPendingConf == 'yes') {
    					$notifyArr += keylist::toArray($fRec->shared);
    				}
    			
    				// Персоналните настройки на потребителите
    				$pKey = crm_Profiles::getSettingsKey();
    				$pName = 'DOC_NOTIFY_PENDING_DOC';
    			
    				$settingsNotifyArr = core_Settings::fetchUsers($pKey, $pName);
    				 
    				if ($settingsNotifyArr) {
    					foreach ($settingsNotifyArr as $userId => $uConfArr) {
    						if ($uConfArr[$pName] == 'no') {
    							unset($notifyArr[$userId]);
    						} elseif ($uConfArr[$pName] == 'yes') {
    							if ($mvc->haveRightFor('single', $rec, $userId)) {
    								$notifyArr[$userId] = $userId;
    							}
    						}
    					}
    				}
    			}
    			 
    			$cu = core_Users::getCurrent();
    			unset($notifyArr[$cu]);
    			$urlArr = array($mvc, 'single', $rec->id);
    			
    			// Ако документа е станал чакащ, генерира се събитие
    			if($rec->state == 'pending'){
    				 
    				// Нотифицираме потребителите за заявката
    				$currUserNick = core_Users::getCurrent('nick');
    				$currUserNick = type_Nick::normalize($currUserNick);
    				 
    				$docRow = $mvc->getDocumentRow($rec->id);
    				$docTitle = $docRow->recTitle ? $docRow->recTitle : $docRow->title;
    				$folderTitle = doc_Folders::getTitleById($rec->folderId, FALSE);
    			
    				$message = "{$currUserNick} |създаде заявка за|* \"|{$docTitle}|*\" |в папка|* \"{$folderTitle}\"";
    				
    				$pSettingsKey = crm_Profiles::getSettingsKey();
    				$prop = 'DOC_STOP_NOTIFY_NEW_DOC_TYPE';
    				$pSettingsNotifyArr = core_Settings::fetchUsers($pSettingsKey, $prop);
    				
    				foreach ($notifyArr as $uId) {
    				    $uSelArr = $pSettingsNotifyArr[$uId];
    				    
    				    // Ако съответния потребител не иска да получава нотификация за документа, да не се праща
    				    if ($uSelArr && ($propValStr = $uSelArr[$prop]) && (type_Keylist::isIn($mvc->getClassId(), $propValStr))) continue;
    				    
    					bgerp_Notifications::add($message, $urlArr, $uId);
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * Ако в документа няма код, който да рутира документа до папка/тред,
     * долния код, рутира документа до "Несортирани - [заглавие на класа]"
     */
    function on_AfterRoute($mvc, &$res, $rec)
    {
        // Ако имаме контейнер, но нямаме тред - определяме треда от контейнера
        if($rec->containerId && !$rec->threadId) {
            $tdRec = doc_Containers::fetch($rec->containerId);
            $rec->threadId = $tdRec->threadId;
        }
        
        // Ако имаме тред, но нямаме папка - определяме папката от контейнера
        if($rec->threadId && !$rec->folderId) {
            $thRec = doc_Threads::fetch($rec->threadId);
            $rec->folderId = $thRec->folderId;
        }
        
        // Ако нямаме папка - форсираме папката по подразбиране за този клас
        if(!$rec->folderId) {
            $rec->folderId = $mvc->getDefaultFolder();
        }
        
        // Ако нямаме тред - създаваме нов тред в тази папка
        if(!$rec->threadId) {
            $rec->threadId = doc_Threads::create($rec->folderId, $rec->createdOn, $rec->createdBy);
        }
        
        // Ако нямаме контейнер - създаваме нов контейнер за 
        // този клас документи в определения тред
        if(!$rec->containerId) {
            $rec->containerId = doc_Containers::create($mvc, $rec->threadId, $rec->folderId, $rec->createdOn, $rec->createdBy);
        }
    }
    
    
    /**
     * Дефолт имплементация на метода $doc->getDefaultFolder()
     *
     * Ако $mvc->defaultFolder != FALSE, тогава връща папка 'куп' с име - $mvc->defaultFolder или заглавието на класа
     * Ако $mvc->defaultFolder === FALSE или нямаме достъп до папката 'куп', тогава се връща основната папка за всеки потребител
     * 
     * @param core_Mvc $mvc
     * @param int $folderId
     * @param int $userId
     * @param boolean $bForce FALSE - не прави опит да създаде папката по подразбиране, в случай
     *                                че тя не съществува
     */
    function on_AfterGetDefaultFolder($mvc, &$folderId, $userId = NULL, $bForce = TRUE)
    {
        if (!$folderId) {
            if($mvc->defaultFolder !== FALSE) {
                $unRec = new stdClass();
                $unRec->name = $mvc->defaultFolder ? $mvc->defaultFolder : $mvc->title;
                
                $folderId = doc_UnsortedFolders::forceCoverAndFolder($unRec, $bForce);
            }
			
            if(!$userId) {
                $userId = core_Users::getCurrent();
            }

            // Ако текущия потребител няма права за тази папка, или тя не е определена до сега,
            // То 'Unsorted' папката е дефолт папката на потребителя, ако има потребител
            if((!$folderId || !doc_Folders::haveRightFor('single', $folderId)) && $userId) {
                $folderId = doc_Folders::getDefaultFolder($userId); 
            }
        }
    }
    
    
    /**
     * Ако няма метод в документа, долния код сработва за да осигури титла за нишката
     */
    function on_AfterGetDocumentTitle($mvc, &$res, $rec, $escaped = TRUE)
    {
        if(!$res) {
            $res = $mvc->getRecTitle($rec, $escaped);
        }
    }
    
    
    /**
     * Когато действието е предизвикано от doc_Thread изглед, тогава
     * връщането е към single изгледа, който от своя страна редиректва към
     * треда, при това с фокус на документа
     */
    function on_BeforePrepareRetUrl($mvc, &$res, $data)
    {
        $retUrl = getRetUrl();
        
        if($retUrl['Ctr'] == 'doc_Containers' && is_a($mvc, 'core_Master') && $data->form->rec->id > 0) {
            $data->retUrl = toUrl(array($mvc, 'single', $data->form->rec->id));
            
            return FALSE;
        }
    }
    
    
    
    /**
     * Ескейпва хендъла и връща стринг, който ще се използва за id на ROW в нишките
     * 
     * @param core_Master $mvc
     * @param string $res
     * @param integer $id
     */
    function on_AfterGetDocumentRowId($mvc, &$res, $id)
    {
        $handle = $mvc->getHandle($id);
        $res = preg_replace('/\!/i', '_', $handle);
    }
    
    
    /**
     * Смяна статута на 'rejected'
     *
     * @return core_Redirect
     */
    function on_BeforeAction($mvc, &$res, $action)
    {
        $notAccessStatusMsg = "|Предишната страница не може да бъде показана, поради липса на права за достъп";
        if ($action == 'single' && !(Request::get('Printing')) && !Mode::is('dataType', 'php')) {
        	expect($id = Request::get('id', 'int'));
            
            expect($rec = $mvc->fetch($id), $id);
            
            // Изтриваме нотификацията, ако има такава, свързани с този документ
            $url = array($mvc, 'single', 'id' => $id);
            bgerp_Notifications::clear($url);
            
            $hnd = $mvc->getDocumentRowId($rec->id);
            
            if($rec->threadId) {
                if(doc_Threads::haveRightFor('single', $rec->threadId)) {
                    
                    // Ако в момента не се скрива или показва - показва документа
                    if (!Request::get('showOrHide') && !Request::get('afterReject')) {
                        doc_HiddenContainers::showOrHideDocument($rec->containerId, FALSE, TRUE);
                    }
                    
                    $handle = $mvc->getHandle($rec->id);
                    $url = array('doc_Containers', 'list', 'threadId' => $rec->threadId, 'docId' => $handle, 'Cid' => Request::get('Cid'), '#' => $hnd);
                    
                    // Добавяме към новото урл специфичните урл параметри за документите 
                    $url += doc_Containers::extractDocParamsFromUrl();
                    
                    if(isset($url['Tab'])){
                    	$url['#'] = 'detailTabs';
                    }
                    
                    if(isset($url["TabTop{$rec->containerId}"])){
                    	$url['#'] = "detailTabTop{$rec->containerId}";
                    }
                    
                    $res = new Redirect($url);
                    
                    return FALSE;
                } else {
                	
                	// Ако нямаме достъп до нишката, да се изчистят всички нотификации в нея
                	$customUrl = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
                	bgerp_Notifications::clear($customUrl);
                	
                	// Ако е инсталиран пакета за работа в партньори
                	if(core_Packs::isInstalled('colab') && core_Users::haveRole('partner')){
                		
                		// И нишката може да бъде видяна от партньора
                		$threadRec = doc_Threads::fetch($rec->threadId);
                		
                		// Редиректваме към нишката на документа
                		if(colab_Threads::haveRightFor('single', $threadRec)){
                			
                			// Променяме урл-то да сочи към документа във видимата нишка
                			$url = array('colab_Threads', 'single', 'threadId' => $rec->threadId, '#' => $hnd);
                			$res = new Redirect($url);
                			
                			return FALSE;
                		}
                	}
                }
            }
        }
       
        // Екшън за избор на действие при оттегляне
        if($action == 'selectaction'){
        	$id  = Request::get('id', 'int');
        	$rec = $mvc->fetch($id);
        	$mvc->requireRightFor('selectaction', $rec);
        	
        	// Подготовка на формата
        	$form = cls::get('core_Form');
        	$form->setAction(array($mvc, 'selectaction', $rec->id));
        	$form->title = 'Избор на действие при оттегляне на нишка|* <b>' . doc_Threads::getThreadTitle($rec->threadId) . "</b>";
        	
        	$form->info = new core_ET(tr("|*[#stopBtn#] |на счетоводното отразяване на операциите в тази нишка|*<br> [#rejBtn#] |на всички документи от тази нишка|*"));
        	$form->info->append(ht::createBtn('Спиране', array($mvc, 'selectAction', $rec->id, 'type' => 'stopped'), FALSE, FALSE, 'ef_icon=img/16/stop.png'), 'stopBtn');
        	$form->info->append(ht::createBtn('Оттегляне', array($mvc, 'selectAction', $rec->id, 'type' => 'rejected'), FALSE, FALSE, 'ef_icon=img/16/reject.png'), 'rejBtn');
        	
        	// Ако има избрано действие в урл-то
        	$type = Request::get('type', 'enum(stopped,rejected)');
        	if(isset($type)){
        		
        		// Спиране на документите
        		doc_Threads::stopDocuments($rec->threadId);
        		
        		// Редирект
        		if($type == 'stopped'){
        			redirect(array($mvc, 'single', $rec->id));
        		} else {
        			redirect(array($mvc, 'reject', $rec->id, 'stop' => 1));
        		}
        	}
        	
        	$form->toolbar->addBtn('Отказ', array($mvc, 'single', $rec->id), 'ef_icon = img/16/close-red.png, title = Прекратяване на действията');
        	$res = $form->renderHtml();
        	
        	return FALSE;
        }
        
        if ($action == 'reject') {
            
            $id  = Request::get('id', 'int');
            $rec = $mvc->fetch($id);
           
            if (isset($rec->id) && $rec->state != 'rejected' && $mvc->haveRightFor('reject', $rec)) {
            	$tRec = doc_Threads::fetch($rec->threadId);
            	
            	// Ако потребителя трябва да избере действие преди оттегляне
            	if($mvc->haveRightFor('selectaction', $rec) && !Request::get('stop', 'int')){;
            		$res = Request::forward(array('Ctr' => $mvc, 'Act' => 'selectaction', 'id' => $rec->id));
            		
            		return FALSE;
            	}
            	
                // Оттегляме документа + нишката, ако се налага
                if ($mvc->reject($rec)) {
                   
                    // Ако оттегляме първия документ в нишка, то оттегляме цялата нишка
                    if ($tRec->firstContainerId == $rec->containerId) {
                        $bSuccess = doc_Threads::rejectThread($rec->threadId);
                    }
                    
                    doc_Prototypes::sync($rec->containerId);
                    doc_HiddenContainers::showOrHideDocument($rec->containerId, TRUE);
                    $mvc->logInAct('Оттегляне', $rec);
                }
            }
            
            try {
                // Обновяваме споделените на нишката, да сме сигурни, че данните ще са актуални
                $threadRec = doc_Threads::fetch($rec->threadId);
                $threadRec->shared = keylist::fromArray(doc_ThreadUsers::getShared($rec->threadId));
                doc_Threads::save($threadRec, 'shared');
            } catch (core_exception_Expect $e) {
                // Да не прекъсва при липса на threadId
            }
           
            // Пренасочваме контрола
            if (!$res = getRetUrl()) {
            	$res = $mvc->getSingleUrlArray($id);
            	
            	if (empty($res)) {
            		$res = array('bgerp_Portal', 'show');
            		core_Statuses::newStatus($notAccessStatusMsg, 'warning');
            	}
            }
            
            $res['afterReject'] = 1;
            
            $res = new Redirect($res); //'OK';

            return FALSE;
        }
        
        if ($action == 'restore') {
            
            $id  = Request::get('id', 'int');
            $rec = $mvc->fetch($id);
            
            if ($rec->state == 'rejected' && $mvc->haveRightFor('restore', $rec)) {
                // Възстановяваме документа + нишката, ако се налага
                if ($mvc->restore($rec)) {
                    $tRec = doc_Threads::fetch($rec->threadId);
                    
                    // Ако възстановяваме първия документ в нишка, то възстановяваме цялата нишка
                    if ($tRec->firstContainerId == $rec->containerId) {
                        doc_Threads::restoreThread($rec->threadId);
                    }
                    
                    $mvc->logInAct('Възстановяване', $rec);
                }
            }
            
            // Пренасочваме контрола
            if (!$res = getRetUrl()) {
            	$res = $mvc->getSingleUrlArray($id);
            }
            
            $res = new Redirect($res); //'OK';
            
            return FALSE;
        }
        
        // При форсиране на документа като разходен обект
        if($action == 'forceexpenseitem'){
        	$mvc->requireRightFor('forceexpenseitem');
        	expect($id  = Request::get('id', 'int'));
        	expect($rec = $mvc->fetch($id));
        	
        	// Форсираме документа като разходен обект
        	$listId = acc_Lists::fetchBySystemId('costObjects')->id;
        	acc_Items::force($mvc->getClassId(), $rec->id, $listId);
        	
        	// Създаване на празен запис в кеш таблицата за разходите
        	doc_ExpensesSummary::save((object)array('containerId' => $rec->containerId));
        	$mvc->logInAct('Документа става разходно перо', $rec);
        	
        	if (!$res = getRetUrl()) {
        		$res = array($mvc, 'single', $id);
        	}
        	
        	$res = new Redirect($res);
        	
        	return FALSE;
        }
        
    	// Екшън за правене на документ от чернова -> чакащ и обратно
        if($action == 'changepending'){
        	$mvc->requireRightFor('pending');
        	expect($id  = Request::get('id', 'int'));
        	expect($rec = $mvc->fetch($id));
        	$mvc->requireRightFor('pending', $rec);
        	
        	$oldState = $rec->state;
        	$newState = ($oldState == 'pending') ? 'draft' : 'pending';
        	$log = ($oldState == 'pending') ? 'Документът се връща в чернова' : 'Документът става на заявка';
        	
        	$rec->state = $newState;
        	$rec->brState = $oldState;
        	$rec->pendingSaved = TRUE;
        	
        	$mvc->save($rec, 'state,brState');
        	
        	$mvc->touchRec($rec->id);
        	$mvc->logInAct($log, $rec);
        	
        	if (!$res = getRetUrl()) {
        	    $res = $mvc->getSingleUrlArray($rec->id);
        	}
        	
        	if (empty($res)) {
        	    
        	    $res = array('bgerp_Portal', 'show');
        	    core_Statuses::newStatus($notAccessStatusMsg, 'warning');
        	}
        	
        	$res = new Redirect($res);
        	 
        	return FALSE;
    	}
    	
    	// Сингъл изглед, който се показва, ако няма достъп до сингъла, но има достъп до източника, където е цитиран
    	if ($action == 'psingle') {
    	    
            // Създаваме обекта $data
            $data = new stdClass();
            
            // Трябва да има id
            expect($id = Request::get('id', 'int'));
            
            // Трябва да има $rec за това $id
            if(!($data->rec = $mvc->fetch($id))) { 
                // Имаме ли въобще права за единичен изглед?
                $mvc->requireRightFor('Psingle');
            }
            
            $linkToSingle = array($mvc, 'single', $data->rec->id);
            
            expect($data->rec, $data, $id, Request::get('id', 'int'));
            
            // Ако има права за сингъла, редиректваме директно там
            if ($mvc->haveRightFor('single', $data->rec)) {
                
                $res = new Redirect($linkToSingle);
                
                return FALSE;
            }
            
            // Трябва да има права за сингъл на оригиналния документ
            Request::setProtected('pUrl');
            
            expect($pSingle = Request::get('pUrl'));
            
            list($clsId, $recId, $docId, $fromList) = explode('_', $pSingle);
            
            expect(cls::load($clsId, TRUE));
            
            if ($clsId == $mvc->getClassId() && ($docId == $recId)) {
                
                expect(is_numeric($docId));
                
                // Трябва да има съответните права
                $mvc->requireRightFor('viewpsingle', $docId);
                
                if ($fromList) {
                    // Трябва да може да се вижда документа
                    $nQuery = $mvc->getQuery();
                    $nQuery->where($docId);
                    doc_Threads::restrictAccess($nQuery, NULL, TRUE);
                    expect($nQuery->fetch());
                }
            } else {
                
                // Ако линка сочи към документа, който е използва в другия документ, трябва да има права за сингъла
                
                $clsInst = cls::get($clsId);
                if ($clsInst instanceof core_Master) {
                    $clsInst->requireRightFor('single', $recId);
                } elseif ($clsInst instanceof core_Detail) {
                    $clsRec = $clsInst->fetch($recId);
                    $clsInst->Master->requireRightFor('single', $clsRec->{$clsInst->masterKey});
                }
                
                // Очакваме документа да съществува в източника
                expect($clsInst->checkDocExist($recId, $data->rec->containerId));
            }
            
            $modeAllowedContainerIdName = $mvc->getAllowedContainerName();
            
            $allowedCidArr = Mode::get($modeAllowedContainerIdName);
            
            if (!isset($allowedCidArr)) {
                $allowedCidArr = array();
            }
            
            $allowedCidArr[$data->rec->containerId] = $data->rec->containerId;
            
            Mode::setPermanent($modeAllowedContainerIdName, $allowedCidArr);
            
            $res = new Redirect($linkToSingle);
            
            return FALSE;
    	}
    }
    
    
    /**
     * Метод по подразбиране за проверка дали документа съществува в източника
     * За pSingle
     * 
     * @param core_Manager $mvc
     * @param boolean|NULL $res
     * @param integer $recId
     * @param integer $docId
     */
    public static function on_AfeterCheckDocExist($mvc, &$res, $recId, $docId)
    {
        $rec = $mvc->fetch($rec->id);
        
        if ($rec->containerId = $docId) {
            $res = TRUE;
        }
    }
    
    
    /**
     * Оттегляне на документ
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param int|stdClass $id
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        $rec = $id;
        if (!is_object($rec)) {
            $rec = $mvc->fetch($id);
        }
        
        if ($rec->state == 'rejected') {
            return;
        }
        
        $rec->brState = $rec->state;
        $rec->state = 'rejected';
        
        $res = static::updateDocumentState($mvc, $rec);
        doc_Threads::setModification($rec->threadId);
        
        doc_Files::recalcFiles($rec->containerId);
    }
    
    
    /**
     * Възстановяване на оттеглен документ
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param int $id
     */
    public static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
    {
        $rec = $id;
        if (!is_object($rec)) {
            $rec = $mvc->fetch($id);
        }
        
        if ($rec->state != 'rejected') {
            return FALSE;
        }
        
        $rec->state = $rec->brState;
        $rec->brState = 'rejected';
        
        $res = static::updateDocumentState($mvc, $rec);
        doc_Threads::setModification($rec->threadId);
        
        doc_Files::recalcFiles($rec->containerId);
    }
    
	
    /**
     * След изтриване на запис
     */
    protected static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
        foreach ($query->getDeletedRecs() as $id => $rec) {
            doc_Files::deleteFilesForContainer($rec->containerId);
        }
    }
    


    /**
     * Запис на състоянието на документ в БД
     *
     * Използва се от @link doc_DocumentPlg::on_AfterReject() и
     * @link doc_DocumentPlg::on_AfterRestore()
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @return boolean
     */
    protected static function updateDocumentState($mvc, $rec)
    {
        if (!$mvc->save($rec, 'state, brState, containerId, modifiedOn, modifiedBy')) {

            return FALSE;
        }
        
        // Ако състоянието е било чернова, не е нужно да се минава от там,
        // защото не е добавена нотификация и няма нужда да се чисти
        if ($rec->brState != 'draft') {
            
            // Премахваме този документ от нотификациите
            $keyUrl = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
            bgerp_Notifications::setHidden($keyUrl, $rec->state == 'rejected' ? 'yes':'no');
        
            // Премахваме документа от "Последно"
            bgerp_Recently::setHidden('document', $rec->containerId, $rec->state == 'rejected' ? 'yes':'no');
        }
        
        return TRUE;
    }
    
    
    /**
     * Връщана документа
     */
    function on_AfterGetHandle($mvc, &$hnd, $id)
    {
        if (is_object($id)) {
            $id = $id->id;
        }

        if(!$hnd) {
            $hnd = $mvc->abbr . $id;
        }
    }
    
    
    /**
     * Реализация по подразбиране на doc_DocumentIntf::fetchByHandle()
     * 
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @param array $parsedHandle
     */
    public static function on_AfterFetchByHandle($mvc, &$rec, $parsedHandle)
    {
        if (empty($rec)) {
            $rec = $mvc::fetch($parsedHandle['id']);
        }
    }
    
    
    function on_AfterGetContainer($mvc, &$res, $id)
    {
        $classId = core_Classes::getId($mvc);
        
        if (is_object($id)) {
            $id = $id->id;
        }
        
        $res = doc_Containers::fetch("#docId = {$id} AND #docClass = {$classId}");
    }
    
    
    /**
     * Връща линк към документа
     */
    function on_AfterGetLink($mvc, &$link, $id, $maxLength = FALSE, $attr = array())
    {
        $iconStyle = 'background-image:url(' . sbf($mvc->getIcon($id), '') . ');';
        $url       = $mvc->getSingleUrlArray($id);
        if($attr['Q']) {
            $url['Q'] = $attr['Q'];
            unset($attr['Q']);
        }
        
        $handle    = $mvc->getHandle($id);
        $rec       = $mvc->fetch($id);

        if($maxLength > 0) {
        	$row = $mvc->getDocumentRow($id);
            $row->title = "#{$handle} - " . str::limitLen($row->title, $maxLength);
        } elseif($maxLength === 0) {
        	$row = new stdClass();
            $row->title = "#{$handle}";
        } else {
        	$row = $mvc->getDocumentRow($id);
        }

        $attr['ef_icon']  = $mvc->getIcon($id);
        $attr['title'] .= "{$mvc->singleTitle}|* №{$rec->id}";
        
        if ($rec->state == 'rejected') {
        	$attr['class'] .= ' state-rejected';
        }
        
        if(!doc_Threads::haveRightFor('single', $rec->threadId) && !$mvc->haveRightFor('single', $rec) && !$mvc->haveRightFor('viewpsingle', $rec)) {
            $url =  array();
        } else {
        	if(Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('pdf')){
        		$url =  array();
        		unset($attr['class'], $attr['style']);
        	}
        }

        $link = ht::createLink("{$row->title}", $url, NULL, $attr);
    }
    
    
    /**
     * Подменя URL-то да сочи към single' а на документа. От там се редиректва в нишката.
     */
    function on_AfterPrepareRetUrl($mvc, $data)
    {
        //Ако създаваме копие, редиректваме до създаденото копие
        if (is_object($data->form) && $data->form->isSubmitted()) {
            //TODO променя URL'то когато записваме и нов имейл
            $data->retUrl = array($mvc, 'single', $data->form->rec->id);
        }
    }
    
    
    /**
     * Ако в Request са зададени coverId и coverClass, а $folderId липсва,
     * Тогава форсира папката на посочената корица и записва в Request id-то й
     *
     * @param   core_Mvc      $mvc
     * @param   std_Class     $res
     * @param   std_Class     $data
     *
     * @return  bool
     */
    static function on_BeforePrepareEditForm($mvc, &$res, $data)
    {
        if(!Request::get('folderId') && ($coverClass = Request::get('coverClass')) && ($coverId = Request::get('coverId', 'int'))) {

            $cMvc = cls::get($coverClass);
            expect(is_a($cMvc, 'core_Mvc'), $cMvc);
            expect($cRec = $cMvc->fetch($coverId));
            $cMvc->requireRightFor('single', $cRec);
            $folderId = $cMvc->forceCoverAndFolder($cRec);

            Request::push(array('folderId' => $folderId));
        }
    }


    
    /**
     * Подготвя полетата threadId и folderId, ако има originId и threadId
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $rec = $data->form->rec;
        
        // Ако редактираме запис
        // В записа на формата "тихо" трябва да са въведени от Request originId, threadId или folderId   
        if($rec->id) { 
            $exRec = $mvc->fetch($rec->id);
            $mvc->threadId = $exRec->threadId;
            
            // Изискваме да има права
            if(core_Packs::isInstalled('colab') && core_Users::haveRole('partner')){
            	colab_Threads::requireRightFor('single', doc_Threads::fetch($mvc->threadId));
            } else {
                
                if (!$mvc->haveRightFor('psingle', $rec)) {
                    doc_Threads::requireRightFor('single', $mvc->threadId);
                }
            }
          
        } elseif ($rec->originId) {
            
            // Ако имаме $originId
           
            expect($oRec = doc_Containers::fetch($rec->originId));
            
            // Трябва да имаме достъп до нишката на оригиналния документ
            if (core_Users::haveRole('partner') && core_Packs::isInstalled('colab')) {
                $tRec = doc_Threads::fetch($oRec->threadId);
                colab_Threads::requireRightFor('single', $tRec);
            } else {
                if (!$mvc->haveRightFor('psingle', $rec)) {
                    doc_Threads::requireRightFor('single', $oRec->threadId);
                }
            }
            
            $rec->threadId = $oRec->threadId;
            $rec->folderId = $oRec->folderId;
            
            $data->form->layout = $data->form->renderLayout();
            $tpl = new ET("<div class='preview-holder'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr("Оригинален документ") . "</b></div><div class='scrolling-holder'>[#DOCUMENT#]</div></div>");
            
            // TODO: да се замени с интерфейсен метод
            
            $document = doc_Containers::getDocument($rec->originId);
            
            $docHtml = $document->getInlineDocumentBody();
            
            $tpl->append($docHtml, 'DOCUMENT');
            
            $data->form->layout->append($tpl);
        }
        
        if($rec->threadId) {
        	$threadRec = doc_Threads::fetch($rec->threadId);
        	if (core_Packs::isInstalled('colab') && core_Users::haveRole('partner')){
        		colab_Threads::requireRightFor('single', $threadRec);
        	} else {
        	    if (!$mvc->haveRightFor('psingle', $rec)) {
        	        doc_Threads::requireRightFor('single', $rec->threadId);
        	    }
        	}
            
            $rec->folderId = $threadRec->folderId;
        }
        
        if(!$rec->folderId) {
            $rec->folderId = $mvc->getDefaultFolder();
        }
        
        if(!$rec->threadId && $rec->folderId && !doc_Folders::haveRightToFolder($rec->folderId)) {
        	if (core_Packs::isInstalled('colab') && haveRole('partner')) {
        		$userId = core_Users::getCurrent();
        		$colabFolders = colab_Folders::getSharedFolders($userId);
        		if(!in_array($rec->folderId, $colabFolders)){
        			error('403 Недостъпен ресурс');
        		}
        	} else {
        		error('403 Недостъпен ресурс');
        	}
        }
        
        $mvc->invoke('AfterPrepareDocumentLocation', array($data->form));
        
        $userListRolesArr = array();
        $userListFieldsArr = array();
        $richTextFieldsArr = array();
        
        // Обхождаме всичко полета в модела
        foreach ((array)$mvc->fields as $fieldName => $field) {
            
            // Ако са от type_Richtext
            if ($field->type instanceof type_Richtext) {
                $richTextFieldsArr[$fieldName] = $field;
            } else if ($field->type instanceof type_UserList) {
                
                // Ако са от type_UserList
                
                $userListFieldsArr[$fieldName] = $field;
                
                // Ако са зададени роли за полето
                if ($field->type->params['roles']) {
                    
                    // Масив с всички роли
                    $userRolesArr = arr::make($field->type->params['roles'], TRUE);
                    $userListRolesArr = array_merge($userRolesArr, $userRolesArr);
                }
                
            }
        }
        
        // Ако има поне едно поле от тип type_UserList
        if (!$userListFieldsArr) {
            $shareUserRoles = 'no_one';
            $userRolesForShare = 'no_one';
        } else {
            
            // Ако са зададени роли в type_UserList
            if ($userListRolesArr) {
                $shareUserRoles = implode(',', $userListRolesArr);
            }
        }
        
        // Ако има поне едно поле от тип type_Richtext
        if ($richTextFieldsArr) {
            
            // Обхождаме всички ричтекст полета
            foreach ((array)$richTextFieldsArr as $fieldName => $field) {
                
                // Ако не са зададени роли за споделяне в ричтекст полето 
                if (!$mvc->fields[$fieldName]->type->params['shareUsersRoles']) {
                    
                    // Добавяме в параметрите ролите за споделяне
                    $mvc->fields[$fieldName]->type->params['shareUsersRoles'] = $shareUserRoles;
                    
                    // Ако има роли за споделяне
                    if ($userRolesForShare) {
                        
                        // Ако не са зададени в ричтекст
                        if (!$mvc->fields[$fieldName]->type->params['userRolesForShare']) {
                            
                            // Добавяме ролите, които могат да споделят към потребители
                            $mvc->fields[$fieldName]->type->params['userRolesForShare'] = $userRolesForShare;
                        }
                    }
                }
            }
        }
        
        if ($data->action == 'clone') {
            
            if ($rec->threadId && $rec->containerId) {
                $tRec = doc_Threads::fetch($rec->threadId);
                
                // Ако е първи документ, да се клонира в нова нишка
                if ($tRec->firstContainerId == $rec->containerId) {
                    
                    unset($rec->threadId);
                }
            }
        }
        
        if ($rec->originId) {
            
            $cid = $rec->originId;
        } elseif ($rec->threadId) {
            
            // Ако добавяме коментар в нишката
            $cid = doc_Threads::fetch($rec->threadId)->firstContainerId;
        }
        
        if (!$data->form->rec->id) {
            // Споделените потребители по подразбиране
            $defaultShared = $mvc->getDefaultShared($rec, $cid);
            
            if (core_Users::isPowerUser()) {
                if ($defaultShared) {
                    unset($defaultShared[-1]);
                    unset($defaultShared[0]);
                    $data->form->setDefault('sharedUsers', $defaultShared);
                }
            }
        }
    }

    
	/**
     * Подготовка на бутоните на формата за добавяне/редактиране.
     *
     * @param core_Manager $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        if (empty($data->form->rec->id) && $data->form->rec->threadId && $data->form->rec->originId) {
            
            $folderId = ($data->form->rec->folderId) ? $data->form->rec->folderId : doc_Threads::fetch($data->form->rec->threadId)->folderId;
        	
            if(($mvc->canAddToFolder($folderId) !== FALSE) && $mvc->onlyFirstInThread !== FALSE){
            	$data->form->toolbar->addSbBtn('Нова нишка', 'save_new_thread', 'id=btnNewThread,order=9.99985','ef_icon = img/16/save_and_new.png');
            }
        }
        
        if(haveRole('powerUser')){
        	$data->form->toolbar->renameBtn('save', 'Чернова');
        } else {
        	$data->form->toolbar->renameBtn('save', 'Запис');
        }
        
        if($mvc->haveRightFor('pending', $data->form->rec)){
        	$data->form->toolbar->addSbBtn('Заявка', 'save_pending', 'id=btnPending,order=9.99989','ef_icon = img/16/tick-circle-frame.png');
        }
    }

    
    /**
     * След подготовката на заглавието на формата
     */
    public static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	//Добавяме текст по подразбиране за титлата на формата
        if ($form->rec->folderId) {
            $fRec = doc_Folders::fetch($form->rec->folderId);
            $title = tr(mb_strtolower($mvc->singleTitle));
        	if(core_Users::getCurrent('id', FALSE)){
                list($t,) = explode('<div', doc_Folders::recToVerbal($fRec)->title);
        		$title .= ' |в|* ' . $t;
        	}
        }
        
        $rec = $form->rec;
        
        if($form->rec->id) {
            $form->title = 'Редактиране на|* ';
        } else {
            if(Request::get('clone')) {
                $form->title = 'Копие на|* ';
            } else {
                if($rec->threadId) {
                    $form->title = 'Добавяне на|* ';
                } else {
                    $form->title = 'Създаване на|* ';
                }
            }
        }
        
        if($rec->threadId) {
            $thRec = doc_Threads::fetch($form->rec->threadId);
            setIfNot($data->singleTitle, $mvc->singleTitle);
            
            if($thRec->firstContainerId != $form->rec->containerId) {
            	$firstDoc = doc_Containers::getDocument($thRec->firstContainerId);
            	$form->title = core_Detail::getEditTitle($firstDoc->getInstance(), $firstDoc->that, $data->singleTitle, $rec->id, NULL, 50);
            	unset($title);
            }
        }
        
        $form->title .= $title;
    }
    
    
    /**
     *
     */
    static function on_AfterInputEditForm($mvc, $form)
    {  
        $rec = &$form->rec;
        
        // Ако има полета за редуциране на текста
        if ($form->isSubmitted() || $form->gotErrors())  {
            $fieldsForReduce = $mvc->selectFields("#reduceText");
            
            if ($fieldsForReduce) {
                
                foreach ($fieldsForReduce as $name => $type) {
                    $form->rec->{$name} = self::reduceString($form->rec->{$name});
                }
            }
        }
    	if ($form->isSubmitted()) {
	        if($form->cmd == 'save_new_thread' && $rec->threadId){
		        unset($rec->threadId);
		    }
		    
		    if($form->cmd == 'save_pending' && $mvc->haveRightFor('pending', $rec)){
		    	$form->rec->state = 'pending';
		    	$form->rec->pendingSaved = TRUE;
		    }
        }
    }
    
    
    /**
     * Замества повтарящите се стрингове с точки
     * 
     * @param string $str
     * 
     * @return string
     */
    protected static function reduceString($str)
    {
        $reduceArr = type_Set::toArray(doc_Setup::get('STRING_FOR_REDUCE'));
        
        foreach ($reduceArr as $rStr) {
            
            if (!($rStr = trim($rStr))) continue;
            
            $rStr = preg_quote($rStr, '/');
            
            $pattern .= ($pattern) ? '|' . $rStr : $rStr;
        }
        
        if ($pattern) {
            $str = preg_replace_callback("/(?'first'({$pattern})\:\s*)(?'others'(\k'first')+)(?'last'(\k'first'))/iu", array(get_called_class(), 'reduceMatches'), $str);
        }
        
        return $str;
    }
    
    
    /**
     * 
     * 
     * @param array $matches
     */
    protected static function reduceMatches($matches)
    {
        $second = str_ireplace($matches['first'], '.', $matches['others']);
        
        return trim($matches['first']) . ' ' . $second . ' ' . $matches['last'];
    }
    
    
    /**
     * Рендиране на документи за вътрешно представяне
     */
    function on_AfterGetInlineDocumentBody($mvc, &$res, $id, $mode = 'html', $options = NULL)
    {
        expect($mode == 'plain' || $mode == 'html' || $mode == 'xhtml');
        
        // Задаваме `text` режим според $mode. singleView-то на $mvc трябва да бъде генерирано
        // във формата, указан от `text` режима (plain или html)
        Mode::push('text', $mode);
        
        Mode::push('inlineDocument', TRUE);
        
        if (!Mode::is('text', 'html')) {
            
            // Ако не е зададено id използваме текущото id на потребите (ако има) и в краен случай id на активиралия потребител
            if (!$userId = $options->__userId) {
                $userId = core_Users::getCurrent();
                if ($userId <= 0) {
                    $userId = $mvc->getContainer($id)->activatedBy;
                }
            }
            // Временна промяна на текущия потребител на този, който е активирал документа
            $sudoUser = core_Users::sudo($userId);
        }
        
        // Ако възникне изключение
        try {
            // Подготвяме данните за единичния изглед
            $data = $mvc->prepareDocument($id, $options);
            
            $data->noDetails = $options->noDetails;
            $data->noToolbar = !$options->withToolbar;
            
            $res  = $mvc->renderDocument($id, $data);
        } catch (core_exception_Expect $e) {
            
            // Възстановяване на текущия потребител
            core_Users::exitSudo($sudoUser);
            
            reportException($e);
            
            expect(FALSE, $e);
        }
        
        // Възстановяване на текущия потребител
        core_Users::exitSudo($sudoUser);

        Mode::pop('inlineDocument');
        
        // Връщаме старата стойност на 'text'
        Mode::pop('text');
    }
    
    
    /**
     * HTML или plain text изгледа на документ при изпращане по емайл.
     *
     * Това е реализацията по подразбиране на интерфейсния метод doc_DocumentIntf::getDocumentBody()
     * Използва single view на мениджъра на документа.
     *
     * @param core_Mvc $mvc мениджър на документа
     * @param core_ET $res генерирания текст под формата на core_ET шаблон
     * @param int $id първичния ключ на документа - key(mvc=$mvc)
     * @param string $mode `plain` или `html`
     * @access private
     */
    function on_AfterGetDocumentBody($mvc, &$res, $id, $mode = 'html', $options = NULL)
    {
        expect($mode == 'plain' || $mode == 'html' || $mode == 'xhtml');
        
        // Задаваме `text` режим според $mode. singleView-то на $mvc трябва да бъде генерирано
        // във формата, указан от `text` режима (plain или html)
        Mode::push('text', $mode);
        
        if (!Mode::is('text', 'html')) {
            // Ако не е зададено id използваме текущото id на потребите (ако има) и в краен случай id на активиралия потребител
            if (!$userId = $options->__userId) {
                $userId = core_Users::getCurrent();
                if ($userId <= 0) {
                    $userId = $mvc->getContainer($id)->activatedBy;
                }
            }
            // Временна промяна на текущия потребител на този, който е активирал документа
            $sudoUser = core_Users::sudo($userId);
        }
        
        // Ако възникне изключение
        try {
            // Подготвяме данните за единичния изглед
            $data = $mvc->prepareDocument($id, $options);
            $res  = $mvc->renderDocument($id, $data);
        } catch (core_exception_Expect $e) {
            
            // Възстановяване на текущия потребител
            core_Users::exitSudo($sudoUser);
            expect(FALSE, $e);
        }
        
        // Възстановяване на текущия потребител
        core_Users::exitSudo($sudoUser);
         
        // Връщаме старата стойност на 'text'
        Mode::pop('text');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * Забранява изтриването на вече използвани сметки
     *
     * @param core_Manager $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if($requiredRoles == 'no_one') return;

        // Ако добавяме
        if ($action == 'add') {
            
            // Ако има нишка
            if ($rec->threadId) {
                
                // Ако няма права за добавяне в нишката
                if($mvc->canAddToThread($rec->threadId) === FALSE){
    	            
                    // Никой не може да добавя
    				$requiredRoles = 'no_one';
    			} elseif(!doc_Threads::haveRightFor('single', $rec->threadId)){
    			    if (core_Users::haveRole('partner') && core_Packs::isInstalled('colab')) {
    			        $tRec = doc_Threads::fetch($rec->threadId);
    			        if (!colab_Threads::haveRightFor('single', $tRec)) {
    			            $requiredRoles = 'no_one';
    			        }
    			    } else {
    			        $requiredRoles = 'no_one';
    			    }
    			} else{
    				
    				// Ако папката на нишката е затворена, не може да се добавят документи
                    $folderId = $rec->folderId ? $rec->folderId : doc_Threads::fetch($rec->threadId)->folderId;
    				if(doc_Folders::fetch($folderId)->state == 'closed'){
    					$requiredRoles = 'no_one';
    				}
    			}
            } elseif ($rec->folderId) {
                
                // Ако създаваме нова нишка
                
                // Ако няма права за добавяне в папката
                if($mvc->canAddToFolder($rec->folderId) === FALSE){
                    
                    // Никой не може да добавя
    				$requiredRoles = 'no_one';
    			} elseif(doc_Folders::fetch($rec->folderId)->state == 'closed') {
    				
    				// Ако папката е затворена не могат да се добавят документи
    				$requiredRoles = 'no_one';
    			}
            }
            
            if($requiredRoles != 'no_one'){
            	if (core_Users::haveRole('partner') && core_Packs::isInstalled('colab')) {
            		if(!colab_Threads::haveRightFor('list', (object)array('folderId' => $rec->folderId))){
            			$requiredRoles = 'no_one';
            		}
            	}
            }
        }
		
        if($requiredRoles == 'no_one') return;

    	if ($rec->id) {
            $oRec = $mvc->fetch($rec->id);
            
            if ($action == 'delete') {
                $requiredRoles = 'no_one';
            } elseif(($action == 'edit') && ($oRec->state != 'draft')) {
            	if(!(($oRec->state == 'active'  || $oRec->state == 'template') && $mvc->canEditActivated === TRUE)){
            		$requiredRoles = 'no_one';
            	} else {
            		// Ако потребителя няма достъп до сингъла, той не може и да редактира записа
            		$haveRightForSingle = $mvc->haveRightFor('single', $rec->id, $userId);
            		if(!$haveRightForSingle){
            			$requiredRoles = 'no_one';
            		}
            	}
            	
            } elseif(($action == 'edit')) {
            	
            	// Ако потребителя няма достъп до сингъла, той не може и да редактира записа
            	$haveRightForSingle = $mvc->haveRightFor('single', $rec->id, $userId);
            	if(!$haveRightForSingle){
            		if(core_Packs::isInstalled('colab') && core_Users::haveRole('partner')){
            			if($oRec->createdBy == $userId){
            				$requiredRoles = 'partner';
            			} else {
            				$requiredRoles = 'no_one';
            			}
            		} else {
            			$requiredRoles = 'no_one';
            		}
            	}
            } elseif ($action == 'reject'  || $action == 'restore') {
                if (doc_Threads::haveRightFor('single', $oRec->threadId, $userId)) {
                    if($requiredRoles != 'no_one'){
                    	$requiredRoles = 'powerUser';
                    }
                } else {
                	if(core_Packs::isInstalled('colab') && core_Users::haveRole('partner', $userId)){
                		if($oRec->createdBy != $userId){
                			$requiredRoles = 'no_one';
                		} else {
                			$requiredRoles = 'partner';
                		}
                	}
                } 
            } elseif ($action == 'single') {
            	
            	// Ако нямаме достъп до нишката
                if (!doc_Threads::haveRightFor('single', $oRec->threadId, $userId) && (($rec->createdBy != $userId) || core_Users::haveRole('partner', $rec->createdBy))) {
                    
                	// Ако е инсталиран пакета 'colab'
                	if(core_Packs::isInstalled('colab') && $oRec->threadId){
                		
                		// И нишката е споделена към контрактора (т.е първия документ в нея е видим и папката на нишката
        				// е споделена с партньора)
                		$isVisibleToContractors = colab_Threads::haveRightFor('single', doc_Threads::fetch($oRec->threadId));
                        
                		if($isVisibleToContractors && doc_Containers::fetch($rec->containerId)->visibleForPartners == 'yes'){
                			
                			// Тогава позволяваме на контрактора да има достъп до сингъла на този документ
                			$requiredRoles = 'partner';
                		} else {
                			$requiredRoles = 'no_one';
                		}
                	} else {
                		$requiredRoles = 'no_one';
                	}
                	
                	if ($requiredRoles == 'no_one' && $rec) {
                	    $modeAllowedContainerIdName = $mvc->getAllowedContainerName();
                	    $allowedCidArr = Mode::get($modeAllowedContainerIdName);
                	    
                	    $cId = $rec->containerId;
                	    
                	    if (!$cId && $rec->id) {
                	        $cId = $mvc->fetchField($rec->id, 'containerId');
                	    }
                	    
                	    if ($cId && $allowedCidArr[$cId]) {
                	        $requiredRoles = $mvc->getRequiredRoles('psingle', $rec, $userId);
                	    }
                	}
                } else {
                    if (($requiredRoles != 'every_one') || ($requiredRoles != 'user')) {
                        $requiredRoles = 'powerUser';
                    }
                }
            } elseif ($action == 'clone') {
                
                // Ако клонираме
                    
                $haveRightForClone = FALSE;
                
                // id на първия документ
                $firstContainerId = doc_Threads::fetch($oRec->threadId)->firstContainerId;
                
                // Ако е първи документ в нишката
                if ($firstContainerId == $oRec->containerId) {
                    
                    // Проверяваме за сингъл права в папката
                    $haveRightForClone = doc_Folders::haveRightFor('single', $oRec->folderId, $userId);
                    
                    // Ако има права
                    if ($haveRightForClone) {
                        
                        // Инстанция на документа
                        $docMvc = doc_Containers::getDocument($oRec->containerId);
                        
                        // Ако може да е начало на нишка
                        $haveRightForClone = ($docMvc->getInstance()->canAddToFolder($oRec->folderId) === FALSE) ? FALSE : TRUE;
                    }
                } else {
                    
                    if (core_Users::haveRole('partner') && $oRec->threadId && core_Packs::isInstalled('colab')) {
                        // За останалите, проверяваме за сингъл в нишката
                        $tRec = doc_Threads::fetch($oRec->threadId);
                        $haveRightForClone = colab_Threads::haveRightFor('single', $tRec, $userId);
                    } else {
                        // За останалите, проверяваме за сингъл в нишката
                        $haveRightForClone = doc_Threads::haveRightFor('single', $oRec->threadId, $userId);
                    }
                    
                    // Ако има права
                    if ($haveRightForClone) {
                        
                        // Инстанция на документа
                        $docMvc = doc_Containers::getDocument($oRec->containerId);
                        
                        // Ако може да се добавя в нишката
                        $haveRightForClone = ($docMvc->getInstance()->canAddToThread($oRec->threadId) === FALSE) ? FALSE : TRUE;
                    }
                }
                
                // Ако един от двата начина върне, че имаме права
                if (!$haveRightForClone) {
                
                    // Никой не може да клонира
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        // @see plg_Clone
        // Ако ще се клонират данните
        if ($rec && ($action == 'cloneuserdata')) {
            
            $cRec = clone $rec;
            
            if ($rec->threadId && $rec->containerId) {
                $tRec = doc_Threads::fetch($rec->threadId);
                
                // Ако е първи документ, да се клонира в нова нишка
                if ($tRec->firstContainerId == $rec->containerId) {
                    
                    unset($cRec->threadId);
                }
            }
            
            // Трябва да има права за добавяне
            if (!$mvc->haveRightFor('add', $cRec, $userId)) {
                $requiredRoles = 'no_one';
            }
            
            // Ако няма сингъл достъп до нишката пак да няма права
            if ($requiredRoles != 'no_one') {
                if (core_Packs::isInstalled('colab') && core_Users::haveRole('partner', $userId)) {
                    if (!colab_Threads::haveRightFor('single', $tRec)){
                        $requiredRoles = 'no_one';
                    }
                } else {
                    if (!doc_Threads::haveRightFor('single', $tRec)){
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
        
        // Проверка, дали има права за експорт на документа
        if ($action == 'exportdoc') {
            if ($rec->state == 'rejected' || $rec->state == 'draft' || !$mvc->haveRightFor('single', $rec) || !$mvc->getExportFormats()) {
                $requiredRoles = 'no_one';
            }
        }
        
        // При опит за форсиране на документа, като разходен обект
        if($action == 'forceexpenseitem' && isset($rec->id)){
        	$costClasses = acc_Setup::get('COST_OBJECT_DOCUMENTS');
        	
        	if(!keylist::isIn($mvc->getClassId(), $costClasses)){
        		
        		// Ако класа на документа не е документите, които могат да са разходни пера не може да се форсира
        		$requiredRoles = 'no_one';
        	} elseif(acc_Items::isItemInList($mvc, $rec->id, 'costObjects')) {
        		
        		// Ако документа, вече е в номенклатура за 'Разходни обекти', не може отново да се добави
        		$requiredRoles = 'no_one';
        	} else {
        		
        		// Ако документа е чернова, затворен или оттеглен, не може да се добави като разходен обект
        		if($rec->state == 'draft' || $rec->state == 'rejected' || $rec->state == 'closed' || $rec->state == 'stopped' || $rec->state == 'pending' || $rec->state == 'waiting' || $rec->state == 'template'){
        			$requiredRoles = 'no_one';
        		}
        	}
        }
        
        // Потребителите само с ранг ексикютив може да редактират документи, които те са създали
        if(!$requiredRoles || $requiredRoles == 'powerUser' || $requiredRoles == 'user') {
            if ($rec->id && ($action == 'edit') || ($action == 'reject')) {
                if(!$userId) {
                    $userId = core_Users::getCurrent();
                }
                if(!$rec->createdBy) {
                    $rec = $mvc->fetch($rec->id);
                }
                if($userId && $userId != $rec->createdBy) {
                    $requiredRoles = 'officer';
                }
            }
        }
        
        // Ако екшъна е за чакащ документ
        if($action == 'pending' && isset($rec)){
        	if($requiredRoles != 'no_one'){
        		
        		// Само чакащите и черновите могат да стават от чакащи -> чернова или обратно
        		if(isset($rec->state) && $rec->state != 'pending' && $rec->state != 'draft'){
        			$requiredRoles = 'no_one';
        		} elseif(!$mvc->haveRightFor('single', $rec)){
        			$requiredRoles = 'no_one';
        		}
        	}
        }
        
        // Ако действието е за избор на действие при оттегляне
        if($action == 'selectaction' && isset($rec)){
        	$requiredRoles = $mvc->getRequiredRoles('reject', $rec, $userId);
        	
        	// Трябва поребителя да може да оттегля документа
        	if($requiredRoles != 'no_one'){
        		$tRec = doc_Threads::fetch($rec->threadId);
        		
        		// И да има достъп до нишката
        		if(!doc_Threads::haveRightFor('single', $tRec)){
        			$requiredRoles = 'no_one';
        			
        			// И да е първия документ в нея
        		} elseif ($tRec->firstContainerId != $rec->containerId) {
        			$requiredRoles = 'no_one';
        		} elseif(acc_Items::fetchItem($mvc, $rec->id)->state == 'closed'){
        			$requiredRoles = 'no_one';
        		} else {
        			// И да има активни контиращи документи и неконтиращи
        			doc_Threads::groupDocumentsInThread($rec->threadId, $contable, $notContable, 'active', 1);
        			if(!(count($contable) && count($notContable))){
        				$requiredRoles = 'no_one';
        			}
        		}
        	}
        }
        
        // Ако не е зададено, да не е admin по подразбиране
        if ($action == 'viewpsingle') {
            if (!isset($mvc->canViewpsingle)) {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'psingle' && $rec) {
            $modeAllowedContainerIdName = $mvc->getAllowedContainerName();
            $allowedCidArr = Mode::get($modeAllowedContainerIdName);
            
            $cId = $rec->containerId;
            
            if (!$cId && $rec->id) {
                $cId = $mvc->fetchField($rec->id, 'containerId');
            }
            
            if (!$cId || !$allowedCidArr[$cId]) {
                $requiredRoles = 'no_one';
            }
        }
        
        if($action == 'pending' && isset($rec)){
        	if(isset($mvc->mainDetail) && $mvc->requireDetailForPending === TRUE){
        		$Detail = cls::get($mvc->mainDetail);
        		if(!$Detail->fetch("#{$Detail->masterKey} = '{$rec->id}'")){
        			$requiredRoles = 'no_one';
        		}
        	}
        }
    }
    
    
    /**
     * 
     * @param core_Manager $mvc
     * @param NULL|array $res
     * @param integer $docId
     * @param core_Manager|NULL $mInst
     * @param integer $dId|NULL
     */
    function on_AfterGetUrlWithAccess($mvc, &$res, $docId, $mInst=NULL, $dId=NULL)
    {
        Request::setProtected('pUrl');
        
        if (!isset($mInst)) {
            $mInst = $mvc;
            expect(!$dId);
            $dId = $docId;
        } else {
            expect($dId);
        }
        
        $clsId = NULL;
        if ($mInst instanceof core_BaseClass) {
            $clsId = $mInst->getClassId();
        }
        
        $isFromList = (int)Mode::is('forListRows');
        
        $res = array($mvc, 'pSingle', $docId, 'pUrl' => $clsId . '_' . $dId . '_' . $docId . '_' . $isFromList, 'ret_url' => TRUE);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function on_AfterPrepareDocument($mvc, &$data, $id, $options = NULL)
    {
        if ($data) return;
        
        // Създаваме обекта $data
        $data = new stdClass();
        
        if (is_object($id)) {
            $id = $id->id;
        }
        
        // Ако сме подали $rec'a в опциите, с променени данни (за бласта)
        if ($options->rec->id == $id) {
        
            // Използваме rec'а в опциите
            $data->rec = $options->rec;    
        } else {
            
            // Трябва да има $rec за това $id
            expect($data->rec = $mvc->fetch($id));
        }
        
//        $data->cacheKey = 'Doc' . $data->rec->id . Mode::get('text') . Mode::get('printing');
//        $data->threadCachedView = core_Cache::get($mvc->className, $data->cacheKey);
        
//        if($data->threadCachedView === FALSE) {
            // Подготвяме данните за единичния изглед
            $mvc->prepareSingle($data);
//        }
        
        // MID се генерира само ако :
        //     o подготвяме документа за изпращане навън - !Mode::is('text', 'html')
        //     o има зададен екшън - doclog_Documents::hasAction()
        if (!Mode::is('text', 'html') && doclog_Documents::hasAction()) {
            if (!isset($options->rec->__mid)) {
                
                // Ако няма стойност
                if (!isset($data->__MID__)) {
                    
                    // Тогава да се запише нов екшън
                    $data->__MID__ = doclog_Documents::saveAction(
                        array('containerId' => $data->rec->containerId)
                    );    
                }
                
                if (is_object($options)) {
                    
                    // Ако не е обект, създаваме го
                    if (!is_object($options->rec)) $options->rec = new stdClass();
                    
                    $options->rec->__mid = $data->__MID__;
                }
            }
        }
        
        if (!isset($data->__MID__) && isset($options->rec->__mid)) {
            $data->__MID__ = $options->rec->__mid;
        }
    }
    
    
    /**
     * Кешира и използва вече кеширани рендирани изгледи на документи
     */
    function on_AfterRenderDocument($mvc, &$tpl, $id, $data)
    {
        if($tpl) return;
        
//        if($data->threadCachedView === FALSE) {
            $tpl = $mvc->renderSingle($data);
            
            if ($data->rec->_resending) {
                $tpl->append(tr($data->rec->_resending), '_resending');    
            }
            $tpl->removeBlocks();
            $tpl->removePlaces();
            
//            if(in_array($data->rec->state, array('closed', 'rejected', 'active', 'waiting', 'open'))) {
//                core_Cache::set($mvc->className, $data->cacheKey, $tpl, isDebug() ?  0.1 : 5);
//            }
//        } else {
//            $tpl = $data->threadCachedView;
//        }
        
        // Заместване на MID. Това няма да се изпълни ако сме в Print Preview. Не може да се
        // премести и в on_AfterRenderSingle, защото тогава ще се кешира стойността на MID,
        // което е неприемливо
        $tpl->content = str_replace(static::getMidPlace(), $data->__MID__, $tpl->content);
    }


    function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        if (Request::get('Printing')) {
            // Заместваме MID само ако сме в Print Preview!
            //  
            // Причината е, резултата от този метод (а следователно и конкретната стокност на MID)
            // в някои случаи се кешира, а това не бива да се случва!
            $tpl->content = str_replace(static::getMidPlace(), $data->__MID__, $tpl->content);
        }
    }
    
    
    /**
     * Изпълнява се, акодефиниран метод getContragentData
     */
    function on_AfterGetContragentData($mvc, $data, $id)
    {
        
        return NULL;
    }
    
    
    /**
     * Изпълнява се, ако е дефиниран метод getContragentData
     */
    function on_AfterGetDefaultEmailBody($mvc, $data, $id)
    {
        
        return NULL;
    }
    
    
    /**
     * Реализация по подразбиране на интерфейсния метод ::getThreadState()
     *
     * TODO: Тук трябва да се направи проверка, дали документа е изпратен или отпечатан
     * и само тогава да се приема състоянието за затворено
     */
    function on_AfterGetThreadState($mvc, &$state, $id)
    {   
        if(!$state) {
            $state = 'closed';
        } 
    }
    
    
    /**
     * Реализация по подразбиране на интерфейсния метод ::getShared()
     * За фунцкии, които не използват doc_SharablePlg
     */
    function on_AfterGetShared($mvc, &$shared, $id)
    {  
        
    }  
    
        
    /**
     * Реализация по подразбиране на интерфейсния метод ::canAddToFolder()
     */
    function on_AfterCanAddToFolder($mvc, &$res, $folderId)
    {
        if(!isset($res)) {
            $res = TRUE;
        }
    }
    
    
    /**
     * Реализация по подразбиране на интерфейсния метод ::canAddToThread()
     */
    function on_AfterCanAddToThread($mvc, &$res, $threadId)
    {
        $res = !($mvc->onlyFirstInThread);
    }
    
    
    /**
     * Връща името на документа с разширение .pdf и стойности 'off'
     *
     * @return array $res - Масив с типа (разширението) на файла и стойност указваща дали е избрана 
     *                      по подразбиране
     */
    function on_AfterGetPossibleTypeConvertings($mvc, &$res, $id)
    {
        //Превръщаме $res в масив
        $res = (array)$res;
        
        // Ако е обект, използваме го директно
        if (is_object($id)) {
            $rec = $id;
        } else {
            //Вземаме данните
            $rec = $mvc::fetch($id);
        }
        
        //Обхождаме всички полета
        foreach ($mvc->fields as $field) {
            
            //Проверяваме дали е инстанция на type_RIchtext
            if ($field->type instanceof type_Richtext) {
                
                if ($field->type->params['hndToLink'] == 'no') continue;
                
                //Името на полето
                $fieldName = $field->name;
                
                //Имената на намерените документи
                $names = doc_RichTextPlg::getAttachedDocs($rec->$fieldName);

                if (count($names)) {
                    foreach ($names as $name=>$doc) {
                        $res += $doc['mvc']->getTypeConvertingsByClass($doc['rec']->id);
                    }
                }
            }
        }
    }
    
    
    /**
     * Връща максимално допустимия размер за прикачени файлове
     * 
     * @param core_Mvc $mvc
     * @param integer $min
     */
    function on_AfterGetMaxAttachFileSizeLimit($mvc, &$max)
    {
        static $maxSizeArr = array();
        
        $clsId = $mvc->getClassId();
        
        if (!$maxSizeArr[$clsId]) {
            
            if (!$max) {
                
                $conf = core_Packs::getConfig('email');
                $maxAttachedLimit = $conf->EMAIL_MAX_ATTACHED_FILE_LIMIT;
                
                // Инстанция на класа за определяне на размера
                $FileSize = cls::get('fileman_FileSize');
                
                // Вземаме размерите, които ще влияят за изпращането на файлове
                $memoryLimit = ini_get('memory_limit');
                
                // Вземаме вербалното им представяне
                $memoryLimit = $FileSize->fromVerbal($memoryLimit) / 3;
                
                // Вземаме мининалния размер
                $maxSizeArr[$clsId] = min($maxAttachedLimit, $memoryLimit);
            } else {
                $maxSizeArr[$clsId] = $max;
            }
        }
        
        $max = $maxSizeArr[$clsId];
    }
    
    
    /**
     * Връща вербалната стойност на размерите подадени в масива
     * 
     * @param core_Mvc $mvc
     * @param string $res
     * @param array $dataArr
     */
    function on_AfterGetVerbalSizesFromArray($mvc, &$res, $dataArr)
    {
        $sizeAll = 0;
        
        // Събираме стойностите от масива
        foreach ($dataArr as $size) {
            $sizeAll += $size;
        } 
        
        // Вербализираме стойността
        $FileSize = cls::get('fileman_FileSize');
        $res = $FileSize->toVerbal($sizeAll);
    }
    
    
    /**
     * 
     * 
     * @param core_Mvc $mvc
     * @param boolean $res
     * @param array $sizeArr
     */
    function on_AfterCheckMaxAttachedSize($mvc, &$res, $sizeArr)
    {
        $nSize=0;
        
        $min = $mvc->getMaxAttachFileSizeLimit();
        
        // Обхождаме масива
        foreach ((array)$sizeArr as $size) {
            
            // Добавяме към размера
            $nSize += $size;
            
            // Ако общия размер на файловете е над допустимия минимум
            if ($nSize > $min) {
                
                $res = FALSE;
                
                return ;
            }
        }
        
        $res = TRUE;
    }
    
    
    /**
     * Връща масив с размерите на прикачените файлове
     * 
     * @param core_Mvc $mvc
     * @param array $resArr
     * @param array $filesArr
     */
    function on_AfterGetFilesSizes($mvc, &$resArr, $filesArr)
    {
        foreach ((array)$filesArr as $fileHnd => $dummy) {
            
            // Вземаме метаданните за файла
            $meta = fileman::getMeta($fileHnd);
            
            // Добавяме размера за този манипулатор
            $resArr[$fileHnd] = $meta['size'];
        }
    }
    
    
    /**
     * Връща размера на всички подадени документи
     * 
     * @param core_Mvc $mvc
     * @param array $resArr
     * @param array $docsArr
     */
    function on_AfterGetDocumentsSizes($mvc, &$resArr, $docsArr)
    {
        foreach ((array)$docsArr as $doc) {
            $resArr[$doc['fileName']] = $doc['doc']->getDocumentSize($doc['ext']);
        }
    }
    
    
    /**
     * Връща размера на документа
     * 
     * @param core_Mvc $mvc
     * @param string $res
     * @param integer $id
     * @param integer $type
     */
    function on_AfterGetDocumentSize($mvc, &$res, $id, $type)
    {
        switch (strtolower($type)) {
            case 'pdf':
                $res = 300000;
            break;
        }
    }
    
    
    /**
     * 
     * 
     * @param core_Mvc $mvc
     * @param string $res
     * @param integer $id
     * 
     * @see email_Incomings->on_BeforeGetTypeConvertingsByClass()
     * @see email_Incomings->on_BeforeCheckSizeForAttach()
     */
    function on_AfterCheckSizeForAttach($mvc, &$res, $id)
    {
    }
    
    
    /**
     * Метод по подразбиране за връщане на възможните файлове за прикачване.
     * По подразбиране всики имат възможност за прикачане на pdf.
     */
    function on_AfterGetTypeConvertingsByClass($mvc, &$res, $id)
    {
        //Превръщаме $res в масив
        $res = (array)$res;
        
        // Вземаме манипулатора на файла
        $name = $mvc->getHandle($id);
        
        //Името на файла е с големи букви, както са документите
        $name = strtoupper($name) . '.pdf';
        
        //Задаваме полето за избор, да не е избран по подразбиране
        $res[$name] = 'off';
    }
    
    
    /**
     * Реализация по подразбиране на doc_DocumentIntf::convertTo()
     * 
     * @param core_Mvc $mvc
     * @param array $res масив с манипулатор на файл (@see fileman)
     * @param int $id първичен ключ на документа
     * @param string $type формат, в който да се генерира съдържанието на док.
     * @param string $fileName име на файл, в който да се запише резултата
     */
    static function on_AfterConvertTo($mvc, &$res, $id, $type, $fileName = NULL)
    {
        // Преобразуваме в масив
        $res = (array)$res;
        
        if (!isset($fileName)) {
            expect($mvc->abbr, 'Липсва зададена абревиатура за документния клас ' . get_class($mvc));
            
            $fileName = strtoupper($mvc->abbr);
            if (!empty($type)) {
                $fileName .= '.' . $type;
            }
        }
        
        switch (strtolower($type)) {
            case 'pdf':
                doclog_Documents::pushAction(
                    array(
                        'action' => doclog_Documents::ACTION_PDF,
                        'containerId' => $mvc->getContainer($id)->id,
                        'data'        => (object)array(
                            'sendedBy'   => core_Users::getCurrent(),
                        )
                    )
                );
                
                Mode::push('pdf', TRUE);

                $html = $mvc->getDocumentBody($id, 'xhtml');
                
                Mode::pop('pdf');

                doclog_Documents::popAction();
                
                //Манипулатора на новосъздадения pdf файл
                $fileHnd = doc_PdfCreator::convert($html, $fileName);
                
                if ($fileHnd) {
                    $res[$fileHnd] = $fileHnd;
                }
                
            break;
        }
    }
    
    
	/**
     * Конвертира документа към pdf файл и връща манипулатора му
     *
     * @param string $fileName - Името на файла, без разширението
     * @param string $type     - Разширението на файла
     *
     * return array $res - Масив с fileHandler' и на документите
     * @deprecated
     * @see doc_DocumentIntf::convertTo()
     */
    function on_AfterConvertDocumentAsFile($mvc, &$res, $id, $fileName, $type)
    {
        //Превръщаме $res в масив
        $res = (array)$res;
        
        if (strtolower($type) != 'pdf') return ;
        
        //Емулираме режим 'xhtml', за да покажем статичните изображения
        Mode::push('text', 'xhtml');
        
        //Вземаме информация за документа, от имена на файла - името на класа и id' to
        $fileInfo = doc_RichTextPlg::getFileInfo($fileName);
        
        //Ако не може да се намери информация, тогава се прескача
        if (!$fileInfo) return;
        
        //Името на класа
        $className = $fileInfo['className'];
        
        $rec = $className::fetchByHandle($fileInfo);
        
        //Вземаме containerId' то на документа
        $containerId = $rec->containerId;
        
        //Ако няма containerId - прескачаме
        if (!$containerId) return;
        
        //Вземаме документа
        $document = doc_Containers::getDocument($containerId);
        
        //Данните на документа
        $data = $document->prepareDocument();
        
        //Рендираме документа
        $html = $document->renderDocument($data);
        
        $html = $mvc->getDocumentBody($id, 'xhtml');
        
        //Манипулатора на новосъздадения pdf файл
        $fh = doc_PdfCreator::convert($html, $fileName);
        
        //масив с всички pdf документи и имената им
        $res[$fh] = $fh;
        
        //Връщаме старата стойност на 'text'
        Mode::pop('text');
    }
        
    
    /**
     * След извличане на ключовите думи
     */
    function on_AfterGetSearchKeywords($mvc, &$searchKeywords, $rec)
    {
       	$rec = $mvc->fetchRec($rec);
    	
       	if (!isset($searchKeywords)) {
       	    $searchKeywords = plg_Search::getKeywords($mvc, $rec);
       	}
       	
    	if ($rec->id) {
    		
        	$handle = $mvc->getHandle($rec->id);
        	
        	$handleNormalized = plg_Search::normalizeText($handle);
        	
        	if (strpos($searchKeywords, $handleNormalized) === FALSE) {
        	    $searchKeywords .= " " . plg_Search::normalizeText($handle);
        	} 
        }
    }
    
    
    /**
     * Полета, по които да се генерират ключове за търсене
     * 
     * @param core_Mvc $mvc
     * @param array $searchFieldsArr
     */   
    function on_AfterGetSearchFields($mvc, &$searchFieldsArr)
    {
        if (!$searchFieldsArr) {
            $searchFieldsArr = arr::make($mvc->searchFields);
        }
    }
    
    
    
    /**
     * Връща стринг, който се използва за плейсхолдер на mid стринга
     * 
     * @return string
     */
    static function getMidPlace() 
    {
        
        return '__MID__';        
    }
    
    
    /**
     * Метод по подразбиране, за намиране на прикачените файлове в документите.
     * Извиква се само ако метода getAttachments($id) не съществува в класа
     */
    function on_AfterGetAttachments($mvc, &$res, $rec)
    {
        // Ако не е обект, тогава вземаме записите за съответния документ
        if (!is_object($rec)) {
            $rec = $mvc->fetch($rec);
        }
        
        $res = $mvc->getLinkedFiles($rec);
        
        if (!isset($res)) {
            $res = array();
        }
        
        return $res;
    }
    
    
   /**
    * Метод по подразбиране за намиране на прикачените файлове в документ
    * 
    * @param object $mvc - 
    * @param array $res - Масив с откритете прикачените файлове
    * @param integer $rec - 
    */
    function on_AfterGetLinkedFiles($mvc, &$res, $rec)
    {
        if (!is_object($rec)) {
            $rec = $mvc->fetch($rec);
        }
        
        $oCid = Mode::get('saveObjectsToCid');
        
        $filesArr = NULL;
        
        // Ако сме пушнали, но няма запис за таблицата
        if ($oCid) {
            $filesArr = doc_UsedInDocs::getObjectVals($rec->containerId, core_Users::getCurrent(), 'files');
            if (!isset($filesArr)) {
                $oCid = NULL;
            }
        }
        
        // Ако не са извлечени файловете или не сме в процес на извличане - форсираме процеса
        if ((!$oCid && $rec->containerId) || ($oCid && ($oCid != $rec->containerId))) {
            
            Mode::push('saveObjectsToCid', $rec->containerId);
            try {
                $cRec = doc_Containers::fetch($rec->containerId);
                if ($cRec->docClass) {
                    $docMvc = cls::get($cRec->docClass);
                }
                
                if ($docMvc) {
                    if (!($cRec->docId)) {
                        $cRec->docId = $docMvc->fetchField("#containerId = {$cRec->id}", 'id');
                        $cInst = cls::get('doc_Containers');
                        $cInst->save_($cRec, 'docId');
                    }
                    
                    $docMvc->prepareDocument($cRec->docId);
                }
            } catch (Exception $e) {
                reportException($e);
            }
            Mode::pop('saveObjectsToCid');
        }
        
        if (!isset($res)) {
            $res = array();
        }
        
        doc_UsedInDocs::flushArr();
        
        if (!isset($filesArr)) {
            $filesArr = doc_UsedInDocs::getObjectVals($rec->containerId, core_Users::getCurrent(), 'files');
        }
        
        if (is_array($filesArr)) {
            foreach ($filesArr as $fileHndArr) {
                if (!is_array($fileHndArr)) continue;
                foreach ($fileHndArr as $fh => $name) {
                    $res[$fh] = $name;
                }
            }
        }
    }
    
    
   /**
    * Метод по подразбиране за намиране на прикачените картинки в документ
    * 
    * @param core_Mvc $mvc - 
    * @param array $res - Масив с откритете прикачените файлове
    * @param integer $rec - 
    */
    function on_AfterGetLinkedImages($mvc, &$res, $rec)
    {
        if (!is_object($rec)) {
            $rec = $mvc->fetch($rec);
        }
        
        if (!isset($res)) {
            $res = array();
        }
        
        $rt = '';
        
        foreach ((array)$mvc->getAllFields($rec) as $fieldName => $field) {
            
            if ($field->type->params['imgToLink'] == 'no') continue;
            
            $fVal = $rec->{$fieldName};
        
            if (!$fVal || !is_string($fVal)) continue;
        
            $fVal = trim($fVal);
        
            if (!$fVal) continue;
        
            // Ако са от type_Richtext
            if ($field->type instanceof type_Richtext) {
                $rt .= "\n" . $fVal;
            }
        }
        
        if ($rt) {
            // Намираме прикачените файлове
            $res = array_merge(cms_GalleryRichTextPlg::getImages($rt), (array)$res);
        }
    }
    
    
    /**
     * 
     * 
     * @param core_Mvc $mvc
     * @param array $res
     * @param integer $id
     * @param integer $userId
     */
    public static function on_AfterGetLinkedDocuments($mvc, &$res, $id, $userId=NULL, $data=NULL)
    {
        try {
            $rec = $mvc->fetch($id);
        } catch (core_exception_Expect $e) {
            reportException($e);
            return ;
        }
        
        $rt = '';
        
        foreach ((array)$mvc->getAllFields($rec) as $fieldName => $field) {
            
            if ($field->type->params['hndToLink'] == 'no') continue;
            
            $fVal = $rec->{$fieldName};
            
            if (!$fVal || !is_string($fVal)) continue;
            
            $fVal = trim($fVal);
            
            if (!$fVal) continue;
            
            // Ако са от type_Richtext
            if ($field->type instanceof type_Richtext) {
                $rt .= "\n" . $fVal;
            }
        }
        
        if (!$rt) return ;
        
        // Ако не е зададено id използваме текущото id на потребите (ако има) и в краен случай id на активиралия потребител
        if (!$userId) {
            $userId = core_Users::getCurrent();
            if ($userId <= 0) {
                $userId = $mvc->getContainer($id)->activatedBy;
            }
        }
        
        $sudoUser = core_Users::sudo($userId);
        
        try {
            // Намираме прикачените документи
            $attachedDocs = doc_RichTextPlg::getAttachedDocs($rec->body);
            if (count($attachedDocs)) {
                $attachedDocs = array_keys($attachedDocs);
                $attachedDocs = array_combine($attachedDocs, $attachedDocs);    
            }
            
            if (!is_array($attachedDocs)) {
            	
            	$attachedDocs = array(); 
            }
            
            $res = array_merge($attachedDocs, (array)$res);
        } catch (core_exception_Expect $e) {
            core_Users::exitSudo($sudoUser);
            
            return ;
        }
        
        core_Users::exitSudo($sudoUser);
    }
    
    
    /**
     * Прихваща извикването на AfterSaveLogChange в change_Plugin
     * 
     * @param core_MVc $mvc
     * @param array $recsArr - Масив със записаните данни
     */
    function on_AfterSaveLogChange($mvc, $recsArr)
    {
        // Отбелязване в лога
        doclog_Documents::changed($recsArr);
    }
    
    
    /**
     * Връща документа, породил зададения документ - реализация по подразбиране
     *
     * @param core_Mvc $mvc
     * @param object $origin
     * @param int|object $id
     * @param string $intf
     * @return NULL|core_ObjectReference
     */
    public static function on_AfterGetOrigin(core_Mvc $mvc, &$origin, $rec, $intf = NULL)
    {
        if (isset($origin)) {
            return;
        }
        
        $rec = $mvc->fetchRec($rec);
        
        $origin = doc_Threads::getFirstDocument($rec->threadId);
    }
    
    
    /**
     * Реализация по подразбиране на метод getDescendants()
     * 
     * Метода връща референции към документите (от всевъзможни типове),
     * които са от същата нишка на даден документ (без него)
     * 
     * @param core_Mvc $mvc
     * @param array $chain масив от core_ObjectReference
     * @param int $originDocId key(mvc=$mvc)
     */
    public static function on_AfterGetDescendants(core_Mvc $mvc, &$chain, $originDocId)
    {
        $chain = array();
        
        $query = doc_Containers::getQuery();
        
        // Извличане на треда и контейнера на документа
        $threadId    = $mvc->fetch($originDocId)->threadId;
        $containerId = $mvc->fetch($originDocId)->containerId;
        
        // Намиране на последващите документи в треда (различни от текущия)
        $chainContainers = $query->fetchAll("
            #id != {$containerId}
            AND #threadId = {$threadId}
            AND #docId IS NOT NULL
        ", 'id, originId');
        
        // За всеки намерен документ, вкарва се във веригата
        foreach ($chainContainers as $cc) {
        	try{
        		$chain[] = doc_Containers::getDocument($cc->id);
        	} catch(core_exception_Expect $e){
        		
        	}
        }
    }
    
    
    /**
     * Реализация по подразбиране на интерфейсния метод ::getUsedDocs()
     * Намира всички цитирания на документи в полета Richtext
     * и ги подготвя във вид подходящ за маркиране като използвани
     */
    function on_AfterGetUsedDocs($mvc, &$res, $id)
    {
    	$rec = $mvc->fetch($id);
    	
    	if ($rec !== FALSE) {
    	    $docs = doc_RichTextPlg::getDocsInRichtextFields($mvc, $rec);
    	} else {
    	    $docs = array();
    	}
    	
    	if (!empty($docs)){
	    	foreach ($docs as $doc){
	    	    if (isset($doc['rec']->containerId)) {
	    	        $res[$doc['rec']->containerId] = $doc['rec']->containerId;
	    	    }
	    	}
    	}
    	
        // Ако ориджина е от друг тред, добавяме и него
    	if ($rec && isset($rec->originId)){
    	    $cRec = doc_Containers::fetch($rec->originId);
    	    if($cRec->threadId != $rec->threadId) {
    	        $res[$rec->originId] = $rec->originId;
    	    }
    	}
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    function getCoversAndInterfacesForNewDoc($mvc, &$res)
    {
    	if(empty($res)){
    		return NULL;
    	} 
    }
    
    
    /**
     * Дефолт метод филтриращ заявка към doc_Folders
     * Добавя условия в заявката, така, че да останат само тези папки, 
     * в които може да бъде добавен документ от типа на $mvc
     * 
     * @param core_Mvc   $mvc     Мениджър на документи
     * @param void       $res     Резултат - не се използва
     * @param core_Query $query   Заявка към doc_Folders
     */
    function on_AfterRestrictQueryOnlyFolderForDocuments($mvc, &$res, $query)
    {
    	$query = doc_Folders::restrictAccess($query, NULL, FALSE);
    }
    
    
	/**
     * Метод по подразбиране
     * Връща иконата на документа
     */
    function on_AfterGetIcon($mvc, &$res, $id = NULL)
    {
        if(!$res) { 
            $res = $mvc->singleIcon;
        }
    }
    
    
    /**
     * Преди записване на клонирания запис
     * 
     * @param core_Mvc $mvc
     * @param object $rec
     * @param object $nRec
     * 
     * @see plg_Clone
     */
    function on_BeforeSaveCloneRec($mvc, $rec, $nRec)
    {
        // Премахваме ненужните полета
        unset($nRec->searchKeywords);
        unset($nRec->createdOn);
        unset($nRec->createdBy);
        unset($nRec->modifiedOn);
        unset($nRec->modifiedBy);
        unset($nRec->brState);
        unset($nRec->activatedBy);
        unset($nRec->activatedOn);
        
        if (!core_Users::haveRole('partner')) {
            unset($nRec->state);
        }
        
        setIfNot($thredId, $nRec->threadId, $rec->threadId);
        setIfNot($containerId, $nRec->containerId, $rec->containerId);
        
        if ($thredId && $containerId) {
            $tRec = doc_Threads::fetch($thredId);
            
            // Ако е първи документ, да се клонира в нова нишка
            if ($tRec->firstContainerId == $containerId) {
                unset($nRec->threadId);
            }
        }
        
        unset($nRec->containerId);
    }
    
    
    /**
     * Добавя в условието да се извличат всички документи, които са начало на нишка,
     * не са отхвърлени и са от съответната папка
     * Извиква се при извикване на GetSameFirstDocumentsQuery
     * 
     * @param core_Mvc $mvc - Инстанция на класа
     * @param core_Query $query - Резултатния обект
     * @param integer $folderId - id на папката
     * @param array $params - Масив с допълнителни параметри
     */
    public static function on_AfterGetSameFirstDocumentsQuery($mvc, &$query, $folderId, $params=array())
    {
        if (!$query) {
            $query = $mvc->getQuery();
        }
        
        $query->where(array("#folderId = '[#1#]'", $folderId));
        $query->where("#state != 'rejected'");
        $query->EXT("firstContainerId", 'doc_threads', "externalName=firstContainerId");
        $query->where("#firstContainerId = #containerId");
    }


    /**
     *
     */
    public static function on_BeforeRenderWrapping($mvc, &$res, &$tpl, $data = NULL)
    {
        if (haveRole('powerUser') && ((Request::get('Act') == 'edit' || Request::get('Act') == 'add' || Request::get('Act') == 'changeFields' || Request::get('Act') == 'cloneFields')
            || ($data->rec->threadId && !doc_Threads::haveRightFor('single', $data->rec->threadId)))) {
            $dc = cls::get('doc_Containers');
            $dc->currentTab = 'Нишка';
            $res = $dc->renderWrapping($tpl, $data);

            // Задаваме таба на менюто да сочи към документите
            Mode::set('pageMenu', 'Документи');
            Mode::set('pageSubMenu', 'Всички');

            return FALSE;
        }
    }
    
    
    /**
     * Създава нов документ със съответните стойности
     * 
     * @param core_Mvc $mvc
     * @param NULL|integer $id
     * @param object $rec
     */
    public static function on_AfterCreateNew($mvc, &$id, $rec)
    {
        // Очакваме да има такива полета в модела
        $allFldArr = $mvc->selectFields("#kind == 'FLD'");
        $recArr = (array)$rec;
        foreach ($recArr as $field => $dummy) {
            expect($allFldArr[$field], "Полето '{$field}' липсва в модела");
        }
        
        $id = $mvc->save($rec);
    }
    
    
    /**
     * Генерираме ключа за кеша
     * Интерфейсен метод
     * 
     * @param core_Mvc $mvc
     * @param NULL|FALSE|string $res
     * @param NULL|integer $id
     * @param object $cRec
     * 
     * @see doc_DocumentIntf
     */
    public static function on_AfterGenerateCacheKey($mvc, &$res, $id, $cRec)
    {
        // Ако не е оставено време за кеширане - не генерираме ключ
        if(!doc_Setup::get('CACHE_LIFETIME') > 0) {
            $res = FALSE;
            
            return ;
        }
        
        // Ако документа има отворена история - не се кешира
        if($cRec->id == Request::get('Cid')) {
            $res = FALSE;
            
            return ;
        }
        
        // Ако модела не допуска кеширане - ключ не се генерира
        if($mvc->preventCache) {
            $res = FALSE;
            
            return ;
        }
        
        // Ако документа е в състояние "чернова" и е променян преди по-малко от 10 минути - не се кешира.
        if($cRec->state == 'draft') {
            $res = FALSE;
            
            return ;
        }
		
        if ($id) {
            $rec = $mvc->fetchRec($id);
        } else {
            $rec = new stdClass();
        }
        
        // Потребител
        $userId = core_Users::getCurrent();
        
        // Последно модифициране
        $modifiedOn = $cRec->modifiedOn;
        
        // Контейнер
        $containerId = $cRec->id;
        
        // Положение на пейджърите
        $pageVar = core_Pager::getPageVar($mvc->className, $id);
        $pages =  serialize(Request::getVarsStartingWith($pageVar));
        
        // Режим на екрана
        $screenMode = Mode::get('screenMode');

        // Отворен горен таб
        $tabTop = Request::get('TabTop');
        
        // Отворен таб с друго име
        $tabTop2 = Request::get('TabTop' . $rec->containerId);
        
        $rejected = Request::get('Rejected');
        
        // Отворен таб на историята
        $tab = Request::get('Tab');

        $lang = core_Lg::getCurrent();

        $cacheStr = $userId . "|" . $containerId . "|" . $modifiedOn . "|" . $pages . "|" . $screenMode . "|" . $tabTop  . "|" . $tabTop2 . "|" . $tab . '|' . $lang . '|' . $rejected;
        
        // Добавка за да работи сортирането на детайли
        $dHnd = $mvc->getHandle($id);
        if(Request::get('docId') == $dHnd) {
           $cacheStr .= Request::get('Sort');
        }

        if ($res) {
            $cacheStr .= $res;
        }
        
        $res = md5($cacheStr);
    }
    
    
    /**
     * 
     * 
     * @param core_Master $mvc
     * @param string|NULL $res
     * @param integer $id
     * @param boolean|NULL $escape
     */
    public function on_BeforeGetTitleForId($mvc, &$res, $id, $escape=TRUE)
    {
        if (!$id) return ;
        
        try {
            $row = $mvc->getDocumentRow($id);
            
            $res = str::limitLen($row->title, 35);
            
            return FALSE;
        } catch (core_exception_Expect $e) {
            
            return ;
        }
    }
    
    
    /**
     * Обновява мастъра
     *
     * @param mixed $id - ид/запис на мастъра
     */
    public static function on_AfterUpdateMaster($mvc, &$res, $id)
    {
    	$rec = $mvc->fetchRec($id);
    	if(!$res){
    		if(is_object($rec)){
    			$rec->modifiedOn = dt::now();
    			$mvc->save_($rec, 'modifiedOn');
    		}
    	}
    }
    
    
    /**
     * Обновява modified стойностите
     * 
     * @param core_Master $mvc
     * @param boolean|NULL $res
     * @param integer $id
     */
    public static function on_AfterTouchRec($mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        if ($rec) {
            
            $cu = Users::getCurrent();
            // Задаваме стойностите на полетата за последно модифициране
            $rec->modifiedBy = $cu ? $cu : 0;
            $rec->modifiedOn = dt::verbal2Mysql();
            
            $mvc->save_($rec, 'modifiedOn, modifiedBy');
            $cid = $rec->containerId;
            
            if ($cid) {
                $cRec = new stdClass();
                $cRec->id = $cid;
                $cRec->modifiedOn = $rec->modifiedOn;
                $cRec->modifiedBy = $rec->modifiedBy;
                
                $containersInst = cls::get('doc_Containers');
                $containersInst->save_($cRec, 'modifiedOn, modifiedBy');
            }
        }
    }
    
    
    /**
     * Интерфейсен метод, който връща антетката на документите
     * 
     * @param core_Master $mvc
     * @param NULL|array $res
     * @param object $rec
     * @param object $row
     */
    public static function on_AfterGetLetterHead($mvc, &$res, $rec, $row)
    {
        $res = getTplFromFile('/doc/tpl/LetterHeadTpl.shtml');
        
        $headerRes = $mvc->getFieldForLetterHead($rec, $row);
        
        $hideArr = array();
        
        // Ако няма избрана версия, да се скрива антетката във външната част
        $hideArr = $mvc->getHideArrForLetterHead($rec, $row);
        
        $showHeadersArr = $mvc->removeHideArrForLetterHead($headerRes, $hideArr);
        
        $tableRows = $mvc->prepareHeaderLines($showHeadersArr);
        
        $res->replace($tableRows, 'TableRow');
        
        $res->placeObject($row);
        
        return $res;
    }
    
    
    /**
     * Кои полета да са скрити във вътрешното показване
     * 
     * @param core_Master $mvc
     * @param NULL|array $res
     * @param object $rec
     * @param object $row
     */
    public static function on_AfterGetHideArrForLetterHead($mvc, &$res, $rec, $row)
    {
        $res = arr::make($res);
        
        // Ако няма избрана версия, да не се показва във вътрешната част
        if (!$row->FirstSelectedVersion) {
            $res['internal']['versionAndDate'] = TRUE;
            $res['internal']['date'] = TRUE;
            $res['internal']['version'] = TRUE;
        }
        
        // Ако има само една версия или няма версии, да не се показват версиите във външната част
        if (!(isset($row->FirstSelectedVersion)) || $row->LastVersion == '0.1') {
            $res['external']['versionAndDate'] = TRUE;
            $res['external']['date'] = TRUE;
            $res['external']['version'] = TRUE;
            
        }
        
        $res['internal']['ident'] = TRUE;
        $res['internal']['createdBy'] = TRUE;
        $res['internal']['createdOn'] = TRUE;
        
        $res['external']['_lastFrom'] = TRUE;
    }
    
    
    /**
     * Добавя допълнителни полетата в антетката
     * 
     * @param core_Master $mvc
     * @param NULL|array $res
     * @param object $rec
     * @param object $row
     */
    public static function on_AfterGetFieldForLetterHead($mvc, &$resArr, $rec, $row)
    {
        if (!$mvc->showLetterHead) return ;
        
        $resArr = arr::make($resArr);
        $title = $mvc->singleTitle ? $mvc->singleTitle : $mvc->title;
        $title = tr($title);
        $resArr['ident'] = array('name' => tr($title), 'val' => '[#ident#]');
        
        // Полета, които ще се показват
        $resArr += change_Plugin::getDateAndVersionRow();
        
        $resArr['createdBy'] = array('name' => tr('Автор'), 'val' => '[#createdBy#]');
        $resArr['createdOn'] = array('name' => tr('Дата'), 'val' => '[#createdOn#]');
        
        // Ако е зададено да се показва действията в документа
        if ($mvc->showLogTimeInHead) {
            $showArr = arr::make($mvc->showLogTimeInHead);
            if ($showArr) {
                $keyArr = array();
                foreach ($showArr as $str => $limit) {
                    $keyArr += log_Data::getObjectRecs($mvc, $rec->id, NULL, 'Документът се връща в чернова', $limit);
                }
                
                if ($keyArr) {
                    $rowArr = log_Data::getRows($keyArr, array('actTime', 'userId'));
                    $lastFromStr = '';
                    foreach ($rowArr as $row) {
                        $lastFromStr .= $lastFromStr ? '<br>' : '';
                        $lastFromStr .= tr('от') . ' ' . $row->userId . ' ' . tr('на') . ' ' . $row->actTime;
                    }
                    
                    if ($lastFromStr) {
                        $resArr['_lastFrom'] = array('name' => tr('Последни промени на състоянието'), 'val' => $lastFromStr);
                    }
                }
            }
        }
    }
    
    
    /**
     * Премахва от масива стойностите, които трябва да се скрият в зависимост от режима
     * 
     * @param core_Master $mvc
     * @param NULL|core_ET $res
     * @param array $headerArr - двумерен масив с ключ името на полето
     * и стойност 'name' - име на полето и 'val' - стойност
     * @param array $hideArr - кои полета да се скриват
     * Отговаря на ключа на $headerArr
     */
    public static function on_AfterRemoveHideArrForLetterHead($mvc, &$res, $headerArr, $hideArr = array())
    {
        if (!$headerArr) return ;
        
        // Когато режима не се показва за външно сервиране, не се принтира и не се генерира PDF
        $isInternal = (boolean) !Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf');
        
        if (!isset($res)) $res = array();
        
        // Добавяме полетата, които ще се показват в съответния режим
        foreach ((array)$headerArr as $key => $value) {
            if ($isInternal && (($hideArr['internal'][$key]) || $hideArr['internal']['*'])) continue;
        
            if (!$isInternal && (($hideArr['external'][$key]) || $hideArr['external']['*'])) continue;
        
            $res[$key] = $value;
        }
    }
    
    
    /**
     * Получава масив със стойности, които да ги показва в таблица.
     * В зависимост от режима, определя как да са подредени и връща редовете и колоните на таблицата
     * 
     * @param core_Master $mvc
     * @param NULL|core_ET $res
     * @param array $headerArr - двумерен масив с ключ името на полето
     * и стойност 'name' - име на полето и 'val' - стойност
     */
    public static function on_AfterPrepareHeaderLines($mvc, &$res, $headerArr)
    {
        if (!$headerArr) return ;
        
        $isNarrow = Mode::is('screenMode', 'narrow') && !Mode::is('printing');
        
        if ($isNarrow) {
            $res = new ET('');
        } else {
            
            $limitForSecondRow = $mvc->headerLinesLimit ? $mvc->headerLinesLimit : 5;
            $haveSecondRow = FALSE;
            
            // Ако бройката е под ограничението, няма да има втори ред
            $noSecondRow = FALSE;
            
            $headerArrCnt = count($headerArr);
            
            if ($headerArrCnt < $limitForSecondRow) {
                $noSecondRow = TRUE;
            } else {
                // Ако не е зададено твърдо броя на колоните в първия ред
                if (!isset($mvc->headerLinesLimit)) {
                    $limitForSecondRow = max(array(ceil($headerArrCnt / 2), $limitForSecondRow));
                }
            }
            
            // Определяме, кои полета ще са на втори ред или дали ще има такива
            $secondRowArr = array();
            $cnt = 0;
            foreach ($headerArr as $key => &$hArr) {
                
                if ($noSecondRow) {
                    unset($hArr['row']);
                    continue;
                }
                
                if ($hArr['row'] != 2) {
                    // Ако не е зададено да е втори ред - добавяме, ако сме надвишили лимита
                    $cnt++;
                    if ($cnt <= $limitForSecondRow) continue;
                    
                    $hArr['row'] = 2;
                }
                
                $haveSecondRow = TRUE;
                
                if ($hArr['row'] == 2) {
                    $secondRowArr[$key] = $hArr;
                }
            }
            
            // Ако имаме само един кандидат за втория ред, да не се показва сам
            if ((count($secondRowArr) == 1)) {
                $key = key($secondRowArr);
                unset($headerArr[$key]['row']);
                $haveSecondRow = FALSE;
            }
            
            $first = '_FIRST_TR';
            $second = '_SECOND_TR';
            $res = new ET("<tr>[#{$first}#]</tr><tr>[#{$second}#]</tr>");
            
            if ($haveSecondRow) {
                $firstSecondRow = '_FIRST_TR_SECOND_ROW';
                $secondSecondRow = '_SECOND_TR_SECOND_ROW';
                
                $res->append(new ET("<tr>[#{$firstSecondRow}#]</tr><tr>[#{$secondSecondRow}#]</tr>"));
            }
        }
        
        $haveVal = FALSE;
        
        $collspan = 0;
        $firstRowCnt = 0;
        $secondRowCnt = count($secondRowArr);
        
        if (!$isNarrow && $haveSecondRow) {
            $firstRowCnt = $headerArrCnt - $secondRowCnt;
            $collspan = $firstRowCnt - $secondRowCnt;
        }
        
        $row1Cnt = 0;
        $row2Cnt = 0;
        $i = 0;
        $addedColspan = FALSE;
        foreach ((array)$headerArr as $key => $value) {
            
            $colspanPlace = '_colspan_' . $i++;
            
            $haveVal = TRUE;
            
            $colon = $isNarrow ? ':' : '';
            
            $val = new ET("<td class='antetkaCell' [#{$colspanPlace}#]><b>{$value['val']}</b></td>");
            
            if ($isNarrow) {
                $name = new ET("<td class='aright nowrap' style='width: 1%;'>{$value['name']}{$colon}</td>");
                $res->append("<tr>");
                $res->append($name);
                $res->append($val);
                $res->append("</tr>");
            } else {
                $name = new ET("<td class='aleft' style='border-bottom: 1px solid #ddd; [#_styleTop_#]' [#{$colspanPlace}#]>{$value['name']}{$colon}</td>");
                
                if (!$addedColspan) {
                    if ($value['row']) {
                        $row2Cnt++;
                    } else {
                        $row1Cnt++;
                    }
                }
                
                $collspanStr = '';
                
                if ($collspan > 0) {
                    // Последният елемент на втората таблица ще има
                    if (($row2Cnt == $secondRowCnt) && (!$addedColspan)) {
                        $collspanStr = 'colspan=' . ($collspan + 1);
                        $addedColspan = TRUE;
                    }
                } elseif ($collspan < 0) {
                    // Последния елемент на първата таблица ще има
                    if (($row1Cnt == $firstRowCnt) && (!$addedColspan)) {
                        $collspanStr = 'colspan=' . (($collspan * -1) + 1);
                        $addedColspan = TRUE;
                    }
                }
                
                $name->replace($collspanStr, $colspanPlace);
                $val->replace($collspanStr, $colspanPlace);
                
                if ($haveSecondRow && $value['row'] == 2) {
                    $name->replace('border-top: 5px solid #ddd;', '_styleTop_');
                    $res->append($name, $firstSecondRow);
                    $res->append($val, $secondSecondRow);
                } else {
                    $res->append($name, $first);
                    $res->append($val, $second);
                }
            }
        }
        
        if (!$haveVal) {
            $res = NULL;
        }
    }
    
    
    /**
     * Връща споделените потребители по подразбиране.
     * 
     * @param core_Master $mvc
     * @param NULL|array $res
     * @param integer $cid
     */
    function on_AfterGetDefaultShared($mvc, &$res, $rec, $originId = NULL)
    {
        $res = arr::make($res, TRUE);
        
        if (!$originId) return ;
        
        if (!$mvc->autoShareOriginCreator) return ;
        
        $document = doc_Containers::getDocument($originId);
        $dRec = $document->fetch();
        
        $createdBy = NULL;
        
        if ($dRec->createdBy > 0) {
            $createdBy = $dRec->createdBy;
        } elseif ($dRec->modifiedBy > 0) {
            $createdBy = $dRec->modifiedBy;
        }
        
        if (isset($createdBy)) {
            if ($createdBy != core_Users::getCurrent()) {
                $res[$createdBy] = $createdBy;
            }
        }
    }
    
    
    /**
     * Намираме потребители, които да се нотифицират допълнително за документа
     * Извън споделени/абонирани в нишката
     * 
     * @param core_Manager $mvc
     * @param NULL|array $res
     * @param stdObject $rec
     */
    function on_AfterGetUsersArrForNotifyInDoc($mvc, &$res, $rec)
    {
        $res = arr::make($res);
    }
    
    
    /**
     * Поддържа точна информацията за записите в детайла
     * 
     * @param core_Manager $mvc
     * @param integer $id
     * @param core_Manager $detailMvc
     */
    static function on_AfterUpdateDetail(core_Master $mvc, $id, core_Manager $detailMvc)
    {
        // Обновява modified полетата
        $mvc->touchRec($id);
    }
    
    
    /**
     * Проверява дали може да се променя записа в зависимост от състоянието на документа
     * 
     * @param core_Manager $mvc
     * @param boolean $res
     * @param object $rec
     * 
     * @see change_Plugin
     */
    public static function on_AfterCanChangeRec($mvc, &$res, $rec)
    {
        // Чернова и затворени документи не могат да се променят
        if (!$mvc->haveRightFor('single', $rec->id)) {
            
            $res = FALSE;
        } 
    }
    
    
    /**
     * 
     * 
     * @param core_Master $mvc
     * 
     * @param array $res
     */
    public static function on_AfterGetExportFormats($mvc, &$res)
    {
        $res = arr::make($res);

        $res['pdf'] = 'PDF формат';
        $res['html'] = 'HTML формат';
    }
    
    
    /**
     * Връща хеш стойността за документа
     * Дефолтна реализация на интерфейсен метод
     * 
     * @see doc_DocumentIntf
     * 
     * @param core_Master $mvc
     * @param NULL|string $res
     * @param integer $id
     */
    function on_AfterGetDocContentHash($mvc, &$res, $id)
    {
        static $hashArr = array();
        
        if (!$id) return ;
        
        if (!isset($hashArr[$id])) {
            $rec = $mvc->fetchRec($id);
            
            $hashArr[$id] = md5($res . '|' . $rec->title . '|' . $res->subject . '|' . $rec->body . '|' . $rec->textPart);
        }
        
        $res = $hashArr[$id];
    }


    /**
     * Преди рендиране на сингъла
     */
    public static function on_BeforeRenderSingleLayout($mvc, &$tpl, &$data)
    {
    	// Ако документа е оттеглен се подсигуряваме че ще се покаже от кого е оттеглен и кога
    	if($data->rec->state == 'rejected') {
    		$nTpl = new ET(tr('|* |от|* [#user#] |на|* [#date#]'));
    		$data->row->state .= $nTpl->placeArray(array('user' => crm_Profiles::createLink($data->rec->modifiedBy), 'date' => dt::mysql2Verbal($data->rec->modifiedOn)));
    	}
    	
    	// При генерирането за външно показване, махаме състоянието, защото е вътрешна информация
    	if (Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('pdf')){
            
    	    if ($data->rec->state == 'draft') {
    	        $data->row->ExtState = $data->row->state;
    	    }
    	    
    		// Оставяме състоянието да се показва само ако не е оттеглено
    		if ($data->rec->state != 'rejected') {
    			unset($data->row->state);
    		}
    	}
    }


    /**
     * Връща дали документа е видим за партньори
     *
     * @param core_Mvc $mvc
     * @param NULL|string $res
     * @param integer|stdObject $rec
     */
    public static function on_AfterIsVisibleForPartners($mvc, &$res, $rec)
    {
    	$rec = $mvc->fetchRec($rec);
    	if (!isset($res)) {
    		if ($mvc->visibleForPartners) {
    			$res = TRUE;
    		}
    	}
    }
    
    
    /**
     * Метод по подразбиране на детайлите за клониране
     */
    public static function on_AfterGetDetailsToClone($mvc, &$res, $rec)
    {
    	// Добавяме артикулите към детайлите за клониране
    	$res = arr::make($mvc->cloneDetails, TRUE);
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
    	if(empty($rec->activatedOn)){
    		$rec->activatedOn = dt::now();
    		$rec->activatedBy = core_Users::getCurrent();
    		$mvc->save_($rec, 'activatedOn,activatedBy');
    	}
    }
}
