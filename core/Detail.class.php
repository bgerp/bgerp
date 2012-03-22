<?php



/**
 * Клас 'core_Detail' - Мениджър за детайлите на бизнес обектите
 *
 *
 * @category  all
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
    function on_AfterDescription(&$mvc)
    {
        expect($mvc->masterKey);
        
        expect($masterClass = $mvc->fields[$mvc->masterKey]->type->params['mvc']);
        
        $this->fields[$mvc->masterKey]->silent = silent;
        
        if(!isset($mvc->fields[$mvc->masterKey]->input)) {
            $mvc->fields[$mvc->masterKey]->input = hidden;
        }
        
        $mvc->Master = &cls::get($masterClass);

        $mvc->currentTab = $masterClass;
        
        setIfNot($mvc->fetchFieldsBeforeDelete, $mvc->masterKey);
    }
    
    
    /**
     * Подготвяме  общия изглед за 'List'
     */
    function prepareDetail_($data)
    {
        // Очакваме да masterKey да е зададен
        expect($this->masterKey);
        
        // Подготвяме заявката за детайла
        $this->prepareDetailQuery($data);
        
        // Подготвяме полетата за показване
        $this->prepareListFields($data);
        
        // Подготвяме навигацията по страници
        $this->prepareListPager($data);
        
        // Подготвяме лентата с инструменти
        $this->prepareListToolbar($data);
        
        // Подготвяме редовете от таблицата
        $this->prepareListRecs($data);
        
        // Подготвяме вербалните стойности за редовете
        $this->prepareListRows($data);
        
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
        // Рендираме общия лейаут
        $tpl = $this->renderDetailLayout($data);
        
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
        $data->query->where("#{$this->masterKey} = {$data->masterId}");
        
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
                    'add',
                    $this->masterKey => $data->masterId,
                    'ret_url' => TRUE
                ),
                'id=btnAdd,class=btn-add');
        }
        
        return $data;
    }
    
    
    /**
     * Подготвя формата за редактиране
     */
    function prepareEditForm_($data)
    {
        parent::prepareEditForm_($data);
        
        $masterKey = $this->masterKey;
        
        expect($data->masterId = $data->form->rec->{$masterKey});
        
        expect($data->masterRec = $this->Master->fetch($data->masterId));
        
        $title = $this->Master->getTitleById($data->masterId);
        
        $data->form->title = $data->form->rec->id ? "Редактиране в" : "Добавяне към";
        
        $data->form->title .= "|* \"$title\"";
        
        return $data;
    }
    
    
    /**
     * Връща ролите, които могат да изпълняват посоченото действие
     */
    function getRequiredRoles_($action, $rec = NULL, $userId = NULL)
    {
        
        if($action == 'read') {
            // return 'no_one';
        }
        
        if($action == 'write' && isset($rec)) {
            
            expect($masterKey = $this->masterKey);
            expect($this->Master, $this);
            $masterRec = $this->Master->fetch($rec->{$masterKey});
            
            return $this->Master->getRequiredRoles('edit', $masterRec, $userId);
        }
        
        return parent::getRequiredRoles_($action, $rec, $userId);
    }
    
    
    /**
     * След запис в детайла извиква събитието 'AfterUpdateDetail' в мастъра
     */
    function on_AfterSave($mvc, $id, $rec)
    {
        $masterKey = $mvc->masterKey;
        
        if($rec->{$masterKey}) {
            $masterId = $rec->{$masterKey};
        } elseif($rec->id) {
            $masterId = $mvc->fetchField($rec->id, $masterKey);
        }
        
        $mvc->Master->invoke('AfterUpdateDetail', array($masterId, $mvc));
    }
    
    
    /**
     * След изтриване в детайла извиква събитието 'AfterUpdateDetail' в мастъра
     */
    function on_AfterDelete($mvc, $numRows, $query, $cond)
    {
        if($numRows) {
            $masterKey = $mvc->masterKey;
            
            foreach($query->getDeletedRecs() as $rec) {
                $masterId = $rec->{$masterKey};
                $mvc->Master->invoke('AfterUpdateDetail', array($masterId, $mvc));
            }
        }
    }
}