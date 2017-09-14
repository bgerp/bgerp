<?php



/**
 * Мениджър на групи с продукти.
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_Groups extends core_Manager
{
    
    
	/**
     * Заглавие
     */
    public $title = "Групи на артикулите";
    
    
    /**
     * Страница от менюто
     */
    public $pageMenu = "Каталог";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, cat_Wrapper, plg_Search, plg_TreeObject, plg_Translate';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,name,productCnt,orderProductBy';
    
    
    /**
     * Полета по които се прави пълнотекстово търсене от плъгина plg_Search
     */
    public $searchFields = 'sysId, name, productCnt';
    
    
    /**
     * Кои полета да се сумират за наследниците
     */
    public $fieldsToSumOnChildren = 'productCnt';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Група";
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'cat,ceo';
    
    
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
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой може да качва файлове
     */
    public $canWrite = 'cat,ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'cat,ceo';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64,ci)', 'caption=Наименование, mandatory,translate');
        $this->FLD('sysId', 'varchar(32)', 'caption=System Id,oldFieldName=systemId,input=none,column=none');
        $this->FLD('productCnt', 'int', 'input=none,caption=Артикули');
        $this->FLD('orderProductBy', 'enum(name=Име,code=Код)', 'caption=Сортиране по,notNull,value=name,after=parentId');
        
        // Свойства присъщи на продуктите в групата
        $this->FLD('meta', 'set(canSell=Продаваеми,
                                canBuy=Купуваеми,
                                canStore=Складируеми,
                                canConvert=Вложими,
                                fixedAsset=Дълготрайни активи,
        						canManifacture=Производими)', 'caption=Свойства->Списък,columns=2,input=none');
        
        $this->setDbUnique("sysId");
        $this->setDbIndex('parentId');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	if($form->isSubmitted()){
    		$condition = "#name = '[#1#]' AND #id != '{$rec->id}' AND ";
    		$condition .= isset($rec->parentId) ? "#parentId = {$rec->parentId}" : " #parentId IS NULL";
    		
    		if($mvc->fetchField(array($condition, $rec->name))){
    			$form->setError('name,parentId', 'Вече съществува запис със същите данни');
    		}
    	}
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('product', 'key(mvc=cat_Products, select=name, allowEmpty=TRUE)', 'caption=Продукт');
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'search,product';
        
        $rec = $data->listFilter->input(NULL, 'silent');
        
        $data->query->orderBy('#name');
        
        if($data->listFilter->rec->product) {
            $groupList = cat_Products::fetchField($data->listFilter->rec->product, 'groups');
            $data->query->where("'{$groupList}' LIKE CONCAT('%|', #id, '|%')");
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if($fields['-list']){
            $row->productCnt = ht::createLinkRef($row->productCnt, array('cat_Products', 'list', 'groupId' => $rec->id), FALSE, "title=Филтър на|* \"{$row->name}\"");
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
        if($action == 'delete' && ($rec->sysId || $rec->productCnt)) {
        	$requiredRoles = 'no_one';
        }
        
        if($action == 'edit' && $rec->sysId){
        	$requiredRoles = 'no_one';
        }
    }
    
    
    /**
     * Преди импорт на записи
     */
    protected static function on_BeforeImportRec($mvc, &$rec)
    {
    	// Ако е зададен баща опитваме се да го намерим
    	if(isset($rec->csv_parentId)){
    		if($parentId = $mvc->fetchField(array("#name = '[#1#]'", $rec->csv_parentId), 'id')){
    			$rec->parentId = $parentId;
    		}
    	}
    }
    
    
    /**
     * След обновяване на модела
     */
    protected static function on_AfterSetupMvc($mvc, &$res)
    {
    	$file = "cat/csv/Groups.csv";
    	$fields = array(
    			0 => "name",
    			1 => "sysId",
    			2 => 'csv_parentId',
    	);
    
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields);
    	$res .= $cntObj->html;
    
    	return $res;
    }
    
    
    /**
     * Връща кейлист от систем ид-та на групите
     * 
     * @param mixed $sysIds - масив със систем ид-та
     * @return string
     */
    public static function getKeylistBySysIds($sysIds)
    {
    	$kList = '';
    	$sysIds = arr::make($sysIds);
    	
    	if(!count($sysIds)) return $kList;
    	
    	foreach($sysIds as $grId){
    		$kList = keylist::addKey($kList, self::fetchField("#sysId = '{$grId}'", 'id'));
    	}
    	
    	return $kList;
    }


    /**
     * Форсира група (маркер) от каталога
     *
     * @param   string  $name       Име на групата. Съдържа целия път
     * @param   int     $parentId   Id на родител
     * @param   boolean $force 
     *
     * @return  int|NULL            id на групата
     */
    public static function forceGroup($name, $parentId = NULL, $force = TRUE)
    {  
        static $groups = array();
        
        $parentIdNumb = (int) $parentId;

        if(!($res = $groups[$parentIdNumb][$name])) {
            
            if(strpos($name, '»')) {
                $gArr = explode('»', $name);
                foreach($gArr as $gName) {
                    $gName = trim($gName);
                    $parentId = self::forceGroup($gName, $parentId, $force);
                }

                $res = $parentId;
            } else {

                if($parentId === NULL) {
                   $cond = "AND #parentId IS NULL";
                } else {
                    expect(is_numeric($parentId), $parentId);

                    $cond = "AND #parentId = {$parentId}";
                }
                
                $gRec = cat_Groups::fetch(array("LOWER(#name) = LOWER('[#1#]'){$cond}", $name));

                if(isset($gRec->name)) {
                    $res = $gRec->id;
                } else {
                    if ($force) {
                        $gRec = (object) array('name' => $name, 'orderProductBy' => 'code', 'meta' => 'canSell,canBuy,canStore,canConvert,canManifacture', 'parentId' => $parentId);
                        
                        cat_Groups::save($gRec);
                        
                        $res = $gRec->id;
                    } else {
                        $res = NULL;
                    }
                }
            }

            $groups[$parentIdNumb][$name] = $res;
        } 
 
        return $res;
    }
    
    
    /**
     * Връщане на списъка от групи като линк
     * 
     * @param string $keylist - списък от групи
     * @param string $class   - клас на линковете
     * @return array $res     - масив от линкове
     */
    public static function getLinks($keylist, $class = 'group-link')
    {
    	$res = array();
    	$groups = (is_array($keylist)) ? $keylist : keylist::toArray($keylist);
    	if(!count($groups)) return $res;
    	
    	$makeLink = (cat_Products::haveRightFor('list') && !Mode::isReadOnly()) ? TRUE : FALSE;
    	foreach ($groups as $grId){
    		if($makeLink === TRUE){
    			$listUrl = array('cat_Products', 'list', 'groupId' => $grId);
    		}
    	
    		$classAttr = "class={$class}";
    		$groupTitle = self::getVerbal($grId, 'name');
    		$groupLink = ht::createLink($groupTitle, $listUrl, FALSE, "{$classAttr},title=Филтриране на артикули по група|* '{$groupTitle}'");
    		$res[] = $groupLink->getContent();
    	}
    	
    	return $res;
    }
    
    
    /**
     * Има ли в подадените групи, такива които са наследници на друга група от списъка
     * 
     * @param mixed $groupList - масив или списък от групи
     * @return boolean
     */
    public static function checkForNestedGroups($groupList)
    {
    	$groups = (is_array($groupList)) ? $groupList : keylist::toArray($groupList);
    	if(!count($groups)) return FALSE;
    	
    	$notAllowed = array();
    	foreach ($groups as $grId){
    		
    		// Ако текущия маркер е в недопустимите сетваме грешка
    		if(array_key_exists($grId, $notAllowed)) return TRUE;
    		 
    		// Иначе добавяме него и наследниците му към недопустимите групи
    		$descendant = cat_Groups::getDescendantArray($grId);
    		$notAllowed += $descendant;
    	}
    	
    	return FALSE;
    }
}
