<?php

/**
 * Задаване на основна валута
 */
defIfNot('BGERP_BASE_CURRENCY', 'BGN');



/**
 * class core_ProtoSetup
 *
 * Протопит на сетъп-клас за модул
 *
 *
 * @category  bgerp
 * @package   currency
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
    var $version;
    
    
    /**
     * Мениджър - входна точка на модула
     */
    var $startCtr;
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends;
    
    
    /**
     * Описание на модула
     */
    var $info;
    

    /**
     * Описание на конфигурационните константи за този модул
     */
    var $configDescription = array(
            
         
        );
    

    /**
     * Пътища до папки, които трябва да бъдат създадени
     */
    var $folders;


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
        // Тук се изреждат мениджърите, които участват в модула
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles;
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
         //   array(ред в менюто, Меню, Под-меню, Мениджър, Екшън, Роли за достъп),
        );


    /**
     * Инсталиране на пакета
     */
    public function install()
    {   
        global $PluginsGlobal;
        
        // Добавяме дефинираните роли
        foreach(arr::make($this->roles) as $role) {
            $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        }
        
        // Вземаме името на пакета
        list ($packName, ) = explode("_", cls::getClassName($this), 2);
        
        // Създаване моделите в базата данни
        $instances = array();
        foreach (arr::make($this->managers) as $manager) {

            // Ако менидръжит е миграция - изпълняваме я еднократно
            if (stripos($manager, 'migrate::') === 0) {
                list($migrate, $method) = explode('::', $manager);
                
                // Ключ в настойките на пакета `core` под който се пази изпълнението на миграцията
                $key = "migration_{$packName}_{$method}";

                if(!core_Packs::getConfigKey('core', $key)) {
                    try {
                        $res = call_user_func(array($this, $method));
                        core_Packs::setConfig('core', array($key => TRUE));
                        if($res) {
                            $html .= $res;
                        } else {
                            $html .= "<li style='color:green;'>Миграцията {$packName}::{$method} беше приложена успешно</li>";
                        }
                    } catch (Exception $е) {
                        $html .= "<li style='color:red;'>Миграцията {$packName}::{$method} не беше успешна</li>";
                    }
                }

                continue;
            }

            if($manager == 'core_Plugins' && $PluginsGlobal) {
                $instances[$manager] = $PluginsGlobal;
            } else {
                $instances[$manager] = &cls::get($manager);
            }
            
            expect(method_exists($instances[$manager], 'setupMVC'), $instances, $manager);

            $html .= $instances[$manager]->setupMVC();
        }
        
        
        // конфигурацията на пакета
        $conf = core_Packs::getConfig($packName);
        
        // 3-те имена на константите за менюто
        $constPosition = strtoupper($packName). "_MENU_POSITION";
        $constMenuName = strtoupper($packName). "_MENU";
        $constSubMenu = strtoupper($packName). "_SUB_MENU";
        $constView = strtoupper($packName). "_VIEW";
        
        // Добавяме връзките към модула в менюто
        if(count($this->menuItems)) { 
            
        
            foreach($this->menuItems as $id=>$item) {
            	
            	$Menu = cls::get('bgerp_Menu');  
            	       	
            	// задаваме позицията в менюто
            	// с приоритет е от конфига
            	if ($conf->{$constPosition."_".$id}) {
            		$row = $conf->{$constPosition."_".$id};
            	} elseif ($item['row']) {
            		$row = $item['row'];
            	} elseif ($item[0]) {
            		$row = $item[0];
            	}
            
            	// задаваме името на менюто
            	// с приоритет е от конфига
            	if ($conf->{$constMenuName."_".$id}) {
            		$menu = $conf->{$constMenuName."_".$id};
            	} elseif ($item['menu']) {
            		$menu = $item['menu'];
            	} elseif ($item[1]) {
            		$menu = $item[1];
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
		        	
			        $html .= $query->delete(array("#ctr = '[#1#]' AND #act = '[#2#]' AND #menu = '[#3#]' AND #subMenu = '[#4#]' AND #createdBy = -1", $ctr, $act, $menu, $subMenu));
			       
	        	} else {
	        	
                	$html .= $Menu->addItem($row, $menu, $subMenu, $ctr, $act, $roles);
	        	}
	        	
	        	$cacheKey = 'menuObj_' . core_Lg::getCurrent();
			        
			    core_Cache::remove('Menu', $cacheKey);
			        
	        	unset($row);
                unset($menu);
                unset($subMenu);
            }
        }

        // Създава, ако е необходимо зададените папки
        foreach(arr::make($this->folders) as $path) {
            if(!is_dir($path)) {
                if(!mkdir($path, 0777, TRUE)) {
                    $html .= "<li style='color:red;'>Не може да се създаде директорията: <b>{$path}</b>";
                } else {
                    $html .= "<li style='color:green;'>Създадена е директорията: <b>{$path}</b>";
                }
            } else {
                $html .= "<li>Съществуваща от преди директория: <b>{$path}</b>";
            }
            
            if(!is_writable($path)) {
                $html .= "<li style='color:red;'>Не може да се записва в директорията <b>{$path}</b>";
            }
        }

        
        return $html;
    }

    
    /**
     * Зареждане на първоначалните данни
     * Извиква метода '->loadSetupData()' на мениджърите, които го имат
     */
    public function loadSetupData()
    {
        // Създаване моделите в базата данни
        $instances = array();
        $htmlRes = '';
        foreach (arr::make($this->managers) as $man) {
            if (stripos($man, 'migrate::') === 0) {
                continue;
            }
            $instances[$man] = &cls::get($man);
            if(method_exists($instances[$man], 'loadSetupData')) {
                $htmlRes .= $instances[$man]->loadSetupData();
            }
        }

        return $htmlRes;
    }
    
    
    /**
     * Връща масив с css и js файловете дефинирани в commonJS и commonCSS
     * 
     * @return array - Двумерен масив с 'css' и 'js' пътищатата
     */
    public function getCommonCssAndJs()
    {
        $resArr = array();
        // Добавяме зададените CSS файлове към главния
        if ($this->commonCSS) {
            $resArr['css'] = arr::make($this->commonCSS, TRUE);
        }
        if ($this->commonJS) {
            $resArr['js'] = arr::make($this->commonJS, TRUE);
        }
        
        return $resArr;
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
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    /**
     * Конструктор
     */
    function core_ProtoSetup() 
    {
        // името на пакета
        list ($packName, ) = explode("_", cls::getClassName($this), 2);
        
        // три имена на променливи за менюто
        $position = strtoupper($packName). "_MENU_POSITION";
        $menuName = strtoupper($packName). "_MENU";
        $subMenu = strtoupper($packName). "_SUB_MENU";
        $view = strtoupper($packName). "_VIEW";       
        
        // взимаме текущото зададено меню
        if (count($this->menuItems)) { 
        	$menu = $this->menuItems;
        	
        	if (is_array($menu)) {
        		foreach($menu as $id=>$m) {
        			
        			// дефинираме константи с определените имена
        			defIfNot($position."_".$id, $m[0]);
        			defIfNot($menuName."_".$id, $m[1]);
        			defIfNot($subMenu."_".$id, $m[2]);
        			defIfNot($view."_".$id, 'yes');
        			
        		    $numbMenu =  $id + 1;
        		    
        			$this->configDescription[$position."_".$id] = array ('double', 'caption=Меню '.$numbMenu.'->Позиция');
        			$this->configDescription[$menuName."_".$id] = array ('varchar', 'caption=Меню '.$numbMenu.'->Меню');
        			$this->configDescription[$subMenu."_".$id] = array ('varchar', 'caption=Меню '.$numbMenu.'->Подменю');
        			$this->configDescription[$view."_".$id] = array ('enum(yes=Да, no=Не),row=2', 'caption=Меню '.$numbMenu.'->Видимо,maxRadio=2');
        			        			
        		}
        	}
        }
    }
}