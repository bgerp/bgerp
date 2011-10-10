<?php


/**
 * Константи за инициализиране на таблицата с контактите
 */
defIfNot('BGERP_OWN_COMPANY_ID', '1');


/**
 *  @todo Чака за документация...
 */
defIfNot('BGERP_OWN_COMPANY_NAME', 'Моята Фирма ООД');


/**
 *  @todo Чака за документация...
 */
defIfNot('BGERP_OWN_COMPANY_COUNTRY', 'Bulgaria');


/**
 * Фирми
 * 
 * Мениджър на фирмите
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    crm
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class crm_Companies extends core_Master
{
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = array(
                        // Интерфайс на всички счетоводни пера, които представляват контрагенти
                        'crm_ContragentAccRegIntf',
                        
                        // Интерфейс за разширяване на информацията за дадена фирма
                        'crm_CompanyExpanderIntf',
                        
                        // Интерфайс за всякакви счетоводни пера
                        'acc_RegisterIntf',

                         // Интерфейс за корица на папка
                         'doc_FolderIntf'
    );

    /**
     *  @todo Чака за документация...
     */
    var $title = "Фирми";
    
    
    /**
     *  Класове за автоматично зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_Printing, plg_State,
                     Groups=crm_Groups, crm_Wrapper, plg_SaveAndNew, plg_PrevAndNext,
                     plg_Sorting, fileman_Files, recently_Plugin, plg_Search, plg_Rejected,
                     acc_plg_Registry,doc_FolderPlg';
    
    
    /**
     *  Полетата, които ще видим в таблицата
     */
    var $listFields = 'id,tools=Пулт,nameList=Име,phonesBox=Комуникации,addressBox=Адрес';

    var $rowToolsField = 'tools';

    /**
     *  @todo Чака за документация...
     */
    var $searchFields = 'name,pCode,place,country,email,tel,fax,website,vatId';
    
    
    /**
     * Права
     */
    var $canWrite = 'crm,admin';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'crm,admin';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $features = 'place, country';
    
    
    /**
     * @var crm_Groups
     */
    var $Groups;
    
    
    /**
     *
     */
    var $singleLayoutFile = 'crm/tpl/SingleCompanyLayout.tpl';


    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        // Име на фирмата
        $this->FLD('name', 'varchar(255)', 'caption=Име,width=100%,mandatory,remember=info');
        $this->FNC('nameList', 'varchar', 'sortingLike=name');

        // Адресни данни
        $this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,allowEmpty)', 'caption=Държава,remember');
        $this->FLD('pCode', 'varchar(255)', 'caption=П. код,recently');
        $this->FLD('place', 'varchar(255)', 'caption=Град,width=100%');
        $this->FLD('address', 'varchar(255)', 'caption=Адрес,width=100%');
        
        // Комуникации
        $this->FLD('email', 'emails', 'caption=Е-мейл,width=100%');
        $this->FLD('tel', 'drdata_PhoneType', 'caption=Телефони,width=100%');
        $this->FLD('fax', 'drdata_PhoneType', 'caption=Факс,width=100%');
        $this->FLD('website', 'varchar(255)', 'caption=Web сайт,width=100%');
        
        // Данъчен номер на фирмата
        $this->FLD('vatId', 'drdata_VatType', 'caption=Данъчен №,remember=info,width=100%');
        
        // Допълнителна информация
        $this->FLD('info', 'richtext', 'caption=Бележки,height=150px');
        $this->FLD('logo', 'fileman_FileType(bucket=pictures)', 'caption=Лого');
        
        // Данни за съдебната регистрация
        $this->FLD('regCourt', 'varchar', 'caption=Решение по регистрация->Съдилище');
        $this->FLD('regDecisionNumber', 'int', 'caption=Решение по регистрация->Номер');
        $this->FLD('regDecisionDate', 'date', 'caption=Решение по регистрация->Дата');
        
        // Фирмено дело
        $this->FLD('regCompanyFileNumber', 'int', 'caption=Фирмено дело->Номер');
        $this->FLD('regCompanyFileYear', 'int', 'caption=Фирмено дело->Година');
        
        // В кои групи е?
        $this->FLD('groupList', 'keylist(mvc=crm_Groups,select=name)', 'caption=Групи->Групи,remember');

        // Състояние
        $this->FLD('state', 'enum(active=Активирано,rejected=Оттеглено)', 'caption=Състояние,value=active,notNull,input=none');
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
    function on_BeforePrepareListRecs($mvc, $res, $data)
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
                    $cond[0] .= ($cond[0]?' OR ':'') .
                    "(LOWER(#name) LIKE LOWER('[#{$i}#]%'))";
                    $cond[$i] = $a;
                    $i++;
                }
            }
            
            $data->query->where($cond);
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
    function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('order', 'enum(alphabetic=Азбучно,last=Последно добавени)', 
                                        'caption=Подредба,input,silent');
        $data->listFilter->FNC('groupId', 'key(mvc=crm_Groups,select=name,allowEmpty)', 
                                          'placeholder=Всички групи,caption=Група,input,silent');
        $data->listFilter->FNC('alpha', 'varchar', 'caption=Буква,input=hidden,silent');
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'search,order,groupId';
        
        $data->listFilter->input('alpha,search,order,groupId', 'silent');
    }
    
    
    /**
     * Премахване на бутон и добавяне на нови два в таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareListToolbar($mvc, $res, $data)
    {
        if($data->toolbar->removeBtn('btnAdd')) {
            $data->toolbar->addBtn('Нова фирма', 
                                    array('Ctr' => $this, 'Act'=>'Add', 'ret_url' => TRUE),
                                    'id=btnAdd,class=btn-add');
        }
    }
    
    
    /**
     * Модифициране на edit формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $form = $data->form;
        
        if(empty($form->rec->id)) {
            // Слагаме Default за поле 'country'
            $Countries = cls::get('drdata_Countries');
            $form->setDefault('country', $Countries->fetchField("#commonName = '" . 
                                                     BGERP_OWN_COMPANY_COUNTRY . "'", 'id' ));
        }
        
        for($i=1989; $i<=date('Y'); $i++) $years[$i] = $i;
        
        $form->setSuggestions('regCompanyFileYear', $years);
    }



    /**
     *
     */
    function on_AfterInputeditForm($mvc, $form)
    {
        $rec = $form->rec;
        
        if($form->isSubmitted()) {
            
            // Правим проверка за дублиране с друг запис
            if(!$rec->id) {
                $nameL = "#" . plg_Search::normalizeText( STR::utf2ascii($rec->name)) . "#";
                
                $buzType = arr::make(strtolower(STR::utf2ascii("АД,АДСИЦ,ЕАД,ЕООД,ЕТ,ООД,КД,КДА,СД,LTD,SRL")));
                
                foreach( $buzType as $word) {
                    $nameL = str_replace(array("#{$word}", "{$word}#"), array('', ''), $nameL);
                }
                
                $nameL = trim(str_replace('#', '', $nameL));

                $query = $mvc->getQuery();
                while($similarRec = $query->fetch(array("#searchKeywords LIKE '% [#1#] %'", $nameL))) {
                    $similars[$similarRec->id] = $similarRec;
                    $similarName = TRUE;
                }
                
                $query = $mvc->getQuery();
                if(trim($rec->vatId)) {
                    while($similarRec = $query->fetch(array("#vatId LIKE ' [#1#]'", trim($rec->vatId)))) {
                        $similars[$similarRec->id] = $similarRec;
                    }
                    $similarVat = TRUE;
                }
                
                if(count($similars)) {
                    foreach($similars as $similarRec) {
                        $similarCompany .= "<li>";
                        $similarCompany .= ht::createLink($similarRec->name, array($mvc, 'single', $similarRec->id), NULL, array('target' => '_blank'));
                        
                        if($similarRec->vatId) {
                            $similarCompany .= ", " . $mvc->getVerbal($similarRec, 'vatId');
                        } 

                        if(trim($similarRec->place)) {
                            $similarCompany .= ", " . $mvc->getVerbal($similarRec, 'place');
                        } else {
                            $similarCompany .= ", " . $mvc->getVerbal($similarRec, 'country');
                        }
                        $similarCompany .= "</li>";
                    }
                    
                    $fields = ($similarVat && $similarName) ? "name,vatId" : ($similarName ? "name" : "vatId");
                    
                    $sledniteFirmi = (count($similars) == 1) ? "следната фирма" : "следните фирми";

                    $form->setWarning($fields, "Възможно е дублиране със {$sledniteFirmi}|*: <ul>{$similarCompany}</ul>");
                }
            }
           
            if( $rec->place ) {
                $rec->place = drdata_Address::normalizePlace($rec->place);
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
    function on_BeforeRenderListTitle($mvc, $tpl, $data)
    {
        if($data->listFilter->rec->groupId) {
            $data->title = "Фирми в групата|* \"<b style='color:green'>" .
            $this->Groups->getTitleById($data->groupId) . "</b>\"";
        } elseif($data->listFilter->rec->search) {
            $data->title = "Фирми отговарящи на филтъра|* \"<b style='color:green'>" .
            type_Varchar::escape($data->listFilter->rec->search) .
            "</b>\"";
        } elseif($data->listFilter->rec->alpha) {
            if($data->listFilter->rec->alpha{0} == '0') {
                $data->title = "Фирми, които не започват с букви";
            } else {
                $data->title = "Фирми започващи с буквите|* \"<b style='color:green'>{$data->listFilter->rec->alpha}</b>\"";
            }
        } elseif(!$data->title) {
            $data->title = "Всички фирми";
        }
    }
    
    
    /**
     * Добавяне на табове
     *
     * @param core_Et $tpl
     * @return core_et $tpl
     */
    function renderWrapping_($tpl)
    {
        $mvc = $this;
        
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphavit'));
        
        $alpha = Request::get('alpha');
        
        $selected = 'none';
        
        $letters = arr::make('0-9,А-A,Б-B,В-V=В-V-W,Г-G,Д-D,Е-D,Ж-J,З-Z,И-I,Й-J,К-Q=К-K-Q,' .
        'Л-L,М-M,Н-N,О-O,П-P,Р-R,С-S,Т-T,У-U,Ф-F,Х-H=Х-X-H,Ц-Ч,Ш-Щ,Ю-Я', TRUE);
        
        foreach($letters as $a => $set) {
            $tabs->TAB($a, '|*' . str_replace('-', '<br>', $a), array($mvc, 'list', 'alpha' => $set));
            
            if($alpha == $set) {
                $selected = $a;
            }
        }
        
        $tpl = $tabs->renderHtml($tpl, $selected);
        
        //$tpl->prepend('<br>');
        
        return $tpl;
    }


    function on_AfterPrepareSingleTitle($mvc, $data)
    {
        $expanders = array('crm_Persons');

        foreach($expanders as $cls) {
            if(!isset($this->{$cls})) {
                $this->{$cls} =  cls::getInterface('crm_CompanyExpanderIntf', $cls);
            }
            
            $data->{$cls} = new stdClass();

            $this->{$cls}->prepareCompanyExpandData($data->{$cls}, $data->rec);
        }
    }


    function on_AfterRenderSingle($mvc, $tpl, $data)
    {
        $expanders = array('crm_Persons');

        foreach($expanders as $cls) {
            if(!isset($this->{$cls})) {
                $this->{$cls} =  cls::getInterface('crm_CompanyExpanderIntf', $cls);
            }
            
            $tpl->append($this->{$cls}->renderCompanyExpandData($data->{$cls}));
        }

    }
    
    
    /**
     * Промяна на данните от таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if($mvc->haveRightFor('single', $rec)) {
            $row->nameList = Ht::createLink(type_Varchar::escape($rec->name), array($this, 'single', $rec->id));
        } else {
            $row->nameList = type_Varchar::escape($rec->name);
        }

        $row->nameTitle = mb_strtoupper($rec->name);
        $row->nameLower = mb_strtolower($rec->name);
        
        $row->country = tr($row->country);
        
        // Fancy ефект за картинката
        $Fancybox = cls::get('fancybox_Fancybox');
        
        $tArr = array(200, 150);
        $mArr = array(600, 450);
        
        if($rec->logo) {
            $row->image = $Fancybox->getImage($rec->logo, $tArr, $mArr);
        } else {
            $row->image = "<img class=\"hgsImage\" src=" . sbf('img/noimage120.gif'). " alt='no image'>";
        }
        
        $country = tr($mvc->getVerbal($rec, 'country'));
        $pCode = $mvc->getVerbal($rec, 'pCode');
        $place = $mvc->getVerbal($rec, 'place');
        $address = $mvc->getVerbal($rec, 'address');
        
        $row->addressBox = $country;
        $row->addressBox .= ($pCode || $place) ? "<br>" : "";
        
        $row->addressBox .= $pCode ? "{$pCode} " : "";
        $row->addressBox .= $place;
        
        $row->addressBox .= $address ? "<br/>{$address}" : "";
        
        $tel = $mvc->getVerbal($rec, 'tel');
        $fax = $mvc->getVerbal($rec, 'fax');
        $eml = $mvc->getVerbal($rec, 'email');
        
        // phonesBox
        $row->phonesBox .= $tel ? "<div class='telephone'>{$tel}</div>" : "";
        $row->phonesBox .= $fax ? "<div class='fax'>{$fax}</div>" : "";
        $row->phonesBox .= $eml ? "<div class='email'>{$eml}</div>" : "";
        
        $row->title = $row->name;
        
        $vatType = new drdata_VatType();
        
        $vat = $vatType->toVerbal($rec->vatId);
        
        $row->title .= ($row->country ? ", " : "") . $row->country;
        $row->title .= ($vat ? "&nbsp;&nbsp;<div style='float:right'>{$vat}</div>" : "");
        $row->nameList .= ($vat ? "<div style='font-size:0.8em;margin-top:5px;'>{$vat}</div>" : "");
        
        //bp($row);
        // END phonesBox
    }
    
    
    /**
     *  След всеки запис (@see core_Mvc::save_())
     */
    function on_AfterSave(crm_Companies $mvc, $id, $rec)
    {
        $mvc->updateGroupsCnt();
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
                $groupsRec->companiesCnt = $cnt;
                $groupsRec->id = $groupId;
                $this->Groups->save($groupsRec, 'companiesCnt');
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
    function on_AfterSetupMvc($mvc, &$res)
    {
        if (!$mvc->fetch(BGERP_OWN_COMPANY_ID)){
            
            $rec = new stdClass();
            $rec->id = BGERP_OWN_COMPANY_ID;
            $rec->name = BGERP_OWN_COMPANY_NAME;
            
            // Страната не е стринг, а id
            $Countries = cls::get('drdata_Countries');
            $rec->country = $Countries->fetchField("#commonName = '" . BGERP_OWN_COMPANY_COUNTRY . "'", 'id' );
            
            if($mvc->save($rec, NULL, 'REPLACE')) {
                
                $res .= "<li style='color:green'>Фирмата " . BGERP_OWN_COMPANY_NAME . " е записана с #id=" .
                BGERP_OWN_COMPANY_ID . " в базата с контктите</li>";
            }
        }
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('pictures', 'Снимки', 'jpg,jpeg', '3MB', 'user', 'every_one');
    }
    
    
    
    
    /*******************************************************************************************
     * 
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_ContragentAccRegIntf
     * 
     ******************************************************************************************/
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    static function getItemRec($objectId)
    {
    	$self = cls::get(__CLASS__);
    	$result = null;
    	
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
    	
    	if ($rec  = $self->fetch($objectId)) {
    		$result = ht::createLink($rec->name, array($self, 'Single', $objectId)); 
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
    
    /**
     * КРАЙ НА интерфейса @see acc_RegisterIntf
     */
}