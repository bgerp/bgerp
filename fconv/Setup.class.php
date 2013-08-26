<?php


/**
 * Пътя до директорията с временните файлове
 */
defIfNot('FCONV_TEMP_PATH', EF_TEMP_PATH . "/fconv/");


/**
 * Конвертиране на файлове
 *
 * @category  vendors
 * @package   fconv
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fconv_Setup extends core_ProtoSetup 
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'fconv_Processes';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Конвертиране на файлове";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    
        // 
       'FCONV_TEMP_PATH'   => array ('varchar', 'caption=Директорията с временните файлов->Път до директорията')
        );
        
        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'fconv_Processes',
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