<?php



/**
 * Документ "Ценоразпис"
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Ценоразписи
 */
class price_ListDocs extends core_Master
{
    
	
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'doc_DocumentIntf, doc_ContragentDataIntf, email_DocumentIntf';


    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = TRUE;
    
    
    /**
     * Заглавие
     */
    var $title = 'Ценоразписи';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Cnr";
    
    
     /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, price_Wrapper, doc_DocumentPlg, doc_EmailCreatePlg,
    	 plg_Printing, bgerp_plg_Blank, plg_Sorting, plg_Search, doc_ActivatePlg, doc_plg_BusinessDoc, Products=cat_Products';
    	
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'title, policyId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, date, handler=Документ, title, policyId, state, createdOn, createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Полето за единичен изглед
     */
    var $rowToolsSingleField = 'handler';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'powerUser';
    
    
    /**
     * Кой може да го промени?
     */
    var $canWrite = 'price, ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'price, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'price,ceo';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'sales,price,ceo';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "3.6|Търговия";
    
    
    /**
     * Заглавие
     */
    var $singleTitle = 'Ценоразпис';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 30;
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'price/tpl/templates/ListDoc.shtml';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile2 = 'price/tpl/templates/ListDocWithoutUom.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('date', 'date(smartTime)', 'caption=Дата,mandatory');
    	$this->FLD('policyId', 'key(mvc=price_Lists, select=title)', 'caption=Политика, silent, mandatory');
    	$this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута,input');
    	$this->FLD('vat', 'enum(yes=с ДДС,no=без ДДС)','caption=ДДС');
    	$this->FLD('title', 'varchar(155)', 'caption=Заглавие');
    	$this->FLD('productGroups', 'keylist(mvc=cat_Groups,select=name,makeLinks)', 'caption=Продукти->Групи,columns=2');
    	$this->FLD('packagings', 'keylist(mvc=cat_UoM,select=name)', 'caption=Продукти->Опаковки,columns=3');
    	$this->FLD('products', 'blob(serialize,compress)', 'caption=Данни,input=none');
    	$this->FLD('showUoms', 'enum(yes=Ценоразпис (пълен),no=Ценоразпис без основна мярка)', 'caption=Шаблон,notNull,default=yes');
    }
    
    
    /**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
    	$data->listFilter->showFields = 'search';
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->input();
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    }
    
    
    /**
     * Извиква се след подготовката на формата
     */
	public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$form->setDefault('date', dt::now());
    	$form->setOptions('policyId', $mvc->getDefaultPolicies($form->rec));
    	$folderClassId = doc_Folders::fetchCoverClassId($form->rec->folderId);
    	
    	$suggestions = cat_UoM::getPackagingOptions();
    	$form->setSuggestions('packagings', $suggestions);
    	
    	// Намираме политиката на зададената папка, ако няма
    	// по подразбиране е "каталог"
    	$coverId = doc_Folders::fetchCoverId($form->rec->folderId);
    	$defaultList = price_ListToCustomers::getListForCustomer($folderClassId, $coverId);
    	$form->setDefault('policyId', $defaultList);
    	
    	$form->setDefault('currencyId', $mvc->getDefaultCurrency($form->rec));
    	
    	
    }
    
    
    /**
     * Валута по подразбиране: ако е контрагент - дефолт валутата му,
     * в противен случай основната валута за периода
     */
    private function getDefaultCurrency($rec)
    {
    	 $folderClass = doc_Folders::fetchCoverClassName($rec->folderId);
    	 
    	 if(cls::haveInterface('doc_ContragentDataIntf', $folderClass)){
    	 	$coverId = doc_Folders::fetchCoverId($rec->folderId);
    	 	
    	 	$contragentData = $folderClass::getContragentData($coverId);
    	 	if($contragentData->countryId){
    	 		$currencyId = drdata_Countries::fetchField($contragentData->countryId, 'currencyCode');
    	 	}
    	 }
    	 
    	 return ($currencyId) ? $currencyId : acc_Periods::getBaseCurrencyCode($rec->date);
    }
    
    
    /**
     * Подготвя всички политики до които има достъп потребителя
     * @param stdClass $rec - запис от модела
     * @return array $options - масив с опции
     */
    private function getDefaultPolicies($rec)
    {
    	$options = array();
    	$polQuery = price_Lists::getQuery();
    	while($polRec = $polQuery->fetch()){
    		if(price_Lists::haveRightFor('read')){
    			$options[$polRec->id] = price_Lists::getTitleById($polRec->id, FALSE);
    		}
    	}
    	
    	return $options;
    }
    
    
    /**
     * Обработка след изпращане на формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		if(!$form->rec->title){
    			$polRec = price_Lists::fetch($form->rec->policyId);
    			$policyName = price_Lists::getVerbal($polRec, 'title');
    			$form->rec->title = "{$mvc->singleTitle} \"{$policyName}\" {$form->rec->id}";
    		} 
    	}
    }
    
    
    /**
   	 * Обработка на Single изгледа
   	 */
   	static function on_AfterPrepareSingle($mvc, &$data)
    {
    	// Обработваме детайлите ако ги няма записани
    	if(!$data->rec->products){
    		$mvc->prepareDetails($data);
    		
    		if($data->rec->productGroups){
	    	 	$groupsArr = keylist::toArray($data->rec->productGroups);
    	    }
    		
    		$data->rec->products = $mvc->groupProductsByGroups($data->rec->details->recs, $groupsArr);
    	}
    	
    	$count = 0;
    	foreach ($data->rec->products as $groupId => &$products){
			$count += count($products);
    	}
    	
    	if(!Mode::is('printing')){
    		$Pager = cls::get('core_Pager', array('itemsPerPage' => $mvc->listItemsPerPage));
	    	$Pager->itemsCount = $count;
	    	$Pager->calc();
	    	$data->pager = $Pager;
    	}
    	
    	$mvc->prepareDetailRows($data);
    }
    
    
    /**
     * Изчислява оптималната десетична дължина насумите от ценоразпирса, така че те да сапдоравнени
     */
    private function prepareDetailRows(&$data)
    {
    	if(!count($data->rec->products)) return;
    	
    	$recs = $data->rec->products;
    	$data->rec->products = new stdClass();
    	$data->rec->products->recs = $recs;
    	
    	// Обръщаме данните във вербални
    	$count = 0;
    	foreach ($data->rec->products->recs as $groupId => &$products){
			foreach ($products as $index => &$pRec){
				$start = $data->pager->rangeStart;
    		 	$end = $data->pager->rangeEnd - 1;
    		 	if(empty($data->pager) || ($count >= $start && $count <= $end)){
    		 		if($pRec->eanCode) {
    		 			$data->showEan = TRUE;
    		 		}
    		 		$data->rec->products->rows[$groupId][$index] = $this->getVerbalDetail($pRec, $data);
    		 	} else {
    		 		unset($data->rec->products->rows[$groupId][$index]);
    		 		unset($data->rec->products->recs[$groupId][$index]);
    		 	}
    		 	$count++;
			}
    	}
    }
    
    
    /**
     *  Подготовка на детайлите
     */
    private function prepareDetails(&$data)
    {
    	// Подготвяме продуктите спрямо избраните групи
	    $this->prepareProducts($data);
	    
	    // Намираме цената на всички продукти
	    $this->calculateProductsPrice($data);
	    
	    if($data->rec->details->recs){
	    	usort($data->rec->details->recs, array($this, "sortResults"));
	    }
    }
    
    
    /**
     * Сортира масива първо по код после по сума (ако кодовете съвпадат)
     */
	function sortResults($a, $b) {
			 if($a->code == $b->code) return strcmp($b->priceM, $a->priceM);
			 
	         return strcmp($a->code, $b->code);
	}
	
	
    /**
     * Извличаме до кои продукти имаме достъп. Ако не сме посочили ограничение
     * на групите показваме всички продукти, ако има ограничение - само тези
     * които са в посочените групи
     */
    private function prepareProducts(&$data)
    {
    	$rec = &$data->rec;
    	
    	// Ако датата на ценоразписа е текущата, извличаме и текущото време
    	if($data->rec->date == dt::today()){
    		$data->rec->date = dt::now();
    	} else {
    		$data->rec->date .= ' 23:59:59';
    	}
    	
    	$customerProducts = price_GroupOfProducts::getAllProducts($data->rec->date); 
    	
    	if($customerProducts){
    		foreach($customerProducts as $id => $product){
    			$productRec = cat_Products::fetch($id);
    			if(!$productRec) continue;
		    	if($rec->productGroups){
		    		$aGroups = keylist::toArray($rec->productGroups);
		    		$pGroups = keylist::toArray($productRec->groups);
		    		$intersectArr = array_intersect($aGroups, $pGroups);
		    		if(!count($intersectArr)) continue;
		    	}
		    	
		    	$arr = cat_Products::fetchField($productRec->id, 'groups');
		    	($arr) ? $arr = keylist::toArray($arr) : $arr = array('0' => '0');
		    	
		    	$rec->details->products[$productRec->id] = (object)array(
		    								   'productId' => $productRec->id,
	    									   'code'      => $productRec->code,
	    									   'measureId' => $productRec->measureId,
		    								   'vat'       => cat_Products::getVat($productRec->id, $rec->date),
	    									   'pack'      => NULL,
	    									   'groups'    => $arr);
    		}
    	}
    }
    
    
    /**
     *  Извличане на цената на листваните продукти
     */
    private function calculateProductsPrice(&$data)
    {
    	$rec = &$data->rec;
    	$rec->currencyRate = currency_CurrencyRates::getRate($rec->date, $rec->currencyId, acc_Periods::getBaseCurrencyCode($rec->date));
    	$rec->listRec = price_Lists::fetch($rec->policyId);
    	
    	if(!count($rec->details->products)) return;
    	$packArr = keylist::toArray($rec->packagings);
    	
    	// Ако няма избрани опаковки, значи сме избрали всички
    	if(!count($packArr)){
    		$packArr = cat_Packagings::makeArray4Select('id');;
    	}
    	
    	foreach($rec->details->products as &$product){
    		
    		// Изчисляваме цената за продукта в основна мярка
    		$displayedPrice = price_ListRules::getPrice($rec->policyId, $product->productId, NULL, $rec->date, TRUE);
    		$vat = $this->Products->getVat($product->productId);
    		$displayedPrice = deals_Helper::getDisplayPrice($displayedPrice, $vat, $rec->currencyRate, $rec->vat);
    		if(!empty($rec->listRec->roundingPrecision)){
    			$displayedPrice = round($displayedPrice, $rec->listRec->roundingPrecision);
    		} else {
    			$displayedPrice = deals_Helper::roundPrice($displayedPrice);
    		}
    		
    		$product->priceM = $displayedPrice;
    		$productInfo = cat_Products::getProductInfo($product->productId);
    		
    		// Ако е пълен ценоразпис и има засичане на опаковките или е непълен и има опаковки
    		if ($productInfo && ($rec->showUoms == 'yes' && array_intersect_key($productInfo->packagings, $packArr) || ($rec->showUoms == 'no' && count($productInfo->packagings)))){
    			$count = 0;
    			
    			// За всяка опаковка
    			foreach ($productInfo->packagings as $pId => $packagingRec){
    				
    				// Ако текущата опаковка е избрана за показване в документа, или се показват всички
    				if(!count($packArr) || in_array($pId, $packArr)){
    					// Изчисляване на цената
    					$object = $this->calculateProductWithPack($rec, $product, $packagingRec);
    					
    					// Ако има цена
    					if($object){
    						
    						// Първата опаковка я добавяме към реда на основната мярка
    						if($count == 0){
				    			$exRec = &$product;
				    			$exRec->pack = $object->pack;
				    			$exRec->eanCode = $object->eanCode;
				    			$exRec->perPack = $object->perPack;
				    			$exRec->priceP = $object->priceP;
				    			$rec->details->recs[] = $exRec;
    						} else {
    							// Всички останали опаковки са на нов ред, без цена
    							unset($object->priceM);
			    				$rec->details->recs[] = $object;
			    			}
    					$count++;
    					}
    				}
    			}
    		} else {
    			// Ако продукта няма опаковки и се показват всички опаковки добавяме го
	    		if($rec->showUoms == 'yes' && $product->priceM){
	    			$rec->details->recs[] = $product;
	    		}
    		}
    	}
    	
    	unset($rec->details->products);
    }
    
    
    /**
     * Проверяване дали продукта поддържа избраната опаковка 
     * ако поддържа и изчислява цената, и ъпдейтва информацията
     * @param stdClass $rec - записа от модела
     * @param stdClass $product - информацията за продукта
     * @param int $packId - ид на опаковката
     * @return stdClass $clone - информация за продукта с опаковката
     */
    private function calculateProductWithPack($rec, $product, $packagingRec)
    {
    	$clone = clone $product;
    	$price = price_ListRules::getPrice($rec->policyId, $product->productId, $packagingRec->packagingId, $rec->date, TRUE);
    	if(!$price) return;
    	
    	$clone->priceP  = $packagingRec->quantity * $price;
    	$vat = $this->Products->getVat($product->productId);
    	$clone->priceP = deals_Helper::getDisplayPrice($clone->priceP, $vat, $rec->currencyRate, $rec->vat);
    	if(!empty($rec->listRec->roundingPrecision)){
    		$clone->priceP = round($clone->priceP, $rec->listRec->roundingPrecision);
    	} else {
    		$clone->priceP = deals_Helper::roundPrice($clone->priceP);
    	}
    	
    	$clone->perPack = $packagingRec->quantity;
    	$clone->eanCode = ($packagingRec->eanCode) ? $packagingRec->eanCode : NULL;
    	$clone->pack    = $packagingRec->packagingId;
    		
    	return $clone;
    }
    
    
    /**
     * Обръщане на детайла във вербален вид
     * 
     * @param stdClass $rec - запис на детайла
     * @param stdClass $masterRec - мастъра
     * @return stdClass $row - вербално представяне на детайла
     */
    private function getVerbalDetail($rec, $data)
    {
    	$masterRec = $data->rec;
    	$varchar = cls::get('type_Varchar');
    	$double = cls::get('type_Double');
    	$double->params['smartRound'] = 'smartRound';
    	
    	$row = new stdClass();
    	$row->productId = cat_Products::getVerbal(cat_Products::fetch($rec->productId), 'name');
    	
    	if(!Mode::is('printing')){
    		if(cat_Products::haveRightFor('single', $rec->productId)){
    			$icon = sbf("img/16/wooden-box.png");
    			$url = array('cat_Products', 'single', $rec->productId);
    			$row->productId = ht::createLink($row->productId, $url, NULL, "style=background-image:url({$icon}),class=linkWithIcon");
    		}
    	}
    	
    	foreach (array('priceP', 'priceM') as $priceFld) {
    		if($rec->$priceFld){
        		$row->$priceFld = $double->toVerbal($rec->$priceFld);
        	}
    	}
        
    	$measureShort = cat_UoM::getShortName($rec->measureId);
		if($rec->pack){
    		$row->pack = cat_Packagings::getTitleById($rec->pack);
    		$row->pack .= "&nbsp;({$double->toVerbal($rec->perPack)}&nbsp;{$measureShort})";
		}
    	
		$row->code = $varchar->toVerbal($rec->code);
		$row->eanCode = $varchar->toVerbal($rec->eanCode);
		
    	if($rec->measureId && $rec->priceM){
    		$row->measureId = $measureShort;
    	} else {
    		unset($row->productId);
    		unset($row->code);
    	}
    	
    	return $row;
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	$tplFile = ($data->rec->showUoms == 'yes') ? $this->singleLayoutFile : $this->singleLayoutFile2;
    	$tpl = getTplFromFile($tplFile);
    }
    
    
    /**
     * Вкарваме css файл за единичния изглед
     */
	static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$mvc->renderDetails($tpl, $data);
    	$tpl->push("price/tpl/NormStyles.css", "CSS");
    }
    
    
    /**
     * Подравняване на цените
     */
    private function alignPrices(&$data)
    {
    	// Обхождаме данните и намираме колко е максималния брой десетични знаци
    	$maxDecP = $maxDecM = 0;
	    foreach ($data->rec->products->recs as $groupId => $products1){
			foreach ($products1 as $index => $dRec){
				if($dRec->priceM){
					core_Math::roundNumber($dRec->priceM, $maxDecM);
				}
				
				if($dRec->priceP){
					core_Math::roundNumber($dRec->priceP, $maxDecP);
				}
			}
	    }
    	
    	// Подравняваме сумите да са с еднакъв брой цифри след десетичния знак
    	$Double = cls::get('type_Double');
    	
    	foreach ($data->rec->products->rows as $groupId => &$products2){
			foreach ($products2 as $index => &$row){
				$rec = $data->rec->products->recs[$groupId][$index];
				if($row->priceM){
					$Double->params['decimals'] = max(2, $maxDecM);
					$rec->priceM = core_Math::roundNumber($rec->priceM, $maxDecM);
					$row->priceM = $Double->toVerbal($rec->priceM);
				}
				
				if($row->priceP){
					$Double->params['decimals'] = max(2, $maxDecP);
					$rec->priceP = core_Math::roundNumber($rec->priceP, $maxDecP);
					$row->priceP = $Double->toVerbal($rec->priceP);
				}
			}
    	}
    }
    
    
	/**
     * Рендиране на "Детайлите" на ценоразписа
     */
    private function renderDetails(&$tpl, $data)
    {
    	$rec = &$data->rec;
    	$detailTpl = $tpl->getBlock("GROUP");
    	
    	if($rec->products->rows){
    		
    		$this->alignPrices($data);
    		
    		foreach ($rec->products->rows as $groupId => $products){
    			if(count($products) != 0){
					
					// Слагаме името на групата
					$groupTpl = clone $detailTpl;
					if($groupId){
						$groupTpl->replace(cat_Groups::getTitleById($groupId), 'GROUP_NAME');
					} else {
						$groupTpl->replace(tr('Без група'), 'GROUP_NAME');
					}
					
					foreach ($products as $row){
		    			$rowTpl = $groupTpl->getBlock('ROW');
			    		$rowTpl->placeObject($row);
			    		
			    		if($data->showEan){
			    			$rowTpl->replace(' ', 'eanCode');
			    		}
			    		
			    		$rowTpl->removeBlocks();
			    		$rowTpl->append2master();
			    	}
    				
    				$groupTpl->removeBlocks();
    				$tpl->append($groupTpl, 'GROUP');
    				
    				if($data->showEan){
    					$tpl->replace(' ', 'showEAN');
    				}
				}
    		}
    	} else {
    		$tpl->replace("<tr><td colspan='6'> " . tr("Няма продукти") . "</td></tr>", 'GROUP');
    	}
    	
    	if($data->pager){
    		$tpl->replace($data->pager->getHtml(), 'PAGER');
    	}
    }
    
    
    /**
     * Преподреждане на продуктите, групирани по групи
     *  	- Ако един продукт е в няколко групи, то 
     *  	  го групираме само към първата обходена
     *  	- Ако са посочени определени групи, то продукта
     *  	  го показваме в първата обходена група, която е
     *  	  посочена в масива с определените групи
     *  
     * @param array $array - Масив с информацията за продуктите
     * @param array $groupsArr - Кои групи ще се показват,
     * 							 NULL ако са всичките
     * @return array $grouped - масив с групираните продукти
     */
    private function groupProductsByGroups(&$array, $groupsArr)
    {
    	$grouped = array();
    	if(count($array)){
	    	foreach($array as $id => $product){
	    		foreach ($product->groups as $group){
	    			if($groupsArr){
	    				if(in_array($group, $groupsArr)){
	    					$firstGroup = $group;
	    					break;
	    				}
	    			} else {
	    				$firstGroup = $group;
	    				break;
	    			}
	    		}
	    		
	    		$grouped[$firstGroup][] = clone $product;
	    		unset($array[$id]);
	    	}
    	}
    	
    	return $grouped;
    }
    
    
    /**
     * При активиране записваме групираните продукти в модела
     */
	public static function on_AfterActivation($mvc, &$rec)
    {
    	$data = new stdClass();
    	$data->rec = $rec;
    	$mvc->prepareDetails($data);
    	
    	if($rec->productGroups){
	    	$groupsArr = keylist::toArray($data->rec->productGroups);
    	}
    	
    	$rec->products = $mvc->groupProductsByGroups($data->rec->details->recs, $groupsArr);
    	
    	$mvc->save($rec);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->policyId = ht::createLink($row->policyId, array('price_Lists', 'single', $rec->policyId));
    	
    	if(isset($fields['-list'])){
    		$row->handler = $mvc->getLink($rec->id, 0);
    	}
    	
    	if(!$rec->productGroups) {
    		$row->productGroups = tr("Всички");
    	}
    	
    	$row->vat = ($rec->vat == 'yes') ? tr('с начислен') : tr('без');
    	
    	// Модифицираме данните които показваме при принтиране
    	if(Mode::is('printing')){
    		$row->printHeader = $row->title;
    		$row->currency =  $row->currencyId;
    		$row->created =  $row->date;
    		$row->number =  $row->id;
    		unset($row->currencyId);
    		unset($row->productGroups);
    		unset($row->packagings);
    		unset($row->createdOn);
    		unset($row->createdBy);
    		unset($row->policyId);
    		unset($row->date);
    	}
    	
        if ($fields['-single']) {
            $row->singleTitle = $row->title;
        }
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = $this->recToVerbal($rec, 'title')->title;
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
		$row->recTitle = $row->title;
        
        return $row;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    static function getHandle($id)
    {
    	$rec = static::fetch($id);
    	$self = cls::get(get_called_class());
    	
    	return $self->abbr . $rec->id;
    }
    
    
	/**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашия ценоразпис:") . '#[#handle#]');
        $tpl->append($handle, 'handle');
        return $tpl->getContent();
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     *
     * @param $threadId int ид на нишката
     */
    public static function canAddToThread($threadId)
    {
        // Добавяме тези документи само в персонални папки
        $threadRec = doc_Threads::fetch($threadId);

        return self::canAddToFolder($threadRec->folderId);
    }

    
	/**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('doc_ContragentDataIntf', 'price_PriceListFolderCoverIntf');
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
    	$allowedIntfs = static::getAllowedFolders();
    	$cover = doc_Folders::getCover($folderId);
    	foreach ($allowedIntfs as $intf){
    		if($cover->haveInterface($intf)){
    			return TRUE;
    		}
    	}
    	return FALSE;
    }
}
