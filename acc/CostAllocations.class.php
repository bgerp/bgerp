<?php


/**
 * Регистър за разпределяне на разходи
 *
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acc_CostAllocations extends core_Manager
{
    /**
     * Име на перото за неразпределени разходи
     */
    const UNALLOCATED_ITEM_NAME = 'Неразпределени разходи';
    
    
    /**
     * Заглавие
     */
    public $title = 'Отнасяне на разходи';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'разход';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, plg_SaveAndNew, acc_Wrapper';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'ceo, acc, purchase';
    
    
    /**
     * Кой може да редактира?
     */
    public $canEdit = 'ceo, acc, purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, acc, purchase';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'containerId,expenseItemId';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'hr_IndicatorsSourceIntf';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'id, containerId, productId, quantity, allocationBy, expenseItemId, productsData=Разпределено по';
    
    
    /**
     * Работен кеш
     */
    public $recontoQueue = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('detailClassId', 'class(interface=core_ManagerIntf)', 'caption=Детайл,mandatory,silent,input=hidden,remember');
        $this->FLD('detailRecId', 'int', 'caption=Ред от детайл,mandatory,silent,input=hidden,remember');
        $this->FLD('productId', 'int', 'caption=Артикул,mandatory,silent,input=hidden,remember');
        $this->FLD('quantity', 'double(Min=0,smartRound)', 'caption=Количество,mandatory,smartCenter');
        $this->FLD('expenseItemId', 'acc_type_Item(select=titleNum,allowEmpty,lists=600,showAll)', 'after=quantity,silent,mandatory,caption=Разход за,removeAndRefreshForm=allocationBy|productsData|chosenProducts');
        $this->FLD('allocationBy', 'enum(auto=Автоматично (по стойност),no=Няма,value=По стойност,quantity=По количество,weight=По тегло,volume=По обем)', 'caption=Разпределяне,input=none,silent,removeAndRefreshForm=productsData|chosenProducts');
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'mandatory,caption=Ориджин,silent,input=hidden');
        $this->FLD('productsData', 'blob(serialize, compress)', 'input=none');
        
        $this->setDbIndex('detailClassId,detailRecId');
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        try {
            $origin = doc_Containers::getDocument($rec->containerId);
            if ($origin->fetchField('state') == 'active') {
                acc_Journal::reconto($rec->containerId);
                $origin->getInstance()->logWrite('Ре-контиране на документа', $origin->that);
            }
            
            // Ако изтритият разходен обект има кеш записи в таблицата за доставка да му се обновят
            if(isset($rec->_oldExpenceItemId) && $rec->expenseItemId != $rec->_oldExpenceItemId){
                self::forceUpdateOnShutdown($rec->_oldExpenceItemId);
            }
        } catch (core_exception_Expect $e) {
            reportException($e);
        }
    }
    
    private static function forceUpdateOnShutdown($expenseItemId)
    {
        // Ако изтритият разходен обект има кеш записи в таблицата за доставка да му се обновят
        $itemRec = acc_Items::fetch($expenseItemId, 'classId,objectId');
        $expenseReg = new core_ObjectReference($itemRec->classId, $itemRec->objectId);
        if($expenseReg->getInstance()->hasPlugin('purchase_plg_ExtractPurchasesData')){
            $Register = cls::get($itemRec->classId);
            purchase_plg_ExtractPurchasesData::setUpdateOnShutdown($Register, $expenseReg->fetch());
        }
    }
    
    
    /**
     * След изтриване на запис
     */
    protected static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
        foreach ($query->getDeletedRecs() as $rec) {
            $origin = doc_Containers::getDocument($rec->containerId);
            if ($origin->fetchField('state') == 'active') {
                acc_Journal::reconto($rec->containerId);
                $origin->getInstance()->logWrite('Ре-контиране на документа', $origin->that);
                
                // Ако изтритият разходен обект има кеш записи в таблицата за доставка да му се обновят
                self::forceUpdateOnShutdown($rec->expenseItemId);
            }
        }
    }
    
    
    /**
     * Форсира ид-то на перото за неразпределени разходи
     *
     * @return int - ид на перото
     */
    public static function getUnallocatedItemId()
    {
        return acc_Items::forceSystemItem(self::UNALLOCATED_ITEM_NAME, 'unallocated', 'costObjects')->id;
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        // По-хубаво заглавие на формата
        $rec = $data->form->rec;
        if (isset($rec->containerId)) {
            $origin = doc_Containers::getDocument($rec->containerId);
            $data->form->title = core_Detail::getEditTitle($origin->getClassId(), $origin->that, $mvc->singleTitle, $rec->id);
        }
    }
    
    
    /**
     * Връща количеството разпределено за реда в документа
     *
     * @param int $detailClassId - клас на документа
     * @param int $detailRecId   - кой ред от оригиналния документ отговаря
     * @param int $id            - ид на записа ако има
     *
     * @return float $allocatedQuantity - разпределеното досега количество
     */
    public static function getAllocatedInDocument($detailClassId, $detailRecId, $id = null)
    {
        $query = static::getQuery();
        
        // Сумиране на разпределените количества към реда
        $query->where("#detailClassId = {$detailClassId} AND #detailRecId = {$detailRecId}");
        if (isset($id)) {
            $query->where("#id != {$id}");
        }
        
        $query->XPR('allocatedQuantity', 'double', 'sum(#quantity)');
        $query->show('allocatedQuantity');
        
        $allocatedQuantity = $query->fetch()->allocatedQuantity;
        $allocatedQuantity = ($allocatedQuantity) ? $allocatedQuantity : 0;
        
        return $allocatedQuantity;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $data->form->rec;
        $rec->_oldExpenceItemId = $rec->expenseItemId;
        
        // Какво к-во се очаква да се разпредели
        $maxQuantity = cls::get($rec->detailClassId)->getMaxQuantity($rec->detailRecId);
        $maxQuantityVerbal = cls::get('type_Double', array('params' => array('smartRound' => true)))->toVerbal($maxQuantity);
        $uomId = key(cat_Products::getPacks($rec->productId));
        $shortUom = cat_UoM::getShortName($uomId);
        
        $productName = cat_Products::getHyperlink($rec->productId, true);
        
        $form->info = "<b>{$productName}</b>, ";
        $form->info .= tr("|Общо к-во|* <b>{$maxQuantityVerbal}</b> {$shortUom}<br>");
        
        // Колко има още за разпределяне
        $allocatedQuantity = self::getAllocatedInDocument($rec->detailClassId, $rec->detailRecId, $rec->id);
        $toAllocate = $maxQuantity - $allocatedQuantity;
        $form->setDefault('quantity', $toAllocate);
        
        // Показване на к-то
        if ($maxQuantity == 1 && $uomId == cat_UoM::fetchBySinonim('pcs')->id) {
            
            // Ако е 1 и мярката е в брой, се показва в проценти
            $form->setFieldType('quantity', core_Type::getByName('percent(Min=0)'));
            $allocatedQuantity = cls::get('type_Percent')->toVerbal($allocatedQuantity);
            $form->setField('quantity', "unit=|Разпределено|*: <b>{$allocatedQuantity}</b>");
        } else {
            
            // Иначе се показва като двоично число
            $allocatedQuantity = cls::get('type_Double', array('params' => array('smartRound' => true)))->toVerbal($allocatedQuantity);
            $form->setField('quantity', "unit=|Разпределено|*: <b>{$allocatedQuantity}</b> {$shortUom}");
        }
        
        // Ако има избрано разходно перо, и то е на покупка/продажба, показва се и полето за разпределяне
        if (isset($rec->expenseItemId)) {
            $itemClassId = acc_Items::fetchField($rec->expenseItemId, 'classId');
            if (cls::haveInterface('acc_AllowArticlesCostCorrectionDocsIntf', $itemClassId)) {
                $form->setField('allocationBy', 'input');
                
                if(in_array($itemClassId, array(sales_Sales::getClassId(), purchase_Purchases::getClassId()))){
                    $form->setDefault('allocationBy', 'auto');
                } else {
                    $form->setDefault('allocationBy', 'no');
                }
            }
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        
        if (isset($rec->expenseItemId)) {
            $itemClassId = acc_Items::fetchField($rec->expenseItemId, 'classId');
            
            if (cls::haveInterface('acc_AllowArticlesCostCorrectionDocsIntf', $itemClassId)) {
                if (isset($rec->allocationBy) && !in_array($rec->allocationBy, array('auto', 'no'))) {
                    $itemRec = acc_Items::fetch($rec->expenseItemId, 'classId,objectId');
                    $origin = new core_ObjectReference($itemRec->classId, $itemRec->objectId);
                    acc_ValueCorrections::addProductsFromOriginToForm($form, $origin);
                }
            }
        }
        
        if ($form->isSubmitted()) {
            
            // Колко ще бъде разпределено след записа
            $allocatedQuantity = self::getAllocatedInDocument($rec->detailClassId, $rec->detailRecId, $rec->id);
            $allocatedQuantity += $rec->quantity;
            $uomId = key(cat_Products::getPacks($rec->productId));
            
            // Проверка дали ще се разпределя повече от допустимото количество
            $maxQuantity = cls::get($rec->detailClassId)->getMaxQuantity($rec->detailRecId);
            
            if ($allocatedQuantity > $maxQuantity) {
                $maxQuantity = cls::get('type_Double', array('params' => array('smartRound' => true)))->toVerbal($maxQuantity);
                $shortUom = cat_UoM::getShortName($uomId);
                $form->setError('quantity', "Разпределяне над допустимото количество от|* <b>{$maxQuantity}</b> {$shortUom}");
            }
            
            if (!$form->gotErrors()) {
                
                // Проверяване дали е въведено допустимо к-во само ако реда не е за 1 брой
                if (!($maxQuantity == 1 && $uomId == cat_UoM::fetchBySinonim('pcs')->id)) {
                    
                    // Проверка на к-то
                    $warning = null;
                    if (!deals_Helper::checkQuantity($uomId, $rec->quantity, $warning)) {
                        $form->setWarning('quantity', $warning);
                    }
                }
            }
            
            // Ако к-то е достигнато се подсигуряваме, че няма да има 'Запис и нов'
            if (!$form->gotErrors()) {
                if ($allocatedQuantity >= $maxQuantity) {
                    $form->cmd = 'save';
                }
                
                // Проверка на избраните артикули
                if (isset($rec->allocationBy)) {
                    if(!in_array($rec->allocationBy, array('no', 'auto'))){
                        if (!countR($form->allProducts)) {
                            $form->setError('allocationBy', 'В избраната сделка няма експедирани/заскладени артикули');
                        }
                    }
                    
                    if($rec->allocationBy != 'no'){
                        if($rec->allocationBy == 'auto'){
                            $errorField = 'allocateBy,chosenProducts';
                            $itemRec = acc_Items::fetch($rec->expenseItemId, 'classId,objectId');
                            $origin = new core_ObjectReference($itemRec->classId, $itemRec->objectId);
                            $rec->productsData = $origin->getCorrectableProducts($mvc);
                        } else {
                            $errorField = 'allocateBy';
                            $rec->productsData = array_intersect_key($form->allProducts, type_Set::toArray($rec->chosenProducts));
                        }
                        
                        $copyArr = $rec->productsData;
                        if ($error = acc_ValueCorrections::allocateAmount($copyArr, $rec->quantity, $rec->allocationBy)) {
                            $form->setError($errorField, $error);
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $uomId = key(cat_Products::getPacks($rec->productId));
        
        // Ако к-то е в  1 брой показва се като процент
        $isPercent = false;
        if ($uomId == cat_UoM::fetchBySinonim('pcs')->id) {
            $maxQuantity = cls::get($rec->detailClassId)->getMaxQuantity($rec->detailRecId);
            if ($maxQuantity == 1) {
                $row->quantity = cls::get('type_Percent')->toVerbal($rec->quantity);
                $isPercent = true;
            }
        }
        
        if ($isPercent === false) {
            $row->uomId = cat_UoM::getShortName($uomId);
        }
        
        // Линк към обекта на перото
        $eItem = acc_Items::getVerbal($rec->expenseItemId, 'titleNum');
        
        $iRec = acc_Items::fetch($rec->expenseItemId);
        $Register = new core_ObjectReference($iRec->classId, $iRec->objectId);
        if (method_exists($Register->getInstance(), 'getSingleUrlArray_') && !Mode::isReadOnly()) {
            $singleUrl = $Register->getSingleUrlArray();
            if(countR($singleUrl)){
                $singleUrl['Sid'] = $Register->fetchField('containerId');
                $eItem = ht::createLink($eItem, $singleUrl);
                if ($iRec->state == 'closed') {
                    $eItem = ht::createHint($eItem, 'Перото е затворено', 'warning', false, array('height' => 14, 'width' => 14))->getContent();
                    $eItem = "<span class='state-closed' style='padding:3px'>{$eItem}</span>";
                }
            }
        }
        
        // Допълнителна проверка дали артикула е нескладируем, невложим и не ДМА
        $pInfo = cat_Products::getProductInfo($rec->productId);
        $hint = isset($pInfo->meta['fixedAsset']) ? 'Артикулът вече е ДА и не може да бъде разпределян като разход' : (isset($pInfo->meta['canConvert']) ? 'Артикулът вече е вложим и не може да бъде разпределян като разход' : null);
        
        $row->expenseItemId = "<b class='quiet'>" . tr('Разход за') . "</b>: {$eItem}";
        if (isset($hint)) {
            $row->expenseItemId = ht::createHint($row->expenseItemId, $hint, 'warning', false, array('height' => 14, 'width' => 14))->getContent();
            $row->expenseItemId = "<span style='opacity: 0.7;'>{$row->expenseItemId}</span>";
        }
        
        if (isset($fields['-list'])) {
            
            try {
                $Document = doc_Containers::getDocument($rec->containerId);
                $row->containerId = $Document->getLink(0);
                $row->ROW_ATTR['class'] = "state-{$Document->fetchField('state')}";
            } catch (core_exception_Expect $e) {
                $row->containerId = "<span class='red'>" . tr('Проблем с показването') . '</span>';
            }
        }
    }
    
    
    /**
     * Подготовка на разходи
     *
     * @param stdClass $data
     */
    private static function prepareAllocatedExpenses(&$data)
    {
        $rec = &$data->rec;
        $data->recs = $data->rows = array();
        
        // Да не се показват ако режима е за четене
        if (Mode::isReadOnly()) {
            
            return;
        }
        
        // Какви разходи са отчетени към реда
        $query = self::getQuery();
        $query->where("#detailClassId = {$rec->detailClassId} AND #detailRecId = {$rec->detailRecId}");
        while ($dRec = $query->fetch()) {
            $data->recs[$dRec->id] = $dRec;
            $data->rows[$dRec->id] = self::recToVerbal($dRec);
        }
        
        // Можели да се добавя нова разбивка на разходите
        if (self::haveRightFor('add', (object) $rec) && !Mode::isReadOnly()) {
            $url = array('acc_CostAllocations', 'add') + (array) $rec;
            $url['ret_url'] = true;
            
            core_Request::setProtected('detailClassId,detailRecId,containerId,productId');
            $data->addBtn = ht::createLink('Отнасяне на разходи', toUrl($url), false, 'title=Отнасяне към разходен обект');
            core_Request::removeProtected('detailClassId,detailRecId,containerId,productId');
        }
    }
    
    
    /**
     * Рендиране на разпределените разходи към ред
     *
     * @param stdClass $data - данни
     *
     * @return core_ET $tpl  - рендиран шаблон
     */
    private static function renderAllocatedExpenses(&$data)
    {
        $tpl = getTplFromFile('acc/tpl/CostAllocation.shtml');
        $clone = $tpl->getBlock('ROW');
        
        foreach ($data->rows as $row) {
            core_RowToolbar::createIfNotExists($row->_rowTools);
            $row->tools = $row->_rowTools->renderHtml();
            
            $dTpl = clone $clone;
            $dTpl->placeObject($row);
            $dTpl->removeBlocks();
            $dTpl->append2Master();
        }
        
        if (isset($data->addBtn)) {
            $tpl->append($data->addBtn, 'buttons');
        }
        
        return $tpl;
    }
    
    
    /**
     * Връща представянето на разпределените разходи към даден ред от документ за купуване на разходи
     *
     * @param core_Detail $Detail         - Детайл на документ
     * @param int         $id             - ид на ред от детайл на документ
     * @param int         $containerId    - ид на контейнера на документа
     * @param int         $productId      - ид на артикул
     * @param int         $packagingId    - ид на опаковка/мярка
     * @param float       $quantityInPack - к-во в опаковка
     *
     * @return string $res            - направените разходи към реда
     */
    public static function getAllocatedExpenses(core_Detail $Detail, $id, $containerId, $productId, $packagingId, $quantityInPack)
    {
        $res = '';
        
        // Подготвяне на данните
        $Detail = cls::get($Detail);
        $arr = array('detailClassId' => $Detail->getClassId(), 'detailRecId' => $id, 'containerId' => $containerId, 'productId' => $productId);
        $data = (object) array('rec' => (object) $arr);
        
        // Подготвяне на разпределените разходи
        self::prepareAllocatedExpenses($data);
        
        // Рендиране на разпределените разходи
        $res = self::renderAllocatedExpenses($data)->getContent();
        
        // Връщане на рендираните разпределени разходи
        return $res;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec)) {
            if (empty($rec->detailClassId) || empty($rec->detailRecId) || empty($rec->containerId) || empty($rec->productId)) {
                $requiredRoles = 'no_one';
                
                return;
            }
            
            // Артикулът трябва да е нескладируем, невложим и не-ДМА
            $pInfo = cat_Products::getProductInfo($rec->productId);
            if (isset($pInfo->meta['canStore']) || isset($pInfo->meta['canConvert']) || isset($pInfo->meta['fixedAsset'])) {
                $requiredRoles = 'no_one';
                
                return;
            }
            
            $origin = doc_Containers::getDocument($rec->containerId);
            if ($origin->isInstanceOf('purchase_Purchases')) {
                $originRec = $origin->fetch('contoActions,state');
                $purchaseActions = type_Set::toArray($originRec->contoActions);
                if (!isset($purchaseActions['ship']) && $originRec->state != 'draft') {
                    $requiredRoles = 'no_one';
                    
                    return;
                }
            } else {
                $firstDocument = doc_Threads::getFirstDocument($origin->fetchField('threadId'));
                if (!($firstDocument->isInstanceOf('purchase_Purchases') || $firstDocument->isInstanceOf('findeals_Deals'))) {
                    $requiredRoles = 'no_one';
                    
                    return;
                }
            }
            
            //... и да е активен
            $state = $origin->fetchField('state');
            
            if ($state != 'active' && $state != 'draft') {
                $requiredRoles = 'no_one';
                
                return;
            }
            
            //... и да е в отворен период
            if (acc_Periods::isClosed($origin->fetchField($origin->valiorFld))) {
                $requiredRoles = 'no_one';
                
                return;
            }
            
            //... и потребителя да има достъп до него
            if (!$origin->haveRightFor('single')) {
                $requiredRoles = 'no_one';
                
                return;
            }
        }
        
        if ($action == 'add' && isset($rec)) {
            
            // Ако е разпределено очакваното, не може да се разпределят разходи
            if (isset($rec->detailClassId, $rec->detailRecId)) {
                $maxQuantity = cls::get($rec->detailClassId)->getMaxQuantity($rec->detailRecId);
                $allocatedByFar = self::getAllocatedInDocument($rec->detailClassId, $rec->detailRecId);
                if ($allocatedByFar >= $maxQuantity) {
                    $requiredRoles = 'no_one';
                    
                    return;
                }
            }
        }
    }
    
    
    /**
     * Помощен метод обработващ записите за подаден ред
     *
     * @param int   $docClassId - клас на детайла
     * @param int   $docRecId   - ид на реда
     * @param int   $productId  - ид на артикул
     * @param float $quantity   - оригинално к-во
     * @param float $amount     - обща сума за разпределяне
     *
     * @return array $res      - обработените записи
     *               о amount        - сума за разпределяне
     *               o productId     - ид на артикула
     *               о quantity      - к-во за разпределяне
     *               o reason        - описание на контировката
     *               o expenseItemId - ид-то на перото към което ще се разпределя
     *               o allocationBy  - как ще се разпределя разхода, ако може
     */
    private static function getRecsWithAllocatedAmount($docClassId, $docRecId, $productId, $quantity, $amount)
    {
        $res = array();
        $Detail = cls::get($docClassId);
        
        // Извличане на записите за въпросния ред от оригиналния документ
        $dQuery = self::getQuery();
        $dQuery->where(array("#detailClassId = {$Detail->getClassId()} AND #detailRecId = {$docRecId}"));
        $dRecs = $dQuery->fetchAll();
        
        // Запомнят се общата сума и к-во за разпределяне
        $totalQuantity = $quantity;
        $totalAmount = $amount;
        $allocatedAmount = 0;
        
        // Ако са намерени записи
        if (is_array($dRecs) && countR($dRecs)) {
            $dRecs = array_values($dRecs);
            
            // За всеки запис
            for ($i = 0; $i <= countR($dRecs) - 1; $i++) {
                $dRec = $dRecs[$i];
                $nextRec = $dRecs[$i + 1];
                
                // Подготвят се данните за разпределяне
                $r = (object) array('productId' => $productId);
                $r->reason = 'Приети услуги и нескладируеми консумативи';
                if (isset($dRec->allocationBy)) {
                    $r->allocationBy = $dRec->allocationBy;
                }
                
                $r->expenseItemId = $dRec->expenseItemId;
                acc_journal_Exception::expect($r->expenseItemId, 'Невалиден разход');
                
                // Задаване на к-то и приспадане
                $r->quantity = $dRec->quantity;
                
                // Какво к-во остава за разпределяне
                $quantity -= $r->quantity;
                $quantity = round($quantity, 8);
                
                // Ако няма следващ обект и цялото к-во е разпределено
                if (!is_object($nextRec) && $quantity <= 0) {
                    
                    // Сумата е остатака от сумата за разпределяне и разпределеното до сега
                    // така е подсигурено че няма да има разлики в сумите
                    $r->amount = round($totalAmount - $allocatedAmount, 2);
                } else {
                    
                    // Ако има следващ обект и още к-во за разпределяне, сумата се изчислява пропорционално
                    $r->amount = round($r->quantity / $totalQuantity * $totalAmount, 2);
                    $allocatedAmount += $r->amount;
                }
                
                if ($dRec->allocationBy != 'no') {
                    $r->correctProducts = $dRec->productsData;
                }
                
                // Добавяне на редовете
                $res[] = $r;
            }
            
            // Ако има неразпределено количество
            if ($quantity > 0) {
                
                // Неразпределеното количество, се отнася към неразпределения разход обект
                $r = (object) array('productId' => $productId);
                $r->reason = 'Приети непроизводствени услуги и нескладируеми консумативи';
                $r->expenseItemId = self::getUnallocatedItemId();
                $r->quantity = $quantity;
                $r->amount = round($totalAmount - $allocatedAmount, 2);
                
                $res[] = $r;
            }
        }
        
        // Връщане на намерените резултати
        return $res;
    }
    
    
    /**
     * Помощна ф-я, определяща как ще се разпределят редовете по разходи
     * Вика се от документите за купуване на услуги. Там за всеки запис на невложима и не ДМА услуга,
     * се проверява как трябва да се разпредели по разходи, Ако в записа има перо за разпределяне директно се
     * разпределя, Ако няма се проверява дали има към документа, документ за разпределяне на разходи.
     * Ако има се гледа какво к-во от оригиналния ред към кой разход се отнася, неразпределеното се отнася към
     * неразпределените.
     *
     * Ако артикула е невложим и не е ДМА:
     * Ако в реда от оригиналния документ имаме разходен обект към него.
     * Ако няма се гледа имали документ за Разпределение на разходи към документа, в които е разпределено
     * к-во от реда, ако има се разбива записа по-количествата, а остатъка отива към неразпределени
     *
     * Ако артикула е Вложим, винаги отива към неразпределени.
     * Ако е ДМА се разпределя към себе си.
     *
     * @param int        $docClassId - клас на детайла
     * @param int        $docRecId   - ид на ред от детайла
     * @param int        $productId  - ид на артикул
     * @param float      $quantity   - к-во от оригиналния документ
     * @param float      $amount     - сума на реда за разпределяне
     * @param float|NULL $discount   - отстъпката от цената, ако има
     *
     * @return array $res           - масив с данни за контировката на услугата
     *               о amount        - сума за разпределяне
     *               o productId     - ид на артикула
     *               о quantity      - к-во за разпределяне
     *               o reason        - описание на контировката
     *               o expenseItemId - ид-то на перото към което ще се разпределя
     */
    public static function getRecsByExpenses($docClassId, $docRecId, $productId, $quantity, $amount, $discount)
    {
        $res = array();
        
        // Ако артикула е складируем, се пропуска
        $pInfo = cat_Products::getProductInfo($productId);
        if (isset($pInfo->meta['canStore'])) {
            
            return $res;
        }
        
        // От сумата се приспада отстъпката, ако има
        $amount = ($discount) ?  $amount * (1 - $discount) : $amount;
        
        $obj = (object) array('productId' => $productId,
            'quantity' => $quantity,
            'amount' => round($amount, 2),
            'reason' => 'Приети услуги и нескладируеми консумативи');
        
        if (isset($pInfo->meta['fixedAsset'])) {
            
            // Ако артикула е ДМА се отнася като разход към себе си
            $obj->expenseItemId = array('cat_Products', $productId);
            $obj->reason = 'Приети ДА';
        } elseif ($pInfo->meta['canConvert']) {
            
            // Ако артикула е вложим, отива към 'неразпределени'
            $obj->expenseItemId = self::getUnallocatedItemId();
            $obj->reason = 'Приети услуги и нескладируеми консумативи за производството';
        } else {
            $dRecs = self::getRecsWithAllocatedAmount($docClassId, $docRecId, $productId, $quantity, $amount);
            if (countR($dRecs)) {
                
                return $dRecs;
            }
            
            // Ако не е уточнено как се разпределя, отива към неразпределени
            if (empty($obj->expenseItemId)) {
                $obj->expenseItemId = self::getUnallocatedItemId();
                $obj->reason = 'Приети непроизводствени услуги и нескладируеми консумативи';
            }
        }
        
        // Задължително трябва да има разходно перо
        if (!is_array($obj->expenseItemId)) {
            acc_journal_Exception::expect(acc_Items::fetch($obj->expenseItemId), 'Невалидно разходно перо');
        }
        
        $res[] = $obj;
        
        // връщане на редовете
        return $res;
    }
    
    
    /**
     * Интерфейсен метод на hr_IndicatorsSourceIntf
     *
     * @return array $result
     */
    public static function getIndicatorNames()
    {
        $result = array();
        
        // Показател за делта на търговеца
        $rec = hr_IndicatorNames::force('salesExpenses', __CLASS__, 1);
        $result[$rec->id] = $rec->name;
        
        return $result;
    }
    
    
    /**
     * Метод за вземане на резултатност на хората. За определена дата се изчислява
     * успеваемостта на човека спрямо ресурса, които е изпозлвал
     *
     * @param datetime $timeline - Времето, след което да се вземат всички модифицирани/създадени записи
     *
     * @return array $result  - масив с обекти
     *
     * 			o date        - дата на стайноста
     * 		    o personId    - ид на лицето
     *          o docId       - ид на документа
     *          o docClass    - клас ид на документа
     *          o indicatorId - ид на показател
     *          o value       - стойноста на показател
     *          o isRejected  - оттеглена или не. Ако е оттеглена се изтрива от показателите
     */
    public static function getIndicatorValues($timeline)
    {
        $result = array();
        
        // Показател за делта на търговеца
        $expenseIndicatorId = hr_IndicatorNames::force('salesExpenses', __CLASS__, 1)->id;
        $eQuery = self::getIndicatorQuery($timeline);
        
        $wh = $eQuery->getWhereAndHaving();
        if (empty($wh->w)) {
            
            return $result;
        }
        
        while ($eRec = $eQuery->fetch()) {
            $saleRec = cls::get($eRec->expenseClassId)->fetch($eRec->expenseObjectId);
            
            $persons = sales_PrimeCostByDocument::getDealerAndInitiatorId($saleRec->containerId);
            if (empty($persons['dealerId'])) {
                continue;
            }
            
            $Document = doc_Containers::getDocument($eRec->containerId);
            if (!acc_Journal::fetchByDoc($Document->getInstance(), $Document->that)) {
                continue;
            }
            $dRec = cls::get($eRec->detailClassId)->fetch($eRec->detailRecId);
            $amount = $dRec->amount;
            
            $sign = ($dRec->isReverse == 'yes') ? -1 : 1;
            $personId = crm_Profiles::fetchField("#userId = '{$persons['dealerId']}'", 'personId');
            $key = "{$personId}|{$Document->getClassId()}|{$Document->that}|{$saleRec->valior}|{$expenseIndicatorId}";
            
            // Ако няма данни, добавят се
            if (!array_key_exists($key, $result)) {
                $result[$key] = (object) array('date' => $saleRec->valior,
                    'personId' => $personId,
                    'docId' => $Document->that,
                    'docClass' => $Document->getClassId(),
                    'indicatorId' => $expenseIndicatorId,
                    'value' => round($sign * $amount, 4),
                    'isRejected' => $dRec->state == 'rejected',);
            } else {
                
                // Ако има вече се сумират
                $ref = &$result[$key];
                $ref->value += round($sign * $amount, 4);
            }
        }
        
        return $result;
    }
    
    
    /**
     * Кои са последно модифицираните контейнери
     */
    private static function getLastModifiedContainers($timeline)
    {
        $containers = array();
        
        // Кои документи ще се проверяват дали са променяни
        $documents = array('purchase_Purchases', 'purchase_Services', 'sales_Services', 'findeals_AdvanceReports', 'planning_DirectProductionNote');
        
        foreach ($documents as $docName) {
            $Doc = cls::get($docName);
            $dQuery = $Doc->getQuery();
            if(!empty($timeline)){
                $dQuery->where("#modifiedOn >= '{$timeline}'");
            }
            $dQuery->where("#state != 'draft' AND #state != 'pending' AND #state != 'stopped'");
            $dQuery->show('containerId');
            $containers += arr::extractValuesFromArray($dQuery->fetchAll(), 'containerId');
        }
        
        return $containers;
    }
    
    
    /**
     * Заявката към записите от модела, чиито документи са променяни след $timeline
     *
     * @param datetime $timeline - времева линия след който ще се филтрират документите
     * @param array    $masters  - помощен масив
     *
     * @return core_Query $iQuery - подготвената заявка
     */
    public static function getIndicatorQuery($timeline)
    {
        $timeline = '';
        $eQuery = self::getQuery();
        
        $containers = self::getLastModifiedContainers($timeline);
        if (!countR($containers)) {
            
            return $eQuery;
        }
        
        $eQuery->EXT('expenseClassId', 'acc_Items', 'externalName=classId,externalKey=expenseItemId');
        $eQuery->EXT('expenseObjectId', 'acc_Items', 'externalName=objectId,externalKey=expenseItemId');
        $eQuery->where('#expenseClassId = ' . sales_Sales::getClassId());
        $eQuery->in('containerId', $containers);
        
        return $eQuery;
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('id', 'DESC');
    }
}
