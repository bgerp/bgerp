<?php


/**
 * Клас 'core_Classes' - Регистър на класовете, имащи някакви интерфейси
 *
 *
 * @category  ef
 * @package   core
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_Classes extends core_Manager
{
    /**
     * Списък за начално
     */
    public $loadList = 'plg_Created, plg_SystemWrapper, plg_State2, plg_RowTools, plg_Search,plg_Sorting';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Класове, имащи интерфейси';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Никой потребител не може да добавя или редактира тази таблица
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Работен кеш за извлечените интерфейсни методи
     */
    public static $interfaceMehods = array();
    
    
    /**
     * Работен кеш за извлечените статичните интерфейсни методи
     */
    public static $staticInterfaceMehods = array();
    
    
    /**
     * Работен кеш за имената и id-тата na klasowete
     */
    public static $classes = array();
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'name, title';
    
    
    protected static $classHashName = 'loadClasses1';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Клас,mandatory,width=100%');
        $this->FLD('title', 'varchar', 'caption=Заглавие,width=100%,oldField=info');
        $this->FLD('interfaces', 'keylist(mvc=core_Interfaces,select=name)', 'caption=Интерфейси');
        
        $this->setDbUnique('name');
        
        // Ако не сме в DEBUG-режим, класовете не могат да се редактират
        if (!isDebug()) {
            $this->canWrite = 'no_one';
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('name');
        
        $data->listFilter->FLD('interface', 'key(mvc=core_Interfaces,select=name, allowEmpty)', 'placeholder=Интерфейс');
        $data->listFilter->showFields = 'search,interface';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->input();
        
        if ($interfaceId = $data->listFilter->rec->interface) {
            $data->query->like('interfaces', "|{$interfaceId}|");
        }
    }
    
    
    /**
     * Добавя информация за класа в регистъра
     */
    public static function add($class, $title = false)
    {
        $class = cls::get($class);
        
        // Ако класът е нова версия на някой предишен, съществуващ,
        // отразяваме този факт в таблицата с класовете
        if (isset($class->oldClassName)) {
            $newClassName = cls::getClassName($class);
            $oldClassName = $class->oldClassName;
            
            if (!core_Classes::fetch("#name = '{$newClassName}'")) {
                if ($rec = core_Classes::fetch("#name = '{$oldClassName}'")) {
                    $rec->name = $newClassName;
                    self::save($rec);
                }
            }
        }
        
        $rec = new stdClass();
        
        $rec->interfaces = core_Interfaces::getKeylist($class);
        
        // Ако класа няма интерфейси, обаче съществува в модела,
        // затваряме го, т.е. няма да излиза като опция
        if (!$rec->interfaces) {
            $rec = core_Classes::fetch(array("#name = '[#1#]'", $name = cls::getClassName($class)));
            
            if ($rec) {
                $rec->interfaces = null;
                $rec->state = 'closed';
                core_Classes::save($rec);
            }
            
            return "<li class='debug-info'>Класът {$name} не поддържа никакви интерфейси</li>";
        }
        
        // Вземаме инстанция на core_Classes
        $Classes = cls::get('core_Classes');
        
        // Очакваме валидно име на клас
        expect($rec->name = cls::getClassName($class), $class);
        
        // Очакваме този клас да може да бъде зареден
        expect(cls::load($rec->name), $rec->name);
        
        $rec->title = $title ? $title : cls::getTitle($rec->name);
        
        $id = $rec->id = $Classes->fetchField("#name = '{$rec->name}'", 'id');
        
        $Classes->save($rec);
        
        if (!$id) {
            $res = "<li class='debug-new'>Класът {$rec->name} е добавен към мениджъра на класове</li>";
        } else {
            $res = "<li class='debug-notice'>Информацията за класа {$rec->name} бе обновена в мениджъра на класове</li>";
        }
        
        return $res;
    }
    
    
    /**
     * Връща опции за селект с устройствата, имащи определения интерфейс
     *
     * @param string $interfaces Имена на интерфейси, разделени с вертикална черта
     * @param string $titlePart  Коя част от записа на класа за се използва за заглавие
     *
     * @return array Масив с опции от вида classId => title
     */
    public static function getOptionsByInterface($interfaces, $titlePart = 'name')
    {
        $params = array($interfaces, $titlePart, core_Lg::getCurrent());
        
        return core_Cache::getOrCalc('getOptionsByInterface', $params, function ($params) {
            $interfaces = $params[0];
            $titlePart = $params[1];
            
            $cC = cls::get('core_Classes');
            
            $interfaceCond = '';
            
            if ($interfaces) {
                $interfacesArr = explode('|', $interfaces);
                
                $interfaceCondArr = array();
                
                foreach ($interfacesArr as $interface) {
                    $interface = trim($interface);
                    if (!$interface) {
                        continue;
                    }
                    
                    // Вземаме инстанция на core_Interfaces
                    $Interfaces = cls::get('core_Interfaces');
                    
                    $interfaceId = $Interfaces->fetchByName($interface);
                    
                    // Очакваме валиден интерфейс
                    expect($interfaceId);
                    
                    $interfaceCondArr[] = "#interfaces LIKE '%|{$interfaceId}|%'";
                }
                
                if (count($interfaceCondArr)) {
                    $interfaceCond = ' AND ' . '(' . implode(' OR ', $interfaceCondArr) . ')';
                }
            }
            
            $options = core_Classes::makeArray4Select($titlePart, "#state = 'active'" . $interfaceCond);
            
            if (is_array($options) && $titlePart == 'title') {
                foreach ($options as $cls => &$name) {
                    $name = core_Classes::translateClassName($name);
                }
            }
            
            return $options;
        });
    }
    
    
    /**
     * Помощна ф-я за превод на име на сложно име на клас
     *
     * @param string $name
     *
     * @return string $name;
     */
    public static function translateClassName($name)
    {
        $exp = explode('»', $name);
        if (count($exp) == 2) {
            $name = tr(trim($exp[0])) . ' » ' . tr(trim($exp[1]));
        } else {
            $name = tr($name);
        }
        
        return $name;
    }
    
    
    /**
     * Връща броя на класовете, които имплементират интерфейса
     *
     * @param $interface - Името или id' то на интерфейса
     *
     * @return int - Броя на класовете, които имплементират интерфейса
     */
    public static function getInterfaceCount($interface)
    {
        if (!is_numeric($interface)) {
            // Вземаме инстанция на core_Interfaces
            $Interfaces = cls::get('core_Interfaces');
            
            // id' то на интерфейса
            $interfaceId = $Interfaces->fetchByName($interface);
        } else {
            $interfaceId = $interface;
        }
        
        // Очакваме валиден интерфейс
        expect($interfaceId);
        
        $query = core_Classes::getQuery();
        $query->where("#state = 'active' AND #interfaces LIKE '%|{$interfaceId}|%'");
        
        return $query->count();
    }
    
    
    /**
     * Връща ид на клас по (име | инстанция | ид)
     *
     * @param mixed $class string (име на клас) или object (инстанция) или int (ид на клас)
     *
     * @return int ид на клас
     */
    public static function getId($class)
    {
        if (is_numeric($class)) {
            $classId = $class;
        } else {
            if (is_object($class)) {
                $className = $class->className;
            } else {
                $className = $class;
            }
            
            if (!count(self::$classes)) {
                self::loadClasses();
            }
            
            $classId = self::$classes[$className];
        }
        
        expect($classId, $class);
        
        return $classId;
    }
    
    
    /**
     * Връща името на класа, според неговото id
     */
    public static function getName($classId)
    {
        expect(is_numeric($classId));
        
        // Зареждаме кеша на класовете
        if (!count(self::$classes)) {
            self::loadClasses();
        }
        
        $className = self::$classes[$classId];
        
        return $className;
    }
    
    
    /**
     * Зарежда кеша на класовете
     */
    private static function loadClasses()
    {
        $dummy = '';
        $classes = core_Cache::getOrCalc(self::$classHashName, $dummy, function ($dummy) {
            $classes = array();
            $query = core_Classes::getQuery();
            while ($rec = $query->fetch("#state = 'active'")) {
                $classes[$rec->id] = $rec->name;
                $classes[$rec->name] = $rec->id;
            }
            
            return $classes;
        });
        
        self::$classes = $classes;
    }
    
    
    /**
     * Инвалидира кеша при обновяване на таблицата
     */
    public static function on_AfterDbTableUpdated($mvc)
    {
        self::$classes = array();
        $cache = cls::get('core_Cache');
        $cache->deleteData(md5(EF_DB_NAME . '|' . CORE_CACHE_PREFIX_SALT . self::$classHashName));
    }
    
    
    /**
     * Рутинен метод, който скрива класовете, които са от посочения пакет или няма код за тях
     */
    public static function deinstallPack($pack)
    {
        $query = self::getQuery();
        $preffix = $pack . '_';
        
        while ($rec = $query->fetch(array("#state = 'active' AND #name LIKE '[#1#]%'", $preffix))) {
            $rec->state = 'closed';
            core_CLasses::save($rec);
        }
        
        self::rebuild();
    }
    
    
    /**
     * Прецизира информацията за интерфейсите на всички 'активни' класове
     * Класовете за които няма съответстващ файл се затварят (стават не-активни)
     */
    public static function rebuild()
    {
        $query = self::getQuery();
        $res = '<li>Обновяване на информацията за класовете</li>';
        
        while ($rec = $query->fetch("#state = 'active'")) {
            $load = cls::load($rec->name, true);
            if ($load) {
                $inst = cls::get($rec->name);
            }
            if (!$load) {
                $rec->state = 'closed';
                self::save($rec, 'state');
                $res .= "<li style='color:red;'>Деактивиран беше класа {$rec->name} защото липсва кода му.</li>";
            } elseif ($inst->deprecated) {
                $res .= "<li style='color:green;'>Деактивиран беше класа {$rec->name} защото е пенсиониран.</li>";
                $rec->state = 'closed';
                self::save($rec, 'state');
            } else {
                core_Classes::add($rec->name);
            }
        }
        
        return $res;
    }
    
    
    /**
     * След подготовка на вербалните стойности
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        if ($fields['-list']) {
            $row->title = tr($row->title);
            
            if ($rec->state == 'active') {
                try {
                    $row->interfaces = $mvc->getVerbalInterfaces($rec);
                } catch (core_exception_Expect $e) {
                    $row->interfaces = "<span style='color:red;'>Error</span>";
                }
            }
        }
    }
    
    
    /**
     * Подготвя интерфейсите на класа за показване в лист изгледа
     * Ако класа не имплементира някои методи на даден итнерфейс, то на
     * итнерфейса има хинт за това кои методи не са имплементирани
     *
     * @param stdClass $rec
     *
     * @return string $verbalInterfaces
     */
    private function getVerbalInterfaces($rec)
    {
        $verbalInterfaces = '';
        if (!cls::load($rec->name, true)) {
            
            return "<span class='red'>Липсва кода на класа</span>";
        }
        
        $ClassMethods = cls::getAccessibleMethods($rec->name);
        $intArray = keylist::toArray($rec->interfaces);
        
        if (count($intArray)) {
            foreach ($intArray as $id) {
                $intName = core_Interfaces::fetchField($id, 'name');
                if (!self::$interfaceMehods[$intName]) {
                    self::$interfaceMehods[$intName] = cls::getAccessibleMethods($intName);
                }
                
                if (!self::$staticInterfaceMehods[$intName]) {
                    self::$staticInterfaceMehods[$intName] = cls::getAccessibleMethods($intName, true);
                }
                
                $methods = self::$interfaceMehods[$intName];
                
                // Намират се всички неимплементирани методи от класа
                $notImplemented = array_diff_assoc($methods, $ClassMethods);
                $verbalInterfaces .= $verbalInterfaces ? ',' : '';
                
                if (self::$staticInterfaceMehods[$intName]) {
                    $hint = implode(', ', self::$staticInterfaceMehods[$intName]);
                    $hint = 'Статични методи: ' . $hint;
                    $verbalInterfaces .= " <span class='interface-container not-implemented' style='color:red;'  title='{$hint}'>{$intName}</span>";
                } elseif (!count($notImplemented)) {
                    $verbalInterfaces .= " <span class='interface-container not-implemented' style='color:green;'>{$intName}</span>";
                } else {
                    $hint = implode(', ', $notImplemented);
                    $verbalInterfaces .= " <span class='interface-container implemented' style='color:orange;' title='{$hint}'>{$intName}</span>";
                }
            }
        }
        
        return $verbalInterfaces;
    }
}
