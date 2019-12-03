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
    public $loadList = 'plg_Created, plg_Rejected, plg_Printing, acc_plg_DocumentSummary, plg_Printing,plg_State, bgerp_plg_Blank, pos_Wrapper, plg_Search, plg_Sorting,plg_Modified';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Бележка за продажба';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'createdOn, modifiedOn, valior, title=Бележка, pointId=Точка, contragentName, total, paid, change, state';
    
    
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
    public $canEdit = 'pos, ceo';
    
    
    /**
     *  Полета по които ще се търси
     */
    public $searchFields = 'contragentName';
    
    
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
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('valior', 'date(format=d.m)', 'caption=Дата,input=none');
        $this->FLD('pointId', 'key(mvc=pos_Points, select=name)', 'caption=Точка на продажба');
        $this->FLD('contragentName', 'varchar(255)', 'caption=Контрагент,input=none');
        $this->FLD('contragentObjectId', 'int', 'input=none');
        $this->FLD('contragentClass', 'key(mvc=core_Classes,select=name)', 'input=none');
        $this->FLD('total', 'double(decimals=2)', 'caption=Общо, input=none, value=0, summary=amount');
        $this->FLD('paid', 'double(decimals=2)', 'caption=Платено, input=none, value=0, summary=amount');
        $this->FLD('change', 'double(decimals=2)', 'caption=Ресто, input=none, value=0, summary=amount');
        $this->FLD('tax', 'double(decimals=2)', 'caption=Такса, input=none, value=0');
        $this->FLD(
                        'state',
                        'enum(draft=Чернова, active=Контиран, rejected=Оттеглен, closed=Затворен,waiting=Чакащ,pending)',
                        'caption=Статус, input=none'
                        );
        $this->FLD('transferedIn', 'key(mvc=sales_Sales)', 'input=none');
        $this->FLD('revertId', 'key(mvc=pos_Receipts)', 'input=none');
        
        $this->setDbIndex('valior');
    }
    
    
    /**
     *  Екшън създаващ нова бележка, и редиректващ към Единичния и изглед
     *  Добавянето на нова бележка става само през този екшън
     */
    public function act_New()
    {
        $cu = core_Users::getCurrent();
        $posId = pos_Points::getCurrent();
        $forced = Request::get('forced', 'int');
        
        // Ако форсираме, винаги създаваме нова бележка
        if ($forced) {
            $id = $this->createNew();
        } else {
            
            // Ако има чернова бележка от същия ден, не създаваме нова
            $today = dt::today();
            if (!$id = $this->fetchField("#valior = '{$today}' AND #createdBy = {$cu} AND #pointId = {$posId} AND #state = 'draft'", 'id')) {
                $id = $this->createNew();
            }
        }
        
        // Записваме, че потребителя е разглеждал този списък
        $this->logWrite('Отваряне на бележка в ПОС терминала', $id);
        Mode::setPermanent("currentOperation", 'add');
        Mode::setPermanent("currentSearchString", null);
        
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
        $defaultContragentId = pos_Points::defaultContragent($rec->pointId);
        $row->currency = acc_Periods::getBaseCurrencyCode($rec->createdOn);
        if(!($defaultContragentId == $rec->contragentObjectId && crm_Persons::getClassId() == $rec->contragentClass)){
            $Contragent = new core_ObjectReference($rec->contragentClass, $rec->contragentObjectId);
            $contragentFolderId = $Contragent->fetchField('folderId');
            $row->contragentId = (isset($contragentFolderId)) ? doc_Folders::recToVerbal($contragentFolderId)->title : $Contragent->getHyperlink(true);
        }
        
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
                $reportQuery->where("#state = 'active'");
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
        
        $row->RECEIPT_CAPTION = tr('КБ');
        $row->PAID_CAPTION = tr('Платено');
        $row->CHANGE_CAPTION = tr("Ресто");
        if($rec->change < 0){
            $row->CHANGE_CAPTION = tr("Остатък");
            $rec->change = abs($rec->change);
            $row->change = $mvc->getFieldType('change')->toVerbal(abs($rec->change));
        }
        
        foreach (array('total', 'paid', 'change') as $fld) {
            $row->{$fld} = ht::styleNumber($row->{$fld}, $rec->{$fld});
        }
        
        if (isset($rec->revertId)) {
            $row->PAID_CAPTION = tr('Върнато');
            $row->REVERT_CLASS = 'is-reverted';
            $row->revertId = pos_Receipts::getHyperlink($rec->revertId, true);
            if (isset($fields['-terminal'])) {
                $row->RECEIPT_CAPTION = tr('СБ');
                $row->loadUrl = ht::createLink('', array('pos_ReceiptDetails', 'load', 'receiptId' => $rec->id, 'from' => $rec->revertId, 'ret_url' => true), false, 'ef_icon=img/16/arrow_refresh.png,title=Зареждане на всички данни от бележката, class=load-btn');
            }
        }
        
        // Слагаме бутон за оттегляне ако имаме права
        if (!Mode::is('printing')) {
            if ($mvc->haveRightFor('reject', $rec)) {
                $row->rejectBtn = ht::createLink('', array($mvc, 'reject', $rec->id, 'ret_url' => toUrl(array($mvc, 'new'), 'local')), 'Наистина ли желаете да оттеглите документа?', 'ef_icon=img/16/reject.png,title=Оттегляне на бележката, class=reject-btn');
            } elseif ($mvc->haveRightFor('delete', $rec)) {
                $row->rejectBtn = ht::createLink('', array($mvc, 'delete', $rec->id, 'ret_url' => toUrl(array($mvc, 'new'), 'local')), 'Наистина ли желаете да изтриете документа?', 'ef_icon=img/16/delete.png,title=Изтриване на бележката, class=reject-btn');
            }
        }
        
        // показваме датата на последната модификация на документа, ако е активиран
        if ($rec->state != 'draft') {
            $row->valior = dt::mysql2verbal($rec->modifiedOn, 'd.m.Y H:i:s');
        }
        
        $cu = core_Users::fetch($rec->createdBy);
        $row->createdBy = ht::createLink(core_Users::recToVerbal($cu)->nick, crm_Profiles::getUrl($rec->createdBy));
        $row->pointId = pos_Points::getHyperLink($rec->pointId, true);
        $row->time = dt::mysql2verbal(dt::now(), 'H:i');
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
            $data->toolbar->addBtn('Нов запис', array($mvc, 'new'), 'id=btnAdd', 'ef_icon = img/16/star_2.png,title=Създаване на нов запис');
        }
    }
    
    
    /**
     * Извлича информацията за всички продукти които са продадени чрез
     * тази бележки, във вид подходящ за контирането
     *
     * @param int id - ид на бележката
     *
     * @return mixed $products - Масив от продукти
     */
    public static function getProducts($id)
    {
        expect($rec = static::fetch($id), 'Несъществуваща бележка');
        
        $products = array();
        
        $query = pos_ReceiptDetails::getQuery();
        $query->where("#receiptId = {$id}");
        $query->where('#quantity != 0');
        $query->where("#action LIKE '%sale%'");
        $query->orderBy('id', 'ASC');
        
        while ($rec = $query->fetch()) {
            $info = cat_Products::getProductInfo($rec->productId);
            $quantityInPack = ($info->packagings[$rec->value]) ? $info->packagings[$rec->value]->quantity : 1;
            
            $products[] = (object) array(
                'productId' => $rec->productId,
                'price' => $rec->price / $quantityInPack,
                'packagingId' => $rec->value,
                'vatPrice' => $rec->price * $rec->param,
                'discount' => $rec->discountPercent,
                'quantity' => $rec->quantity);
        }
        
        return $products;
    }
    
    
    /**
     * Ъпдейтване на бележката
     *
     * @param int $id - на бележката
     */
    public function updateReceipt($id)
    {
        expect($rec = $this->fetch($id));
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
            if ($rec->state != 'draft') {
                $res = 'no_one';
            } elseif (!pos_Points::haveRightFor('select', $rec->pointId)) {
                $res = 'no_one';
            }
        }
        
        // Никой не може да редактира бележка
        if ($action == 'edit') {
            $res = 'no_one';
        }
        
        // Никой не може да оттегли затворена бележка
        if ($action == 'reject' && isset($rec)) {
            $period = acc_Periods::fetchByDate($rec->valior);
            if ($rec->state == 'closed' || $period->state == 'closed') {
                $res = 'no_one';
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
        
        // Не могат да се възстановяват пранзи бележки
        if ($action == 'restore' && isset($rec)) {
            if ($rec->total == 0) {
                $res = 'no_one';
            }
        }
        
        // Може ли да бъде направено плащане по бележката
        if ($action == 'pay' && isset($rec)) {
            if (!$rec->total || ($rec->total && abs($rec->paid) >= abs($rec->total))) {
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
        $fields = array('shipmentStoreId' => $posRec->storeId, 'caseId' => $posRec->caseId, 'receiptId' => $rec->id);
        
        $products = $this->getProducts($rec->id);
        
        // Опитваме се да създадем чернова на нова продажба породена от бележката
        if ($sId = sales_Sales::createNewDraft($contragentClassId, $contragentId, $fields)) {
            
            // Намираме продуктите на бележката (трябва да има поне един)
            $products = $this->getProducts($rec->id);
            
            // За всеки продукт
            foreach ($products as $product) {
                
                // Намираме цената от ценовата политика
                $Policy = cls::get('price_ListToCustomers');
                $pInfo = $Policy->getPriceInfo($contragentClassId, $contragentId, $product->productId, $product->packagingId, $product->quantity);
                
                // Колко са двете цени с приспадната отстъпка
                $rPrice1 = $product->price * (1 - $product->discount);
                $rPrice2 = $pInfo->price * (1 - $pInfo->discount);
                
                // Оставяме по-малката цена
                if ($rPrice2 < $rPrice1) {
                    $product->price = $pInfo->price;
                    $product->discount = $pInfo->discount;
                }
                
                // Добавяме го като детайл на продажбата;
                sales_Sales::addRow($sId, $product->productId, $product->quantity, $product->price, $product->packagingId, $product->discount);
            }
        }
        
        // Отбелязваме къде е прехвърлена рецептата
        $rec->transferedIn = $sId;
        $rec->state = 'closed';
        $this->save($rec);
        core_Statuses::newStatus("|Бележка|* №{$rec->id} |е затворена|*");
        
        // Споделяме потребителя към нишката на създадената продажба
        $cu = core_Users::getCurrent();
        $sRec = sales_Sales::fetch($sId);
        doc_ThreadUsers::addShared($sRec->threadId, $sRec->containerId, $cu);
        
        // Редирект към новата бележка
        return new Redirect(array('sales_Sales', 'single', $sId), '|Успешно прехвърляне на бележката');
    }
    
    
    /**
     * Имплементиране на интерфейсен метод ( @see acc_TransactionSourceIntf )
     */
    public static function getLink($id)
    {
        return static::recToVerbal(static::fetchRec($id), 'id,title,-list')->title;
    }
    
    
    /**
     * Метод по подразбиране на canActivate
     */
    public static function canActivate($rec)
    {
        if (empty($rec->id) && $rec->state != 'draft' && ($rec->total == 0 || $rec->paid < $rec->total)) {
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Проверка на количеството
     *
     * @param stdClass $rec
     * @param string   $error
     *
     * @return bool
     */
    public static function checkQuantity($rec, &$error)
    {
        // Ако е забранено продаването на неналични артикули да се проверява
        $notInStockChosen = pos_Setup::get('ALLOW_SALE_OF_PRODUCTS_NOT_IN_STOCK');
        if ($notInStockChosen == 'yes') {
            
            return true;
        }
        
        $pointId = self::fetchField($rec->receiptId, 'pointId');
        $quantityInStock = pos_Stocks::getQuantity($rec->productId, $pointId);
        
        $pRec = cat_products_Packagings::getPack($rec->productId, $rec->value);
        $quantityInPack = ($pRec) ? $pRec->quantity : 1;
        $quantityInStock -= $rec->quantity * $quantityInPack;
        
        if ($quantityInStock < 0) {
            $error = 'Желаното количество не е налично';
            
            return false;
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
        
        $rec->state = 'waiting';
        $rec->__closed = true;
        if ($this->save($rec)) {
            
            // Обновяваме складовите наличности
            pos_Stocks::updateStocks($rec->id);
        }
        
        // Създаване на нова чернова бележка
        return new Redirect(array($this, 'new'));
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
        
        $query = $this->getQuery();
        $query->where("#pointId = {$data->masterId}");
        $query->where("#state = 'waiting' OR #state = 'draft'");
        $query->orderBy('#state');
        if ($count = $query->count()) {
            $data->count = core_Type::getByName('int')->toVerbal($count);
        }
        $conf = core_Packs::getConfig('pos');
        
        while ($rec = $query->fetch()) {
            $num = substr($rec->id, -1 * $conf->POS_SHOW_RECEIPT_DIGITS);
            $stateClass = ($rec->state == 'draft') ? 'state-draft' : 'state-waiting';
            $num = (isset($rec->revertId)) ? "<span class='red'>{$num}</span>" : $num;
            $borderColor = (isset($rec->revertId)) ? 'red' : '#a6a8a7';
            
            if (!Mode::isReadOnly()) {
                if ($this->haveRightFor('terminal', $rec)) {
                    $num = ht::createLink($num, array('pos_Terminal', 'open', 'receiptId' => $rec->id), false, 'title=Довършване на бележката,ef_icon=img/16/cash-register.png');
                } elseif ($this->haveRightFor('single', $rec)) {
                    $num = ht::createLink($num, array($this, 'single', $rec->id), false, "title=Преглед на бележка №{$rec->id},ef_icon=img/16/view.png");
                }
            }
            
            if ($rec->state == 'draft') {
                if ($rec->total != 0) {
                    $num = ht::createHint($num, 'Бележката е започната, но не е приключена', 'warning', false);
                }
            }
            $num = " <span class='open-note {$stateClass}' style='border:1px solid {$borderColor}'>{$num}</span>";
            
            $data->rows[$rec->id] = $num;
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
        $tpl = new ET('');
        $str = implode('', $data->rows);
        $tpl->append($str);
        $tpl->replace($data->count, 'waitingCount');
        
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
        
        //$foundArr = $this->findReceiptByNumber($id, true);
        if (!is_object($foundArr['rec'])) {
            //core_Statuses::newStatus($foundArr['notFoundError'], 'error');
            
            //return followRetUrl();
        }
        
        $newReceiptId = $this->createNew($id);
        
        Mode::setPermanent("currentOperation", 'add');
        Mode::setPermanent("currentSearchString", null);
        
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
            if ($res['rec']->pointId != pos_Points::getCurrent()) {
                $res['notFoundError'] = '|Може да бъде сторнира само бележка от същия POS|*!';
                $res['rec'] = false;
            } elseif ($forRevert === true) {
                if (self::fetchField("#revertId = {$res['rec']->id}")) {
                    $res['notFoundError'] = '|Има вече създадена бележка, сторнираща търсената|*!';
                    $res['rec'] = false;
                } elseif (self::fetchField("#id = {$res['rec']->id} AND #revertId IS NOT NULL")) {
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
        
        $rec->contragentName = cls::get($rec->contragentClass)->getVerbal($rec->contragentObjectId, 'name');
        
        $this->save($rec, 'contragentObjectId,contragentClass');
        
        followRetUrl();
    }
}
