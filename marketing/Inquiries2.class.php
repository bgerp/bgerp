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
class marketing_Inquiries2 extends embed_Manager
{
    
	
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'cat_ProductDriverIntf';
	
	
	/**
	 * Как се казва полето за избор на вътрешния клас
	 */
	public $driverClassField = 'innerClass';
	

	/**
	 * Флаг, който указва, че документа е партньорски
	 */
	public $visibleForPartners = TRUE;
	
	
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
     * Икона за фактура
     */
    public $singleIcon = 'img/16/inquiry.png';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn';
    
    
    /**
     * Опашка за записи, на които трябва да се изпратят нотифициращи имейли
     */
    protected $sendNotificationEmailQueue = array();
    
    
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
    	$this->FLD('proto', "key(mvc=cat_Products,allowEmpty,select=name)", "caption=Прототип,silent,input=hidden,refreshForm,placeholder=Популярни продукти");
    	$this->FLD('title', 'varchar', 'caption=Заглавие,silent');
    	
    	$this->FLD('quantities', 'blob(serialize,compress)', 'input=none,column=none');
    	$this->FLD('quantity1', 'double(decimals=2)', 'caption=Количества->Количество|* 1,hint=Въведете количество,input=none,formOrder=47');
    	$this->FLD('quantity2', 'double(decimals=2)', 'caption=Количества->Количество|* 2,hint=Въведете количество,input=none,formOrder=48');
    	$this->FLD('quantity3', 'double(decimals=2)', 'caption=Количества->Количество|* 3,hint=Въведете количество,input=none,formOrder=49');
    	$this->FLD('inqDescription', 'richtext(rows=4)', 'caption=Съобщение,mandatory,before=name');
    	$this->FLD('name', 'varchar(255)', 'caption=Контактни дани->Имена,class=contactData,mandatory,hint=Вашето име,contragentDataField=person,formOrder=50');
    	$this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Контактни дани->Държава,class=contactData,hint=Вашата държава,mandatory,formOrder=51');
    	$this->FLD('email', 'email(valid=drdata_Emails->validate)', 'caption=Контактни дани->Имейл,class=contactData,mandatory,hint=Вашият имейл,formOrder=52');
    	$this->FLD('company', 'varchar(255)', 'caption=Контактни дани->Фирма,class=contactData,hint=Вашата фирма,formOrder=53');
    	$this->FLD('tel', 'drdata_PhoneType', 'caption=Контактни дани->Телефони,class=contactData,hint=Вашият телефон,formOrder=54');
    	$this->FLD('pCode', 'varchar(16)', 'caption=Контактни дани->П. код,class=contactData,hint=Вашият пощенски код,formOrder=55');
        $this->FLD('place', 'varchar(64)', 'caption=Контактни дани->Град,class=contactData,hint=Населено място: град или село и община,formOrder=56');
        $this->FLD('address', 'varchar(255)', 'caption=Контактни дани->Адрес,class=contactData,hint=Вашият адрес,formOrder=57');
    
    	$this->FLD('ip', 'varchar', 'caption=Ип,input=none');
    	$this->FLD('browser', 'varchar(80)', 'caption=UA String,input=none');
      	$this->FLD('brid', 'varchar(8)', 'caption=Браузър,input=none');
    }


    /**
     * Разширява формата за редакция
     * 
     * @param stdClass $data
     * @return void
     */
    private function expandEditForm(&$data)
    {
    	$form = &$data->form;
    	$form->setField('innerClass', "remember,removeAndRefreshForm=proto|measureId|meta");
    	
    	// Ако има избран прототип, зареждаме му данните в река
    	if(isset($form->rec->proto)){
    		if($pRec = cat_Products::fetch($form->rec->proto)) {
    			if(is_array($pRec->driverRec)){
    				foreach ($pRec->driverRec as $fld => $value){
    					$form->rec->{$fld} = $value;
    				}
    			}
    		}
    	}
    	
    	$caption = 'Количества|*';
    	if(isset($data->Driver)){
    		
    		if($pRec->measureId){
    			$measureName = cat_UoM::getShortName($pRec->measureId);
    		}
    		$measureName = $data->Driver->getDefaultUom($measureName);
    		if(!$measureName){
    			$measureName = 'pcs';
    		}
    		
    		$measureId = cat_UoM::fetchBySinonim($measureName)->id;
    		$uom = cat_UoM::getShortName($measureId);
    	
    		if(isset($form->rec->moq)){
    			$moq = cls::get('type_Double', array('params' => array('smartRound' => 'smartRound')))->toVerbal($form->rec->moq);
    			$caption .= "|* <small><i>( |Минимална поръчка|* " . $moq . " {$uom} )</i></small>";
    		}
    	}
    	 
    	// Добавяме полета за количество според параметрите на продукта
    	$quantityCount = $form->rec->quantityCount;
    	if(!$quantityCount || $quantityCount > 3 || $quantityCount < 0){
    		$quantityCount = 3;
    	}
    	
    	for($i = 1; $i <= $quantityCount; $i++){
    		$fCaption = ($quantityCount === 1) ? 'Количество' : "Количество|* {$i}";
    		$form->setField("quantity{$i}", "input,unit={$uom},caption={$caption}->{$fCaption}");
    		if(isset($form->rec->moq)){
    			$form->setFieldTypeParams("quantity{$i}", array('min' => $form->rec->moq));
    		}
    	}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;

    	if($form->rec->innerClass){
    		$protoProducts = cat_Categories::getProtoOptions($form->rec->innerClass);
            if(count($protoProducts)){
            	$form->setField('proto', 'input');
            	$form->setOptions('proto', $protoProducts);
            }
    	}
    	
    	$mvc->expandEditForm($data);
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

        $row->brid = log_Browsers::getLink($rec->brid);
    	 
    	if($fields['-list']){
    		$row->title = $mvc->getTitle($rec);
    
    		$attr = array();
    		$attr['class'] = 'linkWithIcon';
    		$attr['style'] = 'background-image:url(' . sbf($mvc->singleIcon) . ');';
    		$row->title = ht::createLink($row->title, array($mvc, 'single', $rec->id), NULL, $attr);
    	}
    	 
    	// До всяко количество се слага unit с мярката на продукта
    	if($Driver = $mvc->getDriver($rec->id)){
    		$uomName = $Driver->getDefaultUom();
    		if(!$uomName){
    			$uomName = 'pcs';
    		}
    		$uomId = cat_UoM::fetchBySinonim($uomName)->id;
    		$shortName = cat_UoM::getShortName($uomId);
    	}
    	
    	$Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
    	foreach (range(1, 3) as $i){
    		if(empty($rec->{"quantity{$i}"})){
    			if(isset($rec->quantities[$i - 1])){
    				$rec->{"quantity{$i}"} = $rec->quantities[$i - 1];
    				$row->{"quantity{$i}"} = $Double->toVerbal($rec->{"quantity{$i}"});
    			}
    		}
    	}
    	
    	foreach (range(1, 3) as $i){
    		if($rec->{"quantity{$i}"}){
    			$row->{"quantity{$i}"} .= " {$shortName}";
    		}
    	}
    	
    	$row->time = core_DateTime::mysql2verbal($rec->createdOn);
    	
    	if(isset($rec->proto)){
    		$row->proto = cat_Products::getHyperlink($rec->proto);
    	}
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = NULL, $mode = NULL)
    {
    	// Нотифициращ имейл се изпраща само след първоначално активиране
    	if($rec->state == 'active' && empty($rec->brState)){
    		if(empty($rec->migrate)){
    			$mvc->sendNotificationEmailQueue[$rec->id] = $rec;
    		}
    	}
    }
    
    
    /**
     * Изчиства записите, заопашени за запис
     *
     * @param acc_Items $mvc
     */
    public static function on_Shutdown($mvc)
    {
    	if(is_array($mvc->sendNotificationEmailQueue)){
    		foreach ($mvc->sendNotificationEmailQueue as $rec){
    			$mvc->isSended = $mvc->sendNotificationEmail($rec);
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
    	$sentFromBox = $conf->MARKETING_INQUIRE_FROM_EMAIL;
    	
    	// Ако са зададено изходящ и входящ имейл се изпраща нотифициращ имейл
    	if($emailsTo && $sentFromBox){
    
    		// Имейла съответстващ на избраната кутия
    		$sentFrom = email_Inboxes::fetchField($sentFromBox, 'email');
    		
    		// Тяло на имейла html и text
    
    		$fields = $this->selectFields();
    		$fields['-single'] = TRUE;
    		
    		// Изпращане на имейл с phpmailer
    		$PML = email_Accounts::getPML($sentFrom);
    		    
    	   /*
    		* Ако не е зададено е 8bit
    		* Проблема се появява при дълъг стринг - без интервали и на кирилица.
    		* Понеже е entity се режи грешно от phpmailer -> class.smtpl.php - $max_line_length = 998;
    		*
    		* @see #Sig281
    		*/
    		$PML->Encoding = "quoted-printable";
    		$Driver = $this->getDriver($rec->id);
    
    		// Рендиране на алт бодито
    		Mode::push('text', 'xhtml');
    		$tpl = getTplFromFile($this->emailNotificationFile);
    		
    		$this->renderInquiryParams($tpl, $rec, $Driver);
    		$row = $this->recToVerbal($rec, $fields);
    		
    		$tpl->placeObject($row);
    		
    		$res = $tpl;
    		
    		$altText = $res->getContent();
    		
    		// Създаваме HTML частта на документа и превръщаме всички стилове в inline
    		// Вземаме всичките css стилове
    
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
    
    		Mode::push('text', 'plain');
    		$altText = html2text_Converter::toRichText($altText);
    		$altText = cls::get('type_RichText')->toVerbal($altText);
    		Mode::pop('text');
    		
    		$PML->AltBody = $altText;
    		
    		// Име на фирма/лице/име на продукта
    		$subject = $this->getTitle($rec);
    		$PML->Subject = str::utf2ascii($subject);
    		$files = $this->getAttachedFiles($rec, $Driver);
    		
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
    		
    		if ($sendStatus = $PML->Send()) {
    		    // Задаваме екшъна за изпращането
                doclog_Documents::pushAction(
                    array(
                        'containerId' => $rec->containerId,
                        'threadId' => $rec->threadId,
                        'action' => doclog_Documents::ACTION_SEND,
                        'data' => (object)array(
                            'sendedBy' => core_Users::getCurrent(),
                            'from' => $sentFromBox,
                            'to' => $emailsTo
                        )
                    )
                );
                
                doclog_Documents::flushActions();
    		} else {
    		    marketing_Inquiries2::logErr('Грешка при изпращане', $rec->id);
    		}
    		
    		// Изпращане
    		return $sendStatus;
    	}
    	 
    	return TRUE;
    }
    
    
    /**
     * Връща прикачените файлове
     */
   private function getAttachedFiles($rec, $Driver)
    {
    	$res = array();
    	
    	$fieldset = $this->getForm();
    	$Driver->addFields($fieldset);
    	$params = $fieldset->selectFields();
    	
    	$arr = (array)$rec;
    	foreach ($arr as $name => $value){
    		if($fieldset->getFieldType($name, FALSE) instanceof type_Richtext){
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
    	
    	$fieldset = cls::get('core_Fieldset');
    	$fieldset->FLD('title', 'varchar', 'caption=Заглавие');
    	$Driver->addFields($fieldset);
    	$params = $fieldset->selectFields();
    	$params = array('title' => 'title') + $params;
    	
    	$dataRow = $tpl->getBlock('DATA_ROW');
    	
    	foreach ($params as $name => $fld){
    		if(empty($recs[$name])) continue;
    		if($fieldset->getFieldParam($name, 'single') === 'none') continue;
    		
    		$value = $fieldset->getFieldType($name)->toVerbal($recs[$name]);
    		$dataRow->replace(tr($fieldset->getField($name)->caption), 'CAPTION');
    		$dataRow->replace($value, 'VALUE');
    		$dataRow->removePlaces();
    		$dataRow->append2master();
    	}
    	
    	// Добавя параметрите на продукта (ако има)
    	$pQuery = cat_products_Params::getQuery();
    	$pQuery->where("#productId = {$recs['id']}");
    	$pQuery->where("#classId = {$this->getClassId()}");
    	while($pRec = $pQuery->fetch()){
    		$paramRec = cat_Params::fetch($pRec->paramId);
    		$value = cat_Params::getTypeInstance($pRec->paramId)->toVerbal($pRec->paramValue);
    		if(isset($paramRec->suffix)){
    			$value .= " {$paramRec->suffix}";
    		}
    		
    		$dataRow->replace(tr(cat_Params::getVerbal($paramRec, 'name')), 'CAPTION');
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
    	$Driver = $this->getDriver($rec->id);
    	 
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
    				$url = array('cat_Products', 'add', "innerClass" => $rec->innerClass, "originId" => $rec->containerId, 'proto' => $rec->proto, 'ret_url' => TRUE);
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
    			$data->toolbar->addBtn('Препращане', array($mvc, 'send', $rec->id), array('ef_icon'=> "img/16/email_forward.png", 'warning' => "Сигурни ли сте, че искате да препратите имейла на '{$conf->MARKETING_INQUIRE_TO_EMAIL}'",'title' => "Препращане на имейла с запитването на '{$conf->MARKETING_INQUIRE_TO_EMAIL}'"));
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
    	$proto = Request::get('protos', 'varchar');
    	$proto = keylist::toArray($proto);
    	if(count($proto)){
    		foreach ($proto as $pId => &$name){
    			$name = cat_Products::getTitleById($pId, FALSE);
    		}
    	}
    	
    	if($lg = Request::get('Lg')){
    		cms_Content::setLang($lg);
    		core_Lg::push($lg);
    	}
    	
    	$form = $this->prepareForm($drvId);
    	$form->FLD('moq', 'double', 'input=hidden,silent');
    	$form->FLD('quantityCount', 'double', 'input=hidden,silent');
    	$form->input(NULL, 'silent');
    	
    	if(count($proto)){
    		
    		$form->setOptions('proto', $proto);
    		if(count($proto) === 1){
    			$form->setDefault('proto', key($proto));
    			$form->setField('proto', 'input=hidden');
    		} else {
    			$form->setField('proto', 'input,caption=Вид,placeholder=Артикули');
    		}
    	} else {
    		$form->setField('proto', 'input=none');
    	}
    	
    	$form->setDefault('country', $this->getDefaultCountry($form->rec));
    	$data = (object)array('form' => $form);
    	
    	if(cls::load($form->rec->{$this->driverClassField}, TRUE)){
    		$Driver = cls::get($form->rec->{$this->driverClassField}, array('Embedder' => $this));
    		$data->Driver = $Driver;
    		
    		$Driver->addFields($data->form);
    		$this->expandEditForm($data);
    		
    		$Driver->invoke('AfterPrepareEditForm', array($this, &$data, &$data));
    		
    		$form->input();
    		$this->invoke('AfterInputEditForm', array(&$form));
    	
    		if(isset($form->rec->title)){
    			$form->setField('title', 'input=hidden');
    		}
    	}
    	
    	$form->title = "|Запитване за|* <b>{$form->getFieldType('title')->toVerbal($form->rec->title)}</b>";
    	
    	if(isset($form->rec->title)){
    		$form->setField('title', 'input=hidden');
    	}
    	
    	// След събмит на формата
    	if($form->isSubmitted()){
    		
    		$rec = &$form->rec;
    		$rec->state = 'active';
    		$rec->ip = core_Users::getRealIpAddr();
    		$rec->brid = log_Browsers::getBrid();
    	
    		if(empty($rec->folderId)){
    			$rec->folderId = $this->Router->route($rec);
    		}
    		
    		// Запис и редирект
    		if($this->haveRightFor('new')){
    		    
    		    vislog_History::add('Ново маркетингово запитване');
    		    
    			$cu = core_Users::getCurrent('id', FALSE);
    		    
    			// Ако няма потребител
    			if(!$cu){
        		    $contactFields = $this->selectFields("#class == 'contactData'");
                    $fieldNamesArr = array_keys($contactFields);
                    $userData = array();
                    foreach ((array)$fieldNamesArr as $fName) {
                        if (!trim($form->rec->$fName)) continue;
                        $userData[$fName] = $form->rec->$fName;
                    }
                    log_Browsers::setVars($userData);
    			}
    		    
    			$id = $this->save($rec);
    			
    			status_Messages::newStatus(tr('Благодарим Ви за запитването'), 'success');
    			 
    			return followRetUrl();
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
    				crm_Persons::logErr('Няма визитка на контрактор', $personId);
    			}
    			 
    			// иначе отива в личната папка на лицето
    			$form->rec->folderId = crm_Persons::forceCoverAndFolder((object)array('id' => $personId, 'inCharge' => $inCharge));
    		}
    
    		$form->title .= " |в|*" . doc_Folders::recToVerbal(doc_Folders::fetch($form->rec->folderId))->title;
    
    		// Слагаме името на лицето, ако не е извлечено
    		$form->setDefault('name', $personRec->name);
    	}
    	 
    	// Ако няма потребител, но има бискйвитка зареждаме данни от нея
    	if(!$cu){
    		$this->setFormDefaultFromCookie($form);
    	}
    	 
    	return $form;
    }


    /**
     * Ако има бисквитка с последно запитване, взима контактите данни от нея
     */
    private function setFormDefaultFromCookie(&$form)
    {
        $contactFields = $this->selectFields("#class == 'contactData'");
        $fieldNamesArr = array_keys($contactFields);
        
        $vars = log_Browsers::getVars($fieldNamesArr);
        
    	foreach ((array)$vars as $name => $val){
    		$form->setDefault($name, $val);
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
    	
    	$rec->ip = core_Users::getRealIpAddr();
    	$rec->brid = log_Browsers::getBrid();
    	$rec->state = 'active';
    }
    
    
    /**
     * Връща данните за запитванията
     * 
     * @param integer $id    - id' то на записа
     * @param email   $email - Имейл
     *
     * @return NULL|object
     */
    static function getContragentData($id)
    {
        if (!$id) return ;
        
        $rec = self::fetch($id);
        
        $contrData = new stdClass();
        
        $contrData->person = $rec->name;
        $contrData->company = $rec->company;
        $contrData->tel = $rec->tel;
        $contrData->pCode = $rec->pCode;
        $contrData->place = $rec->place;
        $contrData->address = $rec->address;
        $contrData->email = $rec->email;
        $contrData->countryId = $rec->country;
        
        if ($contrData->countryId) {
            $contrData->country = self::getVerbal($rec, 'country');
        }
        
        return $contrData;
    }
}