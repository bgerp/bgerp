<?php



/**
 * Мениджър за "Детайли на разходните норми" 
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class price_ConsumptionNormDetails extends core_Detail {
    
    
    /**
     * Заглавие
     */
    var $title = 'Детайли на разходните норми';
    
    
    /**
	 * Мастър ключ към дъските
	 */
	var $masterKey = 'normId';
    
    
	/**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Sorting, price_Wrapper';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от 
     * таблицата.
     */
    var $rowToolsField = 'tools';
    
    
	/**
	 * Полета за изглед
	 */
	var $listFields = 'tools=Пулт, id, dProductId, dUom, quantity';
	
	
    /**
     * Кой може да променя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой може да променя?
     */
    var $canList = 'no_one';
    
	
    /**
	 * Брой детайли на страница
	 */
	var $listItemsPerPage = '25';
	
	
  	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('normId', 'key(mvc=price_ConsumptionNorms)', 'caption=Норма, input=hidden, silent');
    	$this->FLD('dProductId', 'key(mvc=cat_Products, select=name)', 'caption=Продукт');
    	$this->FLD('dUom', 'key(mvc=cat_UoM, select=name, allowEmpty)', 'caption=Мярка');
    	$this->FLD('quantity', 'int', 'caption=Количество,mandatory');
    }
    
    
	/**
     * Преди извличане на записите от БД
     */
    public static function on_AfterPrepareListRows($mvc, &$res, $data)
    {
    	if($data->rows){
	    	foreach($data->rows as $row){
	    		$arr = cat_Products::fetchField($data->recs[$row->id]->dProductId, 'groups');
	    		$row->groups = keylist::toArray($arr);
	    	}
    	}
    }
    
    
    /**
     * Рендиране на детайлите
     */
    public function renderDetail_($data)
    {
        $tpl = new ET("");
    	$tplDetail = new ET(tr('|*' . getFileContent('price/tpl/ConsumptionNormDetails.shtml')));
    	$groups = price_ConsumptionNorms::$ingredientProductGroups;
    	foreach($groups as $gr){
    		$grRec = cat_Groups::fetch("#sysId = '{$gr}'");
		    if(!$data->rows) break;
    		$ingredients = array_filter($data->rows, function(&$val) use ($grRec) {
    													if($res = in_array($grRec->id, $val->groups)){
									    					unset($val);
									    				}
									    				return $res;});
    		if(count($ingredients) != 0){
    			$cloneTpl = clone $tplDetail;
    			$cloneTpl->replace($grRec->name, 'Cat');
    			foreach ($ingredients as $ing){
    				$rowTpl = $cloneTpl->getBlock('ROW');
    				$rowTpl->placeObject($ing);
    				$rowTpl->removeBlocks();
    				$rowTpl->append2master();
    			}
    			$cloneTpl->removeBlocks();
    			$tpl->append($cloneTpl);
    		}
    	}
    	$tpl->append(new ET("[#ListToolbar#]"));
    	$tpl->replace($this->renderListToolbar($data), 'ListToolbar');
    	$tpl->push('price/tpl/NormStyles.css', 'CSS');
    	
    	return $tpl;	
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	// Филтрираме продуктите така че да немогат да се добавят
    	// продукти които използват вече текущия продукт, както
    	// и продукти които са вече част от нормата
    	$data->form->setOptions('dProductId', $mvc->Master->getAllowedProducts($data->form->rec->normId, $data->form->rec->id));
    	$productName = cat_Products::getTitleById($data->masterRec->productId);
    	($data->form->rec->id) ? $title = "Редактиране на съставка в норма" : $title = "Добавяне на съставка в норма";
    	$data->form->title = tr($title) . " |*\"{$productName}\"";
    }
    
    
 	/**
     * Обработка след изпращане на формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()) {
    		$rec = &$form->rec;
    		$productUom = cat_Products::fetchField($rec->dProductId, 'measureId');
    		if($rec->dUom) {
    			
    			// Проверяваме дали мярката е от позволените за продукта
    			$similarMeasures = cat_UoM::getSameTypeMeasures($productUom);
    			if(!array_key_exists($rec->dUom, $similarMeasures)){
    				$form->setError('dUom', "Избраната мярка не е от същата група като основната мярка на продукта (" . cat_Uom::getTitleById($productUom) . ')');
    			}
    		} else {
    			
    			// Ако няма мярка приемаме че е основната на продукта
    			$rec->dUom = $productUom;
    		}
    	}
    }

    
   /**
    * Помощна функция която записва в един масив всички
    * продукти които са част от дървото на нормата
    * @param int $productId - id на продукта
    * @param array $children - масив събиращ децата
    * @param boolean $root - дали poductId е корена на дървото
    */
   public function getChildren($productId, &$children, $root = FALSE)
   {
    	if(!array_key_exists($productId, $children) && !$root){
    		$children[$productId] = $productId;
    	}
    	$ingredients = price_ConsumptionNorms::getIngredients($productId);
    	if($ingredients){
    		foreach($ingredients as $ing){
    			$res = $this->getChildren($ing->productId, $children);
	    	}
    	}
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($recipeRec = price_ConsumptionNorms::fetchByProduct($rec->dProductId)){
    		$icon = sbf("img/16/legend.png");
    		$row->dProductId = ht::createLink($row->dProductId, array('price_ConsumptionNorms', 'single', $recipeRec->id), NULL, "style=background-image:url({$icon}),class=linkWithIcon");
    	} else {
    		$icon = sbf("img/16/wooden-box.png");
			$row->dProductId = ht::createLink($row->dProductId, array('cat_Products', 'single', $rec->dProductId), NULL, "style=background-image:url({$icon}),class=linkWithIcon");
    	}
    }
    
    
 	/**
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{ 
		if($action == 'add' && isset($rec->normId)){
			$masterRec = $mvc->Master->fetch($rec->normId);
			if($masterRec->state == 'draft'){
				$res = 'price, ceo';
			}
		}
	}
}