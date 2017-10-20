<?php



/**
 * Клас 'colab_FolderToPartners' - Релация между партньори и папки
 *
 *
 * @category  bgerp
 * @package   colab
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
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
    public $loadList = 'plg_Created, doc_Wrapper, plg_RowTools';
    
    
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
     * Заглавие
     */
    public $title = "Споделени партньори";
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Споделен партньор";
    
        
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела на нишките от контейнери за документи
     */
    function description()
    {
        // Информация за нишката
        $this->FLD('folderId', 'key2(mvc=doc_Folders)', 'caption=Папка,silent,input=hidden,after=contractorId');
        $this->FLD('contractorId', 'key(mvc=core_Users,select=names)', 'caption=Потребител,notNull,silent');
         
        // Поставяне на уникални индекси
        $this->setDbUnique('folderId,contractorId');

        $this->setDbIndex('contractorId');
        $this->setDbIndex('folderId');
    }

    
    /**
     * Връща опции за избор на потребители контрактори / които нямат споделена папка
     * 
     * @return array
     */
	public static function getContractorOptions($folderId)
	{
		$uQuery = core_Users::getQuery();
		$uQuery->where("#state = 'active'");
		$cId = core_Roles::fetchByName('partner');
		$uQuery->like('roles', "|{$cId}|");
		$uQuery->show('id,names');
		
		$options = array();
		
		while ($uRec = $uQuery->fetch()){
 	        if(!static::fetch("#contractorId = {$uRec->id}")){
		        $options[$uRec->id] = $uRec->names;
		    } 
		}
		
		return $options;
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
        	
        	$form->setOptions('contractorId', array('' => '') + self::getContractorOptions($form->rec->folderId));
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

        $data->partners = array();
        $folderId = $data->masterData->rec->folderId;
        if ($folderId) {
            $query = self::getQuery();
            
            $rejectedArr = array();
            
            $count = 1;
            while($rec = $query->fetch("#folderId = {$folderId}")) {
                $uRec = core_Users::fetch($rec->contractorId);
                if($uRec->state != 'rejected') {
                    $data->partners[$rec->contractorId] = self::recToVerbal($rec);
                    $data->partners[$rec->contractorId]->count = cls::get('type_Int')->toVerbal($count);
                    $count++;
                } else {
                    
                    $rec->RestoreLink = TRUE;
                    $rejectedArr[$rec->contractorId] = self::recToVerbal($rec);
                }
            }
            
            if (!empty($rejectedArr)) {
                foreach ($rejectedArr as $contractorId => $rejectedRow) {
                    $data->partners[$contractorId] = $rejectedRow;
                    $data->partners[$contractorId]->count = cls::get('type_Int')->toVerbal($count);
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
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->names = core_Users::getVerbal($rec->contractorId, 'names');
    	$row->names .= " (" . crm_Profiles::createLink($rec->contractorId) . ") ";
    	$row->names .= core_Users::getVerbal($rec->contractorId, 'lastLoginTime');
    	
    	$restoreLink = '';
    	
    	if ($rec->RestoreLink) {
    	    $pId = crm_Profiles::getProfileId($rec->contractorId);
            $restoreLink = '';
            
            if ($pId) {
                if (crm_Profiles::haveRightFor('restore', $pId)) {
                    
                    $restoreLink = ht::createLink('', 
                        array('crm_Profiles', 'restore', $pId, 'ret_url' => TRUE), 
                        tr('Наистина ли желаете да възстановите потребителя|*?'), 'id=btnRestore, ef_icon = img/16/restore.png');
                }
            }
    	}
    	
    	$row->names .= "<span style='margin-left:10px'>{$restoreLink}{$row->tools}</span>";
    }
    
    
    /**
     * Рендира данните за партньорите
     * 
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public static function renderPartners($data, &$tpl)
    {
		//if(!cls::haveInterface('crm_ContragentAccRegIntf', $data->masterMvc)) return;
  
		$me = cls::get(get_called_class());
		
		$dTpl = getTplFromFile('colab/tpl/PartnerDetail.shtml');
		
		// Подготвяме таблицата с данните извлечени от журнала
		$table = cls::get('core_TableView');
		$details = $table->get($data->partners, 'count=№,names=Свързани');
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
			
			// Добавяме бутон за създаването на нов партньор, визитка и профил
			$ht = ht::createBtn('Нов партньор', array($me, 'createNewContractor', 'companyId' => $data->masterId, 'ret_url' => TRUE), FALSE, FALSE, 'ef_icon=img/16/star_2.png,title=Създаване на нов партньор');
			$btns->append($ht);
			
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
    	Request::setProtected(array('companyId', 'rand', 'fromEmail'));
    	
    	redirect(array('colab_FolderToPartners', 'Createnewcontractor', 'companyId' => $data['companyId'], 'rand' => $data['rand'], 'fromEmail' => TRUE));
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
    	
    	$form->FNC('to', 'emails', 'caption=До имейл, width=100%, mandatory, input');
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
    	
    	$subject = "Регистрация в " . EF_APP_NAME; 
    	$form->setDefault('subject', $subject);
    	
    	$url = core_Forwards::getUrl($this, 'Createnewcontractor', array('companyId' => $companyId, 'rand' => str::getRand()), 604800);
    	
    	$body = new ET(
            tr("Уважаеми потребителю||Dear User") . ",\n\n" . 
            tr("За да се регистрираш като служител на фирма||To have registration as a member of company") .
            " \"[#company#]\", " . 
            tr("моля последвай този||please follow this") .
            " [link=[#link#]]" . tr("линк||link") . "[/link] - " . 
            tr("изтича след 7 дена||it expired after 7 days"));
		
    	$companyName = str_replace(array('&lt;', '&amp;'), array("<", "&"), $companyName);
    	$body->replace($companyName, 'company');
		$body->replace($url, 'link');
		
		$footer = cls::get('email_Outgoings')->getFooter($companyRec->country);
		$body = $body->getContent() . "\n\n" . $footer;
		
    	$form->setDefault('body', $body);
    	
    	$form->input();
    	if($form->isSubmitted()){
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
    		}
    	}
    	
    	$PML->Encoding = "quoted-printable";
    	
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
    	Request::setProtected(array('companyId', 'rand', 'fromEmail'));
    	
    	expect($companyId = Request::get('companyId', 'key(mvc=crm_Companies)'));
    	$Users = cls::get('core_Users');
    	$companyRec = crm_Companies::fetch($companyId);
    	
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

    	
    	// Задаваме дефолтните роли
    	$defRoles = array();
    	foreach (array('partner') as $role){
    		$id = core_Roles::fetchByName($role);
    		$defRoles[$id] = $id;
    	}
    	
    	$form->setDefault('roleRank', core_Roles::fetchByName('partner'));
    	$Users->invoke('AfterPrepareEditForm', array((object)array('form' => $form), (object)array('form' => $form)));
    	$form->setDefault('state', 'active');
    	$form->setField('roleRank', 'input=hidden');
    	$form->setField('roleOthers', "caption=Достъп за външен потребител->Роли");
    	
    	if(!$Users->haveRightFor('add')){
    		$form->setField('rolesInput', 'input=hidden');
    		$form->setField('roleOthers', 'input=hidden');
    		$form->setField('state', 'input=hidden');
    	}
    	
    	$form->input();
    	$form->rec->rolesInput = keylist::fromArray($defRoles);
    	
    	if($form->isSubmitted()){
    		if(!$Users->isUnique($form->rec, $fields)){
    			$form->setError($fields, "Вече съществува запис със същите данни");
    		}
    	}
    	
    	$Users->invoke('AfterInputEditForm', array(&$form));
    	
    	// След събмит ако всичко е наред създаваме потребител, лице и профил
    	if($form->isSubmitted()){
    		
    		$uId = $Users->save($form->rec);
    		$personId = crm_Profiles::fetchField("#userId = {$uId}", 'personId');
    		$personRec = crm_Persons::fetch($personId);
    		
    		// Свързваме лицето към фирмата
    		$personRec->buzCompanyId = $companyId;
    		$personRec->country = $form->rec->country;
    		$personRec->inCharge = $companyRec->inCharge;
    		crm_Persons::save($personRec);
    		
    		$folderId = crm_Companies::forceCoverAndFolder($companyId);
    		static::save((object)array('contractorId' => $uId, 'folderId' => $folderId));
    		
    		// Изтриваме линка, да не може друг да се регистрира с него
    		core_Forwards::deleteUrl($this, 'Createnewcontractor', array('companyId' => $companyId, 'rand' => $rand), 604800);
    
    		return followRetUrl(array('core_Users', 'login'), '|Успешно са създадени потребител и визитка на нов партньор');
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
    	
    	
    	return $tpl;
    }
}
