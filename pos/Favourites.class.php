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
    var $title = "PoS Продукти";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_Rejected, plg_Printing, pos_Wrapper, plg_State2';

    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "PoS Продукт";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, productId, pointId, image, createdOn, createdBy, state';
    
    
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
    	$this->FLD('pointId', 'keylist(mvc=pos_Points, select=title)', 'caption=Точки на Продажба, mandatory');
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
     */
    static function prepareProducts(){
    	$me = cls::get(get_called_class());
    	$productsArr = $me->preparePosProducts();
    	$filterForm = $me->prepareSearchForm();
    	
    	return (object)array('arr'=>$productsArr, 'filter'=> $filterForm);
    }
    
    
    /**
     * метод подготвящ формата за филтриране на продуктите
     */
    function prepareSearchForm() {
    	$form = cls::get('core_Form');
    	//@TODO
    	return $form;
    }
    
    
    /**
     * Подготвя продуктите за показване в пос терминала
     * @return array $arr - 
     */
    function preparePosProducts()
    {
    	$arr = array();
    	$varchar = cls::get('type_Varchar');
    	$double = cls::get('type_Double');
    	$double->params['decimals'] = 2;
    	
    	// Коя е текущата точка на продажба и нейния дефолт контрагент
    	$posRec = pos_Points::fetch(pos_Points::getCurrent());
    	$defaultPosContragentId = pos_Points::defaultContragent($posRec->id);
    	$crmPersonsClassId = crm_Persons::getClassId();
    	$Policy = cls::get($posRec->policyId);
    	
    	$query = static::getQuery();
    	$query->where("#pointId LIKE '%{$posRec->id}%'");
    	$query->where("#state = 'active'");
    	
    	while($rec = $query->fetch()){
    		
    		// Информацията за продукта с неговата опаковка
    		$info = cat_Products::getProductInfo($rec->productId, $rec->packagingId);
    		$productRec = $info->productRec;
    		$packRec = $info->packagingRec;
    		$obj = new stdClass();
    		$obj->name = $varchar->toVerbal($productRec->name);
    		if($packRec) {
    			$obj->quantity = $packRec->quantity;
    			($packRec->customCode) ? $code = $packRec->customCode : $code = $packRec->eanCode;
    		} else {
    			$obj->quantity = 1;
    			($productRec->code) ? $code = $productRec->code : $code = $productRec->eanCode;
    		}
    		$obj->code = $varchar->toVerbal($code);
    		
    		// Цена на продукта от ценовата политика
    		$price = $Policy->getPriceInfo($crmPersonsClassId, $defaultPosContragentId, $rec->productId, $rec->packagingId, $obj->quantity, dt::verbal2mysql());
    		$obj->price = $double->toVerbal($price->price);
    		$obj->image  = $rec->image;
    		$arr[$rec->id] = $obj;
    	}
    	
    	return $arr;
    }
    
    
    /**
     * Рендира продуктите във вид подходящ за пос терминала
     * @param array $arr - масив от продукти с информацията за тях
     * @return core_ET $tpl - шаблон с продуктите
     */
	static function renderPosProducts($data)
	{
    	$tpl = new ET(getFileContent('pos/tpl/Favourites.shtml'));
		$blockTpl = $tpl->getBlock('ITEM');
		$baseCurrency = acc_Periods::getBaseCurrencyCode();
		
		$attr = array('isAbsolute' => FALSE, 'qt' => '');
        $size = array(80, 'max'=>TRUE);
    	foreach($data->arr as $row) {
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
    	
    	return $tpl;
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
    	
    	if($rec->packagingId) {
    		$packName = cat_Packagings::fetchField($rec->packagingId, 'name');
    		$pack = " , &nbsp;{$varchar->toVerbal($packName)}";
    	} else {
    		$productRec = cat_Products::fetch($rec->productId);
    		$productRow = cat_Products::recToVerbal($productRec, 'measureId');
    		$pack = " , &nbsp;{$productRow->measureId}";
    	}
    	
    	$row->productId .= $pack;
    }
}