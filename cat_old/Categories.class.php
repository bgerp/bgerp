<?php
/**
 * Мениджира Категориите от продукти
 * 
 * Всеки продукт (@see cat_Products) принадлежи на точно една категория. Категорията определя 
 * атрибутите на продуктите, които са в нея.
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 * @title Продуктови категории
 *
 */
class cat_Categories extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Категории";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Каталог";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools, cat_Wrapper, plg_State2';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id,name,params,packagings,state';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'id';
    
	var $details = 'cat_Products';    
    
    /**
     * Права
     */
    var $canRead = 'admin,user';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,acc,broker';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,acc,broker';
    
    /**
     *  @todo Чака за документация...
     */
    var $canList = 'admin,acc,broker';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,acc';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Име, mandatory');
        $this->FLD('info', 'text', 'caption=Инфо');
        $this->FLD('params', 'keylist(mvc=cat_Params,select=name)', 'caption=Параметри');
        $this->FLD('packagings', 'keylist(mvc=cat_Packagings,select=name)', 'caption=Опаковки');
        $this->FLD('productCnt', 'int', 'input=none,caption=Продукти');
    }
    
    
    function on_AfterPrepareListRecs($mvc, $data)
    {
        if (count($data->rows)) {
            foreach ($data->rows as $i=>&$row) {
            	$rec = $data->recs[$i];
            	$row->productCnt = intval($rec->productCnt);
            	$row->name = $rec->name;
            	$row->name .= " ({$row->productCnt})";
            	$row->name = ht::createLink($row->name, array('cat_Products', 'list', 'categoryId' => $rec->id));
            	$row->name .= "<div><small>{$rec->info}</small></div>";
            }
        }
    }
    
    static function &getParamsForm($id, &$form = NULL)
    {
    	$rec = self::fetch($id);
    	$paramIds = type_Keylist::toArray($rec->params);
    	
    	sort($paramIds); // за да бъде подредбата предсказуема и новите парам. да са най-отдолу.
    	
    	if (!isset($form)) {
    		$form = cls::get('core_Form');
    	}
    	
    	foreach ($paramIds as $paramId) {
    		$rec = cat_Params::fetch($paramId);
    		cat_Params::createParamInput($rec, $form);
    	}
    	
    	return $form;
    }
}