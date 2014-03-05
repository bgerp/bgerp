<?php



/**
 * Документ "Запитване"
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Inquiries extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Inq';
    
    
    /**
     * Заглавие
     */
    public $title = 'Запитвания';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Запитване';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting, doc_DocumentPlg, acc_plg_DocumentSummary, plg_Search,
					doc_EmailCreatePlg, bgerp_plg_Blank, plg_Printing';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт, title, name, company, email, folderId, drvId, createdOn, createdBy';
    
    
    /**
     * Име на папката по подразбиране при създаване на нови документи от този тип.
     * Ако стойноста е 'FALSE', нови документи от този тип се създават в основната папка на потребителя
     */
    public $defaultFolder = 'Запитвания';
    
    
    /**
     * Колоната, в която да се появят инструментите на plg_RowTools
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,sales';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,sales';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,sales';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'powerUser';
	
	
	/**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'every_one';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'folderId, name, company, email';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'sales/tpl/SingleLayoutInquiry.shtml';
    
    
    /**
     * Икона за фактура
     */
    public $singleIcon = 'img/16/inquiry.png';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('drvId', 'class(interface=techno_ProductsIntf,select=title,allowEmpty)', 'caption=Тип,silent,mandatory');
    	$this->FLD('quantity1', 'double(decimals=2)', 'caption=Количества->К-во 1,hint=Въведете количество,width=6em');
    	$this->FLD('quantity2', 'double(decimals=2)', 'caption=Количества->К-во 2,hint=Въведете количество,width=6em');
    	$this->FLD('quantity3', 'double(decimals=2)', 'caption=Количества->К-во 3,hint=Въведете количество,width=6em');
    	
    	$this->FLD('name', 'varchar(255)', 'caption=Адресни данни->Лице,class=contactData,mandatory,hint=Вашето име');
    	$this->FLD('email', 'emails(valid=drdata_Emails->validate)', 'caption=Адресни данни->Имейл,class=contactData,mandatory,hint=Вашият имейл');
    	$this->FLD('tel', 'drdata_PhoneType', 'caption=Адресни данни->Телефони,class=contactData,hint=Вашият телефон');
    	$this->FLD('company', 'varchar(255)', 'caption=Адресни данни->Фирма,class=contactData,hint=Вашата фирма');
    	$this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Адресни данни->Държава,class=contactData,hint=Вашата държава');
        $this->FLD('pCode', 'varchar(16)', 'caption=Адресни данни->П. код,class=pCode,hint=Вашият пощенски код');
        $this->FLD('place', 'varchar(64)', 'caption=Адресни данни->Град,class=contactData,hint=Населено място: град или село и община,hint=Вашаият град');
        $this->FLD('address', 'varchar(255)', 'caption=Адресни данни->Адрес,class=contactData,hint=Вашият адрес');
    
        $this->FLD('params', 'blob(serialize,compress)', 'input=none,silent');
        $this->FLD('data', 'blob(serialize,compress)', 'caption=Информация за продукта,input=none');
    }
    
    
    /**
     * Екшън за добавяне на запитване от нерегистрирани потребители
     */
    function act_New()
    {
    	$this->requireRightFor('add');
    	expect($drvId = Request::get('drvId', 'int'));
    	
    	$params = Request::get('coParams');
    	$params = $this->parseParams($params);
    	
    	// Взимаме формата
    	$form = $this->getForm();
    	$form->rec->drvId = $drvId;
    	$form->rec->params = $params;
    	$form->setField('drvId', 'input=hidden');
    	
    	$form->title = 'Добавяне на ново запитване';
    	if($cu = core_Users::getCurrent('id', FALSE)){
    		$profRec = crm_Profiles::fetch("#userId = {$cu}");
    		$form->rec->folderId = crm_Persons::forceCoverAndFolder($profRec->personId, FALSE);
    		$form->title .= "|в|*" . doc_Folders::recToVerbal(doc_Folders::fetch($form->rec->folderId))->title;
    	
    		// Попълване данните на контрагента ако има
    		$this->fillContragentData($form);
    	}
    	
    	// Добавяме полетата от избрания драйвер
    	$this->addFormFieldsFromDriver($form);
    	
    	// Инпут на формата
    	$form->input();
    	
    	// След събмит на формата
    	if($form->isSubmitted()){
    		$rec = &$form->rec;
    		$rec->data = $this->getDataFromForm($form);
    		$rec->state = 'active';
    		
    		if(empty($rec->folderId)){
    			$rec->folderId = $this->route($rec);
    		}
    		
    		// Запис и редирект
    		if($this->haveRightFor('add')){
    			$this->save($rec);
    			status_Messages::newStatus(tr('Благодарим ви за запитването'), 'success');
    			
    			return followRetUrl();
    		}
    	}
    	
    	$retUrl = Request::get('ret_url');
    	
    	$form->toolbar->addSbBtn('Запитване', 'save', 'id=save, ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', getRetUrl(),  'id=cancel, ef_icon = img/16/close16.png');
        
    	$tpl = $form->renderHtml();
    	
    	return $tpl;
    }
    
    
    /**
     * Попълва адресните данни от папката на контрагента
     */
    private function fillContragentData(&$form)
    {
    	$rec = &$form->rec;
    	$cData = doc_Folders::getContragentData($rec->folderId);
    	$rec->name    = $cData->person;
    	$rec->company = $cData->company;
    	$rec->country = $cData->country;
    	$rec->place   = $cData->place;
    	$rec->pCode   = $cData->pCode;
    	
    	$coverId = doc_Folders::fetchCoverClassId($rec->folderId);
    	
    	// Ако е лице, взимаме адресните данни на лицето, иначе на фирмата
    	if($coverId == crm_Persons::getClassId()){
    		$rec->email   = $cData->pEmail;
    		$rec->tel     = $cData->pTel;
    		$rec->address = $cData->pAddress;
    	} else {
    		$rec->email   = $cData->email;
    		$rec->tel     = $cData->tel;
    		$rec->address = $cData->address;
    	}
    }
    
    
    /**
     * Рутиране на задание по посочения имейл, в следната последователност
     * 
     * 1. В папката на лице
     * 2. В папката на фирма
     * 3. В разпозната папка от имейла
     * 4. Ако е логнат потребител в неговата папка
     * 5. Ако е нерегистриран в дефолт папката на модела
     * 
     * @param stdClass $rec
     */
    private function route(&$rec)
    {
    	$email = $rec->email;
    	
    	// Ако имейла е асоцииран с папка на фирма, връщаме нея
    	$companyFolderId = crm_Companies::getFolderFromEmail($email);
    	if($companyFolderId) {
    		
    		return $companyFolderId;
    	}
    	
    	// Ако имейла е асоцииран с папка на лице , връщаме нея
    	$personFolderId = crm_Persons::getFolderFromEmail($email);
    	if($personFolderId) {
    		
    		return $personFolderId;
    	}
    	
    	$folderId = email_Router::getEmailFolder($email);
    	if($folderId) {
    		
    		return $folderId;
    	}
    	
    	if(empty($rec->country)){
    		$Drdata = cls::get('drdata_Countries');
    		$countryId = $Drdata->getByIp();
    	}
    	
    	if($userId = core_Users::getCurrent('id', FALSE)){
    		$defFolderId = doc_Folders::getDefaultFolder($userId);
    	} else {
    		$unRec = new stdClass();
            $unRec->name = $this->defaultFolder;
            $defFolderId = doc_UnsortedFolders::forceCoverAndFolder($unRec, TRUE);
    	}
    	
    	// Връщане на папката по подразбиране
    	return $defFolderId;
    }
    
    
    /**
     * Извлича данните за драйвъра от формата
     */
    private function getDataFromForm(&$form)
    {
    	// Преобразува допълнителната информация във вид удобен за съхраняване
    	$rows = $recs = array();
    	$dataFlds = $form->selectFields('#params');
    	
    	if(count($dataFlds)){
    		foreach ((array)$dataFlds as $k => $v){
    			if(isset($form->rec->$k) && strlen($form->rec->$k)){
    				$recs[$k] = $form->rec->$k;
    				$caption = explode('->', $form->fields[$k]->caption);
    				$rows[$caption[1]] = $form->fields[$k]->type->toVerbal($form->rec->$k);
    			}
    		}
    		
    		return array('recs' => $recs, 'rows' => $rows);
    	}
    }
    
    
    /**
     * Подготвя полетата за допълнителна информация от драйвера
     * @param core_Form $form
     */
    private function addFormFieldsFromDriver(&$form)
    {
    	$Driver = cls::get($form->rec->drvId);
		expect(cls::haveInterface('techno_ProductsIntf', $Driver));
		
		$Driver->fillInquiryForm($form);
		
		$uomId = $Driver->getDriverUom($form->rec->params);
		$shortUom = cat_UoM::getShortName($uomId);
		$form->setField('quantity1', "unit={$shortUom}");
		$form->setField('quantity2', "unit={$shortUom}");
		$form->setField('quantity3', "unit={$shortUom}");
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
       if(haveRole('powerUser')){
        	return FALSE;
       } 
        
       return TRUE;
    }
    
    
	/**
	 * След подготовка на лист тулбара
	 */
	public static function on_AfterPrepareListToolbar($mvc, $data) 
	{
		if (!empty ($data->toolbar->buttons ['btnAdd'])) {
			unset($data->toolbar->buttons['btnAdd']);
		}
	}
	
	
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    	}
    	
    	if($fields['-single']){
    		$row->header = $mvc->singleTitle . "&nbsp;№<b>{$row->id}</b>" . " ({$row->state})";
    	
    		$Driver = cls::get($rec->drvId);
			$uomId = cat_UoM::getShortName($Driver->getDriverUom($rec->params));
			foreach (range(1, 3) as $i){
				if($rec->{"quantity{$i}"}){
					$row->{"quantity{$i}"} .= " {$uomId}";
				}
			}
    	}
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$dataRow = $tpl->getBlock('DATA_ROW');
    	
    	$rows = $data->rec->data['rows'];
    	if(count($rows)){
	    	foreach ($rows as $caption => $value){
	    		$dataRow->replace($caption, 'CAPTION');
	    		$dataRow->replace($value, 'VALUE');
	    		$dataRow->removePlaces();
	    		$dataRow->append2master();
	    	}
    	}
    }
    
    
	/**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	$mvc->sendNotificationEmail($rec);
    }
    
    
    /**
     * Изпращане на нотифициращ имейл
     */
    private function sendNotificationEmail($rec)
    {
    	$conf = core_Packs::getConfig('sales');
    	$emailsTo = $conf->SALE_INQUIRE_TO_EMAIL;
    	$sentFrom = $conf->SALE_INQUIRE_FROM_EMAIL;
    	
    	if($emailsTo && $sentFrom){
    		$tpl = getTplFromFile('sales/tpl/InquiryNotificationEmail.shtml');
    		
    		$obj = (object)array('date'       => dt::now(), 
    							 'name'       => $rec->name, 
    							 'email'      => $rec->email, 
    							 'technoName' => cls::get($rec->drvId)->singleTitle);
    		
    		$tpl->placeObject($obj);
    		
    		$PML = cls::get('phpmailer_Instance');
        	$PML->Body = $tpl->getContent();
        	
        	$PML->Subject = "Създаване на ново запитване {$this->getHandle($rec->id)}";
        	$PML->AddAddress($emailsTo);
        	$PML->SetFrom($sentFrom);
	        $PML->Send();
    	}
    }
    
    
	/**
     * След подготовка на тулбара на единичен изглед
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
    	if($rec->state == 'active'){
	    	$Driver = cls::get($rec->drvId);
	    	if($Driver->haveRightFor('add')){
	    		$data->toolbar->addBtn($Driver->singleTitle, $url, "ef_icon=img/16/view.png,title=Създаване на нов {$Driver->singleTitle}");
	    	}
	    	
	    	if($mvc->haveRightFor('makeperson', $rec)){
	    		$data->toolbar->addBtn('Направи визитка', array($mvc, 'makePerson', 'id' => $rec->id), "ef_icon=img/16/vcard.png,title=Създаване на визитка с адресните данни на подателя");
	    	}
    	}
    }
    
    
    /**
     * Създава нова визитка на лице и премества нишката в новата папка
     */
    function act_MakePerson()
    {
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	$cover = doc_Folders::getCover($rec->folderId);
    	expect(!$cover->haveInterface('doc_ContragentDataIntf'));
    	
    	$pRec = (object)array('name' => $rec->name, 'email' => $rec->email);
    	
    	if($pId = crm_Persons::save($pRec)){
    		$folderId = crm_Persons::forceCoverAndFolder($pId);
    		status_Messages::newStatus(tr("|Успешно създаване на лице|* \"{$rec->name}\""));
    	
    		doc_Threads::move($rec->threadId, $folderId);
    		status_Messages::newStatus(tr("|Нишката е успешно преместена в папка |* \"{$rec->name}\""));
    	
    		return redirect(array($this, 'single', $id));
    	}
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = $this->singleTitle . " №{$id}";
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
		$row->recTitle = $row->title;
		
        return $row;
    }
    
    
    /**
     * Парсира текстовия вид на параметрите в удобен масив за работа
     * 
     * @param text $params - параметри
     * @return array $newArr - параметри
     */
    private function parseParams($params)
    {
    	$paramsArr = explode(PHP_EOL, $params);
    	if(count($paramsArr)){
    		foreach ($paramsArr as $str){
    			$arr = explode('=', $str);
    			$newArr[$arr[0]] = $arr[1];
    		}
    	}
    	
    	return $newArr;
    }
    
    
	/**
     * Извиква се преди изпълняването на екшън
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
    	if($action == 'add'){
    		
    		// Ако някой се опита да извика екшъна 'Add' през урл-то
    		expect(FALSE);
    	}
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	// От логнатите потребители, само контрактора може да създава запитвания
    	if($action == 'add' && isset($userId)){
    		if(haveRole('powerUser')){
    			$res = 'no_one';
    		}
    	}
    	
    	if($action == 'makeperson' && isset($rec)){
    		$cover = doc_Folders::getCover($rec->folderId);
    		if($cover->haveInterface('doc_ContragentDataIntf') || $rec->state != 'active'){
    			$res = 'no_one';
    		}
    		
    		if(crm_Persons::fetch("#name = '{$rec->name}' AND #email = '{$rec->email}'")){
    			$res = 'no_one';
    		}
    	}
    }
    
    
	/**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с вашето запитване") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
}