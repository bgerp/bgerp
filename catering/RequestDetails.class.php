<?php 


/**
 * Мениджира детайлите на поръчка (Details)
 *
 *
 * @category  bgerp
 * @package   catering
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class catering_RequestDetails extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    var $title = "Детайли на поръчка";
    
    
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
                     MenuDetails=catering_MenuDetails,
                     EmployeesList=catering_EmployeesList, 
                     Companies=catering_Companies, 
                     Requests=catering_Requests';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'requestId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'num, personId, companyName, menuDetailsId, quantity, price=Цена, tools=Ред';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = "catering_Requests";
    
    
    /**
     * Кой  може да пише?
     */
    var $canWrite = 'catering, admin, user';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'catering, admin, user';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('requestId', 'key(mvc=catering_Requests)', 'caption=Поръчка, input=hidden, silent');
        $this->FLD('menuDetailsId', 'key(mvc=catering_MenuDetails)', 'caption=Избор, notSorting');
        $this->FLD('personId', 'key(mvc=catering_EmployeesList )', 'caption=Служител, mandatory');
        $this->FLD('quantity', 'int', 'caption=Брой, notSorting');
        $this->FNC('companyName', 'key(mvc=catering_Companies)', 'caption=Фирма, notSorting');
        $this->FNC('num', 'int', 'caption=No, notSorting');
        $this->FNC('price', 'double(decimals=2)', 'caption=Цена, notSorting');
        
        $this->setDbUnique('requestId, personId, menuDetailsId');
    }
    
    
    /**
     * Преди извличане на записите от БД
     * Ако няма права admin,catering се показват заявките само за потребителя
     * Ако има права admin,catering записите се сортират първо по $personId
     * Ако заявката е със state=closed се скрива колоната за редакция на записите
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        // Check current user roles
        if (!haveRole('admin,catering')) {
            $personId = $mvc->EmployeesList->getPersonIdForCurrentUser();
            
            // Filter by $personId
            $data->query->where("#personId = '{$personId}'");
            $data->query->orderBy('#createdOn', 'DESC');
        } else {
            // Order by 'person_id', 'crated_on'
            $data->query->orderBy('#personId');
            $data->query->orderBy('#createdOn', 'DESC');
        }
        
        // Проверка за state на заявката
        $requestId = $data->masterId;
        $state = catering_Requests::fetchField("#id = {$requestId}", 'state');
        
        if ($state == 'closed') {
            unset($data->listFields['tools']);
        }
    }
    
    
    /**
     * Подготовка на формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        // Prepare $personId
        if (!haveRole('admin,catering')) {
            $personId = $mvc->EmployeesList->getPersonIdForCurrentUser();
            
            // set form title
            $personName = $mvc->EmployeesList->getPersonNameForCurrentUser();
            $data->form->title = "Добавяне на запис в \"Детайли на поръчка\" за служител|* " . $personName;
            
            // set hidden 'personId'
            // $data->form->setHidden('personId', $personId);
            
            // Само една опция за името
            $selectOptEmployeesList[$personId] = $personName;
        } else {
            $queryEmployeesList = $mvc->EmployeesList->getQuery();
            
            while($recEmployeesList = $queryEmployeesList->fetch("1=1")) {
                $selectOptEmployeesList[$recEmployeesList->id] = crm_Persons::fetchField($recEmployeesList->personId, 'name');
            }
        }
        
        $data->form->setOptions('personId', $selectOptEmployeesList);
        
        // END Prepare $personId
        
        $recRequest = $mvc->Requests->fetch($data->form->rec->requestId);
        
        $selectedDate = $recRequest->date;
        $selectedWeekDay = $mvc->Menu->getRepeatDay($recRequest->date);
        
        // Prepare $menuArr
        $queryMenu = $mvc->Menu->getQuery();
        $where = "#date = '{$selectedDate}'
                  OR (#date IS NULL AND #repeatDay ='{$selectedWeekDay}'
                  OR (#date IS NULL AND #repeatDay = '99.AllDays'))";
        
        // Сортираме по фирма, по 'repeatDay'
        $queryMenu->orderBy('companyId', 'ASC');
        $queryMenu->orderBy('repeatDay', 'ASC');
        
        while($rec = $queryMenu->fetch($where)) {
            $menuArr[$rec->id] = $rec;
        }
        
        // END Prepare $menuArr
        
        $menuArr = (array) $menuArr;
        
        // Prepare $menuDetailsArr
        foreach($menuArr as $k => $v) {
            $queryMenuDetails = $mvc->MenuDetails->getQuery();
            
            while($rec = $queryMenuDetails->fetch("#menuId = {$k}")) {
                $menuDetailsArr[$rec->id]->companyId = $mvc->Menu->fetchField("#id = {$k}", 'companyId');
                $menuDetailsArr[$rec->id]->companyIdCrmCompanies = catering_Companies::fetchField("#id = {$menuDetailsArr[$rec->id]->companyId}", 'companyId');
                $menuDetailsArr[$rec->id]->companyName = crm_Companies::fetchField("#id = {$menuDetailsArr[$rec->id]->companyIdCrmCompanies}", 'name');
                
                $menuDetailsArr[$rec->id]->food = $rec->food;
                $menuDetailsArr[$rec->id]->price = $rec->price;
                
                $selectOptFood[$rec->id] = "Фирма: \"" . $menuDetailsArr[$rec->id]->companyName . "\"- " .
                $menuDetailsArr[$rec->id]->food . " - Цена: "
                . number_format($menuDetailsArr[$rec->id]->price, 2, '.', ' ') . " лв";
            }
        }
        
        // END Prepare $menuDetailsArr
        
        // Prepare quantity
        $data->form->setOptions('menuDetailsId', $selectOptFood);
        
        $selectOptQuantity = array('1' => '1 бр.',
            '2' => '2 бр.',
            '3' => '3 бр.',
            '4' => '4 бр.',
            '5' => '5 бр.');
        
        $data->form->setOptions('quantity', $selectOptQuantity);
        $data->form->setDefault('quantity', '1');
    }
    
    
    /**
     * Подготовка на визуализацията в таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Prpare 'No'
        static $num;
        
        // Prepare $num and $personId
        static $lastPersonId;
        
        $personName = crm_Persons::fetchField($rec->personId, 'name');
        
        if ($lastPersonId == $rec->personId) {
            $row->personId = "<div style='color: #777777;'>" . $personName . "</div>";
            $num += 1;
        } else {
            $row->personId = $personName;
            $num = 1;
        }
        
        $row->num = $num;
        $lastPersonId = $rec->personId;
        
        // END Prepare $num and $personId
        
        
        // Prepare 'Фирма'
        $menuId = $mvc->MenuDetails->fetchField($rec->menuDetailsId, 'menuId');
        $companyId = $mvc->Menu->fetchField($menuId, 'companyId');
        $companyIdCrmCompanies = catering_Companies::fetchField($companyId, 'companyId');
        $row->companyName = catering_Companies::fetchField($companyIdCrmCompanies, 'name');
        
        // Prepare 'Избор'
        $row->menuDetailsId = $mvc->MenuDetails->fetchField($rec->menuDetailsId, 'food');
        
        // Prepare 'Цена'
        $priceForOne = $mvc->MenuDetails->fetchField($rec->menuDetailsId, 'price');
        $row->price = "<div style='text-align: right; width: 70px;'>" . number_format($priceForOne * $rec->quantity, 2, '.', ' ') . " лв</div>";
    }
    
    
    /**
     * Запис в Requests на 'totalPrice'
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param stdClass $rec
     */
    static function on_AfterSave($mvc, $id, $rec)
    {
        $mvc->Requests->calcTotal($rec->requestId);
    }
    
    
    /**
     * Преди изтриване на запис
     */
    static function on_BeforeDelete($mvc, &$numRows, $query, $cond)
    {
        $queryTmp = clone ($query);
        
        while ($recTmp = $queryTmp ->fetch($cond)) {
            $query->masterIdList[$recTmp->requestId] = TRUE;
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterDelete($mvc, &$numRows, $query, $cond)
    {
        foreach($query->masterIdList as $id => $dummy) {
            $mvc->Requests->calcTotal($id);
        }
    }
    
    
    /**
     * Премахване на бутона за добавяне, ако state-а е closed
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        // Проверка за state на заявката
        $requestId = $data->masterId;
        $state = catering_Requests::fetchField("#id = {$requestId}", 'state');
        
        if ($state == 'closed') {
            $data->toolbar->removeBtn('btnAdd');
        }
    }
}