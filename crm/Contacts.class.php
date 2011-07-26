<?php


/**
 * Константи за инициализиране на таблицата с контактите
 */
defIfNot('BGERP_OWN_COMPANY_ID', '1');


/**
 *  @todo Чака за документация...
 */
defIfNot('BGERP_OWN_COMPANY_NAME', 'MyCompany');


/**
 *  @todo Чака за документация...
 */
defIfNot('BGERP_OWN_COMPANY_COUNTRY', 'Bulgaria');


/**
 * Регистър на контактите
 */
class contacts_Contacts extends core_Master implements intf_Register
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Контакти";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Контакти";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_AccRegistry, plg_Printing,
                     Groups=contacts_Groups,
                     contacts_Wrapper,
                     plg_Sorting, fileman_Files, recently_Plugin';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id,type,nameList=Име,country,addressBox,phonesBox'; // Полетата, които ще видим в таблицата
    
    
    /**
     * Права
     */
    var $canWrite = 'contacts,admin';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'contacts,admin';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $features = 'place, country';
    
    
    /**
     * @var contacts_Groups
     */
    var $Groups;
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('type', 'enum(person=Лице, company=Компания)', 'caption=Тип, width=400px, input=hidden, silent');
        $this->FLD('name', 'varchar(255)', 'caption=Име, width=400px,mandatory,remember=info');
        $this->FLD('birthday', 'combodate', 'caption=Рожден ден, input=none');
        $this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,allowEmpty)', 'caption=Държава,remember,width=400px');
        $this->FLD('pCode', 'varchar(255)', 'caption=Код,width=300px,recently');
        $this->FLD('place', 'varchar(255)', 'caption=Град,mandatory,width=100%');
        $this->FLD('address', 'varchar(255)', 'caption=Адрес,mandatory,width=100%');
        $this->FLD('email', 'email', 'caption=Email,mandatory,width=100%');
        $this->FLD('tel', 'varchar(64)', 'caption=Телефон,width=100%');
        $this->FLD('mobile', 'varchar(64)', 'caption=Мобилен,width=100%');
        $this->FLD('fax', 'varchar(64)', 'caption=Факс,width=100%');
        $this->FLD('website', 'varchar(255)', 'caption=Web сайт,width=100%');
        $this->FLD('pictures', 'fileman_FileType(bucket=pictures)', 'input=none');
        $this->FLD('info', 'richtext', 'caption=Друга информация');
        $this->FLD('groupList', 'keylist(mvc=contacts_Groups,select=name)', 'caption=Групи,remember');
        
        $this->FNC('addressBox', 'varchar(255)', 'caption=Адрес,width=400px,notSorting');
        $this->FNC('phonesBox', 'varchar(255)', 'caption=Телфони,width=400px,notSorting');
        
        $this->FNC('nameList', 'varchar', 'caption=Име,orderAs=name');
        $this->FNC('group', 'key(mvc=contacts_Groups)');
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_CalcGroup($mvc, &$rec) {
        $rec->group = (int)substr($rec->groupList, 1); // ид-то на първата група от списъка
        $rec->group = $this->Groups->fetchField($rec->group, 'name');
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
            $data->query->orderBy('name=DESC');
        } elseif($data->listFilter->rec->order == 'last') {
            $data->query->orderBy('createdOn=DESC');
        }
        
        // Филтър
        if($data->listFilter->rec->search) {
            $data->query->where(array(
                "LOWER(CONCAT(' ', #name, ' ')) LIKE LOWER('% [#1#]%')",
                $data->listFilter->rec->search
            ));
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
                    "(LOWER(CONCAT(' ', #name, ' ')) LIKE LOWER('% [#{$i}#]%'))";
                    $cond[$i] = $a;
                    $i++;
                }
            }
            
            $data->query->where($cond);
        }
        
        if($data->groupId = Request::get('groupId', 'key(mvc=contacts_Groups,select=name)')) {
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
        $data->listFilter->FNC('search', 'varchar', 'placeholder=Филтър,caption=Филтър,input,silent,recently');
        $data->listFilter->FNC('order', 'enum(alphabetic=Азбучно,last=Последно добавени)', 'caption=Подредба,input,silent');
        $data->listFilter->FNC('groupId', 'key(mvc=contacts_Groups,select=name,allowEmpty)', 'placeholder=Всички групи,caption=Група,input,silent');
        $data->listFilter->FNC('alpha', 'varchar', 'caption=Буква,input=hidden,silent');
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай');
        
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
        $data->toolbar->removeBtn('*');
        $data->toolbar->addBtn('Нова фирма', array('Ctr' => $this, 'Act' => 'Add', 'type' => 'company', 'ret_url' => TRUE));
        $data->toolbar->addBtn('Ново лице', array('Ctr' => $this, 'Act' => 'Add', 'type' => 'person', 'ret_url' => TRUE));
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
            $form->setDefault('country', $Countries->fetchField("#commonName = '" . BGERP_OWN_COMPANY_COUNTRY . "'", 'id' ));
        }
        
        $type = $form->rec->type;
        
        // със setField слагаме наименования на полетата
        if ($type == "company") {
            $form->setField('name', 'caption=Фирма');
            $form->setField('pictures', 'caption=Лого, input=input');
        } else {
            expect($type == 'person', $type);
            $form->setField('name', 'caption=Имена');
            $form->setField('birthday', 'input');
            $form->setField('pictures', 'caption=Снимка, input');
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
            $data->title = "Контакти в групата|* \"<b style='color:green'>" .
            $this->Groups->getTitleById($data->groupId) . "</b>\"";
        } elseif($data->listFilter->rec->search) {
            $data->title = "Визитки отговарящи на филтъра|* \"<b style='color:green'>" .
            type_Varchar::toVerbal($data->listFilter->rec->search) .
            "</b>\"";
        } elseif($data->listFilter->rec->alpha) {
            if($data->listFilter->rec->alpha{0} == '0') {
                $data->title = "Имена и фирми, които не започват с букви";
            } else {
                $data->title = "Имена и фирми започващи с буквите|* \"<b style='color:green'>{$data->listFilter->rec->alpha}</b>\"";
            }
        } elseif(!$data->title) {
            $data->title = "Всички визитки";
        }
    }
    
    
    /**
     * Добавяне на бутони за 'Предишен' и 'Следващ'
     *
     * @param unknown_type $mvc
     * @param unknown_type $res
     * @param unknown_type $data
     */
    function on_AfterPrepareEditToolbar($mvc, $res, $data)
    {
        if (isset($data->buttons->prevId)) {
            $data->form->toolbar->addSbBtn('Предишен', 'save_n_prev', array('id'=>'prev'));
        }
        
        if (isset($data->buttons->nextId)) {
            $data->form->toolbar->addSbBtn('Следващ', 'save_n_next', array('id'=>'next'));
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
    
    
    /**
     * Шаблон за визитката
     *
     * @return core_Et $tpl
     */
    function renderSingleLayout_($data)
    {
        $data->rec->titleText = "xxx2";
        
        // BEGIN Prepare groups for a contact
        $groupList = type_Keylist::toArray($data->rec->groupList);
        
        $groupListVerbal = array();
        
        foreach ($groupList as $group) {
            $groupListVerbal[] = $this->Groups->fetchField($group, 'name');
        }
        
        $data->rec->groupListVerbal = $groupListVerbal;
        // END Prepare groups for a contact
        
        $viewContact = cls::get('contacts_tpl_ViewSingleLayout', array('data' => $data));
        // $viewContact->replace($data->rec->titleText, 'titleText');
        
        return $viewContact;
    }
    
    
    /**
     * Промяна на данните от таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal ($mvc, $row, $rec)
    {
        $row->nameList = Ht::createLink(type_Varchar::toVerbal($rec->name), array($this, 'single', $rec->id));
        $row->nameTitle = mb_strtoupper($rec->name);
        $row->nameLower = mb_strtolower($rec->name);
        
        // highslide ефект за картинката
        $Highslide = cls::get('highslide_Highslide');
        
        $tArr = array(200, 150);
        $mArr = array(600, 450);
        
        if($rec->pictures) {
            $row->image = $Highslide->getImage($rec->pictures, $tArr, $mArr);
        } else {
            $row->image = "<img class=\"hgsImage\" src=" . sbf('img/noimage120.gif'). " alt='no image'>";
        }
        
        $row->addressBox = $mvc->getVerbal($rec, 'pCode') . ", " . $mvc->getVerbal($rec, 'place') . "<br/>" . $mvc->getVerbal($rec, 'address');
        
        // phonesBox
        $row->phonesBox = "<div class='contacts-row'>";
        
        if ($rec->tel) {
            $row->phonesBox .= "<p class='clear_l w-80px gr'>телефон: </p>
                                <p>" . $mvc->getVerbal($rec, 'tel') . "</p>";
        }
        
        if ($rec->mobile) {
            $row->phonesBox .= "<p class='clear_l w-80px gr'>мобилен: </p>
                                <p>" . $mvc->getVerbal($rec, 'mobile') . "</p>";
        }
        
        if ($rec->fax) {
            $row->phonesBox .= "<p class='clear_l w-80px gr'>факс: </p> 
                               <p>" . $mvc->getVerbal($rec, 'fax') . "</p>";
        }
        
        $row->phonesBox .= "</div>";
        
        // bp($row);
        // END phonesBox
    }
    
    
    /**
     * Промяна на бутоните
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareReturl($mvc, $data)
    {
        $Cmd = Request::get('Cmd');
        
        $data->buttons->prevId = $this->getNeighbour($data, '<');
        $data->buttons->nextId = $this->getNeighbour($data, '>');
        
        if (isset($Cmd['save_n_prev'])) {
            $data->retUrl = array($this, 'edit', 'id'=>$data->buttons->prevId);
        } elseif (isset($Cmd['save_n_next'])) {
            $data->retUrl = array($this, 'edit', 'id'=>$data->buttons->nextId);
        }
    }
    
    
    /**
     * Връща id на съседния запис в зависимост next/prev
     *
     * @param stdClass $data
     * @param string $dir
     */
    private function getNeighbour($data, $dir)
    {
        if (!isset($data->rec->id)) {
            return null;
        }
        
        $query = $this->getQuery();
        
        $query->where("#id {$dir} {$data->rec->id}");
        $query->limit(1);
        $query->orderBy('id', $dir == '>'?'ASC':'DESC');
        
        $rec = $query->fetch();
        
        return $rec->id;
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
        core_Classes::addClass($mvc);
        
        if (!$mvc->fetch(BGERP_OWN_COMPANY_ID)){
            
            $rec = new stdClass();
            $rec->id = BGERP_OWN_COMPANY_ID;
            $rec->name = BGERP_OWN_COMPANY_NAME;
            
            // Страната не е стринг, а id
            $Countries = cls::get('drdata_Countries');
            $rec->country = $Countries->fetchField("#commonName = '" . BGERP_OWN_COMPANY_COUNTRY . "'", 'id' );
            
            // ! Необходимо е да укажем, че визитката е тип - фирма
            $rec->type = 'company';
            
            if($mvc->save($rec, NULL, 'REPLACE')) {
                
                $res .= "<li style='color:green'>Фирмата " . BGERP_OWN_COMPANY_NAME . " е записана с #id=" .
                BGERP_OWN_COMPANY_ID . " в базата с контктите</li>";
            }
        }
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('pictures', 'Снимки', 'jpg,jpeg', '3MB', 'user', 'every_one');
    }
    
    
    /**
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see intf_Register
     */
    
    
    /**
     * Връща заглавието на перото за контакта
     *
     * Част от интерфейса: intf_Register
     */
    function getAccItemRec($rec)
    {
        return (object) array('title' => $rec->name);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function getGroupTypes()
    {
        return array('group' => 'Група', 'city' => 'Град');
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function getFeatures()
    {
        return array('group' => 'Група', 'place' => 'Град');
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function getFeatureOf($objectId, $featureId)
    {
        expect(!empty($this->fields[$featureId]));
        
        return $this->fetchField($objectId, $featureId);
    }
    
    
    /**
     * Възможни стойности на зададен признак за групиране.
     *
     * @see intf_Register::getGroups()
     */
    function getGroups($groupType)
    {
        $method = 'get' . ucfirst($groupType) . 'Groups';
        
        if (method_exists($this, $method)) {
            return $this->{$method}();
        }
    }
    
    
    /**
     * Връща ид на продукти, групирани по стойност на зададения критерий
     *
     * @see intf_Register::getGroupObjects()
     */
    function getGroupObjects($groupType, $groupValue = NULL)
    {
        $method = 'get' . ucfirst($groupType) . 'GroupObjects';
        
        if (method_exists($this, $method)) {
            return $this->{$method}($groupValue);
        }
    }
    
    
    /**
     * КРАЙ НА интерфейса @see intf_Register
     */
    
    
    /**
     * Връща дефинираните от потребителя групи продукти
     */
    private function getGroupGroups()
    {
        $result = array();
        $query = $this->Groups->getQuery();
        
        while ($rec = $query->fetch()) {
            $result[$rec->id] = $rec->name;
        }
        
        return $result;
    }
    
    private function getGroupGroupObjects($groupValue)
    {
        if (!isset($groupValue)) {
            $groups = array_keys($this->getGroupGroups());
        } else {
            $groups = array($groupValue);
        }
        
        $result = array();
        
        foreach ($groups as $groupId) {
            $query = $this->getQuery();
            $query->where("#groupList LIKE '%|{$groupId}|%'");
            
            while ($rec = $query->fetch()) {
                $result[$groupId][] = $rec->id;
            }
        }
        
        return $result;
    }
    
    private function getCityGroups()
    {
        $result = array();
        
        $query = $this->getQuery();
        $query->XPR('dplace', 'varchar', 'DISTINCT #place');
        $query->where("#place != ''");
        $query->show('dplace');
        
        while ($rec = $query->fetch()) {
            $result[md5($rec->dplace)] = $rec->dplace;
        }
        
        return $result;
    }
    
    private function getCityGroupObjects($groupValue)
    {
        if (!isset($groupValue)) {
            $groups = array_keys($this->getCityGroups());
        } else {
            $groups = array($groupValue);
        }
        
        $result = array();
        
        foreach ($groups as $groupId) {
            $query = $this->getQuery();
            $query->where("MD5(#place) = '{$groupId}'");
            
            while ($rec = $query->fetch()) {
                $result[$groupId][] = $rec->id;
            }
        }
        
        return $result;
    }
}