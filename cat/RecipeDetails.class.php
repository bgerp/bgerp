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
	 * Полета за изглед
	 */
	var $listFields = 'id, dProductId, dUom, quantity';
	
	
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
    }
    
    
     /**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	//$data->form->setOptions('dProductId', $mvc->Master->getAllowedProducts());
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