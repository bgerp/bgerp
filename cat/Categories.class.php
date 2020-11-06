<?php


/**
 * Мениджър на категории с продукти.
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
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
    public $title = 'Категории на артикулите';
    
    
    /**
     * Страница от менюто
     */
    public $pageMenu = 'Каталог';
    
    
    /**
     * Кои документи да се добавят като бързи бутони в папката на корицата
     */
    public $defaultDefaultDocuments = 'cat_Products';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, cat_Wrapper, plg_State, doc_FolderPlg, plg_Rejected, plg_Modified, core_UserTranslatePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name,meta=Свойства,useAsProto=Шаблони,count=Артикули';
    
    
    /**
     * Полета по които се прави пълнотекстово търсене от плъгина plg_Search
     */
    public $searchFields = 'sysId, name, productCnt, info';
    
    
    /**
     * Да се създаде папка при създаване на нов запис
     */
    public $autoCreateFolder = 'instant';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Категория';
    
    
    /**
     * Икона за единичен изглед
     */
    public $singleIcon = 'img/16/category-icon.png';
    
    
    /**
     * Кой може да чете
     */
    public $canRead = 'cat,ceo,sales,purchase';
    
    
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
    public $canDelete = 'no_one';
    
    
    /**
     * Кой има право да го оттегля?
     */
    public $canReject = 'cat,ceo';
    
    
    /**
     * Клас за елемента на обграждащия <div>
     */
    public $cssClass = 'folder-cover';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'cat/tpl/SingleCategories.shtml';
    
    
    /**
     * Дефолт достъп до новите корици
     */
    public $defaultAccess = 'team';
    
    
    /**
     * Извиква се след подготовката на формата
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        
        $suggestions = cat_UoM::getUomOptions();
        $form->setSuggestions('measures', $suggestions);
        
        if (isset($form->rec->folderId)) {
            if (cat_Products::fetchField("#folderId = {$form->rec->folderId}")) {
                $form->setReadOnly('useAsProto');
            }
        }
    }
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(64,ci)', 'caption=Наименование, mandatory, translate=user|tr|transliterate');
        $this->FLD('sysId', 'varchar(32)', 'caption=System Id,oldFieldName=systemId,input=none,column=none');
        $this->FLD('info', 'richtext(bucket=Notes,rows=4)', 'caption=Бележки');
        $this->FLD('useAsProto', 'enum(no=Не,yes=Да)', 'caption=Използване на артикулите като шаблони->Използване');
        $this->FLD('measures', 'keylist(mvc=cat_UoM,select=name,allowEmpty)', 'caption=Настройки - допустими за артикулите в категорията (всички или само избраните)->Мерки,columns=2,hint=Ако не е избрана нито една - допустими са всички');
        $this->FLD('prefix', 'varchar(64)', 'caption=Настройки - препоръчителни за артикулите в категорията->Начало код');
        $this->FLD('markers', 'keylist(mvc=cat_Groups,select=name,allowEmpty)', 'caption=Настройки - препоръчителни за артикулите в категорията->Групи,columns=2');
        $this->FLD('params', 'keylist(mvc=cat_Params,select=typeExt,makeLinks)', 'caption=Настройки - препоръчителни за артикулите в категорията->Параметри');
        
        // Свойства присъщи на продуктите в групата
        $this->FLD('meta', 'set(canSell=Продаваеми,
                                canBuy=Купуваеми,
                                canStore=Складируеми,
                                canConvert=Вложими,
                                fixedAsset=Дълготрайни активи,
        			canManifacture=Производими,generic=Генерични)', 'caption=Настройки - препоръчителни за артикулите в категорията->Свойства,columns=2');
        
        $this->setDbUnique('sysId');
        $this->setDbUnique('name');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        // Ако групата е системна или в нея има нещо записано - не позволяваме да я изтриваме
        if ($action == 'delete' && ($rec->sysId || $rec->productCnt)) {
            $requiredRoles = 'no_one';
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if ($fields['-list']) {
            $row->name .= " {$row->folder}";
            $count = cat_Products::count("#folderId = '{$rec->folderId}'");
            
            $row->count = cls::get('type_Int')->toVerbal($count);
            $row->count = "<span style='float:right'>{$row->count}</span>";
            
            if (empty($rec->useAsProto)) {
                $row->useAsProto = $mvc->getFieldType('useAsProto')->toVerbal('no');
            }
        }
        
        if ($fields['-single']) {
            if ($rec->useAsProto == 'yes') {
                $row->protoFolder = tr('Всички артикули в папката са шаблони');
            }
            
            $uRec = price_Updates::fetch("#type = 'category' AND #objectId = {$rec->id}");
            if(is_object($uRec)){
                $row->updateCostsInfo = price_Updates::getUpdateTpl($uRec);
            } else {
                $row->updateCostsInfo = tr('Все още няма зададени правила за обновяване');
            }
            
            if (price_Updates::haveRightFor('add', (object) array('type' => 'category', 'objectId' => $rec->id))) {
                $row->updateCostBtn = ht::createLink('', array('price_Updates', 'add', 'type' => 'category', 'objectId' => $rec->id, 'ret_url' => true), false, 'title=Създаване на ново правило за обновяване,ef_icon=img/16/add.png');
            }
        }
    }
    
    
    /**
     * Връща keylist от id-та на групи, съответстващи на даден стрингов
     * списък от sysId-та, разделени със запетайки
     */
    public static function getKeylistBySysIds($list, $strict = false)
    {
        $sysArr = arr::make($list);
        
        foreach ($sysArr as $sysId) {
            $id = static::fetchField("#sysId = '{$sysId}'", 'id');
            
            if ($strict) {
                expect($id, $sysId, $list);
            }
            
            if ($id) {
                $keylist .= '|' . $id;
            }
        }
        
        if ($keylist) {
            $keylist .= '|';
        }
        
        return $keylist;
    }
    
    
    /**
     * Изпълнява се преди импортирването на данните
     */
    protected static function on_BeforeImportRec($mvc, &$rec)
    {
        if ($rec->csv_measures) {
            $measures = arr::make($rec->csv_measures, true);
            $rec->measures = '';
            foreach ($measures  as $m) {
                $rec->measures = keylist::addKey($rec->measures, cat_UoM::fetchBySinonim($m)->id);
            }
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    protected static function on_AfterSetupMvc($mvc, &$res)
    {
        $res .= core_Classes::add($mvc);
        
        $file = 'cat/csv/Categories.csv';
        $fields = array(
            0 => 'name',
            1 => 'info',
            2 => 'sysId',
            3 => 'meta',
            4 => 'csv_measures',
        );
        
        $cntObj = csv_Lib::importOnce($mvc, $file, $fields);
        $res .= $cntObj->html;
        
        return $res;
    }
    
    
    /**
     * Връща мета дефолт мета данните на папката
     *
     * @param int $id - ид на категория
     *
     * @return array $meta - масив с дефолт мета данни
     */
    public function getDefaultMeta($id)
    {
        $rec = $this->fetchRec($id);
        
        return arr::make($rec->meta, true);
    }
    
    
    /**
     * Връща дефолтния код на артикула добавен в папката на корицата
     */
    public function getDefaultProductCode($id)
    {
        $rec = $this->fetchRec($id);
        
        // Ако има представка
        if ($rec->prefix) {
            
            // Опитваме се да намерим първия код започващ с представката
            $code = str::addIncrementSuffix('', $rec->prefix);
            while (cat_Products::getByCode($code)) {
                $code = str::addIncrementSuffix($code, $rec->prefix);
                if (!cat_Products::getByCode($code)) {
                    break;
                }
            }
        }
        
        // Връщаме намерения код
        return $code;
    }
    
    
    /**
     * Връща мета дефолт параметрите със техните дефолт стойностти, които да се добавят във формата на
     * универсален артикул, създаден в папката на корицата
     *
     * @param int $id - ид на корицата
     *
     * @return array $params - масив с дефолтни параметри И техните стойности
     *               <ид_параметър> => <дефолтна_стойност>
     */
    public function getDefaultProductParams($id)
    {
        $rec = $this->fetchRec($id);
        $params = keylist::toArray($rec->params);
        foreach ($params as $paramId => &$value) {
            $value = null;
        }
        
        return $params;
    }
    
    
    /**
     * Връща папките, в които може да има прототипи
     *
     * @return array $folders
     */
    public static function getProtoFolders()
    {
        $folders = array();
        
        // В кои категории може да има прототипни артикули
        $query = self::getQuery();
        $query->where("#useAsProto = 'yes'");
        $query->show('folderId');
        while ($cRec = $query->fetch()) {
            $folders[$cRec->folderId] = $cRec->folderId;
        }
        
        return $folders;
    }
    
    
    /**
     * Връща възможните за избор прототипни артикули с дадения драйвер и свойства
     *
     * @param int|NULL    $driverId - Ид на продуктов драйвер
     * @param string|NULL $meta     - Мета свойство на артикулите
     * @param int|NULL    $limit    - Ограничаване на резултатите
     * @param int|NULL    $folderId - Папка
     *
     * @return array $newOptions - прототипните артикули
     */
    public static function getProtoOptions($driverId = null, $meta = null, $limit = null, $folderId = null)
    {
        // Извличане на всички прототипни артикули
        $options = doc_Prototypes::getPrototypes('cat_Products', $driverId, $folderId);
        $newOptions = array();
        
        $count = 0;
        foreach ($options as $productId => $name) {
            
            // Ако има изискване за свойство, махат се тези които нямат свойството
            if (isset($meta)) {
                $pMeta = cat_Products::fetchField($productId, $meta);
                if ($pMeta == 'no') {
                    continue;
                }
            }
            $count++;
            
            // Ако има лимит, проверка дали е достигнат
            if (isset($limit) && $count > $limit) {
                break;
            }
            
            // Ако е стигнато до тук, артикулът се добавя към резултатите
            $newOptions[$productId] = $name;
        }
        
        return $newOptions;
    }
    
    
    /**
     * След подготовка на филтъра за филтриране в корицата
     *
     * @param core_mvc   $mvc
     * @param core_Form  $threadFilter
     * @param core_Query $threadQuery
     */
    protected static function on_AfterPrepareThreadFilter($mvc, core_Form &$threadFilter, core_Query &$threadQuery)
    {
        // Добавяме поле за избор на групи
        $threadFilter->FLD('group', 'key(mvc=cat_Groups,select=name,allowEmpty)', 'caption=Група');
        $threadFilter->showFields .= ',group';
        $threadFilter->input('group');
        
        if (isset($threadFilter->rec)) {
            
            // Ако търсим по група
            if ($group = $threadFilter->rec->group) {
                $catClass = cat_Products::getClassId();
                
                // Подготвяме заявката да се филтрират само нишки с начало Артикул
                $threadQuery->EXT('docId', 'doc_Containers', 'externalName=docId,externalKey=firstContainerId');
                $threadQuery->EXT('docClass', 'doc_Containers', 'externalName=docClass,externalKey=firstContainerId');
                $threadQuery->where("#docClass = {$catClass}");
                
                // Разпъваме групите
                $descendants = cat_groups::getDescendantArray($group);
                $keylist = keylist::fromArray($descendants);
                
                // Намираме ид-та на артикулите от тези групи
                $catQuery = cat_Products::getQuery();
                $catQuery->likeKeylist('groups', $keylist);
                $catQuery->show('id');
                $productIds = array_map(create_function('$o', 'return $o->id;'), $catQuery->fetchAll());
                
                if (empty($productIds)) {
                    // Искаме от нишките да останат само тези за въпросните артикули
                    $threadQuery->where('1=2');
                } else {
                    // Искаме от нишките да останат само тези за въпросните артикули
                    $threadQuery->in('docId', $productIds);
                }
            }
        }
    }
    
    
    /**
     * Дали артикулът създаден в папката трябва да е публичен (стандартен) или не
     *
     * @param mixed $id - ид или запис
     *
     * @return string - Стандартен / Нестандартен / Шаблон
     */
    public function getProductType($id)
    {
        $rec = $this->fetchRec($id);
        
        if ($rec->useAsProto == 'yes') {
            
            return 'template';
        }
        
        return 'public';
    }
    
    
    /**
     * Добавена проверка на различните комбинации от свойства
     * 
     * @param mixed $metasArr
     * @param int|null $productId
     * @param string|null $error
     * @return boolean
     */
    public static function checkMetas($metasArr, $productId, &$error)
    {
        $metasArr = is_array($metasArr) ? $metasArr : type_Set::toArray($metasArr);
        $exMeta = (isset($productId)) ? type_Set::toArray(cat_Products::fetchField($productId, 'meta')) : array();
        
        if(isset($metasArr['generic'])) {
             if(isset($metasArr['canBuy']) || isset($metasArr['canSell']) || isset($metasArr['fixedAsset']) || isset($metasArr['canManifacture'])){
                $error = "Генеричният артикул не може да е Продаваем, Купуваем, Производим или ДА|*";
             } elseif(!isset($metasArr['canConvert'])){
                 $error = "Генеричният артикул трябва да е и Вложим|*!";
             } elseif(isset($productId) && !haveRole('debug')){
                $exMeta = type_Set::toArray(cat_Products::fetchField($productId, 'meta'));
                if(!isset($exMeta['generic'])){
                    $error = "Съществуващ артикул не може да става генеричен|*!";
                }
            }
        } elseif(isset($productId)) {
            if(isset($exMeta['generic'])){
                $error = "Артикул създаден като генеричен, не може да се променя на негенеричен|*!";
            }
        }
        
        if(isset($productId)){
            $genericProductId = planning_GenericMapper::fetchField("#productId = {$productId}", 'genericProductId');
            if(isset($genericProductId)){
                $genericMeta = type_Set::toArray(cat_Products::fetchField($genericProductId, 'meta'));
                if(!isset($metasArr['canConvert'])){
                    $error = "Артикулът има избран генеричен. Трябва да остане вложим|*!";
                } elseif(isset($metasArr['canStore']) && !isset($genericMeta['canStore'])){
                    $error = "Артикулът има избран генеричен. Трябва да остане НЕ складируем|*!";
                } elseif(!isset($metasArr['canStore']) && isset($genericMeta['canStore'])){
                    $error = "Артикулът има избран генеричен. Трябва да остане Складируем|*!";
                }
            }
            
            if(core_Packs::isInstalled('eshop')){
                if(!isset($metasArr['canSell']) && eshop_ProductDetails::count("#productId = {$productId}")){
                    $error = "Артикулът се използва е Е-маг. Трябва да остане продаваем|*!";
                }
            }
        }
        
        return empty($error);
    }
}
