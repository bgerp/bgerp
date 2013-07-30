<?php



/**
 * Мениджър на групи с продукти.
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_Groups extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Групи на артикулите";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Каталог";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, cat_Wrapper, doc_FolderPlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,name';
    
    
    /**
     * Дали да се превежда, транслитерира singleField полето
     * 
     * translate - Превежда
     * transliterate - Транслитерира
     */
    var $langSingleField = 'translate';
    
    
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
    var $singleTitle = "Група->продукти";
    
    
    /**
     * Икона за единичен изглед
     */
    var $singleIcon = 'img/16/category-icon.png';

    
    /**
     * Права
     */
    var $canRead = 'user';
    
    
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
	var $canList = 'user';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'user';
	
    
    /**
     * Кой може да качва файлове
     */
    var $canWrite = 'ceo,cat';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'user';
    
    
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
    var $singleLayoutFile = 'cat/tpl/SingleGroup.shtml';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Наименование, mandatory');
        $this->FLD('sysId', 'varchar(32)', 'caption=System Id,oldFieldName=systemId,input=none,column=none');
        $this->FLD('info', 'richtext(bucket=Notes)', 'caption=Бележки');
        $this->FLD('productCnt', 'int', 'input=none');
        
        // Свойства присъщи на продуктите в групата
        $this->FLD('meta', 'set(canSell=Продаваеми,
        						canBuy=Купуваеми,
        						canStore=Складируеми,
        						canConvert=Вложими,
        						fixedAsset=ДМА,
        						canManifacture=Производими)', 'caption=Свойства->Списък,columns=2');
        
        $this->setDbUnique("sysId");
    }
    
    
     /**
     * Изпълнява се след подготовка на Едит Формата
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
    	if(!haveRole('ceo')){
    		
    		// Кой може да променя мета пропъртитата на групите
    		$data->form->setField('meta', 'input=none');
    	}
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->productCnt = intval($rec->productCnt);
    	if($fields['-list']){
    		$row->name .= " ({$row->productCnt})";
    	}
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
        if(($rec->sysId || $rec->productCnt) && $action == 'delete') {
            $requiredRoles = 'no_one';
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
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	$file = "cat/csv/Groups.csv";
    	$fields = array( 
	    	0 => "name", 
	    	1 => "info", 
	    	2 => "sysId", 
	    	3 => "meta");
    	
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields);
    	$res .= $cntObj->html;
    	
    	return $res;
    }
}
