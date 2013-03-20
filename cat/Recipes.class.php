<?php



/**
 * Модел  Рецептурник
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_Recipes extends core_Master {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Рецептурник';
    
    
    /**
     * Заглавие
     */
    var $singleTitle = 'Рецепта за себестойност';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, productId, uom, groups, state, createdOn, createdBy, modifiedOn, modifiedBy';
    
    
    /**
	 * Коментари на статията
	 */
	var $details = 'cat_RecipeDetails';
	
	
	/**
	 * Брой рецепти на страница
	 */
	var $listItemsPerPage = '40';
	
	
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, cat_Wrapper, cat_RecipeWrapper, doc_DocumentPlg,
    	 plg_Printing, bgerp_plg_Blank, plg_Sorting, plg_Search';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от 
     * таблицата.
     */
    var $rowToolsField = 'tools';

    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/legend.png';
    
    
    /**
     * Кой може да чете
     */
    var $canRead = 'cat, admin';
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'cat, admin';
    
 
    /**
     * Файл с шаблон за единичен изглед
     */
    var $singleLayoutFile = 'cat/tpl/SingleLayoutRecipes.shtml';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "10.1|Каталог";
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('productId', 'key(mvc=cat_Products, select=name)', 'caption=Продукт,width=18em');
    	$this->FLD('uom', 'key(mvc=cat_UoM, select=name, allowEmpty)', 'caption=Мярка,notSorting,width=18em');
    	$this->FLD('info', 'text(rows=4)', 'caption=Информация,width=18em');
    	$this->FLD('groups', 'keylist(mvc=cat_RecipeGroups, select=title)', 'caption=Групи, mandatory');
    	$this->FLD('state','enum(draft=Чернова, active=Активиран, rejected=Оттеглен)', 'caption=Статус, input=none');
    
    	$this->setDbUnique('productId,uom');
    }
    
    
    /**
     * Обработка след изпращане на формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()) {
    		if($form->rec->measureId) {
    			$productUom = cat_Products::fetchField($form->rec->productId, 'measureId');
    			$productUomRec = cat_UoM::fetch($productUom);
    			$uomRec = cat_UoM::fetch($form->rec->uom);
    			if($uomRec->baseUnitId != $productUom && $uomRec->baseUnitId != $productUomRec->baseUnitId) {
    				$form->setError('uom', 'Избраната мярка не е от същата група като основната мярка на продукта');
    			}
    		}
    	}
    }
    
    
    /**
   	 * Обработка на SingleToolbar-a
   	 */
   	static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$data->toolbar->addBtn('Изчисли', array($mvc, 'calcPrice', $data->rec->id));
    }
   
    
    /**
     * Извлича продуктите които съставят даден продукт
     * @TODO
     * @param int $productId - ид на продукт
     * @param int $quantity - количество от продукта
     * @return array $results - масив с обекти на съставящите го
     * продукти
     */
    public static function getIngredients($productId, $quantity = 1)
    {
    	$results = array();
    	expect($productRec = cat_Products::fetch($productId));
    	$rec = static::fetchByProduct($productId, NULL);
    	
    	$query = cat_RecipeDetails::getQuery();
    	$query->where("#recipeId = {$rec->id}");
    	while($detail = $query->fetch()){
    		$obj = new stdClass();
    		$obj->productId = $detail->dProductId;
    		$obj->quantity = $detail->quantity;
    		$results[$detail->id] = $obj;
    	}
    	
    	return $results;
    }
    
    
 	/**
   	 * Обработка на SingleToolbar-a
   	 */
   	static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$data->toolbar->addBtn('Калкулиране на себестойности', array($mvc, 'calcAll'));
    }
    
    
	/**
	 * @TODO
	 */
    public function getAllowedProducts()
    {
    	$notAllowed = array();
    	$catQuery = cat_Products::getQuery();
    	$products = $catQuery->fetchAll();
    	$recipeQuery = $this->getQuery();
    	while($rec = $recipeQuery->fetch()){
    		$this->filterProducts($rec->productId, $rec->uom,  $notAllowed);
    	}
    	bp($notAllowed);
    }
    
    
    /**
	 * @TODO
	 */
    function filterProducts($productId, $uom, &$notAllowed)
    {
    	if(in_array($productId, $notAllowed)) return;
    	$notAllowed[] = $productId;
    	
    	$rec = $this->fetchByProduct($productId, $uom);
    	if(!$rec) return;
    	
    	$detailQuery = cat_RecipeDetails::getQuery();
    	$detailQuery->where("#recipeId = {$rec->id}");
    	while($detail = $detailQuery->fetch()){
    		$this->filterProducts($detail->dProductId, $detail->dUom, $notAllowed);
    	}
    }
    
    
    
    public static function fetchByProduct($productId)
    {
    	$query = static::getQuery();
    	$query->where("#productId = {$productId}");
    	return $query->fetch();
    }
    
    
    function act_Test(){
    	$cost = static::calcCost(7);
    	bp($cost);
    }
    
    
    /** 
     * @TODO;
     * Enter description here ...
     * @param unknown_type $productId
     * @param unknown_type $quantity
     * @param unknown_type $datetime
     */
    public static function calcCost($productId, $quantity = 1, $datetime = NULL)
    {
    	$price = 0;
    	$conf = core_Packs::getConfig('price');
    	
    	$ingredients = static::getIngredients($productId, $quantity);
    	
    	if(!$ingredients) {
    		$ruleRec = price_ListRules::fetch("#productId = {$productId} && #listId = {$conf->PRICE_LIST_COST}");
    		if(!$ruleRec->price){
    			$ruleRec->price = 0;
    		}
    		
    		return $ruleRec->price;
    	} else {
    		foreach($ingredients as $ing){
    			$recipeRec = static::fetchByProduct($ing->productId);
    			
	    		if($recipeRec){
	    			$pPrice = static::calcCost($recipeRec->productId, $recipeRec->quantity, $datetime);
	    			$price += $pPrice;
	    			
	    		} else {
	    			$ruleRec = price_ListRules::fetch("#productId = {$productId} && #listId = {$conf->PRICE_LIST_COST}");
	    			$price += $ruleRec->price;
	    		}
    		}
    	}
    	
    	return $price;
    }
    
    
    /**
     * Изпълнява се след създаване на нова рецепта
     */
    function on_AfterCreate($mvc, $id)
    {
    	// Обновяване на броя рецепти във всяка група
    	cat_RecipeGroups::updateCount();
    }
    
    
    /**
     * Изчислява себестойноста на всички листвани рецепти и ги
     * записва в модел себестойности
     */
    function act_calcAll()
    {
    	//@TODO
    }
    
    
    /**
     * 
     */
    function act_calcPrice()
    {
    	$this->requireRightFor('read');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	$data = new stdClass();
    	$data->rec = $rec;
    	$data->row = $this->recToverbal($rec);
    	$this->prepareCalcPrice($data);
    	if($data->form) {
        	$rec = $data->form->input();
            $this->requireRightFor('add', $data->rec);
            if ($data->form->isSubmitted()){
            	
            	Redirect(array($this, 'single', $data->rec->id), FALSE, 'TEST');
            }
    	}
    	
    	$tpl = $this->renderWrapping($data->form->renderHtml());
    	return $tpl;
    }
    
    
    /**
     * 
     */
    private function prepareCalcPrice(&$data)
    {
    	$form = cls::get("core_Form");
    	$form->FNC('uom', 'key(mvc=cat_UoM, select=name)', 'input,caption=Мярка,width=11em');
    	$form->FNC('quantity', 'int', 'input,caption=Количество,width=11em');
    	$form->toolbar->addSbBtn("Изчисли");
    	$form->title = tr("Изчисляване на себестойност на продукт: {$data->row->productId}");
    	$data->form = $form;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
		//@TODO
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = "Отчет за бърза продажба №{$rec->id}";
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;

        return $row;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    static function getHandle($id)
    {
    	$rec = static::fetch($id);
    	$self = cls::get(get_called_class());
    	
    	return $self->abbr . $rec->id;
    }
}