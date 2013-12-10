<?php


/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с label
 *
 * @category  bgerp
 * @package   label
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class label_Setup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'label_Labels';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Отпечатване на етикети";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {   
        // Инсталиране на мениджърите
        $managers = array(
            'label_Labels',
            'label_Templates',
            'label_TemplateFormats',
            'label_Counters',
            'label_CounterItems',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        // Добавяме роля
        $html .= core_Roles::addRole('label') ? "<li style='color:green'>Добавена е роля <b>label</b></li>" : '';
        
        // Добавяме роля за master
        $html .= core_Roles::addRole('labelMaster', 'label') ? "<li style='color:green'>Добавена е роля <b>labelMaster</b></li>" : '';
        
        // Добавяме меню
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(3.66, 'Производство', 'Етикиране', 'label_Labels', 'default', "label, admin, ceo");
        
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
