<?php



/**
 * Клас 'doc_FolderPlg'
 *
 * Плъгин за обектите, които се явяват корици на папки
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class doc_FolderPlg extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    public static function on_AfterDescription(&$mvc)
    {
        if(!$mvc->fields['folderId']) {
            
            if($mvc->className != 'doc_Folders') {
                
                // Поле за id на папката. Ако не е зададено - обекта няма папка
                $mvc->FLD('folderId', 'key(mvc=doc_Folders)', 'caption=Папка,input=none');
                $mvc->setDbIndex('folderId');
            }
            
            // Определя достъпа по подразбиране за новите папки
            setIfNot($defaultAccess, $mvc->defaultAccess, 'team');
            
            $mvc->FLD('inCharge' , 'user(role=powerUser, rolesForAll=executive)', 'caption=Права->Отговорник,formOrder=10000,smartCenter');
            $mvc->FLD('access', 'enum(team=Екипен,private=Личен,public=Общ,secret=Секретен)', 'caption=Права->Достъп,formOrder=10001,notNull,value=' . $defaultAccess);
            $mvc->FLD('shared' , 'userList', 'caption=Права->Споделяне,formOrder=10002');
            
            $mvc->setDbIndex('inCharge');
        }
        
        // Добавя интерфейс за папки
        $mvc->interfaces = arr::make($mvc->interfaces);
        setIfNot($mvc->interfaces['doc_FolderIntf'], 'doc_FolderIntf');
		setIfNot($mvc->canCreatenewfolder, 'powerUser');
		setIfNot($mvc->canViewlogact, 'powerUser');
		
        $mvc->details = arr::make($mvc->details);

        $mvc->details['Rights'] = $mvc->className;
        $mvc->details['History'] = $mvc->className;
    }
    
    
    /**
     * След подготовка на таба със правата
     */
    public static function on_AfterPrepareRights($mvc, $res, $data)
    {
        $data->TabCaption = 'Права';
    }

    
    /**
     * След рендиране на таба със правата
     */
    public static function on_AfterRenderRights($mvc, &$tpl, $data)
    {
        $tpl = new ET(tr('|*' . getFileContent('doc/tpl/RightsLayout.shtml')));
 
        $tpl->placeObject($data->masterData->row);
    }
    
    
    /**
     * След подготовка на таба с историята
     */
    public static function on_AfterPrepareHistory($mvc, $res, $data)
    {
        if ($mvc->haveRightFor('viewlogact', $data->rec)) {
            $data->TabCaption = 'История';
        }

        if(!$data->TabCaption || !$data->isCurrent) return;
 
        $data->HaveRightForLog = TRUE;
            
        $data->ActionLog = new stdClass();
            
        $perPage = $mvc->actLogPerPage ? $mvc->actLogPerPage : 10;
            
        $data->ActionLog->pager = cls::get('core_Pager', array('itemsPerPage' => $perPage, 'pageVar' => 'P_Act_Log'));
             
        $data->ActionLog->recs = log_Data::getRecs($mvc, $data->masterData->rec->id, $data->ActionLog->pager);
        $data->ActionLog->rows = log_Data::getRows($data->ActionLog->recs, array('userId', 'actTime', 'actionCrc', 'ROW_ATTR'));
            
        // Ако има роля admin
        if (log_Data::haveRightFor('list')) {
                
                $attr = array();
		        $attr['ef_icon'] = '/img/16/page_go.png';
		        $attr['title'] = 'Екшън лог на потребителя';
                
                $logUrl = array('log_Data', 'list', 'class' => $mvc->className, 'object' => $data->rec->id, 'Cmd[refresh]' => TRUE, 'ret_url' => TRUE);
                
                $data->ActionLog->actionLogLink = ht::createLink(tr("Още..."), $logUrl, FALSE, $attr);  
        }
    }

    
    /**
     * След рендиране на таба със правата
     */
    public static function on_AfterRenderHistory($mvc, &$tpl, $data)
    {
   
        if (($data->ActionLog) && ($data->ActionLog->rows)) {
            $tpl = getTplFromFile('doc/tpl/FolderHistoryLog.shtml');
            
            $logBlockTpl = $tpl->getBlock('log');
            
            foreach ((array)$data->ActionLog->rows as $rows) {
                $logBlockTpl->placeObject($rows);
                $logBlockTpl->replace($rows->ROW_ATTR['class'], 'logClass');
                $logBlockTpl->append2Master();
            }
            
            $tpl->append($data->ActionLog->pager->getHtml(), 'pager');
            $tpl->append($data->ActionLog->actionLogLink, 'actionLogLink');
        } else {
            $data->masterData->History->disabled = TRUE;
        }
    }

    
    /**
     * Извиква се след подготовка на фирмата за редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        if($mvc->className == 'doc_Folders') return;
        
        // Полета за Достъп
        if(!$data->form->rec->inCharge) {
            $data->form->setDefault('inCharge', core_Users::getCurrent());
        }
        if(!$data->form->rec->access) {
            $data->form->setDefault('access', $mvc->defaultAccess ? $mvc->defaultAccess : 'team');
        }
        
        // Ако сме в тесен режим
        if (Mode::is('screenMode', 'narrow')) {
            
            // Да има само 2 колони
            $data->form->setField('shared', array('maxColumns' => 2));    
        }
        
        // При редакция
        if($data->form->rec->id){
       		
        	// Ако нямаш достъп до обекта, но имаш до корицата да не можеш да променяш правата за достъп
        	if(!doc_Folders::haveRightToObject($data->form->rec)){
       			$data->form->setField('inCharge', 'input=none');
       			$data->form->setField('access', 'input=none');
       			$data->form->setField('shared', 'input=none');
       		}
        }
    }
    
    
    /**
     * Добавя бутон "Папка" в единичния изглед
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        if($mvc->className == 'doc_Folders') return;
        
        if($data->rec->folderId && ($fRec = doc_Folders::fetch($data->rec->folderId))) {
        	
            $openThreads = $fRec->openThreadsCnt ? "|* ({$fRec->openThreadsCnt})" : "";
            
            if(doc_Folders::haveRightFor('single', $data->rec->folderId)){
            	$data->toolbar->addBtn('Папка' . $openThreads,
            			array('doc_Threads', 'list',
            					'folderId' => $data->rec->folderId),
            			array('title' => 'Отваряне на папката', 'ef_icon' => $fRec->openThreadsCnt ? 'img/16/folder-g.png' : 'img/16/folder-y.png'));
            }
        } else {
        	if ($mvc->haveRightFor('createnewfolder', $data->rec)) {
        		$title = $mvc->getFolderTitle($data->rec->id, FALSE);
        		$data->toolbar->addBtn('Папка', array($mvc, 'createFolder', $data->rec->id), array(
        				'warning' => "Наистина ли желаете да създадетe папка за документи към|* \"{$title}\"?",
        		), array('ef_icon' => 'img/16/folder_new.png', 'title' => "Създаване на папка за документи към|* {$title}"));
        	}
        }
    }
    
    
    /**
     * Подготовка за рендиране на единичния изглед
     * 
     * @param core_Master $mvc
     * @param object $res
     * @param object $data
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        // Рендираме екшън лога на потребителя
    }
    
    
    /**
     * След рендиране на единичния изглед
     * 
     * @param core_Master $mvc
     * @param core_ET $tpl
     * @param object $data
     */
    public static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        if (Request::get('share')) {
            bgerp_Notifications::clear(array($mvc, 'single', $data->rec->id, 'share' => TRUE));
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * Забранява изтриването на вече използвани сметки
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако оттегляме документа
        if ($action == 'reject' && $rec->folderId && $requiredRoles != 'no_one') {
            
            // Ако има запис, който не е оттеглен
            if (doc_Folders::fetch($rec->folderId)->allThreadsCnt) {
                
                // Никой да не може да оттегля папката
                $requiredRoles = 'no_one';    
            }
        }
        
        if ($rec->id && ($action == 'delete' || $action == 'edit' || $action == 'write' || $action == 'single' || $action == 'newdoc') && $requiredRoles != 'no_one') {
            
            $rec = $mvc->fetch($rec->id);
            
            // Ако модела е достъпен за всички потребители по подразбиране, 
            // но конкретния потребител няма права за конкретния обект
            // забраняваме достъпа
            if (!doc_Folders::haveRightToObject($rec, $userId)) {
                
                if($requiredRoles != 'no_one' && $rec->access == 'team'){
                	
                	// Ако има зададени мастър роли за достъп
            		$requiredRoles = $mvc->coverMasterRoles ? $mvc->coverMasterRoles : 'no_one';
            	} else {
            		$requiredRoles = 'no_one';
            	}
            }

            if($rec->state == 'rejected' && $action != 'single') {
                $requiredRoles = 'no_one';
            }
            
            if($action == 'delete' && $rec->folderId) {
                $requiredRoles = 'no_one';
            } 
        }
        
        // Не може да се създава нова папка, ако потребителя няма достъп до обекта
        if($action == 'createnewfolder' && isset($rec) && $requiredRoles != 'no_one'){
        	if (!doc_Folders::haveRightToObject($rec, $userId)) {
        		$requiredRoles = 'no_one';
        	} elseif($rec->state == 'rejected'){
        		$requiredRoles = 'no_one';
        	}
        }
        
        if (($action == 'viewlogact') && $rec && $requiredRoles != 'no_one') {
            if (!$mvc->haveRightFor('single', $rec, $userId)) {
                $requiredRoles = 'no_one';
            }
        }
        
        // Потребителите само с ранг ексикютив може да променят само корици на които са отговорник
        if(!$requiredRoles || $requiredRoles == 'powerUser' || $requiredRoles == 'user') {
            if($rec->id && ($action == 'delete' || $action == 'edit' || $action == 'write' || $action == 'close' || $action == 'reject')) {
                if(!$userId) {
                    $userId = core_Users::getCurrent();
                }
                if(!$rec->inCharge) {
                    $rec = $mvc->fetch($rec->id);
                }
                if ($userId && ($userId != $rec->inCharge) && !type_Keylist::isIn($userId, $rec->shared)) {
                    $requiredRoles = 'officer';
                }
            }
        }
    }
    
    
    /**
     * Премахва от резултатите скритите от менютата за избор
     */
    public static function on_BeforeMakeArray4Select($mvc, &$res, $fields = NULL, &$where = "", $index = 'id'  )
    { 
    	// Могат да се избират само не оттеглените и затворени корици
    	$where .= ($where ? " AND " : "") . " (#state != 'closed' AND #state != 'rejected')";
    	
    	$cu = core_Users::getCurrent();

        if(!haveRole('ceo') && $cu >0) {
            $add = "NOT (#access = 'secret' AND #inCharge != $cu AND !(#shared LIKE '%|{$cu}|%')) || (#access IS NULL)";
            if($where) {
                $where = "($where) AND " . $add;
            } else {
                $where = $add;
            }
        }
    }
    
    
    /**
     * Предпазва от листване скритите папки
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $mvc->restrictAccess($data->query);
    }
    
    
    /**
     * Дефолт имплементация на метод, която форсира създаването на обект - корица
     * на папка и след това форсира създаването на папка към този обект
     */
    public static function on_AfterForceCoverAndFolder($mvc, &$folderId, &$rec, $bForce = TRUE)
    {
        if (!$folderId) {
            // Понеже този плъгин по съвместителство се ползва и за doc_Folders, а този
            // метод няма смисъл в doc_Folders, не очакваме да се вика в този случай
            expect($mvc->className != 'doc_Folders');
            
            if(is_numeric($rec)) {
                expect($exRec = $mvc->fetch($rec), $rec);
                $rec = $exRec;
            } elseif($rec->id) {
                expect($exRec = $mvc->fetch($rec->id), $rec);
                $rec = $exRec;
            } else {
                $res = $mvc->isUnique($rec, $fields, $exRec);
               
                if($exRec) { 
                    $rec = $exRec;
                } elseif(!$rec) { 
                    $rec = new stdClass();
                }
                
                expect(is_object($rec));
            }
            
            // Ако обекта няма папка (поле $rec->folderId), създаваме една нова
            if($bForce && (!$rec->folderId || !doc_Folders::fetch($rec->folderId))) {
            	
            	// Очакваме да не е подаден празен stdClass
            	// Така се подсигуряваме да не се създаде празна корица
            	expect(count((array)$rec), 'Опит за създаване на празна корица');
            	
                $rec->folderId = doc_Folders::createNew($mvc);
                $mvc->save($rec);
            }

            $folderId = $rec->folderId;
        }
    }
    
    
    /**
     * Функция, която представлява метод за ::getFolderTitle по подразбиране
     */
    public static function on_AfterGetFolderTitle($mvc, &$title, $id, $escaped = TRUE)
    {
        if(!$title) {
            $title = $mvc->getTitleById($id, $escaped);
        }
    }
    
    
    /**
     * Реализация на екшън-а 'act_CreateFolder'
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
    	// Екшън за форсиране на документ в папка на корица
    	if($action == 'forcedocumentinfolder'){
    		
    		expect($id = Request::get('id', 'int'));
    		expect($documentClassId = Request::get('documentClassId', 'class(interface=doc_DocumentIntf)'));
    		
    		// Форсираме папката на корицата
    		$folderId = $mvc->forceCoverAndFolder($id);
    		
    		// Въпросния документ трябва да може да бъде създаден в папката
    		$Document = cls::get($documentClassId);
    		expect($Document->haveRightFor('add', (object)array('folderId' => $folderId)));
    		
    		// Редирект към екшъна за добавяне на документа
    		$url = array($Document, 'add', 'folderId' => $folderId);
    		if($retUrl = getRetUrl()){
    			$url['ret_url'] = $retUrl;
    		}
    		
    		// Редирект
    		$res = new Redirect($url);
    		
    		// Спираме изпълнението на други плъгини
    		return FALSE;
    	}
    	
    	if($action != 'createfolder' || $mvc->className == 'doc_Folders') return;
        
        // Входни параметри и проверка за права
        expect($id = Request::get('id', 'int'));
        expect($rec = $mvc->fetch($id));
        
        $mvc->requireRightFor('createnewfolder', $rec);
        
        $mvc->requireRightFor('createnewfolder', $rec);
        
        // Вземаме текущия потребител
        $cu = core_Users::getCurrent();     // Текущия потребител
        // Ако текущия потребител не е отговорник на тази корица на папка, 
        // правим необходимото за да му я споделим
        if($cu != $rec->inCharge && $cu > 0) {
            $rec->shared = keylist::addKey($rec->shared, $cu);
        }

        // Този синтаксис заобикаля предупрежденията на PHP5.4 за Deprecated: Call-time pass-by-reference
        // но е доста грозен
        // call_user_func_array(array($mvc, 'forceCoverAndFolder'), array(&$rec));
    
        $rec->folderId = $mvc->forceCoverAndFolder($rec);
 
        if(doc_Folders::haveRightFor('single', $rec->folderId)){
        	$res = new Redirect(array('doc_Threads', 'list', 'folderId' => $rec->folderId), '|Папката е създадена успешно');
        } else {
        	$res = new Redirect(array($mvc, 'single', $rec->id));
        }
        
        return FALSE;
    }
    
    
    /**
     * Изпълнява се преди запис и задава стойности на някои полета, ако не им е зададена такава
     * 1) Прави състоянието 'closed' по подразбиране
     * 2) Текущия потребител е отговорник на обекта
     * 3) Обекта има "Екипен" режим за достъп
     */
    public static function on_BeforeSave($mvc, $id, $rec, $fields = NULL)
    {
        // Вземаме текущия потребител
        $cu = core_Users::getCurrent();
        
        $fArr = arr::make($fields, TRUE);

        if((!$fields || $fArr['inCharge']) && !$rec->inCharge) {
            $rec->inCharge = $cu;
        }
        
        if((!$fields || $fArr['state']) && !$rec->state) {
            $rec->state = 'active';
        }
        
        // Подсигуряване да не се създава корица с отговорник @system или @anonym
        // в такъв случай отговорника става първия регистриран потребител в системата
        if(!$rec->inCharge || $rec->inCharge == -1){
        	
        	// Ако няма отговорник, това става или първия admin или първия ceo
        	// Така избягваме възможността, отговорника да е @system или @anonym
        	$rec->inCharge = self::getDefaultInCharge();
        }
    }
    
    
    /**
     * Връща дефолт потребителя, който може да е отговорник на папка, това е или първия
     * администратор или първия ceo регистриран в системата
     */
    public static function getDefaultInCharge()
    {
    	// Ид на ролята "admin"
    	$adminRoleId = core_Roles::fetchByName('admin');
    	
    	// Извличане на първия активен потребител с роля 'admin'
        $query = core_Users::getQuery();
        $query->where("#state = 'active'");
        $query->orderBy('createdOn', 'ASC');
        	
        $query2 = clone $query;
        $query->like("roles", "|{$adminRoleId}|");
        	
        // Ако няма такъв администратор, намираме първия 'ceo'
        if(!$userRec = $query->fetch()){
        	$ceoId = core_Roles::fetchByName('ceo');
        	$query2->like("roles", "|{$ceoId}|");
        	$userRec = $query2->fetch();
        }
        
        // Връщаме ид-то на намерения потребител
        return $userRec->id;
    }
    
    
    /**
     * Изпълнява се след запис на обект
     * Прави синхронизацията между данните записани в обекта-корица и папката
     */
    public static function on_AfterSave($mvc, &$id, $rec, $fields = NULL)
    {
        expect($id);

        if($mvc->className == 'doc_Folders') return;
        
        if(!$rec->folderId) {
            $rec->folderId = $mvc->fetchField($rec->id, 'folderId');
        }
        
        if($rec->folderId) {
            
            //Ако има папка - обновяме ковъра
            doc_Folders::updateByCover($rec->folderId);
        } else {
            
            //Ako няма папка и autoCreateFolder е TRUE, тогава създава папка
            if ($mvc->autoCreateFolder == 'instant') {
                $mvc->forceCoverAndFolder($rec);
            }
        }
        
        // При променя на споделените потребители прави или чисти нотификацията
        if (isset($rec->__mustNotify)) {
            
            // Добавяме и отговорниците към списъка
            $rec->__oShared = type_Keylist::addKey($rec->__oShared, $rec->__oInCharge);
            $rec->shared = type_Keylist::addKey($rec->shared, $rec->inCharge);
            
            $sArr = type_Keylist::getDiffArr($rec->__oShared, $rec->shared);
            
            $currUserNick = core_Users::getCurrent('nick');
            $currUserNick = type_Nick::normalize($currUserNick);
            
            $folderTitle = $mvc->getFolderTitle($rec->id);
            
            $notifyArr = array();
            if (!empty($sArr['add'])) {
                $notifyArr = $sArr['add'];
            }
            
            $delNotifyArr = array();
            if (!empty($sArr['delete'])) {
                $delNotifyArr = $sArr['delete'];
            }
            
            // Изтриваме нотификациите от премахнатите потребители 
            if (!empty($delNotifyArr)) {
                foreach ($delNotifyArr as $clearUser) {
                    bgerp_Notifications::setHidden(array('doc_Threads', 'list', 'folderId' => $rec->folderId, 'share' => TRUE), 'yes', $clearUser);
                    bgerp_Notifications::setHidden(array($mvc, 'single', $rec->id, 'share' => TRUE), 'yes', $clearUser);
                    bgerp_Notifications::setHidden(array($mvc, 'list', 'share' => TRUE), 'yes', $clearUser);
                }
            }
            
            // Нотифицираме новите споделени потребители
            if ($notifyArr) {
                foreach ($notifyArr as $notifyUserId) {
            
                    if (!$notifyUserId) continue;
            
                    $url = array();
                    $msg = '';
            
                    if($rec->folderId && ($fRec = doc_Folders::fetch($rec->folderId))) {
                         
                        if(doc_Folders::haveRightFor('single', $rec->folderId, $notifyUserId)){
                            $url = array('doc_Threads', 'list', 'folderId' => $rec->folderId, 'share' => TRUE);
                        }
            
                        $msg = $currUserNick . ' |сподели папка|* "' . $folderTitle . '"';
                    }
            
                    if (empty($url)) {
                        if (($mvc instanceof core_Master) && $mvc->haveRightFor('single', $rec, $notifyUserId)) {
                            $url = array($mvc, 'single', $rec->id, 'share' => TRUE);
                            $msg = $currUserNick . ' |сподели|* "|' . $mvc->singleTitle . '|*"';
                        } else {
                            $url = array($mvc, 'list', 'share' => TRUE);
                            $msg = $currUserNick . ' |сподели|* "|' . $mvc->title . '|*"';
                        }
                    }
                    
                    bgerp_Notifications::add($msg, $url, $notifyUserId, 'normal');
                }
            }
        }
    }
    
    
    /**
     * Ако отговорника на папката е системата
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if ($rec->inCharge == -1) {
            $row->inCharge = core_Setup::get('SYSTEM_NICK');
        }
        
        if($fields['-single']) {
            if(Mode::is('screenMode', 'narrow')) {
                $imageUrl = sbf($mvc->singleIcon, "");
                $row->SingleIcon = ht::createElement("img", array('src' => $imageUrl, 'alt' => ''));
            } else {
                $imageUrl = sbf(str_replace('/16/', '/24/', $mvc->singleIcon), "");
                $row->SingleIcon = ht::createElement("img", array('src' => $imageUrl, 'alt' => ''));
            }
        }
        $currUrl = getCurrentUrl();
      
        // Подготовка на линк към папката (или създаване на нова) на корицата
        if($fField = $mvc->listFieldForFolderLink) { 
            $folderTitle = $mvc->getFolderTitle($rec->id);
           
            if($rec->folderId && ($fRec = doc_Folders::fetch($rec->folderId))) {   
                if (doc_Folders::haveRightFor('single', $rec->folderId) && !$currUrl['Rejected']) { 
                    core_RowToolbar::createIfNotExists($row->_rowTools);
                    $row->_rowTools->addLink('Папка', array('doc_Threads', 'list', 'folderId' => $rec->folderId), array('ef_icon' => $fRec->openThreadsCnt ? 'img/16/folder-g.png' : 'img/16/folder-y.png', 'title' => "Папка към|* {$folderTitle}", 'class' => 'new-folder-btn'));
 
                    $row->{$fField} = ht::createLink($row->{$fField},
                            array('doc_Threads', 'list', 'folderId' => $rec->folderId),
                            NULL, array('ef_icon' => $fRec->openThreadsCnt ? 'img/16/folder-g.png' : 'img/16/folder-y.png', 'title' => "Папка към|* {$folderTitle}", 'class' => 'new-folder-btn', 'order' => 19));
                }
            } else {
            	if($mvc->hasPlugin('plg_RowTools2')){
            		if($mvc->haveRightFor('createnewfolder', $rec) && !$currUrl['Rejected']) {
            			core_RowToolbar::createIfNotExists($row->_rowTools);
            			$row->_rowTools->addLink('Папка', array($mvc, 'createFolder', $rec->id), array('ef_icon' => 'img/16/folder_new.png', 'title' => "Създаване на папка за документи към|* {$folderTitle}", 'class' => 'new-folder-btn', 'warning' => "Наистина ли желаете да създадетe папка за документи към|*  \"{$folderTitle}\"?", 'order' => 19));
            		}
            	}
            }
        }

        // В лист изгледа
        if($fields['-list']) {
        
        	// Имали бързи бутони
        	if($mvc->hasPlugin('plg_RowTools2') && $rec->state != 'rejected' && doc_Folders::haveRightToObject($rec)){
        		$managersIds = doc_Threads::getFastButtons($mvc, $rec->id);
        		if(count($managersIds)){
        		
        			// За всеки документ който може да се създаде от бърз бутон
        			foreach ($managersIds as $classId){
        				$Cls = cls::get($classId);
        				
        				if($Cls->haveRightFor('add', (object)array('folderId' => $mvc->forceCoverAndFolder($rec->id, FALSE)))){
        					$btnTitle = ($Cls->buttonInFolderTitle) ? $Cls->buttonInFolderTitle : $Cls->singleTitle;
        					$url = array($mvc, 'forcedocumentinfolder', 'id' => $rec->id, 'documentClassId' => $classId, 'ret_url' => TRUE);
        		
        					// Добавяме го в rowToolbar-а
        					core_RowToolbar::createIfNotExists($row->_rowTools);
        					$row->_rowTools->addLink($btnTitle, $url, "ef_icon = {$Cls->singleIcon},order=18,title=Създаване на " . mb_strtolower($Cls->singleTitle));
        				}
        			}
        		}
        	}
        }
    }
    
    
    /**
     * Вариант на doc_Folders::restrictAccess, който ограничава достъпа до записи, които
     * могат да са корици на папка, но не е задължително да имат създадена папка
     */
    public static function on_AfterRestrictAccess($mvc, $res, &$query, $userId = NULL, $viewAccess=TRUE)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
            
            if (!isset($userId)) {
                $userId = 0;
            }
        }
        
        $teammates = keylist::toArray(core_Users::getTeammates($userId));
        $managers  = (array)core_Users::getByRole('manager');
        $ceos = (array)core_Users::getByRole('ceo');
        
        // Подчинените в екипа (използва се само за мениджъри)
        $subordinates = array_diff($teammates, $managers);
        $subordinates = array_diff($subordinates, $ceos);
        
        // Премахваме текущия потребител
        unset($ceos[$userId]);
        
        foreach (array('teammates', 'ceos', 'managers', 'subordinates') as $v) {
            if (${$v}) {
                ${$v} = implode(',', ${$v});
            } else {
                ${$v} = FALSE;
            }
        }
        
        $conditions = array(
            "LOCATE('|{$userId}|', #folderShared) ", // Всеки има достъп до споделените с него папки
            "#folderInCharge = {$userId}",        // Всеки има достъп до папките, на които е отговорник
        );
        
        // Всеки (освен конракторите) имат достъп до публичните папки
        if (!core_Users::isContractor()) {
            $conditions[] = "#folderAccess = 'public'";
            
            if ($viewAccess) {
                $conditions[] = "#folderAccess = 'team'";
                $conditions[] = "#folderAccess = 'private'";
            }
        }

        if(core_Users::haveRole('ceo')) {
            
            // ceo има достъп до всички team папки, дори, когато са на друг ceo, който не е от неговия екип
            $conditions[] = "#folderAccess = 'team'";
        } elseif ($teammates) {

            // Всеки има достъп до екипните папки, за които отговаря негов съекипник
            $conditions[] = "#folderAccess = 'team' AND #folderInCharge IN ({$teammates})";
        }
        
        switch (true) {
            case core_Users::haveRole('ceo') :
                // CEO вижда всичко с изключение на private и secret папките на другите CEO
                // Ако има само един `ceo` и е текущия потребител, да не сработва
                if ($ceos) {
                    $conditions[] = "#folderInCharge NOT IN ({$ceos})";
                }
                
                // CEO да може да вижда private папките на друг `ceo`
                if ($viewAccess) {
                    $conditions[] = "#folderAccess != 'secret'";
                }
                
            break;
            case core_Users::haveRole('manager') :
                // Manager вижда private папките на подчинените в екипите си
                if ($subordinates) {
                    $conditions[] = "#folderAccess = 'private' AND #folderInCharge IN ({$subordinates})";
                }
            break;
        }
        
        if (!$query->fields['folderAccess']) {
            $query->XPR('folderAccess', 'varchar', '#access');
        }
        
        if (!$query->fields['folderInCharge']) {
            $query->XPR('folderInCharge', 'varchar', '#inCharge');
        }
        
        if (!$query->fields['folderShared']) {
            $query->XPR('folderShared', 'varchar', '#shared');
        }
        
        $query->where(core_Query::buildConditions($conditions, 'OR'));
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public static function on_AfterSetupMvc($mvc, &$res) 
    {
    	// Ако има папки с отговорник @system или @anonym, те стават на първия admin или ceo
    	self::transferEmptyOwnership($mvc, $res);
    }
    
    
    /**
     * Прехвърля от празен отговорник на първия админ или ceo
     * 
     * @param core_Mvc $mvc
     * @param string $html
     */
    public static function transferEmptyOwnership(core_Mvc $mvc, &$html)
    {
    	$transfered = 0;
    	
    	// Кой е дефолт отговорника
    	$inCharge = self::getDefaultInCharge();
    	
    	// Намираме всички записи от модела без отговорник
    	$query = $mvc->getQuery();
    	$query->where("#inCharge IS NULL OR #inCharge = '-1' OR #inCharge = 0");
    	if($query->count()){
    		while($rec = $query->fetch()){
    			
    			// Сменяме им отговорника на дефолт отговорника
    			$rec->inCharge = $inCharge;
    			$mvc->save_($rec, 'inCharge');
    			$transfered ++;
    		}
    	}
    	
    	if($transfered && $inCharge && ($inCharge > 0)) {
    		$userNick = core_Users::fetchField($inCharge, 'nick');
    		$html .= "<li> {$userNick} стана отговорник на {$transfered} папки на {$mvc->className}</li>";
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        
        if ($form->isSubmitted()) {
            
            // Обхождаме всички полета от модела, за да разберем кои са ричтекст
            foreach ((array)$mvc->fields as $name=>$field) {
                if ($field->type instanceof type_Richtext) {
                    
                    if ($field->type->params['nickToLink'] == 'no') continue;
                    
                    // Вземаме споделените потребители
                    $sharedUsersArr = rtac_Plugin::getNicksArr($rec->$name);
                    if (!$sharedUsersArr) continue;
                    
                    // Обединяваме всички потребители от споделянията
                    $sharedUsersArr = array_merge($sharedUsersArr, $sharedUsersArr);
                }
            }
            
            // Ако има споделяния
            if ($sharedUsersArr) {
                
                // Добавяме id-тата на споделените потребители
                foreach ((array)$sharedUsersArr as $nick) {
                    $nick = strtolower($nick);
                    $id = core_Users::fetchField(array("LOWER(#nick) = '[#1#]'", $nick), 'id');
                    $rec->shared = type_Keylist::addKey($rec->shared, $id);
                }
            }
            
            // Ако след записа няма да имаме достъп до корицата слагаме предупреждение
            if(!doc_Folders::haveRightToObject($rec)){
            	$form->setWarning('inCharge,access', 'След запис няма да имате достъп до корицата');
            }
        }
        
        if ($form->isSubmitted()) {
            
            if ($rec->id) {
                $oRec = $mvc->fetch($form->rec->id);
                $rec->__mustNotify = TRUE;
                $rec->__oShared = $oRec->shared;
                $rec->__oInCharge = $oRec->inCharge;
            }
        }
    }
    
    
    /**
     * След рендиране на лист таблицата
     */
    public static function on_AfterRenderListTable($mvc, &$tpl, &$data)
    {
        if (Request::get('share')) {
            bgerp_Notifications::clear(array($mvc, 'list', 'share' => TRUE));
        }
    }
    
    
    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     */
    public static function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
    	// Ако има форма, и тя е събмитната и действието е 'запис'
    	if ($data->form && $data->form->isSubmitted() && $data->form->cmd == 'save') {
    		
    		// Ако имаме достъп до корицата и тя наследява core_Master пренасочваме към сингъла
    		if(doc_Folders::haveRightToObject($data->form->rec) && $mvc instanceof core_Master){
    			
                if(is_array($data->retUrl) && (strtolower($data->retUrl[1]) == 'list' || strtolower($data->retUrl[1]) == 'default' || strtolower($data->retUrl['Act']) == 'list' || strtolower($data->retUrl['Act']) == 'default')) {
    			    $data->retUrl = toUrl(array($mvc, 'single', $data->form->rec->id));
                }
    		} else {
    			
    			// Ако нямаме достъп, пренасочваме към списъчния изглед
    			$data->retUrl = toUrl(array($mvc, 'list'));
    		}
    	}
    }
    
    
    /**
     * Кои документи да се показват като бързи бутони в папката на корицата
     */
    public static function on_AfterGetDocButtonsInFolder($mvc, &$res, $id)
    {
    	if(!$res){
    		
    		// Ако има зададени такива тях, иначе никои
    		if(isset($mvc->defaultDefaultDocuments)){
    			$res = arr::make($mvc->defaultDefaultDocuments);
    		} else {
    			$res = array();
    		}
    	}
    }
    
    
    /**
     * Филтрираме заявката преди експорт
     * 
     * @param core_Mvc $mvc
     * @param core_Query $query
     */
    public static function on_AfterPrepareExportQuery($mvc, $query)
    {
        if (!Request::get('Rejected')) {
            $query->where("#state != 'rejected'");
        }
    }
}