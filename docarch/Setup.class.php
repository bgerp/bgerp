<?php


/**
 * class docarch_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със архивирането
 * и съхранението на документи
 *
 *
 * @category  bgerp
 * @package   docarch
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class docarch_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'docarch_Movement';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Archiving';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'docarch_Archives',
        'docarch_Movements',
        'docarch_Volumes'
    
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('docarch'),
        array('docarchMaster', 'docarch'),
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
   var $menuItems = array(
           array(1.95, 'Документи', 'Архив', 'docarch_Movements', 'default', "ceo"),
       );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}
