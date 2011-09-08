<?php
/**
 * Мениджира динамичните параметри на категориите
 * 
 * Всяка категория (@see cat_Categories) има нула или повече динамични параметри. Те са всъщност
 * параметрите на продуктите (@see cat_Products), които принадлежат на категорията.
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 * @title Продуктови параметри
 *
 */
class cat_Params extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Параметри";
    
    
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
//    var $listFields = 'id,title, inPriceLists,state,groupIcon';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'id';
    
    
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
    var $canDelete = 'admin,acc';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Име, mandatory');
        $this->FLD('type', 'enum(double=Число, int=Цяло число, varchar=Текст, color=Цвят, date=Дата)', 'caption=Тип');
        $this->FLD('suffix', 'varchar(64)', 'caption=Суфикс');
    }
	
	static function createParamInput($rec, $form)
	{
		$name    = "value_{$rec->id}";
		$caption = "Параметри->{$rec->name}";
		
		$type    = $rec->type;
		switch ($type) {
			case 'color':
				$type = 'varchar';
				break;
		}
		
    	$form->FLD($name, $type, "input,caption={$caption}");
	}
	
}