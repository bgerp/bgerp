<?php

/**
 * Клас 'core_Detail' - Мениджър за детаилите на бизнес обектите
 *
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2009 Experta Ltd.
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class core_Detail extends core_Manager
{
    
    
    /**
     * Полето-ключ към мастера
     */
    var $masterKey;
    
    
    /**
     * По колко реда от резултата да показава на страница в детайла на документа
     * Стойност '0' означава, че детайла няма да се странира
     */
    var $listItemsPerPage = 0;

    
    /**
     * Изпълнява се след началното установяване на модела
     */
    function on_AfterDescription($mvc)
    {
        expect($this->masterKey);
        
        expect($masterClass = $this->fields[$this->masterKey]->type->params['mvc']);
        
        $this->fields[$this->masterKey]->silent = silent;
        
        if(!isset($this->fields[$this->masterKey]->input)) {
            $this->fields[$this->masterKey]->input = hidden;
        }
        
        $mvc->Master = &cls::get($masterClass);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function prepareDetail_($data)
    {
        // Създаваме заявката
        $data->query = $this->getQuery();
        
        // Очакваме да masterKey да е зададен
        expect($this->masterKey);
        
        // Добавяме връзката с мастер-обекта
        $data->query->where("#{$this->masterKey} = {$data->masterId}");
        
        // Подготвяме полетата за показване
        $this->prepareListFields($data);
        
        // Подготвяме навигацията по страници
        $this->prepareListPager($data);

        // Подготвяме тулбара
        $this->prepareListToolbar($data);

        // Подготвяме редовете от таблицата
        $this->prepareListRecs($data);
        
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
     * Подготвя лентата с инструменти за табличния изглед
     */
    function prepareListToolbar_(&$data)
    {
        $data->toolbar = cls::get('core_Toolbar');
        
        if ($this->Master->haveRightFor('edit', $data->masterId) &&
            $this->haveRightFor('add')   ) {
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

        $title = $this->Master->getTitleById($data->form->rec->{$masterKey});

        $data->form->title = $data->form->rec->id?"Редактиране в":"Добавяне към";

        $data->form->title .= "|* \"$title\"";

        return $data;
    }
    
    
    
    /**
     * Връща ролите, които могат да изпълняват посоченото действие
     */
    function getRequiredRoles_($action, $rec = NULL, $userId = NULL)
    { 
        if($action == 'read') {
            return 'no_one';
        }
        
        expect($masterKey = $this->masterKey);
        
        if($action == 'write' && isset($rec)) {
            $masterRec = $this->Master->fetch($rec->{$masterKey});
            
            return $this->Master->getRequiredRoles('edit', $masterRec, $userId);
        }
        
        return parent::getRequiredRoles_($action, $rec, $userId);
    }
}