<?php


/**
 * Палети
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Milen Georgiev <milen@experta.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
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
    public $loadList = 'plg_RowTools2, plg_Created, rack_Wrapper,recently_Plugin,plg_Sorting, plg_Search, bgerp_plg_Export';


    /**
     * Полета, които могат да бъдат експортирани
     */
    public $exportableCsvFields = 'position,code,productId,batch,quantity,measureId';


    /**
     * Права за плъгин-а bgerp_plg_Export
     */
    public $canExport = 'ceo,rack';


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
    public $canList = 'ceo,rackSee';


    /**
     * Кой може да сваля всичко на пода?
     */
    public $canMovealltofloor = 'debug';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кои полета ще се виждат в листовия изглед
     */
    public $listFields = 'label,position,productId,batch=Партида,quantity,uom=Мярка,closedOn';
    
    
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
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад,input=hidden,mandatory,silent');
        $this->FLD('rackId', 'key(mvc=rack_Racks,select=num)', 'caption=Стелаж,input=none');
        $this->FLD('position', 'rack_PositionType', 'caption=Позиция,smartCenter');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,allowEmpty,selectSourceArr=rack_Products::getStorableProducts,forceAjax)', 'caption=Артикул,mandatory,tdClass=productCell,silent');
        $this->FLD('quantity', 'double(smartRound,decimals=3)', 'caption=Количество,mandatory,silent');
        $this->FLD('batch', 'text', 'smartCenter,caption=Партида');
        $this->FLD('label', 'varchar(32)', 'caption=Етикет,tdClass=rightCol,smartCenter');
        $this->FLD('comment', 'varchar', 'caption=Коментар,column=none');
        $this->FLD('state', 'enum(active=Активно,closed=Затворено)', 'caption=Състояние,input=none,notNull,value=active');
        $this->FLD('closedOn', 'datetime(format=smartTime)', 'caption=Затворено на,input=none');

        $this->FNC('newProductId', 'key2(mvc=cat_Products,select=name,allowEmpty,selectSourceArr=rack_Products::getStorableProducts,forceAjax)', 'caption=Ревизия->Артикул,class=w100,removeAndRefreshForm=newPackagingId|newPackQuantity|newBatch,input,autohide,silent');
        $this->FNC('newPackagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Ревизия->Опаковка,input=hidden');
        $this->FNC('newPackQuantity', 'double(smartRound,decimals=3,min=0)', 'caption=Ревизия->Количество,input=hidden');
        $this->FNC('newBatch', 'text', 'caption=Ревизия->Партида,input=hidden,silent');

        $this->setDbIndex('productId');
        $this->setDbIndex('productId,storeId');
        $this->setDbIndex('state');
        $this->setDbIndex('storeId');
        $this->setDbIndex('position');
    }


    /**
     * Връща к-то от палета в движения оставящи по зони
     *
     * @param int $productId
     * @param mixed $batch
     * @param int|null $id
     * @param string $state
     *
     * @return double $sum
     */
    public static function getSumInZoneMovements($productId, $batch, $id, $state)
    {
        $sum = 0;
        $mQuery = rack_Movements::getQuery();
        $mQuery->XPR('sum', 'double', 'ROUND(#quantity, 2)');
        $mQuery->where("#state = '{$state}'");
        if(isset($id)){
            $mQuery->where("#palletId = {$id}");
        } else {
            $floorPosition = rack_PositionType::FLOOR;
            $mQuery->where("#productId = {$productId} AND #palletId IS NULL AND #position = '{$floorPosition}'");
            if(!is_null($batch)){
                $mQuery->where("#batch = '{$batch}'");
            }
        }

        while($mRec = $mQuery->fetch()){
            $zones = type_Table::toArray($mRec->zones);
            if(countR($zones)){
                array_filter($zones, function($a) use (&$sum, $mRec){$sum += $a->quantity * $mRec->quantityInPack;});
            }
        }

        return $sum;
    }


    /**
     * Връща наличните палети за артикула
     *
     * @param int  $productId               - ид на артикул
     * @param int  $storeId                 - ид на склад
     * @param mixed $batch                  - null за всички партиди, стринг за конкретна (включително и без партида)
     * @param bool $withoutPendingMovements - към които да има или няма чакащи движения
     * @param bool $deductWaitingMovements  - да се приспаднат ли и запазените движения
     *
     * @return array $pallets - масив с палети
     */
    public static function getAvailablePallets($productId, $storeId, $batch = null, $withoutPendingMovements = false, $deductWaitingMovements = false)
    {
        $pallets = array();
        $query = self::getQuery();
        $query->where("#productId = {$productId} AND #storeId = {$storeId} AND #state != 'closed'");
        $query->show('quantity,position,createdOn');
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
                $sumPending = static::getSumInZoneMovements($productId, $rec->batch, $rec->id, 'pending');
                if($sumPending >= $rec->quantity) continue;
                $rest = $rec->quantity - $sumPending;
            }

            // Ако ще се приспадат и запазените движения тяхното к-во да се изважда от наличното на палета
            if ($withoutPendingMovements === true) {
                $sumWaiting = static::getSumInZoneMovements($productId, $rec->batch, $rec->id, 'waiting');
                if($sumWaiting >= $rest) continue;
                $rest = $rest - $sumWaiting;
            }

            // Разликата
            $pallets[$rec->id] = (object) array('quantity' => $rest, 'position' => $rec->position, 'createdOn' => $rec->createdOn);
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
        $form = &$data->form;
        $rec = &$form->rec;

        $form->setReadOnly('productId');
        $form->setReadOnly('quantity');
        $form->setReadOnly('position');
        $form->setReadOnly('batch');

        $packName = tr(cat_UoM::getShortName(cat_Products::fetchField($rec->productId, 'measureId')));
        $form->setField('quantity', "unit={$packName}");

        // Ако е избран нов артикул да се появят полетата за опаковка и партида (ако има)
        if(isset($rec->newProductId)){
            $form->setField('newPackagingId', 'input');
            $form->setField('newPackQuantity', 'input');

            $packs = cat_Products::getPacks($rec->newProductId);
            $form->setOptions('newPackagingId', $packs);
            $form->setDefault('newPackagingId', cat_Products::fetchField($rec->newProductId, 'measureId'));

            // Ако има партиди, позволява се да се смени партидата с друга налична
            $BatchClass = batch_Defs::getBatchDef($rec->newProductId);
            if ($BatchClass) {
                $form->setField('newBatch', 'input,placeholder=Без партида');
                $batches = batch_Items::getBatches($rec->newProductId, $rec->storeId, true);
                if(countR($batches)){
                    $form->setOptions('newBatch', array('' => '') + $batches);
                } else {
                    $form->setReadOnly('newBatch');
                }

                $fieldCaption = $BatchClass->getFieldCaption();
                if (!empty($fieldCaption)) {
                    $form->setField('newBatch', "caption=Ревизия->{$fieldCaption}");
                }
            }
        }
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;

        if ($form->isSubmitted()) {
            if(!empty($rec->newProductId)){
                $activeMovementCount = rack_Movements::count("#storeId = {$rec->storeId} AND (#state = 'active' OR #state = 'waiting') AND (#palletId = {$rec->id} OR (#productId = {$rec->productId} AND #batch = '{$rec->batch}' AND (#position = '{$rec->position}' OR #positionTo = '{$rec->position}')))");
                if($activeMovementCount){
                    $countVerbal = core_Type::getByName('int')->toVerbal($activeMovementCount);
                    if(rack_Movements::haveRightFor('list')){
                        $countVerbal = ht::createLink($countVerbal, array('rack_Movements', 'list', 'palletId' => $rec->id));
                    }
                    $form->setError("newProductId", "Не може да ревизирате палета, докато има|* <b>{$countVerbal}</b> |започнато или запазено движение|*");
                } elseif(empty($rec->newPackQuantity)){
                    $form->setError("newPackQuantity", "Количеството трябва да е попълнено");
                } else {
                    $warning = null;
                    if (!deals_Helper::checkQuantity($rec->newPackagingId, $rec->newPackQuantity, $warning)) {
                        $form->setWarning('newPackQuantity', $warning);
                    }
                }

                if(!$form->gotErrors()){

                    // От новия артикул каква е наличността
                    $packRec = cat_products_Packagings::getPack($rec->newProductId, $rec->newPackagingId);
                    $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
                    $availableQuantity = rack_Pallets::getAvailableQuantity(null, $rec->newProductId, $rec->storeId, $rec->newBatch);
                    $rec->newQuantity = $quantityInPack * $rec->newPackQuantity;

                    // Ако въведеното е над наличното
                    if($rec->newQuantity > $availableQuantity){
                        $measureId = cat_Products::fetchField($rec->productId, 'measureId');
                        $measureName = cat_UoM::getVerbal($measureId, 'name');
                        $availableQuantityVerbal = str::getPlural($availableQuantity, $measureName);
                        $form->setError('newQuantity', "Над наличното количество в склада от:|* <b>{$availableQuantityVerbal}</b>");
                    }

                    if(!$form->gotErrors()){
                        if($rec->productId == $rec->newProductId && $rec->quantity == $rec->newQuantity && $rec->batch == $rec->newBatch){
                            $form->setError('newProductId,newBatch,newPackQuantity,newPackagingId', 'За ревизия трябва да има промяна');
                        } else {
                            // Подмяна на ревизираните данни със новите
                            $rec->_logMsg = $mvc->getRevisionLogMsg($rec->position, $rec->productId, $rec->batch, $rec->quantity, $rec->newProductId, $rec->newBatch, $rec->newQuantity);
                            $rec->productId = $rec->newProductId;
                            $rec->quantity = $rec->newQuantity;
                            $rec->batch = $rec->newBatch;
                            $rec->_isRevisioned = true;
                        }
                    }
                }
            }
        }
    }


    /**
     *  Подготвя лога за ревизия на палета
     */
    private function getRevisionLogMsg($position, $oldProductId, $oldBatch, $oldQuantity, $newProductId, $newBatch, $newQuantity)
    {
        // Подготовка на лога за ревизия
        $oldMeasureId = cat_Products::fetchField($oldProductId, 'measureId');
        $newMeasureId = cat_Products::fetchField($newProductId, 'measureId');

        $oldProductName = cat_Products::getTitleById($oldProductId);
        $newProductName = cat_Products::getTitleById($newProductId);
        $oldMeasureName = cat_UoM::getVerbal($oldMeasureId, 'name');
        $newMeasureName = cat_UoM::getVerbal($newMeasureId, 'name');

        $msg = "Ревизиране на палет {$position}: {$oldProductName}/{$oldBatch}/{$oldMeasureName}/{$oldQuantity} => {$newProductName}/{$newBatch}/{$newMeasureName}/{$newQuantity}";

        return $msg;
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
        $used = rack_Pallets::getUsed($productId);
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
        $cacheType = 'UsedRacksPositions' . $rec->storeId;
        core_Cache::removeByType($cacheType);

        // Ако е ревизирано движението
        if($rec->_isRevisioned){

            // Лог на ревизията и изтриване на чакащите движения
            rack_Logs::add($rec->storeId, $rec->productId, 'revision', $rec->position, null, $rec->_logMsg);

            // Имали движения към ревизирания палет засягащи зони?
            $hasMovementWithZones = rack_Movements::count("#storeId = {$rec->storeId} AND #state = 'pending' AND (#palletId = {$rec->id} OR #positionTo = '{$rec->position}') AND (#zoneList IS NOT NULL OR #zoneList != '')");

            // Изтриване на чакащите движения за този палет
            rack_Movements::delete("#storeId = {$rec->storeId} AND #state = 'pending' AND (#palletId = {$rec->id} OR #positionTo = '{$rec->position}')");

            // Ако е изтрито поне едно движение към зона, да се регенерират всички движения в склада
            if($hasMovementWithZones){
                rack_Zones::pickupAll($rec->storeId);
            }
        }
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

        Mode::push('text', 'plain');
        $rackOptions = rack_Racks::getOptionsByStoreId($storeId);
        Mode::pop('text', 'plain');
        $data->listFilter->setOptions('rackId', array('' => '') + $rackOptions);

        $data->listFilter->setFieldType('productId', 'key2(mvc=cat_Products,select=name,allowEmpty,selectSourceArr=rack_Products::getStorableProducts)');
        $data->listFilter->FLD('stateFilter', 'enum(,active=Активни,closed=Затворено)', 'caption=Всички,silent');
        $data->listFilter->setDefault('stateFilter', 'active');
        
        $data->listFilter->showFields = 'productId,search,rackId,stateFilter';
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

        if (!empty($rec->rackId)) {
            $data->query->where("#rackId = '{$rec->rackId}'");
        }

        if (!empty($rec->stateFilter)) {
            $data->query->where("#state = '{$rec->stateFilter}'");
        }
        
        if (!$order) {
            $data->query->orderBy('#createdOn', 'DESC');
        }
    }


    /**
     * Може ли в склада да има повече от един палет на една позиция
     *
     * @param $storeId
     * @return bool
     */
    public static function canHaveMultipleOnOnePosition($storeId)
    {
        $sRec = store_Stores::fetch($storeId);
        if($sRec) {
            $samePosPallets = $sRec->samePosPallets;
        }
        if(!isset($samePosPallets)) {
            $samePosPallets = rack_Setup::get('DIFF_PALLETS_IN_SAME_POS');
        }

        return $samePosPallets == 'yes';
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
        $rec = self::fetch(array("#productId = {$productId} AND #position = '[#1#]' AND #storeId = {$storeId} AND #state != 'closed'", $position));
        if(!$rec) {
            $samePosPallets = static::canHaveMultipleOnOnePosition($storeId);

            if(!$samePosPallets) {
                $rec = self::fetch(array("#position = '[#1#]' AND #storeId = {$storeId} AND #state != 'closed'", $position));
            }
        }            
      
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

                if(rack_OldMovements::haveRightFor('list')){
                    $row->_rowTools->addLink('Хронология', array('rack_OldMovements', 'list', 'palletId' => $rec->id), 'ef_icon=img/16/clock_history.png,title=Хронология на движенията на палета');
                }
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
    public static function getUsed($productId = '*', $storeId = null)
    {
        if (!$storeId) {
            $storeId = store_Stores::getCurrent();
        }

        if(!$productId) {
            $productId = '*';
        }
        
        $cacheType = 'UsedRacksPositions' . $storeId;
        $cacheKey = '@' . $productId;

        if (!($res = core_Cache::get($cacheType, $cacheKey))) {
            $res = array();
            $query = self::getQuery();
            $query->orderBy('id', 'DESC');
            while ($rec = $query->fetch("#storeId = {$storeId} AND #state != 'closed'")) {
                if ($rec->position) {
                    if(isset($res[$rec->position])) {
                        if($rec->productId == $productId) {
                            // Swap
                            $res[$rec->position]->all[ $res[$rec->position]->productId] = 
                                (object)array('productId' => $res[$rec->position]->productId, 'batch' => $res[$rec->position]->batch);
                            $res[$rec->position]->productId = $rec->productId;
                            $res[$rec->position]->batch = $rec->batch;
                        } else {
                            $res[$rec->position]->all[$rec->productId] = (object)array('productId' => $rec->productId, 'batch' => $rec->batch);
                        }
                    } else {
                        $res[$rec->position] = (object)array('productId' => $rec->productId, 'batch' => $rec->batch);
                    }
                }
            }
            core_Cache::set($cacheType, $cacheKey, $res, 1440);
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

        if ($mvc->haveRightFor('movealltofloor')) {
            $data->toolbar->addBtn('Изтриване', array($mvc, 'movealltofloor', 'ret_url' => true), 'ef_icon=img/16/bug.png,title=Изтриване на палети,warning=Наистина ли желаете да изтриете всички палети в склада');
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
    public static function getByPosition($position, $storeId, $productId = null)
    {
        if (empty($position) || $position == rack_PositionType::FLOOR) return;

        $rec = null;
        if(isset($productId)) {
            $rec = self::fetch(array("#productId = {$productId} AND #position = '{$position}' AND #state != 'closed' AND #storeId = {$storeId}"));
        }

        if(!$rec) {
            $rec = self::fetch(array("#position = '{$position}' AND #state != 'closed' AND #storeId = {$storeId}"));
        }
        
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
    public static function getFloorToPalletImgLink($storeId, $productId, $packagingId, $packQuantity, $batch = null, $containerId = null)
    {
        if (store_Stores::getCurrent('id', false) != $storeId || core_Mode::isReadOnly()) return false;

        if (rack_Movements::haveRightFor('add', (object) array('productId' => $productId))){
            $addPalletUrl = array('rack_Movements', 'add', 'productId' => $productId, 'packagingId' => $packagingId, 'maxPackQuantity' => $packQuantity, 'fromIncomingDocument' => 'yes', 'movementType' => 'floor2rack', 'ret_url' => true);
            if(!empty($batch)){
                $addPalletUrl['batch'] = $batch;
            }
            
            if($containerId){
                $addPalletUrl['containerId'] = $containerId;
            }
            
            return  ht::createLink('', $addPalletUrl, false, 'ef_icon=img/16/pallet1.png,class=smallIcon,title=Палетиране на артикул');
        }
        
        return false;
    }


    /**
     * Екшън за сваляне всичко на пода
     */
    public function act_Movealltofloor()
    {
        $this->requireRightFor('movealltofloor');

        $form = cls::get('core_Form');
        $form->title = "Сваляне на всички палети на пода";
        $form->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад,mandatory');
        $form->setDefault('storeId', store_Stores::getCurrent());
        $form->input();

        if($form->isSubmitted()){

            $products = array();
            $pQuery = rack_Pallets::getQuery();
            $pQuery->where("#storeId = {$form->rec->storeId} AND #state = 'active'");
            $pQuery->show('productId,storeId');

            $count = $pQuery->count();
            core_App::setTimeLimit($count * 0.5, false, 200);

            while($pRec = $pQuery->fetch()){
                $products[$pRec->productId] = $pRec->productId;
                static::delete($pRec->id);
            }

            if(countR($products)){
                foreach ($products as $productId){
                    rack_Pallets::recalc($productId, $form->rec->storeId);
                }
            }

            static::logWrite('Изтриване на палетите');
            followRetUrl(null, 'Успешно премахване на палетите');
        }

        $form->toolbar->addSbBtn('Избор', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');

        // Рендираме опаковката
        return $this->renderWrapping($form->renderHtml());
    }


    /**
     * След взимане на полетата за експорт в csv
     *
     * @see bgerp_plg_CsvExport
     */
    protected static function on_AfterGetCsvFieldSetForExport($mvc, &$fieldset)
    {
        $fieldset->setFieldType('batch', 'varchar');
        $fieldset->setFieldType('productId', 'key(mvc=cat_Products,select=name)');
        $fieldset->FNC('code', 'varchar', 'caption=Код,after=position');
        $fieldset->FNC('measureId', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,after=batch');
    }


    /**
     * Преди експортиране като CSV
     *
     * @see bgerp_plg_CsvExport
     */
    protected static function on_BeforeExportCsv($mvc, &$recs)
    {
        if(is_array($recs)){
            foreach ($recs as &$rec){
                $pRec = cat_Products::fetch($rec->productId, 'code,name,nameEn,measureId');
                $rec->code = cat_Products::getVerbal($pRec, 'code');
                $rec->measureId = $pRec->measureId;

                if($Def = batch_Defs::getBatchDef($rec->productId)){
                    if(!empty($rec->batch)){
                        Mode::push('text', 'plain');
                        $rec->batch = strip_tags($Def->toVerbal($rec->batch));
                        Mode::pop('text');
                    }
                }
            }

            arr::sortObjects($recs, 'position', 'ASC', 'natural');
        }
    }
}
