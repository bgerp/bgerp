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
        
        
        // Създаване моделите в базата данни
        $instances = array();
        foreach (arr::make($this->managers) as $manager) {
            if($manager == 'core_Plugins' && $PluginsGlobal) {
                $instances[$manager] = $PluginsGlobal;
            } else {
                $instances[$manager] = &cls::get($manager);
            }
            $html .= $instances[$manager]->setupMVC();
        }
        

        // Добавяме връзките към модула в менюто
        if(count($this->menuItems)) {
            $Menu = cls::get('bgerp_Menu');
            foreach($this->menuItems as $item) {
                $row     = $item['row'] ? $item['row'] : $item[0];
                $menu    = $item['menu'] ? $item['menu'] : $item[1];
                $subMenu = $item['subMenu'] ? $item['subMenu'] : $item[2];
                $ctr     = $item['ctr'] ? $item['ctr'] : $item[3];
                $act     = $item['act'] ? $item['act'] : $item[4];
                $roles   = $item['roles'] ? $item['roles'] : $item[5];

                $html .= $Menu->addItem($row, $menu, $subMenu, $ctr, $act, $roles);
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
     * Извиква метода '->loadInitData' на мениджърите, които го имат
     */
    public function loadSetupData()
    {
        // Създаване моделите в базата данни
        $instances = array();
        $htmlRes = '';
        foreach (arr::make($managers) as $man) {
            $instances[$man] = &cls::get($man);
            if(method_exists($instances[$man], 'loadSetupData')) {
                $htmlRes .= $instances[$man]->loadSetupData();
            }
        }

        return $htmlRes;
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
}