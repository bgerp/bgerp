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
	public $hideListFieldsIfEmpty = 'discount, reff';
	
	
	/**
	 * Задължителни полета за модела
	 */
	public static function setDocumentFields($mvc)
	{
		$mvc->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,notNull,mandatory', 'tdClass=productCell leftCol wrap,silent');
		$mvc->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка,smartCenter,tdClass=small-field nowrap,after=productId,mandatory,silent,removeAndRefreshForm=packPrice|discount,input=hidden');
		
		$mvc->FLD('quantity', 'double', 'caption=Количество,input=none');
		$mvc->FLD('quantityInPack', 'double(decimals=2)', 'input=none,column=none');
		$mvc->FLD('price', 'double(decimals=2)', 'caption=Цена,input=none');
		$mvc->FNC('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума,input=none');
		$mvc->FNC('packQuantity', 'double(Min=0)', 'caption=Количество,smartCenter,input=input');
		$mvc->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input,smartCenter');
		$mvc->FLD('discount', 'percent(min=0,max=1)', 'caption=Отстъпка,smartCenter');
		$mvc->FLD('notes', 'richtext(rows=3)', 'caption=Забележки');
		
		$mvc->setDbUnique("{$mvc->masterKey},productId,packagingId,price,quantity,discount");
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
		
		$products = $mvc->getProducts($masterRec);
		expect(count($products));
			
		if (empty($rec->id)) {
			$data->form->setField('productId', "removeAndRefreshForm=packPrice|discount|packagingId|batch");
			$data->form->setOptions('productId', array('' => ' ') + $products);
		} else {
			$data->form->setOptions('productId', array($rec->productId => $products[$rec->productId]));
		}
		
		$data->form->setSuggestions('discount', array('' => '') + arr::make('5 %,10 %,15 %,20 %,25 %,30 %', TRUE));
		
		if (!empty($rec->packPrice)) {
			$vat = cat_Products::getVat($rec->productId, $masterRec->valior);
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
	
		if($form->rec->productId){
			$vat = cat_Products::getVat($rec->productId, $masterRec->valior);
			$productInfo = cat_Products::getProductInfo($rec->productId);
			
			$packs = cat_Products::getPacks($rec->productId);
			$form->setOptions('packagingId', $packs);
			$form->setDefault('packagingId', key($packs));
			
			$LastPolicy = ($masterRec->isReverse == 'yes') ? 'ReverseLastPricePolicy' : 'LastPricePolicy';
			if(isset($mvc->{$LastPolicy})){
				$policyInfoLast = $mvc->{$LastPolicy}->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->packQuantity, $masterRec->valior, $masterRec->currencyRate, $masterRec->chargeVat);
				if($policyInfoLast->price != 0){
					$form->setSuggestions('packPrice', array('' => '', "{$policyInfoLast->price}" => $policyInfoLast->price));
				}
			}
			
			if(!isset($productInfo->meta['canStore'])){
				$measureShort = cat_UoM::getShortName($rec->packagingId);
				$form->setField('packQuantity', "unit={$measureShort}");
			} else {
				$form->setField('packagingId', 'input');
			}
		}
		
		if ($form->isSubmitted() && !$form->gotErrors()) {
			if(!isset($rec->packQuantity)){
				$defQuantity = cat_UoM::fetchField($rec->packagingId, 'defQuantity');
    			if(!empty($defQuantity)){
    				$rec->packQuantity = $defQuantity;
    			} else {
    				$form->setError('packQuantity', 'Не е въведено количество');
    			}
			}
			
			// Проверка на к-то
			if(!deals_Helper::checkQuantity($rec->packagingId, $rec->packQuantity, $warning)){
				$form->setError('packQuantity', $warning);
			}
	
			// Ако артикула няма опаковка к-то в опаковка е 1, ако има и вече не е свързана към него е това каквото е било досега, ако още я има опаковката обновяваме к-то в опаковка
			$rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
			$rec->quantity = $rec->packQuantity * $rec->quantityInPack;
	
			// Проверка дали к-то е под МКП
			deals_Helper::isQuantityBellowMoq($form, $rec->productId, $rec->quantity, $rec->quantityInPack);
			
			if (!isset($rec->packPrice)) {
				$autoPrice = TRUE;
				
				// Ако продукта има цена от пораждащия документ, взимаме нея, ако не я изчисляваме наново
				$origin = $mvc->Master->getOrigin($masterRec);
				if($origin->haveInterface('bgerp_DealAggregatorIntf')){
					$dealInfo = $origin->getAggregateDealInfo();
					$products = $dealInfo->get('products');
					
					if(count($products)){
						foreach ($products as $p){
							if($rec->productId == $p->productId && $rec->packagingId == $p->packagingId){
								$policyInfo = new stdClass();
								$policyInfo->price = deals_Helper::getDisplayPrice($p->price, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
								$policyInfo->discount = $p->discount;
								break;
							}
						}
					}
				}
				
				if(!$policyInfo){
					$listId = ($dealInfo->get('priceListId')) ? $dealInfo->get('priceListId') : NULL;
					
					// Ако има политика в документа и той не прави обратна транзакция, използваме нея, иначе продуктовия мениджър
					$Policy = ($masterRec->isReverse == 'yes') ? (($mvc->ReversePolicy) ? $mvc->ReversePolicy : cls::get('price_ListToCustomers')) : (($mvc->Policy) ? $mvc->Policy : cls::get('price_ListToCustomers'));
					$policyInfo = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->quantity, $masterRec->valior, $masterRec->currencyRate, $masterRec->chargeVat, $listId);
				}
				
				// Ако няма последна покупна цена и не се обновява запис в текущата покупка
				if (empty($policyInfo->price) && empty($pRec)) {
					$form->setError('packPrice', 'Продуктът няма цена в избраната ценова политика');
				} else {
						
					// Ако се обновява запис се взима цената от него, ако не от политиката
					$rec->price = $policyInfo->price;
					$rec->packPrice = $policyInfo->price * $rec->quantityInPack;
				}
				
				if($policyInfo->discount && !isset($rec->discount)){
					$rec->discount = $policyInfo->discount;
				}
				
			} else {
				$autoPrice = FALSE;
				
				// Изчисляване цената за единица продукт в осн. мярка
				$rec->price  = $rec->packPrice  / $rec->quantityInPack;
				
				if(!$form->gotErrors() || ($form->gotErrors() && Request::get('Ignore'))){
					$rec->packPrice =  deals_Helper::getPurePrice($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
				}
			}
			
			// Проверка на цената
			if(!deals_Helper::isPriceAllowed($rec->price, $rec->quantity, $autoPrice, $msg)){
				$form->setError('packPrice,packQuantity', $msg);
			}
			
			$rec->price = deals_Helper::getPurePrice($rec->price, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
			
			// Ако има такъв запис, сетваме грешка
			$exRec = deals_Helper::fetchExistingDetail($mvc, $rec->{$mvc->masterKey}, $rec->id, $rec->productId, $rec->packagingId, $rec->price, $rec->discount, NULL, NULL, $rec->batch, $rec->expenseItemId, $rec->notes);
			if($exRec){
				$form->setError('productId,packagingId,packPrice,discount,notes', 'Вече съществува запис със същите данни');
				unset($rec->packPrice, $rec->price, $rec->quantity, $rec->quantityInPack);
			}
			
			// При редакция, ако е променена опаковката слагаме преудпреждение
			if($rec->id){
				$oldRec = $mvc->fetch($rec->id);
				if($oldRec && $rec->packagingId != $oldRec->packagingId && trim($rec->packPrice) == trim($oldRec->packPrice)){
					$form->setWarning('packPrice,packagingId', "Опаковката е променена без да е променена цената.|*<br />| Сигурнили сте, че зададената цена отговаря на  новата опаковка!");
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
		$masterRec = $data->masterData->rec;
		$firstDocument = doc_Threads::getFirstDocument($masterRec->threadId);
		
		// Скриваме полето "мярка"
		$data->listFields = array_diff_key($data->listFields, arr::make('quantityInPack', TRUE));
		
		if(!count($recs)) return;
		arr::placeInAssocArray($data->listFields, array('reff' => 'Ваш номер'), 'productId');
		$data->listTableMvc->FNC('reff', 'varchar', 'smartCenter');
		
		$listSysId = ($firstDocument->isInstanceOf('sales_Sales')) ? 'salesList' : 'purchaseList';
		$listId = cond_Parameters::getParameter($masterRec->contragentClassId, $masterRec->contragentId, $listSysId);
		
		if(count($data->rows)) {
			foreach ($data->rows as $i => &$row) {
				$rec = &$data->recs[$i];
				
				// Показваме подробната информация за опаковката при нужда
				deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
				
				// Показване на вашия реф ако има
				if(isset($listId)){
					$row->reff = cat_Listings::getReffByProductId($listId, $rec->productId, $rec->packagingId);
				}
				
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
			unset($data->toolbar->buttons['btnAdd']);
			$products = $mvc->getProducts($data->masterData->rec);
			
			if(!count($products)){
				$error = "error=Няма артикули, ";
			}
	
			$data->toolbar->addBtn('Артикул', array($mvc, 'add', $mvc->masterKey => $data->masterId, 'ret_url' => TRUE),
					"id=btnAdd,{$error} order=10,title=Добавяне на артикул", 'ef_icon = img/16/shopping.png');
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