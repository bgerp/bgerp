<?php



/**
 * Мениджър за Размерите на опаковките
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Размер на опаковките
 */
class cat_PackParams extends core_Manager
{
	
	
    /**
     * Заглавие
     */
    public $title = "Размери на опаковките";
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = "Размер на опаковка";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, cat_Wrapper, plg_Search, plg_State2, plg_SaveAndNew, plg_Sorting, plg_Created, plg_Modified';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'packEdit,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'packEdit,ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'packEdit,ceo,sales,purchase';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'packEdit,ceo';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('title', 'varchar', 'caption=Заглавие');
    	$this->FLD('packagingId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'caption=Опаковка,mandatory');
    	$this->FLD('sizeWidth', 'cat_type_Size(min=0,unit=cm)', 'caption=Параметри->Дължина');
    	$this->FLD('sizeHeight', 'cat_type_Size(min=0,unit=cm)', 'caption=Параметри->Широчина');
    	$this->FLD('sizeDepth', 'cat_type_Size(min=0,unit=cm)', 'caption=Параметри->Височина');
    	$this->FLD('tareWeight', 'cat_type_Weight(min=0)', 'caption=Параметри->Тара');
    	
    	$this->setDbIndex('packagingId');
    }
    
    
    /**
     * Проверява дали посочения запис не влиза в конфликт с някой уникален
     * 
     * @param: $rec stdClass записа, който ще се проверява
     * @param: $fields array|string полетата, които не уникални.
     * @return: bool
     */
    public function isUnique($rec, &$fields = array(), &$exRec = NULL)
    {
    	if(!empty($rec->title)){
    		$where = "#id != '{$rec->id}' AND #packagingId = '{$rec->packagingId}' AND #title = '{$rec->title}'";
    		$fields = array('title', 'packagingId');
    	} else {
    		$where = "#id != '{$rec->id}' AND #packagingId = '{$rec->packagingId}' AND (#title = '' OR #title IS NULL)";
    		$where .= (!empty($rec->sizeWidth)) ? " AND #sizeWidth = {$rec->sizeWidth}" : " AND #sizeWidth IS NULL";
    		$where .= (!empty($rec->sizeHeight)) ? " AND #sizeHeight = {$rec->sizeHeight}" : " AND #sizeHeight IS NULL";
    		$where .= (!empty($rec->sizeDepth)) ? " AND #sizeDepth = {$rec->sizeDepth}" : " AND #sizeDepth IS NULL";
    		$where .= (!empty($rec->tareWeight)) ? " AND #tareWeight = {$rec->tareWeight}" : " AND #tareWeight IS NULL";
    		$fields = array('packagingId', 'sizeWidth', 'sizeHeight', 'sizeDepth', 'tareWeight');
    	}
    	
    	$res = $this->fetch($where);
    	if($res){
    		$exRec = $res;
    		return FALSE;
    	}
    
    	unset($fields);
    	return TRUE;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$options = cat_UoM::getPackagingOptions();
    	$form->setOptions('packagingId', array('' => '') + $options);
    }
    
    
    /**
     * Връща шаблоните за дадена опаковка
     * 
     * @param int $packagingId - ид на опаковка
     * @return array $array    - масив с шаблони на опаковки
     */
    public static function getTemplates($packagingId)
    {
    	$array = array();
    	
    	$query = self::getQuery();
    	$query->where("#packagingId = {$packagingId} AND #state != 'closed'");
    	while($rec = $query->fetch()){
    		$title = $rec->title;
    		
    		if(empty($rec->title)){
    			$row = self::recToVerbal($rec, 'packagingId,sizeWidth,sizeHeight,sizeDepth,tareWeight');
    			$title = new core_ET("[#packagingId#] <!--ET_BEGIN sizeWidth-->[#sizeWidth#]|<!--ET_END sizeWidth--><!--ET_BEGIN sizeHeight-->[#sizeHeight#]|<!--ET_END sizeHeight--><!--ET_BEGIN sizeDepth-->[#sizeDepth#]|<!--ET_END sizeDepth-->[#tareWeight#]");
    			$title = $title->placeObject($row)->getContent();
    			$title = trim($title, '|');
    		}
    		
    		$array[$rec->id] = $title;
    	}
    	
    	uasort($array, function($a, $b) {return strcmp($a, $b);});
    	
    	return $array;
    }
    
    
    /**
     * След като се поготви заявката за модела
     */
    protected static function on_AfterGetQuery($mvc, $query)
    {
    	$query->orderBy('title,sizeWidth,sizeHeight,sizeDepth');
    }
}