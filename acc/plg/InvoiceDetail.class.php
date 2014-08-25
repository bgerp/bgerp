<?php



/**
 * Плъгин за детайл на фактура
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_plg_InvoiceDetail extends core_Plugin
{
	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 */
	static function on_AfterPrepareListRows($mvc, &$data)
	{
		$masterRec = $data->masterData->rec;
		if($masterRec->type != 'invoice'){
	
			// При дебитни и кредитни известия показваме основанието
			$data->listFields = array();
			$data->listFields['number'] = '№';
			$data->listFields['reason'] = 'Основание';
			$data->listFields['amount'] = 'Сума';
			$data->rows = array();
	
			// Показване на сумата за промяна на известието
			$amount = $mvc->getFieldType('amount')->toVerbal($masterRec->dealValue / $masterRec->rate);
	
			$data->rows[] = (object) array('number' => 1,
					'reason' => $masterRec->reason,
					'amount' => $amount);
		}
	}
	
	
	/**
	 * След извличане на записите от базата данни
	 */
	public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
	{
		$recs = &$data->recs;
		$invRec = &$data->masterData->rec;
		$haveDiscount = FALSE;
		
		$mvc->calculateAmount($recs, $invRec);
	
		if (empty($recs)) return;
	
		foreach ($recs as &$rec){
			$haveDiscount = $haveDiscount || !empty($rec->discount);
		}
	
		if(!$haveDiscount) {
			unset($data->listFields['discount']);
		}
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$ProductMan = cls::get($rec->classId);
		$row->productId = $ProductMan::getTitleById($rec->productId);
	
		if($rec->note){
			$varchar = cls::get('type_Varchar');
			$row->note = $varchar->toVerbal($rec->note);
			$row->productId .= "<br/><small style='color:#555;'>{$row->note}</small>";
		}
		 
		$pInfo = $ProductMan->getProductInfo($rec->productId);
		$measureShort = cat_UoM::getShortName($pInfo->productRec->measureId);
		
		if($rec->packagingId){
			$row->quantityInPack = $mvc->getFieldType('quantityInPack')->toVerbal($rec->quantityInPack);
			$row->packagingId .= " <small style='color:gray'>{$row->quantityInPack} {$measureShort}</small>";
			$row->packagingId = "<span class='nowrap'>{$row->packagingId}</span>";
		} else {
			$row->packagingId = $measureShort;
		}
	}
	
	
	/**
	 * След проверка на ролите
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'add' && isset($rec->invoiceId)){
			$invType = $mvc->Master->fetchField($rec->invoiceId, 'type');
	
			if($invType == 'invoice'){
				$masterRec = $mvc->Master->fetch($rec->invoiceId);
				if($masterRec->state != 'draft'){
					$res = 'no_one';
				} else {
					// При начисляване на авансово плащане не може да се добавят други продукти
					if($masterRec->dpOperation == 'accrued'){
						$res = 'no_one';
					}
				}
			} else {
				// Към ДИ и КИ немогат да се добавят детайли
				$res = 'no_one';
			}
		}
	}
	
	
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
	 * Преди извличане на записите филтър по number
	 */
	static function on_AfterPrepareListFilter($mvc, &$data)
	{
		$data->query->orderBy('#id', 'ASC');
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
	
			if($rec->quantity == 0){
				$form->setError('quantity', 'Количеството не може да е|* "0"');
			}
	
			$masterRec  = $mvc->Master->fetch($rec->{$mvc->masterKey});
	
			if(empty($rec->id)){
				$where = "#{$mvc->masterKey} = {$rec->{$mvc->masterKey}} AND #classId = {$rec->classId} AND #productId = {$rec->productId} AND #packagingId";
				$where .= ($rec->packagingId) ? "={$rec->packagingId}" : " IS NULL";
				if($pRec = $mvc->fetch($where)){
					$form->setWarning("productId", "Има вече такъв продукт с тази опаковка. Искате ли да го обновите?");
					$rec->id = $pRec->id;
					$update = TRUE;
				}
				}
	
				$productRef = new core_ObjectReference($ProductMan, $rec->productId);
				expect($productInfo = $productRef->getProductInfo());
	
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
						$ProductMan = ($mvc->Policy) ? $mvc->Policy : $ProductMan;
						$policyInfo = $ProductMan->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->classId, $rec->packagingId, $rec->quantity, dt::now());
					}
					
					// Ако няма последна покупна цена и не се обновява запис в текущата покупка
					if (!isset($policyInfo->price) && empty($pRec)) {bp($policyInfo, $ProductMan);
						$form->setError('price', 'Продукта няма цена в избраната ценова политика');
					} else {
							
						// Ако се обновява вече съществуващ запис
						if($pRec){
							$pRec->packPrice = deals_Helper::getPriceToCurrency($pRec->packPrice, $vat, $masterRec->rate, $masterRec->vatRate);
						}
							
						// Ако се обновява запис се взима цената от него, ако не от политиката
						$rec->price = ($pRec->price) ? $pRec->price : $policyInfo->price;
						$rec->packPrice = ($pRec->packPrice) ? $pRec->packPrice : $policyInfo->price * $rec->quantityInPack;
					}
	
				} else {
					
					// Обръщаме цената в основна валута, само ако не се ъпдейтва или се ъпдейтва и е чекнат игнора
					if(!$update || ($update && Request::get('Ignore'))){
						$rec->packPrice =  deals_Helper::getPriceFromCurrency($rec->packPrice, 0, $masterRec->rate, $masterRec->vatRate);
					}
					
					// Изчисляване цената за единица продукт в осн. мярка
					$rec->price  = $rec->packPrice  / $rec->quantityInPack;
	
				}
	
				// Записваме основната мярка на продукта
				$rec->uomId = $productInfo->productRec->measureId;
				$rec->amount = $rec->packPrice * $rec->quantity;
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