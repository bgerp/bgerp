<?php



/**
 * Клас 'core_Manager' - Дефиниране и web-управление на таблица от db
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
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
    var $title = '?Мениджър?';
    
    
    /**
     * Заглавие на единичния обект
     */
    var $singleTitle = '?Обект?';
    
    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/view.png';
    
    
    /**
     * По подразбиране колко резултата да показва на страница
     */
    var $listItemsPerPage = 20;
    
    
    /**
     * Колко дни да пазим логовете за този клас?
     */
    static $logKeepDays = 1;
    
    
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
     */
    var $tabPage = FALSE;
    
    
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
        
        // Създаваме заявката
        $data->query = $this->getQuery();
        
        // Подготвяме полетата за показване
        $this->prepareListFields($data);
        
        // Подготвяме формата за филтриране
        $this->prepareListFilter($data);
        
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
        $tpl = $this->renderWrapping($tpl);
        
        // Записваме, че потребителя е разглеждал този списък
        $this->log('List: ' . ($data->log ? $data->log : tr($data->title)));
        
        return $tpl;
    }
    
    
    /**
     * Действие по подразбиране. Ако не се предефинира метода,
     * редиректва към табличното показване на мениджъра
     */
    function act_Default()
    {
        return $this->act_List();
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
        
        expect($data->id = Request::get('id', 'int'),
            "Липсва id на записа за изтриване");
        
        expect($data->rec = $this->fetch($data->id),
            "Некоректно id на записа за изтриване");
        
        // Дали имаме права за това действие към този запис?
        $this->requireRightFor($data->cmd, $data->rec, NULL, $data->retUrl);
        
        $this->delete($data->id);
        
        $this->log($data->cmd, $id);
        
        return new Redirect($data->retUrl);
    }
    
    
    /**
     * Действие (екшън) за добавяне и редактиране на запис от модела
     */
    function act_Manage()
    {
        $data = new stdClass();
        
        // Създаване и подготвяне на формата
        $this->prepareEditForm($data);
        
        // Подготвяме адреса за връщане, ако потребителя не е логнат.
        // Ресурса, който ще се зареди след логване обикновено е страницата, 
        // от която се извиква екшън-а act_Manage
        $retUrl = getRetUrl();
        
        // Определяме, какво действие се опитваме да направим
        $data->cmd = isset($data->form->rec->id) ? 'Edit' : 'Add';
        
        // Очакваме до този момент във формата да няма грешки
        expect(!$data->form->gotErrors(), 'Има грешки в silent полетата на формата', $data->form->errors);
        
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
        
        // Генерираме събитие в mvc, след въвеждането на формата, ако е именувана
        $this->invoke('AfterInputEditForm', array($data->form));
        
        // Дали имаме права за това действие към този запис?
        $this->requireRightFor($data->cmd, $rec, NULL, $retUrl);
        
        // Ако формата е успешно изпратена - запис, лог, редирект
        if ($data->form->isSubmitted()) {
            
            // Записваме данните
            $id = $this->save($rec);
            
            // Правим запис в лога
            $this->log($data->cmd, $id);
            
            // Подготвяме адреса, към който трябва да редиректнем,  
            // при успешно записване на данните от формата
            $this->prepareRetUrl($data);
            
            // Редиректваме към предварително установения адрес
            return new Redirect($data->retUrl);
        } else {
            // Подготвяме адреса, към който трябва да редиректнем,  
            // при успешно записване на данните от формата
            $this->prepareRetUrl($data);
        }
        
        // Подготвяме лентата с инструменти на формата
        $this->prepareEditToolbar($data);
        
        // Получаваме изгледа на формата
        $tpl = $data->form->renderHtml();
        
        // Опаковаме изгледа
        $tpl = $this->renderWrapping($tpl);
        
        return $tpl;
    }
    
    
    /**
     * Начално установяване на менуджъра
     */
    function act_SetupMVC()
    {
        $tpl = new ET(parent::setupMVC());
        
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
        }
        
        return $data;
    }
    
    
    /**
     * Подготвя навигацията по страници
     */
    function prepareListPager_(&$data)
    {
        $perPage = (Request::get('PerPage', 'int') > 0 && Request::get('PerPage', 'int') <= 1000) ?
        Request::get('PerPage', 'int') : $this->listItemsPerPage;
        
        if($perPage) {
            $data->pager = & cls::get('core_Pager', array('pageVar' => 'P_' . $this->className));
            $data->pager->itemsPerPage = $perPage;
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
                'id=btnAdd,class=btn-add');
        }
        
        return $data;
    }
    
    
    /**
     * Подготвя заглавието на таблицата
     */
    function prepareListTitle_(&$data)
    {
        setIfNot($data->title, $this->title);
        
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
        
        // Извличаме редовете
        while ($rec = $data->query->fetch()) {
            $data->recs[$rec->id] = $rec;
        }
        
        return $data;
    }
    
    
    /**
     * Подготвя редовете във вербална форма
     */
    function prepareListRows_(&$data)
    {
        if(count($data->recs)) {
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
        
        $data->form->title = ($data->form->rec->id ? 'Редактиране' : 'Добавяне') . ' на запис' .
        "|*" . ($this->title ? ' |в|* ' . '"' . $this->title . '"' : '');
        
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
        $data->form->toolbar->addSbBtn('Запис', 'save', array('class' => 'btn-save'));
        $data->form->toolbar->addBtn('Отказ', $data->retUrl, array('class' => 'btn-cancel'));
        
        return $data;
    }
    
    
    /**
     * Podgotwq адреса за връщане след добавяне/редактиране
     */
    function prepareRetUrl_($data)
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
                $data->retUrl = array($this, 'list');
            }
        }
        
        return $data;
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
            <div 1style='display:table' class='clearfix21 {$className}'>
                [#ListTitle#]
                <div class='listTopContainer'>
                    [#ListFilter#]
                    [#ListSummary#]
                </div>
                [#ListPagerTop#]
                [#ListTable#]
                [#ListPagerBottom#]
                [#ListToolbar#]
            </div>
          ");
        
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
        $table = cls::get('core_TableView', array('mvc' => $this));
        
        $data->listFields = arr::make($data->listFields, TRUE);
        
        $tpl = $table->get($data->rows, $data->listFields);
        
        return new ET("<div class='listRows'>[#1#]</div>", $tpl);
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
        if(cls::isSubclass($data->toolbar, 'core_Toolbar') && !Mode::is('printing')) {
            
            return new ET("<div class='listToolbar'>[#1#]</div>", $data->toolbar->renderHtml());
        }
    }
    
    
    /**
     * Прави стандартна 'обвивка' на изгледа
     * @todo: да се отдели като плъгин
     */
    function renderWrapping_($tpl)
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
        
        $requiredRoles = $self->getRequiredRoles(strtolower($action), $rec, $userId);
        
        return Users::requireRole($requiredRoles, $retUrl, $action);
    }
    
    /****************************************************************************************
     *                                                                                      *
     *               Помощни функции                                                        *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Добавя запис в лога
     */
    static function log($detail, $objectId = NULL, $logKeepDays = NULL)
    {
        if (!$logKeepDays) {
            $logKeepDays = static::$logKeepDays;
        }
        
        core_Logs::add(get_called_class(), $objectId, $detail, $logKeepDays);
    }
    
    
    /**
     * Разшифрова лог съобщение
     */
    function logToVerbal($objectId, $detail)
    {
        $text = ucfirst($detail) . ' "' . tr($this->title ? $this->title : $this->className) . '"';
        
        if ($objectId) {
            $text .= ', ' . $objectId . " - " . $this->getTitleById($objectId);
        }
        
        return $text;
    }
    
    
    /**
     * Връща списък е елементи <option> при ajax заявка
     */
    function act_ajax_GetOptions()
    {
        Mode::set('wrapper', 'tpl_DefaultAjax');
        
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
        
        $this->log("ajaxGetOptions|{$q}");
        
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
}
