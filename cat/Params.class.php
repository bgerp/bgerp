<?php



/**
 * Мениджира динамичните параметри на продуктите
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Продуктови параметри
 */
class cat_Params extends bgerp_ProtoParam
{
	
	
    /**
     * Заглавие
     */
    public $title = "Продуктови параметри";
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = "Продуктов параметър";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, cat_Wrapper, plg_Search, plg_State2,plg_SaveAndNew';
    
    
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
	public $canList = 'cat,ceo';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'cat,ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'cat,ceo';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'group, name, suffix,  sysId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'typeExt,order,driverClass=Тип,state,roles,showInPublicDocuments=Показване в документи->Външни,showInTasks=Показване в документи->Пр. операции';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo,admin';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	parent::setFields($this);
    	$this->FLD('showInPublicDocuments', 'enum(no=Не,yes=Да)', 'caption=Показване на параметъра->Външни документи,notNull,value=yes,maxRadio=2');
    	$this->FLD('showInTasks', 'enum(no=Не,yes=Да)', 'caption=Показване на параметъра->Пр. операции,notNull,value=no,maxRadio=2');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$data->form->setDefault('showInPublicDocuments', 'yes');
    	
    	if(isset($data->form->rec->sysId)){
    		$data->form->setReadOnly('showInTasks');
    	}
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
    	$file = "cat/csv/Params.csv";
    	$fields = array(
    			0 => "name",
    			1 => "driverClass",
    			2 => "suffix",
    			3 => "sysId",
    			4 => "options",
    			5 => "default",
    			6 => "showInPublicDocuments",
    			7 => "state",
    			8 => 'csv_params',
    			9 => 'showInTasks',
    	);
    	 
    	$cntObj = csv_Lib::importOnce($this, $file, $fields);
    	$res = $cntObj->html;
    	
    	return $res;
    }
    
    
    /**
     * Връща дефолт стойността за параметъра
     * 
     * @param $paramId - ид на параметър
     * @return FALSE|string
     */
    public static function getDefault($paramId)
    {
    	// Ако няма гледаме имали дефолт за параметъра
    	$default = self::fetchField($paramId, 'default');
    	
    	if(!empty($default)) return $default;
    	
    	return FALSE;
    }
    
    
    /**
     * Връща нормализирано име на параметъра
     * 
     * @param mixed $rec         - ид или запис на параметър
     * @param boolean $upperCase - всички букви да са в долен или в горен регистър
     * @param string $lg         - език на който да е преведен
     * @return string $name      - нормализирано име
     */
    public static function getNormalizedName($rec, $upperCase = FALSE, $lg = 'bg')
    {
    	$rec = cat_Params::fetchRec($rec, 'name,suffix');
    	
    	core_Lg::push($lg);
    	$name = tr($rec->name) . ((!empty($rec->suffix)) ? " (" . tr($rec->suffix) . ")": '');
    	$name = preg_replace('/\s+/', '_', $name);
    	$name = ($upperCase) ? mb_strtoupper($name) : mb_strtolower($name);
    	core_Lg::pop();
    	
    	return $name;
    }
    
    
    /**
     * Разбира масив с параметри на масив с ключвове, преведените
     * имена на параметрите
     * 
     * @param array $params      - масив с параметри
     * @param boolean $upperCase - дали имената да са в долен или горен регистър
     * @return array $arr        - масив
     */
    public static function getParamNameArr($params, $upperCase = FALSE)
    {
    	$arr = array();
    	if(is_array($params)){
    		foreach ($params as $key => $value){
    			expect($rec = cat_Params::fetch($key, 'name,suffix'));
    			
    			// Името на параметъра се превежда на местния език
    			$key1 = self::getNormalizedName($rec, $upperCase);
    			$arr[$key1] = $value;
    			
    			// Името на параметъра се превежда на глобалния език
    			$key2 = self::getNormalizedName($rec, $upperCase, 'en');
    			$arr[$key2] = $value;
    		}
    	}
    	
    	return $arr;
    }
    
    
    /**
     * Рендира блок с параметри за артикули
     * 
     * @param array $paramArr
     * @return core_ET $tpl
     */
    public static function renderParamBlock($paramArr)
    {
    	$tpl = getTplFromFile('cat/tpl/products/Params.shtml');
    	$lastGroupId = NULL;
    	
    	if(is_array($paramArr)){
    		foreach($paramArr as &$row2) {
    			 
    			$block = clone $tpl->getBlock('PARAM_GROUP_ROW');
    			if($row2->group != $lastGroupId){
    				$block->replace($row2->group, 'group');
    			}
    			$lastGroupId = $row2->group;
    			unset($row2->group);
    			$block->placeObject($row2);
    			$block->removeBlocks();
    			$block->removePlaces();
    			$tpl->append($block, 'ROWS');
    		}
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Форсира параметър
     *
     * @param string $sysId             - систем ид на параметър
     * @param string $name              - име на параметъра
     * @param string $type              - тип на параметъра
     * @param NULL|text $options        - опции на параметъра само за типовете enum и set
     * @param NULL|string $suffix       - наставка
     * @param NULL|boolean $showInTasks - може ли да се показва в производствена операция
     * @return int                      - ид на параметъра
     */
    public static function force($sysId, $name, $type, $options = array(), $suffix = NULL, $showInTasks = FALSE)
    {
    	// Ако има параметър с това систем ид,връща се
    	$id = self::fetchIdBySysId($sysId);
    	if(!empty($id)) return $id;
    	
    	$nRec = static::makeNewRec($sysId, $name, $type, $options, $suffix);
    	$nRec->showInTasks = ($showInTasks) ? 'yes' : 'no';
    	
    	// Създаване на параметъра
    	return self::save($nRec);
    }
    
    
    /**
     * Връща параметрите, които се показват само в задачите
     * 
     * @return array $res
     */
    public static function getTaskParamIds()
    {
    	$query = self::getQuery();
    	$query->where("#showInTasks = 'yes'");
    	$res = arr::extractValuesFromArray($query->fetchAll(), 'id');
    	
    	return $res;
    }
}