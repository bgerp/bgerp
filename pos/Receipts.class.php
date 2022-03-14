<?php


/**
 * Мениджър за "Бележки за продажби"
 *
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.11
 */
class pos_Receipts extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Бележки за продажба';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_Rejected, plg_Printing, acc_plg_DocumentSummary, plg_Printing, plg_State, pos_Wrapper, cat_plg_AddSearchKeywords, plg_Search, plg_Sorting, plg_Modified,plg_RowTools2,store_plg_StockPlanning';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Бележка за продажба';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, createdOn, modifiedOn, valior, title=Бележка, pointId=Точка, contragentName, productCount, total, paid, change, state, revertId, returnedTotal';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'sales_RatingsSourceIntf';
    
    
    /**
     * Детайли на бележката
     */
    public $details = 'pos_ReceiptDetails';
    
    
    /**
     * Главен детайл на модела
     */
    public $mainDetail = 'pos_ReceiptDetails';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, pos';
    
    
    /**
     * Кой може да приключи бележка?
     */
    public $canClose = 'ceo, pos';
    
    
    /**
     * Кой може да прехвърли бележка?
     */
    public $canTransfer = 'ceo, pos';
    
    
    /**
     * Кой може да променя?
     */
    public $canAdd = 'pos, ceo';
    
    
    /**
     * Кой може да сторнира?
     */
    public $canRevert = 'pos, ceo';
    
    
    /**
     * Кой може да плати?
     */
    public $canPay = 'pos, ceo';
    
    
    /**
     * Кой може да променя?
     */
    public $canTerminal = 'pos, ceo';
    
    
    /**
     * Кой може да оттегля
     */
    public $canReject = 'pos, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,pos';
    
    
    /**
     * Кой може да задава клиент?
     */
    public $canSetcontragent = 'ceo,pos';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,pos';
    
    
    /**
     * Кой може да променя?
     */
    public $canEdit = 'pos,ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'pos/tpl/SingleLayoutReceipt.shtml';
    
    
    /**
     * Кои полета да се извлекат преди изтриване
     */
    public $fetchFieldsBeforeDelete = 'id';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, valior, modifiedOn';
    
    
    /**
     *  Полета по които ще се търси
     */
    public $searchFields = 'pointId, contragentName, valior';
    
    
    /**
     *  Служебно ид на дефолтна рецепта за сторниране
     */
    const DEFAULT_REVERT_RECEIPT = -1;


    /**
     *  При преминаването в кои състояния ще се обновяват планираните складови наличностти
     */
    public $updatePlannedStockOnChangeStates = array('waiting');


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('valior', 'date', 'caption=Дата,input=none');
        $this->FLD('pointId', 'key(mvc=pos_Points, select=name)', 'caption=Точка на продажба');
        $this->FLD('contragentName', 'varchar(255)', 'caption=Контрагент,input=none');
        $this->FLD('contragentObjectId', 'int', 'input=none');
        $this->FLD('contragentLocationId', 'key(mvc=crm_Locations)', 'caption=Локация,input=none');
        $this->FLD('contragentClass', 'key(mvc=core_Classes,select=name)', 'input=none');
        $this->FLD('total', 'double(decimals=2)', 'caption=Общо, input=none, value=0, summary=amount');
        $this->FLD('paid', 'double(decimals=2)', 'caption=Платено, input=none, value=0, summary=amount');
        $this->FLD('change', 'double(decimals=2)', 'caption=Ресто, input=none, value=0, summary=amount');
        $this->FLD('tax', 'double(decimals=2)', 'caption=Такса, input=none, value=0');
        $this->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Оттеглен, closed=Затворен,waiting=Чакащ)', 'caption=Статус, input=none');
        $this->FLD('transferedIn', 'key(mvc=sales_Sales)', 'input=none');
        $this->FLD('revertId', 'int', 'input=none,caption=Сторнира');
        $this->FLD('returnedTotal', 'double(decimals=2)', 'caption=Сторнирано, input=none');
        $this->FNC('productCount', 'int', 'caption=Артикули');
        
        $this->setDbIndex('valior');
        $this->setDbIndex('revertId');
    }
    
    
    /**
     *  Екшън създаващ нова бележка, и редиректващ към Единичния и изглед
     *  Добавянето на нова бележка става само през този екшън
     */
    public function act_New()
    {
        $cu = core_Users::getCurrent();
        $pointId = Request::get('pointId', 'int');
        
        if(!isset($pointId)){
            $pointId = pos_Points::getCurrent();
        } else {
            pos_Points::selectCurrent($pointId);
        }
        
        pos_Points::requireRightFor('select', $pointId);
        $forced = Request::get('forced', 'int');
        
        // Ако форсираме, винаги създаваме нова бележка
        if ($forced) {
            $id = $this->createNew();
            $this->logWrite('Създаване на нова бележка', $id);
        } else {
            
            // Коя е последната чернова бележка от ПОС-а
            $today = dt::today();
            $query = $this->getQuery();
            $query->where("#pointId = {$pointId} AND #state = 'draft' AND #revertId IS NULL");
            $query->show('valior,contragentClass,contragentObjectId,total');
            $query->orderBy('id', 'DESC');
            $lastDraft = $query->fetch();
            
            $id = null;
            if(is_object($lastDraft)){
                
                // Ако има такава и тя е без контрагент и е празна
                $defaultContragentId = pos_Points::defaultContragent($pointId);
                if(empty($lastDraft->total) && $lastDraft->contragentClass == crm_Persons::getClassId() && $lastDraft->contragentObjectId == $defaultContragentId){
                    $today = dt::today();
                    
                    // Ако е със стара дата, подменя се
                    if($lastDraft->valior != $today){
                        $lastDraft->valior = $today;
                        $this->save_($lastDraft, 'valior');
                    }
                    
                    // Ще се редиректне към нея
                    $id = $lastDraft->id;
                }
            }
            
            if (empty($id)) {
                $id = $this->createNew();
                $this->logWrite('Създаване на нова бележка', $id);
            }
        }
        
        // Записваме, че потребителя е разглеждал този списък
        $foundRec = self::fetch($id);
        $operation = (empty($foundRec->paid)) ? 'add' : 'payment';
        Mode::setPermanent("currentOperation{$id}", $operation);
        Mode::setPermanent("currentSearchString{$id}", null);
        
        return new Redirect(array('pos_Terminal', 'open', 'receiptId' => $id));
    }
    
    
    /**
     * Създава нова чернова бележка
     */
    private function createNew($revertId = null)
    {
        $rec = new stdClass();
        $posId = pos_Points::getCurrent();
        
        $rec->contragentName = 'Анонимен Клиент';
        $rec->contragentClass = core_Classes::getId('crm_Persons');
        $rec->contragentObjectId = pos_Points::defaultContragent($posId);
        $rec->pointId = $posId;
        $rec->valior = dt::now();
        $this->requireRightFor('add', $rec);
        
        if (!empty($revertId)) {
            $rec->revertId = $revertId;
        }
        
        return $this->save($rec);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->currency = acc_Periods::getBaseCurrencyCode($rec->createdOn);
        if(!empty($rec->returnedTotal) && empty($rec->revertId)){
            $row->returnedTotal = ht::styleIfNegative("-{$row->returnedTotal}", -1 * $rec->returnedTotal);
            $row->returnedCurrency = $row->currency;
        }
        
        $Contragent = new core_ObjectReference($rec->contragentClass, $rec->contragentObjectId);
        $contragentFolderId = $Contragent->fetchField('folderId');
        $row->contragentId = (isset($contragentFolderId)) ? doc_Folders::recToVerbal($contragentFolderId)->title : $Contragent->getHyperlink(true);
        
        if ($fields['-list']) {
            $row->title = $mvc->getHyperlink($rec->id, true);
        } elseif ($fields['-single']) {
            $row->title = self::getRecTitle($rec);
            $row->iconStyle = 'background-image:url("' . sbf('img/16/view.png', '') . '");';
            $row->caseId = cash_Cases::getHyperLink(pos_Points::fetchField($rec->pointId, 'caseId'), true);
            $row->storeId = store_Stores::getHyperLink(pos_Points::fetchField($rec->pointId, 'storeId'), true);
            $row->baseCurrency = acc_Periods::getBaseCurrencyCode($rec->createdOn);
            if ($rec->transferedIn) {
                $row->transferedIn = sales_Sales::getHyperlink($rec->transferedIn, true);
            }
           
            if ($rec->state == 'closed' || $rec->state == 'rejected') {
                $reportQuery = pos_Reports::getQuery();
                $reportQuery->where("#state = 'active' || #state = 'closed'");
                $reportQuery->show('details');
                
                // Опитваме се да намерим репорта в който е приключена бележката
                //@TODO не е много оптимално защото търсим в блоб поле...
                while ($rRec = $reportQuery->fetch()) {
                    $id = $rec->id;
                    $found = array_filter($rRec->details['receipts'], function ($e) use (&$id) {
                        
                        return $e->id == $id;
                    });
                    
                    if ($found) {
                        $row->inReport = pos_Reports::getLink($rRec->id, 0);
                        break;
                    }
                }
            }
        }
        
        if(isset($fields['-terminal'])){
            $row->id = ht::createLink($row->id, pos_Receipts::getSingleUrlArray($rec->id));
        }
        
        $rec->total = abs($rec->total);
        $row->total = $mvc->getFieldType('change')->toVerbal($rec->total);
        if(!empty($rec->paid)){
            $row->PAID_CAPTION = (isset($rec->revertId)) ? tr('Върнато') : tr('Платено');
            $rec->paid = abs($rec->paid);
            $row->paid = $mvc->getFieldType('paid')->toVerbal($rec->paid);
            if(!empty($rec->change)){
                $row->CHANGE_CLASS = ($rec->change < 0 || isset($rec->revertId)) ? 'changeNegative' : 'changePositive';
                $row->CHANGE_CAPTION = ($rec->change < 0 || isset($rec->revertId)) ? tr("За плащане") : tr("Ресто");
                $row->change = $mvc->getFieldType('change')->toVerbal(abs($rec->change));
                $row->changeCurrency = $row->currency;
            } else {
                unset($row->change);
            }
        } else{
            unset($row->paid);
            unset($row->change);
        }
        
        if (isset($rec->revertId)) {
            $row->REVERT_CAPTION = tr("Сторно");
            $row->revertId = ($rec->revertId != self::DEFAULT_REVERT_RECEIPT) ? pos_Receipts::getHyperlink($rec->revertId, true) : (!Mode::is('printing') ? ht::createHint(' ', 'Произволна сторнираща бележка', 'warning') : null);
        } elseif($rec->state != 'draft') {
            if(isset($rec->transferedIn)){
                $row->revertId = tr('Прехвърлена');
            } else {
                $row->revertId = $row->state;
            }
        }
        
        if($rec->state == 'rejected'){
            $row->TERMINAL_STATE_CLASS = "rejected-receipt";
            $row->revertId = $row->state;
        }
        
        // показваме датата на последната модификация на документа, ако е активиран
        if ($rec->state != 'draft') {
            $row->valior = dt::mysql2verbal($rec->modifiedOn, 'd.m.Y H:i:s');
        }
        
        $cu = core_Users::fetch($rec->createdBy);
        $row->createdBy = ht::createLink(core_Users::recToVerbal($cu)->nick, crm_Profiles::getUrl($rec->createdBy));
        $row->pointId = pos_Points::getHyperLink($rec->pointId, true);
        $row->time = dt::mysql2verbal(dt::now(), 'H:i');
        $row->productCount = $mvc->getProducts($rec->id, true);
        
        if(isset($rec->contragentLocationId)){
            $row->contragentLocationId = crm_Locations::getHyperlink($rec->contragentLocationId);
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if ($mvc->haveRightFor('terminal', $data->rec)) {
            $data->toolbar->addBtn('Терминал', array('pos_Terminal', 'open', 'receiptId' => $data->rec->id, 'ret_url' => true), 'ef_icon=img/16/forward16.png, order=18,target=_blank');
        }
    }
    
    
    /**
     * След подготовката на туулбара на списъчния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        // Подменяме бутона за добавяне с такъв сочещ към терминала
        if (!empty($data->toolbar->buttons['btnAdd'])) {
            $data->toolbar->removeBtn('btnAdd');
            $data->toolbar->addBtn('Терминал', array($mvc, 'new'), 'id=btnAdd', 'ef_icon = img/16/forward16.png,title=Създаване на нова бележка');
        }
    }
    
    
    /**
     * Извлича информацията за всички продукти които са продадени чрез
     * тази бележки, във вид подходящ за контирането
     *
     * @param int id         - ид на бележката
     * @param boolean $count - дали да е само броя
     *
     *
     * @return mixed $products - Масив от продукти
     */
    public static function getProducts($id, $count = false)
    {
        expect($rec = static::fetch($id), 'Несъществуваща бележка');
        
        $query = pos_ReceiptDetails::getQuery();
        $query->where("#receiptId = {$id}");
        $query->where('#quantity != 0');
        $query->where("#action LIKE '%sale%'");
        $query->orderBy('id', 'ASC');
        
        if($count){
            
            return $query->count();
        }
        
        $products = array();
        while ($rec = $query->fetch()) {
            $info = cat_Products::getProductInfo($rec->productId);
            $quantityInPack = ($info->packagings[$rec->value]) ? $info->packagings[$rec->value]->quantity : 1;
            
            $products[] = (object) array('productId'   => $rec->productId,
                                         'price'       => $rec->price / $quantityInPack,
                                         'packagingId' => $rec->value,
                                         'text'        => $rec->text,
                                         'vatPrice'    => $rec->price * $rec->param,
                                         'discount'    => $rec->discountPercent,
                                         'quantity'    => $rec->quantity);
        }
        
        
        
        return $products;
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     *
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
        expect($rec = $this->fetchRec($id));
        $rec->change = $rec->total = $rec->paid = 0;
        
        $dQuery = $this->pos_ReceiptDetails->getQuery();
        $dQuery->where("#receiptId = {$id}");
        while ($dRec = $dQuery->fetch()) {
            $action = explode('|', $dRec->action);
            switch ($action[0]) {
                case 'sale':
                    $price = $this->getDisplayPrice($dRec->price, $dRec->param, $dRec->discountPercent, $rec->pointId, $dRec->quantity);
                    $rec->total += round($dRec->quantity * $price, 2);
                    break;
                case 'payment':
                    $paidAmount = $dRec->amount;
                    if ($action[1] != '-1') {
                        $paidAmount = cond_Payments::toBaseCurrency($action[1], $paidAmount, $rec->valior);
                    }
                    
                    $rec->paid += $paidAmount;
                    $rec->change += $dRec->value;
                    break;
            }
        }
        
        $diff = round($rec->paid - $rec->total, 2);
        $rec->change = $diff;
        $rec->total = $rec->total;
        
        $this->save($rec);
    }
    
    
    /**
     *  Филтрираме бележката
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        pos_Points::addPointFilter($data->listFilter, $data->query);
        $filterDateFld = $data->listFilter->rec->filterDateField;
        $data->query->orderBy($filterDateFld, 'DESC');
        
        foreach (array('valior', 'createdOn', 'modifiedOn') as $fld) {
            if ($fld != $data->listFilter->rec->filterDateField) {
                unset($data->listFields[$fld]);
            }
        }
    }
    
    
    /**
     * Модификация на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        // Само черновите бележки могат да се редактират в терминала
        if ($action == 'terminal' && isset($rec)) {
            if (!pos_Points::haveRightFor('select', $rec->pointId)) {
                $res = 'no_one';
            }
        }
        
        // Никой не може да оттегли затворена бележка
        if ($action == 'reject' && isset($rec)) {
            if(in_array($rec->state, array('closed', 'pending', 'rejected'))){
                $res = 'no_one';
            } elseif(empty($rec->total)){
                if(empty($rec->revertId)){
                    $res = 'no_one';
                }
            }
        }
        
        // Ако бележката е започната, може да се изтрие
        if ($action == 'delete' && isset($rec)) {
            if ($rec->state != 'draft') {
                $res = 'no_one';
            }
        }
        
        // Можем да контираме бележки само когато те са чернови и платената
        // сума е по-голяма или равна на общата или общата сума е <= 0
        if ($action == 'close' && isset($rec->id)) {
            if ($rec->total == 0 || round($rec->paid, 2) < round($rec->total, 2)) {
                $res = 'no_one';
            }
        }
        
        // Може ли да бъде направено плащане по бележката
        if ($action == 'pay' && isset($rec)) {
            if ($rec->state != 'draft' || !$rec->total || ($rec->total && abs($rec->paid) >= abs($rec->total))) {
                $res = 'no_one';
            }
        }
        
        // Не може да се прехвърля бележката, ако общото и е нула, има платено или не е чернова
        if ($action == 'transfer' && isset($rec)) {
            if (empty($rec->id) || round($rec->paid, 2) > 0 || $rec->state != 'draft') {
                $res = 'no_one';
            }
        }
        
        if($action == 'setcontragent' && isset($rec)){
            if(!$mvc->haveRightFor('terminal', $rec)){
                $res = 'no_one';
            }
        }
        
        if($action == 'edit' && isset($rec) && $rec->state == 'waiting'){
            $res = 'no_one';
        }
        
        if ($action == 'revert' && isset($rec) && ($rec != pos_Receipts::DEFAULT_REVERT_RECEIPT)) {
            if(isset($rec->revertId) || (!in_array($rec->state, array('waiting', 'closed'))) || (!empty($rec->returnedTotal) && round($rec->total - $rec->returnedTotal, 2) <= 0)){
                $res = 'no_one';
            }
        }
    }
    
    
    /**
     * Активира документа и ако е зададено пренасочва към създаването на нова фактура
     */
    public function act_Transfer()
    {
        $this->requireRightFor('transfer');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        
        // Извличаме нужните ни параметри от рекуеста
        expect($contragentClassId = Request::get('contragentClassId', 'int'));
        expect($contragentId = Request::get('contragentId', 'int'));
        expect($contragentClass = cls::get($contragentClassId));
        expect($contragentClass->fetch($contragentId));
        $this->requireRightFor('transfer', $rec);
        
        // Подготвяме масива с данните на новата продажба, подаваме склада и касата на точката
        $posRec = pos_Points::fetch($rec->pointId);
        $fields = array('shipmentStoreId' => $posRec->storeId, 'caseId' => $posRec->caseId, 'receiptId' => $rec->id, 'deliveryLocationId' => $rec->contragentLocationId);
        $products = $this->getProducts($rec->id);
        
        // Опитваме се да създадем чернова на нова продажба породена от бележката
        if ($sId = sales_Sales::createNewDraft($contragentClassId, $contragentId, $fields)) {
            sales_Sales::logWrite('Прехвърлена от POS продажба', $sId);
            
            // Намираме продуктите на бележката (трябва да има поне един)
            $products = $this->getProducts($rec->id);
            
            // Всеки продукт се прехвърля едно към 1
            foreach ($products as $product) {
                if($product->discount < 0){
                    $product->price *= (1 + abs($product->discount));
                    $product->discount = null;
                }
                
                sales_Sales::addRow($sId, $product->productId, $product->quantity, $product->price, $product->packagingId, $product->discount, null, null, $product->text);
            }
        }
        
        // Отбелязваме къде е прехвърлена рецептата
        $rec->transferedIn = $sId;
        $rec->state = 'closed';
        $this->save($rec);
        $this->logInAct('Прехвърляне на бележка', $rec->id);
        core_Statuses::newStatus("|Бележка|* №{$rec->id} |е затворена|*");
        
        // Споделяме потребителя към нишката на създадената продажба
        $cu = core_Users::getCurrent();
        $sRec = sales_Sales::fetch($sId);
        doc_ThreadUsers::addShared($sRec->threadId, $sRec->containerId, $cu);
        Mode::setPermanent("currentOperation{$rec->id}", 'receipts');
        Mode::setPermanent("currentSearchString{$rec->id}", null);
        
        // Редирект към новата бележка
        return new Redirect(array('sales_Sales', 'single', $sId), 'Успешно прехвърляне на бележката');
    }
    
    
    /**
     * Проверка на количеството
     *
     * @param stdClass    $rec
     * @param string      $error
     * @param string|null $warning
     *
     * @return bool
     */
    public static function checkQuantity($rec, &$error, &$warning = null)
    {
        // Ако е забранено продаването на неналични артикули да се проверява
        $notInStockChosen = pos_Setup::get('ALLOW_SALE_OF_PRODUCTS_NOT_IN_STOCK');
        if ($notInStockChosen == 'yes') {

           return true;
        }

        $today = dt::today();
        $pRec = cat_products_Packagings::getPack($rec->productId, $rec->value);
        $stRec = store_Products::getQuantities($rec->productId, $rec->storeId, $today);
        $freeQuantityNow = $stRec->free;
        $quantityInStock = $stRec->quantity;
        $freeQuantity = store_Products::getQuantities($rec->productId, $rec->storeId)->free;

        // Ако има положителна наличност
        if(core_Packs::isInstalled('batch') && $quantityInStock > 0){
            if($BatchDef = batch_Defs::getBatchDef($rec->productId)){
                if(!empty($rec->batch)){

                    // И е подадена конкретна партида, взима се нейното количество
                    $quantityInStock = batch_Items::getQuantity($rec->productId, $rec->batch, $rec->storeId);
                } else {

                    // Ако е без партида но има партидност, гледа се колко има в склада, които са без партида
                    $batchesIn = batch_Items::getBatchQuantitiesInStore($rec->productId, $rec->storeId);
                    $quantityOnBatches = 0;
                    array_walk($batchesIn, function($a) use(&$quantityOnBatches){ if($a > 0) {$quantityOnBatches += $a;}});
                    $quantityInStock = round($quantityInStock - $quantityOnBatches, 4);
                }
            }
        }

        $originalQuantityInStock = $quantityInStock;
        $originalFreeQuantityNow = $freeQuantityNow;
        $originalFreeQuantity = $freeQuantity;

        $quantityInPack = ($pRec) ? $pRec->quantity : 1;
        $quantityInStock -= round($rec->quantity * $quantityInPack, 2);
        $freeQuantity -= round($rec->quantity * $quantityInPack, 2);
        $freeQuantityNow -= round($rec->quantity * $quantityInPack, 2);

        $freeQuantityNow = round($freeQuantityNow, 2);
        $freeQuantity = round($freeQuantity, 2);
        $quantityInStock = round($quantityInStock, 2);
        $Double = core_Type::getByName('double(decimals=2)');
        $pName = cat_Products::getTitleById($rec->productId);

        if ($quantityInStock < 0) {
            $originalQuantityInStockVerbal = $Double->toVerbal($originalQuantityInStock);
            $error = "|* {$pName}: |Количеството не е налично в склад|*: " . store_Stores::getTitleById($rec->storeId);
            $error .= ". |Налично в момента|* {$originalQuantityInStockVerbal}";

            return false;
        }

        if($freeQuantityNow < 0){
            $originalFreeQuantityNowVerbal = $Double->toVerbal($originalFreeQuantityNow);
            $warning = "|* {$pName}: Количеството e над разполагаемото|* {$originalFreeQuantityNowVerbal} |днес в склад|*: " . store_Stores::getTitleById($rec->storeId);

            return true;
        }

        if($freeQuantity < 0){
            $originalFreeQuantityVerbal = $Double->toVerbal($originalFreeQuantity);
            $warning = "|* {$pName}: Количеството e над минималното разполагаемото|* {$originalFreeQuantityVerbal} |в склад|*: " . store_Stores::getTitleById($rec->storeId);
        }

        return true;
    }
    
    
    /**
     * Активира документа и ако е зададено пренасочва към създаването на нова фактура
     */
    public function act_Close()
    {
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        if ($rec->state != 'draft') {

            // Създаване на нова чернова бележка
            return new Redirect(array($this, 'new'));
        }
        
        $this->requireRightFor('close', $rec);
        
        // Ако е сторно бележка, проверява се може ли да се контира
        if(isset($rec->revertId)){
            $error = null;
            if(!static::canCloseRevertReceipt($rec, $error)){

                followRetUrl(null, $error, 'error');
            }
        }

        $rec->state = 'waiting';
        $rec->__closed = true;

        if ($this->save($rec)) {
            if(isset($rec->revertId) && $rec->revertId != static::DEFAULT_REVERT_RECEIPT){
                $this->calcRevertedTotal($rec->revertId);
            }

            $this->logInAct('Приключване на бележка', $rec->id);
        }
        
        // Създаване на нова чернова бележка
        return new Redirect(array($this, 'new'));
    }
    
    
    /**
     * Показва краткия номер на бележката, съгласно настройките на пакета
     * 
     * @param int $id
     * 
     * @return string $num
     */
    public static function getReceiptShortNum($id)
    {
        $conf = core_Packs::getConfig('pos');
        $num = substr($id, -1 * $conf->POS_SHOW_RECEIPT_DIGITS);
        if(strlen($id) > strlen($num)){
            $num = "*{$num}";
        }
        
        return $num;
    }
    
    
    /**
     * Подготвя чакащите бележки в сингъла на точката на продажба
     *
     * @param stdClass $data
     *
     * @return void
     */
    public function prepareReceipts(&$data)
    {
        $data->rows = array();
        $data->Pager = cls::get('core_Pager', array('itemsPerPage' => 20));
        $data->count = 0;
        
        $query = $this->getQuery();
        $query->where("#pointId = {$data->masterId}");
        $query->where("#state = 'waiting' OR #state = 'draft'");
        $query->orderBy('#state=ASC,id=DESC');
        if ($count = $query->count()) {
            $data->count = core_Type::getByName('int')->toVerbal($count);
        }
        
        $baseCurrencyCode = acc_Periods::getBaseCurrencyCode();
        
        $fields = $this->selectFields();
        $fields['-list'] = true;
        $data->listFields = arr::make("num=Бележка,productCount=Артикули,contragentId=Клиент,total=Сума");
        
        $data->Pager->setLimit($query);
        while ($rec = $query->fetch()) {
            $data->rows[$rec->id] = $this->recToVerbal($rec, $fields);
            $num = self::getRecTitle($rec);
            if (!Mode::isReadOnly()) {
                if ($this->haveRightFor('terminal', $rec)) {
                    $num = ht::createLink($num, array('pos_Terminal', 'open', 'receiptId' => $rec->id), false, 'title=Довършване на бележката,ef_icon=img/16/cash-register.png');
                } elseif ($this->haveRightFor('single', $rec)) {
                    $num = ht::createLink($num, array($this, 'single', $rec->id), false, "title=Отваряне на бележка №{$rec->id},ef_icon=img/16/view.png");
                }
            }
            
            $data->rows[$rec->id]->total = ht::styleNumber($data->rows[$rec->id]->total,  $rec->total);
            $data->rows[$rec->id]->total .= " <span class='cCode'>{$baseCurrencyCode}</span>";
            $data->rows[$rec->id]->num = $num;
        }
    }
    
    
    /**
     * Рендиране на чакащите бележки в сингъла на точката на продажба
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    public function renderReceipts($data)
    {
        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $tpl->append(tr('Чакащи бележки') . " ({$data->count})", 'title');
        $fieldset = new core_FieldSet();
        
        $fieldset->FLD('num', 'varchar', 'tdClass=leftCol');
        $fieldset->FLD('contragentId', 'varchar', 'tdClass=leftCol');
        $fieldset->FLD('total', 'double', 'smartcenter');
        
        $table = cls::get('core_TableView', array('mvc' => $fieldset));
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        $table->tableClass = 'listTable receiptsInSingle';
        $details = $table->get($data->rows, $data->listFields);
        
        $tpl->append($details, 'content');
        if (isset($data->Pager)) {
            $tpl->append($data->Pager->getHtml(), 'content');
        }
        
        return $tpl;
    }
    
    
    /**
     * Преди изтриване
     */
    protected static function on_AfterDelete($mvc, &$numRows, $query, $cond)
    {
        foreach ($query->getDeletedRecs() as $rec) {
            pos_ReceiptDetails::delete("#receiptId = {$rec->id}");
        }
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $valiorVerbal = self::getVerbal($rec, 'valior');
        $pointIdVerbal = self::getVerbal($rec, 'pointId');
        $title = "{$pointIdVerbal}/{$rec->id}/{$valiorVerbal}";
        
        if (isset($rec->revertId)) {
            $title = ht::createHint($title, 'Сторно бележка');
            $title->prepend("<span class='red'>");
            $title->append("</span>");
        }
        
        return $title;
    }
    
    
    /**
     * Екшън за започване на действие за сторниране на бележка
     */
    public function act_Revert()
    {
        $this->requireRightFor('revert');
        expect($id = Request::get('id', 'int'));
        $this->requireRightFor('revert', $id);
        
        $newReceiptId = $this->createNew($id);
        $this->logWrite('Създаване на сторнираща бележка', $id);
        
        Mode::setPermanent("currentOperation{$newReceiptId}", 'add');
        Mode::setPermanent("currentSearchString{$newReceiptId}", null);
        
        return new Redirect(array('pos_Terminal', 'open', "receiptId" => $newReceiptId));
    }
    
    
    /**
     * Опит за намиране на ПОС бележка по даден стринг
     */
    protected function on_AfterFindReceiptByNumber($mvc, &$res, $string, $forRevert = false)
    {
        if (!isset($res['rec']) && empty($res['notFoundError'])) {
            if (type_Int::isInt($string)) {
                $res['rec'] = self::fetch($string);
                if (!is_object($res['rec'])) {
                    $res['notFoundError'] = "|Не е намерена бележка от номер|* '<b>{$string}</b>'!";
                    $res['rec'] = false;
                }
            }
        }
        
        if (is_object($res['rec'])) {
            if ($forRevert === true) {
                if (self::fetchField("#id = {$res['rec']->id} AND #revertId IS NOT NULL")) {
                    $res['notFoundError'] = '|Не може да сторнирате сторнираща бележка|*!';
                    $res['rec'] = false;
                }
            }
        }
    }
    
    
    /**
     * Обработване на цената
     */
    protected function on_AfterGetDisplayPrice($mvc, &$res, $priceWithoutVat, $vat, $discountPercent, $pointId, $quantity)
    {
        $quantity = !empty($quantity) ? $quantity : 1;
        
        if (empty($res)) {
            $res = $priceWithoutVat * $quantity * (1 + $vat);
            if (!empty($discountPercent)) {
                $res *= (1 - $discountPercent);
            }
            $res /= $quantity;
            
            $res = round($res, 2);
        }
    }
    
    
    /**
     * Екшън задаващ контрагент на бележката
     */
    public function act_setcontragent()
    {
        $this->requireRightFor('setcontragent');
        expect($id = Request::get('id'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('setcontragent', $rec);
        expect($rec->contragentClass = Request::get('contragentClassId', 'int'));
        expect($rec->contragentObjectId = Request::get('contragentId', 'int'));
        $locationId = Request::get('locationId', 'int');
        
        $rec->contragentName = cls::get($rec->contragentClass)->getVerbal($rec->contragentObjectId, 'name');
        $rec->contragentLocationId = $locationId;
        $this->save($rec, 'contragentObjectId,contragentClass,contragentName,contragentLocationId');
        
        $Policy = cls::get('price_ListToCustomers');
        
        // Ако има детайли
        $dQuery = pos_ReceiptDetails::getQuery();
        $dQuery->where("#action = 'sale|code' AND #receiptId = {$rec->id}");
        while($dRec = $dQuery->fetch()){
           
            // Обновява им се цената по текущата политика, ако може
            $packRec = cat_products_Packagings::getPack($dRec->productId, $dRec->value);
            $perPack = (is_object($packRec)) ? $packRec->quantity : 1;
            $price = $Policy->getPriceInfo($rec->contragentClass, $rec->contragentObjectId, $dRec->productId, $dRec->value, 1, $rec->createdOn, 1, 'no');
            if(!empty($price->price)){
               
                $dRec->price = $price->price * $perPack;
                $dRec->amount = $dRec->price * $dRec->quantity;
                $dRec->discountPercent = $price->discount;
                pos_ReceiptDetails::save($dRec, 'price,amount,discountPercent');
            }
        }
        
        $this->logWrite('Задаване на контрагент', $id);
        
        followRetUrl();
    }
    
    
    /**
     * Последните записи от потребителския лог в четим вид
     * 
     * @param int $id
     * @param string $type
     * @param int|null $limit
     * 
     * @return stdClass
     */
    public static function getLastUserActionsVerbal($id, $type = 'write', $limit = null)
    {
        $rows = array();
        $rec = static::fetchRec($id);
        $actions = log_Data::getObjectRecs(get_called_class(), $rec->id, $type, null, $limit);
        foreach ($actions as $aRec){
            $rows[] = (object)array('action' => log_Actions::getActionFromCrc($aRec->actionCrc), 
                                    'time' => dt::mysql2verbal(dt::timestamp2Mysql($aRec->time)), 
                                    'userId' => crm_Profiles::createLink($aRec->userId));
        }
        
        return $rows;
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        // Добавяне на използваните платежни методи към ключовите думи
        if(isset($rec->id)){
            $detailsKeywords = '';
            $dQuery = pos_ReceiptDetails::getQuery();
            $dQuery->where("#receiptId = '{$rec->id}' AND #action != 'sale|code'");
            while ($dRec = $dQuery->fetch()) {
                $action = cls::get('pos_ReceiptDetails')->getAction($dRec->action);
                $payment = ($action->value != -1) ? cond_Payments::getTitleById($action->value) : tr('В брой');
                $detailsKeywords .= ' ' . plg_Search::normalizeText($payment);
            }
            
            // Ако има нови ключови думи, добавят се
            if (!empty($detailsKeywords)) {
                $res = ' ' . $res . ' ' . $detailsKeywords;
            }
        }
    }
    
    
    /**
     * Калкулира, колко върнато по-бележката досега
     * 
     * @param int $id
     */
    private function calcRevertedTotal($id)
    {
        $rec = $this->fetch($id);
        
        $query = pos_Receipts::getQuery();
        $query->where("#revertId = {$rec->id}");
        $query->XPR('returnedTotalCalc', 'double', 'SUM(#total)');
        $query->show('returnedTotalCalc');
        $tRec = $query->fetch();
        
        $rec->returnedTotal = ($tRec->returnedTotalCalc) ? -1 * $tRec->returnedTotalCalc : null;
        $this->save_($rec, 'returnedTotal');
    }
    
    
    /**
     * Може ли да се приключи сторниращата бележка ?
     * 
     * @param mixed $rec
     * @param null|string $error
     * @return boolean
     */
    public static function canCloseRevertReceipt($rec, &$error = null)
    {
        $rec = static::fetchRec($rec);
        if($rec->revertId != static::DEFAULT_REVERT_RECEIPT){
            expect($toRevertRec = static::fetch($rec->revertId));
            $rest = round(($toRevertRec->total - $toRevertRec->returnedTotal), 2);
            
            if(round(abs($rec->total), 2) > $rest){
                $restVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($rest);
                $error = "Не може да се сторнира по-голяма сума от очакваната|* <b>{$restVerbal}</b> !";
                return false;
            }
        }
        
        return true;
    }
    
    
    /**
     * Подготовка на рейтингите за продажба на артикулите
     * @see sales_RatingsSourceIntf
     *
     * @return array $res - масив с обекти за върнатите данни
     *                 o objectClassId - ид на клас на обект
     *                 o objectId      - ид на обект
     *                 o classId       - текущия клас
     *                 o key           - ключ
     *                 o value         - стойност
     */
    public function getSaleRatingsData()
    {
        $time = pos_Setup::get('RATINGS_DATA_FOR_THE_LAST');
        $valiorFrom = dt::verbal2mysql(dt::addSecs(-1 * $time), false);
        
        // За всяка бележка, намират се най-продаваните 100 артикула
        $receiptQuery = pos_ReceiptDetails::getQuery();
        $receiptQuery->EXT('state', 'pos_Receipts', 'externalName=state,externalKey=receiptId');
        $receiptQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        $receiptQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
        $receiptQuery->EXT('pointId', 'pos_Receipts', 'externalName=pointId,externalKey=receiptId');
        $receiptQuery->EXT('valior', 'pos_Receipts', 'externalName=valior,externalKey=receiptId');
        $receiptQuery->EXT('revertId', 'pos_Receipts', 'externalName=revertId,externalKey=receiptId');
        $receiptQuery->where("#state != 'draft' && #state != 'rejected' AND #revertId IS NULL AND #isPublic = 'yes' AND #valior >= '{$valiorFrom}' AND #productId IS NOT NULL");
        $receiptQuery->show('productId,pointId,valior');
        
        $count = $receiptQuery->count();
        core_App::setTimeLimit($count * 0.4, false, 200);
        $classId = $this->getClassId();
        $objectClassId = cat_Products::getClassId();
        
        $res = array();
        while ($receiptRec = $receiptQuery->fetch()){
            $storeId = pos_Points::fetchField($receiptRec->pointId, 'storeId');
            $index = "{$receiptRec->productId}|{$storeId}";
            
            $monthsBetween = countR(dt::getMonthsBetween($receiptRec->valior));
            $rating = round(12 / $monthsBetween);
            $rating = 100 * $rating;
            
            sales_ProductRatings::addRatingToObject($res, $index, $classId, $objectClassId, $receiptRec->productId, $storeId, $rating);
        }
        
        // Ако има артикули в бележките изчисляват се и техните рейтинги от продажбите
        $deltaQuery = sales_PrimeCostByDocument::getQuery();
        $deltaQuery->where("#sellCost IS NOT NULL AND (#state = 'active' OR #state = 'closed') AND #isPublic = 'yes'");
        $deltaQuery->where("#valior >= '{$valiorFrom}'");
        $deltaQuery->show('productId,storeId,detailClassId,valior');
        
        // Ако артикула се среща и в експедиционен документ е с по-малка тежест
        $reportClassId = pos_Reports::getClassId();
        while ($deltaRec = $deltaQuery->fetch()){
            $rating = ($deltaRec->detailClassId == $reportClassId) ? 150 : 1;
            $monthsBetween = countR(dt::getMonthsBetween($receiptRec->valior));
            $rating = $rating * round(12 / $monthsBetween);
            
            $index = "{$deltaRec->productId}|{$deltaRec->storeId}";
            sales_ProductRatings::addRatingToObject($res, $index, $classId, $objectClassId, $deltaRec->productId, $deltaRec->storeId, $rating);
        }
        
        $res = array_values($res);
        
        return $res;
    }


    /**
     * Връща планираните наличности
     *
     * @param stdClass $rec
     * @return array
     *       ['productId']        - ид на артикул
     *       ['storeId']          - ид на склад, или null, ако няма
     *       ['date']             - на коя дата
     *       ['quantityIn']       - к-во очаквано
     *       ['quantityOut']      - к-во за експедиране
     *       ['genericProductId'] - ид на генеричния артикул, ако има
     *       ['reffClassId']      - клас на обект (различен от този на източника)
     *       ['reffId']           - ид на обект (различен от този на източника)
     */
    public function getPlannedStocks($rec)
    {
        $id = is_object($rec) ? $rec->id : $rec;
        $rec = $this->fetch($id, '*', false);

        $dQuery = pos_ReceiptDetails::getQuery();
        $dQuery->EXT('generic', 'cat_Products', "externalName=generic,externalKey=productId");
        $dQuery->EXT('canConvert', 'cat_Products', "externalName=canConvert,externalKey=productId");
        $dQuery->where("#receiptId = {$rec->id} AND #action LIKE '%sale%'");

        $res = array();
        while($dRec = $dQuery->fetch()){
            $packRec = cat_products_Packagings::getPack($dRec->productId, $dRec->value);
            $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
            $quantity = $quantityInPack * $dRec->quantity;

            if(!empty($dRec->storeId)){
                $key = "{$dRec->storeId}|{$dRec->productId}";
                if(!array_key_exists($key, $res)){
                    $genericProductId = null;
                    if($dRec->generic == 'yes'){
                        $genericProductId = $dRec->productId;
                    } elseif($dRec->canConvert == 'yes'){
                        $genericProductId = planning_GenericMapper::fetchField("#productId = {$dRec->productId}", 'genericProductId');
                    }
                    $res[$key] = (object)array('storeId'          => $dRec->storeId,
                                               'productId'        => $dRec->productId,
                                               'date'             => $rec->valior,
                                               'quantityIn'       => null,
                                               'quantityOut'      => 0,
                                               'genericProductId' => $genericProductId);
                }
                $res[$key]->quantityOut += $quantity;
            }
        }

        return $res;
    }


    /**
     * ф-я връщаща най-голямото налично к-во в точката
     *
     * @param int $productId
     * @param int $pointId
     * @return double
     */
    public static function getBiggestQuantity($productId, $pointId)
    {
        $stores = pos_Points::getStores($pointId);
        $storeArr = store_Products::getQuantitiesByStore($productId, null, $stores);
        arsort($storeArr);

        return $storeArr[key($storeArr)];
    }
}
