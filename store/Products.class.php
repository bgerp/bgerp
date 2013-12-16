<?php



/**
 * Продукти
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_Products extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Продукти';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, store_Wrapper, plg_Search';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,store';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,store';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,store';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,store';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,store';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, tools=Пулт, name, storeId, quantity, quantityNotOnPallets, quantityOnPallets, makePallets';
    
    
    /**
     * Полета за търсене
     */
    var $searchField = 'name';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'int', 'caption=Име,remember=info,oldFieldName=name');
        $this->FLD('classId', 'class(interface=cat_ProductAccRegIntf, select=title)', 'caption=Мениджър,silent,input=hidden');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад');
        $this->FLD('quantity', 'int', 'caption=Количество->Общо');
        $this->FNC('quantityNotOnPallets', 'int', 'caption=Количество->Непалетирано,input=hidden');
        $this->FLD('quantityOnPallets', 'int', 'caption=Количество->На палети,input=hidden');
        $this->FNC('makePallets', 'varchar(255)', 'caption=Палетиране');
        $this->FNC('name', 'varchar(255)', 'caption=Пълно име');
        
        $this->setDbUnique('productId, classId, storeId');
    }
    
    
    /**
     * Изчисляване на заглавието спрямо продуктовия мениджър
     */
    public function on_CalcName(core_Mvc $mvc, $rec)
    {
    	if(empty($rec->productId) || empty($rec->classId)){
    		return;
    	}
    	
    	return $rec->name = cls::get($rec->classId)->getTitleById($rec->productId);
    }
    
    
    /**
     * Смяна на заглавието
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListTitle($mvc, $data)
    {
        // Взема селектирания склад
        $selectedStoreName = store_Stores::getCurrent('name');
        
        $data->title = "|Продукти в СКЛАД|* \"{$selectedStoreName}\"";
    }
    
    
    /**
     * Извличане записите само от избрания склад
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $selectedStoreId = store_Stores::getCurrent();
        $data->query->where("#storeId = {$selectedStoreId}");
    }
    
    
	/**
      * Добавя ключови думи за пълнотекстово търсене
      */
     function on_AfterGetSearchKeywords7($mvc, &$res, $rec)
     {
    	// Извличане на ключовите, вслучая името на продукта
     	$object = new core_ObjectReference($rec->classId, $rec->productId);
    	$keywords = $object->getTitleById();
    	
     	$res = plg_Search::normalizeText($keywords);
    	$res = " " . $res;
     }
     
     
    /**
     * При добавяне/редакция на палетите - данни по подразбиране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        expect($ProductManager = cls::get($rec->classId));
        
    	if (empty($rec->id)) {
            $form->setOptions('productId', $ProductManager::getByProperty('canStore'));
        } else {
            $form->setOptions('productId', array($rec->productId => $ProductManager->getTitleById($rec->productId)));
        }
        
        $form->setReadOnly('storeId', store_Stores::getCurrent());
    }
    
    
    /**
     * Изпълнява се след конвертирането на $rec във вербални стойности
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $pInfo = cls::get($rec->classId)->getProductInfo($rec->productId);
        $measureShortName = cat_UoM::getShortName($pInfo->productRec->measureId);
        
        if (haveRole('ceo,store')) {
            $row->makePallets = Ht::createBtn('Палетиране', array('store_Pallets', 'add', 'productId' => $rec->id));
        }
        
        $row->quantity .= ' ' . $measureShortName;
        $row->quantityOnPallets .= ' ' . $measureShortName;
        $row->quantityNotOnPallets = $rec->quantity - $rec->quantityOnPallets . ' ' . $measureShortName;
    }
    
    
    /**
     * Филтър
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->title = 'Търсене';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->showFields = 'search';
        
        // Активиране на филтъра
        $recFilter = $data->listFilter->input();
        
        // Ако филтъра е активиран
        if ($data->listFilter->isSubmitted()) {
            if ($recFilter->productIdFilter) {
                $condProductId = "#id = '{$recFilter->productIdFilter}'";
            }
            
            if ($condProductId) $data->query->where($condProductId);
        }
    }
    
	
	/**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	if (!empty($data->toolbar->buttons['btnAdd'])) {
            $productManagers = core_Classes::getOptionsByInterface('cat_ProductAccRegIntf');
            $masterRec = $data->masterData->rec;
            $addUrl = $data->toolbar->buttons['btnAdd']->url;
            
            foreach ($productManagers as $manId => $manName) {
            	$productMan = cls::get($manId);
            	$products = $productMan::getByProperty('canStore');
                if(!count($products)){
                	$error = "error=Няма складируеми {$productMan->title}";
                }
                
                $data->toolbar->addBtn($productMan->singleTitle, $addUrl + array('classId' => $manId),
                    "id=btnAdd-{$manId},{$error},order=10", 'ef_icon = img/16/shopping.png');
            	unset($error);
            }
            
            unset($data->toolbar->buttons['btnAdd']);
        }
    }
}