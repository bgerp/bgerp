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
    var $loadList = 'plg_Created, plg_RowTools,  plg_LastUsedKeys,plg_Rejected, plg_Select,
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
    public $details = 'ContragentLocations=crm_Locations';
    

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
        $this->FLD('birthday', 'combodate', 'caption=Рожден ден');

        // Адресни данни
        $this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,allowEmpty)', 'caption=Държава,remember,class=contactData');
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

        // Лична карта
        $this->FLD('idCardNumber', 'varchar(16)', 'caption=Лична карта->Номер');
        $this->FLD('idCardIssuedOn', 'date', 'caption=Лична карта->Издадена на');
        $this->FLD('idCardExpiredOn', 'date', 'caption=Лична карта->Валидна до');
        $this->FLD('idCardIssuedBy', 'varchar(64)', 'caption=Лична карта->Издадена от');

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
        if($data->listFilter->rec->order == 'alphabetic' || !$data->listFilter->rec->order) {
            $data->query->orderBy('#name');
        } elseif($data->listFilter->rec->order == 'last') {
            $data->query->orderBy('#createdOn=DESC');
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
        $data->listFilter->FNC('order', 'enum(alphabetic=Азбучно,last=Последно добавени)', 'caption=Подредба,input,silent');
        $data->listFilter->FNC('groupId', 'key(mvc=crm_Groups,select=name,allowEmpty)', 'placeholder=Всички групи,caption=Група,input,silent');
        $data->listFilter->FNC('alpha', 'varchar', 'caption=Буква,input=hidden,silent');

        $data->listFilter->view = 'horizontal';

        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');

        // Показваме само това поле. Иначе и другите полета
        // на модела ще се появят
        $data->listFilter->showFields = 'search,order,groupId';

        $data->listFilter->input('alpha,search,order,groupId', 'silent');
        
        // Ако се подреждат по последно, се добавя полето Създаване
        if($data->listFilter->rec->order == 'last') {
            $data->listFields['createdOn'] = 'Създаване';
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
            $form->setDefault('country', $Countries->fetchField("#commonName = '" . $conf->BGERP_OWN_COMPANY_COUNTRY . "'", 'id'));
        }

        $mvrQuery = drdata_Mvr::getQuery();

        while($mvrRec = $mvrQuery->fetch()) {
            $mvrName = 'МВР - ';
            $mvrName .= drdata_Mvr::getVerbal($mvrRec, 'city');
            $mvrSug[$mvrName] = $mvrName;
        }

        $form->setSuggestions('idCardIssuedBy', $mvrSug);
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

        if(isset($rec->egn) && ($rec->birthday == '??-??-????')) {
            try {
                $Egn = new drdata_BulgarianEGN($rec->egn);
            } catch(Exception $e) {
                $err = $e->getMessage();
            }

            if(!$err) {
                $rec->birthday = $Egn->birth_day . "-" . $Egn->birth_month . "-" . $Egn->birth_year;
            }
        }

        if($form->isSubmitted()) {

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
            } elseif(!Mode::is('screenMode', 'narrow')) {
                    $row->image = "<img class=\"hgsImage\" src=" . sbf('img/noimage120.gif') . " alt='no image'>";
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
    static function on_AfterSave($mvc, &$id, $rec)
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
            list($d, $m, $y) = explode('-', $rec->birthday);
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
                if($y && ($y > $year)) continue;
                
                $calRec = new stdClass();
                
                // Ключ на събитието
                $calRec->key = $prefix . '-' . $year;
                
                // TODO да се проверява за високосна година
                $calRec->time = date('Y-m-d 00:00:00', mktime(0, 0, 0, $m, $d, $year) );

                $calRec->type = 'birthday';
                $calRec->allDay = 'yes';
                
                $calRec->title = "ЧРД {$rec->name}";

                if($y > 0) {
                    $calRec->title .= " на " . ($year - $y) . " г.";
                }
                
                $calRec->users = '';

                $calRec->url = toUrl(array('crm_Persons', 'Single', $id), 'local');

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

        if(count($groupsCnt)) {
            foreach($groupsCnt as $groupId => $cnt) {
                $groupsRec = new stdClass();
                $groupsRec->personsCnt = $cnt;
                $groupsRec->id = $groupId;
                crm_Groups::save($groupsRec, 'personsCnt');
            }
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
                if($rec->state == 'active') {
                    $rec->state = 'closed';
                }

                $mvc->save($rec, 'state');
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
        $query = self::getQuery();
        
        foreach($data->namesArr as $name) { 
            $query->orWhere("#searchKeywords LIKE ' {$name} %'");
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
                            <legend class='groupTitle'>" . tr('От визитника') . "</legend>
                                <div class='groupList,clearfix21'>
                                 [#persons#]
                            </div>
                            <!--ET_BEGIN regCourt--><div><b>[#regCourt#]</b></div><!--ET_END regCourt-->
                         </fieldset>");

        foreach($data->rows as $row) {
 
            $tpl->append("<span style='font-weight:bold;'>{$row->name}</span>{$comma} ", 'persons');
            
            $comma = Mode::is('screenMode', 'narrow') ? '<br>' : ',';
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
            $birthday = static::instance()->getField('birthday')->type->toVerbal($rec->birthday, 'Y,m,d');
            if (strlen($birthday) == 10) {
                // Всички компоненти на датата са зададени
                $vcard->setBday($birthday);
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
}