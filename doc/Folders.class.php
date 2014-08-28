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
     * Максимална дължина на показваните заглавия 
     */
    const maxLenTitle = 48;
    
    
    /**
     * Интерфейси
     */
    var $interfaces = 'custom_SettingsIntf';
    
    
    /**
     * Кой може да модофицира
     * @see custom_SettingsIntf
     */
    var $canModify = 'powerUser';
    
    
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
    var $canRead = 'powerUser';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'powerUser';
    
    
    /**
     * Кой може да добавя?
     */
    var $canAdd = 'no_one';

    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'powerUser';
    
    
    /**
     * 
     */
    var $canNewdoc = 'powerUser';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'powerUser';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'powerUser';
    
    
    /**
     * полета от БД по които ще се търси
     */
    var $searchFields = 'title';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Папка';
    

    /**
     * Масив в id-та на папки, които трябва да се обновят на Shutdown
     */
    var $updateByContentOnShutdown = array();


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
     * Връща линк към документа
     */
    static function getLink($id, $maxLength = FALSE, $attr = array())
    {
        $rec = static::fetch($id);
        $haveRight = static::haveRightFor('single', $rec);
        
        $iconStyle = 'background-image:url(' . static::getIconImg($rec, $haveRight) . ');';
        $url = array('doc_Folders', 'single', $id);
        
        $title = static::getVerbal($rec, 'title');
        
        if(!static::haveRightFor('single', $id)) {
            $url =  array();
        }
        
        $attr['class'] .= ' linkWithIcon';
        $attr['style'] .= $iconStyle;
        
        if ($rec->state == 'rejected') {
            $attr['class'] .= ' state-rejected';
        }

        $link = ht::createLink($title, $url, NULL, $attr);
        
        return $link;
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
		$data->listFilter->FNC('users', 'users(rolesForAll = |officer|manager|ceo|)', 'caption=Потребител,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
		$data->listFilter->FNC('order', 'enum(pending=Първо чакащите,last=Сортиране по "последно")', 'caption=Подредба,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
		$data->listFilter->view = 'horizontal';
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
		// Показваме само това поле. Иначе и другите полета
		// на модела ще се появят
		$data->listFilter->showFields = 'search,users,order';
		$data->listFilter->input('search,users,order', 'silent');
		
		if(!$data->listFilter->rec->users) {
			$data->listFilter->rec->users = '|' . core_Users::getCurrent() . '|';
		}
		
		if(!$data->listFilter->rec->search) {
			$data->query->where("'{$data->listFilter->rec->users}' LIKE CONCAT('%|', #inCharge, '|%')");
			$data->query->orLikeKeylist('shared', $data->listFilter->rec->users);
			$data->title = 'Папките на |*<span class="green">' .
			$data->listFilter->getFieldType('users')->toVerbal($data->listFilter->rec->users) . '</span>';
		} else {
			$data->title = 'Търсене на папки отговарящи на |*<span class="green">"' .
			$data->listFilter->getFieldType('search')->toVerbal($data->listFilter->rec->search) . '"</span>';
		}
		
		// Ограничения при показване на папките
		static::restrictAccess($data->query);
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
        
        // Всеки има право на достъп до папката за която отговаря
        if($rec->inCharge === $userId) return TRUE;
        
        // Всеки има право на достъп до папките, които са му споделени
        if(strpos($rec->shared, '|' . $userId . '|') !== FALSE) return TRUE;
        
        // Всеки има право на достъп до общите папки
        if($rec->access == 'public') return TRUE;
        
        // 'ceo' има достъп до всяка папка
        if (core_Users::haveRole('ceo', $userId)) {
            
            // с изключение на личните и секретните на други CEO
            if (core_Users::haveRole('ceo', $rec->inCharge) && (($rec->access == 'private') || ($rec->access == 'secret'))) {
                return FALSE;
            } else {
                return TRUE;
            }
        }
        
        // Вземаме членовете на екипа на потребителя
        $teamMembers = core_Users::getTeammates($userId);
        
        // Дали обекта има отговорник - съекипник
        $fromTeam = strpos($teamMembers, '|' . $rec->inCharge . '|') !== FALSE;
        
        // Ако папката е екипна, и е на член от екипа на потребителя, и потребителя е manager или officer или executive - има достъп
        if($rec->access == 'team' && $fromTeam && core_Users::haveRole('manager,officer,executive', $userId)) return TRUE;
        
        // Ако собственика на папката има права 'manager' или 'ceo' отказваме достъпа
        if(core_Users::haveRole('manager,ceo', $rec->inCharge)) return FALSE;
        
        // Ако папката е лична на член от екипа, и потребителя има права 'manager' - има достъп
        if($rec->access == 'private' && $fromTeam && core_Users::haveRole('manager', $userId)) return TRUE;
        
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
            $row->threads = "<span style='float-right; color:#5a6;'>$openThreads</span>";
        }
        
        $row->threads .= "<span style='float:right;'>&nbsp;&nbsp;&nbsp;" . $mvc->getVerbal($rec, 'allThreadsCnt') . "</span>";
        
        $attr['class'] = 'linkWithIcon';
        
        
        if(mb_strlen($row->title) > self::maxLenTitle) {
            $attr['title'] = $row->title;
        }

        $row->title = str::limitLen($row->title, self::maxLenTitle);
        
        $haveRight = $mvc->haveRightFor('single', $rec);
        
        // Иконката на папката според достъпа и
        $img = static::getIconImg($rec, $haveRight);
        
        // Ако състоянието е оттеглено
        if ($rec->state == 'rejected') {
            
           // Добавяме към класа да е оттеглено
            $attr['class'] .= ' state-rejected';
        }
        
        if($haveRight) {
            $attr['style'] = 'background-image:url(' . $img . ');';
            $link = array('doc_Threads', 'list', 'folderId' => $rec->id);
            
            // Ако е оттеглен
            if ($rec->state == 'rejected') {
                
                // Да сочи към коша
                $link['Rejected'] = 1;
            }
            $row->title = ht::createLink($row->title, $link, NULL, $attr);
        } else {
            $attr['style'] = 'color:#777;background-image:url(' . $img . ');';
            $row->title = ht::createElement('span', $attr, $row->title);
        }
        
        $typeMvc = cls::get($rec->coverClass);
        
        $attr['style'] = 'background-image:url(' . sbf($typeMvc->singleIcon) . ');';

        if($typeMvc->haveRightFor('single', $rec->coverId)) {
            $row->type = ht::createLink(tr($typeMvc->singleTitle), array($typeMvc, 'single', $rec->coverId), NULL, $attr);
        } else {
            $attr['style'] .= 'color:#777;';
            $row->type = ht::createElement('span', $attr, tr($typeMvc->singleTitle));
        }

        if($rec->inCharge){
        	$row->inCharge = crm_Profiles::createLink($rec->inCharge);
        }
    }
    

    /**
     * Добавя бутони за нова фирма, лице и проект
     */
    static function on_AfterPrepareListToolbar($mvc, $data)
    {
        $data->toolbar->addBtn('Нова фирма', array('crm_Companies', 'add', 'ret_url' => TRUE), 'ef_icon=img/16/group.png');
        $data->toolbar->addBtn('Ново лице', array('crm_Persons', 'add', 'ret_url' => TRUE), 'ef_icon=img/16/vcard.png');
        $data->toolbar->addBtn('Нов проект', array('doc_UnsortedFolders', 'add', 'ret_url' => TRUE), 'ef_icon=img/16/basket.png');
    }
    
    
    static function updateFolderByContent($id)
    {
        $mvc = cls::get('doc_Folders');
        $mvc->updateByContentOnShutdown[$id] = $id;
    }

    
    /**
     * Обновява информацията за съдържанието на дадена папка
     */
    static function on_Shutdown($mvc)
    {
        // Първо изпълняваме shutdown процедурата на doc_Threads, тъй-като кода по-долу зависи
        // от нейното действие, а не е гарантирано, че doc_Threads::on_Shutdown() е вече
        // изпълнен.
        doc_Threads::doUpdateThread();
        
        if(count($mvc->updateByContentOnShutdown)) {
            foreach($mvc->updateByContentOnShutdown as $id) {
                // Извличаме записа на папката
                $rec = doc_Folders::fetch($id);

                if(!$rec) {
                    return;
                }
                
                // Запомняме броя на отворените теми до сега
                $exOpenThreadsCnt = $rec->openThreadsCnt;
                
                $thQuery = doc_Threads::getQuery();
                $rec->openThreadsCnt = $thQuery->count("#folderId = {$id} AND state = 'opened'");
                
                // Възстановяване на корицата, ако е оттеглена.
                self::getCover($rec)->restore();
                
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
                    
                    // id на класа
                    $folderClassId = doc_Folders::getClassId();
                    
                    // Вземаме данните
                    $noNotificationsUsersArr = custom_Settings::fetchUsers($folderClassId, $rec->id, 'folOpenings');
                    
                    $userId = $rec->inCharge;
                    
                    $msg = '|Отворени теми в|*' . " \"$rec->title\"";
                    
                    $url = array('doc_Threads', 'list', 'folderId' => $id);
                        
                    $priority = 'normal';
                    
                    // В зависимост от персонализацията избираме дали да покажим нотификацията
                    if ($noNotificationsUsersArr[$userId] == 'yes' || ($noNotificationsUsersArr[$userId] != 'no' && (!$noNotificationsUsersArr[-1] || $noNotificationsUsersArr[-1] == 'yes'))) {                        
                        
                        bgerp_Notifications::add($msg, $url, $userId, $priority);
                    }
                    
                    if($rec->shared) {
                        foreach(keylist::toArray($rec->shared) as $userId) {
                            
                            // В зависимост от персонализацията избираме дали да покажим нотификацията
                            if ($noNotificationsUsersArr[$userId] == 'yes' || ($noNotificationsUsersArr[$userId] != 'no' && (!$noNotificationsUsersArr[-1] || $noNotificationsUsersArr[-1] == 'yes'))) {
                                bgerp_Notifications::add($msg, $url, $userId, $priority);
                            }
                        }
                    }
                } elseif($exOpenThreadsCnt > 0 && $rec->openThreadsCnt == 0) {
                    // Изчистване на нотификации за отворени теми в тази папка
                    $url = array('doc_Threads', 'list', 'folderId' => $rec->id);
                    bgerp_Notifications::clear($url, '*');
                }
            }
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

        $isRevert = ($rec->state == 'rejected' && $coverRec->state != 'rejected');
        $isReject = ($rec->state != 'rejected' && $coverRec->state == 'rejected');
        
        $fields = 'title,inCharge,access,shared';
        
        foreach(arr::make($fields) as $field) {
            if($rec->{$field} != $coverRec->{$field}) {
                $rec->{$field} = $coverRec->{$field};
                $mustSave = TRUE;
            }
        }

    	if($isReject) {
			$rec->state = 'rejected';
			$mustSave = TRUE;
		}

		if($isRevert) {
			$mustSave = TRUE;
		}
                
        if($mustSave) {
            if($isRevert || !$rec->state) {
                $rec->state = 'open';
            }

            static::save($rec);
            
            // Ако сега сме направили операцията възстановяване
            if($isRevert || !$rec->state) {
                self::updateFolderByContent($rec->id);
            }
            
            // URL за нотификациите
            $keyUrl = array('doc_Threads', 'list', 'folderId' => $id);
            
            // Ако оттегляме
            if ($isReject) {
                
                // Скриваме нотификациите
                bgerp_Notifications::setHidden($keyUrl, 'yes');
                
                // Скриваме последно
                bgerp_Recently::setHidden('folder', $id, 'yes');
            } elseif ($isRevert) {
                
                // Скриваме нотификациите
                bgerp_Notifications::setHidden($keyUrl, 'no');
                
                // Скриваме последно
                bgerp_Recently::setHidden('folder', $id, 'no');
            }
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
        
        expect($rec->coverClass);
        
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
     * @param boolean $fullAccess - Възможно най - много права за папката
     */
    static function restrictAccess(&$query, $userId = NULL, $fullAccess=TRUE)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
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
                
                // CEO да може да вижда private папките на друг `ceo`
                if ($fullAccess) {
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
     * Първо проверява в обръщенията, после в папката
     *
     * @param int $id - id' то на папката
     *
     * @return string $lg - Двубуквеното означение на предполагаемия език на имейла
     */
    static function getLanguage($id)
    {
        //Ако няма стойност, връщаме
        if (!$id) return ;
        
        // Търсим езика в поздравите
        $lg = email_Salutations::getLg($id, NULL);

        // Ако сме открили езика в обръщенията
        if ($lg) return $lg;
        
        //id' то на класа, който е корица
        $coverClassId = doc_Folders::fetchField($id, 'coverClass');
        
        //Името на корицата на класа
        $coverClass = cls::getClassName($coverClassId);
        
        //Ако корицата не е Лице или Фирма
        if (($coverClass != 'crm_Persons') && ($coverClass != 'crm_Companies')) return ;
            
        //Вземаме държавата
        $classRec = $coverClass::fetch("#folderId = '{$id}'", 'country');
        
        //Ако няма въведена държава
        if (!$classRec->country) return ;
            
        // Вземаме стринга с официалните езици
        $lgStr = drdata_Countries::fetchField($classRec->country, 'languages');
        
        // Ако няма нищо
        if (!$lgStr) return ;
        
        // Превръщаме в масив
        $lgArr = explode(',', $lgStr);
        
        // Обхождаме масива
        foreach ((array)$lgArr as $lg) {
            
            // Ако няам език прескачаме
            if (!$lg) continue;
            
            // Първия добър език
            return strtolower($lg);
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

        if ($inboxId = email_Outgoings::getDefaultInboxId(NULL, $userId)) {
            $inboxRec = email_Inboxes::fetch($inboxId);
            
            $rec->email = $inboxRec->email;
            $rec->accountId = $inboxRec->accountId;
            $folderId = email_Inboxes::forceCoverAndFolder($rec);
        } else {
            
            $corpAccRec = email_Accounts::getCorporateAcc();
            
            if($corpAccRec) {
                $rec->email = "{$nick}@{$corpAccRec->domain}";
                $rec->accountId = $corpAccRec->id;
                $folderId = email_Inboxes::forceCoverAndFolder($rec);
            } else {
                $rec->name = "Документите на {$nick}";
                $folderId = doc_UnsortedFolders::forceCoverAndFolder($rec);
            }
        } 

        return $folderId;
    }
    
    
    /**
     * Връща линка на папката във вербален вид
     * 
     * @param array $params - Масив с частите на линка
     * @param $params['Ctr'] - Контролера
     * @param $params['Act'] - Действието
     * @param $params['folderId'] - id' то на папката
     * 
     * @return $res - Линк
     */
    static function getVerbalLink($params)
    {
        // Проверяваме дали е число
        if (!is_numeric($params['folderId'])) return FALSE;
        
        // Записите за папката
        $rec = static::fetch($params['folderId']);
            
        $haveRight = static::haveRightFor('single', $rec);
        
        // Проверяваме дали има права
        if (!$rec || (!($haveRight) && $rec->access != 'private')) return FALSE;

        // Заглавието на файла във вербален вид
        $title = static::getVerbal($rec, 'title');
        
        // Иконата на папката
        $sbfIcon = static::getIconImg($rec, $haveRight);
        
        if (Mode::is('text', 'plain')) {

            // Ескейпваме плейсхолдърите и връщаме титлата
            $res = core_ET::escape($title);
        } elseif (Mode::is('text', 'xhtml') || !$haveRight) {
            
            // Ескейпваме плейсхолдърите
            $title = core_ET::escape($title);
            
            // TODO може да се използва този начин вместо ескейпването
            //$res = new ET("<span class='linkWithIcon' style='background-image:url({$sbfIcon});'> [#1#] </span>", $title);
            
            // Добаваме span с иконата и заглавиетео - не е линк
            // TODO класа да не е linkWithIcon
            $res = "<span class='linkWithIcon' style='background-image:url({$sbfIcon});'> {$title} </span>";    
        } else {

            // Дали линка да е абсолютен
            $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
            
            // Линка
            $link = toUrl($params, $isAbsolute);

            // Атрибути на линка
            $attr['class'] = 'linkWithIcon';
            $attr['style'] = "background-image:url({$sbfIcon})";    
            $attr['target'] = '_blank'; 

            // Създаваме линк
            $res = ht::createLink($title, $link, NULL, $attr); 
        }
        
        return $res;
    }
    
    
    public static function fetchCoverClassId($id)
    {
        return static::fetchField($id, 'coverClass');
    }
    
    
    /**
     * Името на класа на корицата на папка
     * 
     * @param int $id key(mvc=doc_Folders)
     * @return string име на PHP клас-наследник на core_Mvc
     */
    public static function fetchCoverClassName($id)
    {
        $folderClass = static::fetchCoverClassId($id);
        $folderClassName = cls::getClassName($folderClass);
        
        return $folderClassName;
    }
    
    
    public static function fetchCoverId($id)
    {
        return static::fetchField($id, 'coverId');
    }
    
    
    /**
     * Инстанция на корицата.
     * 
     * Резултата има всички методи, налични в мениджъра на корицата
     * 
     * @param int|stdClass $id идентификатор или запис на папка
     * @return core_ObjectReference
     */
    public static function getCover($id)
    {
        expect($rec = static::fetchRec($id));

        $cover = new core_ObjectReference($rec->coverClass, $rec->coverId);
        
        return $cover;
    }
    

    /**
     * Поправка на структурата на папките
     */
    function repair()
    {
        $query = $this->getQuery();

        while($rec = $query->fetch()) {
            
            if(!$rec->inCharge > 0) {
                $err[$rec->id] .= 'Missing inCharge; ';
                $rec->inCharge = core_Users::getCurrent();
            }
            
            $projectName = FALSE;

            if(!$rec->coverClass) {
                $err[$rec->id] .= 'Missing coverClass; ';
                $projectName = "LaF " . $rec->title;
            } else {
            
                if(!($cls =  cls::load($rec->coverClass, TRUE))) {
                    $err[$rec->id] .= 'Not exists coverClass; ';
                    $projectName = "LaF " . $rec->title;
                } else {
                    if(!$rec->coverId) {
                        $err[$rec->id] .= 'Not exists coverId; ';
                        $projectName = "LaF " . $className . ' ' . $rec->title;
                    } else {

                        $cls = cls::get($rec->coverClass);

                        if(!$cls->fetch($rec->coverId)) {
                            $err[$rec->id] .= 'Not exists cover; ';
                            $projectName = "LaF " . $className . ' ' . $rec->title;
                        }
                    }
                }
            }

            if($projectName) {
                $rec->coverClass = core_Classes::fetchIdByName('doc_UnsortedFolders');
                $rec->coverId = 0;
                $this->save($rec);
                $unRec = new stdClass();
                $unRec->name = $projectName . ' ' . doc_UnsortedFolders::count();
                $unRec->inCharge = core_Users::getCurrent();
                $unRec->folderId = $rec->id;
                $rec->coverId = doc_UnsortedFolders::save($unRec);
                $this->save($rec);
            }
            
            if(!$rec->title) {
                $err[$rec->id] .= 'Missing title; ';
            }
        }
        
        if(count($err)) {
            foreach($err as $id => $msg) {
                $res .= "<li> $id => $msg </li>";
            }
        }

        return $res;
    }
    

    /**
     * Екшън за поправка на структурите в документната система
     */
    function act_Repair()
    {
        requireRole('admin');

        core_Debug::$isLogging = FALSE;

        $Folders = cls::get('doc_Folders');
        set_time_limit($Folders->count());
        $html .= $Folders->repair();
        
        $Containers = cls::get('doc_Containers');
        set_time_limit($Containers->count());
        $html .= $Containers->repair();

        $Router = cls::get('email_Router');
        set_time_limit($Router->count());
        $html .= $Router->repair();

        return new Redirect(array('core_Packs'), $html);
    }
    

    /**
     * Връща иконата на папката според достъпа
     * 
     * @params object $rec - Данните за записа
     * @param boolean $haveRight - Дали има права за single
     * 
     * @return string $sbfImg - Иконата
     */
    static function getIconImg($rec, $haveRight = FALSE)
    {
        switch($rec->access) {
            case 'secret' :
                $img = 'folder_key.png';
            break;
            
            case 'private' :
                if ($haveRight) {
                    $img = 'folder_user.png';    
                } else {
                    $img = 'lock.png';
                }
                
            break;
            
            case 'team' :
            case 'public' :
            default :
                $img = 'folder-icon.png';
            break;
        }
        
        // Дали линка да е абсолютен
        $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        // Връщаме sbf линка до иконата
        $sbfImg = sbf('img/16/' . $img, '"', $isAbsolute);

        return $sbfImg;        
    }

    /**
     * Връща масив с всички активни потребители, които имат достъп до дадена папка
     * 
     * @param doc_Folders $folderId - id на папката
     * @param boolean $removeCurrent - Дали да се премахне текущия потребител от резултатите
     * 
     * @return array $sharedUsersArr - Масив с всички споделени потребители
     */
    static function getSharedUsersArr($folderId, $removeCurrent=FALSE)
    {
        // Масив с потребителите, които имат права за папката
        $sharedUsersArr = array();
        
        // Вземаме всички активни потребители
        $userQuery = core_Users::getQuery();
        $userQuery->where("#state='active'");
        while ($rec = $userQuery->fetch()) {
            
            // Ако потребителя има права за single в папката
            if (doc_Folders::haveRightFor('single', $folderId, $rec->id)) {
                
                // Добавяме в масива
                $sharedUsersArr[$rec->id] = core_Users::getVerbal($rec, 'nick');
            }
        }
        
        // Ако е зададен да се премахне текущия потребител от масива и има такъв потребител
        if ($removeCurrent && ($currUser = core_Users::getCurrent())) {
            
            // Премахваме от масива текущия потребител
            unset($sharedUsersArr[$currUser]);
        }
        
        return $sharedUsersArr;
    }
    
    
    /**
     * Извиква се след изчисляване на ролите необходими за дадено действие
     * 
     * @param doc_Folders $mvc
     * @param string $res
     * @param string $action
     * @param object $rec
     * @param integer $userId
     */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec, $userId = NULL)
    {
        // @see custom_Settings
        if ($action == 'modify' && $rec) {
            if (!$mvc->haveRightFor('single', $rec)) {
                $res = 'no_one';
            }
        }
    }
    
    
    /**
     * Интерфейсен метод на custom_SettingsIntf
     * Подготвяме формата за персонализиране на настройките за нишката
     * 
     * @param core_Form $form
     * @see custom_SettingsIntf
     */
    function prepareCustomizationForm(&$form)
    {
        // Задаваме таба на менюто да сочи към документите
        Mode::set('pageMenu', 'Документи');
        Mode::set('pageSubMenu', 'Всички');
        $this->currentTab = 'Теми';
        
        // Определяме заглавито
        $rec = $this->fetch($form->rec->objectId);
        $row = $this->recToVerbal($rec, 'title');
        $form->title = 'Настройка на|*: ' . $row->title;
        
        // Добавяме функционални полета
        $form->FNC('folOpenings', 'enum(default=Автоматично, yes=Винаги, no=Никога)', 'caption=Отворени нишки->Известяване, input=input');
        $form->FNC('perPage', 'enum(default=Автоматично, 10=10, 20=20, 40=40, 100=100, 200=200)', 'caption=Теми на една страница->Брой, input=input');
        $form->FNC('ordering', 'enum(default=Автоматично, opened=Първо отворените, recent=По последно, create=По създаване, numdocs=По брой документи)', 'caption=Подредба на нишките->Правило, input=input');
        
        // Задаваме стойностите по подразбиране
//        $form->setDefault('folOpenings', 'default');
//        $form->setDefault('perPage', 'default');
//        $form->setDefault('ordering', 'default');
        
        // Сетваме стринг за подразбиране
        $defaultStr = 'По подразбиране|*: ';
        
        // Ако сме в мобилен режим, да не е хинт
        $paramType = Mode::is('screenMode', 'narrow') ? 'unit' : 'hint';
        
        // Сетваме стойност по подразбиране
        $form->setParams('folOpenings', array($paramType => $defaultStr . '|Винаги'));
        $form->setParams('perPage', array($paramType => $defaultStr . '20'));
        $form->setParams('ordering', array($paramType => $defaultStr . '|Първо отворените'));
    }
    
    
    /**
     * Интерфейсен метод на custom_SettingsIntf
     * Проверява въведените данни във формата
     * 
     * @param core_Form $form
     * @see custom_SettingsIntf
     */
    function checkCustomizationForm(&$form)
    {
        
        return ;
    }
}
