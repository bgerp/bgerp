<?php


/**
 * Драйвер за документи готови за експедиция
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Готовност за експедиция
 */
class sales_reports_ShipmentReadiness extends frame2_driver_Proto
{
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectDriver = 'ceo, store, sales, admin';
	
	
	/**
	 * Нормализираните имена на папките
	 *
	 * @var array
	 */
	private static $folderNames = array();
	
	
	/**
	 * Имената на контрагентите
	 *
	 * @var array
	 */
	private static $contragentNames = array();
	
	
	/**
	 * Дилърите
	 *
	 * @var array
	 */
	private static $dealers = array();
	
	
	/**
	 * Брой записи на страница
	 * 
	 * @var инт
	 */
	private $listItemsPerPage = 50;
	
	
	/**
	 * Връща заглавието на отчета
	 *
	 * @param stdClass $rec - запис
	 * @return string|NULL  - заглавието или NULL, ако няма
	 */
	public function getTitle($rec)
	{
		return 'Готовност за експедиция';
	}
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('dealers', 'keylist(mvc=core_Users,select=nick)', 'caption=Търговци,after=title,mandatory,single=none');
		$fieldset->FLD('precision', 'percent(min=0,max=1)', 'caption=Готовност,unit=и нагоре,after=dealers');
		$fieldset->FLD('orderBy', 'enum(readiness=По готовност,contragents=По контрагенти)', 'caption=Подредба,after=precision');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param frame2_driver_Proto $Driver $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
	{
		$form = &$data->form;
		
		if(haveRole('sales')){
			$form->setDefault('dealers', keylist::addKey('', core_Users::getCurrent()));
		}
	}
	
	
	/**
	 * Рендиране на данните на справката
	 *
	 * @param stdClass $rec - запис на справката
	 * @return core_ET      - рендирания шаблон
	 */
	public function renderData($rec)
	{
		$tpl = new core_ET("[#PAGER_TOP#][#TABLE#][#PAGER_BOTTOM#]");
		
		$data = $rec->data;
		$data->listFields = $this->getListFields($rec);
		$data->rows = array();
		
		// Подготовка на пейджъра
		if(!Mode::isReadOnly()){
			$data->Pager = cls::get('core_Pager',  array('itemsPerPage' => $this->listItemsPerPage));
			$data->Pager->setPageVar('frame2_Reports', $rec->id);
			$data->Pager->itemsCount = count($data->recs);
		}
		
		// Вербализиране само на нужните записи
		if(is_array($data->recs)){
			foreach ($data->recs as $index => $dRec){
				if(isset($data->Pager) && !$data->Pager->isOnPage()) continue;
				$data->rows[$index] = $this->detailRecToVerbal($dRec);
			}
		}
		
		// Рендиране на пейджъра
		if(isset($data->Pager)){
			$tpl->append($data->Pager->getHtml(), 'PAGER_TOP');
			$tpl->append($data->Pager->getHtml(), 'PAGER_BOTTOM');
		}
		
		// Рендиране на лист таблицата
		$fld = cls::get('core_FieldSet');
		$fld->FLD('dealerId', 'varchar', 'smartCenter');
		$fld->FLD('readiness', 'double');
		$fld->FLD('document', 'varchar', 'smartCenter');
		
		$table = cls::get('core_TableView', array('mvc' => $fld));
		$tpl->append($table->get($data->rows, $data->listFields), 'TABLE');
		$tpl->removeBlocks();
		$tpl->removePlaces();
		
		// Връщане на шаблона
		return $tpl;
	}
	
	
	/**
	 * Връща списъчните полета
	 * 
	 * @param stdClass $rec  - запис
	 * @return array $fields - полета
	 */
	private function getListFields($rec)
	{
		$fields = array('dealerId'     => 'Търговец', 
				        'contragent'   => 'Контрагент',
						'deliveryTime' => 'Доставка',
				        'document'     => 'Документ', 
				        'readiness'    => 'Готовност');
		
		return $fields;
	}
	
	
	/**
	 * Вербализиране на данните
	 * 
	 * @param stdClass $dRec - запис от детайла
	 * @return stdClass $row - вербалния запис
	 */
	private function detailRecToVerbal(&$dRec)
	{
		$isPlain = Mode::is('text', 'plain');
		$row = new stdClass();
		$Document = doc_Containers::getDocument($dRec->containerId);
		
		// Линк към дилъра
		if(!array_key_exists($dRec->dealerId, self::$dealers)){
			self::$dealers[$dRec->dealerId] = crm_Profiles::createLink($dRec->dealerId);
		}
		
		$row->dealerId = self::$dealers[$dRec->dealerId];
		if($isPlain){
			$row->dealerId = strip_tags($row->dealerId->getContent());
		}
		
		// Линк към контрагента
		$key = "{$dRec->contragentClassId}|{$dRec->contragentId}";
		if(!array_key_exists($key, self::$contragentNames)){
			self::$contragentNames[$key] = cls::get($dRec->contragentClassId)->getShortHyperlink($dRec->contragentId);
		}
		$row->contragent = self::$contragentNames[$key];
		if($isPlain){
			$row->contragent = strip_tags($row->contragent);
			$row->contragent = rtrim($row->contragent, "&nbsp;");
		}
		
		// Линк към документа
		$singleUrl = $Document->getSingleUrlArray();
		$handle = $Document->getHandle();
		
		$row->document = "#{$handle}";
		if(!Mode::isReadOnly() && !$isPlain){
			$row->document = ht::createLink("#{$handle}", $singleUrl, FALSE, "ef_icon={$Document->singleIcon}");
		}
		
		$row->readiness = ($isPlain) ?  frame_CsvLib::toCsvFormatDouble($dRec->readiness * 100) : cls::get('type_Percent')->toVerbal($dRec->readiness);
		
		if(!Mode::isReadOnly() && !$isPlain){
			$row->ROW_ATTR['class'] = "state-{$Document->fetchField('state')}";
			
			if($dRec->readiness == 0){
				$row->readiness = "<span class='quiet'>{$row->readiness}<span>";
			} elseif($dRec->readiness >= 0.8) {
				$row->readiness = "<span style='color:blue'>{$row->readiness}<span>";
			} else {
				$row->readiness = "<span style='color:green'>{$row->readiness}<span>";
			}
		}
		
		$row->deliveryTime = ($isPlain) ? frame_CsvLib::toCsvFormatData($dRec->deliveryTime) : cls::get('type_Datetime')->toVerbal($dRec->deliveryTime);
		
		return $row;
	}
	
	
	/**
	 * След рендиране на единичния изглед
	 *
	 * @param frame2_driver_Proto $Driver
	 * @param embed_Manager $Embedder
	 * @param core_ET $tpl
	 * @param stdClass $data
	 */
	public static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
	{
		if(isset($rec->precision)){
			$row->precision .= " " . tr('и нагоре');
		}
		
		$dealers = keylist::toArray($rec->dealers);
		foreach ($dealers as $userId => &$nick) {
			$nick = crm_Profiles::createLink($userId)->getContent();
		}
		
		$row->dealers = implode(', ', $dealers);
	}
	
	
	/**
	 * След рендиране на единичния изглед
	 *
	 * @param cat_ProductDriver $Driver
	 * @param embed_Manager $Embedder
	 * @param core_ET $tpl
	 * @param stdClass $data
	 */
	public static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
	{
		$tpl->append(tr("|*<fieldset><legend class='groupTitle'><small><b>|Търговци|*</b></small></legend><small>{$data->row->dealers}</small></fieldset>"), 'DRIVER_FIELDS');
	}
	
	
	/**
	 * Връща нормализирано име на корицата, за по-лесно сортиране
	 * 
	 * @param int $folderId
	 * @return string
	 */
	private static function normalizeFolderName($folderId)
	{
		if(!array_key_exists($folderId, self::$folderNames)){
			self::$folderNames[$folderId] = strtolower(str::utf2ascii(doc_Folders::fetchField($folderId, 'title')));
		}
		
		return self::$folderNames[$folderId];
	}
	
	
	/**
	 * Подготвя данните на справката от нулата, които се записват в модела
	 *
	 * @param stdClass $rec        - запис на справката
	 * @return stdClass|NULL $data - подготвените данни
	 */
	public function prepareData($rec)
	{
		core_App::setTimeLimit(150);
		$Sales = sales_Sales::getSingleton();
		
		$data = new stdClass();
		$data->recs = array();
		
		$dealers = keylist::toArray($rec->dealers);
		
		// Всички чакащи и активни продажби на избраните дилъри
		$sQuery = sales_Sales::getQuery();
		$sQuery->where("#state = 'pending' || #state = 'active'");
		if(count($dealers)){
			$sQuery->in('dealerId', $dealers);
		}
		
		// За всяка
		while($sRec = $sQuery->fetch()){
			
			// Изчислява се готовноста
			$readiness = core_Cache::get('sales_reports_ShipmentReadiness', "c{$sRec->containerId}");
			if($readiness === FALSE) {
				$readiness = self::calcSaleReadiness($sRec);
				core_Cache::set('sales_reports_ShipmentReadiness', "c{$sRec->containerId}", $readiness, 58);
			}
			
			// Ако има някаква изчислена готовност
			if($readiness !== FALSE && $readiness !== NULL){
				
				// И тя е в посочения диапазон
				if(!isset($rec->precision) || (isset($rec->precision) && $readiness >= $rec->precision)){
					
					$delTime = (!empty($sRec->deliveryTime)) ? $sRec->deliveryTime : (!empty($sRec->deliveryTermTime) ?  dt::addSecs($sRec->deliveryTermTime, $sRec->valior) : NULL);
					if(empty($delTime)){
						$delTime = $Sales->getMaxDeliveryTime($sRec->id);
						$delTime = ($delTime) ? $delTime : $sRec->valior;
					}
					
					// Добавя се
					$dRec = (object)array('containerId'       => $sRec->containerId,
							              'contragentName'    => self::normalizeFolderName($sRec->folderId),
										  'contragentClassId' => $sRec->contragentClassId,
										  'contragentId'      => $sRec->contragentId,
										  'deliveryTime'      => $delTime,
							              'folderId'          => $sRec->folderId,
							              'dealerId'          => $sRec->dealerId,
							              'readiness'         => $readiness);
					
					$data->recs[$sRec->containerId] = $dRec;
				}
			}
			
			// Всички чакащи ЕН-та от треда на продажбата
			$shipQuery = store_ShipmentOrders::getQuery();
			$shipQuery->where("#state = 'pending'");
			$shipQuery->where("#threadId = {$sRec->threadId}");
			
			while($soRec = $shipQuery->fetch()){
				
				// Изчислява им се готовноста
				$readiness1 = core_Cache::get('sales_reports_ShipmentReadiness', "c{$soRec->containerId}");
				if($readiness1 === FALSE) {
					$readiness1 = self::calcSoReadiness($soRec);
					core_Cache::set('sales_reports_ShipmentReadiness', "c{$soRec->containerId}", $readiness1, 58);
				}
				
				// Ако има изчислена готовност
				if($readiness1 !== FALSE && $readiness1 !== NULL){
					
					// И тя е в посочения диапазон
					if(isset($rec->precision) && $readiness1 < $rec->precision) continue;
					
					$deliveryTime = !empty($soRec->deliveryTime) ? $soRec->deliveryTime : $soRec->valior;
					
					// Добавя се
					$r = (object)array('containerId'       => $soRec->containerId,
							           'contragentName'    => self::normalizeFolderName($soRec->folderId),
							           'contragentClassId' => $sRec->contragentClassId,
							           'contragentId'      => $sRec->contragentId,
									   'deliveryTime'      => $deliveryTime,
							           'folderId'          => $soRec->folderId, 
							           'dealerId'          => $sRec->dealerId, 
							           'readiness'         => $readiness1);
					
					$data->recs[$soRec->containerId] = $r;
				}
			}
		}
		
		// Ако е избрано филтриране по контрагенти
		if($rec->orderBy == 'contragents'){
			
			// Първо се сортират по нормализираните имена на контрагентите, след това по готовноста
			usort($data->recs, function($a, $b) {
				if($a->contragentName == $b->contragentName){
					return ($a->readiness < $b->readiness) ? 1 : -1;
				}
				
				return (strnatcasecmp($a->contragentName, $b->contragentName) < 0) ? -1 : 1;
				
			});
		} else {
			
			// По дефолт се сортират по готовност във низходящ ред, при равенство по нормализираното име на контрагента
			usort($data->recs, function($a, $b) {
				if($a->readiness === $b->readiness){
					return (strnatcasecmp($a->contragentName, $b->contragentName) < 0) ? -1 : 1;
				}
			
				return ($a->readiness < $b->readiness) ? 1 : -1;
			
			});
		}
		
		// Връщане на датата
		return $data;
	}
	
	
	/**
	 * Изчислява готовноста на продажбата
	 * 
	 * @param stdClass $saleRec - запис на продажба
	 * @return double|NULL      - готовност между 0 и 1, или NULL ако няма готовност
	 */
	public static function calcSaleReadiness($saleRec)
	{
		// На не чакащите и не активни не се изчислява готовноста
		if($saleRec->state != 'pending' && $saleRec->state != 'active') return NULL;
		
		// На бързите продажби също не се изчислява
		if(strpos($saleRec->contoActions, "ship") !== FALSE) return NULL;
		
		// Взимане на договорените и експедираните артикули по продажбата (събрани по артикул)
		$Sales = sales_Sales::getSingleton();
		$dealInfo = $Sales->getAggregateDealInfo($saleRec);
		$agreedProducts = $dealInfo->get('products');
		$shippedProducts = $dealInfo->get('shippedProducts');
			
		$totalAmount = 0;
		$readyAmount = NULL;
			
		// За всеки договорен артикул
		foreach ($agreedProducts as $pId => $pRec){
			$productRec = cat_Products::fetch($pId, 'canStore,isPublic');
			if($productRec->canStore != 'yes') continue;
					
			// Количеството е неекспедираното
			$quantity = (isset($shippedProducts[$pId])) ? ($pRec->quantity - $shippedProducts[$pId]->quantity) : $pRec->quantity;
			
			// Ако всичко е експедирано се пропуска реда
			if($quantity <= 0) continue;
					
			$amount = NULL;
			$totalAmount += $quantity * $pRec->price;
					
			// Ако артикула е нестандартен и има приключено задание по продажбата и няма друго активно по нея
			if($productRec->isPublic == 'no'){
				$closedJobId = planning_Jobs::fetchField("#productId = {$pId} AND #state = 'closed' AND #saleId = {$saleRec->id}");
				$activeJobId = planning_Jobs::fetchField("#productId = {$pId} AND (#state = 'active' || #state = 'stopped' || #state = 'wakeup') AND #saleId = {$saleRec->id}");
						
				// Се приема че е готово
				if($closedJobId && !$activeJobId){
					$amount = $quantity * $pRec->price;
				}
			}
					
			if(is_null($amount)){
						
				// Изчислява се колко от сумата на артикула може да се изпълни
				$quantityInStock = store_Products::getQuantity($pId, $saleRec->shipmentStoreId);
				$quantityInStock = ($quantityInStock > $quantity) ? $quantity : (($quantityInStock < 0) ? 0 : $quantityInStock);
						
				$amount = $quantityInStock * $pRec->price;
			}
					
			// Събиране на изпълнената сума за всеки ред
			if(isset($amount)){
				$readyAmount += $amount;
			}
		}
			
		// Готовноста е процента на изпълнената сума от общата
		$readiness = (isset($readyAmount)) ? @round($readyAmount / $totalAmount, 2) : NULL;
		
		// Връщане на изчислената готовност или NULL ако не може да се изчисли
		return $readiness;
	}
	
	
	/**
	 * Изчислява готовноста на експедиционното нареждане
	 *
	 * @param stdClass $soRec - запис на ЕН
	 * @return double|NULL    - готовност между 0 и 1, или NULL ако няма готовност
	 */
	public static function calcSoReadiness($soRec)
	{
		// На не чакащите не се изчислява готовност
		if($soRec->state != 'pending') return NULL;
		
		// Намират се детайлите на ЕН-то
		$dQuery = store_ShipmentOrderDetails::getQuery();
		$dQuery->where("#shipmentId = {$soRec->id}");
		
		// Детайлите се сумират по артикул
		$all = deals_Helper::normalizeProducts(array($dQuery->fetchAll()));
		
		$totalAmount = 0;
		$readyAmount = NULL;
		
		// За всеки се определя колко % може да се изпълни
		foreach ($all as $pId => $pRec){
			$totalAmount += $pRec->quantity * $pRec->price;
				
			// Определя се каква сума може да се изпълни
			$quantityInStock = store_Products::getQuantity($pId, $soRec->storeId);
			$quantityInStock = ($quantityInStock > $pRec->quantity) ? $pRec->quantity : (($quantityInStock < 0) ? 0 : $quantityInStock);
			
			$amount = $quantityInStock * $pRec->price;
				
			if(isset($amount)){
				$readyAmount += $amount;
			}
		}
		
		// Готовноста е процента на изпълнената сума от общата
		$readiness = (isset($readyAmount)) ? @round($readyAmount / $totalAmount, 2) : NULL;
		
		// Връщане на изчислената готовност или NULL ако не може да се изчисли
		return $readiness;
	}
	
	
	/**
	 * Връща редовете на CSV файл-а
	 *
	 * @param stdClass $rec
	 * @return array
	 */
	public function getCsvExportRows($rec)
	{
		$dRecs = $rec->data->recs;
		$exportRows = array();
		
		Mode::push('text', 'plain');
		if(is_array($dRecs)){
			foreach ($dRecs as $key => $dRec){
				$exportRows[$key] = $this->detailRecToVerbal($dRec);
			}
		}
		Mode::pop('text');
		
		return $exportRows;
	}
	
	
	/**
	 * Връща полетата за експортиране във csv
	 *
	 * @param stdClass $rec
	 * @return array
	 */
	public function getCsvExportFieldset($rec)
	{
		$fieldset = new core_FieldSet();
		$fieldset->FLD('dealerId', 'varchar','caption=Търговец');
		$fieldset->FLD('contragent', 'varchar','caption=Контрагент');
		$fieldset->FLD('deliveryTime', 'varchar','caption=Доставка');
		$fieldset->FLD('document', 'varchar','caption=Документ');
		$fieldset->FLD('readiness', 'varchar','caption=Готовност %');
		
		return $fieldset;
	}
}