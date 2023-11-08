<?php


/**
 * Модел за клиентски карти
 *
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class crm_ext_Cards extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Клиентски карти';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'crm_Wrapper, plg_Search, plg_Sorting, plg_State2, plg_RowTools2, plg_Created';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Клиентска карта';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'ceo, crm';
    
    
    /**
     * Кой може да въвежда картата
     */
    public $canCheckcard = 'every_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, crm';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'number=Карта,type,personId,companyId,source,createdOn,createdBy,state=Състояние';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'number';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Константа за несъществуваща карта
     */
    const STATUS_NOT_FOUND = 'notfound';


    /**
     * Константа за неактивна карта
     */
    const STATUS_NOT_ACTIVE = 'notActive';


    /**
     * Константа за активна карта
     */
    const STATUS_ACTIVE = 'active';


    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'companyId,source';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('number', 'varchar(32)', 'caption=Номер,placeholder=Автоматично генериране');
        $this->FLD('personId', 'key2(mvc=crm_Persons,select=name)', 'caption=Лице,silent,input=hidden');
        $this->FLD('type', 'enum(personal=Лична,company=Фирмена)', 'caption=Вид,notNull,value=personal,silent,removeAndRefreshForm=companyId');
        $this->FLD('companyId', 'key(mvc=crm_Companies,select=name)', 'input=hidden,silent,caption=Фирма,tdClass=leftCol');
        $this->FLD('source', 'int', 'caption=Източник,input=none');


        $this->setDbUnique('number');
        $this->setDbIndex('personId');
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $rec = $data->form->rec;
        if (isset($rec->personId)) {
            $data->form->title = core_Detail::getEditTitle('crm_Persons', $rec->personId, $mvc->singleTitle, $rec->id, 'на');
        }
    }


    /**
     * Извиква се след подготовката на формата
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        $userId = crm_Profiles::getUserByPerson($rec->personId);
        $companyOptions = array();
        if($userId){
            if(core_Packs::isInstalled('colab')){
                if(core_Users::isContractor($userId)){
                    $sharedContragentFolders = colab_Folders::getSharedFolders($userId, true, 'crm_CompanyAccRegIntf');
                    foreach ($sharedContragentFolders as $companyFolderId => $companyName){
                        $companyId = crm_Companies::fetchField("#folderId = {$companyFolderId}");
                        $companyOptions[$companyId] = $companyName;
                    }
                }
            }
        }

        if(!countR($companyOptions)){
            $form->setReadOnly('type', 'personal');
        }

        if($rec->type == 'company'){
            $form->setField('companyId', 'input,mandatory');
            $form->setOptions('companyId', array('' => '') + $companyOptions);
        } else {
            $form->setField('companyId', 'input=none');
        }
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if($form->isSubmitted()){
            if(empty($form->rec->number)){
                $form->rec->number = $mvc->getNewToken();
            }
        }
    }
    
    
    /**
     * Връща нов неизползван досега номер
     *
     * @return string $number - генерираният токен
     */
    private function getNewToken()
    {
        // Докато не се получи уникален номер, се генерира нов
        $number = self::generate();
        while(self::fetch("#number = '{$number}'")){
            $number = self::generate();
        }
        
        return $number;
    }
    
    
    /**
     * Генериане на номер на карта
     *
     * @return $number - генерирания номер
     */
    public static function generate()
    {
        $number = str::getRand('dddddd');
        $checkSum = substr(strtolower(md5($number . EF_SALT)), 0, 2);
        $number .= $checkSum;
        
        return $number;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->personId = crm_Persons::getHyperLink($rec->personId, true);
        if(isset($rec->companyId)){
            $row->companyId = crm_Companies::getHyperLink($rec->companyId, true);
        }
        $row->created = tr("|* {$row->createdOn} |от|* {$row->createdBy}");

        if($info = static::getInfo($rec->number)){
            if($info['status'] == static::STATUS_NOT_ACTIVE){
                $row->number = ht::createHint($row->number, 'Картата е неактивна|*!', 'warning', false);
            }
        }
    }
    
    
    /**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FLD('typeFilter', 'enum(all=Всички,personal=Лична,company=Фирмена)');
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->showFields = 'search,typeFilter';
        $data->listFilter->setDefault('typeFilter', 'all');
        $data->listFilter->input();

        if($filter = $data->listFilter->rec){
            if($filter->typeFilter != 'all'){
                $data->query->where("#type = '{$filter->typeFilter}'");
            }
        }
    }
    
    
    /**
     * Подготовка на клиентските карти на избраното лице
     */
    public function prepareCards($data)
    {
        $masterRec = $data->masterData->rec;

        // Подготовка на клиентските карти
        $query = $this->getQuery();
        if($data->masterMvc instanceof crm_Persons){
            $data->listFields = arr::make('number=Карта,type=Вид,companyId=Фирма,source=Източник,state=Състояние', true);
            $query->where("#personId = '{$masterRec->id}'");
        } else {
            $data->listFields = arr::make('number=Карта,type=Вид,personId=Лице,source=Източник,state=Състояние', true);
            $query->where("#companyId = '{$masterRec->id}'");
        }

        $query->orderBy("#state");
        while ($rec = $query->fetch()) {
            $row = $this->recToVerbal($rec);
            $data->rows[$rec->id] = $row;
        }
        
        // Добавяне на бутон при нужда
        if($data->masterMvc instanceof crm_Persons){
            if ($this->haveRightFor('add', (object)array('personId' => $data->masterId))) {
                $addUrl = array($this, 'add', 'personId' => $data->masterId, 'ret_url' => true);
                $data->addBtn = ht::createLink('', $addUrl, null, array('ef_icon' => 'img/16/add.png', 'class' => 'addSalecond', 'title' => 'Добавяне на нова клиентска карта'));
            }
        }
    }
    
    
    /**
     * Рендиране на клиентските карти на избрания клиент
     */
    public function renderCards($data)
    {
        $tpl = new core_ET('');
        $tpl->append(tr('Клиентски карти'), 'cardTitle');
        $fieldset = new core_FieldSet("");
        $fieldset->FLD('companyId', 'varchar', 'tdClass=leftCol');
        $table = cls::get('core_TableView', array('mvc' => $fieldset));

        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        $data->listFields = core_TableView::filterEmptyColumns($data->rows, $data->listFields, 'companyId');
        $details = $table->get($data->rows, $data->listFields);
        $tpl->append($details);
        
        if (isset($data->addBtn)) {
            $tpl->append($data->addBtn, 'addCardBtn');
        }
        
        return $tpl;
    }
    
    
    /**
     * Връща информация за картата с този номер
     *
     * @param string $number - номер на карта
     *
     * @return array $info - информация за картата
     *             ['contragentClassId'] - Клас на контрагента
     *             ['contragentId']      - Ид на контрагента
     *             ['type']              - Тип на картата
     *             ['status']            - Статус на картата активна/несъществуваща/изтекла
     */
    public static function getInfo($number)
    {
        $info = array('status' => self::STATUS_NOT_FOUND);
        $query = static::getQuery();
        $query->where(array("#number = '[#1#]'", $number));
        $rec = $query->fetch();
        if(!$rec) return $info;

        $info['type'] = $rec->type;
        $info['status'] = ($rec->state != 'closed') ? self::STATUS_ACTIVE : self::STATUS_NOT_ACTIVE;
        if($rec->type == 'company'){
            $info['contragentClassId'] = crm_Companies::getClassId();
            $info['contragentId'] = $rec->companyId;

            // Ако потребителя е партньор и папката на фирмата е все още споделена - значи е активна
            $userId = crm_Profiles::getUserByPerson($rec->personId);
            if(core_Packs::isInstalled('colab') && isset($userId) && core_Users::isContractor($userId)){
                $folderId = crm_Companies::fetchField($rec->companyId, 'folderId');
                if(!colab_FolderToPartners::fetchField("#contractorId = {$userId} AND #folderId = {$folderId}")){
                    $info['status'] = self::STATUS_NOT_ACTIVE;
                }
            } else {

                // Фирмената карта не е активна, ако потребителя не е партньор или вече не му е споделена папката
                $info['status'] = self::STATUS_NOT_ACTIVE;
            }
        } else {
            $info['contragentClassId'] = crm_Persons::getClassId();
            $info['contragentId'] = $rec->personId;
        }

        if($info['status'] == self::STATUS_ACTIVE){
            $contragentState = cls::get($info['contragentClassId'])->fetchField($info['contragentId'], 'state');
            if(in_array($contragentState, array('closed', 'rejected'))){
                $info['status'] = self::STATUS_NOT_ACTIVE;
            }
        }
        
        return $info;
    }

    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec)) {
            if(empty($rec->personId)){
                $requiredRoles = 'no_one';
            } elseif (!crm_Persons::haveRightFor('edit', $rec->personId)) {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'checkcard') {
            $domainId = isset($rec->domainId) ? $rec->domainId : cms_Domains::getPublicDomain()->id;
            $settings = cms_Domains::getSettings($domainId);
            
            if(isset($userId)){
                $requiredRoles = 'no_one';
            } elseif($settings->canUseCards != 'yes'){
                $requiredRoles = 'no_one';
            } elseif(!crm_ext_Cards::count("#state = 'active'")){
                $requiredRoles = 'no_one';
            } elseif(!core_Packs::isInstalled('colab')){
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Екшън за въвеждане на клиентска карта
     */
    function act_CheckCard()
    {
        $this->requireRightFor('checkcard');
        Mode::set('currentExternalTab', 'eshop_Carts');
        $lang = cms_Domains::getPublicDomain('lang');
        core_Lg::push($lang);
       
        // Подготовка на формата
        $form = cls::get('core_Form');
        $form->title = "Въвеждане на клиентска карта";
        $form->FLD('search', 'varchar', 'mandatory,caption=Номер,silent');
        $form->input(null, 'silent');
        $form->input();
        
        if($form->isSubmitted()){

            // Извличане на иформацията за картата
            $info = crm_ext_Cards::getInfo($form->rec->search);
            if($info['status'] == self::STATUS_NOT_FOUND){
                $form->setError('search', "Невалиден номер на карта");
            } elseif($info['status'] == self::STATUS_NOT_ACTIVE){
                $form->setError('search', "Картата вече не е активна");
            }
            
            if(!$form->gotErrors()){
                $var = Mode::get(cms_Domains::CMS_CURRENT_DOMAIN_REC);
                $domainRec = &$var;
                $domainRec->clientCardNumber = $form->rec->search;
               
                // Ако към папката на фирмата има свързани партньори, линк към формата за логване
                $Contragent = new core_ObjectReference($info['contragentClassId'], $info['contragentId']);
                $folderId = $Contragent->fetchField('folderId');
                if(isset($folderId) && colab_FolderToPartners::count("#folderId = {$folderId}")){
                   
                   return new Redirect(array('core_Users', 'login'), 'Моля логнете се с вашия потребител');
               }
               
               $retUrl = array($this, 'checkCard', 'ret_url' => true);
               $redirectUrl = colab_FolderToPartners::getRegisterUserUrlByCardNumber($Contragent->getInstance(), $Contragent->that, $retUrl);
               expect(!empty($redirectUrl));
                   
               return new Redirect($redirectUrl);
            }
        }
        
        // Показване на бутон за сканиране ако се гледа от андроид устройство
        $userAgent = log_Browsers::getUserAgentOsName();
        if($userAgent == 'Android'){
            $scanUrl = toUrl(array($this, 'checkCard', 'search' => '__CODE__'), true);
            $form->toolbar->addBtn('Сканирай', barcode_Search::getScannerActivateUrl($scanUrl), 'id=scanBtn', 'ef_icon = img/16/scanner.png');
        }
        
        // Подготовка на тулбара
        $form->toolbar->addSbBtn('Въведи', 'save', 'id=save, ef_icon = img/16/disk.png', 'title=Запис');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'id=cancel, ef_icon = img/16/close-red.png', 'title=Прекратяване на действията');
        $this->unloadPlugin('cms_Wrapper');
        
        Mode::set('wrapper', 'cms_page_External');
        $tpl = $form->renderHtml();
        core_Form::preventDoubleSubmission($tpl, $form);
        core_Lg::pop();
        vislog_History::add("Въвеждане на клиентска карта");
        
        return $tpl;
    }
}