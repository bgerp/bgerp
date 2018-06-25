<?php



/**
 * Клас 'colab_FolderToPartners' - Релация между партньори и папки
 *
 *
 * @category  bgerp
 * @package   colab
 * @author    Milen Georgiev <milen@download.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class colab_FolderToPartners extends core_Manager
{   

     
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'doc_FolderToPartners';
	
	
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, doc_Wrapper, plg_RowTools2';
    
    
     /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой може да пише
     */
    public $canWrite = 'officer';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'admin';
    
    
    /**
     * Кой може да добавя
     */
    public $canSendemail = 'officer';
    
    
    /**
     * Кой може да изтрива
     */
    public $canDelete = 'officer';
    
    
    /**
     * Кой има право да изтрива потребителите, създадени от системата?
    */
    public $canDeletesysdata = 'officer';
    
    
    /**
     * Заглавие
     */
    public $title = "Споделени партньори";
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Споделен партньор";
    
    
    /**
     * Описание на модела на нишките от контейнери за документи
     */
    function description()
    {
        // Информация за нишката
        $this->FLD('folderId', 'key2(mvc=doc_Folders, selectSourceArr=colab_FolderToPartners::getFolderOptions, exludeContractors=' . Request::get('contractorId') . ')', 'caption=Папка,silent,input=hidden,after=contractorId,mandatory');
        $this->FLD('contractorId', 'key2(mvc=core_Users, titleFld=names, rolesArr=partner, selectSourceArr=colab_FolderToPartners::getContractorOptions, excludeFolders=' . Request::get('folderId') . ')', 'caption=Потребител,notNull,silent,mandatory');
         
        // Поставяне на уникални индекси
        $this->setDbUnique('folderId,contractorId');

        $this->setDbIndex('contractorId');
        $this->setDbIndex('folderId');
    }
    
    
    /**
     * Форсира папката като споделена към потребител-партньор
     * 
     * @param int $folderId
     * @param int|NULL $userId
     * @return int|FALSE
     */
    public static function force($folderId, $userId = NULL)
    {
    	$userId = isset($userId) ? $userId : core_Users::getCurrent('id', FALSE);
    	if(empty($userId) || !core_Users::isContractor($userId)) return FALSE;
    	
    	$rec = self::fetchField("#folderId = {$folderId} AND #contractorId = {$userId}");
    	if(!$rec) {
    		$rec = (object)array('folderId' => $folderId, 'contractorId' => $userId);
    		self::save($rec);
    	}
    	
    	return $rec->id;
    }
    
    
    /**
     * Коя е първата споделена папка на контрагент на партньор
     * 
     * @param int|NULL $userId - ид на партньор
     * @return NULL|int $folderId - първата споделена папка
     */
    public static function getLastSharedContragentFolder($cu = NULL)
    {
    	$cu = isset($cu) ? $cu : core_Users::getCurrent('id', FALSE);
    	if(empty($cu) || !core_Users::isContractor($cu)) return NULL;
    	
    	// Коя е последно активната му папка на Фирма
    	$folderId = self::getLastSharedFolder($cu, 'crm_Companies');
    	
    	// Ако няма тогава е последно активната му папка на Лице
    	if(empty($folderId)){
    		$folderId = self::getLastSharedFolder($cu, 'crm_Persons');
    	}
    	
    	return (!empty($folderId)) ? $folderId : NULL;
    }
    
    
    /**
     * Последната активна папка на потребителя
     * 
     * @param int $cu              - потребител
     * @param mixed $class         - Клас на корицата
     * @return NULL|int $folderId  - Ид на папка
     */
    private static function getLastSharedFolder($cu, $class)
    {
    	$Class = cls::get($class);
    	$query = self::getQuery();
    	$query->where("#contractorId = {$cu}");
    	$query->EXT('coverClass', 'doc_Folders', 'externalName=coverClass,externalKey=folderId');
    	$query->where("#coverClass =" . $Class->getClassId());
    	
    	$query->show('folderId');
    	$fIds = arr::extractValuesFromArray($query->fetchAll(), 'folderId');
    	
    	if(count($fIds)) {
    	
    		if(count($fIds) == 1) {
    			$folderId = key($fIds);
    		} else {
    	
    			if(!$folderId) {
    				// Първа е папката в която този потребител последно е писал
    				$cQuery = doc_Containers::getQuery();
    				$cQuery->limit(1);
    				$cQuery->orderBy('#modifiedOn', 'DESC');
    				$cQuery->where("#createdBy = {$cu}");
    				$cQuery->where('#folderId IN (' . implode(',', $fIds) . ')');
    				$cQuery->where("#state != 'rejected'");
    				$cRec = $cQuery->fetch();
    				if($cRec) {
    					$folderId = $cRec->folderId;
    				}
    			}
    	
    			if(!$folderId) {
    				// След това е папката, в която има последно движение
    				$fQuery = doc_Folders::getQuery();
    				$fQuery->limit(1);
    				$fQuery->orderBy('#last', 'DESC');
    				$fQuery->where('#id IN (' . implode(',', $fIds) . ')');
    				$fQuery->where("#state != 'rejected'");
    				$fRec = $fQuery->fetch();
    				if($fRec) {
    					$folderId = $rec->id;
    				}
    			}
    		}
    	}
    	 
    	if (!empty($folderId) && !colab_Threads::haveRightFor('list', (object)array('folderId' => $folderId), $cu)) {
    		$folderId = NULL;
    	}
    	
    	return $folderId;
    }
    
    
    /**
     * Връща опциите за папки
     *
     * @param array $params
     * @param NULL|integer $limit
     * @param string $q
     * @param NULL|integer|array $onlyIds
     * @param boolean $includeHiddens
     *
     * @return array
     */
    public static function getFolderOptions($params, $limit = NULL, $q = '', $onlyIds = NULL, $includeHiddens = FALSE)
    {
        $excludeArr = array();
        if ($params['removeDuplicate'] || $params['exludeContractors']) {
            $query = self::getQuery();
            
            $query->in('folderId', $fArr);
            
            $query->show('folderId');
            
            if ($params['exludeContractors']) {
                $cArr = explode('|', $params['exludeContractors']);
                $query->orWhereArr('contractorId', $cArr);
            }
            
            while ($rec = $query->fetch()) {
                $excludeArr[$rec->folderId] = $rec->folderId;
            }
        }
        
        if (!empty($excludeArr)) {
            $params['excludeArr'] = $excludeArr;
        }
        
        $resArr = doc_Folders::getSelectArr($params, $limit, $q, $onlyIds, $includeHiddens);
        
        return $resArr;
    }
    
    
    /**
     * Опции за партньори
     */
    public static function getContractorOptions($params, $limit = NULL, $q = '', $onlyIds = NULL, $includeHiddens = FALSE)
    {
        $excludeArr = array();
        if ($params['removeDuplicate'] || $params['excludeFolders']) {
            $query = self::getQuery();
            
            $query->show('contractorId');
            
            if ($params['excludeFolders']) {
                $foldersArr = explode('|', $params['excludeFolders']);
                $query->orWhereArr('folderId', $foldersArr);
            }
            
            while ($rec = $query->fetch()) {
                $excludeArr[$rec->contractorId] = $rec->contractorId;
            }
        }
        
        if (!empty($excludeArr)) {
            $params['excludeArr'] = $excludeArr;
        }
        
        $resArr = core_Users::getSelectArr($params, $limit, $q, $onlyIds, $includeHiddens);
        
        return $resArr;
    }
	
    
	/**
	 * След подготовка на формата
	 */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {  
        $form = $data->form;

        if(isset($form->rec->contractorId)) {
        	$form->setReadOnly('contractorId');
        	$form->setField('folderId', 'input');
        } else {
        	// Ако няма избрана папка форсираме от данните за контрагента от урл-то
        	if(empty($form->rec->folderId)){
        		expect($coverClassId = request::get('coverClassId', "key(mvc=core_Classes)"));
        		$coverName = cls::getClassName($coverClassId);
        		expect($coverId = request::get('coverId', "key(mvc={$coverName})"));
        	
        		$form->setDefault('folderId', cls::get($coverClassId)->forceCoverAndFolder($coverId));
        	}
        }
        
        if ($form->rec->folderId) {
            $form->fields['contractorId']->type->params['excludeFolders'] = $form->rec->folderId;
        }
        
        if ($form->rec->contractorId) {
            $form->fields['folderId']->type->params['exludeContractors'] = $form->rec->contractorId;
        }
    }


    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	if(isset($data->form->rec->folderId)){
    		$Cover = doc_Folders::getCover($data->form->rec->folderId);
    		$data->form->title = core_Detail::getEditTitle($Cover->getInstance(), $Cover->that, $mvc->singleTitle, $data->form->rec->id);
    	}
    }
    
    
    /**
     * Подготвя данните на партньорите
     */
    public static function preparePartners($data)
    {
        if(!$data->isCurrent) return;

        $data->rows = array();
        $folderId = $data->masterData->rec->folderId;
        if ($folderId) {
            $query = self::getQuery();
            $query->EXT('lastLogin', 'core_Users', 'externalName=lastLoginTime,externalKey=contractorId');
            $query->where("#folderId = {$folderId}");
            $query->orderBy('lastLogin', "DESC");
            
            $rejectedArr = array();
            $count = 1;
            while($rec = $query->fetch()) {
                $uRec = core_Users::fetch($rec->contractorId);
                if($uRec->state != 'rejected') {
                    $data->rows[$rec->contractorId] = self::recToVerbal($rec);
                    $data->rows[$rec->contractorId]->count = cls::get('type_Int')->toVerbal($count);
                    $count++;
                } else {
                    
                    $rec->RestoreLink = TRUE;
                    $rejectedArr[$rec->contractorId] = self::recToVerbal($rec);
                }
            }
            
            if (!empty($rejectedArr)) {
                foreach ($rejectedArr as $contractorId => $rejectedRow) {
                    $data->rows[$contractorId] = $rejectedRow;
                    $data->rows[$contractorId]->count = cls::get('type_Int')->toVerbal($count);
                    $count++;
                }
            }
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'add' && isset($rec)){
    		
    		// Само към папка на контрагент
    		if($rec->folderId){
    			$cover = doc_Folders::getCover($rec->folderId);
    			if(FALSE && !$cover->haveInterface('crm_ContragentAccRegIntf')){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    	
    	// Можем ли да изпратим автоматичен имейл до обекта
    	if($action == 'sendemail' && isset($rec)){
    		if(!doc_Folders::haveRightToObject($rec)){
    			$requiredRoles = 'no_one';
    		} else {
    			$emailsFrom = email_Inboxes::getAllowedFromEmailOptions(NULL);
    			if(!count($emailsFrom)){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->names = core_Users::getVerbal($rec->contractorId, 'names');
    	$row->names .= " (" . crm_Profiles::createLink($rec->contractorId) . ") ";
    	$row->names .= core_Users::getVerbal($rec->contractorId, 'lastLoginTime');
    	
    	if($rec->RestoreLink) {
            if($pId = crm_Profiles::getProfileId($rec->contractorId)) {
                if (crm_Profiles::haveRightFor('restore', $pId)) {
                    core_RowToolbar::createIfNotExists($row->_rowTools);
                    $row->_rowTools->addLink('Възстановяване', array('crm_Profiles', 'restore', $pId, 'ret_url' => TRUE), "warning=Наистина ли желаете да възстановите потребителя|*?,ef_icon = img/16/restore.png,title=Възстановяване на профила на споделен партньор,id=rst{$rec->id}");
                }
            }
    	}
    }
    
    
    /**
     * Рендира данните за партньорите
     * 
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public static function renderPartners($data, &$tpl)
    {
		$me = cls::get(get_called_class());
		
		$dTpl = getTplFromFile('colab/tpl/PartnerDetail.shtml');
		
		// Подготвяме таблицата с данните извлечени от журнала
		$table = cls::get('core_TableView');
		
		$data->listFields = arr::make('count=№,names=Свързани');
		$me->invoke('BeforeRenderListTable', array($dTpl, &$data));
		$details = $table->get($data->rows, $data->listFields);
		$dTpl->append($details, 'TABLE_PARTNERS');
        
		$folderId = $data->masterData->rec->folderId;
		$btns = new core_ET("");
		
		// Добавяме бутон за свързване на папка с партньор, ако имаме права
		if($me->haveRightFor('add', (object)array('folderId' => $folderId))){
			$ht = ht::createLink('', array($me, 'add', 'folderId' => $folderId, 'ret_url' => TRUE, 'coverClassId' => $data->masterMvc->getClassId(), 'coverId' => $data->masterId), FALSE, 'ef_icon=img/16/add.png,title=Свързване на партньор към папката на обекта');
			$dTpl->append($ht, 'addBtn');
		}
		
		// Само за фирми
		if($data->masterMvc instanceof crm_Companies){
			Request::setProtected(array('companyId'));
			
			if (haveRole('admin')) {
			    // Добавяме бутон за създаването на нов партньор, визитка и профил
			    $ht = ht::createBtn('Нов партньор', array($me, 'createNewContractor', 'companyId' => $data->masterId, 'ret_url' => TRUE), FALSE, FALSE, 'ef_icon=img/16/star_2.png,title=Създаване на нов партньор');
			    $btns->append($ht);
			}
			
			// Ако фирмата има имейли и имаме имейл кутия, слагаме бутон за изпращане на имейл за регистрация
			if($me->haveRightFor('sendemail', $data->masterData->rec)){
				$ht = ht::createBtn('Имейл', array($me, 'sendRegisteredEmail', 'companyId' => $data->masterId, 'ret_url' => TRUE), FALSE, FALSE, 'ef_icon=img/16/email_edit.png,title=Изпращане на имейл за регистрация на партньори към фирмата');
				$btns->append($ht);
			} else {
				$ht = ht::createErrBtn('Имейл', 'Фирмата няма имейли, или нямате имейл кутия');
				$btns->append($ht);
			}
		}
		
		$dTpl->append($btns, 'PARTNER_BTNS');
		$dTpl->removeBlocks();
		
		$tpl->append($dTpl, 'PARTNERS');
    }
    
    
    /**
     * Колбек функция, която се извиква екшъна за създаване на нов контрактор
     */
    public static function callback_Createnewcontractor($data)
    {
    	Request::setProtected(array('companyId', 'rand', 'email', 'fromEmail', 'userNames'));
 
    	redirect(array('colab_FolderToPartners', 'Createnewcontractor', 'companyId' => $data['companyId'], 'email' => $data['email'], 'rand' => $data['rand'], 'userNames' => $data['userNames'], 'fromEmail' => TRUE));
    }
    
    
    /**
     * Екшън за автоматично изпращане на имейл за регистрация
     *
     * @return core_ET - шаблона на екшъна
     */
    function act_SendRegisteredEmail()
    {
    	Request::setProtected(array('companyId'));
    	
    	$this->requireRightFor('sendemail');
    	expect($companyId = Request::get('companyId', 'key(mvc=crm_Companies)'));
    	expect($companyRec = crm_Companies::fetch($companyId));
    	$companyName = crm_Companies::getVerbal($companyId, 'name');
    	
    	$this->requireRightFor('sendemail', $companyRec);
    	
    	$form = cls::get('core_Form');
    	$form->title = "Изпращане на имейл за регистрация на партньори в|* <b>{$companyName}</b>";
    	
    	$form->FNC('to', 'email', 'caption=До имейл, width=100%, mandatory, input');
    	$form->FNC('from', 'key(mvc=email_Inboxes,select=email)', 'caption=От имейл, width=100%, mandatory, optionsFunc=email_Inboxes::getAllowedFromEmailOptions, input');
    	$form->FNC('subject', 'varchar', 'caption=Относно,mandatory,width=100%, input');
    	$form->FNC('body', 'richtext(rows=15,bucket=Postings)', 'caption=Съобщение,mandatory, input');
    	
    	$emailsArr = type_Emails::toArray($companyRec->email);
    	if (!empty($emailsArr)) {
    	    $emailsArr = array_combine($emailsArr, $emailsArr);
    	    $emailsArr = array('' => '') + $emailsArr;
    	}
    	
    	$form->setSuggestions('to', $emailsArr);
    	
    	$form->setDefault('from', email_Outgoings::getDefaultInboxId());
    	
    	core_Lg::push(drdata_Countries::getLang($companyRec->country));
    	
    	$subject = tr("Създайте нов акаунт в") . " " . core_Setup::get('EF_APP_TITLE', TRUE);

    	$form->setDefault('subject', $subject);
    	
    	$placeHolder = '{{' . tr('линк||link') . '}}';
    	
    	$body = new ET(
            tr("Уважаеми потребителю||Dear User") . ",\n\n" . 
            tr("За да се регистрираш като служител на фирма||To have registration as a member of company") .
            " \"[#company#]\", " . 
            tr("моля последвай този||please follow this") .
            " {$placeHolder} - " . 
            tr("изтича след 7 дена||it expired after 7 days"));
		
    	$companyName = str_replace(array('&lt;', '&amp;'), array("<", "&"), $companyName);
    	$body->replace($companyName, 'company');
		
		$footer = cls::get('email_Outgoings')->getFooter($companyRec->country);
		$body = $body->getContent() . "\n\n" . $footer;
		
    	$form->setDefault('body', $body);
    	
    	core_Lg::pop();
    	
    	$form->input();

        // Проверка за грешки
        if($form->isSubmitted()) {
            if(!strpos($form->rec->body, $placeHolder)) {
                $form->setError('body', 'Липсва плейсхолдера на линка за регистриране|* - ' . $placeHolder);
            }
        }

    	if($form->isSubmitted()){
           
            $form->rec->companyId = $companyId;
            $form->rec->placeHolder = $placeHolder;
    		$res = $this->sendRegistrationEmail($form->rec);
    		$msg = ($res) ? 'Успешно изпратен имейл' : 'Проблем при изпращането на имейл';
    		
    		return followRetUrl(NULL, $msg);
    	}
    	
    	$form->toolbar->addSbBtn('Изпращане', 'save', 'id=save, ef_icon = img/16/lightning.png', 'title=Изпращане на имейл за регистрация на парньори');
    	$form->toolbar->addBtn('Отказ', getRetUrl(),  'id=cancel, ef_icon = img/16/close-red.png', 'title=Прекратяване на действията');
    	 
    	$tpl = $this->renderWrapping($form->renderHtml());
    	core_Form::preventDoubleSubmission($tpl, $form);
        
    	return $tpl;
    }
    
    
    /**
     * Изпраща имейл за регистрация на имейла на контрагента
     */
    private function sendRegistrationEmail($rec)
    {
    	$sentFrom = email_Inboxes::fetchField($rec->from, 'email');
    	
    	// Изпращане на имейл с phpmailer
    	$PML = email_Accounts::getPML($sentFrom);
    	
    	// Ако има дестинационни имейли, ще изпратим имейла до тези които са избрани
    	if ($rec->to) {
    		$toArr = type_Emails::toArray($rec->to);
    		foreach ($toArr as $to) {
    			$PML->AddAddress($to);
                if(!core_Users::fetch(array("#email = '[#1#]'", $to))) {
                    $userEmail = $to;
                }
    		}
    	}
        
    	$PML->Encoding = "quoted-printable";
       
        $url = core_Forwards::getUrl($this, 'Createnewcontractor', array('companyId' => $rec->companyId, 'email' => $userEmail, 'rand' => str::getRand()), 604800);
    	
        $rec->body = str_replace($rec->placeHolder, "[link=$url]link[/link]", $rec->body);

    	Mode::push('text', 'plain');
    	$bodyAlt = cls::get('type_Richtext')->toVerbal($rec->body);
    	Mode::pop('text');
 
    	Mode::push('text', 'xhtml');
    	$bodyTpl = cls::get('type_Richtext')->toVerbal($rec->body);
    	email_Sent::embedSbfImg($PML);
   
    	Mode::pop('text');
    	
    	$PML->AltBody = $bodyAlt;
    	$PML->Body = $bodyTpl->getContent();
    	$PML->IsHTML(TRUE);
    	$PML->Subject = str::utf2ascii($rec->subject);
    	$PML->AddCustomHeader("Customer-Origin-Email: {$rec->to}");
    	 
    	$files = fileman_RichTextPlg::getFiles($rec->body);
    	
    	// Ако има прикачени файлове, добавяме ги
    	if(count($files)){
    		foreach ($files as $fh => $name){
    			$name = fileman_Files::fetchByFh($fh, 'name');
    			$path = fileman_Files::fetchByFh($fh, 'path');
    			$PML->AddAttachment($path, $name);
    		}
    	}
    	
    	// От кой адрес е изпратен
    	$PML->SetFrom($sentFrom);
    	
    	// Изпращане
    	$isSended = $PML->Send();
    	
    	// Логване на евентуални грешки при изпращането
    	if (!$isSended) {
    		$error = trim($PML->ErrorInfo);
    		if (isset($error)) {
    			log_System::add('phpmailer_Instance', "PML error: " . $error, NULL, 'err');
    		}
    	}
    	
    	return $isSended;
    }
    
    
    /**
     * Форма за създаване на потребител контрактор, създавайки негов провил, визитка и го свързва към фирмата
     * 
     * @return core_ET - шаблона на формата
     */
    function act_Createnewcontractor()
    {
    	Request::setProtected(array('companyId', 'rand', 'fromEmail', 'email', 'userNames'));
    	
    	if (!$email = Request::get('email', 'email')) {
    	    Request::removeProtected(array('email'));
    	}
    	
    	expect($companyId = Request::get('companyId', 'key(mvc=crm_Companies)'));
    	$Users = cls::get('core_Users');
    	$companyRec = crm_Companies::fetch($companyId);
    	
        core_Lg::push(drdata_Countries::getLang($companyRec->country));
      	$rand = Request::get('rand');
    	
    	// Ако не сме дошли от имейл, трябва потребителя да има достъп до обекта
    	$fromEmail = Request::get('fromEmail');  
    	if(!$fromEmail){
            requireRole('powerUser');
    		expect(doc_Folders::haveRightToObject($companyRec));
    	}
    	
    	$form = $Users->getForm();
    	$companyName = crm_Companies::getHyperlink($companyId, TRUE);
    	$form->title = "Нов партньор от|* <b>{$companyName}</b>";
    	$form->setDefault('country', $companyRec->country);
		
        // Ако има готово име, попълва се
		if($userNames = Request::get('userNames', 'varchar')){
			$form->setDefault('names', $userNames);
		}
    	
        // Ако имаме хора от crm_Persons, които принадлежат на тази компания, и нямат свързани профили,
        // добавяме поле, преди nick, за избор на такъв човек. Ако той се подаде, данните за потребителя се вземат частично
        // от визитката, а новосъздадения профил се свързва със визитката
        if(haveRole('officer,manager,ceo')) {
            $pOpt = array();
            $pQuery = crm_Persons::getQuery();
            while($pRec = $pQuery->fetch(array("#state = 'active' AND #buzCompanyId = [#1#]", $companyId))) { 
                if(!crm_Profiles::fetch("#personId = {$pRec->id}")) {
                    $pOpt[$pRec->id] = $pRec->name;
                }
            } 
            if(count($pOpt)) {
                $form->FNC("personId", "key(mvc=crm_Persons,allowEmpty)", "caption=Лице,before=nick,input,silent,removeAndRefreshForm=names|email");
                $form->setOptions('personId', $pOpt);
            }
        }
    	
    	$form->setDefault('roleRank', core_Roles::fetchByName('partner'));
    	$Users->invoke('AfterPrepareEditForm', array((object)array('form' => $form), (object)array('form' => $form)));
    	$form->setDefault('state', 'active');
        
    	if ($email) {
    	    $form->setDefault('email', $email);
    	    $form->setReadonly('email');
    	}
        
    	$form->setField('roleRank', 'input=none');
    	$form->setField('roleOthers', "caption=Достъп за външен потребител->Роли");
    	
    	if(!$Users->haveRightFor('add')){
    		$form->setField('rolesInput', 'input=none');
    		$form->setField('roleOthers', 'input=none');
    		$form->setField('state', 'input=none');
    	}
    	
    	// Задаваме дефолтните роли
    	$dRolesArr = array('partner');
    	try {
    	    $autoCreateQuote = cond_Parameters::getParameter(crm_Companies::getClassId(), $companyId, 'autoCreateQuote');
    	    
    	    if ($autoCreateQuote == 'yes') {
    	        $dRolesArr[] = 'agent';
    	    }
    	} catch (core_exception_Expect $e) {
    	    reportException($e);
    	}
    	$defRoles = array();
    	foreach ($dRolesArr as $role){
    	    $id = core_Roles::fetchByName($role);
    	    $defRoles[$id] = $id;
    	}
    	if (!empty($defRoles)) {
    	    $form->setDefault('roleOthers', $defRoles);
    	}
    	
    	$form->input();
    	
    	if($form->isSubmitted()){
    		if(!$Users->isUnique($form->rec, $fields)){
    			$form->setError($fields, "Вече съществува запис със същите данни");
    		}
    	}
    	
    	if($form->isSubmitted()){
    	    if (core_Users::isForbiddenNick($form->rec->nick)) {
    	        $form->setError('nick', "Вече съществува запис със същите данни");
    	    }
    	}
    	
    	$Users->invoke('AfterInputEditForm', array(&$form));
    	
    	if ($form->isSubmitted() && haveRole('powerUser')) {
    	    $cRec = clone $form->rec;
    	    $cRec->name = $form->rec->names;
    	    $wStr = crm_Persons::getSimilarWarningStr($cRec, $fields);
    	    
    	    if ($fields) {
    	        $fieldsArr = arr::make($fields, TRUE);
    	        if ($fieldsArr['name']) {
    	            $fieldsArr['names'] = 'names';
    	            unset($fieldsArr['name']);
    	            $fields = implode(',', $fieldsArr);
    	        }
    	    }
    	    
    	    if ($wStr) {
    	        $form->setWarning($fields, $wStr);
    	    }
    	}
    	
    	// След събмит ако всичко е наред създаваме потребител, лице и профил
    	if ($form->isSubmitted()) {
    		$uId = $Users->save($form->rec);
    		$personId = crm_Profiles::fetchField("#userId = {$uId}", 'personId');
    		$personRec = crm_Persons::fetch($personId);
    		
    		// Свързваме лицето към фирмата
    		$personRec->buzCompanyId = $companyId;
    		$personRec->country = $form->rec->country;
    		$personRec->inCharge = $companyRec->inCharge;
    		
    		// Имейлът да е бизнес имейла му
    		$buzEmailsArr = type_Emails::toArray($personRec->buzEmail);
    		$buzEmailsArr[] = $personRec->email;
    		$personRec->buzEmail = type_Emails::fromArray($buzEmailsArr);
    		$personRec->email = '';
    		
    		crm_Persons::save($personRec);
    		
    		$folderId = crm_Companies::forceCoverAndFolder($companyId);
    		static::save((object)array('contractorId' => $uId, 'folderId' => $folderId));
    		
    		// Изтриваме линка, да не може друг да се регистрира с него
    		core_Forwards::deleteUrl($this, 'Createnewcontractor', array('companyId' => $companyId, 'email' => $email, 'rand' => $rand), 604800);
    		
    		return followRetUrl(array('colab_Threads', 'list', 'folderId' => $folderId), '|Успешно са създадени потребител и визитка на нов партньор');
    	}
    	
    	$form->toolbar->addSbBtn('Запис', 'save', 'id=save, ef_icon = img/16/disk.png', 'title=Запис');
    	
    	if ($retUrl = getRetUrl()) {
    	    $form->toolbar->addBtn('Отказ', $retUrl,  'id=cancel, ef_icon = img/16/close-red.png', 'title=Прекратяване на действията');
    	}
    	
    	$cu = core_Users::getCurrent('id', FALSE);
    	if ($cu && core_Users::haveRole('powerUser', $cu)) {
    		$tpl = $this->renderWrapping($form->renderHtml());
    	} else {
    		$tpl = $form->renderHtml();
    	}
    	core_Form::preventDoubleSubmission($tpl, $form);
    	
    	core_Lg::pop();

    	return $tpl;
    }
    
    
    /**
     * Тестова функция за създаване на потребители
     */
    function act_CreateTestUsers()
    {
        requireRole('admin');
        requireRole('debug');
        
        expect(core_Packs::isInstalled('colab'));
        
        core_App::setTimeLimit(600);
        
        $nickCnt = Request::get('cnt', 'int');
        setIfNot($nickCnt, 1000);
        if ($nickCnt > 20000) {
            $nickCnt = 20000;
        }
        
        $pass = Request::get('pass');
        setIfNot($pass, '123456');
        
        $nickPref = Request::get('nickPref');
        setIfNot($nickPref, 'test_');
        
        $pRoleId = core_Roles::fetchByName('partner');
        $dRoleId = core_Roles::fetchByName('distributor');
        $aRoleId = core_Roles::fetchByName('agent');
        
        while ($nickCnt--) {
            $uRec = new stdClass();
            $uRec->nick = $nickPref . str::getRand();
            $uRec->names = $uRec->nick . ' Name';
            $uRec->email = $uRec->nick . '@bgerp.com';
            $uRec->state = 'active';
            $uRec->rolesInput = array($pRoleId => $pRoleId);
            
            if (rand(1,3) == 1) {
                $uRec->rolesInput[$dRoleId] = $dRoleId;
            }
            if (rand(1,3) == 3) {
                $uRec->rolesInput[$aRoleId] = $aRoleId;
            }
            $uRec->rolesInput = type_Keylist::fromArray($uRec->rolesInput);
            $uRec->ps5Enc = core_Users::encodePwd($pass, $uRec->nick);
            
            core_Users::save($uRec, NULL, 'IGNORE');
        }
    }
}
