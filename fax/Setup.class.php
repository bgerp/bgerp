<?php


/**
 * Факс на изпращача.
 * Трябва да е дефиниран, като допустим в efax.com, за да може да изпращаме факс
 */
defIfNOt('FAX_SENDER_BOX', 'team@efax.bgerp.com');

/**
 * Максималният допустим брой на прикачените файлове и документи при изпращане на факсове
 */
defIfNot(MAX_ALLOWED_ATTACHMENTS_IN_FAX, 10);

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
    var $startCtr = 'fax_Outgoings';
    
    
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
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        
           'FAX_SENDER_BOX' => array ('email', 'mandatory'),
    
           'MAX_ALLOWED_ATTACHMENTS_IN_FAX'   => array ('int')
    
    
        );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'fax_Outgoings',
            'fax_Sent'
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
        $html .= $Menu->addItem(1, 'Документи', 'Факсове', 'fax_Outgoings', 'default', "user");

        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за изпращане на факсове
        $saved = $Plugins->installPlugin('EFax', 'fax_EFaxPlg', 'fax_Sent', 'private', 'stopped');
        if ($saved) {
            $state = 'Спряно';
        } else {
            $state = 'Активно';
        }
        $html .= "<li>Закачане на fax_EFaxPlg към fax_Sent - ({$state})";
        
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