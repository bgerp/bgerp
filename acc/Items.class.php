<?php

/**
 * Мениджър на пера.
 *
 * Перата са детайли (master-detail) на модела Номенклатури (@see acc_Lists)
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 */
class acc_Items extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_State2, plg_RowTools, editwatch_Plugin, 
                     plg_SaveAndNew, acc_WrapperSettings, Lists=acc_Lists, plg_State2,plg_Sorting';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Пера';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,acc';
    
    
    /**
     * var $canList = 'admin,acc';
     */
    var $canAdmin = 'admin,acc';
    
    
    /**
     * @var acc_Lists
     */
    var $Lists;
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'num,titleLink=Наименование,uomId,lastUseOn,state,tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        // Разпознаваем от човек номер на перото. При показване, това число се допълва с водещи 
        // нули, докато броят на цифрите му достигне стойността на полето padding, зададено в 
        // съответната му мастър номенклатура.
        $this->FLD('num', 'int', "caption=Номер,mandatory,remember=info,notNull");
        
        // Заглавие
        $this->FLD('title', 'varchar(64)', 'caption=Наименование,mandatory,remember=info');
        
        // Външен ключ към номенклатурата на това перо.
        $this->FLD('lists', 'keylist(mvc=acc_Lists,select=name)', 'caption=Номенклатура,input,mandatory');
        
        // Външен ключ към модела (класа), генерирал това перо. Този клас трябва да реализира
        // интерфейса, посочен в полето `interfaceId` на мастъра @link acc_Lists 
        $this->FLD('classId', 'class(interface=acc_RegisterIntf,select=title,allowEmpty)', 
        	'caption=Регистър,input=none');
        
        // Външен ключ към обекта, чиято сянка е това перо. Този обект е от класа, посочен в
        // полето `classId` 
        $this->FLD('objectId', 'int', "input=none,column=none,caption=Обект");
        
        // Мярка на перото. Има смисъл само ако мастър номенклатурата е отбелязана като 
        // "оразмерима" (acc_Lists::dimensional == true). Мярката се показва и въвежда само 
        // ако има смисъл.
        $this->FLD('uomId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'caption=Мярка,remember');
        
        // Състояние на перото
        $this->FLD('state', 'enum(active=Активно,closed=Затворено)', 'caption=Състояние,input=none');
        
        // Кога за последно е използвано
        $this->FLD('lastUseOn', 'datetime', 'caption=Последно,input=none');
        
        // Титла - хипервръзка
        $this->FNC('titleLink', 'html', 'column=none');
        
        // Номер и титла - хипервръзка
        $this->FNC('numTitleLink', 'html', 'column=none');
        
        // Наименование 
        $this->FNC('caption', 'html', 'column=none');
        
        $this->setDbUnique('objectId,classId');
    }
    
    
    /**
     * За полето titleLink създава линк към обекта от регистъра
     *
     * @todo: Това не е добро решение, защото това функционално поле ще се изчислява в много случаи без нужда.
     */
    function on_CalcTitleLink($mvc, $rec)
    {
        if ($rec->classId) {
            $AccRegister = cls::getInterface('acc_RegisterIntf', $rec->classId);
            $rec->titleLink = $AccRegister->getLinkToObj($rec->objectId);
        } else {
        	$rec->titleLink = $rec->title;
        }
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_CalcNumTitleLink($mvc, $rec)
    {
        if (!isset($rec->titleLink)) {
            $mvc->on_CalcTitleLink($mvc, $rec);
        }
        $rec->numTitleLink = $rec->num . '. ' . $rec->titleLink;
    }
    
    
    /**
     *
     */
    function on_CalcCaption($mvc, $rec)
    {
        $rec->caption = $mvc->getVerbal($rec, 'num') . '&nbsp;' . $mvc->getVerbal($rec, 'title');
    }
    
    
    /**
     *
     */
    function on_AfterGetVerbal($mvc, &$num, $rec, $part)
    {
        if($part == 'num') {
            $listRec = $mvc->Lists->fetch($mvc->getCurrentListId());
            $maxNumLen = strlen($listRec->itemMaxNum);
            $num = str_pad($num, $maxNumLen,'0',STR_PAD_LEFT);
            $num = str_replace('&nbsp;', '', $num);
        }
    }
    
    
    /**
     * Изпълнява се преди подготовката на редовете в таблицата
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $data->query->orderBy('#num');
    }
    
   
    /**
     * Изпълнява се след запис на перо
     * Предизвиква обновяване на обобщената информация за перата
     */
    function on_AfterSave($mvc, $id, $rec)
    {
    	$affectedLists = type_Keylist::toArray($rec->lists);
    	
    	foreach ($affectedLists as $listId) {
	        $mvc->Lists->updateSummary($listId);
    	}
    }
    
    
    /**
     * Изпълнява се преди изтриване на пера
     * Събира информация, на кои номенклатури трябва да си обновят информацията
     */
    function on_BeforeDelete($mvc, &$numRows, $query, $cond)
    {
    	$tmpQuery = clone($query);
        $query->_listsForUpdate = array();
        
        while($rec = $tmpQuery->fetch($cond)) {
        	$query->_listsForUpdate += type_Keylist::toArray($rec->lists);
        }
    }
    
    
    /**
     * Изпълнява се след изтриване на пера
     * Предизвиква обновяване на информацията на подбрание преди изтриване номенклатури
     */
    function on_AfterDelete($mvc, &$numRows, $query, $cond)
    {
        if(count($query->_listsForUpdate)) {
            foreach($query->_listsForUpdate as $listId) {
                $mvc->Lists->updateSummary($listId);
            }
        }
    }
    
    
    /**
     * Извиква се преди подготовката на титлата в списъчния изглед
     */
    function on_AfterPrepareListTitle($mvc, $data, $data)
    {
        $listId = $mvc->getCurrentListId();
        $listRec = $mvc->Lists->fetch($listId);
        
        $data->title = "Пера в номенклатурата|* <font color=green> {$listRec->caption} </font>";
        
        return FALSE;
    }
    
    
    /**
     *
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = $data->form;
        
        $listId = $mvc->getCurrentListId();
        $listRec = $mvc->Lists->fetch($listId);
        
        if($listRec->dimensional == 'no') {
            $form->setField('uomId', 'input=none');
        }
        
        $form->fields['lists']->type->suggestions = acc_Lists::getPossibleLists(null);
        
        if(!$form->rec->num && ($num = Mode::get('lastEnterItemNumIn'.$listId))) {
            $num++;
            
            if(!$mvc->fetch("#lists LIKE '%|{$listId}|%' && #num = {$num}")) {
                $form->setDefault('num', $num);
            }
        }
        
        $form->title = "Добавяне на перо в|* <b>{$listRec->caption}<b>";
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от заявката във формата
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    function on_AfterInputEditForm($mvc, $form)
    {
    	if ($form->gotErrors()) {
    		return;
    	}
        if(!$form->rec->id) {
            $listId = $mvc->getCurrentListId();
            Mode::setPermanent('lastEnterItemNumIn'.$listId, $rec->num);
        }
    }
    
    
    /**
     *
     */
    function on_AfterPrepareListFields($mvc, $data)
    {
        $listId = $mvc->getCurrentListId();
        $listRec = $mvc->Lists->fetch($listId);
        
        if($listRec->dimensional == 'no') {
            unset($data->listFields['uomId']);
        }
        
        if($listRec->regInterfaceId) {
            unset($data->listFields['tools']);
        }
    }
    
    
    /**
     * Добавя филтър към перата
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('listId', 'key(mvc=acc_Lists,select=name)', 'input,caption=xxx');
        $data->listFilter->FNC('search', 'varchar', 'caption=Търсене,input,silent');
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'listId, search';
        
        $data->listFilter->setDefault('listId', $listId = $mvc->getCurrentListId());

        $filter = $data->listFilter->input();
        
        expect($filter->listId);
        
        $data->query->where("#lists LIKE '%|{$filter->listId}|%'");
        
        if($filter->search) {
            $data->query->where(array("#title LIKE '[#1#]'", "%{$filter->search}%"));
        }
    }
    
    
    /**
     *
     */
    function on_BeforeGetRequiredRoles($mvc, &$roles, $cmd)
    {
        if($cmd == 'write') {
            $listId = $mvc->getCurrentListId();
            
            $listRec = $mvc->Lists->fetch($listId);
            
            if(!$listRec || $listRec->regInterfaceId) {
                $roles = 'no_one';
                
                return FALSE;
            }
        }
    }
    
    
    /**
     * Тази функция връща текущата номенклатура, като я открива по първия възможен начин:
     *
     * 1. От Заявката (Request)
     * 2. От Сесията (Mode)
     * 3. Първата активна номенклатура от таблицата
     *
     */
    function getCurrentListId()
    {
        $listId = Request::get('listId', 'key(mvc=acc_Lists,select=name)');
        
        if(!$listId) {
            $listId = Mode::get('currentListId');
        }
        
        if(!$listId) {
            $listQuery = $this->Lists->getQuery();
            $listQuery->orderBy('num');
            $listRec = $listQuery->fetch('1=1');
            $listId = $listRec->id;
        }
        
        if($listId) {
            Mode::setPermanent('currentListId', $listId);
        } else {
            redirect(array('acc_Lists'));
        }
        
        return $listId;
    }
    
    
    /**
     * Извлича опциите според id-то на номенклатурата
     */
    function fetchOptions($listId)
    {
        $query = $this->getQuery();
        
        $query->orderBy("#num");
        
        while($rec = $query->fetch("#lists LIKE '%|{$listId}|%' AND #state = 'active'")) {
            $options[$rec->id] = $this->getVerbal($rec, 'caption');
        }
        
        return $options;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function getItemsKeys($objectKeys, $listId) {
        $query = $this->getQuery();
        $query->where("#lists LIKE '%|{$listId}|%'");
        $query->where("#objectId IN (" . implode(',', $objectKeys) . ')');
        
        $result = array();
        
        while ($rec = $query->fetch()) {
            $result[$rec->objectId] = $rec->id;
        }
        
        return $result;
    }
}
