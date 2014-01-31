<?php



/**
 * Мениджър на физическите лица
 *
 *
 * @category  bgerp
 * @package   crm
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
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
                     bgerp_plg_Importer';


    /**
     * Полета, които се показват в листови изглед
     */
    var $listFields = 'id=№,id,nameList=Име,phonesBox=Комуникации,addressBox=Адрес,name=';


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
    var $searchFields = 'name,egn,country,place,email,info';


    /**
     * Кой  може да пише?
     */
    var $canWrite = 'powerUser';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'powerUser';
    
    
	/**
	 * Кой може да го разглежда?
	 */
	var $canList = 'powerUser';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'powerUser';
    
	
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
                    ContragentBankAccounts=bank_Accounts,ObjectLists=acc_Items,IdCard=crm_ext_IdCards,CustomerSalecond=cond_ConditionsToCustomers';
    

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
        $this->FLD('name', 'varchar(255)', 'caption=Имена,class=contactData,mandatory,remember=info');
        $this->FNC('nameList', 'varchar', 'sortingLike=name');

        // Единен Граждански Номер
        $this->FLD('egn', 'bglocal_EgnType', 'caption=ЕГН');

        // Дата на раждане
        $this->FLD('birthday', 'combodate(minYear=1850,maxYear=' . date('Y') . ')', 'caption=Рожден ден');

        // Адресни данни
        $this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Държава,remember,class=contactData');
        $this->FLD('pCode', 'varchar(16)', 'caption=П. код,recently,class=pCode');
        $this->FLD('place', 'varchar(64)', 'caption=Град,class=contactData,hint=Населено място: град или село и община');
        $this->FLD('address', 'varchar(255)', 'caption=Адрес,class=contactData');

        // Служебни комуникации
        $this->FLD('buzCompanyId', 'key(mvc=crm_Companies,select=name,allowEmpty, where=#state !\\= \\\'rejected\\\')', 
            'caption=Служебни комуникации->Фирма,oldFieldName=buzCumpanyId,class=contactData');
        $this->FLD('buzPosition', 'varchar(64)', 'caption=Служебни комуникации->Длъжност,class=contactData');
        $this->FLD('buzEmail', 'emails', 'caption=Служебни комуникации->Имейли,class=contactData');
        $this->FLD('buzTel', 'drdata_PhoneType', 'caption=Служебни комуникации->Телефони,class=contactData');
        $this->FLD('buzFax', 'drdata_PhoneType', 'caption=Служебни комуникации->Факс,class=contactData');
        $this->FLD('buzAddress', 'varchar(255)', 'caption=Служебни комуникации->Адрес,class=contactData');

        // Лични комуникации
        $this->FLD('email', 'emails', 'caption=Лични комуникации->Имейли,class=contactData');
        $this->FLD('tel', 'drdata_PhoneType', 'caption=Лични комуникации->Телефони,class=contactData,silent');
        $this->FLD('mobile', 'drdata_PhoneType', 'caption=Лични комуникации->Мобилен,class=contactData,silent');
        $this->FLD('fax', 'drdata_PhoneType', 'caption=Лични комуникации->Факс,class=contactData,silent');
        $this->FLD('website', 'url', 'caption=Лични комуникации->Сайт/Блог,class=contactData');

        // Допълнителна информация
        $this->FLD('info', 'richtext(bucket=crmFiles)', 'caption=Информация->Бележки,height=150px,class=contactData');
        $this->FLD('photo', 'fileman_FileType(bucket=pictures)', 'caption=Информация->Фото');

        // В кои групи е?
        $this->FLD('groupList', 'keylist(mvc=crm_Groups,select=name,where=#allow !\\= \\\'companies\\\')', 'caption=Групи->Групи,remember,silent');

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
        $data->listFilter->FNC('users', 'users(rolesForAll = officer|manager|ceo, rolesForTeams = officer|manager|ceo|executive)', 'caption=Потребител,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
        
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

        $data->listFilter->FNC('order', $orderType,'caption=Подредба,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
                                         
        $data->listFilter->FNC('groupId', 'key(mvc=crm_Groups,select=name,allowEmpty)', 'placeholder=Всички групи,caption=Група,input,silent', 
            array('attr' => array('onchange' => 'this.form.submit();')));
        $data->listFilter->FNC('alpha', 'varchar', 'caption=Буква,input=hidden,silent', array('attr' => array('onchange' => 'this.form.submit();')));

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
                $data->title = "Именници на <font color='green'>" . dt::mysql2verbal($date, 'd.m.Y, l') . "</font>";
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
                $data->toolbar->addBtn('Ново лице', array('Ctr' => $mvc, 'Act' => 'Add', "groupList[{$groupId}]" => 'on'), 'id=btnAdd', 'ef_icon = img/16/star_2.png');
            } else {
                $data->toolbar->addBtn('Ново лице', array('Ctr' => $mvc, 'Act' => 'Add'), 'id=btnAdd', 'ef_icon = img/16/star_2.png');
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
        }

        $row->country = $mvc->getVerbal($rec, 'country');
        $pCode = $mvc->getVerbal($rec, 'pCode');
        $place = $mvc->getVerbal($rec, 'place');
        $address = $mvc->getVerbal($rec, 'address');


        if($fields['-list']) {
            
            // Дали има права single' а на този потребител
            $canSingle = static::haveRightFor('single', $rec);
            
            $row->nameList = $row->name;

            $row->addressBox = $row->country;
            $row->addressBox .= ($pCode || $place) ? "<br>" : "";

            $row->addressBox .= $pCode ? "{$pCode} " : "";
            $row->addressBox .= $place;

            // Ако имаме права за сингъл
            if ($canSingle) {
                
                // Добавяме адреса
                $row->addressBox .= $address ? "<br/>{$address}" : "";    
            
                // Мобилен телефон
                $mob = $mvc->getVerbal($rec, 'mobile');
                $row->phonesBox .= $mob ? "<div class='mobile'>{$mob}</div>" : "";
                
                // Телефон
                $tel = $mvc->getVerbal($rec, $rec->buzTel ? 'buzTel' : 'tel');
                $row->phonesBox .= $tel ? "<div class='telephone'>{$tel}</div>" : "";
                
                // Факс
                $fax = $mvc->getVerbal($rec, $rec->buzFax ? 'buzFax' : 'fax');
                $row->phonesBox .= $fax ? "<div class='fax'>{$fax}</div>" : "";
                
                // Email
                $eml = $mvc->getVerbal($rec, $rec->buzEmail ? 'buzEmail' : 'email');
                $row->phonesBox .= $eml ? "<div class='email'>{$eml}</div>" : "";
    
                $row->phonesBox = "<div style='max-width:400px;'>{$row->phonesBox}</div>";
            } else {
                
                // Добавяме линк към профила на потребителя, който е inCharge на визитката
                $row->phonesBox = crm_Profiles::createLink($rec->inCharge);
            }
        }
        
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
            $row->nameList .= "<div style='font-size:0.8em;margin:3px;'>$dateType:&nbsp;{$birthday}</div>";
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
     * Добавя номера за лицето
     */
    static function updateNumbers($rec)
    {
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
        
        // Добавяме номерата в КЦ
        return callcenter_Numbers::addNumbers($numbersArr, $classId, $rec->id);
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
                
                $calRec->users = '';

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
                'num' => $rec->id,
                'title' => $rec->name,
                'features' => 'foobar' // @todo!
            );
        }

        return $result;
    }


    /**
     * @see crm_ContragentAccRegIntf::getLinkToObj
     * @param int $objectId
     */
    static function getLinkToObj($objectId)
    {
        $self = cls::get(__CLASS__);

        if ($rec = $self->fetch($objectId)) {
            $result = ht::createLink(static::getVerbal($rec, 'name'), array($self, 'Single', $objectId));
        } else {
            $result = '<i>неизвестно</i>';
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

        while($rec = $query->fetch()) { ;
            $data->recs[$rec->id] = $rec;
            $row = $data->rows[$rec->id] = $this->recToVerbal($rec, 'name,mobile,tel,email,buzEmail,buzTel');
            $row->name = ht::createLink($row->name, array($this, 'Single', $rec->id));

            if(!$row->buzTel) $row->buzTel = $row->tel;

            if(!$row->buzEmail) $row->buzEmail = $row->email;
        }
    }


    /**
     * Рендира данните
     */
    function renderCompanyExpandData($data)
    {
        if(!count($data->rows)) return '';

        $tpl = new ET("<fieldset class='detail-info'>
                            <legend class='groupTitle'>" . tr('Представители') . "</legend>
                                <div class='groupList clearfix21'>
                                 [#persons#]
                            </div>
                            <!--ET_BEGIN regCourt--><div><b>[#regCourt#]</b></div><!--ET_END regCourt-->
                         </fieldset>");

        foreach($data->rows as $row) {
            $tpl->append("<div>", 'persons');

            $tpl->append("<div style='font-weight:bold;'>{$row->name}</div>", 'persons');

            if($row->mobile) {
                $tpl->append("<div class='mobile'>{$row->mobile}</div>", 'persons');
            }

            if($row->buzTel) {
                $tpl->append("<div class='telephone'>{$row->buzTel}</div>", 'persons');
            }

            if($row->buzEmail) {
                $tpl->append("<div class='email'>{$row->buzEmail}</div>", 'persons');
            }

            $tpl->append("</div>", 'persons');

            if ($i ++ % 2 == 1) {
                $tpl->append("<div class='clearfix21'></div>", 'persons');
            }
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
    protected static function createRoutingRules($emails, $objectId)
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
            // Опит за конвертиране на рожденната дата във 
