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
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class doc_FolderPlg extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        if(!$mvc->fields['folderId']) {
            
            if($mvc->className != 'doc_Folders') {
                
                // Поле за id на папката. Ако не е зададено - обекта няма папка
                $mvc->FLD('folderId', 'key(mvc=doc_Folders)', 'caption=Папка,input=none');
            }
            
            // Определя достъпа по подразбиране за новите папки
            setIfNot($defaultAccess, $mvc->defaultAccess, 'team');
            
            $mvc->FLD('inCharge' , 'key(mvc=core_Users, select=nick)', 'caption=Права->Отговорник,formOrder=10000');
            $mvc->FLD('access', 'enum(team=Екипен,private=Личен,public=Общ,secret=Секретен)', 'caption=Права->Достъп,formOrder=10001,notNull,value=' . $defaultAccess);
            $mvc->FLD('shared' , 'userList', 'caption=Права->Споделяне,formOrder=10002');
        }
        
        // Добавя интерфейс за папки
        $mvc->interfaces = arr::make($mvc->interfaces);
        setIfNot($mvc->interfaces['doc_FolderIntf'], 'doc_FolderIntf');

        $mvc->details = arr::make($mvc->details);

        $mvc->details['Rights'] = $mvc->className;
    }
    
    
    public static function on_AfterPrepareRights($mvc, $res, $data)
    {
        $data->TabCaption = 'Права';
        doc_FolderToPartners::preparePartners($data);

    }

    
    public static function on_AfterRenderRights($mvc, &$tpl, $data)
    {
        $tpl = new ET(tr('|*' . getFileContent('doc/tpl/RightsLayout.shtml')));
                
        $tpl->placeObject($data->masterData->row);
        doc_FolderToPartners::renderPartners($data, $tpl);
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
            			array('ef_icon' => $fRec->openThreadsCnt ? 'img/16/folder.png' : 'img/16/folder-y.png'));
            }
            
        } else {
        	if($mvc->haveRightFor('single', $data->rec) && $mvc->haveRightFor('write', $data->rec)){
        		$title = $mvc->getFolderTitle($data->rec->id);
        		$data->toolbar->addBtn('Папка', array($mvc, 'createFolder', $data->rec->id), array(
        				'warning' => "Наистина ли желаете да създадетe папка за документи към|* \"{$title}\"?",
        		), array('ef_icon' => 'img/16/folder_new.png', 'title' => "Създаване на папка за документи към {$title}"));
        	}
        	
        	$currUrl = getCurrentUrl();
        	
        	if($mvc->haveRightFor('single', $data->rec) && $currUrl['Act'] == 'single'){
        		$title = $mvc->getFolderTitle($data->rec->id);
        		$data->toolbar->addBtn('Папка', array($mvc, 'createFolder', $data->rec->id), array(
        				'warning' => "Наистина ли желаете да създадетe папка за документи към|* \"{$title}\"?",
        		), array('ef_icon' => 'img/16/folder_new.png', 'title' => "Създаване на папка за документи към {$title}"));
        	}
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
        if ($action == 'reject' && $rec->folderId) {
            
            // Ако има запис, който не е оттеглен
            if (doc_Threads::fetch("#folderId = '{$rec->folderId}' && #state != 'rejected'")) {
                
                // Никой да не може да оттегля папката
                $requiredRoles = 'no_one';    
            }
        }
        
        if ($rec->id && ($action == 'delete' || $action == 'edit' || $action == 'write' || $action == 'single' || $action == 'newdoc')) {
            
            $rec = $mvc->fetch($rec->id);
            
            // Ако модела е достъпен за всички потребители по подразбиране, 
            // но конкретния потребител няма права за конкретния обект
            // забраняваме достъпа
            if (!doc_Folders::haveRightToObject($rec, $userId)) {
                
                if($requiredRoles != 'no_one'){
                	
                	// Ако има зададени мастър роли за достъп
            		$requiredRoles = $mvc->coverMasterRoles ? $mvc->coverMasterRoles : 'no_one';
            	}
            }

            if($rec->state == 'rejected' && $action != 'single') {
                $requiredRoles = 'no_one';
            }
            
            if($action == 'delete' && $rec->folderId) {
                $requiredRoles = 'no_one';
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
        if(!haveRole('ceo') && ($cu = core_Users::getCurrent())) {
            $data->query->where("NOT (#access = 'secret' AND #inCharge != $cu AND !(#shared LIKE '%|{$cu}|%')) || (#access IS NULL)");
        }
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
                $rec = $mvc->fetch($rec);
            } elseif($rec->id) {
                $rec = $mvc->fetch($rec->id);
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
        if($action != 'createfolder' || $mvc->className == 'doc_Folders') return;
        
        // Входни параметри и проверка за права
        expect($id = Request::get('id', 'int'));
        expect($rec = $mvc->fetch($id));
        
        $mvc->requireRightFor('single', $rec);
        
        $mvc->requireRightFor('write', $rec);
        
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
        	$res = new Redirect(array('doc_Threads', 'list', 'folderId' => $rec->folderId), 'Папката е създадена успешно');
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
    }
    
    
    /**
     * Ако отговорника на папката е системата
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if ($rec->inCharge == -1) {
            $row->inCharge = '@system';
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
                    $row->folder = ht::createLink('',
                            array('doc_Threads', 'list', 'folderId' => $rec->folderId),
                            NULL, array('ef_icon' => $fRec->openThreadsCnt ? 'img/16/folder.png' : 'img/16/folder-y.png', 'title' => "Папка към {$folderTitle}", 'class' => 'new-folder-btn'));
                }
            } else {
                if($mvc->haveRightFor('single', $rec->id) && !$currUrl['Rejected']) {
                    $row->{$fField} = ht::createLink('', array($mvc, 'createFolder', $rec->id),  "Наистина ли желаете да създадетe папка за документи към  \"{$folderTitle}\"?",
                    array('ef_icon' => 'img/16/folder_new.png', 'title' => "Създаване на папка за документи към {$folderTitle}", 'class' => 'new-folder-btn'));
                }
            }
        }

    }
    
    
    /**
     * Вариант на doc_Folders::restrictAccess, който ограничава достъпа до записи, които
     * могат да са корици на папка, но не е задължително да имат създадена папка
     */
    public static function on_AfterRestrictAccess($mvc, $res, &$query, $userId = NULL, $fullAccess=TRUE)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
            
            if (!isset($userId)) {
                $userId = 0;
            }
        }
        
        $teammates = keylist::toArray(core_Users::getTeammates($userId));
        $managers  = core_Users::getByRole('manager');
        $ceos = core_Users::getByRole('ceo');
        
        // Подчинените в екипа (използва се само за мениджъри)
        $subordinates = array_diff($teammates, $managers);
        $subordinates = array_diff($subordinates, $ceos);
        
        foreach (array('teammates', 'ceos', 'managers', 'subordinates') as $v) {
            if (${$v}) {
                ${$v} = implode(',', ${$v});
            } else {
                ${$v} = FALSE;
            }
        }
        
        $conditions = array(
            "#access = 'public'",           // Всеки има достъп до публичните папки
            "#shared LIKE '%|{$userId}|%'", // Всеки има достъп до споделените с него папки
            "#inCharge = {$userId}",        // Всеки има достъп до папките, на които е отговорник
        );
        
        if ($teammates) {
            // Всеки има достъп до екипните папки, за които отговаря негов съекипник
            $conditions[] = "#access = 'team' AND #inCharge IN ({$teammates})";
        }
        
        switch (true) {
            case core_Users::haveRole('ceo') :
                // CEO вижда всичко с изключение на private и secret папките на другите CEO
                if ($ceos) {
                    $conditions[] = "#inCharge NOT IN ({$ceos})";
                }
                
                // CEO да може да вижда private папките на друг `ceo`
                if ($fullAccess) {
                    $conditions[] = "#access != 'secret'";
                }
                
            break;
            case core_Users::haveRole('manager') :
                // Manager вижда private папките на подчинените в екипите си
                if ($subordinates) {
                    $conditions[] = "#access = 'private' AND #inCharge IN ({$subordinates})";
                }
            break;
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
        if ($form->isSubmitted()) {
            
            $rec = &$form->rec;
            
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
    			$data->retUrl = toUrl(array($mvc, 'single', $data->form->rec->id));
    		} else {
    			
    			// Ако нямаме достъп, пренасочваме към списъчния изглед
    			$data->retUrl = toUrl(array($mvc, 'list'));
    		}
    	}
    }
}