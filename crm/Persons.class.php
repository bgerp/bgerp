<?php



/**
 * Мениджър на физическите лица
 *
 *
 * @category  bgerp
 * @package   crm
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
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
                     crm_Wrapper, crm_AlphabetWrapper, plg_SaveAndNew, plg_PrevAndNext,  plg_Printing, plg_State,
                     plg_Sorting, recently_Plugin, plg_Search, acc_plg_Registry, doc_FolderPlg,
                     bgerp_plg_Importer, groups_Extendable';


    /**
     * Полета, които се показват в листови изглед
     */
    var $listFields = 'numb=№,nameList=Име,phonesBox=Комуникации,addressBox=Адрес,tools=Пулт,name=';


    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';


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
    var $canWrite = 'user';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'user';


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
    public $details = '';
    

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
        $this->FLD('egn', 'drdata_EgnType', 'caption=ЕГН');

        // Дата на раждане
        $this->FLD('birthday', 'combodate(minYear=1850,maxYear=' . date('Y') . ')', 'caption=Рожден ден');

        // Адресни данни
        $this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Държава,remember,class=contactData');
        $this->FLD('pCode', 'varchar(16)', 'caption=Пощ. код,recently,class=pCode');
        $this->FLD('place', 'varchar(64)', 'caption=Нас. място,class=contactData');
        $this->FLD('address', 'varchar(255)', 'caption=Адрес,class=contactData');

        // Служебни комуникации
        $this->FLD('buzCompanyId', 'key(mvc=crm_Companies,select=name,allowEmpty)', 
            'caption=Служебни комуникации->Фирма,oldFieldName=buzCumpanyId,class=contactData');
        $this->FLD('buzPosition', 'varchar(64)', 'caption=Служебни комуникации->Длъжност,class=contactData');
        $this->FLD('buzEmail', 'email', 'caption=Служебни комуникации->Имейл,class=contactData');
        $this->FLD('buzTel', 'drdata_PhoneType', 'caption=Служебни комуникации->Телефони,class=contactData');
        $this->FLD('buzFax', 'drdata_PhoneType', 'caption=Служебни комуникации->Факс,class=contactData');
        $this->FLD('buzAddress', 'varchar(255)', 'caption=Служебни комуникации->Адрес,class=contactData');

        // Лични комуникации
        $this->FLD('email', 'emails', 'caption=Лични комуникации->Имейли,class=contactData');
        $this->FLD('tel', 'drdata_PhoneType', 'caption=Лични комуникации->Телефони,class=contactData');
        $this->FLD('mobile', 'drdata_PhoneType', 'caption=Лични комуникации->Мобилен,class=contactData');
        $this->FLD('fax', 'drdata_PhoneType', 'caption=Лични комуникации->Факс,class=contactData');
        $this->FLD('website', 'url', 'caption=Лични комуникации->Сайт/Блог,class=contactData');

        // Допълнителна информация
        $this->FLD('info', 'richtext(bucket=crmFiles)', 'caption=Информация->Бележки,height=150px,class=contactData');
        $this->FLD('photo', 'fileman_FileType(bucket=pictures)', 'caption=Информация->Фото');

        // В кои групи е?
        $this->FLD('groupList', 'keylist(mvc=crm_Groups,select=name)', 'caption=Групи->Групи,remember,silent');

        // Състояние
        $this->FLD('state', 'enum(active=Вътрешно,closed=Нормално,rejected=Оттеглено)', 'caption=Състояние,value=closed,notNull,input=none');
    }


    /**
     * Подредба и филтър на on_BeforePrepareListRecs()
     * Манипулации след подготвянето на основния пакет данни
     * предназначен за рендиране на списъчния изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        // Подредба
        setIfNot($data->listFilter->rec->order, 'alphabetic');
        $orderCond = $mvc->listOrderBy[$data->listFilter->rec->order][1];
        if($orderCond) {
            $data->query->orderBy($orderCond);
        }
         
        if($data->listFilter->rec->alpha) {
            if($data->listFilter->rec->alpha{0} == '0') {
                $cond = "#name NOT REGEXP '^[a-zA-ZА-Яа-я]'";
            } else {
                $alphaArr = explode('-', $data->listFilter->rec->alpha);
                $cond = array();
                $i = 1;

                foreach($alphaArr as $a) {
                    $cond[0] .= ($cond[0] ? ' OR ' : '') .
                    "(LOWER(#name) LIKE LOWER('[#{$i}#]%'))";
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
                $data->title = "Именници на <font color='green'>" . dt::mysql2verbal($date, 'd-m-Y, l') . "</font>";
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
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('users', 'users', 'caption=Потребител,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
        $data->listFilter->setDefault('users', 'all_users'); 
       
        // Подготовка на полето за подредба
        foreach($mvc->listOrderBy as $key => $attr) {
            $options[$key] = $attr[0];
        }
        $orderType = cls::get('type_Enum');
        $orderType->options = $options;

        $data->listFilter->FNC('order', $orderType,'caption=Подредба,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
                                         
        $data->listFilter->FNC('groupId', 'key(mvc=crm_Groups,select=name,allowEmpty)', 'placeholder=Всички групи,caption=Група,input,silent');
        $data->listFilter->FNC('alpha', 'varchar', 'caption=Буква,input=hidden,silent', array('attr' => array('onchange' => 'this.form.submit();')));

        $data->listFilter->view = 'horizontal';

        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');

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
                $data->toolbar->addBtn('Ново лице', array('Ctr' => $mvc, 'Act' => 'Add', "groupList[{$groupId}]" => 'on'), 'id=btnAdd,class=btn-add');
            } else {
                $data->toolbar->addBtn('Ново лице', array('Ctr' => $mvc, 'Act' => 'Add'), 'id=btnAdd,class=btn-add');
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
            $data->title = "Лица в групата|* \"<b style='color:green'>" .
            crm_Groups::getTitleById($data->groupId) . "</b>\"";
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
                $rec->place = drdata_Address::canonizePlace($rec->place);
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
        $row->numb = $rec->id;
        
        if($fields['-single']) {

            // Fancy ефект за картинката
            $Fancybox = cls::get('fancybox_Fancybox');

            $tArr = array(200, 150);
            $mArr = array(600, 450);

            if($rec->photo) {
                $row->image = $Fancybox->getImage($rec->photo, $tArr, $mArr);
            } else {
                if($rec->email) {
                    $emlArr = type_Emails::toArray($rec->email);
                    $imgUrl = avatar_Gravatar::getUrl($emlArr[0], 120);
                } elseif($rec->buzEmail) {
                    $imgUrl = avatar_Gravatar::getUrl($rec->buzEmail, 120);
                } elseif(!Mode::is('screenMode', 'narrow')) {
                    $imgUrl = sbf('img/noimage120.gif');
                }
                
                if($imgUrl) {
                    $row->image = "<img class=\"hgsImage\" src=" . $imgUrl . " alt='no image'>";
                }

            }
        }

        $country = tr($mvc->getVerbal($rec, 'country'));
        $pCode = $mvc->getVerbal($rec, 'pCode');
        $place = $mvc->getVerbal($rec, 'place');
        $address = $mvc->getVerbal($rec, 'address');


        if($fields['-list']) {
            $row->nameList = $row->name;

            $row->addressBox = $country;
            $row->addressBox .= ($pCode || $place) ? "<br>" : "";

            $row->addressBox .= $pCode ? "{$pCode} " : "";
            $row->addressBox .= $place;

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
        }

        $row->title = $row->name;

        $row->title .= ($row->country ? ", " : "") . $country;

        $birthday = trim($mvc->getVerbal($rec, 'birthday'));

        if($birthday) {
            $row->title .= "&nbsp;&nbsp;<div style='float:right'>{$birthday}</div>";

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
            $row->nameList .= "<div style='font-size:0.8em;margin-top:5px;'>$dateType:&nbsp;{$birthday}</div>";
        } elseif($rec->egn) {
            $egn = $mvc->getVerbal($rec, 'egn');
            $row->title .= "&nbsp;&nbsp;<div style='float:right'>{$egn}</div>";
            $row->nameList .= "<div style='font-size:0.8em;margin-top:5px;'>{$egn}</div>";
        }

        if($rec->buzCompanyId && crm_Companies::haveRightFor('single', $rec->buzCompanyId)) {
            $row->buzCompanyId = ht::createLink($mvc->getVerbal($rec, 'buzCompanyId'), array('crm_Companies', 'single', $rec->buzCompanyId));
            $row->nameList .= "<div>{$row->buzCompanyId}</div>";
        }
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

                $calRec->url = toUrl(array('crm_Persons', 'Single', $id), 'local');

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
            $keyArr = type_Keylist::toArray($rec->groupList);

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
                                <div class='groupList,clearfix21'>
                                 [#persons#]
                            </div>
                            <!--ET_BEGIN regCourt--><div><b>[#regCourt#]</b></div><!--ET_END regCourt-->
                         </fieldset>");

        foreach($data->rows as $row) {
            $tpl->append("<div style='padding:5px; float:left;min-width:300px;'>", 'persons');

            $tpl->append("<div style='font-weight:bold;'>{$row->name}</div>", 'persons');

            if($row->mobile) {
                $tpl->append("<div class='mobile'>{$row->mobile}</div>", 'persons');
            }

            if($row->buzTel) {
                $tpl->append("<div class='telephone'>{$row->buzTel}</div>", 'persons');
            }

            if($row->email) {
                $tpl->append("<div class='email'>{$row->email}</div>", 'persons');
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
            $query->orWhere("#searchKeywords LIKE ' {$name} %' AND (#inCharge = '{$currentId}' OR #shared LIKE '|{$currentId}|')");
        }
        
        $self = cls::get('crm_Persons');

        while($rec = $query->fetch()) { ;
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
                                <div class='groupList,clearfix21'>
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
            $contrData->name = $person->name;
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
        }

        return $contrData;
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
     * 
     */
    function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
        // Ако е субмитната формата и не сме натиснали бутона "Запис и нов"
        if ($data->form && $data->form->isSubmitted() && $data->form->cmd != 'save_n_new') {

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
     * @param crm_Persons $query - Заявката към системата
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
        $query->where("#state = 'active'");
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
    	
        $form = $data->form;
        
        if(empty($form->rec->id)) {
            // Слагаме Default за поле 'country'
            $Countries = cls::get('drdata_Countries');
            $form->setDefault('country', $Countries->fetchField("#commonName = '" .
                    $conf->BGERP_OWN_COMPANY_COUNTRY . "'", 'id'));
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
            
            // Данните за визитката от съответния драйвер
            $data = array();
            
            // Опитваме се да подготвим данните
            try {
                
                // Подготвяме данните
                $data = $driver->prepareData($fRec);
            } catch (Exception $e) { }

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
                $rec->place = drdata_Address::canonizePlace($rec->place);
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
        $form->toolbar->addSbBtn('Запис', 'save', array('class' => 'btn-save'), array('order' => 1));
        $form->toolbar->addBtn('Отказ', getRetUrl(), array('class' => 'btn-cancel'), array('order' => 10));
        
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
                $Egn = new drdata_BulgarianEGN($rec->egn);
            } catch(Exception $e) {
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
}
