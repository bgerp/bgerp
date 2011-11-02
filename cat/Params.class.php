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
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools, cat_Wrapper';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id,typeExt';
    
    
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
        
        $this->FNC('typeExt', 'varchar', 'caption=Име');
        
        $this->setDbUnique('name, suffix');
    }
    
    
    function on_CalcTypeExt($mvc, $rec)
    {
    	$rec->typeExt = $rec->name;
    	if (!empty($rec->suffix)) {
    		$rec->typeExt  .= ' [' . $rec->suffix . ']';
    	}
    }
	
	static function createInput($rec, $form)
	{
		$name    = "value_{$rec->id}";
		$caption = "Параметри->{$rec->name}";
		
		$type    = $rec->type;
		switch ($type) {
			case 'color':
				$type = 'varchar';
				break;
		}
		
    	$form->FLD($name, $type, "input,caption={$caption},unit={$rec->suffix}");
	}
	
	
	/**
	 * Зареждане на първоначални данни
	 *
	 * @param core_Mvc $mvc
	 * @param mixed $res
	 */
	function on_AfterSetupMvc($mvc, &$res)
	{
		$initData = array(
			array(
				'name' => 'Дължина',
				'type' => 'double',
				'suffix' => 'см',
			),
			array(
				'name' => 'Височина',
				'type' => 'double',
				'suffix' => 'см',
			),
			array(
				'name' => 'Тегло',
				'type' => 'double',
				'suffix' => 'гр',
			),
			array(
				'name' => 'Тегло',
				'type' => 'double',
				'suffix' => 'кг',
			),
			array(
				'name' => 'Цвят',
				'type' => 'varchar',
				'suffix' => '',
			),
		);
		
		foreach ($initData as $rec) {
			$rec = (object)$rec;
			$rec->id = $mvc->fetchField("#name = '{$rec->name}' AND #suffix = '{$rec->suffix}'", 'id');
			$isUpdate = !empty($rec->id);
			if ($mvc->save($rec)) {
				$res .= "<li>" . ($isUpdate ? 'Обновен' : 'Добавен') . " параметър {$rec->name} [{$rec->suffix}]</li>";
			} else {
				$res .= "<li class=\"error\">Проблем при" . ($isUpdate ? 'обновяване' : 'добавяне') . " на параметър {$rec->name} [{$rec->suffix}]</li>";
			}
		}
	}
}