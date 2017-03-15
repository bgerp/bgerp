<?php



/**
 * Клас 'core_Manager' - Дефиниране и web-управление на таблица от db
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @method renderWrapping(core_ET|string|null &$tpl=NULL, $data = NULL)
 */
class core_Manager extends core_Mvc
{
    
    
    /****************************************************************************************
     *                                                                                      *
     *        ОПИСАТЕЛНА ЧАСТ                                                               *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = '?Мениджър?';
    
    
    /**
     * Заглавие на единичния обект
     */
    public $singleTitle = '?Обект?';
    
    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/page_white_text.png';
    
    
    /**
     * По подразбиране колко резултата да показва на страница
     */
    public $listItemsPerPage = 20;
    
    
    /**
     * Колко дни да пазим логовете за този клас?
     */
    public static $logKeepDays = 7;
    
    
    /**
     * Кой линк от главното меню на страницата да бъде засветен?
     */
    var $menuPage = FALSE;
    
    
    /**
     * Кой таб-контрол е зареден?
     */
    var $tabControl = FALSE;
    
    
    /**
     * Кой таб от таб-контрола (ако има) да бъде засветен?
     * 
     * @var string|FALSE
     */
    var $currentTab = FALSE;
    
    
    /**
     * Кои роли имат пълни права за този мениджър?
     */
    var $canAdmin = 'admin';
    
    
    /****************************************************************************************
     *                                                                                      *
     *       ПРЕДЕФИНИРАНИ ДЕЙСТВИЯ (ЕКШЪНИ) НА МЕНИДЖЪРА                                   *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Конструктора на таблицата. По подразбиране работи със singleton
     * адаптор за база данни на име "db". Разчита, че адапторът
     * е вече свързан към базата.
     */
    function init($params = array())
    {
    	parent::init($params);
    	$this->declareInterface('core_ManagerIntf');
    }
    
    
    /**
     * Връща линк към подадения обект
     * 
     * @param integer $objId
     * 
     * @return core_ET
     */
    public static function getLinkForObject($objId)
    {
        $me = get_called_class();
        $inst = cls::get($me);
        
        if ($objId) {
            $title = $inst->getTitleForId($objId);
        } else {
            $title = $inst->className;
        }
        
        $linkArr = array();

        if ($inst->haveRightFor('list', $objId)) {
            $linkArr = array(get_called_class(), 'list', $objId);
        }
        
        $link = ht::createLink($title, $linkArr);
        
        return $link;
    }
    
    
    /**
     * Изпълнява заявка за листов изглед на страница от модела
     */
    function act_List()
    {
        // Ако печатаме, задаваме 'printing'
        if(Request::get('Print')) {
            Mode::set('printing');
        }
        
        // Проверяваме дали потребителя може да вижда списък с тези записи
        $this->requireRightFor('list');
        
        // Създаваме обекта $data
        $data = new stdClass();
        $data->action = 'list';
        
        $data->ListId = Request::get('id', 'int');
        
        // Създаваме заявката
        $data->query = $this->getQuery();
        
        // Подготвяме полетата за показване
        $this->prepareListFields($data);
        
        // Подготвяме формата за филтриране
        $this->prepareListFilter($data);
        
        // Подготвяме заявката за резюме/обощение
        $this->prepareListSummary($data);
        
        // Подготвяме навигацията по страници
        $this->prepareListPager($data);
        
        // Подготвяме записите за таблицата
        $this->prepareListRecs($data);
        
        // Подготвяме редовете на таблицата
        $this->prepareListRows($data);
        
        // Подготвяме заглавието на таблицата
        $this->prepareListTitle($data);
        
        // Подготвяме лентата с инструменти
        $this->prepareListToolbar($data);
        
        // Рендираме изгледа
        $tpl = $this->renderList($data);
        
        // Опаковаме изгледа
        $tpl = $this->renderWrapping($tpl, $data);
        
        if (!Request::get('ajax_mode')) {
            // Записваме, че потребителя е разглеждал този списък
            $this->logInAct('Листване', NULL, 'read');
        }
        
        return $tpl;
    }
    
    
    /**
     * Действие по подразбиране. Ако не се предефинира метода,
     * редиректва към табличното показване на мениджъра
     */
    function act_Default()
    {
        if(!isset($this->dbTableName)) {
            $res = $this->renderWrapping("<h2>Този модел няма таблица</h2>");
        } else {
            $res = $this->act_List();
        }

        return $res;
    }
    
    
    /**
     * Показва формата за редактиране
     */
    function act_Edit()
    {
        return $this->act_Manage();
    }
    
    
    /**
     * Показва формата за добавяне
     */
    function act_Add()
    {
        return $this->act_Manage();
    }
    
    
    /**
     * Записва данните от редактирането или добавянето
     */
    function act_Save()
    {
        return $this->act_Manage();
    }
    
    
    /**
     * Изтрива записа с указаното id
     */
    function act_Delete()
    {
        $data = new stdClass();
        
        $data->cmd = 'delete';
        
        $this->prepareRetUrl($data);
        
        $this->requireRightFor($data->cmd, NULL, NULL, $data->retUrl);
        
        expect(Request::isConfirmed());

        expect($data->id = Request::get('id', 'int'),
            "Липсва id на записа за изтриване");
        
        expect($data->rec = $this->fetch($data->id),
            "Некоректно id на записа за изтриване");
        
        // Дали имаме права за това действие към този запис?
        $this->requireRightFor($data->cmd, $data->rec, NULL, $data->retUrl);
        
        $this->delete($data->id);
        
        $this->logWrite('Изтриване', $data->id);
        
        return new Redirect($data->retUrl);
    }
    
    
    /**
     * Действие (екшън) за добавяне и редактиране на запис от модела
     */
    function act_Manage()
    {
        // Експериментално: Трябва да има поне 1 от тези 2 роли
        if(!$this->haveRightFor('Edit')) {
            $this->requireRightFor('Add');
        }

        $data = new stdClass();

        $data->action = 'manage';
        
        // Създаване и подготвяне на формата
        $this->prepareEditForm($data);
        
        // Подготвяме адреса за връщане, ако потребителя не е логнат.
        // Ресурса, който ще се зареди след логване обикновено е страницата, 
        // от която се извиква екшън-а act_Manage
        $retUrl = getRetUrl();
        
        // Определяме, какво действие се опитваме да направим
        $data->cmd = isset($data->form->rec->id) ? 'Edit' : 'Add';
        
        // Очакваме до този момент във формата да няма грешки
        $fieldsH = $this->selectFields("#input == 'hidden'");
        
        expect(!$data->form->gotErrors(array_keys($fieldsH)), 'Има грешки в silent полетата на формата', $data->form->errors);
        
        // Дали имаме права за това действие към този запис?
        $this->requireRightFor($data->cmd, $data->form->rec, NULL, $retUrl);
        
        // Зареждаме формата
        $data->form->input();
        
        $rec = &$data->form->rec;
        
        // Проверка дали входните данни са уникални
        if($rec) {
            if($data->form->isSubmitted() && !$this->isUnique($rec, $fields)) {
                $data->form->setError($fields, "Вече съществува запис със същите данни");
            }
        }
        
        // Генерираме събитие в $this, след въвеждането на формата
        $this->invoke('AfterInputEditForm', array($data->form));
       
        // Дали имаме права за това действие към този запис?
        $this->requireRightFor($data->cmd, $rec, NULL, $retUrl);
       
        // Ако формата е успешно изпратена - запис, лог, редирект
        if ($data->form->isSubmitted()) {
            
            // Записваме данните
            $id = $this->save($rec);
            
            $msg = ($data->cmd == 'Add') ? 'Създаване' : 'Редактиране';
            
            $this->logInAct($msg, $rec);
            
            // Подготвяме адреса, към който трябва да редиректнем,  
            // при успешно записване на данните от формата
            $this->prepareRetUrl($data, $id);
      
            // Редиректваме към предварително установения адрес
            return new Redirect($data->retUrl);
        } else {
            // Подготвяме адреса, към който трябва да редиректнем,  
            // при успешно записване на данните от формата
            $this->prepareRetUrl($data);
        }
        
        // Подготвяме лентата с инструменти на формата
        $this->prepareEditToolbar($data);
        
        // Подготвяме заглавието на формата
        $this->prepareEditTitle($data);
        
        // Получаваме изгледа на формата
        $tpl = $data->form->renderHtml();
        
        $formId = $data->form->formAttr['id'];
        jquery_Jquery::run($tpl, "preventDoubleSubmission('{$formId}');");
        
        // Опаковаме изгледа
        $tpl = $this->renderWrapping($tpl, $data);
        
        return $tpl;
    }
    
    
    /**
     * Подготвя заглавието на формата
     */
    function prepareEditTitle_($data)
    {
    	setIfNot($data->title, $this->title);
    	$data->form->title = ($data->form->rec->id ? 'Редактиране' : 'Добавяне') . ' на запис' .
    			"|*" . ($this->title ? ' |в|* ' . '"' . tr($data->title) . '"' : '');
    }
    
    
    /**
     * Логва действието
     * 
     * @param string $msg
     * @param NULL|stdClass|integer $rec
     * @param string $type
     */
    function logInAct($msg, $rec = NULL, $type = 'write')
    {
        if (is_numeric($rec)) {
            $rec = $this->fetch($rec);
        }
        
        $id = NULL;
        
        if ($rec) {
            $id = $rec->id;
        }
        if ($type == 'write') {
            $this->logWrite($msg, $id);
        } else {
            $this->logRead($msg, $id);
        }
    }
    
    /**
     * Начално установяване на мениджъра
     */
    function act_SetupMVC()
    {
        $tpl = new ET(parent::act_SetupMVC());
        
        $tpl = $this->renderWrapping($tpl);
        
        return $tpl;
    }
    
    /****************************************************************************************
     *                                                                                      *
     *   РАБОТА С МОДЕЛИТЕ. ПОДГОТОВКА НА ДАННИ                                             *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Подготвя полетата (колоните) които ще се показват
     */
    function prepareListFields_(&$data)
    {
        if(isset($data->listFields)) {
            
            $data->listFields = arr::make($data->listFields, TRUE);
        } elseif(isset($this->listFields)) {
            
            // Ако са зададени $this->listFields използваме ги тях за колони
            $data->listFields = arr::make($this->listFields, TRUE);
        } else {
            
            // Използваме за колони, всички полета, които не са означени с column = 'none'
            $fields = $this->selectFields("#column != 'none'");
            
            if (count($fields)) {
                foreach ($fields as $name => $fld) {
                    $data->listFields[$name] = $fld->caption;
                }
            }
        }
        
        if (count($data->listFields)) {
            
            // Ако титлата съвпада с името на полето, вадим името от caption
            foreach ($data->listFields as $field => $caption) {
                if (($field == $caption) && $this->fields[$field]->caption) {
                    $data->listFields[$field] = $this->fields[$field]->caption;
                }
            }
        }
        
        return $data;
    }
    
    
    /**
     * Подготвя формата за филтриране
     */
    function prepareListFilter_($data)
    {
        if (!$data->listFilter) {
            $formParams = array(
                'method' => 'GET',
                'toolbar' => ht::createSbBtn('Филтър')
            );
            $data->listFilter = $this->getForm($formParams);
            $mf = $data->listFilter->selectFields('#mandatory');
            foreach($mf as $name => $field) {
                $data->listFilter->setField($name, array('mandatory' => NULL));
            }
        }
        
        if ($data->ListId) {
            $data->query->where($data->ListId);
        }
        
        return $data;
    }
    
    
    /**
     * Рендира заявката за създаване на резюме
     */
    function prepareListSummary_(&$data)
    {
        // Ако има заявка
        if ($data->query) {
            
            // Ако няма обощени
            if (!$data->listSummary) {
                
                // Създаваме обекта
                $data->listSummary = new stdClass();
            }
            
            // Ако няма заявка за резюме
            if (!$data->listSummary->query) {
                
                // Клонираме заявката
                $data->listSummary->query = clone $data->query;
            }
        }
    }
    
    
    /**
     * Подготвя навигацията по страници
     */
    function prepareListPager_(&$data)
    {
        $perPage = (Request::get('PerPage', 'int') > 0 && Request::get('PerPage', 'int') <= 1000) ?
        Request::get('PerPage', 'int') : $this->listItemsPerPage;
        
        if($perPage) {  
            $data->pager = & cls::get('core_Pager', array('pageVar' => $data->pageVar));
            $data->pager->itemsPerPage = $perPage;
            if(isset($data->rec->id)) {
                $data->pager->setPageVar($this->className, $data->rec->id);
            } else {
                $data->pager->setPageVar($this->className);
            }
        }
        
        return $data;
    }
    
    
    /**
     * Подготвя лентата с инструменти за табличния изглед
     */
    function prepareListToolbar_(&$data)
    {
        $data->toolbar = cls::get('core_Toolbar');
        
        if ($this->haveRightFor('add')) {
            $data->toolbar->addBtn('Нов запис', array(
                    $this,
                    'add'
                ),
                'id=btnAdd', 'ef_icon = img/16/star_2.png,title=Създаване на нов запис');
        }
        
        return $data;
    }
    
    
    /**
     * Подготвя заглавието на таблицата
     */
    function prepareListTitle_(&$data)
    {
        setIfNot($data->title, $this->title);
        
        if ($data->ListId) {
            $data->title = "Резултати за запис номер|* {$data->ListId}: |" . $data->title;
        }
        
        return $data;
    }
    
    
    /**
     * Извлича редовете, които ще се покажат на текущата страница
     */
    function prepareListRecs_(&$data)
    {
        // Добавяме лимит според страньора, ако има такъв
        if ($data->pager) {
            $data->pager->setLimit($data->query);
        }
        
        if (!isset($data->recs)) {
            $data->recs = array();
        }
        
        // Извличаме редовете
        while ($rec = $data->query->fetchAndCache()) {
            $data->recs[$rec->id] = $rec;
        }
    
        return $data;
    }
    
    
    /**
     * Подготвя редовете във вербална форма
     */
    function prepareListRows_(&$data)
    {
        if (!isset($data->rows)) {
            $data->rows = array();
        }
        
        if(isset($data->recs) && !empty($data->recs)) {
            foreach($data->recs as $id => $rec) {
                $data->rows[$id] = $this->recToVerbal($rec, arr::combine($data->listFields, '-list'));
            }
        }
        
        return $data;
    }
    
    
    /**
     * Подготвя формата за редактиране
     */
    function prepareEditForm_($data)
    {
        // Създаване на формата
        $params = array(
            'method' => 'POST',
            'name' => 'EditForm'
        );
        
        // Създаване и подготвяне на формата за редактиране/добавяне
        $data->form = $this->getForm($params);
        
        // Добавяме id на формата според името на mvc-класа
        $data->form->formAttr['id'] = $this->className . "-EditForm";
        
        // Задаваме екшън-а "запис"
        $data->form->setAction($this, 'save');
        
        $data->form->FNC('ret_url', 'varchar(1024)', 'input=hidden,silent');
        
        $data->form->input(NULL, 'silent');

        // Ако имаме 
        if($data->form->rec->id && $data->form->cmd != 'refresh') {
            
            // Очакваме, че има такъв запис
            expect($rec = $this->fetch($data->form->rec->id));
            
            foreach((array) $rec as $key => $value) {
                $data->form->rec->{$key} = $value;
            }
        }
        
        return $data;
    }
    
    
    /**
     * Подготвя лентата с инструменти на формата за редактиране
     */
    function prepareEditToolbar_($data)
    {
        $data->form->toolbar->addSbBtn('Запис', 'save', 'id=save, ef_icon = img/16/disk.png', 'title=Запис на документа');
        $data->form->toolbar->addBtn('Отказ', $data->retUrl,  'id=cancel, ef_icon = img/16/close-red.png', 'title=Прекратяване на действията');
        
        return $data;
    }
    
    
    /**
     * Подготвя адреса за връщане след добавяне/редактиране
     */
    function prepareRetUrl_($data, $id = NULL)
    { 
        if (getRetUrl()) {
            
            $data->retUrl = getRetUrl();
        } else {
            if (method_exists($this, 'act_Single') && $data->form->rec->id && $data->cmd != 'delete') {
                $data->retUrl = array(
                    $this,
                    'single',
                    'id' => $data->form->rec->id
                );
            } else {
                if(is_a($this, 'core_Detail')) {
                    if(($masterKey = $this->masterKey) && ($masterId = $data->form->rec->{$masterKey})) {
                        $master = $mvc->masterClass;
                        if(!$master) {
                            $master = $this->getFieldTypeParam($masterKey, 'mvc');
                        }
                        if($master) {
                            $data->retUrl = array($master, 'single', $masterId);
                        }
                    }
                } 
                
                if(!$data->retUrl) {
                    $data->retUrl = array($this, 'list');
                }
            }
        }

        $idPlaceholder = self::getUrlPlaceholder('id');

        if(is_array($data->retUrl)) {
            foreach($data->retUrl as $key => $value) {
                if($value == $idPlaceholder) {
                    $data->retUrl[$key] = $id;
                }
            }
        }
 
        return $data;
    }


    /**
     * Връща плейсхолдър за стойността на id
     */
    public static function getUrlPlaceholder($paramName)
    {
        $placeholder = str::addHash($paramName . '_placeholder', 6, 'id');
        
        return $placeholder;
    }
      
    
    /****************************************************************************************
     *                                                                                      *
     *       РЕНДЕРИ, КОИТО ГЕНЕРИРАТ ИЗГЛЕДИ                                               *    
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Рендираме общия изглед за 'List'
     */
    function renderList_($data)
    {
        // Рендираме общия лейаут
        $tpl = $this->renderListLayout($data);
        
        // Попълваме титлата
        $tpl->append($this->renderListTitle($data), 'ListTitle');
        
        // Попълваме формата-филтър
        $tpl->append($this->renderListFilter($data), 'ListFilter');
        
        // Попълваме обобщената информация
        $tpl->append($this->renderListSummary($data), 'ListSummary');
        
        // Попълваме горния страньор
        $tpl->append($this->renderListPager($data), 'ListPagerTop');
        
        // Попълваме долния страньор
        $tpl->append($this->renderListPager($data), 'ListPagerBottom');
        
        // Попълваме таблицата с редовете
        setIfNot($data->listTableMvc, clone $this);
        $tpl->append($this->renderListTable($data), 'ListTable');
        
        // Попълваме долния тулбар
        $tpl->append($this->renderListToolbar($data), 'ListToolbar');
        
        return $tpl;
    }
    
    
    /**
     * Създаване на шаблона за общия List-изглед
     */
    function renderListLayout_($data)
    {
        $className = cls::getClassName($this);
        
        // Шаблон за листовия изглед
        $listLayout = new ET("
            <div class='clearfix21 listBlock {$className}'>
                [#ListTitle#]
                <div class='listTopContainer clearfix21'>
                    [#ListFilter#]
                    [#ListSummary#]
                </div>
                <div class='top-pager'> 
                	[#ListPagerTop#]
                </div>
                <!--ET_BEGIN ListTable-->
                	[#ListTable#]
                <!--ET_END ListTable-->
                <div class='bottom-pager'>
                	[#ListPagerBottom#]
                </div>
                [#ListToolbar#]
            </div>
          ");
        
        if($data->listScroll){
        	$listLayout->replace('narrow-scroll', 'NARROWSCROLL');
        }
        		
        return $listLayout;
    }
    
    
    /**
     * Рендира обобщена информация за извлечения списък от редове
     */
    function renderListSummary_($data)
    {
        
        /**
         * @todo: Някакво стандартно обобщение?
         */
    }
    
    
    /**
     * Рендира формата за филтриранена листовия изглед
     */
    function renderListFilter_($data)
    {
        if (count($data->listFilter->showFields)) {
            
            return new ET("<div class='listFilter'>[#1#]</div>", $data->listFilter->renderHtml(NULL, $data->listFilter->rec));
        }
    }
    
    
    /**
     * Рендира  навигация по страници
     */
    function renderListPager_($data)
    {
        if ($data->pager) {
            return $data->pager->getHtml();
        }
    }
    
    
    /**
     * Рендира таблицата с редовете
     */
    function renderListTable_($data)
    {
        setIfNot($data->listTableMvc, $this);
    	$table = cls::get('core_TableView', array('mvc' => $data->listTableMvc));
        
        if($data->action == 'list') {
            $table->tableClass ='listTable listAction';
        }

        // Кои ще са колоните на таблицата
        $data->listFields = arr::make($data->listFields, TRUE);
        
        // Имали колони в които ако няма данни да не се показват ?
        $hideColumns = arr::make($this->hideListFieldsIfEmpty, TRUE);
       
        // Ако има колони за филтриране, филтрираме ги
        if(count($hideColumns)){
        	$data->listFields = core_TableView::filterEmptyColumns($data->rows, $data->listFields, $hideColumns);
        }
        
        // Рендираме таблицата
        $tpl = $table->get($data->rows, $data->listFields);
        
        if(!$class = $data->listClass) {
            $class = 'listRows';
        }
        
        return new ET("<div class='{$class} {$data->listTableClass}'>[#1#]</div>", $tpl);
    }
    
    
    /**
     * Добавя титла на списъчния изглед
     */
    function renderListTitle_($data)
    {
        if(!empty($data->title)) {
            return new ET("<div class='listTitle'>[#1#]</div>", tr($data->title));
        }
    }
    
    
    /**
     * Рендира тулбара за списъчния изглед
     */
    function renderListToolbar_($data)
    {
        if(isset($data->toolbar) && cls::isSubclass($data->toolbar, 'core_Toolbar') && !Mode::is('printing') && $data->toolbar->count()) {
            $res = new ET("<div class='listToolbar'>[#1#]</div>", $data->toolbar->renderHtml());
        }

        return $res;
    }
    
    
    /**
     * Прави стандартна 'обвивка' на изгледа
     * @todo: да се отдели като плъгин
     */
    function renderWrapping_($tpl, $data = NULL)
    {
        return $tpl;
    }
    
    /****************************************************************************************
     *                                                                                      *
     *         ФУНКЦИИ ОПРЕДЕЛЯЩИ ПРАВОТО НА ДЕЙСТВИЕ СПОРЕД РОЛЯТА НА ПОТРЕБИТЕЛЯ          *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Връща ролите, които могат да изпълняват посоченото действие
     */
    function getRequiredRoles_(&$action1, $rec = NULL, $userId = NULL)
    {
        $action = $action1;
        
        $action{0} = strtoupper($action{0});
        $action = 'can' . $action;
        
        if(isset($this->{$action})) {
            $requiredRoles = $this->{$action};
        } else {
            switch($action) {
                case 'canAdd' :
                case 'canDelete' :
                case 'canEdit' :
                    
                    return $this->getRequiredRoles('write', $rec, $userId);
                
                case 'canList' :
                case 'canSingle' :
                    
                    return $this->getRequiredRoles('read', $rec, $userId);
                
                default :
                
                return $this->getRequiredRoles('admin', $rec, $userId);
            }
        }
        
        return $requiredRoles;
    }
    
    
    /**
     * Проверява дали текущият потребител има право да прави посоченото действие
     * върху посочения запис или ако не, - върху всички записи
     */
    static function haveRightFor($action, $rec = NULL, $userId = NULL)
    {
        $self = cls::get(get_called_class());
        
        // Ако вместо $rec е зададено $id - зареждаме $rec
        if(!is_object($rec) && $rec > 0) {
            $rec = $self->fetch($rec);
        }

        // Ако нямаме зададен потребите - приемаме, че въпроса се отнася за текущия
        if(!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        $requiredRoles = $self->getRequiredRoles(strtolower($action), $rec, $userId);
        
        return Users::haveRole($requiredRoles, $userId);
    }
    
    
    /**
     * Изисква потребителят да има права за това действие
     */
    static function requireRightFor($action, $rec = NULL, $userId = NULL, $retUrl = NULL)
    {
        $self = cls::get(get_called_class());
        
        // Ако вместо $rec е зададено $id - зареждаме $rec
        if(!is_object($rec) && $rec > 0) {
            $rec = $self->fetch($rec);
        }
        
        // Ако нямаме зададен потребител - приемаме, че въпроса се отнася за текущия
        if(!isset($userId)) {
            $userId = core_Users::getCurrent();
        }

        $requiredRoles = $self->getRequiredRoles(strtolower($action), $rec, $userId);
        
        return Users::requireRole($requiredRoles, $retUrl, $action);
    }
    
    /****************************************************************************************
     *                                                                                      *
     *               Помощни функции                                                        *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Връща списък е елементи <option> при ajax заявка
     */
    function act_ajax_GetOptions()
    {
        // Приключваме, ако няма права за четене
        if (!$this->haveRightFor('list')) {
            return array(
                'error' => 'Недостатъчни права за четене на ' . $this->title
            );
        }
        
        // Приключваме, ако класът не представлява модел
        if (count($this->fields) <= 1) {
            return array(
                'error' => 'Този клас не е модел: ' . $this->title
            );
        }
        
        // Приключваме, ако няма заявка за търсене
        $q = Request::get('q');
        
        if (!$q) {
            return array(
                'error' => 'Липсва заявка за филтриране'
            );
        }
        
        $select = new ET('');
        
        $options = $this->fetchOptions($q);
        
        if (is_array($options)) {
            foreach ($options as $id => $title) {
                $attr = array();
                
                $element = 'option';
                
                if (is_object($title)) {
                    if ($title->group) {
                        if ($openGroup) {
                            // затваряме групата                
                            $select->append('</optgroup>');
                        }
                        $element = 'optgroup';
                        $attr = $title->attr;
                        $attr['label'] = $title->title;
                        $option = ht::createElement($element, $attr);
                        $select->append($option);
                        $openGroup = TRUE;
                        continue;
                    } else {
                        $attr = $title->attr;
                        $title = $title->title;
                    }
                }
                $attr['value'] = $id;
                
                if ($id == $selected) {
                    $attr['selected'] = 'selected';
                }
                $option = ht::createElement($element, $attr, $title);
                $select->append($option);
            }
        }
        
        return array(
            'content' => $select->getContent()
        );
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function fetchOptions($q)
    {
        // Обработваме заявката
        $q = strtolower(str::utf2ascii($q));
        $q = trim(preg_replace('/[^a-zа-я0-9]+/', ' ', $q));
        
        $query = $this->getQuery();
        
        // Подготовка на полетата по които ще се търси
        foreach ($this->fields as $name => $field) {
            if ($field->searchable || $name == 'id') {
                $concat .= ", LOWER(#{$name}), ' '";
                
                if (is_a($field->type, 'type_Varchar') || $name == 'id') {
                    $show .= ($show ? ',' : '') . $name;
                }
            }
        }
        
        $q = explode(' ', $q);
        
        foreach ($q as $str) {
            $str = ltrim(trim($str), '0');
            
            if ($str) {
                $query->where("CONCAT(' '{$concat})  LIKE  '% $str%'");
            }
        }
        
        $query->limit(50);
        
        $query->show($show);
        
        $options = array(
            '' => '&nbsp;'
        );
        
        while ($rec = $query->fetch()) {
            $this->addVerbalOption($options, $rec);
        }
        
        return $options;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function addVerbalOption(&$options, $rec)
    {
        $value = $this->getVerbalName($rec);
        $options[$value] = $value;
    }
    
    
    /**
     * Връща вербалната стойност на името
     */
    function getVerbalName($rec, $pad = 5)
    {
        $rec->id = str_pad($rec->id, $pad, '0', STR_PAD_LEFT);
        
        return implode(' ', get_object_vars($rec));
    }

    
    /**
     * Валидиране на форма
     * 
     * @param core_Form $form
     * @return boolean
     */
    public function validate(core_Form $form)
    {
        // Запазваме текущите стойности на `cmd` и `method`
        $_backup = array($form->cmd, $form->method, $form->errors);
        
        $form->validate(NULL, FALSE, (array)$form->rec);
        
        // Временно променяме `cmd` и `method`. Целта е да "измамим" формата така, че метода
        // й isSubmitted() да връща TRUE. Правим това, защото искаме да изпълним пълния набор
        // от on_AfterInputEditForm()-хендлъри, а повечето от тях не правят нищо ако isSubmitted()
        // върне FALSE.  
        $form->cmd    = 'validate'; // Това за сега е произволно, работа върши всеки непразен стринг
        $form->method = $_SERVER['REQUEST_METHOD'];
        
        // Генерираме събитие в $this, след въвеждането на формата
        $this->invoke('AfterInputEditForm', array($form));
        
        $isValid = !$form->gotErrors();
        
        // Възстановяваме оригиналните ст-сти на `cmd` и `method`
        list($form->cmd, $form->method, $form->errors) = $_backup;
        
        return $isValid;
    }
    
    
    /**
     * Връща заглавието на мениджъра
     */
    function getTitle_()
    {
        $title = $this->title;
        
        return $title;
    }
    
    
    /**
     * @see core_BaseClass::action_()
     */
    function action_($act)
    {
        $res = parent::action_($act);
        
        // Ако заявката не е по AJAX и няма нищо записано в лога, записваме екшъна
        if (!Request::get('ajax_mode') && !count(log_Data::$toAdd)) {
            
            if (Request::$vars['_POST']) {
                self::logWrite(ucfirst($act), Request::get('id'), 180);
            } else {
                self::logInfo(ucfirst($act), Request::get('id'));
            }
        }
        
        return $res;
    }
}
