<?php



/**
 * Списък с листвани артикули за клиента/доставчика
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_ListingDetails extends doc_Detail
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'crm_ext_ProductListToContragents';
	
	
	/**
	 * Кой  може да изтрива?
	 */
	public $canDelete = 'cat, ceo';
	
	
	/**
	 * Кой  може да добавя?
	 */
	public $canAdd = 'cat, ceo';
	
	
	/**
	 * Кой  може да листва?
	 */
	public $canList = 'debug';
	
	
	/**
	 * Кой  може да редактира?
	 */
	public $canEdit = 'cat, ceo';
	
	
	/**
	 * Име на поле от модела, външен ключ към мастър записа
	 */
	public $masterKey = 'listId';
	
	
	/**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId=Артикул,packagingId=Опаковка,reff=Техен код,moq,modifiedOn,modifiedBy';
			

    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Modified, cat_Wrapper, plg_RowTools2, plg_SaveAndNew, plg_RowNumbering';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Артикул за листване';
    
    
    /**
     * Заглавие
     */
    public $title = 'Артикули за листване';
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Брой на страница
     */
    public $listItemsPerPage = 20;
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     *
     *  @var string
     */
    public $hideListFieldsIfEmpty = 'moq';
    
    
	/**
	 * Описание на модела (таблицата)
	 */
	function description()
	{
		$this->FLD('listId', 'key(mvc=cat_Listings,select=id)', 'caption=Лист,silent,mandatory');
		$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,notNull,mandatory', 'tdClass=productCell leftCol wrap,silent,removeAndRefreshForm=packagingId|canSell|canBuy,caption=Артикул');
    	$this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка', 'smartCenter,tdClass=small-field nowrap,silent,caption=Опаковка,input=hidden,mandatory');
    	$this->FLD('reff', 'varchar(32)', 'caption=Техен код,smartCenter');
    	$this->FLD('moq', 'double(smartRound,Min=0)', 'caption=МКП||MOQ');
    	$this->FLD('canSell', 'enum(yes=Да,no=Не)', 'input=none');
    	$this->FLD('canBuy', 'enum(yes=Да,no=Не)', 'input=none');
    	
    	$this->setDbUnique('listId,productId,packagingId');
    	$this->setDbUnique('listId,reff');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна
	 */
	protected static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		$rec = $form->rec;
		$mvc->currentTab = 'Листване';
		$masterRec = $data->masterRec;
		
		if(empty($rec->id)){
			$Cover = doc_Folders::getCover($masterRec->folderId);
			if($Cover->haveInterface('crm_ContragentAccRegIntf')){
				$products = cat_Products::getProducts($Cover->getClassId(), $Cover->that, NULL, 'canStore', NULL, NULL, TRUE);
			} else {
				$products = cat_Products::getProducts(NULL, NULL, NULL, 'canStore', NULL, NULL, TRUE);
			}
			
			$products = array('' => '') + $products;
		} else {
			$products = array($rec->productId => cat_Products::getRecTitle(cat_Products::fetch($rec->productId), FALSE));
		}
		
		$form->productOptions = $products;
		$form->setOptions('productId', $products);
		
		// Ако е избран артикул, показва се и опаковката му
		if(isset($rec->productId)){
			$packs = cat_Products::getPacks($rec->productId);
			$form->setField('packagingId', 'input');
			$form->setOptions('packagingId', $packs);
			$form->setDefault('packagingId', key($packs));
		}
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 */
	protected static function on_AfterInputEditForm($mvc, &$form)
	{
		$rec = $form->rec;
		if($form->isSubmitted()){
			
			// Ако няма код
			if(empty($rec->reff)){
				
				// И има такава опаковка, взима се ЕАН кода
				if($pack = cat_products_Packagings::getPack($rec->productId, $rec->packagingId)){
					$rec->reff = (!empty($pack->eanCode)) ? $pack->eanCode : NULL;
				}
				
				// Ако още не е намерен код, взима се кода на артикула
				if(empty($rec->reff)){
					$rec->reff = cat_Products::fetchField($rec->productId, 'code');
				}
				
				if(empty($rec->reff)){
					$form->setError('reff', 'Трябва да бъде въведен код');
				}
			}
			
			if(!empty($rec->moq)){
				if(!deals_Helper::checkQuantity($rec->packagingId, $rec->moq, $warning)){
					$form->setError('moq', $warning);
				}
			}
			
			if(!$form->gotErrors()){
				$pRec = cat_Products::fetch($rec->productId, 'canBuy,canSell');
				$rec->canSell = $pRec->canSell;
				$rec->canBuy = $pRec->canBuy;
			}
		}
	}
	
	
	/**
	 * След подготовката на заглавието на формата
	 */
	protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
	{
		$rec = $data->form->rec;
		
		// Махане на бутона запис и нов, ако няма достатъчно записи
		if(count($data->form->productOptions) <= 1){
			$data->form->toolbar->removeBtn('saveAndNew');
		}
	}
	
	
	/**
	 * Извиква се след подготовката на toolbar-а за табличния изглед
	 */
	protected static function on_AfterPrepareListToolbar($mvc, &$data)
	{
		// Добавяне на бутони
		$masterRec = $data->masterData->rec;
		$data->toolbar->removeBtn('btnAdd');
		
		if($mvc->haveRightFor('add', (object)array('listId' => $masterRec->id))){
			$data->toolbar->addBtn('Артикул', array($mvc, 'add', 'listId' => $masterRec->id, 'ret_url' => TRUE), NULL, 'ef_icon = img/16/shopping.png,title=Добавяне на нов артикул за листване');
			$data->toolbar->addBtn('Импорт', array($mvc, 'import', 'listId' => $masterRec->id, 'ret_url' => TRUE), NULL, 'ef_icon=img/16/import.png,title=Импортиране на артикули');
		}
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec)){
			if(empty($rec->listId)){
				$requiredRoles = 'no_one';
			} else {
				$state = cat_Listings::fetchField($rec->listId, 'state');
				if($state != 'draft'){
					$requiredRoles = 'no_one';
				}
			}
		}
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид
	 */
	protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		if(isset($fields['-list'])){
			$row->productId = cat_Products::getShortHyperlink($rec->productId);
			$row->reff = "<b>{$row->reff}</b>";
			 
			$listRec = cat_Listings::fetch($rec->listId, 'folderId');
			$Cover = doc_Folders::getCover($listRec->folderId);
			 
			if($Cover->haveInterface('crm_ContragentAccRegIntf')){
				$policyInfo = cls::get('price_ListToCustomers')->getPriceInfo($Cover->getClassId(), $Cover->that, $rec->productId, $rec->packagingId, 1);
				
				if(!isset($policyInfo->price)){
					$row->productId = ht::createHint($row->productId, 'Артикулът няма цена по ценовата политика на контрагента', 'warning', FALSE);
					$row->productId = ht::createElement("span", array('style' => 'color:#755101'), $row->productId);
				}
			}
		}
	}
	
	
	/**
	 * Кешира и връща всички листвани артикули за клиента
	 * 
	 * @param int|stdClass $listId
	 */
	public static function getAll($listId)
	{
		expect($listRec = cat_Listings::fetchRec($listId));
		
		// Ако няма наличен кеш за контрагента, извлича се наново
		if(!isset(self::$cache[$listRec->id])){
			self::$cache[$listRec->id] = array();
			
			// Кои са листваните артикули за контрагента
			$query = self::getQuery();
			$query->where("#listId = {$listRec->id}");
			$query->orderBy('id', 'ASC');
			
			// Добавя се всеки запис, групиран според типа
			while($rec = $query->fetch()){
				$obj = (object)array('productId' => $rec->productId, 'packagingId' => $rec->packagingId, 'reff' => $rec->reff, 'moq' => $rec->moq);
				self::$cache[$listRec->id][$rec->id] = $obj;
			}
		}
		
		// Връщане на кешираните данни
		return self::$cache[$listRec->id];
	}
	
	
	/**
	 * Помощна ф-я връщаща намерения артикул и опаковка според кода
	 * 
	 * @param mixed $cClass          - ид на клас
	 * @param int $cId               - ид на контрагента
	 * @param varchar $reff          - код за търсене
	 * @return NULL|stdClass         - обект с ид на артикула и опаковката или NULL ако няма
	 */
	public static function getProductByReff($cClass, $cId, $reff)
	{
		// Взимане от кеша на листваните артикули
		$all = self::getAll($cClass, $cId);
		
		// Оставят се само тези записи, в които се среща кода
		$res = array_filter($all, function (&$e) use ($reff) {
			if($e->reff == $reff){
				return TRUE;
			}
				
			return FALSE;
		});
		
		// Ако има първи елемент, взима се той
		$firstFound = $res[key($res)];
		$reff = (is_object($firstFound)) ? (object)array('productId' => $firstFound->productId, 'packagingId' => $firstFound->packagingId) : NULL;
		
		return $reff;
	}
	
	
	/**
	 * Помощна ф-я връщаща намерения код според артикула и опаковката, ако няма опаковка
	 * се връща първия намерен код
	 *
	 * @param mixed $cClass          - ид на клас
	 * @param int $cId               - ид на контрагента
	 * @param int $productId         - ид на артикул
	 * @param int|NULL $packagingId  - ид на опаковка, NULL ако не е известна
	 * @return varchar|NULL          - намерения код или NULL
	 */
	public static function getReffByProductId($cClass, $cId, $productId, $packagingId = NULL)
	{
		if(!isset($cClass) && !isset($cId)) return NULL;
		
		// Извличане на всичките листвани артикули
		$all = self::getAll($cClass, $cId);
		
		// Намират се записите за търсения артикул
		$res = array_filter($all, function (&$e) use ($productId, $packagingId) {
			if(isset($packagingId)){
				if($e->productId == $productId && $e->packagingId == $packagingId){
					return TRUE;
				}
			} else{
				if($e->productId == $productId){
					return TRUE;
				}
			}
				
			return FALSE;
		});
		
		// Ако има намерен поне един запис се връща кода
		$firstFound = $res[key($res)];
		$reff = (is_object($firstFound)) ? $firstFound->reff : NULL;
		
		// Връща се намерения код
		return $reff;
	}
	
	
	/**
	 * Екшън за импорт на артикули за листване
	 */
	function act_Import()
	{
		// Проверки за права
		$this->requireRightFor('add');
		expect($listId = Request::get('listId', 'int'));
		expect($listRec = cat_Listings::fetch($listId));
		$this->requireRightFor('add', (object)array('listId' => $listRec->id));
			
		// Подготовка на формата
		$form = cls::get('core_Form');
		$form->method = 'POST';
		$form->title = "Импортиране на артикули за листване в|*" . cat_Listings::getHyperlink($listId);
		$form->FLD('listId', 'int', "input=hidden,silent");
		
		$Cover = doc_Folders::getCover($listRec->folderId);
		if($Cover->haveInterface('crm_ContragentAccRegIntf')){
			$form->FLD('from', 'enum(,group=Група,sales=Предишни продажби)', "caption=Избор,removeAndRefreshForm=fromDate|toDate|selected,silent");
		} else {
			$form->FLD('from', 'enum(,group=Група)', "caption=Избор,removeAndRefreshForm=fromDate|toDate|selected,silent");
			$form->setDefault('from', 'group');
			$form->setReadOnly('from');
		}
		
		$form->FLD('code', 'enum(code=Наш код,barcode=Баркод)', "caption=Техен код");
		$form->FLD('fromDate', 'date', "caption=От,input=hidden,silent,removeAndRefreshForm=category|selected");
		$form->FLD('toDate', 'date', "caption=До,input=hidden,silent,removeAndRefreshForm=category|selected");
		$form->FLD('group', 'key(mvc=cat_Groups,select=name,allowEmpty)', "caption=Група,input=hidden,silent,removeAndRefreshForm=selected|fromDate|toDate");
		
		// Инпутване на скритите полета
		$form->input(NULL, 'silent');
		$form->input();
			
		$submit = FALSE;
		
		// Ако е избран източник на импорт
		if(isset($form->rec->from)){
			$rec = $form->rec;
			
			// И той  е група
			if($rec->from == 'group'){
				
				// Показваме полето за избор на група и намиране на артикулите във нея
				$form->setField('group', 'input');
				if(isset($rec->group)){
					$products = $this->getFromGroup($rec->group, $rec->listId);
				
					if(!$products ){
						$form->setError('from,group', 'Няма артикули за импортиране от групата');
					}
				}
			} else {
				
				// Ако е избрано от последни продажби, показват се полетата за избор на период
				$form->setField('fromDate', 'input');
				$form->setField('toDate', 'input');
					
				// И се извличат артикулите от продажбите в този период на контрагента
				if(!empty($rec->fromDate) || !empty($rec->toDate)){
					$products = $this->getFromSales($rec->fromDate, $rec->toDate, $cClass, $contragentId);
				}
			}
		
			// Ако има намерени продукти показват се в друго поле за избор, чекнати по подразбиране
			if(isset($products) && count($products)){
				$set = cls::get('type_Set', array('suggestions' => $products));
				$form->FLD('selected', 'varchar', 'caption=Артикули,mandatory');
				$form->setFieldType('selected', $set);
				$form->input('selected');
				$form->setDefault('selected', $set->fromVerbal($products));
					
				$submit = TRUE;
			}
		}
		
		// Ако е събмитната формата
		if($form->isSubmitted()){
			$products = type_Set::toArray($form->rec->selected);
			expect(count($products));
			
			$error = $toSave = array();
			
			// Проверяване на избраните артикули
			foreach($products as $productId){
				$toSave[$productId]['productId'] = $productId;
				
				// Опаковката е основната мярка/опаковка
				$toSave[$productId]['packagingId'] = key(cat_Products::getPacks($productId));
				
				// Ако е избрано кода да е барков се изчлича, ако няма ще се показва грешка
				if($rec->code == 'barcode'){
					$pack = cat_products_Packagings::getPack($toSave[$productId]['productId'], $toSave[$productId]['packagingId']);
					if(isset($pack) && !empty($pack->eanCode)){
						$toSave[$productId]['reff'] = $pack->eanCode;
					} else {
						$error[] = cat_Products::getTitleById($productId, FALSE);
					}
				} else {
					
					// Ако не се иска баркод, се попълва за код кода на артикула, ако няма ид-то му
					$code = cat_Products::fetchField($productId, 'code');
					$toSave[$productId]['reff'] = (!empty($code)) ? $code : $productId;
				}
			}
			
			// Ако има грешки 
			if(count($error)){
				$error = "Артикулите|* <b>" . implode(', ', $error) . "</b> |нямат баркод на тяхната основна опаковка/мярка|*";
				$form->setError('selected', $error);
			} else {
				
				// Ако няма се добавят избраните артикули
				$count = 0;
				foreach ($toSave as $r){
					$newRec = (object)$r;
					$newRec->listId = $listRec->id;
					$this->save($newRec, NULL, 'REPLACE');
					$count++;
				}
				
				// Редирект
				followRetUrl(NULL, "Импортирани са|* '{$count}' |артикула|*");
			}
		}
		
		// Ако няма избрани артикули, бутона за импорт е недостъпен
		if($submit === TRUE){
			$form->toolbar->addSbBtn('Импорт', 'save', 'ef_icon = img/16/import.png, title = Импорт');
		} else {
			$form->toolbar->addBtn('Импорт', array(), 'ef_icon = img/16/import.png, title = Импорт');
		}
		
		$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
			
		// Рендиране на опаковката
		$tpl = $this->renderWrapping($form->renderHtml());
		
		return $tpl;
	}
	
	
	/**
	 * Помощен метод извличащ всички артикули за листване от дадена група
	 * 
	 * @param int $group
	 * @param int $listId
	 * @return array $products
	 */
	private function getFromGroup($group, $listId)
	{
		$products = array();
		$cDescendants = cat_Groups::getDescendantArray($group);
		$alreadyIn = arr::extractValuesFromArray(self::getAll($listId), 'productId');
		
		// Извличане на всички активни, продаваеми артикули от дадената група и нейните подгрупи
		$query = cat_Products::getQuery();
		$query->likeKeylist('groups', $cDescendants);
		$query->notIn('id', $alreadyIn);
		$query->where("#state = 'active'");
		$query->where("#canSell = 'yes' || #canBuy = 'yes'");
		$query->show('isPublic,folderId,meta,id,code,name');
		
		while($rec = $query->fetch()){
			$products[$rec->id] = static::getRecTitle($rec, FALSE);
		}
		
		// Връщане на намерените артикули
		return $products;
	}
	
	
	/**
	 * Помщен метод за намиране на всички продадени артикули на контрагента
	 * 
	 * @param date $from
	 * @param date $to
	 * @param mixed $cClass
	 * @param int $contragentId
	 * @return array $products
	 */
	public function getFromSales($from, $to, $cClass, $contragentId)
	{
		$products = array();
		$alreadyIn = arr::extractValuesFromArray(self::getAll($cClass, $contragentId), 'productId');
		
		// Извличане на всички продавани артикули на контрагента, които не са листвани все още
		$query = sales_SalesDetails::getQuery();
		$query->EXT('valior', 'sales_Sales', 'externalName=valior,externalKey=saleId');
		$query->EXT('contragentClassId', 'sales_Sales', 'externalName=contragentClassId,externalKey=saleId');
		$query->EXT('contragentId', 'sales_Sales', 'externalName=contragentId,externalKey=saleId');
		$query->EXT('state', 'sales_Sales', 'externalName=state,externalKey=saleId');
		$query->where("#contragentClassId = {$cClass} AND #contragentId = {$contragentId}");
		$query->where("#state = 'active' || #state = 'closed'");
		
		if(!empty($from)){
			$query->where("#valior >= '{$from}'");
		}
		
		if(!empty($to)){
			$query->where("#valior <= '{$to}'");
		}
		
		$query->notIn('id', $alreadyIn);
		$query->show('productId');
		
		while($rec = $query->fetch()){
			$products[$rec->productId] = cat_Products::getTitleById($rec->productId, FALSE);
		}
		
		// Връщане на намерените артикул
		return $products;
	}
}