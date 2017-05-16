<?php

/**
 * Клас 'cat_products_Packagings'
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cat_products_Packagings extends core_Detail
{
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'productId';
    
    
    /**
     * Заглавие
     */
    var $title = 'Опаковки';
    
    
    /**
     * Единично заглавие
     */
    var $singleTitle = 'Опаковка';
 
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'packagingId=Наименование, quantity=К-во, code=EAN, netWeight=, tareWeight=, weight=Тегло, 
        sizeWidth=, sizeHeight=, sizeDepth=, dimention=Габарити, 
        eanCode=';
    
    
    /**
     * Поле за редактиране
     */
    var $rowToolsField = 'tools';

    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'cat_Wrapper, plg_RowTools2, plg_SaveAndNew, plg_AlignDecimals2, plg_Created';
    
    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = 'cat_Products';
    
    
    /**
     * Кой може да качва файлове
     */
    var $canAdd = 'cat,ceo,sales,purchase,catEdit';
    
    
    /**
     * Кой може да качва файлове
     */
    var $canEdit = 'cat,ceo,sales,purchase,catEdit';
    
    
    /**
     * Кой може да качва файлове
     */
    var $canDelete = 'cat,ceo,sales,purchase,catEdit';
    

    /**  
     * Предлог в формата за добавяне/редактиране  
     */  
    public $formTitlePreposition = 'на';  

    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden, silent');
        $this->FLD('packagingId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'input,tdClass=leftCol,caption=Опаковка,mandatory,smartCenter');
        $this->FLD('quantity', 'double(Min=0)', 'input,caption=Количество,mandatory,smartCenter');
        $this->FLD('isBase', 'enum(yes=Да,no=Не)', 'caption=Основна,mandatory,maxRadio=2');
        $this->FLD('netWeight', 'cat_type_Weight', 'caption=Тегло->Нето');
        $this->FLD('tareWeight', 'cat_type_Weight', 'caption=Тегло->Тара');
        $this->FLD('sizeWidth', 'cat_type_Size', 'caption=Габарит->Ширина');
        $this->FLD('sizeHeight', 'cat_type_Size', 'caption=Габарит->Височина');
        $this->FLD('sizeDepth', 'cat_type_Size', 'caption=Габарит->Дълбочина');
        $this->FLD('eanCode', 'gs1_TypeEan', 'caption=Код->EAN');
        
        $this->setDbUnique('productId,packagingId');
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от Request
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
        if ($form->isSubmitted()){
            $rec = &$form->rec;
            
            $baseMeasureId = cat_Products::getProductInfo($rec->productId)->productRec->measureId;
            
            if($baseMeasureId == $rec->packagingId){
                if($rec->quantity != 1){
                    $form->setError('quantity', 'Количеството не може да е различно от единица за избраната мярка/опаковка');
                }
            }
            
            if($rec->eanCode) {
                    
                // Проверяваме дали има продукт с такъв код (като изключим текущия)
                $check = $mvc->Master->getByCode($rec->eanCode);
                if($check && ($check->productId != $rec->productId)
                    || ($check->productId == $rec->productId && $check->packagingId != $rec->packagingId)) {
                    $form->setError('eanCode', 'Има вече продукт с такъв код!');
                }
            }
                
            // Ако за този продукт има друга основна опаковка, тя става не основна
            if($rec->isBase == 'yes' && $packRec = static::fetch("#productId = {$rec->productId} AND #isBase = 'yes'")){
                $packRec->isBase = 'no';
                static::save($packRec);
            }
            
            // Проверка на к-то
            if(!deals_Helper::checkQuantity($baseMeasureId, $rec->quantity, $warning)){
                $form->setError('quantity', $warning);
            }
        }
    }
    
    
    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if($requiredRoles == 'no_one') return;
        
        if($action == 'add' && isset($rec->productId)) {
            if (!count($mvc::getRemainingOptions($rec->productId))) {
                $requiredRoles = 'no_one';
            } 
        }
        
        if(($action == 'add' ||  $action == 'delete' ||  $action == 'edit') && isset($rec->productId)) {
            $productRec = cat_Products::fetch($rec->productId, 'isPublic,state');
            if($productRec->state != 'active' && $productRec->state != 'template'){
                $requiredRoles = 'no_one';
            } elseif($productRec->isPublic == 'yes'){
                if(!haveRole('ceo,cat')){
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        // Ако потрбителя няма достъп до сингъла на артикула, не може да модифицира опаковките
        if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec) && $requiredRoles != 'no_one'){
            $productInfo = cat_Products::getProductInfo($rec->productId);
            if(empty($productInfo->meta['canStore'])){
                $requiredRoles = 'no_one';
            }
        }
        
        // Ако опаковката вече е използвана не може да се изтрива
        if($action == 'delete' && isset($rec)){
            if(self::isUsed($rec->productId, $rec->packagingId, strtolower(Request::get('Act')) == 'list')){
                $requiredRoles = 'no_one';
            }
        }
        
        // Ако потребителя не е създал записа, трябва да има cat или ceo за да го промени
        if(($action == 'edit' || $action == 'delete') && isset($rec)){
            if($rec->createdBy != $userId && !haveRole('ceo,cat', $userId)){
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    public static function on_AfterPrepareListFields($mvc, $data)
    {
        $data->query->orderBy('#id');
        
        if(isset($data->masterId)){
            $measureId = cat_Products::getProductInfo($data->masterId)->productRec->measureId;
            $shortMeasure = cat_UoM::getShortName($measureId);
            
            $data->listFields['quantity'] .= "|* ({$shortMeasure})";
        }
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране.
     *
     * @param core_Manager $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditToolbar($mvc, $data)
    {
        if(!(count($mvc::getRemainingOptions($data->form->rec->productId)) - 1)){
            $data->form->toolbar->removeBtn('saveAndNew');
        }
    }
    
    
    /**
     * Връща не-използваните параметри за конкретния продукт, като опции
     *
     * @param $productId int ид на продукта
     * @param $id int ид от текущия модел, което не трябва да бъде изключено
     * @return array $options - опциите
     */
    public static function getRemainingOptions($productId, $id = NULL)
    {
        // Извличаме мерките и опаковките
        $uomArr = cat_UoM::getUomOptions();
        $packArr = cat_UoM::getPackagingOptions();
        
        // Отсяваме тези, които вече са избрани за артикула
        $query = self::getQuery();
        if($id) {
            $query->where("#id != {$id}");
        }

        while($rec = $query->fetch("#productId = $productId")) {
           unset($uomArr[$rec->packagingId]);
           unset($packArr[$rec->packagingId]);
        }

        // Групираме опциите, ако има такива
        $options = array();
        if(count($packArr)){
            $options = array('p' => (object)array('group' => TRUE, 'title' => tr('Опаковки'))) + $packArr;
        }
        
        if(count($uomArr)){
            $options += array('u' => (object)array('group' => TRUE, 'title' => tr('Мерки'))) + $uomArr;
        }
        
        // Връщаме намерените опции
        return $options;
    }

    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $options = self::getRemainingOptions($rec->productId, $rec->id);
        
        if (empty($options)) {
            
            // Няма повече недефинирани опаковки
            redirect(getRetUrl(), FALSE, '|Всички налични мерки/опаковки за артикула са вече избрани');
        }
        
        if(!$rec->id){
            $options = array('' => '') + $options;
        }
        
        $form->setDefault('isBase', 'no');
        $form->setOptions('packagingId', $options);
        
        $pInfo = cat_Products::getProductInfo($rec->productId);
        $unit = cat_UoM::getShortName($pInfo->productRec->measureId);
        $form->setField('quantity', "unit={$unit}");
        
        // Ако редактираме, но опаковката е използвана не може да се променя
        if(isset($rec->id)){
            if(self::isUsed($rec->productId, $rec->packagingId, TRUE)){
                $form->setReadOnly('packagingId');
                $form->setReadOnly('quantity');
            }
        }
    }
    
   
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if($rec->sizeWidth == 0) {
            $row->sizeWidth = '-';
        }
        
        if($rec->sizeHeight == 0) {
            $row->sizeHeight = '-';
        }
        
        if($rec->sizeDepth == 0) {
            $row->sizeDepth = '-';
        }
        
        $row->dimention = "{$row->sizeWidth} x {$row->sizeHeight} x {$row->sizeDepth}";
        
        if($rec->eanCode){
            $row->code = $row->eanCode;
        }
        
        if($rec->netWeight){
            $row->weight = tr("|Нето|*: ") . $row->netWeight . "<br>";
        }
        
        if($rec->tareWeight){
            $row->weight .= tr("|Тара|*: {$row->tareWeight}");
        }
        
        if($rec->isBase == 'yes'){
            $row->packagingId = "<b>" . $row->packagingId . "</b>";
        }
    }
    
    
    /**
     * Подготвя опаковките на артикула
     * 
     * @param stdClass $data
     */
    public function preparePackagings($data)
    {
        // Ако мастъра не е складируем, няма смисъл да показваме опаковките му
        $productInfo = $data->masterMvc->getProductInfo($data->masterId);
        if(empty($productInfo->meta['canStore'])){
            $data->notStorable = TRUE;
        }
        
        $data->recs = $data->rows = array();
        
        $query = self::getQuery();
        $query->where("#productId = {$data->masterId}");
        $query->orderBy('quantity', 'ASC');
        $query->orderBy('packagingId', 'ASC');
        while($rec = $query->fetch()){
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = self::recToVerbal($rec);
        }
        
        if ($this->haveRightFor('add', (object)array('productId' => $data->masterId))) {
            $data->addUrl = array(
                    $this,
                    'add',
                    'productId' => $data->masterId,
                    'ret_url' => getCurrentUrl() + array('#'=> get_class($this))
            );
        }
    }
    
    
    /**
     * Подготвя опаковките на артикула
     * 
     * @param stdClass $data
     */
    public function renderPackagings($data)
    {
        if($data->notStorable === TRUE && !count($data->recs)) return;
        
        $tpl = getTplFromFile('cat/tpl/PackigingDetail.shtml');
        
        if ($data->addUrl  && !Mode::isReadOnly()) {
            $addBtn = ht::createLink("<img src=" . sbf('img/16/add.png') . " style='vertical-align: middle; margin-left:5px;'>", $data->addUrl, FALSE, 'title=Добавяне на нова опаковка/мярка');
            $tpl->append($addBtn, 'TITLE');
        }
        $data->listFields = arr::make($this->listFields);
        
        // Ако артикула не е производим, показваме в детайла
        if($data->notStorable === TRUE){
            $tpl->append(" <small style='color:red'>(" . tr('Артикулът не е складируем') . ")</small>", 'TITLE');
            $tpl->append("state-rejected", 'TAB_STATE');
            unset($data->listFields['tools']);
        }
        
        $table = cls::get('core_TableView', array('mvc' => $this));
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        
        $tpl->append($table->get($data->rows, $data->listFields), 'CONTENT');
        
        return $tpl;
    }
    
    
    /**
     * Връща опаковката ако има такава
     * 
     * @param int $productId - ид на продукта
     * @param int $packagingId - ид на опаковката
     * @return stdClass
     */
    public static function getPack($productId, $packagingId)
    {
        return self::fetch("#productId = {$productId} AND #packagingId = {$packagingId}");
    }
    

    /**
     * Връща количеството на дадения продукт в посочената опаковка
     */
    public static function getQuantityInPack($productId, $pack = 'pallet')
    { 
        $uomRec = cat_UoM::fetchBySinonim(mb_strtolower($pack));
 
        if($uomRec) {

            $packRec = self::getPack($productId, $uomRec->id);

            if($packRec) {
 
                return $packRec->quantity;
            }
        }
    }
    

    /**
     * Връща най-голямата опаковка, която има по-малко бройки в себе си, от посоченото
     */
    public static function getLowerPack($productId, $quantity)
    {
        $bestRec = NULL;

        $query = self::getQuery();
        while($rec = $query->fetch("#productId = {$productId}")) {
            if($rec->quantity < $quantity) {
                if(!$bestRec || $bestRec->quantity < $rec->quantity) {
                    $bestRec = $rec;
                }
            }
        }

        return $bestRec;
    }

    
    /**
     * Дали в бизнес документите е използван артикула с посочената опаковка
     * 
     * @param int $productId   - ид на артикул
     * @param int $uomId       - мярка на артикула
     * @param boolean $cache   - дали искаме данните да се кешират при използване или не
     * @return boolean $isUsed -използван или не
     */
    public static function isUsed($productId, $uomId, $cache = FALSE)
    {  
        $cacheKey = "{$productId}|{$uomId}";

        // Ако искаме кеширани данни
        if($cache === TRUE){
            $isUsed = FALSE;
            
            // Проверяваме имали кеш
            $hasCache = core_Cache::get('cat_Products',  $cacheKey);
            
            // Ако артикула е използван в тази си опаковка, кешираме че е използван
            if($hasCache !== 'y' && $hasCache !== 'n'){
                
                // Ако няма проверяваме дали е използван с тази опаковка (без кеш)
                if(self::isUsed($productId, $uomId)){  
                    core_Cache::set('cat_Products', $cacheKey, 'y', 10080);
                    $isUsed = TRUE;
                } else {
                    core_Cache::set('cat_Products', $cacheKey, 'n', 10080);
                    $isUsed = FALSE;
                }
            } else {
              
                $isUsed = ($hasCache == 'y');
            }
            
            // Връщаме намерения резултат
            return $isUsed;
        }
        
        // Детайли в които ще проверяваме
        $details = array('sales_SalesDetails', 
                         'purchase_PurchasesDetails', 
                         'store_ShipmentOrderDetails', 
                         'store_ReceiptDetails', 
                         'sales_QuotationsDetails', 
                         'sales_InvoiceDetails', 
                         'purchase_InvoiceDetails', 
                         'planning_DirectProductNoteDetails',
                         'planning_ConsumptionNoteDetails', 
                         'cat_BomDetails', 
                         'sales_ProformaDetails', 
                         'sales_ServicesDetails', 
                         'purchase_ServicesDetails', 
                         'store_ConsignmentProtocolDetailsReceived', 
                         'store_ConsignmentProtocolDetailsSend');
        
        // За всеки от изброените документи проверяваме дали е избран артикула с мярката
        $isUsed = FALSE;
        foreach ($details as $Detail){
            if($Detail == 'cat_BomDetails'){
                if($rec = $Detail::fetch("#resourceId = {$productId} AND #packagingId = '{$uomId}'", 'id')){
                    $isUsed = TRUE;
                    break;
                }
            } else {
                if($rec = $Detail::fetch("#productId = {$productId} AND #packagingId = '{$uomId}'", 'id')){
                    $isUsed = TRUE;
                    break;
                }
            }
        }
        
        // Ако няма проверяваме дали е използван с тази опаковка (без кеш)
        if($isUsed){
            core_Cache::set('cat_Products', $cacheKey, 'y', 10080);
        } else {
            core_Cache::set('cat_Products', $cacheKey, 'n', 10080);
        }

        // Връщаме резултат
        return $isUsed;
    }
    
    
    /**
     * Синхронизиране на дефолтните опаковки
     * 
     * @param mixed $productRec
     */
    public static function sync($productRec)
    {
        // Имали драйвер?
        $Driver = cat_Products::getDriver($productRec);
        if(!$Driver) return;
        
        // Имали дефолтни опаковки от драйвера
        $defaultPacks = $Driver->getDefaultPackagings($productRec);
        if(!count($defaultPacks) || !is_array($defaultPacks)) return;
        
        foreach ($defaultPacks as $obj)
        {
            // Дефолтната опаковка ще се добавя/обновява ако е вече добавена
            $r = (object)array('productId' => $productRec->id, 'packagingId' => $obj->packagingId, 'quantity' => $obj->quantity);
            if($id = self::getPack($productRec->id, $obj->packagingId)->id){
                $r->id = $id;
            }
            
            // и ще се запише промяната ако не е използвана
            if(!self::isUsed($productRec->id, $obj->packagingId, TRUE)){
                self::save($r);
            }
        }
    }
}
