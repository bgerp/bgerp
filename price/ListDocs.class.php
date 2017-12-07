<?php



/**
 * Документ "Ценоразпис"
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Документ "Ценоразпис"
 */
class price_ListDocs extends core_Master
{
    
	
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf';


    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = TRUE;
    
    
    /**
     * Заглавие
     */
    public $title = 'Ценоразписи';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Cnr";
    
    
     /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, price_Wrapper, plg_Clone, doc_DocumentPlg, doc_EmailCreatePlg,
    	 plg_Printing, bgerp_plg_Blank, plg_Sorting, plg_Search, doc_ActivatePlg, doc_plg_SelectFolder';
    	
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'title, policyId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'date, handler=Документ, title, policyId, folderId, state, createdOn, createdBy';
    
    
    /**
     * Полето за единичен изглед
     */
    public $rowToolsSingleField = 'handler';
    
    
    /**
     * Кой може да го промени?
     */
    public $canWrite = 'sales, priceDealer, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'sales, priceDealer, ceo';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'sales, priceDealer, ceo';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "3.6|Търговия";
    
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Ценоразпис';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 30;
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'price/tpl/templates/ListDoc.shtml';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile2 = 'price/tpl/templates/ListDocWithoutUom.shtml';
    
    
    /**
     * Работен кеш
     */
    public $cache = array();
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'crm_ContragentAccRegIntf,doc_UnsortedFolders';

    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'date';
    
    
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
    
    	$this->FLD('round', 'int', 'caption=Закръгляне на цена->В мярка');
    	$this->FLD('roundPack', 'int', 'caption=Закръгляне на цена->В опаковка');
    }
    
    
    /**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
    	$data->listFilter->showFields = 'search';
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->input();
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    }
    
    
    /**
     * Извиква се след подготовката на формата
     */
	protected static function on_AfterPrepareEditForm($mvc, &$data)
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
    	 	
    	 	$currencyCode = $folderClass::getDefaultCurrencyId($coverId);
    	 	$currencyId = currency_Currencies::getIdByCode($currencyCode);
    	 }
    	 
    	 return ($currencyId) ? $currencyId : acc_Periods::getBaseCurrencyCode($rec->date);
    }
    
    
    /**
     * Подготвя всички политики до които има достъп потребителя
     * 
     * @param stdClass $rec - запис от модела
     * @return array $options - масив с опции
     */
    private function getDefaultPolicies($rec)
    {
    	$options = array();
    	$polQuery = price_Lists::getQuery();
    	$polQuery->show('title');
    	while($polRec = $polQuery->fetch()){
    		$options[$polRec->id] = price_Lists::getTitleById($polRec->id, FALSE);
    	}
    	
    	if(!haveRole('ceo,priceDealer')){
    		unset($options[price_ListRules::PRICE_LIST_COST]);
    	}
    	
    	return $options;
    }
    
    
    /**
     * Обработка след изпращане на формата
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
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
   	protected static function on_AfterPrepareSingle($mvc, &$data)
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
    	
    	if(!Mode::is('printing') && !Mode::is('text', 'xhtml') && !Mode::is('pdf')){
    		$Pager = cls::get('core_Pager', array('itemsPerPage' => $mvc->listItemsPerPage));
    		$Pager->setPageVar($mvc->className, $data->rec->id);
	    	$Pager->itemsCount = $count;
	    	$Pager->calc();
	    	$data->pager = $Pager;
    	} else {
    		
    		// Дигаме времето за изпълнение, ако показваме записите без странициране
    		$total = count($data->rec->products, COUNT_RECURSIVE);
    		$timeLimit = $total * 0.12;
    		core_App::setTimeLimit($timeLimit);
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
	private function sortResults($a, $b) {
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
    	
    	$customerProducts = price_ListRules::getProductOptions($data->rec->policyId);
    	unset($customerProducts['pu']);
    	
    	$aGroups = cat_Groups::getDescendantArray($rec->productGroups);
    	
    	if($customerProducts){
    		foreach($customerProducts as $id => $product){
    			$productRec = cat_Products::fetch($id);
    			if(!$productRec) continue;
    			
		    	if($rec->productGroups){
		    		$pGroups = keylist::toArray($productRec->groups);
		    		$intersectArr = array_intersect($aGroups, $pGroups);
		    		if(!count($intersectArr)) continue;
		    	}
		    	
		    	$arr = cat_Products::fetchField($productRec->id, 'groups');
		    	$arr = cat_Groups::getParentsArray($arr);
		    	(count($arr)) ? $arr = keylist::toArray($arr) : $arr = array('0' => '0');
		    	
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
    	currency_CurrencyRates::checkRateAndRedirect($rec->currencyRate);
    	
    	$rec->listRec = price_Lists::fetch($rec->policyId);
    	
    	if(!count($rec->details->products)) return;
    	$packArr = keylist::toArray($rec->packagings);
    	
    	// Ако няма избрани опаковки, значи сме избрали всички
    	if(!count($packArr)){
    		$packs = cat_UoM::getPackagingOptions();
    		$packArr = array_combine(array_keys($packs), array_keys($packs));
    	}
    	
    	foreach($rec->details->products as &$product){
    		
    		// Изчисляваме цената за продукта в основна мярка
    		$displayedPrice = price_ListRules::getPrice($rec->policyId, $product->productId, NULL, $rec->date);
    		$vat = cat_Products::getVat($product->productId);
    		$displayedPrice = deals_Helper::getDisplayPrice($displayedPrice, $vat, $rec->currencyRate, $rec->vat);
    		
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
    	$price = price_ListRules::getPrice($rec->policyId, $product->productId, $packagingRec->packagingId, $rec->date);
    	if(!$price) return;
    	
    	$clone->priceP  = $packagingRec->quantity * $price;
    	$vat = cat_Products::getVat($product->productId);
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
    	$Varchar = cls::get('type_Varchar');
    	$Double = cls::get('type_Double');
    	$Double->params['smartRound'] = 'smartRound';
    	
    	$row = new stdClass();
    	$row->productId = cat_Products::getShortHyperlink($rec->productId);
    	
    	foreach (array('priceP', 'priceM') as $priceFld) {
    		if($rec->{$priceFld}){
        		$row->{$priceFld} = $Double->toVerbal($rec->{$priceFld});
        	}
    	}
        
    	if(!array_key_exists($rec->measureId, $this->cache)){
    		$this->cache[$rec->measureId] = tr(cat_UoM::getShortName($rec->measureId));
    	}
    	$measureShort = $this->cache[$rec->measureId];
    	
		if($rec->pack){
			if(!array_key_exists($rec->pack, $this->cache)){
				$this->cache[$rec->pack] = cat_UoM::getShortName($rec->pack);
			}
			
    		$row->pack = $this->cache[$rec->pack];
    		$row->pack .= deals_Helper::getPackMeasure($rec->measureId, $rec->perPack);
		}
    	
		$row->code = $Varchar->toVerbal($rec->code);
		if(isset($rec->eanCode)){
			$row->eanCode = $Varchar->toVerbal($rec->eanCode);
			$row->eanCode = "<small>{$row->eanCode}</small>";
		}
		
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
    protected static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	$tplFile = ($data->rec->showUoms == 'yes') ? $mvc->singleLayoutFile : $mvc->singleLayoutFile2;
    	$tpl = getTplFromFile($tplFile);
    }
    
    
    /**
     * Вкарваме css файл за единичния изглед
     */
	protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$mvc->renderDetails($tpl, $data);
    	$tpl->push("price/tpl/NormStyles.css", "CSS");
    }
    
    
    /**
     * Подравняване на цените
     */
    private function alignPrices(&$data)
    {
    	$Double = cls::get('type_Double');
    	
    	$roundM = $data->rec->round;
    	$roundP = $data->rec->roundPack;
    	
    	foreach ($data->rec->products->rows as $groupId => &$products2){
			foreach ($products2 as $index => &$row){
				$rec = $data->rec->products->recs[$groupId][$index];
				
				if($row->priceM){
					$round = strlen(substr(strrchr($rec->priceM, "."), 1));
					$Double->params['decimals'] = (isset($roundM)) ? $roundM : (($round < 2) ? 2 : $round);
					$row->priceM = $Double->toVerbal($rec->priceM);
				}
				
				if($row->priceP){
					$round = strlen(substr(strrchr($rec->priceP, "."), 1));
					$Double->params['decimals'] = (isset($roundP)) ? $roundP : (($round < 2) ? 2 : $round);
					$row->priceP = $Double->toVerbal($rec->priceP);
				}
			}
    	}
    	
    	$recs = $data->rec->products->recs;
    	$rows = &$data->rec->products->rows;
    	
    	$recs1 = $rows1 = array();
    	
    	foreach ($recs as $id => $el){
    		if(is_array($el)){
    			foreach ($el as $id1 => $r1){
    				$index = "$id|$id1";
    				$recs1[$index] = $r1;
    				$rows1[$index] = &$rows[$id][$id1];
    			}
    		}
    	}
    	
    	$fieldset = cls::get('core_FieldSet');
    	if(!isset($roundM)){
    		$fieldset->FLD('priceM', 'double(decimals=6)');
    	}
    	
    	if(!isset($roundP)){
    		$fieldset->FLD('priceP', 'double(decimals=6)');
    	}
    	
    	plg_AlignDecimals2::alignDecimals($fieldset, $recs1, $rows1);
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
						$groupTpl->replace(cat_Groups::getVerbal($groupId, 'name'), 'GROUP_NAME');
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
    		$tpl->replace("<tr><td colspan='6'> " . tr("Няма артикули") . "</td></tr>", 'GROUP');
    	}
    	
    	if($data->pager){
    		$tpl->replace($data->pager->getHtml(), 'PAGER_TOP');
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
	protected static function on_AfterActivation($mvc, &$rec)
    {
    	$data = new stdClass();
    	$data->rec = $rec;
    	
    	Mode::push("cacheList{$rec->id}", TRUE);
    	$mvc->prepareDetails($data);
    	Mode::pop("cacheList{$rec->id}");
    	
    	if($rec->productGroups){
	    	$groupsArr = keylist::toArray($data->rec->productGroups);
    	}
    	
    	$rec->products = $mvc->groupProductsByGroups($data->rec->details->recs, $groupsArr);
    	
    	$mvc->save($rec);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->policyId = price_Lists::getHyperlink($rec->policyId, TRUE);
    	
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
    public static function getHandle($id)
    {
    	$rec = static::fetch($id);
    	$self = cls::get(get_called_class());
    	
    	return $self->abbr . $rec->id;
    }
    
    
	/**
     * Връща тялото на имейла генериран от документа
     * 
     * @see email_DocumentIntf
     * @param int $id - ид на документа
     * @param boolean $forward
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = FALSE)
    {
        $handle = $this->getHandle($id);
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
}
