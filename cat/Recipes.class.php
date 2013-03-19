<?php



/**
 * Модел  Рецептурник
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_Recipes extends core_Master {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Рецептурник';
    
    
    /**
     * Заглавие
     */
    var $singleTitle = 'Рецепта за себестойност';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, productId, uom, state, createdOn, createdBy, modifiedOn, modifiedBy';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'productId';
    
    
    /**
	 * Коментари на статията
	 */
	var $details = 'cat_RecipeDetails';
	
	
	/**
	 * Брой рецепти на страница
	 */
	var $listItemsPerPage = '40';
	
	
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, cat_Wrapper, cat_RecipeWrapper, doc_DocumentPlg,
    	 plg_Printing, bgerp_plg_Blank, plg_Sorting, plg_Search';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от 
     * таблицата.
     */
    var $rowToolsField = 'tools';

    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/legend.png';
    
    
    /**
     * Кой може да чете
     */
    var $canRead = 'cat, admin';
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'cat, admin';
    
 
    /**
     * Файл с шаблон за единичен изглед
     */
    var $singleLayoutFile = 'cat/tpl/SingleLayoutRecipes.shtml';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "10.1|Каталог";
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('productId', 'key(mvc=cat_Products, select=name)', 'caption=Продукт,width=18em');
    	$this->FLD('uom', 'key(mvc=cat_UoM, select=name, allowEmpty)', 'caption=Мярка,notSorting,width=18em');
    	$this->FLD('info', 'text(rows=4)', 'caption=Информация,width=18em');
    	$this->FLD('groups', 'keylist(mvc=cat_RecipeGroups, select=title)', 'caption=Групи');
    	$this->FLD('state','enum(draft=Чернова, active=Активиран, rejected=Оттеглен)', 'caption=Статус, input=none');
    }
    
    
    /**
     * Обработка след изпращане на формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()) {
    		if($form->rec->measureId) {
    			$productUom = cat_Products::fetchField($form->rec->productId, 'measureId');
    			$productUomRec = cat_UoM::fetch($productUom);
    			$uomRec = cat_UoM::fetch($form->rec->uom);
    			if($uomRec->baseUnitId != $productUom && $uomRec->baseUnitId != $productUomRec->baseUnitId) {
    				$form->setError('uom', 'Избраната мярка не е от същата група като основната мярка на продукта');
    			}
    		}
    	}
    }
    
    
    /**
   	 * Обработка на SingleToolbar-a
   	 */
   	static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$data->toolbar->addBtn('Изчисти', array($mvc, 'clean'));
    }
   
    
    /**
     * Извлича продуктите които съставят даден продукт
     * @TODO
     * @param int $productId - ид на продукт
     * @param int $quantity - количество от продукта
     * @param datetime $datetime - дата
     * @return array $results - масив с обекти на съставящите го
     * продукти
     */
    public static function getIngredients($productId, $quantity = 1, $datetime = NULL)
    {
    	$results = array();
    	expect($productRec = cat_Products::fetch($productId));
    	$query = cat_RecipeDetails::getQuery();
    	$query->where("#dProductId = {$productId}");
    	while($rec = $query->fetch()){
    		$obj = new stdClass();
    		$obj->productId = $rec->productId;
    		$obj->quantity = $rec->quantity;
    		$results[$rec->id] = $obj;
    	}
    	
    	return $results;
    }
    
    
 	/**
   	 * Обработка на SingleToolbar-a
   	 */
   	static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$data->toolbar->addBtn('Калкулиране на себестойности', array($mvc, 'calcAll'));
    }
    
    
    /**
     * Изчислява себестойноста на всички листвани рецепти и ги
     * записва в модел себестойности
     */
    function act_calcAll()
    {
    	//@TODO
    }
    
    
    /**
     * 
     */
    function act_Clean()
    {
    	//@TODO
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
		//@TODO
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = "Отчет за бърза продажба №{$rec->id}";
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