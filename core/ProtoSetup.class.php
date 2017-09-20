<?php



/**
 * class core_ProtoSetup
 *
 * Протопит на сетъп-клас за модул
 *
 *
 * @category  bgerp
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_ProtoSetup
{
    
    /**
     * Версия на пакета
     */
    public $version;
    
    
    /**
     * Мениджър - входна точка на модула
     */
    public $startCtr;
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    public $depends;
    
    
    /**
     * Описание на модула
     */
    public $info;
    

    /**
     * Описание на конфигурационните константи за този модул
     */
    protected $configDescription = array();
    

    /**
     * Стойности на константите за конфигурацията на пакета
     */
    static $conf = array();


    /**
     * Пътища до папки, които трябва да бъдат създадени
     */
    protected $folders = array();


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array();
    

    /**
     * Роли за достъп до модула
     */
    var $roles;
    

    /**
     * Дефинирани класове, които имат интерфейси
     */
    protected $defClasses;


    /**
     * Връзки от менюто, сочещи към модула
     * array(ред в менюто, Меню, Под-меню, Мениджър, Екшън, Роли за достъп)
     */
    var $menuItems = array();


    /**
     * Масив с настойки за Cron
     *
     * @var array
     */
    protected $cronSettings;

    
    /**
     * Дали пакета е системен
     */
    public $isSystem = FALSE;
    
    
    /**
     * Дали да се пропусне, като избор за инсталиране
     */
    public $noInstall = FALSE;
    
    
    /**
     * Дали се спира поддръжката на този пакет
     */
    public $deprecated = FALSE;
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {  
        // Взимаме името на пакета
        $packName = $this->getPackName();
        
        // Създаване моделите в базата данни
        $instances = array();

        // Масив с класовете, които имат интерфейси
        $this->defClasses = arr::make($this->defClasses, TRUE);

        foreach (arr::make($this->managers) as $manager) {

            // Ако мениджърът е миграция - изпълняваме я еднократно
            if (stripos($manager, 'migrate::') === 0) {
                
                list($migrate, $method) = explode('::', $manager);
                
                $html .= $this->callMigrate($method, $packName);
                
                continue;
            }

            $instances[$manager] = &cls::get($manager);

            // Допълваме списъка, защото проверяваме дали мениджърите имат интерфейси
            $this->defClasses[$manager] = $manager;

            expect(method_exists($instances[$manager], 'setupMVC'), $instances, $manager);

            $html .= $instances[$manager]->setupMVC();
        }
        
        // Създава посочените директории
        $html .= core_Os::createDirectories($this->folders);
        
        // Добавяне на класове, поддържащи интерфейси в регистъра core_Classes
        $html .= $this->setClasses();


        // Добавяме дефинираните роли в модула
        foreach(arr::make($this->roles) as $role) {
            $html .= core_Roles::addOnce($role);
        }

        return $html;
    }
    
    
    /**
     * Пуска подадена миграция от съответния пакет, ако преди това не е била пусната
     * 
     * @param string $method
     * @param string $packName
     * 
     * @return string
     */
    public function callMigrate($method, $packName)
    {
        Mode::push('isMigrate', TRUE);
        
        // Ключ в настойките на пакета `core` под който се пази изпълнението на миграцията
        $key = "migration_{$packName}_{$method}";
        
        if(!core_Packs::getConfigKey('core', $key)) {
            //try {
                $res = call_user_func(array($this, $method));
                core_Packs::setConfig('core', array($key => TRUE));
                if($res) {
                    $html = $res;
                } else {
                    $html = "<li class='debug-new'>Миграцията {$packName}::{$method} беше приложена успешно</li>";
                }
            //} catch(ErrorException $e) {
               // $html = "<li class='debug-error'>Миграцията {$packName}::{$method} не беше успешна</li>";
               // reportException($e);
           // }
        }
        
        Mode::pop('isMigrate', TRUE);
        
        return $html;
    }

    
    /**
     * Зареждане на първоначалните данни
     * Извиква метода '->loadSetupData()' на мениджърите, които го имат
     */
    public function loadSetupData($itr = '')
    {
        $htmlRes = '';
        
        $method = 'loadSetupData' . $itr;


        // Зареждане на данните в моделите
        $instances = array();
        foreach (arr::make($this->managers) as $man) {
            if (stripos($man, 'migrate::') === 0) {
                continue;
            }
            $instances[$man] = &cls::get($man);
            if(method_exists($instances[$man], $method)) {
                $htmlRes .= $instances[$man]->{$method}();
            }
        }
        
        // Нагласяване на Крон
        $htmlRes .= $this->setCron();

        // Добавяне на елементи в Менюто
        $htmlRes .= $this->setMenuItems();

        return $htmlRes;
    }
    
    
    /**
     * Връща CSS файлове за компактиране
     * 
     * @return string
     */
    public function getCommonCss()
    {
        
        return $this->preparePacksPath($this->getPackName(), $this->commonCSS);
    }
    
    
    /**
     * Връща JS файлове за компактиране
     * 
     * @return string
     */
    public function getCommonJs()
    {
        
        return $this->preparePacksPath($this->getPackName(), $this->commonJS);
    }
    
    
    /**
     * Замества зададените плейсхолдери в стринга с конфигурационната им стойност
     * 
     * @param $packName
     * @param $pathStr
     * 
     * @return string
     */
    public static function preparePacksPath($packName, $pathStr)
    {
        if (!trim($pathStr)) return $pathStr;
        
        // Хващаме всички плейсхолдери
        preg_match_all('/\[\#(.+?)\#\]/', $pathStr, $matches);
        
        // Ако няма плейсхолдер
        if (!$matches[0]) return $pathStr;
        
        foreach ((array)$matches[1] as $key => $constName) {
            
            // Ако е подаден и пакета
            if (strpos($constName, '::')) {
                
                // Вземаме пакета за конфигурацията от константата
                list($confPackName, $constName) = explode('::', $constName);
                $conf = core_Packs::getConfig($confPackName);
            } else {
                $conf = core_Packs::getConfig($packName);
            }
            
            // Заместваме плейсхолдерите
            $pathStr = str_replace($matches[0][$key], $conf->$constName, $pathStr);
        }
        
        return $pathStr;
    }


    /**
     * Връща името на пакета, за когото е този сетъп
     *
     * @return $string
     */
    public static function getPackName()
    {
        list($packName, ) = explode("_", get_called_class(), 2);
        
        return $packName;
    }


    /**
     * Връща конфигурацията на пакета в който се намира Setup-а
     *
     * @return array
     */
    public static function getConfig()
    {
        $packName = self::getPackName();
        if(!isset(self::$conf[$packName])) {
            self::$conf[$packName] = core_Packs::getConfig($packName);
        }
        
        return self::$conf[$packName];
    }


    /**
     * Връща стойността на посочената константа
     * 
     * @param string $name
     *
     * @return mixed
     */
    public static function get($name, $absolute = FALSE, $userId = NULL)
    {
        if(!$absolute) {
            $prefix = strtoupper(self::getPackName()) . '_';
        }

        $name = $prefix . $name;
        
        if($userId > 0) {
            core_Users::sudo($userId);
        }

        $conf = self::getConfig();
        $res = $conf->{$name};
        
        if($userId > 0) {
            core_Users::exitSudo();
        }
        
        return $res;
    }
    
    
    /**
     * Проверяваме дали всичко е сетнато, за да работи пакета
     * Ако има грешки, връщаме текст
     */
    public function checkConfig()
    {
        return NULL;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
    

    /**
     * Добавя дефинираните класове в модела за класове, поддържащи интерфейси
     *
     * @return string
     */
    protected function setClasses()
    {
        $classes = arr::make($this->defClasses);

        foreach (arr::make($this->managers) as $manager) {

            // Ако менидръжит е миграция - изпълняваме я еднократно
            if (stripos($manager, 'migrate::') === 0) continue;
            $classes[$manager] = $manager;
        }

        $res = '';

        foreach($classes as $cls) {
            $res .= core_Classes::add($cls);
        }

        return $res;
    }


    /**
     * Функция, която добавя настройките за Cron
     */
    protected function setCron()
    {
        if(is_array($this->cronSettings) && count($this->cronSettings)) {
            
            if(!is_array($this->cronSettings[0])) {
                $this->cronSettings = array($this->cronSettings);
            }
            
            $res = '';

            foreach($this->cronSettings as $setting) {
                $res .= core_Cron::addOnce($setting);
            }
        }

        return $res;
    }


    /**
     * Добавяне на елементите на менюто за този модул
     */
    protected function setMenuItems()
    {
        $res = '';

        if(count($this->menuItems)) { 
            
            $conf = $this->getConfig();        
            
            // Името на пакета
            $packName = $this->getPackName();

            // 3-те имена на константите за менюто
            $constPosition = strtoupper($packName). "_MENU_POSITION";
            $constMenuName = strtoupper($packName). "_MENU";
            $constSubMenu = strtoupper($packName). "_SUB_MENU";
            $constView = strtoupper($packName). "_VIEW";

            foreach($this->menuItems as $id => $item) {

            	// задаваме позицията в менюто
            	// с приоритет е от конфига
            	if ($conf->{$constPosition."_".$id}) {
            		$row = $conf->{$constPosition."_".$id};
            	} elseif ($item['row']) {
            		$row = $item['row'];
            	} elseif ($item[0]) {
            		$row = $item[0];
            	} else {
                    expect($row);
                }
            
            	// задаваме името на менюто
            	// с приоритет е от конфига
            	if ($conf->{$constMenuName."_".$id}) {
            		$menu = $conf->{$constMenuName."_".$id};
            	} elseif ($item['menu']) {
            		$menu = $item['menu'];
            	} elseif ($item[1]) {
            		$menu = $item[1];
            	} else {
                    expect($menu);
                }
            	
            	// задаваме името на подменюто
            	// с приоритет е от конфига
            	if ($conf->{$constSubMenu."_".$id}) {
            		$subMenu = $conf->{$constSubMenu."_".$id};
            	} elseif ($item['subMenu']) {
            		$subMenu = $item['subMenu'];
            	} elseif ($item[2]) {
            		$subMenu = $item[2];
            	}

                $ctr     = $item['ctr'] ? $item['ctr'] : $item[3];
                $act     = $item['act'] ? $item['act'] : $item[4];
                $roles   = $item['roles'] ? $item['roles'] : $item[5];
                
	            // ако искаме това меню да не е видимо, го изтриваме
                if ($conf->{$constView."_".$id} === 'no')  { 
	        	
		        	$query = bgerp_Menu::getQuery();
		        	
			        $del = $query->delete(array("#ctr = '[#1#]' AND #act = '[#2#]' AND #menu = '[#3#]' AND #subMenu = '[#4#]' AND #createdBy = -1", $ctr, $act, $menu, $subMenu));
                    if($del) {
                        $res .= "<li class='debug-update'>Премахнат е елемента на менюто <b>{$menu} » {$subMenu}</b></li>";
                    }
			       
	        	} else {
	        	    // Добавя елемента на менюто
                	$res .= bgerp_Menu::addOnce($row, $menu, $subMenu, $ctr, $act, $roles);
	        	}
	        	
	        	$cacheKey = 'menuObj_' . core_Lg::getCurrent();
			        
			    core_Cache::remove('Menu', $cacheKey);
			        
	        	unset($row);
                unset($menu);
                unset($subMenu);
            }
        }

        return $res;
    }
    
    
    /**
     * Връща описанието на web-константите
     *
     * @return array
     */
    public function getConfigDescription() 
    {
        $description = $this->configDescription;
                              
        // взимаме текущото зададено меню
        if ($this->menuItems && count($this->menuItems)) { 
            
            // Името на пакета
            $packName = $this->getPackName();

            // три имена на променливи за менюто
            $position = strtoupper($packName). "_MENU_POSITION";
            $menuName = strtoupper($packName). "_MENU";
            $subMenu = strtoupper($packName). "_SUB_MENU";
            $view = strtoupper($packName). "_VIEW";
            
        	$menu = $this->menuItems;
        	
        	if (is_array($menu)) {
        		foreach($menu as $id=>$m) {
        			
        			// дефинираме константи с определените имена
        			defIfNot($position."_".$id, $m[0]);
        			defIfNot($menuName."_".$id, $m[1]);
        			defIfNot($subMenu."_".$id, $m[2]);
        			defIfNot($view."_".$id, 'yes');
        			
        		    $numbMenu =  $id + 1;

        		    if($numbMenu == 1) {
                        $numbMenu = '';
                    } else {
                        $numbMenu = " ({$numbMenu})";
                    }

        			$description[$position."_".$id] = array ('double', 'caption=Меню '.$numbMenu.'->Позиция');
        			$description[$menuName."_".$id] = array ('varchar', 'caption=Меню '.$numbMenu.'->Група');
        			$description[$subMenu."_".$id] = array ('varchar', 'caption=Меню '.$numbMenu.'->Подменю');
        			$description[$view."_".$id] = array ('enum(yes=Да, no=Не),row=2', 'caption=Меню '.$numbMenu.'->Показване,maxRadio=2');
        		}
        	} 
            
        }
        
        // За всеки случай нулираме, за да не се обърка някой по-нататък
        if(is_array($description) && !count($description)) {
            $description = NULL;
        }

        return $description;
    }
}