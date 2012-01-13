<?php


/**
 * Фирми доставчици на храна
 *
 *
 * @category  bgerp
 * @package   catering
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class catering_Companies extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Фирми за кетъринг";
    
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created,  plg_RowTools, plg_State,
                             plg_Printing, catering_Wrapper, plg_Sorting,
                             CrmCompanies=crm_Companies';
    
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'num, tools=Пулт, name=Фирма, address=Адрес, phones=Телефони';
    
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'catering,admin';
    
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'catering,admin';
    
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FNC('num', 'int', 'caption=№, notSorting');
        $this->FLD('companyId', 'key(mvc=crm_Companies, select=name)', 'caption=Фирма');
        $this->FNC('name', 'varchar(255)', 'caption=Фирма');
        $this->FNC('address', 'varchar(255)', 'caption=Адрес, notSorting');
        $this->FNC('phones', 'varchar(255)', 'caption=Телефони, notSorting');
        
        $this->setDbUnique('companyId');
    }
    
    
    
    /**
     * Ако няма записи не вади таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        if(!count($data->recs)) {
            $res = new ET('За да изполвате услугата "Кетъринг" е необходимо да има дефинирана поне една компания за доставка на храна.<br/><br/>');
            
            return FALSE;
        }
    }
    
    
    
    /**
     * Манипулации по формата за редактиране / добавяне
     * Ако редактираме се листват всички фирми.
     * Ако добавяме се листват само тези фирми, които не са вече добавени.
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $data->form->title = "Добавяне на запис във \"Фирми за кетъринг\"";
        $data->form->setDefault('state', 'active');
        
        // START Prepare select options for $companyId
        if ($data->form->rec->id) {
            // В случай, че редактираме записа
            $queryCrmCompanies = $mvc->CrmCompanies->getQuery();
            
            while($recCrmCompanies = $queryCrmCompanies->fetch("1=1")) {
                $selectOptCompanies[$recCrmCompanies->id] = $mvc->CrmCompanies->fetchField("#id = {$recCrmCompanies->id}", 'name');
            }
            
            unset($recCrmCompanies);
            // END Редактираме записа
        } else {
            
            // В случай, че добавяне нов запис
            $queryCompaniesInUse = $this->getQuery();
            $where = "1=1";
            
            while($recCompaniesInUse = $queryCompaniesInUse->fetch($where)) {
                $companiesInUse[$recCompaniesInUse->companyId] = $mvc->CrmCompanies->fetchField("#id = {$recCompaniesInUse->companyId}", 'name');
            }
            unset($recCompaniesInUse);
            
            $queryCrmCompanies = $mvc->CrmCompanies->getQuery();
            
            if (!empty($companiesInUse)) {
                // List only companies which are not already in use
                while($recCrmCompanies = $queryCrmCompanies->fetch("1=1")) {
                    if (!array_key_exists($recCrmCompanies->id, $companiesInUse)) {
                        $selectOptCompanies[$recCrmCompanies->id] = $mvc->CrmCompanies->fetchField("#id = {$recCrmCompanies->id}", 'name');
                    }
                }
            } else {
                // List all companies
                while($recCrmCompanies = $queryCrmCompanies->fetch("1=1")) {
                    $selectOptCompanies[$recCrmCompanies->id] = $mvc->CrmCompanies->fetchField("#id = {$recCrmCompanies->id}", 'name');
                }
            }
            
            unset($recCrmCompanies);
        }
        
        $data->form->setOptions('companyId', $selectOptCompanies);
        // END Prepare select options for $companyId        
    }
    
    
    
    /**
     * Промяна на данните от таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal ($mvc, $row, $rec)
    {
        // Prpare 'Num'
        static $num;
        $num += 1;
        $row->num = $num;
        
        $queryCrmCompanies = $mvc->CrmCompanies->getQuery();
        
        while($cRec = $queryCrmCompanies->fetch("#id = {$rec->companyId}")) {
            $companyDetails = $cRec;
        }
        
        $row->name = $mvc->getVerbal($companyDetails, 'name');
        $row->address = type_Varchar::escape($companyDetails->pCode);
        
        $row->address = type_Varchar::escape($companyDetails->pCode) . ", 
                        " . type_Varchar::escape($companyDetails->place) .
        "<br/>" . type_Varchar::escape($companyDetails->address);
        
        $row->phones = "<div class='contacts-row'>
                             <p class='clear_l w-80px gr'>телефон: </p>
                             <p>" . type_Varchar::escape($companyDetails->tel) . "</p>
                             <p class='clear_l w-80px gr'>мобилен: </p>
                             <p>" . type_Varchar::escape($companyDetails->mobile) . "</p>
                             <p class='clear_l w-80px gr'>факс: </p> 
                             <p>" . type_Varchar::escape($companyDetails->fax) . "</p>
                         </div>";
    }
    
    
    
    /**
     * Махаме бутона за печат, ако няма записи
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareListToolbar($mvc, $data)
    {
        if(!count($data->recs)) {
            $data->toolbar->removeBtn('btnPrint');
        }
    }
}