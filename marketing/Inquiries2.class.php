<?php


/**
 * Документ "Запитване"
 *
 *
 * @category  bgerp
 * @package   marketing
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
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
    public $visibleForPartners = true;
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, marketing_InquiryEmbedderIntf,colab_CreateDocumentIntf';
    
    
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
    public $loadList = 'plg_RowTools2, marketing_Wrapper, plg_Sorting, plg_Clone, doc_DocumentPlg, acc_plg_DocumentSummary, plg_Search,
					doc_EmailCreatePlg, bgerp_plg_Blank, plg_Printing, cond_plg_DefaultValues, drdata_PhonePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title=Заглавие, personNames, company, email, folderId, sourceId=Източник, createdOn, createdBy';
    
    
    /**
     * Името на полето, което ще е на втори ред
     */
    public $listFieldsExtraLine = 'title';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '3.91|Търговия';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,marketing';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = true;
    
    
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
     * Кой може автоматично да създава продажба от запитването?
     */
    public $canAutocreatesale = 'ceo,sales,marketing';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'folderId, personNames, title, company, email, place';
    
    
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
    public $singleIcon = 'img/16/help_contents.png';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn';
    
    
    /**
     * Опашка за записи, на които трябва да се изпратят нотифициращи имейли
     */
    protected $sendNotificationEmailQueue = array();
    
    
    /**
     * Кои външни(external) роли могат да създават/редактират документа в споделена папка
     */
    public $canWriteExternal = 'agent';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'title';
    
    
    /**
     * Кой има право да препраща имейла
     */
    protected $canResendemail = 'ceo,marketing';
    
    
    /**
     * Кой може да филтрира по всички
     * 
     * @see acc_plg_DocumentSummary
     */
    public $filterRolesForAll = 'ceo,marketing';
    
    
    /**
     * Кой може да филтрира по екипи
     * 
     * @see acc_plg_DocumentSummary
     */
    public $filterRolesForTeam = 'ceo,marketing';
    
    
    /**
     * На участъци от по колко записа да се бекъпва?
     */
    public $backupMaxRows = 10000;
    
    
    /**
     * Кои полета да определят рзличността при backup
     */
    public $backupDiffFields = 'modifiedOn,state';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
        'tel' => 'clientData|lastDocUser',
        'company' => 'clientData|lastDocUser',
        'country' => 'clientData|lastDocUser|defMethod',
        'pCode' => 'clientData|lastDocUser',
        'place' => 'clientData|lastDocUser',
        'address' => 'clientData|lastDocUser',
    );
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('proto', 'key(mvc=cat_Products,allowEmpty,select=name)', 'caption=Шаблон,silent,input=hidden,refreshForm,placeholder=Популярни артикули,groupByDiv=»');
        $this->FLD('title', 'varchar', 'caption=Заглавие');
        $this->FLD('additionalData', 'blob(1000000, serialize, compress)', 'caption=Допълнително,input=none,column=none,single=none');
        
        $this->FLD('quantities', 'blob(serialize,compress)', 'input=none,column=none');
        $this->FLD('quantity1', 'double(decimals=2,Min=0)', 'caption=Количества->Количество|* 1,hint=Въведете количество,input=none,formOrder=47,silent');
        $this->FLD('quantity2', 'double(decimals=2,Min=0)', 'caption=Количества->Количество|* 2,hint=Въведете количество,input=none,formOrder=48');
        $this->FLD('quantity3', 'double(decimals=2,Min=0)', 'caption=Количества->Количество|* 3,hint=Въведете количество,input=none,formOrder=49');
        $this->FLD('company', 'varchar(128)', 'caption=Контактни данни->Фирма,class=contactData,hint=Вашата фирма,formOrder=50');
        $this->FLD('personNames', 'varchar(128)', 'caption=Контактни данни->Лице,class=contactData,hint=Вашето име||Your name,contragentDataField=person,formOrder=51,oldFieldName=name');
        $this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Контактни данни->Държава,class=contactData,hint=Вашата държава,formOrder=52,contragentDataField=countryId,mandatory');
        $this->FLD('email', 'email(valid=drdata_Emails->validate)', 'caption=Контактни данни->Имейл,class=contactData,hint=Вашият имейл||Your email,formOrder=53,mandatory');
        $this->FLD('tel', 'drdata_PhoneType(type=tel)', 'caption=Контактни данни->Телефони,class=contactData,hint=Вашият телефон,formOrder=54');
        $this->FLD('pCode', 'varchar(16)', 'caption=Контактни данни->П. код,class=contactData,hint=Вашият пощенски код,formOrder=55');
        $this->FLD('place', 'varchar(64)', 'caption=Контактни данни->Град,class=contactData,hint=Населено място: град или село и община,formOrder=56');
        $this->FLD('address', 'varchar(255)', 'caption=Контактни данни->Адрес,class=contactData,hint=Вашият адрес,formOrder=57');
        $this->FLD('inqDescription', 'richtext(rows=4,bucket=InquiryBucket)', 'caption=Вашето запитване||Your inquiry->Съобщение||Message,formOrder=50000');
        $this->FLD('deliveryAdress', 'varchar', 'caption=Вашето запитване||Your inquiry->Доставка||Delivery,formOrder=50004');
        
        $this->FLD('ip', 'varchar', 'caption=Ип,input=none');
        $this->FLD('browser', 'varchar(80)', 'caption=UA String,input=none');
        $this->FLD('brid', 'varchar(8)', 'caption=Браузър,input=none');
        $this->FLD('sourceClassId', 'class(interface=marketing_InquirySourceIntf)', 'caption=Източник клас,input=none');
        $this->FLD('sourceId', 'int', 'caption=Източник id,input=none,tdClass=leftCol');
        
        if (!acc_plg_DocumentSummary::$rolesAllMap[$this->className]) {
            acc_plg_DocumentSummary::$rolesAllMap[$this->className] = $this->filterRolesForAll;
        }
        
        $this->setDbIndex('proto');
        $this->setDbIndex('createdOn');
    }
    
    
    /**
     * Разширява формата за редакция
     *
     * @param stdClass $data
     *
     * @return void
     */
    private function expandEditForm(&$data)
    {
        $cu = core_Users::getCurrent('id', false);
        $hide = (isset($cu) && core_Users::haveRole('partner', $cu)) ? true : false;
        
        $form = &$data->form;
        $form->setField('innerClass', 'remember,removeAndRefreshForm=proto|measureId|meta');
        $form->setField('deliveryAdress', array('placeholder' => '|Държава|*, |Пощенски код|*'));
        if (!core_Users::isContractor($cu)) {
            $form->setField('deliveryAdress', 'input=none');
        }
        
        $Driver = $this->getDriver($form->rec);
        
        // Ако има избран прототип, зареждаме му данните в река
        if (isset($form->rec->proto)) {
            if ($pRec = cat_Products::fetch($form->rec->proto)) {
                
                if (is_array($pRec->driverRec)) {
                    foreach ($pRec->driverRec as $fld => $value) {
                        $form->rec->{$fld} = $value;
                    }
                }
            }
        }
        
        if ($Driver){
            $Driver->addInquiryFields($data->form->rec->proto, $data->form, true);
            if(is_array($form->rec->additionalData)){
                foreach ($form->rec->additionalData as $aFld => $aValue){
                    if($form->getField($aFld, false)){
                        $form->setDefault($aFld, $aValue);
                    }
                }
            }
        }
        
        $caption = 'Количества|*';
        if (isset($data->Driver) || isset($form->rec->innerClass)) {
            $uom = '';
            $uomId = $form->rec->measureId;
            if (isset($uomId) && ($uomId != cat_UoM::fetchBySysId('pcs')->id || $form->rec->quantityCount > 0)) {
                $uom = cat_UoM::getShortName($uomId);
            }
            
            if (isset($form->rec->moq)) {
                $moq = cls::get('type_Double', array('params' => array('smartRound' => 'smartRound')))->toVerbal($form->rec->moq);
                $caption .= '|* <small><i>( |Минимална поръчка|* ' . $moq . " {$uom} )</i></small>";
            }
        }
        
        // Добавяме полета за количество според параметрите на продукта
        $quantityCount = &$form->rec->quantityCount;
        
        if ($quantityCount > 3) {
            $quantityCount = 3;
        } elseif (isset($quantityCount) && $quantityCount == 0) {
            if ($form->rec->moq) {
                $form->setReadOnly('quantity1', $form->rec->moq);
                $form->setField('quantity1', "input,unit={$uom},caption={$caption}->Количество|* 1");
            } else {
                $form->setDefault('quantity1', 1);
                $form->setField('quantity1', 'input=hidden');
            }
        } elseif (!isset($quantityCount)) {
            $quantityCount = 3;
        }
        
        for ($i = 1; $i <= $quantityCount; $i++) {
            $fCaption = ($quantityCount == 1) ? 'Количество' : "Количество|* {$i}";
            $form->setField("quantity{$i}", "input,unit={$uom},caption={$caption}->{$fCaption}");
        }
        
        if (isset($cu) && !core_Users::isPowerUser()) {
            $personRec = crm_Profiles::getProfile($cu);
            
            $emails = type_Emails::toArray($personRec->buzEmail);
            $marketingEmail = countR($emails) ? $emails[0] : $personRec->email;
            $form->setDefault('personNames', $personRec->name);
            $form->setDefault('email', $marketingEmail);
            
            if ($companyFolderId = core_Mode::get('lastActiveContragentFolder')) {
                $form->setDefault('company', doc_Folders::getCover($companyFolderId)->fetchField('name'));
            } else {
                $hide = false;
            }
        }
        
        $contactFields = $this->selectFields("#class == 'contactData'");
        if (is_array($contactFields)) {
            foreach ($contactFields as $name => $value) {
                if ($hide === true) {
                    $form->setField($name, 'input=hidden');
                }
            }
        }
    }
    
   
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        
        if ($form->rec->innerClass) {
            $form->setFieldType('proto', "key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,driverId={$form->rec->innerClass},isPublic=yes,showTemplates,maxSuggestions=100,forceAjax)");
            $form->setField('proto', 'input');
        }
        
        if (cls::load($form->rec->innerClass, true)) {
            if ($Driver = cls::get($form->rec->innerClass)) {
                if ($moq = $Driver->getMoq()) {
                    $form->rec->moq = $moq;
                }
                
                if ($form->rec->quantityCount === null && ($inqQuantity = $Driver->getInquiryQuantities()) !== null) {
                    $form->rec->quantityCount = $inqQuantity;
                }
            }
        }
        
        $mvc->expandEditForm($data);
        
        if (haveRole('powerUser')) {
            $form->setField('personNames', 'mandatory=unsetValue');
            $form->setField('country', 'mandatory=unsetValue');
            $form->setField('email', 'mandatory=unsetValue');
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
        
        return cls::haveInterface('crm_ContragentAccRegIntf', $folderClass);
    }
    
    
    /**
     * Каква е дефолтната мярка
     */
    private function getDefaultMeasureId($rec)
    {
        if (isset($rec->measureId)) {
            
            return $rec->measureId;
        }
        
        if (isset($rec->id)) {
            $Driver = $this->getDriver($rec->id);
        } else {
            $Driver = cls::get($rec->{$this->driverClassField}, array('Embedder' => $this));
        }
        
        if (is_object($Driver)) {
            $measureId = $Driver->getDefaultUomId();
        }
        
        if (!$measureId) {
            $measureId = core_Packs::getConfigValue('cat', 'CAT_DEFAULT_MEASURE_ID');
        }
        
        if (!$measureId) {
            $measureId = cat_UoM::fetchBySinonim('pcs')->id;
        }
        
        return $measureId;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (empty($rec->createdBy)) {
            $row->createdBy = '@anonym';
        }
        
        if (!Mode::is('text', 'plain') && !Mode::is('text', 'xhtml')) {
            if ($rec->email) {
                $row->email = "<div class='email'>{$row->email}</div>";
            }
            $row->ip = type_Ip::decorateIp($rec->ip, $rec->createdOn);
        }
        
        $row->brid = log_Browsers::getLink($rec->brid);
        
        if ($fields['-list']) {
            $row->title = $mvc->getTitle($rec);
            
            $attr = array();
            $attr['class'] = 'linkWithIcon';
            $attr['style'] = 'background-image:url(' . sbf($mvc->singleIcon) . ');';
            $row->title = ht::createLink($row->title, array($mvc, 'single', $rec->id), null, $attr);
        }
        
        if(isset($rec->sourceClassId)){
            if(cls::load($rec->sourceClassId, true)){
                $Source = cls::get($rec->sourceClassId);
                $row->sourceId = ($Source instanceof core_Master) ? $Source->getHyperlink($rec->sourceId, true) : $Source->getTitleById($rec->sourceId);
            }
        }
        
        $measureId = $mvc->getDefaultMeasureId($rec);
        $shortName = cat_UoM::getShortName($measureId);
        
        $Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
        foreach (range(1, 3) as $i) {
            if (empty($rec->{"quantity{$i}"})) {
                if (isset($rec->quantities[$i - 1])) {
                    $rec->{"quantity{$i}"} = $rec->quantities[$i - 1];
                    $row->{"quantity{$i}"} = $Double->toVerbal($rec->{"quantity{$i}"});
                }
            }
        }
        
        $cntQuantities = 0;
        foreach (range(1, 3) as $i) {
            if ($rec->{"quantity{$i}"}) {
                $row->{"quantity{$i}"} .= " {$shortName}";
                $cntQuantities++;
            }
        }
        
        if ($cntQuantities > 1) {
            $row->q1Number = '1';
        }
        
        $row->time = core_DateTime::mysql2verbal($rec->createdOn);
        
        if (isset($rec->proto)) {
            $row->proto = cat_Products::getHyperlink($rec->proto);
            $protoRec = cat_Products::fetch($rec->proto, 'state');
            $row->protoCaption = ($protoRec->state != 'template') ? 'Запитване за' : 'Базирано на';
        }
        
        $row->innerClass = core_Classes::translateClassName($row->innerClass);
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        // Изпращане на нотифициращ имейл само ако създателя не е контрактор
        if ($rec->createdBy == core_Users::ANONYMOUS_USER || empty($rec->createdBy)) {
            $mvc->sendNotificationEmailQueue[$rec->id] = $rec;
        }
        
        // Ако запитването е в папка на контрагент вкарва се в група запитвания
        $Cover = doc_Folders::getCover($rec->folderId);
        if ($Cover->haveInterface('crm_ContragentAccRegIntf')) {
            $clientGroupId = crm_Groups::getIdFromSysId('customers');
            $groupRec = (object)array('name' => 'Запитвания', 'sysId' => 'inquiryClients', 'parentId' => $clientGroupId);
            $groupId = crm_Groups::forceGroup($groupRec);
            
            $Cover->forceGroup($groupId, false);
        }
    }
    
    
    /**
     * Изчиства записите, заопашени за запис
     *
     * @param acc_Items $mvc
     */
    public static function on_Shutdown($mvc)
    {
        if (is_array($mvc->sendNotificationEmailQueue)) {
            foreach ($mvc->sendNotificationEmailQueue as $rec) {
                try {
                    $mvc->isSended = $mvc->sendNotificationEmail($rec);
                    cat_Products::logDebug("Изпратен имейл за запитване създадено от '{$rec->createdBy}'", $rec->id);
                } catch (core_exception_Expect $e) {
                    self::logErr('Грешка при изпращане', $rec->id);
                    reportException($e);
                }
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
        if ($emailsTo && $sentFromBox) {
            
            // Имейла съответстващ на избраната кутия
            $sentFrom = email_Inboxes::fetchField($sentFromBox, 'email');
            $sentFromName = email_Inboxes::getFromName($sentFromBox);
            
            // Изпращане на имейл с phpmailer
            $PML = email_Accounts::getPML($sentFrom);
            
            /*
    		* Ако не е зададено е 8bit
    		* Проблема се появява при дълъг стринг - без интервали и на кирилица.
    		* Понеже е entity се режи грешно от phpmailer -> class.smtpl.php - $max_line_length = 998;
    		*
    		* @see #Sig281
    		*/
            $body = $this->getDocumentBody($rec->id, 'xhtml');
            $body = $body->getContent();
            
            // Създаваме HTML частта на документа и превръщаме всички стилове в inline
            // Вземаме всичките css стилове
            
            $css = getFileContent('css/common.css') .
            "\n" . getFileContent('css/Application.css');
            
            $res = '<div id="begin">' . $body . '<div id="end">';
            
            // Вземаме пакета
            $conf = core_Packs::getConfig('csstoinline');
            
            // Класа
            $CssToInline = $conf->CSSTOINLINE_CONVERTER_CLASS;
            
            // Инстанция на класа
            $inst = cls::get($CssToInline);
            
            // Стартираме процеса
            $body = $inst->convert($body, $css);
            $body = str::cut($res, '<div id="begin">', '<div id="end">');
            
            $PML->Body = $body;
            $PML->IsHTML(true);
            
            // Ембедване на изображенията
            email_Sent::embedSbfImg($PML);
            
            $altText = $this->getDocumentBody($rec->id, 'plain');
            $altText = $altText->getContent();
            
            Mode::push('text', 'plain');
            $altText = html2text_Converter::toRichText($altText);
            $altText = cls::get('type_Richtext')->toVerbal($altText);
            Mode::pop('text');
            
            $PML->AltBody = $altText;
            
            // Име на фирма/лице/име на продукта
            $subject = $this->getTitle($rec);
            $PML->Subject = str::utf2ascii($subject);
            
            // Адрес на който да се изпрати
            $PML->AddAddress($emailsTo);
            $PML->AddCustomHeader("Customer-Origin-Email: {$rec->email}");
            
            // От кой адрес е изпратен
            $PML->SetFrom($sentFrom, $sentFromName);
            
            if ($sendStatus = $PML->Send()) {
                // Задаваме екшъна за изпращането
                doclog_Documents::pushAction(
                    array(
                        'containerId' => $rec->containerId,
                        'threadId' => $rec->threadId,
                        'action' => doclog_Documents::ACTION_SEND,
                        'data' => (object) array(
                            'sendedBy' => core_Users::getCurrent(),
                            'from' => $sentFromBox,
                            'to' => $emailsTo
                        )
                    )
                );
                
                doclog_Documents::flushActions();
                marketing_Inquiries2::logWrite('АВТОМАТИЧНО изпращане на имейл', $rec->id);
            } else {
                marketing_Inquiries2::logErr('Грешка при изпращане', $rec->id);
            }
            
            // Изпращане
            return $sendStatus;
        }
        
        return true;
    }
    
    
    /**
     * Връща прикачените файлове
     */
    private function getAttachedFiles($rec, $Driver)
    {
        $res = array();
        
        $fieldset = $this->getForm();
        $Driver->addFields($fieldset);
        
        $arr = (array) $rec;
        foreach ($arr as $name => $value) {
            if ($fieldset->getFieldType($name, false) instanceof type_Richtext) {
                $files = fileman_RichTextPlg::getFiles($value);
                $res = array_merge($res, $files);
            }
        }
        
        return $res;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $self = cls::get(get_called_class());
        
        return $self->getTitle($rec);
    }
    
    
    /**
     * Връща името на запитването
     */
    private function getTitle($id)
    {
        $rec = $this->fetchRec($id);
        $name = $this->getFieldType('personNames')->toVerbal((($rec->company) ? $rec->company : $rec->personNames));
        $subject = "{$name} / {$rec->title}";
        
        $Varchar = cls::get('type_Varchar');
        
        return $Varchar->toVerbal($subject);
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingle($mvc, &$data)
    {
        if (haveRole('partner')) {
            unset($data->row->ip, $data->row->time, $data->row->brid);
        }
        
        // Вербализиране на допълнителните полета от драйвера
        if($Driver = $mvc->getDriver($data->rec)){
            if(is_array($data->rec->additionalData)){
                $inquiryFields = marketing_Inquiries2::getInquiryFields($data->rec->proto, $Driver);
                foreach ($data->rec->additionalData as $fld => $value){
                    if(array_key_exists($fld, $inquiryFields)){
                        $data->row->{$fld} = $inquiryFields[$fld]->type->toVerbal($value);
                    }
                }
            }
        }
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    protected static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
        if($Driver = $mvc->getDriver($data->rec)){
            $tpl->append($Driver->getInquiryDataTpl($data->rec), 'ADDITIONAL_BLOCK');
        }
    }
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = &$data->rec;
        if ($rec->state == 'active' && !core_Users::isContractor()) {
            if ($pId = cat_Products::fetchField("#originId = {$rec->containerId} AND #state = 'active'")) {
                $arrow = html_entity_decode('&#9660;', ENT_COMPAT | ENT_HTML401, 'UTF-8');
                $data->toolbar->addBtn("Артикул|* {$arrow}", array('cat_Products', 'single', $pId), 'ef_icon=img/16/wooden-box.png,title=Преглед на артикул по това запитване');
            } else {
                
                // Създаване на нов артикул от запитването
                if (cat_Products::haveRightFor('add', (object) array('folderId' => $rec->folderId, 'originId' => $rec->containerId, 'innerClass' => $rec->innerClass, 'threadId' => $rec->threadId))) {
                    $url = array('cat_Products', 'add', 'innerClass' => $rec->innerClass, 'originId' => $rec->containerId, 'ret_url' => true);
                    if (doc_Folders::getCover($rec->folderId)->haveInterface('crm_ContragentAccRegIntf')) {
                        $url['folderId'] = $rec->folderId;
                        $url['threadId'] = $rec->threadId;
                    }
                    
                    $data->toolbar->addBtn('Артикул', $url, 'ef_icon=img/16/wooden-box.png,title=Създаване на артикул по това запитване');
                }
            }
            
            // Ако може да се създава лица от запитването се слага бутон
            if ($mvc->haveRightFor('makeperson', $rec)) {
                $companyId = doc_Folders::fetchCoverId($rec->folderId);
                $data->toolbar->addBtn('Визитка на лице', array('crm_Persons', 'add', 'name' => $rec->personNames, 'buzCompanyId' => $companyId, 'country' => $rec->country), 'ef_icon=img/16/vcard.png,title=Създаване на визитка с адресните данни на подателя');
            }
            
            // Ако е настроено да се изпраща нотифициращ имейл, добавяме бутона за препращане
            if ($mvc->haveRightFor('resendemail', $rec)) {
                $conf = core_Packs::getConfig('marketing');
                $data->toolbar->addBtn('Препращане', array($mvc, 'send', $rec->id), array('ef_icon' => 'img/16/email_forward.png', 'warning' => "Сигурни ли сте, че искате да препратите имейла на|* '{$conf->MARKETING_INQUIRE_TO_EMAIL}'",'title' => "Препращане на имейла със запитването към|* '{$conf->MARKETING_INQUIRE_TO_EMAIL}'"));
            }
        }
        
        // Ако запитването е за стандартен артикул бутон за автоматично добавяне към продажба
        if($mvc->haveRightFor('autocreatesale', $rec)){
            $addSaleUrl = array('sales_Sales', 'autoCreateInFolder', 'folderId' => $rec->folderId, 'autoAction' => 'addProduct', 'productId' => $rec->proto);
            $data->toolbar->addBtn('Продажба', $addSaleUrl, 'ef_icon=img/16/cart_go.png,title=Създаване на артикула в нова продажба,warning=Наистина ли искате да добавите артикула в нова продажба|*?');
        }
    }
    
    
    /**
     * Препраща имейл-а генериран от създаването на запитването отново
     */
    public function act_Send()
    {
        $this->requireRightFor('resendemail');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('resendemail', $rec);
        
        $msg = '|Успешно препращане';
        try {
            $this->sendNotificationEmail($rec);
            $this->logWrite('Ръчно препращане на имейл', $rec->id);
        } catch (core_exception_Expect $e) {
            $this->logErr('Грешка при изпращане', $rec->id);
            reportException($e);
            $msg = '|Грешка при препращане';
        }
        
        return new Redirect(array($this, 'single', $rec->id), $msg);
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        $row->title = $this->getTitle($rec);
        $row->authorId = $rec->createdBy;
        $row->author = $rec->email;
        $row->authorEmail = $rec->email;
        $row->state = $rec->state;
        $row->recTitle = $row->title;
        
        return $row;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        // Кога може да се създава лице
        if ($action == 'makeperson' && isset($rec)) {
            
            // Ако корицата не е на фирма или състоянието не е активно никой не може
            $cover = doc_Folders::getCover($rec->folderId);
            if (!$cover->instance instanceof crm_Companies || $rec->state != 'active') {
                $res = 'no_one';
            }
        }
        
        if ($action == 'resendemail' && $res != 'no_one') {
            if (!trim(marketing_Setup::get('INQUIRE_TO_EMAIL')) || !marketing_Setup::get('INQUIRE_FROM_EMAIL')) {
                $res = 'no_one';
            } elseif (isset($rec->id) && !$mvc->haveRightFor('single', $rec->id)) {
                $res = 'no_one';
            }
        }
        
        // Запитването е за стандартен продаваем артикул да може да се добавя в продажба
        if($action == 'autocreatesale' && isset($rec)){
            if($rec->state != 'active' || empty($rec->proto) || empty($rec->folderId)){
                $res = 'no_one';
            } else {
                if(!sales_Sales::haveRightFor('add', (object)array('folderId' => $rec->folderId))){
                    $res = 'no_one';
                } else {
                    $protoRec = cat_Products::fetch($rec->proto, 'state,canSell');
                    if($protoRec->state != 'active' || $protoRec->canSell != 'yes'){
                        $res = 'no_one';
                    }
                }
            }
        }
    }
    
    
    /**
     * Връща тялото на имейла генериран от документа
     *
     * @see email_DocumentIntf
     *
     * @param int  $id      - ид на документа
     * @param bool $forward
     *
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = false)
    {
        $rec = $this->fetch($id);
        $date = dt::mysql2verbal($rec->createdOn, 'd-M');
        $time = dt::mysql2verbal($rec->createdOn, 'H:i');
        
        $tpl = new ET(tr("|Благодаря за Вашето запитване|*, |получено на|* {$date} |в|* {$time} |чрез нашия уеб сайт|*."));
        
        return $tpl->getContent();
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     *
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getCoversAndInterfacesForNewDoc()
    {
        return array('crm_ContragentAccRegIntf');
    }
    
    
    /**
     * Състояние на нишката
     */
    public static function getThreadState($id)
    {
        return 'opened';
    }
    
    
    /**
     * Екшън за добавяне на запитване от нерегистрирани потребители
     */
    public function act_New()
    {
        $cu = core_Users::getCurrent('id', false);
        Mode::set('showBulletin', false);
        Request::setProtected('classId, objectId');
        expect404($classId = Request::get('classId', 'int'));
        expect404($objectId = Request::get('objectId', 'int'));
        $Source = cls::getInterface('marketing_InquirySourceIntf', $classId);
        $sourceData = $Source->getInquiryData($objectId);
        
        $this->requireRightFor('new');
        expect404($drvId = $sourceData['drvId']);
        $proto = $sourceData['protos'];
        $proto = keylist::toArray($proto);
        $title = $sourceData['title'];
        
        // Поставя временно външният език, за език на интерфейса
        $lang = cms_Domains::getPublicDomain('lang');
        core_Lg::push($lang);
        
        if (countR($proto)) {
            foreach ($proto as $pId => &$name) {
                
                // Ако прототипа е оттеглен или затворен, маха се от списъка
                $pState = cat_Products::fetchField($pId, 'state');
                if ($pState != 'rejected' && $pState != 'closed') {
                    $name = cat_Products::getTitleById($pId, false);
                } else {
                    unset($proto[$pId]);
                }
            }
        }
        
        asort($proto);
        
        $form = $this->prepareForm($drvId);
        $form->setDefault('sourceClassId', $classId);
        $form->setDefault('sourceId', $objectId);
        
        // Рефрешване на формата ако потребителя се логне докато е в нея
        cms_Helper::setLoginInfoIfNeeded($form);
        
        $form->formAttr['id'] = 'newEnquiryForm';
        $form->FLD('measureId', 'key(mvc=cat_UoM,select=name)', 'input=hidden,silent');
        $form->FLD('moq', 'double', 'input=hidden,silent');
        $form->FLD('drvId', 'class', 'input=hidden,silent');
        $form->FLD('quantityCount', 'double', 'input=hidden,silent');
        $form->FLD('protos', 'varchar(10000)', 'input=hidden,silent');
        cms_Domains::addMandatoryText2Form($form);
        
        foreach (array('measureId', 'moq', 'drvId', 'quantityCount', 'protos') as $fld) {
            $form->setDefault($fld, $sourceData[$fld]);
        }
        
        if (empty($cu)) {
            $form->setDefault('title', $title);
        }
        
        $mandatoryField = marketing_Setup::get('MANDATORY_CONTACT_FIELDS');
        if (in_array($mandatoryField, array('company', 'both'))) {
            $form->setField('company', 'mandatory');
        }
        
        if (in_array($mandatoryField, array('person', 'both'))) {
            $form->setField('personNames', 'mandatory');
        }
        
        $form->input(null, 'silent');
        
        if (countR($proto)) {
            $form->setOptions('proto', $proto);
            if (countR($proto) === 1) {
                $form->setDefault('proto', key($proto));
                $form->setField('proto', 'input=hidden');
            } else {
                $form->setField('proto', 'input,caption=Шаблон,placeholder=Артикули||Products,groupByDiv=»');
            }
        } else {
            $form->setField('proto', 'input=none');
        }
        
        $data = (object) array('form' => $form);
        
        if (cls::load($form->rec->{$this->driverClassField}, true)) {
            $Driver = cls::get($form->rec->{$this->driverClassField}, array('Embedder' => $this));
            $data->Driver = $Driver;
            
            $Driver->addFields($data->form);
            $this->expandEditForm($data);
            
            if ($countryId = $this->getDefaultCountry($form->rec)) {
                $form->setDefault('country', $countryId);
            } else {
                $form->setField('country', 'input');
            }
            
            $Driver->invoke('AfterPrepareEditForm', array($this, &$data, &$data));
            
            $form->input();
            $this->invoke('AfterInputEditForm', array(&$form));
        }
        
        $titleVerbal = $form->getFieldType('title')->toVerbal($title);
        if(isset($sourceData['url'])){
            $titleVerbal = ht::createLink($titleVerbal, $sourceData['url']);
        }
        
        $form->title = "|Запитване за|* <b class='inquiryTitleLink'>{$titleVerbal}</b>";
        vislog_History::add('Форма за ' . $form->getFieldType('title')->toVerbal($sourceData['title']));
        
        if (isset($form->rec->title) && !isset($cu)) {
            $form->setField('title', 'input=hidden');
        }
        
        // След събмит на формата
        if ($form->isSubmitted()) {
            $rec = &$form->rec;
            
            // Ако има регистриран потребител с този имейл. Изисква се да се логне
            if ($error = cms_Helper::getErrorIfThereIsUserWithEmail($rec->email)) {
                $form->setError('email', $error);
            }
            
            if (!$form->gotErrors()) {
                $rec->state = 'active';
                $rec->ip = core_Users::getRealIpAddr();
                $rec->brid = log_Browsers::getBrid();
                
                // Винаги се рутира към правилната папка
                $domainId = cms_Domains::getPublicDomain()->id;
                $routerExplanation = null;
                $rec->folderId = marketing_InquiryRouter::route($rec->company, $rec->personNames, $rec->email, $rec->tel, $rec->country, $rec->pCode, $rec->place, $rec->address, $rec->brid, null, null, $routerExplanation, $domainId);
                
                // Запис и редирект
                if ($this->haveRightFor('new')) {
                    vislog_History::add('Ново маркетингово запитване');
                    
                    // Ако няма потребител
                    if (!$cu) {
                        $contactFields = $this->selectFields("#class == 'contactData'");
                        $fieldNamesArr = array_keys($contactFields);
                        $userData = array();
                        foreach ((array) $fieldNamesArr as $fName) {
                            if (!trim($form->rec->{$fName})) {
                                continue;
                            }
                            $userData[$fName] = $form->rec->{$fName};
                        }
                        log_Browsers::setVars($userData);
                    }
                    
                    $id = $this->save($rec);
                    doc_Threads::doUpdateThread($rec->threadId);
                    $this->logWrite('Създаване от е-артикул', $id);
                    if(!empty($routerExplanation)){
                        $this->logDebug($routerExplanation, $id, 7);
                    }
                    
                    $singleUrl = self::getSingleUrlArray($id);
                    if (countR($singleUrl)) {
                        
                        return redirect($singleUrl, false, 'Благодарим Ви за запитването|*!', 'success');
                    }
                    
                    return followRetUrl(null, 'Благодарим Ви за запитването|*!', 'success');
                }
            }
        }
        
        $form->toolbar->addSbBtn('Изпрати', 'save', 'id=save, ef_icon = img/16/disk.png,title=Изпращане на запитването');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'id=cancel, ef_icon = img/16/close-red.png,title=Отказ');
        $tpl = $form->renderHtml();
        core_Form::preventDoubleSubmission($tpl, $form);
        
        // Рефрешване на формата ако потребителя се логне докато е в нея
        cms_Helper::setRefreshFormIfNeeded($tpl);
        
        // Поставяме шаблона за външен изглед
        Mode::set('wrapper', 'cms_page_External');
        $tpl->prepend("\n<meta name=\"robots\" content=\"nofollow\">", 'HEAD');
        
        // Премахва зададения временно текущ език
        core_Lg::pop();
        
        return $tpl;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        
        if(isset($rec->proto)){
            $protoRec = cat_Products::fetch($rec->proto);
            $Driver = cat_Products::getDriver($protoRec);
            
            // Скриване на полетата от драйвера, ако прототипа не е шаблон
            if($protoRec->state != 'template'){
                if(isset($Driver)){
                    $DriverFields = array_keys($mvc->getDriverFields($Driver));
                    foreach ($DriverFields as $fld) {
                        if($form->getField($fld, false)){
                            $form->setField($fld, 'input=hidden');
                        }
                    }
                }
            }
        }
        
        if ($form->isSubmitted()) {
            
            $moqVerbal = cls::get('type_Double', array('params' => array('smartRound' => true)))->toVerbal($rec->moq);
            
            // Ако няма въведени количества
            if (empty($rec->quantity1) && empty($rec->quantity2) && empty($rec->quantity3)) {
                
                // Ако има МОК, потребителя трябва да въведе количество, иначе се приема за еденица
                if ($rec->moq > 0) {
                    $form->setError('quantity1,quantity2,quantity3', "Очаква се поне едно от количествата да е над||It is expected that at least one quantity is over|* <b>{$moqVerbal}</b>");
                } else {
                    $rec->quantity1 = 1;
                }
            }
            
            // Ако има минимално количество за поръчка
            $errorMoqs = $errorQuantities = $errorQuantitiesDecimals = $allQuantities = array();
            $roundError = null;
            
            // Проверка на въведените количества
            foreach (range(1, 3) as $i) {
                $quantity = $rec->{"quantity{$i}"};
                if (empty($quantity)) {
                    continue;
                }
                
                if ($rec->moq > 0 && $quantity < $rec->moq) {
                    $errorMoqs[] = "quantity{$i}";
                }
                
                if (in_array($quantity, $allQuantities)) {
                    $errorQuantities[] = "quantity{$i}";
                } else {
                    $allQuantities[] = $quantity;
                }
                
                $measureId = $mvc->getDefaultMeasureId($rec);
                if (!deals_Helper::checkQuantity($measureId, $quantity, $roundError)) {
                    $errorQuantitiesDecimals[] = "quantity{$i}";
                }
            }
            
            if (countR($errorMoqs)) {
                $form->setError(implode(',', $errorMoqs), "Количеството не трябва да е под||Quantity can't be bellow|* <b>{$moqVerbal}</b>");
            }
            
            if (countR($errorQuantities)) {
                $form->setError(implode(',', $errorQuantities), 'Количествата трябва да са различни||Quantities must be different|*');
            }
            
            if (countR($errorQuantitiesDecimals)) {
                $form->setError(implode(',', $errorQuantitiesDecimals), $roundError);
            }
            
            if (!empty($rec->deliveryAdress)) {
                $address = drdata_Address::parsePlace($rec->deliveryAdress);
                
                // Опит за разпознаване на адреса и дали се поддържа доставка до там
                if (!$address) {
                    $form->setError('deliveryAdress', 'Адресът трябва да съдържа държава и пощенски код');
                } elseif (isset($address->countryId)) {
                    if (empty($rec->country)) {
                        $countryId = $rec->country;
                    } elseif (isset($rec->folderId)) {
                        $Cover = doc_Folders::getCover($rec->folderId);
                        $Cover->haveInterface('doc_ContragentDataIntf');
                        $countryId = $Cover->getContragentData()->countryId;
                    }
                    
                    // Само ако държавата в запитването е различна от тази на адреса
                    if ($countryId != $address->countryId) {
                        $countryDeliveryTermId = cond_Countries::getParameterByCountryId($address->countryId, 'deliveryTermSale');
                        if (empty($countryDeliveryTermId)) {
                            $form->setError('deliveryAdress', 'Не се извършва доставка до посочената локация');
                        } else {
                            $TransportCalculator = cond_DeliveryTerms::getTransportCalculator($countryDeliveryTermId);
                            
                            $params = array('deliveryCountry' => $address->countryId, 'deliveryPCode' => $address->pCode);
                            $totalFee = $TransportCalculator->getTransportFee($countryDeliveryTermId, 1, 1000, $params);
                            if ($totalFee['fee'] < 0) {
                                $form->setError('deliveryAdress', 'Не се извършва доставка до посочената локация');
                            }
                        }
                    }
                }
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
        $cu = core_Users::getCurrent('id', false);
        
        // Ако има логнат потребител
        if ($cu && !haveRole('powerUser')) {
            $personId = crm_Profiles::fetchField("#userId = {$cu}", 'personId');
            $personRec = crm_Persons::fetch($personId);
            $inCharge = marketing_Router::getInChargeUser($form->rec->place, $form->rec->country, cms_Domains::getPublicDomain()->id);
            
            // Ако лицето е обвързано с фирма, документа отива в нейната папка
            if ($personCompanyId = $personRec->buzCompanyId) {
                $form->rec->folderId = crm_Companies::forceCoverAndFolder((object) array('id' => $personCompanyId, 'inCharge' => $inCharge));
            } else {
                try {
                    expect($personRec || $personId, "Няма визитка на контрактор {$personId}");
                } catch (core_exception_Expect $e) {
                    crm_Persons::logErr('Няма визитка на контрактор', $personId);
                }
                
                // иначе отива в личната папка на лицето
                $form->rec->folderId = crm_Persons::forceCoverAndFolder((object) array('id' => $personId, 'inCharge' => $inCharge));
            }
            
            $form->title .= ' |в|*' . doc_Folders::recToVerbal(doc_Folders::fetch($form->rec->folderId))->title;
            
            // Слагаме името на лицето, ако не е извлечено
            $form->setDefault('personNames', $personRec->name);
        }
        
        // Ако няма потребител, но има бисквитка зареждаме данни от нея
        if (!$cu) {
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
        
        foreach ((array) $vars as $name => $val) {
            $form->setDefault($name, $val);
        }
    }
    
    
    /**
     * Връща дефолт държавата на заданието
     */
    public static function getDefaultCountry($rec)
    {
        if ($cu = core_Users::getCurrent('id', false)) {
            $profileRec = crm_Profiles::getProfile($cu);
            if (isset($profileRec->country)) {
                
                return $profileRec->country;
            }
        }
        
        if (cms_Content::getLang() == 'bg') {
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
        $fieldsBefore = arr::make(array_keys($form->selectFields()), true);
        $Driver->addEmbeddedFields($form);
        $fieldsAfter = arr::make(array_keys($form->selectFields()), true);
        
        $params = array_diff_assoc($fieldsAfter, $fieldsBefore);
        
        return $params;
    }
    
    
    /**
     * Изпълнява се преди запис
     */
    protected static function on_BeforeSave($mvc, &$id, $rec, $fields = null, $mode = null)
    {
        // Допълваме данните само при създаване
        if ($rec->id) {
            
            return;
        }
        
        // Ако има оригинална дата на създаване, подменяме нея с текущата
        if (isset($rec->oldCreatedOn)) {
            $rec->createdOn = $rec->oldCreatedOn;
        }
        
        $rec->ip = core_Users::getRealIpAddr();
        $rec->brid = log_Browsers::getBrid();
        
        if ($rec->state != 'rejected') {
            $rec->state = 'active';
            if(empty($rec->activatedOn)){
                $rec->activatedOn = dt::now();
                $rec->activatedBy = core_Users::getCurrent();
            }
        }
        
        $Driver = cls::get($rec->innerClass);
        if (!strlen($rec->title)) {
            if ($Driver) {
                if ($title = $Driver->getProductTitle($rec)) {
                    $rec->title = $title;
                }
            }
        }
        
        // Добавяне на полетата от запитването в блоб
        $inquiryDriverFields = array_keys($mvc->getInquiryFields($rec->proto, $Driver));
        if(is_array($inquiryDriverFields)){
            $additionalData = array();
            foreach ($inquiryDriverFields as $name) {
                $additionalData[$name] = $rec->{$name};
                unset($rec->{$name});
            }
            
            $rec->additionalData = $additionalData;
        }
    }
    
    
    /**
     * Връща данните за запитванията
     *
     * @param int   $id    - id' то на записа
     * @param string $email - Имейл
     *
     * @return NULL|object
     */
    public static function getContragentData($id)
    {
        if (!$id) {
            
            return ;
        }
        
        $rec = self::fetch($id);
        
        $contrData = new stdClass();
        
        $contrData->person = $rec->personNames;
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
    
    
    /**
     * Връща полетата добавени от драйвера
     *
     * @param core_BaseClass $driver           - драйвер
     * @param bool           $onlySingleFields - дали да са само полетата за сингъл
     * @param bool           $returnAsFieldSet - дали да се върнат като фийлд сетове
     *
     * @return array $res - добавените полета от драйвера
     */
    public static function getInquiryFields($protoId, $driver, $onlySingleFields = false)
    {
        $fieldset = cls::get('core_Fieldset');
        $driver->addInquiryFields($protoId, $fieldset);
        
        $res = array();
        if (is_array($fieldset->fields)) {
            foreach ($fieldset->fields as $name => $f) {
                if ($onlySingleFields === true && $f->single == 'none') {
                    continue;
                }
                
                $res[$name] = $f;
            }
        }
        
        return $res;
    }
}
