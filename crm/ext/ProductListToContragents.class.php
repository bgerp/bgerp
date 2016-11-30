<?php



/**
 * Списък с листвани артикули за клиента/доставчика
 *
 * @category  bgerp
 * @package   crm
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class crm_ext_ProductListToContragents extends core_Manager
{
	
	
	/**
	 * Кой  може да изтрива?
	 */
	public $canDelete = 'ceo, crm';
	
	
	/**
	 * Кой  може да добавя?
	 */
	public $canAdd = 'ceo, crm';
	
	
	/**
	 * Кой  може да листва?
	 */
	public $canList = 'debug';
	
	
	/**
	 * Кой  може да редактира?
	 */
	public $canEdit = 'ceo, crm';
	
	
	/**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId=Артикул,packagingId=Опаковка,reff=Техен код,modified=Модифициране';
			

    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Modified, crm_Wrapper, plg_RowTools2, plg_SaveAndNew, plg_Search';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'productId,packagingId,reff';
    
    
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
	 * Описание на модела (таблицата)
	 */
	function description()
	{
		$this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'caption=Собственик->Клас,input=hidden,silent');
		$this->FLD('contragentId', 'int', 'caption=Собственик->Id,input=hidden,silent');
		$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,notNull,mandatory', 'tdClass=productCell leftCol wrap,silent,removeAndRefreshForm=packagingId,caption=Артикул');
    	$this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка', 'smartCenter,tdClass=small-field nowrap,silent,caption=Опаковка,input=hidden,mandatory');
    	$this->FLD('reff', 'varchar(32)', 'caption=Техен код');
	
    	$this->setDbUnique('contragentClassId,contragentId,productId,packagingId');
    	$this->setDbUnique('reff');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна
	 */
	protected static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		$rec = $form->rec;
		$mvc->currentTab = ($rec->contragentClassId == crm_Companies::getClassId()) ? 'Фирми' : 'Лица';
		
		if(empty($rec->id)){
			$products = cat_Products::getProducts($rec->contragentClassId, $rec->contragentId, NULL, 'canSell', NULL, NULL, TRUE);
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
			
			if(!$form->gotErrors()){
				if(!empty($rec->reff)){
					if($fRec = self::fetch(array("#contragentClassId = {$rec->contragentClassId} AND #contragentId = {$rec->contragentId} AND #productId != {$rec->productId} AND #reff = '[#1#]'", $rec->reff))){
						$pVerbal = cat_Products::getTitleById($fRec->productId, TRUE);
						$form->setError('reff', "|*<b>{$pVerbal}</b> |има същия код|*");
					}
				}
			}
		}
	}
	
	
	/**
	 * След подготовката на заглавието на формата
	 */
	protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
	{
		$rec = $data->form->rec;
		$data->form->title = core_Detail::getEditTitle($rec->contragentClassId, $rec->contragentId, $mvc->singleTitle, $rec->id, 'в');
	
		// Махане на бутона запис и нов, ако няма достатъчно записи
		if(count($data->form->productOptions) <= 1){
			$data->form->toolbar->removeBtn('saveAndNew');
		}
	}
	
	
	/**
	 * Подготовка на листваните артикули за един контрагент
	 * 
	 * @param stdClass $data
	 * @return void
	 */
	public function prepareProductList($data)
	{
		// Намират се ид-та на групите за клиенти и доставчици
		$clientGroupId = crm_Groups::getIdFromSysId('customers');
		$supplierGroupId = crm_Groups::getIdFromSysId('suppliers');
		
		$data->contragentClassId = $data->masterMvc->getClassId();
		$data->isClient = keylist::isIn($clientGroupId, $data->masterData->rec->groupList);
		$data->isSupplier = keylist::isIn($supplierGroupId, $data->masterData->rec->groupList);
		$Tab = core_Request::get('Tab', 'varchar');
		
		// Ако контагента не е доставчик или клиент и няма листвани артикули или не е отворен таба, не се подготвя нищо
		if(($data->isClient === FALSE && $data->isSupplier === FALSE && !self::fetch("#contragentClassId = {$data->contragentClassId} AND #contragentId = {$data->masterId}")) || $Tab !== 'CommerceDetails'){
			$data->render = FALSE;
			return;
		}
		
		// Подготовка на данните
		$this->prepareData($data);
		
		// Добавяне на бутони
		if($this->haveRightFor('add', (object)array('contragentClassId' => $data->contragentClassId, 'contragentId' => $data->masterId))){
			$data->addSellableUrl = array($this, 'add', 'contragentClassId' => $data->contragentClassId, 'contragentId' => $data->masterId, 'ret_url' => TRUE);
			$data->addImportUrl = array($this, 'import', 'contragentClassId' => $data->contragentClassId, 'contragentId' => $data->masterId, 'ret_url' => TRUE);
		}
	}
	
	
	/**
	 * Подготовка на формата
	 * 
	 * @param stdClass $data
	 * @return void
	 */
	private function prepareForm($data)
	{
		// Подготвяме формата за филтър по склад
        $form = cls::get('core_Form');
        $form->class = 'filterForm';
        
        $form->FLD("search", 'varchar', 'placeholder=Търсене,silent');
        $form->view = 'horizontal';
        $form->setAction(getCurrentUrl());
        $form->toolbar->addSbBtn('', 'default', 'id=filter', 'ef_icon=img/16/funnel.png');
        
        // Инпутване на формата
        $form->input();
        $data->form = $form;
	}
	
	
	/**
	 * Подготовка на данни
	 * 
	 * @param stdClass $data
	 * @return void
	 */
	private function prepareData(&$data)
	{
		$this->prepareListFields($data);
		$data->sellable = new stdClass();
		$data->sellable->recs = $data->sellable->rows = array();
		$data->sellable->listFields = $data->listFields;
		
		// Подготовка на форма за филтриране
		$this->prepareForm($data);
		
		// Намиране на всички листвани артикули за контрагента
		$sellableQuery = self::getQuery();
		$sellableQuery->where("#contragentClassId = {$data->contragentClassId} AND #contragentId = {$data->masterId}");
		$sellableQuery->orderBy('id', "ASC");
		
		// Ако има филтър по ключови думи, добавя се и той
		if(!empty($data->form->rec->search)){
			plg_Search::applySearch($data->form->rec->search, $sellableQuery);
		}
		
		// Подготовка на листваните артикули за продажба
		$data->sellable->recs = $sellableQuery->fetchAll();
		$data->sellable->pager = cls::get('core_Pager',  array('itemsPerPage' => $this->listItemsPerPage));
		$data->sellable->pager->itemsCount = count($data->sellable->recs);
		$data->sellable->pager->setPageVar('s');
		
		// За всеки запис, вербализира се, ако трябва да се показва
		foreach ($data->sellable->recs as $sId => $sRec){
			if(!$data->sellable->pager->isOnPage()) continue;
			$data->sellable->rows[$sId] = $this->recToVerbal($sRec);
		}
	}
	
	
	/**
	 * Рендиране на листваните артикули за продажба
	 * 
	 * @param core_ET $tpl
	 * @param stdClass $data
	 */
	private function renderSellableBlock(&$tpl, $data)
	{
		// Рендиране на таблицата с артикулите
		$table = cls::get('core_TableView', array('mvc' => $this));
		$this->invoke('BeforeRenderListTable', array($tpl, &$data->sellable));
		$tableTpl = $table->get($data->sellable->rows, $data->sellable->listFields);
		$tpl->replace($tableTpl, 'SELLABLE');
		
		// Редниране на бутона за добавяне
		if(isset($data->addSellableUrl)){
			$btn = ht::createBtn('Артикул', $data->addSellableUrl, NULL, NULL, 'ef_icon=img/16/shopping.png,title=Добавяне на нов артикул за листване в продажба');
			$tpl->append($btn, 'SELLABLE_BTN');
		}
		
		if(isset($data->addImportUrl)){
			$btn1 = ht::createBtn('Импорт', $data->addImportUrl, NULL, NULL, 'ef_icon=img/16/import.png,title=Импортиране на арткули');
			$tpl->append($btn1, 'SELLABLE_BTN');
		}
		
		// Рендиране на пейджъра
		if(isset($data->sellable->pager)){
			$tpl->append($data->sellable->pager->getHtml(), 'SELLABLE_PAGER');
		}
	}
	
	
	/**
	 * Рендиране на листваните артикули за клиента
	 * 
	 * @param stdClass $data
	 * @return core_ET $tpl
	 */
	public function renderProductList($data)
	{
		// Взимане на шаблона
		$tpl = getTplFromFile("crm/tpl/ProductListToContragents.shtml");
		
		// Ако няма да се рендира нищо, връща се празен шаблон
		if($data->render === FALSE) return $tpl;
		$tpl->replace(tr('Листвани артикули'), 'listTitle');
		
		// Ако има филтър форма, рендира се
		if(isset($data->form)){
			$tpl->append($data->form->renderHtml(), 'FILTER');
		}
		
		// Рендиране на двете таблици за листвани артикули
		self::renderSellableBlock($tpl, $data);
		
		// Връщане на шаблона
		return $tpl;
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec)){
			
			// Ако няма контрагент, записа не може да бъде променян
			if(empty($rec->contragentClassId) || empty($rec->contragentId)){
				$requiredRoles = 'no_one';
			} else {
				
				// Ако потребителя не може да редактира визитката, не може да променя листваните артикули
				if(!cls::get($rec->contragentClassId)->haveRightFor('edit', $rec->contragentId)){
					$requiredRoles = 'no_one';
				}
			}
		}
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид
	 */
	protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
		$row->modified = "<div class='nowrap'>" . $mvc->getFieldType('modifiedOn')->toVerbal($rec->modifiedOn);
		$row->modified .= " " . tr('от||by') . " " . crm_Profiles::createLink($rec->modifiedBy) . "</div>";
		$row->productId = cat_Products::getShortHyperlink($rec->productId);
	    $row->reff = "<b>{$row->reff}</b>";
	    
	    $policyInfo = cls::get('price_ListToCustomers')->getPriceInfo($rec->contragentClassId, $rec->contragentId, $rec->productId, $rec->packagingId, 1);
	    if(!isset($policyInfo->price)){
	    	$row->productId = ht::createHint($row->productId, 'Артикулът няма цена по ценовата политика на контрагента', 'warning', FALSE);
	    }
	}
	
	
	/**
	 * Кешира и връща всички листвани артикули за клиента
	 * 
	 * @param int $contragentClassId
	 * @param int $contragentId
	 */
	public static function getAll($contragentClassId, $contragentId)
	{
		$contragentClassId = cls::get($contragentClassId)->getClassId();
		
		// Ако няма наличен кеш за контрагента, извлича се наново
		if(!isset(self::$cache[$contragentClassId][$contragentId])){
			self::$cache[$contragentClassId][$contragentId] = array();
			
			// Кои са листваните артикули за контрагента
			$query = self::getQuery();
			$query->where("#contragentClassId = {$contragentClassId} AND #contragentId = {$contragentId}");
			$query->orderBy('id', 'DESC');
			
			// Добавя се всеки запис, групиран според типа
			while($rec = $query->fetch()){
				$obj = (object)array('productId' => $rec->productId, 'packagingId' => $rec->packagingId, 'reff' => $rec->reff);
				self::$cache[$contragentClassId][$contragentId][$rec->id] = $obj;
			}
		}
		
		// Връщане на кешираните данни
		return self::$cache[$contragentClassId][$contragentId];
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
		expect($cClass = Request::get('contragentClassId', 'int'));
		expect($contragentId = Request::get('contragentId', 'int'));
		expect(cls::get($cClass)->fetch($contragentId));
		$this->requireRightFor('add', (object)array('contragentClassId' => $cClass, 'contragentId' => $contragentId));
			
		// Подготовка на формата
		$form = cls::get('core_Form');
		$form->method = 'POST';
		$form->title = "Импортиране на артикули за листване в|*" . cls::get($cClass)->getHyperlink($contragentId, TRUE);
		$form->FLD('contragentClassId', 'int', "input=hidden,silent");
		$form->FLD('contragentId', 'int', "input=hidden,silent");
		
		$form->FLD('from', 'enum(,group=Група,sales=Предишни продажби)', "caption=Избор,removeAndRefreshForm=fromDate|toDate|selected,silent");
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
					$products = $this->getFromGroup($rec->group, $cClass, $contragentId);
				
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
					$newRec->contragentClassId = $rec->contragentClassId;
					$newRec->contragentId = $rec->contragentId;
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
	 * @param mixed $cClass
	 * @param int $contragentId
	 * @return array $products
	 */
	private function getFromGroup($group, $cClass, $contragentId)
	{
		$products = array();
		$folderId = cls::get($cClass)->forceCoverAndFolder($contragentId);
		$cDescendants = cat_Groups::getDescendantArray($group);
		$alreadyIn = arr::extractValuesFromArray(self::getAll($cClass, $contragentId), 'productId');
		
		// Извличане на всички активни, продаваеми артикули от дадената група и нейните подгрупи
		$query = cat_Products::getQuery();
		$query->likeKeylist('groups', $cDescendants);
		$query->notIn('id', $alreadyIn);
		$query->where("#state = 'active'");
		$query->where("#isPublic = 'yes' OR #folderId = '{$folderId}'");
		$query->where("#canSell = 'yes'");
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