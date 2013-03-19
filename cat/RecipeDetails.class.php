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
    	$this->FLD('dUom', 'key(mvc=cat_UoM, select=name, allowEmpty)', 'caption=Мярка,notSorting,width=18em');
    	$this->FLD('quantity', 'int', 'caption=Количество,mandatory');
    }
    
    
    /**
     * Обработка след изпращане на формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()) {
    		//@TODO
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