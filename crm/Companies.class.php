<?php


/**
 * Отдалечен сървър за генериране на лого на фирма
 */
defIfNot('CRM_REMOTE_COMPANY_LOGO_CREATOR', 'https://experta.bg/api_Companies/getLogo/apiKey/crm123/');


/**
 * Мениджър на фирмите
 *
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.11
 *
 * @method restrictAccess(core_Query $query, NULL|integer $userId = NULL, boolean $viewAccess = TRUE)
 */
class crm_Companies extends core_Master
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
        
        // Интерфейс за счетоводни пера, отговарящи на фирми
        'crm_CompanyAccRegIntf',
        
        // Интерфейс за всякакви счетоводни пера
        'acc_RegisterIntf',
        
        // Интерфейс за корица на папка
        'doc_FolderIntf',
        
        // Интерфейс за данните на контрагента
        'doc_ContragentDataIntf',
        
        // Интерфейс за корица на папка в която може да се създава артикул
        'cat_ProductFolderCoverIntf',
    );
    
    
    /**
     * Заглавие
     */
    public $title = 'Фирми';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Фирма';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'callcenter=0.1';
    
    
    /**
     * Икона на единичния обект
     */
    public $singleIcon = 'img/16/office-building.png';
    
    
    /**
     *
     * @see plg_Select
     */
    public $doWithSelected = 'export=Експортиране';
    
    
    /**
     * Полета за експорт
     */
    public $exportableCsvFields = 'name,vatId,uicId,eori,country,pCode,place,address,email,tel,fax,website,info,logo,folderName,nkid,groupList';
    
    /**
     * Класове за автоматично зареждане
     */
    public $loadList = 'plg_Created, plg_Modified, plg_RowTools2, plg_State, 
                     Groups=crm_Groups, crm_Wrapper, crm_AlphabetWrapper, plg_SaveAndNew, plg_PrevAndNext,
                     plg_Sorting, recently_Plugin, plg_Search, plg_Rejected,doc_FolderPlg, bgerp_plg_Groups, drdata_plg_Canonize, plg_Printing,
                     acc_plg_Registry, doc_plg_Close, plg_LastUsedKeys,plg_Select,bgerp_plg_Import, drdata_PhonePlg,bgerp_plg_Export,
                     plg_ExpandInput, core_UserTranslatePlg, callcenter_AdditionalNumbersPlg, crm_ContragentGroupsPlg';
    
    
    /**
     * Полетата, които ще видим в таблицата
     */
    public $listFields = 'nameList=Фирма,phonesBox=Комуникации,addressBox=Адрес,name=';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';


    /**
     * Кои полета да се канонизират и запишат в друг модел
     *
     * @see drdata_plg_Canonize
     */
    public $canonizeFields = 'uicId=uic';


    /**
     * Кой може да добавя?
     */
    public $canClose = 'crm,ceo';
    
    
    /**
     * Полета по които се прави пълнотекстово търсене от плъгина plg_Search
     */
    public $searchFields = 'name,pCode,place,country,folderName,email,tel,fax,website,vatId,info,uicId,id,eori';
    
    
    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    public $fetchFieldsBeforeDelete = 'id,name';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    public $canReports = 'ceo,sales,purchase,acc';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    public $canAddacclimits = 'ceo,salesMaster,purchaseMaster,accMaster,accLimits';
    
    
    /**
     * Кой  може да пише?
     */
    public $canWrite = 'powerUser';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'powerUser';
    
    
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
     * Поле, в което да се постави връзка към папката в листови изглед
     */
    public $listFieldForFolderLink = 'folder';
    
    
    /**
     * Детайли, на модела
     */
    public $details = 'AccReports=acc_ReportDetails,CompanyExpandData=crm_Persons,ContragentLocations=crm_Locations,
                    ContragentBankAccounts=bank_Accounts,CourtReg=crm_ext_CourtReg,CommerceDetails=crm_CommerceDetails,ContragentUnsortedFolders=doc_UnsortedFolders';
    
    
    /**
     * По кои сметки ще се правят справки
     */
    public $balanceRefAccounts = '1511,1512,1513,1514,1521,1522,1523,1524,153,159,323,401,402,403,404,405,406,409,411,412,413,414,415,419';
    
    
    /**
     * По кой итнерфейс ще се групират сметките
     */
    public $balanceRefGroupBy = 'crm_ContragentAccRegIntf';
    
    
    /**
     * @todo Чака за документация...
     */
    public $features = 'place, country';
    
    
    /**
     * @var crm_Groups
     */
    public $Groups;
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'crm/tpl/SingleCompanyLayout.shtml';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'groupList';
    
    
    /**
     * Полето, което ще се разширява
     *
     * @see plg_ExpandInput
     */
    public $expandFieldName = 'groupList';
    
    
    /**
     * Как се казва полето за държава на контрагента
     */
    public $countryFieldName = 'country';
    
    
    /**
     *
     * @see type_Key::filterByGroup
     */
    public $groupsField = 'groupList';
    
    
    /**
     * Кои полета да се записват в номерата
     * @var array
     * @see callcenter_AdditionalNumbersPlg
     */
    public $updateNumMap = array('tel' => 'tel', 'fax' => 'fax');
    
    
    /**
     * Предефинирани подредби на листовия изглед
     */
    public $listOrderBy = array(
        'alphabetic' => array('Азбучно', '#nameT=ASC'),
        'last' => array('Последно добавени', '#createdOn=DESC', 'createdOn=Създаване->На,createdBy=Създаване->От'),
        'modified' => array('Последно променени', '#modifiedOn=DESC', 'modifiedOn=Модифициране->На,modifiedBy=Модифициране->От'),
        'vatId' => array('Данъчен №', '#vatId=DESC', 'vatId=Данъчен №'),
        'pCode' => array('Пощенски код', '#pCode=DESC', 'pCode=П. код'),
        'website' => array('Сайт/Блог', '#website', 'website=Сайт/Блог'),
    );
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        // Име на фирмата
        $this->FLD('name', 'varchar(255,ci)', 'caption=Фирма,class=contactData,mandatory,remember=info,silent,export=Csv, translate=user|tr|transliterate');
        $this->FNC('nameList', 'varchar', 'sortingLike=name');
        
        // Данъчен номер на фирмата
        $this->FLD('vatId', 'drdata_VatType', 'caption=ДДС (VAT) №,remember=info,class=contactData,export=Csv,silent');
        $this->FLD('uicId', 'drdata_type_Uic(26)', 'caption=Национален №,remember=info,class=contactData,export=Csv,silent');
        $this->FLD('eori', 'drdata_type_Eori', 'caption=EORI №,remember=info,class=contactData,export=Csv,silent');
        
        // Адресни данни
        $this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Държава,remember,class=contactData,mandatory,export=Csv,silent,removeAndRefreshForm');
        $this->FLD('pCode', 'varchar(16)', 'caption=П. код,recently,class=pCode,export=Csv');
        $this->FLD('place', 'varchar(64)', 'caption=Град,class=contactData,hint=Населено място: град или село и община,export=Csv');
        $this->FLD('address', 'varchar(255)', 'caption=Адрес,class=contactData,export=Csv');
        
        // Комуникации
        $this->FLD('email', 'emails', 'caption=Имейли,class=contactData,export=Csv');
        $this->FLD('tel', 'drdata_PhoneType(type=tel,unrecognized=warning)', 'caption=Телефони,class=contactData,silent,export=Csv');
        $this->FLD('fax', 'drdata_PhoneType(type=fax)', 'caption=Факс,class=contactData,silent,export=Csv');
        $this->FLD('website', 'url', 'caption=Web сайт,class=contactData,export=Csv');
        
        // Вземаме конфига
        $visibleNKID = crm_Setup::get('VISIBLE_NKID');
        
        // Ако полето е обозначено за оказване
        if ($visibleNKID == 'yes') {
            // Добавяме поле във формата
            $this->FLD('nkid', 'key(mvc=bglocal_NKID, select=title,allowEmpty=true)', 'caption=НКИД,after=folderName, hint=Номер по НКИД');
        }
        
        // Допълнителна информация
        $this->FLD('info', 'richtext(bucket=crmFiles, passage=Общи)', 'caption=Бележки,height=150px,class=contactData,export=Csv');
        $this->FLD('logo', 'fileman_FileType(bucket=pictures)', 'caption=Лого,export=Csv');
        $this->FLD('folderName', 'varchar', 'caption=Име на папка');
        
        // В кои групи е?
        $this->FLD('groupList', 'keylist(mvc=crm_Groups,select=name,makeLinks,where=#allow !\\= \\\'persons\\\'AND #state !\\= \\\'rejected\\\',classLink=group-link)', 'caption=Групи->Групи,remember,silent,export=Csv');
        
        // Състояние
        $this->FLD('state', 'enum(active=Вътрешно,closed=Нормално,rejected=Оттеглено)', 'caption=Състояние,value=closed,notNull,input=none');
        
        // Индекси
        $this->setDbIndex('name');
        $this->setDbIndex('country');
        $this->setDbIndex('email');
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('users', 'users(rolesForAll = officer|manager|ceo, rolesForTeams = officer|manager|ceo|executive)', 'caption=Потребител,input,silent,autoFilter');
        
        // Вземаме стойността по подразбиране, която може да се покаже
        $default = $data->listFilter->getField('users')->type->fitInDomain('all_users');
        
        // Задаваме стойността по подразбиране
        $data->listFilter->setDefault('users', $default);
        
        // Задаваме стойността по подразбиране
        $data->listFilter->setDefault('nkid', '');
        
        $options = array();
        
        // Подготовка на полето за подредба
        foreach ($mvc->listOrderBy as $key => $attr) {
            $options[$key] = $attr[0];
        }
        $orderType = cls::get('type_Enum');
        $orderType->options = $options;
        $data->listFilter->FNC('order', $orderType, 'caption=Подредба,input,silent,autoFilter');
        
        // Филтриране по група
        $data->listFilter->FNC(
            'groupId',
            'key(mvc=crm_Groups,select=name,allowEmpty)',
            'placeholder=Всички групи,caption=Група,input,silent,autoFilter'
        );
        $data->listFilter->FNC('alpha', 'varchar', 'caption=Буква,input=hidden,silent');
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета
        // на модела ще се появят
        $data->listFilter->showFields = 'search,users,order,groupId';
        $data->listFilter->input('alpha,users,search,order,groupId', 'silent');
        
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
            if (strpos($orderCond, '#nameT') !== false) {
                $data->query->XPR('nameT', 'varchar', "TRIM(LEADING ' ' FROM TRIM(LEADING '''' FROM TRIM(LEADING '\"' FROM #name)))");
            }
            $data->query->orderBy($orderCond);
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
                    if ($data->rows[$rec->id]->folderName) {
                        $data->rows[$rec->id]->nameList .= $data->rows[$rec->id]->folderName;
                    } else {
                        $data->rows[$rec->id]->nameList .= $data->rows[$rec->id]->titleNumber;
                    }
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
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if ($data->toolbar->removeBtn('btnAdd')) {
            self::addNewCompanyBtn2Toolbar($data->toolbar, $data->listFilter);
        }
    }
    
    
    /**
     * Добавя бутон за създаване на нова фирма към тулбар, взимайки под внимание филтър
     * 
     * @param core_Toolbar $toolbar
     * @param core_Form $listFilter
     * 
     * @return void
     */
    public static function addNewCompanyBtn2Toolbar(core_Toolbar &$toolbar,core_Form $listFilter)
    {
        $addCompanyUrl = array('crm_Companies', 'add');
        if($groupId = $listFilter->rec->groupId){
            $addCompanyUrl["groupList"] = $groupId;
        }
        
        $searchString = $listFilter->rec->search;
        
        // Ако има въведен стринг за търсене
        if(!empty($searchString)){
            list($status) = cls::get('drdata_Vats')->checkStatus($searchString);
            
            if($status == 'valid'){
                
                // и е валиден ДДС №, подава се за номер на новата фирма
                $addCompanyUrl['vatId'] = $searchString;
            } elseif($status == 'bulstat' || (ctype_digit($searchString) && strlen($searchString) >= 5)){
                
                // и е дълго число, подава се като нац. № на новата фирма
                $addCompanyUrl['uicId'] = $searchString;
            } else {
                
                // Ако не е от горните се добавя към името на новата фирма
                $addCompanyUrl['name'] = $searchString;
            }
        }
        
        $toolbar->addBtn('Нова фирма', $addCompanyUrl, 'ef_icon=img/16/office-building-add.png', 'title=Създаване на нова визитка на фирма');
    }
    
    
    /**
     * Модифициране на edit формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = $data->form;
        
        if (empty($form->rec->name)) {
            $form->setField('vatId', 'removeAndRefreshForm=name|address|pCode|country|place');
            $form->setField('uicId', 'removeAndRefreshForm=name|address|pCode|country|place');
            
            if(empty($form->rec->name)){
                $cDataSource = !empty($form->rec->vatId) ? $form->rec->vatId : $form->rec->uicId;
                
                // Ако не е въведено име, но има валиден ват попълват се адресните данни от него
                if(!empty($cDataSource)){
                    if($cData = self::getCompanyDataFromString($cDataSource)){
                        
                        foreach (array('name', 'country', 'pCode', 'place', 'address', 'vatId', 'uicId') as $cFld){
                            if(!empty($cData->{$cFld})){
                                $form->setDefault($cFld, $cData->{$cFld});
                            }
                        }
                    }
                }
            }
            
            // Дефолтната държава е същата, като на "Моята фирма"
            $myCompany = self::fetchOwnCompany();
            $form->setDefault('country', $myCompany->countryId);
        }
        
        // Ако сме в тесен режим
        if (Mode::is('screenMode', 'narrow')) {
            
            // Да има само 2 колони
            $data->form->setField($mvc->expandInputFieldName, array('maxColumns' => 2));
        }
        
        $mvc->autoChangeFields($form);
    }
    
    
    /**
     * Преди модифициране на edit формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    protected static function on_BeforePrepareEditForm($mvc, &$res, $data)
    {
        if ($country = Request::get('country')) {
            if (($tel = Request::get('tel')) || ($fax = Request::get('fax'))) {
                $code = drdata_Countries::fetchField($country, 'telCode');
                if ($tel) {
                    $tel1 = drdata_PhoneType::setCodeIfMissing($tel, $code);
                    if ($tel1 != $tel) {
                        Request::push(array('tel' => $tel1));
                    }
                }
                if ($fax) {
                    $fax1 = drdata_PhoneType::setCodeIfMissing($fax, $code);
                    if ($fax1 != $fax) {
                        Request::push(array('tel' => $fax1));
                    }
                }
            }
        }
    }
    
    
    /**
     * Добавя стойности на полетата за автоматична промяна
     *
     * @param core_Form $form
     */
    public static function autoChangeFields($form)
    {
        Request::setProtected('AutoChangeFields');
        
        if ($changeFieldsArr = Request::get('AutoChangeFields')) {
            $changeFieldsArr = unserialize($changeFieldsArr);
            
            if ($changeFieldsArr) {
                
                $oldValArr = array();
                foreach ($changeFieldsArr as $fName => $fVal) {
                    if ($form->rec->{$fName} == $fVal) {
                        continue;
                    }
                    
                    $oldValArr[$fName] = $form->rec->{$fName};
                    $form->rec->{$fName} = $fVal;
                }
                
                if ($oldValArr) {
                    foreach ($oldValArr as $fName => $fVal) {
                        if (!$form->fields[$fName]) {
                            continue;
                        }
                        
                        if ($form->fields[$fName]->type instanceof type_Key || $form->fields[$fName]->type instanceof type_Keylist) {
                            $form->fields[$fName]->unit = '|*(' . $form->fields[$fName]->type->toVerbal($fVal) . ')';
                        }
                        
                        $form->fields[$fName]->hint = 'Предишна стойност|*: ' . $fVal;
                        $form->fields[$fName]->class .= ' flashElem';
                    }
                }
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
            $similarCompany = '';
            foreach ($similarsArr as $similarRec) {
                $class = '';
                
                if ($similarRec->state == 'rejected') {
                    $class = "class='state-rejected'";
                } elseif ($similarRec->state == 'closed') {
                    $class = "class='state-closed'";
                }
                
                $similarCompany .= "<li {$class}>";
                
                $singleUrl = array();
                $otherParamArr = array();
                
                if ($haveRightForSingle = self::haveRightFor('single', $similarRec->id)) {
                    $singleUrl = array(get_called_class(), 'single', $similarRec->id);
                    $otherParamArr['target'] = '_blank';
                }
                
                $similarCompany .= ht::createLink(self::getVerbal($similarRec, 'name'), $singleUrl, null, $otherParamArr);
                
                if ($haveRightForSingle && $similarRec->vatId) {
                    $similarCompany .= ', ' . self::getVerbal($similarRec, 'vatId');
                }
                
                if (trim($similarRec->place)) {
                    $similarCompany .= ', ' . self::getVerbal($similarRec, 'place');
                } else {
                    $similarCompany .= ', ' . self::getVerbal($similarRec, 'country');
                }
                
                if (!$haveRightForSingle) {
                    $similarCompany .= ' - ' . crm_Profiles::createLink($similarRec->inCharge);
                }
                
                $similarCompany .= '</li>';
            }
            
            $sledniteFirmi = (countR($similarsArr) == 1) ? 'следната фирма' : 'следните фирми';
            
            $resStr = "Възможно е дублиране със {$sledniteFirmi}|*: <ul>{$similarCompany}</ul>";
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
        
        $fieldsArr = array();
        
        $nameL = '#' . mb_strtolower($rec->name) . '#';
        
        static $companyTypesArr = array();
        
        if (empty($companyTypesArr)) {
            $companyTypes = getFileContent('drdata/data/companyTypes.txt');
            $companyTypesArr = explode("\n", $companyTypes);
            arr::combine($companyTypesArr, array('ет','еоод','сд', 'ад', 'еад'));
        }
        
        foreach ($companyTypesArr as $word) {
            $word = trim($word, '|');
            $nameL = str_replace(array("#{$word}", "{$word}#"), array('', ''), $nameL);
        }
        
        $nameL = trim(str_replace('#', '', $nameL));
        
        $oQuery = self::getQuery();
        self::restrictAccess($oQuery);
        
        $nQuery = clone $oQuery;
        $nQuery->where(array("CONCAT(' ', LOWER(#name), ' ') LIKE '% [#1#] %'", $nameL));
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
        
        $vatNumb = preg_replace('/[^0-9]/', '', $rec->vatId);
        
        if ($vatNumb) {
            $vQuery = clone $oQuery;
            $vQuery->where(array("#vatId LIKE '%[#1#]%'", $vatNumb));
            
            while ($similarRec = $vQuery->fetch()) {
                if ($rec->id && ($similarRec->id == $rec->id)) {
                    continue;
                }
                
                $similarsArr[$similarRec->id] = $similarRec;
                $fieldsArr['vatId'] = 'vatId';
            }
        }
        
        if ($rec->email) {
            $emailArr = type_Emails::toArray($rec->email);
            
            if (!empty($emailArr)) {
                foreach ($emailArr as $email) {
                    $folderId = email_Router::route($email, null, email_Router::RuleFrom, false);
                    
                    if ($folderId) {
                        $fRec = doc_Folders::fetch($folderId);
                        
                        if ($fRec->coverClass == core_Classes::getId('crm_Companies')) {
                            if ($rec->id && ($fRec->coverId == $rec->id)) {
                                continue;
                            }
                            
                            $similarsArr[$fRec->coverId] = self::fetch($fRec->coverId);
                            $fieldsArr['email'] = 'email';
                        }
                    }
                }
            }
        }
        
        $fields = implode(',', $fieldsArr);
        
        return $similarsArr;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;

        if ($form->isSubmitted()) {

            // Проверяваме да няма дублиране на записи
            $fields = '';
            $resStr = static::getSimilarWarningStr($form->rec, $fields);
            if ($resStr) {
                $form->setWarning($fields, $resStr);
            }
            
            if ($rec->place) {
                $rec->place = bglocal_Address::canonizePlace($rec->place);
            }
            
            if ($rec->regCompanyFileYear && $rec->regDecisionDate) {
                $dYears = abs($rec->regCompanyFileYear - (int) $rec->regDecisionDate);
                
                if ($dYears > 1) {
                    $form->setWarning('regCompanyFileYear,regDecisionDate', 'Годината на регистрацията на фирмата и фирменото дело се различават твърде много.');
                }
            }
            
            if ($rec->vatId) {
                if (empty($rec->uicId)) {
                    $rec->uicId = drdata_Vats::getUicByVatNo($rec->vatId);
                }
                $Vats = cls::get('drdata_Vats');
                $rec->vatId = $Vats->canonize($rec->vatId);
            }

            if(!empty($rec->uicId)){
                drdata_type_Uic::check($form, $rec->uicId, $rec->country);
            }
        }
    }
    
    
    /**
     * Манипулации със заглавието
     *
     * @param core_Mvc $mvc
     * @param core_Et  $tpl
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListTitle($mvc, &$tpl, $data)
    {
        if ($data->listFilter->rec->groupId) {
            $data->title = "Фирми в групата|* \"<b style='color:green'>|" .
            $mvc->Groups->getTitleById($data->listFilter->rec->groupId) . '|*</b>"';
        } elseif ($data->listFilter->rec->search) {
            $data->title = "Фирми, отговарящи на филтъра|* \"<b style='color:green'>" .
            type_Varchar::escape($data->listFilter->rec->search) .
            '</b>"';
        } elseif ($data->listFilter->rec->alpha) {
            if ($data->listFilter->rec->alpha[0] == '0') {
                $data->title = 'Фирми, които започват с не-буквени символи';
            } else {
                $data->title = "Фирми, започващи с буквите|* \"<b style='color:green'>{$data->listFilter->rec->alpha}</b>\"";
            }
        } else {
            $data->title = null;
        }
    }


    /**
     * Изпълнява се преди преобразуването към вербални стойности на полетата на записа
     */
    protected static function on_BeforeRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        $mvc->setFieldTypeParams('uicId', array('countryId' => $rec->country));
    }


    /**
     * Промяна на данните от таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     * @param stdClass $fields
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        $row->nameList = $mvc->getLinkToSingle($rec->id, 'name');
        
        if ($fields['-single']) {
            // Fancy ефект за картинката
            $Fancybox = cls::get('fancybox_Fancybox');
            
            $tArr = array(200, 150);
            $mArr = array(600, 450);
            
            if ($rec->logo) {
                $row->image = $Fancybox->getImage($rec->logo, $tArr, $mArr);
            } elseif (!Mode::is('screenMode', 'narrow')) {
                $row->image = '<img class="hgsImage" src=' . sbf('img/noimage120.gif') . " alt='no image'>";
            }
            
            $VatType = new drdata_VatType();
            $row->vat = $VatType->toVerbal($rec->vatId);
            
            if ($rec->folderName) {
                $row->title = $row->name;
            }
            
            // Разширяване на $row
            crm_ext_ContragentInfo::extendRow($mvc, $row, $rec);
        }
        
        // Дали има права single' а на тазу фирма
        $canSingle = static::haveRightFor('single', $rec);
        
        $row->country = $mvc->getVerbal($rec, 'country');
        
        $pCode = $mvc->getVerbal($rec, 'pCode');
        $place = $mvc->getVerbal($rec, 'place');
        $address = $mvc->getVerbal($rec, 'address');
        
        $row->addressBox .= $pCode ? "{$pCode} " : '';
        $row->addressBox .= $place;
        
        // Ако имаме права за сингъл
        if ($canSingle) {
            $row->addressBox .= $address ? "<br/>{$address}" : '';
            
            $tel = $mvc->getVerbal($rec, 'tel');
            $fax = $mvc->getVerbal($rec, 'fax');
            $eml = $mvc->getVerbal($rec, 'email');
            
            // phonesBox
            $row->phonesBox .= $tel ? "<div class='crm-icon telephone'>{$tel}</div>" : '';
            $row->phonesBox .= $fax ? "<div class='crm-icon fax'>{$fax}</div>" : '';
            $row->phonesBox .= $eml ? "<div class='crm-icon email'>{$eml}</div>" : '';
            $row->phonesBox = "<div style='max-width:400px;'>{$row->phonesBox}</div>";
        } else {
            
            // Добавяме линк към профила на потребителя, който е inCharge на визитката
            $row->phonesBox = tr('Отговорник') . ': ' . crm_Profiles::createLink($rec->inCharge);
        }
        
        $ownCompany = crm_Companies::fetchOurCompany();
        if ($ownCompany->country != $rec->country) {
            $country = $row->country;
        } else {
            $currentCountry = $mvc->getVerbal($rec, 'place');
            $country = $currentCountry;
        }
        
        $row->nameList = '<div class="namelist">'. $row->nameList . "<span class='icon'>". $row->folder .'</span></div>';
        $row->id = $mvc->getVerbal($rec, 'id');
        $row->nameList .= ($country ? "<div style='font-size:0.8em;margin-bottom:2px;margin-left: 4px;'>{$country}</div>" : '');
        
        if (!$row->title) {
            $row->title .= $mvc->getTitleById($rec->id);
        }
        
        if ($rec->folderName) {
            $row->folderName = "<div style='color:blue;'>" . $mvc->getVerbal($rec, 'folderName') . '</div>';
        }
        
        $row->titleNumber = "<div class='number-block' style='display:inline'>№{$rec->id}</div>";
        if ($rec->vatId && $rec->uicId) {
            if ("BG{$rec->uicId}" == $rec->vatId) {
                unset($row->uicId);
            }
        }
    }
    
    
    /**
     * След добавяне на запис в модела
     *
     * @param crm_Companies $mvc
     * @param int           $id
     * @param stdClass      $rec
     * @param string|NULL   $saveFileds
     */
    protected static function on_AfterSave(crm_Companies $mvc, &$id, $rec, $saveFileds = null)
    {
        $mvc->updateGroupsCnt = true;
        
        $mvc->updatedRecs[$id] = $rec;
        
        
        /**
         * @TODO Това не трябва да е тук, но по някаква причина не сработва в on_Shutdown()
         */
        $mvc->updateRoutingRules($rec);
        
        // Ако се редактира текущата фирма, генерираме лог от данните
        if (crm_Setup::BGERP_OWN_COMPANY_ID == $rec->id) {
            hr_Departments::forceFirstDepartment($rec->name);
            $mvc->prepareCompanyLogo();
        }
    }
    
    
    /**
     * Сетваме лого за компанията
     */
    protected static function prepareCompanyLogo()
    {
        self::setCompanyLogo('BGERP_COMPANY_LOGO_SVG');
        
        core_Lg::push('en');
        self::setCompanyLogo('BGERP_COMPANY_LOGO_SVG_EN');
        core_Lg::pop();
    }
    
    
    /**
     * Връща размера за шрифта на името на файла в зависимост от дължината
     *
     * @param string $companyName
     *
     * @return float
     */
    public static function getCompanyFontSize($companyName)
    {
        $companyNameLen = mb_strlen($companyName);
        
        if ($companyNameLen > 48) {
            $companyFontSize = 80;
        } elseif ($companyNameLen > 42) {
            $companyFontSize = 90;
        } elseif ($companyNameLen > 37) {
            $companyFontSize = 100;
        } elseif ($companyNameLen > 33) {
            $companyFontSize = 110;
        } elseif ($companyNameLen > 31) {
            $companyFontSize = 120;
        } elseif ($companyNameLen > 29) {
            $companyFontSize = 130;
        } elseif ($companyNameLen > 25) {
            $companyFontSize = 140;
        } elseif ($companyNameLen > 23) {
            $companyFontSize = 150;
        } elseif ($companyNameLen > 19) {
            $companyFontSize = 160;
        } elseif ($companyNameLen > 17) {
            $companyFontSize = 190;
        } else {
            $companyFontSize = 220;
        }
        
        return $companyFontSize;
    }
    
    
    /**
     * Помощна функция за сетване на лого на компанията
     *
     * @param string $companyConstName
     */
    protected static function setCompanyLogo($companyConstName)
    {
        $cRec = crm_Companies::fetchOwnCompany();
        
        $pngHnd = self::getCompanyLogoHnd($companyConstName, $cRec);
        
        if (!empty($pngHnd)) {
            core_Packs::setConfig('bgerp', array($companyConstName => $pngHnd));
        }
    }
    
    
    /**
     *
     *
     * @param string        $fileName
     * @param NULL|stdClass $cRec
     *
     * @return string
     */
    public static function getCompanyLogoHnd($fileName, $cRec = null)
    {
        $tpl = getTplFromFile('bgerp/tpl/companyBlank.svg');
        if (!isset($cRec)) {
            $cRec = crm_Companies::fetchOwnCompany();
        }
        
        $cRec->company = trim($cRec->company);
        $companyName = transliterate(tr($cRec->company));
        $tpl->append($companyName, 'myCompanyName');
        
        $tpl->replace(self::getCompanyFontSize($cRec->company), 'companyFontSize');
        
        // Подготвяме адреса
        $fAddres = '';
        if ($cRec->country) {
            $fAddres .= transliterate($cRec->country);
        }
        
        if (trim($cRec->pCode)) {
            $fAddres .= (trim($fAddres)) ? ', ' : '';
            $fAddres .= transliterate($cRec->pCode);
        }
        
        if (trim($cRec->place)) {
            if (trim($fAddres)) {
                $fAddres .= (trim($cRec->pCode)) ? ' ' : ', ';
            }
            
            $fAddres .= transliterate(tr($cRec->place));
        }
        
        if (trim($cRec->address)) {
            $fAddres .= (trim($fAddres)) ? ', ' : '';
            $fAddres .= transliterate(tr($cRec->address));
        }
        
        if (trim($cRec->tel)) {
            $telArr = drdata_PhoneType::toArray($cRec->tel);
            if (!empty($telArr) && $telArr[0]) {
                $tel = $telArr[0]->original;
            }
        }
        
        if (trim($cRec->fax)) {
            $faxArr = drdata_PhoneType::toArray($cRec->fax);
            if (!empty($faxArr) && $faxArr[0]) {
                $fax = $faxArr[0]->original;
            }
        }
        
        if (trim($cRec->email)) {
            $emailsArr = type_Emails::toArray($cRec->email);
            if (!empty($emailsArr)) {
                $email = $emailsArr[0];
            }
        }
        
        if (mb_strlen($cRec->website) > 32 || mb_strlen($email) > 20) {
            $tpl->append(58, 'smallFontSize');
        } else {
            $tpl->append(66, 'smallFontSize');
        }
        $tpl->append($fAddres, 'address');
        
        if (trim($tel)) {
            $tpl->append($tel, 'tel');
        } else {
            $tpl->removeBlock('tel');
        }
        
        if (trim($fax)) {
            $tpl->append($fax, 'fax');
        } else {
            $tpl->removeBlock('fax');
        }
        
        if (trim($cRec->website)) {
            $tpl->append($cRec->website, 'site');
        } else {
            $tpl->removeBlock('site');
        }
        
        if (trim($email)) {
            $tpl->append($email, 'email');
        } else {
            $tpl->removeBlock('email');
        }
        
        $content = $tpl->getContent();
        
        $pngHnd = '';
        
        try {
            if (!core_Os::isWindows()) {
                $pngHnd = fileman_webdrv_Inkscape::toPng($content, 'string', $fileName);
            }
        } catch (ErrorException $e) {
            reportException($e);
        }
        
        // Ако не може да се генерира локално лого на фирмата, се прави опит да се генерира отдалечено
        try {
            if (empty($pngHnd)) {
                if (defined('CRM_REMOTE_COMPANY_LOGO_CREATOR')) {
                    $url = CRM_REMOTE_COMPANY_LOGO_CREATOR;
                    
                    $data = array('myCompanyName' => $companyName,
                        'address' => $fAddres,
                        'tel' => $tel,
                        'fax' => $fax,
                        'email' => $email,
                        'site' => $cRec->website,
                        'baseColor' => $baseColor,
                        'activeColor' => $activeColor,
                        'lg' => core_Lg::getCurrent()
                    );
                    
                    $options = array(
                        'http' => array(
                            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                            'method' => 'POST',
                            'content' => http_build_query($data),
                        ),
                    );
                    
                    $context = stream_context_create($options);
                    $result = @file_get_contents($url, false, $context);
                    
                    if ($result) {
                        $result = json_decode($result);
                        if ($result && $url = $result->url) {
                            $bucketId = fileman_Buckets::fetchByName('pictures');
                            $pngHnd = fileman_Get::getFile((object) array('url' => $url, 'bucketId' => $bucketId));
                        }
                    }
                }
            }
        } catch (ErrorException $e) {
            reportException($e);
        }
        
        return $pngHnd;
    }
    
    
    /**
     * Подготвяме опциите на тип key
     *
     * @param crm_Companies $mvc
     * @param array         $options
     * @param type_Key      $typeKey
     * @param string        $where
     */
    protected static function on_BeforePrepareKeyOptions($mvc, $options, $typeKey, $where = '')
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
     * Подготовка на опции за key2
     */
    public static function getSelectArr($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        $ownCountry = self::fetchOurCompany()->country;
        
        if (core_Lg::getCurrent() == 'bg') {
            $countryNameField = 'commonNameBg';
        } else {
            $countryNameField = 'commonName';
        }
        
        $query = self::getQuery();
        $query->orderBy('modifiedOn=DESC');
        
        $viewAccess = true;
        if ($params['restrictViewAccess'] == 'yes') {
            $viewAccess = false;
        }
        
        $me = cls::get(get_called_class());
        $me->restrictAccess($query, null, $viewAccess);
        
        if (!$includeHiddens) {
            $query->where("#state != 'rejected' AND #state != 'closed'");
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
        $query->EXT($countryNameField, 'drdata_Countries', 'externalKey=country');
        $xpr = "CONCAT(' ', #{$titleFld}, IF(#country = {$ownCountry}, IF(LENGTH(#place), CONCAT(' - ', #place), ''), CONCAT(' - ', #{$countryNameField})))";
        $query->XPR('searchFieldXpr', 'text', $xpr);
        $query->XPR('searchFieldXprLower', 'text', "LOWER({$xpr})");
        
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
                $query->where(array("#searchFieldXprLower REGEXP '(" . $pBegin . "){1}[#1#]'", $w));
            }
        }
        
        if ($limit) {
            $query->limit($limit);
        }
        
        $query->show('id,searchFieldXpr');
        
        $res = array();
        
        while ($rec = $query->fetch()) {
            $res[$rec->id] = trim($rec->searchFieldXpr);
        }
        
        return $res;
    }
    
    
    /**
     * Рутинни действия, които трябва да се изпълнят в момента преди терминиране на скрипта
     */
    public static function on_AfterSessionClose($mvc)
    {
        if ($mvc->updateGroupsCnt) {
            crm_Groups::updateGroupsCnt($mvc->className, 'companiesCnt');
        }
        
        if (countR($mvc->updatedRecs)) {
            foreach ($mvc->updatedRecs as $id => $rec) {
                $mvc->updateRoutingRules($rec);
            }
        }
    }
    
    
    /**
     * Прекъсва връзките на изтритите визитки с всички техни имейл адреси.
     *
     * @param core_Mvc   $mvc
     * @param stdClass   $res
     * @param core_Query $query
     */
    protected static function on_AfterDelete($mvc, &$res, $query)
    {
        $mvc->updateGroupsCnt = true;
        
        foreach ($query->getDeletedRecs() as $rec) {
            // изтриваме всички правила за рутиране, свързани с визитката
            email_Router::removeRules('company', $rec->id);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public static function updateRoutingRules($rec)
    {
        if ($rec->state == 'rejected') {
            // Визитката е оттеглена - изтриваме всички правила за рутиране, свързани с нея
            email_Router::removeRules('company', $rec->id);
        } else {
            if ($rec->email) {
                static::createRoutingRules($rec->email, $rec->id);
            }
        }
    }
    
    
    /**
     * Създава `From` и `Doman` правила за рутиране след запис на визитка
     *
     * Използва се от @link crm_Companies::updateRoutingRules() като инструмент за добавяне на
     * правила
     *
     * @access protected
     *
     * @param mixed $emails   един или повече имейли, зададени като стринг или като масив
     * @param int   $objectId
     */
    public static function createRoutingRules($emails, $objectId)
    {
        // Приоритетът на всички правила, генериране след запис на визитка е среден и нарастващ с времето
        $priority = email_Router::dateToPriority(dt::now(), 'mid', 'asc');
        
        // Нормализираме параметъра $emails - да стане масив от валидни имейл адреси
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
                    'objectType' => 'company',
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
                        'objectType' => 'company',
                        'objectId' => $objectId
                    )
                );
            }
        }
    }
    
    
    /**
     * Връща информацията, която има за нашата фирма
     *
     * @param string $fields
     *
     * @return null|stdClass $rec
     */
    public static function fetchOurCompany($fields = '*')
    {
        $rec = self::fetch(crm_Setup::BGERP_OWN_COMPANY_ID, $fields);
        
        if ($rec) {
            $rec->classId = core_Classes::getId('crm_Companies');
        }
        
        return $rec;
    }
    
    
    /**
     * Изпълнява се след инсталацията
     */
    public static function loadData()
    {
        $html = '';
        $me = cls::get(get_called_class());
        if (!static::fetch(crm_Setup::BGERP_OWN_COMPANY_ID)) {
            $conf = core_Packs::getConfig('crm');
            
            $rec = new stdClass();
            $rec->id = crm_Setup::BGERP_OWN_COMPANY_ID;
            $rec->name = $conf->BGERP_OWN_COMPANY_NAME;
            
            //$rec->groupList = '|7|';
            $groupList = cls::get('crm_Groups');
            $group = 'Свързани лица';
            $rec->{$me->expandInputFieldName} = '|'. $groupList->fetchField("#name = '{$group}'", 'id') . '|';
            
            // Страната не е стринг, а id
            $Countries = cls::get('drdata_Countries');
            $rec->country = $Countries->fetchField("#commonName = '" . $conf->BGERP_OWN_COMPANY_COUNTRY . "'", 'id');
            
            if (self::save($rec, null, 'REPLACE')) {
                $html .= "<li style='color:green'>Фирмата " . $conf->BGERP_OWN_COMPANY_NAME . ' е записана с #id=' .
                crm_Setup::BGERP_OWN_COMPANY_ID . ' в базата с константите</li>';
            }
        }
        
        // Добавяме визитка за Експерта ООД
        $expertaName = 'Експерта ООД';
        if (!self::fetch("#name = '{$expertaName}'")) {
            $eRec = new stdClass();
            $eRec->name = $expertaName;
            $eRec->{$me->expandInputFieldName} = '|'. crm_Groups::fetchField("#name = 'Доставчици'", 'id') . '|';
            $eRec->country = drdata_Countries::fetchField("#commonNameBg = 'България'");
            $eRec->pCode = '5000';
            $eRec->place = 'В. Търново';
            $eRec->address = 'ул. П. Евтимий №7';
            $eRec->website = 'http://experta.bg';
            $eRec->tel = '062/611-539, 062/611-540';
            $eRec->vatId = 'BG104066415';
            $eRec->uicId = '104066415';
            $eRec->email = 'team@experta.bg';
            $eRec->info = 'Разработчик и консултант за внедряване на bgERP';
            
            if (self::save($eRec)) {
                $html .= "<li style='color:green'>Добавена е фирмата '{$expertaName}'</li>";
            }
        }
        
        return $html;
    }
    
    
    /**
     * Дали на фирмата се начислява ДДС:
     * Не начисляваме ако:
     * 		1 . Не е от ЕС
     * 		2.  Има ЕИК от ЕС, различен от BG
     * Ако няма държава начисляваме ДДС
     *
     * @param int $id - id' то на записа
     *
     * @return bool TRUE/FALSE
     */
    public static function shouldChargeVat($id)
    {
        $rec = static::fetch($id);
        
        // Ако не е посочена държава, вингаи начисляваме ДДС
        if (!$rec->country) {
            
            return true;
        }
        
        // Ако не е в Еропейския съюз, не начисляваме ДДС
        if (!drdata_Countries::isEu($rec->country)) {
            
            return false;
        }
        
        $ownCompany = crm_Companies::fetchOurCompany();
        
        // Ако няма VAT номер или има валиден ват и не е от държавата на myCompany не начисляваме
        if ((empty($rec->vatId) || drdata_Vats::isHaveVatPrefix($rec->vatId)) && ($ownCompany->country != $rec->country)) {
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Връща валутата по подразбиране за търговия дадения контрагент
     * в зависимост от дъжавата му
     *
     * @param int $id - ид на записа
     *
     * @return string(3) - BGN|EUR|USD за дефолт валутата
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
        if (drdata_Countries::isEur($rec->country)) {
            
            return 'EUR';
        }
        
        
        // За всички останали е 'USD'
        return 'USD';
    }
    
    
    /**
     * Фирмата, от чието лице работи bgerp (crm_Setup::BGERP_OWN_COMPANY_ID)
     *
     * @return stdClass @see doc_ContragentDataIntf::getContragentData()
     */
    public static function fetchOwnCompany()
    {
        return static::getContragentData(crm_Setup::BGERP_OWN_COMPANY_ID);
    }
    
    
    /****************************************************************************************
     *                                                                                      *
     *  Методи на интерфейс "doc_FoldersIntf"                                               *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Връща заглавието на папката
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        if ($rec->folderName) {
            $title = $rec->folderName;
        } else {
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
        }
        
        // Ако е зададено да се ескейпва
        if ($escaped) {
            
            // Ескейпваваме заглавието
            $title = type_Varchar::escape($title);
        }
        
        return $title;
    }
    
    /*******************************************************************************************
     *
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_ContragentAccRegIntf
     *
     ******************************************************************************************/
    
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     *
     * @param int $objectId
     */
    public static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = null;
        
        if ($rec = $self->fetch($objectId)) {
            $result = (object) array(
                'num' => $rec->id . ' f',
                'title' => $rec->name,
                'features' => array('Държава' => $self->getVerbal($rec, 'country'),
                    'Град' => bglocal_Address::canonizePlace($self->getVerbal($rec, 'place')),)
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
    
    
    /**
     * КРАЙ НА интерфейса @see acc_RegisterIntf
     */
    
    
    /**
     * Връща данните на фирмата
     *
     * @param int    $id    - id' то на записа
     * @param string $email - Имейл
     *
     * return object
     */
    public static function getContragentData($id)
    {
        //Вземаме данните от визитката
        $company = crm_Companies::fetch($id);
        
        //Заместваме и връщаме данните
        if ($company) {
            $contrData = new stdClass();
            $contrData->company = $company->name;
            $contrData->companyVerb = crm_Companies::getVerbal($company, 'name');
            $contrData->companyId = $company->id;
            $contrData->vatNo = $company->vatId;
            $contrData->eori = $company->eori;
            $contrData->uicId = $company->uicId;
            $contrData->tel = $company->tel;
            $contrData->fax = $company->fax;
            $contrData->country = crm_Companies::getVerbal($company, 'country');
            $contrData->countryId = $company->country;
            $contrData->pCode = $company->pCode;
            $contrData->place = $company->place;
            $contrData->address = $company->address;
            $contrData->email = $company->email;
            $contrData->website = $company->website;
            
            // Вземаме груповите имейли
            $contrData->groupEmails = crm_Persons::getGroupEmails($company->id);
        }
        
        return $contrData;
    }
    
    
    /**
     * Връща опции със всички лица свързани към тази фирма
     *
     * @param int  $id      - ид на фирма
     * @param bool $intKeys - дали ключовете на масива да са int
     *
     * @return array $options
     */
    public static function getPersonOptions($id, $intKeys = true)
    {
        $options = crm_Persons::makeArray4Select('name', "#buzCompanyId = {$id}");
        
        if (countR($options)) {
            if (!$intKeys) {
                $options = array_combine($options, $options);
            }
            
            $options = array('' => ' ') + $options;
        }
        
        return $options;
    }
    
    
    /**
     * Създава папка на фирма по указаните данни
     */
    public static function getCompanyFolder($company, $country, $pCode, $place, $address, $email, $tel, $fax, $website, $vatId, $inCharge, $access, $shared)
    {
        $rec = new stdClass();
        $rec->name = $company;
        
        // Адресни данни
        $rec->country = $country;
        $rec->pCode = $pCode;
        $rec->place = $place;
        $rec->address = $address;
        
        // Комуникации
        $rec->email = $email;
        $rec->tel = $tel;
        $rec->fax = $fax;
        $rec->website = $website;
        
        // Достъп/права
        $rec->inCharge = $inCharge;
        $rec->access = $access;
        $rec->shared = $shared;
        
        
        if ($vatId) {
            // Данъчен номер на фирмата
            $Vats = cls::get('drdata_Vats');
            $rec->vatId = $Vats->canonize($vatId);
        }
        
        $Companies = cls::get('crm_Companies');
        
        $folderId = $Companies->forceCoverAndFolder($rec);
        
        return $folderId;
    }
    
    
    /**
     * Функция, която задава правата за достъп до дадена фирма в търсенето
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
     * Манипулация на списъка с екстендерите
     *
     * @param core_Master $master
     * @param array       $extenders
     * @param stdClass    $rec       запис на crm_Companies
     */
    public static function on_AfterGetExtenders(core_Master $master, &$extenders, $rec)
    {
        // Премахваме от списъка екстендерите, които не могат да бъдат приложени към фирми
        $extenders = array_diff_key($extenders, arr::make('idCard, profile', true));
    }
    
    
    /**
     * Връща папката на фирмата от имейла, ако имаме достъп до нея
     *
     * @param string $email - Имейл, за който търсим
     *
     * @return int|bool $fodlerId - id на папката
     */
    public static function getFolderFromEmail($email)
    {
        // Имейла в долния регистър
        $email = mb_strtolower($email);
        
        // Вземаме компанията с този имейл
        $companyId = static::fetchField(array("LOWER(#email) LIKE '%[#1#]%'", $email));
        
        // Ако има такава компания
        if ($companyId) {
            
            // Вземаме папката на фирмата
            $folderId = static::forceCoverAndFolder($companyId);
            
            // Проверяваме дали имаме права за папката
            if (doc_Folders::haveRightFor('single', $folderId)) {
                
                return $folderId;
            }
        }
        
        return false;
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
        
        if ($action == 'edit' && isset($rec)) {
            if ($rec->id == crm_Setup::BGERP_OWN_COMPANY_ID) {
                if (!haveRole('ceo,admin')) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if ($action == 'close' && isset($rec)) {
            if ($rec->id == crm_Setup::BGERP_OWN_COMPANY_ID) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Филтрира данните за нашата фирма - id на компанията, име на компанията, адрес, телефон, факс и имейли
     *
     * @param std_Object &$contrData - Обект от който ще се премахва
     */
    public static function removeOwnCompanyData(&$contrData)
    {
        // Записи за нашата компания
        $ownCompany = static::fetchOwnCompany();
        
        // Ако id' то е в данните на контрагента
        if ($ownCompany->companyId == $contrData->companyId) {
            
            // Премахваме id' тото
            $contrData->companyId = null;
            
            // Премахваме името на компанията
            $contrData->company = null;
        }
        
        // Ако името на компанията съвпада
        if (mb_strtolower($ownCompany->company) == mb_strtolower($contrData->company)) {
            
            // Премахваме от списъка
            $contrData->company = null;
        }
        
        // Ако има открити телефони
        if ($ownCompany->tel && $contrData->tel) {
            
            // Масив с телефони на нашата компания
            $oTelArr = drdata_PhoneType::toArray($ownCompany->tel);
            
            // Масив с телефони на контрагента
            $cTelArr = drdata_PhoneType::toArray($contrData->tel);
            
            // Обхождаме масива с телефони на нашата фирма
            foreach ($oTelArr as $oTel) {
                
                // Обхождаме масива с телефони на контрагента
                foreach ($cTelArr as $key => $cTel) {
                    
                    // Ако телефона е същия
                    if (($cTel->countryCode == $oTel->countryCode) && ($cTel->areaCode == $oTel->areaCode)
                        && ($cTel->number == $oTel->number)) {
                        
                        // Премахваме от масива на контрагента
                        unset($cTelArr[$key]);
                    }
                }
            }
            
            $newCTel = '';
            
            // Обхождаме останалия масив
            foreach ($cTelArr as $cTel) {
                
                // Добавяме в стринга телефона
                
                $newCTel .= ($newCTel) ? ', ' . $cTel->original : $cTel->original;
            }
            
            // Заместваме новия стринг с данните на котрагента
            $contrData->tel = $newCTel;
        }
        
        // Ако има открити факсове
        if ($ownCompany->fax && $contrData->fax) {
            
            // Масив с факсове на нашата компания
            $oFaxArr = drdata_PhoneType::toArray($ownCompany->fax);
            
            // Масив с факсове на контрагента
            $cFaxArr = drdata_PhoneType::toArray($contrData->fax);
            
            // Обхождаме масива с факсове на нашата фирма
            foreach ($oFaxArr as $oFax) {
                
                // Обхождаме масива с факсове на контрагента
                foreach ($cFaxArr as $key => $cFax) {
                    
                    // Ако факса е същия
                    if (($cFax->countryCode == $oFax->countryCode) && ($cFax->areaCode == $oFax->areaCode)
                        && ($cTel->number == $oFax->number)) {
                        
                        // Премахваме от масива на контрагента
                        unset($cFaxArr[$key]);
                    }
                }
            }
            $newCFax = '';
            
            // Обхождаме останалия масив
            foreach ($cFaxArr as $cFax) {
                
                // Добавяме в стринга факса
                
                $newCFax .= ($newCFax) ? ', ' . $cFax->original : $cFax->original;
            }
            
            // Заместваме новия стринг с данните на котрагента
            $contrData->fax = $newCFax;
        }
        
        // Ако адреса е същия
        if (mb_strtolower($ownCompany->address) == mb_strtolower($contrData->address)) {
            
            // Премахваме от данните
            $contrData->address = null;
        }
        
        // Ако има имейли
        if ($ownCompany->email && $contrData->email) {
            
            // Масив с имейлите на нашата компания
            $oEmailArr = type_Emails::toArray($ownCompany->email);
            
            // Масив с имейлите на контрагента
            $cEmailArr = type_Emails::toArray($contrData->email);
            
            // Ако има имейли
            if (countR($oEmailArr) && countR($cEmailArr)) {
                
                // Ключа на масивите е същата със стойността
                $oEmailArr = array_combine($oEmailArr, $oEmailArr);
                $cEmailArr = array_combine($cEmailArr, $cEmailArr);
                
                // Обхождаме масива с имейли на нашата фирма
                foreach ($oEmailArr as $oEmail) {
                    
                    // Ако стойността я има в масива на контрагента, премахваме го
                    if ($cEmailArr[$oEmail]) {
                        unset($cEmailArr[$oEmail]);
                    }
                }
                
                // Останалите имейли ги записваме в имейли, като стринг
                $contrData->email = type_Emails::fromArray($cEmailArr);
            }
        }
        
        // Ако има групови имейли
        if ($ownCompany->email && $contrData->groupEmails) {
            
            // Ако не сме намерили масива преди
            if (!$oEmailArr) {
                
                // Всички имейли в масив
                $oEmailArr = type_Emails::toArray($ownCompany->email);
                
                // Ако има стойнност
                if (countR($oEmailArr)) {
                    
                    // Ключовете да са равни със стойностите
                    $oEmailArr = array_combine($oEmailArr, $oEmailArr);
                }
            }
            
            // Масив с груповите имейли
            $cGroupEmailArr = type_Emails::toArray($contrData->groupEmails);
            
            // Ако има стойности в масива
            if (countR($cGroupEmailArr)) {
                
                // Ключовете да са равни със стойностите
                $cGroupEmailArr = array_combine($cGroupEmailArr, $cGroupEmailArr);
                
                // Обхождаме масива с имейлите на нашата фирма
                foreach ($oEmailArr as $oEmail) {
                    
                    // Ако имейла е в масива премахваме го от груповите
                    if ($cGroupEmailArr[$oEmail]) {
                        unset($cGroupEmailArr[$oEmail]);
                    }
                }
            }
            
            // Останалите имейли ги записва в груповите
            $contrData->groupEmails = type_Emails::fromArray($cGroupEmailArr);
        }
        
        // Ако сме премахнали имейлите и има имейли в групите
        if (!$contrData->email && countR($cGroupEmailArr)) {
            
            // Добавяме първия в имейлите
            $contrData->email = key($cGroupEmailArr);
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
            if ($rec->{$fld}) {
                if ($fld == 'address' && $showAddress !== true) {
                    continue;
                }
                
                $obj->{$fld} = $Varchar->toVerbal($rec->{$fld});
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
                core_Statuses::newStatus("|Фирмата е включена в група |* '{$groupName}'");
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
            $res[] = (object)array('class' => 'email_Outgoings');
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
            $res[] = (object)array('class' => 'purchase_Offers', 'caption' => 'Вх. оферта');
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
     * @param crm_Companies $mvc
     * @param array         $fields
     */
    public static function on_AfterPrepareImportFields($mvc, &$fields)
    {
        $Dfields = $mvc->selectFields();
        
        $fields = array();
        
        foreach ($Dfields as $name => $fld) {
            if ($fld->input != 'none' && $fld->input != 'hidden' && $fld->kind != 'FNC') {
                $fields[$name] = array('caption' => $fld->caption, 'mandatory' => $fld->mandatory);
                if ($name == $mvc->expandInputFieldName) {
                    $tGroup = $fields[$name];
                    unset($fields[$name]);

                    $fields['groups'] = array('caption' => 'Група->CSV');

                    $fields[$name] = $tGroup;

                    $fields[$name]['notColumn'] = true;
                    $fields[$name]['caption'] = 'Група->Избор';
                    $fields[$name]['type'] = 'keylist(mvc=crm_Groups,select=name,makeLinks,where=#allow !\\= \\\'persons\\\'AND #state !\\= \\\'rejected\\\')';
                }
            }
        }

        unset($fields['shared']);
        unset($fields['access']);
        unset($fields['inCharge']);
    }
    
    
    /**
     * След подготовка на полетата за импортиране
     *
     * @param crm_Companies $mvc
     * @param array         $recs
     */
    public static function on_AfterPrepareExportRecs($mvc, &$recs)
    {
        // Ограничаваме данните, които ще се експортират от фирмите, до които нямаме достъп
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
            
            $recs[$key] = $nRec;
        }
    }
    
    
    /**
     * След подготовка на записите за експортиране
     *
     * @param crm_Companies $mvc
     * @param object        $rec
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
        // id на държавата
        if (isset($rec->country)) {
            $rec->country = drdata_Countries::getIdByName($rec->country);
        }

        if (isset($rec->nkid)) {
            $rec->nkid = bglocal_NKID::fetchField(array("#title = '[#1#]'", $rec->nkid));
        }

        // Проверка дали има дублиращи се записи
        $query = $mvc->getQuery();
        if ($name = trim($rec->name)) {
            $query->where(array("#name = '[#1#]'", $name));
        }
        
        if ($vatId = trim($rec->vatId)) {
            $query->orWhere(array("#name = '[#1#]'", $vatId));
        }
        
        $query->orderBy('#vatId', 'DESC');
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
     * Дали артикулът създаден в папката трябва да е публичен (стандартен) или не
     *
     * @param mixed $id - ид или запис
     *
     * @return string public|private|template - Стандартен / Нестандартен / Шаблон
     */
    public function getProductType($id)
    {
        return 'private';
    }
    
    
    /**
     * Добавя ключовио думи за държавата и на bg и на en
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        $res = drdata_Countries::addCountryInBothLg($rec->country, $res);
        
        // Ако полето е обозначено за оказване
        if (isset($rec->nkid)) {
            
            // Добавяме в ключовите думи
            $res .= ' ' . plg_Search::normalizeText(bglocal_NKID::getTitleById($rec->nkid));
        }
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
        if (core_Users::isContractor() || !haveRole('user')) {
            
            return ;
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
     * @param string      $vatId     - ват номер
     * @param string      $uicNo     - нац. номер
     * @param int         $countryId - ид на държава
     * @param string|NULL $pCode     - п. код
     * @param string|NULL $place     - населено място
     * @param string|NULL $address   - адрес
     *
     * @return void
     */
    public static function updateContactDataByFolderId($folderId, $name, $vatId, $uicNo, $countryId, $pCode, $place, $address)
    {
        $saveFields = array();
        $rec = self::fetch("#folderId = {$folderId}");
        $arr = array('name' => $name, 'vatId' => $vatId, 'uicId' => $uicNo, 'country' => $countryId, 'pCode' => $pCode, 'place' => $place, 'address' => $address);
        
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
    
    
    /**
     * Извличане на данните на фирмата според зададения източник
     * 
     * @param string $string - ЕИК или Ват номер
     * 
     * @return false|stdClass - обект с данни или false, ако не намери нищо
     *          o name    - име
     *          o country - ид на държава
     *          o pCode   - пощенски код
     *          o place   - населено място
     *          o address - адрес
     *          o uicId   - ДДС номер (ако име)
     *          o vatId   - ЕИК (ако има)
     */
    public static function getCompanyDataFromString($string)
    {
        $data = false;
        $useVies = crm_Setup::get('REGISTRY_USE_VIES') == 'yes';
        $useBrra = crm_Setup::get('REGISTRY_USE_BRRA') == 'yes';
        
        // Нормализиране на стринга
        $string = str::removeWhiteSpace($string);
        $string = strtoupper($string);
       
        // Ако е избран търговски регистър, и е въведен български ЕИК или ДДС номер, взимат се данните от търговския регистър
        if($useBrra){
            $brraString = (drdata_Vats::isHaveVatPrefix($string)) ? drdata_Vats::getUicByVatNo($string) : $string;
            $data = drdata_Vats::getFromBrra($brraString);
            
            // Ако има данни в търговския регистър
            if(is_object($data)){
                if(drdata_Vats::isHaveVatPrefix($string)){
                    $data->uicId = $brraString;
                }
                
                // и има валиден ДДС номер, ще се върне и ДДС номерът
                list($status) = cls::get('drdata_Vats')->checkStatus("BG{$brraString}");
                if($status == 'valid'){
                    $data->vatId = "BG{$brraString}";
                }
            }
        }
            
        // Ако няма да се връщат или не са намерени данни от търговския регистър, взимат се от VIES, ако е избрано
        if($data === false && $useVies){
            
            // Ако е валиден български ЕИК добавя му се BG отпред
            if(!drdata_Vats::isHaveVatPrefix($string)){
                if(drdata_Vats::isBulstat($string)){
                    $string = "BG{$string}";
                }
            }
            
            // Връщане на данните от VIES - ако са намерени
            $data = drdata_Vats::getFromVies($string);
        }
        
        // Ако има намерени данни
        if(is_object($data)){
            
            // Нормализиране на името на фирмата
            $data->name = str_replace('"', "", $data->name);
            $data->name = str_replace("'", "", $data->name);
            $data->name = mb_strtolower(str::removeWhiteSpace($data->name, " "));
            $data->name = str::toUpperAfter($data->name, " ");
            $data->name = trim($data->name);
            
            // Специалните думи се капитализират
            foreach (array('оод', 'еоод', 'ад', 'еад', 'ltd', 'ltd.', 's.r.o', 's.a', 'e.k', 'a.s', 'srl', 'd.o.o', 'cmbh') as $specialPart){
                $data->name = str_replace(" " . str::mbUcfirst($specialPart),  " " . mb_strtoupper($specialPart), $data->name);
            }
        }
       
        // Връщане на данните, ако са извлечени
        return $data;
    }
}
