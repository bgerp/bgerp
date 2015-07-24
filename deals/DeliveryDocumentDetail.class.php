<?php



/**
 * Базов клас за наследяване на детайл на ф-ри
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class deals_DeliveryDocumentDetail extends doc_Detail
{
	
	
	/**
	 * Кои полета от листовия изглед да се скриват ако няма записи в тях
	 */
	protected $hideListFieldsIfEmpty = 'discount';
	
	
	/**
	 * Задължителни полета за модела
	 */
	public static function setDocumentFields($mvc)
	{
		$mvc->FLD('classId', 'class(select=title)', 'caption=Мениджър,silent,input=hidden');
		$mvc->FLD('productId', 'int', 'caption=Продукт,notNull,mandatory', 'tdClass=leftCol wrap,silent');
		$mvc->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка,after=productId,mandatory,silent,removeAndRefreshForm=packPrice|discount');
		
		$mvc->FLD('quantity', 'double', 'caption=К-во,input=none');
		$mvc->FLD('quantityInPack', 'double(decimals=2)', 'input=none,column=none');
		$mvc->FLD('price', 'double(decimals=2)', 'caption=Цена,input=none');
		$mvc->FNC('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума,input=none');
		$mvc->FNC('packQuantity', 'double(Min=0)', 'caption=К-во,input=input,mandatory');
		$mvc->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input');
		$mvc->FLD('discount', 'percent(Min=0,max=1)', 'caption=Отстъпка');
		$mvc->FLD('notes', 'richtext(rows=3)', 'caption=Забележки,formOrder=110001');
	}
	
	
	/**
	 * Преди подготвяне на едит формата
	 */
	public static function on_BeforePrepareEditForm($mvc, &$res, $data)
	{
		if($classId = Request::get('classId', 'class(interface=cat_ProductAccRegIntf)')){
			$data->ProductManager = cls::get($classId);
			$mvc->getField('productId')->type = cls::get('type_Key', array('params' => array('mvc' => $data->ProductManager->className, 'select' => 'name')));
		}
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm(core_Mvc $mvc, &$data)
	{
		$rec = &$data->form->rec;
		
		$masterRec = $data->masterRec;
		
		$data->form->fields['packPrice']->unit = "|*" . $masterRec->currencyId . ", ";
		$data->form->fields['packPrice']->unit .= ($masterRec->chargeVat == 'yes') ? "|с ДДС|*" : "|без ДДС|*";
		
		$ProductManager = ($data->ProductManager) ? $data->ProductManager : cls::get($rec->classId);
			
		$products = $mvc->getProducts($ProductManager, $masterRec);
		expect(count($products));
			
		if (empty($rec->id)) {
			$data->form->setField('productId', "removeAndRefreshForm=packPrice|discount|packagingId");
			$data->form->setOptions('productId', array('' => ' ') + $products);
		} else {
			$data->form->setOptions('productId', array($rec->productId => $products[$rec->productId]));
		}
		
		$data->form->setSuggestions('discount', array('' => '') + arr::make('5 %,10 %,15 %,20 %,25 %,30 %', TRUE));
		
		if (!empty($rec->packPrice)) {
			$vat = cls::get($rec->classId)->getVat($rec->productId, $masterRec->valior);
			$rec->packPrice = deals_Helper::getDisplayPrice($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
		}
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 *
	 * @param core_Mvc $mvc
	 * @param core_Form $form
	 */
	protected static function inputDocForm(core_Mvc $mvc, core_Form $form)
	{
		$rec = &$form->rec;
		$masterRec  = $mvc->Master->fetch($rec->{$mvc->masterKey});
		$update = FALSE;
	
		/* @var $ProductMan core_Manager */
		expect($ProductMan = cls::get($rec->classId));
		if($form->rec->productId){
			$vat = cls::get($rec->classId)->getVat($rec->productId, $masterRec->valior);
			
			$productRef = new core_ObjectReference($ProductMan, $rec->productId);
			expect($productInfo = $productRef->getProductInfo());
			
			$packs = $ProductMan->getPacks($rec->productId);
			$form->setOptions('packagingId', $packs);
			$form->setDefault('packagingId', key($packs));
			
			$LastPolicy = ($masterRec->isReverse == 'yes') ? 'ReverseLastPricePolicy' : 'LastPricePolicy';
			if(isset($mvc->$LastPolicy)){
				$policyInfoLast = $mvc->$LastPolicy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->classId, $rec->packagingId, $rec->packQuantity, $masterRec->valior, $masterRec->currencyRate, $masterRec->chargeVat);
				if($policyInfoLast->price != 0){
					$form->setSuggestions('packPrice', array('' => '', "{$policyInfoLast->price}" => $policyInfoLast->price));
				}
			}
		} else {
			$form->setReadOnly('packagingId');
		}
		
		if ($form->isSubmitted() && !$form->gotErrors()) {
			
			// Извличане на информация за продукта - количество в опаковка, единична цена
			$rec = &$form->rec;
	
			// Закръгляме количеството спрямо допустимото от мярката
			$roundQuantity = cat_UoM::round($rec->packQuantity, $rec->productId, $rec->packagingId);
			if($roundQuantity == 0){
				$form->setError('packQuantity', 'Не може да бъде въведено количество, което след закръглянето указано в|* <b>|Артикули|* » |Каталог|* » |Мерки/Опаковки|*</b> |ще стане|* 0');
				return;
			}
			
			if($roundQuantity != $rec->packQuantity){
				$form->setWarning('packQuantity', 'Количеството ще бъде закръглено до указаното в |*<b>|Артикули » Каталог » Мерки/Опаковки|*</b>|');
				 
				// Ако не е чекнат игнора, не продължаваме за да не се изчислят данните
				if(!Request::get('Ignore')){
					return;
				}
				 
				// Закръгляме количеството
				$rec->packQuantity = $roundQuantity;
			}
	
			if(empty($rec->id)){
				$where = "#{$mvc->masterKey} = {$rec->{$mvc->masterKey}} AND #classId = {$rec->classId} AND #productId = {$rec->productId}";
				if($form->getField('packagingId', FALSE)){
					$where .= ($rec->packagingId) ? " AND #packagingId={$rec->packagingId}" : " IS NULL";
				}
				if($pRec = $mvc->fetch($where)){
					$form->setWarning("productId", "Има вече такъв продукт. Искате ли да го обновите?");
					$rec->id = $pRec->id;
					$update = TRUE;
				}
			}
	
			// Ако артикула няма опаковка к-то в опаковка е 1, ако има и вече не е свързана към него е това каквото е било досега, ако още я има опаковката обновяваме к-то в опаковка
			$rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
			$rec->quantity = $rec->packQuantity * $rec->quantityInPack;
	
			if (!isset($rec->packPrice)) {
				
				// Ако продукта има цена от пораждащия документ, взимаме нея, ако не я изчисляваме наново
				$origin = $mvc->Master->getOrigin($masterRec);
				if($origin->haveInterface('bgerp_DealAggregatorIntf')){
					$dealInfo = $origin->getAggregateDealInfo();
					$products = $dealInfo->get('products');
					
					if(count($products)){
						foreach ($products as $p){
							if($rec->classId == $p->classId && $rec->productId == $p->productId && $rec->packagingId == $p->packagingId){
								$policyInfo = new stdClass();
								$policyInfo->price = deals_Helper::getDisplayPrice($p->price, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
								$policyInfo->discount = $p->discount;
								break;
							}
						}
					}
				}
				
				if(!$policyInfo){
					// Ако има политика в документа и той не прави обратна транзакция, използваме нея, иначе продуктовия мениджър
					$Policy = ($masterRec->isReverse == 'yes') ? (($mvc->ReversePolicy) ? $mvc->ReversePolicy : cls::get($rec->classId)->getPolicy()) : (($mvc->Policy) ? $mvc->Policy : cls::get($rec->classId)->getPolicy());
					$policyInfo = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->classId, $rec->packagingId, $rec->packQuantity, $masterRec->valior, $masterRec->currencyRate, $masterRec->chargeVat);
				}
				
				// Ако няма последна покупна цена и не се обновява запис в текущата покупка
				if (empty($policyInfo->price) && empty($pRec)) {
					$form->setError('packPrice', 'Продукта няма цена в избраната ценова политика');
				} else {
						
					// Ако се обновява вече съществуващ запис
					if($pRec){
						$pRec->packPrice = deals_Helper::getDisplayPrice($pRec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
					}
						
					// Ако се обновява запис се взима цената от него, ако не от политиката
					$rec->price = ($pRec->price) ? $pRec->price : $policyInfo->price;
					$rec->packPrice = ($pRec->packPrice) ? $pRec->packPrice : $policyInfo->price * $rec->quantityInPack;
				}
				
				if($policyInfo->discount && empty($rec->discount)){
					$rec->discount = $policyInfo->discount;
				}
				
			} else {
				// Изчисляване цената за единица продукт в осн. мярка
				$rec->price  = $rec->packPrice  / $rec->quantityInPack;
				
				// Обръщаме цената в основна валута, само ако не се ъпдейтва или се ъпдейтва и е чекнат игнора
				if(!$update || ($update && Request::get('Ignore'))){
					$rec->packPrice =  deals_Helper::getPurePrice($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
				}
			}
			
			$rec->price = deals_Helper::getPurePrice($rec->price, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
			
			// При редакция, ако е променена опаковката слагаме преудпреждение
			if($rec->id){
				$oldRec = $mvc->fetch($rec->id);
				if($oldRec && $rec->packagingId != $oldRec->packagingId && trim($rec->packPrice) == trim($oldRec->packPrice)){
					$form->setWarning('packPrice,packagingId', 'Опаковката е променена без да е променена цената.|*<br />| Сигурнили сте че зададената цена отговаря на  новата опаковка!');
				}
			}
		}
	}
		

	/**
	 * Преди рендиране на таблицата
	 */
	public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
	{
		$recs = &$data->recs;
		$rows = &$data->rows;
		 
		// Скриваме полето "мярка"
		$data->listFields = array_diff_key($data->listFields, arr::make('quantityInPack', TRUE));
		
		if(!count($recs)) return;
		
		if(count($data->rows)) {
			foreach ($data->rows as $i => &$row) {
				$rec = &$data->recs[$i];
		
				// Показваме подробната информация за опаковката при нужда
				deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
				
				$row->weight = (!empty($rec->weight)) ? $row->weight : "<span class='quiet'>0</span>";
				$row->volume = (!empty($rec->volume)) ? $row->volume : "<span class='quiet'>0</span>";
			}
		}
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if(($action == 'edit' || $action == 'delete' || $action == 'add') && isset($rec)){
			if($mvc->Master->fetchField($rec->{$mvc->masterKey}, 'state') != 'draft'){
				$requiredRoles = 'no_one';
			}
		}
	}
	
	
	/**
	 * След извличане на записите от базата данни
	 */
	public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
	{
		$recs = &$data->recs;
		$orderRec = $data->masterData->rec;
	
		deals_Helper::fillRecs($mvc->Master, $recs, $orderRec);
	}


	/**
	 * След подготовка на лист тулбара
	 */
	public static function on_AfterPrepareListToolbar($mvc, &$data)
	{
		if (!empty($data->toolbar->buttons['btnAdd'])) {
			$productManagers = core_Classes::getOptionsByInterface('cat_ProductAccRegIntf');
			$masterRec = $data->masterData->rec;
	
			foreach ($productManagers as $manId => $manName) {
				$productMan = cls::get($manId);
				$products = $mvc->getProducts($productMan, $masterRec);
				 
				if(!count($products)){
					$error = "error=Няма {$productMan->title}, ";
				}
	
				$title = mb_strtolower($productMan->singleTitle);
				$data->toolbar->addBtn($productMan->singleTitle, array($mvc, 'add', $mvc->masterKey => $masterRec->id, 'classId' => $manId, 'ret_url' => TRUE),
						"id=btnAdd-{$manId},{$error} order=10,title=Добавяне на {$title}", 'ef_icon = img/16/shopping.png');
				unset($error);
			}
	
			unset($data->toolbar->buttons['btnAdd']);
		}
	}
	
	
	/**
	 * Изчисляване на цена за опаковка на реда
	 */
	public static function on_CalcPackPrice(core_Mvc $mvc, $rec)
	{
		if (!isset($rec->price) || empty($rec->quantity) || empty($rec->quantityInPack)) {
			return;
		}
	
		$rec->packPrice = $rec->price * $rec->quantityInPack;
	}
	
	
	/**
	 * Изчисляване на количеството на реда в брой опаковки
	 */
	public static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
	{
		if (!isset($rec->price) || empty($rec->quantity) || empty($rec->quantityInPack)) {
			return;
		}
	
		$rec->packQuantity = $rec->quantity / $rec->quantityInPack;
	}
	
	
	/**
	 * Изчисляване на сумата на реда
	 */
	public static function on_CalcAmount(core_Mvc $mvc, $rec)
	{
		if (empty($rec->price) || empty($rec->quantity)) {
			return;
		}
	
		$rec->amount = $rec->price * $rec->quantity;
	}
}