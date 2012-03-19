<?php



/**
 * Мениджър за групи на валутите
 *
 *
 * @category  all
 * @package   currency
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class currency_CurrencyGroupContent extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, currency_Wrapper, CurrencyGroups=currency_CurrencyGroups';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "id, currencyName";
    
    
    /**
     * Заглавие
     */
    var $title = 'Валути в група';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('groupId', 'key(mvc=currency_CurrencyGroups, select=name)', 'caption=Група, input=hidden');
        $this->FLD('currencyName', 'key(mvc=currency_Currencies, select=name)', 'caption=Валути');
        
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
     * Сменяме заглавието на edit формата и даваме стойност на скритото поле
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClassunknown_type $data
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $data->form->title = "Добавяне валути в група|* \"" . Mode::get('groupName') . "\"";
        $data->form->setDefault('groupId', Mode::get('groupId'));
    }
}