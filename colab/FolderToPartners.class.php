<?php



/**
 * Клас 'colab_FolderToPartners' - Релация между партньори и папки
 *
 *
 * @category  bgerp
 * @package   colab
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
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
    public $canAdd = 'officer';
    
    
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
        $this->FLD('folderId', 'key(mvc=doc_Folders,select=title)', 'caption=Папка,silent');
        $this->FLD('contractorId', 'key(mvc=core_Users,select=names)', 'caption=Потребител,notNull');
         
        // Поставяне на уникални индекси
        $this->setDbUnique('folderId,contractorId');
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
		$cId = core_Roles::fetchByName('contractor');
		$pUserId = core_Roles::fetchByName('powerUser');
		$uQuery->like('roles', "|{$cId}|");
		$uQuery->like('roles', "|{$pUserId}|", FALSE);
		$uQuery->show('id,names');
		
		$options = array();
		
		while ($uRec = $uQuery->fetch()){
			if($folderId){
				if(!static::fetch("#folderId = {$folderId} && #contractorId = {$uRec->id}")){
					$options[$uRec->id] = $uRec->names;
				} 
			} else {
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
        $form->title = "Добавяне на нов партньор в папка";
        
        // Ако няма избрана папка форсираме от данните за контрагента от урл-то
        if(empty($form->rec->folderId)){
        	expect($coverClassId = request::get('coverClassId', "key(mvc=core_Classes)"));
        	$coverName = cls::getClassName($coverClassId);
        	expect($coverId = request::get('coverId', "key(mvc={$coverName})"));
        	
        	$form->setDefault('folderId', cls::get($coverClassId)->forceCoverAndFolder($coverId));
        }
        
        $form->setReadOnly('folderId');
        
        $form->setOptions('contractorId', self::getContractorOptions($form->rec->folderId));
    }


    /**
     * Подготвя данните на партньорите
     */
    public static function preparePartners($data)
    {
        $data->partners = array();
        $folderId = $data->masterData->rec->folderId;
        if ($folderId) {
            $query = self::getQuery();
            while($rec = $query->fetch("#folderId = {$folderId}")) {
               $uRec = core_Users::fetch($rec->contractorId);
               if($uRec->state != 'rejected') {
                  $data->partners[$rec->contractorId] = self::recToVerbal($rec);
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
    			if(!$cover->haveInterface('crm_ContragentAccRegIntf')){
    				$requiredRoles = 'no_one';
    			} else {
    				// Ако не могат да бъдат избрани контрактори, не може да се добави запис
    				$contractors = self::getContractorOptions($rec->folderId);
    				if(!count($contractors)){
    					$requiredRoles = 'no_one';
    				}
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
    	$row->names .= "<span style='margin-left:10px'>{$row->tools}</span>";
    }
    
    
    /**
     * Рендира данните за партньорите
     * 
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public static function renderPartners($data, &$tpl)
    {
		if(!cls::haveInterface('crm_ContragentAccRegIntf', $data->masterMvc)) return;
		$me = cls::get(get_called_class());
		
		$dTpl = getTplFromFile('doc/tpl/PartnerDetail.shtml');
		
		if(count($data->partners)) {
			// Подготвяме таблицата с данните извлечени от журнала
			$table = cls::get('core_TableView');

			// Ако сумите на крайното салдо са отрицателни - оцветяваме ги
			$details = $table->get($data->partners, 'names=Свързани');
			$dTpl->append($details, 'TABLE_PARTNERS');
		}
        
		$folderId = $data->masterData->rec->folderId;
		
		$btns = new core_ET();
		
		// Добавяме бутон за свързване на папка с партньор, ако имаме права
		if($me->haveRightFor('add', (object)array('folderId' => $folderId))){
			$ht = ht::createBtn('Свързване', array($me, 'add', 'folderId' => $folderId, 'ret_url' => TRUE, 'coverClassId' => $data->masterMvc->getClassId(), 'coverId' => $data->masterId), FALSE, FALSE, 'ef_icon=img/16/disk.png,title=Свързване на партньор към папката');
			$btns->append($ht);
		}
		
		// Само за фирми
		if($data->masterMvc instanceof crm_Companies){
			Request::setProtected(array('companyId'));
			
			// Добавяме бутон за създаването на нов партньор, визитка и профил
			$ht = ht::createBtn('Нов партньор', array($me, 'createNewContractor', 'companyId' => $data->masterId, 'ret_url' => TRUE), FALSE, FALSE, 'ef_icon=img/16/star_2.png,title=Създаване на нов контрактор');
			$btns->append($ht);
			
			// Ако фирмата има имейли и имаме имейл кутия, слагаме бутон за изпращане на имейл за регистрация
			if($me->haveRightFor('sendemail', $data->masterData->rec)){
				Request::setProtected(array('companyId'));
				$ht = ht::createBtn('Имейл', array($me, 'sendRegisteredEmail', 'companyId' => $data->masterId, 'ret_url' => TRUE), FALSE, FALSE, 'ef_icon=img/16/email_edit.png,title=Изпращане на имейл за регистрация на контрактори към фирмата');
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
    	Request::setProtected(array('companyId', 'fromEmail'));
    	
    	redirect(array('colab_FolderToPartners', 'Createnewcontractor', 'companyId' => $data['companyId'], 'fromEmail' => TRUE));
    }
    
    
    /**
     * Екшън за автоматично изпращане на имейл за регистрация
     *
     * @return core_ET - шаблона на екшъна
     */
    function act_SendRegisteredEmail()
    {
    	Request::setProtected(array('companyId, fromEmail'));
    	
    	$this->requireRightFor('sendemail');
    	expect($companyId = Request::get('companyId', 'key(mvc=crm_Companies)'));
    	expect($companyRec = crm_Companies::fetch($companyId));
    	$companyName = crm_Companies::getVerbal($companyId, 'name');
    	
    	$this->requireRightFor('sendemail', $companyRec);
    	
    	$form = cls::get('core_Form');
    	$form->title = "Изпращане на имейл за регистрация на партньори в|* <b>{$companyName}</b>";
    	
    	$form->FLD('to', 'emails', 'caption=До имейл, width=100%, silent,mandatory');
    	$form->FLD('from', 'key(mvc=email_Inboxes,select=email,allowEmpty)', 'caption=От имейл, width=100%, silent,mandatory, optionsFunc=email_Inboxes::getAllowedFromEmailOptions');
    	$form->FLD('subject', 'varchar', 'caption=Относно,mandatory,width=100%');
    	$form->FLD('body', 'richtext(rows=15,bucket=Postings, appendQuote)', 'caption=Съобщение,mandatory');
    	$form->setSuggestions('to', $companyRec->email);
    	$form->setDefault('from', email_Outgoings::getDefaultInboxId());
    	
    	$subject = "Регистрация в " . EF_APP_NAME; 
    	$form->setDefault('subject', $subject);
    	
    	$url = core_Forwards::getUrl($this, 'Createnewcontractor', array('companyId' => $companyId), 604800);
    	
    	$body = new ET('Уважаеми потребителю. За да се регистрираш като служител на фирма [#company#] моля последвай този линк:
		[#link#] (линка изтича след 7 дена)');
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
    	$form->toolbar->addBtn('Отказ', getRetUrl(),  'id=cancel, ef_icon = img/16/close16.png', 'title=Прекратяване на действията');
    	 
    	$tpl = $this->renderWrapping($form->renderHtml());
    	 
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
    	$bodyAlt = cls::get('type_RichText')->toVerbal($rec->body);
    	Mode::pop('text');
    	
    	Mode::push('text', 'xhtml');
    	$bodyTpl = cls::get('type_RichText')->toVerbal($rec->body);
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
    			core_Manager::log("PML error: " . $error);
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
    	Request::setProtected(array('companyId', 'fromEmail'));
    	
    	expect($companyId = Request::get('companyId', 'key(mvc=crm_Companies)'));
    	$Users = cls::get('core_Users');
    	$companyRec = crm_Companies::fetch($companyId);
    	
    	// Ако не сме дошли от имейл, трябва потребителя да има достъп до обекта
    	$fromEmail = Request::get('fromEmail');
    	if(!$fromEmail){
    		expect(doc_Folders::haveRightToObject($companyRec));
    	}
    	
    	$form = $Users->getForm();
    	$form->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Държава,mandatory,after=names');
    	$companyName = crm_Companies::getVerbal($companyId, 'name');
    	$form->title = "Създаване на служител на|* <b>{$companyName}</b>";
    	
    	$form->setDefault('country', $companyRec->country);
    	$form->setDefault('country', crm_Companies::fetchOwnCompany()->countryId);
    	
    	// Задаваме дефолтните роли
    	$defRoles = array();
    	foreach (array('contractor') as $role){
    		$id = core_Roles::fetchByName($role);
    		$defRoles[$id] = $id;
    	}
    	
    	$Users->invoke('AfterPrepareEditForm', array((object)array('form' => $form), (object)array('form' => $form)));
    	$form->setDefault('state', 'active');
    	
    	if(!$Users->haveRightFor('add')){
    		$form->setField('rolesInput', 'input=hidden');
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
    		
    		return followRetUrl(array('core_Users', 'login'), 'Успешно са създадени потребител и визитка на нов партньор');
    	}
    	
    	$form->toolbar->addSbBtn('Запис', 'save', 'id=save, ef_icon = img/16/disk.png', 'title=Запис');
    	$form->toolbar->addBtn('Отказ', getRetUrl(),  'id=cancel, ef_icon = img/16/close16.png', 'title=Прекратяване на действията');
    	
    	if($cu = core_Users::getCurrent('id', FALSE) && core_Users::haveRole('powerUser', $cu)){
    		$tpl = $this->renderWrapping($form->renderHtml());
    	} else {
    		$tpl = $form->renderHtml();
    	}
    	
    	return $tpl;
    }
}
