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
abstract class deals_InvoiceDetail extends doc_Detail
{
	
	/**
	 * Помощен масив за мапиране на полета изпозлвани в deals_Helper
	 */
	public $map = array( 'rateFld'     => 'rate',
								'chargeVat'   => 'vatRate',
								'quantityFld' => 'quantity',
								'valior'      => 'date',
								'alwaysHideVat' => TRUE,);
	

	/**
	 * Кои полета от листовия изглед да се скриват ако няма записи в тях
	 */
	protected $hideListFieldsIfEmpty = 'discount';
	
	
	/**
	 * Полета свързани с цени
	 */
	public $priceFields = 'amount,discount,packPrice';
	

	/**
	 * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
	 */
	public $rowToolsField = 'RowNumb';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'productId, packagingId, quantity, packPrice, discount, amount';


	/**
	 * Извиква се след описанието на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function setInvoiceDetailFields(&$mvc)
	{
		$mvc->FLD('productId', 'int', 'caption=Продукт','tdClass=large-field leftCol wrap,removeAndRefreshForm=packPrice|discount|packagingId');
		$mvc->FLD('classId', 'class(interface=cat_ProductAccRegIntf, select=title)', 'caption=Мениджър,silent,input=hidden');
		$mvc->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty, select2MinItems=0)', 'caption=Мярка','tdClass=small-field,silent,removeAndRefreshForm=packPrice|discount|uomId');
		$mvc->FLD('quantity', 'double(Min=0)', 'caption=К-во,mandatory','tdClass=small-field');
		$mvc->FLD('quantityInPack', 'double(smartRound)', 'input=none');
		$mvc->FLD('price', 'double', 'caption=Цена, input=none');
		$mvc->FLD('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума,input=none');
		$mvc->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input');
		$mvc->FLD('discount', 'percent(Min=0,max=1)', 'caption=Отстъпка');
		$mvc->FLD('notes', 'richtext(rows=3)', 'caption=Забележки,formOrder=110001');
	}
	
	
	/**
	 * Извиква се след подготовката на формата
	 */
	public static function on_AfterPrepareEditForm($mvc, $data)
	{
		$rec = &$data->form->rec;
		$masterRec = $data->masterRec;
		$ProductManager = ($data->ProductManager) ? $data->ProductManager : cls::get($rec->classId);
	
		$data->form->fields['packPrice']->unit = "|*" . $masterRec->currencyId . ", ";
		$data->form->fields['packPrice']->unit .= ($masterRec->chargeVat == 'yes') ? "|с ДДС|*" : "|без ДДС|*";
	
		$products = $ProductManager->getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->valior, $mvc->metaProducts);
		expect(count($products));
	
		$data->form->setSuggestions('discount', array('' => '') + arr::make('5 %,10 %,15 %,20 %,25 %,30 %', TRUE));
	
		if (empty($rec->id)) {
			$data->form->setOptions('productId', array('' => ' ') + $products);
			 
		} else {
			// Нямаме зададена ценова политика. В този случай задъжително трябва да имаме
			// напълно определен продукт (клас и ид), който да не може да се променя във формата
			// и полето цена да стане задължително
			$data->form->setOptions('productId', array($rec->productId => $products[$rec->productId]));
		}
	
		if (!empty($rec->packPrice)) {
			$rec->packPrice = deals_Helper::getDisplayPrice($rec->packPrice, 0, $masterRec->rate, 'no');
		}
		
		if($masterRec->type === 'dc_note'){
			foreach (array('packagingId', 'notes', 'discount') as $fld){
				$data->form->setField($fld, 'input=hidden');
			}
		}
		
		// Помощно поле за запомняне на последно избрания артикул
		//@TODO да се махне
		$data->form->FNC('lastProductId', 'int', 'silent,input=hidden');
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
				$error = '';
				if(!count($productMan->getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->valior, $mvc->metaProducts, NULL, 1))){
					$text = ($mvc->metaProducts == 'canSell') ? "продаваеми" : "купуваеми";
					$error = "error=Няма {$text} {$productMan->title},";
				}
	
				$title = mb_strtolower($productMan->singleTitle);
				$data->toolbar->addBtn($productMan->singleTitle, array($mvc, 'add', "{$mvc->masterKey}" => $masterRec->id, 'classId' => $manId, 'ret_url' => TRUE),
						"id=btnAdd-{$manId},{$error} order=10,title=Добавяне на {$title}", 'ef_icon = img/16/shopping.png');
				unset($error);
			}
	
			unset($data->toolbar->buttons['btnAdd']);
		}
	}

	
	/**
	 * Изчисляване на цена за опаковка на реда
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $rec
	 */
	public static function on_CalcPackPrice(core_Mvc $mvc, $rec)
	{
		if (!isset($rec->price) || empty($rec->quantity) || empty($rec->quantityInPack)) {
			return;
		}
	
		$rec->packPrice = $rec->price * $rec->quantityInPack;
	}
	
	
	/**
	 * След калкулиране на общата сума
	 */
	public function calculateAmount_(&$recs, &$rec)
	{	
		// Ако документа е известие
		if($rec->type === 'dc_note'){
			if(count($recs)){
				// Намираме оригиналните к-ва и цени 
				$cached = $this->Master->getInvoiceDetailedInfo($rec->originId);
				
				// За всеки запис ако е променен от оригиналния показваме промяната
				foreach($recs as &$dRec){
					$originRef = $cached[$dRec->productId][$dRec->packagingId];
					
					$diffQuantity = $dRec->quantity - $originRef['quantity'];
					$diffPrice = $dRec->packPrice - $originRef['price'];
					
					if(round($diffQuantity, 5) != 0){
						$dRec->quantity = $diffQuantity;
						$dRec->changedQuantity = TRUE;
					}
					
					if(round($diffPrice, 5) != 0){
						$dRec->packPrice = $diffPrice;
						$dRec->changedPrice = TRUE;
					}
				}
			} 
		}
		
		deals_Helper::fillRecs($this->Master, $recs, $rec, $this->map);
	}
	
	
	public static function on_BeforeRenderListTable($mvc, &$res, $data)
	{
		if(!count($data->rows)) return;
		
		$masterRec = $data->masterData->rec;
		if($masterRec->type != 'dc_note') return;
		
		foreach ($data->rows as $id => &$row){
			$rec = $data->recs[$id];
			
			$changed = FALSE;
			
			foreach (array('Quantity' => 'quantity', 'Price' => 'packPrice', 'Amount' => 'amount') as $key => $fld){
				if($rec->{"changed{$key}"} === TRUE){
					$changed = TRUE;
					if($rec->$fld < 0){
						$row->$fld = "<span style='color:red'>{$row->$fld}</span>";
					} elseif($rec->$fld > 0){
						$row->$fld = "<span style='color:green'>+{$row->$fld}</span>";
					}
				}
			}
			
			// Ако няма промяна реда
			if($changed === FALSE){
				
				// При активна ф-ра не го показваме
				if($masterRec->state == 'active'){
					unset($data->rows[$id]);
				} else {
					
					// Иначе го показваме в сив ред
					$row->ROW_ATTR['style'] = " background-color:#f1f1f1;color:#777";
				}
			}
		}
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 */
	public static function on_AfterPrepareListRows($mvc, &$data)
	{
		$masterRec = $data->masterData->rec;
		
		if(isset($masterRec->type)){
			if($masterRec->type == 'debit_note' || $masterRec->type == 'credit_note' || ($masterRec->type == 'dc_note' && isset($masterRec->changeAmount) && !count($data->rows))){
				// При дебитни и кредитни известия показваме основанието
				$data->listFields = array();
				$data->listFields['number'] = '№';
				$data->listFields['reason'] = 'Основание';
				$data->listFields['amount'] = 'Сума';
				$data->rows = array();
				
				// Показване на сумата за промяна на известието
				$amount = $mvc->getFieldType('amount')->toVerbal($masterRec->dealValue / $masterRec->rate);
				$originRec = doc_Containers::getDocument($masterRec->originId)->rec();
				
				if($originRec->dpOperation == 'accrued'){
					$reason = ($amount > 0) ? 'Увеличаване на авансово плащане' : 'Намаляване на авансово плащане';
				} else {
					$reason = ($amount > 0) ? 'Увеличаване на стойност на фактура' : 'Намаляване на стойност на фактура';
				}
				
				$data->recs['advance'] = (object) array('amount' => $masterRec->dealValue / $masterRec->rate, 'changedAmount' => TRUE);
				$data->rows['advance'] = (object) array('number' => 1,
						'reason' => $reason,
						'amount' => $amount);
			} 
		}
	}
	
	
	/**
	 * След извличане на записите от базата данни
	 */
	public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
	{
		$recs = &$data->recs;
		$invRec = &$data->masterData->rec;
		
		$mvc->calculateAmount($recs, $invRec);
	}
	
	
	/**
	 * Конвертира един запис в разбираем за човека вид
	 * Входният параметър $rec е оригиналният запис от модела
	 * резултата е вербалният еквивалент, получен до тук
	 */
	public static function recToVerbal_($rec, &$fields = '*')
	{
		$row = parent::recToVerbal_($rec, $fields);
		
		$mvc = cls::get(get_called_class());
		
		$ProductMan = cls::get($rec->classId);
		$row->productId = $ProductMan->getProductDescShort($rec->productId);
		if($rec->notes){
			$row->productId .= "<div class='small'>{$mvc->getFieldType('notes')->toVerbal($rec->notes)}</div>";
		}
			
		$pInfo = $ProductMan->getProductInfo($rec->productId);
		$measureShort = cat_UoM::getShortName($pInfo->productRec->measureId);
		
		if($rec->packagingId){
			if(cat_Packagings::fetchField($rec->packagingId, 'showContents') == 'yes'){
				$row->quantityInPack = $mvc->getFieldType('quantityInPack')->toVerbal($rec->quantityInPack);
				$row->packagingId .= " <small style='color:gray'>{$row->quantityInPack} {$measureShort}</small>";
				$row->packagingId = "<span class='nowrap'>{$row->packagingId}</span>";
			}
		} else {
			$row->packagingId = $measureShort;
		}
		
		return $row;
	}
	
	
	/**
	 * След проверка на ролите
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{
		if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec->{$mvc->masterKey})){
			$hasType = $mvc->Master->getField('type', FALSE);
	
			if(empty($hasType) || (isset($hasType)  && $mvc->Master->fetchField($rec->{$mvc->masterKey}, 'type') == 'invoice')){
				$masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
				if($masterRec->state != 'draft'){
					$res = 'no_one';
				} else {
					// При начисляване на авансово плащане не може да се добавят други продукти
					if($masterRec->dpOperation == 'accrued'){
						$res = 'no_one';
					}
				}
			} elseif(isset($hasType) && $mvc->Master->fetchField($rec->{$mvc->masterKey}, 'type') == 'dc_note') {
				
				// На ДИ и КИ не можем да изтривсме и добавяме
				if($action == 'add' || $action == 'delete'){
					$res = 'no_one';
				}
			}
		}
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
	 * Преди извличане на записите филтър по number
	 */
	public static function on_AfterPrepareListFilter($mvc, &$data)
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
		$masterRec  = $mvc->Master->fetch($rec->{$mvc->masterKey});
		$update = FALSE;
	
		/* @var $ProductMan core_Manager */
		expect($ProductMan = cls::get($rec->classId));
		if($form->rec->productId){
			$vat = cls::get($rec->classId)->getVat($rec->productId);
			
			$productRef = new core_ObjectReference($ProductMan, $rec->productId);
			expect($productInfo = $productRef->getProductInfo());
			
			$packs = $ProductMan->getPacks($rec->productId);
			if(isset($rec->packagingId) && !isset($packs[$rec->packagingId])){
				$packs[$rec->packagingId] = cat_Packagings::getTitleById($rec->packagingId, FALSE);
			}
			if(count($packs)){
				$form->setOptions('packagingId', $packs);
			} else {
				$form->setReadOnly('packagingId');
			}
			$uomName = cat_UoM::getTitleById($productInfo->productRec->measureId);
			$form->setField('packagingId', "placeholder={$uomName}");
			
			// Само при рефреш слагаме основната опаковка за дефолт
			if($form->cmd == 'refresh'){
				$baseInfo = $ProductMan->getBasePackInfo($rec->productId);
				
				// Избираме базовата опаковка само ако сме променяли артикула
				if($baseInfo->classId == 'cat_Packagings' && $form->rec->lastProductId != $rec->productId){
					$form->setDefault('packagingId', $baseInfo->id);
				}
				 
				$form->rec->lastProductId = $rec->productId;
			}
				
			if(isset($mvc->LastPricePolicy)){
				$policyInfoLast = $mvc->LastPricePolicy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->classId, $rec->packagingId, $masterRec->rate);
					
				if($policyInfo->price != 0){
					$form->setSuggestions('packPrice', array('' => '', "{$policyInfoLast->price}" => $policyInfoLast->price));
				}
			}
		}
	
		if ($form->isSubmitted() && !$form->gotErrors()) {
	
			// Извличане на информация за продукта - количество в опаковка, единична цена
			$rec = &$form->rec;
	
			// Закръгляме количеството спрямо допустимото от мярката
			$roundQuantity = cat_UoM::round($rec->quantity, $rec->productId, $rec->packagingId);
				
			if($roundQuantity != $rec->quantity){
				$form->setWarning('quantity', 'Въведеното количество ще бъде закръглено до указаната точност');
					
				// Ако не е чекнат игнора, не продължаваме за да не се изчислят данните
				if(!Request::get('Ignore')){
					return;
				}
					
				// Закръгляме количеството
				$rec->quantity = $roundQuantity;
			}
	
			if(empty($rec->id)){
				$where = "#{$mvc->masterKey} = {$rec->{$mvc->masterKey}} AND #classId = {$rec->classId} AND #productId = {$rec->productId} AND #packagingId";
				$where .= ($rec->packagingId) ? "={$rec->packagingId}" : " IS NULL";
				if($pRec = $mvc->fetch($where)){
					$form->setWarning("productId", "Има вече такъв продукт с тази опаковка. Искате ли да го обновите?");
					$rec->id = $pRec->id;
					$update = TRUE;
				}
			}
	
			$rec->quantityInPack = (empty($rec->packagingId)) ? 1 : $productInfo->packagings[$rec->packagingId]->quantity;
				
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
							$policyInfo->price = deals_Helper::getDisplayPrice($p->price, $vat, $masterRec->rate, 'no');
							$policyInfo->discount = $p->discount;
							break;
						}
					}
				}
						
				if(!$policyInfo){
					$Policy = cls::get($rec->classId)->getPolicy();
					$policyInfo = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->classId, $rec->packagingId, $rec->quantity, dt::now(), $masterRec->rate);
				}
					
				// Ако няма последна покупна цена и не се обновява запис в текущата покупка
				if (empty($policyInfo->price) && empty($pRec)) {
					$form->setError('packPrice', 'Продукта няма цена в избраната ценова политика');
				} else {
							
					// Ако се обновява вече съществуващ запис
					if($pRec){
						$pRec->packPrice = deals_Helper::getDisplayPrice($pRec->packPrice, $vat, $masterRec->rate, 'no');
					}
							
					// Ако се обновява запис се взима цената от него, ако не от политиката
					$rec->price = ($pRec->price) ? $pRec->price : $policyInfo->price;
					$rec->packPrice = ($pRec->packPrice) ? $pRec->packPrice : $policyInfo->price * $rec->quantityInPack;
					
					if($policyInfo->discount && empty($rec->discount)){
						$rec->discount = $policyInfo->discount;
					}
				}
	
			} else {
				// Изчисляване цената за единица продукт в осн. мярка
				$rec->price  = $rec->packPrice  / $rec->quantityInPack;
				
				// Обръщаме цената в основна валута, само ако не се ъпдейтва или се ъпдейтва и е чекнат игнора
				if(!$update || ($update && Request::get('Ignore'))){
					$rec->packPrice =  deals_Helper::getPurePrice($rec->packPrice, 0, $masterRec->rate, $masterRec->vatRate);
				}
			}
	
			$rec->price = deals_Helper::getPurePrice($rec->price, 0, $masterRec->rate, $masterRec->chargeVat);
			
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
			
			if($masterRec->type === 'dc_note'){
				$cache = $mvc->Master->getInvoiceDetailedInfo($masterRec->originId);
				$cache = $cache[$rec->productId][$rec->packagingId];
				
				if(round($cache['quantity'], 5) != round($rec->quantity, 5) && round($cache['price'], 5) != round($rec->packPrice, 5)){
					$form->setError('quantity,packPrice', 'Не може да е променена и цената и количеството');
				}
			}
			
			
			$originRef = $cached[$dRec->productId][$dRec->packagingId];
		}
	}
}