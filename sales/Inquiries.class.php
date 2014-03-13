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
					doc_EmailCreatePlg, bgerp_plg_Blank, plg_Printing, cond_plg_DefaultValues, doc_plg_BusinessDoc';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт, name, company, email, folderId, drvId, createdOn, createdBy';
    
    
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
    public $canEdit = 'powerUser';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,sales';
	
	
	/**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canNew = 'every_one';
    
    
    /**
     * Кой има право да създава визитки на лица?
     */
    public $canMakeperson = 'ceo,sales,crm';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'folderId, name, company, email, tel';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'sales/tpl/SingleLayoutInquiry.shtml';
    
    
    /**
     * Шаблон за нотифициращ имейл (html)
     */
    public $emailNotificationFile = 'sales/tpl/InquiryNotificationEmail.shtml';
    
    
    /**
     * Алтернативен шаблон за нотифициращ имейл (text)
     */
    public $emailNotificationAltFile = 'sales/tpl/InquiryNotificationEmailAlt.txt';
    
    
    /**
     * Икона за фактура
     */
    public $singleIcon = 'img/16/inquiry.png';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    	'name'    => 'lastDocUser|clientData',
    	'email'   => 'lastDocUser|clientData',
    	'tel'     => 'lastDocUser|clientData',
    	'company' => 'lastDocUser|clientData',
    	'country' => 'lastDocUser|clientData|defMethod',
    	'pCode'   => 'lastDocUser|clientData',
    	'place'   => 'lastDocUser|clientData',
    	'address' => 'lastDocUser|clientData',
    );
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('drvId', 'class(interface=techno_ProductsIntf,select=title,allowEmpty)', 'caption=Тип,silent,mandatory');
    	$this->FLD('quantity1', 'double(decimals=2)', 'caption=Количества->Количество|* 1,hint=Въведете количество,width=6em');
    	$this->FLD('quantity2', 'double(decimals=2)', 'caption=Количества->Количество|* 2,hint=Въведете количество,width=6em');
    	$this->FLD('quantity3', 'double(decimals=2)', 'caption=Количества->Количество|* 3,hint=Въведете количество,width=6em');
    	
    	$this->FLD('company', 'varchar(255)', 'caption=Контактни дани->Фирма,class=contactData,hint=Вашата фирма');
    	$this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Контактни дани->Държава,class=contactData,hint=Вашата държава');
    	$this->FLD('name', 'varchar(255)', 'caption=Контактни дани->Лице,class=contactData,mandatory,hint=Вашето име');
    	$this->FLD('email', 'emails()', 'caption=Контактни дани->Имейл,class=contactData,mandatory,hint=Вашият имейл');
    	$this->FLD('tel', 'drdata_PhoneType', 'caption=Контактни дани->Телефони,class=contactData,hint=Вашият телефон');
    	$this->FLD('pCode', 'varchar(16)', 'caption=Контактни дани->П. код,class=pCode,hint=Вашият пощенски код');
        $this->FLD('place', 'varchar(64)', 'caption=Контактни дани->Град,class=contactData,hint=Населено място: град или село и община,hint=Вашаият град');
        $this->FLD('address', 'varchar(255)', 'caption=Контактни дани->Адрес,class=contactData,hint=Вашият адрес');
    
        $this->FLD('params', 'blob(serialize,compress)', 'input=none,silent');
        $this->FLD('data', 'blob(serialize,compress)', 'caption=Информация за продукта,input=none');
    }
    
    
    /**
     * Екшън за добавяне на запитване от нерегистрирани потребители
     */
    function act_New()
    {
    	$this->requireRightFor('new');
    	expect($drvId = Request::get('drvId', 'int'));
    	
    	$params = Request::get('coParams');
    	$params = $this->parseParams($params);
    	
    	// Взимаме формата
    	$form = $this->prepareForm($drvId);
    	$form->rec->params = $params;
    	$form->rec->country = $this->getDefaultCountry($form->rec);
    	
    	// Извикване на евента, за да се закъчи cond_plg_DefaultValues
    	if(core_Users::getCurrent('id', FALSE)){
    		$this->invoke('AfterPrepareCustomForm', array((object)array('form' => $form)));
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
    		if($this->haveRightFor('new')){
    			$this->save($rec);
    			status_Messages::newStatus(tr('Благодарим ви за запитването'), 'success');
    			
    			return followRetUrl();
    		}
    	}
    	
    	$form->toolbar->addSbBtn('Изпрати', 'save', 'id=save, ef_icon = img/16/disk.png,title=Изпращане на запитването');
        $form->toolbar->addBtn('Отказ', getRetUrl(),  'id=cancel, ef_icon = img/16/close16.png,title=Oтказ');
        $tpl = $form->renderHtml();
    	
    	return $tpl;
    }
    
    
    /**
     * Подготовка на формата за екшъна 'New'
     */
    private function prepareForm($drvId)
    {
    	$form = $this->getForm();
    	$form->rec->drvId = $drvId;
    	$form->setField('drvId', 'input=hidden');
    	
    	$form->title = 'Запитване за поръчков продукт';
    	
    	// Ако има логнат потребител
    	if($cu = core_Users::getCurrent('id', FALSE)){
    		$personId = crm_Profiles::fetchField("#userId = {$cu}", 'personId');
    		$personRec = crm_Persons::fetch($personId);
    		
    		// Ако лицето е обвързано с фирма, документа отива в нейната папка
    		if($personCompanyId = $personRec->buzCompanyId){
    			$form->rec->folderId = crm_Companies::forceCoverAndFolder($personCompanyId);
    		} else {
    			
    			// иначе отива в личната папка на лицето
    			$form->rec->folderId = crm_Persons::forceCoverAndFolder($personId);
    		}
    		
    		$form->title .= " |в|*" . doc_Folders::recToVerbal(doc_Folders::fetch($form->rec->folderId))->title;
    		
    		// Слагаме името на лицето, ако не е извлечено
    		$form->setDefault('name', $personRec->name);
    	}
    	
    	return $form;
    }
    
    
	/**
     * Подготовка на формата за добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$data->form->addAttr('drvId', array('onchange' => "addCmdRefresh(this.form);this.form.submit();"));
    	
    	if($data->form->rec->id){
    		foreach($data->form->rec->data['recs'] as $fld => $dRec){
    			$data->form->setDefault($fld, $dRec);
    		}
    	}
    	
    	if($data->form->rec->drvId){
    		$mvc->addFormFieldsFromDriver($data->form);
    	}
    }
    
    
    /**
     * Връща дефолт държавата на заданието
     */
    public static function getDefaultCountry($rec)
    {
    	if(cms_Content::getLang() == 'bg'){
    		$countryId = drdata_Countries::fetchField("#commonName = 'Bulgaria'");
    	} else {
	    	$Drdata = cls::get('drdata_Countries');
	    	$countryId = $Drdata->getByIp();
    	}
    		
    	return $countryId;
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
    	if ($form->isSubmitted()){
    		
    		$form->rec->data = $mvc->getDataFromForm($form);
    	}
    }
    
    
    /**
     * Рутиране на задание по посочения имейл ако потребителя е
     * нерегистриран, в следната последователност
     * 
     * 1. Ако има фирма, форсира се създаването на фирма във визитника
     * 2. Ако от имейла се разпознае папката там
     * 3. В дефолт папката на модела
     * 
     * @param stdClass $rec
     */
    private function route(&$rec)
    {
    	$email = $rec->email;
    	//$rec->inCharge = '2';
    	
    	// Ако има компания се форсира създаването и
    	if($rec->company){
    		return $this->forceCompany($rec);
    	}
    	
    	if($folderId = email_Router::getEmailFolder($email)) {
    		
    		return $folderId;
    	}
    	
    	// Ако няма държава я намира по ип-то
    	if(empty($rec->country)){
    		$Drdata = cls::get('drdata_Countries');
    		$rec->country = $Drdata->getByIp();
    	}
    	
    	// Ако има държава, форсира папка за несортиране с името на държавата
    	if($rec->country){
    		bp($rec);
    		return email_Router::doRuleCountry($rec);
    	}
    	
    	$unRec = new stdClass();
        $unRec->name = $this->defaultFolder;
        $defFolderId = doc_UnsortedFolders::forceCoverAndFolder($unRec, TRUE);
    	
    	// Връщане на папката по подразбиране
    	return $defFolderId;
    }
    
    
    /**
     * Създава визитка на фирма и форсира папката и
     * 
     * @param stdClass $rec - запис на запитване
     * @return int $folderId - папка на фирма
     */
    private function forceCompany($rec)
    {
    	// Имали фирма с това име и имейл
    	$compId = crm_Companies::fetchField("#name = '{$rec->company}' AND #email LIKE '%{$rec->email}%'", 'id');
    	
    	// Ако няма фирма
    	if(empty($compId)){
    		$cRec = new stdClass();
	    	$cRec->name = $rec->company;
	    	foreach (array('email', 'country', 'pCode', 'address', 'place', 'tel') as $fld){
	    		$cRec->$fld = $rec->$fld;
	    	}
	    	
	    	// Запис на фирмата
	    	$compId = crm_Companies::save($cRec);
    	}
    	
    	// Форсиране на папка на фирмата
    	return crm_Companies::forceCoverAndFolder($compId);
    }
    
    
    /**
     * Извлича данните за драйвъра от формата
     * 
     * @param core_Form $form - форма
     * @return array масив с вътрешните и вербалните представяния на полетата
     */
    private function getDataFromForm($form)
    {
    	// Преобразува допълнителната информация във вид удобен за съхраняване
    	$rows = $recs = array();
    	$dataFlds = $form->selectFields('#params');
    	
    	if(count($dataFlds)){
    		foreach ((array)$dataFlds as $k => $v){
    			
    			// За всеки елемент, се извличат неговите вътрешни и вербални данни
    			// вербалните ще се използват за визуализиране в сингъла
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
     * 
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
    	// Можем да добавяме или ако корицата е контрагент или сме в папката на текущата каса
        $cover = doc_Folders::getCover($folderId);
        
        return $cover->haveInterface('doc_ContragentDataIntf');
    }
	
	
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(empty($rec->createdBy)){
    		$row->createdBy = '@anonym';
    	}
    	
    	$row->email = "<div class='email'>{$row->email}</div>";
    	
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    	}
    	
    	if($fields['-single']){
    		$row->header = $mvc->singleTitle . "&nbsp;№<b>{$row->id}</b>" . " ({$row->state})";
    	
    		// До всяко количество се слага unit с мярката на продукта
    		$Driver = cls::get($rec->drvId);
			$uomId = cat_UoM::getShortName($Driver->getDriverUom($rec->params));
			foreach (range(1, 3) as $i){
				if($rec->{"quantity{$i}"}){
					$row->{"quantity{$i}"} .= " {$uomId}";
				}
			}
    	}
    	
    	if($fields['-plainText']){
    		$row->email = $rec->email;
    	}
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$mvc->renderInquiryParams($tpl, $data->rec->data['rows']);
    }
    
    
    /**
     * Рендира информацията за продукта
     */
    private function renderInquiryParams(&$tpl, $rows, $html = FALSE)
    {
    	$dataRow = $tpl->getBlock('DATA_ROW');
    	
    	if(count($rows)){
	    	foreach ($rows as $caption => $value){
	    		$value = ($html) ? strip_tags($value) : $value;
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
    	// Нотифициращ имейл се изпраща само след първоначално активиране
    	if($rec->state == 'active' && empty($rec->brState)){
    		$mvc->sendNotificationEmail($rec);
    	}
    }
    
    
    /**
     * Изпращане на нотифициращ имейл
     * 
     * @param stdClass $rec
     */
    private function sendNotificationEmail($rec)
    {
    	// Взимат се нужните константи от пакета 'sales'
    	$conf = core_Packs::getConfig('sales');
    	$emailsTo = $conf->SALE_INQUIRE_TO_EMAIL;
    	$sentFrom = $conf->SALE_INQUIRE_FROM_EMAIL;
    	
    	// Ако са зададено изходящ и входящ имейл се изпраща нотифициращ имейл
    	if($emailsTo && $sentFrom){
    		
    		// Имейла съответстващ на избраната кутия
    		$sentFrom = email_Inboxes::fetchField($sentFrom, 'email');
    		
    		// Тяло на имейла html и text
    		$tpl = getTplFromFile($this->emailNotificationFile);
    		$tplAlt = getTplFromFile($this->emailNotificationAltFile);
    		
    		$fields = $this->selectFields();
    		$fields['-plainText'] = $fields['-single'] = TRUE;
    		$row = $this->recToVerbal($rec, $fields);
    		$tpl->placeObject($row);
    		$tplAlt->placeObject($row);
    		
    		$this->renderInquiryParams($tpl, $rec->data['rows']);
    		$this->renderInquiryParams($tplAlt, $rec->data['rows'], TRUE);
    		
    		// Изпращане на имейл с phpmailer
    		$PML = cls::get('phpmailer_Instance');
    		$PML->Subject = "Направено е ново запитване на";
    		$PML->Body = $tpl->getContent();
            $PML->AltBody = $tplAlt->getContent();
        	$PML->IsHTML(TRUE);
        	
        	// Адрес на който да се изпрати
        	$PML->AddAddress($emailsTo);
        	
        	// От кой адрес е изпратен
        	$PML->SetFrom($sentFrom);
        	
        	// Изпращане
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
    		
    		// Бутон за генериране на продукт от посочения драйвер
	    	$Driver = cls::get($rec->drvId);
	    	if($Driver->haveRightFor('add')){
	    		$data->toolbar->addBtn($Driver->singleTitle, $url, "ef_icon=img/16/view.png,title=Създаване на нов {$Driver->singleTitle}");
	    	}
	    	
	    	// Ако може да се създава лица от запитването се слага бутон
	    	if($mvc->haveRightFor('makeperson', $rec)){
	    		$companyId = doc_Folders::fetchCoverId($rec->folderId);
	    		$data->toolbar->addBtn('Направи визитка', array('crm_Persons', 'add', 'name' => $rec->name, 'buzCompanyId' => $companyId), "ef_icon=img/16/vcard.png,title=Създаване на визитка с адресните данни на подателя");
	    	}
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
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	// От логнатите потребители, само контрактора може да създава запитвания
    	if($action == 'new' && isset($userId)){
    		if(haveRole('powerUser')){
    			$res = 'no_one';
    		}
    	}
    	
    	// Кога може да се създава лице
    	if($action == 'makeperson' && isset($rec)){
    		
    		// Ако корицата не е на фирма или състоянието не е активно никой неможе
    		$cover = doc_Folders::getCover($rec->folderId);
    		if(!$cover->instance instanceof crm_Companies || $rec->state != 'active'){
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
    
    
	/**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('doc_ContragentDataIntf');
    }
}