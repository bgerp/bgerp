<?php


/**
 * Мениджър на физическите лица
 *
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.12
 * @title     Физически лица
 *
 * @method restrictAccess(core_Query $query, NULL|integer $userId = NULL, boolean $viewAccess = TRUE)
 */
class crm_Persons extends core_Master
{
    /**
     * Да се създаде папка при създаване на нов запис
     */
    public $autoCreateFolder = 'instant';
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = array(
        
        // Интерфейс на всички счетоводни пера, които представляват контрагенти
        'crm_ContragentAccRegIntf',
        
        // Интерфейс за счетоводни пера, отговарящи на физически лица
        'crm_PersonAccRegIntf',
        
        // Интерфейс за всякакви счетоводни пера
        'acc_RegisterIntf',
        
        // Интерфейс за корица на папка
        'doc_FolderIntf',
        
        //Интерфейс за данните на контрагента
        'doc_ContragentDataIntf',
        
        // Интерфейс за входящ документ
        'fileman_FileActionsIntf',
        
        // Интерфейс за корица на папка в която може да се създава артикул
        'cat_ProductFolderCoverIntf',
    );
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Лица';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Лице';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'callcenter=0.1';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/vcard.png';
    
    
    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    public $fetchFieldsBeforeDelete = 'id,name';
    
    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    public $loadList = 'plg_Created, plg_Modified, plg_RowTools2,  plg_LastUsedKeys,plg_Rejected, plg_Select,
                     crm_Wrapper, crm_AlphabetWrapper, plg_SaveAndNew, plg_PrevAndNext, bgerp_plg_Groups, plg_Printing, plg_State,
                     plg_Sorting, recently_Plugin, plg_Search, acc_plg_Registry, doc_FolderPlg,
                     bgerp_plg_Import, doc_plg_Close, drdata_PhonePlg,bgerp_plg_Export,plg_ExpandInput, core_UserTranslatePlg,
                     callcenter_AdditionalNumbersPlg, crm_ContragentGroupsPlg';
    
    
    /**
     * Полета, които се показват в листови изглед
     */
    public $listFields = 'nameList=Име,phonesBox=Комуникации,addressBox=Адрес,name=';
    
    
    /**
     * Полета за експорт
     */
    public $exportableCsvFields = 'salutation,name,nameList,egn,vatId,eori,birthday,country,pCode,place,address,buzCompanyId,buzLocationId,buzPosition,buzEmail,buzTel,buzFax,buzAddress,email,tel,mobile,fax,website,info,photo,groupList';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'groupList';
    
    
    /**
     * Полета по които се правитърсене от плъгина plg_Search
     */
    public $searchFields = 'salutation, name, egn, birthday, country, pCode, place, address, email, tel, mobile, fax, website, info, buzCompanyId, buzLocationId, buzPosition, buzEmail, buzTel, buzFax, buzAddress, id, eori, vatId';
    
    
    /**
     * Кой  може да пише?
     */
    public $canWrite = 'powerUser';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'powerUser';
    
    
    /**
     * Кой може да добавя?
     */
    public $canClose = 'crm,ceo';
    
    
    /**
     * По кои сметки ще се правят справки
     */
    public $balanceRefAccounts = '1511,1512,1513,1514,1521,1522,1523,1524,153,159,323,401,402,403,404,405,406,409,411,412,413,414,415,419,422';
    
    
    /**
     * По кой итнерфейс ще се групират сметките
     */
    public $balanceRefGroupBy = 'crm_ContragentAccRegIntf';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    public $canReports = 'ceo,sales,purchase,acc';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    public $canAddacclimits = 'ceo,salesMaster,purchaseMaster,accMaster,accLimits';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'powerUser';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'powerUser';
    
    
    /**
     * Кой  може да групира "С избраните"?
     */
    public $canGrouping = 'powerUser';
    
    
    /**
     * Кой може да оттегля
     */
    public $canReject = 'powerUser';
    
    
    /**
     * Кой може да го възстанови?
     */
    public $canRestore = 'powerUser';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'admin, ceo';
    
    
    /**
     * Кой има право да оттегля системните данни?
     */
    public $canRejectsysdata = 'admin, ceo';


    /**
     * @see plg_ExpandInput
     */
    public $fixExpandFieldOnSetup = false;

    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'crm/tpl/SinglePersonLayout.shtml';
    
    
    public $doWithSelected = 'export=Експортиране';
    
    
    /**
     * Детайли на този мастър обект
     *
     * @var string|array
     */
    public $details = 'AccReports=acc_ReportDetails,ContragentLocations=crm_Locations,
                    ContragentBankAccounts=bank_Accounts,PersonsDetails=crm_PersonsDetails,CommerceDetails=crm_CommerceDetails,ContragentUnsortedFolders=doc_UnsortedFolders';
    
    
    /**
     * Поле, в което да се постави връзка към папката в листови изглед
     */
    public $listFieldForFolderLink = 'folder';
    
    
    /**
     * Полето, което ще се разширява
     *
     * @see plg_ExpandInput
     */
    public $expandFieldName = 'groupList';
    
    
    /**
     * Кои полета да се записват в номерата
     * @var array
     * @see callcenter_AdditionalNumbersPlg
     */
    public $updateNumMap = array('tel' => 'tel', 'buzTel' => 'tel', 'fax' => 'fax', 'buzFax' => 'fax', 'mobile' => 'mobile');
    
    
    /**
     * Предефинирани подредби на листовия изглед
     */
    public $listOrderBy = array(
        'alphabetic' => array('Азбучно', '#name=ASC'),
        'last' => array('Последно добавени', '#createdOn=DESC', 'createdOn=Създаване->На,createdBy=Създаване->От'),
        'modified' => array('Последно променени', '#modifiedOn=DESC', 'modifiedOn=Модифициране->На,modifiedBy=Модифициране->От'),
        'birthday' => array('Рожден ден', '#birthday=DESC'),
        'website' => array('Сайт/Блог', '#website', 'website=Сайт/Блог'),
    );
    
    
    /**
     *
     * @see type_Key::filterByGroup
     */
    public $groupsField = 'groupList';
    
    
    /**
     * Как се казва полето за държава на контрагента
     */
    public $countryFieldName = 'country';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        // Име на лицето
        $this->FLD('salutation', 'enum(,mr=Г-н,mrs=Г-жа,miss=Г-ца)', 'caption=Обръщение,export=Csv');
        $this->FLD('name', 'varchar(255,ci)', 'caption=Имена,class=contactData,mandatory,remember=info,silent,export=Csv, translate=transliterate');
        $this->FNC('nameList', 'varchar', 'sortingLike=name, translate=transliterate');
        
        // Единен Граждански Номер
        $this->FLD('egn', 'bglocal_EgnType', 'caption=ЕГН,export=Csv,silent');
        $this->FLD('vatId', 'drdata_VatType', 'caption=ДДС (VAT) №,remember=info,class=contactData,export=Csv');
        $this->FLD('eori', 'drdata_type_Eori', 'caption=EORI №,remember=info,class=contactData,export=Csv,silent');

        // Дата на раждане
        $this->FLD('birthday', 'combodate(minYear=1850,maxYear=' . date('Y') . ')', 'caption=Рожден ден,export=Csv');
        
        // Адресни данни
        $this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Държава,remember,class=contactData,mandatory,silent,export=Csv');
        $this->FLD('pCode', 'varchar(16)', 'caption=П. код,recently,class=pCode,export=Csv');
        $this->FLD('place', 'varchar(64)', 'caption=Град,class=contactData,hint=Населено място: град или село и община,export=Csv');
        $this->FLD('address', 'varchar(255)', 'caption=Адрес,class=contactData,export=Csv');
        
        // Служебни комуникации
        $this->FLD(
            'buzCompanyId',
            'key2(mvc=crm_Companies,where=#state !\\= \\\'rejected\\\', allowEmpty)',
            'caption=Служебни комуникации->Фирма,class=contactData,silent,export=Csv,remember,removeAndRefreshForm=buzLocationId'
        );
        $this->FLD('buzLocationId', 'key(mvc=crm_Locations,select=title,allowEmpty)', 'caption=Служебни комуникации->Локация,class=contactData,export=Csv,input=hidden');
        $this->FLD('buzPosition', 'varchar(64)', 'caption=Служебни комуникации->Длъжност,class=contactData,export=Csv');
        $this->FLD('buzEmail', 'emails', 'caption=Служебни комуникации->Имейли,class=contactData,export=Csv');
        $this->FLD('buzTel', 'drdata_PhoneType(type=tel,unrecognized=warning)', 'caption=Служебни комуникации->Телефони,class=contactData,export=Csv');
        $this->FLD('buzFax', 'drdata_PhoneType(type=fax)', 'caption=Служебни комуникации->Факс,class=contactData,export=Csv');
        $this->FLD('buzAddress', 'varchar(255)', 'caption=Служебни комуникации->Адрес,class=contactData,export=Csv');
        
        // Лични комуникации
        $this->FLD('email', 'emails', 'caption=Лични комуникации->Имейли,class=contactData,export=Csv');
        $this->FLD('tel', 'drdata_PhoneType(type=tel,unrecognized=warning)', 'caption=Лични комуникации->Телефони,class=contactData,silent,export=Csv');
        $this->FLD('mobile', 'drdata_PhoneType(type=tel)', 'caption=Лични комуникации->Мобилен,class=contactData,silent,export=Csv');
        $this->FLD('fax', 'drdata_PhoneType(type=fax)', 'caption=Лични комуникации->Факс,class=contactData,silent,export=Csv');
        $this->FLD('website', 'url', 'caption=Лични комуникации->Сайт/Блог,class=contactData,export=Csv');
        
        // Допълнителна информация
        $this->FLD('info', 'richtext(bucket=crmFiles, passage)', 'caption=Информация->Бележки,height=150px,class=contactData,export=Csv');
        $this->FLD('photo', 'fileman_FileType(bucket=pictures)', 'caption=Информация->Фото,export=Csv');
        
        // В кои групи е?
        $this->FLD('groupList', 'keylist(mvc=crm_Groups,select=name,makeLinks,where=#allow !\\= \\\'companies\\\' AND #state !\\= \\\'rejected\\\',classLink=group-link)', 'caption=Групи->Групи,remember,silent,export=Csv');
        
        // Състояние
        $this->FLD('state', 'enum(active=Вътрешно,closed=Нормално,rejected=Оттеглено)', 'caption=Състояние,value=closed,notNull,input=none');
        
        // Индекси
        $this->setDbIndex('name');
        $this->setDbIndex('country');
        $this->setDbIndex('email');
        $this->setDbIndex('buzCompanyId');
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param crm_Persons $mvc
     * @param stdClass    $data
     */
    public static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('users', 'users(rolesForAll = officer|manager|ceo, rolesForTeams = officer|manager|ceo|executive)', 'caption=Потребител,input,silent,autoFilter');
        
        // Вземаме стойността по подразбиране, която може да се покаже
        $default = $data->listFilter->getField('users')->type->fitInDomain('all_users');
        
        // Задаваме стойността по подразбиране
        $data->listFilter->setDefault('users', $default);
        
        $options = array();
        
        // Подготовка на полето за подредба
        foreach ($mvc->listOrderBy as $key => $attr) {
            $options[$key] = $attr[0];
        }
        $orderType = cls::get('type_Enum');
        $orderType->options = $options;
        
        $data->listFilter->FNC('order', $orderType, 'caption=Подредба,input,silent,autoFilter');
        
        $data->listFilter->FNC('groupId', 'key(mvc=crm_Groups,select=name,allowEmpty)', 'placeholder=Всички групи,caption=Група,input,silent,autoFilter');
        $data->listFilter->FNC('alpha', 'varchar', 'caption=Буква,input=hidden,silent,autoFilter');
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета
        // на модела ще се появят
        $data->listFilter->showFields = 'search,users,order,groupId';
        
        $data->listFilter->input('users,alpha,search,order,groupId', 'silent');
        
        // Според заявката за сортиране, показваме различни полета
        $showColumns = $mvc->listOrderBy[$data->listFilter->rec->order][2];
        
        if ($showColumns) {
            $showColumns = arr::make($showColumns, true);
            foreach ($showColumns as $field => $title) {
                $data->listFields[$field] = $title;
            }
        }
        
        // Подредба
        setIfNot($data->listFilter->rec->order, 'alphabetic');
        $orderCond = $mvc->listOrderBy[$data->listFilter->rec->order][1];
        if ($orderCond) {
            $data->query->orderBy($orderCond);
        }
        if ($data->listFilter->rec->order == 'birthday') {
            $mvc->birthdayFilter = true;
        }
        if ($data->listFilter->rec->alpha) {
            if ($data->listFilter->rec->alpha[0] == '0') {
                $cond = "LTRIM(REPLACE(REPLACE(REPLACE(LOWER(#name), '\"', ''), '\'', ''), '`', '')) NOT REGEXP '^[a-zA-ZА-Яа-я]'";
            } else {
                $alphaArr = explode('-', $data->listFilter->rec->alpha);
                $cond = array();
                $i = 1;
                
                foreach ($alphaArr as $a) {
                    $cond[0] .= ($cond[0] ? ' OR ' : '') .
                    "( LTRIM(REPLACE(REPLACE(REPLACE(LOWER(#name), '\"', ''), '\'', ''), '`', '')) LIKE LOWER('[#{$i}#]%'))";
                    $cond[$i] = $a;
                    $i++;
                }
            }
            
            $data->query->where($cond);
        }
        
        if ($names = Request::get('names')) {
            $namesArr = explode(',', $names);
            $first = true;
            
            foreach ($namesArr as $name) {
                $name = trim($name);
                
                if ($first) {
                    $data->query->where(array("#searchKeywords LIKE ' [#1#] %'", $name));
                } else {
                    $data->query->orWhere(array("#searchKeywords LIKE ' [#1#] %'", $name));
                }
                $first = false;
            }
            
            $date = Request::get('date', 'date');
            
            if ($date) {
                $data->title = 'Именици на <span class="green">' . dt::mysql2verbal($date, 'd.m.Y, l') . '</span>';
            } else {
                $data->title = 'Именици';
            }
        }
        
        // Филтриране по потребител/и
        if (!$data->listFilter->rec->users) {
            $data->listFilter->rec->users = '|' . core_Users::getCurrent() . '|';
        }
        
        if (($data->listFilter->rec->users != 'all_users') && (strpos($data->listFilter->rec->users, '|-1|') === false)) {
            $data->query->where("'{$data->listFilter->rec->users}' LIKE CONCAT('%|', #inCharge, '|%')");
            $data->query->orLikeKeylist('shared', $data->listFilter->rec->users);
        }
        
        if (!empty($data->listFilter->rec->groupId)) {
            $data->query->where("LOCATE('|{$data->listFilter->rec->groupId}|', #groupList)");
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на редовете за листовия изглед
     */
    public static function on_AfterPrepareListRows($mvc, &$res, $data)
    {
        if (is_array($data->recs)) {
            $cnt = array();
            foreach ($data->recs as $rec) {
                $cnt[str::utf2ascii(trim($rec->name))]++;
            }
            foreach ($data->recs as $rec) {
                if ($cnt[str::utf2ascii(trim($rec->name))] >= 2) {
                    $data->rows[$rec->id]->nameList .= $data->rows[$rec->id]->titleNumber;
                }
            }
        }
    }
    
    
    /**
     * Премахване на бутон и добавяне на нови два в таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if ($data->toolbar->removeBtn('btnAdd')) {
            self::addNewPersonBtn2Toolbar($data->toolbar, $data->listFilter);
        }
    }
    
    
    /**
     * Добавя бутон за създаване на ново лице към тулбар, взимайки под внимание филтър
     *
     * @param core_Toolbar $toolbar
     * @param core_Form $listFilter
     *
     * @return void
     */
    public static function addNewPersonBtn2Toolbar(core_Toolbar &$toolbar,core_Form $listFilter)
    {
        $addPersonUrl = array('crm_Persons', 'add', 'ret_url' => true);
        if($groupId = $listFilter->rec->groupId){
            $addPersonUrl["groupList"] = $groupId;
        }
        $searchString = $listFilter->rec->search;
        
        // Ако има въведен стринг за търсене
        if(!empty($searchString)){
            
            // и е валидно ЕГН
            $egnCheck = cls::get('bglocal_EgnType')->isValid($searchString);
            if(empty($egnCheck['error'])){
                $addPersonUrl['egn'] = $searchString;
            } else {
                $addPersonUrl['name'] = $searchString;
            }
        }
        
        $toolbar->addBtn('Ново лице', $addPersonUrl, 'ef_icon=img/16/vcard-add.png', 'title=Създаване на нова визитка на лице');
    }
    
    
    /**
     * Манипулации със заглавието
     *
     * @param core_Mvc $mvc
     * @param core_Et  $tpl
     * @param stdClass $data
     */
    public static function on_AfterPrepareListTitle($mvc, &$tpl, $data)
    {
        if ($data->listFilter->rec->groupId) {
            $data->title = "Лица в групата|* \"<b style='color:green'>|" .
            crm_Groups::getTitleById($data->listFilter->rec->groupId) . '|*</b>"';
        } elseif ($data->listFilter->rec->search) {
            $data->title = "Лица отговарящи на филтъра|* \"<b style='color:green'>" .
            type_Varchar::escape($data->listFilter->rec->search) .
            '</b>"';
        } elseif ($data->listFilter->rec->alpha) {
            if ($data->listFilter->rec->alpha[0] == '0') {
                $data->title = 'Лица, които започват с не-буквени символи';
            } else {
                $data->title = "Лица започващи с буквите|* \"<b style='color:green'>{$data->listFilter->rec->alpha}</b>\"";
            }
        } else {
            $data->title = '';
        }
    }
    
    
    /**
     * Изпълнява се след въвеждането на данните от заявката във формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        
        // Подготвяме рожденния ден
        static::prepareBirthday($rec);
        
        if ($form->isSubmitted()) {
            
            // Проверяваме да няма дублиране на записи
            $resStr = static::getSimilarWarningStr($form->rec, $fields);
            
            if ($resStr) {
                $form->setWarning($fields, $resStr);
            }
            
            if ($rec->place) {
                $rec->place = bglocal_Address::canonizePlace($rec->place);
            }

            if(isset($rec->id)){

                // Ако е сменена фирмата, но има останали стари контактни данни
                $exRec = $mvc->fetch($rec->id, '*', false);

                if(!empty($exRec->buzCompanyId) && $exRec->buzCompanyId != $rec->buzCompanyId){
                    $warningFields = array();
                    foreach (array('buzLocationId', 'buzPosition', 'buzEmail', 'buzTel', 'buzFax', 'buzAddress') as $buzFld){
                        if($rec->{$buzFld} = $exRec->{$buzFld}){
                            $warningFields[] = $buzFld;
                        }
                    }

                    if(countR($warningFields)){
                        $form->setWarning($warningFields, 'При промяна на фирмата, моля проверете контактните данни');
                    }
                }
            }
        }
    }
    
    
    /**
     * Промяна на данните от таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = null)
    {
        if ($fields['-single']) {
            
            // Fancy ефект за картинката
            $Fancybox = cls::get('fancybox_Fancybox');
            
            $tArr = array(200, 150);
            $mArr = array(600, 450);
            
            if ($rec->photo) {
                $row->image = $Fancybox->getImage($rec->photo, $tArr, $mArr);
            } else {
                
                // Ако има профил
                if (($profileRec = crm_Profiles::fetch("#personId = '{$rec->id}'")) && $profileRec->userId) {
                    
                    // Вземаме записа
                    $userRec = core_Users::fetch($profileRec->userId);
                    
                    // Ако има зададен аватар
                    if ($userRec->avatar) {
                        
                        // Използваме аватара
                        $row->image = core_Users::getVerbal($userRec, 'avatar');
                        
                        // Флаг
                        $haveAvatar = true;
                    }
                }
                
                // Ако няма открит аватар
                if (!$haveAvatar) {
                    if ($rec->email) {
                        $emlArr = type_Emails::toArray($rec->email);
                        $imgUrl = avatar_Gravatar::getUrl($emlArr[0], 120);
                    } elseif ($rec->buzEmail) {
                        $emlArr = type_Emails::toArray($rec->buzEmail);
                        $imgUrl = avatar_Gravatar::getUrl($emlArr[0], 120);
                    } elseif (!Mode::is('screenMode', 'narrow')) {
                        $imgUrl = sbf('img/noimage120.gif');
                    }
                    
                    if ($imgUrl) {
                        $row->image = '<img class="hgsImage" src=' . $imgUrl . " alt='no image'>";
                    }
                }
            }
            
            if ($rec->buzLocationId) {
                $row->buzLocationId = crm_Locations::getHyperLink($rec->buzLocationId, true);
            }
            
            // Разширяване на $row
            crm_ext_ContragentInfo::extendRow($mvc, $row, $rec);
        }
        
        static $ownCompany;
        if (!$ownCompany) {
            $ownCompany = crm_Companies::fetchOurCompany();
        }
        if ($ownCompany->country != $rec->country) {
            $row->country = $mvc->getVerbal($rec, 'country');
        }
        
        $pCode = $mvc->getVerbal($rec, 'pCode');
        $place = $mvc->getVerbal($rec, 'place');
        $address = $mvc->getVerbal($rec, 'address');
        
        
        if ($fields['-list']) {
            
            // Дали има права single' а на този потребител
            $canSingle = static::haveRightFor('single', $rec);
            
            $row->nameList = $mvc->getLinkToSingle($rec->id, 'name');
            
            if ($row->country) {
                $row->addressBox = $row->country;
                $row->addressBox .= ($pCode || $place) ? '<br>' : '';
            }
            
            $row->addressBox .= $pCode ? "{$pCode} " : '';
            $row->addressBox .= $place;
            
            // Ако имаме права за сингъл
            if ($canSingle) {
                
                // Добавяме адреса
                $row->addressBox .= $address ? "<br/>{$address}" : '';
                
                // Мобилен телефон
                $mob = $mvc->getVerbal($rec, 'mobile');
                $row->phonesBox .= $mob ? "<div class='crm-icon mobile'>{$mob}</div>" : '';
                
                // Телефон
                $tel = $mvc->getVerbal($rec, $rec->buzTel ? 'buzTel' : 'tel');
                $row->phonesBox .= $tel ? "<div class='crm-icon telephone'>{$tel}</div>" : '';
                
                // Факс
                $fax = $mvc->getVerbal($rec, $rec->buzFax ? 'buzFax' : 'fax');
                $row->phonesBox .= $fax ? "<div class='crm-icon fax'>{$fax}</div>" : '';
                
                // Email
                $eml = $mvc->getVerbal($rec, $rec->buzEmail ? 'buzEmail' : 'email');
                $row->phonesBox .= $eml ? "<div class='crm-icon email'>{$eml}</div>" : '';
                
                $row->phonesBox = "<div style='max-width:400px;'>{$row->phonesBox}</div>";
            } else {
                
                // Добавяме линк към профила на потребителя, който е inCharge на визитката
                $row->phonesBox = tr('Отговорник') . ': ' . crm_Profiles::createLink($rec->inCharge);
            }
        }
        $currentId = $mvc->getVerbal($rec, 'id');
        
        
        $row->nameList = '<div class="namelist">'. $row->nameList . "<span class='icon'>". $row->folder .'</span></div>';
        
        $row->title = $mvc->getTitleById($rec->id);
        $row->titleNumber = "<div class='number-block' style='display:inline'>№{$rec->id}</div>";
        
        $birthday = trim($mvc->getVerbal($rec, 'birthday'));
        
        if ($birthday) {
            if (strlen($birthday) == 5) {
                $dateType = 'Рожден&nbsp;ден';
            } else {
                if ($rec->salutation == 'mr') {
                    $dateType = 'Роден';
                } elseif ($rec->salutation == 'mrs' || $rec->salutation == 'miss') {
                    $dateType = 'Родена';
                } else {
                    $dateType = 'Роден(а)';
                }
            }
            if ($mvc->birthdayFilter) {
                $dateType = tr($dateType);
                $row->nameList .= "<div style='font-size:0.8em;margin:3px;'>{$dateType}:&nbsp;{$birthday}</div>";
            }
        } elseif ($rec->egn) {
            $egn = $mvc->getVerbal($rec, 'egn');
            $row->nameList .= "<div style='font-size:0.8em;margin:3px;'>{$egn}</div>";
        }
        
        if ($rec->buzCompanyId && crm_Companies::haveRightFor('single', $rec->buzCompanyId)) {
            $row->buzCompanyId = ht::createLink($mvc->getVerbal($rec, 'buzCompanyId'), array('crm_Companies', 'single', $rec->buzCompanyId));
            $row->nameList .= "<div style='font-size:0.8em;margin:3px;'>{$row->buzCompanyId}</div>";
        }
    }
    
    
    /**
     * Връща заглавието на папката
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        // Конфигурационните данните
        $conf = core_Packs::getConfig('crm');
        
        // Заглавието
        $title = $rec->name;
        
        // Ако е зададена държава
        if ($rec->country) {
            
            // Името на дръжавата
            $commonName = mb_strtolower(drdata_Countries::fetchField($rec->country, 'commonName'));
            $country = self::getVerbal($rec, 'country');
        }
        
        // Ако е зададен града и държавата не е същата
        if ($rec->place && ($commonName == mb_strtolower($conf->BGERP_OWN_COMPANY_COUNTRY))) {
            
            // Добавяме града
            $title .= ' - ' . $rec->place;
        } elseif ($country) {
            
            // Или ако има държава
            $title .= ' - ' . $country;
        }
        
        // Ако е зададено да се ескейпва
        if ($escaped) {
            
            // Ескейпваваме заглавието
            $title = type_Varchar::escape($title);
        }
        
        return $title;
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public static function on_AfterSave($mvc, &$id, $rec, $saveFileds = null)
    {
        $mvc->updateGroupsCnt = true;
        
        $mvc->updatedRecs[$id] = $rec;
        
        $mvc->updateRoutingRules($rec);
        
        if (crm_Profiles::fetch("#personId = {$rec->id}")) {
            $Profiles = cls::get('crm_Profiles');
            $Profiles->invoke('AfterMasterSave', array($rec, $mvc));
        }
    }
    
    
    /**
     * Подготвяме опциите на тип key
     *
     * @param crm_Persons $mvc
     * @param array       $options
     * @param type_Key    $typeKey
     * @param string      $where
     */
    public static function on_BeforePrepareKeyOptions($mvc, $options, $typeKey, $where = '')
    {
        if ($typeKey->params['select'] == 'name') {
            $query = $mvc->getQuery();
            
            $viewAccess = true;
            if ($typeKey->params['restrictViewAccess'] == 'yes') {
                $viewAccess = false;
            }
            
            $mvc->restrictAccess($query, null, $viewAccess);
            $query->where("#state != 'rejected'");
            
            if (trim($where)) {
                $query->where($where);
            }
            
            while ($rec = $query->fetch()) {
                $typeKey->options[$rec->id] = $rec->name . " ({$rec->id})";
            }
        }
    }
    
    
    /**
     * След изтриване на запис
     */
    public static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
        $mvc->updateGroupsCnt = true;
        
        foreach ($query->getDeletedRecs() as $id => $rec) {
            $mvc->updatedRecs[$id] = $rec;
            
            // изтриваме всички правила за рутиране, свързани с визитката
            email_Router::removeRules('person', $rec->id);
        }
    }
    
    
    /**
     * Рутинни действия, които трябва да се изпълнят в момента преди терминиране на скрипта
     */
    public static function on_AfterSessionClose($mvc)
    {
        if ($mvc->updateGroupsCnt) {
            crm_Groups::updateGroupsCnt($mvc->className, 'personsCnt');
        }
        
        if (countR($mvc->updatedRecs)) {
            // Обновяване на информацията за рожденните дни, за променените лица
            foreach ($mvc->updatedRecs as $id => $rec) {
                static::updateBirthdaysToCalendar($id);
            }
        }
    }
    
    
    /**
     * Обновяване на рожденните дни по разписание
     * (Еженощно)
     */
    public function cron_UpdateCalendarEvents()
    {
        $query = self::getQuery();
        
        while ($rec = $query->fetch()) {
            $res = static::updateBirthdaysToCalendar($rec->id);
            $new += $res['new'];
            $deleted += $res['deleted'];
            $updated += $res['updated'];
        }
        
        $status = "В календара са добавени {$new}, обновени {$updated} и изтрити {$deleted} рожденни дни";
        
        return $status;
    }
    
    
    /**
     * Обновява информацията за рожденните дни на посочения
     * човек за текущата и следващите три години
     */
    public static function updateBirthdaysToCalendar($id)
    {
        if (($rec = static::fetch($id)) && ($rec->state != 'rejected')) {
            if ($rec->birthday) {
                list($y, $m, $d) = type_Combodate::toArray($rec->birthday);
            } else {
                $y = $m = $d = 0;
            }
        }
        
        $events = array();
        
        // Годината на датата от преди 30 дни е начална
        $cYear = date('Y', time() - 30 * 24 * 60 * 60);
        
        // Начална дата
        $fromDate = "{$cYear}-01-01";
        
        // Крайна дата
        $toDate = ($cYear + 2) . '-12-31';
        
        // Масив с години, за които ще се вземат рожденните дни
        $years = array($cYear, $cYear + 1, $cYear + 2);
        
        // Префикс на клучовете за рожденните дни на това лице
        $prefix = "BD-{$id}";
        
        if ($d > 0 && $m > 0) {
            foreach ($years as $year) {
                
                // Родените в бъдещето, да си празнуват рождения ден там
                if (($y > 0) && ($y > $year)) {
                    continue;
                }
                
                $calRec = new stdClass();
                
                // Ключ на събитието
                $calRec->key = $prefix . '-' . $year;
                
                // TODO да се проверява за високосна година
                $calRec->time = date('Y-m-d 00:00:00', mktime(0, 0, 0, $m, $d, $year));
                
                $calRec->type = 'birthday';
                $calRec->allDay = 'yes';
                
                if ($y > 0) {
                    $calRec->title = $rec->name . ' на ' . ($year - $y) . ' г.';
                } else {
                    $calRec->title = "ЧРД: {$rec->name}";
                }
                
                // Само рожденните дни на потребителите и на публично достъпните лица се виждат от всички
                if (crm_Profiles::fetch("#personId = {$id}") || $rec->access == 'public') {
                    $calRec->users = '';
                } else {
                    $calRec->users = str_replace('||', '|', "|{$rec->inCharge}|" . $rec->shared);
                }
                
                
                $calRec->url = array('crm_Persons', 'Single', $id);
                
                $calRec->priority = 90;
                
                $events[] = $calRec;
            }
        }
        
        return cal_Calendar::updateEvents($events, $fromDate, $toDate, $prefix);
    }
    
    
    /**
     * Ако е празна таблицата с контактите я инициализираме с един нов запис
     * Записа е с id=1 и е с данните от файла bgerp.cfg.php
     *
     * @param crm_Persons $mvc
     * @param stdClass    $res
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
        if (Request::get('Full')) {
            $query = $mvc->getQuery();
            
            while ($rec = $query->fetch()) {
                $rec->state = 'active';
                
                list($y, $m, $d) = type_Combodate::toArray($rec->birthday);
                
                if ($y > 0 || $m > 0 || $d > 0) {
                    $rec->birthday = type_Combodate::create($y, $m, $d);
                } else {
                    $rec->birthday = null;
                }
                
                $res .= "<li style=''> {$rec->name} =>  {$rec->birthday}";
                
                $mvc->save($rec, 'state,birthday');
            }
        }
    }
    
    
    /**
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_PersonAccRegIntf
     */
    
    
    /**
     * Връща запис-перо съответстващо на лицето
     *
     * @see crm_PersonAccRegIntf::getItemRec()
     */
    public static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = null;
        
        if ($rec = $self->fetch($objectId)) {
            $result = (object) array(
                'num' => $rec->id . ' p',
                'title' => $rec->name,
                'features' => array('Държава' => static::getVerbal($rec, 'country'),
                    'Град' => bglocal_Address::canonizePlace(static::getVerbal($rec, 'place')))
            );
            
            // Добавяме свойствата от групите, ако има такива
            $groupFeatures = crm_Groups::getFeaturesArray($rec->groupList);
            if (countR($groupFeatures)) {
                $result->features += $groupFeatures;
            }
            
            $result->features = cond_ConditionsToCustomers::getFeatures($self, $objectId, $result->features);
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     *
     * @param int $objectId
     */
    public static function itemInUse($objectId)
    {
        // @todo!
    }
    
    
    /****************************************************************************************
     *                                                                                      *
     *  Реализиране на интерфейса crm_CompanyExpandIntf                                     *
     *                                                                                      *
     ****************************************************************************************/
    
    /**
     * Подготвя (извлича) данните за представителите на фирмата
     */
    public function prepareCompanyExpandData(&$data)
    {
        if (!$data->query) {
            $query = $this->getQuery();
            $query->where("#buzCompanyId = {$data->masterId}");
            $query->where("#state != 'rejected'");
        } else {
            $query = $data->query;
        }
        $query->limit(50);
        $data->companiesCnt = $query->count();
        
        while ($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $row = $data->rows[$rec->id] = $this->recToVerbal($rec, 'name,mobile,tel,email,buzEmail,buzTel,buzLocationId,buzPosition');
            
            if ($this->haveRightFor('single', $rec)) {
                $row->name = ht::createLink($row->name, array($this, 'Single', $rec->id));
            }
            
            if ($rec->buzLocationId) {
                $row->name .= " - {$row->buzLocationId}";
            }
            
            if (!$row->buzTel) {
                $row->buzTel = $row->tel;
            }
            
            if (!$row->buzEmail) {
                $row->buzEmail = $row->email;
            }
        }
        
        if (crm_Persons::haveRightFor('add') && crm_Companies::haveRightFor('edit', $data->masterId)) {
            $addUrl = array('crm_Persons', 'add', 'buzCompanyId' => $data->masterId, 'ret_url' => true);
            
            if (!Mode::isReadOnly()) {
                $data->addBtn = ht::createLink('', $addUrl, null, array('ef_icon' => 'img/16/add.png', 'class' => 'addSalecond', 'title' => 'Добавяне на представител'));
            }
        }
    }
    
    
    /**
     * Рендира данните
     */
    public function renderCompanyExpandData($data)
    {
        $tpl = new ET("<fieldset class='detail-info'>
                            <legend class='groupTitle'>" . tr('Представители') . "<!--ET_BEGIN CNT--> ([#CNT#])<!--ET_END CNT-->[#BTN#]</legend>
                                <div class='groupList clearfix21'>
                                 [#persons#]
                            </div>
                            <!--ET_BEGIN regCourt--><div><b>[#regCourt#]</b></div><!--ET_END regCourt-->
                         </fieldset>");
        
        if ($data->addBtn) {
            $tpl->replace($data->addBtn, 'BTN');
        }
        if ($data->companiesCnt) {
            $tpl->replace($data->companiesCnt, 'CNT');
        }
        if (countR($data->rows)) {
            $i = 0;
            foreach ($data->rows as $id => $row) {
                $tpl->append("<div style='margin-bottom:10px'>", 'persons');
                
                if (crm_Persons::haveRightFor('edit', $id)) {
                    $editImg = '<img src=' . sbf('img/16/edit-icon.png') . ' alt="' . tr('Редакция') . '">';
                    $editLink = ht::createLink($editImg, array($this, 'edit', $id, 'ret_url' => true), null, "id=edt{$id},title=Редактиране на " . mb_strtolower($this->singleTitle));
                    $row->name .= " {$editLink}";
                }
                
                $positionsStr = '';
                
                if ($row->buzPosition && $row->name) {
                    $positionsStr = "<i style='font-size:0.9em;'> ({$row->buzPosition})</i>";
                }
                
                $tpl->append("<div> <span style='font-weight:bold;'>{$row->name}</span>{$positionsStr}</div>", 'persons');
                
                if ($row->mobile) {
                    $tpl->append("<div class='crm-icon mobile'>{$row->mobile}</div>", 'persons');
                }
                
                if ($row->buzTel) {
                    $tpl->append("<div class='crm-icon telephone'>{$row->buzTel}</div>", 'persons');
                }
                
                if ($row->buzEmail) {
                    $tpl->append("<div class='crm-icon email'>{$row->buzEmail}</div>", 'persons');
                }
                
                $tpl->append('</div>', 'persons');
                
                if ($i ++ % 2 == 1) {
                    $tpl->append("<div class='clearfix21'></div>", 'persons');
                }
            }
        } else {
            $tpl->append(tr('Няма записи'), 'persons');
        }
        
        return $tpl;
    }
    
    
    /****************************************************************************************
     *                                                                                      *
     *  Подготвя и рендира Имениците                                                       *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Подготвя (извлича) данните за Имениците
     */
    public static function prepareNamedays(&$data)
    {
        if (!countR($data->namesArr)) {
            
            return;
        }
        
        $currentId = core_Users::getCurrent();
        $query = self::getQuery();
        $query->XPR('trimmedSarchKeywords', 'varchar', 'TRIM(#searchKeywords)');
        $query->where("#inCharge = '{$currentId}' OR #shared LIKE '|{$currentId}|'");
        $query->where("#state != 'rejected' AND #state != 'closed'");
        $query->show('name,buzTel,tel,buzEmail,email');
        
        $or = '';
        foreach ($data->namesArr as $name) {
            $where .= "{$or}#trimmedSarchKeywords LIKE '{$name} %'";
            $or = ' OR ';
        }
        $query->where($where);
        
        while ($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $row = self::recToVerbal($rec, 'name,tel,buzEmail,email');
            $row->name = crm_Persons::getHyperlink($rec->id, true);
            $row->buzTel = (!empty($rec->buzTel)) ? $row->buzTel : ((!empty($rec->tel)) ? $row->tel : null);
            $row->buzEmail = (!empty($rec->buzEmail)) ? $row->buzEmail : ((!empty($rec->email)) ? $row->email : null);
            
            $data->rows[$rec->id] = $row;
        }
    }
    
    
    /**
     * Рендира данните
     */
    public static function renderNamedays($data)
    {
        if (!countR($data->rows)) {
            
            return '';
        }
        
        $tpl = new ET("<fieldset class='detail-info'>
                            <legend class='groupTitle'>" . tr('Именици във визитника') . "</legend>
                                <div class='groupList clearfix21'>
                                 <!--ET_BEGIN person-->
        						 [#person#]
        						 <div style='font-weight:bold;'>[#name#]
        						 <!--ET_BEGIN buzTel--> - <span style='font-style:italic;'>[#buzTel#]</span><!--ET_END buzTel-->
        						 <!--ET_BEGIN buzEmail--><span style='font-style:italic'>, [#buzEmail#]</span><!--ET_END buzEmail-->
        						</div>
        						 <!--ET_END person-->
                            </div>
                         </fieldset>");
        
        $block = $tpl->getBlock('person');
        foreach ($data->rows as $row) {
            $clone = clone $block;
            $block->placeObject($row);
            $block->removeBlocks();
            $block->append2Master();
        }
        
        return $tpl;
    }
    
    
    /**
     * Обновява правилата за рутиране според наличните данни във визитката
     *
     * @param stdClass $rec
     */
    public static function updateRoutingRules($rec)
    {
        if ($rec->state == 'rejected') {
            // Визитката е оттеглена - изтриваме всички правила за рутиране, свързани с нея
            email_Router::removeRules('person', $rec->id);
        } else {
            if ($rec->buzEmail) {
                // Лицето има служебен имейл. Ако има и фирма, регистрираме служебния имейл на
                // името на фирмата
                if ($rec->buzCompanyId) {
                    crm_Companies::createRoutingRules($rec->buzEmail, $rec->buzCompanyId);
                } else {
                    static::createRoutingRules($rec->buzEmail, $rec->id);
                }
            }
            
            if ($rec->email) {
                // Регистрираме личния имейл на името на лицето
                static::createRoutingRules($rec->email, $rec->id);
            }
        }
    }
    
    
    /**
     * Създава `From` и `Doman` правила за рутиране след запис на визитка
     *
     * Използва се от @link crm_Persons::updateRoutingRules() като инструмент за добавяне на
     * правила според различни сценарии на базата на данните на визитката
     *
     * @access protected
     *
     * @param mixed $emails   един или повече имейли, зададени като стринг или като масив
     * @param int   $objectId
     */
    public static function createRoutingRules($emails, $objectId)
    {
        // Приоритетът на всички правила, генериране след запис на визитка е нисък и нарастващ с времето
        $priority = email_Router::dateToPriority(dt::now(), 'low', 'asc');
        
        // Нормализираме параметъра $emails - да стане масив от имейл адреси
        if (!is_array($emails)) {
            $emails = type_Emails::toArray($emails);
        }
        
        foreach ($emails as $email) {
            // Създаване на `From` правило
            email_Router::saveRule(
                (object) array(
                    'type' => email_Router::RuleFrom,
                    'key' => email_Router::getRoutingKey($email, null, email_Router::RuleFrom),
                    'priority' => $priority,
                    'objectType' => 'person',
                    'objectId' => $objectId
                )
            );
            
            // Създаване на `Domain` правило
            if ($key = email_Router::getRoutingKey($email, null, email_Router::RuleDomain)) {
                // $key се генерира само за непублични домейни (за публичните е FALSE), така че това
                // е едновременно индиректна проверка дали домейнът е публичен.
                email_Router::saveRule(
                    (object) array(
                        'type' => email_Router::RuleDomain,
                        'key' => $key,
                        'priority' => $priority,
                        'objectType' => 'person',
                        'objectId' => $objectId
                    )
                );
            }
        }
    }
    
    
    /**
     * Дали на лицето се начислява ДДС:
     * Начисляваме винаги ако е в ЕУ
     * Ако няма държава начисляваме ДДС
     *
     * @param int $id - id' то на записа
     *
     * @return bool TRUE/FALSE
     */
    public static function shouldChargeVat($id)
    {
        $rec = static::fetch($id);
        
        if (!$rec->country) {
            
            return true;
        }
        
        return drdata_Countries::isEu($rec->country);
    }
    
    
    /**
     * Връща данните на лицето
     *
     * @param int $id - id' то на записа
     *
     * return object
     */
    public static function getContragentData($id)
    {
        //Вземаме данните
        $person = crm_Persons::fetch($id);
        
        if ($person->buzCompanyId) {
            $company = crm_Companies::fetch($person->buzCompanyId);
        }
        
        // Заместваме и връщаме данните
        if ($person) {
            $contrData = new stdClass();
            $contrData->company = crm_Persons::getVerbal($person, 'buzCompanyId');
            if ($company) {
                $contrData->companyVerb = crm_Companies::getVerbal($company, 'name');
            }
            $contrData->companyId = $person->buzCompanyId;
            $contrData->person = $person->name;
            $contrData->personVerb = crm_Persons::getVerbal($person, 'name');
            $contrData->country = crm_Persons::getVerbal($person, 'country');
            $contrData->countryId = $person->country;
            $contrData->pCode = $person->pCode;
            $contrData->vatNo = $person->vatId;
            $contrData->eori = $person->eori;
            $contrData->uicId = $person->egn;
            $contrData->place = $person->place;
            $contrData->email = $person->buzEmail;
            $contrData->tel = $person->buzTel;
            $contrData->fax = $person->buzFax;
            $contrData->address = $person->buzAddress;
            
            $contrData->pTel = $person->tel;
            $contrData->pMobile = $person->mobile;
            $contrData->pFax = $person->fax;
            $contrData->pAddress = $person->address;
            $contrData->pEmail = $person->email;
            
            $contrData->salutationRec = $person->salutation;
            $contrData->salutation = crm_Persons::getVerbal($person, 'salutation');
            
            // Ако е свързан с фирма
            if ($person->buzCompanyId) {
                
                // Вземаме всички имейли
                $contrData->groupEmails = static::getGroupEmails($person->buzCompanyId);
            }
            
            $clsId = core_Classes::getId(get_called_class());
            $locationEmails = crm_Locations::getEmails($clsId, $id);
            
            if ($locationEmails) {
                $contrData->groupEmails .= ($contrData->groupEmails) ? ', ' . $locationEmails : $locationEmails;
            }
            
            // Ако има личен имейл
            if ($person->email) {
                
                // Добавяме и него към групата
                $contrData->groupEmails .= ($contrData->groupEmails) ? ', ' . $person->email : $person->email;
            }
        }
        
        return $contrData;
    }
    
    
    /**
     * Връща всички имейли свързани с компанията:
     * Имейла на фирмата и бизнес имейлите на потребителите, свързани с фирмата
     *
     * @param int $companyId - id на фирмата
     *
     * @return string $res - Стринг с имейли
     */
    public static function getGroupEmails($companyId)
    {
        // Имейла на фирмата
        $companyEmail = crm_Companies::fetchField($companyId, 'email');
        
        // Ако има имейл
        if ($companyEmail) {
            
            // Добавяме към резултата
            $res = $companyEmail;
        }
        
        $query = static::getQuery();
        $query->where("#buzCompanyId = '{$companyId}'");
        $query->where("#state != 'rejected'");
        
        // Извличаме всички потребители, които са свързани с фирмата
        while ($rec = $query->fetch()) {
            
            // Ако няма имейл, прескачаме
            if (!trim($rec->buzEmail)) {
                continue;
            }
            
            // Добавяме към резултата
            $res .= ($res) ? ', ' . $rec->buzEmail : $rec->buzEmail;
        }
        
        // Добавяме и имейлите от локациите
        $clsId = core_Classes::getId('crm_Companies');
        $locationEmails = crm_Locations::getEmails($clsId, $companyId);
        
        if ($locationEmails) {
            $res .= ($res) ? ', ' . $locationEmails : $locationEmails;
        }
        
        return $res;
    }
    
    
    /**
     * Реализира обработката на данните, изпратени чрез импорт форма
     *
     * В случая този метод реализира импортирането на данни от VCF файл съхраняван от fileman.
     *
     * Метода се очаква / извиква от плъгина bgerp_plg_Importer. Всеки мениджър, който използва
     * bgerp_plg_Importer задължително трябва да имплементира метод import()!
     *
     * @param stdClass $rec запис, получен при субмитването на импорт формата
     *
     * @return string HTML който да се покаже като обратна връзка за потребителя относно резултата
     *                от импорта.
     */
    public static function import($rec)
    {
        $vcfData = fileman_Files::getContent($rec->file);
        
        $vcards = pear_Vcard::parseString($vcfData);
        
        $res = '<h2>Резултат от импорт</h2>';
        
        /* @var $vcard pear_Vcard */
        foreach ($vcards as $vcard) {
            $rec = new stdClass();
            
            if (!$rec->name = $vcard->getFormattedName()) {
                $line = 'Липсва име';
            } else {
                $rec->salutation = $vcard->getName('prefix');
                $rec->birthday = $vcard->getBday();
                
                $address = $vcard->getAddress();
                
                if (is_array($address)) {
                    // За сега използваме първия адрес от първия възможен тип:
                    $address = reset($address);
                    
                    $rec->place = $address['locality'];
                    
                    //
                    // {{{ Извличане на държавата
                    //
                    $country = $address['country'];
                    if (!empty($country) &&
                        !($rec->country = drdata_Countries::fetchField(array("#formalName = '[#1#]'", $country), 'id')) &&
                        !($rec->country = drdata_Countries::fetchField(array("#commonName = '[#1#]'", $country), 'id'))) {
                        // Ако не можем да определим ключа на държавата, добавяме я към града, за
                        // да не се загуби напълно
                        $rec->place .= ", {$country}";
                    }
                    
                    //
                    // Край с държавата }}}
                    //
                    $rec->pcode = $address['code'];
                    $rec->address = $address['street'];
                }
                
                
                if ($organisation = $vcard->getOrganisation()) {
                    $rec->buzCompanyId = crm_Companies::fetchField(array("#name = '[#1#]'", $organisation), 'id');
                    
                    // Записваме пълната организация към забележките, за да не се загуби
                    $rec->info = "Организация:\n===========\n" . implode("\n", $vcard->getOrganisation(true)) . "\n\n";
                }
                
                //
                // {{{ Извличане на имейли - служебни и лични
                //
                $emails = $vcard->getEmails();
                
                $persEmails = array();
                $bizEmails = array();
                
                // Приемаме, че имейлите без тип и от тип "home" са лични,
                // всички останали - служебни
                foreach ($emails as $type => $list) {
                    if ($type == 'home' || $type == 0) {
                        $persEmails = array_merge($persEmails, $list);
                    } else {
                        $bizEmails = array_merge($bizEmails, $list);
                    }
                }
                
                $rec->email = implode(', ', array_unique($persEmails));
                $rec->buzEmail = implode(', ', array_unique($bizEmails));
                
                //
                // Край с имейлите }}}
                //
                
                //
                // {{{ Извличане на телефони и факсове - служебни и лични
                //
                $tels = $vcard->getTel();
                
                $persTel = $persMob = $persFax = $bizTel = $bizFax = $voiceTel = array();
                
                if (isset($tels['cell'])) {
                    $persMob = $tels['cell'];
                    unset($tels['cell']);
                }
                if (isset($tels['home'])) {
                    $persTel = array_diff($tels['home'], $persMob);
                    unset($tels['home']);
                }
                if (isset($tels[0])) {
                    // Приемаме, че телефоните без тип са лични
                    $persTel = array_merge($persTel, array_diff($tels[0], $persMob));
                    unset($tels[0]);
                }
                if (isset($tels['work'])) {
                    $bizTel = array_diff($tels['work'], $persMob);
                    unset($tels['work']);
                }
                if (isset($tels['fax'])) {
                    $bizFax = $tels['fax'];
                    unset($tels['fax']);
                    
                    // Факсовете, които не са лични (home) са служебни
                    foreach ($bizFax as $i => $num) {
                        if (in_array($num, $persTel)) {
                            unset($bizFax[$i]);
                        }
                    }
                }
                
                if (is_array($tels)) {
                    // Приемаме, че всички останали телефони са служебни
                    foreach ($tels as $list) {
                        $bizTel = array_merge($bizTel, $list);
                    }
                    
                    $rec->buzTel = implode(', ', array_unique($bizTel));
                    $rec->buzFax = implode(', ', array_unique($bizFax));
                    $rec->tel = implode(', ', array_unique($persTel));
                    $rec->mobile = implode(', ', array_unique($persMob));
                    $rec->fax = implode(', ', array_unique($persFax));
                    
                    //
                    // Край с телефоните }}}
                    //
                }
                
                //
                // {{{ Снимка
                //
                if ($photoUrl = $vcard->getPhotoUrl()) {
                    // @TODO: Как да добавя файл в кофата 'pictures' когато знам URL-то му???
                }
                
                //
                // Край Снимка }}}
                //
                
                // Запис
                if (static::save($rec)) {
                    $res .= sprintf('<li>Добавен: %s</li>', $rec->name);
                } else {
                    $res .= sprintf('<li>Прочетен но НЕ записан: %s</li>', $rec->name);
                }
            }
        }
        
        $res = sprintf('<ul>%s</ul>', $res);
        
        return $res;
    }
    
    
    public static function act_Export()
    {
        $selected = null;
        
        if ($selected = Request::get('Selected')) {
            $selected = arr::make($selected);
            foreach ($selected as $i => $id) {
                $selected[$i] = intval($selected[$i]);
            }
        } elseif ($id = Request::get('id', 'key(mvc=crm_Persons)')) {
            $selected = array($id);
        }
        
        $vcards = static::export($selected);
        
        pear_Vcard::httpRespond($vcards);
        
        shutdown();
    }
    
    
    public static function export($ids = null)
    {
        /* @var $query core_Query */
        $query = static::getQuery();
        
        if (!empty($ids)) {
            $query->where('#id IN (' . implode(', ', $ids) . ')');
        }
        
        $vcards = array();
        
        while ($rec = $query->fetch()) {
            // Проверка за права
            if (!static::haveRightFor('read', $rec)) {
                continue;
            }
            
            $vcards[] = static::exportRec($rec);
        }
        
        return $vcards;
    }
    
    
    protected static function exportRec($rec)
    {
        $row = static::recToVerbal($rec);
        
        $vcard = pear_Vcard::createEmpty();
        
        $vcard->setFormattedName($rec->name);
        $vcard->setName(array('prefix' => $rec->salutation));
        
        if ($rec->birthday) {
            // Опит за конвертиране на рожденната дата във формат YYYY-mm-dd. Това не винаги
            // е възможно, за стойности от тип 'combodate', но от друга страна формата vCard
            // изисква пълна дата - ден, месец, година.
            list($y, $m, $d) = type_Combodate::toArray($rec->birthday);
            if ($y > 0 && $m > 0 && $d > 0) {
                // Всички компоненти на датата са зададени
                $vcard->setBday("{$y}-{$m}-{$d}");
            }
        }
        
        $vcard->addAddress(
            array(
                'street' => $rec->address,
                'locality' => $row->place,
                'code' => $row->pCode,
                'country' => $row->country,
            ),
            array(
                'TYPE' => 'HOME'
            )
        );
        
        $vcard->addAddressLabel(
            $rec->bizAddress,
            array(
                'TYPE' => 'WORK'
            )
        );
        
        static::addTelsToVcard($vcard, $rec->tel, array('TYPE' => 'HOME'));
        static::addTelsToVcard($vcard, $rec->mobile, array('TYPE' => 'CELL'));
        static::addTelsToVcard($vcard, $rec->fax, array('TYPE' => 'FAX'));
        static::addTelsToVcard($vcard, $rec->buzTel, array('TYPE' => 'WORK'));
        static::addTelsToVcard($vcard, $rec->buzFax, array('TYPE' => 'WORK,FAX'));
        
        static::addEmailsToVcard($vcard, $rec->emails, array('TYPE' => 'HOME'));
        static::addEmailsToVcard($vcard, $rec->buzEmails, array('TYPE' => 'WORK'));
        
        $vcard->setOrganisation($row->bizCompanyId);
        
        if ($rec->photo) {
            $vcard->setPhotoUrl(fileman_Download::getDownloadUrl($rec->photo));
        }
        
        $vcard->setNote($rec->info);
        
        return $vcard;
    }
    
    
    protected static function addTelsToVcard($vcard, $tels, $params = array())
    {
        if (!$tels) {
            
            return;
        }
        
        foreach ($params as $i => $p) {
            $params[$i] = arr::make($p);
        }
        
        $tels = drdata_PhoneType::toArray($tels);
        
        foreach ($tels as $tel) {
            if ($tel->mobile) {
                $params['TYPE'][] = 'CELL';
            }
            
            $vcard->addTel($tel->original, $params);
        }
    }
    
    
    protected static function addEmailsToVcard($vcard, $emails, $params = array())
    {
        if (!$emails) {
            
            return;
        }
        
        foreach ($params as $i => $p) {
            $params[$i] = arr::make($p);
        }
        
        $emails = type_Emails::toArray($emails);
        
        foreach ($emails as $email) {
            $vcard->addEmail($email, $params);
        }
    }
    
    
    /**
     * Функция, която задава правата за достъп до даден потребител в търсенето
     *
     * Вземаме всики папки на които сме inCharge или са споделени с нас или са публични или
     * (са екипни и inCharge е някой от нашия екип) и състоянието е активно
     *
     * @param core_Query $query  - Заявката към системата
     * @param int        $userId - Потребителя, за който ще се отнася
     */
    public static function applyAccessQuery(&$query, $userId = null)
    {
        // Ако няма зададен потребител
        if (!$userId) {
            
            // Вземаме текущия
            $userId = core_Users::getCurrent();
        }
        
        $user = '|' . $userId . '|';
        
        // Вземаме членовете на екипа
        $teammates = core_Users::getTeammates($userId);
        
        // Проверка дали не е inCharge
        $query->where("'{$user}' LIKE CONCAT('%|', #inCharge, '|%')");
        
        // Проверка дали не е споделен към потребителя
        $query->orLikeKeylist('shared', $user);
        
        // Вземаме всички публични
        $query->orWhere("#access = 'public'");
        
        // Ако достъпа е отборен и собственика е екипа на потребителя
        $query->orWhere("#access = 'team' AND '{$teammates}' LIKE CONCAT('%|', #inCharge, '|%')");
        
        // Състоянието да е активно
        $query->where("#state != 'rejected'");
    }
    
    
    /**
     * Модифициране на edit формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $conf = core_Packs::getConfig('crm');
        
        $form = &$data->form;
        
        if (empty($form->rec->id)) {
            // Слагаме Default за поле 'country'
            $Countries = cls::get('drdata_Countries');
            $form->setDefault('country', $Countries->fetchField("#commonName = '" .
                    $conf->BGERP_OWN_COMPANY_COUNTRY . "'", 'id'));
        }
        
        // Ако сме в тесен режим
        if (Mode::is('screenMode', 'narrow')) {
            
            // Да има само 2 колони
            $data->form->setField($mvc->expandInputFieldName, array('maxColumns' => 2));
        }
        
        if (!$form->rec->id && $form->rec->buzCompanyId && isset($_GET['buzCompanyId'])) {
            $form->setReadOnly('buzCompanyId');
        }

        if (!empty($form->rec->buzCompanyId)) {
            $locations = crm_Locations::getContragentOptions(crm_Companies::getClassId(), $form->rec->buzCompanyId);
            $form->setOptions('buzLocationId', $locations);
            if (countR($locations)) {
                $form->setOptions('buzLocationId', $locations);
                $form->setField('buzLocationId', 'input');
            }
        }

        crm_Companies::autoChangeFields($form);
    }
    
    
    /**
     * Задава титла на формата за редактиране
     */
    public static function on_AfterPrepareEditTitle($mvc, &$res, $data)
    {
        $form = &$data->form;
        
        if ($form->rec->buzCompanyId) {
            $form->title = core_Detail::getEditTitle('crm_Companies', $form->rec->buzCompanyId, 'представител', $form->rec->id);
        }
    }
    
    
    /**
     * Интерфейсен метод на fileman_FileActionsIntf
     *
     * Връща масив с действия, които могат да се извършат с дадения файл
     *
     * @param stdClass $fRec - Обект са данни от модела
     *
     * @return array $arr - Масив с данните
     *               $arr['url'] - array URL на действието
     *               $arr['title'] - Заглавието на бутона
     *               $arr['icon'] - Иконата
     */
    public static function getActionsForFile_($fRec)
    {
        // Позволените разширения, за създаване на визитка
        $vCardExtArr = array('vcf', 'vcard');
        
        // Разширението на файла
        $ext = fileman_Files::getExt($fRec->name);
        
        $arr = null;
        
        // Ако разширението е в допустимите, имамем права за добваня и имаме права за single' а на файла
        if (in_array($ext, $vCardExtArr) && (static::haveRightFor('add') && (fileman_Files::haveRightFor('single', $fRec)))) {
            
            // Създаваме масива за съзване на визитка
            $arr = array();
            $arr['vcard']['url'] = array('crm_Persons', 'extractVcard', 'fh' => $fRec->fileHnd, 'ret_url' => true);
            $arr['vcard']['title'] = 'Лице';
            $arr['vcard']['icon'] = '/img/16/extract_foreground_objects.png';
        }
        
        return $arr;
    }
    
    
    /**
     * Екшън за извличане на информация за създаване на лице от визитка.
     */
    public function act_ExtractVcard()
    {
        // Трябва да има права за добавяне
        static::requireRightFor('add');
        
        // Манипулатора на файла
        $fh = Request::get('fh');
        
        // Очакваме да има подаден манипулатор
        expect($fh);
        
        // Очакваме да има такъв запис
        expect($fRec = fileman_Files::fetchByFh($fh));
        
        // Очакваме да има права за single'а на файла
        fileman_Files::requireRightFor('single', $fRec);
        
        // Разширението на файла
        $ext = fileman_Files::getExt($fRec->name);
        
        // Драйверите на файла
        $drivers = fileman_Indexes::getDriver($ext);
        
        // Масив с всички визитки в съответния файл
        $allVcards = array();
        
        // Обхождаме всички драйвери
        foreach ($drivers as $driver) {
            
            // Опитваме се да подготвим данните
            try {
                
                // Подготвяме данните
                $data = $driver->prepareData($fRec);
            } catch (core_exception_Expect $e) {
                // Данните за визитката от съответния драйвер
                $data = array();
            }
            
            // Събираме всички данни
            $allVcards = array_merge($allVcards, $data);
        }
        
        // Вземаме формата към този модел
        $form = $this->getForm();
        
        // Въвеждаме съдържанието на полетата
        $form->input();
        
        static::prepareBirthday($rec);
        
        // Ако формата е субмитната
        if ($form->isSubmitted()) {
            
            // Инстанция на класа
            $class = cls::get('crm_Persons');
            
            // Проверявяме да няма дублирани полета
            $resStr = static::getSimilarWarningStr($form->rec, $fields);
            
            if ($resStr) {
                $form->setWarning($fields, $resStr);
            }
        }
        
        // Ако формата е субмитнара успешно
        if ($form->isSubmitted()) {
            
            // Опитваме се да форматираме населеното място
            if ($rec->place) {
                $rec->place = bglocal_Address::canonizePlace($rec->place);
            }
            
            // Записваме данните
            $id = static::save($form->rec);
            
            // Създаваме обект
            $data = new stdClass();
            
            // Добавяме формата към него
            $data->form = $form;
            
            // Подготяваме URL' то където ще редиректваме след записа
            static::prepareRetUrl($data);
            
            // Ако не може да се подготви URL' то
            $retUrl = ($data->retUrl) ? $data->retUrl : array('crm_Persons', 'single', $id);
            
            // Редиректваме
            return new Redirect($retUrl);
        }
        
        // Задаваме текущия потребител да е отговорник по подразбиране
        $form->setDefault('inCharge', core_Users::getCurrent());
        
        // TODO какво ще се направи, когато имаме повече от една визитка в един файл?
//        $cntOfVcards = count($allVcards);
        
        // Добавяме титлата на формата
        $form->title = 'Създаване на потребител от визитка';
        
        // За сега вземаме първата визитка във файла
        $currVcard = $allVcards[0];
        
        // Ако няма визитка
        if (!$currVcard) {
            
            return static::renderWrapping($form->renderHtml());
        }
        
        // Опитваме се да извлечем името
        if (!($names = $currVcard['formattedName'])) {
            $names = "{$currVcard['name']['given']} {$currVcard['name']['additional']} {$currVcard['name']['surname']}";
        }
        
        // Задаваме да е избрано по подразбиране името, което сме определили
        $form->setDefault('name', $names);
        
        // Опитваме се да намерим обръщението
        if ($currVcard['name']['prefix']) {
            
            // Вземаме всички допустими обръщения
            $salutationOpt = $form->getOptions('salutation');
            
            if (is_null($salutationOpt)) {
                $salutationOpt = $form->fields['salutation']->type->options;
            }
            
            if (isset($salutationOpt)) {
                // Проверяваме дали обръщението го има в масив, като го превеждаме
                $salutationKey = array_search(tr($currVcard['name']['prefix']), $salutationOpt);
                
                // Задаваме по подразбиране да е избано обръщението, което сме определили
                $form->setDefault('salutation', $salutationKey);
            }
        }
        
        // Ако има зададен рожден ден
        if ($currVcard['bDay']) {
            
            // Задаваме рожденния ден
            $form->setDefault('birthday', $currVcard['bDay']);
        }
        
        // TODO не работи с линкове, а само с fileHnd
        // Вземаме първия линк от масива URL'та
//        $photoKey = key($currVcard['photoUrl']);
        // Ако има въведено URL
//        if ($photoKey !== NULL) {
        
        // Задаваме да е избран по подразбиране
//            $form->setDefault('photo', $currVcard['photoUrl'][$photoKey]);
//        }
        
        $phonesStrArr = array();
        
        // Вземаме всички телефонни номера и ги групираме в масив в зависимост от вида им
        $phonesStrArr['work'] = core_Array::extractMultidimensionArray($currVcard['tel'], 'work');
        $phonesStrArr['voice'] = core_Array::extractMultidimensionArray($currVcard['tel'], 'voice');
        $phonesStrArr['home'] = core_Array::extractMultidimensionArray($currVcard['tel'], 'home');
        $phonesStrArr['fax'] = core_Array::extractMultidimensionArray($currVcard['tel'], 'fax');
        $phonesStrArr['cell'] = core_Array::extractMultidimensionArray($currVcard['tel'], 'cell');
        $phonesStrArr['pref'] = core_Array::extractMultidimensionArray($currVcard['tel'], 'pref');
        
        // Добавяме номерата, които са pref в стринга
        if ($phonesStrArr['pref']) {
            $personePhone .= ($personePhone) ? ', ' . $phonesStrArr['pref'] : $phonesStrArr['pref'];
        }
        
        // Добавяме номерата, които са home в стринга
        if ($phonesStrArr['home']) {
            $personePhone .= ($personePhone) ? ', ' . $phonesStrArr['home'] : $phonesStrArr['home'];
        }
        
        // Добавяме номерата, които са voice в стринга
        if ($phonesStrArr['voice']) {
            $personePhone .= ($personePhone) ? ', ' . $phonesStrArr['voice'] : $phonesStrArr['voice'];
        }
        
        // Задаваме съответните стойности да са избрани по подразбиране
        $form->setDefault('buzTel', $phonesStrArr['work']);

//        $form->setDefault('buzFax', $phonesStrArr['fax']);
        $form->setDefault('fax', $phonesStrArr['fax']);
        $form->setDefault('mobile', $phonesStrArr['cell']);
        $form->setDefault('tel', $personePhone);
        
        // Вземаме всички имейли
        $emails = core_Array::extractMultidimensionArray($currVcard['Emails']);
        
        // Задаваме полето имейли, да съдържа всички намерени имейли
        $form->setDefault('email', $emails);
        
        // Опитваме се да извлечем адреса от масива
        if ($currVcard['addressLabel']) {
            
            // Ако сме взели адреса, като стринга
            // Създавме масив за всички адреси
            $addressLabel = $currVcard['addressLabel'];
            
            // Вземаме адреса на фирмата
            $workAddr = core_Array::extractMultidimensionArray($addressLabel, 'work', ' | ');
            
            // Премахваме го от масива
            unset($addressLabel['work']);
            
            // Създаваме нов масив, където на първо място са домашните
            $newAddLabel = array();
            $newAddLabel['home'] = $addressLabel['home'];
            $newAddLabel['dom'] = $addressLabel['dom'];
            $newAddLabel += (array) $addressLabel;
            
            // Вземаме всички адреси, без служебния, като на първо място е домашния
            $homeAddr = core_Array::extractMultidimensionArray($addressLabel, false, ' | ');
        } else {
            
            // Създавме масив за всички адреси
            $addressArr = $currVcard['Address'];
            
            // Вземаме адреса на фирмата
            $workAddr = core_Array::extractMultidimensionArray($addressArr, 'work', ' | ');
            
            // Премахваме го от масива
            unset($addressArr['work']);
            
            // Създаваме нов масив, където на първо място са домашните
            $newAddrArr['home'] = $addressArr['home'];
            $newAddrArr['dom'] = $addressArr['dom'];
            $newAddrArr += (array) $addressArr;
            
            // Вземаме всички адреси, без служебния, като на първо място е домашния
            $homeAddr = core_Array::extractMultidimensionArray($newAddrArr, false, ' | ');
        }
        
        // Задаваме адреса на фирмата
        $form->setDefault('buzAddress', $workAddr);
        
        // Задаваме адреса на лицето
        $form->setDefault('address', $homeAddr);
        
        // Ако има задедена организация
        if ($currVcard['organization']) {
            
            // Името на фирмата в долния регистър
            $organization = mb_strtolower($currVcard['organization']);
            
            // Гледаме дали има такава въведена фирма
            $companyId = crm_Companies::fetch(array("LOWER(#name) LIKE '%[#1#]%'", $organization), 'id')->id;
            
            // Избираме я по подразбиране
            $form->setDefault('buzCompanyId', $companyId);
        }
        
        // Името на работата
        $jobTitle = tr($currVcard['jobTitle']);
        
        // Ролята
        $role = tr($currVcard['role']);
        
        // Съединяваме името на работата с ролята
        $buzPosition = ($role) ? "${jobTitle} - ${role}" : $jobTitle;
        
        // Задаваме позицията на работата
        $form->setDefault('buzPosition', $buzPosition);
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png', array('order' => 1));
        $form->toolbar->addBtn('Отказ', getRetUrl(), array('order' => 10), 'ef_icon = img/16/close-red.png');
        
        // Добавяме във формата информация, за да знаем коя визитка добавяме
//        $form->info = "Извличане на информация за първата визитка";
        
        return static::renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Поправяне на ключовете в документите
     */
    public function act_RepairKeywords()
    {
        requireRole('admin');
        
        core_App::setTimeLimit(600);
        
        $force = Request::get('force');
        
        $rArr = self::regenerateSerchKeywords($force);
        $cnt = $rArr['crm_Persons'] + $rArr['crm_Companies'];
        
        if ($cnt == 0) {
            $msg = '|Няма визитки за ре-индексиране';
        } else {
            if ($cnt == 1) {
                $msg = "|Ре-индексиран|* {$cnt} |запис";
            } else {
                $msg = "|Ре-индексиран|* {$cnt} |записа";
            }
        }
        
        $retUrl = getRetUrl();
        
        if (empty($retUrl)) {
            $retUrl = array('core_Packs');
        }
        
        return new Redirect($retUrl, $msg);
    }
    
    
    /**
     * Регенерира ключовите думи, ако е необходимо
     *
     * @param bool  $force
     * @param array $rClassArr
     *
     * @return array
     */
    public static function regenerateSerchKeywords($force = false, $rClassArr = array('crm_Persons', 'crm_Companies'))
    {
        $resArr = array();
        
        $rClassArr = arr::make($rClassArr);
        
        foreach ($rClassArr as $class) {
            $clsInst = cls::get($class);
            
            $query = $clsInst->getQuery();
            $query->show('searchKeywords');
            $resArr[$class] = 0;
            while ($rRec = $query->fetch()) {
                $generatedKeywords = $clsInst->getSearchKeywords($rRec);
                
                if (!$force && ($generatedKeywords == $rRec->searchKeywords)) {
                    continue;
                }
                
                $generatedKeywords = plg_Search::purifyKeywods($generatedKeywords);
                
                $rRec->searchKeywords = $generatedKeywords;
                
                $clsInst->save_($rRec, 'searchKeywords');
                
                $resArr[$class]++;
            }
        }
        
        return $resArr;
    }


    /**
     * Подготвяме рожденния ден. Ако няма въведение хубави данни, използваме ЕГН' то
     */
    public static function prepareBirthday(&$rec)
    {
        list($y, $m, $d) = type_Combodate::toArray($rec->birthday);
        
        if (isset($rec->egn) && !($y > 0 || $m > 0 || $d > 0)) {
            try {
                $Egn = new bglocal_BulgarianEGN($rec->egn);
            } catch (bglocal_exception_EGN $e) {
                $err = $e->getMessage();
            }

            if (!$err) {
                $rec->birthday = type_Combodate::create($Egn->birth_year, $Egn->birth_month, $Egn->birth_day);
            }
        }
    }
    
    
    /**
     * Проверява дали полето име и полето ЕГН се дублират. Ако се дублират сетваме грешка.
     *
     * @param stdClass $rec
     * @param string   $fields
     *
     * @return string
     */
    public static function getSimilarWarningStr($rec, &$fields = '')
    {
        $resStr = '';
        
        $similarsArr = self::getSimilarRecs($rec, $fields);
        
        if (!empty($similarsArr)) {
            $similarPersons = '';
            foreach ($similarsArr as $similarRec) {
                $class = '';
                
                if ($similarRec->state == 'rejected') {
                    $class = "class='state-rejected'";
                } elseif ($similarRec->state == 'closed') {
                    $class = "class='state-closed'";
                }
                
                $similarPersons .= "<li {$class}>";
                
                $singleUrl = array();
                $otherParamArr = array();
                
                if ($haveRightForSingle = self::haveRightFor('single', $similarRec->id)) {
                    $singleUrl = array(get_called_class(), 'single', $similarRec->id);
                    $otherParamArr['target'] = '_blank';
                }
                
                $similarPersons .= ht::createLink(self::getVerbal($similarRec, 'name'), $singleUrl, null, $otherParamArr);
                
                if ($haveRightForSingle) {
                    if ($similarRec->egn) {
                        $similarPersons .= ', ' . self::getVerbal($similarRec, 'egn');
                    } elseif ($birthday = self::getverbal($similarRec, 'birthday')) {
                        $similarPersons .= ', ' . $birthday;
                    }
                }
                
                if (trim($similarRec->place)) {
                    $similarPersons .= ', ' . self::getVerbal($similarRec, 'place');
                }
                
                if (!$haveRightForSingle) {
                    $similarPersons .= ' - ' . crm_Profiles::createLink($similarRec->inCharge);
                }
                
                $similarPersons .= '</li>';
            }
            
            $sledniteLica = (countR($similarsArr) == 1) ? 'следното лице' : 'следните лица';
            
            $resStr = "Възможно е дублиране със {$sledniteLica}|*: <ul>{$similarPersons}</ul>";
        }
        
        return $resStr;
    }
    
    
    /**
     * Връща масив с възможните съвпадения
     *
     * @param stdClass $rec
     * @param string   $fields
     *
     * @return array
     */
    protected static function getSimilarRecs($rec, &$fields = '')
    {
        $similarsArr = array();
        
        $similarName = $similarEgn = false;
        
        $fieldsArr = array();
        
        // Правим проверка за дублиране с друг запис
        $nameL = plg_Search::normalizeText($rec->name);
        
        $oQuery = self::getQuery();
        self::restrictAccess($oQuery);
        
        $nQuery = clone $oQuery;
        
        $nQuery->where(array("#searchKeywords LIKE '% [#1#] %'", $nameL));
        if ($rec->country) {
            $nQuery->where(array("#country = '[#1#]'", $rec->country));
        }
        
        while ($similarRec = $nQuery->fetch()) {
            if ($rec->id && ($similarRec->id == $rec->id)) {
                continue;
            }
            
            $similarsArr[$similarRec->id] = $similarRec;
            $fieldsArr['name'] = 'name';
        }
        
        if ($rec->egn) {
            $egnNumb = preg_replace('/[^0-9]/', '', $rec->egn);
            
            if ($egnNumb) {
                $eQuery = clone $oQuery;
                $eQuery->where((array("#egn LIKE '[#1#]'", $egnNumb)));
                
                while ($similarRec = $eQuery->fetch()) {
                    if ($rec->id && ($similarRec->id == $rec->id)) {
                        continue;
                    }
                    
                    $similarsArr[$similarRec->id] = $similarRec;
                }
                $fieldsArr['egn'] = 'egn';
            }
        }
        
        
        if ($rec->email || $rec->buzEmail) {
            $emailArr = type_Emails::toArray($rec->email . ', ' . $rec->buzEmail);
            
            if (!empty($emailArr)) {
                $eQuery = clone $oQuery;
                
                $toPrev = false;
                foreach ($emailArr as $email) {
                    $eQuery->where(array("#email LIKE '%[#1#]%'", $email), $toPrev);
                    $eQuery->orWhere(array("#buzEmail LIKE '%[#1#]%'", $email));
                    
                    $toPrev = true;
                }
                
                while ($similarRec = $eQuery->fetch()) {
                    if ($rec->id && ($similarRec->id == $rec->id)) {
                        continue;
                    }
                    
                    $similarsArr[$similarRec->id] = $similarRec;
                    if ($rec->buzEmail) {
                        $fieldsArr['buzEmail'] = 'buzEmail';
                    } else {
                        $fieldsArr['email'] = 'email';
                    }
                }
            }
        }
        
        $fields = implode(',', $fieldsArr);
        
        return $similarsArr;
    }
    
    
    /**
     * Връща папката на фирмата от бизнес имейла, ако имаме достъп до нея
     *
     * @param string $email - Имейл, за който търсим
     *
     * @return int|FALSE $fodlerId - id на папката
     */
    public static function getFolderFromBuzEmail($email)
    {
        // Имейла в долния регистър
        $email = mb_strtolower($email);
        
        // Вземаме потребителя с такъв бизнес имейл
        $personRec = static::fetch(array("LOWER(#buzEmail) LIKE '%[#1#]%'", $email));
        
        // Ако има бизнес имейл и асоциирана фирма с потребителя
        if ($companyId = $personRec->buzCompanyId) {
            
            // Вземаме папката на фирмата
            $folderId = crm_Companies::forceCoverAndFolder($companyId);
            
            // Проверяваме дали имаме права за папката
            if (doc_Folders::haveRightFor('single', $folderId)) {
                
                return $folderId;
            }
        }
        
        return false;
    }
    
    
    /**
     * Връща папката на лицето от имейла, ако имаме достъп до нея
     *
     * @param string $email - Имейл, за който търсим
     *
     * @return int $fodlerId - id на папката
     */
    public static function getFolderFromEmail($email)
    {
        // Вземаме потребителя с личен имейл
        $personId = static::fetchField(array("LOWER(#email) LIKE '%[#1#]%'", $email));
        
        // Ако има такъв потребител
        if ($personId) {
            
            // Вземаме папката
            $folderId = static::forceCoverAndFolder($personId);
            
            // Ако имаме права за нея
            if (doc_Folders::haveRightFor('single', $folderId)) {
                
                return $folderId;
            }
        }
        
        return false;
    }
    
    
    /**
     * Създава папка на лице по указаните данни
     */
    public static function getPersonFolder($salutation, $name, $country, $pCode, $place, $address, $email, $tel, $website, $inCharge, $access, $shared)
    {
        $rec = new stdClass();
        $rec->salutation = $salutation;
        $rec->name = $name;
        
        // Адресни данни
        $rec->country = $country;
        $rec->pCode = $pCode;
        $rec->place = $place;
        $rec->address = $address;
        
        // Комуникации
        $rec->email = $email;
        $rec->tel = $tel;
        $rec->website = $website;
        
        // Достъп/права
        $rec->inCharge = $inCharge;
        $rec->access = $access;
        $rec->shared = $shared;
        
        $Persons = cls::get('crm_Persons');
        
        $folderId = $Persons->forceCoverAndFolder($rec);
        
        return $folderId;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc      $mvc
     * @param string        $requiredRoles
     * @param string        $action
     * @param stdClass|NULL $rec
     * @param int|NULL      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        // Никой да не може да изтрива
        if ($action == 'delete') {
            $requiredRoles = 'no_one';
        }
    }
    
    
    /**
     * След подготовка на тулбара за еденичен изглед
     */
    public function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        // Ако има профил
        if ($profileRec = crm_Profiles::fetch("#personId = '{$data->rec->id}'")) {
            
            // Ако има права за single на профила
            if (crm_Profiles::haveRightFor('single', $profileRec)) {
                
                // URL към профила
                $profileUrl = crm_Profiles::getUrl($profileRec->userId);
                
                // Добавяме бутон към профилите
                $data->toolbar->addBtn(tr('Профил'), $profileUrl, 'id=btnProfile', 'ef_icon = img/16/user-profile.png');
            }
        } else {
            
            // Ако има запис и имаме права admin
            if ($data->rec->id && haveRole('admin') && $data->rec->state != 'rejected') {
                
                // sysId на групата
                $crmId = crm_Groups::getIdFromSysId('users');
                
                // Ако е в групата на потребители
                if (keylist::isIn($crmId, $data->rec->groupList)) {
                    
                    // URL за създаване на потребител
                    $personUrl = array('core_Users', 'add', 'personId' => $data->rec->id, 'ret_url' => true);
                    
                    // Добавяме бутона
                    $data->toolbar->addBtn(tr('Потребител'), $personUrl, 'id=btnUser', 'ef_icon = img/16/user_add.png');
                }
            }
        }
    }
    
    
    /**
     * Връща пълния конкатениран адрес на контрагента
     *
     * @param int       $id            - ид на контрагент
     * @param bool      $translitarate - дали да се транслитерира адреса
     * @param bool|NULL $showCountry   - да се показвали винаги държавата или Не, NULL означава че автоматично ще се определи
     * @param bool      $showAddress   - да се показва ли адреса
     *
     * @return core_ET $tpl - адреса
     */
    public function getFullAdress($id, $translitarate = false, $showCountry = null, $showAddress = true)
    {
        expect($rec = $this->fetchRec($id));
        
        $obj = new stdClass();
        $tpl = new ET('<!--ET_BEGIN country-->[#country#]<br><!--ET_END country--> <!--ET_BEGIN pCode-->[#pCode#]<!--ET_END pCode--><!--ET_BEGIN place--> [#place#]<br><!--ET_END place--> [#address#]');
        
        // Показваме държавата само ако е различна от тази на моята компания
        if (!isset($showCountry)) {
            if ($rec->country) {
                $ourCompany = crm_Companies::fetchOurCompany();
                if ($ourCompany->country != $rec->country) {
                    $obj->country = $this->getVerbal($rec, 'country');
                }
            }
        } elseif ($showCountry === true) {
            $obj->country = $this->getVerbal($rec, 'country');
        }
        
        $Varchar = cls::get('type_Varchar');
        foreach (array('pCode', 'place', 'address') as $fld) {
            if ($rec->$fld) {
                if ($fld == 'address' && $showAddress !== true) {
                    continue;
                }
                
                $obj->$fld = $Varchar->toVerbal($rec->$fld);
                if ($translitarate === true) {
                    if ($fld != 'pCode') {
                        $obj->$fld = transliterate(tr($obj->{$fld}));
                    }
                }
            }
        }
        
        $tpl->placeObject($obj);
        
        return $tpl;
    }
    
    
    /**
     * Връща валутата по подразбиране за търговия дадения контрагент
     * в зависимост от дъжавата му
     *
     * @param int $id - ид на записа
     *
     * @return string - BGN|EUR|USD за дефолт валутата
     */
    public static function getDefaultCurrencyId($id)
    {
        $rec = self::fetch($id);
        
        // Ако контрагента няма държава, то дефолт валутата е BGN
        if (empty($rec->country)) {
            
            return 'BGN';
        }
        
        // Ако държавата му е България, дефолт валутата е 'BGN'
        if (drdata_Countries::fetchField($rec->country, 'letterCode2') == 'BG') {
            
            return 'BGN';
        }
        
        // Ако не е 'България', но е в ЕС, дефолт валутата е 'EUR'
        if (drdata_Countries::isEu($rec->country)) {
            
            return 'EUR';
        }
        
        
        // За всички останали е 'USD'
        return 'USD';
    }
    
    
    /**
     * Форсира контрагент в дадена група
     *
     * @param int    $id         -ид на продукт
     * @param string $groupSysId - sysId или ид на група
     * @param bool   $isSysId    - дали е систем ид
     */
    public static function forceGroup($id, $groupSysId, $isSysId = true)
    {
        expect($rec = static::fetch($id));
        $me = cls::get(get_called_class());
        if ($isSysId === true) {
            expect($groupId = crm_Groups::getIdFromSysId($groupSysId));
        } else {
            $groupId = $groupSysId;
            expect(crm_Groups::fetch($groupId));
        }
        
        // Ако контрагента не е включен в групата, включваме го
        if (!keylist::isIn($groupId, $rec->groupList)) {
            $groupName = crm_Groups::getTitleById($groupId);
            $rec->{$me->expandInputFieldName} = keylist::addKey($rec->{$me->expandInputFieldName}, $groupId);
            
            if (haveRole('powerUser')) {
                core_Statuses::newStatus("|Лицето е включено в група |* '{$groupName}'");
            }
            
            return static::save($rec, $me->expandInputFieldName);
        }
        
        return true;
    }
    
    
    /**
     * Връща мета дефолт мета данните на папката
     *
     * @param int $id - ид на папка
     *
     * @return array $meta - масив с дефолт мета данни
     */
    public function getDefaultMeta($id)
    {
        $rec = $this->fetchRec($id);
        
        $clientGroupId = crm_Groups::getIdFromSysId('customers');
        $supplierGroupId = crm_Groups::getIdFromSysId('suppliers');
        
        $groups = crm_Groups::getQuery();
        
        $meta = array();
        
        $catConf = core_Packs::getConfig('cat');
        
        // Ако контрагента е в група доставчици'
        if (keylist::isIn($supplierGroupId, $rec->groupList)) {
            $meta = type_Set::toArray($catConf->CAT_DEFAULT_META_IN_SUPPLIER_FOLDER);
        }
        
        if (keylist::isIn($clientGroupId, $rec->groupList)) {
            $meta1 = type_Set::toArray($catConf->CAT_DEFAULT_META_IN_CONTRAGENT_FOLDER);
            $meta = array_merge($meta, $meta1);
        }
        
        return $meta;
    }
    
    
    /**
     * Кои документи да се показват като бързи бутони в папката на корицата
     *
     * @param int $id - ид на корицата
     *
     * @return array $res - възможните класове
     */
    public function getDocButtonsInFolder_($id)
    {
        $res = array();
        
        $rec = $this->fetch($id);
        
        if (email_Outgoings::haveRightFor('add', array('folderId' => $rec->folderId))) {
            $res[] = 'email_Outgoings';
        }
        
        static $clientGroupId, $supplierGroupId, $debitGroupId, $creditGroupId;
        
        if (!isset($clientGroupId)) {
            $clientGroupId = crm_Groups::getIdFromSysId('customers');
            $supplierGroupId = crm_Groups::getIdFromSysId('suppliers');
            $debitGroupId = crm_Groups::getIdFromSysId('debitors');
            $creditGroupId = crm_Groups::getIdFromSysId('creditors');
        }
        
        $groupList = crm_Groups::getParentsArray($rec->groupList);
        
        // Ако е в група дебитори или кредитови, показваме бутон за финансова сделка
        if (in_array($debitGroupId, $groupList) || in_array($creditGroupId, $groupList)) {
            $res[] = (object)array('class' => 'findeals_Deals');
        }
        
        // Ако е в група на клиент, показваме бутона за продажба
        if (in_array($clientGroupId, $groupList)) {
            $res[] = (object)array('class' => 'sales_Sales', 'url' => array('sales_Sales', 'autoCreateInFolder', 'folderId' => $rec->folderId, 'ret_url' => true));
            $res[] = (object)array('class' => 'sales_Quotations', 'url' => array('sales_Quotations', 'autoCreateInFolder', 'folderId' => $rec->folderId, 'ret_url' => true));
        }
        
        // Ако е в група на достачик, показваме бутона за покупка
        if (in_array($supplierGroupId, $groupList)) {
            $res[] = (object)array('class' => 'purchase_Purchases', 'url' => array('purchase_Purchases', 'autoCreateInFolder', 'folderId' => $rec->folderId, 'ret_url' => true));
            $res[] = (object)array('class' => 'purchase_Quotations', 'url' => array('purchase_Quotations', 'autoCreateInFolder', 'folderId' => $rec->folderId, 'ret_url' => true), 'caption' => 'Оферта от доставчик');
        }
        
        return $res;
    }
    
    
    /**
     * Връща мета дефолт параметрите със техните дефолт стойностти, които да се добавят във формата на
     * универсален артикул, създаден в папката на корицата
     *
     * @param int $id - ид на корицата
     *
     * @return array $params - масив с дефолтни параметри И техните стойности
     *               <ид_параметър> => <дефолтна_стойност>
     */
    public function getDefaultProductParams($id)
    {
        return array();
    }
    
    
    /**
     * След подготовка на полетата за импортиране
     *
     * @param crm_Persons $mvc
     * @param array       $fields
     */
    public static function on_AfterPrepareImportFields($mvc, &$fields)
    {
        crm_Companies::on_AfterPrepareImportFields($mvc, $fields);
        
        if ($fields[$mvc->expandInputFieldName]) {
            $fields[$mvc->expandInputFieldName]['type'] = 'keylist(mvc=crm_Groups,select=name,makeLinks,where=#allow !\\= \\\'companies\\\' AND #state !\\= \\\'rejected\\\')';
        }
    }
    
    
    /**
     * След подготовка на записите за експортиране
     *
     * @param crm_Companies $mvc
     * @param array         $recs
     */
    public static function on_AfterPrepareExportRecs($mvc, &$recs)
    {
        // Ограничаваме данните, които ще се експортират от лицата, до които нямаме достъп
        $query = $mvc->getQuery();
        
        $mvc->restrictAccess($query, null, false);
        
        $restRecs = $query->fetchAll();
        
        foreach ((array) $recs as $key => $rec) {
            if (isset($restRecs[$key])) {
                continue;
            }
            
            $nRec = new stdClass();
            $nRec->id = $rec->id;
            $nRec->name = $rec->name;
            $nRec->country = $rec->country;
            $nRec->pCode = $rec->pCode;
            $nRec->place = $rec->place;
            $nRec->buzCompanyId = $rec->buzCompanyId;
            
            $recs[$key] = $nRec;
        }
    }
    
    
    /**
     * Преди записване на в модела
     *
     * @param crm_Persons $mvc
     * @param stdClass    $rec
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
        // id на държавата
        if (isset($rec->country)) {
            $rec->country = drdata_Countries::getIdByName($rec->country);
        }
        
        // id на групите
        if (isset($rec->buzCompanyId)) {
            $companyName = trim($rec->buzCompanyId);
            $rec->buzCompanyId = crm_Companies::fetchField(array("#name = '[#1#]'", $companyName), 'id');
        }
        
        // id на групите
        if (isset($rec->buzLocationId)) {
            $locationTitle = trim($rec->buzLocationId);
            $locationTitle = mb_strtolower($locationTitle);
            $rec->buzLocationId = crm_Locations::fetchField(array("LOWER(#title) = '[#1#]'", $locationTitle), 'id');
        }
        
        // Проверка дали има дублиращи се записи
        $query = $mvc->getQuery();
        if ($egn = trim($rec->egn)) {
            $query->where(array("#egn = '[#1#]'", $egn));
        }
        
        if ($name = $rec->name) {
            $query->orWhere(array("#name = '[#1#]'", $name));
            
            $or = false;
            if ($tel = trim($rec->tel)) {
                $query->where(array("#tel = '[#1#]'", $tel), $or);
                $or = true;
            }
            
            if ($mobile = trim($rec->mobile)) {
                $query->where(array("#mobile = '[#1#]'", $mobile), $or);
            }
        }
        
        $query->orderBy('#egn', 'DESC');
        $query->orderBy('#tel', 'DESC');
        $query->orderBy('#mobile', 'DESC');
        $query->orderBy('#state', 'ASC');
        
        $query->limit(1);
        $query->show('id');
        
        if ($oRec = $query->fetch()) {
            $rec->id = $oRec->id;
        }

        // Ако има избрана група от csv файла
        if (isset($rec->groups)) {
            $delimiter = csv_Lib::getDevider($rec->groups);

            $groupArr = explode($delimiter, $rec->groups);

            $groupIdArr = array();

            $missingGroupArr = array();
            foreach ($groupArr as $groupName) {
                $groupName = trim($groupName);

                if (!$groupName) {
                    continue;
                }

                $force = false;
                if (haveRole('debug')) {
                    $force = true;
                }
                $groupId = crm_Groups::force($groupName, null, $force);

                if (!isset($groupId)) {
                    $missingGroupArr[] = $groupName;
                }

                $groupIdArr[$groupId] = $groupId;
            }

            if (!empty($missingGroupArr)) {
                $groupName = implode(', ', $missingGroupArr);
                $rec->__errStr = "Липсваща група при импортиране: {$groupName}";
                self::logNotice($rec->__errStr);

                return false;
            }

            if ($rec->groupListInput) {
                if (!empty($groupIdArr)) {
                    $rec->groupListInput = type_Keylist::merge($rec->groupListInput, type_Keylist::fromArray($groupIdArr));
                }
            } else {
                if (!empty($groupIdArr)) {
                    $rec->groupListInput = type_Keylist::fromArray($groupIdArr);
                }
            }
        }
    }
    
    
    /**
     * Лицата от група 'Служители'
     *
     * @param bool       $withAccess - да се филтрира ли по права за редакция или не
     * @param bool|false $hrCode     - null за всички, bool за дали да са с кодове като човешки ресурси или не
     *
     * @return array $options        - опции
     */
    public static function getEmployeesOptions($withAccess = false, $hrCodes = null)
    {
        $options = array();
        $emplGroupId = crm_Groups::getIdFromSysId('employees');
        
        $query = self::getQuery();
        $query->like('groupList', "|{$emplGroupId}|");
        
        // Ако е указано, само тези които нямат кодове в производствените ресурси
        if (!is_null($hrCodes)) {
            $hrQuery = planning_Hr::getQuery();
            $hrQuery->show('personId');
            $hrIds = arr::extractValuesFromArray($hrQuery->fetchAll(), 'personId');
            if ($hrCodes === true) {
                $query->in('id', $hrIds);
            } else {
                $query->notIn('id', $hrIds);
            }
        }
        
        while ($rec = $query->fetch()) {
            if ($withAccess === true && !crm_Persons::haveRightFor('edit', $rec->id)) {
                continue;
            }
            
            // Показва се името с ид-то след него заради служителите с еднакви имена
            $options[$rec->id] = self::getVerbal($rec, 'name') . " ({$rec->id})";
        }
        
        if (countR($options)) {
            $options = array('e' => (object) array('group' => true, 'title' => tr('Служители'))) + $options;
        }
        
        return $options;
    }
    
    
    /**
     * Дали артикулът създаден в папката трябва да е публичен (стандартен) или не
     *
     * @param mixed $id - ид или запис
     *
     * @return string - public|private|template - Стандартен / Нестандартен / Шаблон
     */
    public function getProductType($id)
    {
        return 'private';
    }
    
    
    /**
     * Подготовка на опции за key2
     */
    public static function getSelectArr($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        $query = self::getQuery();
        $query->orderBy('modifiedOn', 'DESC');
        
        
        $viewAccess = true;
        if ($params['restrictViewAccess'] == 'yes') {
            $viewAccess = false;
        }
        
        $me = cls::get(get_called_class());
        $me->restrictAccess($query, null, $viewAccess);
        
        if (!$includeHiddens) {
            $query->where("#state != 'rejected' AND #state != 'closed'");
        }
        
        if ($params['where']) {
            $query->where($params['where']);
        }
        
        if (is_array($onlyIds)) {
            if (!countR($onlyIds)) {
                
                return array();
            }
            
            $ids = implode(',', $onlyIds);
            expect(preg_match("/^[0-9\,]+$/", $onlyIds), $ids, $onlyIds);
            
            $query->where("#id IN (${ids})");
        } elseif (ctype_digit("{$onlyIds}")) {
            $query->where("#id = ${onlyIds}");
        }
        
        $titleFld = $params['titleFld'];
        $query->XPR('searchFieldXpr', 'text', "LOWER(CONCAT(' ', #{$titleFld}))");
        
        if ($q) {
            if ($q[0] == '"') {
                $strict = true;
            }
            
            $q = trim(preg_replace("/[^a-z0-9\p{L}]+/ui", ' ', $q));
            
            $q = mb_strtolower($q);
            
            if ($strict) {
                $qArr = array(str_replace(' ', '.*', $q));
            } else {
                $qArr = explode(' ', $q);
            }
            
            $pBegin = type_Key2::getRegexPatterForSQLBegin();
            foreach ($qArr as $w) {
                $query->where(array("#searchFieldXpr REGEXP '(" . $pBegin . "){1}[#1#]'", $w));
            }
        }
        
        if ($limit) {
            $query->limit($limit);
        }
        
        $query->show('id, buzCompanyId, ' . $titleFld);
        
        $res = array();

        if ($params['group']) {
            $gId = crm_Groups::getIdFromSysId($params['group']);
            expect($gId);

            $query->likeKeylist('groupList', $gId);
        }

        while ($rec = $query->fetch()) {
            $str = trim($rec->{$titleFld});
            
            if ($rec->buzCompanyId) {
                $str .= ' - ' . crm_Companies::fetchField($rec->buzCompanyId, 'name');
            }
            
            $str .= " ({$rec->id})";
            
            $res[$rec->id] = $str;
        }

        return $res;
    }
    
    
    /**
     * Добавя ключови думи за държавата и на bg и на en
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        $res = drdata_Countries::addCountryInBothLg($rec->country, $res);
    }
    
    
    /**
     * Дали лицето е в подадената група
     *
     * @param int    $id
     * @param string $groupSysId
     *
     * @return bool
     */
    public static function isInGroup($id, $groupSysId)
    {
        $employeeId = crm_Groups::getIdFromSysId($groupSysId);
        if (keylist::isIn($employeeId, crm_Persons::fetchField($id, 'groupList'))) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * След взимане на иконката за единичния изглед
     *
     * @param core_Mvc $mvc
     * @param string   $res
     * @param int      $id
     */
    public static function on_AfterGetSingleIcon($mvc, &$res, $id)
    {
        if (core_Users::isContractor()) {
            
            return;
        }
        
        if ($extRec = crm_ext_ContragentInfo::getByContragent($mvc->getClassId(), $id)) {
            if ($extRec->overdueSales == 'yes') {
                $res = 'img/16/stop-sign.png';
            }
        }
    }
    
    
    /**
     * След взимане на заглавието за единичния изглед
     *
     * @param core_Mvc $mvc
     * @param string   $res
     * @param int      $id
     */
    public static function on_AfterGetSingleTitle($mvc, &$res, $id)
    {
        if (core_Users::isContractor()) {
            
            return;
        }
        
        if ($extRec = crm_ext_ContragentInfo::getByContragent($mvc->getClassId(), $id)) {
            if ($extRec->overdueSales == 'yes') {
                $res = "<span class='dangerTitle'>{$res}</span>";
            }
        }
    }
    
    
    /**
     * Обновяване на адресните данни на фирмата
     *
     * @param int         $folderId  - ид на папка
     * @param string      $name      - име на папката
     * 
     * @param string      $vatId     - ДДС №
     * @param string      $egn       - ЕГН
     * @param int         $countryId - ид на държава
     * @param string|NULL $pCode     - п. код
     * @param string|NULL $place     - населено място
     * @param string|NULL $address   - адрес
     *
     * @return void
     */
    public static function updateContactDataByFolderId($folderId, $name, $vatId, $egn, $countryId, $pCode, $place, $address)
    {
        $saveFields = array();
        $rec = self::fetch("#folderId = {$folderId}");
        $arr = array('name' => $name, 'vatId' => $vatId, 'country' => $countryId, 'egn' => $egn, 'pCode' => $pCode, 'place' => $place, 'address' => $address);
        
        // Обновяване на зададените полета
        foreach ($arr as $name => $value) {
            if (!empty($value) && $rec->{$name} != $value) {
                $rec->{$name} = $value;
                $saveFields[] = $name;
            }
        }
        
        // Ако има полета за обновяване
        if (countR($saveFields)) {
            self::save($rec, $saveFields);
        }
    }
}
