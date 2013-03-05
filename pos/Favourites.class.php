<?php



/**
 * Мениджър за "PoS Продукти" 
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class pos_Favourites extends core_Manager {
    
    /**
     * Заглавие
     */
    var $title = "Продукти";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_Rejected, 
    				 plg_Printing, pos_Wrapper, pos_FavouritesWrapper, plg_State2';

    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, productId, pointId, catId, image, createdOn, createdBy, state';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
	
	/**
     * Кой може да го прочете?
     */
    var $canRead = 'admin, pos';
    
    
    /**
     * Кой може да променя?
     */
    var $canAdd = 'admin, pos';
    
    
    /**
     * Кой може да променя?
     */
    var $canEdit = 'pos, admin';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'admin, pos';
    
	
	/**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('productId', 'key(mvc=cat_Products, select=name)', 'caption=Продукт, mandatory');
    	$this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Опаковка');
    	$this->FLD('catId', 'key(mvc=pos_FavouritesCategories, select=name)', 'caption=Категория, mandatory');
    	$this->FLD('pointId', 'keylist(mvc=pos_Points, select=title)', 'caption=Точка на Продажба');
    	$this->FLD('image', 'fileman_FileType(bucket=pos_ProductsImages)', 'caption=Картинка');
    	
    	$this->setDbUnique('productId, packagingId');
    }
    
    
    /**
     * Извиква се след подготовката на формата
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	// намираме дефолт контрагента на текущата точка на продажба
    	$contragentId = pos_Points::defaultContragent();
    	$policyId = pos_Points::fetchField(pos_Points::getCurrent(), 'policyId');
    	$Policy = cls::get($policyId);
    	$data->form->setOptions('productId', $Policy->getProducts(crm_Persons::getClassId(), $contragentId));
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от Request
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
    	if($form->isSubmitted()) {
    		$rec = &$form->rec;
    		
    		$productInfo = cat_Products::getProductInfo($rec->productId, $rec->packagingId);
    		if(!$productInfo) {
    			$form->setError('productId', 'Избрания продукт не поддържа посочената опаковка');
    		}
    	}
    }
    
    
    /**
     * Метод подготвящ продуктите и формата за филтриране
     * @return stdClass - обект съдържащ пос продуктите за текущата
     * точка и формата за филтриране
     */
    public static function prepareProducts(){
    	$self = cls::get(get_called_class());
    	$productsArr = $self->preparePosProducts();
    	$categoriesArr = pos_FavouritesCategories::prepareAll();
    	
    	return (object)array('arr' => $productsArr, 'categories' => $categoriesArr);
    }
    
    
    /**
     * Подготвя продуктите за показване в пос терминала
     * @return array $arr - масив от всички позволени продукти
     */
    function preparePosProducts()
    {
    	$varchar = cls::get('type_Varchar');
    	$double = cls::get('type_Double');
    	$double->params['decimals'] = 2;
    	$cache = core_Cache::get('pos_Favourites', 'products');
    	$array = array();
    	
    	// Коя е текущата точка на продажба и нейния дефолт контрагент
    	$posRec = pos_Points::fetch(pos_Points::getCurrent());
    	$defaultPosContragentId = pos_Points::defaultContragent($posRec->id);
    	$crmPersonsClassId = crm_Persons::getClassId();
    	$Policy = cls::get($posRec->policyId);
    	
    	$query = static::getQuery();
    	$query->where("#pointId IS NULL");
    	$query->orWhere("#pointId LIKE '%{$posRec->id}%'");
    	$query->where("#state = 'active'");
    	while($rec = $query->fetch()){
    		if(!$cache[$rec->id]) {
    			$obj = $this->prepareProductObject($rec);
	    		$obj->code = $varchar->toVerbal($obj->code);
	    		$obj->name = $varchar->toVerbal($obj->name);
	    		
	    		// Цена на продукта от ценовата политика
	    		$price = $Policy->getPriceInfo($crmPersonsClassId, $defaultPosContragentId, $rec->productId, $rec->packagingId, $obj->quantity, dt::verbal2mysql());
	    		$obj->price = $double->toVerbal($price->price);
    			$cache[$rec->id] = $obj;
    		}
    		 
    		$array[$rec->id] = $cache[$rec->id];
    	}
    	
    	core_Cache::set('pos_Favourites', 'products', $array, 300);
    	return $array;
    }
    
    
    /**
     * Подготвяме единичен продукт от модела
     * @param stdClass $rec - запис от модела
     * @return stdClass $obj - обект с информацията за продукта
     */
    function prepareProductObject($rec)
    {
    	// Информацията за продукта с неговата опаковка
    	$info = cat_Products::getProductInfo($rec->productId, $rec->packagingId);
    	$productRec = $info->productRec;
    	$packRec = $info->packagingRec;
    	$arr['name'] = $productRec->name;
    	$arr['catId'] = $rec->catId;
        $obj = new stdClass();
    	if($packRec) {
    		$obj->quantity = $packRec->quantity;
    		($packRec->customCode) ? $code = $packRec->customCode : $code = $packRec->eanCode;
    	} else {
    		$obj->quantity = 1;
    		($productRec->code) ? $code = $productRec->code : $code = $productRec->eanCode;
    	}
    	$arr['code'] = $code;
    	$arr['image'] = $rec->image;
    		
    	return (object)$arr;
    }
    
    
    /**
     * Рендираме PoS продуктите и техните категории в подходящ вид
     * @param stdClass $data - обект съдържащ масивите с продуктите,
     * категориите и темата по подразбиране
     * @return core_ET $tpl - шаблона с продуктите
     */
    public static function renderPosProducts($data)
    {
    	$tpl = new ET(getFileContent($data->theme.'/Favourites.shtml'));
    	$self = cls::get(get_called_class());
    	$self->renderProducts($data->arr, $tpl);
    	$self->renderCategories($data->categories, $tpl);
    	
    	return $tpl;
    }
    
    
    /**
     * Рендира категориите на продуктите в удобен вид
     * @param array $categories - Масив от продуктовите категории
     * @param core_ET $tpl - шаблона в който ще поставяме категориите
     */
    function renderCategories($categories, &$tpl)
    {
    	$blockTpl = $tpl->getBlock('CAT');
    	foreach($categories as $cat) {
    		$rowTpl = clone($blockTpl);
    		$rowTpl->placeObject($cat);
    		$rowTpl->removeBlocks();
    		$rowTpl->append2master();
    	}
    }
    
    
    /**
     * Рендира продуктите във вид подходящ за пос терминала
     * @param array $products - масив от продукти с информацията за тях
     * @return core_ET $tpl - шаблон с продуктите
     */
	function renderProducts($products, &$tpl)
	{
    	$blockTpl = $tpl->getBlock('ITEM');
		$baseCurrency = acc_Periods::getBaseCurrencyCode();
		
		$attr = array('isAbsolute' => FALSE, 'qt' => '');
        $size = array(80, 'max' => TRUE);
    	foreach($products as $row) {
    		if($row->image) {
    			$imageUrl = thumbnail_Thumbnail::getLink($row->image, $size, $attr);
    			$row->image = ht::createElement('img', array('src' => $imageUrl, 'width'=>'90px', 'height'=>'90px'));
    		}
    		$rowTpl = clone($blockTpl);
    		$rowTpl->replace($baseCurrency, 'baseCurrency');
    		$rowTpl->placeObject($row);
    		$rowTpl->removeBlocks();
    		$rowTpl->append2master();
    	}
	}
    
	
    /**
     * Вербална обработка на продуктите
     */
    static function on_AfterRecToVerbal ($mvc, $row, $rec)
    {
    	$varchar = cls::get('type_Varchar');
    	if($rec->image) {
    		$Fancybox = cls::get('fancybox_Fancybox');
			$row->image = $Fancybox->getImage($rec->image, array(30, 30), array(400, 400));
    	}
    	
    	// До името на продукта показваме неговата основна мярка и ако
    	// има зададена опаковка - колко броя в опаковката има.
    	$info = cat_Products::getProductInfo($rec->productId, $rec->packagingId);
    	$measureRow = cat_UoM::fetchField($info->productRec->measureId, 'shortName');
    	$measureRow = $varchar->toVerbal($measureRow);
    	if($info->packagingRec) {
    		$packName = cat_Packagings::fetchField($rec->packagingId, 'name');
    		$packName = $varchar->toVerbal($packName);
    		$quantity = $info->packagingRec->quantity;
    		$pack = " , {$quantity} {$measureRow} в {$packName}";
    	} else {
    		$pack = " , 1 {$measureRow}";
    	}
    	
    	if(!$rec->pointId) {
    		$row->pointId = tr('Всички');
    	}
    	
    	$row->productId .= $pack;
    }
}