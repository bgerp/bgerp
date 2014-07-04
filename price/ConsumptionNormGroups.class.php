<?php



/**
* Модел Групи на разходните норми
*
*
* @category bgerp
* @package price
* @author Ivelin Dimov <ivelin_pdimov@abv.bg>
* @copyright 2006 - 2012 Experta OOD
* @license GPL 3
* @since v 0.1
*/
class price_ConsumptionNormGroups extends core_Manager {
    
    
	/**
	* Заглавие
	*/
	var $title = 'Групи';
	    
	    
	/**
	* Полета, които ще се показват в листов изглед
	*/
	var $listFields = 'tools=Пулт, title, normCount, createdOn, createdBy';
	    
	    
	/**
	* Плъгини за зареждане
	*/
	var $loadList = 'plg_RowTools, plg_Created, price_Wrapper, plg_Printing, plg_Sorting';
	    
	    
	/**
	* Полето в което автоматично се показват иконките за редакция и изтриване на реда от
	* таблицата.
	*
	* @see plg_RowTools
	* @var $string име на поле от този модел
	*/
	var $rowToolsField = 'tools';
	
	    
	/**
	* Кой може да чете
	*/
	var $canRead = 'price, ceo';
	    
	    
	/**
	* Кой може да пише
	*/
	var $canWrite = 'price, ceo';
	    
	    
	/**
	* Описание на модела (таблицата)
	*/
    function description()
    {
	     $this->FLD('title', 'varchar(255)', 'caption=Име, mandatory');
	     $this->FLD('normCount', 'int', 'caption=Разходни норми, input=none, value=0');
	    
	     $this->setDbUnique('title');
    }
    
    
	/**
	* Обновяване на броя Разходни норми във всяка група
	*/
    public static function updateCount()
    {
	     $query = static::getQuery();
	     while($rec = $query->fetch()){
		     $recipeQuery = price_ConsumptionNorms::getQuery();
		     $recipeQuery->where("#groups LIKE '%|{$rec->id}|%'");
		     $rec->normCount = $recipeQuery->count();
		     static::save($rec);
     	}
    }
    
    
    /**
	* След преобразуване на записа в четим за хора вид.
	*/
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
     	$row->title = ht::createLink($row->title, array('price_ConsumptionNorms', 'list', 'gr' => $rec->id));
    }
}