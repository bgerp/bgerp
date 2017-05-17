<?php



/**
 * Движения в палетния склад
 *
 *
 * @category  bgerp
 * @package   pallet
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pallet_Movements extends core_Manager
{
    
    
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'store_Movements';
	
	
    /**
     * Заглавие
     */
    var $title = 'Движения';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools2, plg_Created, pallet_Wrapper, plg_RefreshRows, plg_State';
    
    
    /**
     * Време за опресняване информацията при лист
     */
    var $refreshRowsTime = 10000;
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,pallet';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,pallet';
    
    
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
    var $listFields = 'id,palletId, positionView=Местене, workerId, state';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('storeId', 'key(mvc=store_Stores, select=name)', 'caption=Склад');
        $this->FLD('palletId', 'key(mvc=pallet_Pallets, select=id)', 'caption=Палет,input=hidden');
        
        $this->FLD('positionOld', 'varchar(32)', 'caption=Палет място->Старо');
        $this->FNC('position', 'varchar(32)', 'caption=Палет място->Текущо');
        $this->FLD('positionNew', 'varchar(32)', 'caption=Палет място->Ново');
        
        $this->FLD('state', 'enum(pending, active, closed, waiting)', 'caption=Състояние, input=hidden');
        $this->FLD('workerId', 'key(mvc=core_Users, select=names)', 'caption=Товарач');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
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
        
        if ($rec->id && ($action == 'edit')) {
            if ($do = Request::get('do')) {
                if ($do == 'palletMove') {
                    $requiredRoles = 'pallet,ceo';
                }
            } else {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'add') {
            if ($do = Request::get('do')) {
                if ($do == 'palletMove') {
                    $requiredRoles = 'pallet,ceo';
                }
            } else {
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
    	$data->title = "|Движения в склад|* <b style='color:green'>{$selectedStoreName}</b>";
    }
    
    
    /**
     * В зависимост от state-а
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // $row->state
        switch($rec->state) {
            case 'waiting' :
                $row->state = Ht::createBtn('Вземи', array($mvc, 'setPalletActive', $rec->id));
                $row->state .= Ht::createBtn('Отказ', array($mvc, 'denyPalletMovement', $rec->id));
                break;
            
            case 'closed' :
                $row->state = 'На място';
                break;
            
            case 'active' :
                $userId = Users::getCurrent();
                
                if ($userId == $rec->workerId) {
                    $row->state = Ht::createBtn('Приключи', array($mvc, 'setPalletClosed', $rec->id));
                } else {
                    $row->state = 'Зает';
                }
                break;
        }
        
        // $row->positionView
        $position = pallet_Pallets::fetchField("#id = {$rec->palletId}", 'position');
        
        // if ($position != 'На пода') {
        if (!preg_match("/^Зона:/u", $position)) {
            $ppRackId2RackNumResult = pallet_Racks::ppRackId2RackNum($position);
            $position = $ppRackId2RackNumResult['position'];
            unset($ppRackId2RackNumResult);
        }
        
        // if ($rec->positionNew != 'На пода') {
        if (!preg_match("/^Зона:/u", $rec->positionNew)) {
            $ppRackId2RackNumResult = pallet_Racks::ppRackId2RackNum($rec->positionNew);
            $row->positionNew = $ppRackId2RackNumResult['position'];
            unset($ppRackId2RackNumResult);
        } else {
            // $row->positionNew = 'На пода';
            $row->positionNew = $rec->positionNew;
        }
        
        // if ($rec->positionOld != 'На пода' && $rec->positionOld != NULL) {
        if (!preg_match("/^Зона:/u", $rec->positionOld) && $rec->positionOld != NULL) {
            $ppRackId2RackNumResult = pallet_Racks::ppRackId2RackNum($rec->positionOld);
            $row->positionOld = $ppRackId2RackNumResult['position'];
            unset($ppRackId2RackNumResult);
        } else {
            // $row->positionOld = 'На пода';
            $row->positionOld = $rec->positionOld;
        }
        
        if ($rec->state == 'waiting' || $rec->state == 'active') {
            $row->positionView = $position . " -> " . $row->positionNew;
        } else {
            $row->positionView = $row->positionOld . " -> " . $row->positionNew;
        }
        
        // ENDOF $row->positionView
    }
    
    
    /**
     * При редакция
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = $data->form;
        
        $palletId = Request::get('palletId', 'int');
        $productId = pallet_Pallets::fetchField($palletId, 'productId');
        
        // Взема селектирания склад
        $selectedStoreId = store_Stores::getCurrent();
        
        $do = Request::get('do');
        
        switch ($do) {
            case 'palletUp' :
                $data->formTitle = "|КАЧВАНЕ|* <b>|от пода|*</b> |на палет с|* ID=<b>{$palletId}</b>";
                $form->FNC('do', 'varchar(64)', 'caption=Движение,input=hidden');
                
                // Как да се постави палета
                $form->FNC('palletPlaceHowto', 'varchar(64)', 'caption=Позициониране');
                $form->FNC('completed', 'set(YES=Да)', 'caption=Приключено');
                
                $palletPlaceHowto = array('' => '',
                    'Автоматично' => 'Автоматично');
                
                $form->setSuggestions('palletPlaceHowto', $palletPlaceHowto);
                
                $form->showFields = 'palletPlaceHowto,completed';
                
                $form->setHidden('palletId', $palletId);
                $form->setHidden('state', 'waiting');
                
                // Действие
                $form->setHidden('do', 'palletUp');
                break;
            
            case 'palletDown' :
                $position = pallet_Pallets::fetchField("#id = {$palletId}", 'position');
                
                $ppRackId2RackNumResult = pallet_Racks::ppRackId2RackNum($position);
                $position = $ppRackId2RackNumResult['position'];
                unset($ppRackId2RackNumResult);
                
                $data->formTitle = "СВАЛЯНЕ |*<b>|на пода|*</b>| на палет с|* ID=<b>{$palletId}</b>
                                <br/>|от пoзиция|* <b>{$position}</b>";
                $form->FNC('do', 'varchar(64)', 'caption=Движение,input=hidden');
                $form->FNC('completed', 'set(YES=Да)', 'caption=Приключено');
                
                $form->showFields = 'zone, completed';
                
                // Избор на зона                
                $queryZones = pallet_Zones::getQuery();
                $where = "#storeId = {$selectedStoreId}";
                
                while($recZones = $queryZones->fetch($where)) {
                    $zones[$recZones->code] = $recZones->comment;
                }
                
                $form->FNC('zone', 'varchar(64)', 'caption=Зона');
                
                unset($queryZones, $where, $recZones);
                
                $form->setOptions('zone', $zones);
                
                // ENDOF Избор на зона
                
                // $form->setHidden('positionNew', 'На пода');
                $form->setHidden('palletId', $palletId);
                $form->setHidden('state', 'waiting');
                
                // Действие
                $form->setHidden('do', 'palletDown');
                break;
            
            case 'palletMove' :
                $position = pallet_Pallets::fetchField("#id = {$palletId}", 'position');
                
                if ($position != 'На пода') {
                    $ppRackId2RackNumResult = pallet_Racks::ppRackId2RackNum($position);
                    $position = $ppRackId2RackNumResult['position'];
                    unset($ppRackId2RackNumResult);
                }
                
                $data->formTitle = "|ПРЕМЕСТВАНЕ от палет място|* <b>{$position}</b> |на палет с|* ID=<b>{$palletId}</b>
                                <br/>|към друго палет място в склада|*";
                $form->FNC('do', 'varchar(64)', 'caption=Движение,input=hidden');
                $form->FNC('completed', 'set(YES=Да)', 'caption=Приключено');
                
                $form->showFields = 'palletPlaceHowto,completed';
                
                $form->FNC('palletPlaceHowto', 'varchar(64)', 'caption=Позициониране->Преместване към позиция');
                
                // Подготвя $palletPlaceHowto suggestions
                $palletPlaceHowto = array('' => '',
                    'Автоматично' => 'Автоматично');
                
                $form->setSuggestions('palletPlaceHowto', $palletPlaceHowto);
                
                $form->setHidden('palletId', $palletId);
                $form->setHidden('state', 'waiting');
                
                // Действие
                $form->setHidden('do', 'palletMove');
                break;
        }
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$data->form->title = $data->formTitle;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            
            // Взема селектирания склад
            $selectedStoreId = store_Stores::getCurrent();
            
            $productId = pallet_Pallets::fetchField($rec->palletId, 'productId');
            
            // проверка за insert/update
            if ($mvc->fetchField("#palletId={$rec->palletId}", 'id')) {
                $rec->id = $mvc->fetchField("#palletId={$rec->palletId}", 'id');
            }
            
            switch ($rec->do) {
                case "palletUp" :
                    // Проверка в зависимост от начина на определяне на палет мястото
                    
                    switch ($rec->palletPlaceHowto) {
                        case "Автоматично" :
                            // Генерира автоматично палет място от стратегията
                            $storeRec = pallet_pallets::fetch($selectedStoreId);
                            $strategy = cls::getInterface('pallet_ArrangeStrategyIntf', $storeRec->strategy);
                            $palletPlaceAuto = $strategy->getAutoPalletPlace($productId);
                            
                            if ($palletPlaceAuto == NULL) {
                                $form->setError('palletPlaceHowto', 'Автоматично не може да бъде предложено палет място в склада');
                            } else {
                                $rec->positionNew = $palletPlaceAuto;
                            }
                            break;
                            
                            // Палет мястото е въведено ръчно    
                        default :
                        $rec->palletPlaceHowto = store_type_PalletPlace::fromVerbal($rec->palletPlaceHowto);
                        
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
                        
                        $isSuitableResult = pallet_Racks::isSuitable($rackId, $productId, $rec->palletPlaceHowto);
                        
                        if ($isSuitableResult[0] === FALSE) {
                            $fErrors = $isSuitableResult[1];
                            pallet_Pallets::prepareErrorsAndWarnings($fErrors, $form);
                        } else {
                            $rec->positionNew = $rec->palletPlaceHowto;
                            $rec->positionOld = 'На пода';
                        }
                        break;
                    }
                    break;
                
                case "palletDown" :
                    $rec->positionNew = 'Зона: ' . $rec->zone;
                    $rec->state = 'waiting';
                    break;
                
                case "palletMove" :
                    // Проверка в зависимост от начина на определяне на палет мястото
                    switch ($rec->palletPlaceHowto) {
                        case "Автоматично" :
                            // Генерира автоматично палет място от стратегията
                            $storeRec = store_Stores::fetch($selectedStoreId);
                            $strategy = cls::getInterface('pallet_ArrangeStrategyIntf', $storeRec->strategy);
                            $palletPlaceAuto = $strategy->getAutoPalletPlace($productId);
                            
                            if ($palletPlaceAuto == NULL) {
                                $form->setError('palletPlaceHowto', 'Автоматично не може да бъде предложено палет място в склада');
                            } else {
                                $rec->positionNew = $palletPlaceAuto;
                            }
                            break;
                            
                            // Палет мястото е въведено ръчно    
                        default :
                        $rec->palletPlaceHowto = store_type_PalletPlace::fromVerbal($rec->palletPlaceHowto);
                        
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
                        
                        $isSuitableResult = pallet_Racks::isSuitable($rackId, $productId, $rec->palletPlaceHowto);
                        
                        if ($isSuitableResult[0] === FALSE) {
                            $fErrors = $isSuitableResult[1];
                            pallet_Pallets::prepareErrorsAndWarnings($fErrors, $form);
                        } else {
                            $rec->positionNew = $rec->palletPlaceHowto;
                            $rec->positionOld = $rec->position;
                        }
                        break;
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
        $rec->storeId = store_Stores::getCurrent();
        
        if (isset($rec->completed)) {
            $recPallets = pallet_Pallets::fetch($rec->palletId);
            
            $recPallets->state = 'closed';
            $recPallets->position = $rec->positionNew;
            pallet_Pallets::save($recPallets);
            
            redirect(array('pallet_Pallets'));
        }
    }
    
    
    /**
     * Смяна на state-а в pallet_Pallets при движение на палета
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param stdClass $rec
     */
    protected static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        if ($rec->do && in_array($rec->do, array('palletUp', 'palletDown', 'palletMove'))) {
            $recPallets = pallet_Pallets::fetch($rec->palletId);
            
            $recPallets->state = 'waiting';
            pallet_Pallets::save($recPallets);
            
            redirect(array('pallet_Pallets'));
        }
    }
    
    
    /**
     * Сменя state в pallet_Movements и в pallet_Pallets на 'active'
     *
     * @return core_Redirect
     */
    function act_SetPalletActive()
    {
        $id = Request::get('id', 'int');
        $userId = Users::getCurrent();
        
        $rec = $this->fetch($id);
        $rec->state = 'active';
        $rec->workerId = $userId;
        $this->save($rec);
        
        $recPallets = pallet_Pallets::fetch("#id = {$rec->palletId}");
        $recPallets->state = 'active';
        pallet_Pallets::save($recPallets);
        
        return new Redirect(array($this));
    }
    
    
    /**
     * Сменя state в pallet_Movements и в pallet_Pallets на 'closed'
     *
     * @return core_Redirect
     */
    function act_SetPalletClosed()
    {
        $id = Request::get('id', 'int');
        $userId = Users::getCurrent();
        
        $rec = $this->fetch($id);
        $recPallets = pallet_Pallets::fetch("#id = {$rec->palletId}");
        
        $recPallets->state = 'closed';
        $rec->state = 'closed';
        
        $rec->positionOld = $recPallets->position;
        $recPallets->position = $rec->positionNew;
        
        pallet_Pallets::save($recPallets);
        self::save($rec);
        
        return new Redirect(array($this));
    }
    
    
    /**
     * Сменя state в pallet_Movements и в pallet_Pallets на 'closed'
     *
     * @return core_Redirect
     */
    function act_DenyPalletMovement()
    {
        $id = Request::get('id', 'int');
        $userId = Users::getCurrent();
        
        $rec = $this->fetch($id);
        
        $recPallets = pallet_Pallets::fetch("#id = {$rec->palletId}");
        
        $recPallets->state = 'closed';
        pallet_Pallets::save($recPallets);
        
        self::delete($rec->id);
        
        return new Redirect(array($this));
    }
    
    
    /**
     * Филтър
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->title = 'Търсене';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->FNC('stateFilter', 'enum(waiting, active, closed,)', 'caption=Състояние');
        $data->listFilter->setDefault('stateFilter', '');
        $data->listFilter->FNC('palletIdFilter', 'key(mvc=pallet_Pallets, select=id, allowEmpty=true)', 'caption=Палет');
        $data->listFilter->FNC('productIdFilter', 'key(mvc=store_Products, select=productId, allowEmpty=true)', 'caption=Продукт');
        
        $data->listFilter->showFields = 'stateFilter, palletIdFilter, productIdFilter';
        
        // Активиране на филтъра
        $recFilter = $data->listFilter->input();
        
        // Ако филтъра е активиран
        if ($data->listFilter->isSubmitted()) {
            if ($recFilter->stateFilter) {
                $condState = "#state = '{$recFilter->stateFilter}'";
            }
            
            if ($recFilter->palletIdFilter) {
                $condPalletId = "#palletId = '{$recFilter->palletIdFilter}'";
            }
            
            if ($recFilter->productIdFilter) {
                // Проверка дали от този продукт има палетирано количество  
                if (pallet_Pallets::fetch("#productId = {$recFilter->productIdFilter}")) {
                    // get pallets with this product
                    $cond = "#productId = {$recFilter->productIdFilter}";
                    $queryPallets = pallet_Pallets::getQuery();
                    
                    while($recPallets = $queryPallets->fetch($cond)) {
                        $palletsSqlString .= ',' . $recPallets->id;
                    }
                    $palletsSqlString = substr($palletsSqlString, 1, strlen($palletsSqlString) - 1);
                    
                    // END get pallets with this product
                    
                    $condProductId = "#palletId IN ({$palletsSqlString})";
                } else {
                    $condProductId = "1=2";
                }
            }
            
            if ($condState) $data->query->where($condState);
            
            if ($condPalletId) $data->query->where($condPalletId);
            
            if ($condProductId) $data->query->where($condProductId);
        }
        
        $data->query->orderBy('state');
    }
    
    
    /**
     * Проверка дали за дадено палет място няма наредено движение
     *
     * @param string $palletPlace
     * @return boolean
     */
    public static function checkIfPalletPlaceHasNoAppointedMovements($palletPlace)
    {
        $selectedStoreId = store_Stores::getCurrent();
        
        if ($recMovements = pallet_Movements::fetch("#positionNew = '{$palletPlace}' AND #storeId = {$selectedStoreId}")) return FALSE;
        
        return TRUE;
    }
}
