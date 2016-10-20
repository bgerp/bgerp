<?php


/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с incoming модула
 *
 *
 * @category  bgerp
 * @package   incoming
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class incoming_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'incoming_Documents';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Входящи документи";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'incoming_Documents',
        );
    

    /**
     * Роли за достъп до модула
     */
    //var $roles = '';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(1.24, 'Документи', 'Входящи', 'incoming_Documents', 'default', "ceo"),
        );
    
        
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Добавяне на плъгина за създаване на входящи документи
        $html .= $Plugins->installPlugin('Създаване на входящ документ', 'incoming_CreateDocumentPlg', 'fileman_Files', 'private');  
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}