<?php



/**
 * Документ "Ценоразпис"
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
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
    	 plg_Printing, bgerp_plg_Blank, plg_Sorting, plg_Search, doc_ActivatePlg, doc_plg_BusinessDoc2';
    	
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'title';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, title, date, policyId, state, createdOn, createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Полето за единичен изглед
     */
    var $rowToolsSingleField = 'title';
    
    
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
     * Икона на единичния обект
     */
    //var $singleIcon = 'img/16/legend.png';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "3.6|Търговия";
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'price/tpl/SingleLayoutListDoc.shtml';
    
    
    /**
     * Заглавие
     */
    var $singleTitle = 'Ценоразпис';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('date', 'date(smartTime)', 'caption=Дата,mandatory,width=6em;');
    	$this->FLD('policyId', 'key(mvc=price_Lists, select=title)', 'caption=Политика, silent, mandotory,width=15em');
    	$this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута,width=8em,input');
    	$this->FLD('vat', 'enum(yes=с ДДС,no=без ДДС)','caption=ДДС');
    	$this->FLD('title', 'varchar(155)', 'caption=Заглавие,width=15em');
    	$this->FLD('productGroups', 'keylist(mvc=cat_Groups,select=name, translate)', 'caption=Продукти->Групи,columns=2');
    	$this->FLD('packagings', 'keylist(mvc=cat_Packagings,select=name)', 'caption=Продукти->Опаковки,columns=3');
    	$this->FLD('products', 'blob(serialize,compress)', 'caption=Данни,input=none');
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
    			$options[$polRec->id] = price_Lists::getTitleById($polRec->id);
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
    			$form->rec->title = "{$mvc->singleTitle} \'{$policyName}\' {$form->rec->id}";
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
    }
    
    
    /**
     * Извличаме до кои продукти имаме достъп. Ако не сме посочили ограничение
     * на групите показваме всички продукти, ако има ограничение - само тези
     * които са в посочените групи
     */
    private function prepareProducts(&$data)
    {
    	$rec = &$data->rec;
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
		    	
		    	$rec->details->products[$productRec->id] = (object)array('productId' => $productRec->id,
	    									   'code' => $productRec->code,
	    									   'eanCode' => $productRec->eanCode,
	    									   'measureId' => $productRec->measureId,
		    								   'vat' => cat_Products::getVat($productRec->id, $rec->date),
	    									   'pack' => NULL,
	    									   'groups' => $arr);
    		}
    	}
    }
    
    
    /**
     *  Извличане на цената на листваните продукти
     */
    private function calculateProductsPrice(&$data)
    {
    	$rec = &$data->rec;
    	if(!count($rec->details->products)) return;
    	$packArr = keylist::toArray($rec->packagings);
    	
    	foreach($rec->details->products as &$product){
    		
    		// Изчисляваме цената за продукта в основна мярка
    		$product->price = price_ListRules::getPrice($rec->policyId, $product->productId, NULL, $rec->date);
    		
    		if( $product->price) {
    			$rec->details->rows[] = $product;
    		}
    		
    		// За всяка от избраните опаковки
    		foreach($packArr as $packId){
    			$object = $this->calculateProductWithPack($rec, $product, $packId);
    			if($object) {
    				$rec->details->rows[] = $object;
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
    private function calculateProductWithPack($rec, $product, $packId)
    {
    	if($info = cat_Products::getProductInfo($product->productId, $packId)){
    		$clone = clone $product;
    		$price = price_ListRules::getPrice($rec->policyId, $product->productId, $packId, $rec->date);
    		if(!$price) return;
    		
    		$clone->price = $info->packagingRec->quantity * $price;
    		$clone->perPack = $info->packagingRec->quantity;
    		$clone->eanCode = $info->packagingRec->eanCode;
    		$clone->code = $info->packagingRec->customCode;
    		$clone->pack = $packId;
    		
    		return $clone;
    	}
    	return FALSE;
    }
    
    
    /**
     * Обръщане на детайла във вербален вид
     * @param stdClass $rec - запис на детайла
     * @return stdClass $row - вербално представяне на детайла
     */
    private function getVerbalDetail($rec, $masterRec)
    {
    	$varchar = cls::get('type_Varchar');
    	$double = cls::get('type_Double');
    	$double->params['decimals'] = 2;
    	
    	$row = new stdClass();
    	$row->productId = cat_Products::getTitleById($rec->productId);
    	
    	if(!Mode::is('printing')){
	    	$icon = sbf("img/16/wooden-box.png");
	    	$url = array('cat_Products', 'single', $rec->productId);
			$row->productId = ht::createLink($row->productId, $url, NULL, "style=background-image:url({$icon}),class=linkWithIcon");
    	}
    	
		if($rec->pack){
    		$row->pack = cat_Packagings::getTitleById($rec->pack);
    		$measureShort = cat_UoM::getShortName($rec->measureId);
    		$row->pack .= " &nbsp;({$rec->perPack} {$measureShort})";
		} else {
    		$row->measureId = cat_UoM::getTitleById($rec->measureId);
    	}
    	
    	if($rec->price) {
    		$vat = ($masterRec->vat == 'yes') ? $rec->vat : 0;
    		$price = $rec->price * (1 + $vat);
    		$rec->price = currency_CurrencyRates::convertAmount($price, $masterRec->date, NULL, $masterRec->currencyId);
        }
        
    	$row->price = $double->toVerbal($rec->price);
    	$row->code = $varchar->toVerbal($rec->code);
    	$row->eanCode = $varchar->toVerbal($rec->eanCode);
    	
    	return $row;
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
     * Рендиране на "Детайлите" на ценоразписа
     */
    private function renderDetails(&$tpl, $data)
    {
    	$rec = &$data->rec;
    	$detailTpl = $tpl->getBlock("GROUP");
    	
    	if($rec->details->rows || $rec->products){
    		
    	   if($rec->productGroups){
	    		$groupsArr = keylist::toArray($rec->productGroups);
    		}
    		
    		// Преподреждаме продуктите групирани по Групи
    		if(!$rec->products){
    			$rec->products = $this->groupProductsByGroups($rec->details->rows, $groupsArr);
    		}
    		
    		krsort($rec->products);
    		foreach ($rec->products as $groupId => $products){
    			if(count($products) != 0){
					
					// Слагаме името на групата
					$groupTpl = clone $detailTpl;
					if($groupId){
						$groupTpl->replace(cat_Groups::getTitleById($groupId), 'GROUP_NAME');
					} else {
						$groupTpl->replace(tr('Без група'), 'GROUP_NAME');
					}
					
					foreach ($products as $row){
		    			$row = $this->getVerbalDetail($row, $rec);
		    			$rowTpl = $groupTpl->getBlock('ROW');
			    		$rowTpl->placeObject($row);
			    		$rowTpl->removeBlocks();
			    		$rowTpl->append2master();
			    	}
    				
    				$groupTpl->removeBlocks();
			    	$tpl->append($groupTpl, 'GROUP');
				}
    		}
    	} else {
    		$tpl->replace("<tr><td colspan='5'> " . tr("Няма цени") . "</td></tr>", 'GROUP');
    	}
    }
    
    
    /**
     * Преподреждане на продуктите, групирани по групи
     *  	- Ако един продукт е в няколко групи, то 
     *  	  го групираме само към първата обходена
     *  	- Ако са посочени определени групи, то продукта
     *  	  го показваме в първата обходена група, която е
     *  	  посочена в масива с определените групи
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
    	
    	$rec->products = $mvc->groupProductsByGroups($data->rec->details->rows, $groupsArr);
    	
    	$mvc->save($rec);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->header = "{$row->title} &nbsp;<b>{$row->ident}</b> ({$row->state})";
    	$row->policyId = ht::createLink($row->policyId, array('price_Lists', 'single', $rec->policyId));
    	
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
    		unset($row->header);
    		unset($row->currencyId);
    		unset($row->productGroups);
    		unset($row->packagings);
    		unset($row->createdOn);
    		unset($row->createdBy);
    		unset($row->policyId);
    		unset($row->date);
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
