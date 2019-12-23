<?php


/**
 * Ценови политики към клиенти
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Ценови политики към клиенти
 */
class price_ListToCustomers extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Ценови политики към клиенти';
    
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Ценова политика';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, price_Wrapper, plg_RowTools2';
    
    
    /**
     * Интерфейс за ценова политика
     */
    public $interfaces = 'price_PolicyIntf';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'listId=Политика, cClass=Контрагент, validFrom=В сила от, createdBy=Създаване->От, createdOn=Създаване->На,state=Състояние';
    
    
    /**
     * Кой може да го промени?
     */
    public $canEdit = 'ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'price,sales,ceo';
    
    
    /**
     * Кой има право да листва?
     */
    public $canList = 'price,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'price,sales,ceo';
    
    
    /**
     * Предлог в формата за добавяне/редактиране
     */
    public $formTitlePreposition = 'за';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('listId', 'key(mvc=price_Lists,select=title)', 'caption=Политика');
        $this->FLD('cClass', 'class(select=title,interface=crm_ContragentAccRegIntf)', 'caption=Клиент->Клас,input=hidden,silent');
        $this->FLD('cId', 'int', 'caption=Клиент->Обект,input=hidden,silent');
        $this->FLD('validFrom', 'datetime(format=smartTime)', 'caption=В сила от');
        $this->FLD('state', 'enum(closed=Неактивен,active=Активен)', 'caption=Състояние,input=none');
        $this->EXT('listState', 'price_Lists', 'externalName=state,externalKey=listId');
        
        $this->setDbIndex('cClass,cId');
        $this->setDbIndex('state');
        $this->setDbIndex('listId');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            
            $now = dt::verbal2mysql();
            
            if (!$rec->validFrom) {
                $rec->validFrom = $now;
            }
            
            if ($rec->validFrom && !$form->gotErrors() && $rec->validFrom > $now) {
                Mode::setPermanent('PRICE_VALID_FROM', $rec->validFrom);
            }
        }
    }
    
    
    /**
     * Подготвя формата за въвеждане на ценови правила за клиент
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $rec = $data->form->rec;
        
        if (!$rec->id) {
            $rec->validFrom = Mode::get('PRICE_VALID_FROM');
        }
        
        $rec->listId = self::getListForCustomer($rec->cClass, $rec->cId);
        
        $data->form->setOptions('listId', price_Lists::getAccessibleOptions($rec->cClass, $rec->cId));
        
        if (price_Lists::haveRightFor('add', (object) array('cClass' => $rec->cClass, 'cId' => $rec->cId))) {
            $data->form->toolbar->addBtn('Нови правила', array('price_Lists', 'add', 'cClass' => $rec->cClass, 'cId' => $rec->cId, 'ret_url' => true), null, 'order=10.00015,ef_icon=img/16/page_white_star.png');
        }
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $rec = $data->form->rec;
        if (isset($rec->cClass, $rec->cId)) {
            $data->form->title = core_Detail::getEditTitle($rec->cClass, $rec->cId, $mvc->singleTitle, $rec->id, $mvc->formTitlePreposition);
        }
    }
    
    
    /**
     * Връща актуалния към посочената дата набор от ценови правила за посочения клиент
     */
    private static function getValidRec($customerClassId, $customerId, $datetime = null)
    {
        $datetime = (isset($datetime)) ? $datetime : dt::verbal2mysql();
        
        $query = self::getQuery();
        $query->where("#cClass = {$customerClassId} AND #cId = {$customerId}");
        $query->where("#validFrom <= '{$datetime}'");
        $query->where("#listState != 'rejected'");
        
        $query->limit(1);
        $query->orderBy('#validFrom,#id', 'DESC');
        $lRec = $query->fetch();
        
        return $lRec;
    }
    
    
    /**
     * Задава ценова политика за определен клиент
     */
    public static function setPolicyTocustomer($policyId, $cClass, $cId, $datetime = null)
    {
        if (!$datetime) {
            $datetime = dt::verbal2mysql();
        }
        
        $rec = new stdClass();
        $rec->cClass = $cClass;
        $rec->cId = $cId;
        $rec->validFrom = $datetime;
        $rec->listId = $policyId;
        
        self::save($rec);
    }
    
    
    /**
     * Подготвя ценоразписите на даден клиент
     */
    public function preparePricelists($data)
    {
        $data->TabCaption = 'Цени';
        
        $data->recs = $data->rows = array();
        $query = self::getQuery();
        $query->where("#listState != 'rejected'");
        $query->where("#cClass={$data->masterMvc->getClassId()} AND #cId = {$data->masterId}");
        $query->orderBy('#validFrom,#id', 'DESC');
        
        while ($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = self::recToVerbal($rec);
        }
        
        if (!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf')) {
            if ($data->masterMvc->haveRightFor('edit', $data->masterData->rec)) {
                if ($this->haveRightFor('add')) {
                    $data->addUrl = array($this, 'add', 'cClass' => $data->masterMvc->getClassId(), 'cId' => $data->masterId, 'ret_url' => true);
                }
            }
        }
    }
    
    
    /**
     * Рендиране на ценоразписите на клиента
     */
    public function renderPricelists($data)
    {
        $tpl = new core_ET('');
        $data->listFields = arr::make('listId=Политика,validFrom=В сила от,created=Създаване,state=Състояние', true);
        
        $table = cls::get('core_TableView', array('mvc' => $this));
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        $tpl->append(tr('Ценови политики'), 'priceListTitle');
        $tpl->append($table->get($data->rows, $data->listFields));
        
        if ($data->addUrl && !Mode::is('text', 'xhtml') && !Mode::is('printing')) {
            $addBtn = ht::createLink('', $data->addUrl, null, array('ef_icon' => 'img/16/add.png', 'class' => 'addSalecond', 'title' => 'Избор на ценова политика'));
            $tpl->append($addBtn, 'priceListTitle');
        }
        
        return $tpl;
    }
    
    
    /**
     * След запис в модела
     */
    protected static function on_AfterSave($mvc, &$id, &$rec, $fields = null)
    {
        // Ако ценовата политика е бъдеща задаваме
        if ($rec->validFrom > dt::now()) {
            core_CallOnTime::setOnce($mvc->className, 'updateStates', (object) array('cClass' => $rec->cClass, 'cId' => $rec->cId, 'validFrom' => $rec->validFrom), $rec->validFrom);
        }
        
        static::updateStates($rec->cClass, $rec->cId);
    }
    
    
    /**
     * Връща валидните ценови правила за посочения клиент
     */
    public static function getListForCustomer($customerClass, $customerId, &$datetime = null)
    {
        $datetime = static::canonizeTime($datetime);
        
        $validRec = self::getValidRec($customerClass, $customerId, $datetime);
        $listId = ($validRec) ? $validRec->listId : cat_Setup::get('DEFAULT_PRICELIST');
        
        return $listId;
    }
    
    
    /**
     * Връща цената за посочения продукт към посочения клиент на посочената дата
     *
     * @param mixed        $customerClass       - клас на контрагента
     * @param int          $customerId          - ид на контрагента
     * @param int          $productId           - ид на артикула
     * @param int          $packagingId         - ид на опаковка
     * @param float        $quantity            - количество
     * @param datetime     $datetime            - дата
     * @param float        $rate                - валутен курс
     * @param string       $chargeVat           - начин на начисляване на ддс
     * @param int|NULL     $listId              - ценова политика
     * @param bool         $quotationPriceFirst - дали първо да търси цена от последна оферта
     *
     * @return stdClass $rec->price  - цена
     *                  $rec->discount - отстъпка
     */
    public function getPriceInfo($customerClass, $customerId, $productId, $packagingId = null, $quantity = null, $datetime = null, $rate = 1, $chargeVat = 'no', $listId = null, $quotationPriceFirst = true)
    {
        $rec = (object) array('price' => null);
        $productRec = cat_Products::fetch($productId, 'isPublic,proto');
        
        // Проверява се имали последна цена по оферта
        if ($quotationPriceFirst === true) {
            $rec = sales_QuotationsDetails::getPriceInfo($customerClass, $customerId, $datetime, $productId, $packagingId, $quantity);
        }
        
        // Ако няма цена по оферта или не се изисква
        if (empty($rec->price)) {
            $listId = (isset($listId)) ? $listId : self::getListForCustomer($customerClass, $customerId, $datetime);
            
            // Проверяваме дали артикула е частен или стандартен
            if ($productRec->isPublic == 'no') {
                $rec = (object) array('price' => null);
                $deltas = price_ListToCustomers::getMinAndMaxDelta($customerClass, $customerId, $listId);
                
                // Ако драйвера може да върне цена, връщаме нея
                if ($Driver = cat_Products::getDriver($productId)) {
                    $rec = $Driver->getPrice($productId, $quantity, $deltas->minDelta, $deltas->maxDelta, $datetime, $rate, $chargeVat, $listId);
                    $rec = is_object($rec) ? $rec : (object)array('price' => $rec);
                    
                    // @TODO хак за закръгляне на цените
                    if (isset($rec->price) && $rate > 0) {
                        $newPrice = $rec->price / $rate;
                        if ($chargeVat == 'yes') {
                            $vat = cat_Products::getVat($productId, $datetime);
                            $newPrice = $newPrice * (1 + $vat);
                        }
                        $newPrice = round($newPrice, 4);
                        
                        if ($chargeVat == 'yes') {
                            $newPrice = $newPrice / (1 + $vat);
                        }
                        
                        $newPrice *= $rate;
                        $rec->price = $newPrice;
                    }
                }
            } else {
                
                // За стандартните артикули се търси себестойността в ценовите политики
                $rec = $this->getPriceByList($listId, $productId, $packagingId, $quantity, $datetime, $rate, $chargeVat);
            }
        }
        
        // Ако все още няма цена, но има прототип проверява се има ли цена по политика за прототипа, използва се тя
        if(is_null($rec->price) && isset($productRec->proto)){
            $rec = $this->getPriceByList($listId, $productRec->proto, $packagingId, $quantity, $datetime, $rate, $chargeVat);
        }
        
        // Обръщаме цената във валута с ДДС ако е зададено и се закръгля спрямо ценоразписа
        if (!is_null($rec->price)) {
            $vat = cat_Products::getVat($productId);
            $rec->price = deals_Helper::getDisplayPrice($rec->price, $vat, $rate, $chargeVat);
        }
        
        // Връщане на цената
        return $rec;
    }
    
    
    /**
     * Връща минималната отстъпка и максималната надценка за даден контрагент
     *
     * @param mixed $customerClass  - ид на клас на контрагента
     * @param int   $customerId     - ид на контрагента
     * @param int   $defPriceListId - ценоразпис
     *
     * @return object $res		   - масив с надценката и отстъпката
     *                o minDelta  - минималната отстъпка
     *                o maxDelta  - максималната надценка
     */
    public static function getMinAndMaxDelta($customerClass, $customerId, $defPriceListId)
    {
        $res = (object) array('minDelta' => 0, 'maxDelta' => 0);
        
        // Ако контрагента има зададен ценоразпис, който не е дефолтния
        if ($defPriceListId != price_ListRules::PRICE_LIST_CATALOG) {
            
            // Взимаме максималната и минималната надценка от него, ако ги има
            $defPriceList = price_Lists::fetch($defPriceListId);
            $res->minDelta = $defPriceList->minSurcharge;
            $res->maxDelta = $defPriceList->maxSurcharge;
        }
        
        // Ако няма мин надценка, взимаме я от търговските условия
        if (!$res->minDelta) {
            $res->minDelta = cond_Parameters::getParameter($customerClass, $customerId, 'minSurplusCharge');
        }
        
        // Ако няма макс надценка, взимаме я от търговските условия
        if (!$res->maxDelta) {
            $res->maxDelta = cond_Parameters::getParameter($customerClass, $customerId, 'maxSurplusCharge');
        }
        
        return $res;
    }
    
    
    /**
     * Опит за намиране на цената според политиката за клиента (ако има такава)
     */
    public function getPriceByList($listId, $productId, $packagingId = null, $quantity = null, $datetime = null, $rate = 1, $chargeVat = 'no')
    {
        $rec = new stdClass();
        $rec->price = price_ListRules::getPrice($listId, $productId, $packagingId, $datetime);
        
        $listRec = price_Lists::fetch($listId);
        
        // Ако е избрано да се връща отстъпката спрямо друга политика
        if (!empty($listRec->discountCompared)) {
            
            // Намираме цената по тази политика и намираме колко % е отстъпката/надценката
            $comparePrice = price_ListRules::getPrice($listRec->discountCompared, $productId, $packagingId, $datetime);
            
            if ($comparePrice && isset($rec->price)) {
                $disc = ($rec->price - $comparePrice) / $comparePrice;
                $discount = round(-1 * $disc, 4);
                
                // Ще показваме цената без отстъпка и отстъпката само ако отстъпката е положителна
                // Целта е да не показваме надценката а само отстъпката
                if ($discount > 0) {
                    
                    // Подменяме цената за да може като се приспадне отстъпката и, да се получи толкова колкото тя е била
                    $rec->discount = round(-1 * $disc, 4);
                    $rec->price = $comparePrice;
                }
            }
        }
        
        return $rec;
    }
    
    
    /**
     * Помощна функция, добавяща 23:59:59 ако е зададена дата без час
     */
    public static function canonizeTime($datetime = null)
    {
        if (!$datetime) {
            $datetime = dt::now(false);
        }
        
        if (strlen($datetime) == 10) {
            $datetime .= ' 23:59:59';
        }
        
        return $datetime;
    }
    
    
    /**
     * Ф-я викаща се по разписание
     *
     * @see core_CallOnTime
     *
     * @param stdClass $data
     */
    public function callback_updateStates($data)
    {
        $this->updateStates($data->cClass, $data->cId);
    }
    
    
    /**
     * Обновяване на състоянието на контрагентски рецепти
     *
     * @param int $cClass - клас на контрагента
     * @param int $cId    - клас Ид
     */
    public static function updateStates($cClass = null, $cId = null)
    {
        $self = cls::get(get_called_class());
        $query = self::getQuery();
        
        $query->where('#cClass IS NOT NULL AND #cId IS NOT NULL');
        if (isset($cClass, $cId)) {
            $query->where("#cClass = {$cClass} AND #cId = {$cId}");
        }
        
        $count = $query->count();
        if ($count > 200) {
            core_App::setTimeLimit($count * 0.7);
        }
        
        $recsToSave = array();
        $cache = array();
        while ($rec = $query->fetch()) {
            $state = 'closed';
            
            $index = "{$rec->cClass}|{$rec->cId}";
            if (!array_key_exists($index, $cache)) {
                $cache[$index] = self::getValidRec($rec->cClass, $rec->cId);
            }
            $aRec = $cache[$index];
            
            if (!empty($aRec) && $rec->id == $aRec->id) {
                $state = 'active';
            }
            
            if ($rec->state != $state) {
                $recsToSave[] = (object) array('id' => $rec->id, 'state' => $state);
            }
        }
        
        $self->saveArray($recsToSave, 'id,state');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {   
        if($rec->cId) {
            $row->cClass = cls::get($rec->cClass)->getHyperlink($rec->cId, true);
            if ($rec->validFrom > dt::now()) {
                $rec->state = 'draft';
                $row->state = tr('Бъдещ');
            }
        }
        
        $row->ROW_ATTR['class'] = "state-{$rec->state}";
        $row->listId = price_Lists::getHyperlink($rec->listId, true);
        $row->created = tr("|на|* {$row->createdOn} |от|* {$row->createdBy}");
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $listId = Request::get('listId', 'key(mvc=price_Lists)');
        if (isset($listId)) {
            $data->query->where("#listId = {$listId}");
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на листовия изглед
     */
    protected static function on_AfterPrepareListTitle($mvc, &$res, $data)
    {
        $listId = Request::get('listId', 'key(mvc=price_Lists)');
        if (isset($listId)) {
            $data->title = 'Ценова политика|* ' . price_Lists::getHyperlink($listId, true);
        }
    }
    
    
    /**
     * Връща масив с контрагентите свързани към даден ценоразпис
     *
     * @param int  $listId - ид на политика
     * @param bool $links  - дали имената на контрагентите да са линк
     *
     * @return array $options - масив със свързаните контрагенти
     */
    public static function getCustomers($listId, $links = false)
    {
        $options = array();
        
        $query = price_ListToCustomers::getQuery();
        $query->where("#listId = {$listId} AND #state = 'active'");
        $count = $query->count();
        if (!empty($count)) {
            while ($rec = $query->fetch()) {
                $title = ($links === true) ? cls::get($rec->cClass)->getHyperlink($rec->cId, true) : cls::get($rec->cClass)->getTitleById($rec->cId, false);
                $options[$rec->id] = $title;
            }
        }
        
        return $options;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'delete' && isset($rec)) {
            if ($rec->validFrom <= dt::now()) {
                $requiredRoles = 'no_one';
            }
        }
        
        if (($action == 'add' || $action == 'delete') && isset($rec)) {
            if (!cls::get($rec->cClass)->haveRightFor('single', $rec->cId)) {
                $requiredRoles = 'no_one';
            }
        }
    }
}
