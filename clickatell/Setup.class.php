<?php


/**
 * URL за изпращане на СМС-и през clickatell
 */
defIfNot('CLICKATELL_URL', 'https://api.clickatell.com/http/sendmsg?callback=2&api_id=[#APIID#]&user=[#USERNAME#]&password=[#PASSWORD#]&to=[#PHONE#]&text=[#MESSAGE#]&from=[#FROM#]');


/**
 * URL за проверка на връзката с clickatell
 */
defIfNot('CLICKATELL_CHECK_URL', 'https://api.clickatell.com/http/auth?api_id=[#APIID#]&user=[#USERNAME#]&password=[#PASSWORD#]');


/**
 * API_ID
 */
defIfNot('CLICKATELL_APIID', '');


/**
 * Потребителско име
 */
defIfNot('CLICKATELL_USERNAME', '');


/**
 * Парола
 */
defIfNot('CLICKATELL_PASSWORD', '');


/**
 * Инсталиране на clickatell
 *
 * @category  vendors
 * @package   clickatell
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class clickatell_Setup extends core_ProtoSetup
{ 
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "SMS изпращане чрез Clickatell";
    
    
    /**
     * 
     */
    var $configDescription = array (
        'CLICKATELL_URL' => array('url'),
        'CLICKATELL_CHECK_URL' => array('url'),
        'CLICKATELL_APIID' => array('varchar', 'mandatory'),
        'CLICKATELL_USERNAME' => array('varchar', 'mandatory'),
        'CLICKATELL_PASSWORD' => array('varchar', 'mandatory'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'clickatell_SMS',
        );
   
    
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