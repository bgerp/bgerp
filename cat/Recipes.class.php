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
    	 plg_Printing, bgerp_plg_Blank, plg_Sorting, plg_Search, doc_ActivatePlg';
    
    
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
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'productId';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    var $singleLayoutFile = 'cat/tpl/SingleLayoutRecipes.shtml';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "10.1|Каталог";
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'productId, info';
    
    
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
    		$productUom = cat_Products::fetchField($form->rec->productId, 'measureId');
    		
    		if($form->rec->uom) {
    			$similarMeasures = cat_UoM::getSameTypeMeasures($productUom);
    			if(!array_key_exists($form->rec->uom, $similarMeasures)) {
    				$form->setError('uom', 'Избраната мярка не е от същата група като основната мярка на продукта');
    			}
    		} else {
    			$form->rec->uom = $productUom;
    		}
    	}
    }
    
    
    /**
   	 * Обработка на SingleToolbar-a
   	 */
   	static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$detailQuery = $mvc->cat_RecipeDetails->getQuery();
    	$detailQuery->where("#recipeId = {$data->rec->id}");
    	if($detailQuery->count()){
    		
    		// Неможе да се изчислява цената на продукт, ако няма съставки
    		$data->toolbar->addBtn('Изчисли', array($mvc, 'calcPrice', $data->rec->id), NULL, 'ef_icon=img/16/calculator.png');
    	}
    }
   
    
    /**
     * Извиква се след подготовката на формата
     */
	public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	if($data->form->rec->id){
    		
    		// При редакция се подсигуряваме че неможе продукт
    		// който е съставка на рецептата да се добави като
    		// нейн начален
	    	$productsArr = $children = array();
	    	$mvc->getChildren($data->form->rec->productId, $children, TRUE);
	    	
	    	// Намираме всички продукти от каталога
	    	$catQuery = cat_Products::getQuery();
	    	while($catRec = $catQuery->fetch()){
	    		$productsArr[$catRec->id] = $catRec->name;
	    	}
	    	
	    	$options = array_diff_key($productsArr,$children);
	    	$data->form->setOptions('productId', $options);
    	}
    }
    
    
    /**
     * Помощна функция която записва в един масив всички
     * пеосукти които са част от дървото на рецептата
     * @param int $productId - Id на продукта
     * @param array $children - Масив събиращ децата
     * @param boolean $root - Дали poductId е корена на дървото
     */
    private function getChildren($productId, &$children, $root = FALSE){
    	if(!array_key_exists($productId, $children) && !$root){
    		$children[$productId] = $productId;
    	}
    	$ingredients = static::getIngredients($productId);
    	if($ingredients){
    		foreach($ingredients as $ing){
    			$res = $this->getChildren($ing->productId, $children);
	    	}
    	}
    }
    
    
    /**
     * Извлича продуктите които съставят даден продукт
     * @param int $productId - ид на продукт
     * @param int $quantity - количество от продукта
     * @return array $results - масив с обекти на
     * съставящите го продукти
     */
    public static function getIngredients($productId, $quantity = 1)
    {
    	$results = array();
    	expect($productRec = cat_Products::fetch($productId));
    	$rec = static::fetchByProduct($productId);
    	if(!$rec) return FALSE;
    	
    	$query = cat_RecipeDetails::getQuery();
    	$query->where("#recipeId = {$rec->id}");
    	while($detail = $query->fetch()){
    		$obj = new stdClass();
    		$obj->productId = $detail->dProductId;
    		$obj->uom = $detail->dUom;
    		$obj->quantity = $quantity * $detail->quantity;
    		$results[$detail->id] = $obj;
    	}
    	
    	return $results;
    }
    
    
 	/**
   	 * Обработка на SingleToolbar-a
   	 */
   	static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	// Да предавам филтър формата на екшъна !!!
    	if(price_ListRules::haveRightFor('add')){
	    	($data->recs) ? $url = array($mvc, "calcAll") : $url = NULL;
	    	if($data->listFilter && $url){
	    		
	    		// Ако е задействан филтъра, добавяме
	    		// параметрите му към екшъна
	    		$recArr = (array)$data->listFilter->rec;
	    		foreach($recArr as $i => $v){
	    			$url[$i] = $v;
	    		}
	    	}
	    	
	    	$data->toolbar->addBtn('Калкулиране на себестойности', $url, NULL, 'ef_icon=img/16/calculator.png,warning=Наистинали искате да изчислите себестойностите на показваните продукти?');
    	}
    	
    	if(price_ListRules::haveRightFor('read')){
    		$conf = core_Packs::getConfig('price');
    		$data->toolbar->addBtn('Себестойности', array('price_Lists', 'single', $conf->PRICE_LIST_COST), NULL, 'ef_icon=img/16/view.png');
    	}
    }
    
	
    /**
     * Филтриране на всички възможни продукти които могат
     * да се добавят към дадена рецепта. Премахват се всички
     * онези продукти, които имат за съставка въпросната рецепта
     * @param int $id - id на рецепта
     * @return array - масив с позволените продукти
     */
    function getAllowedProducts($id)
    {
    	// Кой продукт ще търсим във всички рецепти
    	$needle = $this->fetchField($id, 'productId');
    	$notAllowed = array();
    	$productsArr = array();
    	
    	// За всяка рецепта проверяваме дали съдържа въпросния
    	// продукт, ако да добавяме нейния продукт в списък
    	// на неразрешените продукти
    	$query = $this->getQuery();
    	while($rec = $query->fetch()){
    		$this->searchProduct($rec->productId, $notAllowed, $needle);
    	}
    	
    	// Изключваме и продуктите, които вече са част от рецептата
    	$dQuery = cat_RecipeDetails::getQuery();
    	$dQuery->where("#recipeId = {$id}");
    	while($detail = $dQuery->fetch()){
    		if(!array_key_exists($detail->dProductId, $notAllowed)){
    			$notAllowed[$detail->dProductId] = $detail->dProductId;
    		}
    	}
    	
    	// Намираме всички продукти от каталога
    	$catQuery = cat_Products::getQuery();
    	while($catRec = $catQuery->fetch()){
    		$productsArr[$catRec->id] = $catRec->name;
    	}
    	
    	// Връщаме тези продукти, които не част от $notAllowed
    	return array_diff_key($productsArr,$notAllowed);
    }
    
    
    /**
     * Рекурсивно обхождаме дървото на рецепта и търсим дали
     * тя съдържа някъде определен продукт, ако да то добавяме
     * всички продукти които са част от дървото към масив.
     * @param int $productId - текущия продукт
     * @param array $notAllowed - Масив където се добавят
     * забранените продукти
     * @param int $needle - продукт, който търсим
     * @param array $path - пътя до продукта в дървото
     */
    function searchProduct($productId, &$notAllowed, &$needle, $path = array())
    {
    	$path[] = $productId;
    	
    	// Ако текущия продукт е търсения продукт
    	if($needle == $productId){
    		foreach($path as $p){
    			
    			// За всеки продукт в пътя до намерения ние го
    			// добавяме в масива notAllowed, ако той, вече не е там 
    			if(!array_key_exists($p, $notAllowed)){
    				$notAllowed[$p] = $p;
    			}
    		}
    		return;
    	}
    	
    	$ingredients = static::getIngredients($productId);
    	if($ingredients){
    		foreach($ingredients as $ing){
    			
    			// Обхождаме всяка съставка на рецептата
	    		$res = $this->searchProduct($ing->productId, $notAllowed, $needle, $path);
	    	}
    	}
    }
    
    
    /**
     * Извлича рецепта по продукт
     * @param int $productId - Id на продукт
     */
    public static function fetchByProduct($productId)
    {
    	$query = static::getQuery();
    	$query->where("#productId = {$productId}");
    	return $query->fetch();
    }
    
    
    /** 
     * Изчислява себестойноста на продукта
     * @param int $productId - id на продукта
     * @param int $quantity - к-во на подукта
     * @param datetime $datetime - дата
     * @param int $uom - мярка на продукта
     * @return double - цената на продукта
     */
    public static function calcCost($productId, $quantity = 1, $datetime = NULL, $uom = NULL)
    {
    	$price = 0;
    	$productUomId = cat_Products::fetchField($productId, 'measureId');
    	
    	$ingredients = static::getIngredients($productId, $quantity);
   		if($ingredients) {
	   		foreach($ingredients as $ing){
			    	$pPrice = static::calcCost($ing->productId, $ing->quantity, $datetime, $ing->uom);
				    $price += $pPrice;
			}
   		} else {
	    	$conf = core_Packs::getConfig('price');
    		$price = price_ListRules::getPrice($conf->PRICE_LIST_COST, $productId, NULL, $datetime);
    		expect($price, "Проблем при изчислението на себестойноста на продукт: {$productId}");
    	}
    	
		$total = $quantity * $price;
    	$total = cat_UoM::convertValue($total, $uom, $productUomId);
    	return round($total, 2);
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
    	$this->requireRightFor('write');
    	$count = 0;
    	$conf = core_Packs::getConfig('price');
    	$query = $this->getQuery();
    	
    	// Предаваме параметрите получени от филтър формата
    	if($gr = Request::get('gr')){
    		$query->where("#groups LIKE '%|{$gr}|%'");
    	}
    	if($measure = Request::get('measure')){
    		$query->where("#uom = {$measure}");
    	}
    	if($search = Request::get('search')){
    		plg_Search::applySearch($search, $query);
    	}
    	
    	while($rec = $query->fetch()) {
    		$listRec = new stdClass();
    		$listRec->listId = $conf->PRICE_LIST_COST;
    		$listRec->productId = $rec->productId;
    		$listRec->price = cat_Recipes::calcCost($rec->productId, 1, NULL, $rec->uom);
    		$listRec->type = 'value';
    		$listRec->validFrom = dt::now();
    		if($listRec->price){
    			$count++;
    			price_ListRules::save($listRec);
    		}
    	}
    	
    	return Redirect(array($this, 'list'), FALSE, "Изчислени са себестойностите на {$count} продукта");
    }
    
    
    /**
     * Екшън който показва форма и калкулира цената на продукта
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
        	if($rec->quantity <= 0){
        		$data->form->setError('quantity', 'Неправилно количество');
        	}
            $this->requireRightFor('add', $data->rec);
            if ($data->form->isSubmitted()){
            	$price = cat_Recipes::calcCost($data->rec->productId, $rec->quantity, NULL, $rec->uom);
            	
            	return Redirect(array($this, 'single', $data->rec->id), FALSE, "Себестойноста  на {$rec->quantity}  {$data->row->productId} е {$price}");
            }
    	}
    	
    	$tpl = $this->renderWrapping($data->form->renderHtml());
    	return $tpl;
    }
    
    
    /**
     * Подготовка на формата за изчисление на цената
     */
    private function prepareCalcPrice(&$data)
    {
    	$form = cls::get("core_Form");
    	$form->FNC('uom', 'key(mvc=cat_UoM, select=name)', 'input,caption=Мярка,width=11em');
    	$form->FNC('quantity', 'int', 'input,caption=Количество,width=11em');
    	if(!$data->rec->uom){
    		$data->rec->uom = cat_Products::fetchField($data->rec->productId, 'measureId');
    	}
    	$form->setOptions('uom', cat_UoM::getSameTypeMeasures($data->rec->uom));
    	$form->setDefault('quantity', '1');
    	$form->setDefault('uom', $data->rec->uom);
    	$form->toolbar->addSbBtn("Изчисли");
    	$form->title = tr("Изчисляване на себестойност на продукт") . "|*:{$data->row->productId}";
    	$data->form = $form;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
		
		if($fields['-single']){
			$icon = sbf("img/16/package-icon.png");
			$row->productId = ht::createLink($row->productId, array('cat_Products', 'single', $rec->productId), NULL, "style=background-image:url({$icon}),class=linkWithIcon");
		}
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
    
    
    /**
	 *  Филтриране на статиите по ключови думи и категория
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{	
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        $data->listFilter->view = 'horizontal';
		$data->listFilter->FNC('gr', 'key(mvc=cat_RecipeGroups, select=title, allowEmpty)', 'width=9em,silent');
		$data->listFilter->FNC('measure', 'key(mvc=cat_UoM, select=name, allowEmpty)', 'width=9em,caption=Мярка,silent');
		$data->listFilter->showFields = 'search,gr,measure';
		$data->listFilter->input();
		if($filter = $data->listFilter->rec) {
			if($group = Request::get('gr', 'int')){
				$data->query->where("#groups LIKE '%|{$group}|%'");
			}
			if($filter->search){
				plg_Search::applySearch($filter->search, $data->query);
			}
			if($filter->measure){
				$data->query->where("#uom = {$filter->measure}");
			}
		}
	}
    
	
	/**
     * След обработка на ролите
     */
	static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'activate') {
			$query = $mvc->cat_RecipeDetails->getQuery();
			$query->where("#recipeId = {$rec->id}");
			if(!$rec || $query->count() == 0){
				
				// Ако не сме създали още рецептата или няма
				// съставки никой неможе да активира
				$res = 'no_one';
			}
		}
	}
}