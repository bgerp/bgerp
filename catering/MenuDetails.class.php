<?php 


/**
 * Менаджира детайлите на менюто (Details)
 *
 *
 * @category  bgerp
 * @package   catering
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class catering_MenuDetails extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    var $title = "Детайли на меню";
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Кетъринг";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, 
                     catering_Wrapper, plg_Sorting, 
                     Menu=catering_Menu, 
                     EmployeesList=catering_EmployeesList, 
                     Companies=catering_Companies,
                     CrmCompanies=crm_Companies';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'menuId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'num, food, price';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'num';
    
    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = "catering_Menu";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, catering';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin, catering';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, catering';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin, catering';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FNC('num', 'int', 'caption=№, notSorting');
        $this->FLD('menuId', 'key(mvc=catering_Menu)', 'caption=Меню, input=hidden');
        $this->FLD('food', 'varchar(255)', 'caption=Артикул, notSorting');
        $this->FLD('price', 'double(decimals=2)', 'caption=Цена, notSorting');
    }
    
    
    /**
     * Prepare 'num'
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Prpare 'Num'
                static $num;
        $num += 1;
        $row->num .= $num;
    }
    
    
    /**
     * Смяна на заглавието
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $companyId = $mvc->Menu->fetchField($data->form->rec->menuId, 'companyId');
        $companyIdCrmCompanies = $mvc->Companies->fetchField($companyId, 'companyId');
        $companyName = $mvc->CrmCompanies->fetchField($companyIdCrmCompanies, 'name');
        
        $repeatDay = $mvc->Menu->fetchField($data->form->rec->menuId, 'repeatDay');
        $date = $mvc->Menu->fetchField($data->form->rec->menuId, 'date');
        $repeatDayRec = $mvc->Menu->fetch($data->form->rec->menuId);
        $repeatDayVerbal = $mvc->Menu->getVerbal($repeatDayRec, 'repeatDay');
        
        $date = dt::mysql2verbal($mvc->Menu->fetchField($data->form->rec->menuId, 'date'), 'd-m-Y');
        
        if ($repeatDay == '0.OnlyOnThisDate') {
            $data->form->title = "Добавяне предложение на фирма|* <b>\"{$companyName}\"</b><br/>|към меню за дата|* <b>{$date}</b>";
        } else {
            $data->form->title = "Добавяне предложение на фирма|* <b>\"{$companyName}\"</b><br/>|към меню за|* <b>\"{$repeatDayVerbal}\"</b>";
        }
    }
}