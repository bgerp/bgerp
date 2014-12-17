<?php



/**
 * Ценови групи
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Групи
 */
class price_Groups extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Ценови групи';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Ценова група";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, price_Wrapper';
   
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, title, description, productsCount=Продукти';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'powerUser';
    
    
    /**
     * Кой може да го промени?
     */
    var $canEdit = 'price,ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'price,ceo';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'price,ceo';
    
	
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'price,ceo';
    
        
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'price,ceo';

    
    /**  
     * Кой има право да променя системните данни?  
     */  
    var $canEditsysdata = 'price,ceo';
    

    /**
     * Поле за връзка към единичния изглед
     */
    var $rowToolsSingleField = 'title';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'price/tpl/SingleLayoutGroups.shtml';
    
    
    var $details = 'ProductInGroup=price_GroupOfProducts';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar(128)', 'mandatory,caption=Група');
        $this->FLD('description', 'text', 'caption=Описание');
		
        $this->setDbUnique('title');
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
        if($action == 'delete') {
            if($rec->id && price_GroupOfProducts::fetch("#groupId = {$rec->id}")) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на титлата в единичния изглед
     */
    static function on_AfterPrepareSingleTitle($mvc, &$data)
    { 
    	$title = $mvc->getVerbal($data->rec, 'title');
    	$data->title = "|*" . $title;
    	
    }
    
    
    /**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        $int = cls::get('type_Int');
    	$row->productsCount = $int->toVerbal($mvc->countProductsInGroup($rec->id));
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res) 
    {
    	// Подготвяме пътя до файла с данните 
    	$file = "price/setup/csv/Groups.csv";
    	
    	// Кои колонки ще вкарваме
    	$fields = array( 
    		0 => "title", 
    		1 => "description",
    		2 => "csv_createdBy",
    	);
    	    	
    	// Импортираме данните от CSV файла. 
    	// Ако той не е променян - няма да се импортират повторно 
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields, NULL, NULL); 
    	// Записваме в лога вербалното представяне на резултата от импортирането 
    	$res .= $cntObj->html;
    }
    
    
    /**
     * Изпълнява се преди импортирването на данните
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
    	if (isset($rec->csv_createdBy)) {

    		$rec->createdBy = -1;
    	}
    }
    
    
	/**
     * Преброява броя на продуктите в групата
     * @param int $id - ид на група
     * @return int - броя уникални продукти в група
     */
    public function countProductsInGroup($id)
    {
    	$i = 0;
    	$query = price_GroupOfProducts::getQuery();
    	$query->orderBy('#validFrom', 'DESC');
       	$used = array();
         while($rec = $query->fetch()) {
         	if($used[$rec->productId]) continue;
            if($id == $rec->groupId) {
            	$i++;
            }
            $used[$rec->productId] = TRUE;
         }
       
         return $i;
    }
}
