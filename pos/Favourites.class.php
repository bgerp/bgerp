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
    var $loadList = 'plg_Created, plg_RowTools, plg_Rejected, plg_Printing,
    				 plg_State, pos_Wrapper';

    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "PoS Продукт";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, productId, packagingId, pointId, image, createdOn, createdBy, state';
    
    
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
     * @TODO 
     * @return array $arr - 
     */
    static function preparePosProducts()
    {
    	$arr = array();
    	$varchar = cls::get('type_Varchar');
    	$posId = pos_Points::getCurrent();
    	$query = static::getQuery();
    	$query->where("#pointId LIKE '%{$posId}%'");
    	while($rec = $query->fetch()){
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
    		$priceCls = cls::get('cat_PricePolicyMockup');
    		$price = $priceCls->getPriceInfo(NULL, NULL, $rec->productId, $rec->packagingId, $obj->quantity, dt::verbal2mysql());
    		$obj->price = $price->price;
    		$obj->image  = $rec->image;
    		$arr[$rec->id] = $obj;
    	}
    	
    	return $arr;
    }
    
    
    /**
     * @TODO
     * @param array $arr - 
     * @return core_ET $tpl -
     */
	static function renderPosProducts($arr)
	{
    	$tpl = new ET(getFileContent('pos/tpl/Favourites.shtml'));
		$blockTpl = $tpl->getBlock('ITEM');
		
		$attr = array('isAbsolute' => FALSE, 'qt' => '');
        $size = array(80, 'max'=>TRUE);
    	foreach($arr as $row) {
    		$row->image = thumbnail_Thumbnail::getLink($row->image, $size, $attr);
    		$rowTpl = clone($blockTpl);
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
    	
    }
}