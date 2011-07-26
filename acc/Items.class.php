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
                     plg_SaveAndNew, acc_Wrapper, Lists=acc_Lists, plg_State2,plg_Sorting';
    
    
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
        
        // Мениджър на перата тази номенклатура
        $this->FLD('listId', 'key(mvc=acc_Lists,select=name)', 'caption=Номенклатура,input=hidden,mandatory');
        
        // Външен ключ към модела, зададен в полето regItemManager 
        $this->FLD('objectId', 'int', "input=none,column=none,caption=Обект");
        
        // Мярка на перото. Има смисъл само ако мастър номенклатурата е отбелязана като 
        // "оразмерима" (acc_Lists::dimensional == true). Мярката се показва и въвежда само 
        // ако има смисъл.
        $this->FLD('uomId', 'key(mvc=common_Units,select=name)', 'caption=Мярка,remember,mandatory');
        
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
        
        $this->setDbUnique('objectId,listId');
        $this->setDbUnique('num,listId');
    }
    
    
    /**
     * За полето titleLink създава линк към обекта от регистъра
     */
    function on_CalcTitleLink($mvc, $rec)
    {
        $listRec = $mvc->Lists->fetch($rec->listId);
        $rec->titleLink = $mvc->getVerbal($rec, 'title');
        
        if($listRec->regClassId) {
            $Classes = &cls::get('core_Classes');
            $regItemManager = $Classes->fetchField($listRec->regClassId, 'name');
            
            if(method_exists($regItemManager, 'act_Single')) {
                $rec->titleLink = Ht::createLink($rec->titleLink, array($regItemManager, 'single', $rec->objectId));
            }
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
            $listRec = $mvc->Lists->fetch($rec->listId);
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
     * Добавя обект от регистър
     * Входни параметри:
     *
     * $mvc - класа на регистъра
     * $rec->objectId - id на обекта от регистъра
     * $rec->title    - заглавие на перото
     * $rec->num      - номер на перото. Ако е пропуснато - използва се objectId
     * $rec->uomId    - мярка на обекта, ако има
     * $rec->inList   - в кои номенклатури е обекта. Празен списък е равносилно на изтриване
     *
     */
    function addFromRegister($itemRec)
    {
        // 1. Вземаме всички номенклатури, които са с този регистър
        // 2. За всички от тези номенклатури, които не се срещат в $rec->inList
        //    изтриваме перата.
        // 3. За всички, които са в $rec->inList, но не са от първия списък - добавяме перото
        
        // Определяме номенклатурите, където трябва да съдържат този обект
        $inList = type_Keylist::toArray($itemRec->inList);
        
        // Определяме всички възможни номенклатури, в които може да бъде включен този обект
        $allList = array();
        $listQuery = $this->Lists->getQuery();
        
        while($rec = $listQuery->fetch("#regClassId = {$itemRec->regClassId} && #state = 'active'")) {
            $allList[$rec->id] = $rec->id;
        }
        
        // Очакваме, че броят на всички номенклатури е по-голям или равен на броя на тези, 
        // в които ще бъде включен обекта
        expect( count($allList) >= count($inList) );
        
        if( count($allList) ) {
            foreach($allList as $id) {
                // Ако има перо в текущата номенклатура, извличаме го
                
                $rec = $this->fetch("#objectId = {$itemRec->objectId} AND #listId = {$id}");
                
                if($inList[$id]) {
                    
                    $num = $itemRec->num ? $itemRec->num : $itemRec->objectId;;
                    $uomId = $itemRec->uomId ? $itemRec->uomId : NULL;
                    
                    // Добавяме обекта към номенклатурата
                    if($rec && ($rec->state == 'active') &&
                    ($rec->num == $num) &&
                    ($rec->title == $itemRec->title)
                    ) {
                        
                        continue;
                    }
                    
                    if(!$rec) {
                        $rec = new stdClass();
                    }
                    
                    $rec->objectId = $itemRec->objectId;
                    $rec->listId = $id;
                    $rec->state = 'active';
                    $rec->num = $num;
                    $rec->title = $itemRec->title;
                    $rec->uomId = $uomId;
                    
                    $this->save($rec);
                } else {
                    // Премахваме обекта от номенклатурата
                    if($rec && ($rec->state == 'active')) {
                        if($rec->lastUseOn) {
                            $rec->state = 'closed';
                            $this->save($rec);
                        } else {
                            $this->delete($rec->id);
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се след запис на перо
     * Предизвиква обновяване на обобщената информация за перата
     */
    function on_AfterSave($mvc, $id, $rec)
    {
        $mvc->Lists->updateSummary($rec->listId);
    }
    
    
    /**
     * Изпълнява се преди изтриване на пера
     * Събира информация, на кои номенклатури трябва да си обновят информацията
     */
    function on_BeforeDelete($mvc, &$numRows, $query, $cond)
    {
        $tmpQuery = clone($query);
        
        while($rec = $tmpQuery->fetch($cond)) {
            $query->_listsForUpdate[$rec->listId] = $rec->listId;
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
        
        $data->title = tr("Пера в номенклатурата|* <font color=green> {$listRec->caption} </font>");
        
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
        
        $form->setHidden('listId', $listId);
        
        if(!$form->rec->num && ($num = Mode::get('lastEnterItemNumIn'.$listId))) {
            $num++;
            
            if(!$mvc->fetch("#listId = {$listId} && #num = {$num}")) {
                $form->setDefault('num', $num);
            }
        }
        
        $form->title = tr("Добавяне на перо в|* <b>{$listRec->caption}<b>");
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от заявката във формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        if(!$form->rec->id && ($num = Request::get('num', 'int'))) {
            $listId = $mvc->getCurrentListId();
            Mode::setPermanent('lastEnterItemNumIn'.$listId, $num);
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
        
        if($listRec->regClassId) {
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
        $data->listFilter->setField('listId', 'input');
        $data->listFilter->FNC('search', 'varchar', 'caption=Търсене,input,silent');
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'listId,search';
        
        $listId = $mvc->getCurrentListId();
        
        $data->listFilter->setDefault('listId', $listId);
        
        $data->query->where("#listId = {$listId}");
        
        $filter = $data->listFilter->input();
        
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
            
            if(!$listRec || $listRec->regClassId) {
                $roles = 'noone';
                
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
        $listId = Request::get('listId');
        $listId = $this->fields['listId']->type->fromVerbal($listId);
        
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
        
        while($rec = $query->fetch("#listId = $listId AND #state = 'active'")) {
            $options[$rec->id] = $this->getVerbal($rec, 'caption');
        }
        
        return $options;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function getItemsKeys($objectKeys, $listId) {
        $query = $this->getQuery();
        $query->where("#listId = {$listId}");
        $query->where("#objectId IN (" . implode(',', $objectKeys) . ')');
        
        $result = array();
        
        while ($rec = $query->fetch()) {
            $result[$rec->objectId] = $rec->id;
        }
        
        return $result;
    }
}