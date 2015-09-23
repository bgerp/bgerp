<?php



/**
 * Мениджър на категории с продукти.
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_Categories extends core_Master
{
    
    
	/**
	 * Поддържани интерфейси
	 */
	public $interfaces = 'cat_ProductFolderCoverIntf';
	
	
    /**
     * Заглавие
     */
    var $title = "Категории на артикулите";
    
    
    /**
     * Страница от менюто
     */
    var $pageMenu = "Каталог";
    
    
    /**
     * Кои документи да се добавят като бързи бутони в папката на корицата
     */
    public $defaultDefaultDocuments  = 'cat_Products';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, cat_Wrapper, plg_State, doc_FolderPlg, plg_Rejected';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,name,meta=Свойства';
    
    
    /**
     * Полета по които се прави пълнотекстово търсене от плъгина plg_Search
     */
    var $searchFields = 'sysId, name, productCnt, info';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Категория";
    
    
    /**
     * Икона за единичен изглед
     */
    var $singleIcon = 'img/16/category-icon.png';
    
    
    /**
     * Кой може да чете
     */
    var $canRead = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    var $canEditsysdata = 'cat,ceo';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'cat,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'cat,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой може да качва файлове
     */
    var $canWrite = 'cat,ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'cat,ceo';
    
    
    /**
     * Клас за елемента на обграждащия <div>
     */
    var $cssClass = 'folder-cover';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'cat/tpl/SingleCategories.shtml';
    
    
    /**
     * Дефолт достъп до новите корици
     */
    public $defaultAccess = 'team';
    
    
    /**
     * Извиква се след подготовката на формата
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$suggestions = cat_UoM::getUomOptions();
    	$data->form->setSuggestions('measures', $suggestions);
    }
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Наименование, mandatory,translate');
        $this->FLD('prefix', 'varchar(64)', 'caption=Представка');
        $this->FLD('sysId', 'varchar(32)', 'caption=System Id,oldFieldName=systemId,input=none,column=none');
        $this->FLD('info', 'richtext(bucket=Notes,rows=4)', 'caption=Бележки');
        $this->FLD('measures', 'keylist(mvc=cat_UoM,select=name,allowEmpty)', 'caption=Настройки - допустими за артикулите в категорията (всички или само избраните)->Мерки,columns=2,hint=Ако не е избрана нито една - допустими са всички');
        $this->FLD('markers', 'keylist(mvc=cat_Groups,select=name,allowEmpty)', 'caption=Настройки - препоръчителни за артикулите в категорията->Маркери,columns=2');
        
        // Свойства присъщи на продуктите в групата
        $this->FLD('meta', 'set(canSell=Продаваеми,
                                canBuy=Купуваеми,
                                canStore=Складируеми,
                                canConvert=Вложими,
                                fixedAsset=Дълготрайни активи,
        			canManifacture=Производими)', 'caption=Настройки - препоръчителни за артикулите в категорията->Свойства,columns=2');
        
        
        $this->setDbUnique("sysId");
        $this->setDbUnique("name");
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако групата е системна или в нея има нещо записано - не позволяваме да я изтриваме
        if($action == 'delete' && ($rec->sysId || $rec->productCnt)) {
            $requiredRoles = 'no_one';
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($fields['-list']){
    		$row->name .= " {$row->folder}";
    	}
    }
    
    
    /**
     * Връща keylist от id-та на групи, съответстващи на даден стрингов
     * списък от sysId-та, разделени със запетайки
     */
    static function getKeylistBySysIds($list, $strict = FALSE)
    {
        $sysArr = arr::make($list);
        
        foreach($sysArr as $sysId) {
            $id = static::fetchField("#sysId = '{$sysId}'", 'id');
            
            if($strict) {
                expect($id, $sysId, $list);
            }
            
            if($id) {
                $keylist .= '|' . $id;
            }
        }
        
        if($keylist) {
            $keylist .= '|';
        }
        
        return $keylist;
    }
    
    
    /**
     * Изпълнява се преди импортирването на данните
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
    	if($rec->csv_measures){
    		$measures = arr::make($rec->csv_measures, TRUE);
    		$rec->measures = '';
    		foreach ($measures  as $m){
    			$rec->measures = keylist::addKey($rec->measures, cat_UoM::fetchBySinonim($m)->id);
    		}
    	}
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        $file = "cat/csv/Categories.csv";
        $fields = array(
            0 => "name",
            1 => "info",
            2 => "sysId",
            3 => "meta",
        	4 => "csv_measures",
        );
        
        $cntObj = csv_Lib::importOnce($mvc, $file, $fields);
        $res .= $cntObj->html;
        
        return $res;
    }
    
    
    /**
     * Връща мета дефолт мета данните на папката
     *
     * @param int $id - ид на спецификация папка
     * @return array $meta - масив с дефолт мета данни
     */
    public function getDefaultMeta($id)
    {
    	$rec = $this->fetchRec($id);
    	
    	return arr::make($rec->meta, TRUE);
    }
    
    
    /**
     * Връща дефолтния код на артикула добавен в папката на корицата
     */
    public function getDefaultProductCode($id)
    {
    	$rec = $this->fetchRec($id);
    	
    	// Ако има представка
    	if($rec->prefix){
    		
    		// Опитваме се да намерим първия код започващ с представката
    		$code = str::addIncrementSuffix("", $rec->prefix);
    		while(cat_Products::getByCode($code)){
    			$code = str::addIncrementSuffix($code, $rec->prefix);
    			if(!cat_Products::getByCode($code)){
    				break;
    			}
    		}
    	}
    	
    	// Връщаме намерения код
    	return $code;
    }
}
