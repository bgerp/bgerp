<?php



/**
 * Клас 'core_Detail' - Мениджър за детайлите на бизнес обектите
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
class core_Detail extends core_Manager
{
    
    
    /**
     * Полето-ключ към мастъра
     */
    var $masterKey;
    
    
    /**
     * По колко реда от резултата да показва на страница в детайла на документа
     * Стойност '0' означава, че детайла няма да се странира
     */
    var $listItemsPerPage = 0;
    
    
    /**
     * Изпълнява се след началното установяване на модела
     */
    static function on_AfterDescription(&$mvc)
    {
        expect($mvc->masterKey);
        
        $mvc->fields[$mvc->masterKey]->silent = 'silent';
                
        setIfNot($mvc->fetchFieldsBeforeDelete, $mvc->masterKey);
        
        if ($mvc->masterClass = $mvc->fields[$mvc->masterKey]->type->params['mvc']) {
            $mvc->Master = cls::get($mvc->masterClass);
        }
    }
    
    
    /**
     * Подготвяме  общия изглед за 'List'
     */
    function prepareDetail_($data)
    {
    	
        setIfNot($data->masterKey, $this->masterKey);
        setIfNot($data->masterMvc, $this->Master);
        
        // Очакваме да masterKey да е зададен
        expect($data->masterKey);
        expect($data->masterMvc instanceof core_Master);
        
        // Подготвяме заявката за детайла
        $this->prepareDetailQuery($data);
        
        // Подготвяме полетата за показване
        $this->prepareListFields($data);
        
        // Подготвяме филтъра
        $this->prepareListFilter($data);
        
        // Подготвяме заявката за резюме/обощение
        $this->prepareListSummary($data);
        
        // Името на променливата за страниране на детайл
        $data->pageVar = 'P_' . $this->className . $data->masterId;
        
        // Подготвяме навигацията по страници
        $this->prepareListPager($data);
        
        // Подготвяме редовете от таблицата
        $this->prepareListRecs($data);
        
        // Подготвяме вербалните стойности за редовете
        $this->prepareListRows($data);
     
        // Подготвяме лентата с инструменти
        $this->prepareListToolbar($data);

        return $data;
    }
    
    
    /**
     * Създаване на шаблона за общия List-изглед
     */
    function renderDetailLayout_($data)
    {
      
        $className = cls::getClassName($this);
        
        // Шаблон за листовия изглед
        $listLayout = new ET("
            <div class='clearfix21 {$className}'>
            	<div class='listTopContainer clearfix21'>
                    [#ListFilter#]
                </div>
                [#ListPagerTop#]
                [#ListTable#]
                [#ListSummary#]
                [#ListToolbar#]
            </div>
        ");
        
        return $listLayout;
    }
    
    
    /**
     * Рендираме общия изглед за 'List'
     */
    function renderDetail_($data)
    { 
        if(!isset($data->listClass)) {
            $data->listClass = 'listRowsDetail';
        }

        if (!isset($this->currentTab)) {
            $this->currentTab = $data->masterMvc->title;
        }
        
        // Рендираме общия лейаут
        $tpl = $this->renderDetailLayout($data);
        
        // Попълваме формата-филтър
        $tpl->append($this->renderListFilter($data), 'ListFilter');
        
        // Попълваме обобщената информация
        $tpl->append($this->renderListSummary($data), 'ListSummary');
        
        // Попълваме таблицата с редовете
        $tpl->append($this->renderListTable($data), 'ListTable');
        
        // Попълваме таблицата с редовете
        $tpl->append($this->renderListPager($data), 'ListPagerTop');
        
        // Попълваме долния тулбар
        $tpl->append($this->renderListToolbar($data), 'ListToolbar');
        
        return $tpl;
    }
    
    
    /**
     * Подготвя заявката за данните на детайла
     */
    function prepareDetailQuery_($data)
    {
        // Създаваме заявката
        $data->query = $this->getQuery();
        
        // Добавяме връзката с мастер-обекта
        $data->query->where("#{$data->masterKey} = {$data->masterId}");
        
        return $data;
    }
    
    
    /**
     * Подготвя лентата с инструменти за табличния изглед
     */
    function prepareListToolbar_(&$data)
    {
        $data->toolbar = cls::get('core_Toolbar');
 
        $masterKey = $data->masterKey;

        if($data->masterId) {
            $rec = new stdClass();
            $rec->{$masterKey} = $data->masterId;
        }

        if ($this->haveRightFor('add', $rec) && $data->masterId) {
            $data->toolbar->addBtn('Нов запис', array(
                    $this,
                    'add',
                    $masterKey => $data->masterId,
                    'ret_url' => array($data->masterMvc, 'single', $rec->{$masterKey}, 'Tab' => Request::get('Tab'))
                ),
                'id=btnAdd', 'ef_icon = img/16/star_2.png,title=Създаване на нов запис');
        }
        
        return $data;
    }
    
    
    /**
     * Подготвя формата за редактиране
     */
    function prepareEditForm_($data)
    {
        setIfNot($data->singleTitle, $this->singleTitle);

        parent::prepareEditForm_($data);
        
        $form = $data->form;

        if(!$data->masterMvc) {
            $data->masterMvc = $this->getMasterMvc($data->form->rec);  
        }

        if(!$data->masterKey) {
            $data->masterKey = $this->getMasterKey($data->form->rec);
        }

        // Очакваме да masterKey да е зададен
        expect($data->masterKey, $data); 
        expect($data->masterMvc instanceof core_Master, $data);
        
        $masterKey = $data->masterKey;

        if(!isset($form->fields[$masterKey]->input) || $form->fields[$masterKey]->input == 'none') {
            $form->fields[$masterKey]->input = 'hidden';
        }
 
        expect($data->masterId = $data->form->rec->{$masterKey}, $data->form->rec);
        expect($data->masterRec = $data->masterMvc->fetch($data->masterId), $data);
        $title = $data->masterMvc->getTitleById($data->masterId);
        if ($data->singleTitle) {
            $single = ' на| ' . mb_strtolower($data->singleTitle) . '|';
       
        }
 
        $data->form->title = $data->form->rec->id ? "Редактиране{$single} в" : "Добавяне{$single} към";
        $data->form->title .= "|* <b style='color:#ffffcc;'>" . str::limitLen($title, 32) . "</b>";
 
        return $data;
    }
    

    /**
     * Дефолт функция за определяне мастера, спрямо дадения запис
     */
    function getMasterMvc_($rec)
    {
        return $this->Master;
    }
    

    /**
     * Дефолт функция за определяне полето-ключ към мастера, спрямо дадения запис
     */
    function getMasterKey_($rec)
    {
        return $this->masterKey;
    }
     
    
    /**
     * Връща ролите, които могат да изпълняват посоченото действие
     */
    function getRequiredRoles_(&$action, $rec = NULL, $userId = NULL)
    {
        
        if($action == 'read') {
            // return 'no_one';
        }
        
        if($action == 'write' && isset($rec) && $this->Master instanceof core_Master) {
            
            expect($masterKey = $this->masterKey);
            
            if($rec->{$masterKey}) {
                $masterRec = $this->Master->fetch($rec->{$masterKey});
            }
            
            if ($masterRec) {
                return $this->Master->getRequiredRoles('edit', $masterRec, $userId);
            }
        }
        
        return parent::getRequiredRoles_($action, $rec, $userId);
    }
    
    
    /**
     * След запис в детайла извиква събитието 'AfterUpdateDetail' в мастъра
     */
    function save_(&$rec, $fieldsList = NULL, $mode = NULL)
    {
        if (!$id = parent::save_($rec, $fieldsList, $mode)) {
            return FALSE;
        }

        $masterKey = $this->masterKey;
        
        $masters = $this->getMasters($rec);
        
        foreach ($masters as $masterKey => $masterInstance) {
            if($rec->{$masterKey}) {
                $masterId = $rec->{$masterKey};
            } elseif($rec->id) {
                $masterId = $this->fetchField($rec->id, $masterKey);
            }
            
            $masterInstance->invoke('AfterUpdateDetail', array($masterId, $this));
        }
        
        return $id;
    }
    
    
    
    /**
     * Логва действието след запис
     * 
     * @param string $msg
     * @param stdClass $rec
     * @param string $type
     */
    function logInAct($msg, $rec, $type = 'info')
    {
        $masterKey = $this->masterKey;
        $masters = $this->getMasters($rec);
        
        $newMsg = $msg . ' на детайл';
        
        foreach ($masters as $masterKey => $masterInstance) {
            if($rec->{$masterKey}) {
                $masterId = $rec->{$masterKey};
            } elseif($rec->id) {
                $masterId = $this->fetchField($rec->id, $masterKey);
            }
            
            if ($type == 'info') {
                $masterInstance->logInfo($newMsg, $masterId);
            } else {
                $masterInstance->logErr($newMsg, $masterId);
            }
        }
        
        parent::logInAct($msg, $rec, $type);
    }
    
    
    /**
     * След изтриване в детайла извиква събитието 'AfterUpdateDetail' в мастъра
     */
    static function on_AfterDelete($mvc, &$numRows, $query, $cond)
    {
        if ($numRows) {
            foreach($query->getDeletedRecs() as $rec) {
                $masters = $mvc->getMasters($rec);
                
                foreach ($masters as $masterKey => $masterInstance) {
                    $masterId = $rec->{$masterKey};
                    $masterInstance->invoke('AfterUpdateDetail', array($masterId, $mvc));
                }
            }
        }
    }
    
    
    /**
     * 
     * 
     * @see core_Manager::act_Delete()
     */
    function act_Delete()
    {
        $id = Request::get('id', 'int');
        
        $rec = $this->fetch($id);
        
        $masterKey = $this->masterKey;
        
        $masters = $this->getMasters($rec);
        
        foreach ($masters as $masterKey => $masterInstance) {
            if ($rec->{$masterKey}) {
                $masterId = $rec->{$masterKey};
            } elseif($rec->id) {
                $masterId = $this->fetchField($rec->id, $masterKey);
            }
            
            $masterInstance->logInfo('Изтриване на детайл', $masterId);
        }
        
        return parent::act_Delete();
    }
    
    
    /**
     * Връща списъка от мастър-мениджъри на зададен детайл-запис.
     * 
     * Обикновено детайлите имат точно един мастър. Използваме този метод в случаите на детайли
     * с повече от един мастър, който евентуално зависи и от данните в детайл-записа $rec.
     * 
     * @param stdClass $rec
     * @return array масив от core_Master-и. Ключа е името на полето на $rec, където се 
     *               съхранява външния ключ към съотв. мастър
     */
    public function getMasters_($rec)
    {
        return isset($this->Master) ? array($this->masterKey => $this->Master) : array();
    }
}
