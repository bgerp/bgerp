<?php

/**
 * Максимално време за еднократно фетчване на писма
 */
defIfNot('EMAIL_MAX_FETCHING_TIME', 30);


/**
 * Максималната разрешена памет за използване
 */
defIfNot('EMAIL_MAX_ALLOWED_MEMORY', '800M');


/**
 * Шаблон за име на папките, където отиват писмата от дадена държава и неподлежащи на
 * по-адекватно сортиране
 */
defIfNot('EMAIL_UNSORTABLE_COUNTRY', 'Несортирани - %s');


/**
 * Максималното време за изчакване на буфера
 */
defIfNot('EMAIL_POP3_TIMEOUT', 2);


/**
 * Максималната големина на файловете, които ще се приемат за CID
 * 10kB
 */
defIfNot('EMAIL_MAXIMUM_CID_LEN', 10240);


/**
 * Ниво за score на SpamAssassin, над което писмото се обявява за твърд СПАМ
 */
defIfNot('SPAM_SA_SCORE_LIMIT', 7);

/**
 * class email_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с 'email'
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'email_Incomings';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Електронна поща";
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'fileman=0.1,doc=0.1';
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    
            // Максимално време за еднократно фетчване на писма
            'EMAIL_MAX_FETCHING_TIME' => array ('int', 'mandatory'),
    
            // Максималното време за изчакване на буфера
            'EMAIL_POP3_TIMEOUT'  => array ('int', 'mandatory'),
            
            // Максималната разрешена памет за използване
            'EMAIL_MAX_ALLOWED_MEMORY' => array ('varchar', 'mandatory'),

            // Шаблон за име на папки
            'EMAIL_UNSORTABLE_COUNTRY' => array ('varchar', 'mandatory'),

            // Максималната големина на файловете, които ще се приемат за CID
            'EMAIL_MAXIMUM_CID_LEN' => array ('int'),
            
            // Ниво за score на SpamAssassin, над което писмото се обявява за твърд СПАМ
            'SPAM_SA_SCORE_LIMIT' => array ('int'),

            
        );
        
        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'email_Incomings',
            'email_Outgoings',
            'email_Inboxes',
            'email_Accounts',
            'email_Router',
            'email_Addresses',
            'email_FaxSent',
            'email_Filters',
            'email_Returned',
            'email_Receipts',
            'email_Spam',
            'email_Fingerprints',
            'email_Unparsable',
            'email_Salutations',
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'email, fax';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(1.23, 'Документи', 'Имейли', 'email_Outgoings', 'default', "admin, email, fax, user"),
        );
        
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
       
        $html = parent::install();
            
        //инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('Email', 'Прикачени файлове в имейлите', NULL, '104857600', 'user', 'user');
             
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме
        $html .= $Plugins->installPlugin('UserInbox', 'email_UserInboxPlg', 'core_Users', 'private');
        
        // Инсталираме плъгина за преобразуване на имейлите в линкове
        $html .= $Plugins->installPlugin('EmailToLink', 'email_ToLinkPlg', 'type_Email', 'private');
        
        //
        // Инсталиране на плъгин за автоматичен превод на входящата поща
        //
        $html .= $Plugins->installPlugin('Email Translate', 'email_plg_IncomingsTranslate', 'email_Incomings', 'private');
        
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