<?php



/**
 * Клас 'store_plg_DocumentDetail'
 * Плъгин даващ възможност на даден документ да бъде складов документ
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_plg_DocumentDetail extends core_Plugin
{
	
	/**
	 * Преди подготвяне на едит формата
	 */
	static function on_BeforePrepareEditForm($mvc, &$res, $data)
	{
		if($classId = Request::get('classId', 'class(interface=cat_ProductAccRegIntf)')){
			$data->ProductManager = cls::get($classId);
			$mvc->getField('productId')->type = cls::get('type_Key', array('params' => array('mvc' => $data->ProductManager->className, 'select' => 'name', 'maxSuggestions' => 1000000000)));
		}
	}

	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm($mvc, $data)
	{
		$rec = &$data->form->rec;
		$masterRec = $data->masterRec;
	
		$data->form->fields['packPrice']->unit = "|*" . $masterRec->currencyId . ", ";
		$data->form->fields['packPrice']->unit .= ($masterRec->chargeVat == 'yes') ? "|с ДДС|*" : "|без ДДС|*";
	
		$data->form->setSuggestions('discount', arr::make('5 %,10 %,15 %,20 %,25 %,30 %', TRUE));
	
		if (!empty($rec->packPrice)) {
			$vat = cls::get($rec->classId)->getVat($rec->productId, $masterRec->valior);
			$rec->packPrice = deals_Helper::getPriceToCurrency($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
		}
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 *
	 * @param core_Mvc $mvc
	 * @param core_Form $form
	 */
	public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
	{
		$rec = &$form->rec;
		$update = FALSE;
	
		/* @var $ProductMan core_Manager */
		expect($ProductMan = cls::get($rec->classId));
		if($form->rec->productId){
			$form->setOptions('packagingId', $ProductMan->getPacks($rec->productId));
	
			// Само при рефреш слагаме основната опаковка за дефолт
			if($form->cmd == 'refresh'){
				$baseInfo = $ProductMan->getBasePackInfo($rec->productId);
				if($baseInfo->classId == cat_Packagings::getClassId()){
					$form->rec->packagingId = $baseInfo->id;
				}
			}
		}
	
		if ($form->isSubmitted() && !$form->gotErrors()) {
	
			// Извличане на информация за продукта - количество в опаковка, единична цена
			$rec = &$form->rec;
	
			if($rec->packQuantity == 0){
				$form->setError('packQuantity', 'Количеството не може да е|* "0"');
			}
	
			$masterRec  = $mvc->Master->fetch($rec->{$mvc->masterKey});
			$contragent = array($masterRec->contragentClassId, $masterRec->contragentId);
	
			if(empty($rec->id)){
				$where = "#{$mvc->masterKey} = {$rec->{$mvc->masterKey}} AND #classId = {$rec->classId} AND #productId = {$rec->productId}";
				if($pRec = $mvc->fetch($where)){
					$form->setWarning("productId", "Има вече такъв продукт. Искате ли да го обновите?");
					$rec->id = $pRec->id;
					$update = TRUE;
				}
				}
	
				$productRef = new core_ObjectReference($ProductMan, $rec->productId);
				expect($productInfo = $productRef->getProductInfo());
	
				// Определяне на цена, количество и отстъпка за опаковка
				$priceAtDate = ($masterRec->pricesAtDate) ? $masterRec->pricesAtDate : dt::now();
	
				if (empty($rec->packagingId)) {
					// Покупка в основна мярка
					$rec->quantityInPack = 1;
				} else {
					// Покупка на опаковки
					if (!$packInfo = $productInfo->packagings[$rec->packagingId]) {
						$form->setError('packagingId', "Артикула няма цена към дата|* '{$masterRec->date}'");
						return;
					}
	
					$rec->quantityInPack = $packInfo->quantity;
				}
	
				$rec->quantity = $rec->packQuantity * $rec->quantityInPack;
				$vat = cls::get($rec->classId)->getVat($rec->productId, $masterRec->valior);
	
				// Ако няма въведена цена
				if (!isset($rec->packPrice)) {
					
					// Ако продукта има цена от пораждащия документ, взимаме нея, ако не я изчисляваме наново
					$origin = $mvc->Master->getOrigin($masterRec);
					$dealInfo = $origin->getAggregateDealInfo();
					$products = $dealInfo->get('products');
					
					if(count($products)){
						foreach ($products as $p){
							if($rec->classId == $p->classId && $rec->productId == $p->productId && $rec->packagingId == $p->packagingId){
								$policyInfo = new stdClass();
								$policyInfo->price = $p->price;
								break;
							}
						}
					}
					
					if(!$policyInfo){
						// Ако има политика в документа и той не прави обратна транзакция, използваме нея, иначе продуктовия мениджър
						$ProductMan = ($mvc->Policy && $masterRec->isReverse === 'no') ? $mvc->Policy : $ProductMan;
						$policyInfo = $ProductMan->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->classId, $rec->packagingId, $rec->packQuantity, $priceAtDate);
					}
					
					// Ако няма последна покупна цена и не се обновява запис в текущата покупка
					if (!isset($policyInfo->price) && empty($pRec)) {
						$form->setError('price', 'Продукта няма цена в избраната ценова политика');
					} else {
							
						// Ако се обновява вече съществуващ запис
						if($pRec){
							$pRec->packPrice = deals_Helper::getPriceToCurrency($pRec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
						}
							
						// Ако се обновява запис се взима цената от него, ако не от политиката
						$rec->price = ($pRec->price) ? $pRec->price : $policyInfo->price;
						$rec->packPrice = ($pRec->packPrice) ? $pRec->packPrice : $policyInfo->price * $rec->quantityInPack;
					}
	
				} else {
	
					// Обръщаме цената в основна валута, само ако не се ъпдейтва или се ъпдейтва и е чекнат игнора
					if(!$update || ($update && Request::get('Ignore'))){
						$rec->packPrice =  deals_Helper::getPriceFromCurrency($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
					}
	
					// Изчисляване цената за единица продукт в осн. мярка
					$rec->price  = $rec->packPrice  / $rec->quantityInPack;
	
				}
	
				// Записваме основната мярка на продукта
				$rec->uomId = $productInfo->productRec->measureId;
	
				// При редакция, ако е променена опаковката слагаме преудпреждение
				if($rec->id){
					$oldRec = $mvc->fetch($rec->id);
            		if($oldRec && $rec->packagingId != $oldRec->packagingId && trim($rec->packPrice) == trim($oldRec->packPrice)){
						$form->setWarning('packPrice,packagingId', 'Опаковката е променена без да е променена цената.|*<br />| Сигурнили сте че зададената цена отговаря на  новата опаковка!');
					}
				}
			}
		}
}