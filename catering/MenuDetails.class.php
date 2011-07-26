<?php 

/**
 * Менаджира детайлите на менюто (Details)
 */
class catering_MenuDetails extends core_Detail
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Детайли на меню";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Кетъринг";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, 
                          plg_Printing, catering_Wrapper, plg_Sorting, 
                          Menu=catering_Menu, 
                          EmployeesList=catering_EmployeesList, 
                          Companies=catering_Companies,
                          CrmCompanies=crm_Companies';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $masterKey = 'menuId';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'num, food, price, tools=Ред';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $tabName = "catering_Menu";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'admin, catering';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin, catering';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin, catering';
    
    
    /**
     *  @todo Чака за документация...
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
        $row->num = $num;
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
            $data->form->title = "Добавяне предложение на фирма <b>\"{$companyName}\"</b><br/>към меню за дата <b>{$date}</b>";
        } else {
            $data->form->title = "Добавяне предложение на фирма <b>\"{$companyName}\"</b><br/>към меню за <b>\"{$repeatDayVerbal}\"</b>";
        }
    }
}