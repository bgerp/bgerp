<?php


/**
 * Мениджър на физическите лица
 *
 *
 * @category  bgerp
 * @package   crm
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.12
 * @title     Физически лица
 */
class crm_Persons extends core_Master
{


    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = array(
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
        'incoming_CreateDocumentIntf',
    		
    	// Интерфейс за източник на производствен ресурс
    	'planning_ResourceSourceIntf',
    		
    	// Интерфейс за корица на папка в която може да се създава артикул
    	'cat_ProductFolderCoverIntf',
    );


    /**
     * Заглавие на мениджъра
     */
    var $title = "Лица";


    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Лице";
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'callcenter=0.1';
    
    
    /**	
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/vcard.png';


    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    var $fetchFieldsBeforeDelete = 'id,name';


    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    var $loadList = 'plg_Created, plg_Modified, plg_RowTools,  plg_LastUsedKeys,plg_Rejected, plg_Select,
                     crm_Wrapper, crm_AlphabetWrapper, plg_SaveAndNew, plg_PrevAndNext, bgerp_plg_Groups, plg_Printing, plg_State,
                     plg_Sorting, recently_Plugin, plg_Search, acc_plg_Registry, doc_FolderPlg,
                     bgerp_plg_Import, drdata_PhonePlg';
    
    
    /**
     * Полета, които се показват в листови изглед
     */
    var $listFields = 'nameList=Име,phonesBox=Комуникации,addressBox=Адрес,name=';


    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';


    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';


    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    var $lastUsedKeys = 'groupList';


    /**
     * Полета по които се правитърсене от плъгина plg_Search
     */
    var $searchFields = 'name,egn,country,place,email,info,id';


    /**
     * Кой  може да пише?
     */
    var $canWrite = 'powerUser';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'powerUser';
    
    
    /**
     * По кои сметки ще се правят справки
     */
    public $balanceRefAccounts = '323,401,402,403,404,405,406,409,411,412,413,414,415,419';
    
    
    /**
     * По кой итнерфейс ще се групират сметките 
     */
    public $balanceRefGroupBy = 'crm_ContragentAccRegIntf';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    var $canReports = 'ceo,sales,purchase,acc';
    
    
	/**
	 * Кой може да го разглежда?
	 */
	var $canList = 'powerUser';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'powerUser';
    
    
    /**
     * Кой  може да групира "С избраните"?
     */
    var $canGrouping = 'powerUser';

    
    /**
     * Кой може да оттегля
     */
    var $canReject = 'powerUser';
 
	
    /**
     * Кой може да го възстанови?
     */
    var $canRestore = 'powerUser';
 
	
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'crm/tpl/SinglePersonLayout.shtml';


    var $doWithSelected = 'export=Експортиране';


    /**
     * Име на полето, указващо в коя група/групи е записа
     * 
     * @var string
     * @see groups_Extendable
     */
    public $groupsField = 'groupList';

    
    /**
     * Детайли на този мастър обект
     * 
     * @var string|array
     */
    public $details = 'ContragentLocations=crm_Locations,Pricelists=price_ListToCustomers,
                    ContragentBankAccounts=bank_Accounts,IdCard=crm_ext_IdCards,CustomerSalecond=cond_ConditionsToCustomers,AccReports=acc_ReportDetails,Cards=pos_Cards,Resources=planning_ObjectResources';
    
    
    /**
     * Поле, в което да се постави връзка към папката в листови изглед
     */
    var $listFieldForFolderLink = 'folder';


    /**
     * Предефинирани подредби на листовия изглед
     */
    var $listOrderBy = array(
        'alphabetic'    => array('Азбучно', '#name=ASC'),
        'last'          => array('Последно добавени', '#createdOn=DESC', 'createdOn=Създаване->На,createdBy=Създаване->От'),
        'modified'      => array('Последно променени', '#modifiedOn=DESC', 'modifiedOn=Модифициране->На,modifiedBy=Модифициране->От'),
        'birthday'      => array('Рожден ден', '#birthday=DESC'),
        'website'       => array('Сайт/Блог', '#website', 'website=Сайт/Блог'),
        );
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Име на лицето
        $this->FLD('salutation', 'enum(,mr=Г-н,mrs=Г-жа,miss=Г-ца)', 'caption=Обръщение');
        $this->FLD('name', 'varchar(255,ci)', 'caption=Имена,class=contactData,mandatory,remember=info,silent');
        $this->FNC('nameList', 'varchar', 'sortingLike=name');

        // Единен Граждански Номер
        $this->FLD('egn', 'bglocal_EgnType', 'caption=ЕГН');

        // Дата на раждане
        $this->FLD('birthday', 'combodate(minYear=1850,maxYear=' . date('Y') . ')', 'caption=Рожден ден');

        // Адресни данни
        $this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Държава,remember,class=contactData,mandatory,silent');
        $this->FLD('pCode', 'varchar(16)', 'caption=П. код,recently,class=pCode');
        $this->FLD('place', 'varchar(64)', 'caption=Град,class=contactData,hint=Населено място: град или село и община');
        $this->FLD('address', 'varchar(255)', 'caption=Адрес,class=contactData');

        // Служебни комуникации
        $this->FLD('buzCompanyId', 'key(mvc=crm_Companies,select=name,allowEmpty, where=#state !\\= \\\'rejected\\\')', 
            'caption=Служебни комуникации->Фирма,oldFieldName=buzCumpanyId,class=contactData,silent');
        $this->FLD('buzLocationId', 'key(mvc=crm_Locations,select=title,allowEmpty)', 'caption=Служебни комуникации->Локация,class=contactData');
        $this->FLD('buzPosition', 'varchar(64)', 'caption=Служебни комуникации->Длъжност,class=contactData');
        $this->FLD('buzEmail', 'emails', 'caption=Служебни комуникации->Имейли,class=contactData');
        $this->FLD('buzTel', 'drdata_PhoneType(type=tel)', 'caption=Служебни комуникации->Телефони,class=contactData');
        $this->FLD('buzFax', 'drdata_PhoneType(type=fax)', 'caption=Служебни комуникации->Факс,class=contactData');
        $this->FLD('buzAddress', 'varchar(255)', 'caption=Служебни комуникации->Адрес,class=contactData');

        // Лични комуникации
        $this->FLD('email', 'emails', 'caption=Лични комуникации->Имейли,class=contactData');
        $this->FLD('tel', 'drdata_PhoneType(type=tel)', 'caption=Лични комуникации->Телефони,class=contactData,silent');
        $this->FLD('mobile', 'drdata_PhoneType(type=tel)', 'caption=Лични комуникации->Мобилен,class=contactData,silent');
        $this->FLD('fax', 'drdata_PhoneType(type=fax)', 'caption=Лични комуникации->Факс,class=contactData,silent');
        $this->FLD('website', 'url', 'caption=Лични комуникации->Сайт/Блог,class=contactData');

        // Допълнителна информация
        $this->FLD('info', 'richtext(bucket=crmFiles)', 'caption=Информация->Бележки,height=150px,class=contactData');
        $this->FLD('photo', 'fileman_FileType(bucket=pictures)', 'caption=Информация->Фото');

        // В кои групи е?
        $this->FLD('groupList', 'keylist(mvc=crm_Groups,select=name,makeLinks,where=#allow !\\= \\\'companies\\\')', 'caption=Групи->Групи,remember,silent');

        // Състояние
        $this->FLD('state', 'enum(active=Вътрешно,closed=Нормално,rejected=Оттеглено)', 'caption=Състояние,value=closed,notNull,input=none');
    }


    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('users', 'users(rolesForAll = officer|manager|ceo, rolesForTeams = officer|manager|ceo|executive)', 'caption=Потребител,input,silent,refreshForm');
        
        // Вземаме стойността по подразбиране, която може да се покаже
        $default = $data->listFilter->getField('users')->type->fitInDomain('all_users');
        
        // Задаваме стойността по подразбиране
        $data->listFilter->setDefault('users', $default);
        
        // Подготовка на полето за подредба
        foreach($mvc->listOrderBy as $key => $attr) {
            $options[$key] = $attr[0];
        }
        $orderType = cls::get('type_Enum');
        $orderType->options = $options;

        $data->listFilter->FNC('order', $orderType,'caption=Подредба,input,silent,refreshForm');
                                         
        $data->listFilter->FNC('groupId', 'key(mvc=crm_Groups,select=name,allowEmpty)', 'placeholder=Всички групи,caption=Група,input,silent,refreshForm');
        $data->listFilter->FNC('alpha', 'varchar', 'caption=Буква,input=hidden,silent,refreshForm');

        $data->listFilter->view = 'horizontal';

        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');

        // Показваме само това поле. Иначе и другите полета
        // на модела ще се появят
        $data->listFilter->showFields = 'search,users,order,groupId';

        $data->listFilter->input('users,alpha,search,order,groupId', 'silent');
        
        // Според заявката за сортиране, показваме различни полета
        $showColumns = $mvc->listOrderBy[$data->listFilter->rec->order][2];

        if($showColumns) {
            $showColumns = arr::make($showColumns, TRUE);
            foreach($showColumns as $field => $title) {
                $data->listFields[$field] = $title;
            }
        }
        
    	// Подредба
        setIfNot($data->listFilter->rec->order, 'alphabetic');
        $orderCond = $mvc->listOrderBy[$data->listFilter->rec->order][1];
        if($orderCond) {
            $data->query->orderBy($orderCond);
        }
        if($data->listFilter->rec->order == 'birthday'){
        	$mvc->birthdayFilter = TRUE;
        }
        if($data->listFilter->rec->alpha) {
            if($data->listFilter->rec->alpha{0} == '0') {
                $cond = "LTRIM(REPLACE(REPLACE(REPLACE(LOWER(#name), '\"', ''), '\'', ''), '`', '')) NOT REGEXP '^[a-zA-ZА-Яа-я]'";
            } else {
                $alphaArr = explode('-', $data->listFilter->rec->alpha);
                $cond = array();
                $i = 1;

                foreach($alphaArr as $a) {
                    $cond[0] .= ($cond[0] ? ' OR ' : '') .
                    "( LTRIM(REPLACE(REPLACE(REPLACE(LOWER(#name), '\"', ''), '\'', ''), '`', '')) LIKE LOWER('[#{$i}#]%'))";
                    $cond[$i] = $a;
                    $i++;
                }
            }

            $data->query->where($cond);
        }

        if($names = Request::get('names')) {
            $namesArr = explode(',', $names);
            $first = TRUE;

            foreach($namesArr as $name) {
                $name = trim($name);

                if($first) {
                    $data->query->where(array("#searchKeywords LIKE ' [#1#] %'", $name));
                } else {
                    $data->query->orWhere(array("#searchKeywords LIKE ' [#1#] %'", $name));
                }
                $first = FALSE;
            }

            $date = Request::get('date', 'date');

            if($date) {
                $data->title = "Именници на <span class=\"green\">" . dt::mysql2verbal($date, 'd.m.Y, l') . "</span>";
            } else {
                $data->title = "Именници";
            }
        }
        
        // Филтриране по потребител/и
        if(!$data->listFilter->rec->users) {
            $data->listFilter->rec->users = '|' . core_Users::getCurrent() . '|';
        }

        if(($data->listFilter->rec->users != 'all_users') && (strpos($data->listFilter->rec->users, '|-1|') === FALSE)) {
            $data->query->where("'{$data->listFilter->rec->users}' LIKE CONCAT('%|', #inCharge, '|%')");
            $data->query->orLikeKeylist('shared', $data->listFilter->rec->users);
        }

        if($data->groupId = Request::get('groupId', 'key(mvc=crm_Groups,select=name)')) {
            $data->query->where("#groupList LIKE '%|{$data->groupId}|%'");
        }
     }


    /**
     * Премахване на бутон и добавяне на нови два в таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if($data->toolbar->removeBtn('btnAdd')) {
            if($groupId = $data->listFilter->rec->groupId) {
                $data->toolbar->addBtn('Ново лице', array('Ctr' => $mvc, 'Act' => 'Add', "groupList[{$groupId}]" => 'on'), 'id=btnAdd', array('ef_icon'=>'img/16/star_2.png', 'title'=>'Създаване на нова визитка на лице'));
            } else {
                $data->toolbar->addBtn('Ново лице', array('Ctr' => $mvc, 'Act' => 'Add'), 'id=btnAdd', array('ef_icon'=>'img/16/star_2.png', 'title'=>'Създаване на нова визитка на лице'));
            }
        }
    }


    /**
     * Манипулации със заглавието
     *
     * @param core_Mvc $mvc
     * @param core_Et $tpl
     * @param stdClass $data
     */
    static function on_AfterPrepareListTitle($mvc, &$tpl, $data)
    {
        if($data->listFilter->rec->groupId) {
            $data->title = "Лица в групата|* \"<b style='color:green'>|" .
            crm_Groups::getTitleById($data->groupId) . "|*</b>\"";
        } elseif($data->listFilter->rec->search) {
            $data->title = "Лица отговарящи на филтъра|* \"<b style='color:green'>" .
            type_Varchar::escape($data->listFilter->rec->search) .
            "</b>\"";
        } elseif($data->listFilter->rec->alpha) {
            if($data->listFilter->rec->alpha{0} == '0') {
                $data->title = "Лица, които започват с не-буквени символи";
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
    static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        
        // Подготвяме рожденния ден
        static::prepareBirthday($rec);

        if($form->isSubmitted()) {

            // Проверяваме да няма дублиране на записи
            static::checkSimilarWarning($mvc, $form);

            if($rec->place) {
                $rec->place = bglocal_Address::canonizePlace($rec->place);
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
    static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = NULL)
    {
        if($fields['-single']) {

            // Fancy ефект за картинката
            $Fancybox = cls::get('fancybox_Fancybox');

            $tArr = array(200, 150);
            $mArr = array(600, 450);
            
            if($rec->photo) {
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
                        $haveAvatar = TRUE;
                    }
                }
                
                // Ако няма открит аватар
                if (!$haveAvatar) {
                    if($rec->email) {
                        $emlArr = type_Emails::toArray($rec->email);
                        $imgUrl = avatar_Gravatar::getUrl($emlArr[0], 120);
                    } elseif($rec->buzEmail) {
                        $emlArr = type_Emails::toArray($rec->buzEmail);
                        $imgUrl = avatar_Gravatar::getUrl($emlArr[0], 120);
                    } elseif(!Mode::is('screenMode', 'narrow')) {
                        $imgUrl = sbf('img/noimage120.gif');
                    }
                    
                    if($imgUrl) {
                        $row->image = "<img class=\"hgsImage\" src=" . $imgUrl . " alt='no image'>";
                    }
                }
            }
            
            if($rec->buzLocationId){
            	$row->buzLocationId = crm_Locations::getHyperLink($rec->buzLocationId, TRUE);
            }
        }
        
        $ownCompany = crm_Companies::fetchOurCompany();
        if ($ownCompany->country != $rec->country) {
        	$row->country = $mvc->getVerbal($rec, 'country');
        }
        
        $pCode = $mvc->getVerbal($rec, 'pCode');
        $place = $mvc->getVerbal($rec, 'place');
        $address = $mvc->getVerbal($rec, 'address');


        if($fields['-list']) {
            
            // Дали има права single' а на този потребител
            $canSingle = static::haveRightFor('single', $rec);
            
            $row->nameList = $mvc->getLinkToSingle($rec->id, 'name');
            
            if ($row->country) {
                $row->addressBox = $row->country;
                $row->addressBox .= ($pCode || $place) ? "<br>" : "";
            }
            
            $row->addressBox .= $pCode ? "{$pCode} " : "";
            $row->addressBox .= $place;

            // Ако имаме права за сингъл
            if ($canSingle) {
                
                // Добавяме адреса
                $row->addressBox .= $address ? "<br/>{$address}" : "";    
            
                // Мобилен телефон
                $mob = $mvc->getVerbal($rec, 'mobile');
                $row->phonesBox .= $mob ? "<div class='crm-icon mobile'>{$mob}</div>" : "";
                
                // Телефон
                $tel = $mvc->getVerbal($rec, $rec->buzTel ? 'buzTel' : 'tel');
                $row->phonesBox .= $tel ? "<div class='crm-icon telephone'>{$tel}</div>" : "";
                
                // Факс
                $fax = $mvc->getVerbal($rec, $rec->buzFax ? 'buzFax' : 'fax');
                $row->phonesBox .= $fax ? "<div class='crm-icon fax'>{$fax}</div>" : "";
                
                // Email
                $eml = $mvc->getVerbal($rec, $rec->buzEmail ? 'buzEmail' : 'email');
                $row->phonesBox .= $eml ? "<div class='crm-icon email'>{$eml}</div>" : "";
    
                $row->phonesBox = "<div style='max-width:400px;'>{$row->phonesBox}</div>";
            } else {
                
                // Добавяме линк към профила на потребителя, който е inCharge на визитката
                $row->phonesBox = tr('Отговорник') . ': ' . crm_Profiles::createLink($rec->inCharge);
            }
        }
        $currentId = $mvc->getVerbal($rec, 'id');


        $row->nameList = '<div class="namelist">'. $row->nameList.  "  <span class='number-block'>". $currentId .
        "</span><div class='custom-rowtools'>". $row->id . ' </div>' . $row->folder .'</div>';
      
        $row->title =  $mvc->getTitleById($rec->id);

        $birthday = trim($mvc->getVerbal($rec, 'birthday'));

        if($birthday) {
            $row->title .= "&nbsp;&nbsp;<div style='display:inline-block'>{$birthday}</div>";

            if(strlen($birthday) == 5) {
                $dateType = 'Рожден&nbsp;ден';
            } else {
                if($rec->salutation == 'mr') {
                    $dateType = 'Роден';
                } elseif($rec->salutation == 'mrs' || $rec->salutation == 'miss') {
                    $dateType = 'Родена';
                } else {
                    $dateType = 'Роден(а)';
                }
            }
            if($mvc->birthdayFilter){
            	$row->nameList .= "<div style='font-size:0.8em;margin:3px;'>$dateType:&nbsp;{$birthday}</div>";
            }
        } elseif($rec->egn) {
            $egn = $mvc->getVerbal($rec, 'egn');
            $row->title .= "&nbsp;&nbsp;<div style='float:right'>{$egn}</div>";
            $row->nameList .= "<div style='font-size:0.8em;margin:3px;'>{$egn}</div>";
        }

        if($rec->buzCompanyId && crm_Companies::haveRightFor('single', $rec->buzCompanyId)) {
            $row->buzCompanyId = ht::createLink($mvc->getVerbal($rec, 'buzCompanyId'), array('crm_Companies', 'single', $rec->buzCompanyId));
            $row->nameList .= "<div style='font-size:0.8em;margin:3px;'>{$row->buzCompanyId}</div>";
        }
    }
	
	
	/**
     * Връща заглавието на папката
     */
    static function getRecTitle($rec, $escaped = TRUE)
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
        if($rec->place && ($commonName == mb_strtolower($conf->BGERP_OWN_COMPANY_COUNTRY))) {
            
            // Добавяме града
            $title .= ' - ' . $rec->place;
        } elseif ($country) {
            
            // Или ако има държава
            $title .= ' - ' . $country;
        }
        
        // Ако е зададено да се ескейпва
        if($escaped) {
            
            // Ескейпваваме заглавието
            $title = type_Varchar::escape($title);
        }
        
        return $title;
    }
    

    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        if($rec->groupList) {
            $mvc->updateGroupsCnt = TRUE;
        }

        $mvc->updatedRecs[$id] = $rec;

        $mvc->updateRoutingRules($rec);
        
        // Обновяме номерата
        $mvc->updateNumbers($rec);
    }
    
    
    /**
     * Подготвяме опциите на тип key
     *
     * @param std Class $mvc
     * @param array $options
     * @param std Class $typeKey
     */    
    static function on_BeforePrepareKeyOptions($mvc, $options, $typeKey)
    {
       if ($typeKey->params['select'] == 'name') {
	       $query = $mvc->getQuery();
	       $mvc->restrictAccess($query);
	       
	       while($rec = $query->fetch("#state != 'rejected'")) {
	       	   $typeKey->options[$rec->id] = $rec->name . " ({$rec->id})";
	       }
       }
    }
    
    
    /**
     * Добавя номера за лицето
     */
    public static function updateNumbers($rec)
    {
        $numbersArr = array();
        
        // Ако има телефон
        if ($rec->tel) {
            
            // Добавяме в масива
            $numbersArr['tel'][] = $rec->tel;
        }
        
        // Ако има бизнес номер
        if ($rec->buzTel) {
            
            // Добавяме към телефона
            $numbersArr['tel'][] = $rec->buzTel;
        }
        
        // Ако има факс
        if ($rec->fax) {
            
            // Добавяме факса
            $numbersArr['fax'][] = $rec->fax;
        }
        
        // Ако има бизнес факс
        if ($rec->buzFax) {
            
            // Добавяме към факса
            $numbersArr['fax'][] = $rec->buzFax;
        }
        
        // Ако има мобилен
        if ($rec->mobile) {
            
            // Добавяме мобилния
            $numbersArr['mobile'][] = $rec->mobile;
        }
        
        // id на класа
        $classId = static::getClassId();
        
        // Ако е инсталиран пакета
        if (core_Packs::isInstalled('callcenter')) {
            $numArr = callcenter_Numbers::addNumbers($numbersArr, $classId, $rec->id, $rec->country);
        }
        
        // Добавяме номерата в КЦ
        return $numArr;
    }


    /**
     * След изтриване на запис
     */
    static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
        $mvc->updateGroupsCnt = TRUE;
        
        foreach($query->getDeletedRecs() as $id => $rec) {
            $mvc->updatedRecs[$id] = $rec;

            // изтриваме всички правила за рутиране, свързани с визитката
            email_Router::removeRules('person', $rec->id);
        }
    }
    
    
    /**
     * Рутинни действия, които трябва да се изпълнят в момента преди терминиране на скрипта
     */
    static function on_Shutdown($mvc)
    { 
        if($mvc->updateGroupsCnt) {
            $mvc->updateGroupsCnt();
        }

        if(count($mvc->updatedRecs)) {
            // Обновяване на информацията за рожденните дни, за променените лица            
            foreach($mvc->updatedRecs as $id => $rec) {
                static::updateBirthdaysToCalendar($id);
            }
        }
    }


    /**
     * Обновяване на рожденните дни по разписание
     * (Еженощно)
     */
    function cron_UpdateCalendarEvents()
    {
        $query = self::getQuery();

        while($rec = $query->fetch()) {
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
    static function updateBirthdaysToCalendar($id)
    {
        if(($rec = static::fetch($id)) && ($rec->state != 'rejected')) {
            list($y, $m, $d) = type_Combodate::toArray($rec->birthday);
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
            
            foreach($years as $year) {

                // Родените в бъдещето, да си празнуват рождения ден там
                if(($y > 0) && ($y > $year)) continue;
                
                $calRec = new stdClass();
                
                // Ключ на събитието
                $calRec->key = $prefix . '-' . $year;
                
                // TODO да се проверява за високосна година
                $calRec->time = date('Y-m-d 00:00:00', mktime(0, 0, 0, $m, $d, $year) );

                $calRec->type = 'birthday';
                $calRec->allDay = 'yes';
                
                if($y > 0) {
                    $calRec->title = $rec->name . " на " . ($year - $y) . " г.";
                } else {
                    $calRec->title = "ЧРД: {$rec->name}";
                }
                
                // Само рожденните дни на потребителите и на публично достъпните лица се виждат от всички
                if(crm_Profiles::fetch("#personId = {$id}") || $rec->access == 'public') {
                    $calRec->users = '';
                } else {
                    $calRec->users =  str_replace('||', '|', "|{$rec->inCharge}|" . $rec->shared);
                }


                $calRec->url = array('crm_Persons', 'Single', $id);

                $calRec->priority = 90;

                $events[] = $calRec;
            }
        }

        return cal_Calendar::updateEvents($events, $fromDate, $toDate, $prefix);
    }


    /**
     * Обновява информацията за количеството на визитките в групите
     */
    function updateGroupsCnt()
    {
        $query = $this->getQuery();

        while($rec = $query->fetch()) {
            $keyArr = keylist::toArray($rec->groupList);

            foreach($keyArr as $groupId) {
                $groupsCnt[$groupId]++;
            }
        }
        
        // Вземаме id' тата на всички групи
        $groupsArr = crm_Groups::getGroupRecsId();
        
        // Обхождаме масива
        foreach ($groupsArr as $id) {
            
            // Записа, който ще обновим
            $groupsRec = new stdClass();
            
            // Броя на потребителите в съответната група
            $groupsRec->personsCnt = (int)$groupsCnt[$id];
            
            // id' то на групата
            $groupsRec->id = $id;
            
            // Обновяваме броя на потребителите
            crm_Groups::save($groupsRec, 'personsCnt');    
        }
    }


    /**
     * Ако е празна таблицата с контактите я инициализираме с един нов запис
     * Записа е с id=1 и е с данните от файла bgerp.cfg.php
     *
     * @param unknown_type $mvc
     * @param unknown_type $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        if(Request::get('Full')) {

            $query = $mvc->getQuery();

            while($rec = $query->fetch()) {
                $rec->state = 'active';
                
                list($y, $m, $d) = type_Combodate::toArray($rec->birthday);
                
                if($y>0 || $m>0 || $d >0) {
                    $rec->birthday = type_Combodate::create($y, $m, $d);
                } else {
                    $rec->birthday = NULL;
                }

                $res .= "<li style=''> $rec->name =>  $rec->birthday";

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
    static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = NULL;

        if ($rec = $self->fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->id . " p",
                'title' => $rec->name,
                'features' => array('Държава' => static::getVerbal($rec, 'country'),
            						'Град' => bglocal_Address::canonizePlace(static::getVerbal($rec, 'place')))
            );
            
        	if($rec->groupList){
            	$groups = strip_tags($self->getVerbal($rec, 'groupList'));
            	$result->features = $result->features + arr::make($groups, TRUE);
            }
            
            $result->features = $self->CustomerSalecond->getFeatures($self, $objectId, $result->features);
        }

        return $result;
    }


    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
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
    function prepareCompanyExpandData(&$data)
    {   
        if(!$data->query) {
            $query = $this->getQuery();
            $query->where("#buzCompanyId = {$data->masterId}");
            $query->where("#state != 'rejected'");
        } else {
            $query = $data->query;
        }

        while($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $row = $data->rows[$rec->id] = $this->recToVerbal($rec, 'name,mobile,tel,email,buzEmail,buzTel,buzLocationId,buzPosition');
            
            if ($this->haveRightFor('single', $rec)) {
                $row->name = ht::createLink($row->name, array($this, 'Single', $rec->id));
            }
            
            if($rec->buzLocationId){
            	$row->name .= " - {$row->buzLocationId}";
            }
            
            if(!$row->buzTel) $row->buzTel = $row->tel;

            if(!$row->buzEmail) $row->buzEmail = $row->email;
        }
        
        if(crm_Persons::haveRightFor('add') && crm_Companies::haveRightFor('edit', $data->masterId)){
        	$img = sbf('img/16/add.png');
		    $addUrl = array('crm_Persons', 'add', 'buzCompanyId' => $data->masterId, 'ret_url' => TRUE);
		    $data->addBtn = ht::createLink('', $addUrl, NULL, array('style' => "background-image:url({$img})", 'class' => 'linkWithIcon addSalecond', 'title' => 'Добавяне на представител')); 
        }
    }


    /**
     * Рендира данните
     */
    function renderCompanyExpandData($data)
    {
        $tpl = new ET("<fieldset class='detail-info'>
                            <legend class='groupTitle'>" . tr('Представители') . "[#BTN#]</legend>
                                <div class='groupList clearfix21'>
                                 [#persons#]
                            </div>
                            <!--ET_BEGIN regCourt--><div><b>[#regCourt#]</b></div><!--ET_END regCourt-->
                         </fieldset>");
		
        if($data->addBtn){
        	$tpl->replace($data->addBtn, 'BTN');
        }
        
        if(count($data->rows)){
            $i = 0;
        	foreach($data->rows as $id => $row) {
        	    
        		$tpl->append("<div style='margin-bottom:10px'>", 'persons');
        	    
        		if(crm_Persons::haveRightFor('edit', $id)){
        			$editImg = "<img src=" . sbf('img/16/edit-icon.png') . " alt=\"" . tr('Редакция') . "\">";
        			$editLink = ht::createLink($editImg, array($this, 'edit', $id, 'ret_url' => TRUE), NULL, "id=edt{$id},title=Редактиране на " . mb_strtolower($this->singleTitle));
        			$row->name .= " {$editLink}";
        		}
        		
        		$positionsStr = '';
        		
        	    if ($row->buzPosition && $row->name) {
        		    $positionsStr = "<i style='font-size:0.9em;'> ({$row->buzPosition})</i>";
        		}
        		
        		$tpl->append("<div> <span style='font-weight:bold;'>{$row->name}</span>{$positionsStr}</div>", 'persons');
        		
        		if($row->mobile) {
        			$tpl->append("<div class='crm-icon mobile'>{$row->mobile}</div>", 'persons');
        		}
        	
        		if($row->buzTel) {
        			$tpl->append("<div class='crm-icon telephone'>{$row->buzTel}</div>", 'persons');
        		}
        	
        		if($row->buzEmail) {
        			$tpl->append("<div class='crm-icon email'>{$row->buzEmail}</div>", 'persons');
        		}
        	
        		$tpl->append("</div>", 'persons');
        	
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
     *  Подготвя и рендира именниците                                                       *
     *                                                                                      *
     ****************************************************************************************/

    /**
     * Подготвя (извлича) данните за именниците
     */
    static function prepareNamedays(&$data)
    {   
    
    	$currentId = core_Users::getCurrent();
        $query = self::getQuery();
       
        foreach($data->namesArr as $name) { 
        	$query->orWhere(array("#searchKeywords LIKE ' [#1#] %' AND (#inCharge = '{$currentId}' OR #shared LIKE '|{$currentId}|')", $name));
        }
        
        $self = cls::get('crm_Persons');

        while($rec = $query->fetch()) { 
        	
            $data->recs[$rec->id] = $rec;
            $row = $data->rows[$rec->id] = self::recToVerbal($rec, 'name');
            $row->name = ht::createLink($row->name, array($self, 'Single', $rec->id), NULL, "ef_icon={$self->singleIcon}");

            if(!$row->buzTel) $row->buzTel = $row->tel;

            if(!$row->buzEmail) $row->buzEmail = $row->email;
        }
    }


    /**
     * Рендира данните
     */
    static function renderNamedays($data)
    {
    	
        if(!count($data->rows)) return '';

        $tpl = new ET("<fieldset class='detail-info'>
                            <legend class='groupTitle'>" . tr('Именници във визитника') . "</legend>
                                <div class='groupList clearfix21'>
                                 [#persons#]
                            </div>
                            <!--ET_BEGIN regCourt--><div><b>[#regCourt#]</b></div><!--ET_END regCourt-->
                         </fieldset>");

        foreach($data->rows as $row) {
 
            $tpl->append("{$comma}<span style='font-weight:bold;'>{$row->name}</span>", 'persons');
            
            $comma = Mode::is('screenMode', 'narrow') ? '<br>' : ', ';
        }

        return $tpl;
    }



    /**
     * Обновява правилата за рутиране според наличните данни във визитката
     *
     * @param stdClass $rec
     */
    static function updateRoutingRules($rec)
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
     * @param mixed $emails един или повече имейли, зададени като стринг или като масив
     * @param int $objectId
     */
    public static function createRoutingRules($emails, $objectId)
    {
        // Приоритетът на всички правила, генериране след запис на визитка е нисък и намаляващ с времето
        $priority = email_Router::dateToPriority(dt::now(), 'low', 'desc');

            // Нормализираме параметъра $emails - да стане масив от имейл адреси
        if (!is_array($emails)) {
            $emails = type_Emails::toArray($emails);
        }

        foreach ($emails as $email) {
            // Създаване на `From` правило
            email_Router::saveRule(
                (object)array(
                    'type' => email_Router::RuleFrom,
                    'key' => email_Router::getRoutingKey($email, NULL, email_Router::RuleFrom),
                    'priority' => $priority,
                    'objectType' => 'person',
                    'objectId' => $objectId
                )
            );

            // Създаване на `Domain` правило
            if ($key = email_Router::getRoutingKey($email, NULL, email_Router::RuleDomain)) {
                // $key се генерира само за непублични домейни (за публичните е FALSE), така че това
                // е едновременно индиректна проверка дали домейнът е публичен.
                email_Router::saveRule(
                    (object)array(
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
     * @param int $id - id' то на записа
     * @return boolean TRUE/FALSE
     */
    static function shouldChargeVat($id)
    {
        $rec = static::fetch($id);
        
        if(!$rec->country) return TRUE;
        
        return drdata_Countries::isEu($rec->country);
    }
    
    
    /**
     * Връща данните на лицето
     * @param integer $id    - id' то на записа
     * @param email   $email - Имейл
     *
     * return object
     */
    static function getContragentData($id)
    {
        //Вземаме данните
        $person = crm_Persons::fetch($id);

        //Заместваме и връщаме данните
        if ($person) {
            $contrData = new stdClass();
            $contrData->company = crm_Persons::getVerbal($person, 'buzCompanyId');
            $contrData->companyId = $person->buzCompanyId;
            $contrData->person = $person->name;
            $contrData->country = crm_Persons::getVerbal($person, 'country');
            $contrData->countryId = $person->country;
            $contrData->pCode = $person->pCode;
            $contrData->place = $person->place;
            $contrData->email = $person->buzEmail;
            $contrData->tel = $person->buzTel;
            $contrData->fax = $person->buzFax;
            $contrData->address = $person->buzAddress;

            $contrData->pTel  = $person->tel;
            $contrData->pMobile  = $person->mobile;
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
     * @param integer $companyId - id на фирмата
     * 
     * @return string $res - Стринг с имейли
     */
    static function getGroupEmails($companyId)
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
        while($rec = $query->fetch()) {
            
            // Ако няма имейл, прескачаме
            if (!trim($rec->buzEmail)) continue;
            
            // Добавяме към резултата
            $res .= ($res) ? ', ' . $rec->buzEmail : $rec->buzEmail;
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
     * @return string HTML който да се покаже като обратна връзка за потребителя относно резултата
     *                 от импорта.
     */
    static function import($rec)
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
                $rec->birthday   = $vcard->getBday();

                $address = $vcard->getAddress();

                // За сега използваме първия адрес от първия възможен тип:
                $address = reset(reset($address));

                $rec->place    = $address['locality'];

                //
                // {{{ Извличане на държавата
                //
                $country = $address['country'];

                if (!empty($country) &&
                    !($rec->country = drdata_Countries::fetchField(array("#formalName = '[#1#]'", $country), 'id')) &&
                    !($rec->country = drdata_Countries::fetchField(array("#commonName = '[#1#]'", $country), 'id')) ) {
                    // Ако не можем да определим ключа на държавата, добавяме я към града, за
                    // да не се загуби напълно
                    $rec->place .= ", {$country}";
                }
                //
                // Край с държавата }}}
                //

                $rec->pcode    = $address['code'];
                $rec->address  = $address['street'];

                if ($organisation = $vcard->getOrganisation()) {
                    $rec->buzCompanyId = crm_Companies::fetchField(array("#name = '[#1#]'", $organisation), 'id');
                    // Записваме пълната организация към забележките, за да не се загуби
                    $rec->info = "Организация:\n===========\n" . implode("\n", $vcard->getOrganisation(TRUE)) . "\n\n";
                }

                //
                // {{{ Извличане на имейли - служебни и лични
                //
                $emails = $vcard->getEmails();

                $persEmails = array();
                $bizEmails  = array();

                // Приемаме, че имейлите без тип и от тип "home" са лични,
                // всички останали - служебни
                foreach ($emails as $type=>$list) {
                    if ($type == 'home' || $type == 0) {
                        $persEmails = array_merge($persEmails, $list);
                    } else {
                        $bizEmails  = array_merge($bizEmails, $list);
                    }
                }

                $rec->email    = implode(', ', array_unique($persEmails));
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
                    foreach ($bizFax as $i=>$num) {
                        if (in_array($num, $persTel)) {
                            unset($bizFax[$i]);
                        }
                    }
                }

                // Приемаме, че всички останали телефони са служебни
                foreach ($tels as $list) {
                    $bizTel = array_merge($bizTel, $list);
                }

                $rec->buzTel = implode(', ', array_unique($bizTel));
                $rec->buzFax = implode(', ', array_unique($bizFax));
                $rec->tel    = implode(', ', array_unique($persTel));
                $rec->mobile = implode(', ', array_unique($persMob));
                $rec->fax    = implode(', ', array_unique($persFax));
                //
                // Край с телефоните }}}
                //

                //
                // {{{ Снимка
                //
                if ($photoUrl == $vcard->getPhotoUrl()) {
                    // @TODO: Как да добавя файл в кофата 'pictures' когато знам URL-то му???
                }
                //
                // Край Снимка }}}
                //

                // Запис
                if (static::save($rec)) {
                    $res .= sprintf("<li>Добавен: %s</li>", $rec->name);
                } else {
                    $res .= sprintf("<li>Прочетен но НЕ записан: %s</li>", $rec->name);
                }
            }
        }

        $res = sprintf("<ul>%s</ul>", $res);

        return $res;
    }


    public static function act_Export()
    {
        $selected = NULL;

        if ($selected = Request::get('Selected')) {
            $selected = arr::make($selected);
            foreach ($selected as $i=>$id) {
                $selected[$i] = intval($selected[$i]);
            }
        } elseif ($id = Request::get('id', 'key(mvc=crm_Persons)')) {
            $selected = array($id);
        }

        $vcards = static::export($selected);

        pear_Vcard::httpRespond($vcards);

        shutdown();
    }


    public static function export($ids = NULL)
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
            if ($y>0 && $m>0 && $d>0) {
                // Всички компоненти на датата са зададени
                $vcard->setBday("{$y}-{$m}-{$d}");
            }
        }

        $vcard->addAddress(
            array(
                'street'   => $rec->address,
                'locality' => $row->place,
                'code'     => $row->pCode,
                'country'  => $row->country,
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

        static::addTelsToVcard($vcard, $rec->tel, array('TYPE'=>'HOME'));
        static::addTelsToVcard($vcard, $rec->mobile, array('TYPE'=>'CELL'));
        static::addTelsToVcard($vcard, $rec->fax, array('TYPE'=>'FAX'));
        static::addTelsToVcard($vcard, $rec->buzTel, array('TYPE'=>'WORK'));
        static::addTelsToVcard($vcard, $rec->buzFax, array('TYPE'=>'WORK,FAX'));

        static::addEmailsToVcard($vcard, $rec->emails, array('TYPE'=>'HOME'));
        static::addEmailsToVcard($vcard, $rec->buzEmails, array('TYPE'=>'WORK'));

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

       foreach ($params as $i=>$p) {
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

       foreach ($params as $i=>$p) {
           $params[$i] = arr::make($p);
       }

       $emails = type_Emails::toArray($emails);

       foreach ($emails as $email) {
           $vcard->addEmail($email, $params);
       }
   }
   
   
    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     */
    protected static function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
        // Ако е субмитната формата и не сме натиснали бутона "Запис и нов"
        if ($data->form && $data->form->isSubmitted() && $data->form->cmd == 'save') {

            // Променяма да сочи към single'a
            $data->retUrl = toUrl(array($mvc, 'single', $data->form->rec->id));
        }
    }
    
    
    /**
     * Функция, която задава правата за достъп до даден потребител в търсенето
     * 
     * Вземаме всики папки на които сме inCharge или са споделени с нас или са публични или 
     * (са екипни и inCharge е някой от нашия екип) и състоянието е активно
     * 
     * @param core_Query $query - Заявката към системата
     * @param int $userId - Потребителя, за който ще се отнася
     */
    static function applyAccessQuery(&$query, $userId = NULL)
    {
        // Ако няма зададен потребител
        if (!$userId) {
            
            // Вземаме текущия
            $userId = core_Users::getCurrent();
        }
        
        $user = "|" . $userId . "|";
        
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
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $conf = core_Packs::getConfig('crm');
    	
        $form = &$data->form;
        
        if(empty($form->rec->id)) {
            // Слагаме Default за поле 'country'
            $Countries = cls::get('drdata_Countries');
            $form->setDefault('country', $Countries->fetchField("#commonName = '" .
                    $conf->BGERP_OWN_COMPANY_COUNTRY . "'", 'id'));
        }
        
        // Ако сме в тесен режим
        if (Mode::is('screenMode', 'narrow')) {
            
            // Да има само 2 колони
            $data->form->setField('groupList', array('maxColumns' => 2));    
        }
        
        if(empty($form->rec->buzCompanyId)){
		    $form->setField('buzLocationId', 'input=none');
        }

        if(!$form->rec->id && $form->rec->buzCompanyId && isset($_GET['buzCompanyId'])) {  
            $form->setReadOnly('buzCompanyId');
        } else {
            $form->addAttr('buzCompanyId', array('onchange' => "addCmdRefresh(this.form); if(document.forms['{$form->formAttr['id']}'].elements['buzLocationId'] != undefined) document.forms['{$form->formAttr['id']}'].elements['buzLocationId'].value ='';this.form.submit();"));
        }
    	
        if($form->rec->buzCompanyId){
        	$locations = crm_Locations::getContragentOptions(crm_Companies::getClassId(), $form->rec->buzCompanyId);
			$form->setOptions('buzLocationId', $locations);
			if(!count($locations)){
				$form->setField('buzLocationId', 'input=none');
			}
        }
    }
    
    
    /**
     * Интерфейсен метод на incoming_CreateDocumentIntf
     * 
     * Връща масив, от който се създава бутона за създаване на входящ документ
     * 
     * @param fileman_Files $fRec - Обект са данни от модела
     * 
     * @return array $arr - Масив с данните
     * $arr['class'] - Името на класа
     * $arr['action'] - Екшъна
     * $arr['title'] - Заглавието на бутона
     * $arr['icon'] - Иконата
     */
    static function canCreate_($fRec)
    {
        // Позволените разширения, за създаване на визитка 
        $vCardExtArr = array('vcf', 'vcard');
        
        // Разширението на файла
        $ext = fileman_Files::getExt($fRec->name);
        
        // Ако разширението е в допустимите, имамем права за добваня и имаме права за single' а на файла
        if (in_array($ext, $vCardExtArr) && (static::haveRightFor('add') && (fileman_Files::haveRightFor('single', $fRec)))) {
            
            // Създаваме масива за съзване на визитка
        	$arr = array();
            $arr['vcard']['class'] = 'crm_Persons';
            $arr['vcard']['action'] = 'extractVcard';
            $arr['vcard']['title'] = 'Лице';
            $arr['vcard']['icon'] = '/img/16/extract_foreground_objects.png';
        }

        return (array)$arr;
    }
    
    
    /**
     * Екшън за извличане на информация за създаване на лице от визитка.
     */
    function act_ExtractVcard()
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
            static::checkSimilarWarning($class, $form);
        }
        
        // Ако формата е субмитнара успешно
        if($form->isSubmitted()) {
            
            // Опитваме се да форматираме населеното място
            if($rec->place) {
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
            return Redirect($retUrl);
        }
        
        // Задаваме текущия потребител да е отговорник по подразбиране
        $form->setDefault('inCharge', core_Users::getCurrent());

        // TODO какво ще се направи, когато имаме повече от една визитка в един файл?
//        $cntOfVcards = count($allVcards);
        
        // Добавяме титлата на формата
        $form->title = "Създавяне на потребител от визитка";
        
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
            
            // Проверяваме дали обръщението го има в масив, като го превеждаме
            $salutationKey = array_search(tr($currVcard['name']['prefix']), $salutationOpt);
            
            // Задаваме по подразбиране да е избано обръщението, което сме определили
            $form->setDefault('salutation', $salutationKey);    
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
            $workAddr = core_Array::extractMultidimensionArray($addressLabel, 'work', " | ");
            
            // Премахваме го от масива
            unset($addressLabel['work']);
            
            // Създаваме нов масив, където на първо място са домашните
            $newAddLabel['home'] = $addressLabel['home'];
            $newAddLabel['dom'] = $addressLabel['dom'];
            $newAddLabel += (array)$addressLabel;
            
            // Вземаме всички адреси, без служебния, като на първо място е домашния
            $homeAddr = core_Array::extractMultidimensionArray($addressLabel, FALSE, " | ");
        } else {
            
            // Създавме масив за всички адреси
            $addressArr = $currVcard['Address'];
            
            // Вземаме адреса на фирмата
            $workAddr = core_Array::extractMultidimensionArray($addressArr, 'work', " | ");
            
            // Премахваме го от масива
            unset($addressArr['work']);
            
            // Създаваме нов масив, където на първо място са домашните
            $newAddrArr['home'] = $addressArr['home'];
            $newAddrArr['dom'] = $addressArr['dom'];
            $newAddrArr += (array)$addressArr;
            
            // Вземаме всички адреси, без служебния, като на първо място е домашния
            $homeAddr = core_Array::extractMultidimensionArray($newAddrArr, FALSE, " | ");
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
        $buzPosition = ($role) ? "$jobTitle - $role" : $jobTitle;
        
        // Задаваме позицията на работата
        $form->setDefault('buzPosition', $buzPosition);
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png', array('order' => 1));
        $form->toolbar->addBtn('Отказ', getRetUrl(), array('order' => 10), 'ef_icon = img/16/close16.png');
        
        // Добавяме във формата информация, за да знаем коя визитка добавяме
//        $form->info = "Извличане на информация за първата визитка";
        
        return static::renderWrapping($form->renderHtml());
    }
    

    /**
     * Подготвяме рожденния ден. Ако няма въведение хубави данни, използваме ЕГН' то
     */
    protected static function prepareBirthday(&$rec)
    {
        list($y, $m, $d) = type_Combodate::toArray($rec->birthday);
    
        if(isset($rec->egn) && !($y>0 || $m>0 || $d>0)) {
            try {
                $Egn = new bglocal_BulgarianEGN($rec->egn);
            } catch(bglocal_exception_EGN $e) {
                $err = $e->getMessage();
            }

            if(!$err) {
                $rec->birthday = type_Combodate::create($Egn->birth_year, $Egn->birth_month, $Egn->birth_day);
            }
        }
    }
    
    
    /**
     * Проверява дали полето име и полето ЕГН се дублират. Ако се дублират сетваме грешка.
     */
    protected static function checkSimilarWarning($mvc, &$form)
    {
        $rec = $form->rec;
        
        // Правим проверка за дублиране с друг запис
        if(!$rec->id) {
            $nameL = strtolower(trim(STR::utf2ascii($rec->name)));

            $query = $mvc->getQuery();

            while($similarRec = $query->fetch(array("#searchKeywords LIKE '% [#1#] %'", $nameL))) {
                $similars[$similarRec->id] = $similarRec;
                $similarName = TRUE;
            }

            $egnNumb = preg_replace("/[^0-9]/", "", $rec->egn);

            if($egnNumb) {
                $query = $mvc->getQuery();

                while($similarRec = $query->fetch(array("#egn LIKE '[#1#]'", $egnNumb))) {
                    $similars[$similarRec->id] = $similarRec;
                }
                $similarEgn = TRUE;
            }

            if(count($similars)) {
                foreach($similars as $similarRec) {
                    $similarPersons .= "<li>";
                    $similarPersons .= ht::createLink($mvc->getVerbal($similarRec, 'name'), array($mvc, 'single', $similarRec->id), NULL, array('target' => '_blank'));

                    if($similarRec->egn) {
                        $similarPersons .= ", " . $mvc->getVerbal($similarRec, 'egn');
                    } elseif($birthday = $mvc->getverbal($similarRec, 'birthday')) {
                        $similarPersons .= ", " . $birthday;
                    }

                    if(trim($similarRec->place)) {
                        $similarPersons .= ", " . $mvc->getVerbal($similarRec, 'place');
                    }
                    $similarPersons .= "</li>";
                }

                $fields = ($similarEgn && $similarName) ? "name,egn" : ($similarName ? "name" : "egn");

                $sledniteLica = (count($similars) == 1) ? "следното лице" : "следните лица";

                $form->setWarning($fields, "Възможно е дублиране със {$sledniteLica}|*: <ul>{$similarPersons}</ul>");
            }
        }
    }
    
    
	/**
     * Връща папката на фирмата от бизнес имейла, ако имаме достъп до нея
     * 
     * @param email $email - Имейл, за който търсим
     * @param object $eContragentData - Контрагент данни за потребител
     * 
     * @return integet $fodlerId - id на папката
     */
    static function getFolderFromBuzEmail($email, &$pContragentData=NULL)
    {
        // Имейла в долния регистър
        $email = mb_strtolower($email);
    
        // Вземаме потребителя с такъв бизнес имейл
        $personRec = static::fetch(array("LOWER(#buzEmail) LIKE '%[#1#]%'", $email));
        
        // Ако има бизнес имейл и асоциирана фирма с потребителя
        if ($companyId = $personRec->buzCompanyId) {
            
            // Вземам контрагент данните за потребителя
            $pContragentData = crm_Persons::getContragentData($personRec->id);
            
            // Вземаме папката на фирмата
            $folderId = crm_Companies::forceCoverAndFolder($companyId);
              
            // Проверяваме дали имаме права за папката
            if (doc_Folders::haveRightFor('single', $folderId)) {

                return $folderId;
            }  
        }
        
        return FALSE;
    }
    
    
    /**
     * Връща папката на лицето от имейла, ако имаме достъп до нея
     * 
     * @param email $email - Имейл, за който търсим
     * 
     * @return integet $fodlerId - id на папката
     */
    static function getFolderFromEmail($email)
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
        
        return FALSE;
    }


    /**
     * Създава папка на лице по указаните данни
     */
    static function getPersonFolder($salutation, $name, $country, $pCode, $place, $address, $email, $tel, $website)
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
        $rec->tel   = $tel;
        $rec->website = $website;
        
         
        $Persons = cls::get('crm_Persons');
        
        $folderId = $Persons->forceCoverAndFolder($rec);
        
        return $folderId;
    }
    
	
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Никой да не може да изтрива
        if ($action == 'delete') {
            $requiredRoles = 'no_one';
        }
    }
    
    
    /**
     * След подготовка на тулбара за еденичен изглед
     */
    function on_AfterPrepareSingleToolbar($mvc, $data)
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
            if ($data->rec->id && haveRole('admin')) {
                
                // sysId на групата
                $crmId = crm_Groups::getIdFromSysId('users');
                
                // Ако е в групата на потребители
                if (keylist::isIn($crmId, $data->rec->groupList)) {
                    
                    // URL за създаване на потребител
                    $personUrl = array('core_Users', 'add', 'personId' => $data->rec->id, 'ret_url' => TRUE);
                    
                    // Добавяме бутона
                    $data->toolbar->addBtn(tr('Потребител'), $personUrl, 'id=btnUser', 'ef_icon = img/16/user_add.png');     
                }
            }
        }
    }
    
    
    /**
     * Връща пълния конкатениран адрес на контрагента
     * @param int $id - ид на контрагент
     * @return param $adress - адреса
     */
    public function getFullAdress($id)
    {
    	expect($rec = $this->fetchRec($id));
    	
    	$obj = new stdClass();
    	$tpl = new ET("[#country#]<br> <!--ET_BEGIN pCode-->[#pCode#] <!--ET_END pCode-->[#place#]<br> [#address#]");
    	if($rec->country){
    		$obj->country = crm_Persons::getVerbal($rec, 'country');
    	}
    
    	$Varchar = cls::get('type_Varchar');
    	foreach (array('pCode', 'place', 'address') as $fld){
    		if($rec->$fld){
    			$obj->$fld = $Varchar->toVerbal($rec->$fld);
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
     * @return string(3) - BGN|EUR|USD за дефолт валутата
     */
    public static function getDefaultCurrencyId($id)
    {
        $rec = self::fetch($id);

        // Ако контрагента няма държава, то дефолт валутата е BGN
    	if(empty($rec->country)) return 'BGN';
    	
    	// Ако държавата му е България, дефолт валутата е 'BGN'
    	if(drdata_Countries::fetchField($rec->country, 'letterCode2') == 'BG'){
    		
    		return 'BGN';
    	} else {
    		
    		// Ако не е 'България', но е в ЕС, дефолт валутата е 'EUR'
    		if(drdata_Countries::isEu($rec->country)){
    			
    			return 'EUR';
    		}
    	}
    	
    	// За всички останали е 'USD'
    	return 'USD';
    }

    
    /**
     * Форсира контрагент в дадена група
     * 
     * @param int $id -ид на продукт
     * @param varchar $groupSysId - sysId на група
     */
    public static function forceGroup($id, $groupSysId)
    {
    	expect($rec = static::fetch($id));
    	expect($groupId = crm_Groups::getIdFromSysId($groupSysId));
    	
    	// Ако контрагента не е включен в групата, включваме го
    	if(!keylist::isIn($groupId, $rec->groupList)){
    		$groupName = crm_Groups::getTitleById($groupId);
    		$rec->groupList = keylist::addKey($rec->groupList, $groupId);
    		
    		if(haveRole('powerUser')){
    			core_Statuses::newStatus(tr("|Лицето е включено в група |* '{$groupName}'"));
    		}
    		
    		return static::save($rec, 'groupList');
    	}
    	
    	return TRUE;
    }
    
    
    /**
     * Можели обекта да се добави като ресурс?
     *
     * @param int $id - ид на обекта
     * @return boolean - TRUE/FALSE
     */
    public function canHaveResource($id)
    {
    	$rec = $this->fetchRec($id);
    	$groupId = crm_Groups::getIdFromSysId('employees');
    	
    	// Само ако е от група "Служители"
    	if(keylist::isIn($groupId, $rec->groupList)){
    		return TRUE;
    	}
    	
    	return FALSE;
    }
    
     
    /**
     * Връща дефолт информация от източника на ресурса
     *
     * @param int $id - ид на обекта
     * @return stdClass $res  - обект с информация
     * 		o $res->name      - име
     * 		o $res->measureId - име мярка на ресурса (@see cat_UoM)
     * 		o $res->type      -  тип на ресурса (material,labor,equipment)
     */
    public function getResourceSourceInfo($id)
    {
    	$rec = $this->fetchRec($id);
    	
    	$res = new stdClass();
    	$res->name = $rec->name;
    	
    	// Основната мярка на ресурса е 'час'
    	$res->measureId = cat_UoM::fetchBySinonim('h')->id; 
    	
    	// Типа на ресурса ще е 'труд'
    	$res->type = 'labor'; 
    	
    	return $res;
    }
    
    
    /**
     * Връща мета дефолт мета данните на папката
     *
     * @param int $id - ид на папка
     * @return array $meta - масив с дефолт мета данни
     */
    public function getDefaultMeta($id)
    {
    	$rec = $this->fetchRec($id);
    	
    	$clientGroupId = crm_Groups::getIdFromSysId('customers');
    	$supplierGroupId = crm_Groups::getIdFromSysId('suppliers');
    	
    	$groups = crm_Groups::getQuery();
    	
    	$meta = array();
    	
    	// Ако контрагента е в група клиенти: дефолт свойствата са 'продаваем и производим'
    	if(keylist::isIn($clientGroupId, $rec->groupList)){
    		$meta['canSell'] = TRUE;
    		$meta['canManifacture'] = TRUE;
    	}
    	
    	// Ако контрагента е в група доставчици: дефолт свойствата са 'купуваем и вложим'
    	if(keylist::isIn($supplierGroupId, $rec->groupList)){
    		$meta['canConvert'] = TRUE;
    		$meta['canBuy'] = TRUE;
    	}
    	
    	return $meta;
    }
}
