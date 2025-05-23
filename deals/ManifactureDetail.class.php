<?php


/**
 * Клас 'deals_ManifactureDetail' - базов клас за детайли на производствени документи
 *
 * @category  bgerp
 * @package   mp
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class deals_ManifactureDetail extends doc_Detail
{
    /**
     * Какви продукти да могат да се избират в детайла
     *
     * @var enum(canManifacture=Производими,canConvert=Вложими)
     */
    protected $defaultMeta;


    /**
     * Какви продукти не могат да се избират в детайла
     *
     * @var enum(canManifacture=Производими,canConvert=Вложими)
     */
    protected $defaultNotHaveMeta;


    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'createdBy,createdOn,requestedQuantity';
    
    
    /**
     * Да се показва ли кода като в отделна колона
     */
    public $showCodeColumn = true;


    /**
     * Да се сумират ли редовете при импорт
     */
    public $combineImportRecs = true;


    /**
     * Може ли да се импортират цени
     */
    public $allowPriceImport = false;


    /**
     * След описанието на модела
     */
    public static function on_AfterDescription(&$mvc)
    {
        // Дефолтни имена на полетата от модела
        setIfNot($mvc->packQuantityFld, 'packQuantity');
        setIfNot($mvc->quantityInPackFld, 'quantityInPack');
        setIfNot($mvc->quantityFld, 'quantity');
        setIfNot($mvc->productFld, 'productId');
        setIfNot($mvc->packagingFld, 'packagingId');
    }
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function setDetailFields($mvc)
    {
        $mvc->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax,titleFld=name,forceOpen)', 'class=w100,caption=Артикул,mandatory', 'tdClass=productCell leftCol wrap,silent,removeAndRefreshForm=quantity|measureId|packagingId|packQuantity|isOutsourced');
        $mvc->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка', 'tdClass=small-field nowrap,smartCenter,mandatory,input=hidden,silent');
        $mvc->FNC('packQuantity', 'double(min=0)', 'caption=Количество,input=input,mandatory,smartCenter');
        $mvc->FLD('quantityInPack', 'double(smartRound)', 'input=none,notNull,value=1');
        
        $mvc->FLD('quantity', 'double', 'caption=Количество,input=none,smartCenter');
        $mvc->FLD('measureId', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,input=hidden');
        $mvc->FLD('notes', 'richtext(rows=3,bucket=Notes)', 'caption=Допълнително->Забележки,formOrder=110001');

        $mvc->setDbIndex('productId,packagingId');
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('id', 'ASC');
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (!isset($rec->quantity) || !isset($rec->quantityInPack)) {
            
            return;
        }
        
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
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
        setIfNot($data->defaultMeta, $mvc->defaultMeta);
        setIfNot($data->defaultNotHaveMeta, $mvc->defaultNotHaveMeta);

        if (!$data->defaultMeta && !$data->defaultNotHaveMeta) return;

        $params = array();
        if(isset($data->defaultMeta)){
            $params['hasProperties'] = $data->defaultMeta;
        }
        if(isset($data->defaultNotHaveMeta)){
            $params['hasnotProperties'] = $data->defaultNotHaveMeta;
        }
        $form->setFieldTypeParams('productId', $params);
        
        if (isset($form->rec->id) && $data->action != 'replaceproduct') {
            $data->form->setReadOnly('productId');
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        $rec = &$form->rec;
        
        if ($rec->productId) {
            $measureId = cat_Products::fetchField($rec->productId, 'measureId');
            $form->setDefault('measureId', $measureId);
            
            if($form->_replaceProduct !== true){
                $packs = cat_Products::getPacks($rec->productId, $rec->packagingId);

                // Ако е само една разрешената мярка да се зареди тя
                if(isset($rec->_onlyAllowedPackId)){
                    $packs = array_intersect_key($packs, array($rec->_onlyAllowedPackId => $rec->_onlyAllowedPackId));
                }

                $form->setOptions('packagingId', $packs);
                $form->setDefault('packagingId', key($packs));
                $form->setField('packagingId', 'input');
            } else {
                $form->rec->packagingId = $measureId;
            }
        }
        
        if ($form->isSubmitted()) {
            $productInfo = cat_Products::getProductInfo($rec->productId);
            $rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
            
            if ($rec->productId) {
                if ($rec->productId) {
                    $rec->measureId = $productInfo->productRec->measureId;
                }
            }
            
            $rec->quantity = $rec->packQuantity * $rec->quantityInPack;
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'edit' || $action == 'delete' || $action == 'add') && isset($rec)) {
            if ($mvc->Master->fetchField($rec->{$mvc->masterKey}, 'state') != 'draft') {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'replaceproduct' && isset($rec)) {
            $masterState = $mvc->Master->fetchField($rec->{$mvc->masterKey}, 'state');
            if (!in_array($masterState, array('pending', 'draft'))) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (!empty($data->toolbar->buttons['btnAdd']) && isset($mvc->defaultMeta)) {
            unset($data->toolbar->buttons['btnAdd']);
            $products = cat_Products::getByProperty($mvc->defaultMeta, null, 1);
            
            if (!countR($products)) {
                $error = 'error=Няма артикули, ';
            }
            
            $data->toolbar->addBtn('Артикул', array($mvc, 'add', $mvc->masterKey => $data->masterId, 'ret_url' => true), "id=btnAdd,{$error} order=10,title=Добавяне на артикул",'ef_icon = img/16/shopping.png');
        }
    }
    
    
    /**
     * Преди подготвяне на едит формата
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->productId = cat_Products::getVerbal($rec->productId, 'name');
        if(!(Mode::is('text', 'xhtml') || Mode::is('printing') || Mode::is('pdf'))){
            $singleUrl = cat_Products::getSingleUrlArray($rec->productId);
            $row->productId = ht::createLinkRef($row->productId, $singleUrl);
        }
        deals_Helper::addNotesToProductRow($row->productId, $rec->notes);
        
        // Показваме подробната информация за опаковката при нужда
        deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
        if (empty($rec->quantity) && !Mode::isReadOnly()) {
            $row->ROW_ATTR['style'] = ' background-color:#f1f1f1;color:#777';
        }
    }
    
    /**
     * Импортиране на артикул генериран от ред на csv файл
     *
     * @param int   $masterId - ид на мастъра на детайла
     * @param array $row      - Обект представляващ артикула за импортиране
     *                        ->code - код/баркод на артикула
     *                        ->quantity - К-во на опаковката или в основна мярка
     *                        ->price - цената във валутата на мастъра, ако няма се изчислява директно
     *                        ->pack - Опаковката
     *
     * @return mixed - резултата от експорта
     */
    public function import($masterId, $row)
    {
        $Master = $this->Master;

        $pRec = cat_Products::getByCode($row->code);
        $pRec->packagingId = (isset($pRec->packagingId)) ? $pRec->packagingId : $row->pack;
        $meta = cat_Products::fetch($pRec->productId, $this->metaProducts);
       
        if (!$meta->metaProducts) { 
            $masterThresdId = $Master::fetchField($masterId, 'threadId');
            
            if (doc_Threads::getFirstDocument($masterThresdId)->className == 'sales_Sales') {
                $meta = $meta->canSell;
            } elseif (doc_Threads::getFirstDocument($masterThresdId)->className == 'purchase_Purchases') {
                $meta = $meta->canBuy;
            }elseif (doc_Threads::getFirstDocument($masterThresdId)->className == 'planning_Jobs') {
                $meta = $meta->canConvert;;
            }
        }

        if ($meta != 'yes') return;
        
        $productInfo = cat_Products::getProductInfo($pRec->productId);
        $quantityInPack = ($productInfo->packagings[$pRec->packagingId]) ? $productInfo->packagings[$pRec->packagingId]->quantity : 1;
        $packQuantity = $row->quantity;
        $batch = is_array($row->batches) ? $row->batches : $row->batch;

        return $Master::addRow($masterId, $pRec->productId,$pRec->packagingId, $packQuantity, $quantityInPack, false, null, false, $batch);
    }


    /**
     * Връща броя на складируемите артикули в детайла
     *
     * @param $masterId
     * @return int
     */
    public static function getStorableProductsCount($masterId)
    {
        $me = cls::get(get_called_class());
        $dQuery = $me->getQuery();

        $dQuery->where("#{$me->masterKey} = {$masterId} AND #canStore = 'yes'");
        $dQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');

        return $dQuery->count();
    }
}
