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
		$mvc->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Продукт','tdClass=large-field leftCol wrap,silent,removeAndRefreshForm=packPrice|discount|packagingId');
		$mvc->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка','tdClass=small-field,silent,removeAndRefreshForm=packPrice|discount,mandatory');
		$mvc->FLD('quantity', 'double', 'caption=К-во,mandatory','tdClass=small-field');
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
	
		$data->form->fields['packPrice']->unit = "|*" . $masterRec->currencyId . ", ";
		$data->form->fields['packPrice']->unit .= ($masterRec->chargeVat == 'yes') ? "|с ДДС|*" : "|без ДДС|*";
	
		$products = cat_Products::getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->valior, $mvc->metaProducts);
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
		
		if($masterRec->type === 'dc_note'){
			$data->form->info = tr('|*<div style="color:#333;margin-top:3px;margin-bottom:12px">|Моля въведете крайното количество|* <b>|или|*</b> |сума след промяната|* <br><small>( |системата автоматично ще изчисли и попълни разликата в известието|* )</small></div>');
			$data->form->setField('quantity', 'caption=|Крайни|* (|след известието|*)->К-во');
			$data->form->setField('packPrice', 'caption=|Крайни|* (|след известието|*)->Цена');
			
			foreach (array('packagingId', 'notes', 'discount') as $fld){
				$data->form->setField($fld, 'input=hidden');
			}
			$data->form->setFieldTypeParams('quantity', array('min' => 0));
		} else {
			$data->form->setFieldTypeParams('quantity', array('Min' => 0));
		}
		
		if (!empty($rec->packPrice)) {
			$rec->packPrice = deals_Helper::getDisplayPrice($rec->packPrice, 0, $masterRec->rate, 'no');
		}
	}


	/**
	 * След подготовка на лист тулбара
	 */
	public static function on_AfterPrepareListToolbar($mvc, &$data)
	{
		if (!empty($data->toolbar->buttons['btnAdd'])) {
			unset($data->toolbar->buttons['btnAdd']);
			
			$error = '';
			if(!count(cat_Products::getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->valior, $mvc->metaProducts, NULL, 1))){
				$text = ($mvc->metaProducts == 'canSell') ? "продаваеми" : "купуваеми";
				$error = "error=Няма {$text} артикули,";
			}
	
			$data->toolbar->addBtn('Артикули', array($mvc, 'add', "{$mvc->masterKey}" => $data->masterId, 'ret_url' => TRUE),
					"id=btnAdd,{$error} order=10,title=Добавяне на артикул", 'ef_icon = img/16/shopping.png');
			
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
		if (!isset($rec->price) || empty($rec->quantityInPack)) {
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
		
		$row->productId = cat_Products::getProductDescShort($rec->productId);
		if($rec->notes){
			$row->productId .= "<div class='small'>{$mvc->getFieldType('notes')->toVerbal($rec->notes)}</div>";
		}
		
		// Показваме подробната информация за опаковката при нужда
		deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
		
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
	
		if($form->rec->productId){
			$vat = cat_Products::getVat($rec->productId);
			$productInfo = cat_Products::getProductInfo($rec->productId);
			
			$packs = cat_Products::getPacks($rec->productId);
			$form->setOptions('packagingId', $packs);
				
			if(isset($mvc->LastPricePolicy)){
				$policyInfoLast = $mvc->LastPricePolicy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $masterRec->rate);
					
				if($policyInfo->price != 0){
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
			$roundQuantity = cat_UoM::round($rec->quantity, $rec->productId);
			
			if($roundQuantity == 0 && $masterRec->type != 'dc_note'){
				$form->setError('packQuantity', 'Не може да бъде въведено количество, което след закръглянето указано в|* <b>|Артикули|* » |Каталог|* » |Мерки/Опаковки|*</b> |ще стане|* 0');
				return;
			}
			
			if($roundQuantity != $rec->quantity){
				$form->setWarning('quantity', 'Количеството ще бъде закръглено до указаното в |*<b>|Артикули » Каталог » Мерки/Опаковки|*</b>|');
				$rec->quantity = $roundQuantity;
			}
	
			$rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
			
			// Ако няма въведена цена
			if (!isset($rec->packPrice)) {
						
				// Ако продукта има цена от пораждащия документ, взимаме нея, ако не я изчисляваме наново
				$origin = $mvc->Master->getOrigin($masterRec);
				$dealInfo = $origin->getAggregateDealInfo();
				$products = $dealInfo->get('products');
						
				if(count($products)){
					foreach ($products as $p){
						if($rec->productId == $p->productId && $rec->packagingId == $p->packagingId){
							$policyInfo = new stdClass();
							$policyInfo->price = deals_Helper::getDisplayPrice($p->price, $vat, $masterRec->rate, 'no');
							$policyInfo->discount = $p->discount;
							break;
						}
					}
				}
						
				if(!$policyInfo){
					$Policy = cls::get('price_ListToCustomers');
					$policyInfo = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->quantity, dt::now(), $masterRec->rate);
				}
					
				// Ако няма последна покупна цена и не се обновява запис в текущата покупка
				if (empty($policyInfo->price) && empty($pRec)) {
					$form->setError('packPrice', 'Продукта няма цена в избраната ценова политика');
				} else {
							
					// Ако се обновява запис се взима цената от него, ако не от политиката
					$rec->price = $policyInfo->price;
					$rec->packPrice = $policyInfo->price * $rec->quantityInPack;
					
					if($policyInfo->discount && empty($rec->discount)){
						$rec->discount = $policyInfo->discount;
					}
				}
	
			} else {
				// Изчисляване цената за единица продукт в осн. мярка
				$rec->price  = $rec->packPrice  / $rec->quantityInPack;
				$rec->packPrice =  deals_Helper::getPurePrice($rec->packPrice, 0, $masterRec->rate, $masterRec->vatRate);
			}
	
			$rec->price = deals_Helper::getPurePrice($rec->price, 0, $masterRec->rate, $masterRec->chargeVat);
			
			// Ако има такъв запис, сетваме грешка
			$exRec = deals_Helper::fetchExistingDetail($mvc, $rec->{$mvc->masterKey}, $rec->id, $rec->productId, $rec->packagingId, $rec->price, $rec->discount);
			if($exRec){
				$form->setError('productId,packagingId,packPrice,discount', 'Вече съществува запис със същите данни');
				unset($rec->packPrice, $rec->price, $rec->quantityInPack);
			}
			
			// Записваме основната мярка на продукта
			$rec->amount = $rec->packPrice * $rec->quantity;
				
			// При редакция, ако е променена опаковката слагаме преудпреждение
			if($rec->id){
				$oldRec = $mvc->fetch($rec->id);
				if($oldRec && $rec->packagingId != $oldRec->packagingId && trim($rec->packPrice) == trim($oldRec->packPrice)){
					$form->setWarning('packPrice,packagingId', "Опаковката е променена без да е променена цената.|*<br />| Сигурнили сте, че зададената цена отговаря на  новата опаковка!");
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