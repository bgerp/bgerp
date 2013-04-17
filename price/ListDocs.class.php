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
    var $interfaces = 'doc_DocumentIntf';
    
    
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
    var $loadList = 'plg_RowTools, price_Wrapper, doc_DocumentPlg,
    	 plg_Printing, bgerp_plg_Blank, plg_Sorting, plg_Search, doc_ActivatePlg';
                    
    
    /**
	 * Брой дeтайли на страница
	 */
	var $listDetailsPerPage = '60';
    
    
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
    var $canRead = 'user';
    
    
    /**
     * Кой може да го промени?
     */
    var $canWrite = 'price, ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'price, ceo';
    
    
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
    	$this->FLD('title', 'varchar(155)', 'caption=Заглавие,width=15em');
    	$this->FLD('productGroups', 'keylist(mvc=cat_Groups,select=name)', 'caption=Продукти->Групи,columns=2');
    	$this->FLD('packagings', 'keylist(mvc=cat_Packagings,select=name)', 'caption=Продукти->Опаковки,columns=3');
    }
    
    
    /**
     * Извиква се след подготовката на формата
     */
	public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$form->setDefault('date', dt::now());
    	$form->setOptions('policyId', $mvc->getDefaultPolicies($form->rec));
    	$folderClass = doc_Folders::fetchCoverClassName($form->rec->folderId);
    	if($folderClass == 'crm_Companies' || $folderClass == 'crm_Persons'){
    		
    		// Ако корицата е Фирма или Лице, намираме 
    		// ценовата и политика и се слага по-подразбиране
    		$contragentId = doc_Folders::fetchCoverId($form->rec->folderId);
    		$defaultList = price_ListToCustomers::getListForCustomer($folderClass::getClassId(), $contragentId);
    		$form->setDefault('policyId', $defaultList);
    	}
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
    			$form->rec->title = "{$mvc->singleTitle} \"{$policyName}\"";
    		}
    	}
    }
    
    
    /**
   	 * Обработка на Single изгледа
   	 */
   	static function on_AfterPrepareSingle($mvc, &$data)
    {
    	$mvc->prepareDetails($data);
    }
    
    
    /**
     * Подготвяне на "Детайлите" на ценоразписа
     */
    private function prepareDetails(&$data)
    {
    	// Подготвяме продуктите спрямо избраните групи
    	$this->prepareSelectedProducts($data);
    	
    	// Намираме цената на всички продукти
    	$this->calculateProductsPrice($data);
    }
    
    
    /**
     * Извличаме до кои продукти имаме достъп. Ако не сме посочили ограничение
     * на групите показваме всички продукти, ако има ограничение - само тези
     * които са в посочените групи
     */
    private function prepareSelectedProducts(&$data)
    {
    	$rec = &$data->rec;
    	$customerProducts = price_GroupOfProducts::getAllProducts($data->rec->date); 
    	
    	if($customerProducts){
    		foreach($customerProducts as $id => $product){
    			$productRec = cat_Products::fetch($id);
		    	if($rec->productGroups){
		    		$aGroups = type_Keylist::toArray($rec->productGroups);
		    		$pGroups = type_Keylist::toArray($productRec->groups);
		    		$intersectArr = array_intersect($aGroups, $pGroups);
		    		if(!count($intersectArr)) continue;
		    	}
	    		
		    	$arr = cat_Products::fetchField($productRec->id, 'groups');
	    		$rec->details->products[$productRec->id] = (object)array('productId' => $productRec->id,
	    									   'code' => $productRec->code,
	    									   'eanCode' => $productRec->eanCode,
	    									   'measureId' => $productRec->measureId,
	    									   'pack' => NULL,
	    									   'groups' => type_Keylist::toArray($arr));
    		}
    	}
    }
    
    
    /**
     *  Извличаме цената на листваните продукти
     */
    private function calculateProductsPrice(&$data)
    {
    	$rec = &$data->rec;
    	if(!count($rec->details->products)) return;
    	$packArr = type_Keylist::toArray($rec->packagings);
    	
    	foreach($rec->details->products as &$product){
    		
    		// Изчисляваме цената за продукта в основна мярка
    		$product->price = price_ListRules::getPrice($rec->policyId, $product->productId, NULL, $rec->date);
	    	$rec->details->rows[] = $product;
    		
    		// За всяка от избраните опаковки
    		foreach($packArr as $pack){
    			
    			// Проверяваме продукта поддържали избраната
    			// опаковка ако поддържа и изчислява цената
    			if($pInfo = cat_Products::getProductInfo($product->productId, $pack)){
    				$clone = clone $product;
    				$price = price_ListRules::getPrice($rec->policyId, $product->productId, $pack, $rec->date);
    				$clone->price = $pInfo->packagingRec->quantity * $price;
    				$clone->pack = $pack;
    				$clone->perPack = $pInfo->packagingRec->quantity;
    				$clone->eanCode = $pInfo->packagingRec->eanCode;
    				$clone->code = $pInfo->packagingRec->customCode;
    				$rec->details->rows[] = $clone;
    			}
    		}
    	}
    	
    	$rec->details->Pager = cls::get('core_Pager', array('itemsPerPage' => $this->listDetailsPerPage));
    	$rec->details->Pager->itemsCount = count($rec->details->rows);
    	if(Mode::is('printing')){
    		$rec->details->Pager->itemsPerPage = 10000;
    	}
    	$rec->details->Pager->calc();
    	unset($rec->details->products);
    }
    
    
    /**
     * Обръщане на детаила във вербален вид
     * @param stdClass $detailRec - запис на детайла
     * @return stdClass $detailRow - вербално представяне на детайла
     */
    private function getVerbalDetail($detailRec)
    {
    	$varchar = cls::get('type_Varchar');
    	$double = cls::get('type_Double');
    	$double->params['decimals'] = 2;
    	
    	$detailRow = new stdClass();
    	$detailRow->productId = cat_Products::getTitleById($detailRec->productId);
    	
    	if(!Mode::is('printing')){
	    	$icon = sbf("img/16/package-icon.png");
	    	$url = array('cat_Products', 'single', $detailRec->productId);
			$detailRow->productId = ht::createLink($detailRow->productId, $url, NULL, "style=background-image:url({$icon}),class=linkWithIcon");
    	}
    	
		if($detailRec->pack){
    		$detailRow->pack = cat_Packagings::getTitleById($detailRec->pack);
    		$measureShort = cat_UoM::fetchField($detailRec->measureId, 'shortName');
    		$detailRow->pack .= " &nbsp;({$detailRec->perPack}/{$measureShort})";
		} else {
    		$detailRow->measureId = cat_UoM::getTitleById($detailRec->measureId);
    	}
    	
    	$detailRow->price = $double->toVerbal($detailRec->price);
    	$detailRow->code = $varchar->toVerbal($detailRec->code);
    	$detailRow->eanCode = $varchar->toVerbal($detailRec->eanCode);
    	
    	return $detailRow;
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
    	$detailTpl = $tpl->getBlock("DETAIL");
    	$count = 0;
    	if($rec->details->rows){
    		
    		if($rec->productGroups){
	    		$groupsArr = type_Keylist::toArray($rec->productGroups);
    		}
    		
    		// Начало и край на пейджъра
    		$start = $rec->details->Pager->rangeStart;
    		$end = $rec->details->Pager->rangeEnd - 1;
    		
    		// Преподреждаме продуктите групирани по Групи
    		$grouped = $this->groupProductsByGroups($rec->details->rows, $groupsArr);
    		foreach ($grouped as $groupId => $products){
    			if(count($products) != 0){
					
					// Слагаме името на групата
					$groupTpl = clone $detailTpl;
					$groupTpl->replace(cat_Groups::getTitleById($groupId), 'GROUP');
					foreach ($products as $row){
						
						// Показваме информацията с продукта
		    			$row = $this->getVerbalDetail($row);
		    			if($count >= $start && $count <= $end){
			    			$rowTpl = $groupTpl->getBlock('ROW');
			    			$rowTpl->placeObject($row);
			    			$rowTpl->removeBlocks();
			    			$rowTpl->append2master();
			    		}
		    			$count++;
    				}
    				
    				$groupTpl->removeBlocks();
			    	$tpl->append($groupTpl, 'DETAIL');
				}
    		}
    	} else {
    		$tpl->append("<tr><td colspan='6'> " . tr("Няма цени") . "</td></tr>", 'ROW');
    	}
    	
    	if($rec->details->Pager){
    		$tpl->append($rec->details->Pager->getHtml(), 'BottomPager');
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
    	
    	return $grouped;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->header = "{$row->title} &nbsp;<b>{$row->ident}</b> ({$row->state})";
    	$row->baseCurrency = acc_Periods::getBaseCurrencyCode($rec->date);
    	
    	if(!$rec->productGroups) {
    		$row->productGroups = tr("Всички");
    	}
    	
    	// Модифицираме данните които показваме при принтиране
    	if(Mode::is('printing')){
    		$row->printHeader = tr($row->title);
    		$row->currency =  $row->baseCurrency;
    		$row->created =  $row->date;
    		$row->number =  $row->id;
    		unset($row->header);
    		unset($row->baseCurrency);
    		unset($row->productGroups);
    		unset($row->packagings);
    		unset($row->createdOn);
    		unset($row->createdBy);
    		unset($row->policyId);
    		unset($row->date);
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
        if($action == 'activate' && !$rec->id) {
        	$res ='no_one';
        }
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = $rec->title;
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;

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
}