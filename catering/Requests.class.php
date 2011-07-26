<?php

/**
 * Заявки на столуващи хора
 */
class catering_Requests extends core_Master
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Заявки за кетаринг";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, plg_Created, catering_Wrapper, plg_State2,
                             RequestDetails=catering_RequestDetails,
                             MenuDetails=catering_MenuDetails,
                             EmployeesList=catering_EmployeesList,
                             Orders=catering_Orders';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'dateState, date, totalPrice, tools=Пулт, makeOrder=Поръчка';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $details = array('catering_RequestDetails');
    
    
    /**
     * Права
     */
    var $canWrite = 'catering, admin, user';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'catering, admin, user';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('date', 'date', 'caption=Дата,  notNull, mandatory');
        $this->FLD('totalPrice', 'double(decimals=2)', 'caption=Сума за деня, input=none');
        $this->FNC('dateState', 'varchar(255)', 'caption=Статус');
        $this->FNC('makeOrder', 'varchar(255)');
        
        $this->setDbUnique('date');
    }
    
    
    /**
     * Подготвя титлата в единичния изглед
     *
     * @param stdClass $data
     * @return stdClass $data
     */
    function prepareSingleTitle_($data)
    {
        $data->title = "Заявка за храна<br/>№ " . $data->rec->id . " / Дата: " . $data->row->date;
        
        return $data;
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
            $res = new ET('');
            
            return FALSE;
        }
    }
    
    
    /**
     * Заглавието на формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $data->form->title = "Добавяне на ден за столуване";
        
        $data->form->setHidden('state', 'active');
    }
    
    
    /**
     * При нов запис, който още няма детайли показваме полето 'Сума' да е 0.00
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Ако потребитела не е 'admin' или 'catering' ще вижда сумата само на неговите поръчки за деня 
        if (!haveRole('admin,catering')) {
            $personId = $mvc->EmployeesList->getPersonIdForCurrentUser();
            
            $queryRequestDetails = $mvc->RequestDetails->getQuery();
            $where = "#requestId = {$rec->id} AND #personId = {$personId}";
            
            $totalPriceForPersonForOneDay = 0;
            
            while($recRequestDetails = $queryRequestDetails->fetch($where)) {
                $priceForOne = $this->MenuDetails->fetchField($recRequestDetails->menuDetailsId, 'price');
                $priceAdd = $priceForOne * $recRequestDetails->quantity;
                $totalPriceForPersonForOneDay += $priceAdd;
            }
            
            // Форматираме изгледа за 'totalPrice' в таблицата
            if ($rec->totalPrice == '') {
                $row->totalPrice = number_format(0, 2, '.', ' ') . " лв";
            } else {
                $row->totalPrice = number_format($totalPriceForPersonForOneDay, 2, '.', ' ') . " лв";
            }
            // ENDOF Форматираме изгледа за 'totalPrice' в таблицата            
        }
        // END Ако потребитела не е 'admin' или 'catering' ще вижда сумата само на неговите поръчки за деня
        
        // Ако потребитела е 'admin' или 'catering' показваме сумата от всички заявки за деня
        if (haveRole('admin,catering')) {
            // Форматираме изгледа за 'totalPrice' в таблицата
            if ($rec->totalPrice == '') {
                $row->totalPrice = number_format(0, 2, '.', ' ') . " лв";
            } else {
                $row->totalPrice = number_format($rec->totalPrice, 2, '.', ' ') . " лв";
            }
            // ENDOF Форматираме изгледа за 'totalPrice' в таблицата            
        }
        // END Ако потребитела е 'admin' или 'catering' показваме сумата от всички заявки за деня
        
        // Проверка за 'dateState'
        $dateTodayFull = dt::timestamp2Mysql(time());
        $dateTomorrow = substr(dt::addDays(1, $dateTodayFull), 0, 10);
        $dateToday = substr(dt::timestamp2Mysql(time()), 0, 10);
        
        if ($rec->date < $dateToday) {
            $row->dateState = " Стара";
        } elseif ($rec->date == $dateToday) {
            $row->dateState = " <b>ЗА ДНЕС</b>";
            $row->date = "<b>" . $row->date . "</b>";
        } elseif ($rec->date == $dateTomorrow) {
            $row->dateState = " <b>За утре</b>";
        } else {
            $row->dateState = "Предстояща";
        }
        // END Проверка за 'dateState'
        
        // Логика за редактиране в зависимост от 'dateState'
        if ($row->dateState == " Стара") {
            // ,,,
        }
        // END Логика за редактиране в зависимост от 'dateState'
        
        // Prepare 'makeOrder'
        if ($orderId = $mvc->Orders->fetchField("#requestId = {$rec->id}", 'id')) {
            $row->makeOrder = Ht::createLink('Разгледай', array('catering_Orders', 'list', 'date' => $row->date));
        } else {
            if ($rec->state == 'closed') {
                $row->makeOrder = Ht::createLink('Създай', array('catering_Orders', 'makeOrder', $rec->id));
            } else {
                $row->makeOrder = "Заявката не е приключена";
            }
        }
    }
    
    
    /**
     * Изчислява сумата на всички поръчки (details) за дадена дата
     *
     * @param int $requestId
     */
    function calcTotal($requestId)
    {
        $queryRequestDetails = $this->RequestDetails->getQuery();
        $where = "#requestId = {$requestId}";
        
        $totalPrice = 0;
        
        while($rec = $queryRequestDetails->fetch($where)) {
            $priceForOne = $this->MenuDetails->fetchField($rec->menuDetailsId, 'price');
            $priceAdd = $priceForOne * $rec->quantity;
            $totalPrice += $priceAdd;
        }
        
        $recRequests->id = $requestId;
        $recRequests->totalPrice = $totalPrice;
        $this->save($recRequests);
    }
    
    
    /**
     * Преди извличане на записите от БД сортираме по date
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('#date', 'DESC');
        
        if (!haveRole('admin,catering')) {
            unset($data->listFields['makeOrder']);
        }
    }
    
    
    /**
     * Смяна статута на 'active'
     *
     */
    function act_ActivateRequest()
    {
        $id = Request::get('id', 'int');
        
        $recForActivation = new stdClass;
        
        $recForActivation->id = $id;
        $recForActivation->state = "active";
        
        $this->save($recForActivation);
        
        return new Redirect(array($this, 'single', $id));
    }
    
    
    /**
     * Смяна статута на 'closed'
     *
     */
    function act_DeactivateRequest()
    {
        $id = Request::get('id', 'int');
        
        $recForDeactivation = new stdClass;
        
        $recForDeactivation->id = $id;
        $recForDeactivation->state = "closed";
        
        $this->save($recForDeactivation);
        
        return new Redirect(array($this, 'single', $id));
    }
    
    
    /**
     * Подготвя шаблона за единичния изглед
     *
     * @param stdClass $data
     */
    function renderSingleLayout_($data)
    {
        $data->toolbar->removeBtn('btnEdit');
        
        $data->toolbar->addBtn('Назад', array('Ctr' => $this,
            'Act' => 'list',
            'ret_url' => TRUE));
        
        if ($data->rec->state == 'active') {
            $data->toolbar->addBtn('Приключи', array('Ctr' => $this,
                'Act' => 'deactivateRequest',
                'id' => $data->rec->id,
                'ret_url' => TRUE));
        }
        
        if ($data->rec->state == 'closed') {
            // Само, ако няма създадени поръчки за този $requestId 
            if (!$recOrder = $this->Orders->fetch("#requestId = {$data->rec->id}")) {
                $data->toolbar->addBtn('Активирай за корекция', array('Ctr' => $this,
                    'Act' => 'activateRequest',
                    'id' => $data->rec->id,
                    'ret_url' => TRUE));
            }
        }
        
        if( count($this->details) ) {
            foreach($this->details as $var => $className) {
                $detailsTpl .= "[#Detail{$var}#]";
            }
        }
        
        return new ET("[#SingleToolbar#]<h2>[#SingleTitle#]</h2>{$detailsTpl}");
    }
    
    
    /**
     * Премахване на бутона за добавяне, ако потребитела не е admin,catering
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareListToolbar($mvc, $res, $data)
    {
        if (!haveRole('admin,catering')) {
            $data->toolbar->removeBtn('btnAdd');
        }
    }
    
    
    /**
     * Ако state е closed, то не можем да редактираме и и зтриваме
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    /*
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($rec->id && ($action == 'delete' || $action == 'edit')  ) {
            $rec = $mvc->fetch($rec->id);
            
            if ($rec->state == 'closed') {
                $requiredRoles = 'no_one';
            }
        }
    }
    */

} 