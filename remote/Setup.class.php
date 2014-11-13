<?php



/**
 * class remote_Setup
 *
 * Инсталиране/Деинсталиране на remote
 *
 *
 * @category  bgerp
 * @package   remote
 * @author    Dimitar Minekov <mitko@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class remote_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * От кои други пакети зависи
     */
    public $depends = '';
    
    
    /**
     * Начален контролер на пакета за връзката в core_Packs
     */
    public $startCtr = 'remote_Hosts';
    
    
    /**
     * Начален екшън на пакета за връзката в core_Packs
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = "Отдалечена връзка с компютри";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'remote_Hosts'
        );
    

    /**
     * Роли за достъп до модула
     */
    public $roles = 'remote';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
            array(1.70, 'Система', 'Отдалечени машини', 'remote_Hosts', 'default', "remote, admin"),
        );
    
        
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
                                 
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
