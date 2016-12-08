<?php



/**
 * Поръчки на храна
 *
 *
 * @category  bgerp
 * @package   catering
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class catering_Orders extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Поръчки за храна";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools2, plg_Created, catering_Wrapper, plg_Printing,
                             Menu=catering_Menu,    
                             Requests=catering_Requests,
                             RequestDetails=catering_RequestDetails,
                             MenuDetails=catering_MenuDetails,
                             EmployeesList=catering_EmployeesList,
                             Companies=catering_Companies,
                             CrmCompanies=crm_Companies';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'requestId, companyId';

    
    /**
     * Права
     */
    var $canWrite = 'no_one';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'catering, ceo';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('requestId', 'key(mvc=catering_Requests, select=date)', 'caption=За дата, input=none');
        $this->FLD('date', 'date', 'caption=Дата, input=none');
        $this->FLD('companyId', 'key(mvc=catering_Companies, select=companyId)', 'caption=Фирма, input=none');
        
        $this->setDbUnique('requestId, date, companyId');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_MakeOrder()
    {
        $requestId = Request::get('id', 'int');
        $date = $this->Requests->fetchField($requestId, 'date');
        
        // Prepare $companiesArr
        $queryCompanies = $this->Companies->getQuery();
        $where = "1=1";
        
        while($recCompanies = $queryCompanies->fetch($where)) {
            $companiesArr[$recCompanies->id] = $this->CrmCompanies->fetchField("#id = {$recCompanies->companyId}", 'name');
        }
        
        // END Prepare $companiesArr
        
        // За всяка компания правим запис
        foreach($companiesArr as $companyId => $companyName) {
            $recNew = new stdClass;
            $recNew->requestId = $requestId;
            $recNew->date = $date;
            $recNew->companyId = $companyId;
            
            $this->save($recNew);
            
            unset($recNew);
        }
        
        return new Redirect(array('catering_Orders', 'list'));
    }
    
    
    /**
     * Преди извличане на записите от БД филтър по date
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        if ($date = substr(Request::get('date', 'date'), 0, 10)) {
            $data->query->where("#date = '{$date}'");
        } else {
            $data->query->orderBy('#date', 'DESC');
        }
    }
    
    
    /**
     * Подготовка на данните за таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        // Array to store table data in
        $orderDetails = array();
        
        // Some params for the order
        $orderId = $data->rec->id;
        $companyId = $data->rec->companyId;
        $requestId = $data->rec->requestId;
        
        $queryRequestDetails = $mvc->RequestDetails->getQuery();
        $where = "#requestId = {$requestId}";
        
        // За всеки запис от RequestDetails, който отговаря на избрания $requestId
        while($recRequestDetails = $queryRequestDetails->fetch($where)) {
            // Get $menuId for the current rec
            $menuIdForCurrentRec = $mvc->MenuDetails->fetchField("#id = {$recRequestDetails->menuDetailsId}", 'menuId');
            
            // Get $companyId for the current rec
            $companyIdForCurrentRec = $mvc->Menu->fetchField("#id = {$menuIdForCurrentRec}", 'companyId');
            
            // Само, ако записа в RequestDetails е за избраната фирма
            if ($companyIdForCurrentRec == $companyId) {
                // Ако в масива $orderDetails няма ключ $menuDetailsId го създаваме. Добавяме 'food' и 'priceForOne'.
                if (!array_key_exists($recRequestDetails->menuDetailsId, $orderDetails)) {
                    // food
                    $food = $mvc->MenuDetails->fetchField($recRequestDetails->menuDetailsId, 'food');
                    $orderDetails[$recRequestDetails->menuDetailsId]['food'] = $food;
                    
                    // price for one
                    $priceForOne = $mvc->MenuDetails->fetchField($recRequestDetails->menuDetailsId, 'price');
                    $orderDetails[$recRequestDetails->menuDetailsId]['priceForOne'] = $priceForOne;
                }
                
                // quantity - calculate
                $orderDetails[$recRequestDetails->menuDetailsId]['quantity'] += $recRequestDetails->quantity;
                
                // price sum for one food article - calculate
                $priceForOne = $orderDetails[$recRequestDetails->menuDetailsId]['priceForOne'];
                $orderDetails[$recRequestDetails->menuDetailsId]['priceSum'] += $priceForOne * $recRequestDetails->quantity;
                unset($priceForOne);
            }
            
            // END Само ако записа в RequestDetails е за избраната фирма
        }
        
        // END За всеки запис от RequestDetails, който отговаря на избрания $requestId
        
        $counter = 0;
        $priceTotalSum = 0;
        $tableRow = array();
        $tableData = array();
        
        foreach ($orderDetails as $k => $v) {
            $counter++;
            $tableRow['counter'] = $counter;
            $tableRow['food'] = $v['food'];
            $tableRow['priceForOne'] = "<div style='float:right;'>" . number_format($v['priceForOne'], 2, ',', ' ') . " лв</div>";
            $tableRow['quantity'] = "<div style='float:right;'>" . $v['quantity'] . " бр.</div>";
            $tableRow['priceSum'] = "<div style='float:right;'>" . number_format($v['priceSum'], 2, ',', ' ') . " лв</div>";
            
            $priceTotalSum += $v['priceSum'];
            $tableData[] = $tableRow;
        }
        
        // END Prepare table data
        
        $data->tableData = $tableData;
        $data->priceTotalSum = $priceTotalSum;
    }
    
    
    /**
     * Render single
     *
     * @param core_Mvc $mvc
     * @param core_Et $tpl
     * @param stdClass $data
     */
    static function on_BeforeRenderSingle($mvc, &$tpl, $data)
    {
        // Some params for the order
        $orderId = $data->rec->id;
        $companyId = $data->rec->companyId;
        
        // Prepare table data
        $orderDate = dt::mysql2verbal($mvc->fetchField($orderId, 'date'), 'd-m-Y');
        $orderCompanyContatcId = $mvc->Companies->fetchField($companyId, 'companyId');
        $orderCompanyName = $mvc->CrmCompanies->fetchField($orderCompanyContatcId, 'name');
        
        // Prepare render table
        $table = cls::get('core_TableView', array('mvc' => $mvc));
        
        $data->listFields = arr::make($data->listFields, TRUE);
        
        $tpl = $table->get($data->tableData, "counter=№, 
                                              food=Избор,
                                              priceForOne=Цена за 1 бр.,
                                              quantity=Количество,
                                              priceSum=Сума");
        
        $tpl->prepend("<div><b>Поръчка за храна</b>
                       <br/>№ " . $orderId . " / Дата: " . $orderDate . " към фирма \"" . $orderCompanyName . "\"</div><br/>");
        
        if ($data->priceTotalSum) {
            $tpl->append("<br/>
                          <div style='float: left; border: solid 1px #999999; padding: 3px; width: 300px;'>
                             <p style='float: left; width: 150px; margin: 0;'>Обща сума: </p>
                             <p style='float: left; width: 150px; margin: 0; text-align: right;'>
                                " . number_format($data->priceTotalSum, 2, ',', ' ') . " лв
                             </p>
                          </div>
                          <div style='clear: both;'></div>");
        }
        
        $tpl->append("<br/>");
        
        // ENDOF Prepare render table
        
        $data->toolbar->addBtn('Назад', array('Ctr' => $this,
                'Act' => 'list',
                'ret_url' => TRUE));
        
        // Поставяме toolbar-а
        // $tpl->append($mvc->renderSingleToolbar($data), 'SingleToolbar');
        $tpl .= $mvc->renderSingleToolbar($data);
        
        // Break render
        return FALSE;
    }
    
    
    /**
     * Сменяме заглавието
     *
     * @param stdClass $data
     */
    static function on_BeforePrepareListTitle($mvc, $data)
    {
        if ($date = Request::get('date')) {
            $data->title = "Поръчки за дата " . $date;
        }
    }
}