<?php

/**
 * Текст за отписване от информационните съобщение
 */
defIfNot('BGERP_BLAST_UNSUBSCRIBE', 'Искате ли да премахнете имейл-а си от листата за получаване на информационни съобщения?');


/**
 * Текст, който се показва, ако не може да се намери имейл адреса в системата
 */
defIfNot('BGERP_BLAST_NO_MAIL', 'Не може да се намери имейл адреса Ви.');


/**
 * Teкст, който се показва когато премахнем имейл-а от блокираните
 */
defIfNot('BGERP_BLAST_SUCCESS_ADD', 'Имейлът Ви е добавен в списъка за информационни съобщения. Искате ли да го премахнете?');


/**
 * Текст, който се показва когато добавим имейл-а в списъка на блокираните имейли
 */
defIfNot('BGERP_BLAST_SUCCESS_REMOVED', 'Имейлът Ви е премахнат от списъка за информационни съобщения. Искате ли да добавите имейл-а си в листата?');

/**
 * class blast_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с 'blast'
 *
 *
 * @category  bgerp
 * @package   blast
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class blast_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'blast_Lists';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Масово разпращане на имейл-и, sms-и, писма, ...";
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        
           // Текст за отписване от информационните съобщение
           'BGERP_BLAST_UNSUBSCRIBE' => array ('text'),
    
         
           // Текст, който се показва, ако не може да се намери имейл адреса в системата
           'BGERP_BLAST_NO_MAIL'   => array ('text'),
    
             
           // Teкст, който се показва когато премахнем имейл-а от блокираните
           'BGERP_BLAST_SUCCESS_ADD'   => array ('text'),
    
            
           // Текст, който се показва когато добавим имейл-а в списъка на блокираните имейли
           'BGERP_BLAST_SUCCESS_REMOVED'   => array ('text')
        );
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'blast_Lists',
            'blast_ListDetails',
            'blast_Emails',
            'blast_Blocked',
            'blast_ListSend',
            'blast_Letters',
            'blast_LetterDetails'
        );
        
        // Роля ръководител на организация 
        // Достъпни са му всички папки и документите в тях
        $role = 'blast';
        $html .= core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('csvContacts', 'CSV контактни данни', 'csv,txt,text,', '10MB', 'user', 'ceo');
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(1, 'Указател', 'Разпращане', 'blast_Lists', 'default', "admin, ceo, {$role}");
        
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
