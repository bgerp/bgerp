<?php



/**
 * Модел  Разходни норми
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class price_ConsumptionNorms extends core_Master {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Разходни Норми';
    
    
    /**
     * Заглавие
     */
    var $singleTitle = 'Разходна норма';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, productId, uom, groups, state, createdOn, createdBy, modifiedOn, modifiedBy';
    
    
    /**
	 * Детайли на рецептата
	 */
	var $details = 'price_ConsumptionNormDetails';
	
	
	/**
	 * Брой Разходна норми на страница
	 */
	var $listItemsPerPage = '40';
	
	
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, price_Wrapper, doc_DocumentPlg,
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
    var $canRead = 'price, ceo';
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'price, ceo';
    
    
    /**
     * Кой може да пише
     */
    var $canAdd = 'price, ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'price,ceo';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'price,ceo';
    
	/**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'productId';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    var $singleLayoutFile = 'price/tpl/SingleLayoutConsumptionNorm.shtml';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "6.4|Счетоводни";
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'productId, info';
    
    
    /**
     * За продуктите от кои групи могат да бъдат правени Разходни норми
     * @see cat_Groups
     */
    public static $normProductGroups = array('products', 'prefabrications', 'services');
    
    
    /**
     * Продуктите от кои групи могат да бъдат включвани като съставка
     * на нормата
     * @see cat_Groups
     */
    public static $ingredientProductGroups = array(
    							'materials',
    							'labor',
    							'externalServices',
    							'prefabrications',
    							'consumables',);
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('productId', 'key(mvc=cat_Products, select=name)', 'caption=Продукт');
    	$this->FLD('uom', 'key(mvc=cat_UoM, select=name, allowEmpty)', 'caption=Мярка');
    	$this->FLD('info', 'text(rows=4)', 'caption=Информация');
    	$this->FLD('groups', 'keylist(mvc=price_ConsumptionNormGroups, select=title)', 'caption=Групи, mandatory');
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
    				$form->setError('uom', "Избраната мярка не е от същата група като основната мярка на продукта (" . cat_Uom::getTitleById($productUom) . ')');
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
    	$detailQuery = $mvc->price_ConsumptionNormDetails->getQuery();
    	$detailQuery->where("#normId = {$data->rec->id}");
    	if($detailQuery->count()){
    		
    		// Не може да се изчислява цената на продукт, ако няма съставки
    		$data->toolbar->addBtn('Изчисли', array($mvc, 'calcPrice', $data->rec->id), NULL, 'ef_icon=img/16/calculator.png');
    	}
    	
    	if($data->rec->state == 'active' && price_Lists::haveRightFor('single')){
	    	$conf = core_Packs::getConfig('price');
	    	$data->toolbar->addBtn('Ценова история', array('price_Lists', 'single', price_ListRules::PRICE_LIST_COST, 'product' => $data->rec->productId), NULL, 'ef_icon=img/16/money_dollar.png');
    	}
    }
   
    
    /**
     * Извиква се след подготовката на формата
     */
	public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$options = $mvc->getProductOptions($data->form->rec);
	    $data->form->setOptions('productId', $options);
    }
    
    
    /**
     * Филтрира продуктите които могат да се добавят като начало на нормата
     * Ако добавяме нова нормата: Зареждаме само продуктите, които са в
     * 							  позволените групи, и изключваме тези
     * 							  от тях които вече са нормати
     * Ако редактираме нормата: Зареждаме всички продукти от позволените
     * 							групи но изключваме продуктите които вече
     * 							са съставка на норматите				
     * @param stdClass $rec - запис от модела
     * @return array $options- опции с позволени продукти
     * 
     */
    private function getProductOptions($rec)
    {
    	$children = array();
	    
    	// зареждаме само продуктите, които могат да имат нормати
    	$productsArr = cat_Products::getByGroup(static::$normProductGroups);
    	
    	if($rec->id){
    		
    		// При редакция се подсигуряваме че не може продукт
    		// който е съставка на нормата да се добави като нейн начален
	    	$this->getChildren($rec->productId, $children, TRUE);
	    } else {
	    	
	    	// При нова норма, изключваме продуктите, имащи вече норма
    		$query = $this->getQuery();
    		while($childRec = $query->fetch()){
    			$children[$childRec->productId] = $childRec->productId;
    		}
    	}
    	
    	$options = array_diff_key($productsArr, $children);
	    
    	return $options;
    }
    
    
    /**
     * Помощна функция която записва в един масив всички
     * продукти които са част от дървото на Разходната нормата
     * @param int $productId - id на продукта
     * @param array $children - масив събиращ децата
     * @param boolean $root - дали poductId е корена на дървото
     */
    private function getChildren($productId, &$children, $root = FALSE)
    {
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
    	
    	$query = price_ConsumptionNormDetails::getQuery();
    	$query->where("#normId = {$rec->id}");
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
	    		
	    		// Ако е задействан филтъра, добавяме параметрите му към екшъна
	    		$url = array_merge($url, (array)$data->listFilter->rec);
	    	}
	    	
	    	$data->toolbar->addBtn('Калкулиране на себестойности', $url, NULL, 'ef_icon=img/16/calculator.png,warning=Наистинали искате да изчислите себестойностите на показваните продукти?');
    	}
    	
    	if(price_ListRules::haveRightFor('read')){
    		$conf = core_Packs::getConfig('price');
    		$data->toolbar->addBtn('Себестойности', array('price_Lists', 'single', price_ListRules::PRICE_LIST_COST), NULL, 'ef_icon=img/16/view.png');
    	}
    }
    
	
    /**
     * Филтриране на всички възможни продукти които могат
     * да се добавят към дадена Разходна норма. Премахват се всички
     * онези продукти, които имат за съставка въпросната норма
     * @param int $id - id на Разходната норма
     * @param int $detailId - id на детайл
     * @return array - масив с позволените продукти
     */
    public function getAllowedProducts($id, $detailId)
    {
    	// Кой продукт ще търсим във всички Разходни норми
    	$needle = $this->fetchField($id, 'productId');
    	$productsArr = $notAllowed = array();
    	
    	// За всяка Разходна норма проверяваме дали съдържа въпросния
    	// продукт, ако да добавяме нейния продукт в списък
    	// на неразрешените продукти
    	$query = $this->getQuery();
    	while($rec = $query->fetch()){
    		$this->searchProduct($rec->productId, $notAllowed, $needle);
    	}
    	
    	// Изключваме и продуктите, които вече са част от Разходната норма
    	$dQuery = price_ConsumptionNormDetails::getQuery();
    	$dQuery->where("#normId = {$id}");
    	if($detailId){
    		$dQuery->where("#id != {$detailId}");
    	}
    	while($detail = $dQuery->fetch()){
    		if(!array_key_exists($detail->dProductId, $notAllowed)){
    			$notAllowed[$detail->dProductId] = $detail->dProductId;
    		}
    	}
    	
    	// Намираме всички продукти от каталога
    	$productsArr = cat_Products::getByGroup(static::$ingredientProductGroups);
    	$options = array_diff_key($productsArr,$notAllowed);
    	if(!count($options)) return Redirect(array($this, 'single', $id), FALSE, 'Не може да се добавят нови съставки');
    	
    	// Връщаме тези продукти, които не част от $notAllowed
    	return $options;
    }
    
    
    /**
     * Рекурсивно обхождаме дървото на Разходната норма и търсим дали
     * тя съдържа някъде определен продукт, ако да то добавяме
     * всички продукти които са част от дървото към масив.
     * @param int $productId - текущия продукт
     * @param array $notAllowed - масив където се добавят
     * забранените продукти
     * @param int $needle - продукт, който търсим
     * @param array $path - пътя до продукта в дървото
     */
    private function searchProduct($productId, &$notAllowed, &$needle, $path = array())
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
    			
    			// Обхождаме всяка съставка на Разходната норма
	    		$res = $this->searchProduct($ing->productId, $notAllowed, $needle, $path);
	    	}
    	}
    }
    
    
    /**
     * Извлича Разходна норма по продукт
     * @param int $productId - id на продукт
     * @return stdClass - запис на Разходна норма
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
    	
    	$ingredients = static::getIngredients($productId);
   		if($ingredients) {
	   		foreach($ingredients as $ing){
			    $pPrice = static::calcCost($ing->productId, $ing->quantity, $datetime, $ing->uom);
				$price += $pPrice;
			}
   		} else {
	    	$price = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $productId, NULL, $datetime);
    		expect($price, "Проблем при изчислението на себестойноста на продукт: {$productId}");
    	}
    	
		$total = $quantity * $price;
    	$total = cat_UoM::convertValue($total, $uom, $productUomId);
    	return round($total, 2);
    }
    
    
    /**
     * Изпълнява се след създаване на нова Разходна норма
     */
    function on_AfterCreate($mvc, $id)
    {
    	// Обновяване на броя Разходни норми във всяка група
    	price_ConsumptionNormGroups::updateCount();
    }
    
    
    /**
     * Изчислява себестойноста на всички листвани Разходни норми и ги
     * записва в модел себестойности
     */
    function act_calcAll()
    {
    	$this->requireRightFor('write');
    	$count = 0;
    	$query = $this->getQuery();
    	$query->where("#state = 'active'");
    	
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
    	
    	if($id = Request::get('normId', 'int')){
    		$query->where("#id = {$id}");
    	}
    	
    	while($rec = $query->fetch()) {
    		$listRec = new stdClass();
    		$listRec->listId = price_ListRules::PRICE_LIST_COST;
    		$listRec->productId = $rec->productId;
    		$listRec->price = price_ConsumptionNorms::calcCost($rec->productId, 1, NULL, $rec->uom);
    		$listRec->type = 'value';
    		$listRec->validFrom = dt::now();
    		if($listRec->price){
    			price_ListRules::save($listRec);
    			$count++;
    		}
    	}
    	
    	return followRetUrl(array($this, 'list'), "Изчислени са себестойностите на {$count} продукта");
    }
    
    
    /**
     * Екшън който показва форма и калкулира цената на продукта
     */
    function act_calcPrice()
    {
    	$this->requireRightFor('read');
    	$data = new stdClass();
    	expect($id = Request::get('id', 'int'));
    	expect($data->rec = $this->fetch($id));
    	$data->row = $this->recToverbal($data->rec);
    	$this->prepareCalcPrice($data);
    	if($data->form) {
        	$rec = $data->form->input();
        	if($rec->quantity <= 0){
        		$data->form->setError('quantity', 'Неправилно количество');
        	}
            $this->requireRightFor('add', $data->rec);
            if ($data->form->isSubmitted()){
            	$price = price_ConsumptionNorms::calcCost($data->rec->productId, $rec->quantity, NULL, $rec->uom);
            	$currency = acc_Periods::getBaseCurrencyCode();
            	$selMeasure = cat_UoM::getTitleById($rec->uom);
            	$msg = tr("|Себестойноста  на|* {$rec->quantity} {$selMeasure} {$data->row->productId} е {$price} <span class='cCode'>{$currency}</span>");
            	Mode::setPermanent('msg', $msg);
            	return Redirect(array($this, 'single', $data->rec->id));
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
    	$form->FNC('uom', 'key(mvc=cat_UoM, select=name)', 'input,caption=Мярка');
    	$form->FNC('quantity', 'int', 'input,caption=Количество');
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
			$icon = sbf("img/16/wooden-box.png");
			$row->productId = ht::createLink($row->productId, array('cat_Products', 'single', $rec->productId), NULL, "style=background-image:url({$icon}),class=linkWithIcon");
		
			$dQuery = $mvc->price_ConsumptionNormDetails->getQuery();
			$dQuery->where("#normId = {$rec->id}");
			$row->ingCount = $dQuery->count();
			if($msg = Mode::get('msg')){
				$row->price = $msg;
				Mode::setPermanent('msg', NULL);
			}
		}
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = $this->singleTitle ." №{$rec->id}";
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
		$row->recTitle = $row->title;
        
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
	 *  След подготовка на филтър формата
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{	
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->view = 'horizontal';
		$data->listFilter->FNC('gr', 'key(mvc=price_ConsumptionNormGroups, select=title, allowEmpty)', 'placeholder=Група,silent');
		$data->listFilter->FNC('measure', 'key(mvc=cat_UoM, select=name, allowEmpty)', 'caption=Мярка,silent');
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
			$query = $mvc->price_ConsumptionNormDetails->getQuery();
			$query->where("#normId = {$rec->id}");
			if(!$rec || $query->count() == 0){
				
				// Ако не сме създали още Разходна норма или няма
				// съставки никой не може да активира
				$res = 'no_one';
			}
		}
	}
	
	
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        return FALSE;

        $folderClass = doc_Folders::fetchCoverClassName($folderId);
    
        return $folderClass == 'cat_Products';
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     * 
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
	public static function canAddToThread($threadId)
    {

return FALSE;

		$folderId = doc_Threads::fetchField($threadId, 'folderId');
        $coverClass = doc_Folders::fetchCoverClassName($folderId);
        
        return $coverClass == 'cat_Products';
    }
    
    
	/**
	 * След запис на документа
	 */
	public static function on_AfterSave($mvc, &$id, $rec)
    {
    	if($rec->state == 'active'){
    		
    		// След като документа се активира, изчисляваме и
    		// записваме неговата себестойност
    		Redirect(array($mvc, 'calcAll', 'normId' => $rec->id, 'ret_url' => array($mvc, 'single', $id)));
    	}
   	}
}