<?php



/**
 * Транспорт - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   transport
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class transport_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'transport_Requests';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Транспортни операции";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
        	'transport_Requests', 
        	'transport_Shipment', 
       		'transport_Claims', 
       		'transport_Registers', 
            
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'transport';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.6, 'Логистика', 'Транспорт', 'transport_Requests', 'default', "transport, ceo"),
        );
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
        
        //инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('Transport', 'Прикачени файлове в транспорт', NULL, '300 MB', 'user', 'user');
        
        
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