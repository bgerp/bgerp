<?php



/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с 'fax'
 *
 *
 * @category  bgerp
 * @package   fax
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fax_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'fax_Incomings';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Факсове";
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'fileman=0.1,doc=0.1';
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
        	'fax_Incomings',
            'fax_Outgoings',
            'fax_Router',
            'fax_Inboxes',
        );
        
        // Роля ръководител на организация 
        // Достъпни са му всички папки и документите в тях
        $role = 'fax';
        $html .= core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        //инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('Fax', 'Прикачени файлове във факсовете', NULL, '104857600', 'user', 'user');
                
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(1, 'Документи', 'Факсове', 'fax_Incomings', 'default', "user");
                
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