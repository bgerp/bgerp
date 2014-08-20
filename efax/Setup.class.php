<?php


/**
 * Факс на изпращача.
 * Трябва да е дефиниран, като допустим в efax.com, за да може да изпращаме факс
 */
defIfNOt('EFAX_SENDER_BOX', '');


/**
 * Максималният допустим брой на прикачените файлове и документи при изпращане на факсове
 */
defIfNot('MAX_ALLOWED_ATTACHMENTS_IN_FAX', 10);


/**
 * Изпращане на факс чрез efax
 *
 * @category   vendors
 * @package    efax
 * @author     Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 3
 */
class efax_Setup extends core_ProtoSetup
{
    /**
     *  Версия на пакета
     */
    var $version = '0.1';
    
  
   /**
     * Описание на модула
     */
    var $info = "Изпращане на факс, чрез eFax";

    
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
        $html = core_Classes::add('efax_Sender');
                
        return $html;
    }
    
    
    /**
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}