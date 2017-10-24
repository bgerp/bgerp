<?php



/**
 * Палети
 *
 *
 * @category  bgerp
 * @package   pallet
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pallet_Pallets extends core_Manager
{
    
    
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'store_Pallets';
	
	
    /**
     * Заглавие
     */
    var $title = 'Палети';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, pallet_Wrapper, plg_State, plg_LastUsedKeys';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    var $lastUsedKeys = 'storeId';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,pallet';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,pallet';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,pallet';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,pallet';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,pallet';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,pallet';
    
    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 50;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id=Палет, tools=Пулт, productId, quantity, dimensions, 
                       positionView=Позиция, move=Състояние';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'pallet_PalletDetails';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('label', 'varchar(64)', 'caption=Етикет');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Място->Склад,input=hidden');
        $this->FLD('productId', 'key(mvc=store_Products, select=productId)', 'caption=Продукт,silent');
        $this->FLD('quantity', 'int', 'caption=Количество');
        $this->FLD('comment', 'varchar', 'caption=Коментар');
        $this->FLD('dimensions', 'key(mvc=pallet_PalletTypes,select=title)', 'caption=Габарити');
        $this->FLD('state', 'enum(pending,waiting=Чакащ движение,
                                          active=Работи се, 
                                          closed=На място)', 'caption=Състояние');
        $this->FLD('position', 'varchar(16)', 'caption=Позиция->Текуща');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     * Забранява изтриването за записи, които не са със state 'closed'
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($rec->id && ($action == 'delete')) {
            $rec = $mvc->fetch($rec->id);
            
            if ($rec->state != 'closed') {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Смяна на заглавието
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListTitle($mvc, $data)
    {
        $selectedStoreName = store_Stores::getHyperlink(store_Stores::getCurrent(), TRUE);
    	$data->title = "|Палети в склад|* <b style='color:green'>{$selectedStoreName}</b>";
    }
    
    
    /**
     * Филтър
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->title = 'Търсене на палет в склада';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->FNC('productIdFilter', 'key(mvc=store_Products, select=productId, allowEmpty=true)', 'caption=Продукт');
        
        $data->listFilter->showFields = 'productIdFilter';
        
        // Активиране на филтъра
        $recFilter = $data->listFilter->input();
        
        // Ако филтъра е активиран
        if ($data->listFilter->isSubmitted()) {
            if ($recFilter->productIdFilter) {
                $cond = "#productId = {$recFilter->productIdFilter}";
                $data->query->where($cond);
            }
        }
        
        $selectedStoreId = store_Stores::getCurrent();
        $data->query->where("#storeId = {$selectedStoreId}");
        $data->query->orderBy('state');
    }
    
    
    /**
     * positionView и move - различни варианти в зависимост от position, positionNew и state
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Дефинираме иконките, които ще използваме
        $imgUp = ht::createElement('img', array('src' => sbf('img/up.gif', ''), 'width' => '16px', 'height' => '16px',
                'style' => 'float: right; margin-left: 5px;'));
        $imgDown = ht::createElement('img', array('src' => sbf('img/down.gif', ''), 'width' => '16px', 'height' => '16px',
                'style' => 'float: right; margin-left: 5px;'));
        $imgMove = ht::createElement('img', array('src' => sbf('img/move.gif', ''), 'width' => '16px', 'height' => '16px',
                'style' => 'float: right; margin-left: 5px;'));
        $imgEdit = ht::createElement('img', array('src' => sbf('img/edit.png', ''), 'width' => '16px', 'height' => '16px',
                'style' => 'float: right; margin-left: 5px;'));
        $imgDel = ht::createElement('img', array('src' => sbf('img/16/delete.png', ''), 'width' => '16px', 'height' => '16px',
                'style' => 'float: right; margin-left: 5px;
                                                                                                                      margin-top: 2px '));
        
        // ENDOF Дефинираме иконките, които ще използваме
        
        // id и label
        if ($rec->label != '') {
            $row->id = $rec->label;
        } else {
            $row->id = '#' . $row->id;
        }
        
        // comment
        if ($rec->comment != '') {
            $row->id .= '<p style=\'clear: left; max-width: 120px; color: #555555; font-size: 12px;\'>' . $rec->comment . '</p>';
        }
        
        // Ако state е 'closed' и позицията е 'На пода'
        if ($rec->state == 'closed' && preg_match("/^Зона:/u", $rec->position)) {
            $row->positionView = $rec->position;
            $row->move = 'На място';
            $row->move .= ht::createLink($imgUp , array('pallet_Movements', 'add', 'palletId' => $rec->id, 'do' => 'palletUp'));
        }
        
        // Ако state е 'closed' и позицията не е 'На пода'
        if ($rec->state == 'closed' && !preg_match("/^Зона:/u", $rec->position)) {
            $ppRackId2RackNumResult = pallet_Racks::ppRackId2RackNum($rec->position);
            $row->position = $ppRackId2RackNumResult['position'];
            unset($ppRackId2RackNumResult);
            
            $row->positionView = $row->position;
            $row->move = 'На място';
            $row->move .= Ht::createLink($imgDown, array('pallet_Movements', 'edit', 'palletId' => $rec->id, 'do' => 'palletDown'));
            $row->move .= " " . Ht::createLink($imgMove, array('pallet_Movements', 'edit', 'palletId' => $rec->id, 'do' => 'palletMove', 'position' => $rec->position));
        }
        
        // Ако state е 'waiting'
        if ($rec->state == 'waiting') {
            $positionNew = pallet_Movements::fetchField("#palletId = {$rec->id}", 'positionNew');
            
            /* if ($positionNew != 'На пода') { */
            if (!preg_match("/^Зона:/u", $positionNew)) {
                $ppRackId2RackNumResult = pallet_Racks::ppRackId2RackNum($positionNew);
                $positionNew = $ppRackId2RackNumResult['position'];
                unset($ppRackId2RackNumResult);
                $row->move = 'Чакащ';
            }
            
            // if ($rec->position != 'На пода') {
            if (!preg_match("/^Зона:/u", $rec->position)) {
                $ppRackId2RackNumResult = pallet_Racks::ppRackId2RackNum($rec->position);
                $row->position = $ppRackId2RackNumResult['position'];
                unset($ppRackId2RackNumResult);
            }
            
            // if ($rec->position == 'На пода') {
            if (preg_match("/^Зона:/u", $rec->position)) {
                // $row->position = 'На пода';
                $row->position = $rec->position;
            }
            
            $row->positionView = $row->position . ' -> ' . $positionNew;
            
            if (preg_match("/^Зона:/u", $rec->position) && preg_match("/^Зона:/u", $positionNew)) {
               $row->positionView = '<b>Нов</b> -> ' . $positionNew;
                $row->move = 'Чакащ';
            }
            
            if (preg_match("/^Зона:/u", $rec->position) && !preg_match("/^Зона:/u", $positionNew)) {
                $row->move = 'Чакащ';
            }
            
            if (!preg_match("/^Зона:/u", $rec->position) && preg_match("/^Зона:/u", $positionNew)) {
                $row->move = 'Чакащ';
            }
            
            if (!preg_match("/^Зона:/u", $rec->position) &&
                !preg_match("/^Зона:/u", $positionNew) &&
                $rec->state == 'closed') {
                $row->move = 'Чакащ';
                $row->move .= " " . Ht::createLink($imgDel, array('pallet_Movements', 'deletePalleteMovement', 'palletId' => $rec->id, 'do' => 'Отмяна на движение'));
                $row->move .= Ht::createLink($imgDown, array('pallet_Movements', 'edit', 'palletId' => $rec->id, 'do' => 'palletDown'));
                $row->move .= " " . Ht::createLink($imgMove, array('pallet_Movements', 'edit', 'palletId' => $rec->id, 'do' => 'palletMove'));
            }
        }
        
        // Ако state e 'active'
        if ($rec->state == 'active') {
            $positionNew = pallet_Movements::fetchField("#palletId = {$rec->id}", 'positionNew');
            
            if (!preg_match("/^Зона:/u", $positionNew)) {
                $ppRackId2RackNumResult = pallet_Racks::ppRackId2RackNum($positionNew);
                $positionNew = $ppRackId2RackNumResult['position'];
                unset($ppRackId2RackNumResult);
            }
            
            if (!preg_match("/^Зона:/u", $rec->position)) {
                $ppRackId2RackNumResult = pallet_Racks::ppRackId2RackNum($rec->position);
                $row->position = $ppRackId2RackNumResult['position'];
                unset($ppRackId2RackNumResult);
            } else {
                $row->position = $rec->position;
            }
            
            if (preg_match("/^Зона:/u", $row->position) && preg_match("/^Зона:/u", $positionNew)) {
                $row->positionView = '<b>Нов</b> -> ' . $positionNew;
            } else {
                $row->positionView = $row->position . ' -> ' . $positionNew;
            }
            
            $row->move = 'Зает';
        }
    }
    
    
    /**
     * Премахва бутона за добавяне
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     * @param stdClass $rec
     */
    protected static function on_AfterPrepareListToolbar($mvc, $data, $rec)
    {
        $data->toolbar->removeBtn('btnAdd');
    }
    
    
    /**
     * При добавяне/редакция на палетите - данни по подразбиране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        expect($productId = Request::get('productId', 'int'));
        $selectedStoreId = store_Stores::getCurrent();
        $form = &$data->form;
        $rec = &$form->rec;
        
        $data->formTitle = "|Добавяне на палети в склад|* '<b>" . store_Stores::getTitleById($selectedStoreId) . "</b>'";
        
        // По подразбиране за нов запис
        if (!$rec->id) {
            $form->setDefault('storeId', $selectedStoreId);
            
            // Брой палети
            $form->FNC('palletsCnt', 'int', 'caption=Брой палети');
            
            // Избор на зона
            $form->FNC('zone', 'varchar(64)', 'caption=Позициониране->В зона');
            
            // Как да се постави палета
            $form->FNC('palletPlaceHowto', 'varchar(64)', 'caption=Позициониране->Движение,hint=Генериране на движение към позиция');
            
            // Подготвя $palletPlaceHowto suggestions
            $palletPlaceHowto = array('' => '', 'Автоматично' => 'Автоматично');
            $form->setSuggestions('palletPlaceHowto', $palletPlaceHowto);
            
            // Подготвя zones suggestions
            $queryZones = pallet_Zones::getQuery();
            $where = "#storeId = {$selectedStoreId}";
            
            while($recZones = $queryZones->fetch($where)) {
                $zones[$recZones->code] = $recZones->comment;
            }
            
            unset($queryZones, $where, $recZones);
            
            $data->form->setOptions('zone', $zones);
            $data->form->setDefault('productId', $productId);
            $data->form->setDefault('palletsCnt', 1);
            $data->form->setDefault('quantity', 1000);
        }
        
        $data->form->showFields = 'label, productId, quantity, palletsCnt, comment, dimensions, zone, palletPlaceHowto';
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$data->form->title = $data->formTitle;
    }
    
    
    /**
     * Извиква се след въвеждането на данните във формата ($form->rec)
     * Прави проверка за количеството (дали има достатъчно от продукта за палетиране) при add
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted() && (!$form->rec->id)) {
            // Проверка за количеството
            $selectedStoreId = store_Stores::getCurrent();
            
            $rec = $form->rec;
            
            if (pallet_Pallets::checkProductQuantity($rec) === FALSE) {
                $form->setError('quantity,palletsCnt', 'Наличното непалетирано количество от този продукт|* 
                                                        <br/>|в склада не е достатъчно за изпълнение|*
                                                        <br/>|на заявената операция');
            }
            
            // Проверка в зависимост от начина на определяне на палет мястото
            switch ($rec->palletPlaceHowto) {
                case "Автоматично" :
                    break;
                    
                    // Палет мястото е въведено ръчно    
                default :
                    $palletPlace = cls::get('store_type_PalletPlace');
                $rec->palletPlaceHowto = $palletPlace->fromVerbal($rec->palletPlaceHowto);
                
                if ($rec->palletPlaceHowto === FALSE) {
                    $form->setError('palletPlaceHowto', 'Неправилно въведено палет място');
                    break;
                }
                
                $ppRackNum2rackIdResult = pallet_Racks::ppRackNum2rackId($rec->palletPlaceHowto);
                
                if ($ppRackNum2rackIdResult[0] === FALSE) {
                    $form->setError('palletPlaceHowto', 'Няма стелаж с въведения номер');
                    break;
                } else {
                    $rec->palletPlaceHowto = $ppRackNum2rackIdResult['position'];
                }
                
                $rackId = $ppRackNum2rackIdResult['rackId'];
                
                $isSuitableResult = pallet_Racks::isSuitable($rackId, $rec->productId, $rec->palletPlaceHowto);
                
                if ($isSuitableResult[0] === FALSE) {
                    $fErrors = $isSuitableResult[1];
                    pallet_Pallets::prepareErrorsAndWarnings($fErrors, $form);
                }
                
                break;
            }
        }
    }
    
    
    /**
     * При нов запис, ако броя на палетите е повече от 1
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param stdClass $rec
     */
    protected static function on_BeforeSave($mvc, &$id, $rec)
    {
        // При add на нов палет
        if (!$rec->id) {
            // 
            $rec->newRec = TRUE;
            $selectedStoreId = store_Stores::getCurrent();
            
            // Ако ръчно е зададено палет място, броя на палетите автоматично става 1
            if ($rec->palletPlaceHowto != 'Автоматично') {
                $rec->palletsCnt = 1;
            }
            
            // Проверка за количеството
            if (pallet_Pallets::checkProductQuantity($rec) === FALSE) {
                redirect(   array("store_Products"),
                            FALSE,
                            "|Междувременно е палетирано от този продукт " .
                            "и наличното непалетирано количество в склада не е достатъчно " .
                            "за извършването на тази операция"
                    );
            } else {
                // При достатъчно количество за пакетиране
                switch ($rec->palletPlaceHowto) {
                    case 'Автоматично' :
                        $rec->state = 'waiting';
                        $rec->position = 'Зона: ' . $rec->zone;
                        
                        if ($rec->palletsCnt > 1) {
                            for ($i = 0; $i < $rec->palletsCnt; $i++) {
                                $recSave = clone ($rec);
                                $recSave->palletsCnt = 0;
                                
                                pallet_Pallets::save($recSave);
                            }
                            
                            return FALSE;
                        }
                        break;
                    
                    default : // Ръчно въведено палет място или на пода в зона
                    if ($rec->palletPlaceHowto != '' && $rec->palletPlaceHowto != 'Автоматично') {
                        // Ръчно въведено
                        $rec->state = 'waiting';
                        $rec->position = 'Зона: ' . $rec->zone;
                    } else {
                        // На пода в зона
                        $rec->state = 'closed';
                        $rec->position = 'Зона: ' . $rec->zone;
                        
                        if ($rec->palletsCnt > 1) {
                            for ($i = 0; $i < $rec->palletsCnt; $i++) {
                                $recSave = clone ($rec);
                                $recSave->palletsCnt = 0;
                                
                                pallet_Pallets::save($recSave);
                            }
                            
                            return FALSE;
                        }
                    }
                    
                    break;
                }
            }
        }
    }
    
    
    /**
     * Актуализира количествата на продуктите (1) и създава движения (2)
     * 1. Запис в store_Products на актуалните количества след създаването на палета
     * 2. Създава ново движение в pallet_Movements в случай, че палета е 'Автоматично' или 'Ръчно' позициониран
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param stdClass $rec
     */
    protected static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        if ($rec->newRec == TRUE) {
            /* Change product quantity on pallets */
            $recProducts = store_Products::fetch($rec->productId);
            
            $productQuantityOnPallets = pallet_Pallets::calcProductQuantityOnPalletes($rec->productId);
            $recProducts->quantityOnPallets = $productQuantityOnPallets;
            
            store_Products::save($recProducts);
            
            /* ENDOF Change product quantity on pallets */

            $recMovements = new stdClass();
                
            /* Създава движение за нов палет, който е 'Автоматично' позициониран */
            if ($rec->palletPlaceHowto == 'Автоматично') {
                // Взема селектирания склад
                $selectedStoreId = store_Stores::getCurrent();
                
                $palletId = $rec->id;
                
                // Генерира автоматично палет място от стратегията
                $storeRec = store_Stores::fetch($selectedStoreId);
                $strategy = cls::getInterface('pallet_ArrangeStrategyIntf', $storeRec->strategy);
                $palletPlaceAuto = $strategy->getAutoPalletPlace($rec->productId);
                
                // Всички палет места за заети или групата на продукта не е допустима
                if ($palletPlaceAuto == NULL) {
                    $rec->state = 'closed';
                    $rec->newRec = FALSE;
                    pallet_Pallets::save($rec);
                    
                    return;
                }
                
                // $recMovements
                $recMovements->storeId = $selectedStoreId;
                $recMovements->palletId = $palletId;
                $recMovements->positionOld = 'Зона: ' . $rec->zone;
                $recMovements->positionNew = $palletPlaceAuto;
                $recMovements->state = 'waiting';
                
                // Записва движение
                pallet_Movements::save($recMovements);
            }
            
            /* ENDOF Създава движение за нов палет, който е 'Автоматично' позициониран */
            
            /* Създава движение за нов палет, който е 'Ръчно' позициониран */
            /* if ($rec->newRec == TRUE && 
                $rec->palletPlaceHowto != 'Автоматично' && 
                $rec->palletPlaceHowto != 'На пода') { */
            if ($rec->newRec == TRUE &&
                $rec->palletPlaceHowto != 'Автоматично' &&
                !preg_match("/^Зона:/u", $rec->palletPlaceHowto)) {
                // Взема селектирания склад
                $selectedStoreId = store_Stores::getCurrent();
                
                $palletId = $rec->id;
                
                // $recMovements
                $recMovements->storeId = $selectedStoreId;
                $recMovements->palletId = $palletId;
                $recMovements->positionOld = 'Зона: - ';
                $recMovements->positionNew = $rec->palletPlaceHowto;
                $recMovements->state = 'waiting';
                
                // Записва движение
                pallet_Movements::save($recMovements);
                
                redirect(array('pallet_Racks'));
            }
            
            /* ENDOF Създава движение за нов палет, който е 'Автоматично' позициониран */
        }
    }
    
    
    /**
     * Проверка при изтриване дали палета не е в движение
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param $query
     * @param string $cond
     */
    protected static function on_BeforeDelete($mvc, &$res, &$query, $cond)
    {
        $_query = clone($query);
        
        while ($rec = $_query->fetch($cond)) {
            $query->deleteRecId = $rec->id;
            $query->deleteRecProductId = $rec->productId;
        }
    }
    
    
    /**
     * Ако е минала проверката за state в on_BeforeDelete, след като е изтрит записа изтриваме всички движения за него
     *
     * @param core_Mvc $mvc
     * @param int $numRows
     * @param stdClass $query
     * @param string $cond
     * @return core_Redirect
     */
    protected static function on_AfterDelete($mvc, &$numRows, $query, $cond)
    {
        pallet_Movements::delete("#palletId = {$query->deleteRecId}");
        
        // Calc store_Products quantity on pallets
        $recProducts = store_Products::fetch($query->deleteRecProductId);
        
        $productQuantityOnPallets = pallet_Pallets::calcProductQuantityOnPalletes($query->deleteRecProductId);
        $recProducts->quantityOnPallets = $productQuantityOnPallets;
        store_Products::save($recProducts);
        
        return redirect(array($mvc));
    }
    
    
    /**
     * Изчислява количеството от даден продукт на палети
     *
     * @param int $productId
     * @return int $productQuantityOnPallets
     */
    public static function calcProductQuantityOnPalletes($productId) 
    {
        $query = static::getQuery();
        $where = "#productId = {$productId}";
        
        $productQuantityOnPallets = 0;
        
        while($rec = $query->fetch($where)) {
            $productQuantityOnPallets += $rec->quantity;
        }
        
        return $productQuantityOnPallets;
    }
    
    
    /**
     * Проверка преди палетиране дали има достатъчно количество от продукта (непалетирано)
     *
     * @param int $selectedStoreId
     * @param $rec
     * @return boolean
     */
    public static function checkProductQuantity($rec)
    {
        // Взема селектирания склад
        $selectedStoreId = store_Stores::getCurrent();
        
        // Новото количество (общо), кето ще се палетира  
        $quantityOnPalletsAdd = $rec->quantity * $rec->palletsCnt;
        $quantityNotOnPallets = store_Products::fetchField("#id = {$rec->productId} AND #storeId = {$selectedStoreId}", "quantityNotOnPallets");
        
        // END Изчисляване на наличното непалетирано количество
        if ($quantityNotOnPallets < $quantityOnPalletsAdd) {
            return FALSE;
        } else {
            return TRUE;
        }
    }
    
    
    /**
     * Връща масив с палетите в склада - тези които са 'На място' и не са 'На пода'
     * и тези, за които има наредено преместване
     *
     * Елементи на масива:
     * $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['palletId']
     * $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['title']
     * $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['productId']
     * $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['stateMovements']
     */
    public static function getPalletsInStore()
    {
        $selectedStoreId = store_Stores::getCurrent();
        
        $queryPallets = pallet_Pallets::getQuery();
        $where = "#storeId = {$selectedStoreId}";
        
        while($recPallets = $queryPallets->fetch($where)) {
            // Само тези палети, които са 'На място' и не са 'На пода'
            // if ($recPallets->position != 'На пода' && $recPallets->state == 'closed') {
            if (!preg_match("/^Зона:/u", $recPallets->position) && $recPallets->state == 'closed') {
                $positionArr = explode("-", $recPallets->position);
                
                $rackId = $positionArr[0];
                $rackRow = $positionArr[1];
                $rackColumn = $positionArr[2];
                
                $palletPosition = $rackId . "-" . $rackRow . "-" . $rackColumn;
                $palletDimensions = number_format($recPallet->width, 2) . "x" . number_format($recPallets->depth, 2) . "x" . number_format($recPallets->height, 2) . " м, max " . $recPallets->maxWeight . " кг";
                
                $recProduct = store_Products::fetch("#id = {$recPallets->productId}");
                
                /* push to $palletsInStoreArr[$rackId][$rackRow][$rackColumn] */
                $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['palletId'] = $recPallets->id;
                
				$pInfo = cat_Products::getProductInfo($recProduct->productId);
				$measureShortName = cat_UoM::getShortName($pInfo->productRec->measureId);
						
				$title = "Палет: " . $recPallets->label . ", " . $recProduct->name . ", " . $recPallets->quantity . " " . $measureShortName . ", № на продукта в склада: " . $recProduct->id;
                $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['title'] = $title;
                $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['productId'] = $recPallets->productId;
                
                /* ENDOF push to $palletsInStoreArr[$rackId][$rackRow][$rackColumn] */
            }
            
            // Палетите, за които има наредено преместване
            if ($recPallets->state != 'closed') {
                $where = "#storeId = {$selectedStoreId} 
                          AND #palletId = {$recPallets->id}
                          AND #state != 'closed'";
                
                $position = pallet_Movements::fetchField($where, 'positionNew');
                $state = pallet_Movements::fetchField($where, 'state');
                
                $positionArr = explode("-", $position);
                
                $rackId = $positionArr[0];
                $rackRow = $positionArr[1];
                $rackColumn = $positionArr[2];
                
                $palletPosition = $rackId . "-" . $rackRow . "-" . $rackColumn;
                $recProduct = store_Products::fetch("#id = {$recPallets->productId}");
                
                /* push to $palletsInStoreArr[$rackId][$rackRow][$rackColumn] */
                $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['palletId'] = $recPallets->id;
                                
				$pInfo = cat_Products::getProductInfo($recProduct->productId);
				$measureShortName = cat_UoM::getShortName($pInfo->productRec->measureId);
						
				$title = "Палет: " . $recPallets->label . ", " . $recProduct->name . ", " . $recPallets->quantity . " " . $measureShortName . ", № на продукта в склада: " . $recProduct->id;
                $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['title'] = $title;
                $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['productId'] = $recPallets->productId;
                $palletsInStoreArr[$rackId][$rackRow][$rackColumn]['stateMovements'] = $state;
                
                /* ENDOF push to $palletsInStoreArr[$rackId][$rackRow][$rackColumn] */
            }
        }
        
        return $palletsInStoreArr;
    }
    
    
    /**
     * Проверява дали дадено палет място е заето или дали има наредено движение към него
     *
     * @param string $position
     * @return boolean
     */
    public static function checkIfPalletPlaceIsFree($position)
    {
        $selectedStoreId = store_Stores::getCurrent();
        
        $palletPlaceCheckPallets = pallet_Pallets::fetch("#position = '{$position}' 
                                                           AND #storeId = {$selectedStoreId}");
        
        $palletPlaceCheckMovements = pallet_Movements::fetch("#positionNew = '{$position}' 
                                                             AND #state != 'closed'
                                                             AND #storeId = {$selectedStoreId}");
        
        if ($palletPlaceCheckPallets || $palletPlaceCheckMovements) {
            
            return FALSE;
        } else return TRUE;
    }
    
    
    /**
     * Подготвя предупреждения и грешки
     *
     * @param array $fErrors
     */
    public static function prepareErrorsAndWarnings($fErrors, $form)
    {
        $countErrors = 0;
        $countWarnings = 0;
        
        foreach($fErrors as $v) {
            /* Подготовка на setError() */
            if ($v[0] == 'PPNE' ||
                $v[0] == 'PPNF' ||
                $v[0] == 'PPOOFU') {
                $countErrors += 1;
                
                if ($countErrors == 1) {
                    $errorReasons = '|*<b>|' . $v[1] . '|*</b>';
                } else {
                    $errorReasons .= '<br/><b>|' . $v[1] . '|*</b>';
                }
            }
            
            /* ENDOF Подготовка на setError() */
            
            /* Подготовка на setWarning() */
            if ($v[0] == 'PGNA' ||
                $v[0] == 'PPR') {
                $countWarnings += 1;
                
                if ($countWarnings == 1) {
                    $warningReasons = '|*<b>|' . $v[1] . '|*</b>';
                } else {
                    $warningReasons .= '<br/><b>|' . $v[1] . '|*</b>';
                }
            }
            
            /* ENDOF Подготовка на setWarning() */
        }
        
        // Ако има грешки
        if ($countErrors) {
            $form->setError('palletPlaceHowto', $errorReasons);
        }
        
        // Ако има предупреждения
        if ($countWarnings) {
            $form->setWarning('palletPlaceHowto', $warningReasons);
        }
    }
}
