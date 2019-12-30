<?php


/**
 * Палети
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Milen Georgiev <milen@experta.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_Pallets extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Палети';
    
    
    /**
     * Еденично заглавие
     */
    public $singleTitle = 'Палет';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, rack_Wrapper,recently_Plugin,plg_Sorting, plg_Search';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,rack';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,rack';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кои полета ще се виждат в листовия изглед
     */
    public $listFields = 'label,position,productId,batch=Партида,uom=Мярка,quantity,closedOn';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'closedOn,batch';
    
    
    /**
     * Колко време след като са затворени палетите да се изтриват
     */
    const DELETE_CLOSED_PALLETS_OLDER_THAN = 5184000;
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'barcode_SearchIntf';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'position,batch,productId,comment';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад,input=none,mandatory');
        $this->FLD('rackId', 'key(mvc=rack_Racks,select=num)', 'caption=Стелаж,input=none');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,allowEmpty,selectSourceArr=rack_Products::getStorableProducts,forceAjax)', 'caption=Артикул,mandatory,tdClass=productCell');
        $this->FLD('quantity', 'double(smartRound,decimals=3)', 'caption=Количество,mandatory,smartCenter,input=none');
        $this->FLD('batch', 'text', 'smartCenter');
        $this->FLD('label', 'varchar(32)', 'caption=Палет,tdClass=rightCol,smartCenter');
        $this->FLD('comment', 'varchar', 'caption=Коментар,column=none');
        $this->FLD('position', 'rack_PositionType', 'caption=Позиция,smartCenter,input=none,after=productId');
        $this->FLD('state', 'enum(active=Активно,closed=Затворено)', 'caption=Състояние,input=none,notNull,value=active');
        $this->FLD('closedOn', 'datetime(format=smartTime)', 'caption=Затворено на,input=none');
        
        $this->setDbIndex('productId');
        $this->setDbIndex('productId,storeId');
        $this->setDbIndex('state');
        $this->setDbIndex('storeId');
        $this->setDbIndex('position');
    }
    
    
    /**
     * Връща наличните палети за артикула
     *
     * @param int  $productId               - ид на артикул
     * @param int  $storeId                 - ид на склад
     * @param mixed $batch                  - null за всички партиди, стринг за конкретна (включително и без партида)
     * @param bool $withoutPendingMovements - към които да има или няма чакащи движения
     *
     * @return array $pallets - масив с палети
     */
    public static function getAvailablePallets($productId, $storeId, $batch = null, $withoutPendingMovements = false)
    {
        $pallets = array();
        $query = self::getQuery();
        $query->where("#productId = {$productId} AND #storeId = {$storeId} AND #state != 'closed'");
        $query->show('quantity,position');
        if(!is_null($batch)){
            $query->where("#batch = '{$batch}'");
        }
       
        $query->orderBy('createdOn', 'ASC');
        while ($rec = $query->fetch()) {
            $rest = $rec->quantity;
            
            // Ако се изискват само палети, към които няма чакащи движения, другите се пропускат
            if ($withoutPendingMovements === true) {
                
                // Палет, от който има неприключено движение не се изключва автоматично от подаваните, а се сумират количествата
                // на всички неприключени движения насочени от него, и ако въпросната сума е по-малка от наличното на палета
                // количество, той се подава на функцията, с остатъчното количество.
                $sum = null;
                $mQuery = rack_Movements::getQuery();
                $mQuery->XPR('sum', 'double', 'ROUND(#quantity, 2)');
                $mQuery->where("#palletId = {$rec->id} AND #state = 'pending'");
                while($mRec = $mQuery->fetch()){
                    $zones = type_Table::toArray($mRec->zones);
                    if(count($zones)){
                        array_filter($zones, function($a) use (&$sum){$sum += $a->quantity;});
                    }
                }
                
                
                if(isset($sum) && $sum >= $rec->quantity){
                    continue;
                }
                
                $rest = $rec->quantity - $sum;
            }
            
            // разликата
            $pallets[$rec->id] = (object) array('quantity' => $rest, 'position' => $rec->position);
        }
        
        return $pallets;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        
        $form->setReadOnly('productId');
        $form->setField('position', 'input');
        $form->setReadOnly('position');
    }
    
    
    /**
     * Връща най-добрата позиция за разполагане на дадения продукт
     */
    public static function getBestPos($productId, $storeId = null)
    {
        if (!$storeId) {
            $storeId = store_Stores::getCurrent();
        }
        
        list($unusable, $reserved) = rack_RackDetails::getunUsableAndReserved();
        $used = rack_Pallets::getUsed();
        list(, $movedTo) = rack_Movements::getExpected();
        
        // Ако намерим палет с този продукт и свободно място към края на стелажа - вземаме него
        $haveInRack = $nearProds = array();
        $inFirstRow = 0;
        foreach ($used as $pos => $pRec) {
            if ($productId != $pRec->productId) {
                continue;
            }
            
            list($n, $r, ) = rack_PositionType::toArray($pos);
            
            if($r == 'A') {
                $inFirstRow++;
            }
            $haveInRack[$n] = $n;
        }
        
        // Търсим най-доброто място
        $rQuery = rack_Racks::getQuery();
        $bestScore = 0;
        $bestPos = '';
        
        if(isset($pRec->productId)){
            $nearProds[$pRec->productId] = 1;
            $relData = sales_ProductRelations::fetchField("#productId = {$pRec->productId}", 'data');
        }
        
        if(is_array($relData)) {
            $i = 2;
            foreach($relData as $npId => $m) {
                $nearProds[$npId] = 1/$i;
                $i++;
            }
        }
        
        while ($rRec = $rQuery->fetch("#storeId = {$storeId}")) {
            for ($cInd = 1; $cInd <= $rRec->columns; $cInd++) {
                for ($rInd = 'A'; $rInd <= $rRec->rows; $rInd++) {
                    
                    $pos = "{$rRec->num}-{$rInd}-{$cInd}";
                    
                    if ($used[$pos] || $unusable[$pos] || ($reserved[$pos] && $reserved[$pos] != $pRec->productId) || $movedTo[$pos]) {
                        continue;
                    }
                    
                    $score = 0;
                    
                    $posUp = "{$rRec->num}-" . chr(ord($rInd)+1) . "-{$cInd}";
                    $posDw = "{$rRec->num}-" . chr(ord($rInd)-1) . "-{$cInd}";
                    $posLf = "{$rRec->num}-{$rInd}-" . ($cInd - 1);
                    $posRg = "{$rRec->num}-{$rInd}-" . ($cInd + 1);
                    
                    // Ако продукта се съдържа в стелажа
                    if($haveInRack[$rRec->num]) {
                        $score += 0.2;
                    }
                    
                    // Ако имаме резервирана позиция за този продукт
                    if($reserved[$pos] == $pRec->productId) {
                        $score += 6;
                    }
                    
                    // Ако нямаме достатъчно на ниска позиция
                    if($rInd == 'A') {
                        if($inFirstRow < 1) {
                            $score += 5;
                        } else {
                            $score -= 2;
                        }
                    }
                    
                    // По-ниското е по-добре
                    $score += 1 - (ord($rInd) - ord('A'))/10;
                    
                    // Ако горния или долния са от този продукт
                    if($used[$posUp] == $pRec->productId) {
                        $score += 3;
                    }
                    
                    if($used[$posDw] == $pRec->productId) {
                        $score += 3.5;
                    }
                    
                    // Ако левия или десния са от този продукт или близки на него
                    if($weight = $nearProds[$used[$posRg]->productId]) {
                        $score += $weight;
                    }
                    
                    if($weight = $nearProds[$used[$posLf]->productId]) {
                        $score += 1.2 * $weight;
                    }
                    
                    // Отделяме най-добрият резултат
                    if ($score > $bestScore) {
                        $bestPos = $pos;
                        $bestScore = $score;
                    }
                }
            }
        }
        
        return $bestPos;
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc     $mvc    Мениджър, в който възниква събитието
     * @param int          $id     Първичния ключ на направения запис
     * @param stdClass     $rec    Всички полета, които току-що са били записани
     * @param string|array $fields Имена на полетата, които sa записани
     * @param string       $mode   Режим на записа: replace, ignore
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        $updateFields = array();
        $saveAgain = false;
        
        // Затваряне ако количеството е 0
        if (round($rec->quantity, 5) <= 0) {
            $rec->state = 'closed';
            $rec->closedOn = dt::now();
            $saveAgain = true;
            $updateFields['state'] = 'state';
            $updateFields['closedOn'] = 'closedOn';
        }
        
        // Ако няма етикет се задава
        if (empty($rec->label)) {
            $rec->label = '#' . $rec->id;
            $saveAgain = true;
            $updateFields['label'] = 'label';
        }
        
        // Ако има полета за обновяване, обновяват се
        if ($saveAgain === true) {
            $mvc->save_($rec, $updateFields);
        }
        
        self::recalc($rec->productId, $rec->storeId);
        core_Cache::remove('UsedRacksPossitions', $rec->storeId);
    }


    /**
     * Добавя филтър към перата
     *
     * @param acc_Items $mvc
     * @param stdClass  $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $storeId = store_Stores::getCurrent();
        $data->title = 'Палетизирани наличности в склад|* <b style="color:green">' . store_Stores::getHyperlink($storeId, true) . '</b>';
        $data->query->where("#storeId = {$storeId}");
        $data->query->orderBy('state', 'ASC');
        
        $data->listFilter->setFieldType('productId', 'key2(mvc=cat_Products,select=name,allowEmpty,selectSourceArr=rack_Products::getStorableProducts)');
        $data->listFilter->FLD('stateFilter', 'enum(,active=Активни,closed=Затворено)', 'caption=Всички,silent');
        $data->listFilter->setDefault('stateFilter', 'active');
        
        $data->listFilter->showFields = 'productId,search,stateFilter';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $rec = $data->listFilter->input();
        if (!$rec->productId) {
            $rec->productId = Request::get('productId', 'int');
            $data->listFilter->setDefault('productId', $rec->productId);
        }
        if ($rec->productId) {
            $data->query->where("#productId = {$rec->productId}");
            if (!Request::get('Sort')) {
                $data->query->orderBy('position', 'ASC');
                $order = true;
            }
        }
        
        if (!empty($rec->stateFilter)) {
            $data->query->where("#state = '{$rec->stateFilter}'");
        }
        
        if (!$order) {
            $data->query->orderBy('#createdOn', 'DESC');
        }
    }
    
    
    /**
     * Увеличава/намалява к-то в палета, ако няма палет създава нов
     *
     * @param int  $productId - ид на артикул
     * @param int  $storeId   - ид на склад
     * @param int  $position  - на коя позиция?
     * @param int  $quantity  - количество от основната мярка в палета
     * @param bool $batch    - партида ако има
     *
     * @return stdClass $rec  - записа на палета
     */
    public static function increment($productId, $storeId, $position, $quantity, $batch)
    {
        // Ако няма палет се създава нов
        $rec = self::fetch(array("#position = '[#1#]' AND #storeId = {$storeId} AND #state != 'closed'", $position));
        if (empty($rec)) {
            $rec = self::create($productId, $storeId, $quantity, $position, $batch);
        } else {
            
            // Ако има променя му се количеството
            expect($rec->productId == $productId, 'Артикулът е различен');
            expect($rec->storeId == $storeId, 'Склада е различен');
            
            $incrementQuantity = $quantity;
            $rec->quantity += $incrementQuantity;
            $rec->quantity = round($rec->quantity, 5);
            
            self::save($rec, 'position,quantity,state,closedOn');
        }
        
        return $rec->id;
    }
    
    
    /**
     * Създаване на нов палет
     *
     * @param int $productId - ид на артикул
     * @param int $storeId   - ид на склад
     * @param int $quantity  - количество от основната мярка в палета
     * @param int $position  - на коя позиция?
     * @param int $batch     - партида
     * @param int $label     - етикет
     *
     * @return stdClass $rec - записа на палета
     */
    public static function create($productId, $storeId, $quantity, $position, $batch = null, $label = null)
    {
        expect(rack_Racks::isPlaceUsable($position, $productId, $storeId, $batch, $error), $error);
        $rec = (object) array('productId' => $productId, 'storeId' => $storeId, 'label' => $label, 'position' => $position, 'quantity' => $quantity, 'state' => 'active', 'batch' => $batch);
        
        list($num, , ) = rack_PositionType::toArray($rec->position);
        $rRec = rack_Racks::getByNum($num);
        $rec->rackId = $rRec->id;
        
        self::save($rec);
        
        return $rec;
    }
    
    
    /**
     * Преизчислява наличността на палети за посочения продукт
     */
    public static function recalc($productId, $storeId, $save = true)
    {
        $query = self::getQuery();
        $query->where("#productId = {$productId} AND #storeId = {$storeId} AND #state != 'closed'");
        $query->XPR('sum', 'double', 'SUM(#quantity)');
        $query->show('sum,productId,storeId');
        $sum = $query->fetch()->sum;
        $sum = ($sum) ? $sum : null;
        
        $rRec = rack_Products::fetch("#productId = {$productId} AND #storeId = {$storeId}", 'id,quantityOnPallets');
        if (!$rRec) {
            $rRec = (object) array('storeId' => $storeId, 'productId' => $productId, 'state' => 'active', 'quantity' => 0, 'quantityOnPallets' => $sum);
        } else {
            $rRec->quantityOnPallets = $sum;
            $rRec->state = 'active';
        }
        
        if ($save === true) {
            rack_Products::save($rRec);
        }
        
        return $rRec;
    }
    
    
    /**
     * Проверява дали указаната позиция е празна
     */
    public static function isEmpty($productId, $position, $storeId = null, &$error = null)
    {
        expect($position);
        $storeId = isset($storeId) ? $storeId : store_Stores::getCurrent();
        
        if ($rec = self::fetch("#storeId = {$storeId} AND #position = '{$position}' AND #productId != '{$productId}' AND #state = 'active'")) {
            $prodTitle = cat_Products::getTitleById($rec->productId);
            $error = "Тази позиция е заета от артикул|*: <b>{$prodTitle}</b>";
            
            return false;
        }
        
        if ($mRec = rack_Movements::fetch("#storeId = {$storeId} AND #positionTo = '{$position}' AND #productId != '{$productId}' AND #state != 'closed'")) {
            $prodTitle = cat_Products::getTitleById($mRec->productId);
            $error = "Към тази позиция има насочено движение от друг артикул|*: {$prodTitle}";
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Проверява дали указаната позиция е празна
     */
    public static function isEmptyOut($num, $row = null, $col = null, $storeId = null, &$error = null)
    {
        if (!$row) {
            $row = chr(ord('A') - 1);
        }
        
        if (!$col) {
            $col = 0;
        }
        
        if (!$storeId) {
            $storeId = store_Stores::getCurrent();
        }
        
        $query = self::getQuery();
        
        while ($rec = $query->fetch("#storeId = {$storeId} AND #position LIKE '{$num}-%' AND #state != 'closed'")) {
            if (!$rec->position) {
                continue;
            }
            
            list($n, $r, $c) = rack_PositionType::toArray($rec->position);
            if ($r > $row || $c > $col) {
                $error = 'Има използвани палети извън тези размери';
                
                return false;
            }
        }
        
        $mQuery = rack_Movements::getQuery();
        
        while ($mRec = $mQuery->fetch("#storeId = {$storeId} AND #positionTo LIKE '{$num}-%' AND #state != 'closed'")) {
            if (!$mRec->positionTo) {
                continue;
            }
            
            list(, $r, $c) = rack_PositionType::toArray($mRec->positionTo);
            if ($r > $row || $c > $col) {
                $error = 'Има насочени движения извън тези размери';
                
                return false;
            }
        }
        
        return true;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        if ($fields['-list']) {
            $uomId = cat_Products::fetch($rec->productId)->measureId;
            if (rack_Movements::haveRightFor('add', (object) array('productId' => $rec->productId)) && $rec->state != 'closed') {
                $addUrl = array('rack_Movements', 'add', 'productId' => $rec->productId, 'palletId' => $rec->id,  'ret_url' => true);
                
                $row->_rowTools->addLink('Преместване', $addUrl + array('movementType' => 'rack2rack'), 'ef_icon=img/16/arrow_switch.png,title=Преместване на палет');
                $row->label .= '&nbsp;&nbsp;' . ht::createLink('', $addUrl + array('movementType' => 'rack2rack'), null, 'ef_icon=img/16/arrow_switch.png,title=Преместване на палет') ;
                
                $row->_rowTools->addLink('Сваляне', $addUrl + array('movementType' => 'rack2floor'), 'ef_icon=img/16/arrow_down.png,title=Сваляне на палета на пода');
                $row->label .= '&nbsp;' . ht::createLink('', $addUrl + array('movementType' => 'rack2floor'), null, 'ef_icon=img/16/arrow_down.png,title=Сваляне на палета на пода') ;
                
                $row->_rowTools->addLink('Хронология', array('rack_Movements', 'palletId' => $rec->id), 'ef_icon=img/16/clock_history.png,title=Хронология на движенията на палета');
            }
            
            $row->productId = cat_Products::getShortHyperlink($rec->productId, true);
            $row->_rowTools->addLink('Палети', array('rack_Pallets', 'productId' => $rec->productId), "id=search{$rec->id},ef_icon=img/16/google-search-icon.png,title=Показване на палетите с този продукт");
            $row->uom = cat_UoM::getShortName($uomId);
            
            $row->ROW_ATTR['class'] = "state-{$rec->state}";
            $row->quantity = ht::styleNumber($row->quantity, $rec->quantity);
            
            if (isset($rec->rackId)) {
                $row->rackId = rack_Racks::getHyperlink($rec->rackId, true);
            }
            
            if ($Definition = batch_Defs::getBatchDef($rec->productId)) {
                if(!empty($rec->batch)){
                    $row->batch = $Definition->toVerbal($rec->batch);
                    
                    if (batch_Movements::haveRightFor('list')) {
                        $link = array('batch_Movements', 'list', 'batch' => $rec->batch);
                        if (isset($fields['-list'])) {
                            $link += array('productId' => $rec->productId, 'storeId' => $rec->storeId);
                        }
                        $row->batch = ht::createLink($row->batch, $link);
                    }
                }
            }
        }
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $rec = static::fetchRec($rec);
        $title = self::getVerbal($rec, 'label');
        if (!empty($rec->position)) {
            $position = self::getVerbal($rec, 'position');
            $title .= "/{$position}";
        }
        
        if (!empty($rec->batch)) {
            $title .= "/{$rec->batch}";
        }
        
        return $title;
    }
    
    
    /**
     * Връща масив с всички използвани палети
     */
    public static function getUsed($storeId = null)
    {
        if (!$storeId) {
            $storeId = store_Stores::getCurrent();
        }
        
        if (!($res = core_Cache::get('UsedRacksPossitions', $storeId))) {
            $res = array();
            $query = self::getQuery();
            while ($rec = $query->fetch("#storeId = {$storeId} AND #state != 'closed'")) {
                if ($rec->position) {
                    $res[$rec->position] = (object)array('productId' => $rec->productId, 'batch' => $rec->batch);
                }
            }
            core_Cache::set('UsedRacksPossitions', $storeId, $res, 1440);
        }
        
        return $res;
    }
    
    
    /**
     * Кои са наличните палети
     *
     * @param int $productId - артикул
     * @param int $storeId   - склад
     *
     * @return array $options
     */
    public static function getPalletOptions($productId, $storeId)
    {
        $options = array();
        $pallets = self::getAvailablePallets($productId, $storeId);
        
        Mode::push('text', 'plain');
        foreach ($pallets as $id => $rec) {
            $options[$id] = self::getRecTitle($id, false);
        }
        Mode::pop('text');
        
        return $options;
    }
    
    
    /**
     * Наличното количество на пода или в палета
     *
     * @param int      $id
     * @param int      $productId
     * @param int      $storeId
     * @param stdClass $data
     * @param null|double $batch 
     *
     * @return float
     */
    public static function getAvailableQuantity($id, $productId, $storeId, $batch = null)
    {
        if(isset($id)){
            
            return rack_Pallets::fetchField($id, 'quantity');
        }
        
        $quantityOnPallets = rack_Products::fetchField("#productId = {$productId} AND #storeId = {$storeId}", 'quantityNotOnPallets');
        
        if(!empty($batch)){
            $batchQuantity = batch_Items::getQuantity($productId, $batch, $storeId);
            
            // От наличното в партидния склад, махаме това което вече е палетирано
            $query = self::getQuery();
            $query->where("#productId = {$productId} AND #storeId = {$storeId} AND #batch = '{$batch}'");
            $query->XPR("sum", 'double', 'SUM(#quantity)');
            $batchQuantity -= $query->fetch()->sum;
            
            return min($quantityOnPallets, $batchQuantity);
        }
        
        return $quantityOnPallets;
    }
    
    
    /**
     * Колко е дефолтното к-во
     *
     * @param int $productId - ид на артикул
     * @param int $storeId   - ид на склад
     *
     * @return null|float - дефолтно к-во
     */
    public static function getDefaultQuantity($productId, $storeId, $excludePosition = null)
    {
        $quantity = null;
        
        if ($palletId = cat_UoM::fetchBySinonim('pallet')->id) {
            $palletRec = cat_products_Packagings::getPack($productId, $palletId);
            $quantity = is_object($palletRec) ? $palletRec->quantity : null;
        }
        
        if (empty($quantity)) {
            $query = rack_Pallets::getQuery();
            $query->where("#productId = {$productId} AND #storeId = {$storeId}");
            if (isset($excludePosition)) {
                $query->where("#position != '{$excludePosition}'");
            }
            
            $query->XPR('max', 'double', 'max(#quantity)');
            $quantity = $query->fetch()->max;
            $quantity = empty($quantity) ? null : $quantity;
        }
        
        return $quantity;
    }
    
    
    /**
     * След подготовка на тулбара на списъчния изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (rack_Movements::haveRightFor('add')) {
            $data->toolbar->addBtn('Палетиране', array('rack_Movements', 'add', 'movementType' => 'floor2rack', 'ret_url' => true), 'ef_icon=img/16/arrow_up.png,title=Палетиране от под-а');
        }
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $data->listTableMvc->FLD('uom', 'varchar', 'smartCenter');
        if (Mode::is('screenMode', 'narrow')) {
            $data->listTableMvc->commonFirst = "<tbody>[#ADD_ROWS#][#ROW#]</tbody>\n";;
            $data->listFields['productId'] = '@Артикул';
        }
    }
    
    
    /**
     * Връща записа отговарящ на позицията
     *
     * @param string $position
     * @param int    $storeId
     *
     * @return null|stdClass
     */
    public static function getByPosition($position, $storeId)
    {
        if (empty($position) || $position == rack_PositionType::FLOOR) {
            
            return;
        }
        
        $rec = self::fetch(array("#position = '{$position}' AND #state != 'closed' AND #storeId = {$storeId}"));
        
        return is_object($rec) ? (object) array('id' => $rec->id, 'productId' => $rec->productId, 'batch' => $rec->batch, 'quantity' => $rec->quantity, 'state' => $rec->state) : null;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'edit' && isset($rec)) {
            if ($rec->state == 'closed') {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Търси по подадения баркод
     *
     * @param string $str
     *
     * @return array
     *               ->title - заглавие на резултата
     *               ->url - линк за хипервръзка
     *               ->comment - html допълнителна информация
     *               ->priority - приоритет
     */
    public function searchByCode($str)
    {
        $resArr = array();
        
        $storeId = store_Stores::getCurrent('id', false);
        
        if (!$storeId || !store_Stores::haveRightFor('list', $storeId)) {
            
            return $resArr;
        }
        
        $str = trim($str);
        
        $prodAndPack = cat_Products::getByCode($str);
        
        if (!$prodAndPack || !$prodAndPack->productId) {
            
            return $resArr;
        }
        
        setIfNot($prodAndPack->packagingId, cat_Products::fetchField($prodAndPack->productId, 'measureId'));
        
        $query = $this->getQuery();
        $query->where("#productId = {$prodAndPack->productId} AND #storeId = {$storeId} AND #state != 'closed'");
        $palletCount = $query->count();
        $palletCountVerbal = core_Type::getByName('int')->toVerbal($palletCount);
        
        $artStr = tr('Артикул');
        $res = (object)array('title' => $artStr . ': ' . cat_Products::getHyperlink($prodAndPack->productId, true),
                             'url' => array(),
                             'comment' => '',
                             'priority' => 1);
        
        $quantityNotOnPallets = rack_Products::fetchField(array("#productId = '[#1#]' AND #storeId = '[#2#]'", $prodAndPack->productId, $storeId), 'quantityNotOnPallets');
        
        if(!empty($palletCount)){
            $res->comment .= "{$palletCountVerbal} " . str::getPlural($palletCount, tr('палет'), true);
        }
        
        if (!empty($quantityNotOnPallets)) {
            $quantityNotOnPalletsVerbal = core_Type::getByName('double(smartRound)')->toVerbal($quantityNotOnPallets);
            $measureId = cat_Products::fetchField($prodAndPack->productId, 'measureId');
            $packName = tr(cat_UoM::getVerbal($measureId, 'name'));
            
            $quantityNotOnPalletsVerbal .= " " . str::getPlural($quantityNotOnPallets, $packName, true);
            $res->comment .= $res->comment ? ' ' . tr('и') . ' ' : '';
            $res->comment .= $quantityNotOnPalletsVerbal . ' ' . tr('непалетирани');
            $res->priority *= 2;
        }
        
        if (!empty($res->comment)) {
            $res->comment .= ' ' . tr('в склад') . ' ' . store_Stores::getHyperlink($storeId, true);
        }
        
        if ($this->haveRightFor('list') && !empty($res->comment)) {
            $filterUrl = array($this, 'list', 'productId' => $prodAndPack->productId, 'state' => '');
            $res->comment .= " " . ht::createBtn('Филтриране', $filterUrl, false, false, 'title=Филтриране на палети в склада,ef_icon=img/16/funnel.png');
        }
        
        $resArr[] = $res;
        
        return $resArr;
    }
    
    
    /**
     * Добавя иконка за палетиране на артикул
     * 
     * @param int $storeId
     * @param int $productId
     * @param int $packagingId
     * @param double $packQuantity
     * @param string $batch
     * 
     * @return boolean|core_ET
     */
    public static function getFloorToPalletImgLink($storeId, $productId, $packagingId, $packQuantity, $batch = null)
    {
        if (store_Stores::getCurrent('id', false) != $storeId || core_Mode::isReadOnly()) {
            
            return false;
        }
        
        if (rack_Movements::haveRightFor('add', (object) array('productId' => $productId))){
            $addPalletUrl = array('rack_Movements', 'add', 'productId' => $productId, 'packagingId' => $packagingId, 'packQuantity' => $packQuantity, 'fromIncomingDocument' => 'yes', 'movementType' => 'floor2rack', 'ret_url' => true);
            if(!empty($batch)){
                $addPalletUrl['batch'] = $batch;
            }
            
            return  ht::createLink('', $addPalletUrl, false, 'ef_icon=img/16/pallet1.png,class=smallIcon,title=Палетиране на артикул');
        }
        
        return false;
    }
}
