<?php



/**
 * Мениджър за параметрите на опаковките
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Параметри на опаковките
 */
class cat_PackParams extends core_Manager
{
	
	
    /**
     * Заглавие
     */
    public $title = "Параметри на опаковките";
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = "Параметър на опаковка";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, cat_Wrapper, plg_Search, plg_State2, plg_SaveAndNew';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'cat,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'cat,ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'cat,ceo';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('packagingId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'caption=Опаковка,mandatory');
    	$this->FLD('sizeWidth', 'cat_type_Size(min=0)', 'caption=Параметри->Ширина');
    	$this->FLD('sizeHeight', 'cat_type_Size(min=0)', 'caption=Параметри->Височина');
    	$this->FLD('sizeDepth', 'cat_type_Size(min=0)', 'caption=Параметри->Дълбочина');
    	$this->FLD('tareWeight', 'cat_type_Weight(min=0)', 'caption=Параметри->Тара');
    	
    	$this->setDbIndex('packagingId');
    }
    
    
    /**
     * Проверява дали посочения запис не влиза в конфликт с някой уникален
     * @param: $rec stdClass записа, който ще се проверява
     * @param: $fields array|string полетата, които не уникални.
     * @return: bool
     */
    public function isUnique($rec, &$fields = array(), &$exRec = NULL)
    {
    	$where = "#id != '{$rec->id}' AND #packagingId = {$rec->packagingId}";
    	$where .= (!empty($rec->sizeWidth)) ? " AND #sizeWidth = {$rec->sizeWidth}" : " AND #sizeWidth IS NULL";
    	$where .= (!empty($rec->sizeHeight)) ? " AND #sizeHeight = {$rec->sizeHeight}" : " AND #sizeHeight IS NULL";
    	$where .= (!empty($rec->sizeDepth)) ? " AND #sizeDepth = {$rec->sizeDepth}" : " AND #sizeDepth IS NULL";
    	$where .= (!empty($rec->tareWeight)) ? " AND #tareWeight = {$rec->tareWeight}" : " AND #tareWeight IS NULL";
    	
    	$res = $this->fetch($where);
    	if($res){
    		$exRec = $res;
    		$fields = array('packagingId', 'sizeWidth', 'sizeHeight', 'sizeDepth', 'tareWeight');
    		return FALSE;
    	}
    
    	return TRUE;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
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
    public static function getPackaginTemplates($packagingId)
    {
    	$array = array();
    	$uomType = cat_UoM::fetchField($packagingId, 'type');
    	if($uomType != 'packaging') return $array;
    	
    	$query = self::getQuery();
    	$query->where("#packagingId = {$packagingId} AND #state != 'closed'");
    	while($rec = $query->fetch()){
    		$row = self::recToVerbal($rec, 'packagingId,sizeWidth,sizeHeight,sizeDepth,tareWeight');
    		
    		$title = new core_ET("[#packagingId#] <!--ET_BEGIN sizeWidth-->[#sizeWidth#]|<!--ET_END sizeWidth--><!--ET_BEGIN sizeHeight-->[#sizeHeight#]|<!--ET_END sizeHeight--><!--ET_BEGIN sizeDepth-->[#sizeDepth#]|<!--ET_END sizeDepth-->[#tareWeight#]");
    		$title->placeObject($row);
    		
    		$array[$rec->id] = $title->getContent();
    	}
    	
    	return $array;
    }
    
    
    /**
     * Създаване на нов шаблон ако няма
     *  
     * @param int $packagingId   - опаковка
     * @param double $sizeWidth  - широчина
     * @param double $sizeHeight - височина
     * @param double $sizeDepth  - дълбочина
     * @param double $tareWeight - тара
     */
    public static function sync($packagingId, $sizeWidth, $sizeHeight, $sizeDepth, $tareWeight)
    {
    	$rec = (object)array('packagingId' => $packagingId, 'sizeWidth' => $sizeWidth, 'sizeHeight' => $sizeHeight, 'sizeDepth' => $sizeDepth, 'tareWeight' => $tareWeight);
    	$self = cls::get(get_called_class());
    	if($self->isUnique($rec, $fields, $exRec)){
    		$self->save($rec);
    	}
    }
}