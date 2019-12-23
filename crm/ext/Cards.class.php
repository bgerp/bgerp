<?php


/**
 * Модел за клиентски карти
 *
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
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
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'pos_Cards';
    
    
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
    public $listFields = 'contragentId=Контрагент,number=Карта,createdOn,createdBy,state=Състояние';
    
    
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
    const MISSING_STATUS = 'missing';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('number', 'varchar(32)', 'caption=Номер,smartCenter,placeholder=Автоматично');
        $this->FLD('contragentId', 'int', 'input=hidden,silent,tdClass=leftCol');
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,silent');
        
        $this->setDbUnique('number');
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $rec = $data->form->rec;
        if (isset($rec->contragentClassId) && isset($rec->contragentId)) {
            $data->form->title = core_Detail::getEditTitle($rec->contragentClassId, $rec->contragentId, $mvc->singleTitle, $rec->id, $mvc->formTitlePreposition);
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
        if (isset($fields['-list'])) {
            $Contragent = cls::get($rec->contragentClassId);
            $row->contragentId = $Contragent->getHyperLink($rec->contragentId, true);
        }
        
        $row->created = tr("|на|* {$row->createdOn} |от|* {$row->createdBy}");
    }
    
    
    /**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->showFields = 'search';
    }
    
    
    /**
     * Подготовка на клиентските карти на избрания клиент
     */
    public function prepareCards($data)
    {
        $Contragent = $data->masterMvc;
        $masterRec = $data->masterData->rec;
        $data->listFields = arr::make('number=Карта,created=Създаване,state=Състояние', true);
        
        // Подготовка на клиентските карти
        $query = $this->getQuery();
        $query->where("#contragentClassId = '{$Contragent->getClassId()}' AND #contragentId = {$masterRec->id}");
        $query->orderBy("#state");
        while ($rec = $query->fetch()) {
            $row = $this->recToVerbal($rec);
            $data->rows[$rec->id] = $row;
        }
        
        // Добавяне на бутон при нужда
        if ($Contragent->haveRightFor('edit', $data->masterId) && $this->haveRightFor('add')) {
            $addUrl = array($this, 'add', 'contragentClassId' => $Contragent->getClassId(), 'contragentId' => $data->masterId, 'ret_url' => true);
            $data->addBtn = ht::createLink('', $addUrl, null, array('ef_icon' => 'img/16/add.png', 'class' => 'addSalecond', 'title' => 'Добавяне на нова клиентска карта'));
        }
    }
    
    
    /**
     * Рендиране на клиентските карти на избрания клиент
     */
    public function renderCards($data)
    {
        $tpl = new core_ET('');
        $tpl->append(tr('Клиентски карти'), 'cardTitle');
        
        $table = cls::get('core_TableView');
        $table->class = 'simpleTable';
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
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
     *             ['contragent'] - Референция към контрагента от картата
     *             ['state'] - Състоянието на картата, или MISSING_STATUS ако несъществува
     */
    public static function getInfo($number)
    {
        $info = array('state' => self::MISSING_STATUS);
        
        $query = static::getQuery();
        $query->where(array("#number = '[#1#]'", $number));
        if($rec = $query->fetch()){
            $info['state'] = $rec->state;
            $info['contragent'] = new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
            $contragentState = $info['contragent']->fetchField('state');
            if(in_array($contragentState, array('closed', 'rejected'))){
                $info['state'] = 'closed';
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
            if (!cls::get($rec->contragentClassId)->haveRightFor('edit', $rec->contragentId)) {
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
            if($info['state'] == self::MISSING_STATUS){
                $form->setError('search', "Невалиден номер на карта");
            } elseif($info['state'] == 'closed'){
                $form->setError('search', "Картата вече не е активна");
            }
            
            if(!$form->gotErrors()){
               $domainRec = &Mode::get(cms_Domains::CMS_CURRENT_DOMAIN_REC);
               $domainRec->clientCardNumber = $form->rec->search;
               
               // Ако към папката на фирмата има свързани партньори, линк към формата за логване
               $folderId = $info['contragent']->fetchField('folderId');
               if(isset($folderId) && colab_FolderToPartners::count("#folderId = {$folderId}")){
                   
                   return new Redirect(array('core_Users', 'login'), 'Моля логнете се с вашия потребител');
               }
               
               $retUrl = array($this, 'checkCard', 'ret_url' => true);
               $redirectUrl = colab_FolderToPartners::getRegisterUserUrlByCardNumber($info['contragent']->getInstance(), $info['contragent']->that, $retUrl);
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