<?php

/**
 * Мениджър за групи на валутите
 */
class bank_CurrencyGroupContent extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, bank_Wrapper, CurrencyGroups=bank_CurrencyGroups';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = "id, currencyName";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Валути в група';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('groupId', 'key(mvc=bank_CurrencyGroups, select=name)', 'caption=Група, input=hidden');
        $this->FLD('currencyName', 'key(mvc=bank_Currencies, select=name)', 'caption=Валути');
        
        $this->setDbUnique('groupId, currencyName');
    }
    
    
    /**
     * Добавяме groupId и groupName в сесия филтрираме select-а
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $groupId = Request::get('id');
        $groupName = Request::get('groupName');
        
        $data->title = $groupName;
        
        Mode::setPermanent('groupId', $groupId);
        Mode::setPermanent('groupName', $groupName);
        
        $data->query->where("#groupId = {$groupId}");
    }
    
    
    /**
     * Сменяме заглавието на edit формната и даваме стойност на скритото поле
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClassunknown_type $data
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $data->form->title = "Добавяне валути в група \"" . Mode::get('groupName') . "\"";
        $data->form->setDefault('groupId', Mode::get('groupId'));
    }
}