<?php


/**
 * Драйвер за готовност за експедиция на документи
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Логистика » Готовност за експедиция
 */
class sales_reports_ShipmentReadiness extends frame2_driver_TableData
{
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectDriver = 'ceo, store, sales, admin, purchase';
	
	
	/**
	 * Кое поле от $data->recs да се следи, ако има нов във новата версия
	 *
	 * @var varchar
	 */
	protected $newFieldToCheck = 'containerId';
	
	
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
	 * Полета от таблицата за скриване, ако са празни
	 *
	 * @var int
	 */
	protected $filterEmptyListFields = 'dueDates';
	
	
	/**
	 * Полета за хеширане на таговете
	 * 
	 * @see uiext_Labels
	 * @var varchar
	 */
	protected $hashField = 'containerId';
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('dealers', 'keylist(mvc=core_Users,select=nick)', 'caption=Търговци,after=title,single=none');
		$fieldset->FLD('countries', 'keylist(mvc=drdata_Countries,select=commonNameBg,allowEmpty)', 'caption=Държави,after=dealers,single=none');
		$fieldset->FLD('precision', 'percent(min=0,max=1)', 'caption=Готовност,unit=и нагоре,after=countries');
		$fieldset->FLD('horizon', 'time(uom=days,Min=0)', 'caption=Падиращи до,after=precision');
		$fieldset->FLD('orderBy', 'enum(readiness=Готовност,contragents=Клиенти,execDate=Срок за изпълнение,dueDate=Дата на падеж)', 'caption=Подредба,after=precision');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param frame2_driver_Proto $Driver $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $data
	 */
	protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
	{
		$form = &$data->form;
		
		// Всички активни потебители
		$uQuery = core_Users::getQuery();
		$uQuery->where("#state = 'active'");
		$uQuery->orderBy("#names", 'ASC');
		$uQuery->show('id');
		
		// Които са търговци
		$roles = core_Roles::getRolesAsKeylist('ceo,sales');
		$uQuery->likeKeylist('roles', $roles);
		$allDealers = arr::extractValuesFromArray($uQuery->fetchAll(), 'id');
		
		// Към тях се добавят и вече избраните търговци
		if(isset($form->rec->dealers)){
			$dealers = keylist::toArray($form->rec->dealers);
			$allDealers = array_merge($allDealers, $dealers);
		}
		
		// Вербализират се
		$suggestions = array();
		foreach ($allDealers as $dealerId){
			$suggestions[$dealerId] = core_Users::fetchField($dealerId, 'nick');
		}
		
		// Задават се като предложение
		$form->setSuggestions('dealers', $suggestions);
		$form->setSuggestions('horizon', explode("|", "1 ден|2 дни|5 дни|7 дни|10 дни|15 дни|20 дни|30 дни|60 дни|90 дни|120 дни"));
		
		// Ако текущия потребител е търговец добавя се като избран по дефолт
		if(haveRole('sales') && empty($form->rec->id)){
			$form->setDefault('dealers', keylist::addKey('', core_Users::getCurrent()));
		}
	}
	
	
	/**
	 * Вербализиране на редовете, които ще се показват на текущата страница в отчета
	 *
	 * @param stdClass $rec  - записа
	 * @param stdClass $dRec - чистия запис
	 * @return stdClass $row - вербалния запис
	 */
	protected function detailRecToVerbal($rec, &$dRec)
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
			$row->dealerId = strip_tags(($row->dealerId instanceof core_ET) ? $row->dealerId->getContent() : $row->dealerId);
		}
		
		// Линк към контрагента
		$key = "{$dRec->contragentClassId}|{$dRec->contragentId}";
		if(!array_key_exists($key, self::$contragentNames)){
			self::$contragentNames[$key] = cls::get($dRec->contragentClassId)->getShortHyperlink($dRec->contragentId);
		}
		$row->contragentName = self::$contragentNames[$key];
		if($isPlain){
			$row->contragentName = strip_tags($row->contragentName);
			$row->contragentName = str_replace('&nbsp;', ' ', $row->contragentName);
			$row->contragentName = str_replace(';', '', $row->contragentName);
		}
		
		// Линк към документа
		$singleUrl = $Document->getSingleUrlArray();
		$handle = $Document->getHandle();
		
		$row->document = "#{$handle}";
		if(!Mode::isReadOnly() && !$isPlain){
			$row->document = ht::createLink("#{$handle}", $singleUrl, FALSE, "ef_icon={$Document->singleIcon}");
			$dTable = $this->getSaleDetailTable($Document->that);
			if(!empty($dTable)){
				$row->document .= $dTable;
			}
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
			
			if($Document->isInstanceOf('sales_Sales')){
				$sRec = $Document->fetchField('amountPaid,paymentState');
				if($sRec->paymentState == 'paid' && !empty($sRec->amountPaid)){
					$row->readiness = ht::createHint($row->readiness, 'Сделката е платена', 'notice', FALSE);
				}
			}
		}
		
		foreach (array('deliveryTime', 'dueDateMin', 'dueDateMax', 'execDate') as $dateFld){
			if(isset($dRec->{$dateFld})){
				if($isPlain){
					$row->{$dateFld} = frame_CsvLib::toCsvFormatData($dRec->{$dateFld});
				} else {
					$DeliveryDate = new DateTime($dRec->{$dateFld});
					$delYear = $DeliveryDate->format('Y');
					$curYear = date('Y');
					$mask = ($delYear == $curYear) ? 'd.M' : 'd.M.y';
					$row->{$dateFld} = dt::mysql2verbal($dRec->{$dateFld}, $mask);
				}
			}
		}
		
		if(!$isPlain){
			if(isset($row->dueDateMin) && isset($row->dueDateMax)){
				if($row->dueDateMin == $row->dueDateMax){
					$row->dueDates = $row->dueDateMin;
				} else {
					$row->dueDates = "{$row->dueDateMin}-{$row->dueDateMax}";
				}
			}
		}
		
		return $row;
	}
	
	
	/**
	 * Подготвя допълнителна информация за продажбата
	 * 
	 * @param int $saleId
	 * @return string|NULL
	 */
	private function getSaleDetailTable($saleId)
	{
		$arr = array();
			
		// Под документа се показват и артикулите, които имат задания към него
		$jQuery = planning_Jobs::getQuery();
		$jQuery->where("#saleId = {$saleId} AND (#state = 'active' || #state = 'stopped' || #state = 'wakeup' || #state = 'closed')");
		$jQuery->show('productId');
		while($jRec = $jQuery->fetch()){
			$pRec = cat_products::fetch($jRec->productId, 'name,code,isPublic,measureId,canStore');
			$inStock = ($pRec->canStore == 'yes') ? store_Products::getQuantity($jRec->productId, NULL, TRUE) . " " . cat_UoM::getShortName($pRec->measureId) : NULL;
			
			$arr[] = array('job' => planning_Jobs::getLink($jRec->id), 'inStock' => $inStock);
		}
			
		if(count($arr)){
			$tableHtml = "<table class='small'>";
			foreach ($arr as $ar){
				$tableHtml .= "<tr><td style='border:0px'>{$ar['job']}</td><td style='border:0px'> / {$ar['inStock']}</td></tr>";
			}
			$tableHtml .= "</table>";
			
			return $tableHtml;
		}
		
		return NULL;
	}
	
	
	/**
	 * След рендиране на единичния изглед
	 *
	 * @param frame2_driver_Proto $Driver
	 * @param embed_Manager $Embedder
	 * @param core_ET $tpl
	 * @param stdClass $data
	 */
	protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
	{
		if(isset($rec->precision) && $rec->precision != 1){
			$row->precision .= " +";
		}
		
		$dealers = keylist::toArray($rec->dealers);
		foreach ($dealers as $userId => &$nick) {
			$nick = crm_Profiles::createLink($userId)->getContent();
		}
		
		$row->dealers = implode(', ', $dealers);
		if(isset($rec->countries)){
			$row->countries = core_Type::getByName('keylist(mvc=drdata_Countries,select=commonNameBg)')->toVerbal($rec->countries);
		}
	}
	
	
	/**
	 * След рендиране на единичния изглед
	 *
	 * @param cat_ProductDriver $Driver
	 * @param embed_Manager $Embedder
	 * @param core_ET $tpl
	 * @param stdClass $data
	 */
	protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
	{
		$fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
							    <!--ET_BEGIN place--><small><div><!--ET_BEGIN dealers-->|Търговци|*: [#dealers#]<!--ET_END dealers--></div><!--ET_BEGIN countries--><div>|Държави|*: [#countries#]</div><!--ET_END countries--><!--ET_BEGIN horizon-->|Падиращи до|* [#horizon#]<!--ET_END horizon--></small></fieldset><!--ET_END BLOCK-->"));
		
		foreach (array('dealers', 'countries', 'horizon') as $fld){
			if(isset($data->rec->{$fld})){
				$fieldTpl->append($data->row->{$fld}, $fld);
			}
		}
		
		$tpl->append($fieldTpl, 'DRIVER_FIELDS');
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
	 * Кои записи ще се показват в таблицата
	 * 
	 * @param stdClass $rec
	 * @param stdClass $data
	 * @return array
	 */
	protected function prepareRecs($rec, &$data = NULL)
	{
		$recs = array();
		
		core_App::setTimeLimit(200);
		$Sales = cls::get('sales_Sales');
		
		$dealers = keylist::toArray($rec->dealers);
		$countries = keylist::toArray($rec->countries);
		$cCount = count($countries);
		
		// Всички чакащи и активни продажби на избраните дилъри
		$sQuery = sales_Sales::getQuery();
		$sQuery->where("#state = 'pending' || #state = 'active'");
		$sQuery->EXT('inCharge', 'doc_Folders', 'externalName=inCharge,externalKey=folderId');
		if(count($dealers)){
			$dealers = implode(',', $dealers);
			$sQuery->where("#inCharge IN ({$dealers}) OR #dealerId IN ({$dealers})");
		}
		
		// За всяка
		while($sRec = $sQuery->fetch()){
				
			// Ако има филтър по държава
			if($cCount){
				$contragentCountryId = cls::get($sRec->contragentClassId)->fetchField($sRec->contragentId, 'country');
				if(!array_key_exists($contragentCountryId, $countries)) continue;
			}
				
			// Изчислява се готовността
			$readiness = core_Cache::get('sales_reports_ShipmentReadiness', "c{$sRec->containerId}");
			if($readiness === FALSE) {
				$readiness = self::calcSaleReadiness($sRec);
				core_Cache::set('sales_reports_ShipmentReadiness', "c{$sRec->containerId}", $readiness, 58);
			}
			
			$delTime = (!empty($sRec->deliveryTime)) ? $sRec->deliveryTime : (!empty($sRec->deliveryTermTime) ?  dt::addSecs($sRec->deliveryTermTime, $sRec->valior) : NULL);
			if(empty($delTime)){
				$delTime = $Sales->getMaxDeliveryTime($sRec->id);
				$delTime = ($delTime) ? dt::addSecs($delTime, $sRec->valior) : $sRec->valior;
			}
			
			$max = $readiness;
			$minDel = $delTime;
			
			$shipQuery = store_ShipmentOrders::getQuery();
			$shipQuery->where("#state = 'pending'");
			$shipQuery->where("#threadId = {$sRec->threadId}");
			while($soRec = $shipQuery->fetch()){
				$deliveryTime = !empty($soRec->deliveryTime) ? $soRec->deliveryTime : $soRec->valior;
				
				// Изчислява им се готовността
				$readiness1 = core_Cache::get('sales_reports_ShipmentReadiness', "c{$soRec->containerId}");
				if($readiness1 === FALSE) {
					$readiness1 = self::calcSoReadiness($soRec);
					core_Cache::set('sales_reports_ShipmentReadiness', "c{$soRec->containerId}", $readiness1, 58);
				}
				
				$max = max($max, $readiness1);
				
				if(isset($deliveryTime)){
					if(empty($minDel) || $deliveryTime < $minDel){
						$minDel = $deliveryTime;
					}
				}
			}
			
			if($max === FALSE || is_null($max)) continue;
				
			if(!isset($rec->precision) || (isset($rec->precision) && $max >= $rec->precision)){
				$dealerId = ($sRec->dealerId) ? $sRec->dealerId : (($sRec->activatedBy) ? $sRec->activatedBy : $sRec->createdBy);
						
				$dueDates = $this->getSaleDueDates($sRec);
						
				$add = TRUE;
				if(isset($rec->horizon)){
					$horizon = dt::addSecs($rec->horizon);
					$compareDate = isset($dueDates['min']) ? $dueDates['min'] : (isset($dueDates['max']) ? $dueDates['max'] : (isset($delTime) ? $delTime : NULL));
					if(!empty($compareDate) && $compareDate > $horizon){
						$add = FALSE;
					}
				}
						
				if($add === TRUE){
					$dRec = (object)array('containerId'       => $sRec->containerId,
										  'contragentName'    => self::normalizeFolderName($sRec->folderId),
										  'contragentClassId' => $sRec->contragentClassId,
										  'contragentId'      => $sRec->contragentId,
										  'deliveryTime'      => $delTime,
										  'execDate'          => $minDel,
										  'dueDateMin'        => $dueDates['min'],
										  'dueDateMax'        => $dueDates['max'],
										  'folderId'          => $sRec->folderId,
										  'dealerId'          => $dealerId,
										  'readiness'         => $max);
			
							$recs[$sRec->containerId] = $dRec;
				}
			}
		}
		
		// Ако е избрано филтриране по контрагенти
		if($rec->orderBy == 'contragents'){
			$data->groupByField = 'contragentName';
			
			// Първо се сортират по нормализираните имена на контрагентите, след това по готовността
			usort($recs, function($a, $b) {
				if($a->contragentName == $b->contragentName){
					if($a->readiness == $b->readiness){
						return ($a->deliveryTime < $b->deliveryTime) ? -1 : 1;
					}
						
					return ($a->readiness < $b->readiness) ? 1 : -1;
				}
		
				return (strnatcasecmp($a->contragentName, $b->contragentName) < 0) ? -1 : 1;
		
			});
		} elseif($rec->orderBy == 'execDate'){
			arr::order($recs, 'execDate', 'ASC');
			$data->groupByField = 'contragentName';
		} elseif($rec->orderBy == 'dueDate'){
			arr::order($recs, 'dueDateMin', 'ASC');
			$data->groupByField = 'contragentName';
		} else {
				
			// По дефолт се сортират по готовност във низходящ ред, при равенство по нормализираното име на контрагента
			usort($recs, function($a, $b) {
				if($a->readiness === $b->readiness){
					if($a->contragentName == $b->contragentName){
						return ($a->deliveryTime < $b->deliveryTime) ? -1 : 1;
					}
					return (strnatcasecmp($a->contragentName, $b->contragentName) < 0) ? -1 : 1;
				}
					
				return ($a->readiness < $b->readiness) ? 1 : -1;
					
			});
		}
		
		return $recs;
	}
	
	
	/**
	 * Връща фийлдсета на таблицата, която ще се рендира
	 *
	 * @param stdClass $rec      - записа
	 * @param boolean $export    - таблицата за експорт ли е
	 * @return core_FieldSet     - полетата
	 */
	protected function getTableFieldSet($rec, $export = FALSE)
	{
		$fld = cls::get('core_FieldSet');
		
		if($export === FALSE){
			$fld->FLD('dealerId', 'varchar', 'smartCenter,caption=Търговец');
			$fld->FLD('contragentName', 'varchar', 'caption=Клиент');
			
			if($rec->orderBy != 'execDate'){
				$fld->FLD('dueDates', 'varchar', 'tdClass=small centered,caption=Падеж');
			}
			
			if($rec->orderBy != 'dueDate'){
				$fld->FLD('execDate', 'varchar', 'tdClass=small centered,caption=Изпълнение');
			}
			
			$fld->FLD('document', 'varchar', 'caption=Документ');
			$fld->FLD('readiness', 'double', 'caption=Готовност');
		} else {
			$fld->FLD('dealerId', 'varchar','caption=Търговец');
			$fld->FLD('contragentName', 'varchar','caption=Клиент');
			
			if($rec->orderBy != 'execDate'){
				$fld->FLD('dueDateMin', 'varchar','caption=Падеж мин');
				$fld->FLD('dueDateMax', 'varchar','caption=Падеж макс');
			}
				
			if($rec->orderBy != 'dueDate'){
				$fld->FLD('execDate', 'varchar','caption=Изпълнение');
			}
			
			$fld->FLD('document', 'varchar','caption=Документ');
			$fld->FLD('readiness', 'varchar','caption=Готовност %');
		}
		
		return $fld;
	}
	
	
	/**
	 * Крайните дати за плащане
	 */
	private function getSaleDueDates($saleRec)
	{
		$dates = array();
		
		$jQuery = planning_Jobs::getQuery();
		$jQuery->where("#saleId = {$saleRec->id} AND (#state = 'active' OR #state = 'stopped' OR #state = 'wakeup' OR #state = 'closed')");
		$jQuery->XPR('max', 'int', "MAX(#dueDate)");
		$jQuery->XPR('min', 'int', "MIN(#dueDate)");
		$jQuery->show('min,max');
		
		$fRec = $jQuery->fetch();
		if(isset($fRec->min) || isset($fRec->max)){
			$dates['min'] = $fRec->min;
			$dates['max'] = $fRec->max;
		}
		
		return $dates;
	}
	
	
	/**
	 * Изчислява готовността на продажбата
	 * 
	 * @param stdClass $saleRec - запис на продажба
	 * @return double|NULL      - готовност между 0 и 1, или NULL ако няма готовност
	 */
	private static function calcSaleReadiness($saleRec)
	{
		// На не чакащите и не активни не се изчислява готовността
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
			
			$price = (isset($pRec->discount)) ? ($pRec->price - ($pRec->discount * $pRec->price)) : $pRec->price;
			$amount = NULL;
			
			// Ако артикула е нестандартен и има приключено задание по продажбата и няма друго активно по нея
			$q = $pRec->quantity;
			
			$ignore = FALSE;
			if($productRec->isPublic == 'no'){
				$closedJobId = planning_Jobs::fetchField("#productId = {$pId} AND #state = 'closed' AND #saleId = {$saleRec->id}");
				$activeJobId = planning_Jobs::fetchField("#productId = {$pId} AND (#state = 'active' OR #state = 'stopped' OR #state = 'wakeup') AND #saleId = {$saleRec->id}");
						
				// Се приема че е готово
				if($closedJobId && !$activeJobId){
					
					// Ако има приключено задание
					$q = planning_Jobs::fetchField($closedJobId, 'quantity');
					$amount = $q * $price;
					
					if(isset($shippedProducts[$pId])){
						$ignore = TRUE;
					}
				}
			}
			
			// Количеството е неекспедираното
			if($ignore === TRUE){
				$quantity = 0;
			} else {
				if(isset($shippedProducts[$pId])){
					if(!empty($shippedProducts[$pId]->quantity)){
						if($q / $shippedProducts[$pId]->quantity > 0.9) continue;
					}
					
					$quantity = $q - $shippedProducts[$pId]->quantity;
				} else {
					$quantity = $q;
				}
			}
			
			// Ако всичко е експедирано се пропуска реда
			if($quantity <= 0) continue;
			
			$totalAmount += $quantity * $price;
			
			if(is_null($amount)){
						
				// Изчислява се колко от сумата на артикула може да се изпълни
				$quantityInStock = store_Products::getQuantity($pId, $saleRec->shipmentStoreId);
				$quantityInStock = ($quantityInStock > $quantity) ? $quantity : (($quantityInStock < 0) ? 0 : $quantityInStock);
				
				$amount = $quantityInStock * $price;
				
			}
			
			// Събиране на изпълнената сума за всеки ред
			if(isset($amount)){
				$readyAmount += $amount;
			}
		}
		
		// Готовността е процента на изпълнената сума от общата
		$readiness = (isset($readyAmount)) ? @round($readyAmount / $totalAmount, 2) : NULL;
		
		// Подсигуряване че процента не е над 100%
		if($readiness > 1){
			$readiness = 1;
		}
		
		// Връщане на изчислената готовност или NULL ако не може да се изчисли
		return $readiness;
	}
	
	
	/**
	 * Изчислява готовността на експедиционното нареждане
	 *
	 * @param stdClass $soRec - запис на ЕН
	 * @return double|NULL    - готовност между 0 и 1, или NULL ако няма готовност
	 */
	private static function calcSoReadiness($soRec)
	{
		// На не чакащите не се изчислява готовност
		if($soRec->state != 'pending') return NULL;
		
		// Намират се детайлите на ЕН-то
		$dQuery = store_ShipmentOrderDetails::getQuery();
		$dQuery->where("#shipmentId = {$soRec->id}");
		$dQuery->show('shipmentId,productId,packagingId,quantity,quantityInPack,price,discount,showMode');
		
		// Детайлите се сумират по артикул
		$all = deals_Helper::normalizeProducts(array($dQuery->fetchAll()));
		
		$totalAmount = 0;
		$readyAmount = NULL;
		
		// За всеки се определя колко % може да се изпълни
		foreach ($all as $pId => $pRec){
			$price = (isset($pRec->discount)) ? ($pRec->price - ($pRec->discount * $pRec->price)) : $pRec->price;
			
			$totalAmount += $pRec->quantity * $price;
				
			// Определя се каква сума може да се изпълни
			$quantityInStock = store_Products::getQuantity($pId, $soRec->storeId);
			$quantityInStock = ($quantityInStock > $pRec->quantity) ? $pRec->quantity : (($quantityInStock < 0) ? 0 : $quantityInStock);
			
			$amount = $quantityInStock * $price;
				
			if(isset($amount)){
				$readyAmount += $amount;
			}
		}
		
		// Готовността е процент на изпълнената сума от общата
		$readiness = (isset($readyAmount)) ? @round($readyAmount / $totalAmount, 2) : NULL;
		
		// Връщане на изчислената готовност или NULL ако не може да се изчисли
		return $readiness;
	}
}