<?php


/**
 * Класа, който ще се използва за конвертиране
 */
defIfNot('LEGALACT_DOCS_ROOT', 'c:/test/docs/');


 
/**
 *
 * @category  vendors
 * @package   legalact
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2012 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class legalact_Setup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Актове на българските съдилища";
    

    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'legalact_Acts';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    

    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    
        // Кой клас да се използва за конвертиране на офис документи
        'LEGALACT_DOCS_ROOT' => array ('varchar', 'mandatory'),
    );
    
        
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'legalact_Acts',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        // Роля за power-user на този модул
        $role = 'legal';        
        $html .= core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(3, 'Производство', 'Съдилища', 'legalact_Acts', 'default', 'ceo,admin,legal');

        return $html;
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