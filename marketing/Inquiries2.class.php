<?php



/**
 * Документ "Запитване"
 *
 *
 * @category  bgerp
 * @package   marketing
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class marketing_Inquiries2 extends core_Embedder
{
    
	
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $innerObjectInterface = 'cat_ProductDriverIntf';
	
	
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, marketing_InquiryEmbedderIntf';
    
    
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
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, marketing_Wrapper, plg_Sorting, doc_DocumentPlg, acc_plg_DocumentSummary, plg_Search,
					doc_EmailCreatePlg, bgerp_plg_Blank, plg_Printing, cond_plg_DefaultValues, doc_plg_BusinessDoc,Router=marketing_InquiryRouter, drdata_PhonePlg';
    
    
    /**
     * @see marketin
     */
    public $Router;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,title=Заглавие, name, company, email, folderId, createdOn, createdBy';
    
    
    /**
     * Групиране на документите
     */ 
    public $newBtnGroup = "3.91|Търговия";
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,marketing';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,marketing';
	
	
	/**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'sales_Inquiries';
    
    
	/**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,marketing';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canNew = 'every_one';
    
    
    /**
     * Кой има право да създава визитки на лица?
     */
    public $canMakeperson = 'ceo,crm,marketing';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'folderId, name, title, company, email, place';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'marketing/tpl/SingleLayoutInquiryNew.shtml';
    
    
    /**
     * Шаблон за нотифициращ имейл (html)
     */
    public $emailNotificationFile = 'marketing/tpl/InquiryNotificationEmail.shtml';
    
    
    /**
     * Алтернативен шаблон за нотифициращ имейл (text)
     */
    public $emailNotificationAltFile = 'marketing/tpl/InquiryNotificationEmailAlt.txt';
    
    
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
    	$this->FLD('title', 'varchar', 'caption=Заглавие,formOrder=46');
    	$this->FLD('quantities', 'blob(serialize,compress)', 'input=none,column=none');
    	$this->FLD('quantity1', 'double(decimals=2)', 'caption=Количества->Количество|* 1,hint=Въведете количество,input=none');
    	$this->FLD('quantity2', 'double(decimals=2)', 'caption=Количества->Количество|* 2,hint=Въведете количество,input=none');
    	$this->FLD('quantity3', 'double(decimals=2)', 'caption=Количества->Количество|* 3,hint=Въведете количество,input=none');
    	
    	$this->FLD('name', 'varchar(255)', 'caption=Контактни дани->Лице,class=contactData,mandatory,hint=Лице за връзка,contragentDataField=person,formOrder=50');
    	$this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Контактни дани->Държава,class=contactData,hint=Вашата държава,mandatory,formOrder=51');
    	$this->FLD('email', 'email(valid=drdata_Emails->validate)', 'caption=Контактни дани->Имейл,class=contactData,mandatory,hint=Вашият имейл,formOrder=52');
    	$this->FLD('company', 'varchar(255)', 'caption=Контактни дани->Фирма,class=contactData,hint=Вашата фирма,formOrder=53');
    	$this->FLD('tel', 'drdata_PhoneType', 'caption=Контактни дани->Телефони,class=contactData,hint=Вашият телефон,formOrder=54');
    	$this->FLD('pCode', 'varchar(16)', 'caption=Контактни дани->П. код,class=contactData,hint=Вашият пощенски код,formOrder=55');
        $this->FLD('place', 'varchar(64)', 'caption=Контактни дани->Град,class=contactData,hint=Населено място: град или село и община,formOrder=56');
        $this->FLD('address', 'varchar(255)', 'caption=Контактни дани->Адрес,class=contactData,hint=Вашият адрес,formOrder=57');
    
        $this->FLD('params', 'blob(serialize,compress)', 'input=none,silent');
    	$this->FLD('ip', 'varchar', 'caption=Ип,input=none');
    	$this->FLD('browser', 'varchar(80)', 'caption=Браузър,input=none');
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	if(!$data->form->rec->innerClass){
    		$data->form->setField('title', 'input=hidden');
    	}
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if ($form->isSubmitted()){
    		$form->rec->ip = core_Users::getRealIpAddr();
    		$form->rec->browser = Mode::get('getUserAgent');
    		$form->rec->state = 'active';
    		
    		$form->rec->quantities = array();
    		$quantities = $form->selectFields('#quantityField');
    		foreach ($quantities as $name => $fld){
    			if(isset($form->rec->{$name})){
    				$form->rec->quantities[] = $form->rec->{$name};
    				unset($form->rec->{$name});
    			}
    		}
    	}
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param int $folderId - ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        $folderClass = doc_Folders::fetchCoverClassName($folderId);
        
        return cls::haveInterface('doc_ContragentDataIntf', $folderClass);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(empty($rec->createdBy)){
    		$row->createdBy = '@anonym';
    	}
    	 
    	if (!Mode::is('text', 'plain') && !Mode::is('text', 'xhtml')){
    		$row->email = "<div class='email'>{$row->email}</div>";
    		$row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn);
    	}
    	 
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    		$row->title = $mvc->getTitle($rec);
    
    		$attr = array();
    		$attr['class'] = 'linkWithIcon';
    		$attr['style'] = 'background-image:url(' . sbf($mvc->singleIcon) . ');';
    		$row->title = ht::createLink($row->title, array($mvc, 'single', $rec->id), NULL, $attr);
    	}
    	 
    	// До всяко количество се слага unit с мярката на продукта
    	$Driver = $mvc->getDriver($rec);
    	
    	$uomId = $Driver->getDriverUom($rec->params);
    	$shortName = cat_UoM::getShortName($uomId);
    	
    	if($fields['-single']){
    		foreach (range(1, 3) as $i){
    			if($rec->{"quantity{$i}"}){
    				$row->{"quantity{$i}"} .= " {$shortName}";
    			}
    		}
    	}
    	 
    	$Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
    	$row->quantities = array();
    	
    	if(count($rec->quantities) && is_array($rec->quantities)){
    	    foreach ($rec->quantities as $q){
    			$row->quantities[] = $Double->toVerbal($q) . " {$shortName}";
    		}
    	}
    	
    	$row->time = core_DateTime::mysql2verbal($rec->createdOn);
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = NULL, $mode = NULL)
    {
    	// Нотифициращ имейл се изпраща само след първоначално активиране
    	if($rec->state == 'active' && empty($rec->brState)){
    		if(empty($rec->migrate)){
    			$mvc->isSended = $mvc->sendNotificationEmail($rec);
    		}
    	}
    }
    
    
    /**
     * Рендира количествата от блоба
     */
    private function renderQuantities($quantities, &$tpl, $placeholder)
    {
    	if(count($quantities)){
    		foreach ($quantities as $quant){
    			$clone = clone $tpl->getBlock($placeholder);
    			$clone->replace($quant, $placeholder);
    			$clone->removeBlocks();
    			$clone->append2Master();
    		}
    	}
    }
    
    
    /**
     * Изпращане на нотифициращ имейл
     *
     * @param stdClass $rec
     */
    public function sendNotificationEmail($rec)
    {
    	// Взимат се нужните константи от пакета 'marketing'
    	$conf = core_Packs::getConfig('marketing');
    	$emailsTo = $conf->MARKETING_INQUIRE_TO_EMAIL;
    	$sentFrom = $conf->MARKETING_INQUIRE_FROM_EMAIL;
    	
    	// Ако са зададено изходящ и входящ имейл се изпраща нотифициращ имейл
    	if($emailsTo && $sentFrom){
    
    		// Имейла съответстващ на избраната кутия
    		$sentFrom = email_Inboxes::fetchField($sentFrom, 'email');
    		
    		// Тяло на имейла html и text
    
    		$fields = $this->selectFields();
    
    		// Изпращане на имейл с phpmailer
    		$PML = cls::get('phpmailer_Instance');
    
    	   /*
    		* Ако не е зададено е 8bit
    		* Проблема се появява при дълъг стринг - без интервали и на кирилица.
    		* Понеже е entity се режи грешно от phpmailer -> class.smtpl.php - $max_line_length = 998;
    		*
    		* @see #Sig281
    		*/
    		$PML->Encoding = "quoted-printable";
    
    		Mode::push('text', 'plain');
    
    		$Driver = $this->getDriver($rec);
    		
    		$tplAlt = getTplFromFile($this->emailNotificationAltFile);
    		
    		// Рендиране на бодито
    		$this->renderInquiryParams($tplAlt, $rec->innerForm, $Driver);
    		$rowPlain = $this->recToVerbal($rec, $fields);
    	
    		$tplAlt->placeObject($rowPlain);
    		$this->renderQuantities($rowPlain->quantities, $tplAlt, 'QUANTITY_ROW');
    		$PML->AltBody = $tplAlt->getContent();
    
    		Mode::pop('text');
    
    		// Рендиране на алт бодито
    		Mode::push('text', 'xhtml');
    		$tpl = getTplFromFile($this->emailNotificationFile);
    		$this->renderInquiryParams($tpl, $rec->innerForm, $Driver);
    		$row = $this->recToVerbal($rec, $fields);
    		$tpl->placeObject($row);
    		$this->renderQuantities($row->quantities, $tpl, 'QUANTITY_ROW');
    		
    		$res = $tpl;
    		
    		//Създаваме HTML частта на документа и превръщаме всички стилове в inline
    		//Вземаме всичките css стилове
    
    		$css = file_get_contents(sbf('css/common.css', "", TRUE)) .
    		"\n" . file_get_contents(sbf('css/Application.css', "", TRUE));
    
    		$res = '<div id="begin">' . $res->getContent() . '<div id="end">';
    
    		// Вземаме пакета
    		$conf = core_Packs::getConfig('csstoinline');
    
    		// Класа
    		$CssToInline = $conf->CSSTOINLINE_CONVERTER_CLASS;
    
    		// Инстанция на класа
    		$inst = cls::get($CssToInline);
    
    		// Стартираме процеса
    		$res =  $inst->convert($res, $css);
    
    		$res = str::cut($res, '<div id="begin">', '<div id="end">');
    		
    		$PML->Body = $res;
    		$PML->IsHTML(TRUE);
    		 
        	// Ембедване на изображенията
    		email_Sent::embedSbfImg($PML);
    		 
    		Mode::pop('text');
    
    		// Име на фирма/лице/име на продукта
    		$subject = $this->getTitle($rec);
    		$PML->Subject = str::utf2ascii($subject);
    		$files = $this->getAttachedFiles($rec);
    		 
    		// Ако има прикачени файлове, добавяме ги
    		if(count($files)){
	    		foreach ($files as $fh => $name){
		    		$name = fileman_Files::fetchByFh($fh, 'name');
		    		$path = fileman_Files::fetchByFh($fh, 'path');
		    		$PML->AddAttachment($path, $name);
	    		}
    		}
    		
    		// Адрес на който да се изпрати
    		$PML->AddAddress($emailsTo);
    		$PML->AddCustomHeader("Customer-Origin-Email: {$rec->email}");
    		 
    		// От кой адрес е изпратен
    		$PML->SetFrom($sentFrom);
    		
    		// Изпращане
    		return $PML->Send();
    	}
    	 
    	return TRUE;
    }
    
    
    /**
     * Връща прикачените файлове
     */
    public function getAttachedFiles($rec)
    {
    	$res = array();
    	
    	$Driver = $this->getDriver($rec);
    	$form = $this->getForm();
    	$Driver->addEmbeddedFields($form);
    	
    	$arr = (array)$rec->innerForm;
    	foreach ($arr as $name => $value){
    		if($form->getFieldType($name, FALSE) instanceof type_Richtext){
    			$files = fileman_RichTextPlg::getFiles($value);
    			$res = array_merge($res, $files);
    		}
    	}
    	
    	return $res;
    }
    
    
    /**
     * Рендира информацията за продукта
     */
    private function renderInquiryParams(&$tpl, $recs, $Driver)
    {
    	$recs = (array)$recs;
    	
    	$form = $this->getForm();
    	$fieldsBefore = arr::make(array_keys($form->selectFields()), TRUE);
    	$Driver->addEmbeddedFields($form);
    	$fieldsAfter = arr::make(array_keys($form->selectFields()), TRUE);
    	
    	$params = array_diff_assoc($fieldsAfter, $fieldsBefore);
    	$params = array('title' => 'title') + $params;
    	
    	$dataRow = $tpl->getBlock('DATA_ROW');
    	 
    	foreach ($params as $name){
    		if(empty($recs[$name])) continue;
    		$value = $form->getFieldType($name)->toVerbal($recs[$name]);
    		$dataRow->replace(tr($form->getField($name)->caption), 'CAPTION');
    		$dataRow->replace($value, 'VALUE');
    		$dataRow->removePlaces();
    		$dataRow->append2master();
    	}
    }
    
    
    /**
     * Връща името на запитването
     */
    private function getTitle($id)
    {
    	$rec = $this->fetchRec($id);
    	$Driver = $this->getDriver($id);
    	 
    	$name = $this->getFieldType('name')->toVerbal((($rec->company) ? $rec->company : $rec->name));
    	
    	$subject = "{$name} / $rec->title";
    	 
    	$Varchar = cls::get('type_Varchar');
    	 
    	return $Varchar->toVerbal($subject);
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
    	 
    	if($rec->state == 'active'){
    
    		if($pId = cat_Products::fetchField("#originId = {$rec->containerId} AND #state = 'active'")){
    			$data->toolbar->addBtn('Артикул', array('cat_Products', 'single', $pId), "ef_icon=img/16/wooden-box.png,title=Преглед на артикул по това запитване");
    		} else {
    			// Създаване на нов артикул от запитването
    			if(cat_Products::haveRightFor('add', (object)array('folderId' => $rec->folderId))){
    				$url = array('cat_Products', 'add', "innerClass" => $rec->innerClass, "originId" => $rec->containerId);
    				if(doc_Folders::getCover($rec->folderId)->haveInterface('doc_ContragentDataIntf')){
    					$url['folderId'] = $rec->folderId; 
    					$url['threadId'] = $rec->threadId;
    				}
    				
    				$data->toolbar->addBtn('Артикул', $url, "ef_icon=img/16/wooden-box.png,title=Създаване на артикул по това запитване");
    			}
    		}
    
    		// Ако може да се създава лица от запитването се слага бутон
    		if($mvc->haveRightFor('makeperson', $rec)){
    			$companyId = doc_Folders::fetchCoverId($rec->folderId);
    			$data->toolbar->addBtn('Визитка на лице', array('crm_Persons', 'add', 'name' => $rec->name, 'buzCompanyId' => $companyId, 'country' => $rec->country), "ef_icon=img/16/vcard.png,title=Създаване на визитка с адресните данни на подателя");
    		}
    		
    		// Ако е настроено да се изпраща нотифициращ имейл, добавяме бутона за препращане
    		$conf = core_Packs::getConfig('marketing');
    		if($mvc->haveRightFor('add') && $conf->MARKETING_INQUIRE_TO_EMAIL && $conf->MARKETING_INQUIRE_FROM_EMAIL){
    			$data->toolbar->addBtn('Препращане', array($mvc, 'send', $rec->id), "ef_icon=img/16/email_forward.png,warning=Сигурни ли сте че искате да препратите имейла на '{$conf->MARKETING_INQUIRE_TO_EMAIL}',title=Препращане на имейла с запитването на '{$conf->MARKETING_INQUIRE_TO_EMAIL}'");
    		}
    	}
    }
    
    
    /**
     * Препраща имейл-а генериран от създаването на запитването отново
     */
    public function act_Send()
    {
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	
    	$this->requireRightFor('add');
    	
    	$this->sendNotificationEmail($rec);
    	
    	redirect(array($this, 'single', $rec->id), 'Успешно препращане');
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	 
    	$row = new stdClass();
    	$row->title       = $this->getTitle($rec);
    	$row->authorId    = $rec->createdBy;
    	$row->author      = $rec->email;
    	$row->authorEmail = $rec->email;
    	$row->state       = $rec->state;
    	$row->recTitle    = $row->title;
    
    	return $row;
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	// Кога може да се създава лице
    	if($action == 'makeperson' && isset($rec)){
    
    		// Ако корицата не е на фирма или състоянието не е активно никой не може
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
    public static function getDefaultEmailBody($id)
    {
    	$rec = static::fetch($id);
    	$date = dt::mysql2verbal($rec->createdOn, 'd-M');
    	$time = dt::mysql2verbal($rec->createdOn, 'H:i');
    	
    	$tpl = new ET(tr("|Благодарим за Вашето запитване|*, |получено на|* {$date} |в|* {$time} |чрез нашия уеб сайт|*"));
    
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
    
    
    /**
     * Дъстояние на нишката
     */
    public static function getThreadState($id)
    {
    	return 'opened';
    }
    
    
    /**
     * Екшън за добавяне на запитване от нерегистрирани потребители
     */
    function act_New()
    {
    	$this->requireRightFor('new');
    	expect($drvId = Request::get('drvId', 'int'));
    	expect($inqCls = Request::get('inqCls'));
    	expect($inqId = Request::get('inqId'));
    	if($lg = Request::get('Lg')){
    		cms_Content::setLang($lg);
    		core_Lg::push($lg);
    	}
    	
    	$Source = new core_ObjectReference($inqCls, $inqId);
    	expect($Source->haveInterface('marketing_InquirySourceIntf'));
    	$params = $Source->getCustomizationParams();
    	
    	$form = $this->prepareForm($drvId);
    	
    	$form->rec->params = $params;
    	$form->setDefault('country', $this->getDefaultCountry($form->rec));
    	$data = (object)array('form' => $form);
    	$Driver = $this->getDriver($form->rec);
    	$Driver->setDriverParams($params);
    	
    	parent::on_AfterPrepareEditForm($this, $data);
    	$form->title = "|Запитване за|* <b>{$form->getFieldType('title')->toVerbal($form->rec->title)}</b>";
    	$Driver = $this->getDriver($form->rec);
    	$form->input();
    	self::on_AfterInputEditForm($this, $form);
    	
    	if(isset($form->rec->title)){
    		$form->setField('title', 'input=hidden');
    	}
    	
    	$Driver->checkEmbeddedForm($form);
    	
    	// След събмит на формата
    	if($form->isSubmitted()){
    		
    		$rec = &$form->rec;
    		$rec->state = 'active';
    		$rec->ip = core_Users::getRealIpAddr();
    		$rec->browser = Mode::get('getUserAgent');
    	
    		if(empty($rec->folderId)){
    			$rec->folderId = $this->Router->route($rec);
    		}
    		
    		$form->rec->innerForm = clone $form->rec;
    		
    		// Запис и редирект
    		if($this->haveRightFor('new')){
    		    
    		    vislog_History::add('Ново запитване');
    		    
    		    $email = trim($form->rec->email);
    		    $names = trim($form->rec->name);
    		    $company = trim($form->rec->company);
    		    $userData = array('email' => $email, 'names' => $names);
        		if ($company) {
                    $userData['company'] = $company;
                }
                core_Browser::setVars($userData);
    		    
    			$id = $this->save($rec);
    			$cu = core_Users::getCurrent('id', FALSE);
    			 
    			// Ако няма потребител, записваме в бисквитка ид-то на последното запитване
    			if(!$cu){
    				setcookie("inquiryCookie[inquiryId]", str::addHash($id, 10), time() + 2592000);
    			}
    			
    			status_Messages::newStatus(tr('Благодарим ви за запитването'), 'success');
    			 
    			// Ако има грешка при изпращане, тя се показва само на powerUser-и
    			if (!$this->isSended && $cu && haveRole('powerUser')) {
    				status_Messages::newStatus(tr('Грешка при изпращане'), 'error');
    			}
    			 
    			return followRetUrl();
    		}
    	}
    	
    	// Попълваме данните, които потребителя е въвел преди
    	$vars = core_Browser::getVars(array('email', 'names', 'company'));
    	if ($vars) {
        	if ($vars['email']) {
        	    $form->setDefault('email', $vars['email']);
        	}
        	
    	    if ($vars['names']) {
        	    $form->setDefault('name', $vars['names']);
        	}
    	    
        	if ($vars['company']) {
        	    $form->setDefault('company', $vars['company']);
        	}
    	}
    	
    	$form->toolbar->addSbBtn('Изпрати', 'save', 'id=save, ef_icon = img/16/disk.png,title=Изпращане на запитването');
    	$form->toolbar->addBtn('Отказ', getRetUrl(),  'id=cancel, ef_icon = img/16/close16.png,title=Oтказ');
    	$tpl = $form->renderHtml();
    	 
    	// Поставяме шаблона за външен изглед
    	Mode::set('wrapper', 'cms_page_External');
    	
    	if($lg){
    		core_Lg::pop();
    	}
    	
    	return $tpl;
    }
    

    /**
     * След подготовка на ембеднатата форма
     */
    public static function on_AfterPrepareEmbeddedForm(core_Mvc $mvc, core_Form &$form)
    {
    	$Driver = $mvc->getDriver($form->rec);
    	
    	$uom = cat_UoM::getShortName($Driver->getDriverUom());
    	
    	// Добавяме полета за количество според параметрите на продукта
    	$params = $Driver->getDriverParams();
    	if(!isset($params['quantities'])){
    		$conf = core_Packs::getConfig('marketing');
    		$params['quantities'] = $conf->MARKETING_INQUIRY_QUANTITIES;
    	}
    	
    	for($i = 1; $i <= $params['quantities']; $i++){
    		if($form->getField("quantity{$i}", FALSE)){
    			$form->setField("quantity{$i}", "input,quantityField,formOrder=4{$i},unit={$uom}");
    		} else {
    			$form->FNC("quantity{$i}", 'double', "caption=Количества->Количество|* {$i},quantityField,input,formOrder=4{$i},unit={$uom}");
    		}
    	}
    }
    
    
    /**
     * Подготовка на формата за екшъна 'New'
     */
    private function prepareForm($drvId)
    {
    	$form = $this->getForm();
    	$form->rec->innerClass = $drvId;
    	$form->setField('innerClass', 'input=hidden');
    	 
    	$form->title = 'Запитване за поръчков продукт';
    	$cu = core_Users::getCurrent('id', FALSE);
    	 
    	// Ако има логнат потребител
    	if($cu && !haveRole('powerUser')){
    		$personId = crm_Profiles::fetchField("#userId = {$cu}", 'personId');
    		$personRec = crm_Persons::fetch($personId);
    		$inCharge = marketing_Router::getInChargeUser($rec->place, $rec->country);
    
    		// Ако лицето е обвързано с фирма, документа отива в нейната папка
    		if($personCompanyId = $personRec->buzCompanyId){
    			$form->rec->folderId = crm_Companies::forceCoverAndFolder((object)array('id' => $personCompanyId, 'inCharge' => $inCharge));
    		} else {
    			try{
    				expect($personRec || $personId, "Няма визитка на контрактор {$personId}");
    			} catch(core_exception_Expect $e){
    				$e->logError();
    			}
    			 
    			// иначе отива в личната папка на лицето
    			$form->rec->folderId = crm_Persons::forceCoverAndFolder((object)array('id' => $personId, 'inCharge' => $inCharge));
    		}
    
    		$form->title .= " |в|*" . doc_Folders::recToVerbal(doc_Folders::fetch($form->rec->folderId))->title;
    
    		// Слагаме името на лицето, ако не е извлечено
    		$form->setDefault('name', $personRec->name);
    	}
    	 
    	// Ако няма потребител, но има бискйвитка зареждаме данни от нея
    	if(!$cu && isset($_COOKIE['inquiryCookie']['inquiryId'])){
    		$this->setFormDefaultFromCookie($form);
    	}
    	 
    	return $form;
    }


    /**
     * Ако има бисквитка с последно запитване, взима контактите данни от нея
     */
    private function setFormDefaultFromCookie(&$form)
    {
    	$inquiryId = str::checkHash($_COOKIE['inquiryCookie']['inquiryId'], 10);
    	$lastInquiry = $this->fetch($inquiryId);
    	$contactFields = $this->selectFields("#class == 'contactData'");
    	foreach ($contactFields as $name => $fld){
    		$form->rec->{$name} = $lastInquiry->{$name};
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
     * Намира кои полета са дошли от драйвера
     */
    public function getFieldsFromDriver($id)
    {
    	$rec = $this->fetchRec($id);
    	$Driver = $this->getDriver($rec);
    	
    	$form = $this->getForm();
    	$fieldsBefore = arr::make(array_keys($form->selectFields()), TRUE);
    	$Driver->addEmbeddedFields($form);
    	$fieldsAfter = arr::make(array_keys($form->selectFields()), TRUE);
    	
    	$params = array_diff_assoc($fieldsAfter, $fieldsBefore);
    	
    	return $params;
    }
    
    
    /**
     * Изпълнява се преди запис
     */
    public static function on_BeforeSave($mvc, &$id, $rec, $fields = NULL, $mode = NULL)
    {
    	// Ако има оригинална дата на създаване, подменяме нея с текущата
    	if(isset($rec->oldCreatedOn)){
    		$rec->createdOn = $rec->oldCreatedOn;
    	}
    }
    
    
    /**
     * След рендиране на еденичния изглед
     */
    public static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$mvc->renderQuantities($data->row->quantities, $tpl, 'QUANTITY_ROW');
    }
}