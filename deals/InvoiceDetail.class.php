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
	public $map = array( 'rateFld'       => 'rate',
						 'chargeVat'     => 'vatRate',
						 'quantityFld'   => 'quantity',
						 'valior'        => 'date',
						 'alwaysHideVat' => TRUE,);
	

	/**
	 * Кои полета от листовия изглед да се скриват ако няма записи в тях
	 */
	public $hideListFieldsIfEmpty = 'discount,reff';
	
	
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
	 * Да се показва ли вашия номер
	 */
	public $showReffCode = TRUE;
	
	
	/**
	 * Да се показва ли кода като в отделна колона
	 */
	public $showCodeColumn = TRUE;
	
	
	/**
	 * Извиква се след описанието на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function setInvoiceDetailFields(&$mvc)
	{
		$mvc->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,mandatory','tdClass=productCell leftCol wrap,silent,removeAndRefreshForm=packPrice|discount|packagingId');
		$mvc->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка','tdClass=small-field nowrap,silent,removeAndRefreshForm=packPrice|discount,mandatory,smartCenter,input=hidden');
		$mvc->FLD('quantity', 'double', 'caption=Количество','tdClass=small-field,smartCenter');
		$mvc->FLD('quantityInPack', 'double(smartRound)', 'input=none');
		$mvc->FLD('price', 'double', 'caption=Цена, input=none');
		$mvc->FLD('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума,input=none');
		$mvc->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input,smartCenter');
		$mvc->FLD('discount', 'percent(min=0,max=1,suggestions=5 %|10 %|15 %|20 %|25 %|30 %)', 'caption=Отстъпка,smartCenter');
		$mvc->FLD('notes', 'richtext(rows=3,bucket=Notes)', 'caption=Допълнително->Забележки,formOrder=110001');
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
		$data->form->setOptions('productId', array('' => ' ') + $products);
	
		if (isset($rec->id)) {
			$data->form->setReadOnly('productId');
		}
		
		if($masterRec->type === 'dc_note'){
			$data->form->info = tr('|*<div style="color:#333;margin-top:3px;margin-bottom:12px">|Моля въведете крайното количество|* <b>|или|*</b> |сума след промяната|* <br><small>( |системата автоматично ще изчисли и попълни разликата в известието|* )</small></div>');
			$data->form->setField('quantity', 'caption=|Крайни|* (|след известието|*)->К-во');
			$data->form->setField('packPrice', 'caption=|Крайни|* (|след известието|*)->Цена');
			
			foreach (array('packagingId', 'notes', 'discount') as $fld){
				$data->form->setField($fld, 'input=none');
			}
			$data->form->setFieldTypeParams('quantity', array('min' => 0));
		} else {
			$data->form->setFieldTypeParams('quantity', array('Min' => 0));
		}
		$data->form->setFieldTypeParams('packPrice', array('min' => 0));
		
		if (!empty($rec->packPrice)) {
			$rec->packPrice = deals_Helper::getDisplayPrice($rec->packPrice, 0, $masterRec->rate, 'no');
		}
		
		if($masterRec->state != 'draft'){
			$fields = $data->form->selectFields("#name != 'notes' AND #name != 'productId' AND #name != 'id' AND #name != 'invoiceId'");
			$data->singleTitle = 'забележка';
			$data->form->editActive = TRUE;
			
			foreach ($fields as $name => $fld){
				$data->form->setField($name, 'input=none');
			}
		}
	}


	/**
	 * След подготовка на лист тулбара
	 */
	public static function on_AfterPrepareListToolbar($mvc, &$data)
	{
		if (!empty($data->toolbar->buttons['btnAdd'])) {
			unset($data->toolbar->buttons['btnAdd']);
			$masterRec = $data->masterData->rec;
			
			$error = '';
			if(!count(cat_Products::getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->valior, $mvc->metaProducts, NULL, 1))){
				$text = ($mvc->metaProducts == 'canSell') ? "продаваеми" : "купуваеми";
				$error = "error=Няма {$text} артикули,";
			}
	
			$data->toolbar->addBtn('Артикул', array($mvc, 'add', "{$mvc->masterKey}" => $data->masterId, 'ret_url' => TRUE),
					"id=btnAdd,{$error} order=10,title=Добавяне на артикул", 'ef_icon = img/16/shopping.png');
		}
		
		// Добавяне на бутон за импортиране на артикулите директно от договора
		if($mvc->haveRightFor('importfromdeal', (object)array("{$mvc->masterKey}" => $data->masterId))){
			$data->toolbar->addBtn('От договора', array($mvc, 'importfromdeal', "{$mvc->masterKey}" => $data->masterId, 'ret_url' => TRUE),
			"id=btnimportfromdeal-{$masterRec->id},{$error} order=10,title=Импортиране на артикулите от договора", array('warning' => 'Редовете на фактурата, ще копират точно тези от договора|*!', 'ef_icon' => 'img/16/shopping.png'));
		}
	}
	
	
	/**
	 * Импортиране на артикулите от договора във фактурата
	 */
	function act_Importfromdeal()
	{
		// Проверки
		$this->requireRightFor('importfromdeal');
		
		expect($id = Request::get("{$this->masterKey}", 'int'));
		expect($invoiceRec = $this->Master->fetch($id));
		$this->requireRightFor('importfromdeal', (object)array("{$this->masterKey}" => $id));
		
		// Извличане на дийл интерфейса от договора-начало на нишка
		$this->delete("#{$this->masterKey} = {$id}");
		$firstDoc = doc_Threads::getFirstDocument($invoiceRec->threadId);
		$dealInfo = $firstDoc->getAggregateDealInfo();
		
		// За всеки артикул от договора, копира се 1:1
		$productsToSave =  $dealInfo->dealProducts;
		if(is_array($dealInfo->dealProducts)){
			foreach ($dealInfo->dealProducts as $det){
				$det->{$this->masterKey} = $id;
				$det->amount = $det->price * $det->quantity;
				$det->quantity /= $det->quantityInPack;
				unset($det->batches);
				
				$this->save($det);
			}
		}
		 
		// Редирект обратно към фактурата
		return followRetUrl(NULL, 'Артикулите от сделката са копирани успешно');
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
			self::modifyDcDetails($recs, $rec, $this);
		}
		
		deals_Helper::fillRecs($this->Master, $recs, $rec, $this->map);
	}
	
	
	/**
	 * Помощна ф-я за обработката на записите на КИ и ДИ
	 * 
	 * @param array $recs
	 * @param stdClass $rec
	 */
	public static function modifyDcDetails(&$recs, $rec, $mvc)
	{
		expect($rec->type === 'dc_note');
		
		if(count($recs)){
			// Намираме оригиналните к-ва и цени
			$cached = $mvc->Master->getInvoiceDetailedInfo($rec->originId);
		
			// За всеки запис ако е променен от оригиналния показваме промяната
			$count = 0;
			foreach($recs as &$dRec){
				$originRef = $cached->recs[$count][$dRec->productId];
					
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
				$count++;
			}
		}
	}
	
	
	/**
	 * Преди рендиране на таблицата
	 */
	public static function on_BeforeRenderListTable($mvc, &$res, $data)
	{
		if(!count($data->rows)) return;
		
		$masterRec = $data->masterData->rec;
		
		$batchesInstalled = core_Packs::isInstalled('batch');
		foreach ($data->rows as $id => &$row1){
			$rec = $data->recs[$id];
			
			if($batchesInstalled && !empty($rec->batches)){
				$b = batch_BatchesInDocuments::displayBatchesForInvoice($rec->productId, $rec->batches);
				if(!empty($b)){
					if(is_string($row1->productId)){
						$row1->productId .= "<div class='small'>{$b}</div>";
					} else {
						$row1->productId->append(new core_ET("<div class='small'>[#BATCH#]</div>"));
						$row1->productId->replace($b, 'BATCH');
					}
				}
			}
			
			deals_Helper::addNotesToProductRow($row1->productId, $rec->notes);
		}
		
		if($masterRec->type != 'dc_note') return;
		
		foreach ($data->rows as $id => &$row){
			$rec = $data->recs[$id];
			
			$changed = FALSE;
			
			foreach (array('Quantity' => 'quantity', 'Price' => 'packPrice', 'Amount' => 'amount') as $key => $fld){
				if($rec->{"changed{$key}"} === TRUE){
					$changed = TRUE;
					if($rec->{$fld} < 0){ 
						$row->{$fld} = "<span style='color:red'>{$row->{$fld}}</span>";
					} elseif($rec->{$fld} > 0){
						$row->{$fld} = "<span style='color:green'>+{$row->{$fld}}</span>";
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
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareListRows($mvc, &$data)
	{
		$masterRec = $data->masterData->rec;
		
		if(isset($masterRec->type)){
			if($masterRec->type == 'debit_note' || $masterRec->type == 'credit_note' || ($masterRec->type == 'dc_note' && isset($masterRec->changeAmount) && !count($data->rows))){
				// При дебитни и кредитни известия показваме основанието
				$data->listFields = array();
				$data->listFields['RowNumb'] = '№';
				$data->listFields['reason'] = 'Основание';
				$data->listFields['amount'] = 'Сума';
				$data->rows = array();
				
				// Показване на сумата за промяна на известието
				$Type = $mvc->getFieldType('amount');
				$Type->params['decimals'] = 2;
				$amount = $Type->toVerbal($masterRec->dealValue / $masterRec->rate);
				$originRec = doc_Containers::getDocument($masterRec->originId)->rec();
				
				if($originRec->dpOperation == 'accrued'){
					$reason = ($amount > 0) ? 'Увеличаване на авансово плащане' : 'Намаляване на авансово плащане';
				} else {
					$reason = ($amount > 0) ? 'Увеличаване на стойност' : 'Намаляване на стойност';
				}
				
				$data->recs['advance'] = (object) array('amount' => $masterRec->dealValue / $masterRec->rate, 'changedAmount' => TRUE);
				
				core_Lg::push($masterRec->tplLang);
				$data->rows['advance'] = (object) array('RowNumb' => 1, 'reason' => tr($reason), 'amount' => $amount);
				core_Lg::pop();
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
		$masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
		$lang = doc_TplManager::fetchField($masterRec->template, 'lang');
		$date = ($masterRec->state == 'draft') ? NULL : $masterRec->modifiedOn;
		
		$row->productId = cat_Products::getAutoProductDesc($rec->productId, $date, 'short', 'invoice', $lang, 1, FALSE);
		
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
					
					if($action == 'edit'){
						if($masterRec->state == 'active'){
							if($masterRec->createdBy == $userId || haveRole('ceo,manager', $userId) || keylist::isIn($userId, core_Users::getTeammates($masterRec->createdBy))){
								$res = 'powerUser';
							} 
						}
					}
				} else {
					if(!haveRole('invoicer, ceo', $userId)){
						$res = 'no_one';
					}
					
					// При начисляване на авансово плащане не може да се добавят други продукти
					if($masterRec->dpOperation == 'accrued'){
						$res = 'no_one';
					}
				}
			} elseif(isset($hasType) && $mvc->Master->fetchField($rec->{$mvc->masterKey}, 'type') == 'dc_note') {
				
				// На ДИ и КИ не можем да изтриваме и добавяме
				if($action == 'add' || $action == 'delete'){
					$res = 'no_one';
				}
			}
		}
		
		if($action == 'importfromdeal'){
			$res = $mvc->getRequiredRoles('add', $rec, $userId);
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
		
		if($form->rec->productId && $masterRec->type != 'dc_note' && $form->editActive !== TRUE){
			$vat = cat_Products::getVat($rec->productId, $masterRec->date);
			$productInfo = cat_Products::getProductInfo($rec->productId);
			
			$packs = cat_Products::getPacks($rec->productId);
			$form->setOptions('packagingId', $packs);
			$form->setDefault('packagingId', key($packs));
			
			// Ако артикула не е складируем, скриваме полето за мярка
			if(!isset($productInfo->meta['canStore'])){
				$measureShort = cat_UoM::getShortName($form->rec->packagingId);
				$form->setField('quantity', "unit={$measureShort}");
			} else {
    			$form->setField('packagingId', 'input');
    		}
			
			if(isset($mvc->LastPricePolicy)){
				$policyInfoLast = $mvc->LastPricePolicy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $masterRec->rate);
					
				if($policyInfoLast->price != 0){
					$form->setSuggestions('packPrice', array('' => '', "{$policyInfoLast->price}" => $policyInfoLast->price));
				}
			}
		} else {
			$form->setReadOnly('packagingId');
		}
		
		if ($form->isSubmitted() && !$form->gotErrors()) {
			if(!isset($rec->quantity) && $masterRec->type != 'dc_note'){
				$form->setDefault('quantity', deals_Helper::getDefaultPackQuantity($rec->productId, $rec->packagingId));
				if(empty($rec->quantity)){
					$form->setError('quantity', 'Не е въведено количество');
				}
			}
			
			if($masterRec->type == 'dc_note'){
				if(!isset($rec->packPrice) || !isset($rec->quantity)){
					$form->setError('packPrice,packQuantity', 'Количеството и сумата трябва да са попълнени');
					return;
				}
			}
			
			// Проверка на к-то
    		if(!deals_Helper::checkQuantity($rec->packagingId, $rec->quantity, $warning)){
    			$form->setError('quantity', $warning);
    		}
	
    		if($masterRec->type != 'dc_note'){
    			$rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
    		}
    		
			// Ако няма въведена цена
			if (!isset($rec->packPrice) && $masterRec->type != 'dc_note') {
				$autoPrice = TRUE;
				
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
					$listId = ($dealInfo->get('priceListId')) ? $dealInfo->get('priceListId') : NULL;
					$Policy = cls::get('price_ListToCustomers');
					$policyInfo = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->quantity, dt::today(), $masterRec->rate, 'no', $listId);
				}
				
				// Ако няма последна покупна цена и не се обновява запис в текущата покупка
				if (empty($policyInfo->price)) {
					$form->setError('packPrice', 'Продуктът няма цена в избраната ценова политика (3)');
				} else {
							
					// Ако се обновява запис се взима цената от него, ако не от политиката
					$rec->price = $policyInfo->price;
					$rec->packPrice = $policyInfo->price * $rec->quantityInPack;
					
					if($policyInfo->discount && !isset($rec->discount)){
						$rec->discount = $policyInfo->discount;
					}
				}
	
			} else {
				$autoPrice = FALSE;
				
				// Изчисляване цената за единица продукт в осн. мярка
				$rec->price = $rec->packPrice  / $rec->quantityInPack;
				$packPrice = NULL;
				if(!$form->gotErrors() || ($form->gotErrors() && Request::get('Ignore'))){
					$rec->packPrice = deals_Helper::getPurePrice($rec->packPrice, 0, $masterRec->rate, $masterRec->vatRate);
				} else {
					$packPrice = deals_Helper::getPurePrice($rec->packPrice, 0, $masterRec->rate, $masterRec->vatRate);
				}
			}
			
			// Проверка на цената
			if(!deals_Helper::isPriceAllowed($rec->price, $rec->quantity, $autoPrice, $msg)){
				$form->setError('packPrice,quantity', $msg);
			}
			
			$rec->price = deals_Helper::getPurePrice($rec->price, 0, $masterRec->rate, $masterRec->chargeVat);
			
			// Ако има такъв запис, сетваме грешка
			$exRec = deals_Helper::fetchExistingDetail($mvc, $rec->{$mvc->masterKey}, $rec->id, $rec->productId, $rec->packagingId, $rec->price, $rec->discount, NULL, NULL, NULL, NULL, $rec->notes);
			if($exRec){
				$form->setError('productId,packagingId,packPrice,discount,notes', 'Вече съществува запис със същите данни');
				unset($rec->packPrice, $rec->price, $rec->quantityInPack);
			}
			
			// Записваме основната мярка на продукта
			$rec->amount = $rec->packPrice * $rec->quantity;
				
			// При редакция, ако е променена опаковката слагаме преудпреждение
			if($rec->id){
				$oldRec = $mvc->fetch($rec->id);
				if($oldRec && $rec->packagingId != $oldRec->packagingId && trim($rec->packPrice) == trim($oldRec->packPrice)){
					$form->setWarning('packPrice,packagingId', "Опаковката е променена без да е променена цената.|*<br />| Сигурни ли сте, че зададената цена отговаря на новата опаковка?");
				}
			}
			
			if($masterRec->type === 'dc_note'){
				$cache = $mvc->Master->getInvoiceDetailedInfo($masterRec->originId);
				
				// За да проверим дали има променено и количество и цена
				// намираме този запис кой пдоред детайл е на нареждането
				// и намираме от кешираните стойности оригиналните количества за сравняване
				$recs = array();
				$query = $mvc->getQuery();
				$query->where("#invoiceId = {$masterRec->id}");
				$query->orderBy('id', 'ASC');
				$query->show('id');
				while($dRec = $query->fetch()){
					$recs[] = $dRec->id;
				}
				$index = array_search($rec->id, $recs);
				$cache = $cache->recs[$index][$rec->productId];
				
				$pPrice = isset($packPrice)? $packPrice : $rec->packPrice;
				if(round($cache['quantity'], 5) != round($rec->quantity, 5) && (isset($rec->packPrice) && round($cache['price'], 5) != round($pPrice, 5))){
					$form->setError('quantity,packPrice', 'Не може да е променена и цената и количеството');
				}
			}
		}
	}
}