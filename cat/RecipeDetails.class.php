<?php



/**
 * Мениджър за "Детайли на рецептите" 
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class cat_RecipeDetails extends core_Detail {
    
    
    /**
     * Заглавие
     */
    var $title = 'Детайли на рецептите';
    
    
    /**
	 * Мастър ключ към дъските
	 */
	var $masterKey = 'recipeId';
    
    
	/**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools';
    
    
     /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от 
     * таблицата.
     */
    var $rowToolsField = 'tools';
    
    
	/**
	 * Полета за изглед
	 */
	var $listFields = 'tools=Пулт, dProductId, dUom, quantity';
	
	
    /**
     * Кой може да променя?
     */
    var $canAdd = 'cat, admin';
    
    
    /**
     * Кой може да променя?
     */
    var $canList = 'no_one';
    

  	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('recipeId', 'key(mvc=cat_Recipes)', 'caption=Рецепта, input=hidden, silent');
    	$this->FLD('dProductId', 'key(mvc=cat_Products, select=name)', 'caption=Продукт,width=18em');
    	$this->FLD('dUom', 'key(mvc=cat_UoM, select=name, allowEmpty)', 'caption=Мярка,notSorting,width=10em');
    	$this->FLD('quantity', 'int', 'caption=Количество,mandatory,width=10em');
    
    	$this->setDbUnique('dProductId,dUom');
    }
    
    
     /**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	//$data->form->setOptions('dProductId', $mvc->Master->getAllowedProducts($data->form->rec->recipeId));
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(!$rec->dUom){
    		$product = cat_Products::fetch($rec->dProductId);
    		$row->dUom = cat_UoM::getTitleById($product->measureId);
    	}
    	
    	if($recipeRec = cat_Recipes::fetchByProduct($rec->dProductId)){
    		$icon = sbf("img/16/legend.png");
    		$row->dProductId = ht::createLink($row->dProductId, array('cat_Recipes', 'single', $recipeRec->id), NULL, "style=background-image:url({$icon}),class=linkWithIcon");
    	} else {
    		$icon = sbf("img/16/package-icon.png");
			$row->dProductId = ht::createLink($row->dProductId, array('cat_Products', 'single', $rec->dProductId), NULL, "style=background-image:url({$icon}),class=linkWithIcon");
    	}
    }
    
    
 	/**
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{ 
		if($action == 'add' && isset($rec->recipeId)){
			$res = 'cat, admin';
		}
	}
}